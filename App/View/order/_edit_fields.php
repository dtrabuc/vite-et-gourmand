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
            <p class="form-text mb-0">Frais recalculés automatiquement : offerts dans Bordeaux, sinon 5 € + 0,59 €/km.</p>
        </div>
        <div class="col-12">
            <label class="form-label" for="<?= $fid('instructions') ?>">Instructions pour le livreur (facultatif)</label>
            <textarea class="form-control" id="<?= $fid('instructions') ?>" name="delivery_instructions" rows="2"><?= $escape($order->getDeliveryInstructions() ?? '') ?></textarea>
        </div>
    </div>
</div>
