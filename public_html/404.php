<?php
require __DIR__ . '/inc/init.php';
require __DIR__ . '/inc/parts.php';

http_response_code(404);
$s = $SETTINGS;

page_head($s, 'Page not found — ' . $s['business_name']);
top_bar($s);
site_header($s);
?>

<div class="pagehead">
  <div class="shell inner">
    <div class="eyebrow">404</div>
    <h1>That page is not here</h1>
    <p class="lede">The link may be out of date, or we may have moved it. Everything below is one click away.</p>
    <div class="hero-cta">
      <a class="btn" href="index.php">Back to the home page</a>
      <a class="btn btn--ghost" href="cars.php">Cars for sale</a>
      <?php if (booking_enabled($s)): ?><a class="btn btn--ghost" href="book.php">Book a service</a><?php endif; ?>
      <a class="btn btn--wa" href="<?= e(wa_link($s)) ?>" target="_blank" rel="noopener"><?= svg_whatsapp() ?>WhatsApp us</a>
    </div>
  </div>
</div>

<?php site_footer($s);
