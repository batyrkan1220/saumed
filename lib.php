<?php
// Ортақ функциялар. Бұл файлды тікелей ашуға болмайды.
if (!defined('APP')) { http_response_code(403); exit; }

ini_set('display_errors', '0');
date_default_timezone_set('UTC');

define('DATA', __DIR__ . '/data');
define('TZ', 5 * 3600);                 // Қазақстан уақыты (UTC+5)
define('GUARD', "<?php exit; ?>\n");    // деректер файлын браузерден ашқанда ештеңе көрінбейді

function ensure_data() {
    if (!is_dir(DATA)) @mkdir(DATA, 0755, true);
    $h = DATA . '/.htaccess';
    if (!file_exists($h)) {
        @file_put_contents($h, "<IfModule mod_authz_core.c>\nRequire all denied\n</IfModule>\n<IfModule !mod_authz_core.c>\nDeny from all\n</IfModule>\n");
    }
    if (!file_exists(DATA . '/index.html')) @file_put_contents(DATA . '/index.html', '');
}

function store_read($name) {
    $f = DATA . "/$name.php";
    if (!is_file($f)) return null;
    $s = @file_get_contents($f);
    if ($s === false) return null;
    $p = strpos($s, "\n");
    $d = json_decode($p === false ? '' : substr($s, $p + 1), true);
    return is_array($d) ? $d : null;
}

function store_write($name, $data) {
    ensure_data();
    $f = DATA . "/$name.php";
    $tmp = $f . '.' . mt_rand(100000, 999999) . '.tmp.php';
    $ok = @file_put_contents($tmp, GUARD . json_encode($data, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT), LOCK_EX);
    if ($ok === false) return false;
    return @rename($tmp, $f);
}

function cfg_default() {
    return [
        'password' => '',
        'message'  => 'Бандаж бойынша жазып тұрмын',
        'mode'     => 'rr',
        'fallback' => '77057636135',
        'managers' => [
            ['id' => 'm1', 'name' => '1-менеджер', 'phone' => '77057636135', 'active' => true],
        ],
    ];
}

function cfg() {
    $c = store_read('config');
    if (!$c) { $c = cfg_default(); store_write('config', $c); }
    return array_merge(cfg_default(), $c);
}

function cfg_save($c) { return store_write('config', $c); }

function log_event($e) {
    ensure_data();
    $f = DATA . '/ev-' . gmdate('Y-m', time() + TZ) . '.php';
    if (!file_exists($f)) {
        $h = @fopen($f, 'x');
        if ($h) { fwrite($h, GUARD); fclose($h); }
    }
    @file_put_contents($f, json_encode($e, JSON_UNESCAPED_UNICODE) . "\n", FILE_APPEND | LOCK_EX);
}

function load_events($from, $to) {
    $out = [];
    $files = glob(DATA . '/ev-*.php') ?: [];
    sort($files);
    $fm = gmdate('Y-m', $from + TZ);
    $tm = gmdate('Y-m', $to + TZ);
    foreach ($files as $f) {
        $ym = substr(basename($f), 3, 7);
        if ($ym < $fm || $ym > $tm) continue;
        $h = @fopen($f, 'r');
        if (!$h) continue;
        fgets($h); // қорғаныс жолын өткізіп жібереміз
        while (($line = fgets($h)) !== false) {
            $e = json_decode($line, true);
            if (!$e || !isset($e['t'])) continue;
            if ($e['t'] < $from || $e['t'] > $to) continue;
            $out[] = $e;
        }
        fclose($h);
    }
    return $out;
}

function first_event_ts() {
    $files = glob(DATA . '/ev-*.php') ?: [];
    sort($files);
    foreach ($files as $f) {
        $h = @fopen($f, 'r');
        if (!$h) continue;
        fgets($h);
        $line = fgets($h);
        fclose($h);
        $e = $line ? json_decode($line, true) : null;
        if ($e && isset($e['t'])) return (int)$e['t'];
    }
    return time();
}

// Кезекпен тарату санағышы (бір уақытта бірнеше адам басса да дұрыс жұмыс істейді)
function next_rr($n) {
    ensure_data();
    $h = @fopen(DATA . '/rr.lock', 'c+');
    if (!$h) return mt_rand(0, $n - 1);
    flock($h, LOCK_EX);
    $v = (int)stream_get_contents($h);
    ftruncate($h, 0);
    rewind($h);
    fwrite($h, (string)($v + 1));
    fflush($h);
    flock($h, LOCK_UN);
    fclose($h);
    return $v % $n;
}

function clean_id($s) { return substr(preg_replace('/[^a-zA-Z0-9]/', '', (string)$s), 0, 32); }
function clean_src($s) { return substr(preg_replace('/[^a-z0-9_.\-]/', '', strtolower((string)$s)), 0, 40); }

function ua() { return isset($_SERVER['HTTP_USER_AGENT']) ? $_SERVER['HTTP_USER_AGENT'] : ''; }

function is_bot() {
    return (bool)preg_match('/bot|crawl|spider|slurp|facebookexternalhit|preview|headless|curl|wget|python/i', ua());
}

function device() {
    $u = ua();
    if (preg_match('/iPhone|iPad|iPod/i', $u)) return 'iOS';
    if (preg_match('/Android/i', $u)) return 'Android';
    return 'Компьютер';
}

// utm_source жоқ болса, қай қосымшадан келгенін анықтауға тырысамыз
function guess_src() {
    $u = ua();
    if (preg_match('/musical_ly|Bytedance|TikTok/i', $u)) return 'tiktok';
    if (preg_match('/Instagram/i', $u)) return 'instagram';
    if (preg_match('/FBAN|FBAV|FB_IAB/i', $u)) return 'facebook';
    $r = isset($_SERVER['HTTP_REFERER']) ? parse_url($_SERVER['HTTP_REFERER'], PHP_URL_HOST) : '';
    $self = isset($_SERVER['HTTP_HOST']) ? $_SERVER['HTTP_HOST'] : '';
    if ($r && $r !== $self) {
        foreach (['tiktok', 'instagram', 'facebook', 'google', 'youtube', 'vk', 'yandex', 'telegram'] as $k) {
            if (stripos($r, $k) !== false) return $k;
        }
        return clean_src(preg_replace('/^www\./', '', $r));
    }
    return 'direct';
}

function norm_phone($p) {
    $d = preg_replace('/\D/', '', (string)$p);
    if (strlen($d) === 11 && $d[0] === '8') $d = '7' . substr($d, 1);
    if (strlen($d) === 10 && $d[0] === '7') $d = '7' . $d;
    return (strlen($d) >= 11 && strlen($d) <= 15) ? $d : '';
}

function nocache() {
    header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
    header('Pragma: no-cache');
}

function h($s) { return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); }
function ldate($fmt, $ts) { return gmdate($fmt, $ts + TZ); }
function day_start($ts) { return (int)(floor(($ts + TZ) / 86400) * 86400 - TZ); }
