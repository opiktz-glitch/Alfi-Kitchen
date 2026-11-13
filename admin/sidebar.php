<?php
$adminNavItems = [
    ['href' => 'dashboard.php', 'label' => 'Dashboard', 'icon' => 'dashboard'],
    ['href' => 'products.php', 'label' => 'Produk', 'icon' => 'manage'],
    ['href' => 'manage_hero.php', 'label' => 'Kelola Slider', 'icon' => 'slider'],
    ['href' => 'hpp_recipes.php', 'label' => 'Kalkulator HPP', 'icon' => 'calculator'],
    ['href' => 'hpp_ingredients.php', 'label' => 'Master Bahan', 'icon' => 'manage'],
    ['href' => 'settings.php', 'label' => 'Pengaturan', 'icon' => 'settings'],
    ['href' => 'activity_log.php', 'label' => 'Log Aktivitas', 'icon' => 'log'],
];
$currentFile = basename($_SERVER['PHP_SELF'] ?? '');
$activePage = [
    'add_product.php' => 'products.php',
    'edit_product.php' => 'products.php',
    'manage_items.php' => 'products.php',
    'edit_item.php' => 'products.php',
    'hpp_edit.php' => 'hpp_recipes.php',
][$currentFile] ?? $currentFile;
?>
<aside class="admin-sidebar" aria-label="Navigasi admin">
    <div class="admin-sidebar-brand">Alfi Kitchen</div>
    <nav class="admin-sidebar-nav">
        <?php foreach ($adminNavItems as $item): ?>
            <a href="<?= htmlspecialchars($item['href'], ENT_QUOTES, 'UTF-8') ?>" class="admin-sidebar-link<?= $activePage === $item['href'] ? ' is-active' : '' ?>"<?= $activePage === $item['href'] ? ' aria-current="page"' : '' ?>>
                <?= admin_icon($item['icon']) ?>
                <?= htmlspecialchars($item['label'], ENT_QUOTES, 'UTF-8') ?>
            </a>
        <?php endforeach; ?>
    </nav>
</aside>
<nav class="admin-bottom-nav" aria-label="Navigasi mobile">
    <?php foreach ($adminNavItems as $item): ?>
        <a href="<?= htmlspecialchars($item['href'], ENT_QUOTES, 'UTF-8') ?>" class="<?= $activePage === $item['href'] ? 'is-active' : '' ?>"<?= $activePage === $item['href'] ? ' aria-current="page"' : '' ?>>
            <?= admin_icon($item['icon']) ?>
            <span><?= htmlspecialchars([
                'Dashboard' => 'Beranda',
                'Produk' => 'Produk',
                'Kelola Slider' => 'Slider',
                'Kalkulator HPP' => 'HPP',
                'Master Bahan' => 'Bahan',
                'Pengaturan' => 'Setelan',
                'Log Aktivitas' => 'Log',
            ][$item['label']], ENT_QUOTES, 'UTF-8') ?></span>
        </a>
    <?php endforeach; ?>
</nav>
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
  document.addEventListener('submit', function (event) {
    var form = event.target;
    if (!form || form.tagName !== 'FORM' || !form.hasAttribute('data-submit-lock')) return;
    if (event.defaultPrevented) return;
    if (form.dataset.submitting === 'true') {
      event.preventDefault();
      return;
    }

    form.dataset.submitting = 'true';
    form.setAttribute('aria-busy', 'true');
    var button = event.submitter;
    if (button && button.form === form && button.tagName === 'BUTTON') {
      button.dataset.originalHtml = button.innerHTML;
      button.textContent = button.dataset.loadingLabel || 'Memproses...';
      button.classList.add('is-loading');
      button.setAttribute('aria-disabled', 'true');
    }
  });
  document.querySelectorAll('[data-download-status]').forEach(function (link) {
    link.addEventListener('click', function (event) {
      if (link.getAttribute('aria-disabled') === 'true') {
        event.preventDefault();
        return;
      }

      link.dataset.originalHtml = link.innerHTML;
      link.textContent = link.dataset.loadingLabel || 'Menyiapkan unduhan...';
      link.classList.add('is-loading');
      link.setAttribute('aria-disabled', 'true');
      window.setTimeout(function () {
        link.innerHTML = link.dataset.originalHtml || link.innerHTML;
        delete link.dataset.originalHtml;
        link.classList.remove('is-loading');
        link.removeAttribute('aria-disabled');
      }, 2500);
    });
  });
  window.addEventListener('pageshow', function () {
    document.querySelectorAll('form[data-submit-lock]').forEach(function (form) {
      form.dataset.submitting = 'false';
      form.removeAttribute('aria-busy');
      var button = form.querySelector('button.is-loading');
      if (!button) return;
      button.innerHTML = button.dataset.originalHtml || button.innerHTML;
      delete button.dataset.originalHtml;
      button.classList.remove('is-loading');
      button.removeAttribute('aria-disabled');
    });
  });
})();
</script>
