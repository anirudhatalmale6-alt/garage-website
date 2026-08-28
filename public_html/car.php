<?php
require __DIR__ . '/inc/init.php';
require __DIR__ . '/inc/parts.php';

$s  = $SETTINGS;
$id = $_GET['id'] ?? '';
$car = null;
foreach ($STOCK as $c) if ($c['id'] === $id) { $car = $c; break; }

if (!$car) {
    http_response_code(404);
    page_head($s, 'Vehicle not found — ' . $s['business_name']);
    top_bar($s); site_header($s, 'cars');
    ?>
    <div class="pagehead"><div class="shell inner">
      <div class="crumbs"><a href="index.php">Home</a> / <a href="cars.php">Used cars</a> / Not found</div>
      <h1>That vehicle has gone</h1>
      <p class="lede">It has either sold or been taken off the forecourt. Have a look at what else is in stock, or give us a ring and we will keep an eye out for something similar.</p>
      <div class="hero-cta">
        <a class="btn" href="cars.php">See all stock</a>
        <a class="btn btn--ghost" href="<?= e(tel_href($s['phone'])) ?>">Call <?= e($s['phone']) ?></a>
      </div>
    </div></div>
    <?php
    site_footer($s);
    exit;
}

$photos  = $car['photos'] ?? [];
$sold    = !empty($car['sold']);
$enquire = "Hi, I'm interested in the " . car_title($car) . ' ' . $car['trim'] .
           ($car['reg'] ? ' (' . $car['reg'] . ')' : '') . ' at ' . money($car['price']) . '. Is it still available?';

$others = array_values(array_filter($STOCK, fn($c) => $c['id'] !== $car['id'] && empty($c['sold'])));
$others = array_slice($others, 0, 3);

page_head($s,
    car_title($car) . ' ' . $car['trim'] . ' — ' . $s['business_name'],
    car_title($car) . ' ' . $car['trim'] . ', ' . miles($car['mileage']) . ', ' . $car['gearbox'] . ' ' .
    $car['fuel'] . ', MOT to ' . $car['mot'] . '. ' . money($car['price']) . ' at ' . $s['business_name'] . '.');
top_bar($s);
site_header($s, 'cars');
?>

