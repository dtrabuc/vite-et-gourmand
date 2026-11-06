<?php
/*
 * Sous-navigation de l'espace équipe, affichée sous la barre du haut
 * sur toutes les pages /admin/... (sauf la page de connexion).
 * L'admin a en plus le tableau de bord, les employés et le chiffre d'affaires.
 */
$staffPath = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';
$staffLinks = [];

if (($role ?? '') === 'admin') {
    $staffLinks['/admin/dashboard'] = 'Tableau de bord';
}

$staffLinks += [
    '/admin/orders' => 'Commandes',
    '/admin/quotes' => 'Devis',
    '/admin/menus' => 'Menus',
    '/admin/dishes' => 'Plats',
    '/admin/hours' => 'Horaires',
    '/admin/comments/pending' => 'Avis',
    '/admin/customers' => 'Clients',
];

if (($role ?? '') === 'admin') {
    $staffLinks['/admin/employees'] = 'Employés';
    $staffLinks['/admin/revenue'] = 'Chiffre d’affaires';
}
?>
<nav class="staff-nav" aria-label="Espace équipe">
    <div class="container">
        <ul class="nav">
            <?php foreach ($staffLinks as $href => $label): ?>
                <?php $isActive = $staffPath === $href || str_starts_with($staffPath, $href . '/'); ?>
                <li class="nav-item">
                    <a class="nav-link<?= $isActive ? ' active' : '' ?>" href="<?= $href ?>"<?= $isActive ? ' aria-current="page"' : '' ?>><?= $escape($label) ?></a>
                </li>
            <?php endforeach; ?>
        </ul>
    </div>
</nav>
