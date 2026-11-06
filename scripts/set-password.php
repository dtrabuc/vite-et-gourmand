<?php
/**
 * Outil en ligne de commande : vérifier ou changer le mot de passe d'un compte.
 *
 * - La saisie est masquée (une étoile par caractère) et ne passe jamais par la
 *   commande : aucun souci avec $, !, # ou & interprétés par PowerShell ou bash.
 * - Le hash est fait par PasswordPolicy, comme dans l'appli (bcrypt).
 * - Les comptes de démonstration sont décrits dans le .env (DEMO_*_EMAIL et
 *   DEMO_*_PASSWORD). Pour eux, un changement met à jour en même temps :
 *     1. la base MariaDB,
 *     2. le hash dans database/schema.sql (pour qu'un réimport garde le bon mot de passe),
 *     3. le mot de passe dans le .env.
 *
 * Utilisation (depuis la racine du projet) :
 *   php scripts/set-password.php <email>           change le mot de passe du compte
 *   php scripts/set-password.php <email> --check   teste un mot de passe sans rien modifier
 *   php scripts/set-password.php --sync            applique les mots de passe DEMO_* du .env
 *                                                  à MariaDB et à database/schema.sql
 *
 * Changer un mot de passe remet aussi à zéro les échecs de connexion et le verrou du compte.
 */
declare(strict_types=1);

use App\Core\Application;
use App\Repository\UserRepository;
use App\Service\PasswordPolicy;

if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}

require dirname(__DIR__) . '/vendor/autoload.php';

// charge le .env et la config, comme pour une requête web
Application::boot();

const ENV_FILE = __DIR__ . '/../.env';
const SCHEMA_FILE = __DIR__ . '/../database/schema.sql';
const DEMO_ROLES = ['ADMIN', 'EMPLOYEE', 'USER'];

// base injoignable : message clair plutôt qu'une trace d'erreur
try {
    App\Core\Database::pdo();
} catch (Throwable) {
    fwrite(STDERR, 'Connexion à MariaDB impossible (' . databaseLabel() . ").\n"
        . "Vérifie que le serveur MariaDB tourne et les variables DB_* du .env.\n");
    exit(1);
}

$users = new UserRepository();
$policy = new PasswordPolicy((int) config('security.password_hash_cost', 12));

$args = array_slice($argv, 1);
$checkOnly = in_array('--check', $args, true);
$sync = in_array('--sync', $args, true);
$email = '';

foreach ($args as $arg) {
    if (!str_starts_with($arg, '--')) {
        $email = mb_strtolower(trim($arg));
    }
}

if ($sync) {
    exit(syncDemoAccounts($users, $policy));
}

if ($email === '') {
    fwrite(STDERR, "Usage :\n"
        . "  php scripts/set-password.php <email>           changer le mot de passe\n"
        . "  php scripts/set-password.php <email> --check   tester un mot de passe\n"
        . "  php scripts/set-password.php --sync            appliquer les mots de passe DEMO_* du .env\n");
    exit(1);
}

$user = $users->findByEmail($email);

if ($user === null) {
    fwrite(STDERR, "Aucun compte avec l'e-mail {$email} dans la base " . databaseLabel() . ".\n");
    exit(1);
}

$lockedUntil = $user->getLockedUntil();
$isLocked = $lockedUntil !== null && $lockedUntil->getTimestamp() > time();
$demoRole = demoRoleFor($email);

fwrite(STDOUT, sprintf(
    "Compte %s (id %d, rôle %s%s) : %s, %d échec(s) de connexion%s.\n",
    $email,
    $user->getId(),
    $user->getRole(),
    $demoRole !== null ? ', compte de démonstration du .env' : '',
    $users->isActive($user->getId()) ? 'actif' : 'DÉSACTIVÉ',
    $user->getFailedAttempts(),
    $isLocked ? ', VERROUILLÉ jusqu’à ' . $lockedUntil->format('H:i:s') : ''
));

