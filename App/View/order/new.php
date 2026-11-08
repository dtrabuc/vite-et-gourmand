<?php
$errors = is_array($errors ?? null) ? $errors : [];
$oldInput = is_array($oldInput ?? null) ? $oldInput : [];
$old = static function (string $key, mixed $default = '') use ($oldInput): string {
    return htmlspecialchars((string) ($oldInput[$key] ?? $default), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
};
$selectedMenu = $selectedMenu ?? null;
$selectedServiceType = (string) ($selectedServiceType ?? $oldInput['service_type'] ?? 'delivery');
$orderUser = $orderUser ?? null;
$initialPeople = $initialPeople ?? '';
?>
<main class="py-4 py-lg-5">
    <div class="container">
        <h1 class="h2 text-primary mb-1">Votre commande</h1>
        <p class="text-muted mb-4">Indiquez combien vous serez : nous vous montrons tout de suite les menus possibles et leur prix total.</p>

        <?php if ($errors !== []): ?>
            <div class="alert alert-danger" role="alert">
                <strong>La commande n’a pas pu être enregistrée.</strong>
                <ul class="mb-0 mt-2">
                    <?php foreach ($errors as $error): ?>
                        <?php if (is_string($error) && trim($error) !== ''): ?>
                            <li><?= $escape($error) ?></li>
                        <?php endif; ?>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>

        <form id="orderFinalForm" method="post" action="/orders" novalidate
              data-old-menu-id="<?= $selectedMenu !== null ? (int) $selectedMenu->getId() : '' ?>">
            <input type="hidden" name="csrf_token" value="<?= $escape($csrfToken) ?>">
            <input type="hidden" name="payment_method" value="cash_on_site">

            <div class="row g-4">
                <div class="col-lg-8">
                    <section class="card border-0 shadow-sm p-3 p-md-4 mb-4" aria-labelledby="stepMenu">
                        <h2 class="h4 text-primary" id="stepMenu">1. Nombre de personnes et menu</h2>

                        <div class="d-flex flex-wrap align-items-center gap-3 mb-3">
                            <label class="fw-semibold mb-0" for="number_of_people">Combien de personnes ?</label>
                            <div class="input-group people-stepper">
                                <button class="btn btn-outline-primary" type="button" data-step="-1" aria-label="Une personne de moins">−</button>
                                <input class="form-control text-center" id="number_of_people" name="number_of_people" type="number"
                                       min="1" max="999" inputmode="numeric" value="<?= $old('number_of_people', $initialPeople) ?>" required>
                                <button class="btn btn-outline-primary" type="button" data-step="1" aria-label="Une personne de plus">+</button>
                            </div>
<div class="people-quick" role="group" aria-label="Choix rapide du nombre de personnes">
                                <?php foreach ([1, 2, 4, 6, 10, 15, 20] as $n): ?>
                                    <button type="button" class="btn btn-chip" data-people="<?= $n ?>"><?= $n ?></button>
                                <?php endforeach; ?>
                            </div>
                            <a class="small" href="/quote">Plus de 50 personnes ou besoin particulier ? Demander un devis</a>
                        </div>

                        <p id="menuAvailabilityMessage" class="small text-muted mb-2" aria-live="polite">Indiquez le nombre de personnes pour voir les menus.</p>
                        <div id="menuOptions" class="menu-options" role="radiogroup" aria-label="Menus"></div>
                        <noscript><p class="alert alert-warning">JavaScript est nécessaire pour choisir un menu.</p></noscript>
                    </section>

                    <section class="card border-0 shadow-sm p-3 p-md-4 mb-4 d-none" id="compositionSection" aria-labelledby="stepCompo">
                        <h2 class="h4 text-primary" id="stepCompo">2. Composez votre menu</h2>
                        <p class="small text-muted">Prix inchangé. Pour chaque convive : choisissez parmi les options, remplacez un plat par un autre, ou demandez une variante sans un allergène.</p>
                        <div id="menuComposition" class="composition"></div>
                        <p id="compositionMessage" class="small text-danger mb-2" aria-live="polite"></p>
                        <label class="form-label" for="customization">Allergies ou précisions pour la cuisine <span class="text-muted">(facultatif)</span></label>
                        <textarea class="form-control" id="customization" name="customization" rows="2" maxlength="1000"
                                  placeholder="Ex. : 1 convive allergique aux fruits à coque, une part sans sel…"><?= $old('customization') ?></textarea>
                        <p class="form-text mb-0">Les demandes « sans allergène » sont préparées à part, mais nous ne pouvons pas garantir l’absence totale de traces. Ne mentionnez aucun nom.</p>
                        <div class="form-check mt-2">
                            <input class="form-check-input" type="checkbox" id="allergy_consent" name="allergy_consent" value="1" <?= ($oldInput['allergy_consent'] ?? '') === '1' ? 'checked' : '' ?>>
                            <label class="form-check-label small" for="allergy_consent">J’accepte que ces informations de santé (allergies, plats sans allergène) soient utilisées pour préparer ma commande. <a href="/confidentialite" target="_blank" rel="noopener">En savoir plus</a></label>
                        </div>
                    </section>

                    <section class="card border-0 shadow-sm p-3 p-md-4 mb-4" aria-labelledby="stepService">
                        <h2 class="h4 text-primary" id="stepService">3. Livraison, retrait ou sur place</h2>
                        <div class="row g-2">
                            <?php foreach ([
                                'delivery' => 'Offerte dans Bordeaux, 5 € + 0,59 €/km jusqu’à 25 km. Au-delà, sur acceptation.',
                                'pickup' => 'Gratuit, à notre adresse à Bordeaux.',
                                'on_site' => 'Gratuit, repas servi chez nous.',
                            ] as $type => $description): ?>
                                <?php $label = \App\Core\Labels::serviceType($type); ?>
                                <div class="col-md-4">
                                    <label class="form-check h-100 border rounded p-3 service-type-option" for="service_type_<?= $type ?>">
                                        <input class="form-check-input me-2" type="radio" id="service_type_<?= $type ?>" name="service_type"
                                               value="<?= $type ?>" <?= $selectedServiceType === $type ? 'checked' : '' ?>>
                                        <strong><?= $escape($label) ?></strong>
                                        <span class="d-block small text-muted mt-1"><?= $escape($description) ?></span>
                                    </label>
                                </div>
                            <?php endforeach; ?>
                        </div>

                        <div id="deliveryFields" class="row g-3 mt-1">
                            <div class="col-12">
                                <div class="address-autocomplete-wrap">
                                    <label class="form-label" for="delivery_address">Adresse de livraison (numéro et rue)</label>
                                    <input class="form-control" id="delivery_address" name="delivery_address" autocomplete="off"
                                           placeholder="Ex. : 12 cours de l’Intendance" value="<?= $old('delivery_address', $orderUser?->getAddress() ?? '') ?>">
                                    <div id="addressSuggestions" class="list-group address-suggestions d-none" role="listbox" aria-label="Adresses trouvées"></div>
                                </div>
                                <div class="form-text">Commencez à taper puis choisissez votre adresse dans la liste (données OpenStreetMap).</div>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label" for="delivery_postal_code">Code postal</label>
                                <input class="form-control" id="delivery_postal_code" name="delivery_postal_code" maxlength="10" inputmode="numeric" value="<?= $old('delivery_postal_code') ?>">
                            </div>
                            <div class="col-md-8">
                                <label class="form-label" for="delivery_city">Ville</label>
                                <input class="form-control" id="delivery_city" name="delivery_city" value="<?= $old('delivery_city') ?>" placeholder="Bordeaux">
                            </div>

                            <div class="col-12">
                                <div id="addressStatus" class="address-status d-none" role="status" aria-live="polite"></div>

                                <div id="outsideZoneChoice" class="outside-choice d-none">
                                    <p class="fw-semibold mb-2">Votre adresse est hors de notre zone de livraison. Que préférez-vous ?</p>
                                    <div class="d-flex flex-column flex-sm-row gap-2">
                                        <button type="button" class="btn btn-outline-primary" id="choosePickup">Retrait gratuit à Bordeaux</button>
                                        <label class="btn btn-outline-primary mb-0" for="accept_outside_zone">
                                            <input class="form-check-input me-1" type="checkbox" id="accept_outside_zone" name="accept_outside_zone" value="1" <?= ($oldInput['accept_outside_zone'] ?? '') === '1' ? 'checked' : '' ?>>
                                            Être livré quand même : <strong id="outsideZoneCost">—</strong>
                                        </label>
                                    </div>
                                </div>

                                <div id="addressManualWrap" class="form-check mt-2 d-none">
                                    <input class="form-check-input" type="checkbox" id="address_manual" name="address_manual" value="1" <?= ($oldInput['address_manual'] ?? '') === '1' ? 'checked' : '' ?>>
                                    <label class="form-check-label" for="address_manual">Mon adresse est correcte, je la confirme telle que saisie.</label>
                                </div>
                            </div>

                            <div class="col-12">
                                <label class="form-label" for="delivery_instructions">Instructions pour le livreur <span class="text-muted">(facultatif : bâtiment, étage, digicode)</span></label>
                                <textarea class="form-control" id="delivery_instructions" name="delivery_instructions" rows="2"><?= $old('delivery_instructions') ?></textarea>
                            </div>
                        </div>
                        <p id="pickupLocationNotice" class="alert alert-info mt-3 mb-0 d-none">
                            <strong>Adresse :</strong> Vite &amp; Gourmand, Bordeaux (indiquée dans la confirmation).
                        </p>
                    </section>

                    <section class="card border-0 shadow-sm p-3 p-md-4 mb-4" aria-labelledby="stepDate">
                        <h2 class="h4 text-primary" id="stepDate">4. Date et heure</h2>
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label" for="delivery_date">Date</label>
                                <input class="form-control" id="delivery_date" name="delivery_date" type="date"
                                       min="<?= date('Y-m-d') ?>" value="<?= $old('delivery_date') ?>" required>
                                <div id="dateAvailabilityMessage" class="form-text">Les jours fermés n’ont pas de créneau.</div>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label" for="delivery_time" id="serviceTimeLabel">Heure</label>
                                <select class="form-select" id="delivery_time" name="delivery_time" data-old="<?= $old('delivery_time') ?>" required disabled>
                                    <option value="">Choisissez d’abord une date</option>
                                </select>
                                <div class="form-text">Créneaux de 15 minutes pendant nos horaires d’ouverture.</div>
                            </div>
                        </div>
                    </section>

                    <section class="card border-0 shadow-sm p-3 p-md-4 mb-4" aria-labelledby="stepContact">
                        <h2 class="h4 text-primary" id="stepContact">5. Contact</h2>
                        <?php if ($orderUser !== null): ?>
                            <p class="small text-muted">
                                Commande au nom de <strong><?= $escape($orderUser->getFirstName() . ' ' . $orderUser->getLastName()) ?></strong>
                                (<?= $escape($orderUser->getEmail()) ?>). <a href="/profile">Modifier mon profil</a>
                            </p>
                        <?php endif; ?>
                        <div class="col-md-6">
                            <label class="form-label" for="contact_phone">Téléphone le jour de la prestation</label>
                            <input class="form-control" id="contact_phone" name="contact_phone" type="tel" inputmode="tel" autocomplete="tel"
                                   data-phone-input aria-describedby="contact_phone_error"
                                   value="<?= $old('contact_phone', $orderUser?->getGsm() ?: $orderUser?->getPhone() ?? '') ?>" required>
                            <?php /* zone d'erreur remplie par phone-input.js */ ?>
                            <div class="invalid-feedback" id="contact_phone_error"></div>
                        </div>
                    </section>
                </div>

                <div class="col-lg-4">
                    <aside class="order-summary card border-0 shadow-sm p-3 p-md-4" aria-labelledby="orderPriceTitle">
                        <h2 class="h5" id="orderPriceTitle">Récapitulatif</h2>
                        <p id="orderSummaryMenu" class="fw-semibold mb-2">Aucun menu choisi</p>
                        <dl class="order-summary__lines small mb-2" aria-live="polite">
                            <div><dt id="orderMenuLabel">Menu</dt><dd id="orderMenuPrice">—</dd></div>
                            <div id="orderDiscountRow" hidden><dt>Remise −10 %</dt><dd id="orderDiscount">—</dd></div>
                            <div><dt>Livraison <span id="orderDeliveryDistance" class="text-muted"></span></dt><dd id="orderDeliveryPrice">—</dd></div>
                            <div class="order-summary__total"><dt>Total</dt><dd id="orderPreviewTotal">—</dd></div>
                        </dl>
                        <p id="orderPriceMessage" class="small text-muted">Le total est recalculé par nos soins à la validation.</p>
                        <p class="small mb-2"><strong>Paiement :</strong> en espèces à la remise de la commande.</p>
                        <p class="small text-muted mb-3"><?= $escape(\App\Service\OrderModificationPolicy::rule()) ?></p>
                        <div id="orderSubmitMessage" class="alert alert-warning small d-none" role="alert"></div>
                        <button class="btn btn-primary w-100 btn-lg" type="submit">Valider la commande</button>
                        <a class="btn btn-link w-100 mt-1" href="/menus">Retour aux menus</a>
                    </aside>
                </div>
            </div>
        </form>
    </div>
</main>
