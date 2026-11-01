<?php
$adminNavItems = [
    ['href' => 'index.php', 'label' => 'Dashboard', 'icon' => 'dashboard'],
    ['href' => 'manage_hero.php', 'label' => 'Kelola Slider', 'icon' => 'slider'],
    ['href' => 'settings.php', 'label' => 'Pengaturan', 'icon' => 'settings'],
    ['href' => 'activity_log.php', 'label' => 'Log Aktivitas', 'icon' => 'log'],
];
$currentFile = basename($_SERVER['PHP_SELF'] ?? '');
?>
<aside class="admin-sidebar" aria-label="Navigasi admin">
    <div class="admin-sidebar-brand">Alfi Kitchen</div>
    <nav class="admin-sidebar-nav">
        <?php foreach ($adminNavItems as $item): ?>
            <a href="<?= htmlspecialchars($item['href'], ENT_QUOTES, 'UTF-8') ?>" class="admin-sidebar-link<?= $currentFile === $item['href'] ? ' is-active' : '' ?>">
                <?= htmlspecialchars($item['label'], ENT_QUOTES, 'UTF-8') ?>
            </a>
        <?php endforeach; ?>
    </nav>
</aside>
<nav class="admin-bottom-nav" aria-label="Navigasi mobile">
    <a href="index.php">Dashboard</a>
    <a href="manage_hero.php">Slider</a>
    <a href="settings.php">Pengaturan</a>
    <a href="activity_log.php">Log</a>
</nav>
<script src="https://unpkg.com/lucide@latest" defer></script>
<script>document.addEventListener('DOMContentLoaded', function(){ if(window.lucide) lucide.createIcons(); });</script>
<script>
(function () {
  // scroll hint
  document.querySelectorAll('.table-responsive').forEach(function (el) {
    function upd() { el.classList.toggle('is-scrollable', el.scrollWidth > el.clientWidth + 4); }
    upd(); el.addEventListener('scroll', upd); window.addEventListener('resize', upd);
  });
  // column sort
  document.querySelectorAll('.table-responsive table').forEach(function (table) {
    var ths = table.querySelectorAll('thead th');
    ths.forEach(function (th, idx) {
      th.addEventListener('click', function () {
        var tbody = table.querySelector('tbody');
        if (!tbody) return;
        var rows = Array.prototype.slice.call(tbody.querySelectorAll('tr'));
        var asc = !th.classList.contains('sort-asc');
        ths.forEach(function (h) { h.classList.remove('sort-asc','sort-desc'); });
        th.classList.add(asc ? 'sort-asc' : 'sort-desc');
        rows.sort(function (a, b) {
          var ta = (a.cells[idx] ? a.cells[idx].innerText : '').trim();
          var tb = (b.cells[idx] ? b.cells[idx].innerText : '').trim();
          var na = parseFloat(ta.replace(/[^\d.-]/g,''));
          var nb = parseFloat(tb.replace(/[^\d.-]/g,''));
          if (!isNaN(na) && !isNaN(nb)) return asc ? na - nb : nb - na;
          return asc ? ta.localeCompare(tb) : tb.localeCompare(ta);
        });
        rows.forEach(function (r) { tbody.appendChild(r); });
      });
    });
  // drag & drop upload enhancement
  document.querySelectorAll('input[type="file"]').forEach(function (input) {
    if (input.dataset.dz) return;
    input.dataset.dz = '1';
    var zone = document.createElement('div');
    zone.className = 'dropzone';
    zone.textContent = 'Seret file ke sini atau klik untuk pilih';
    input.parentNode.insertBefore(zone, input);
    zone.appendChild(input);
    var preview = document.createElement('img');
    preview.className = 'dropzone-preview';
    preview.style.display = 'none';
    zone.appendChild(preview);
    function showPreview(file) {
      if (!file || !file.type.startsWith('image/')) { preview.style.display = 'none'; return; }
      var r = new FileReader();
      r.onload = function (e) { preview.src = e.target.result; preview.style.display = 'block'; };
      r.readAsDataURL(file);
    }
    input.addEventListener('change', function () { if (input.files && input.files[0]) showPreview(input.files[0]); });
    ['dragenter','dragover'].forEach(function (ev) { zone.addEventListener(ev, function (e) { e.preventDefault(); zone.classList.add('is-dragover'); }); });
    ['dragleave','drop'].forEach(function (ev) { zone.addEventListener(ev, function (e) { e.preventDefault(); zone.classList.remove('is-dragover'); }); });
    zone.addEventListener('drop', function (e) {
      if (e.dataTransfer.files && e.dataTransfer.files.length) {
        input.files = e.dataTransfer.files;
        showPreview(e.dataTransfer.files[0]);
      }
    });
    zone.addEventListener('click', function (e) { if (e.target !== input) input.click(); });
  });
</script>
