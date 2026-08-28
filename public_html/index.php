<?php
require __DIR__ . '/inc/init.php';
require __DIR__ . '/inc/parts.php';

$s         = $SETTINGS;
$available = stock_available($STOCK);
$featured  = array_values(array_filter($available, fn($c) => !empty($c['featured'])));
if (count($featured) < 3) {                       // keep the row full even if nothing is flagged
    foreach ($available as $c) {
        if (count($featured) >= 3) break;
        if (!in_array($c['id'], array_column($featured, 'id'), true)) $featured[] = $c;
    }
}
$featured = array_slice($featured, 0, 3);

/* the eight service icons, in the same order as $s['services'] */
$ICONS = [
  '<path d="M14.7 6.3a4 4 0 0 0 5.3 5.2l-8 8a2.8 2.8 0 0 1-4-4l8-8a4 4 0 0 0-1.3-1.2Z"/><path d="M14.7 6.3 18 3l3 3-3.3 3.3"/>',
  '<path d="M4 14v-3h3l2-2h4l2 2h2v-2h2v7h-2v-2h-2l-2 2H9l-2-2H4Z"/><path d="M8 9V6h5"/>',
  '<rect x="3" y="4" width="3.2" height="16" rx="1.4"/><rect x="8.4" y="6.5" width="3.2" height="11" rx="1.4"/><rect x="13.8" y="4" width="3.2" height="16" rx="1.4"/><path d="M17 12h4"/><circle cx="21.2" cy="12" r="1.2"/>',
  '<circle cx="11" cy="12" r="7.6"/><circle cx="11" cy="12" r="2.4"/><path d="M16.4 6.6a7.6 7.6 0 0 1 0 10.8"/><path d="M17.4 7.4h3.2a1 1 0 0 1 1 1v7.2a1 1 0 0 1-1 1h-3.2"/>',
  '<rect x="2" y="5" width="20" height="12" rx="2"/><path d="M6 20h12M9 9l2 3-2 3M14 15h3"/>',
  '<path d="M4 18h10a3 3 0 0 0 3-3v-4h3l-2-3"/><path d="M4 18V9h8v6"/><path d="M7 9V6h4"/>',
  '<rect x="2" y="8" width="18" height="10" rx="2"/><path d="M20 11h2v4h-2M6 5v3M14 5v3M6 13h4M8 11v4"/>',
  '<circle cx="12" cy="12" r="9"/><circle cx="12" cy="12" r="5"/><path d="M12 3v2.4M12 18.6V21M3 12h2.4M18.6 12H21M5.6 5.6l1.7 1.7M18.4 5.6l-1.7 1.7M5.6 18.4l1.7-1.7M18.4 18.4l-1.7-1.7"/><circle cx="12" cy="12" r="1.4"/>',
];

$title = $s['business_name'] . ' — Car Repairs & Quality Used Cars in ' . $s['town'];
page_head($s, $title, $s['meta_desc']);
top_bar($s);
site_header($s, 'home');
?>

<!-- ============ HERO ============ -->
<div class="hero">
  <div class="bg"><img src="assets/img/shop-lift.jpg" alt="Car raised on a ramp in the workshop"></div>
  <div class="shell inner">
    <div class="eyebrow">Independent garage<?= $s['established'] ? ' · Est. ' . e($s['established']) : '' ?> · <?= e($s['town']) ?></div>
    <h1><?= strip_tags($s['hero_heading'], '<em><br>') ?></h1>
    <p class="lede"><?= e($s['hero_text']) ?></p>

    <div class="hero-cta">
      <a class="btn" href="<?= e(tel_href($s['phone'])) ?>"><?= svg_phone() ?>Call Now</a>
      <a class="btn btn--wa" href="<?= e(wa_link($s)) ?>" target="_blank" rel="noopener"><?= svg_whatsapp() ?>WhatsApp Chat</a>
      <a class="btn btn--dark" href="#book">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="4" width="18" height="17" rx="2"/><path d="M16 2v4M8 2v4M3 10h18"/></svg>
        Book a Service
      </a>
      <a class="btn btn--ghost" href="cars.php">
        View Cars for Sale
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M5 12h14M13 6l6 6-6 6"/></svg>
      </a>
    </div>

    <div class="hero-strip">
      <?php if ($s['google_rating']): ?><div>Rated on Google<b class="num"><?= e($s['google_rating']) ?> ★</b></div><?php endif; ?>
      <div>Cars in stock<b class="num"><?= count($available) ?></b></div>
      <?php if ($s['established']): ?><div>Years on site<b class="num"><?= max(1, (int)date('Y') - (int)$s['established']) ?></b></div><?php endif; ?>
      <?php if ($s['warranty']): ?><div>Warranty on repairs<b class="num"><?= e($s['warranty']) ?></b></div><?php endif; ?>
    </div>
  </div>
</div>

