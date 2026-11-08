<?php
declare(strict_types=1);

namespace App\Service;

use App\Core\Labels;
use PHPMailer\PHPMailer\Exception as MailerException;
use PHPMailer\PHPMailer\PHPMailer;

/**
 * Point d'envoi unique des e-mails de l'application.
 *
 * Transport SMTP via PHPMailer, configuré par les variables MAIL_* du .env
 * (voir config/app.php, clé "mail").
 */
class MailService
{
    public function __construct(private readonly array $config)
    {
    }

    /**
     * Adresse de l'entreprise qui reçoit les messages de contact et les alertes
     * (MAIL_TO_ADDRESS, à défaut MAIL_FROM_ADDRESS).
     */
    public function companyAddress(): string
    {
        $to = trim((string) ($this->config['to_address'] ?? ''));

        return filter_var($to, FILTER_VALIDATE_EMAIL) ? $to : $this->fromAddress();
    }

    /**
     * Envoie un e-mail.
     *
     * @param string|null $replyTo adresse utilisée quand le destinataire clique sur « Répondre »
     * @return bool true si le serveur SMTP a accepté le message.
     */
    public function send(string $to, string $subject, string $body, bool $isHTML = false, ?string $replyTo = null): bool
    {
        $host = trim((string) ($this->config['host'] ?? ''));

        if (($this->config['driver'] ?? 'smtp') !== 'smtp' || $host === '') {
            error_log('Email non envoyé à ' . $to . ' : SMTP non configuré (MAIL_DRIVER=smtp et MAIL_HOST requis).');
            return false;
        }

        $mailer = new PHPMailer(true);

        try {
            $mailer->isSMTP();
            $mailer->Host = $host;
            $mailer->Port = (int) ($this->config['port'] ?? 587);
            $mailer->Timeout = 10;
            $mailer->CharSet = PHPMailer::CHARSET_UTF8;

            $username = (string) ($this->config['username'] ?? '');
            if ($username !== '') {
                $mailer->SMTPAuth = true;
                $mailer->Username = $username;
                $mailer->Password = (string) ($this->config['password'] ?? '');
            }

            switch (strtolower((string) ($this->config['encryption'] ?? 'tls'))) {
                case 'ssl':
                case 'smtps':
                    $mailer->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
                    break;
                case 'tls':
                case 'starttls':
                    $mailer->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
                    break;
                default:
                    // "none" ou vide : serveur local de test (Mailpit, MailHog…).
                    $mailer->SMTPSecure = '';
                    $mailer->SMTPAutoTLS = false;
            }

            $mailer->setFrom($this->fromAddress(), (string) ($this->config['from_name'] ?? 'Vite & Gourmand'));
            $mailer->addAddress($to);

            if ($replyTo !== null && filter_var($replyTo, FILTER_VALIDATE_EMAIL)) {
                $mailer->addReplyTo($replyTo);
            }
            $mailer->Subject = $subject;
            $mailer->isHTML($isHTML);
            $mailer->Body = $body;

            if ($isHTML) {
                $mailer->AltBody = trim(html_entity_decode(
                    strip_tags(preg_replace('/<br\s*\/?>/i', "\n", $body) ?? $body),
                    ENT_QUOTES | ENT_HTML5,
                    'UTF-8'
                ));
            }

            return $mailer->send();
        } catch (MailerException $exception) {
            error_log('Email non envoyé à ' . $to . ' : ' . $mailer->ErrorInfo);
            return false;
        }
    }

    public function sendWelcomeEmail(string $to, string $firstName): bool
    {
        $body = <<<TEXT
Bonjour {$firstName},

Bienvenue chez Vite & Gourmand ! Votre compte client est créé avec l'adresse {$to}.
Vous pouvez dès maintenant vous connecter pour consulter nos menus et passer commande.

Si vous n'êtes pas à l'origine de cette inscription, contactez-nous.

À bientôt,
L'équipe Vite & Gourmand
TEXT;
        return $this->send($to, 'Bienvenue chez Vite & Gourmand', $body);
    }

    /**
     * Informe un employé de la création de son compte. Le mot de passe n'est
     * jamais transmis par e-mail : l'employé doit le demander à l'administrateur.
     */
    public function sendEmployeeAccountEmail(string $to, string $firstName, string $loginUrl): bool
    {
        $body = <<<TEXT
Bonjour {$firstName},

Un compte employé Vite & Gourmand vient d'être créé pour vous.

Identifiant de connexion : {$to}
Espace équipe : {$loginUrl}

Pour des raisons de sécurité, votre mot de passe ne vous est pas envoyé par e-mail.
Rapprochez-vous de l'administrateur pour l'obtenir.

À bientôt,
L'équipe Vite & Gourmand
TEXT;

        return $this->send($to, 'Création de votre compte employé', $body);
    }

