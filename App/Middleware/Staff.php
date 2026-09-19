<?php
namespace App\Middleware;

class Staff
{
    public function __invoke(): void
    {
        if (session_status() !== PHP_SESSION_ACTIVE) {
            session_start();
        }

        if (empty($_SESSION['user_id']) || !in_array($_SESSION['role'] ?? '', ['employee', 'admin'], true)) {
            header('Location: /login');
            exit;
        }
    }
}
