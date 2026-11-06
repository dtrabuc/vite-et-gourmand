<?php
declare(strict_types=1);

namespace App\Core;

final class Response
{
    public static function redirect(string $location, int $status = 302): never
    {
        http_response_code($status);
        header('Location: ' . $location);
        exit;
    }

    public static function json(array $data, int $status = 200): never
    {
        http_response_code($status);
        header('Content-Type: application/json; charset=UTF-8');
        echo json_encode(
            $data,
            JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR
        );
        exit;
    }
}