<?php
define('BASE', '../');
require __DIR__ . '/../inc/init.php';
require __DIR__ . '/../inc/parts.php';
require __DIR__ . '/_layout.php';

admin_require_login();
$s = $SETTINGS;

$id    = $_GET['id'] ?? '';
$idx   = null;
foreach ($STOCK as $i => $c) if ($c['id'] === $id) { $idx = $i; break; }
$isNew = $idx === null;

$car = $isNew ? [
    'id' => '', 'make' => '', 'model' => '', 'trim' => '', 'year' => date('Y'),
    'mileage' => '', 'engine' => '', 'gearbox' => 'Manual', 'fuel' => 'Petrol',
    'doors' => 5, 'colour' => '', 'mot' => '', 'history' => '', 'price' => '',
    'owners' => 1, 'reg' => '', 'photos' => [], 'sold' => false, 'featured' => false, 'blurb' => '',
] : $STOCK[$idx];

$errors = [];

/* ---------------------------------------------------------------
   Photo upload. Shrinks anything huge coming off a phone camera —
   a 4 MB photo straight from an iPhone makes the page crawl on 4G.
--------------------------------------------------------------- */
function save_upload(array $file, string $slug): ?string {
    if (($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) return null;
    if (!is_uploaded_file($file['tmp_name'])) return null;

    $info = @getimagesize($file['tmp_name']);
    if (!$info) return null;                                   // not an image at all

    $allowed = [IMAGETYPE_JPEG, IMAGETYPE_PNG, IMAGETYPE_WEBP];
    if (!in_array($info[2], $allowed, true)) return null;

    $name = $slug . '-' . bin2hex(random_bytes(4)) . '.jpg';
    $dest = UPLOAD_DIR . '/' . $name;

    /* GD is on every HostGator plan, but fall back to a plain copy
       if it ever is not, rather than losing the photo. */
    if (function_exists('imagecreatetruecolor')) {
        $src = match ($info[2]) {
            IMAGETYPE_JPEG => @imagecreatefromjpeg($file['tmp_name']),
            IMAGETYPE_PNG  => @imagecreatefrompng($file['tmp_name']),
            IMAGETYPE_WEBP => @imagecreatefromwebp($file['tmp_name']),
            default        => null,
        };
        if ($src) {
            $w = imagesx($src); $h = imagesy($src);
            $max = 1600;
            if ($w > $max) {
                $nh  = (int)round($h * $max / $w);
                $out = imagecreatetruecolor($max, $nh);
                imagefill($out, 0, 0, imagecolorallocate($out, 255, 255, 255));
                imagecopyresampled($out, $src, 0, 0, 0, 0, $max, $nh, $w, $h);
                imagedestroy($src);
                $src = $out;
            }
            imagejpeg($src, $dest, 82);
            imagedestroy($src);
            return 'uploads/' . $name;
        }
    }

    $ext  = image_type_to_extension($info[2], false);
    $name = $slug . '-' . bin2hex(random_bytes(4)) . '.' . $ext;
    return move_uploaded_file($file['tmp_name'], UPLOAD_DIR . '/' . $name) ? 'uploads/' . $name : null;
}

function make_slug(string $text, array $stock, string $keep = ''): string {
    $slug = strtolower(trim(preg_replace('/[^a-z0-9]+/i', '-', $text), '-'));
    if ($slug === '') $slug = 'vehicle';
    $taken = array_column($stock, 'id');
    $try = $slug; $n = 2;
    while (in_array($try, $taken, true) && $try !== $keep) $try = $slug . '-' . $n++;
    return $try;
}

/* ---------------- delete / reorder a single photo ---------------- */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['photo_action'] ?? '') !== '') {
    csrf_check();
    $p   = $_POST['photo'] ?? '';
    $pos = array_search($p, $car['photos'] ?? [], true);
    if ($pos !== false) {
        if ($_POST['photo_action'] === 'delete') {
            if (str_starts_with($p, 'uploads/')) @unlink(ROOT_DIR . '/' . $p);
            array_splice($car['photos'], $pos, 1);
        } elseif ($_POST['photo_action'] === 'main') {
            array_splice($car['photos'], $pos, 1);
            array_unshift($car['photos'], $p);
        }
        if (!$isNew) { $STOCK[$idx] = $car; store_write('stock', $STOCK); }
    }
    header('Location: vehicle.php?id=' . rawurlencode($car['id']));
    exit;
}

