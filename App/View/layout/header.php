<?php
$currentPath = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';
$isAuthenticated = !empty($user);
$escape = static fn (mixed $value): string => htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
?>
<!doctype html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="<?= $escape($csrfToken) ?>">
    <title><?= $escape($title) ?></title>
    <link rel="stylesheet" href="/assets/css/bootstrap.min.css">
    <link rel="stylesheet" href="/assets/css/style.css">
</head>
<body class="d-flex flex-column min-vh-100 bg-light" data-page="<?= $escape(trim($currentPath, '/') ?: 'home') ?>">
<nav class="navbar navbar-expand-lg navbar-light bg-white shadow-sm sticky-top">
    <div class="container">
        <a class="navbar-brand text-primary fw-bold" href="/">Vite &amp; Gourmand</a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#mainNavigation" aria-controls="mainNavigation" aria-expanded="false" aria-label="Afficher la navigation">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="mainNavigation">
            <ul class="navbar-nav ms-auto align-items-lg-center gap-lg-1">
                <li class="nav-item"><a class="nav-link <?= $currentPath === '/' ? 'active' : '' ?>" href="/">Accueil</a></li>
                <li class="nav-item"><a class="nav-link <?= str_starts_with($currentPath, '/menus') ? 'active' : '' ?>" href="/menus">Menus</a></li>
                <li class="nav-item"><a class="nav-link <?= $currentPath === '/contact' ? 'active' : '' ?>" href="/contact">Contact</a></li>
                <?php if ($isAuthenticated): ?>
                    <li class="nav-item"><a class="nav-link" href="/orders/new">Commander</a></li>
                    <li class="nav-item"><a class="nav-link" href="/orders">Mes commandes</a></li>
                    <li class="nav-item"><a class="nav-link" href="/profile">Mon profil</a></li>
                    <?php if ($role === 'admin'): ?>
                        <li class="nav-item"><a class="nav-link" href="/admin/dashboard">Administration</a></li>
                    <?php elseif ($role === 'employee'): ?>
                        <li class="nav-item"><a class="nav-link" href="/admin/orders">Commandes</a></li><li class="nav-item"><a class="nav-link" href="/admin/dishes">Plats</a></li><li class="nav-item"><a class="nav-link" href="/admin/hours">Horaires</a></li><li class="nav-item"><a class="nav-link" href="/admin/comments/pending">Avis</a></li>
                    <?php endif; ?>
                    <li class="nav-item">
                        <form method="post" action="/logout" class="d-inline">
                            <input type="hidden" name="csrf_token" value="<?= $escape($csrfToken) ?>">
                            <button class="btn btn-outline-primary btn-sm" type="submit">Déconnexion</button>
                        </form>
                    </li>
                <?php else: ?>
                    <li class="nav-item"><a class="nav-link" href="/login">Connexion</a></li>
                    <li class="nav-item"><a class="btn btn-primary btn-sm" href="/register">Créer un compte</a></li>
                <?php endif; ?>
            </ul>
        </div>
    </div>
</nav>