if ($checkOnly) {
    // simple vérification : on ne touche ni au hash ni aux compteurs
    $password = readHidden('Mot de passe à tester');
    warnSpaces($password);
    $ok = password_verify($password, $user->getPasswordHash());
    fwrite(STDOUT, $ok
        ? "OK : ce mot de passe correspond au compte.\n"
        : "ÉCHEC : ce mot de passe ne correspond pas au hash enregistré.\n");
    exit($ok ? 0 : 2);
}

$password = readHidden('Nouveau mot de passe');
warnSpaces($password);
$confirm = readHidden('Confirmer le mot de passe');

if ($password !== $confirm) {
    fwrite(STDERR, "Les deux saisies sont différentes, rien n'a été modifié.\n");
    exit(1);
}

$errors = passwordErrors($policy, $password, $demoRole !== null);

if ($errors !== []) {
    fwrite(STDERR, "Mot de passe refusé :\n  - " . implode("\n  - ", $errors) . "\n");
    exit(1);
}

$hash = $policy->hash($password);

// 1. base MariaDB
$users->updatePassword($user->getId(), $hash);
$users->updateFailedAttempts($user->getId(), 0, null);

$saved = $users->findByEmail($email);

if ($saved === null || !password_verify($password, $saved->getPasswordHash())) {
    fwrite(STDERR, "Le mot de passe a été écrit mais la vérification a échoué : regarde la base.\n");
    exit(3);
}

fwrite(STDOUT, "MariaDB : mot de passe enregistré et vérifié, échecs remis à zéro, compte déverrouillé.\n");

// 2 et 3. compte de démonstration : schema.sql et .env suivent
if ($demoRole !== null) {
    fwrite(STDOUT, updateSchemaHash($email, $hash)
        ? "database/schema.sql : hash mis à jour.\n"
        : "database/schema.sql : compte absent du fichier, rien à mettre à jour.\n");
    setEnvValue('DEMO_' . $demoRole . '_PASSWORD', $password);
    fwrite(STDOUT, ".env : DEMO_{$demoRole}_PASSWORD mis à jour.\n");
}

exit(0);

// ---------------------------------------------------------------------------
// Fonctions
// ---------------------------------------------------------------------------

/**
 * Applique les mots de passe DEMO_* du .env à MariaDB et à database/schema.sql.
 * Un compte sans mot de passe dans le .env est ignoré.
 */
function syncDemoAccounts(UserRepository $users, PasswordPolicy $policy): int
{
    $status = 0;

    foreach (DEMO_ROLES as $role) {
        $email = mb_strtolower(trim((string) ($_ENV['DEMO_' . $role . '_EMAIL'] ?? '')));
        $password = (string) ($_ENV['DEMO_' . $role . '_PASSWORD'] ?? '');

        if ($email === '' || $password === '') {
            fwrite(STDOUT, "DEMO_{$role} : e-mail ou mot de passe vide dans le .env, ignoré.\n");
            continue;
        }

        $errors = passwordErrors($policy, $password, true);

        if ($errors !== []) {
            fwrite(STDERR, "DEMO_{$role} ({$email}) : mot de passe du .env refusé :\n  - "
                . implode("\n  - ", $errors) . "\n");
            $status = 1;
            continue;
        }

        $user = $users->findByEmail($email);

        if ($user === null) {
            fwrite(STDERR, "DEMO_{$role} : aucun compte {$email} dans la base " . databaseLabel()
                . ". Importe d'abord database/schema.sql.\n");
            $status = 1;
            continue;
        }

        // on garde le hash actuel s'il correspond déjà, sinon on en fait un nouveau
        $hash = password_verify($password, $user->getPasswordHash())
            ? $user->getPasswordHash()
            : $policy->hash($password);

        $users->updatePassword($user->getId(), $hash);
        $users->updateFailedAttempts($user->getId(), 0, null);

        $schemaHash = schemaHashFor($email);
        $schemaState = 'compte absent du fichier';

        if ($schemaHash !== null) {
            if (password_verify($password, $schemaHash)) {
                $schemaState = 'déjà à jour';
            } else {
                updateSchemaHash($email, $hash);
                $schemaState = 'hash mis à jour';
            }
        }

        fwrite(STDOUT, "DEMO_{$role} ({$email}) : MariaDB à jour, compte déverrouillé ; schema.sql {$schemaState}.\n");
    }

    return $status;
}