    public function sendPasswordResetEmail(string $to, string $firstName, string $resetUrl): bool
    {
        $subject = 'Réinitialisation de votre mot de passe';
        $body = <<<TEXT
Bonjour {$firstName},

Vous avez demandé à réinitialiser votre mot de passe. Veuillez cliquer sur le lien ci-dessous pour choisir un nouveau mot de passe :

{$resetUrl}

Ce lien est valable pour une durée limitée. Si vous n'êtes pas à l'origine de cette demande, veuillez ignorer cet email.

À bientôt,
L'équipe Vite & Gourmand
TEXT;

        return $this->send($to, $subject, $body);
    }

    public function sendReviewInvitationEmail(string $to, string $firstName, int $orderId): bool
    {
        return $this->send(
            $to,
            'Votre commande est terminée : donnez votre avis',
            "Bonjour {$firstName},\n\nVotre commande #{$orderId} est terminée. Vous pouvez maintenant vous connecter à votre espace client pour laisser une note de 1 à 5 et un commentaire.\n\nÀ bientôt,\nL'équipe Vite & Gourmand"
        );
    }

    public function sendEquipmentReturnNoticeEmail(string $to, string $firstName, int $orderId): bool
    {
        return $this->send(
            $to,
            'Retour du matériel prêté — commande #' . $orderId,
            "Bonjour {$firstName},\n\nLe matériel prêté pour la commande #{$orderId} doit être restitué. À défaut de restitution sous 10 jours ouvrés, des frais de 600 euros sont prévus selon les conditions générales de vente.\n\nPour organiser le retour, veuillez prendre contact avec Vite & Gourmand."
        );
    }

    public function sendQuoteRequestAcknowledgement(
        string $to,
        string $firstName,
        array $details
    ): bool {
        $serviceLabel = Labels::serviceType((string) ($details['service_type'] ?? ''));

        $body = <<<TEXT
Bonjour {$firstName},

Nous avons bien reçu votre demande de devis grand événement n°{$details['id']}.

Date : {$details['event_date']}
Nombre de personnes : {$details['number_of_people']}
Prestation : {$serviceLabel}

Notre équipe va étudier votre demande et vous répondre par email.

À bientôt,
L'équipe Vite & Gourmand
TEXT;

        return $this->send($to, 'Accusé de réception de votre demande de devis', $body);
    }

    public function sendQuoteRequestNotificationToStaff(
        string $to,
        array $details
    ): bool {
        $serviceLabel = Labels::serviceType((string) ($details['service_type'] ?? ''));

        $body = <<<TEXT
Nouvelle demande de devis grand événement #{$details['id']}

Client : {$details['first_name']} {$details['last_name']}
Email : {$details['email']}
Téléphone : {$details['phone']}
Société : {$details['company']}

Date : {$details['event_date']}
Personnes : {$details['number_of_people']}
Prestation : {$serviceLabel}
Lieu : {$details['event_location']}
Code postal : {$details['postal_code']}

Besoin :
{$details['details']}

Connectez-vous à l'espace équipe pour traiter la demande.
TEXT;

        return $this->send($to, 'Nouvelle demande de devis #' . $details['id'], $body);
    }

    public function sendQuoteRequestStatusEmail(
        string $to,
        string $firstName,
        int $requestId,
        string $status,
        string $reply
    ): bool {
        $statusLabel = Labels::quoteStatus($status);

        $body = <<<TEXT
Bonjour {$firstName},

Concernant votre demande de devis #{$requestId}, son statut est désormais :
{$statusLabel}

Réponse de l'équipe :
{$reply}

À bientôt,
L'équipe Vite & Gourmand
TEXT;

        return $this->send($to, 'Mise à jour de votre demande de devis #' . $requestId, $body);
    }

    public function sendOrderNotificationToStaff(string $to, array $details): bool
    {
        $serviceLabel = Labels::serviceType((string) ($details['service_type'] ?? ''));
        $money = static fn (mixed $amount): string => MenuPricing::format((float) $amount);
        $choices = implode("\n", array_map(static fn (string $l): string => '- ' . $l, $details['composition'] ?? []));

        $body = <<<TEXT
Nouvelle commande #{$details['id']}

Client : {$details['first_name']} {$details['last_name']}
Email : {$details['email']}
Menu : {$details['menu_title']}
Nombre de personnes : {$details['number_of_people']}
Prestation : {$serviceLabel}
Date : {$details['delivery_date']}
Heure : {$details['delivery_time']}

Adresse : {$details['delivery_address']}
Ville : {$details['delivery_city']}
Code postal : {$details['delivery_postal_code']}
Instructions : {$details['delivery_instructions']}

Choix du client :
{$choices}
Précisions cuisine : {$details['customization']}
Paiement : Espèces sur place
Total : {$money($details['total_price'])}

Connectez-vous à l’espace équipe pour traiter la commande.
TEXT;

        return $this->send($to, 'Nouvelle commande #' . $details['id'], $body);
    }

