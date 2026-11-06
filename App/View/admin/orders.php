<?php $labels = \App\Core\Labels::ORDER_STATUS; ?>
<main class="py-5">
    <div class="container">
        <div class="mb-4">
            <span class="badge bg-primary-subtle text-primary">Espace équipe</span>
            <h1 class="h2 text-primary mt-2 mb-1">Gestion des commandes</h1>
            <p class="text-muted mb-0">Suivi des commandes clients et des transitions de prestation.</p>
        </div>

        <form method="get" action="/admin/orders" class="card border-0 shadow-sm mb-4">
            <div class="card-body">
                <div class="row g-3 align-items-end">
                    <div class="col-12 col-md-6 col-xl-4">
                        <label class="form-label" for="adminCustomer">Client</label>
                        <input class="form-control" id="adminCustomer" name="customer"
                               value="<?= $escape($customer ?? '') ?>" placeholder="Nom ou email">
                    </div>
                    <div class="col-12 col-md-6 col-xl-4">
                        <label class="form-label" for="adminStatus">Statut</label>
                        <select class="form-select" id="adminStatus" name="status">
                            <option value="">Tous</option>
                            <?php foreach ($labels as $key => $label): ?>
                                <option value="<?= $escape($key) ?>" <?= ($selectedStatus ?? '') === $key ? 'selected' : '' ?>>
                                    <?= $escape($label) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-12 col-xl-4 d-grid d-xl-flex justify-content-xl-end">
                        <button class="btn btn-primary" type="submit">Filtrer</button>
                    </div>
                </div>
            </div>
        </form>

        <?php foreach ($orders as $order): ?><section class="card border-0 shadow-sm mb-4" id="order-<?= $order->getId() ?>">
    <div class="card-body p-4">
        <div class="d-flex flex-column flex-md-row justify-content-between gap-3 mb-3">
            <div>
                <h2 class="h5 mb-1">Commande <?= $escape($order->getOrderNumber()) ?></h2>
                <p class="small text-muted mb-0">Identifiant interne : #<?= $order->getId() ?></p>
            </div>
            <span class="badge text-bg-secondary align-self-start">
                <?= $escape($labels[$order->getStatus()] ?? $order->getStatus()) ?>
            </span>
        </div>

        <div class="row g-3 mb-4">
            <div class="col-12 col-sm-6 col-lg-3">
                <div class="summary-box h-100 p-3">
                    <span class="small text-muted d-block">Client</span>
                    <strong><?= $escape($order->getCustomerName() ?? ('#' . $order->getUserId())) ?></strong>
                </div>
            </div>
            <div class="col-12 col-sm-6 col-lg-3">
                <div class="summary-box h-100 p-3">
                    <span class="small text-muted d-block">Prestation</span>
                    <strong><?= $escape($order->getDeliveryDate()) ?></strong>
                    <span class="small d-block"><?= $escape($order->getDeliveryTime()) ?></span>
                </div>
            </div>
            <div class="col-12 col-sm-6 col-lg-3">
                <div class="summary-box h-100 p-3">
                    <span class="small text-muted d-block">Type</span>
                    <strong><?= $escape(\App\Core\Labels::serviceType($order->getServiceType())) ?></strong>
                </div>
            </div>
            <div class="col-12 col-sm-6 col-lg-3">
                <div class="summary-box h-100 p-3">
                    <span class="small text-muted d-block">Total</span>
                    <strong><?= number_format($order->getTotalPrice(), 2, ',', ' ') ?> €</strong>
                </div>
            </div>
        </div>

        <div class="row g-3 mb-3">
            <div class="col-12 col-lg-6">
                <p class="small mb-1"><strong>E-mail :</strong> <?= $escape($order->getCustomerEmail() ?? '') ?></p>
                <p class="small mb-1"><strong>Téléphone :</strong> <?= $escape($order->getContactPhone()) ?></p>
                <p class="small mb-0"><strong>Lieu :</strong> <?= $escape($order->getDeliveryAddress()) ?>
                    <?= $order->getDeliveryCity() !== '' ? ' — ' . $escape($order->getDeliveryPostalCode() . ' ' . $order->getDeliveryCity()) : '' ?>
                </p>
            </div>
            <div class="col-12 col-lg-6">
                <p class="small mb-1"><strong>Personnes :</strong> <?= $order->getNumberOfPeople() ?></p>
                <p class="small mb-1"><strong>Menu :</strong> <?= $escape($order->getMenuTitle() ?? ('#' . $order->getMenuId())) ?></p>
                <p class="small mb-0"><strong>Total :</strong> <?= number_format($order->getTotalPrice(), 2, ',', ' ') ?> €</p>
            </div>
        </div>

        <div class="row g-3 mb-3">
            <div class="col-12 col-lg-6">
                <p class="small mb-1"><strong>Référence :</strong> <?= $escape($order->getOrderNumber()) ?></p>
                <?php if ($order->getCreatedAt() instanceof \DateTimeInterface): ?>
                    <p class="small mb-1"><strong>Commande reçue :</strong> <?= $escape($order->getCreatedAt()->format('d/m/Y H:i')) ?></p>
                <?php endif; ?>
                <p class="small mb-0"><strong>Statut en base :</strong> <?= $escape($labels[$order->getStatus()] ?? $order->getStatus()) ?></p>
            </div>
            <div class="col-12 col-lg-6">
                <p class="small mb-1"><strong>Prix menu enregistré :</strong> <?= number_format($order->getMenuPrice(), 2, ',', ' ') ?> €</p>
                <p class="small mb-1"><strong>Livraison enregistrée :</strong> <?= number_format($order->getDeliveryCost(), 2, ',', ' ') ?> €</p>
                <p class="small mb-0"><strong>Remise enregistrée :</strong> <?= number_format($order->getDiscountRate(), 0) ?> %</p>
            </div>
        </div>
        <p class="small text-muted mb-3">
            Matériel prêté : <?= $order->isEquipmentLoaned() ? 'Oui' : 'Non' ?>
        </p>

        <details class="mb-3">
            <summary class="fw-semibold small">Historique de la commande</summary>
            <ol class="small ps-3 mt-2 mb-0">
                <?php foreach (($history[$order->getId()] ?? []) as $entry): ?>
                    <li>
                        <?= $escape($labels[$entry['status']] ?? $entry['status']) ?>
                        — <?= $entry['changed_at'] instanceof \DateTimeInterface ? $escape($entry['changed_at']->format('d/m/Y H:i')) : '' ?>
                        <?php if (($entry['notes'] ?? '') !== ''): ?>
                            <span class="d-block text-muted"><?= $escape($entry['notes']) ?></span>
                        <?php endif; ?>
                    </li>
                <?php endforeach; ?>
            </ol>
        </details>

        <?php if (in_array($order->getStatus(), $editableStatuses ?? [], true)): ?>
            <details class="mb-3">
                <summary class="fw-semibold small">Modifier la commande (après contact avec le client)</summary>
                <form method="post" action="/admin/orders/<?= $order->getId() ?>/edit" class="row g-2 mt-2 order-edit-form">
                    <input type="hidden" name="csrf_token" value="<?= $escape($csrfToken) ?>">
                    <?php $fieldPrefix = 'staff'; require dirname(__DIR__) . '/order/_edit_fields.php'; ?>
                    <div class="col-md-4">
                        <label class="form-label" for="edit_contact_<?= $order->getId() ?>">Mode de contact du client</label><?= info_tip('Obligatoire : contactez le client (téléphone ou e-mail) avant de modifier sa commande.') ?>
                        <select class="form-select" id="edit_contact_<?= $order->getId() ?>" name="contact_mode" required>
                            <option value="">Choisir</option>
                            <option value="Téléphone">Téléphone</option>
                            <option value="E-mail">E-mail</option>
                            <option value="Sur place">Sur place</option>
                        </select>
                    </div>
                    <div class="col-md-8">
                        <label class="form-label" for="edit_reason_<?= $order->getId() ?>">Motif de la modification</label>
                        <input class="form-control" id="edit_reason_<?= $order->getId() ?>" name="modification_reason" maxlength="255" required>
                    </div>
                    <div class="col-12">
                        <button class="btn btn-outline-primary btn-sm" type="submit">Enregistrer la modification</button>
                    </div>
                </form>
            </details>
        <?php endif; ?>
