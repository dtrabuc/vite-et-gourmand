<?php
declare(strict_types=1);

namespace App\Service;

/**
 * Politique unique des mots de passe : règles de robustesse et hachage bcrypt
 * au coût défini par PASSWORD_HASH_COST (config security.password_hash_cost).
 */
final readonly class PasswordPolicy
{
    public const MIN_LENGTH = 10;

    public function __construct(private int $cost = 12)
    {
    }

    /**
     * @return string[] Règles non respectées (tableau vide si le mot de passe est valide).
     */
    public function validate(string $password): array
    {
        $errors = [];

        if (mb_strlen($password) < self::MIN_LENGTH) {
            $errors[] = 'Le mot de passe doit contenir au moins ' . self::MIN_LENGTH . ' caractères.';
        }
        if (!preg_match('/[A-Z]/', $password)) {
            $errors[] = 'Le mot de passe doit contenir au moins une majuscule.';
        }
        if (!preg_match('/[a-z]/', $password)) {
            $errors[] = 'Le mot de passe doit contenir au moins une minuscule.';
        }
        if (!preg_match('/[0-9]/', $password)) {
            $errors[] = 'Le mot de passe doit contenir au moins un chiffre.';
        }
        if (!preg_match('/[^A-Za-z0-9]/', $password)) {
            $errors[] = 'Le mot de passe doit contenir au moins un caractère spécial.';
        }

        return $errors;
    }

    public function hash(string $password): string
    {
        return password_hash($password, PASSWORD_BCRYPT, ['cost' => $this->cost()]);
    }

    public function needsRehash(string $hash): bool
    {
        return password_needs_rehash($hash, PASSWORD_BCRYPT, ['cost' => $this->cost()]);
    }

    /**
     * Coût borné aux valeurs acceptées par bcrypt (4 à 31).
     */
    private function cost(): int
    {
        return max(4, min(31, $this->cost));
    }
}
