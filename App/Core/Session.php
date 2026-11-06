<?php
declare(strict_types=1);

namespace App\Core;

final class Session
{
    /** Expire la session après une période d'inactivité et renouvelle son cookie. */
    public static function expireAfterInactivity(int $lifetimeSeconds): void
    {
        $now = time();
        $lastActivity = $_SESSION['_last_activity_at'] ?? null;

        if (is_numeric($lastActivity) && $now - (int) $lastActivity >= $lifetimeSeconds) {
            $_SESSION = [];
            session_regenerate_id(true);
        }

        $_SESSION['_last_activity_at'] = $now;

        if (headers_sent()) {
            return;
        }

        $params = session_get_cookie_params();
        setcookie(session_name(), session_id(), [
            'expires' => $now + $lifetimeSeconds,
            'path' => $params['path'] ?: '/',
            'domain' => $params['domain'],
            'secure' => $params['secure'],
            'httponly' => $params['httponly'],
            'samesite' => $params['samesite'] ?? 'Lax',
        ]);
    }

    public static function id(): ?int
    {
        $id = $_SESSION['user_id'] ?? null;

        return is_numeric($id) && (int) $id > 0
            ? (int) $id
            : null;
    }

    public static function role(): ?string
    {
        $role = $_SESSION['role'] ?? null;

        return is_string($role) && $role !== ''
            ? $role
            : null;
    }

    public static function login(
        int $userId,
        string $role,
        array $identity = []
    ): void {
        session_regenerate_id(true);

        $_SESSION['user_id'] = $userId;
        $_SESSION['role'] = $role;

        foreach ($identity as $key => $value) {
            $_SESSION[$key] = $value;
        }

        self::csrfToken();
    }

    public static function logout(): void
    {
        $_SESSION = [];

        if (ini_get('session.use_cookies')) {
            $params = session_get_cookie_params();

            setcookie(
                session_name(),
                '',
                [
                    'expires' => time() - 42000,
                    'path' => $params['path'],
                    'domain' => $params['domain'],
                    'secure' => $params['secure'],
                    'httponly' => $params['httponly'],
                    'samesite' => $params['samesite'] ?? 'Lax',
                ]
            );
        }

        session_destroy();
    }

    public static function csrfToken(): string
    {
        if (
            !isset($_SESSION['csrf_token'])
            || !is_string($_SESSION['csrf_token'])
            || strlen($_SESSION['csrf_token']) !== 64
        ) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }

        return $_SESSION['csrf_token'];
    }

    /**
     * Garde l'URL demandée par un visiteur, pour l'y renvoyer après la connexion.
     * Une valeur qui n'est pas un chemin interne est ignorée.
     */
    public static function rememberReturnUrl(string $url): void
    {
        if (self::isSafeReturnUrl($url)) {
            $_SESSION['return_url'] = $url;
        }
    }

    /**
     * URL de retour gardée, utilisable une seule fois ; sinon la destination par défaut.
     */
    public static function pullReturnUrl(string $default): string
    {
        $url = $_SESSION['return_url'] ?? null;
        unset($_SESSION['return_url']);

        return is_string($url) && self::isSafeReturnUrl($url) ? $url : $default;
    }

    // anti-redirection ouverte : chemin interne qui commence par "/" mais pas par "//" ni "/\"
    // (le navigateur les lit comme une autre adresse), sans caractère de contrôle
    public static function isSafeReturnUrl(string $url): bool
    {
        return str_starts_with($url, '/')
            && !str_starts_with($url, '//')
            && !str_starts_with($url, '/\\')
            && preg_match('/[\x00-\x1F\x7F]/', $url) !== 1;
    }

    public static function flash(string $key, mixed $value): void
    {
        $_SESSION['_flash'][$key] = $value;
    }

    public static function pullFlash(
        string $key,
        mixed $default = null
    ): mixed {
        $value = $_SESSION['_flash'][$key] ?? $default;
        unset($_SESSION['_flash'][$key]);
        return $value;
    }
}
