<?php
$statusLabels = \App\Core\Labels::ORDER_STATUS;
$serviceLabels = \App\Core\Labels::SERVICE_TYPE;
?>
<main class="py-5"><div class="container">
<div class="d-flex justify-content-between align-items-center mb-4"><div><h1 class="h2 text-primary mb-1">Mes commandes</h1><p class="text-muted mb-0">Suivez vos prestations, leurs statuts et leurs dates.</p></div><a class="btn btn-primary" href="/orders/new">Nouvelle commande</a></div>
<?php foreach ($orders as $order): ?>
<section class="card border-0 shadow-sm mb-4"><div class="card-body">
<div class="d-flex justify-content-between flex-wrap gap-2">
    <div>
        <h2 class="h5 mb-1">Commande <?= $escape($order->getOrderNumber()) ?></h2>
        <p class="small text-muted mb-0">Identifiant interne : #<?= $order->getId() ?></p>
        <?php if ($order->getCreatedAt() instanceof \DateTimeInterface): ?>
            <p class="small text-muted mb-0">Reçue le <?= $escape($order->getCreatedAt()->format('d/m/Y H:i')) ?></p>
        <?php endif; ?>
    </div>
    <span class="badge text-bg-secondary"><?= $escape($statusLabels[$order->getStatus()] ?? $order->getStatus()) ?></span>
</div>
<p class="mb-1 mt-3"><strong>Prestation :</strong> <?= $escape($serviceLabels[$order->getServiceType()] ?? $order->getServiceType()) ?> — <?= $escape($order->getDeliveryDate()) ?> à <?= $escape($order->getDeliveryTime()) ?></p>
<p class="small text-muted mb-1"><strong>Téléphone :</strong> <?= $escape($order->getContactPhone()) ?></p>
<p class="small text-muted mb-3"><strong>Lieu :</strong> <?= $escape($order->getDeliveryAddress()) ?><?= $order->getDeliveryCity() !== '' ? ' — ' . $escape($order->getDeliveryPostalCode() . ' ' . $order->getDeliveryCity()) : '' ?></p>
<p class="mb-1"><strong>Personnes :</strong> <?= $order->getNumberOfPeople() ?></p>
<p class="mb-1"><strong>Sous-total menu :</strong> <?= number_format($order->getMenuPrice(),2,',',' ') ?> €</p>
<?php if ($order->getDiscountRate() > 0): ?><p class="small text-success mb-1">Remise appliquée : <?= number_format($order->getDiscountRate(),0) ?> %</p><?php endif; ?>
<p class="mb-1"><strong>Frais de livraison :</strong> <?= number_format($order->getDeliveryCost(),2,',',' ') ?> €</p>
<p class="mb-1"><strong>Total :</strong> <?= number_format($order->getTotalPrice(),2,',',' ') ?> €</p>
<h3 class="h6 mt-4">Suivi</h3><ol class="small ps-3">
<?php $previousStatus = null; ?>
<?php foreach (($history[$order->getId()] ?? []) as $entry): ?>
<?php $label = $entry['status'] === $previousStatus ? 'Commande modifiée' : ($statusLabels[$entry['status']] ?? $entry['status']); $previousStatus = $entry['status']; ?>
<li><?= $escape($label) ?> — <?= $entry['changed_at'] instanceof \DateTimeInterface ? $escape($entry['changed_at']->format('d/m/Y H:i')) : '' ?></li>
<?php endforeach; ?>
</ol>
<?php if ($order->getStatus() === 'pending'): ?>
<hr><details><summary class="fw-semibold">Modifier la commande</summary>
<p class="small text-muted mt-2 mb-0">Tout peut être modifié sauf le menu, tant que la commande n’a pas été acceptée. Le prix est recalculé.</p>
<form method="post" action="/orders/<?= $order->getId() ?>/edit" class="row g-2 mt-1 order-edit-form">
<input type="hidden" name="csrf_token" value="<?= $escape($csrfToken) ?>">
<?php $fieldPrefix = 'customer'; require __DIR__ . '/_edit_fields.php'; ?>
<div class="col-12"><button class="btn btn-outline-primary btn-sm" type="submit">Enregistrer les modifications</button></div>
</form></details>
<?php endif; ?>
<?php if ($order->getStatus() === 'pending'): ?>
<form method="post" action="/orders/<?= $order->getId() ?>/status" class="d-inline"><input type="hidden" name="csrf_token" value="<?= $escape($csrfToken) ?>"><input type="hidden" name="status" value="cancelled"><input type="hidden" name="cancellation_reason" value="Annulation demandée par le client"><button class="btn btn-outline-danger btn-sm" data-confirm="Annuler cette commande ?">Annuler la commande</button></form>
<?php endif; ?>
<?php if ($order->getStatus() === 'completed' && empty($reviews[$order->getId()])): ?>
<hr><h3 class="h6">Votre avis</h3><form method="post" action="/orders/<?= $order->getId() ?>/review" class="row g-2">
<input type="hidden" name="csrf_token" value="<?= $escape($csrfToken) ?>"><div class="col-md-3"><label class="form-label" for="rating_<?= $order->getId() ?>">Note</label><select class="form-select" id="rating_<?= $order->getId() ?>" name="rating" required><option value="">Choisir</option><?php for($rating=1;$rating<=5;$rating++): ?><option value="<?= $rating ?>"><?= $rating ?>/5</option><?php endfor; ?></select></div>
<div class="col-md-9"><label class="form-label" for="comment_<?= $order->getId() ?>">Commentaire</label><textarea class="form-control" id="comment_<?= $order->getId() ?>" name="comment" required></textarea></div><div class="col-12"><button class="btn btn-primary btn-sm">Envoyer l'avis</button></div></form>
<?php elseif (!empty($reviews[$order->getId()])): ?><p class="small text-muted">Avis déjà transmis.</p><?php endif; ?>
</div></section>
<?php endforeach; ?>
<?php if ($orders === []): ?><div class="card border-0 shadow-sm"><div class="card-body text-center text-muted py-4">Vous n'avez pas encore de commande.</div></div><?php endif; ?>
</div>
</main>
