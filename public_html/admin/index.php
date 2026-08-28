<?php
define('BASE', '../');
require __DIR__ . '/../inc/init.php';
require __DIR__ . '/../inc/parts.php';
require __DIR__ . '/_layout.php';

admin_session_start();
$s = $SETTINGS;

/* ---------------- sign out ---------------- */
if (isset($_GET['logout'])) {
    $_SESSION = [];
    session_destroy();
    header('Location: index.php');
    exit;
}

/* ---------------- sign in ---------------- */
$error = '';
if (!admin_logged_in()) {
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['password'])) {
        $wait = ($_SESSION['tries'] ?? 0) >= 5;
        if ($wait) {
            $error = 'Too many attempts. Wait a minute and try again.';
            if (time() - ($_SESSION['try_at'] ?? 0) > 60) { $_SESSION['tries'] = 0; $error = ''; }
        }
        if (!$error) {
            if (password_verify($_POST['password'], $s['password_hash'] ?? '')) {
                session_regenerate_id(true);
                $_SESSION['admin'] = true;
                $_SESSION['tries'] = 0;
                header('Location: index.php');
                exit;
            }
            $_SESSION['tries']  = ($_SESSION['tries'] ?? 0) + 1;
            $_SESSION['try_at'] = time();
            $error = 'That password is not right.';
        }
    }

    page_head($s, 'Stock Manager — ' . $s['business_name']);
    ?>
    <div class="login-wrap">
      <div class="login">
        <?= site_logo($s, 'Stock Manager') ?>
        <h1>Stock Manager</h1>
        <p class="lede">Sign in to add cars, mark them sold or change your business details.</p>
        <?php if ($error): ?><div class="flash bad"><?= e($error) ?></div><?php endif; ?>
        <form method="post">
          <label class="fld">
            <span>Password</span>
            <input type="password" name="password" autofocus required autocomplete="current-password">
          </label>
          <button class="btn btn--block" type="submit">Sign in</button>
        </form>
        <p class="hint" style="margin-top:22px"><a href="<?= BASE ?>index.php">← Back to the website</a></p>
      </div>
    </div>
    </body></html>
    <?php
    exit;
}

/* ---------------- actions ---------------- */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $id     = $_POST['id'] ?? '';
    $action = $_POST['action'] ?? '';
    $idx    = null;
    foreach ($STOCK as $i => $c) if ($c['id'] === $id) { $idx = $i; break; }

    if ($idx !== null) {
        $car = $STOCK[$idx];
        switch ($action) {
            case 'sold':
                $STOCK[$idx]['sold'] = empty($car['sold']);
                store_write('stock', $STOCK);
                flash_set('ok', car_title($car) . ' is now marked ' . (empty($car['sold']) ? 'SOLD' : 'for sale') . ' on the website.');
                break;

            case 'featured':
                $STOCK[$idx]['featured'] = empty($car['featured']);
                store_write('stock', $STOCK);
                flash_set('ok', car_title($car) . (empty($car['featured']) ? ' now shows on the home page.' : ' no longer shows on the home page.'));
                break;

            case 'up':
            case 'down':
                $swap = $action === 'up' ? $idx - 1 : $idx + 1;
                if (isset($STOCK[$swap])) {
                    [$STOCK[$idx], $STOCK[$swap]] = [$STOCK[$swap], $STOCK[$idx]];
                    store_write('stock', $STOCK);
                }
                break;

            case 'delete':
                /* delete the vehicle's uploaded photos too, so the server
                   does not slowly fill up with pictures of cars that sold
                   two years ago. Photos that ship with the site are left. */
                foreach ($car['photos'] ?? [] as $p) {
                    if (str_starts_with($p, 'uploads/')) @unlink(ROOT_DIR . '/' . $p);
                }
                array_splice($STOCK, $idx, 1);
                store_write('stock', $STOCK);
                flash_set('ok', car_title($car) . ' has been removed from the website.');
                break;
        }
    }
    header('Location: index.php');
    exit;
}

/* ---------------- page ---------------- */
$avail = count(stock_available($STOCK));
$sold  = count($STOCK) - $avail;

admin_start($s, 'Stock Manager', 'stock');
?>

<div class="pagehead">
  <div class="shell inner">
    <h1>Vehicles on the forecourt</h1>
    <p class="lede">Everything here is live. Flick a switch and the website changes for the next person who visits it — no waiting, nothing to publish.</p>
  </div>
</div>

