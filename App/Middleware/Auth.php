<?php
namespace App\Middleware;

class Guest
{
    public function __invoke()
    {
        if (session_status() !== PHP_SESSION_ACTIVE) {
            session_start();
        }

        // If the user is logged in, redirect to the dashboard
        if (!empty($_SESSION['user_id'])) {
            header('Location: /');
            exit;
        }
    }
}

class Auth
{
    public function __invoke()
    {
        if (session_status() !== PHP_SESSION_ACTIVE) {
            session_start();
        }

        // Check if the user is logged in
        if (empty($_SESSION['user_id'])) {
            header('Location: /login');
            exit;
        }
    }
}

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

class Admin
{
    public function __invoke()
    {
        if (session_status() !== PHP_SESSION_ACTIVE) {
            session_start();
        }

        // Check if the user is logged in and is an admin
        if (empty($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
            header('Location: /login');
            exit;
        }
    }
}
