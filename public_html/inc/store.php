<?php
/* ------------------------------------------------------------------
   Tiny JSON store.

   Everything the site shows — the vehicles and the business details —
   lives in two JSON files inside /data. No database to set up, no
   MySQL password to lose, and a backup is just a copy of the folder.
------------------------------------------------------------------ */

function store_path(string $name): string {
    return DATA_DIR . '/' . $name . '.json';
}

function store_read(string $name, array $fallback = []): array {
    $file = store_path($name);
    if (!is_file($file)) return $fallback;
    $raw = file_get_contents($file);
    if ($raw === false || $raw === '') return $fallback;
    $data = json_decode($raw, true);
    return is_array($data) ? $data : $fallback;
}

/* Write via a temp file + rename so a half-written file can never be
   served, and lock so two browser tabs cannot clobber each other. */
function store_write(string $name, array $data): bool {
    $file = store_path($name);
    if (!is_dir(DATA_DIR)) @mkdir(DATA_DIR, 0755, true);

    $json = json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    if ($json === false) return false;

    $lock = fopen($file . '.lock', 'c');
    if ($lock) flock($lock, LOCK_EX);

    $tmp = $file . '.' . getmypid() . '.tmp';
    $ok  = file_put_contents($tmp, $json) !== false && rename($tmp, $file);
    if (!$ok) @unlink($tmp);

    if ($lock) { flock($lock, LOCK_UN); fclose($lock); }
    return $ok;
}
