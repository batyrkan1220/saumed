<?php
// Ортақ функциялар. Бұл файлды тікелей ашуға болмайды.
if (!defined('APP')) { http_response_code(403); exit; }

ini_set('display_errors', '0');
date_default_timezone_set('UTC');

define('ROOT', __DIR__);
define('DATA', __DIR__ . '/data');
define('TZ', 5 * 3600);                 // Қазақстан уақыты (UTC+5)
define('GUARD', "<?php exit; ?>\n");    // деректер файлын браузерден ашқанда ештеңе көрінбейді
define('RESERVED', 'data,img,up,admin,go,track,lib,index,px,api,www,mail,cgi-bin');

/* ================= Сақтау ================= */
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

/* ================= Проекттер ================= */
function project_default($id = 'p1', $slug = '', $name = '') {
    return [
        'id'       => $id,
        'slug'     => $slug,
        'name'     => $name,
        'active'   => true,
        // Сайт мәтіні
        'top'      => '',
        'title'    => 'БАҒАСЫН БІЛІП ТОЛЫҚ АҚПАРАТ АЛУ ҮШІН',
        'title_hl' => 'БАТЫРМАНЫ БАСЫҢЫЗ',
        'button'   => 'WHATSAPP-ҚА ЖАЗУ',
        'hint'     => 'Бір басыңыз — WhatsApp ашылады',
        'message'  => 'Сәлеметсіз бе! Бағасын білгім келеді',
        // Фото
        'img'      => '',
        'img_webp' => '',
        'img_w'    => 0,
        'img_h'    => 0,
        // Тарату
        'mode'     => 'rr',
        'managers' => [],
        // Пиксельдер
        'tt_ids'   => '',
        'tt_event' => 'ClickButton',
        'fb_ids'   => '',
        'fb_event' => 'Lead',
    ];
}

function cfg() {
    $c = store_read('config');
    if (!$c) $c = [];
    if (!isset($c['projects'])) {
        // Ескі (бір проектілі) баптауларды Saumed проектіне көшіреміз
        $p = project_default('p1', 'bel', 'Saumed — бандаж');
        $p['top']      = 'БЕЛ ЖӘНЕ ОМЫРТҚА АУРУЛАРЫН ЕМДЕЙДІ';
        $p['message']  = isset($c['message']) ? $c['message'] : 'Бандаж бойынша жазып тұрмын';
        $p['mode']     = isset($c['mode']) ? $c['mode'] : 'rr';
        $p['managers'] = isset($c['managers']) ? $c['managers'] : [['id' => 'm1', 'name' => '1-менеджер', 'phone' => '77057636135', 'active' => true]];
        $p['tt_ids']   = isset($c['tt_ids']) ? $c['tt_ids'] : 'DBCM57BC77U4J2JQ97R0';
        $p['tt_event'] = isset($c['tt_event']) ? $c['tt_event'] : 'ClickButton';
        $p['fb_ids']   = isset($c['fb_ids']) ? $c['fb_ids'] : '';
        $p['fb_event'] = isset($c['fb_event']) ? $c['fb_event'] : 'Lead';
        $p['img'] = 'img/bandaj.jpg'; $p['img_webp'] = 'img/bandaj.webp'; $p['img_w'] = 540; $p['img_h'] = 560;
        $c = ['password' => isset($c['password']) ? $c['password'] : '', 'projects' => [$p]];
        store_write('config', $c);
    }
    if (!isset($c['password'])) $c['password'] = '';
    if (!isset($c['wz_key'])) $c['wz_key'] = '';
    if (!isset($c['wz_channels'])) $c['wz_channels'] = [];
    if (!isset($c['spend_map'])) $c['spend_map'] = [];
    if (empty($c['wz_secret']) || empty($c['spend_secret'])) {
        if (empty($c['wz_secret'])) $c['wz_secret'] = bin2hex(random_bytes(12));
        if (empty($c['spend_secret'])) $c['spend_secret'] = bin2hex(random_bytes(12));
        store_write('config', $c);
    }
    foreach ($c['projects'] as $i => $p) $c['projects'][$i] = array_merge(project_default(), $p);
    return $c;
}