<section>
  <div class="shell admin-wrap">
    <?php flash_out(); ?>

    <?php if (!empty($s['password_is_default'])): ?>
      <div class="flash bad">
        You are still using the password I set up. Change it on the
        <a href="settings.php#password" style="text-decoration:underline">Business details</a> page — anyone who guesses it can edit your website.
      </div>
    <?php endif; ?>

    <div class="panel">
      <div class="panel-head">
        <h3>Your stock</h3>
        <span class="hint"><?= count($STOCK) ?> vehicles · <?= $avail ?> for sale · <?= $sold ?> sold</span>
        <a class="btn btn--sm" href="vehicle.php">+ Add a vehicle</a>
      </div>

      <?php if (!$STOCK): ?>
        <div style="padding:30px 24px">
          <p class="lede" style="margin:0">No vehicles yet. Hit <b>Add a vehicle</b> and the first car will be on the website a minute later.</p>
        </div>
      <?php else: ?>
      <table class="stock-table">
        <thead>
          <tr>
            <th>Vehicle</th>
            <th>Price</th>
            <th>MOT</th>
            <th>Status</th>
            <th>Home page</th>
            <th style="text-align:right">Mark as sold</th>
            <th style="text-align:right">Actions</th>
          </tr>
        </thead>
        <tbody>
        <?php foreach ($STOCK as $i => $c): ?>
          <tr class="<?= !empty($c['sold']) ? 'is-sold' : '' ?>">
            <td>
              <span class="veh">
                <img src="<?= e(car_photo($c)) ?>" alt="">
                <span>
                  <b><?= e(car_title($c)) ?></b>
                  <span><?= e($c['trim']) ?> · <?= miles($c['mileage']) ?> · <?= e($c['reg']) ?></span>
                </span>
              </span>
            </td>
            <td class="num"><?= money($c['price']) ?></td>
            <td><?= e($c['mot']) ?></td>
            <td><span class="pill <?= !empty($c['sold']) ? 'gone' : 'live' ?>"><?= !empty($c['sold']) ? 'Sold' : 'For sale' ?></span></td>
            <td>
              <form method="post" class="inline">
                <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>">
                <input type="hidden" name="id" value="<?= e($c['id']) ?>">
                <input type="hidden" name="action" value="featured">
                <button class="star<?= !empty($c['featured']) ? ' on' : '' ?>" type="submit" title="Show this car on the home page">
                  <svg viewBox="0 0 24 24"><path d="m12 2 3 6.5 7 .9-5 4.8 1.2 7L12 17.8 5.8 21.2 7 14.2 2 9.4l7-.9L12 2Z"/></svg>
                </button>
              </form>
            </td>
            <td style="text-align:right">
              <form method="post" class="inline">
                <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>">
                <input type="hidden" name="id" value="<?= e($c['id']) ?>">
                <input type="hidden" name="action" value="sold">
                <button class="toggle" type="submit" role="switch" aria-pressed="<?= !empty($c['sold']) ? 'true' : 'false' ?>" aria-label="Mark as sold"></button>
              </form>
            </td>
            <td>
              <span class="row-acts">
                <form method="post" class="inline">
                  <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>">
                  <input type="hidden" name="id" value="<?= e($c['id']) ?>">
                  <input type="hidden" name="action" value="up">
                  <button class="btn btn--dark btn--sm" type="submit" <?= $i === 0 ? 'disabled' : '' ?> title="Move up">↑</button>
                </form>
                <form method="post" class="inline">
                  <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>">
                  <input type="hidden" name="id" value="<?= e($c['id']) ?>">
                  <input type="hidden" name="action" value="down">
                  <button class="btn btn--dark btn--sm" type="submit" <?= $i === count($STOCK) - 1 ? 'disabled' : '' ?> title="Move down">↓</button>
                </form>
                <a class="btn btn--dark btn--sm" href="vehicle.php?id=<?= e($c['id']) ?>">Edit</a>
                <a class="btn btn--dark btn--sm" href="<?= e(car_url($c)) ?>" target="_blank" rel="noopener">View</a>
                <form method="post" class="inline" onsubmit="return confirm('Remove the <?= e(car_title($c)) ?> from the website completely?\n\nIf it has just sold, use the Mark as sold switch instead — it stays on the site with a SOLD stamp, which brings in enquiries for the next one.')">
                  <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>">
                  <input type="hidden" name="id" value="<?= e($c['id']) ?>">
                  <input type="hidden" name="action" value="delete">
                  <button class="btn btn--ghost btn--sm danger" type="submit">Remove</button>
                </form>
              </span>
            </td>
          </tr>
        <?php endforeach; ?>
        </tbody>
      </table>
      <?php endif; ?>
    </div>

    <div class="notice">
      <b>Sold or removed?</b> The switch stamps the car SOLD — it greys out on the website, drops off the home page and stops counting as available, but people can still see it (which is what brings in "have you got another one like that?" calls). Remove deletes it from the site for good.
    </div>
  </div>
</section>

<?php admin_end();
