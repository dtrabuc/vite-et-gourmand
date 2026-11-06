<?php
declare(strict_types=1);

namespace App\Service;

use App\Core\Labels;
use App\Repository\QuoteRequestRepository;
use InvalidArgumentException;

final readonly class QuoteService
{
    public function __construct(
        private QuoteRequestRepository $quoteRequestRepository,
        private MailService $mailService,
        private NotificationService $notificationService,
        private PhoneValidator $phoneValidator
    ) {
    }

    public function create(array $data, ?int $userId = null): int
    {
        $email = mb_strtolower(trim((string) ($data['email'] ?? '')));
        $firstName = trim((string) ($data['first_name'] ?? ''));
        $lastName = trim((string) ($data['last_name'] ?? ''));
        $phone = trim((string) ($data['phone'] ?? ''));
        $company = trim((string) ($data['company'] ?? ''));
        $eventDate = trim((string) ($data['event_date'] ?? ''));
        $numberOfPeople = filter_var($data['number_of_people'] ?? null, FILTER_VALIDATE_INT);
        $serviceType = trim((string) ($data['service_type'] ?? ''));
        $eventLocation = trim((string) ($data['event_location'] ?? ''));
        $postalCode = trim((string) ($data['postal_code'] ?? ''));
        $details = trim((string) ($data['request_details'] ?? ''));

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new InvalidArgumentException('Adresse email invalide.');
        }

        $phoneError = $phone !== '' ? $this->phoneValidator->validate($phone) : null;

        if ($phoneError !== null) {
            throw new InvalidArgumentException($phoneError);
        }

        // le téléphone est facultatif ici, mais s'il y en a un on le stocke en E.164 comme partout
        if ($phone !== '') {
            $phone = $this->phoneValidator->normalize($phone);
        }

        if ($eventDate === '' || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $eventDate)) {
            throw new InvalidArgumentException('Date de réception invalide.');
        }

        if ($eventDate < date('Y-m-d')) {
            throw new InvalidArgumentException('La date de réception ne peut pas être passée.');
        }

        if ($numberOfPeople === false || $numberOfPeople < 1) {
            throw new InvalidArgumentException('Le nombre de personnes est invalide.');
        }

        if (!array_key_exists($serviceType, Labels::SERVICE_TYPE)) {
            throw new InvalidArgumentException('Mode de prestation invalide.');
        }

        if ($serviceType !== 'pickup' && $eventLocation === '') {
            throw new InvalidArgumentException('Le lieu de réception est requis pour une livraison ou une prestation sur place.');
        }

        $id = $this->quoteRequestRepository->create([
            'user_id' => $userId,
            'email' => $email,
            'first_name' => $firstName !== '' ? $firstName : null,
            'last_name' => $lastName !== '' ? $lastName : null,
            'phone' => $phone !== '' ? $phone : null,
            'company' => $company !== '' ? $company : null,
            'event_date' => $eventDate,
            'number_of_people' => $numberOfPeople,
            'service_type' => $serviceType,
            'event_location' => $eventLocation !== '' ? $eventLocation : null,
            'postal_code' => $postalCode !== '' ? $postalCode : null,
            'request_details' => $details !== '' ? $details : null,
        ]);

        $this->mailService->sendQuoteRequestAcknowledgement(
            $email,
            $firstName,
            [
                'id' => $id,
                'event_date' => $eventDate,
                'number_of_people' => $numberOfPeople,
                'service_type' => $serviceType,
            ]
        );

        $companyEmail = $this->mailService->companyAddress();

        if ($userId !== null) {
            $this->notificationService->notify(
                $userId,
                'quote',
                'Demande de devis enregistrée',
                'Votre demande de devis #' . $id . ' a bien été reçue. Notre équipe va l’étudier.',
                null,
                $id
            );
        }

        $this->notificationService->notifyStaff(
            'quote',
            'Nouvelle demande de devis #' . $id,
            'Une nouvelle demande de devis attend votre traitement.',
            null,
            $id
        );

        $this->mailService->sendQuoteRequestNotificationToStaff(
            $companyEmail,
            [
                'id' => $id,
                'email' => $email,
                'first_name' => $firstName,
                'last_name' => $lastName,
                'phone' => $phone,
                'company' => $company,
                'event_date' => $eventDate,
                'number_of_people' => $numberOfPeople,
                'service_type' => $serviceType,
                'event_location' => $eventLocation,
                'postal_code' => $postalCode,
                'details' => $details,
            ]
        );

        return $id;
    }

    public function findForStaff(?string $status = null): array
    {
        return $this->quoteRequestRepository->findForStaff($status);
    }

    public function updateStatus(int $id, string $status, string $reply): void
    {
        $allowed = ['new', 'in_review', 'quoted', 'accepted', 'declined', 'closed'];

        if (!in_array($status, $allowed, true)) {
            throw new InvalidArgumentException('Statut de demande invalide.');
        }

        $request = $this->quoteRequestRepository->findById($id);

        if ($request === null) {
            throw new InvalidArgumentException('Demande de devis introuvable.');
        }

        $reply = trim($reply);

        if (
            in_array($status, ['quoted', 'accepted', 'declined', 'closed'], true)
            && $reply === ''
        ) {
            throw new InvalidArgumentException('Une réponse est requise pour ce statut.');
        }

        $this->quoteRequestRepository->updateStatus($id, $status, $reply !== '' ? $reply : null);

        if ($request['user_id'] !== null) {
            $this->notificationService->notify(
                (int) $request['user_id'],
                'quote',
                'Réponse à votre demande de devis',
                'Votre demande de devis #' . $id . ' a été mise à jour : ' .
                ($reply !== '' ? $reply : (Labels::quoteStatus($status) . '.')),
                null,
                $id
            );
        }

        $this->mailService->sendQuoteRequestStatusEmail(
            (string) $request['email'],
            (string) ($request['first_name'] ?? ''),
            $id,
            $status,
            $reply
        );
    }
}
