<?php
/* ------------------------------------------------------------------
   Bootstrap — included by every page.

   Pages in the site root include it as:      require __DIR__ . '/inc/init.php';
   Pages inside /admin set BASE first:        define('BASE', '../');
------------------------------------------------------------------ */

if (!defined('BASE')) define('BASE', '');           // path back to the site root
define('ROOT_DIR',   dirname(__DIR__));
define('DATA_DIR',   ROOT_DIR . '/data');
define('UPLOAD_DIR', ROOT_DIR . '/uploads');

date_default_timezone_set('Europe/London');
mb_internal_encoding('UTF-8');

require_once __DIR__ . '/store.php';
require_once __DIR__ . '/defaults.php';
require_once __DIR__ . '/booking.php';

/* ---------- first run: create the data files ---------- */
if (!is_dir(DATA_DIR))   @mkdir(DATA_DIR, 0755, true);
if (!is_dir(UPLOAD_DIR)) @mkdir(UPLOAD_DIR, 0755, true);

if (!is_file(store_path('settings'))) {
    $seed = default_settings();
    $seed['password_hash'] = password_hash('changeme', PASSWORD_DEFAULT);
    store_write('settings', $seed);
}
if (!is_file(store_path('stock'))) {
    store_write('stock', default_stock());
}
if (!is_file(store_path('bookings'))) {
    store_write('bookings', []);
}

/* ---------- load ---------- */
$SETTINGS = array_merge(default_settings(), store_read('settings'));
$STOCK    = store_read('stock', []);

/* ================= helpers ================= */

function e($s): string {
    return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8');
}

function money($n): string {
    return '£' . number_format((float)$n, 0, '.', ',');
}

function miles($n): string {
    return number_format((float)$n, 0, '.', ',') . ' miles';
}

/* tel: links must have no spaces in them or some phones mis-dial */
function tel_href(string $num): string {
    return 'tel:' . preg_replace('/[^0-9+]/', '', $num);
}

/* WhatsApp deep link, optionally with the message already typed in */
function wa_link(array $s, string $text = ''): string {
    $num = preg_replace('/[^0-9]/', '', $s['whatsapp'] ?? '');
    $url = 'https://wa.me/' . $num;
    if ($text !== '') $url .= '?text=' . rawurlencode($text);
    return $url;
}

/* wa.me only accepts full international numbers. A customer types
   07746 533000; WhatsApp needs 447746533000 or the link goes nowhere. */
function intl_uk(string $num): string {
    $d = preg_replace('/[^0-9]/', '', $num);
    if (str_starts_with($d, '00')) return substr($d, 2);
    if (str_starts_with($d, '44')) return $d;
    if (str_starts_with($d, '0'))  return '44' . ltrim($d, '0');
    return $d;
}

function full_address(array $s): string {
    return trim(implode(', ', array_filter([
        $s['address_line1'] ?? '', $s['address_line2'] ?? '', $s['postcode'] ?? '',
    ])));
}

function maps_embed(array $s): string {
    return 'https://www.google.com/maps?q=' . rawurlencode(full_address($s)) . '&output=embed';
}

function maps_directions(array $s): string {
    return 'https://www.google.com/maps/dir/?api=1&destination=' . rawurlencode(full_address($s));
}

/* Is the garage open right now? Returns [bool, 'HH:MM' closing time] */
function open_state(array $s): array {
    $hours = $s['hours'] ?? [];
    $idx   = (int)date('N') - 1;                    // Monday = 0
    $today = $hours[$idx] ?? null;
    if (!$today || !empty($today['closed'])) return [false, ''];

    $now  = (int)date('H') * 60 + (int)date('i');
    $open = hhmm_to_mins($today['open'] ?? '');
    $shut = hhmm_to_mins($today['close'] ?? '');
    if ($open === null || $shut === null) return [false, ''];

    return [$now >= $open && $now < $shut, $today['close']];
}

function hhmm_to_mins(string $t): ?int {
    if (!preg_match('/^(\d{1,2}):(\d{2})$/', trim($t), $m)) return null;
    return (int)$m[1] * 60 + (int)$m[2];
}

function hours_summary(array $s): string {
    $out = [];
    foreach (($s['hours'] ?? []) as $h) {
        $out[] = mb_substr($h['day'], 0, 3) . ' ' .
                 (!empty($h['closed']) ? 'Closed' : $h['open'] . '–' . $h['close']);
    }
    /* collapse runs of identical days: "Mon–Fri 08:00–18:00 · Sat 08:30–13:00" */
    $parts = []; $i = 0; $n = count($out);
    while ($i < $n) {
        $time = substr($out[$i], 4);
        $j = $i;
        while ($j + 1 < $n && substr($out[$j + 1], 4) === $time) $j++;
        $days = ($j > $i) ? substr($out[$i], 0, 3) . '–' . substr($out[$j], 0, 3) : substr($out[$i], 0, 3);
        if ($time !== 'Closed') $parts[] = $days . ' ' . $time;
        $i = $j + 1;
    }
    return implode(' · ', $parts);
}

/* first photo of a vehicle, with a fallback so a car with no photo
   still renders a card instead of a broken image */
function car_photo(array $c, int $i = 0): string {
    $p = $c['photos'][$i] ?? '';
    return $p !== '' ? BASE . $p : BASE . 'assets/img/placeholder.svg';
}

function car_title(array $c): string {
    return trim($c['year'] . ' ' . $c['make'] . ' ' . $c['model']);
}

function car_url(array $c): string {
    return BASE . 'car.php?id=' . rawurlencode($c['id']);
}

/* The reviews section hides itself until there is something real to put
   in it — an empty "what our customers say" band looks broken. */
function has_reviews(array $s): bool {
    return !empty($s['reviews']) || !empty($s['google_rating']);
}

function stock_available(array $stock): array {
    return array_values(array_filter($stock, fn($c) => empty($c['sold'])));
}

/* ================= admin session ================= */

function admin_session_start(): void {
    if (session_status() === PHP_SESSION_NONE) {
        session_name('ngstock');
        session_start();
    }
}

function admin_logged_in(): bool {
    admin_session_start();
    return !empty($_SESSION['admin']);
}

function admin_require_login(): void {
    if (!admin_logged_in()) {
        header('Location: index.php?next=' . rawurlencode(basename($_SERVER['PHP_SELF'])));
        exit;
    }
}

function csrf_token(): string {
    admin_session_start();
    if (empty($_SESSION['csrf'])) $_SESSION['csrf'] = bin2hex(random_bytes(16));
    return $_SESSION['csrf'];
}

function csrf_check(): void {
    admin_session_start();
    $sent = $_POST['csrf'] ?? '';
    if (!hash_equals($_SESSION['csrf'] ?? '', $sent)) {
        http_response_code(400);
        exit('Session expired — go back, reload the page and try again.');
    }
}
