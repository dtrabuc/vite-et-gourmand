<main class="py-5"><div class="container">
    <?php if ($menu === null): ?>
        <div class="alert alert-warning"><h1 class="h4">Menu introuvable</h1><a href="/menus">Retour au catalogue</a></div>
    <?php else: ?>
        <div class="row justify-content-center"><div class="col-lg-8"><article class="card border-0 shadow-sm"><div class="card-body p-4 p-md-5">
            <span class="badge bg-primary-subtle text-primary"><?= $escape($menu->getTheme()) ?></span>
            <h1 class="display-6 text-primary mt-3"><?= $escape($menu->getTitle()) ?></h1>
            <p class="lead text-muted"><?= $escape($menu->getDescription()) ?></p>
            <dl class="row mt-4"><dt class="col-sm-5">Prix par personne</dt><dd class="col-sm-7"><?= number_format($menu->getBasePrice(), 2, ',', ' ') ?> €</dd><dt class="col-sm-5">Nombre minimum</dt><dd class="col-sm-7"><?= $menu->getMinPeople() ?> personnes</dd><dt class="col-sm-5">Stock disponible</dt><dd class="col-sm-7"><?= $menu->getAvailableStock() ?> commandes</dd></dl>
            <section class="alert alert-warning"><h2 class="h5">Conditions de commande</h2><p class="mb-0"><?= $escape($menu->getConditions()) ?></p></section>
            <div class="d-flex gap-2 flex-wrap"><a class="btn btn-outline-primary" href="/menus">Retour aux menus</a><?php if (!empty($user)): ?><a class="btn btn-primary" href="/orders/new?menu=<?= $menu->getId() ?>">Commander ce menu</a><?php else: ?><a class="btn btn-primary" href="/login">Connectez-vous pour commander</a><?php endif; ?></div>
        </div></article></div></div>
    <?php endif; ?>
</div></main>
