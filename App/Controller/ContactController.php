<?php
declare(strict_types=1);

namespace App\Controller;

use App\Service\MailService;
use App\Core\Session;

final class ContactController extends BaseController
{
    public function __construct(
        private readonly MailService $mailService
    ) {
    }

    public function index(): void
    {
        $this->render('contact/index', [
            'errors' => (array) Session::pullFlash('contact_errors', []),
            'old' => (array) Session::pullFlash('contact_old_input', []),
        ]);
    }

    public function send(): void
    {
        $email = trim((string) ($_POST['email'] ?? ''));
        $subject = trim((string) ($_POST['subject'] ?? ''));
        $message = trim((string) ($_POST['message'] ?? ''));
        $errors = [];

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors['email'] = 'Adresse email invalide.';
        }

        if ($subject === '' || mb_strlen($subject) > 150) {
            $errors['subject'] = 'Le titre est requis et doit contenir au maximum 150 caractères.';
        }

        if ($message === '') {
            $errors['message'] = 'La description est requise.';
        }

        if ($errors !== []) {
            Session::flash('contact_errors', $errors);
            Session::flash('contact_old_input', ['email' => $email, 'subject' => $subject, 'message' => $message]);
            $this->redirect('/contact');
        }

        // le message part directement à l'adresse de l'entreprise (MAIL_TO_ADDRESS du .env),
        // avec l'adresse du visiteur en « Répondre à » : l'équipe répond depuis sa messagerie
        $companyEmail = $this->mailService->companyAddress();
        $body = "Message reçu depuis le formulaire de contact.\n\n"
            . "Email : {$email}\nTitre : {$subject}\n\n{$message}";

        if ($this->mailService->send($companyEmail, 'Formulaire de contact : ' . $subject, $body, false, $email)) {
            Session::flash('contact_success', 'Votre message a bien été envoyé. Nous vous répondrons par e-mail.');
        } else {
            // sans enregistrement en base, un échec d'envoi doit être visible : on garde la saisie
            error_log('Impossible d’envoyer le message de contact à ' . $companyEmail);
            Session::flash('contact_errors', ['general' => 'Votre message n’a pas pu être envoyé. Merci de réessayer plus tard.']);
            Session::flash('contact_old_input', ['email' => $email, 'subject' => $subject, 'message' => $message]);
        }

        $this->redirect('/contact');
    }
}
