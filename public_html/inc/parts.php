<?php
/* ------------------------------------------------------------------
   Shared page furniture: head, top bar, header, footer, car card.
   Every page draws its chrome from here so the phone number only ever
   has to be changed in one place (the Stock Manager).
------------------------------------------------------------------ */

function svg_whatsapp(string $cls = ''): string {
    return '<svg' . ($cls ? ' class="' . $cls . '"' : '') . ' viewBox="0 0 24 24" fill="currentColor"><path d="M12 2a10 10 0 0 0-8.6 15l-1.3 4.7 4.9-1.3A10 10 0 1 0 12 2Zm5.8 14.2c-.2.7-1.4 1.3-2 1.4-.5.1-1.2.1-1.9-.1-.4-.1-1-.3-1.8-.6-3.1-1.3-5.1-4.4-5.3-4.6-.1-.2-1.2-1.6-1.2-3s.7-2.1 1-2.4c.3-.3.6-.4.8-.4h.6c.2 0 .5-.1.7.5l1 2.4c.1.2.1.4 0 .6l-.4.5-.3.4c-.1.1-.3.3-.1.6.2.3.8 1.3 1.7 2.1 1.1 1 2.1 1.3 2.4 1.5.3.1.5.1.6-.1l.9-1c.2-.2.4-.2.6-.1l2.2 1c.3.2.5.2.5.4.1.1.1.6-.1 1.3Z"/></svg>';
}

function svg_phone(): string {
    return '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 16.9v3a2 2 0 0 1-2.2 2 19.8 19.8 0 0 1-8.6-3.1 19.5 19.5 0 0 1-6-6A19.8 19.8 0 0 1 2.1 4.2 2 2 0 0 1 4.1 2h3a2 2 0 0 1 2 1.7c.1 1 .4 1.9.7 2.8a2 2 0 0 1-.5 2.1L8.1 9.9a16 16 0 0 0 6 6l1.3-1.2a2 2 0 0 1 2.1-.5c.9.3 1.8.6 2.8.7a2 2 0 0 1 1.7 2Z"/></svg>';
}

function site_logo(array $s, string $sub = ''): string {
    $sub = $sub !== '' ? $sub : ($s['strapline'] ?? '');
    if (!empty($s['logo_file'])) {
        return '<a class="logo" href="' . BASE . 'index.php">'
             . '<img src="' . BASE . e($s['logo_file']) . '" alt="' . e($s['business_name']) . '" class="logo-img">'
             . '</a>';
    }
    return '<a class="logo" href="' . BASE . 'index.php">'
         . '<span class="mark">' . e(mb_substr($s['logo_mark'] ?: mb_substr($s['business_name'], 0, 1), 0, 2)) . '</span>'
         . '<span class="wordmark">' . e($s['business_name']) . '<span>' . e($sub) . '</span></span>'
         . '</a>';
}

function page_head(array $s, string $title, string $desc = ''): void {
    ?><!DOCTYPE html>
<html lang="en-GB">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title><?= e($title) ?></title>
<?php if ($desc !== ''): ?><meta name="description" content="<?= e($desc) ?>">
<?php endif; ?>
<meta property="og:title" content="<?= e($title) ?>">
<meta property="og:description" content="<?= e($desc) ?>">
<meta property="og:type" content="website">
<link rel="stylesheet" href="<?= BASE ?>assets/css/style.css?v=3">
</head>
<body>
<div class="hazard"></div>
<?php
}

function top_bar(array $s): void {
    [$open, $closes] = open_state($s);
    ?>
<div class="topbar">
  <div class="shell">
    <?php if ($open): ?>
      <span class="open-now"><i></i> Open today until <?= e($closes) ?></span>
    <?php else: ?>
      <span class="open-now closed"><i></i> Closed now — leave us a WhatsApp</span>
    <?php endif; ?>
    <span class="tb-right">
      <span class="hide-sm"><?= e(full_address($s)) ?></span>
      <a href="<?= e(tel_href($s['phone'])) ?>"><?= e($s['phone']) ?></a>
    </span>
  </div>
</div>
<?php
}

function site_header(array $s, string $current = ''): void {
    $links = [
        'home'     => ['Home',          BASE . 'index.php'],
        'services' => ['Services',      BASE . 'index.php#services'],
        'cars'     => ['Cars for Sale', BASE . 'cars.php'],
        'about'    => ['About',         BASE . 'index.php#about'],
        'reviews'  => ['Reviews',       BASE . 'index.php#reviews'],
        'contact'  => ['Contact',       BASE . 'index.php#contact'],
    ];
    /* no reviews yet? then no link to an anchor that is not on the page */
    if (!has_reviews($s)) unset($links['reviews']);
    ?>
<header class="site">
  <div class="shell">
    <?= site_logo($s) ?>
    <button class="burger" id="burger" aria-label="Menu" aria-expanded="false"><span></span><span></span><span></span></button>
    <nav class="main" id="nav">
      <?php foreach ($links as $key => [$label, $href]): ?>
        <a href="<?= $href ?>"<?= $key === $current ? ' aria-current="page"' : '' ?>><?= e($label) ?></a>
      <?php endforeach; ?>
    </nav>
    <div class="head-cta">
      <a class="btn btn--wa btn--sm" href="<?= e(wa_link($s)) ?>" target="_blank" rel="noopener"><?= svg_whatsapp() ?><span>WhatsApp</span></a>
      <a class="btn btn--sm" href="<?= e(tel_href($s['phone'])) ?>"><?= svg_phone() ?><span>Call Now</span></a>
    </div>
  </div>
</header>
<?php
}