<section id="detail">
  <div class="shell">
    <div class="crumbs"><a href="index.php">Home</a> / <a href="cars.php">Used cars</a> / <?= e($car['make'] . ' ' . $car['model']) ?></div>
    <div class="detail">

      <div class="gallery">
        <div class="main">
          <img id="mainShot" src="<?= e(car_photo($car)) ?>" alt="<?= e(car_title($car)) ?>">
          <?php if ($sold): ?><span class="sold-band"><b>Sold</b></span><?php endif; ?>
        </div>
        <?php if (count($photos) > 1): ?>
        <div class="thumbs" id="thumbs">
          <?php foreach ($photos as $i => $p): ?>
            <button class="<?= $i === 0 ? 'on' : '' ?>" type="button" data-src="<?= e(BASE . $p) ?>">
              <img src="<?= e(BASE . $p) ?>" alt="Photo <?= $i + 1 ?> of <?= count($photos) ?>">
            </button>
          <?php endforeach; ?>
        </div>
        <p class="hint" style="margin-top:14px"><?= count($photos) ?> photos · click a thumbnail to enlarge</p>
        <?php endif; ?>

        <?php if (!empty($car['blurb'])): ?>
          <h3 style="margin-top:34px">Our notes on this car</h3>
          <p class="lede" style="margin-top:12px"><?= e($car['blurb']) ?></p>
        <?php endif; ?>
      </div>

      <div>
        <h1 style="font-size:clamp(30px,3.6vw,44px)"><?= e($car['make'] . ' ' . $car['model']) ?></h1>
        <div class="trim" style="font-family:var(--cond);text-transform:uppercase;letter-spacing:.13em;color:var(--fog-dim);margin-top:8px">
          <?= e($car['trim']) ?> · <?= e($car['year']) ?><?= $car['colour'] ? ' · ' . e($car['colour']) : '' ?>
        </div>

        <div class="price-block">
          <span class="p num"><?= money($car['price']) ?></span>
          <span class="was"><?= $sold ? 'Now sold' : 'Part exchange welcome' ?></span>
        </div>

        <div class="badge-row">
          <?php if ($car['mot']): ?>
            <span class="badge"><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4"><path d="m5 12 5 5 9-9"/></svg>MOT to <?= e($car['mot']) ?></span>
          <?php endif; ?>
          <?php if ($car['history']): ?>
            <span class="badge"><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4"><path d="m5 12 5 5 9-9"/></svg><?= e($car['history']) ?></span>
          <?php endif; ?>
          <span class="badge"><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4"><path d="m5 12 5 5 9-9"/></svg>HPI clear</span>
        </div>

        <table class="spec-table">
          <tr><th>Make &amp; model</th><td><?= e($car['make'] . ' ' . $car['model']) ?></td></tr>
          <tr><th>Year</th><td><?= e($car['year']) ?></td></tr>
          <tr><th>Mileage</th><td><?= miles($car['mileage']) ?></td></tr>
          <tr><th>Engine size</th><td><?= e($car['engine']) ?></td></tr>
          <tr><th>Gearbox</th><td><?= e($car['gearbox']) ?></td></tr>
          <tr><th>Fuel type</th><td><?= e($car['fuel']) ?></td></tr>
          <tr><th>Doors</th><td><?= e($car['doors']) ?></td></tr>
          <tr><th>Colour</th><td><?= e($car['colour']) ?></td></tr>
          <tr><th>MOT expiry</th><td><?= e($car['mot']) ?></td></tr>
          <tr><th>Service history</th><td><?= e($car['history']) ?></td></tr>
          <tr><th>Former keepers</th><td><?= e($car['owners']) ?></td></tr>
          <tr><th>Registration</th><td><?= e($car['reg']) ?></td></tr>
        </table>

        <?php if ($sold): ?>
          <div class="notice">This one has <b>sold</b>. We normally have something similar coming through — ring us and we will keep an eye out.</div>
          <div class="hero-cta"><a class="btn btn--ghost btn--block" href="cars.php">See what else is in stock</a></div>
        <?php else: ?>
          <div class="hero-cta" style="margin-top:0">
            <a class="btn" href="<?= e(tel_href($s['phone'])) ?>"><?= svg_phone() ?>Enquire / Call Us</a>
            <a class="btn btn--wa" href="<?= e(wa_link($s, $enquire)) ?>" target="_blank" rel="noopener"><?= svg_whatsapp() ?>WhatsApp Us</a>
          </div>
          <p class="hint" style="margin-top:16px">The WhatsApp button opens a chat with the car and price already typed in.</p>
        <?php endif; ?>
      </div>

    </div>
  </div>
</section>

<?php if ($others): ?>
<section class="bg-steel">
  <div class="shell">
    <div class="sec-head">
      <div>
        <div class="eyebrow">More stock</div>
        <h2>Also on the forecourt</h2>
      </div>
      <a class="btn btn--ghost" href="cars.php">See all stock</a>
    </div>
    <div class="car-grid">
      <?php foreach ($others as $c) car_card($c, $s); ?>
    </div>
  </div>
</section>
<?php endif; ?>

<script type="application/ld+json"><?= json_encode([
  '@context' => 'https://schema.org',
  '@type'    => 'Car',
  'name'     => car_title($car) . ' ' . $car['trim'],
  'brand'    => ['@type' => 'Brand', 'name' => $car['make']],
  'model'    => $car['model'],
  'vehicleModelDate'        => (string)$car['year'],
  'mileageFromOdometer'     => ['@type' => 'QuantitativeValue', 'value' => (int)$car['mileage'], 'unitCode' => 'SMI'],
  'fuelType'                => $car['fuel'],
  'vehicleTransmission'     => $car['gearbox'],
  'color'                   => $car['colour'],
  'numberOfDoors'           => (int)$car['doors'],
  'offers' => [
    '@type'         => 'Offer',
    'price'         => (int)$car['price'],
    'priceCurrency' => 'GBP',
    'availability'  => $sold ? 'https://schema.org/SoldOut' : 'https://schema.org/InStock',
    'seller'        => ['@type' => 'AutoDealer', 'name' => $s['business_name'], 'telephone' => $s['phone']],
  ],
], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) ?></script>

<?php site_footer($s);
