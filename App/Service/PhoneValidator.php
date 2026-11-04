<?php
declare(strict_types=1);

namespace App\Service;

use libphonenumber\NumberParseException;
use libphonenumber\PhoneNumberFormat;
use libphonenumber\PhoneNumberUtil;
use libphonenumber\ValidationResult;

/**
 * Validation des numéros de téléphone, la même pour tout le site
 * (inscription, profil, commande, devis, compte employé).
 *
 * Le champ intl-tel-input du navigateur aide à la saisie, mais c'est ici
 * que ça se décide : on ne fait jamais confiance à ce qui arrive du client.
 */
final class PhoneValidator
{
    /** Pays acceptés (codes ISO à 2 lettres). */
    public const ALLOWED_REGIONS = ['FR'];

    /** Un numéro saisi sans "+" est lu comme un numéro français. */
    private const DEFAULT_REGION = 'FR';

    /**
     * Messages par code d'erreur, les mêmes codes que getValidationError() côté JS.
     */
    public const MESSAGES = [
        'REQUIRED' => 'Le numéro de téléphone est requis.',
        'INVALID_CHARACTERS' => 'Le numéro ne peut contenir que des chiffres, des espaces, des points, des tirets, des parenthèses et un + au début.',
        'INVALID_COUNTRY_CODE' => 'L’indicatif du pays n’est pas reconnu.',
        'TOO_SHORT' => 'Le numéro est trop court.',
        'TOO_LONG' => 'Le numéro est trop long.',
        'INVALID_LENGTH' => 'La longueur du numéro ne correspond pas au pays.',
        'IS_POSSIBLE_LOCAL_ONLY' => 'Ce numéro est incomplet : ajoutez l’indicatif régional.',
        'INVALID_NUMBER' => 'Ce numéro de téléphone n’existe pas.',
        'COUNTRY_NOT_ALLOWED' => 'Seuls les numéros français sont acceptés.',
    ];

    /**
     * @return string|null Le message d'erreur, ou null si le numéro est bon.
     */
    public function validate(string $phone): ?string
    {
        $code = $this->errorCode($phone);

        return $code === null ? null : self::MESSAGES[$code];
    }

    /**
     * Numéro au format E.164 (ex. +33612345678), c'est ce qu'on stocke en base.
     * À appeler seulement sur un numéro déjà validé.
     */
    public function normalize(string $phone): string
    {
        $phoneUtil = PhoneNumberUtil::getInstance();

        return $phoneUtil->format(
            $phoneUtil->parse(trim($phone), self::DEFAULT_REGION),
            PhoneNumberFormat::E164
        );
    }

    /**
     * Donne le code du problème, ou null si tout va bien.
     */
    private function errorCode(string $phone): ?string
    {
        $phone = trim($phone);

        if ($phone === '') {
            return 'REQUIRED';
        }

        // libphonenumber transforme les lettres en chiffres (06 12 AB…), on les refuse avant
        if (!preg_match('/^\+?[0-9\s.\-()]+$/', $phone)) {
            return 'INVALID_CHARACTERS';
        }

        $phoneUtil = PhoneNumberUtil::getInstance();

        try {
            // la région vient du numéro lui-même ; sans "+", on part sur la France
            $parsed = $phoneUtil->parse($phone, self::DEFAULT_REGION);
        } catch (NumberParseException $exception) {
            return match ($exception->getErrorType()) {
                NumberParseException::INVALID_COUNTRY_CODE => 'INVALID_COUNTRY_CODE',
                NumberParseException::TOO_SHORT_NSN, NumberParseException::TOO_SHORT_AFTER_IDD => 'TOO_SHORT',
                NumberParseException::TOO_LONG => 'TOO_LONG',
                default => 'INVALID_NUMBER',
            };
        }

        $region = $phoneUtil->getRegionCodeForNumber($parsed);

        // indicatif connu mais pays hors liste (ex. +49, +1)
        if ($region === null || !in_array($region, self::ALLOWED_REGIONS, true)) {
            return $phoneUtil->getRegionCodeForCountryCode((int) $parsed->getCountryCode()) === 'ZZ'
                ? 'INVALID_COUNTRY_CODE'
                : 'COUNTRY_NOT_ALLOWED';
        }

        // longueur d'abord, pour donner un message plus précis que "invalide"
        $reason = $phoneUtil->isPossibleNumberWithReason($parsed);

        $lengthCode = match ($reason) {
            ValidationResult::TOO_SHORT => 'TOO_SHORT',
            ValidationResult::TOO_LONG => 'TOO_LONG',
            ValidationResult::INVALID_LENGTH => 'INVALID_LENGTH',
            ValidationResult::INVALID_COUNTRY_CODE => 'INVALID_COUNTRY_CODE',
            ValidationResult::IS_POSSIBLE_LOCAL_ONLY => 'IS_POSSIBLE_LOCAL_ONLY',
            default => null,
        };

        if ($lengthCode !== null) {
            return $lengthCode;
        }

        return $phoneUtil->isValidNumber($parsed) ? null : 'INVALID_NUMBER';
    }
}
