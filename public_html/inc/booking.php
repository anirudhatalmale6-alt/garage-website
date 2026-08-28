<?php
/* ------------------------------------------------------------------
   Online booking — free slots, availability and confirmation emails.

   Slots are worked out from the opening hours in the Stock Manager, so
   changing the hours changes the booking calendar automatically. There
   is no separate timetable to keep in step.

   Every booking is written to /data/bookings.json BEFORE any email is
   sent, and the admin diary reads from that file — so a booking is
   never lost even if the mail server is having a bad day.
------------------------------------------------------------------ */

function bookings_all(): array {
    return store_read('bookings', []);
}

function bookings_save(array $rows): bool {
    return store_write('bookings', array_values($rows));
}

function booking_ref(): string {
    /* short, readable on the phone, no confusable letters */
    $pool = 'ACDEFHJKMNPRTVWXY3479';
    $out  = '';
    for ($i = 0; $i < 5; $i++) $out .= $pool[random_int(0, strlen($pool) - 1)];
    return 'MB-' . $out;
}

function booking_services(array $s): array {
    $raw = trim((string)($s['booking_services'] ?? ''));
    if ($raw === '') return ['Service', 'General repair', 'Other'];
    $out = [];
    foreach (preg_split('/\R/', $raw) as $line) {
        $line = trim($line);
        if ($line !== '') $out[] = $line;
    }
    return $out ?: ['Service'];
}

/* one-off closed days: "2026-12-25" per line, blanks and junk ignored */
function booking_closed_dates(array $s): array {
    $out = [];
    foreach (preg_split('/[\s,]+/', (string)($s['closed_dates'] ?? '')) as $d) {
        $d = trim($d);
        if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $d)) $out[] = $d;
    }
    return $out;
}

function booking_enabled(array $s): bool {
    return !empty($s['booking_enabled']);
}

/* first and last date a customer is allowed to pick */
function booking_window(array $s): array {
    $lead  = max(0, (int)($s['lead_hours']  ?? 2));
    $ahead = max(1, (int)($s['days_ahead']  ?? 42));
    $first = date('Y-m-d', strtotime('+' . $lead . ' hours'));
    $last  = date('Y-m-d', strtotime('+' . $ahead . ' days'));
    return [$first, $last];
}

function mins_to_hhmm(int $m): string {
    return sprintf('%02d:%02d', intdiv($m, 60), $m % 60);
}

/* how many live bookings already sit on this date+time */
function booking_counts(string $ymd): array {
    $counts = [];
    foreach (bookings_all() as $b) {
        if (($b['date'] ?? '') !== $ymd) continue;
        if (($b['status'] ?? 'new') === 'cancelled') continue;
        $t = $b['time'] ?? '';
        $counts[$t] = ($counts[$t] ?? 0) + 1;
    }
    return $counts;
}

/**
 * Every slot on one day, with how many spaces are left in each.
 * Returns [] when the garage is shut that day.
 *
 * [['time' => '09:00', 'left' => 2, 'past' => false], …]
 */
function booking_slots(array $s, string $ymd): array {
    if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $ymd)) return [];
    if (in_array($ymd, booking_closed_dates($s), true)) return [];

    $ts  = strtotime($ymd . ' 00:00:00');
    if ($ts === false) return [];
    $idx = (int)date('N', $ts) - 1;                       // Monday = 0

    $day = ($s['hours'] ?? [])[$idx] ?? null;
    if (!$day || !empty($day['closed'])) return [];

    $open  = hhmm_to_mins((string)($day['open']  ?? ''));
    $close = hhmm_to_mins((string)($day['close'] ?? ''));
    if ($open === null || $close === null || $close <= $open) return [];

    $step = max(15, (int)($s['slot_minutes'] ?? 60));
    $cap  = max(1, (int)($s['slot_capacity'] ?? 2));

    $bFrom = hhmm_to_mins((string)($s['break_from'] ?? ''));
    $bTo   = hhmm_to_mins((string)($s['break_to']   ?? ''));

    $earliest = strtotime('+' . max(0, (int)($s['lead_hours'] ?? 2)) . ' hours');
    $counts   = booking_counts($ymd);

    $slots = [];
    for ($t = $open; $t + $step <= $close; $t += $step) {
        /* a slot that starts inside the lunch break, or would run into it */
        if ($bFrom !== null && $bTo !== null && $bTo > $bFrom
            && $t < $bTo && ($t + $step) > $bFrom) continue;

        $hhmm = mins_to_hhmm($t);
        $when = strtotime($ymd . ' ' . $hhmm);
        $slots[] = [
            'time' => $hhmm,
            'left' => max(0, $cap - (int)($counts[$hhmm] ?? 0)),
            'past' => $when < $earliest,
        ];
    }
    return $slots;
}

/* 'closed' | 'past' | 'full' | 'open' — used to colour the calendar */
function booking_day_state(array $s, string $ymd): string {
    [$first, $last] = booking_window($s);
    if ($ymd < date('Y-m-d')) return 'past';
    $slots = booking_slots($s, $ymd);
    if (!$slots) return 'closed';
    if ($ymd < $first || $ymd > $last) return 'past';

    $free = $stillToCome = 0;
    foreach ($slots as $sl) {
        if ($sl['past']) continue;
        $stillToCome++;
        if ($sl['left'] > 0) $free++;
    }
    if ($free > 0) return 'open';
    /* today after the last slot has gone by is not "fully booked" — it has
       simply run out of day. Saying "Full" would be a lie about the diary. */
    return $stillToCome > 0 ? 'full' : 'past';
}

