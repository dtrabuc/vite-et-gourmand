<?php
declare(strict_types=1);

namespace App\Controller;

use App\Core\Session;
use App\Service\PrivacyService;
use InvalidArgumentException;

/** Droits RGPD depuis le profil : télécharger ses données, supprimer son compte. */
final class PrivacyController extends BaseController
{
    public function __construct(
        private readonly PrivacyService $privacyService
    ) {
    }

    public function export(): never
    {
        $userId = Session::id();
        if ($userId === null) {
            $this->redirect('/login');
        }

        $json = json_encode(
            $this->privacyService->export($userId),
            JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR
        );

        header('Content-Type: application/json; charset=utf-8');
        header('Content-Disposition: attachment; filename="mes-donnees-vite-et-gourmand.json"');
        header('Cache-Control: no-store');
        echo $json;
        exit;
    }

    public function delete(): never
    {
        $userId = Session::id();
        if ($userId === null) {
            $this->redirect('/login');
        }

        if (($_POST['confirm_delete'] ?? '') !== '1') {
            Session::flash('privacy_error', 'Cochez la case de confirmation pour supprimer votre compte.');
            $this->redirect('/profile');
        }

        try {
            $this->privacyService->deleteAccount($userId, (string) ($_POST['current_password'] ?? ''));
        } catch (InvalidArgumentException $exception) {
            Session::flash('privacy_error', $exception->getMessage());
            $this->redirect('/profile');
        }

        Session::logout();
        // nouvelle session vide, juste pour afficher le message de confirmation
        if (session_status() !== PHP_SESSION_ACTIVE) {
            session_start();
        }
        session_regenerate_id(true);
        Session::flash('register_success', 'Votre compte a été supprimé et vos données personnelles effacées.');
        $this->redirect('/');
    }
}
