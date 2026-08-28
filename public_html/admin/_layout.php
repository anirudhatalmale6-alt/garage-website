<?php
/* Chrome for the three Stock Manager pages. */

function admin_start(array $s, string $title, string $current = ''): void {
    page_head($s, $title . ' — ' . $s['business_name']);
    ?>
<div class="topbar">
  <div class="shell">
    <span class="open-now"><i></i> Signed in — Stock Manager</span>
    <span class="tb-right">
      <a href="<?= BASE ?>index.php" target="_blank" rel="noopener">View the live site</a>
      <a href="index.php?logout=1">Sign out</a>
    </span>
  </div>
</div>

<header class="site">
  <div class="shell">
    <?= site_logo($s, 'Stock Manager') ?>
    <button class="burger" id="burger" aria-label="Menu"><span></span><span></span><span></span></button>
    <nav class="main" id="nav">
      <a href="index.php"<?= $current === 'stock' ? ' aria-current="page"' : '' ?>>Vehicles</a>
      <a href="vehicle.php"<?= $current === 'vehicle' ? ' aria-current="page"' : '' ?>>Add a vehicle</a>
      <a href="settings.php"<?= $current === 'settings' ? ' aria-current="page"' : '' ?>>Business details</a>
    </nav>
    <div class="head-cta">
      <a class="btn btn--sm" href="<?= BASE ?>cars.php" target="_blank" rel="noopener"><span>See the public page</span></a>
    </div>
  </div>
</header>
<?php
}

function admin_end(): void {
    ?>
<footer class="site">
  <div class="shell">
    <div class="foot-base" style="border-top:0">
      <span>Stock Manager</span>
      <span>Changes appear on the website immediately</span>
    </div>
  </div>
</footer>
<script src="<?= BASE ?>assets/js/site.js?v=3"></script>
</body>
</html>
<?php
}

/* green / red strip at the top of a page after a save */
function flash_out(): void {
    admin_session_start();
    if (!empty($_SESSION['flash'])) {
        [$kind, $msg] = $_SESSION['flash'];
        unset($_SESSION['flash']);
        echo '<div class="flash ' . e($kind) . '">' . e($msg) . '</div>';
    }
}

function flash_set(string $kind, string $msg): void {
    admin_session_start();
    $_SESSION['flash'] = [$kind, $msg];
}
