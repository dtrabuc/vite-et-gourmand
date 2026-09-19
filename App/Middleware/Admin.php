<?php
namespace App\Middleware;

class Admin
{
    public function __invoke(): void
    {
        if (session_status() !== PHP_SESSION_ACTIVE) {
            session_start();
        }

        if (empty($_SESSION['user_id']) || ($_SESSION['role'] ?? '') !== 'admin') {
            header('Location: /login');
            exit;
        }
    }
}
