<?php
declare(strict_types=1);

namespace App\Controller;

use App\Core\Response;
use App\Core\Session;
use App\Core\View;
use App\Repository\OpeningHoursRepository;

abstract class BaseController
{
    protected function render(string $template, array $data = []): void
    {
        try {
            $data['openingHours'] ??= (new OpeningHoursRepository())->findAll();
        } catch (\Throwable $exception) {
            error_log('Impossible de charger les horaires : ' . $exception->getMessage());
            $data['openingHours'] = [];
        }

        $content = View::render($template, $data);

        $layoutData = array_merge($data, [
            'content' => $content,
            'title' => $data['title'] ?? $this->titleFromTemplate($template),
            'user' => Session::id(),
            'first_name' => $_SESSION['first_name'] ?? '',
            'last_name' => $_SESSION['last_name'] ?? '',
            'role' => $_SESSION['role'] ?? '',
            'csrfToken' => Session::csrfToken(),
        ]);

        echo View::render('layout/layout', $layoutData);
    }

    /**
     * Lit des champs texte de $_POST (valeur absente : chaîne vide).
     *
     * @param string[] $fields
     * @return array<string, string>
     */
    protected function postFields(array $fields): array
    {
        $values = [];

        foreach ($fields as $field) {
            $value = $_POST[$field] ?? '';
            $values[$field] = is_scalar($value) ? trim((string) $value) : '';
        }

        return $values;
    }

    protected function json(array $data, int $status = 200): never
    {
        Response::json($data, $status);
    }

    protected function redirect(string $location, int $status = 302): never
    {
        Response::redirect($location, $status);
    }

    private function titleFromTemplate(string $template): string
    {
        $titles = [
            'auth/login' => 'Connexion client',
            'auth/admin_login' => 'Connexion équipe',
            'auth/register' => 'Créer un compte',
            'auth/profile' => 'Mon profil',
            'home/index' => 'Accueil',
            'home/menus' => 'Nos menus',
            'home/menu_detail' => 'Détail du menu',
            'home/legal' => 'Mentions légales',
            'home/cgv' => 'Conditions générales de vente',
            'home/privacy' => 'Politique de confidentialité',
            'contact/index' => 'Contact',
            'quote/index' => 'Demander un devis',
            'order/new' => 'Nouvelle commande',
            'order/index' => 'Mes commandes',
            'order/confirmation' => 'Confirmation de commande',
            'admin/dashboard' => 'Tableau de bord',
            'admin/orders' => 'Gestion des commandes',
            'admin/quotes' => 'Gestion des devis',
            'admin/dishes' => 'Gestion des plats',
            'admin/menus' => 'Gestion des menus',
            'admin/customers' => 'Gestion des clients',
            'admin/employees' => 'Gestion des employés',
            'admin/opening_hours' => 'Horaires d’ouverture',
            'admin/comments' => 'Modération des avis',
            'admin/revenue' => 'Chiffre d’affaires',
        ];

        $template = trim($template, '/');
        $label = $titles[$template] ?? str_replace(['/', '_'], ' ', $template);

        return ucfirst($label) . ' - Vite & Gourmand';
    }
}