function cfg_save($c) { return store_write('config', $c); }

function proj_index($c, $key, $val) {
    foreach ($c['projects'] as $i => $p) if ($p[$key] === $val) return $i;
    return -1;
}

function valid_slug($s) {
    return preg_match('/^[a-z0-9][a-z0-9-]{1,29}$/', $s) && !in_array($s, explode(',', RESERVED), true);
}

// Проекттің папкасы: /slug/index.php — сайтты көрсетеді
function ensure_stub($slug) {
    if (!valid_slug($slug)) return false;
    $d = ROOT . '/' . $slug;
    if (!is_dir($d)) @mkdir($d, 0755);
    $f = $d . '/index.php';
    $code = "<?php\n// Проект сайты. Мәтіндер админ беттен өзгертіледі.\ndefine('APP', 1);\nrequire dirname(__DIR__) . '/lib.php';\nrender_landing(basename(__DIR__));\n";
    if (!file_exists($f) || file_get_contents($f) !== $code) @file_put_contents($f, $code);
    // Ескі статикалық бет қалып қойса — өшіреміз (index.html index.php-дан бұрын ашылады)
    if (file_exists($d . '/index.html') && file_exists($f)) @unlink($d . '/index.html');
    return file_exists($f);
}

function ensure_all_stubs($c) {
    foreach ($c['projects'] as $p) ensure_stub($p['slug']);
}

/* ================= Оқиғалар (статистика) ================= */
function log_event($e) {
    ensure_data();
    $f = DATA . '/ev-' . gmdate('Y-m', time() + TZ) . '.php';
    if (!file_exists($f)) {
        $h = @fopen($f, 'x');
        if ($h) { fwrite($h, GUARD); fclose($h); }
    }
    @file_put_contents($f, json_encode($e, JSON_UNESCAPED_UNICODE) . "\n", FILE_APPEND | LOCK_EX);
}

function ev_pid($e) { return !empty($e['p']) ? $e['p'] : 'p1'; }  // ескі оқиғалар — Saumed

function load_events($from, $to, $pid = null) {
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
        fgets($h);
        while (($line = fgets($h)) !== false) {
            $e = json_decode($line, true);
            if (!$e || !isset($e['t'])) continue;
            if ($e['t'] < $from || $e['t'] > $to) continue;
            if ($pid !== null && ev_pid($e) !== $pid) continue;
            $out[] = $e;
        }
        fclose($h);
    }
    return $out;
}

function first_event_ts($pid = null) {
    $files = glob(DATA . '/ev-*.php') ?: [];
    sort($files);
    foreach ($files as $f) {
        $h = @fopen($f, 'r');
        if (!$h) continue;
        fgets($h);
        while (($line = fgets($h)) !== false) {
            $e = json_decode($line, true);
            if ($e && isset($e['t']) && ($pid === null || ev_pid($e) === $pid)) { fclose($h); return (int)$e['t']; }
        }
        fclose($h);
    }
    return time();
}

// Проект статистикасын тазалау (басқа проекттердікі қалады)
function purge_events($pid) {
    foreach (glob(DATA . '/ev-*.php') ?: [] as $f) {
        $lines = @file($f);
        if (!$lines) continue;
        $keep = [GUARD];
        foreach (array_slice($lines, 1) as $line) {
            $e = json_decode($line, true);
            if ($e && ev_pid($e) !== $pid) $keep[] = $line;
        }
        @file_put_contents($f, implode('', $keep), LOCK_EX);
    }
}

