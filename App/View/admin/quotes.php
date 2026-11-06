<?php
$labels = \App\Core\Labels::QUOTE_STATUS;
$serviceLabels = \App\Core\Labels::SERVICE_TYPE;
?>
<main class="py-5">
    <div class="container">
        <div class="d-flex justify-content-between align-items-center gap-3 mb-4">
            <div>
                <span class="badge bg-primary-subtle text-primary">Équipe</span>
                <h1 class="h2 text-primary mt-2 mb-1">Demandes de devis</h1>
                <p class="text-muted mb-0">Traitez les événements importants et répondez directement au client.</p>
            </div>
            <form method="get" action="/admin/quotes" class="d-flex gap-2">
                <label class="visually-hidden" for="quoteStatusFilter">Filtrer par statut</label>
                <select class="form-select" id="quoteStatusFilter" name="status">
                    <option value="">Tous les statuts</option>
                    <?php foreach ($labels as $value => $label): ?>
                        <option value="<?= $escape($value) ?>" <?= ($selectedStatus ?? '') === $value ? 'selected' : '' ?>>
                            <?= $escape($label) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <button class="btn btn-outline-primary" type="submit">Filtrer</button>
            </form>
        </div>

        <?php foreach ($quotes as $quote): ?>
            <section class="card border-0 shadow-sm mb-4">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-start gap-3 flex-wrap">
                        <div>
                            <h2 class="h5 mb-1">
                                Demande #<?= (int) $quote['id'] ?>
                                — <?= $escape($quote['contact_name']) ?>
                            </h2>
                            <p class="small text-muted mb-0">
                                <?= $escape($quote['email']) ?>
                                <?php if (!empty($quote['phone'])): ?>
                                    · <?= $escape($quote['phone']) ?>
                                <?php endif; ?>
                            </p>
                        </div>
                        <span class="badge text-bg-secondary">
                            <?= $escape($labels[$quote['status']] ?? $quote['status']) ?>
                        </span>
                    </div>

                    <hr>

                    <div class="row g-3">
                        <div class="col-md-3"><strong>Date</strong><div><?= $escape($quote['event_date']) ?></div></div>
                        <div class="col-md-3"><strong>Personnes</strong><div><?= (int) $quote['number_of_people'] ?></div></div>
                        <div class="col-md-3"><strong>Prestation</strong><div><?= $escape($serviceLabels[$quote['service_type']] ?? $quote['service_type']) ?></div></div>
                        <div class="col-md-3"><strong>Société</strong><div><?= $escape($quote['company'] ?? '—') ?></div></div>
                        <div class="col-md-8"><strong>Lieu</strong><div><?= $escape($quote['event_location'] ?? '—') ?></div></div>
                        <div class="col-md-4"><strong>Code postal</strong><div><?= $escape($quote['postal_code'] ?? '—') ?></div></div>
                        <div class="col-12">
                            <strong>Demande</strong>
                            <div class="bg-light rounded p-3 mt-1"><?= nl2br($escape($quote['request_details'] ?? 'Aucun détail complémentaire.')) ?></div>
                        </div>
                    </div>

                    <form method="post" action="/admin/quotes/<?= (int) $quote['id'] ?>/status" class="row g-3 mt-2">
                        <input type="hidden" name="csrf_token" value="<?= $escape($csrfToken) ?>">
                        <div class="col-md-4">
                            <label class="form-label" for="quote_status_<?= (int) $quote['id'] ?>">Statut</label>
                            <select class="form-select" id="quote_status_<?= (int) $quote['id'] ?>" name="status">
                                <?php foreach ($labels as $value => $label): ?>
                                    <option value="<?= $escape($value) ?>" <?= $quote['status'] === $value ? 'selected' : '' ?>>
                                        <?= $escape($label) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-8">
                            <label class="form-label" for="quote_reply_<?= (int) $quote['id'] ?>">Réponse au client</label>
                            <textarea class="form-control" id="quote_reply_<?= (int) $quote['id'] ?>" name="reply" rows="4" placeholder="Proposition tarifaire, disponibilité, précisions, motif de refus…"><?= $escape($quote['employee_reply'] ?? '') ?></textarea>
                        </div>
                        <div class="col-12">
                            <button class="btn btn-primary" type="submit">Enregistrer et envoyer l’email</button>
                        </div>
                    </form>
                </div>
            </section>
        <?php endforeach; ?>

        <?php if ($quotes === []): ?>
            <div class="card border-0 shadow-sm">
                <div class="card-body text-center text-muted py-5">Aucune demande de devis.</div>
            </div>
        <?php endif; ?>
    </div>
</main>
