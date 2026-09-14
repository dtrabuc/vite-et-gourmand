<?php
namespace Src\Middleware;

class Guest
{
    public function __invoke()
    {
        session_start();

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
        session_start();

        // Check if the user is logged in
        if (empty($_SESSION['user_id'])) {
            header('Location: /login');
            exit;
        }
    }
}

class Admin
{
    public function __invoke()
    {
        session_start();

        // Check if the user is logged in and is an admin
        if (empty($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
            header('Location: /login');
            exit;
        }
    }
}
