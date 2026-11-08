<!doctype html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="<?= $escape($csrfToken) ?>">
    <title><?= $escape($title) ?></title>
    <?php /* réglages d'accessibilité remis avant l'affichage (taille du texte, contraste...) */ ?>
    <script src="/assets/js/a11y-init.js"></script>
    <link rel="stylesheet" href="/assets/css/bootstrap.min.css">
    <link rel="stylesheet" href="/assets/css/tokens.css">
    <link rel="stylesheet" href="/assets/css/base.css">
    <link rel="stylesheet" href="/assets/css/components/navbar.css">
    <link rel="stylesheet" href="/assets/css/components/buttons.css">
    <link rel="stylesheet" href="/assets/css/components/cards.css">
    <link rel="stylesheet" href="/assets/css/components/menu-card.css">
    <link rel="stylesheet" href="/assets/css/components/forms.css">
    <link rel="stylesheet" href="/assets/css/components/staff-nav.css">
    <link rel="stylesheet" href="/assets/css/components/tables.css">
    <link rel="stylesheet" href="/assets/css/components/footer.css">
    <link rel="stylesheet" href="/assets/css/components/hero.css">
    <link rel="stylesheet" href="/assets/css/components/feedback.css">
    <link rel="stylesheet" href="/assets/css/components/accessibility.css">
    <link rel="stylesheet" href="/assets/css/pages/home.css">
    <link rel="stylesheet" href="/assets/css/pages/menus.css">
    <link rel="stylesheet" href="/assets/css/pages/menu-detail.css">
    <link rel="stylesheet" href="/assets/css/pages/orders.css">
    <link rel="stylesheet" href="/assets/css/pages/admin.css">
    <link rel="stylesheet" href="/assets/css/pages/auth.css">
    <link rel="stylesheet" href="/assets/css/pages/contact.css">
    <link rel="stylesheet" href="/assets/css/pages/quote.css">
    <link rel="stylesheet" href="/assets/css/responsive.css">
    <?php /* CSS d'intl-tel-input, seulement sur les pages avec un champ téléphone */ ?>
    <?php if (!empty($phoneInput)): ?>
        <link rel="stylesheet" href="/assets/vendor/intl-tel-input/29.5.3/css/intlTelInput.min.css">
    <?php endif; ?>
</head>
<body class="d-flex flex-column min-vh-100"
      data-page="<?= $escape(trim((string) parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH), '/') ?: 'home') ?>">
<?php /* premier lien de la page : permet de sauter la navigation au clavier ou au lecteur d'écran */ ?>
<a class="skip-link" href="#contenu">Aller au contenu</a>
<?php require __DIR__ . '/header.php'; ?>
<?php /* sous-navigation de l'équipe sur les pages /admin/... */ ?>
<?php if (in_array($role ?? '', ['employee', 'admin'], true) && str_starts_with((string) parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH), '/admin/') && !str_starts_with((string) parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH), '/admin/login')): ?>
    <?php require __DIR__ . '/staff_nav.php'; ?>
<?php endif; ?>
<div id="contenu" tabindex="-1" class="d-flex flex-column flex-grow-1">
<?php require __DIR__ . '/flash.php'; ?>
<?= $content ?>
</div>
<?php /* notifications en direct (notifications.js) */ ?>
<div class="toast-container position-fixed bottom-0 end-0 p-3" id="toastArea" aria-live="polite" aria-atomic="false"></div>
<?php require __DIR__ . '/accessibility.php'; ?>
<?php require __DIR__ . '/footer.php'; ?>
</body>
</html>