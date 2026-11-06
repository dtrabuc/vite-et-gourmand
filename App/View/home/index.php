<main>
    <section class="hero home-hero text-white py-5">
        <div class="container py-5">
            <div class="row align-items-center g-4">
                <div class="col-lg-7">
                    <span class="badge bg-light text-primary rounded-pill px-3 py-2 mb-3">Traiteur évènementiel à Bordeaux</span>
                    <h1 class="display-4 fw-bold mb-4">Des réceptions soignées, un choix clair, une commande simple.</h1>
                    <p class="lead mb-4">Mariages, cocktails, repas d'entreprise, anniversaires ou déjeuners d'équipe : des formules adaptées, du service sur place à la livraison.</p>
                    <div class="d-flex flex-wrap gap-3">
                        <a href="/menus" class="btn btn-light btn-lg text-primary fw-bold">Découvrir les menus</a>
                        <?php if (!empty($user)): ?>
                            <a href="/orders/new" class="btn btn-primary btn-lg fw-bold">Démarrer une commande</a>
                        <?php else: ?>
                            <a href="/login" class="btn btn-outline-light btn-lg fw-bold">Se connecter</a>
                            <a href="/quote" class="btn btn-outline-light btn-lg fw-bold">Demander un devis</a>
                        <?php endif; ?>
                    </div>
                </div>
                <div class="col-lg-5">
                    <div class="card border-0 shadow-lg">
                        <div class="card-body p-4 p-lg-5">
                            <h2 class="h4 text-primary mb-3">Pourquoi nous choisir ?</h2>
                            <div class="d-grid gap-3">
                                <div class="summary-box p-3">
                                    <strong class="d-block mb-1">Formules lisibles</strong>
                                    <span class="small text-muted">Menus complets, options végétales et cocktails.</span>
                                </div>
                                <div class="summary-box p-3">
                                    <strong class="d-block mb-1">Service adapté</strong>
                                    <span class="small text-muted">Retrait, livraison ou prestation sur place selon votre évènement.</span>
                                </div>
                                <div class="summary-box p-3">
                                    <strong class="d-block mb-1">Zone d'intervention</strong>
                                    <span class="small text-muted">Bordeaux et sa métropole, avec extension sur demande en Gironde.</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <section class="py-5" aria-labelledby="teamTitle">
        <div class="container">
            <div class="row justify-content-center">
                <div class="col-xl-10">
                    <div class="text-center mb-4">
                        <span class="badge bg-primary-subtle text-primary">Notre équipe</span>
                        <h2 class="section-title mt-3" id="teamTitle">Julie et José, 25 ans de métier à Bordeaux</h2>
                        <p class="text-muted mb-0">
                            Depuis 25 ans, Vite &amp; Gourmand accompagne les Bordelais pour leurs repas de fête,
                            de Noël à Pâques, et pour tous leurs évènements, avec des menus qui évoluent au fil des saisons.
                        </p>
                    </div>
                    <div class="row g-4">
                        <div class="col-md-6">
                            <article class="card h-100 border-0 shadow-sm">
                                <div class="card-body p-4">
                                    <h3 class="h5 text-primary">Julie</h3>
                                    <p class="small text-muted mb-2">Cofondatrice · relation clients et composition des menus</p>
                                    <p class="mb-0">
                                        Julie imagine les menus, écoute chaque client et adapte les formules à l’évènement :
                                        nombre de convives, régimes alimentaires, allergies.
                                    </p>
                                </div>
                            </article>
                        </div>
                        <div class="col-md-6">
                            <article class="card h-100 border-0 shadow-sm">
                                <div class="card-body p-4">
                                    <h3 class="h5 text-primary">José</h3>
                                    <p class="small text-muted mb-2">Cofondateur · cuisine et organisation des prestations</p>
                                    <p class="mb-0">
                                        José dirige la cuisine et l’organisation des prestations, de la préparation à la
                                        livraison ou au service sur place, avec la même exigence depuis 25 ans.
                                    </p>
                                </div>
                            </article>
                        </div>
                    </div>
                    <ul class="list-unstyled d-flex flex-wrap justify-content-center gap-4 mt-4 mb-0 small">
                        <li><strong>25 ans</strong> d’expérience à Bordeaux</li>
                        <li><strong>Menus renouvelés</strong> au fil des saisons et des fêtes</li>
                        <li><strong>Allergènes</strong> indiqués pour chaque plat</li>
                    </ul>
                </div>
            </div>
        </div>
    </section>

    <section class="py-5 bg-light">
        <div class="container">
            <div class="row justify-content-center">
                <div class="col-xl-10">
                    <div class="card border-0 shadow-sm">
                        <div class="card-body p-4 p-lg-5">
                            <div class="row align-items-center g-4">
                                <div class="col-lg-8">
                                    <span class="badge bg-primary-subtle text-primary">Sur mesure</span>
                                    <h2 class="h3 text-primary mt-3">Une grande réception ou un événement sur devis ?</h2>
                                    <p class="text-muted mb-0">
                                        Tous nos menus se commandent directement en ligne. Si vous préférez une proposition
                                        adaptée à votre événement, vous pouvez aussi nous demander un devis.
                                    </p>
                                </div>
                                <div class="col-lg-4 text-lg-end">
                                    <a href="/quote" class="btn btn-primary">Demander un devis</a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <section class="py-5 bg-white">
        <div class="container">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <div>
                    <h2 class="h2 text-primary mb-1">Menus à la une</h2>
                    <p class="text-muted mb-0">Les formules disponibles actuellement.</p>
                </div>
                <a href="/menus" class="btn btn-outline-primary">Tout voir</a>
            </div>
            <div class="row g-4">
                <?php foreach ($menus as $menu): ?>
                    <?php $cover = menu_cover_image($menuCovers ?? [], $menu->getId(), $menu->getTitle()); ?>