    public function sendOrderConfirmationEmail(string $to, string $firstName, array $orderDetails): bool
    {
        $money = static fn (mixed $amount): string => MenuPricing::format((float) $amount);
        $people = (int) $orderDetails['number_of_people'];
        $menuLine = $people . ' × ' . $money($orderDetails['per_person'] ?? 0);
        if ((float) ($orderDetails['discount_rate'] ?? 0) > 0) {
            $menuLine .= ', remise de 10 % incluse';
        }
        if (($orderDetails['service_type'] ?? '') !== 'delivery') {
            $delivery = 'aucune (retrait ou repas sur place)';
        } elseif (!empty($orderDetails['delivery_cost_pending'])) {
            $delivery = 'à confirmer par notre équipe (adresse saisie manuellement), nous vous appellerons';
        } else {
            $delivery = (float) $orderDetails['delivery_cost'] > 0 ? $money($orderDetails['delivery_cost']) : 'offerte';
            if (($orderDetails['delivery_zone'] ?? '') === 'outside') {
                $delivery .= ' (livraison hors zone acceptée)';
            }
        }
        $firstName = mb_convert_case(trim($firstName), MB_CASE_TITLE, 'UTF-8');
        $serviceLabel = Labels::serviceType((string) ($orderDetails['service_type'] ?? ''));
        $orderDate = self::frenchDate((string) $orderDetails['order_date'], true);
        $choices = implode("\n", array_map(static fn (string $l): string => '- ' . $l, $orderDetails['composition'] ?? []));
        $choices = $choices !== '' ? "\nVos choix :\n" . $choices . "\n" : '';
        $notes = trim((string) ($orderDetails['customization'] ?? ''));
        $notes = $notes !== '' ? 'Précisions pour la cuisine : ' . $notes . "\n" : '';
        $serviceDate = self::frenchDate((string) ($orderDetails['delivery_date'] ?? '') . ' ' . (string) ($orderDetails['delivery_time'] ?? ''), true);

        $body = <<<TEXT
Bonjour {$firstName},

Merci pour votre commande ! Voici le récapitulatif :

Commande n° {$orderDetails['id']} du {$orderDate}
Menu : {$orderDetails['menu_title']}
Nombre de personnes : {$people}
Prestation : {$serviceLabel}, le {$serviceDate}
{$choices}{$notes}
Prix du menu : {$money($orderDetails['menu_price'])} ({$menuLine})
Livraison : {$delivery}
Total à régler en espèces : {$money($orderDetails['total_price'])}

Prochaine étape : notre équipe vérifie votre commande et l'accepte, en général sous 24 h.
Vous recevrez un e-mail et une notification sur le site à chaque étape (acceptée, en préparation, en livraison…).
Modification ou annulation : depuis « Mes commandes », tant que la commande n'est pas acceptée,
dans un délai de 15 min (prestation le jour même), 24 h (le lendemain) ou 48 h (plus tard).
Suivi : {$this->siteUrl()}/orders

À bientôt,
L'équipe Vite & Gourmand
TEXT;

        return $this->send($to, 'Confirmation de votre commande n° ' . $orderDetails['id'], $body);
    }

    /** Changement d'état d'une commande (acceptée, en préparation, en livraison, livrée, annulée). */
    public function sendOrderStatusEmail(string $to, string $firstName, int $orderId, string $title, string $message): bool
    {
        $firstName = mb_convert_case(trim($firstName), MB_CASE_TITLE, 'UTF-8');
        $body = <<<TEXT
Bonjour {$firstName},

{$message}

Suivre la commande : {$this->siteUrl()}/orders

À bientôt,
L'équipe Vite & Gourmand
TEXT;

        return $this->send($to, $title, $body);
    }

    private static function frenchDate(string $value, bool $withTime): string
    {
        return french_date($value, $withTime);
    }

    /** Adresse publique du site (APP_URL), sans barre finale. */
    private function siteUrl(): string
    {
        return rtrim((string) config('app.url', ''), '/');
    }

    private function fromAddress(): string
    {
        return (string) ($this->config['from_address'] ?? 'noreply@viteetgourmand.com');
    }
}
