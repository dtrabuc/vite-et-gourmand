<?php
/**
 * Champs de modification d'une commande (tout sauf le menu).
 * Variables attendues : $order (App\Entity\Order), $escape, $fieldPrefix (identifiants uniques).
 */
$fid = static fn (string $name): string => $fieldPrefix . '_' . $name . '_' . $order->getId();
$currentService = $order->getServiceType();
$isDelivery = $currentService === 'delivery';
?>
<div class="col-md-3">
    <label class="form-label" for="<?= $fid('people') ?>">Personnes</label>
    <input class="form-control" id="<?= $fid('people') ?>" type="number" min="1" name="number_of_people"
           value="<?= $order->getNumberOfPeople() ?>" required>
</div>
<div class="col-md-3">
    <label class="form-label" for="<?= $fid('date') ?>">Date</label>
    <input class="form-control order-edit-date" id="<?= $fid('date') ?>" type="date" name="delivery_date"
           value="<?= $escape($order->getDeliveryDate()) ?>" min="<?= date('Y-m-d') ?>" required>
</div>
<div class="col-md-3">
    <label class="form-label" for="<?= $fid('time') ?>">Créneau</label>
    <select class="form-select order-edit-time" id="<?= $fid('time') ?>" name="delivery_time"
            data-current-time="<?= $escape($order->getDeliveryTime()) ?>" required>
        <option value="">Choisir</option>
    </select>
    <div class="form-text text-danger order-edit-time-message" aria-live="polite"></div>
</div>
<div class="col-md-3">
    <label class="form-label" for="<?= $fid('phone') ?>">Téléphone</label>
    <input class="form-control" id="<?= $fid('phone') ?>" type="tel" name="contact_phone" autocomplete="tel"
           inputmode="tel" data-phone-input aria-describedby="<?= $fid('phone') ?>_error"
           value="<?= $escape($order->getContactPhone()) ?>" required>
    <?php /* erreur du champ téléphone, remplie par phone-input.js */ ?>
    <div class="invalid-feedback" id="<?= $fid('phone') ?>_error"></div>
</div>
<div class="col-12">
    <fieldset>
        <legend class="form-label fs-6">Mode de prestation</legend>
        <?php foreach (\App\Core\Labels::SERVICE_TYPE as $type => $label): ?>
            <div class="form-check form-check-inline">
                <input class="form-check-input order-edit-service" type="radio" name="service_type"
                       id="<?= $fid('service_' . $type) ?>" value="<?= $type ?>" <?= $currentService === $type ? 'checked' : '' ?>>
                <label class="form-check-label" for="<?= $fid('service_' . $type) ?>"><?= $label ?></label>
            </div>
        <?php endforeach; ?>
    </fieldset>
</div>
<div class="col-12 order-edit-delivery <?= $isDelivery ? '' : 'd-none' ?>">
    <div class="row g-2">
        <div class="col-md-6">
            <label class="form-label" for="<?= $fid('address') ?>">Adresse</label>
            <input class="form-control" id="<?= $fid('address') ?>" name="delivery_address"
                   value="<?= $isDelivery ? $escape($order->getDeliveryAddress()) : '' ?>" <?= $isDelivery ? 'required' : '' ?>>
        </div>
        <div class="col-md-2">
            <label class="form-label" for="<?= $fid('postal') ?>">Code postal</label>
            <input class="form-control" id="<?= $fid('postal') ?>" name="delivery_postal_code" maxlength="10"
                   value="<?= $isDelivery ? $escape($order->getDeliveryPostalCode()) : '' ?>" <?= $isDelivery ? 'required' : '' ?>>
        </div>
        <div class="col-md-2">
            <label class="form-label" for="<?= $fid('city') ?>">Ville</label>
            <input class="form-control" id="<?= $fid('city') ?>" name="delivery_city"
                   value="<?= $isDelivery ? $escape($order->getDeliveryCity()) : '' ?>" <?= $isDelivery ? 'required' : '' ?>>
        </div>
        <div class="col-md-2">
            <p class="form-text mb-0">Nouvelle adresse vérifiée sur la carte. Offerte dans Bordeaux, sinon 5 € + 0,59 €/km.</p>
        </div>
        <div class="col-md-6">
            <div class="form-check">
                <input class="form-check-input" type="checkbox" name="address_manual" value="1" id="<?= $fid('manual') ?>">
                <label class="form-check-label small" for="<?= $fid('manual') ?>">Adresse introuvable sur la carte mais correcte (saisie manuelle)</label>
            </div>
        </div>
        <div class="col-md-6">
            <div class="form-check">
                <input class="form-check-input" type="checkbox" name="accept_outside_zone" value="1" id="<?= $fid('outside') ?>">
                <label class="form-check-label small" for="<?= $fid('outside') ?>">Accepter la livraison hors zone (plus de <?= (int) \App\Service\DeliveryDistanceService::ZONE_RADIUS_KM ?> km), au tarif kilométrique</label>
            </div>
        </div>
        <?php if (($fieldPrefix ?? '') === 'staff' && $order->isDeliveryCostPending()): ?>
            <div class="col-md-6">
                <label class="form-label" for="<?= $fid('cost') ?>">Frais de livraison confirmés avec le client (€)</label>
                <input class="form-control" id="<?= $fid('cost') ?>" name="delivery_cost_override" type="number" min="0" max="1000" step="0.01">
                <div class="form-text">Adresse saisie à la main : indiquez les frais convenus par téléphone.</div>
            </div>
        <?php endif; ?>
        <div class="col-12">
            <label class="form-label" for="<?= $fid('instructions') ?>">Instructions pour le livreur (facultatif)</label>
            <textarea class="form-control" id="<?= $fid('instructions') ?>" name="delivery_instructions" rows="2"><?= $escape($order->getDeliveryInstructions() ?? '') ?></textarea>
        </div>
    </div>
</div>
<?php /* choix dans le menu (composition.js) : répartition, remplacements, variantes sans allergène */ ?>
<?php $orderItems = $items[$order->getId()] ?? []; ?>
<div class="col-12">
    <h3 class="h6 mt-2 mb-1">Choix dans le menu</h3>
    <div class="composition composition--compact" data-composition data-menu-id="<?= (int) $order->getMenuId() ?>"
         data-items="<?= $escape(json_encode($orderItems, JSON_UNESCAPED_UNICODE)) ?>"></div>
    <p class="small text-danger mb-0" data-composition-message aria-live="polite"></p>
</div>
<div class="col-12">
    <label class="form-label" for="<?= $fid('customization') ?>">Allergies ou précisions pour la cuisine (facultatif)</label>
    <textarea class="form-control" id="<?= $fid('customization') ?>" name="customization" rows="2" maxlength="1000"><?= $escape($order->getCustomization() ?? '') ?></textarea>
    <?php if (($fieldPrefix ?? '') === 'customer'): ?>
        <div class="form-check mt-1">
            <input class="form-check-input" type="checkbox" id="<?= $fid('consent') ?>" name="allergy_consent" value="1" checked>
            <label class="form-check-label small" for="<?= $fid('consent') ?>">J’accepte que ces informations de santé (allergies, plats sans allergène) soient utilisées pour préparer ma commande.</label>
        </div>
    <?php endif; ?>
</div>
