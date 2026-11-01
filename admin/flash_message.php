<?php
$adminFlash = consume_admin_flash();
if ($adminFlash !== null):
    $flashClass = $adminFlash['type'] === 'error' ? 'alert-error' : 'alert-success';
    $flashRole = $adminFlash['type'] === 'error' ? 'alert' : 'status';
?>
    <div class="alert <?= $flashClass ?>" role="<?= $flashRole ?>">
        <?= htmlspecialchars($adminFlash['message'], ENT_QUOTES, 'UTF-8') ?>
    </div>
<?php endif; ?>
