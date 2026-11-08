<?php
use App\Core\Labels;
use App\Service\MenuPricing;

$status = $order->getStatus();
$isDelivery = $order->getServiceType() === 'delivery';
// étapes affichées au client, dans l'ordre
$steps = [
    'pending' => 'Commande envoyée',
    'accepted' => 'Acceptée par l’équipe',
    'preparing' => 'En préparation',
    'delivering' => $isDelivery ? 'En livraison' : 'Prête',
    'completed' => 'Terminée',
];
$stepKeys = array_keys($steps);
$position = match ($status) {
    'delivered', 'awaiting_return' => 3,
    default => array_search($status, $stepKeys, true),
};
$position = $position === false ? 0 : $position;
$time = substr($order->getDeliveryTime(), 0, 5);
?>
<main class="py-5">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-12 col-xl-9">
                <section class="card border-0 shadow-sm order-confirmation">
                    <div class="card-body p-4 p-md-5">
                        <?php if ($status === 'cancelled'): ?>
                            <div class="text-center mb-4">
                                <h1 class="h2 text-danger">Commande n° <?= (int) $order->getId() ?> annulée</h1>
                            </div>
                        <?php else: ?>
                            <div class="text-center mb-4">
                                <div class="order-confirmation__check" aria-hidden="true">✓</div>
                                <h1 class="h2 text-success mt-3 mb-2">Commande envoyée !</h1>
                                <p class="lead mb-1">Commande n° <strong><?= (int) $order->getId() ?></strong> (référence <?= $escape($order->getOrderNumber()) ?>)</p>
                                <p class="mb-0">Un e-mail de confirmation vient de vous être envoyé.</p>
                            </div>

                            <ol class="order-steps" aria-label="Suivi de la commande">
                                <?php foreach (array_values($steps) as $index => $label): ?>
                                    <li class="order-steps__item <?= $index < $position ? 'is-done' : ($index === $position ? 'is-current' : '') ?>"
                                        <?= $index === $position ? 'aria-current="step"' : '' ?>>
                                        <span class="order-steps__dot" aria-hidden="true"><?= $index < $position || $index === 0 ? '✓' : $index + 1 ?></span>
                                        <span class="order-steps__label"><?= $escape($label) ?></span>
                                    </li>
                                <?php endforeach; ?>
                            </ol>

                            <div class="alert alert-info">
                                <strong>Et maintenant ?</strong>
                                <?php if ($status === 'pending'): ?>
                                    Notre équipe vérifie votre commande et l’accepte, en général sous 24 h.
                                <?php else: ?>
                                    État actuel : <?= $escape(Labels::orderStatus($status)) ?>.
                                <?php endif; ?>
                                Vous serez prévenu à chaque étape par e-mail et par une notification (cloche 🔔 en haut de page).
                                <?php $deadline = \App\Service\OrderModificationPolicy::deadline($order); ?>
                                <?php if (\App\Service\OrderModificationPolicy::customerCanChange($order) && $deadline !== null): ?>
                                    Vous pouvez la modifier ou l’annuler depuis « Mes commandes » jusqu’au <strong><?= $escape(french_date($deadline->format('Y-m-d H:i'), true)) ?></strong>.
                                <?php endif; ?>
                            </div>
                        <?php endif; ?>

                        <div class="row g-3 my-3">
                            <div class="col-12 col-md-4">
                                <div class="summary-box p-3 h-100">
                                    <span class="small text-muted d-block">Prestation</span>
                                    <strong><?= $escape(Labels::serviceType($order->getServiceType())) ?></strong>
                                </div>
                            </div>
                            <div class="col-12 col-md-4">
                                <div class="summary-box p-3 h-100">
                                    <span class="small text-muted d-block">Date et heure</span>
                                    <strong><?= $escape(ucfirst(french_date($order->getDeliveryDate()))) ?></strong>
                                    <span class="small d-block">à <?= $escape($time) ?></span>
                                </div>
                            </div>
                            <div class="col-12 col-md-4">
                                <div class="summary-box p-3 h-100">
                                    <span class="small text-muted d-block">Téléphone de contact</span>
                                    <strong><?= $escape($order->getContactPhone()) ?></strong>
                                </div>
                            </div>
                        </div>

                        <dl class="order-recap">
                            <div><dt>Menu</dt><dd><?= $escape($menu?->getTitle() ?? 'Menu') ?> pour <?= $order->getNumberOfPeople() ?> personne<?= $order->getNumberOfPeople() > 1 ? 's' : '' ?></dd></div>
                            <?php if (!empty($items)): ?><div class="order-recap__items"><dt>Vos choix</dt><dd><?= order_items_html($items) ?></dd></div><?php endif; ?>
                            <?php if ($order->getCustomization()): ?><div><dt>Précisions cuisine</dt><dd><?= $escape($order->getCustomization()) ?></dd></div><?php endif; ?>
                            <div><dt><?= $isDelivery ? 'Adresse de livraison' : 'Lieu' ?></dt><dd>
                                <?php if ($order->getServiceType() === 'pickup'): ?>
                                    Retrait chez Vite &amp; Gourmand, Bordeaux
                                <?php else: ?>
                                    <?= $escape($order->getDeliveryAddress()) ?><?= $order->getDeliveryCity() !== '' ? ', ' . $escape($order->getDeliveryPostalCode() . ' ' . $order->getDeliveryCity()) : '' ?>
                                    <?php if ($isDelivery && $order->getAddressStatus() === 'verified'): ?><span class="badge text-bg-success ms-1">adresse vérifiée</span><?php endif; ?>
                                    <?php if ($isDelivery && $order->getAddressStatus() === 'manual'): ?><span class="badge text-bg-warning ms-1">saisie manuelle</span><?php endif; ?>
                                <?php endif; ?>
                            </dd></div>
                            <?php if ($order->getDeliveryInstructions()): ?><div><dt>Instructions</dt><dd><?= $escape($order->getDeliveryInstructions()) ?></dd></div><?php endif; ?>
                            <div><dt>Menu<?= $order->getDiscountRate() > 0 ? ' (remise de ' . number_format($order->getDiscountRate(), 0) . ' % incluse)' : '' ?></dt><dd><?= MenuPricing::format($order->getMenuPrice()) ?></dd></div>
                            <div><dt>Livraison</dt><dd>
                                <?php if (!$isDelivery): ?>aucune
                                <?php elseif ($order->isDeliveryCostPending()): ?>à confirmer par l’équipe (adresse saisie manuellement)
                                <?php elseif ($order->getDeliveryCost() <= 0): ?>offerte
                                <?php else: ?><?= MenuPricing::format($order->getDeliveryCost()) ?><?= $order->getDeliveryZone() === 'outside' ? ' (hors zone)' : '' ?>
                                <?php endif; ?>
                            </dd></div>
                            <div class="order-recap__total"><dt>Total à régler en espèces</dt><dd><?= MenuPricing::format($order->getTotalPrice()) ?><?= $order->isDeliveryCostPending() ? ' + livraison' : '' ?></dd></div>
                        </dl>

                        <div class="d-flex flex-wrap gap-2 mt-4">
                            <a class="btn btn-primary" href="/orders">Suivre mes commandes</a>
                            <a class="btn btn-outline-primary" href="/menus">Retour aux menus</a>
                        </div>
                    </div>
                </section>
            </div>
        </div>
    </div>
</main>
