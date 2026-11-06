<?php
$old = is_array($old ?? null) ? $old : [];
?>
<main class="py-5">
    <div class="container">
        <section class="row justify-content-center">
            <div class="col-xl-9">
                <div class="card border-0 shadow-sm">
                    <div class="card-body p-4 p-lg-5">
                        <span class="badge bg-primary-subtle text-primary">Événements importants</span>
                        <h1 class="h2 text-primary mt-3">Demander un devis traiteur</h1>
                        <p class="text-muted">
                            Pour les mariages, séminaires, repas d'entreprise ou toute prestation
                            sur mesure, nous préparons une proposition adaptée. Cette demande est facultative :
                            tous nos menus peuvent aussi être <a href="/menus">commandés directement</a>.
                        </p>

                        <div class="alert alert-info">
                            Indiquez le nombre de personnes, la date, puis choisissez retrait,
                            livraison ou prestation sur place. Notre équipe vous répondra par email.
                        </div>

                        <form method="post" action="/quote" class="row g-3">
                            <input type="hidden" name="csrf_token" value="<?= $escape($csrfToken) ?>">

                            <div class="col-md-6">
                                <label class="form-label" for="quote_first_name">Prénom</label>
                                <input class="form-control" id="quote_first_name" name="first_name"
                                       value="<?= $escape($old['first_name'] ?? '') ?>" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label" for="quote_last_name">Nom</label>
                                <input class="form-control" id="quote_last_name" name="last_name"
                                       value="<?= $escape($old['last_name'] ?? '') ?>" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label" for="quote_email">Email</label>
                                <input class="form-control" id="quote_email" type="email" name="email"
                                       value="<?= $escape($old['email'] ?? '') ?>" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label" for="quote_phone">Téléphone</label>
                                <?php /* pays choisi dans la liste d'intl-tel-input, plus de select à part */ ?>
                                <input class="form-control" id="quote_phone" name="phone" type="tel"
                                       autocomplete="tel" inputmode="tel" data-phone-input
                                       aria-describedby="quote_phone_error"
                                       value="<?= $escape($old['phone'] ?? '') ?>" required>
                                <div class="invalid-feedback" id="quote_phone_error"></div>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label" for="quote_company">Société</label>
                                <input class="form-control" id="quote_company" name="company"
                                       value="<?= $escape($old['company'] ?? '') ?>">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label" for="quote_people">Nombre de personnes</label>
                                <input class="form-control" id="quote_people" name="number_of_people"
                                       type="number" min="1" required
                                       value="<?= $escape($old['number_of_people'] ?? '') ?>">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label" for="quote_date">Date de réception</label>
                                <input class="form-control" id="quote_date" name="event_date"
                                       type="date" min="<?= date('Y-m-d') ?>" required
                                       value="<?= $escape($old['event_date'] ?? '') ?>">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label" for="quote_service">Prestation</label>
                                <select class="form-select" id="quote_service" name="service_type" required>
                                    <option value="pickup" <?= ($old['service_type'] ?? '') === 'pickup' ? 'selected' : '' ?>>À emporter / retrait</option>
                                    <option value="delivery" <?= ($old['service_type'] ?? '') === 'delivery' ? 'selected' : '' ?>>Livraison</option>
                                    <option value="on_site" <?= ($old['service_type'] ?? '') === 'on_site' ? 'selected' : '' ?>>Prestation sur place</option>
                                </select>
                            </div>
                            <div class="col-md-8">
                                <label class="form-label" for="quote_location">Lieu de réception</label>
                                <input class="form-control" id="quote_location" name="event_location"
                                       value="<?= $escape($old['event_location'] ?? '') ?>">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label" for="quote_postal">Code postal</label>
                                <input class="form-control" id="quote_postal" name="postal_code"
                                       maxlength="10" value="<?= $escape($old['postal_code'] ?? '') ?>">
                            </div>
                            <div class="col-12">
                                <label class="form-label" for="quote_details">Votre besoin</label>
                                <textarea class="form-control" id="quote_details" name="request_details"
                                          rows="7"
                                          placeholder="Type d’événement, menu souhaité, boissons, contraintes alimentaires, service, matériel…"><?= $escape($old['request_details'] ?? '') ?></textarea>
                            </div>
                            <div class="col-12">
                                <button class="btn btn-primary btn-lg" type="submit">
                                    Envoyer ma demande de devis
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </section>
    </div>
</main>
