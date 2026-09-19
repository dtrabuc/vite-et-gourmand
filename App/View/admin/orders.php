<?php $labels=['pending'=>'En attente','accepted'=>'Acceptée','preparing'=>'En préparation','delivering'=>'En livraison','delivered'=>'Livrée','awaiting_return'=>'En attente de retour','completed'=>'Terminée','cancelled'=>'Annulée']; ?>
<main class="py-5"><div class="container">
<h1 class="h2 text-primary">Gestion des commandes</h1>
<form method="get" action="/admin/orders" class="row g-2 my-4"><div class="col-md-4"><label class="form-label">Client</label><input class="form-control" name="customer" value="<?= $escape($customer ?? '') ?>" placeholder="Nom ou email"></div><div class="col-md-4"><label class="form-label">Statut</label><select class="form-select" name="status"><option value="">Tous</option><?php foreach($labels as $key=>$label): ?><option value="<?= $key ?>" <?= ($selectedStatus ?? '')===$key?'selected':'' ?>><?= $escape($label) ?></option><?php endforeach; ?></select></div><div class="col-md-4 align-self-end"><button class="btn btn-primary">Filtrer</button></div></form>
<?php foreach($orders as $order): ?><section class="card border-0 shadow-sm mb-3"><div class="card-body">
<div class="d-flex justify-content-between"><h2 class="h5">Commande #<?= $order->getId() ?></h2><span><?= $escape($labels[$order->getStatus()] ?? $order->getStatus()) ?></span></div>
<p class="mb-1">Client #<?= $order->getUserId() ?> — prestation <?= $escape($order->getDeliveryDate()) ?> à <?= $escape($order->getDeliveryTime()) ?></p><p class="small text-muted mb-2">Matériel prêté : <?= $order->isEquipmentLoaned() ? "Oui" : "Non" ?></p><p>Total : <strong><?= number_format($order->getTotalPrice(),2,',',' ') ?> €</strong></p>
<?php
$next = match ($order->getStatus()) {
    'pending' => ['accepted', 'cancelled'],
    'accepted' => ['preparing', 'cancelled'],
    'preparing' => ['delivering', 'cancelled'],
    'delivering' => ['delivered', 'cancelled'],
    'delivered' => $order->isEquipmentLoaned() ? ['awaiting_return'] : ['completed'],
    'awaiting_return' => ['completed'],
    default => [],
};
if($next): ?>
<form method="post" action="/admin/orders/<?= $order->getId() ?>/status" class="row g-2"><input type="hidden" name="csrf_token" value="<?= $escape($csrfToken) ?>"><div class="col-md-3"><select class="form-select" name="status"><?php foreach($next as $s): ?><option value="<?= $s ?>"><?= $escape($labels[$s]) ?></option><?php endforeach; ?></select></div><div class="col-md-3"><input class="form-control" name="contact_mode" placeholder="Mode de contact"></div><div class="col-md-3"><input class="form-control" name="cancellation_reason" placeholder="Motif si annulation"></div><div class="col-md-3"><input class="form-control" name="notes" placeholder="Note"></div><div class="col-md-3 d-flex align-items-center"><div class="form-check"><input class="form-check-input" type="checkbox" name="equipment_loaned" value="1" id="equipment_<?= $order->getId() ?>" <?= $order->isEquipmentLoaned() ? "checked" : "" ?>><label class="form-check-label" for="equipment_<?= $order->getId() ?>">Matériel prêté</label></div></div><div class="col-12"><button class="btn btn-outline-primary btn-sm">Mettre à jour</button></div></form>
<?php endif; ?></div></section><?php endforeach; ?>
<?php if($orders===[]): ?><p class="text-muted">Aucune commande trouvée.</p><?php endif; ?></div></main>