function site_footer(array $s): void {
    ?>
<footer class="site">
  <div class="shell">
    <div class="foot-grid">
      <div>
        <?= site_logo($s) ?>
        <p style="margin-top:18px"><?= e($s['footer_blurb']) ?></p>
      </div>
      <div>
        <h4>Services</h4>
        <ul>
          <li><a href="<?= BASE ?>index.php#services">General repairs</a></li>
          <li><a href="<?= BASE ?>index.php#services">Engine &amp; clutch</a></li>
          <li><a href="<?= BASE ?>index.php#services">Brakes &amp; tyres</a></li>
          <li><a href="<?= BASE ?>index.php#services">Diagnostics</a></li>
        </ul>
      </div>
      <div>
        <h4>Sales</h4>
        <ul>
          <li><a href="<?= BASE ?>cars.php">Cars for sale</a></li>
          <li><a href="<?= BASE ?>index.php#contact">Part exchange</a></li>
          <li><a href="<?= BASE ?>admin/">Stock manager</a></li>
        </ul>
      </div>
      <div>
        <h4>Get in touch</h4>
        <ul>
          <li><a href="<?= e(tel_href($s['phone'])) ?>"><?= e($s['phone']) ?></a></li>
          <?php if (!empty($s['mobile'])): ?><li><a href="<?= e(tel_href($s['mobile'])) ?>"><?= e($s['mobile']) ?></a></li><?php endif; ?>
          <li><?= nl2br(e($s['address_line1'] . "\n" . $s['address_line2'] . ' ' . $s['postcode'])) ?></li>
        </ul>
      </div>
    </div>
    <div class="foot-base">
      <span>&copy; <?= date('Y') ?> <?= e($s['business_name']) ?></span>
      <span><?= e(hours_summary($s)) ?></span>
    </div>
  </div>
</footer>

<a class="wa-float" href="<?= e(wa_link($s)) ?>" target="_blank" rel="noopener" aria-label="Chat on WhatsApp"><?= svg_whatsapp() ?></a>

<script src="<?= BASE ?>assets/js/site.js?v=3"></script>
</body>
</html>
<?php
}

/* ---------------- car card, shared by home + listing ---------------- */
function car_card(array $c, array $s): void {
    $enquire = "Hi, I'm interested in the " . car_title($c) . ' ' . $c['trim'] . ' at ' . money($c['price']) . '. Is it still available?';
    $sold    = !empty($c['sold']);
    ?>
  <article class="car<?= $sold ? ' sold' : '' ?>">
    <a class="shot" href="<?= e(car_url($c)) ?>">
      <img src="<?= e(car_photo($c)) ?>" alt="<?= e(car_title($c) . ' ' . $c['trim']) ?>" loading="lazy">
      <span class="price-tag"><?= money($c['price']) ?></span>
      <?php if (count($c['photos'] ?? []) > 1): ?>
        <span class="shot-count">
          <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="5" width="18" height="15" rx="2"/><circle cx="12" cy="12" r="3.5"/></svg>
          <?= count($c['photos']) ?>
        </span>
      <?php endif; ?>
      <?php if ($sold): ?><span class="sold-band"><b>Sold</b></span><?php endif; ?>
    </a>
    <div class="body">
      <h3><a href="<?= e(car_url($c)) ?>"><?= e($c['make'] . ' ' . $c['model']) ?></a></h3>
      <div class="trim"><?= e($c['trim']) ?> · <?= e($c['year']) ?></div>
      <div class="spec-chips">
        <span><?= miles($c['mileage']) ?></span>
        <span><?= e($c['engine']) ?></span>
        <span><?= e($c['gearbox']) ?></span>
        <span><?= e($c['fuel']) ?></span>
      </div>
      <div class="mot">MOT until <b><?= e($c['mot']) ?></b><?= $c['history'] ? ' · ' . e($c['history']) : '' ?></div>
      <div class="acts">
        <?php if ($sold): ?>
          <a class="btn btn--ghost btn--block" href="<?= BASE ?>cars.php">See similar stock</a>
        <?php else: ?>
          <a class="btn" href="<?= e(tel_href($s['phone'])) ?>">Enquire / Call</a>
          <a class="btn btn--wa" href="<?= e(wa_link($s, $enquire)) ?>" target="_blank" rel="noopener"><?= svg_whatsapp() ?>WhatsApp</a>
        <?php endif; ?>
      </div>
    </div>
  </article>
<?php
}
