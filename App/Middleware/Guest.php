<?php
declare(strict_types=1);

namespace App\Middleware;

use App\Core\Response;
use App\Core\Session;

final class Guest
{
    public function __invoke(): void
    {
        $role = Session::role();

        if (Session::id() === null) {
            return;
        }

        Response::redirect(
            match ($role) {
                'admin' => '/admin/dashboard',
                'employee' => '/admin/orders',
                default => '/',
            }
        );
    }
}
