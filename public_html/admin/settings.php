<?php
define('BASE', '../');
require __DIR__ . '/../inc/init.php';
require __DIR__ . '/../inc/parts.php';
require __DIR__ . '/_layout.php';

admin_require_login();
$s      = $SETTINGS;
$errors = [];

/* ---------------- change the password ---------------- */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['form'] ?? '') === 'password') {
    csrf_check();
    $cur = $_POST['current'] ?? '';
    $new = $_POST['new'] ?? '';
    $rpt = $_POST['repeat'] ?? '';

    if (!password_verify($cur, $s['password_hash'] ?? '')) $errors[] = 'Your current password is not right.';
    if (strlen($new) < 8)   $errors[] = 'Make the new password at least 8 characters.';
    if ($new !== $rpt)      $errors[] = 'The two new passwords do not match.';

    if (!$errors) {
        $s['password_hash']       = password_hash($new, PASSWORD_DEFAULT);
        $s['password_is_default'] = false;
        store_write('settings', $s);
        flash_set('ok', 'Password changed. Use the new one next time you sign in.');
        header('Location: settings.php');
        exit;
    }
}

/* ---------------- save the business details ---------------- */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['form'] ?? '') === 'details') {
    csrf_check();

    foreach ([
        'business_name', 'strapline', 'logo_mark', 'established', 'town',
        'phone', 'mobile', 'whatsapp',
        'address_line1', 'address_line2', 'postcode',
        'hero_heading', 'hero_text', 'meta_desc',
        'about_heading', 'about_1', 'about_2', 'footer_blurb',
        'serviced_a_year', 'warranty',
        'google_rating', 'google_count', 'google_url',
    ] as $k) {
        if (isset($_POST[$k])) $s[$k] = trim($_POST[$k]);
    }

    /* opening hours */
    $hours = [];
    foreach (($_POST['day'] ?? []) as $i => $day) {
        $hours[] = [
            'day'    => trim($day),
            'open'   => trim($_POST['open'][$i] ?? ''),
            'close'  => trim($_POST['close'][$i] ?? ''),
            'closed' => !empty($_POST['closed'][$i]),
        ];
    }
    if ($hours) $s['hours'] = $hours;

    /* services */
    $svcs = [];
    foreach (($_POST['svc_title'] ?? []) as $i => $t) {
        $t = trim($t);
        if ($t === '') continue;
        $svcs[] = ['title' => $t, 'text' => trim($_POST['svc_text'][$i] ?? '')];
    }
    if ($svcs) $s['services'] = $svcs;

    /* reviews — an empty name drops the row */
    $revs = [];
    foreach (($_POST['rev_name'] ?? []) as $i => $n) {
        $n = trim($n);
        if ($n === '' || trim($_POST['rev_text'][$i] ?? '') === '') continue;
        $revs[] = ['name' => $n, 'when' => trim($_POST['rev_when'][$i] ?? ''), 'text' => trim($_POST['rev_text'][$i])];
    }
    $s['reviews'] = $revs;

    /* logo */
    if (!empty($_FILES['logo']['name']) && ($_FILES['logo']['error'] ?? 1) === UPLOAD_ERR_OK) {
        $info = @getimagesize($_FILES['logo']['tmp_name']);
        $ok   = [IMAGETYPE_JPEG, IMAGETYPE_PNG, IMAGETYPE_WEBP, IMAGETYPE_GIF];
        if ($info && in_array($info[2], $ok, true)) {
            $ext  = image_type_to_extension($info[2], false);
            $name = 'logo-' . bin2hex(random_bytes(3)) . '.' . $ext;
            if (move_uploaded_file($_FILES['logo']['tmp_name'], UPLOAD_DIR . '/' . $name)) {
                if (!empty($s['logo_file']) && str_starts_with($s['logo_file'], 'uploads/')) {
                    @unlink(ROOT_DIR . '/' . $s['logo_file']);
                }
                $s['logo_file'] = 'uploads/' . $name;
            }
        } else {
            $errors[] = 'That logo file was not an image I could read — try a PNG or JPG.';
        }
    }
    if (!empty($_POST['drop_logo'])) {
        if (!empty($s['logo_file']) && str_starts_with($s['logo_file'], 'uploads/')) @unlink(ROOT_DIR . '/' . $s['logo_file']);
        $s['logo_file'] = '';
    }

    if (!$errors) {
        store_write('settings', $s);
        flash_set('ok', 'Saved — the website is showing your new details now.');
        header('Location: settings.php');
        exit;
    }
}

admin_start($s, 'Business details', 'settings');
?>

