<?php
namespace App\Service;

class MailService
{
    private string $fromAddress;
    private string $fromName;

    public function __construct(string $fromAddress = 'noreply@viteetgourmand.com', string $fromName = 'Vite & Gourmand')
    {
        $this->fromAddress = $fromAddress;
        $this->fromName = $fromName;
    }

    /**
     * Send an email.
     * @param string $to
     * @param string $subject
     * @param string $body
     * @param bool $isHTML
     * @return bool True if email was sent successfully, false otherwise.
     */
    public function send(string $to, string $subject, string $body, bool $isHTML = false): bool
    {
        $headers = "From: {$this->fromName} <{$this->fromAddress}>\r\n";
        if ($isHTML) {
            $headers .= "Content-Type: text/html; charset=UTF-8\r\n";
        } else {
            $headers .= "Content-Type: text/plain; charset=UTF-8\r\n";
        }

        // Additional headers
        $headers .= "MIME-Version: 1.0\r\n";
        $headers .= "X-Mailer: PHP/" . phpversion() . "\r\n";

        return mail($to, $subject, $body, $headers);
    }

    public function sendWelcomeEmail(string $to, string $firstName): bool
    {
        $subject = 'Bienvenue chez Vite & Gourmand !';
        $body = <<<HTML
Bonjour {$firstName},

Bienvenue chez Vite & Gourmand ! Nous sommes ravis de vous compter parmi nos utilisateurs.

Vous pouvez dès maintenant vous connecter à votre espace personnel et commencer à passer des commandes.

À très bientôt,
L'équipe Vite & Gourmand
HTML;

        return $this->send($to, $subject, $body);
    }

    public function sendPasswordResetEmail(string $to, string $firstName, string $resetUrl): bool
    {
        $subject = 'Réinitialisation de votre mot de passe';
        $body = <<<HTML
Bonjour {$firstName},

Vous avez demandé à réinitialiser votre mot de passe. Veuillez cliquer sur le lien ci-dessous pour choisir un nouveau mot de passe :

{$resetUrl}

Ce lien est valable pour une durée limitée. Si vous n'êtes pas à l'origine de cette demande, veuillez ignorer cet email.

À bientôt,
L'équipe Vite & Gourmand
HTML;

        return $this->send($to, $subject, $body);
    }

    public function sendReviewInvitationEmail(string $to, string $firstName, int $orderId): bool
    {
        return $this->send(
            $to,
            'Votre commande est terminée : donnez votre avis',
            "Bonjour {$firstName},\\n\\nVotre commande #{$orderId} est terminée. Vous pouvez maintenant vous connecter à votre espace client pour laisser une note de 1 à 5 et un commentaire.\\n\\nÀ bientôt,\\nL'équipe Vite & Gourmand"
        );
    }

    public function sendEquipmentReturnNoticeEmail(string $to, string $firstName, int $orderId): bool
    {
        return $this->send(
            $to,
            'Retour du matériel prêté — commande #' . $orderId,
            "Bonjour {$firstName},\\n\\nLe matériel prêté pour la commande #{$orderId} doit être restitué. À défaut de restitution sous 10 jours ouvrés, des frais de 600 euros sont prévus selon les conditions générales de vente.\\n\\nPour organiser le retour, veuillez prendre contact avec Vite & Gourmand."
        );
    }

    public function sendOrderConfirmationEmail(string $to, string $firstName, array $orderDetails): bool
    {
        $subject = 'Confirmation de votre commande';
        $body = <<<HTML
Bonjour {$firstName},

Merci pour votre commande ! Voici les détails :

Numéro de commande : {$orderDetails['id']}
Date : {$orderDetails['order_date']}
Menu : {$orderDetails['menu_title']}
Nombre de personnes : {$orderDetails['number_of_people']}
Prix du menu : {$orderDetails['menu_price']} €
Coût de livraison : {$orderDetails['delivery_cost']} €
Total : {$orderDetails['total_price']} €

Nous vous contacterons bientôt pour confirmer les détails de la livraison.

À bientôt,
L'équipe Vite & Gourmand
HTML;

        return $this->send($to, $subject, $body);
    }
}