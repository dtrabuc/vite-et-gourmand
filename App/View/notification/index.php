<?php
$typeLabels = [
    'order' => 'Commande',
    'quote' => 'Devis',
];
?>
<main class="py-5">
    <div class="container">
        <div class="d-flex justify-content-between align-items-center gap-3 mb-4">
            <div>
                <span class="badge bg-primary-subtle text-primary">Espace client</span>
                <h1 class="h2 text-primary mt-2 mb-1">Notifications</h1>
                <p class="text-muted mb-0">Retrouvez les confirmations et les mises à jour de vos demandes.</p>
            </div>
            <?php if (array_filter($notifications, static fn (array $item): bool => empty($item['is_read'])) !== []): ?>
                <form method="post" action="/notifications/read-all">
                    <input type="hidden" name="csrf_token" value="<?= $escape($csrfToken) ?>">
                    <button class="btn btn-outline-primary btn-sm" type="submit">Tout marquer comme lu</button>
                </form>
            <?php endif; ?>
        </div>

        <?php foreach ($notifications as $notification): ?>
            <article class="card border-0 shadow-sm mb-3 <?= empty($notification['is_read']) ? 'border-start border-4 border-primary' : '' ?>">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-start gap-3">
                        <div>
                            <span class="badge bg-light text-primary">
                                <?= $escape($typeLabels[$notification['type']] ?? 'Information') ?>
                            </span>
                            <h2 class="h5 mt-2 mb-2"><?= $escape($notification['title']) ?></h2>
                        </div>
                        <small class="text-muted">
                            <?= $escape((string) ($notification['created_at'] ?? '')) ?>
                        </small>
                    </div>
                    <p class="mb-3"><?= nl2br($escape($notification['message'])) ?></p>
                    <div class="d-flex flex-wrap gap-2">
                        <?php if (!empty($notification['order_id'])): ?>
                            <a class="btn btn-outline-primary btn-sm" href="/orders">Voir mes commandes</a>
                        <?php elseif (!empty($notification['quote_request_id'])): ?>
                            <a class="btn btn-outline-primary btn-sm" href="/quote">Voir ma demande de devis</a>
                        <?php endif; ?>
                        <?php if (empty($notification['is_read'])): ?>
                            <form method="post" action="/notifications/<?= (int) $notification['id'] ?>/read">
                                <input type="hidden" name="csrf_token" value="<?= $escape($csrfToken) ?>">
                                <button class="btn btn-outline-secondary btn-sm" type="submit">Marquer comme lu</button>
                            </form>
                        <?php endif; ?>
                    </div>
                </div>
            </article>
        <?php endforeach; ?>

        <?php if ($notifications === []): ?>
            <div class="card border-0 shadow-sm">
                <div class="card-body text-center py-5 text-muted">
                    Aucune notification pour le moment.
                </div>
            </div>
        <?php endif; ?>
    </div>
</main>
