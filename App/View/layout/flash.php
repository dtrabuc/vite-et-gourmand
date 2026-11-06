<?php
declare(strict_types=1);

use App\Core\Session;

$flashKeys = [
    'login_error' => 'danger',
    'register_success' => 'success',
    'register_error' => 'danger',
    'register_errors' => 'danger',
    'profile_success' => 'success',
    'profile_errors' => 'danger',
    'password_success' => 'success',
    'password_errors' => 'danger',
    'order_success' => 'success',
    'order_error' => 'danger',
    'admin_success' => 'success',
    'admin_error' => 'danger',
    'menu_success' => 'success',
    'menu_error' => 'danger',
    'forgot_success' => 'success',
    'forgot_error' => 'danger',
    'reset_success' => 'success',
    'reset_error' => 'danger',
    'reset_errors' => 'danger',
    'contact_success' => 'success',
    'contact_errors' => 'danger',
    'quote_success' => 'success',
    'quote_error' => 'danger',
    'email_success' => 'success',
    'email_error' => 'danger',
];

$formatMessage = static function (mixed $value): string {
    if (is_array($value)) {
        return implode(
            ' ',
            array_map(
                static fn (mixed $item): string => is_scalar($item)
                    ? (string) $item
                    : '',
                $value
            )
        );
    }

    return (string) $value;
};
?>
<div class="container pt-3" aria-live="polite">
    <?php foreach ($flashKeys as $key => $type): ?>
        <?php $message = Session::pullFlash($key); ?>
        <?php if ($message !== null && $message !== ''): ?>
            <div class="alert alert-<?= $escape($type) ?> alert-dismissible fade show" role="alert">
                <?= $escape($formatMessage($message)) ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Fermer"></button>
            </div>
        <?php endif; ?>
    <?php endforeach; ?>
</div>