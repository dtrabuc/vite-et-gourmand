<?php
declare(strict_types=1);

namespace App\Middleware;

use App\Core\Response;
use App\Core\Session;

final readonly class Staff
{
    public function __construct(
        private Auth $auth
    ) {
    }

    public function __invoke(): void
    {
        ($this->auth)();

        if (!in_array(Session::role(), ['employee', 'admin'], true)) {
            Response::redirect('/admin/login');
        }
    }
}
