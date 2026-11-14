<?php
$adminNavItems = [
    ['href' => 'dashboard.php', 'label' => 'Dashboard', 'icon' => 'dashboard'],
    ['href' => 'products.php', 'label' => 'Produk', 'icon' => 'manage'],
    ['href' => 'hpp_recipes.php', 'label' => 'Kalkulator HPP', 'icon' => 'calculator'],
    ['href' => 'hpp_ingredients.php', 'label' => 'Master Bahan', 'icon' => 'database'],
    ['href' => 'settings.php', 'label' => 'Pengaturan', 'icon' => 'settings'],
];
$currentFile = basename($_SERVER['PHP_SELF'] ?? '');
$activePage = [
    'add_product.php' => 'products.php',
    'edit_product.php' => 'products.php',
    'manage_items.php' => 'products.php',
    'edit_item.php' => 'products.php',
    'manage_hero.php' => 'settings.php',
    'activity_log.php' => 'settings.php',
    'hpp_edit.php' => 'hpp_recipes.php',
][$currentFile] ?? $currentFile;

$mobileNavItems = [
    [
        'href' => 'dashboard.php',
        'label' => 'Beranda',
        'icon' => 'dashboard',
        'active' => $activePage === 'dashboard.php',
    ],
    [
        'href' => 'products.php',
        'label' => 'Produk',
        'icon' => 'manage',
        'active' => $activePage === 'products.php',
    ],
    [
        'href' => 'hpp_recipes.php',
        'label' => 'Kalkulator',
        'icon' => 'calculator',
        'active' => $activePage === 'hpp_recipes.php',
    ],
    [
        'href' => 'hpp_ingredients.php',
        'label' => 'Master Bahan',
        'icon' => 'database',
        'active' => $activePage === 'hpp_ingredients.php',
    ],
    [
        'href' => 'settings.php',
        'label' => 'Setelan',
        'icon' => 'settings',
        'active' => in_array($activePage, ['settings.php', 'activity_log.php'], true),
    ],
];
?>
<aside class="admin-sidebar" aria-label="Navigasi admin">
    <script>if(localStorage.getItem('alfi_admin_sidebar_collapsed')==='1'){document.currentScript.parentElement.classList.add('is-collapsed');}</script>
    <div class="admin-sidebar-header">
        <a href="dashboard.php" class="admin-sidebar-brand" title="Alfi Kitchen">
            <span class="admin-brand-title">Alfi Kitchen</span>
            <span class="admin-brand-short">AK</span>
        </a>
        <button type="button" class="admin-sidebar-toggle" id="btn-toggle-sidebar" aria-label="Ciutkan menu" title="Ciutkan menu" aria-expanded="true">
            <svg class="admin-icon icon-collapse" viewBox="0 0 24 24" aria-hidden="true" focusable="false"><rect x="3" y="3" width="18" height="18" rx="3" fill="none" stroke="currentColor" stroke-width="2"/><path fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" d="M9 3v18M14 9l-3 3 3 3"/></svg>
            <svg class="admin-icon icon-expand" viewBox="0 0 24 24" aria-hidden="true" focusable="false"><rect x="3" y="3" width="18" height="18" rx="3" fill="none" stroke="currentColor" stroke-width="2"/><path fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" d="M9 3v18M12 15l3-3-3-3"/></svg>
        </button>
    </div>
    <nav class="admin-sidebar-nav">
        <?php foreach ($adminNavItems as $item): ?>
            <a href="<?= htmlspecialchars($item['href'], ENT_QUOTES, 'UTF-8') ?>" class="admin-sidebar-link<?= $activePage === $item['href'] ? ' is-active' : '' ?>"<?= $activePage === $item['href'] ? ' aria-current="page"' : '' ?> data-tooltip="<?= htmlspecialchars($item['label'], ENT_QUOTES, 'UTF-8') ?>" title="<?= htmlspecialchars($item['label'], ENT_QUOTES, 'UTF-8') ?>">
                <?= admin_icon($item['icon']) ?>
                <span class="admin-nav-label"><?= htmlspecialchars($item['label'], ENT_QUOTES, 'UTF-8') ?></span>
            </a>
        <?php endforeach; ?>
    </nav>
    <div class="admin-sidebar-footer">
        <div class="admin-sidebar-user" title="<?= htmlspecialchars($_SESSION['username'] ?? 'Admin', ENT_QUOTES, 'UTF-8') ?> (Administrator)">
            <div class="admin-user-avatar"><?= strtoupper(substr($_SESSION['username'] ?? 'A', 0, 1)) ?></div>
            <div class="admin-user-info">
                <span class="admin-user-name"><?= htmlspecialchars($_SESSION['username'] ?? 'Admin', ENT_QUOTES, 'UTF-8') ?></span>
                <span class="admin-user-role">Administrator</span>
            </div>
        </div>
        <a href="logout.php" class="admin-sidebar-action-btn admin-sidebar-logout" title="Keluar dari sesi admin" aria-label="Logout" data-tooltip="Logout">
            <?= admin_icon('logout') ?>
            <span>Logout</span>
        </a>
    </div>