<?php
/* statuts possibles : table OrderService::ALLOWED_TRANSITIONS passée par le contrôleur */
$next = $transitions[$order->getStatus()] ?? [];
// livrée sans matériel prêté : « Terminée » en premier (le serveur tranche avec la case « Matériel prêté »)
if ($order->getStatus() === 'delivered' && !$order->isEquipmentLoaned()) {
    $next = array_reverse($next);
}
if($next): ?>
<form method="post" action="/admin/orders/<?= $order->getId() ?>/status" class="row g-2"><input type="hidden" name="csrf_token" value="<?= $escape($csrfToken) ?>"><div class="col-12 col-md-6 col-xl-3">
<label class="form-label" for="status_<?= $order->getId() ?>">Statut</label>
<select class="form-select" id="status_<?= $order->getId() ?>" name="status"><?php foreach($next as $s): ?><option value="<?= $s ?>"><?= $escape($labels[$s]) ?></option><?php endforeach; ?></select></div>
<div class="col-12 col-md-6 col-xl-3">
<label class="form-label" for="contact_<?= $order->getId() ?>">Mode de contact</label><?= info_tip('Obligatoire : comment le client a été prévenu (téléphone, e-mail) avant tout changement.') ?>
<input class="form-control" id="contact_<?= $order->getId() ?>" name="contact_mode" placeholder="Téléphone, email..." required>
</div>
<div class="col-12 col-md-6 col-xl-3">
<label class="form-label" for="reason_<?= $order->getId() ?>">Motif d’annulation</label>
<input class="form-control" id="reason_<?= $order->getId() ?>" name="cancellation_reason" placeholder="Obligatoire si annulation">
</div>
<div class="col-12 col-md-6 col-xl-3">
<label class="form-label" for="notes_<?= $order->getId() ?>">Note interne</label>
<input class="form-control" id="notes_<?= $order->getId() ?>" name="notes" placeholder="Note complémentaire">
</div><div class="col-12"><div class="form-check"><input class="form-check-input" type="checkbox" name="equipment_loaned" value="1" id="equipment_<?= $order->getId() ?>" <?= $order->isEquipmentLoaned() ? "checked" : "" ?>><label class="form-check-label" for="equipment_<?= $order->getId() ?>">Matériel prêté</label><?= info_tip('Si coché, la commande passe par « En attente du retour de matériel » : le client a 10 jours ouvrés pour le rendre, sinon 600 € de frais.') ?></div></div><div class="col-12"><button class="btn btn-outline-primary" type="submit">Mettre à jour</button></div></form>
<?php endif; ?></div></section><?php endforeach; ?>
<?php if($orders===[]): ?><p class="text-muted">Aucune commande trouvée.</p><?php endif; ?></div></main>
