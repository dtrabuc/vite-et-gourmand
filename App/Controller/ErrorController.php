<?php
declare(strict_types=1);

namespace App\Controller;

use App\Core\Request;

/**
 * Pages d'erreur : HTML pour les pages du site, JSON pour les appels d'API.
 */
final class ErrorController extends BaseController
{
    public function notFound(): void
    {
        $this->respond(404, 'Page introuvable.', 'errors/404', 'Page introuvable - Vite & Gourmand');
    }

    public function serverError(): void
    {
        $this->respond(500, 'Une erreur interne est survenue.', 'errors/500', 'Erreur interne - Vite & Gourmand');
    }

    private function respond(int $status, string $message, string $template, string $title): void
    {
        if (Request::wantsJson()) {
            $this->json(['success' => false, 'error' => $message], $status);
        }

        if (!headers_sent()) {
            http_response_code($status);
        }

        $this->render($template, ['title' => $title]);
    }
}
