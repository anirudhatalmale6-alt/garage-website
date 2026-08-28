<?php
define('BASE', '../');
require __DIR__ . '/../inc/init.php';
require __DIR__ . '/../inc/parts.php';
require __DIR__ . '/_layout.php';

admin_require_login();
$s = $SETTINGS;

/* ---------------- actions ---------------- */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $id   = $_POST['id'] ?? '';
    $what = $_POST['do'] ?? '';
    $rows = bookings_all();

    foreach ($rows as $i => $b) {
        if (($b['id'] ?? '') !== $id) continue;

        if ($what === 'delete') {
            array_splice($rows, $i, 1);
            flash_set('ok', 'Booking ' . $b['ref'] . ' removed from the diary.');
        } elseif (in_array($what, ['new', 'confirmed', 'done', 'cancelled'], true)) {
            $rows[$i]['status'] = $what;
            $words = [
                'confirmed' => 'marked as confirmed',
                'done'      => 'marked as done',
                'cancelled' => 'cancelled — the slot is free again',
                'new'       => 'put back to new',
            ];
            flash_set('ok', 'Booking ' . $b['ref'] . ' ' . $words[$what] . '.');
        }
        bookings_save($rows);
        break;
    }
    header('Location: bookings.php' . (isset($_POST['past']) ? '?show=past' : ''));
    exit;
}

/* ---------------- read ---------------- */
$showPast = ($_GET['show'] ?? '') === 'past';
$today    = date('Y-m-d');

$rows = bookings_all();
usort($rows, function ($a, $b) {
    return [$a['date'] ?? '', $a['time'] ?? ''] <=> [$b['date'] ?? '', $b['time'] ?? ''];
});

$upcoming = $past = [];
foreach ($rows as $b) {
    if (($b['date'] ?? '') >= $today && ($b['status'] ?? '') !== 'cancelled') $upcoming[] = $b;
    else $past[] = $b;
}
$past = array_reverse($past);
$list = $showPast ? $past : $upcoming;

/* group by day so it reads like a diary rather than a spreadsheet */
$byDay = [];
foreach ($list as $b) $byDay[$b['date'] ?? ''][] = $b;

$newCount = 0;
foreach ($upcoming as $b) if (($b['status'] ?? 'new') === 'new') $newCount++;

/* did any recent booking fail to email? worth saying out loud */
$mailTrouble = 0;
foreach (array_slice($rows, -20) as $b) {
    if (isset($b['mail_garage']) && !$b['mail_garage']) $mailTrouble++;
}

$STATUS = [
    'new'       => ['New',       'st-new'],
    'confirmed' => ['Confirmed', 'st-ok'],
    'done'      => ['Done',      'st-done'],
    'cancelled' => ['Cancelled', 'st-off'],
];

admin_start($s, 'Bookings', 'bookings');
?>

<div class="pagehead">
  <div class="shell inner">
    <div class="crumbs"><a href="index.php">Stock manager</a> / Bookings</div>
    <h1>Booking diary</h1>
    <p class="lede">Everything customers book on the website lands here — whether or not the email got through.</p>
  </div>
</div>

