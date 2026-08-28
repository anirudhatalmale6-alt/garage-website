<?php
require __DIR__ . '/inc/init.php';
require __DIR__ . '/inc/parts.php';

$s = $SETTINGS;

/* ------------------------------------------------------------------
   Which day is the customer looking at?
------------------------------------------------------------------ */
[$firstDate, $lastDate] = booking_window($s);

$day = $_GET['d'] ?? $_POST['date'] ?? '';
if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $day)) $day = '';

$month = $_GET['m'] ?? '';
if (!preg_match('/^\d{4}-\d{2}$/', $month)) {
    $month = $day !== '' ? substr($day, 0, 7) : substr($firstDate, 0, 7);
}

$errors = [];
$form   = [
    'name' => '', 'phone' => '', 'email' => '',
    'vehicle' => '', 'service' => '', 'notes' => '', 'time' => '',
];

/* ------------------------------------------------------------------
   Taking the booking
------------------------------------------------------------------ */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['book'])) {

    /* Bots fill in every field they can see, including the one that is
       hidden. Quietly pretend it worked rather than telling them why. */
    if (trim($_POST['website'] ?? '') !== '') {
        header('Location: book.php?ok=' . rawurlencode('MB-XXXXX'));
        exit;
    }

    foreach ($form as $k => $_) $form[$k] = trim((string)($_POST[$k] ?? ''));

    if (!booking_enabled($s)) {
        $errors[] = 'Online booking is switched off at the moment — please give us a ring.';
    }
    if ($day === '')            $errors[] = 'Pick a day first.';
    if ($form['time'] === '')   $errors[] = 'Pick a time.';
    if ($form['name'] === '')   $errors[] = 'We need a name to put in the diary.';
    if ($form['phone'] === '')  $errors[] = 'We need a phone number in case we have to reach you.';
    elseif (strlen(preg_replace('/[^0-9]/', '', $form['phone'])) < 9) {
        $errors[] = 'That phone number looks too short — please check it.';
    }
    if ($form['email'] !== '' && !filter_var($form['email'], FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'That email address does not look right.';
    }
    if ($form['service'] === '') $form['service'] = booking_services($s)[0];

    /* Someone else may have taken the slot while this form was open. */
    if (!$errors && !booking_slot_free($s, $day, $form['time'])) {
        $errors[] = 'Sorry — that slot has just gone. Please pick another time below.';
    }

    /* a crude flood guard: nobody books five cars in an hour */
    if (!$errors) {
        $ip = $_SERVER['REMOTE_ADDR'] ?? '';
        $recent = 0;
        foreach (bookings_all() as $b) {
            if (($b['ip'] ?? '') === $ip && (int)($b['created'] ?? 0) > time() - 3600) $recent++;
        }
        if ($recent >= 5) $errors[] = 'That is a lot of bookings from one place. Please ring us instead.';
    }

    if (!$errors) {
        $booking = [
            'id'      => bin2hex(random_bytes(6)),
            'ref'     => booking_ref(),
            'created' => time(),
            'date'    => $day,
            'time'    => $form['time'],
            'service' => $form['service'],
            'name'    => $form['name'],
            'phone'   => $form['phone'],
            'email'   => $form['email'],
            'vehicle' => $form['vehicle'],
            'notes'   => $form['notes'],
            'status'  => 'new',
            'ip'      => $_SERVER['REMOTE_ADDR'] ?? '',
        ];

        /* Saved first, emailed second. If the mail server is down the
           booking is still in the diary — it is never lost. */
        $rows   = bookings_all();
        $rows[] = $booking;
        bookings_save($rows);

        $to = trim((string)($s['booking_email'] ?? ''));
        $booking['mail_garage'] = $to !== '' && booking_mail(
            $s, $to,
            'New booking ' . $booking['ref'] . ' — ' . booking_pretty_date($day) . ' ' . $booking['time'],
            booking_garage_body($s, $booking),
            $booking['email']
        );
        $booking['mail_customer'] = $booking['email'] !== '' && booking_mail(
            $s, $booking['email'],
            $s['business_name'] . ' — booking confirmed for ' . booking_pretty_date($day) . ' at ' . $booking['time'],
            booking_customer_body($s, $booking)
        );

        /* re-read before writing back: the file may have moved on */
        $rows = bookings_all();
        foreach ($rows as $i => $r) {
            if (($r['id'] ?? '') === $booking['id']) { $rows[$i] = $booking; break; }
        }
        bookings_save($rows);

        header('Location: book.php?ok=' . rawurlencode($booking['ref']));
        exit;
    }
}

/* ------------------------------------------------------------------
   Thank you page
------------------------------------------------------------------ */
$doneRef = trim((string)($_GET['ok'] ?? ''));
$done    = null;
if ($doneRef !== '') {
    foreach (bookings_all() as $b) if (($b['ref'] ?? '') === $doneRef) { $done = $b; break; }
}

$title = 'Book a Service — ' . $s['business_name'] . ', ' . $s['town'];
page_head($s, $title, 'Book your car in online at ' . $s['business_name'] . ' in ' . $s['town'] . '. Pick a day and a time that suits you and we will confirm it.');
top_bar($s);
site_header($s, 'book');
?>

<div class="pagehead">
  <div class="shell inner">
    <div class="eyebrow">Book online</div>
    <h1><?= $done ? 'You are booked in' : 'Book a service or repair' ?></h1>
    <?php if (!$done): ?>
      <p class="lede"><?= e($s['booking_note']) ?></p>
    <?php endif; ?>
  </div>
</div>

<?php if ($done): /* ============ CONFIRMED ============ */
  $waText = 'Hi, I have just booked online. Reference ' . $done['ref'] . ' — '
          . booking_pretty_date($done['date']) . ' at ' . $done['time'] . ' for ' . $done['service'] . '.';
?>
<section>
  <div class="shell narrow">
    <div class="booked">
      <div class="tick" aria-hidden="true">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4"><path d="m4 12.5 5.2 5.2L20 7"/></svg>
      </div>
      <h2>Thanks, <?= e(explode(' ', $done['name'])[0]) ?> — it is in the diary.</h2>
      <p class="lede">Your reference is <b><?= e($done['ref']) ?></b>. Quote it if you need to change anything.</p>

      <dl class="booked-facts">
        <div><dt>When</dt><dd><?= e(booking_pretty_date($done['date'])) ?><br><b><?= e($done['time']) ?></b></dd></div>
        <div><dt>Work</dt><dd><?= e($done['service']) ?></dd></div>
        <?php if (!empty($done['vehicle'])): ?><div><dt>Vehicle</dt><dd><?= e($done['vehicle']) ?></dd></div><?php endif; ?>
        <div><dt>Where</dt><dd><?= e(full_address($s)) ?></dd></div>
      </dl>

      <?php if (!empty($done['email'])): ?>
        <p class="hint">
          <?php if (!empty($done['mail_customer'])): ?>
            A confirmation is on its way to <?= e($done['email']) ?>. If it is not there in a few minutes, have a look in your spam folder.
          <?php else: ?>
            We could not send the confirmation email just now, but the booking is safely in our diary — nothing is lost.
          <?php endif; ?>
        </p>
      <?php endif; ?>

      <div class="hero-cta" style="justify-content:center">
        <a class="btn btn--wa" href="<?= e(wa_link($s, $waText)) ?>" target="_blank" rel="noopener"><?= svg_whatsapp() ?>Send us a WhatsApp about it</a>
        <a class="btn btn--ghost" href="<?= e(maps_directions($s)) ?>" target="_blank" rel="noopener">Get directions</a>
      </div>
      <p class="hint" style="margin-top:22px">Need to change or cancel? Ring <a href="<?= e(tel_href($s['phone'])) ?>"><?= e($s['phone']) ?></a>.</p>
    </div>
  </div>
</section>

<?php elseif (!booking_enabled($s)): /* ============ SWITCHED OFF ============ */ ?>
<section>
  <div class="shell narrow">
    <div class="panel"><div class="panel-body" style="text-align:center">
      <h3>Online booking is off just now</h3>
      <p class="lede">Give us a ring or send a WhatsApp and we will get you booked in.</p>
      <div class="hero-cta" style="justify-content:center">
        <a class="btn" href="<?= e(tel_href($s['phone'])) ?>"><?= svg_phone() ?>Call <?= e($s['phone']) ?></a>
        <a class="btn btn--wa" href="<?= e(wa_link($s)) ?>" target="_blank" rel="noopener"><?= svg_whatsapp() ?>WhatsApp</a>
      </div>
    </div></div>
  </div>
</section>

<?php else: /* ============ THE PICKER ============ */

  $mStart  = $month . '-01';
  $mTs     = strtotime($mStart);
  $daysIn  = (int)date('t', $mTs);
  $lead    = (int)date('N', $mTs) - 1;                 // blanks before the 1st
  $prevM   = date('Y-m', strtotime($mStart . ' -1 month'));
  $nextM   = date('Y-m', strtotime($mStart . ' +1 month'));
  $canPrev = $prevM >= substr($firstDate, 0, 7);
  $canNext = $nextM <= substr($lastDate, 0, 7);
  /* A day outside the bookable window is not on offer, however the
     visitor got to it — a stale link, a bookmark, a typed URL. */
  $dayOpen = $day !== '' && $day >= $firstDate && $day <= $lastDate;
  $slots   = $dayOpen ? booking_slots($s, $day) : [];
?>
<section>
  <div class="shell book-grid">

    <!-- ---------- calendar ---------- -->
    <div class="panel cal-panel">
      <div class="panel-head">
        <h3>1. Pick a day</h3>
        <span class="hint">Green days have free slots</span>
      </div>
      <div class="panel-body">
        <div class="cal-nav">
          <?php if ($canPrev): ?>
            <a class="btn btn--ghost btn--sm" href="?m=<?= e($prevM) ?><?= $day ? '&d=' . e($day) : '' ?>" aria-label="Previous month">&larr;</a>
          <?php else: ?><span class="btn btn--ghost btn--sm is-off" aria-hidden="true">&larr;</span><?php endif; ?>
          <strong><?= e(date('F Y', $mTs)) ?></strong>
          <?php if ($canNext): ?>
            <a class="btn btn--ghost btn--sm" href="?m=<?= e($nextM) ?><?= $day ? '&d=' . e($day) : '' ?>" aria-label="Next month">&rarr;</a>
          <?php else: ?><span class="btn btn--ghost btn--sm is-off" aria-hidden="true">&rarr;</span><?php endif; ?>
        </div>

        <div class="cal">
          <?php foreach (['Mon','Tue','Wed','Thu','Fri','Sat','Sun'] as $dn): ?>
            <span class="cal-dow"><?= $dn ?></span>
          <?php endforeach; ?>

          <?php for ($i = 0; $i < $lead; $i++): ?><span class="cal-pad"></span><?php endfor; ?>

          <?php for ($d = 1; $d <= $daysIn; $d++):
            $ymd   = sprintf('%s-%02d', $month, $d);
            $state = booking_day_state($s, $ymd);
            $free  = $state === 'open' ? booking_free_count($s, $ymd) : 0;
            $cls   = 'cal-day is-' . $state . ($ymd === $day ? ' is-on' : '');
          ?>
            <?php if ($state === 'open'): ?>
              <a class="<?= $cls ?>" href="?d=<?= e($ymd) ?>#times">
                <b><?= $d ?></b><span><?= $free ?> free</span>
              </a>
            <?php else: ?>
              <span class="<?= $cls ?>">
                <b><?= $d ?></b><span><?= $state === 'full' ? 'Full' : ($state === 'closed' ? 'Closed' : '') ?></span>
              </span>
            <?php endif; ?>
          <?php endfor; ?>
        </div>

        <ul class="cal-key">
          <li><i class="k-open"></i> Slots free</li>
          <li><i class="k-full"></i> Fully booked</li>
          <li><i class="k-closed"></i> Closed</li>
        </ul>
      </div>
    </div>

    <!-- ---------- times + form ---------- -->
    <div id="times">
      <?php if ($errors): ?>
        <div class="flash bad"><?= implode('<br>', array_map('e', $errors)) ?></div>
      <?php endif; ?>

      <?php if ($day === ''): ?>
        <div class="panel"><div class="panel-body pick-first">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6"><rect x="3" y="4" width="18" height="17" rx="2"/><path d="M16 2v4M8 2v4M3 10h18"/></svg>
          <h3>Choose a day on the calendar</h3>
          <p>You will see every free time for that day, and can book it there and then.</p>
        </div></div>

      <?php elseif (!$dayOpen): ?>
        <div class="panel"><div class="panel-body pick-first">
          <h3>That day is not open for booking</h3>
          <p>You can book from <?= e(booking_pretty_date($firstDate)) ?> up to <?= e(booking_pretty_date($lastDate)) ?>. Pick a green day on the calendar.</p>
        </div></div>

      <?php elseif (!$slots): ?>
        <div class="panel"><div class="panel-body pick-first">
          <h3>We are closed on <?= e(booking_pretty_date($day)) ?></h3>
          <p>Pick another day, or ring us on <a href="<?= e(tel_href($s['phone'])) ?>"><?= e($s['phone']) ?></a>.</p>
        </div></div>

      <?php else: ?>
        <form method="post" class="panel">
          <input type="hidden" name="book" value="1">
          <input type="hidden" name="date" value="<?= e($day) ?>">
          <div class="hp"><label>Leave this empty<input type="text" name="website" tabindex="-1" autocomplete="off"></label></div>

          <div class="panel-head">
            <h3>2. Pick a time</h3>
            <span class="hint"><?= e(booking_pretty_date($day)) ?></span>
          </div>
          <div class="panel-body">
            <?php $any = false; ?>
            <div class="slots">
              <?php foreach ($slots as $sl):
                $free = !$sl['past'] && $sl['left'] > 0;
                if ($free) $any = true;
              ?>
                <label class="slot<?= $free ? '' : ' is-off' ?>">
                  <input type="radio" name="time" value="<?= e($sl['time']) ?>"
                         <?= $free ? '' : 'disabled' ?>
                         <?= $form['time'] === $sl['time'] ? 'checked' : '' ?> required>
                  <b><?= e($sl['time']) ?></b>
                  <span><?= $sl['past'] ? 'Gone' : ($sl['left'] > 0 ? $sl['left'] . ' free' : 'Booked') ?></span>
                </label>
              <?php endforeach; ?>
            </div>
            <?php if (!$any): ?>
              <p class="hint" style="margin-top:14px">Every slot on this day has gone. Try another day, or ring us — we can sometimes squeeze one in.</p>
            <?php endif; ?>
          </div>

          <?php if ($any): ?>
          <div class="panel-head"><h3>3. Your details</h3></div>
          <div class="panel-body">
            <div class="grid-2">
              <label class="fld"><span>Your name *</span>
                <input name="name" value="<?= e($form['name']) ?>" required autocomplete="name"></label>
              <label class="fld"><span>Phone number *</span>
                <input name="phone" value="<?= e($form['phone']) ?>" required inputmode="tel" autocomplete="tel"></label>
              <label class="fld"><span>Email (for your confirmation)</span>
                <input name="email" type="email" value="<?= e($form['email']) ?>" autocomplete="email" placeholder="you@example.com"></label>
              <label class="fld"><span>What is it for?</span>
                <select name="service">
                  <?php foreach (booking_services($s) as $svc): ?>
                    <option<?= $form['service'] === $svc ? ' selected' : '' ?>><?= e($svc) ?></option>
                  <?php endforeach; ?>
                </select>
              </label>
              <label class="fld" style="grid-column:1/-1"><span>Car and registration</span>
                <input name="vehicle" value="<?= e($form['vehicle']) ?>" placeholder="Ford Fiesta — AB16 CDE"></label>
              <label class="fld" style="grid-column:1/-1"><span>Anything we should know?</span>
                <textarea name="notes" rows="3" placeholder="Grinding noise from the front when braking…"><?= e($form['notes']) ?></textarea></label>
            </div>
          </div>
          <div class="form-actions">
            <button class="btn" type="submit">Confirm my booking</button>
            <span class="hint">No payment needed — you settle up at the garage.</span>
          </div>
          <?php endif; ?>
        </form>
      <?php endif; ?>
    </div>

  </div>
</section>
<?php endif; ?>

<?php site_footer($s);
