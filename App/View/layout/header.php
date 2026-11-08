<?php
/*
 * Barre de navigation du haut.
 * - visiteur : liens publics + Connexion / Créer un compte
 * - client : Commander, Mes commandes, menu « Mon compte »
 * - équipe (employé, admin) : lien vers l'espace équipe + menu « Mon compte »
 *   (les rubriques de l'espace équipe sont dans layout/staff_nav.php)
 */
$currentPath = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';
$isAuthenticated = !empty($user);
$isStaff = in_array($role ?? '', ['employee', 'admin'], true);
$staffHome = ($role ?? '') === 'admin' ? '/admin/dashboard' : '/admin/orders';
$active = static fn (bool $on): string => $on ? ' active" aria-current="page' : '';
?>
<nav class="navbar navbar-expand-lg navbar-light bg-white sticky-top" aria-label="Navigation principale">
    <div class="container">
        <a class="navbar-brand" href="/">Vite &amp; Gourmand</a>

        <?php /* toujours visible, même menu replié : ouvre le panneau layout/accessibility.php */ ?>
        <button class="btn btn-a11y ms-auto me-2 ms-lg-2 me-lg-0 order-lg-last" type="button"
                data-bs-toggle="offcanvas" data-bs-target="#a11yPanel" aria-controls="a11yPanel"
                aria-label="Réglages d’accessibilité">
            <svg viewBox="0 0 24 24" fill="currentColor" aria-hidden="true" focusable="false">
                <circle cx="12" cy="4" r="2"/>
                <path d="M19 8.5 14 9.2V21h-2v-5.5h-0V21h-2V9.2L5 8.5l.3-2L12 7.4l6.7-.9z"/>
            </svg>
            <span class="d-none d-md-inline">Accessibilité</span>
        </button>

        <button class="navbar-toggler" type="button"
                data-bs-toggle="collapse" data-bs-target="#mainNavigation"
                aria-controls="mainNavigation" aria-expanded="false"
                aria-label="Afficher la navigation">
            <span class="navbar-toggler-icon"></span>
        </button>

        <div class="collapse navbar-collapse" id="mainNavigation">
            <ul class="navbar-nav ms-auto align-items-lg-center gap-lg-1">
                <li class="nav-item"><a class="nav-link<?= $active($currentPath === '/') ?>" href="/">Accueil</a></li>
                <li class="nav-item"><a class="nav-link<?= $active(str_starts_with($currentPath, '/menus')) ?>" href="/menus">Menus</a></li>
                <li class="nav-item"><a class="nav-link<?= $active($currentPath === '/contact') ?>" href="/contact">Contact</a></li>

                <?php if (!$isAuthenticated): ?>
                    <li class="nav-item"><a class="nav-link<?= $active($currentPath === '/login') ?>" href="/login">Connexion</a></li>
                    <li class="nav-item ms-lg-2"><a class="btn btn-primary" href="/register">Créer un compte</a></li>
                <?php else: ?>
                    <?php if ($isStaff): ?>
                        <li class="nav-item"><a class="nav-link<?= $active(str_starts_with($currentPath, '/admin')) ?>" href="<?= $staffHome ?>">Espace équipe</a></li>
                    <?php else: ?>
                        <li class="nav-item"><a class="nav-link<?= $active(str_starts_with($currentPath, '/orders/new')) ?>" href="/orders/new">Commander</a></li>
                        <li class="nav-item"><a class="nav-link<?= $active($currentPath === '/orders' || str_starts_with($currentPath, '/orders/confirmation/')) ?>" href="/orders">Mes commandes</a></li>
                    <?php endif; ?>

                    <?php /* cloche : nombre de notifications non lues, mis à jour par notifications.js */ ?>
                    <?php $unread = (int) ($unreadNotifications ?? 0); ?>
                    <li class="nav-item">
                        <a class="nav-link nav-bell<?= $active($currentPath === '/notifications') ?>" href="/notifications" id="notificationBell"
                           aria-label="Notifications<?= $unread > 0 ? ' : ' . $unread . ' non lue' . ($unread > 1 ? 's' : '') : '' ?>">
                            <svg viewBox="0 0 24 24" width="20" height="20" fill="currentColor" aria-hidden="true" focusable="false">
                                <path d="M12 22a2.5 2.5 0 0 0 2.45-2h-4.9A2.5 2.5 0 0 0 12 22Zm7-6V11a7 7 0 0 0-5.5-6.84V3.5a1.5 1.5 0 0 0-3 0v.66A7 7 0 0 0 5 11v5l-2 2v1h18v-1Z"/>
                            </svg>
                            <span class="d-lg-none ms-1">Notifications</span>
                            <span class="badge rounded-pill text-bg-danger nav-bell__count<?= $unread > 0 ? '' : ' d-none' ?>" id="notificationCount"><?= $unread > 99 ? '99+' : $unread ?></span>
                        </a>
                    </li>

                    <?php /* menu déroulant du compte (Bootstrap JS) */ ?>
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle<?= $active(in_array($currentPath, ['/profile', '/notifications'], true)) ?>" href="#" role="button"
                           data-bs-toggle="dropdown" aria-expanded="false">Mon compte</a>
                        <ul class="dropdown-menu dropdown-menu-lg-end">
                            <li><a class="dropdown-item" href="/profile">Mon profil</a></li>
                            <li><a class="dropdown-item" href="/notifications">Notifications</a></li>
                            <li><hr class="dropdown-divider"></li>
                            <li>
                                <form method="post" action="/logout">
                                    <input type="hidden" name="csrf_token" value="<?= $escape($csrfToken) ?>">
                                    <button class="dropdown-item" type="submit">Déconnexion</button>
                                </form>
                            </li>
                        </ul>
                    </li>
                <?php endif; ?>
            </ul>
        </div>
    </div>
</nav>
