<?php
$status = $order->getStatus();
?>
<main class="py-5">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-12 col-xl-9">
                <section class="card border-0 shadow-sm">
                    <div class="card-body p-4 p-md-5">
                        <div class="text-center mb-4">
                            <div class="d-flex flex-wrap justify-content-center gap-2">
                                <span class="badge text-bg-success">Commande enregistrée</span>
                                <span class="badge text-bg-secondary"><?= $escape(\App\Core\Labels::orderStatus($status)) ?></span>
                            </div>
                            <h1 class="h2 text-primary mt-3">Merci pour votre commande</h1>
                            <p class="mb-1">Référence : <strong><?= $escape($order->getOrderNumber()) ?></strong></p>
                            <p class="mb-0">Commande enregistrée pour le <?= $escape($order->getDeliveryDate()) ?>.</p>
                        </div>

                        <div class="row g-3 mb-4">
                            <div class="col-12 col-md-4">
                                <div class="summary-box p-3 h-100">
                                    <span class="small text-muted d-block">Prestation</span>
                                    <strong><?= $escape(\App\Core\Labels::serviceType($order->getServiceType())) ?></strong>
                                </div>
                            </div>
                            <div class="col-12 col-md-4">
                                <div class="summary-box p-3 h-100">
                                    <span class="small text-muted d-block">Date / heure</span>
                                    <strong><?= $escape($order->getDeliveryDate()) ?></strong>
                                    <span class="small d-block"><?= $escape($order->getDeliveryTime()) ?></span>
                                </div>
                            </div>
                            <div class="col-12 col-md-4">
                                <div class="summary-box p-3 h-100">
                                    <span class="small text-muted d-block">Téléphone de contact</span>
                                    <strong><?= $escape($order->getContactPhone()) ?></strong>
                                </div>
                            </div>
                        </div>

                        <dl class="row text-start mt-4">
                            <dt class="col-sm-5">Menu</dt>
                            <dd class="col-sm-7"><?= $escape($menu?->getTitle() ?? 'Menu') ?></dd>
                            <dt class="col-sm-5">Adresse / lieu</dt>
                            <dd class="col-sm-7"><?= $escape($order->getDeliveryAddress()) ?><?= $order->getDeliveryCity() !== '' ? ' — ' . $escape($order->getDeliveryPostalCode() . ' ' . $order->getDeliveryCity()) : '' ?></dd>
                            <dt class="col-sm-5">Paiement</dt>
                            <dd class="col-sm-7">Espèces sur place</dd><?php if ($order->getCustomization()): ?><dt class="col-sm-5">Options choisies</dt><dd class="col-sm-7"><?= $escape($order->getCustomization()) ?></dd><?php endif; ?><?php if ($order->getDeliveryInstructions()): ?><dt class="col-sm-5">Instructions</dt><dd class="col-sm-7"><?= $escape($order->getDeliveryInstructions()) ?></dd><?php endif; ?><dt class="col-sm-5">Nombre de personnes</dt><dd class="col-sm-7"><?= $order->getNumberOfPeople() ?></dd><dt class="col-sm-5">Sous-total menu</dt><dd class="col-sm-7"><?= number_format($order->getMenuPrice(), 2, ',', ' ') ?> €</dd><dt class="col-sm-5">Remise</dt><dd class="col-sm-7"><?= number_format($order->getDiscountRate(), 0) ?> %</dd><dt class="col-sm-5">Livraison</dt><dd class="col-sm-7"><?= number_format($order->getDeliveryCost(), 2, ',', ' ') ?> €</dd><dt class="col-sm-5">Total</dt><dd class="col-sm-7"><?= number_format($order->getTotalPrice(), 2, ',', ' ') ?> €</dd></dl><a class="btn btn-primary" href="/orders">Voir mes commandes</a></div></section></div></div></div></main>
