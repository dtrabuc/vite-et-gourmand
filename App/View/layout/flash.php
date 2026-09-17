<?php
$flashMessages = [
    'login_error' => ['danger', $_SESSION['login_error'] ?? null],
    'register_success' => ['success', $_SESSION['register_success'] ?? null],
    'register_error' => ['danger', $_SESSION['register_error'] ?? null],
    'profile_success' => ['success', $_SESSION['profile_success'] ?? null],
    'order_success' => ['success', $_SESSION['order_success'] ?? null],
];
?>
<div class="container pt-3" aria-live="polite">
    <?php foreach ($flashMessages as $key => [$type, $message]): ?>
        <?php if ($message): ?>
            <div class="alert alert-<?= $escape($type) ?> alert-dismissible fade show" role="alert">
                <?= $escape($message) ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Fermer"></button>
            </div>
            <?php unset($_SESSION[$key]); ?>
        <?php endif; ?>
    <?php endforeach; ?>
</div>
