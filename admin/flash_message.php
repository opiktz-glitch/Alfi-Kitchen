<?php
$adminFlash = consume_admin_flash();
if ($adminFlash !== null):
    $flashClass = $adminFlash['type'] === 'error' ? 'toast-error' : 'toast-success';
?>
    <div class="toast <?= $flashClass ?>" role="status" aria-live="polite">
        <?= htmlspecialchars($adminFlash['message'], ENT_QUOTES, 'UTF-8') ?>
    </div>
    <script>
    (function () {
        var t = document.querySelector('.toast');
        if (!t) return;
        setTimeout(function () { t.classList.add('toast-hide'); }, 3200);
        setTimeout(function () { t.remove(); }, 3600);
    })();
    </script>
<?php endif; ?>