/**
 * Règles de PasswordPolicy, plus celles du .env pour un compte de démonstration :
 * le .env enlève les espaces et les guillemets en début et en fin de valeur.
 */
function passwordErrors(PasswordPolicy $policy, string $password, bool $storedInEnv): array
{
    $errors = $policy->validate($password);

    if (preg_match('/[\r\n]/', $password)) {
        $errors[] = 'Le mot de passe ne doit pas contenir de retour à la ligne.';
    }

    if ($storedInEnv && $password !== trim($password, " \t\"'")) {
        $errors[] = 'Pour un compte du .env, le mot de passe ne doit ni commencer ni finir par un espace ou un guillemet.';
    }

    return $errors;
}

/** Rôle (ADMIN, EMPLOYEE, USER) si l'e-mail est un compte de démonstration du .env. */
function demoRoleFor(string $email): ?string
{
    foreach (DEMO_ROLES as $role) {
        if (mb_strtolower(trim((string) ($_ENV['DEMO_' . $role . '_EMAIL'] ?? ''))) === $email) {
            return $role;
        }
    }

    return null;
}

function databaseLabel(): string
{
    return config('database.mariadb.database', '?') . ' (' . config('database.mariadb.host', '?')
        . ':' . config('database.mariadb.port', '?') . ')';
}

/** Regex qui trouve le hash bcrypt d'un compte dans l'INSERT des users de schema.sql. */
function schemaPattern(string $email): string
{
    return "/('" . preg_quote($email, '/') . "',\\s*')(\\$2y\\$\\d{2}\\$[.\\/A-Za-z0-9]{53})(')/";
}

function schemaHashFor(string $email): ?string
{
    $sql = (string) @file_get_contents(SCHEMA_FILE);

    return preg_match(schemaPattern($email), $sql, $match) === 1 ? $match[2] : null;
}

/** Remplace le hash du compte dans database/schema.sql (le reste du fichier ne bouge pas). */
function updateSchemaHash(string $email, string $hash): bool
{
    $sql = (string) @file_get_contents(SCHEMA_FILE);
    $updated = preg_replace_callback(
        schemaPattern($email),
        static fn (array $m): string => $m[1] . $hash . $m[3],
        $sql,
        1,
        $count
    );

    if ($count !== 1 || $updated === null) {
        return false;
    }

    return file_put_contents(SCHEMA_FILE, $updated) !== false;
}

/** Écrit (ou ajoute) CLE="valeur" dans le .env en gardant ses fins de ligne. */
function setEnvValue(string $key, string $value): void
{
    $content = is_readable(ENV_FILE) ? (string) file_get_contents(ENV_FILE) : '';
    $eol = str_contains($content, "\r\n") ? "\r\n" : "\n";
    $line = $key . '="' . $value . '"';
    $pattern = '/^' . preg_quote($key, '/') . '=.*$/m';

    if (preg_match($pattern, $content) === 1) {
        $content = preg_replace_callback($pattern, static fn (): string => $line, $content, 1);
    } else {
        $content = rtrim($content, "\r\n") . $eol . $line . $eol;
    }

    file_put_contents(ENV_FILE, $content);
}

/** Prévient si le mot de passe commence ou finit par un espace (souvent un copier-coller raté). */
function warnSpaces(string $password): void
{
    if ($password !== trim($password)) {
        fwrite(STDOUT, "  Attention : ce mot de passe commence ou finit par un espace.\n");
    }
}