<!-- ============ SERVICES ============ -->
<section id="services">
  <div class="shell">
    <div class="sec-head rv">
      <div>
        <div class="eyebrow">Garage services</div>
        <h2>Everything your car needs,<br>under one roof</h2>
        <p class="lede">No job too small. Free written estimate before any work starts, and we will always ring you before spending a penny more than we quoted.</p>
      </div>
      <a class="btn btn--ghost" href="#book">Book a service</a>
    </div>

    <div class="svc-grid rv">
      <?php foreach (($s['services'] ?? []) as $i => $svc): ?>
      <article class="svc">
        <span class="idx"><?= str_pad((string)($i + 1), 2, '0', STR_PAD_LEFT) ?></span>
        <svg class="ico" viewBox="0 0 24 24"><?= $ICONS[$i % count($ICONS)] ?></svg>
        <h3><?= e($svc['title']) ?></h3>
        <p><?= e($svc['text']) ?></p>
      </article>
      <?php endforeach; ?>
    </div>
  </div>
</section>

<!-- ============ CARS ============ -->
<section class="bg-steel" id="cars">
  <div class="shell">
    <div class="sec-head rv">
      <div>
        <div class="eyebrow">Used cars for sale</div>
        <h2>This week on the forecourt</h2>
        <p class="lede">Every car is serviced, MOT'd and HPI checked in our own workshop before it goes on sale. Part exchange welcome.</p>
      </div>
      <a class="btn btn--ghost" href="cars.php">See all stock</a>
    </div>
    <div class="car-grid rv">
      <?php if ($featured): foreach ($featured as $c) car_card($c, $s); else: ?>
        <p class="lede">Nothing on the forecourt just at the moment — give us a ring, we usually have something coming through.</p>
      <?php endif; ?>
    </div>
  </div>
</section>

<!-- ============ ABOUT ============ -->
<section id="about">
  <div class="shell">
    <div class="contact-grid">
      <div class="rv">
        <div class="eyebrow">About the company</div>
        <h2><?= e($s['about_heading']) ?></h2>
        <p class="lede" style="margin-top:20px"><?= e($s['about_1']) ?></p>
        <p class="lede"><?= e($s['about_2']) ?></p>
        <div class="hero-strip" style="margin-top:34px">
          <?php if ($s['serviced_a_year']): ?><div>Vehicles serviced a year<b class="num"><?= e($s['serviced_a_year']) ?></b></div><?php endif; ?>
          <?php if ($s['google_rating']): ?><div>Google rating<b class="num"><?= e($s['google_rating']) ?> ★</b></div><?php endif; ?>
          <?php if ($s['established']): ?><div>Established<b class="num"><?= e($s['established']) ?></b></div><?php endif; ?>
        </div>
      </div>
      <div class="rv">
        <img src="assets/img/shop-diag.jpg" alt="Technician running diagnostics on a vehicle" style="border:1px solid var(--line);border-radius:3px">
      </div>
    </div>
  </div>
</section>

<!-- ============ WHY US ============ -->
<div class="why rv">
  <div>
    <svg class="ico" viewBox="0 0 24 24"><path d="M12 2 3 6v6c0 5 3.8 8.9 9 10 5.2-1.1 9-5 9-10V6l-9-4Z"/><path d="m9 12 2 2 4-4"/></svg>
    <h3>Experienced Service</h3><p>Time-served technicians, dealer-level equipment.</p>
  </div>
  <div>
    <svg class="ico" viewBox="0 0 24 24"><path d="M12 21s-7-4.4-7-10a7 7 0 0 1 14 0c0 5.6-7 10-7 10Z"/><path d="M9 11h6M12 8v6"/></svg>
    <h3>Honest Advice</h3><p>We show you the worn part before we replace it.</p>
  </div>
  <div>
    <svg class="ico" viewBox="0 0 24 24"><path d="M12 2v20M17 6H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/></svg>
    <h3>Competitive Prices</h3><p>Fixed quotes, no hidden extras, main-dealer quality.</p>
  </div>
  <div>
    <svg class="ico" viewBox="0 0 24 24"><path d="M5 17h14l1-6-2-4H6L4 11l1 6Z"/><circle cx="8" cy="18" r="2"/><circle cx="16" cy="18" r="2"/></svg>
    <h3>Quality Used Cars</h3><p>Prepared, warranted and HPI clear before sale.</p>
  </div>
  <div>
    <svg class="ico" viewBox="0 0 24 24"><path d="M12 21s-7-4.4-7-10a7 7 0 0 1 14 0c0 5.6-7 10-7 10Z"/><circle cx="12" cy="11" r="2.5"/></svg>
    <h3>Local Garage</h3><p>Same faces every visit, and we know the area.</p>
  </div>
</div>