<div class="pagehead">
  <div class="shell inner">
    <div class="crumbs"><a href="index.php">Stock manager</a> / Business details</div>
    <h1>Business details</h1>
    <p class="lede">Your name, number, address, opening hours and the words on the home page. Change anything here and it changes everywhere on the site at once.</p>
  </div>
</div>

<section>
  <div class="shell admin-wrap">
    <?php flash_out(); ?>
    <?php if ($errors): ?><div class="flash bad"><?= implode('<br>', array_map('e', $errors)) ?></div><?php endif; ?>

    <form method="post" enctype="multipart/form-data">
      <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>">
      <input type="hidden" name="form" value="details">

      <div class="panel">
        <div class="panel-head"><h3>Name &amp; logo</h3></div>
        <div class="panel-body">
          <div class="grid-4">
            <label class="fld" style="grid-column:span 2"><span>Business name</span><input name="business_name" value="<?= e($s['business_name']) ?>"></label>
            <label class="fld" style="grid-column:span 2"><span>Strapline (under the name)</span><input name="strapline" value="<?= e($s['strapline']) ?>"></label>
            <label class="fld"><span>Town / city</span><input name="town" value="<?= e($s['town']) ?>"></label>
            <label class="fld"><span>Established</span><input name="established" value="<?= e($s['established']) ?>" placeholder="2009"></label>
            <label class="fld"><span>Logo letter</span><input name="logo_mark" value="<?= e($s['logo_mark']) ?>" maxlength="2"></label>
            <label class="fld"><span>Or upload a logo</span><input type="file" name="logo" accept="image/*"></label>
          </div>
          <?php if (!empty($s['logo_file'])): ?>
            <div class="logo-now">
              <img src="<?= e(BASE . $s['logo_file']) ?>" alt="Current logo">
              <label class="check"><input type="checkbox" name="drop_logo"> <span>Remove this logo and go back to the letter mark</span></label>
            </div>
          <?php endif; ?>
        </div>
      </div>

      <div class="panel">
        <div class="panel-head"><h3>Contact &amp; address</h3></div>
        <div class="panel-body">
          <div class="grid-4">
            <label class="fld"><span>Landline</span><input name="phone" value="<?= e($s['phone']) ?>" placeholder="0113 000 0000"></label>
            <label class="fld"><span>Mobile</span><input name="mobile" value="<?= e($s['mobile']) ?>" placeholder="07000 000000"></label>
            <label class="fld" style="grid-column:span 2"><span>WhatsApp Business number</span><input name="whatsapp" value="<?= e($s['whatsapp']) ?>" placeholder="447000000000"></label>
            <label class="fld" style="grid-column:span 2"><span>Address line 1</span><input name="address_line1" value="<?= e($s['address_line1']) ?>"></label>
            <label class="fld"><span>Town</span><input name="address_line2" value="<?= e($s['address_line2']) ?>"></label>
            <label class="fld"><span>Postcode</span><input name="postcode" value="<?= e($s['postcode']) ?>"></label>
          </div>
          <p class="hint">The WhatsApp number needs the country code and no spaces — a UK 07123 456789 becomes 447123456789. The map and Get Directions button follow the address automatically.</p>
        </div>
      </div>

      <div class="panel">
        <div class="panel-head"><h3>Opening hours</h3></div>
        <div class="panel-body">
          <table class="hours-edit">
            <?php foreach (($s['hours'] ?? []) as $i => $h): ?>
              <tr>
                <td><input name="day[<?= $i ?>]" value="<?= e($h['day']) ?>" class="day"></td>
                <td><input name="open[<?= $i ?>]" value="<?= e($h['open']) ?>" placeholder="08:00" class="time"></td>
                <td class="dash">to</td>
                <td><input name="close[<?= $i ?>]" value="<?= e($h['close']) ?>" placeholder="18:00" class="time"></td>
                <td><label class="check"><input type="checkbox" name="closed[<?= $i ?>]" <?= !empty($h['closed']) ? 'checked' : '' ?>> <span>Closed</span></label></td>
              </tr>
            <?php endforeach; ?>
          </table>
          <p class="hint">Times go in as 24 hour — 08:00, 17:30. Today's row is highlighted on the site, and the green "open now" line at the top switches itself off when you shut.</p>
        </div>
      </div>

      <div class="panel">
        <div class="panel-head"><h3>Home page words</h3></div>
        <div class="panel-body">
          <label class="fld"><span>Headline</span><input name="hero_heading" value="<?= e($s['hero_heading']) ?>"></label>
          <p class="hint" style="margin:6px 0 18px">Wrap the part you want in amber with &lt;em&gt; … &lt;/em&gt;</p>
          <label class="fld"><span>Opening paragraph</span><textarea name="hero_text" rows="3"><?= e($s['hero_text']) ?></textarea></label>
          <div class="grid-4" style="margin-top:18px">
            <label class="fld" style="grid-column:span 2"><span>About heading</span><input name="about_heading" value="<?= e($s['about_heading']) ?>"></label>
            <label class="fld"><span>Vehicles serviced a year</span><input name="serviced_a_year" value="<?= e($s['serviced_a_year']) ?>"></label>
            <label class="fld"><span>Warranty on repairs</span><input name="warranty" value="<?= e($s['warranty']) ?>"></label>
          </div>
          <label class="fld" style="margin-top:18px"><span>About, first paragraph</span><textarea name="about_1" rows="3"><?= e($s['about_1']) ?></textarea></label>
          <label class="fld"><span>About, second paragraph</span><textarea name="about_2" rows="3"><?= e($s['about_2']) ?></textarea></label>
          <label class="fld"><span>Footer line</span><textarea name="footer_blurb" rows="2"><?= e($s['footer_blurb']) ?></textarea></label>
          <label class="fld"><span>Google search description</span><textarea name="meta_desc" rows="2"><?= e($s['meta_desc']) ?></textarea></label>
        </div>
      </div>

      <div class="panel">
        <div class="panel-head">
          <h3>Services</h3>
          <span class="hint">Clear a title to drop that box off the site</span>
        </div>
        <div class="panel-body">
          <?php foreach (($s['services'] ?? []) as $i => $svc): ?>
            <div class="grid-svc">
              <label class="fld"><span>Service <?= $i + 1 ?></span><input name="svc_title[<?= $i ?>]" value="<?= e($svc['title']) ?>"></label>
              <label class="fld"><span>Description</span><input name="svc_text[<?= $i ?>]" value="<?= e($svc['text']) ?>"></label>
            </div>
          <?php endforeach; ?>
        </div>
      </div>

      <div class="panel">
        <div class="panel-head"><h3>Reviews</h3></div>
        <div class="panel-body">
          <div class="grid-4">
            <label class="fld"><span>Google rating</span><input name="google_rating" value="<?= e($s['google_rating']) ?>" placeholder="4.9"></label>
            <label class="fld"><span>Number of reviews</span><input name="google_count" value="<?= e($s['google_count']) ?>" placeholder="187"></label>
            <label class="fld" style="grid-column:span 2"><span>Link to your Google reviews</span><input name="google_url" value="<?= e($s['google_url']) ?>" placeholder="https://g.page/r/..."></label>
          </div>
          <p class="hint" style="margin:4px 0 20px">Paste the three reviews you are proudest of. The "Read all reviews on Google" button only appears once you put a link in.</p>

          <?php $revs = $s['reviews'] ?? []; for ($i = 0; $i < max(3, count($revs)); $i++): $r = $revs[$i] ?? ['name' => '', 'when' => '', 'text' => '']; ?>
            <div class="rev-edit">
              <div class="grid-4">
                <label class="fld"><span>Name</span><input name="rev_name[<?= $i ?>]" value="<?= e($r['name']) ?>" placeholder="Dan H."></label>
                <label class="fld"><span>When</span><input name="rev_when[<?= $i ?>]" value="<?= e($r['when']) ?>" placeholder="2 weeks ago"></label>
                <label class="fld" style="grid-column:span 2"><span>What they said</span><textarea name="rev_text[<?= $i ?>]" rows="2"><?= e($r['text']) ?></textarea></label>
              </div>
            </div>
          <?php endfor; ?>
        </div>
      </div>

      <div class="form-actions">
        <button class="btn" type="submit">Save business details</button>
        <a class="btn btn--ghost" href="<?= BASE ?>index.php" target="_blank" rel="noopener">Open the website</a>
      </div>
    </form>

    <div class="panel" id="password">
      <div class="panel-head"><h3>Password</h3></div>
      <div class="panel-body">
        <form method="post">
          <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>">
          <input type="hidden" name="form" value="password">
          <div class="grid-4">
            <label class="fld"><span>Current password</span><input type="password" name="current" autocomplete="current-password"></label>
            <label class="fld"><span>New password</span><input type="password" name="new" autocomplete="new-password"></label>
            <label class="fld"><span>New password again</span><input type="password" name="repeat" autocomplete="new-password"></label>
          </div>
          <div class="form-actions" style="margin-top:18px"><button class="btn btn--dark" type="submit">Change password</button></div>
        </form>
      </div>
    </div>

  </div>
</section>

<?php admin_end();