/**
 * Saisie masquée : une étoile par caractère.
 * - Windows : Read-Host -AsSecureString de PowerShell (affiche des étoiles).
 * - Linux / macOS : terminal en mode brut avec stty, étoiles affichées par le script.
 * - Entrée redirigée (pipe, fichier) : simple lecture de ligne, rien à masquer.
 */
function readHidden(string $label): string
{
    if (!function_exists('stream_isatty') || !stream_isatty(STDIN)) {
        fwrite(STDOUT, $label . ' : ');
        $line = fgets(STDIN);
        fwrite(STDOUT, "\n");

        return $line === false ? '' : rtrim($line, "\r\n");
    }

    $value = PHP_OS_FAMILY === 'Windows' ? readHiddenWindows($label) : readHiddenUnix($label);

    if ($value !== null) {
        return $value;
    }

    // dernier recours : saisie visible, mais on prévient
    fwrite(STDOUT, "(saisie masquée indisponible sur ce terminal, le mot de passe sera visible)\n" . $label . ' : ');
    $line = fgets(STDIN);

    return $line === false ? '' : rtrim($line, "\r\n");
}

function readHiddenWindows(string $label): ?string
{
    $prompt = str_replace("'", "''", $label);
    $script = "[Console]::OutputEncoding = [Text.Encoding]::UTF8;"
        . "\$s = Read-Host -AsSecureString -Prompt '{$prompt}';"
        . "\$b = [Runtime.InteropServices.Marshal]::SecureStringToBSTR(\$s);"
        . "try { [Console]::Out.Write([Runtime.InteropServices.Marshal]::PtrToStringBSTR(\$b)) }"
        . " finally { [Runtime.InteropServices.Marshal]::ZeroFreeBSTR(\$b) }";
    $encoded = base64_encode(mb_convert_encoding($script, 'UTF-16LE', 'UTF-8'));

    $process = @proc_open(
        ['powershell.exe', '-NoProfile', '-ExecutionPolicy', 'Bypass', '-EncodedCommand', $encoded],
        [0 => STDIN, 1 => ['pipe', 'w'], 2 => STDERR],
        $pipes
    );

    if (!is_resource($process)) {
        return null;
    }

    $output = (string) stream_get_contents($pipes[1]);
    fclose($pipes[1]);

    if (proc_close($process) !== 0) {
        return null;
    }

    // PowerShell renvoie le texte en UTF-8 ; on enlève un éventuel BOM
    return preg_replace('/^\xEF\xBB\xBF/', '', $output) ?? $output;
}

function readHiddenUnix(string $label): ?string
{
    $saved = shell_exec('stty -g 2>/dev/null');

    if (!is_string($saved) || trim($saved) === '') {
        return null;
    }

    $saved = trim($saved);
    $restore = static function () use ($saved): void {
        shell_exec('stty ' . escapeshellarg($saved) . ' 2>/dev/null');
    };
    register_shutdown_function($restore);

    shell_exec('stty -echo -icanon min 1 time 0 2>/dev/null');
    fwrite(STDOUT, $label . ' : ');

    $value = '';

    while (true) {
        $char = fread(STDIN, 1);

        if ($char === false || $char === '' || $char === "\n" || $char === "\r") {
            break;
        }

        if ($char === "\x7f" || $char === "\x08") {
            // retour arrière : on enlève le dernier caractère (UTF-8 compris) et son étoile
            if ($value !== '') {
                $value = mb_substr($value, 0, -1);
                fwrite(STDOUT, "\x08 \x08");
            }
            continue;
        }

        if ($char === "\x03") {
            // Ctrl+C : on remet le terminal en état avant de sortir
            $restore();
            fwrite(STDOUT, "\n");
            exit(130);
        }

        $value .= $char;

        // une étoile par caractère, pas par octet (é, ç… font 2 octets en UTF-8)
        if ((ord($char) & 0xC0) !== 0x80) {
            fwrite(STDOUT, '*');
        }
    }

    $restore();
    fwrite(STDOUT, "\n");

    return $value;
}
