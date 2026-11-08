<?php
/**
 * Docker : applique les mots de passe DEMO_*_PASSWORD (docker-compose.yml)
 * aux comptes de démonstration créés par database/schema.sql.
 * Ne touche qu'à la base, jamais à schema.sql ni à un .env.
 */
declare(strict_types=1);

use App\Core\Application;
use App\Repository\UserRepository;
use App\Service\PasswordPolicy;

if (PHP_SAPI !== 'cli') {
    exit(1);
}

require dirname(__DIR__) . '/vendor/autoload.php';
Application::boot();

$users = new UserRepository();
$policy = new PasswordPolicy((int) config('security.password_hash_cost', 12));
$ok = true;

foreach (['ADMIN', 'EMPLOYEE', 'USER'] as $role) {
    $email = mb_strtolower(trim((string) ($_ENV['DEMO_' . $role . '_EMAIL'] ?? '')));
    $password = (string) ($_ENV['DEMO_' . $role . '_PASSWORD'] ?? '');

    if ($email === '' || $password === '') {
        continue;
    }

    $errors = $policy->validate($password);
    if ($errors !== []) {
        fwrite(STDERR, "DEMO_{$role} : mot de passe refusé (" . implode(' ', $errors) . ")\n");
        $ok = false;
        continue;
    }

    try {
        $user = $users->findByEmail($email);
    } catch (Throwable $e) {
        fwrite(STDERR, "Base indisponible : " . $e->getMessage() . "\n");
        exit(1);
    }

    if ($user === null) {
        fwrite(STDERR, "DEMO_{$role} : compte {$email} introuvable (schema.sql pas encore importé ?)\n");
        $ok = false;
        continue;
    }

    $users->updatePassword($user->getId(), $policy->hash($password));
    $users->updateFailedAttempts($user->getId(), 0, null);
    echo "DEMO_{$role} ({$email}) : mot de passe appliqué.\n";
}

exit($ok ? 0 : 1);