<div class="col-md-6 col-lg-4">
                        <article class="card menu-card h-100 border-0 shadow-sm overflow-hidden">
                            <img src="<?= $escape($cover['url']) ?>" class="card-img-top menu-card__image" alt="<?= $escape($cover['alt_text'] ?? $menu->getTitle()) ?>" width="1200" height="800" loading="lazy" decoding="async">
                            <div class="card-body d-flex flex-column">
                                <span class="badge bg-primary-subtle text-primary align-self-start mb-2"><?= $escape($menu->getTheme()) ?></span>
                                <h3 class="h4 text-primary"><?= $escape($menu->getTitle()) ?></h3>
                                <p class="text-muted flex-grow-1"><?= $escape($menu->getDescription()) ?></p>
                                <p class="mb-3"><strong><?= number_format($menu->getBasePrice(), 2, ',', ' ') ?> €</strong> · minimum <?= $menu->getMinPeople() ?> personnes</p>
                                <div class="d-grid gap-2">
                                    <a class="btn btn-outline-primary" href="/menus/<?= $menu->getId() ?>">Voir le détail</a>
                                    <?php if (!empty($user)): ?>
                                        <a class="btn btn-primary" href="/orders/new?menu=<?= $menu->getId() ?>">Commander</a>
                                    <?php else: ?>
                                        <a class="btn btn-primary" href="/login?redirect=<?= $escape(rawurlencode('/orders/new?menu=' . $menu->getId())) ?>">Se connecter pour commander</a>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </article>
                    </div>
                <?php endforeach; ?>
                <?php if ($menus === []): ?>
                    <div class="col-12"><p class="text-muted mb-0">Les menus seront disponibles prochainement.</p></div>
                <?php endif; ?>
            </div>
        </div>
    </section>

    <section class="py-5 bg-light">
        <div class="container">
            <div class="d-flex justify-content-between align-items-center flex-wrap gap-3 mb-4">
                <div>
                    <h2 class="h2 text-primary fw-bold mb-1">Avis clients</h2>
                    <p class="text-muted mb-0">Quelques retours après des prestations.</p>
                </div>
                <?php if (!empty($user)): ?>
                    <a class="btn btn-outline-primary" href="/orders">Mes commandes</a>
                <?php endif; ?>
            </div>
            <div class="row g-4">
                <?php foreach ($reviews as $review): ?>
                    <div class="col-md-4">
                        <article class="card h-100 border-0 shadow-sm">
                            <div class="card-body">
                                <p class="text-warning mb-2"><?= str_repeat('★', max(0, min(5, (int) ($review['rating'] ?? 5)))) ?></p>
                                <p class="fst-italic">« <?= $escape($review['comment'] ?? '') ?> »</p>
                                <p class="mb-0 text-muted small"><?= $escape($review['user_name'] ?? '') ?></p>
                            </div>
                        </article>
                    </div>
                <?php endforeach; ?>
                <?php if ($reviews === []): ?>
                    <div class="col-12"><p class="text-muted mb-0">Les avis validés seront affichés ici.</p></div>
                <?php endif; ?>
            </div>
        </div>
    </section>
</main>