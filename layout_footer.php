  </main><!-- /page-content -->
</div><!-- /main-wrapper -->

<!-- ── Bottom Navigation (mobile) ────────────────────────── -->
<nav class="bottom-nav">
  <a href="<?= BASE_PATH ?>index.php"              class="bnav-item <?= $activeNav==='dashboard'?'active':'' ?>"><span>&#x1F3E0;</span><small>Home</small></a>
  <a href="<?= BASE_PATH ?>entries/index.php"      class="bnav-item <?= $activeNav==='entries'?'active':'' ?>"><span>&#x1F95B;</span><small>Entries</small></a>
  <a href="<?= BASE_PATH ?>customers/index.php"    class="bnav-item <?= $activeNav==='customers'?'active':'' ?>"><span>&#x1F465;</span><small>Customers</small></a>
  <a href="<?= BASE_PATH ?>payments/index.php"     class="bnav-item <?= $activeNav==='payments'?'active':'' ?>"><span>&#x1F4B0;</span><small>Payments</small></a>
  <a href="<?= BASE_PATH ?>bills/index.php"        class="bnav-item <?= $activeNav==='bills'?'active':'' ?>"><span>&#x1F4C4;</span><small>Bills</small></a>
</nav>

<script>
// Sidebar toggle
function toggleSidebar() {
  document.getElementById('sidebar').classList.toggle('open');
  document.getElementById('sidebarOverlay').classList.toggle('show');
}
function closeSidebar() {
  document.getElementById('sidebar').classList.remove('open');
  document.getElementById('sidebarOverlay').classList.remove('show');
}
// Dark mode
function toggleDark() {
  const isDark = document.body.classList.toggle('dark');
  document.documentElement.classList.toggle('dark', isDark);
  document.cookie = 'dark_mode=' + (isDark ? '1' : '0') + ';path=/;max-age=31536000';
  location.reload();
}
// Auto-dismiss flash after 4s
setTimeout(() => { const f = document.getElementById('flashMsg'); if(f) f.remove(); }, 4000);

// Confirm delete
function confirmDelete(url, name) {
  if (confirm('Delete "' + name + '"? This cannot be undone.')) location.href = url;
}
</script>
</body>
</html>
<?php ob_end_flush(); ?>
