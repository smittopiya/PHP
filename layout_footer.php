  </main><!-- /page-content -->
</div><!-- /main-wrapper -->

<!-- ── Bottom Navigation (mobile) ────────────────────────── -->
<nav class="bottom-nav">
  <a href="/milk-management/index.php"              class="bnav-item <?= $activeNav==='dashboard'?'active':'' ?>"><span>🏠</span><small>Home</small></a>
  <a href="/milk-management/entries/index.php"      class="bnav-item <?= $activeNav==='entries'?'active':'' ?>"><span>🥛</span><small>Entries</small></a>
  <a href="/milk-management/customers/index.php"    class="bnav-item <?= $activeNav==='customers'?'active':'' ?>"><span>👥</span><small>Customers</small></a>
  <a href="/milk-management/payments/index.php"     class="bnav-item <?= $activeNav==='payments'?'active':'' ?>"><span>💰</span><small>Payments</small></a>
  <a href="/milk-management/bills/index.php"        class="bnav-item <?= $activeNav==='bills'?'active':'' ?>"><span>📄</span><small>Bills</small></a>
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
