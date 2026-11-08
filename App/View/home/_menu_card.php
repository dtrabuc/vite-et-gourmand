<?php
// Carte d'un menu dans le catalogue. Même rendu que renderMenus() dans menus.js.
// Variables : $menu, $dishes, $cover, $people (int|null), $user.
use App\Service\MenuPricing;

$minPeople = $menu->getMinPeople();
$perPerson = MenuPricing::perPerson($menu->getBasePrice(), $minPeople);
$quote = $people !== null ? MenuPricing::quote($menu->getBasePrice(), $minPeople, $people) : null;
$orderable = $quote === null || $quote['orderable'];
$orderUrl = '/orders/new?menu=' . $menu->getId() . ($people !== null && $orderable ? '&people=' . $people : '');
?>
<div class="col-sm-6 col-lg-4" data-menu-card>
    <article class="card menu-card h-100 border-0 shadow-sm overflow-hidden<?= $orderable ? '' : ' menu-card--unavailable' ?>">
        <div class="menu-card__media">
            <img src="<?= $escape($cover['url']) ?>" class="card-img-top menu-card__image" alt="<?= $escape($cover['alt_text'] ?? $menu->getTitle()) ?>" width="1200" height="800" loading="lazy" decoding="async">
            <span class="badge menu-card__theme"><?= $escape($menu->getTheme()) ?></span>
        </div>
        <div class="card-body d-flex flex-column">
            <h3 class="h5 mb-1"><a class="menu-card__title" href="/menus/<?= $menu->getId() ?><?= $people !== null ? '?people=' . $people : '' ?>"><?= $escape($menu->getTitle()) ?></a></h3>
            <p class="menu-card__price mb-2">
                <strong><?= MenuPricing::format($perPerson) ?></strong> / pers.
                <span class="text-muted">· dès <?= $minPeople ?> pers. · <?= $escape(\App\Core\Labels::dietaryRegime($menu->getDietaryRegime())) ?></span>
            </p>
            <p class="menu-card__desc text-muted small mb-3"><?= $escape($menu->getDescription()) ?></p>

            <div class="menu-card__included mb-3">
                <p class="menu-card__label mb-1">Inclus pour chaque convive</p>
                <ul class="list-unstyled mb-0">
                    <?php foreach (menu_composition($dishes) as $group): ?>
                        <li><span class="menu-card__count"><?= $escape($group['summary']) ?></span> <span class="text-muted">: <?= $escape(implode($group['key'] === 'side' ? ', ' : ' ou ', $group['names'])) ?></span></li>
                    <?php endforeach; ?>
                </ul>
            </div>

            <div class="menu-card__total mt-auto mb-3" aria-live="polite">
                <?php if ($quote === null): ?>
                    <span class="text-muted small">Indiquez le nombre de personnes en haut de page pour voir le total.</span>
                <?php elseif (!$quote['orderable']): ?>
                    <span class="small fw-semibold">Disponible à partir de <?= $minPeople ?> personnes.</span>
                <?php else: ?>
                    <div class="d-flex justify-content-between align-items-baseline gap-2">
                        <span class="small">Total pour <?= $people ?> pers.</span>
                        <span>
                            <?php if ($quote['discount_rate'] > 0): ?>
                                <s class="text-muted small"><?= MenuPricing::format($quote['gross']) ?></s>
                            <?php endif; ?>
                            <strong class="fs-5"><?= MenuPricing::format($quote['total']) ?></strong>
                        </span>
                    </div>
                    <?php if ($quote['discount_rate'] > 0): ?>
                        <span class="badge text-bg-success">−10 % appliqués</span>
                    <?php else: ?>
                        <span class="small text-muted">−10 % à partir de <?= MenuPricing::discountFrom($minPeople) ?> personnes.</span>
                    <?php endif; ?>
                <?php endif; ?>
                <?php if ($menu->getAvailableStock() <= 5): ?>
                    <span class="d-block small text-danger mt-1">Plus que <?= $menu->getAvailableStock() ?> commande<?= $menu->getAvailableStock() > 1 ? 's' : '' ?> possible<?= $menu->getAvailableStock() > 1 ? 's' : '' ?>.</span>
                <?php endif; ?>
            </div>

            <div class="d-grid gap-2">
                <?php if (!$orderable): ?>
                    <a class="btn btn-outline-primary" href="/menus/<?= $menu->getId() ?>">Voir le détail</a>
                <?php elseif (!empty($user)): ?>
                    <a class="btn btn-primary" href="<?= $escape($orderUrl) ?>">Commander<?= $people !== null ? ' pour ' . $people . ' pers.' : '' ?></a>
                <?php else: ?>
                    <a class="btn btn-primary" href="/login?redirect=<?= $escape(rawurlencode($orderUrl)) ?>">Se connecter pour commander</a>
                <?php endif; ?>
            </div>
        </div>
    </article>
</div>