/* ---------------- save ---------------- */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['save'] ?? '') !== '') {
    csrf_check();

    $car['make']     = trim($_POST['make'] ?? '');
    $car['model']    = trim($_POST['model'] ?? '');
    $car['trim']     = trim($_POST['trim'] ?? '');
    $car['year']     = (int)($_POST['year'] ?? 0);
    $car['mileage']  = (int)preg_replace('/[^0-9]/', '', $_POST['mileage'] ?? '0');
    $car['engine']   = trim($_POST['engine'] ?? '');
    $car['gearbox']  = trim($_POST['gearbox'] ?? '');
    $car['fuel']     = trim($_POST['fuel'] ?? '');
    $car['doors']    = (int)($_POST['doors'] ?? 5);
    $car['colour']   = trim($_POST['colour'] ?? '');
    $car['mot']      = trim($_POST['mot'] ?? '');
    $car['history']  = trim($_POST['history'] ?? '');
    $car['price']    = (int)preg_replace('/[^0-9]/', '', $_POST['price'] ?? '0');
    $car['owners']   = (int)($_POST['owners'] ?? 1);
    $car['reg']      = strtoupper(trim($_POST['reg'] ?? ''));
    $car['blurb']    = trim($_POST['blurb'] ?? '');
    $car['sold']     = !empty($_POST['sold']);
    $car['featured'] = !empty($_POST['featured']);

    if ($car['make'] === '')  $errors[] = 'The make is missing (Ford, Vauxhall, BMW…).';
    if ($car['model'] === '') $errors[] = 'The model is missing (Fiesta, Corsa, 3 Series…).';
    if ($car['price'] <= 0)   $errors[] = 'Put a price on it — buyers skip past cars with no price.';

    if (!$errors) {
        if ($car['id'] === '') {
            $car['id'] = make_slug($car['year'] . '-' . $car['make'] . '-' . $car['model'], $STOCK);
        }

        /* photos */
        $files = $_FILES['photos'] ?? null;
        if ($files && is_array($files['name'])) {
            $count = count($files['name']);
            for ($i = 0; $i < $count; $i++) {
                if (count($car['photos']) >= 12) break;         // 12 is plenty for one car
                $one = [
                    'name' => $files['name'][$i], 'type' => $files['type'][$i],
                    'tmp_name' => $files['tmp_name'][$i], 'error' => $files['error'][$i],
                    'size' => $files['size'][$i],
                ];
                if ($one['error'] === UPLOAD_ERR_NO_FILE) continue;
                $saved = save_upload($one, $car['id']);
                if ($saved) $car['photos'][] = $saved;
                else $errors[] = 'Could not read "' . htmlspecialchars($one['name']) . '" — is it a JPG, PNG or WEBP?';
            }
        }

        if ($isNew) $STOCK[] = $car; else $STOCK[$idx] = $car;
        store_write('stock', $STOCK);

        flash_set('ok', car_title($car) . ' saved — it is on the website now.');
        header('Location: index.php');
        exit;
    }
}

admin_start($s, $isNew ? 'Add a vehicle' : 'Edit vehicle', 'vehicle');
$GEARBOX = ['Manual', 'Automatic', 'Semi-automatic'];
$FUELS   = ['Petrol', 'Diesel', 'Hybrid', 'Electric', 'Petrol Plug-in Hybrid'];
?>

<div class="pagehead">
  <div class="shell inner">
    <div class="crumbs"><a href="index.php">Stock manager</a> / <?= $isNew ? 'Add a vehicle' : e(car_title($car)) ?></div>
    <h1><?= $isNew ? 'Add a vehicle' : 'Edit vehicle' ?></h1>
    <p class="lede">Fill in what you know. Anything you leave blank simply does not show on the car's page.</p>
  </div>
</div>