function booking_free_count(array $s, string $ymd): int {
    $n = 0;
    foreach (booking_slots($s, $ymd) as $sl) if (!$sl['past'] && $sl['left'] > 0) $n++;
    return $n;
}

/* Is this exact slot still bookable? Checked again at save time, because
   two people can be filling the form in at the same moment. */
function booking_slot_free(array $s, string $ymd, string $time): bool {
    [$first, $last] = booking_window($s);
    if ($ymd < $first || $ymd > $last) return false;
    foreach (booking_slots($s, $ymd) as $sl) {
        if ($sl['time'] === $time) return !$sl['past'] && $sl['left'] > 0;
    }
    return false;
}

function booking_pretty_date(string $ymd): string {
    $ts = strtotime($ymd);
    return $ts ? date('l j F Y', $ts) : $ymd;
}

/* ================= email ================= */

function site_domain(): string {
    $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
    $host = preg_replace('/:\d+$/', '', $host);
    return preg_replace('/^www\./i', '', $host);
}

function booking_from_address(array $s): string {
    $from = trim((string)($s['email_from'] ?? ''));
    if ($from !== '' && filter_var($from, FILTER_VALIDATE_EMAIL)) return $from;
    return 'noreply@' . site_domain();
}

/**
 * Plain-text mail. Deliberately not HTML: a short plain message from the
 * domain's own address is far less likely to be filed as spam, and it
 * reads properly on every phone.
 */
function booking_mail(array $s, string $to, string $subject, string $body, string $replyTo = ''): bool {
    $to = trim($to);
    if ($to === '' || !filter_var($to, FILTER_VALIDATE_EMAIL)) return false;
    if (!function_exists('mail')) return false;

    $from = booking_from_address($s);
    $name = $s['business_name'] ?? 'Bookings';

    $headers = [
        'From: ' . booking_mime_name($name) . ' <' . $from . '>',
        'MIME-Version: 1.0',
        'Content-Type: text/plain; charset=UTF-8',
        'Content-Transfer-Encoding: 8bit',
        'X-Mailer: PHP/' . phpversion(),
    ];
    if ($replyTo !== '' && filter_var($replyTo, FILTER_VALIDATE_EMAIL)) {
        $headers[] = 'Reply-To: ' . $replyTo;
    }

    /* -f sets the envelope sender, which is what SPF is actually checked
       against. Without it shared hosts send as the cPanel user and the
       message fails SPF at Gmail. */
    return @mail(
        $to,
        booking_mime_name($subject),
        booking_wrap($body),
        implode("\r\n", $headers),
        '-f' . $from
    );
}

/* subjects and display names must be encoded if they are not plain ASCII */
function booking_mime_name(string $text): string {
    $text = str_replace(["\r", "\n"], ' ', $text);
    return preg_match('/[\x80-\xFF]/', $text)
        ? '=?UTF-8?B?' . base64_encode($text) . '?='
        : $text;
}

function booking_wrap(string $body): string {
    return str_replace(["\r\n", "\r"], "\n", $body);
}

function booking_customer_body(array $s, array $b): string {
    $lines = [
        'Hello ' . $b['name'] . ',',
        '',
        'Your booking with ' . $s['business_name'] . ' is in the diary.',
        '',
        '  Reference:  ' . $b['ref'],
        '  Date:       ' . booking_pretty_date($b['date']),
        '  Time:       ' . $b['time'],
        '  Work:       ' . $b['service'],
    ];
    if ($b['vehicle'] !== '') $lines[] = '  Vehicle:    ' . $b['vehicle'];
    if ($b['notes'] !== '')   $lines[] = '  Your notes: ' . $b['notes'];

    $lines = array_merge($lines, [
        '',
        'Where to find us:',
        '  ' . full_address($s),
        '',
        'Need to change or cancel it? Just ring ' . $s['phone']
            . ($s['mobile'] ? ' or ' . $s['mobile'] : '') . ' and quote ' . $b['ref'] . '.',
        '',
        'Please bring the vehicle keys and the locking wheel nut key if you have one.',
        '',
        'Thanks,',
        $s['business_name'],
        $s['phone'],
    ]);
    return implode("\n", $lines) . "\n";
}

function booking_garage_body(array $s, array $b): string {
    $lines = [
        'NEW BOOKING — ' . $b['ref'],
        str_repeat('-', 40),
        '',
        '  ' . strtoupper(booking_pretty_date($b['date'])) . ' at ' . $b['time'],
        '',
        '  Work:     ' . $b['service'],
        '  Name:     ' . $b['name'],
        '  Phone:    ' . $b['phone'],
        '  Email:    ' . ($b['email'] !== '' ? $b['email'] : '(not given)'),
        '  Vehicle:  ' . ($b['vehicle'] !== '' ? $b['vehicle'] : '(not given)'),
        '  Notes:    ' . ($b['notes'] !== '' ? $b['notes'] : '—'),
        '',
        'Booked online at ' . date('j M Y H:i', (int)$b['created']) . '.',
        'It is already in your diary here: https://' . site_domain() . '/admin/bookings.php',
    ];
    return implode("\n", $lines) . "\n";
}