// Кезекпен тарату санағышы — әр проектке бөлек
function next_rr($pid, $n) {
    ensure_data();
    $h = @fopen(DATA . '/rr-' . preg_replace('/[^a-z0-9]/', '', $pid) . '.lock', 'c+');
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

/* ================= Көмекші ================= */
function clean_id($s) { return substr(preg_replace('/[^a-zA-Z0-9]/', '', (string)$s), 0, 32); }
function clean_cid($s) { return substr(preg_replace('/\D/', '', (string)$s), 0, 25); }
function clean_cname($s) { return mb_substr(trim(preg_replace('/[\x00-\x1F<>"]/u', '', (string)$s)), 0, 100); }
function clean_src($s) { return substr(preg_replace('/[^a-z0-9_.\-]/', '', strtolower((string)$s)), 0, 40); }
function ua() { return isset($_SERVER['HTTP_USER_AGENT']) ? $_SERVER['HTTP_USER_AGENT'] : ''; }

// Админге кірген браузер (сіз өзіңіз тексеріп жүрсіз) — статистикаға жазылмайды
function is_admin_visitor() { return !empty($_COOKIE['adm']) || !empty($_COOKIE['adm_seen']); }

// Жарнамадан келді ме: TikTok (ttclid), Meta (fbclid), Google (gclid), Яндекс (yclid) немесе UTM белгісі
function is_ad_event($e) {
    if (isset($e['a'])) return (int)$e['a'] === 1;
    return !empty($e['s']) && $e['s'] !== 'direct';   // ескі жазбалар
}

function is_bot() {
    $u = ua();
    if ($u === '' || strlen($u) < 20) return true;
    return (bool)preg_match('/bot|crawl|spider|slurp|externalhit|externalagent|preview|headless|lighthouse|pagespeed|curl|wget|python|java\/|okhttp|go-http|axios|node-fetch|monitor|scan|check/i', $u);
}

function device() {
    $u = ua();
    if (preg_match('/iPhone|iPad|iPod/i', $u)) return 'iOS';
    if (preg_match('/Android/i', $u)) return 'Android';
    return 'Компьютер';
}

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

/* ================= Пиксельдер ================= */
function px_list($s, $re) {
    $out = [];
    foreach (preg_split('/[\s,;]+/', (string)$s) as $id) {
        if ($id !== '' && preg_match($re, $id)) $out[] = $id;
    }
    return array_values(array_unique($out));
}

function px_js($p) {
    $tt = px_list($p['tt_ids'], '/^[A-Z0-9]{8,30}$/');
    $fb = px_list($p['fb_ids'], '/^\d{8,20}$/');
    $te = preg_replace('/[^A-Za-z]/', '', $p['tt_event']) ?: 'ClickButton';
    $fe = preg_replace('/[^A-Za-z]/', '', $p['fb_event']) ?: 'Lead';
    $js = '(function(w,d){var TT=' . json_encode($tt) . ',TE=' . json_encode($te) . ',FB=' . json_encode($fb) . ',FE=' . json_encode($fe) . ";\n";
    if ($tt) {
        $js .= <<<'JS'
!function(w,d,t){w.TiktokAnalyticsObject=t;var ttq=w[t]=w[t]||[];ttq.methods=["page","track","identify","instances","debug","on","off","once","ready","alias","group","enableCookie","disableCookie","holdConsent","revokeConsent","grantConsent"],ttq.setAndDefer=function(t,e){t[e]=function(){t.push([e].concat(Array.prototype.slice.call(arguments,0)))}};for(var i=0;i<ttq.methods.length;i++)ttq.setAndDefer(ttq,ttq.methods[i]);ttq.instance=function(t){for(var e=ttq._i[t]||[],n=0;n<ttq.methods.length;n++)ttq.setAndDefer(e,ttq.methods[n]);return e},ttq.load=function(e,n){var r="https://analytics.tiktok.com/i18n/pixel/events.js";ttq._i=ttq._i||{},ttq._i[e]=[],ttq._i[e]._u=r,ttq._t=ttq._t||{},ttq._t[e]=+new Date,ttq._o=ttq._o||{},ttq._o[e]=n||{};n=d.createElement("script");n.type="text/javascript",n.async=!0,n.src=r+"?sdkid="+e+"&lib="+t;e=d.getElementsByTagName("script")[0];e.parentNode.insertBefore(n,e)};
for(var k=0;k<TT.length;k++)ttq.load(TT[k]);ttq.page();}(w,d,'ttq');

JS;
    }
    if ($fb) {
        $js .= <<<'JS'
!function(f,b,e,v,n,t,s){if(f.fbq)return;n=f.fbq=function(){n.callMethod?n.callMethod.apply(n,arguments):n.queue.push(arguments)};if(!f._fbq)f._fbq=n;n.push=n;n.loaded=!0;n.version='2.0';n.queue=[];t=b.createElement(e);t.async=!0;t.src=v;s=b.getElementsByTagName(e)[0];s.parentNode.insertBefore(t,s)}(w,d,'script','https://connect.facebook.net/en_US/fbevents.js');
for(var j=0;j<FB.length;j++)fbq('init',FB[j]);fbq('track','PageView');

JS;
    }
    $js .= "w.__pxClick=function(){if(TT.length&&w.ttq)w.ttq.track(TE);if(FB.length&&w.fbq)w.fbq('track',FE);};})(window,document);";
    return $js;
}

/* ================= Фото жүктеу ================= */
function save_upload($file, $slug) {
    if (empty($file['tmp_name']) || !is_uploaded_file($file['tmp_name'])) return 'Файл не выбран';
    if ($file['size'] > 8 * 1024 * 1024) return 'Файл слишком большой (максимум 8 МБ)';
    $info = @getimagesize($file['tmp_name']);
    if (!$info || !in_array($info[2], [IMAGETYPE_JPEG, IMAGETYPE_PNG, IMAGETYPE_WEBP], true)) return 'Только изображения JPG, PNG или WEBP';
    $dir = ROOT . '/up';
    if (!is_dir($dir)) @mkdir($dir, 0755);
    $base = 'up/' . $slug . '-' . substr(md5(uniqid('', true)), 0, 6);
    $res = ['img' => '', 'img_webp' => '', 'img_w' => $info[0], 'img_h' => $info[1]];
    if (function_exists('imagecreatefromstring')) {
        $src = @imagecreatefromstring(file_get_contents($file['tmp_name']));
        if ($src) {
            // Барлық проекттегі фото бірдей: 540×560, ортасынан кесіледі
            $TW = 540; $TH = 560;
            $w = imagesx($src); $hh = imagesy($src);
            $scale = max($TW / $w, $TH / $hh);
            $cw = (int)round($TW / $scale); $ch = (int)round($TH / $scale);
            $sx = (int)round(($w - $cw) / 2); $sy = (int)round(($hh - $ch) / 2);
            $dst = imagecreatetruecolor($TW, $TH);
            imagefill($dst, 0, 0, imagecolorallocate($dst, 255, 255, 255));
            imagecopyresampled($dst, $src, 0, 0, $sx, $sy, $TW, $TH, $cw, $ch);
            imageinterlace($dst, true);
            imagejpeg($dst, ROOT . '/' . $base . '.jpg', 76);
            $res['img'] = $base . '.jpg';
            if (function_exists('imagewebp') && @imagewebp($dst, ROOT . '/' . $base . '.webp', 70)) $res['img_webp'] = $base . '.webp';
            $res['img_w'] = $TW; $res['img_h'] = $TH;
            imagedestroy($src); imagedestroy($dst);
            return $res;
        }
    }
    // GD жоқ болса — түпнұсқаны сақтаймыз
    $ext = [IMAGETYPE_JPEG => 'jpg', IMAGETYPE_PNG => 'png', IMAGETYPE_WEBP => 'webp'][$info[2]];
    if (!@move_uploaded_file($file['tmp_name'], ROOT . '/' . $base . '.' . $ext)) return 'Не удалось сохранить файл';
    $res['img'] = $base . '.' . $ext;
    return $res;
}

/* ================= Проект сайты (лендинг) ================= */
function render_landing($slug) {
    $c = cfg();
    $i = proj_index($c, 'slug', $slug);
    header('Content-Type: text/html; charset=UTF-8');
    header('Cache-Control: no-cache');
    if ($i < 0 || empty($c['projects'][$i]['active'])) {
        http_response_code(404);
        echo '<!DOCTYPE html><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Табылмады</title><p style="font:18px sans-serif;text-align:center;margin-top:30vh">Бет табылмады</p>';
        return;
    }
    $p = $c['projects'][$i];
    $go = '../go.php?p=' . rawurlencode($p['id']);
    ?>
<!DOCTYPE html>
<html lang="kk">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
<title><?= h($p['top'] ?: $p['name']) ?></title>
<meta name="description" content="<?= h(trim($p['title'] . ' ' . $p['title_hl'])) ?>">
<meta name="theme-color" content="#1faa55">
<?php if ($p['img']): ?>
<meta property="og:image" content="../<?= h($p['img']) ?>">
<link rel="preload" as="image" href="../<?= h($p['img_webp'] ?: $p['img']) ?>">
<?php endif; ?>
<script><?= px_js($p) ?></script>
<style>
*{box-sizing:border-box;margin:0;padding:0;-webkit-tap-highlight-color:transparent}
html{-webkit-text-size-adjust:100%}
body{background:#fff;color:#111;font-family:-apple-system,BlinkMacSystemFont,"Segoe UI",Roboto,Arial,sans-serif;min-height:100vh;min-height:100dvh;display:flex;flex-direction:column;align-items:center;justify-content:center;padding:calc(14px + env(safe-area-inset-top)) 16px calc(16px + env(safe-area-inset-bottom))}
main{width:100%;max-width:440px;text-align:center}
.top{color:#13803f;font-weight:800;font-size:clamp(14px,4.2vw,17px);line-height:1.3;margin-bottom:10px}
.photo{display:block;margin:0 auto 12px}
.photo img{display:block;margin:0 auto;width:auto;max-width:100%;height:min(38vh,320px);border-radius:16px}
.title{font-size:clamp(21px,6.2vw,27px);font-weight:900;line-height:1.25;margin-bottom:16px}
.title span{color:#13803f}
.wa{display:flex;align-items:center;justify-content:center;gap:12px;width:100%;min-height:76px;padding:12px 16px;background:#1faa55;color:#fff;text-decoration:none;border-radius:18px;font-size:clamp(17px,5.5vw,24px);font-weight:900;white-space:nowrap;box-shadow:0 5px 0 #13803f;animation:pulse 1.8s ease-in-out infinite}
.wa:active{transform:translateY(4px);box-shadow:0 1px 0 #13803f}
.wa svg{width:34px;height:34px;flex-shrink:0;fill:#fff}
.hint{margin-top:12px;font-size:clamp(15px,4.4vw,17px);color:#444;font-weight:600}
.sticky{position:fixed;left:0;right:0;bottom:0;z-index:9;padding:8px 12px calc(8px + env(safe-area-inset-bottom));background:#fff;border-top:1px solid #e5e5e5;display:none}
.sticky.show{display:block}
.sticky .wa{max-width:440px;margin:0 auto;min-height:64px;animation:none}
@keyframes pulse{0%,100%{transform:scale(1)}50%{transform:scale(1.03)}}
@media (prefers-reduced-motion:reduce){.wa{animation:none}}
</style>
</head>
<body>
<svg width="0" height="0" style="position:absolute" aria-hidden="true"><symbol id="wa" viewBox="0 0 32 32"><path d="M16 3C9.4 3 4 8.4 4 15c0 2.4.7 4.6 1.9 6.5L4 29l7.7-1.9A12 12 0 0 0 16 27c6.6 0 12-5.4 12-12S22.6 3 16 3zm0 21.8c-1.8 0-3.5-.5-5-1.4l-.4-.2-4.6 1.1 1.1-4.4-.2-.4A9.8 9.8 0 0 1 6.2 15c0-5.4 4.4-9.8 9.8-9.8s9.8 4.4 9.8 9.8-4.4 9.8-9.8 9.8zm5.4-7.3c-.3-.1-1.7-.9-2-1-.3-.1-.5-.1-.7.1-.2.3-.8 1-.9 1.2-.2.2-.3.2-.6.1-.3-.1-1.2-.5-2.4-1.5-.9-.8-1.5-1.7-1.6-2-.2-.3 0-.5.1-.6l.4-.5c.1-.2.2-.3.3-.5.1-.2 0-.4 0-.5l-.9-2.2c-.2-.6-.5-.5-.7-.5h-.6c-.2 0-.5.1-.8.4-.3.3-1 1-1 2.5s1.1 2.8 1.2 3c.1.2 2.1 3.2 5 4.4.7.3 1.3.5 1.7.6.7.2 1.4.2 1.9.1.6-.1 1.7-.7 2-1.4.2-.7.2-1.3.2-1.4-.1-.1-.3-.2-.6-.3z"/></symbol></svg>
<main>
<?php if ($p['top'] !== ''): ?>  <p class="top"><?= h($p['top']) ?></p>
<?php endif; ?>
<?php if ($p['img']): ?>  <a class="photo js-wa" href="<?= h($go) ?>" aria-label="WhatsApp">
    <picture><?php if ($p['img_webp']): ?><source srcset="../<?= h($p['img_webp']) ?>" type="image/webp"><?php endif; ?><img src="../<?= h($p['img']) ?>" width="<?= (int)$p['img_w'] ?>" height="<?= (int)$p['img_h'] ?>" alt="<?= h($p['name']) ?>"></picture>
  </a>
<?php endif; ?>
  <h1 class="title"><?= h($p['title']) ?><?php if ($p['title_hl'] !== ''): ?> <span><?= h($p['title_hl']) ?></span> 👇<?php endif; ?></h1>
  <a id="waBtn" class="wa js-wa" href="<?= h($go) ?>"><svg aria-hidden="true"><use href="#wa"/></svg><span><?= h($p['button']) ?></span></a>
<?php if ($p['hint'] !== ''): ?>  <p class="hint"><?= h($p['hint']) ?></p>
<?php endif; ?>
</main>
<div class="sticky" id="sticky"><a class="wa js-wa" href="<?= h($go) ?>" tabindex="-1"><svg aria-hidden="true"><use href="#wa"/></svg><span><?= h($p['button']) ?></span></a></div>
<script>
(function () {
  var P = <?= json_encode($p['id']) ?>;
  function sg(s, k) { try { return s.getItem(k); } catch (e) { return null; } }
  function ss(s, k, v) { try { s.setItem(k, v); } catch (e) {} }
  var LS = window.localStorage, SS = window.sessionStorage;
  var vid = sg(LS, 'vid');
  if (!vid) { vid = Date.now().toString(36) + Math.random().toString(36).slice(2, 10); ss(LS, 'vid', vid); }
  document.cookie = 'vid=' + vid + ';path=/;max-age=31536000;samesite=lax';
  var q = new URLSearchParams(location.search);
  var src = (q.get('utm_source') || '').toLowerCase();
  if (!src) src = q.get('ttclid') ? 'tiktok' : q.get('fbclid') ? 'facebook' : q.get('gclid') ? 'google' : '';
  if (!src && document.referrer) {
    try {
      var hh = new URL(document.referrer).hostname.replace(/^www\./, '');
      if (hh && hh !== location.hostname) {
        src = hh;
        var k = ['tiktok', 'instagram', 'facebook', 'google', 'youtube', 'vk', 'yandex', 'telegram'];
        for (var i = 0; i < k.length; i++) if (hh.indexOf(k[i]) > -1) { src = k[i]; break; }
      }
    } catch (e) {}
  }
  src = src || sg(SS, 'src') || ''; ss(SS, 'src', src);
  var cmp = (q.get('utm_campaign') || sg(SS, 'cmp') || '').toLowerCase(); ss(SS, 'cmp', cmp);
  var cn = q.get('utm_campaign') || sg(SS, 'cn') || ''; ss(SS, 'cn', cn);           // кампания атауы (түпнұсқа)
  var ci = (q.get('utm_id') || sg(SS, 'ci') || '').replace(/\D/g, ''); ss(SS, 'ci', ci); // кампания ID
  var mg = (q.get('m') || sg(SS, 'm' + P) || '').replace(/[^a-zA-Z0-9]/g, ''); ss(SS, 'm' + P, mg);
  // Жарнамадан келді ме (таргет): ttclid / fbclid / gclid / yclid / utm_source
  var ad = (q.get('ttclid') || q.get('fbclid') || q.get('gclid') || q.get('yclid') || q.get('utm_source')) ? '1' : (sg(SS, 'ad' + P) || '0');
  ss(SS, 'ad' + P, ad);
  var url = '../go.php?p=' + encodeURIComponent(P) + '&vid=' + encodeURIComponent(vid) + '&src=' + encodeURIComponent(src) +
            '&cmp=' + encodeURIComponent(cmp) + '&ci=' + ci + '&cn=' + encodeURIComponent(cn) + '&ad=' + ad + (mg ? '&m=' + encodeURIComponent(mg) : '');
  // Сілтеме бірден ашылады — WhatsApp сенімді ашылуы үшін кешіктірмейміз
  var links = document.querySelectorAll('.js-wa');
  function px() { if (typeof window.__pxClick === 'function') { try { window.__pxClick(); } catch (er) {} } }
  for (var b = 0; b < links.length; b++) { links[b].href = url; links[b].addEventListener('click', px); }
  // Кіруді тек бет экранда шынымен көрінгенде санаймыз (фонда алдын ала жүктелгенін емес)
  var sent = false;
  function track() {
    if (sent || document.visibilityState === 'hidden' || document.prerendering) return;
    sent = true;
    try {
      var fd = new FormData();
      fd.append('p', P); fd.append('vid', vid); fd.append('src', src); fd.append('cmp', cmp); fd.append('m', mg); fd.append('ad', ad); fd.append('ci', ci); fd.append('cn', cn);
      if (navigator.sendBeacon) navigator.sendBeacon('../track.php', fd);
      else fetch('../track.php', { method: 'POST', body: fd, keepalive: true });
    } catch (e) {}
  }
  setTimeout(track, 0);
  document.addEventListener('visibilitychange', track);
  document.addEventListener('prerenderingchange', track);
  var st = document.getElementById('sticky');
  if ('IntersectionObserver' in window) {
    new IntersectionObserver(function (en) { st.classList.toggle('show', !en[0].isIntersecting); }).observe(document.getElementById('waBtn'));
  }
})();
</script>
</body>
</html>
<?php
}

/* ================= Wazzup24 ================= */
// Wazzup API-ге сұраныс (хостингтен жіберіледі)
function wz_api($key, $method, $path, $body = null) {
    $url = 'https://api.wazzup24.com/v3/' . $path;
    $hdr = ['Authorization: Bearer ' . $key, 'Content-Type: application/json'];
    $data = $body === null ? null : json_encode($body, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    if (function_exists('curl_init')) {
        $ch = curl_init($url);
        curl_setopt_array($ch, [CURLOPT_CUSTOMREQUEST => $method, CURLOPT_HTTPHEADER => $hdr, CURLOPT_RETURNTRANSFER => true,
                                CURLOPT_TIMEOUT => 40, CURLOPT_CONNECTTIMEOUT => 10]);
        if ($data !== null) curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
        $res = curl_exec($ch);
        $code = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $err = curl_error($ch);
        curl_close($ch);
        if ($res === false) return [0, null, $err];
    } else {
        $ctx = stream_context_create(['http' => ['method' => $method, 'header' => implode("\r\n", $hdr), 'content' => (string)$data, 'timeout' => 40, 'ignore_errors' => true]]);
        $res = @file_get_contents($url, false, $ctx);
        $code = 0;
        if (isset($http_response_header[0]) && preg_match('/\s(\d{3})\s/', $http_response_header[0], $mm)) $code = (int)$mm[1];
        if ($res === false) return [0, null, 'Нет соединения'];
        $err = '';
    }
    return [$code, json_decode($res, true), $err ?: (is_string($res) ? substr($res, 0, 300) : '')];
}

// Каналдар тізімін алу: channelId => нөмір
function wz_channels($key) {
    list($code, $j, $err) = wz_api($key, 'GET', 'channels');
    if ($code !== 200 || !is_array($j)) return [null, 'Ответ Wazzup: ' . $code . ' ' . $err];
    $out = [];
    foreach ($j as $c) if (isset($c['channelId'])) {
        $out[$c['channelId']] = ['phone' => preg_replace('/\D/', '', isset($c['plainId']) ? $c['plainId'] : ''), 'transport' => isset($c['transport']) ? $c['transport'] : '', 'state' => isset($c['state']) ? $c['state'] : ''];
    }
    return [$out, ''];
}

// Клиенттер (чаттар) базасы: бірінші жазған уақыты, проект, менеджер, жауап уақыты
function wz_chats_update($fn) {
    ensure_data();
    $f = DATA . '/wz-chats.php';
    $h = @fopen($f, 'c+');
    if (!$h) return null;
    flock($h, LOCK_EX);
    $s = stream_get_contents($h);
    $p = strpos($s, "\n");
    $d = $p === false ? [] : (json_decode(substr($s, $p + 1), true) ?: []);
    $r = $fn($d);
    ftruncate($h, 0);
    rewind($h);
    fwrite($h, GUARD . json_encode($d, JSON_UNESCAPED_UNICODE));
    fflush($h);
    flock($h, LOCK_UN);
    fclose($h);
    return $r;
}

function wz_chats() { $d = store_read('wz-chats'); return $d ?: []; }

function norm_text($s) { return mb_strtolower(trim(preg_replace('/\s+/u', ' ', (string)$s))); }

// Хабарламаның қай проект пен менеджерге тиесілі екенін анықтау
function wz_route($c, $phone, $text) {
    $t = norm_text($text);
    $cands = [];
    foreach ($c['projects'] as $p) {
        foreach ($p['managers'] as $m) if ($m['phone'] === $phone) { $cands[] = [$p, $m]; break; }
    }
    if (!$cands) return [null, null];
    // Клиент автоматты мәтінмен жазса — проект нақты анықталады
    if ($t !== '') foreach ($cands as $pm) {
        $msg = norm_text($pm[0]['message']);
        if ($msg !== '' && (strpos($t, $msg) === 0 || strpos($msg, $t) === 0)) return $pm;
    }
    return $cands[0];
}

/* ================= Чат тарихы ================= */
function msg_log($m) {
    ensure_data();
    $f = DATA . '/msg-' . gmdate('Y-m', time() + TZ) . '.php';
    if (!file_exists($f)) {
        $h = @fopen($f, 'x');
        if ($h) { fwrite($h, GUARD); fclose($h); }
    }
    @file_put_contents($f, json_encode($m, JSON_UNESCAPED_UNICODE) . "\n", FILE_APPEND | LOCK_EX);
}

// Бір чаттың хабарламалары (соңғы $limit)
function chat_msgs($ck, $since, $limit = 300) {
    $out = [];
    $files = glob(DATA . '/msg-*.php') ?: [];
    sort($files);
    $fm = gmdate('Y-m', $since + TZ);
    foreach ($files as $f) {
        if (substr(basename($f), 4, 7) < $fm) continue;
        $h = @fopen($f, 'r');
        if (!$h) continue;
        fgets($h);
        while (($line = fgets($h)) !== false) {
            if (strpos($line, $ck) === false) continue;
            $m = json_decode($line, true);
            if ($m && $m['ck'] === $ck) $out[] = $m;
        }
        fclose($h);
    }
    return array_slice($out, -$limit);
}
