<main class="py-5">
    <div class="container">
        <h1 class="h2 text-primary mb-4">Avis à modérer</h1>

        <?php foreach ($comments as $comment): ?>
            <section class="card border-0 shadow-sm mb-3">
                <div class="card-body">
                    <h2 class="h6">
                        <?= $escape($comment['first_name'] . ' ' . $comment['last_name']) ?>
                        — <?= (int) $comment['rating'] ?>/5
                    </h2>
                    <p><?= nl2br($escape($comment['comment'])) ?></p>

                    <div class="d-flex gap-2">
                        <form method="post" action="/admin/comments/<?= $escape($comment['id']) ?>/validate">
                            <input type="hidden" name="csrf_token" value="<?= $escape($csrfToken) ?>">
                            <button class="btn btn-outline-success btn-sm" type="submit">Valider</button>
                        </form>
                        <form method="post" action="/admin/comments/<?= $escape($comment['id']) ?>/reject">
                            <input type="hidden" name="csrf_token" value="<?= $escape($csrfToken) ?>">
                            <button class="btn btn-outline-danger btn-sm" type="submit">Refuser</button>
                        </form>
                    </div>
                </div>
            </section>
        <?php endforeach; ?>

        <?php if ($comments === []): ?>
            <p class="text-muted">Aucun avis en attente.</p>
        <?php endif; ?>
    </div>
</main>
