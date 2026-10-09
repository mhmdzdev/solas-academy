<?php
define('ROOT', dirname(__DIR__));
$GLOBALS['cfg'] = require ROOT . '/config.php';
if (is_file(ROOT . '/config.local.php')) {
    $GLOBALS['cfg'] = array_merge($GLOBALS['cfg'], (array)require ROOT . '/config.local.php');
}
date_default_timezone_set($GLOBALS['cfg']['timezone'] ?? 'UTC');

function cfg($k, $d = null) { return $GLOBALS['cfg'][$k] ?? $d; }

if (session_status() === PHP_SESSION_NONE) {
    session_set_cookie_params(['httponly' => true, 'samesite' => 'Lax', 'secure' => !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off']);
    session_start();
}

/* ---------- URLs ---------- */
function base_url() {
    static $b = null;
    if ($b !== null) return $b;
    $c = cfg('base_url', '');
    if ($c !== '') return $b = rtrim($c, '/');
    $doc = realpath($_SERVER['DOCUMENT_ROOT'] ?? '');
    $root = realpath(ROOT);
    $b = ($doc && $root && strpos($root, $doc) === 0) ? str_replace('\\', '/', substr($root, strlen($doc))) : '';
    return $b = rtrim($b, '/');
}
function url($p = '') { return base_url() . '/' . ltrim($p, '/'); }
function abs_url($p = '') {
    $https = !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off';
    return ($https ? 'https' : 'http') . '://' . ($_SERVER['HTTP_HOST'] ?? 'localhost') . url($p);
}
function asset($p) { $f = ROOT . '/' . ltrim($p, '/'); return url($p) . (is_file($f) ? '?v=' . filemtime($f) : ''); }
function redirect($p) {
    if (!preg_match('~^(https?:)?//~', $p) && ($p === '' || $p[0] !== '/')) $p = url($p);
    header('Location: ' . $p);
    exit;
}
function e($s) { return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); }

/* ---------- Database ---------- */
function db() {
    static $pdo = null;
    if ($pdo) return $pdo;
    if (cfg('db_driver') === 'mysql') {
        $m = cfg('mysql');
        $pdo = new PDO("mysql:host={$m['host']};dbname={$m['name']};charset={$m['charset']}", $m['user'], $m['pass']);
    } else {
        $path = cfg('sqlite_path');
        if (!is_dir(dirname($path))) mkdir(dirname($path), 0755, true);
        $pdo = new PDO('sqlite:' . $path);
    }
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
    return $pdo;
}
function q($sql, $p = []) { $st = db()->prepare($sql); $st->execute($p); return $st; }
function row($sql, $p = []) { $r = q($sql, $p)->fetch(); return $r ?: null; }
function rows($sql, $p = []) { return q($sql, $p)->fetchAll(); }
function val($sql, $p = []) { $r = q($sql, $p)->fetchColumn(); return $r === false ? null : $r; }
function insert($table, $data) {
    $cols = implode(',', array_keys($data));
    $ph = implode(',', array_fill(0, count($data), '?'));
    q("INSERT INTO $table ($cols) VALUES ($ph)", array_values($data));
    return (int)db()->lastInsertId();
}
function update($table, $data, $id) {
    $set = implode(',', array_map(function ($c) { return "$c=?"; }, array_keys($data)));
    q("UPDATE $table SET $set WHERE id=?", array_merge(array_values($data), [$id]));
}
function now() { return date('Y-m-d H:i:s'); }
function is_installed() {
    try { q('SELECT 1 FROM users LIMIT 1'); return true; } catch (Throwable $e) { return false; }
}

/* ---------- Settings ---------- */
function settings_all($reload = false) {
    static $cache = null;
    if ($cache === null || $reload) {
        $cache = [];
        try { foreach (rows('SELECT k, v FROM settings') as $r) $cache[$r['k']] = $r['v']; } catch (Throwable $e) {}
    }
    return $cache;
}
function setting($k, $d = '') {
    $c = settings_all();
    return isset($c[$k]) && $c[$k] !== '' ? $c[$k] : $d;
}
function set_setting($k, $v) {
    q('DELETE FROM settings WHERE k=?', [$k]);
    q('INSERT INTO settings (k, v) VALUES (?, ?)', [$k, $v]);
    settings_all(true);   // keep the in-request cache in sync
}

/* ---------- Flash / CSRF ---------- */
function flash($type, $msg) { $_SESSION['flash'][] = [$type, $msg]; }
function flashes() { $f = $_SESSION['flash'] ?? []; unset($_SESSION['flash']); return $f; }
function csrf_token() {
    if (empty($_SESSION['csrf'])) $_SESSION['csrf'] = bin2hex(random_bytes(32));
    return $_SESSION['csrf'];
}
function csrf_field() { return '<input type="hidden" name="_csrf" value="' . e(csrf_token()) . '">'; }
function csrf_check() {
    if (!hash_equals(csrf_token(), (string)($_POST['_csrf'] ?? $_GET['_csrf'] ?? ''))) {
        http_response_code(419);
        exit('Session expired. Please go back, refresh the page and try again.');
    }
}
function is_post() { return ($_SERVER['REQUEST_METHOD'] ?? '') === 'POST'; }

/* ---------- Auth ---------- */
function current_user() {
    static $u = false;
    if ($u !== false) return $u;
    $id = $_SESSION['uid'] ?? null;
    return $u = $id ? row('SELECT * FROM users WHERE id=?', [$id]) : null;
}
function login_user($u) { session_regenerate_id(true); $_SESSION['uid'] = (int)$u['id']; }
function require_login() {
    $u = current_user();
    if (!$u) redirect('login.php?next=' . urlencode($_SERVER['REQUEST_URI'] ?? ''));
    return $u;
}
/* Roles: owner (can manage managers + trainees), manager (content + trainees), student = trainee (view only) */
function is_owner() { $u = current_user(); return $u && $u['role'] === 'owner'; }
function is_staff() { $u = current_user(); return $u && in_array($u['role'], ['owner', 'manager'], true); }
function is_admin() { return is_staff(); }
function role_label($r) { return ['owner' => 'Owner', 'manager' => 'Manager', 'student' => 'Trainee'][$r] ?? $r; }
function deny_hidden() { http_response_code(404); exit('Not found'); }
// Staff area: owner or manager. Anyone else just sees "Not found" (the area is not advertised).
function require_admin() {
    $u = current_user();
    if (!$u || !is_staff()) deny_hidden();
    return $u;
}
function require_owner() {
    $u = require_admin();
    if ($u['role'] !== 'owner') { http_response_code(403); exit('Only the platform owner can do this.'); }
    return $u;
}

/* Secret login keys for owner / manager access links (never shown on the public site) */
function staff_key($role) {
    $over = cfg($role . '_login_key');
    if ($over) return (string)$over;
    $k = setting($role . '_key', '');
    if ($k === '') { $k = bin2hex(random_bytes(16)); set_setting($role . '_key', $k); }
    return $k;
}
function staff_link($role) { return abs_url('access.php?k=' . urlencode(staff_key($role))); }
function staff_role_for_key($key) {
    $key = (string)$key;
    if ($key === '') return null;
    foreach (['owner', 'manager'] as $r) if (hash_equals(staff_key($r), $key)) return $r;
    return null;
}

/* ---------- Uploads ---------- */
function save_image($file, $dir, $maxMb = 5) {
    if (!$file || ($file['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) return null;
    if ($file['error'] !== UPLOAD_ERR_OK) throw new RuntimeException('Image upload failed. Please try again.');
    if ($file['size'] > $maxMb * 1048576) throw new RuntimeException("Image is larger than {$maxMb}MB.");
    $info = @getimagesize($file['tmp_name']);
    $map = [IMAGETYPE_JPEG => 'jpg', IMAGETYPE_PNG => 'png', IMAGETYPE_WEBP => 'webp', IMAGETYPE_GIF => 'gif'];
    if (!$info || !isset($map[$info[2]])) throw new RuntimeException('Only JPG, PNG, WEBP or GIF images are allowed.');
    $target = ROOT . '/uploads/' . $dir;
    if (!is_dir($target)) mkdir($target, 0755, true);
    $name = bin2hex(random_bytes(8)) . '.' . $map[$info[2]];
    if (!move_uploaded_file($file['tmp_name'], $target . '/' . $name)) throw new RuntimeException('Could not save the image.');
    return 'uploads/' . $dir . '/' . $name;
}
function delete_upload($rel) {
    if ($rel && strpos($rel, 'uploads/') === 0 && strpos($rel, '..') === false) @unlink(ROOT . '/' . $rel);
}

function valid_gmc($v) { return (bool)preg_match('/^\d{7}$/', $v); }

/* ---------- Formatting ---------- */
function fmt_date($s, $fmt = 'M j, Y') { return $s ? date($fmt, strtotime($s)) : '—'; }
function validity_label($m) {
    $m = (int)$m;
    if ($m <= 0) return 'No expiry';
    if ($m % 12 === 0) { $y = $m / 12; return $y . ' year' . ($y > 1 ? 's' : ''); }
    return $m . ' month' . ($m > 1 ? 's' : '');
}

/* ---------- Lesson content: build "on this page" list from h2 only ---------- */
function lesson_with_toc($html) {
    $toc = [];
    if (trim($html) === '') return [$html, $toc];
    $prev = libxml_use_internal_errors(true);
    $dom = new DOMDocument();
    $dom->loadHTML('<?xml encoding="utf-8"?><div id="__root">' . $html . '</div>', LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);
    libxml_clear_errors();
    libxml_use_internal_errors($prev);
    $i = 0;
    foreach ($dom->getElementsByTagName('h2') as $h) {
        $i++;
        $id = 'sec-' . $i;
        $h->setAttribute('id', $id);
        $toc[] = ['id' => $id, 'text' => trim($h->textContent)];
    }
    $root = $dom->getElementById('__root');
    if (!$root) {
        foreach ($dom->getElementsByTagName('div') as $d) if ($d->getAttribute('id') === '__root') { $root = $d; break; }
    }
    $out = '';
    if ($root) foreach ($root->childNodes as $n) $out .= $dom->saveHTML($n);
    return [$out !== '' ? $out : $html, $toc];
}

/* ---------- Enrollments & certificates ---------- */
function latest_enrollment($uid, $cid) {
    return row('SELECT * FROM enrollments WHERE user_id=? AND course_id=? ORDER BY id DESC LIMIT 1', [$uid, $cid]);
}
function enrollment_progress($enr) {
    $total = (int)val('SELECT COUNT(*) FROM lessons WHERE course_id=?', [$enr['course_id']]);
    $done = (int)val('SELECT COUNT(*) FROM lesson_progress lp JOIN lessons l ON l.id=lp.lesson_id WHERE lp.enrollment_id=? AND l.course_id=?', [$enr['id'], $enr['course_id']]);
    return [$done, $total];
}
function cert_is_expired($c) { return !empty($c['expires_at']) && strtotime($c['expires_at'] . ' 23:59:59') < time(); }
function cert_url($serial) { return abs_url('certificate.php?serial=' . urlencode($serial)); }
function make_serial() {
    do { $s = 'PSY-' . date('Y') . '-' . strtoupper(bin2hex(random_bytes(4))); }
    while (val('SELECT COUNT(*) FROM certificates WHERE serial=?', [$s]));
    return $s;
}
function issue_certificate($enr) {
    $existing = row('SELECT * FROM certificates WHERE enrollment_id=?', [$enr['id']]);
    if ($existing) return $existing;
    $course = row('SELECT * FROM courses WHERE id=?', [$enr['course_id']]);
    $months = (int)($course['cert_validity_months'] ?? 0);
    $id = insert('certificates', [
        'serial' => make_serial(),
        'user_id' => $enr['user_id'],
        'course_id' => $enr['course_id'],
        'enrollment_id' => $enr['id'],
        'issued_at' => date('Y-m-d'),
        'expires_at' => $months > 0 ? date('Y-m-d', strtotime("+$months months")) : null,
    ]);
    return row('SELECT * FROM certificates WHERE id=?', [$id]);
}

/* ---------- Icons ---------- */
function icon($name, $size = 20) {
    $p = [
        'menu'  => '<path d="M4 6h16M4 12h16M4 18h16"/>',
        'close' => '<path d="M6 6l12 12M18 6L6 18"/>',
        'back'  => '<path d="M15 6l-6 6 6 6"/>',
        'check' => '<path d="M5 12.5l4.5 4.5L19 7.5"/>',
        'cal'   => '<rect x="4" y="5" width="16" height="15" rx="2"/><path d="M8 3v4M16 3v4M4 10h16"/>',
        'cert'  => '<rect x="3" y="4" width="18" height="13" rx="2"/><path d="M8 21l4-3 4 3v-4M8 9h8M8 12h5"/>',
        'book'  => '<path d="M4 5a2 2 0 012-2h13v16H6a2 2 0 00-2 2V5z"/><path d="M19 19v2H6"/>',
        'user'  => '<circle cx="12" cy="8" r="4"/><path d="M4 21a8 8 0 0116 0"/>',
        'users' => '<circle cx="9" cy="8" r="3.5"/><path d="M2.5 20a6.5 6.5 0 0113 0M16 4.5a3.5 3.5 0 010 7M18 14.5a6.5 6.5 0 013.5 5.5"/>',
        'cog'   => '<circle cx="12" cy="12" r="3"/><path d="M12 2v3M12 19v3M2 12h3M19 12h3M5 5l2 2M17 17l2 2M19 5l-2 2M7 17l-2 2"/>',
        'home'  => '<path d="M3 11l9-8 9 8M5 10v10h14V10"/>',
        'print' => '<path d="M6 9V3h12v6M6 18H4v-7h16v7h-2M7 14h10v7H7z"/>',
        'link'  => '<path d="M10 14a4 4 0 005.7 0l3-3a4 4 0 00-5.7-5.7l-1 1M14 10a4 4 0 00-5.7 0l-3 3a4 4 0 005.7 5.7l1-1"/>',
    ][$name] ?? '';
    return '<svg class="ico" width="' . $size . '" height="' . $size . '" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">' . $p . '</svg>';
}

/* ---------- Bootstrap check ---------- */
if (!defined('INSTALLING')) {
    try {
        if (!is_installed()) redirect('install.php');
        if ((int)val("SELECT COUNT(*) FROM users WHERE role='admin'") > 0) q("UPDATE users SET role='owner' WHERE role='admin'");
        try { q('SELECT gmc_number FROM users LIMIT 1'); }
        catch (Throwable $e) { db()->exec('ALTER TABLE users ADD COLUMN gmc_number VARCHAR(30) NULL'); }
    } catch (Throwable $e) {
        http_response_code(500);
        exit('Database connection error. Please check config.php.');
    }
}
