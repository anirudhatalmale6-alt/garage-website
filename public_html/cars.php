<?php
require __DIR__ . '/inc/init.php';
require __DIR__ . '/inc/parts.php';

$s      = $SETTINGS;
$filter = $_GET['f'] ?? 'all';

$list = match ($filter) {
    'available' => array_values(array_filter($STOCK, fn($c) => empty($c['sold']))),
    'Petrol', 'Diesel', 'Hybrid', 'Electric'
                => array_values(array_filter($STOCK, fn($c) => ($c['fuel'] ?? '') === $filter)),
    'Automatic' => array_values(array_filter($STOCK, fn($c) => ($c['gearbox'] ?? '') === 'Automatic')),
    default     => $STOCK,
};

$chips = [
    'all'       => 'All stock',
    'available' => 'Available now',
    'Petrol'    => 'Petrol',
    'Diesel'    => 'Diesel',
    'Automatic' => 'Automatic',
];

page_head($s, 'Used Cars for Sale — ' . $s['business_name'] . ', ' . $s['town'],
    "Hand-picked used cars for sale in " . $s['town'] . ". Every car serviced, MOT'd and HPI checked in our own workshop before it goes on sale.");
top_bar($s);
site_header($s, 'cars');
?>

<div class="pagehead">
  <div class="shell inner">
    <div class="crumbs"><a href="index.php">Home</a> / Used cars for sale</div>
    <h1>Used cars for sale</h1>
    <p class="lede">Small forecourt, carefully chosen stock. Every car is serviced, MOT'd, HPI checked and valeted in our own workshop before it goes on sale — and we will happily put it on the ramp so you can see underneath.</p>
  </div>
</div>

<section>
  <div class="shell">
    <div class="filters">
      <?php foreach ($chips as $key => $label): ?>
        <a class="chip<?= $filter === $key ? ' on' : '' ?>" href="cars.php?f=<?= e($key) ?>"><?= e($label) ?></a>
      <?php endforeach; ?>
      <span class="count">
        <?= count($list) ?> vehicle<?= count($list) === 1 ? '' : 's' ?> ·
        <?= count(stock_available($STOCK)) ?> available
      </span>
    </div>

    <div class="car-grid">
      <?php if ($list): foreach ($list as $c) car_card($c, $s); else: ?>
        <p class="lede">Nothing in stock matching that just now — give us a ring, we usually have something coming through.</p>
      <?php endif; ?>
    </div>
  </div>
</section>

<div class="band">
  <div class="shell row">
    <div>
      <h2>Seen something you like?</h2>
      <p>Ring the workshop or send a WhatsApp with the registration and we will tell you everything we know about the car — including anything we had to put right.</p>
    </div>
    <div class="hero-cta" style="margin:0">
      <a class="btn" href="<?= e(tel_href($s['phone'])) ?>">Call <?= e($s['phone']) ?></a>
      <a class="btn btn--wa" href="<?= e(wa_link($s)) ?>" target="_blank" rel="noopener">Message on WhatsApp</a>
    </div>
  </div>
</div>

<?php site_footer($s);
