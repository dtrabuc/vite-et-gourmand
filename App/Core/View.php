<?php
declare(strict_types=1);

namespace App\Core;

use RuntimeException;

final class View
{
    public static function render(string $template, array $data = []): string
    {
        $path = dirname(__DIR__) . '/View/' . trim($template, '/') . '.php';

        if (!is_file($path)) {
            throw new RuntimeException('Vue introuvable : ' . $template);
        }

        $escape = static fn (mixed $value): string =>
            htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');

        $csrfToken = Session::csrfToken();

        extract($data, EXTR_SKIP);

        ob_start();
        require $path;

        return (string) ob_get_clean();
    }
}