</aside>
<nav class="admin-bottom-nav" aria-label="Navigasi mobile">
    <?php foreach ($mobileNavItems as $item): ?>
        <a href="<?= htmlspecialchars($item['href'], ENT_QUOTES, 'UTF-8') ?>" class="<?= $item['active'] ? 'is-active' : '' ?>"<?= $item['active'] ? ' aria-current="page"' : '' ?>>
            <?= admin_icon($item['icon']) ?>
            <span><?= htmlspecialchars($item['label'], ENT_QUOTES, 'UTF-8') ?></span>
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
      if (th.classList.contains('no-sort') || th.textContent.trim() === 'Aksi') return;
      th.addEventListener('click', function () {
        var tbody = table.querySelector('tbody');
        if (!tbody) return;
        var mainRows = Array.prototype.slice.call(tbody.querySelectorAll('tr:not(.ingredient-edit-drawer-row):not(.category-variants-row)'));
        if (!mainRows.length) return;
        var asc = !th.classList.contains('sort-asc');
        ths.forEach(function (h) { h.classList.remove('sort-asc','sort-desc'); });
        th.classList.add(asc ? 'sort-asc' : 'sort-desc');
        mainRows.sort(function (a, b) {
          var ta = (a.cells[idx] ? a.cells[idx].innerText : '').trim();
          var tb = (b.cells[idx] ? b.cells[idx].innerText : '').trim();
          var na = parseFloat(ta.replace(/[^\d.-]/g,''));
          var nb = parseFloat(tb.replace(/[^\d.-]/g,''));
          if (!isNaN(na) && !isNaN(nb)) return asc ? na - nb : nb - na;
          return asc ? ta.localeCompare(tb) : tb.localeCompare(ta);
        });
        mainRows.forEach(function (r) {
          tbody.appendChild(r);
          var editId = r.getAttribute('data-ingredient-id');
          if (editId) {
            var editRow = document.getElementById('drawer-edit-' + editId);
            if (editRow) tbody.appendChild(editRow);
          }
        });
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
  // drawer toggle handler (delegated to document so all elements work regardless of render order)
  document.addEventListener('click', function (e) {
    var btn = e.target.closest('.admin-drawer-toggle, .btn-drawer-close');
    if (!btn) return;
    var targetId = btn.getAttribute('data-target');
    if (!targetId) return;
    var drawer = document.getElementById(targetId);
    if (!drawer) return;
    e.preventDefault();
    var isHidden = drawer.hasAttribute('hidden');
    if (isHidden) {
      drawer.removeAttribute('hidden');
      drawer.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
      var firstInput = drawer.querySelector('input:not([type="hidden"]), select, textarea');
      if (firstInput) firstInput.focus();
    } else {
      drawer.setAttribute('hidden', '');
    }
    var toggleBtn = document.querySelector('.admin-drawer-toggle[data-target="' + targetId + '"]');
    if (toggleBtn) {
      toggleBtn.setAttribute('aria-expanded', isHidden ? 'true' : 'false');
    }
  });

  // sidebar collapse toggle
  var sidebar = document.querySelector('.admin-sidebar');
  var sidebarToggleBtn = document.getElementById('btn-toggle-sidebar');
  if (sidebar && sidebarToggleBtn) {
    var SIDEBAR_KEY = 'alfi_admin_sidebar_collapsed';
    if (localStorage.getItem(SIDEBAR_KEY) === '1') {
      sidebar.classList.add('is-collapsed');
      sidebarToggleBtn.setAttribute('aria-expanded', 'false');
      sidebarToggleBtn.setAttribute('title', 'Perluas menu');
      sidebarToggleBtn.setAttribute('aria-label', 'Perluas menu');
    }
    sidebarToggleBtn.addEventListener('click', function () {
      var isCollapsed = sidebar.classList.toggle('is-collapsed');
      try {
        localStorage.setItem(SIDEBAR_KEY, isCollapsed ? '1' : '0');
      } catch (err) {}
      sidebarToggleBtn.setAttribute('aria-expanded', isCollapsed ? 'false' : 'true');
      var caption = isCollapsed ? 'Perluas menu' : 'Ciutkan menu';
      sidebarToggleBtn.setAttribute('title', caption);
      sidebarToggleBtn.setAttribute('aria-label', caption);
    });
  }
})();
</script>