<section>
  <div class="shell admin-wrap">
    <?php flash_out(); ?>

    <?php if (!booking_enabled($s)): ?>
      <div class="flash bad">Online booking is switched off, so customers cannot book at the moment. Turn it back on in <a href="settings.php#booking">Business details</a>.</div>
    <?php elseif (empty($s['booking_email'])): ?>
      <div class="flash bad">No email address set for new bookings — nobody is being told when one comes in. Add one in <a href="settings.php#booking">Business details</a>.</div>
    <?php elseif ($mailTrouble): ?>
      <div class="flash bad"><?= (int)$mailTrouble ?> recent booking<?= $mailTrouble === 1 ? '' : 's' ?> could not be emailed to you. The bookings are all safely listed below — but check the address in <a href="settings.php#booking">Business details</a>.</div>
    <?php endif; ?>

    <div class="book-tabs">
      <a class="btn btn--sm<?= $showPast ? ' btn--ghost' : '' ?>" href="bookings.php">
        Upcoming<?= $upcoming ? ' (' . count($upcoming) . ')' : '' ?>
      </a>
      <a class="btn btn--sm<?= $showPast ? '' : ' btn--ghost' ?>" href="bookings.php?show=past">
        Past &amp; cancelled<?= $past ? ' (' . count($past) . ')' : '' ?>
      </a>
      <?php if ($newCount): ?><span class="pill pill--new"><?= (int)$newCount ?> not yet confirmed</span><?php endif; ?>
    </div>

    <?php if (!$byDay): ?>
      <div class="panel"><div class="panel-body pick-first">
        <h3><?= $showPast ? 'Nothing here yet' : 'No bookings in the diary' ?></h3>
        <p>When somebody books on <a href="<?= BASE ?>book.php" target="_blank" rel="noopener">the booking page</a> it will appear here straight away.</p>
      </div></div>
    <?php endif; ?>

    <?php foreach ($byDay as $ymd => $days): ?>
      <div class="panel">
        <div class="panel-head">
          <h3><?= e(booking_pretty_date($ymd)) ?><?= $ymd === $today ? ' — today' : '' ?></h3>
          <span class="hint"><?= count($days) ?> booking<?= count($days) === 1 ? '' : 's' ?></span>
        </div>
        <div class="panel-body">
          <?php foreach ($days as $b):
            [$label, $cls] = $STATUS[$b['status'] ?? 'new'] ?? $STATUS['new'];
          ?>
            <div class="bk">
              <div class="bk-time"><b><?= e($b['time']) ?></b><span><?= e($b['ref']) ?></span></div>
              <div class="bk-main">
                <h4><?= e($b['name']) ?> <span class="pill <?= $cls ?>"><?= e($label) ?></span></h4>
                <p class="bk-svc"><?= e($b['service']) ?><?= !empty($b['vehicle']) ? ' · ' . e($b['vehicle']) : '' ?></p>
                <p class="bk-con">
                  <a href="<?= e(tel_href($b['phone'])) ?>"><?= e($b['phone']) ?></a>
                  <?php if (!empty($b['email'])): ?> · <?= e($b['email']) ?><?php endif; ?>
                  · <a href="<?= e(wa_link(['whatsapp' => intl_uk($b['phone'])], 'Hi ' . explode(' ', $b['name'])[0] . ', about your booking ' . $b['ref'] . ' on ' . booking_pretty_date($b['date']) . ' at ' . $b['time'] . ' — ')) ?>" target="_blank" rel="noopener">WhatsApp them</a>
                </p>
                <?php if (!empty($b['notes'])): ?><p class="bk-note"><?= e($b['notes']) ?></p><?php endif; ?>
              </div>
              <div class="bk-acts">
                <?php
                  $buttons = [];
                  $st = $b['status'] ?? 'new';
                  if ($st !== 'confirmed' && $st !== 'done') $buttons['confirmed'] = ['Confirm', 'btn--dark'];
                  if ($st !== 'done')      $buttons['done']      = ['Done', 'btn--ghost'];
                  if ($st !== 'cancelled') $buttons['cancelled'] = ['Cancel', 'btn--ghost'];
                  foreach ($buttons as $do => [$txt, $style]):
                ?>
                  <form method="post" class="inline"<?= $do === 'cancelled' ? ' onsubmit="return confirm(\'Cancel this booking? The slot goes back on the website.\')"' : '' ?>>
                    <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>">
                    <input type="hidden" name="id" value="<?= e($b['id']) ?>">
                    <input type="hidden" name="do" value="<?= e($do) ?>">
                    <?php if ($showPast): ?><input type="hidden" name="past" value="1"><?php endif; ?>
                    <button class="btn btn--sm <?= $style ?>" type="submit"><?= e($txt) ?></button>
                  </form>
                <?php endforeach; ?>
                <form method="post" class="inline" onsubmit="return confirm('Delete this booking for good?')">
                  <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>">
                  <input type="hidden" name="id" value="<?= e($b['id']) ?>">
                  <input type="hidden" name="do" value="delete">
                  <?php if ($showPast): ?><input type="hidden" name="past" value="1"><?php endif; ?>
                  <button class="btn btn--sm btn--ghost danger" type="submit">Delete</button>
                </form>
              </div>
            </div>
          <?php endforeach; ?>
        </div>
      </div>
    <?php endforeach; ?>

  </div>
</section>

<?php admin_end();