<section>
  <div class="shell admin-wrap">
    <?php if ($errors): ?>
      <div class="flash bad"><?= implode('<br>', array_map('e', $errors)) ?></div>
    <?php endif; ?>

    <form method="post" enctype="multipart/form-data">
      <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>">
      <input type="hidden" name="save" value="1">

      <div class="panel">
        <div class="panel-head"><h3>The car</h3></div>
        <div class="panel-body">
          <div class="grid-4">
            <label class="fld"><span>Make *</span><input name="make" value="<?= e($car['make']) ?>" placeholder="Ford" required></label>
            <label class="fld"><span>Model *</span><input name="model" value="<?= e($car['model']) ?>" placeholder="Fiesta" required></label>
            <label class="fld" style="grid-column:span 2"><span>Trim / description</span><input name="trim" value="<?= e($car['trim']) ?>" placeholder="1.0 EcoBoost Zetec 5dr"></label>

            <label class="fld"><span>Year</span><input name="year" value="<?= e($car['year']) ?>" placeholder="2016" inputmode="numeric"></label>
            <label class="fld"><span>Mileage</span><input name="mileage" value="<?= e($car['mileage']) ?>" placeholder="62000" inputmode="numeric"></label>
            <label class="fld"><span>Price (£) *</span><input name="price" value="<?= e($car['price']) ?>" placeholder="5995" inputmode="numeric" required></label>
            <label class="fld"><span>Registration</span><input name="reg" value="<?= e($car['reg']) ?>" placeholder="AB16 CDE"></label>

            <label class="fld"><span>Engine size</span><input name="engine" value="<?= e($car['engine']) ?>" placeholder="998 cc"></label>
            <label class="fld"><span>Gearbox</span>
              <select name="gearbox"><?php foreach ($GEARBOX as $g): ?>
                <option<?= $car['gearbox'] === $g ? ' selected' : '' ?>><?= e($g) ?></option>
              <?php endforeach; ?></select>
            </label>
            <label class="fld"><span>Fuel type</span>
              <select name="fuel"><?php foreach ($FUELS as $f): ?>
                <option<?= $car['fuel'] === $f ? ' selected' : '' ?>><?= e($f) ?></option>
              <?php endforeach; ?></select>
            </label>
            <label class="fld"><span>Doors</span><input name="doors" value="<?= e($car['doors']) ?>" placeholder="5" inputmode="numeric"></label>

            <label class="fld"><span>Colour</span><input name="colour" value="<?= e($car['colour']) ?>" placeholder="Frozen White"></label>
            <label class="fld"><span>MOT expiry</span><input name="mot" value="<?= e($car['mot']) ?>" placeholder="14 March 2027"></label>
            <label class="fld"><span>Former keepers</span><input name="owners" value="<?= e($car['owners']) ?>" placeholder="2" inputmode="numeric"></label>
            <label class="fld"><span>Service history</span><input name="history" value="<?= e($car['history']) ?>" placeholder="Full service history — 6 stamps"></label>
          </div>

          <label class="fld" style="margin-top:18px">
            <span>Your notes on this car</span>
            <textarea name="blurb" rows="4" placeholder="What would you tell someone stood on the forecourt? New cambelt, four new tyres, one local owner…"><?= e($car['blurb']) ?></textarea>
          </label>

          <div class="switch-row">
            <label class="check"><input type="checkbox" name="sold" <?= !empty($car['sold']) ? 'checked' : '' ?>> <span>Mark as <b>SOLD</b></span></label>
            <label class="check"><input type="checkbox" name="featured" <?= !empty($car['featured']) ? 'checked' : '' ?>> <span>Show on the <b>home page</b></span></label>
          </div>
        </div>
      </div>

      <div class="panel">
        <div class="panel-head">
          <h3><?= empty($car['photos']) ? 'Photos' : 'Add more photos' ?></h3>
          <span class="hint">First photo is the one people see on the listings</span>
        </div>
        <div class="panel-body">
          <label class="fld">
            <span>Add photos (you can pick several at once)</span>
            <input type="file" name="photos[]" accept="image/*" multiple>
          </label>
          <p class="hint">Straight off your phone is fine — big photos get resized automatically so the page still loads quickly.</p>
        </div>
      </div>

      <div class="form-actions">
        <button class="btn" type="submit"><?= $isNew ? 'Add to the website' : 'Save changes' ?></button>
        <a class="btn btn--ghost" href="index.php">Cancel</a>
      </div>
    </form>

    <?php if (!$isNew && !empty($car['photos'])): ?>
      <div class="panel">
        <div class="panel-head"><h3>Manage photos</h3></div>
        <div class="panel-body">
          <div class="photo-grid">
            <?php foreach ($car['photos'] as $i => $p): ?>
              <figure class="ph">
                <img src="<?= e(BASE . $p) ?>" alt="">
                <?php if ($i === 0): ?><span class="tag">Main</span><?php endif; ?>
                <figcaption>
                  <?php if ($i > 0): ?>
                  <form method="post" class="inline">
                    <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>">
                    <input type="hidden" name="photo" value="<?= e($p) ?>">
                    <input type="hidden" name="photo_action" value="main">
                    <button class="btn btn--dark btn--sm" type="submit">Make main</button>
                  </form>
                  <?php endif; ?>
                  <form method="post" class="inline" onsubmit="return confirm('Delete this photo?')">
                    <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>">
                    <input type="hidden" name="photo" value="<?= e($p) ?>">
                    <input type="hidden" name="photo_action" value="delete">
                    <button class="btn btn--ghost btn--sm danger" type="submit">Delete</button>
                  </form>
                </figcaption>
              </figure>
            <?php endforeach; ?>
          </div>
        </div>
      </div>
    <?php endif; ?>

  </div>
</section>

<?php admin_end();