<!-- ============ REVIEWS ============ -->
<section id="reviews" class="bg-steel">
  <div class="shell">
    <div class="eyebrow">Customer reviews</div>
    <h2 style="margin-bottom:32px">What our customers say</h2>
    <div class="rev-top rv">
      <div class="rev-score">
        <span class="big num"><?= e($s['google_rating']) ?></span>
        <span class="meta">
          <span class="stars">
            <?php for ($i = 0; $i < 5; $i++): ?>
              <svg viewBox="0 0 24 24"><path d="m12 2 3 6.5 7 .9-5 4.8 1.2 7L12 17.8 5.8 21.2 7 14.2 2 9.4l7-.9L12 2Z"/></svg>
            <?php endfor; ?>
          </span>
          Based on <?= e($s['google_count']) ?> Google reviews
        </span>
      </div>
      <?php if (!empty($s['google_url'])): ?>
        <a class="btn btn--ghost" href="<?= e($s['google_url']) ?>" target="_blank" rel="noopener">Read all reviews on Google</a>
      <?php endif; ?>
    </div>

    <div class="rev-grid rv">
      <?php foreach (array_slice($s['reviews'] ?? [], 0, 3) as $r):
        $words = preg_split('/\s+/', trim($r['name']));
        $ini   = mb_strtoupper(mb_substr($words[0] ?? '', 0, 1) . mb_substr($words[1] ?? '', 0, 1));
      ?>
      <article class="rev">
        <p><?= e($r['text']) ?></p>
        <div class="who"><span class="av"><?= e($ini) ?></span><span><b><?= e($r['name']) ?></b><span><?= e($r['when']) ?></span></span></div>
      </article>
      <?php endforeach; ?>
    </div>
  </div>
</section>

<!-- ============ BOOK / CTA BAND ============ -->
<div class="band" id="book">
  <div class="shell row">
    <div>
      <h2>Book a service or MOT</h2>
      <p>Tell us the registration and what it is doing. We will come back with a price and the first free slot — usually the same day.</p>
    </div>
    <div class="hero-cta" style="margin:0">
      <a class="btn" href="<?= e(tel_href($s['phone'])) ?>">Call <?= e($s['phone']) ?></a>
      <a class="btn btn--wa" href="<?= e(wa_link($s, 'Hi, I would like to book my car in. Registration: ')) ?>" target="_blank" rel="noopener">Message on WhatsApp</a>
    </div>
  </div>
</div>

<!-- ============ CONTACT ============ -->
<section id="contact">
  <div class="shell">
    <div class="sec-head rv">
      <div>
        <div class="eyebrow">Contact &amp; location</div>
        <h2>Where to find us</h2>
      </div>
    </div>
    <div class="contact-grid rv">
      <div>
        <div class="info-line">
          <?= svg_phone() ?>
          <span><b>Workshop</b><a href="<?= e(tel_href($s['phone'])) ?>"><?= e($s['phone']) ?></a></span>
        </div>
        <?php if (!empty($s['mobile'])): ?>
        <div class="info-line">
          <svg viewBox="0 0 24 24"><rect x="6" y="2" width="12" height="20" rx="2"/><path d="M11 18h2"/></svg>
          <span><b>Mobile</b><a href="<?= e(tel_href($s['mobile'])) ?>"><?= e($s['mobile']) ?></a></span>
        </div>
        <?php endif; ?>
        <div class="info-line">
          <svg viewBox="0 0 24 24"><path d="M21 11.5a8.4 8.4 0 0 1-12.2 7.5L3 21l2-5.8A8.4 8.4 0 1 1 21 11.5Z"/></svg>
          <span><b>WhatsApp Business</b><a href="<?= e(wa_link($s)) ?>" target="_blank" rel="noopener">Start a chat</a></span>
        </div>
        <div class="info-line">
          <svg viewBox="0 0 24 24"><path d="M12 21s-7-4.4-7-10a7 7 0 0 1 14 0c0 5.6-7 10-7 10Z"/><circle cx="12" cy="11" r="2.5"/></svg>
          <span><b>Address</b><span><?= e(full_address($s)) ?></span></span>
        </div>

        <h3 style="margin:32px 0 4px">Opening hours</h3>
        <table class="hours">
          <?php $todayIdx = (int)date('N') - 1; foreach (($s['hours'] ?? []) as $i => $h): ?>
            <tr<?= $i === $todayIdx ? ' class="today"' : '' ?>>
              <td><?= e($h['day']) ?></td>
              <td><?= !empty($h['closed']) ? 'Closed' : e($h['open'] . ' – ' . $h['close']) ?></td>
            </tr>
          <?php endforeach; ?>
        </table>
      </div>

      <div>
        <div class="map-embed">
          <iframe src="<?= e(maps_embed($s)) ?>" loading="lazy" referrerpolicy="no-referrer-when-downgrade" title="Map showing where we are"></iframe>
        </div>
        <div class="hero-cta" style="margin-top:16px">
          <a class="btn" href="<?= e(maps_directions($s)) ?>" target="_blank" rel="noopener">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 11 22 2l-9 19-2-8-8-2Z"/></svg>
            Get Directions
          </a>
          <a class="btn btn--ghost" href="<?= e(tel_href($s['phone'])) ?>">Call the workshop</a>
        </div>
      </div>
    </div>
  </div>
</section>

<?php site_footer($s);
