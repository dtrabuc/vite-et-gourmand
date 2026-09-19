<?php
namespace App\Middleware;

class Guest
{
    public function __invoke(): void
    {
        if (session_status() !== PHP_SESSION_ACTIVE) {
            session_start();
        }

        if (!empty($_SESSION['user_id'])) {
            header('Location: /');
            exit;
        }
    }
}
