<?php
define('APP', 1);
require __DIR__ . '/lib.php';
nocache();
header('X-Robots-Tag: noindex, nofollow');

session_name('adm');
session_set_cookie_params(0, '/', '', !empty($_SERVER['HTTPS']), true);
session_start();

ensure_data();
$cfg = cfg();
if (!file_exists(__DIR__ . '/px.js')) write_px($cfg);
if (empty($_SESSION['csrf'])) $_SESSION['csrf'] = bin2hex(random_bytes(16));
$CSRF = $_SESSION['csrf'];

function go_to($q = '', $msg = '') {
    if ($msg !== '') $_SESSION['flash'] = $msg;
    header('Location: admin.php' . ($q ? '?' . $q : ''));
    exit;
}
function post($k, $d = '') { return isset($_POST[$k]) ? trim((string)$_POST[$k]) : $d; }

/* ---------------- HTML бастамасы / соңы ---------------- */
function head_html($title) { ?>
<!DOCTYPE html>
<html lang="kk">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="robots" content="noindex, nofollow">
<title><?= h($title) ?></title>
<style>
:root{--g:#16a34a;--g2:#25d366;--gl:#dcfce7;--t:#0f172a;--m:#64748b;--b:#e2e8f0;--bg:#f6f8fa;--r:#dc2626}
*{box-sizing:border-box;margin:0;padding:0}
body{font-family:-apple-system,BlinkMacSystemFont,"Segoe UI",Roboto,Helvetica,Arial,sans-serif;background:var(--bg);color:var(--t);font-size:15px;line-height:1.45}
a{color:var(--g)}
.top{background:#fff;border-bottom:1px solid var(--b);position:sticky;top:0;z-index:5}
.top-in{max-width:1080px;margin:0 auto;padding:10px 16px;display:flex;align-items:center;gap:12px;flex-wrap:wrap}
.brand{font-weight:900;display:flex;align-items:center;gap:8px}
.brand i{width:10px;height:10px;border-radius:50%;background:var(--g2);display:inline-block}
.tabs{display:flex;gap:4px;margin-left:auto;flex-wrap:wrap}
.tabs a{text-decoration:none;color:var(--t);padding:8px 12px;border-radius:10px;font-weight:600;font-size:14px}
.tabs a.on{background:var(--gl);color:#0d7a37}
.tabs a.out{color:var(--m)}
.wrap{max-width:1080px;margin:0 auto;padding:18px 16px 60px}
h2{font-size:18px;margin:0 0 12px}
.card{background:#fff;border:1px solid var(--b);border-radius:16px;padding:16px;margin-bottom:16px}
.grid{display:grid;gap:12px}
.kpis{grid-template-columns:repeat(4,1fr)}
.two{grid-template-columns:1fr 1fr}
@media(max-width:820px){.kpis{grid-template-columns:repeat(2,1fr)}.two{grid-template-columns:1fr}}
.kpi{background:#fff;border:1px solid var(--b);border-radius:16px;padding:14px 16px}
.kpi .l{color:var(--m);font-size:13px;font-weight:600}
.kpi .v{font-size:28px;font-weight:900;letter-spacing:-.5px;margin-top:2px}
.kpi.hi{background:linear-gradient(135deg,#22c55e,#16a34a);color:#fff;border:0}
.kpi.hi .l{color:#dcfce7}
.ranges{display:flex;flex-wrap:wrap;gap:6px;align-items:center;margin-bottom:14px}
.ranges a{text-decoration:none;padding:7px 12px;border-radius:999px;background:#fff;border:1px solid var(--b);color:var(--t);font-weight:600;font-size:13px}
.ranges a.on{background:var(--t);color:#fff;border-color:var(--t)}
.ranges form{display:flex;gap:6px;align-items:center;margin-left:auto;flex-wrap:wrap}
table{width:100%;border-collapse:collapse;font-size:14px}
th,td{text-align:left;padding:9px 8px;border-bottom:1px solid var(--b);vertical-align:middle}
th{color:var(--m);font-weight:600;font-size:12.5px;text-transform:uppercase;letter-spacing:.3px}
td.n,th.n{text-align:right;white-space:nowrap}
tr:last-child td{border-bottom:0}
.bar{height:6px;background:var(--gl);border-radius:6px;overflow:hidden;margin-top:5px;min-width:60px}
.bar b{display:block;height:100%;background:var(--g2)}
.muted{color:var(--m);font-size:13px}
input[type=text],input[type=password],input[type=tel],input[type=date],textarea,select{
  font:inherit;padding:10px 12px;border:1px solid #cbd5e1;border-radius:10px;background:#fff;width:100%;color:var(--t)}
input[type=date]{width:auto;padding:6px 8px;font-size:13px}
textarea{min-height:80px;resize:vertical}
label{display:block;font-weight:600;font-size:13px;margin:10px 0 5px}
.btn{font:inherit;font-weight:700;border:0;border-radius:10px;padding:10px 16px;background:var(--g);color:#fff;cursor:pointer;white-space:nowrap}
.btn.sm{padding:7px 11px;font-size:13px}
.btn.gray{background:#eef2f6;color:var(--t)}
.btn.red{background:#fee2e2;color:var(--r)}
.row{display:flex;gap:8px;align-items:center;flex-wrap:wrap}
.flash{background:var(--gl);color:#0d7a37;border-radius:12px;padding:10px 14px;margin-bottom:14px;font-weight:600}
.err{background:#fee2e2;color:var(--r)}
.pill{display:inline-block;padding:3px 9px;border-radius:999px;font-size:12px;font-weight:700}
.pill.on{background:var(--gl);color:#0d7a37}.pill.off{background:#f1f5f9;color:var(--m)}
.mg{display:grid;grid-template-columns:34px 1fr 1fr auto;gap:8px;align-items:center;padding:12px 0;border-bottom:1px solid var(--b)}
.mg:last-child{border-bottom:0}
.mg .num{width:30px;height:30px;border-radius:50%;background:var(--gl);color:#0d7a37;font-weight:900;display:flex;align-items:center;justify-content:center}
.mg.off .num{background:#f1f5f9;color:var(--m)}
.mg .acts{display:flex;gap:6px;flex-wrap:wrap;justify-content:flex-end}
@media(max-width:700px){.mg{grid-template-columns:34px 1fr}.mg .acts{grid-column:1/-1;justify-content:flex-start}.mg input[name=phone]{grid-column:2}}
.mlink{grid-column:2/-1;display:flex;gap:8px;align-items:center;flex-wrap:wrap}
.mlink input{flex:1;min-width:200px;padding:7px 10px;font-size:13px;background:#f8fafc;color:#0d7a37}
@media(max-width:700px){.mlink{grid-column:1/-1}}
.chart{width:100%;height:auto;display:block}
.legend{display:flex;gap:14px;font-size:12.5px;color:var(--m);margin-bottom:6px}
.legend i{display:inline-block;width:10px;height:10px;border-radius:3px;margin-right:5px;vertical-align:-1px}
.login{max-width:360px;margin:12vh auto;padding:0 16px}
.login .card{padding:24px}
code{background:#f1f5f9;padding:2px 6px;border-radius:6px;font-size:13px;word-break:break-all}
.radio{display:flex;gap:8px;align-items:flex-start;margin:6px 0;font-weight:500}
.radio input{margin-top:4px}
</style>
</head>
<body>
<?php }
function foot_html() { ?>
<script>
function cp(b){var i=b.parentNode.querySelector('input');i.select();i.setSelectionRange(0,999);
var ok=function(){var t=b.textContent;b.textContent='Көшірілді ✓';setTimeout(function(){b.textContent=t},1500)};
if(navigator.clipboard&&window.isSecureContext){navigator.clipboard.writeText(i.value).then(ok,function(){document.execCommand('copy');ok()})}
else{document.execCommand('copy');ok()}}
</script>
</body>
</html>
<?php }

/* ---------------- Алғашқы баптау: құпиясөз орнату ---------------- */
if ($cfg['password'] === '') {
    $err = '';
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $p1 = post('p1'); $p2 = post('p2');
        if (strlen($p1) < 6) $err = 'Құпиясөз кемінде 6 таңбадан тұруы керек';
        elseif ($p1 !== $p2) $err = 'Құпиясөздер сәйкес келмейді';
        else {
            $cfg['password'] = password_hash($p1, PASSWORD_DEFAULT);
            if (!cfg_save($cfg)) $err = 'data папкасына жазу мүмкін емес. Хостингте data папкасына 755 немесе 775 рұқсат беріңіз.';
            else { session_regenerate_id(true); $_SESSION['ok'] = 1; go_to('tab=managers', 'Құпиясөз орнатылды! Енді менеджерлерді қосыңыз.'); }
        }
    }
    head_html('Админ — алғашқы баптау'); ?>
    <div class="login"><div class="card">
      <h2>Админ бетке қош келдіңіз 👋</h2>
      <p class="muted">Алдымен админ бетке кіру үшін құпиясөз ойлап табыңыз.</p>
      <?php if ($err): ?><div class="flash err" style="margin-top:12px"><?= h($err) ?></div><?php endif; ?>
      <form method="post">
        <label>Жаңа құпиясөз</label><input type="password" name="p1" required minlength="6" autocomplete="new-password">
        <label>Құпиясөзді қайталаңыз</label><input type="password" name="p2" required minlength="6" autocomplete="new-password">
        <button class="btn" style="width:100%;margin-top:14px">Сақтау</button>
      </form>
    </div></div>
    <?php foot_html(); exit;
}

/* ---------------- Шығу / Кіру ---------------- */
if (isset($_GET['logout'])) { $_SESSION = []; session_destroy(); header('Location: admin.php'); exit; }

if (empty($_SESSION['ok'])) {
    $err = '';
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        if (password_verify(post('p'), $cfg['password'])) {
            session_regenerate_id(true); $_SESSION['ok'] = 1; go_to();
        }
        sleep(1);
        $err = 'Құпиясөз қате';
    }
    head_html('Админ — кіру'); ?>
    <div class="login"><div class="card">
      <h2>Админ бет</h2>
      <?php if ($err): ?><div class="flash err"><?= h($err) ?></div><?php endif; ?>
      <form method="post">
        <label>Құпиясөз</label><input type="password" name="p" required autofocus autocomplete="current-password">
        <button class="btn" style="width:100%;margin-top:14px">Кіру</button>
      </form>
    </div></div>
    <?php foot_html(); exit;
}

/* ---------------- Әрекеттер (POST) ---------------- */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!hash_equals($CSRF, post('csrf'))) go_to('', 'Бетті жаңартып, қайта көріңіз');
    $a = post('a');
    $id = post('id');
    $idx = -1;
    foreach ($cfg['managers'] as $i => $m) if ($m['id'] === $id) $idx = $i;

    if ($a === 'add') {
        $phone = norm_phone(post('phone'));
        if (!$phone) go_to('tab=managers', '⚠️ Нөмір дұрыс емес. Мысалы: 87051234567');
        $max = 0;
        foreach ($cfg['managers'] as $m) $max = max($max, (int)substr($m['id'], 1));
        $name = post('name') ?: ((count($cfg['managers']) + 1) . '-менеджер');
        $cfg['managers'][] = ['id' => 'm' . ($max + 1), 'name' => mb_substr($name, 0, 40), 'phone' => $phone, 'active' => true];
        cfg_save($cfg);
        go_to('tab=managers', 'Менеджер қосылды ✓');
    }
    if ($a === 'save' && $idx >= 0) {
        $phone = norm_phone(post('phone'));
        if (!$phone) go_to('tab=managers', '⚠️ Нөмір дұрыс емес');
        $cfg['managers'][$idx]['name'] = mb_substr(post('name') ?: $cfg['managers'][$idx]['name'], 0, 40);
        $cfg['managers'][$idx]['phone'] = $phone;
        cfg_save($cfg);
        go_to('tab=managers', 'Сақталды ✓');
    }
    if ($a === 'toggle' && $idx >= 0) {
        $cfg['managers'][$idx]['active'] = empty($cfg['managers'][$idx]['active']);
        cfg_save($cfg);
        go_to('tab=managers', $cfg['managers'][$idx]['active'] ? 'Менеджер қосылды — өтінімдер түседі' : 'Менеджер тоқтатылды — өтінімдер түспейді');
    }
    if ($a === 'del' && $idx >= 0) {
        array_splice($cfg['managers'], $idx, 1);
        cfg_save($cfg);
        go_to('tab=managers', 'Менеджер өшірілді');
    }
    if (($a === 'up' || $a === 'down') && $idx >= 0) {
        $j = $a === 'up' ? $idx - 1 : $idx + 1;
        if (isset($cfg['managers'][$j])) {
            $t = $cfg['managers'][$idx]; $cfg['managers'][$idx] = $cfg['managers'][$j]; $cfg['managers'][$j] = $t;
            cfg_save($cfg);
        }
        go_to('tab=managers');
    }
    if ($a === 'settings') {
        $cfg['message'] = mb_substr(post('message'), 0, 500);
        $cfg['mode'] = post('mode') === 'random' ? 'random' : 'rr';
        cfg_save($cfg);
        go_to('tab=settings', 'Баптаулар сақталды ✓');
    }
    if ($a === 'pixels') {
        $tt = px_list(strtoupper(post('tt_ids')), '/^[A-Z0-9]{8,30}$/');
        $fb = px_list(post('fb_ids'), '/^\d{8,20}$/');
        $cfg['tt_ids'] = implode(', ', $tt);
        $cfg['fb_ids'] = implode(', ', $fb);
        $cfg['tt_event'] = in_array(post('tt_event'), ['ClickButton', 'Contact', 'SubmitForm', 'Lead', 'CompleteRegistration'], true) ? post('tt_event') : 'ClickButton';
        $cfg['fb_event'] = in_array(post('fb_event'), ['Lead', 'Contact', 'SubmitApplication', 'CompleteRegistration'], true) ? post('fb_event') : 'Lead';
        cfg_save($cfg);
        if (!file_exists(__DIR__ . '/px.js')) go_to('tab=settings', '⚠️ px.js файлын жазу мүмкін болмады. Сайт папкасына жазу рұқсатын тексеріңіз.');
        go_to('tab=settings', 'Пиксельдер сақталды ✓ Сайтта бірден жұмыс істейді');
    }
    if ($a === 'pass') {
        if (!password_verify(post('old'), $cfg['password'])) go_to('tab=settings', '⚠️ Қазіргі құпиясөз қате');
        if (strlen(post('p1')) < 6 || post('p1') !== post('p2')) go_to('tab=settings', '⚠️ Жаңа құпиясөз кемінде 6 таңба және екеуі бірдей болуы керек');
        $cfg['password'] = password_hash(post('p1'), PASSWORD_DEFAULT);
        cfg_save($cfg);
        go_to('tab=settings', 'Құпиясөз өзгертілді ✓');
    }
    if ($a === 'reset' && post('confirm') === 'ИӘ') {
        foreach (glob(DATA . '/ev-*.php') ?: [] as $f) @unlink($f);
        go_to('tab=settings', 'Статистика тазаланды');
    }
    go_to();
}

$flash = isset($_SESSION['flash']) ? $_SESSION['flash'] : '';
unset($_SESSION['flash']);
$tab = isset($_GET['tab']) ? $_GET['tab'] : 'stats';
if (!in_array($tab, ['stats', 'managers', 'settings'], true)) $tab = 'stats';

$base = (!empty($_SERVER['HTTPS']) ? 'https' : 'http') . '://' . (isset($_SERVER['HTTP_HOST']) ? $_SERVER['HTTP_HOST'] : '') . rtrim(dirname($_SERVER['SCRIPT_NAME']), '/\\') . '/';
$land = $base . 'bel/';  // бандаж сайтының мекенжайы

head_html('Админ панель');
?>
<div class="top"><div class="top-in">
  <div class="brand"><i></i> Админ панель</div>
  <nav class="tabs">
    <a href="?tab=stats" class="<?= $tab === 'stats' ? 'on' : '' ?>">📊 Аналитика</a>
    <a href="?tab=managers" class="<?= $tab === 'managers' ? 'on' : '' ?>">👥 Менеджерлер</a>
    <a href="?tab=settings" class="<?= $tab === 'settings' ? 'on' : '' ?>">⚙️ Баптаулар</a>
    <a href="bel/" target="_blank">🌐 Сайт</a>
    <a href="?logout=1" class="out">Шығу</a>
  </nav>
</div></div>
<div class="wrap">
<?php if ($flash): ?><div class="flash<?= strpos($flash, '⚠️') === 0 ? ' err' : '' ?>"><?= h($flash) ?></div><?php endif; ?>

<?php
/* ================= АНАЛИТИКА ================= */
if ($tab === 'stats'):
    $now = time();
    $t0 = day_start($now);
    $r = isset($_GET['r']) ? $_GET['r'] : 'today';
    $from = $t0; $to = $now;
    $rangeLabel = 'Бүгін';
    if ($r === 'yesterday') { $from = $t0 - 86400; $to = $t0 - 1; $rangeLabel = 'Кеше'; }
    elseif ($r === '7') { $from = $t0 - 6 * 86400; $rangeLabel = 'Соңғы 7 күн'; }
    elseif ($r === '30') { $from = $t0 - 29 * 86400; $rangeLabel = 'Соңғы 30 күн'; }
    elseif ($r === 'all') { $from = day_start(first_event_ts()); $rangeLabel = 'Барлық уақыт'; }
    elseif ($r === 'custom') {
        $df = isset($_GET['from']) ? $_GET['from'] : ''; $dt = isset($_GET['to']) ? $_GET['to'] : '';
        if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $df) && preg_match('/^\d{4}-\d{2}-\d{2}$/', $dt)) {
            $from = strtotime($df . ' 00:00:00 UTC') - TZ;
            $to = strtotime($dt . ' 00:00:00 UTC') - TZ + 86399;
            if ($to < $from) { $x = $from; $from = $to - 86399; $to = $x + 86399; }
            $rangeLabel = ldate('d.m.Y', $from) . ' — ' . ldate('d.m.Y', $to);
        } else $r = 'today';
    }

    $ev = load_events($from, $to);
    $visits = 0; $clicks = 0; $uv = []; $uc = [];
    $byM = []; $byS = []; $byD = []; $byC = []; $recent = [];
    $hourly = ($to - $from) <= 86400;
    $buckets = [];
    if ($hourly) { for ($i = 0; $i < 24; $i++) $buckets[$i] = [sprintf('%02d', $i), 0, 0]; }
    else { for ($d = day_start($from); $d <= $to; $d += 86400) $buckets[ldate('Y-m-d', $d)] = [ldate('d.m', $d), 0, 0]; }

    foreach ($ev as $e) {
        $isC = $e['e'] === 'c';
        $vk = !empty($e['v']) ? $e['v'] : ('x' . $e['t']);
        $s = !empty($e['s']) ? $e['s'] : 'direct';
        $dv = !empty($e['d']) ? $e['d'] : '—';
        $bk = $hourly ? (int)ldate('G', $e['t']) : ldate('Y-m-d', $e['t']);
        if (!isset($byS[$s])) $byS[$s] = [0, 0];
        if (!isset($byD[$dv])) $byD[$dv] = [0, 0];
        if ($isC) {
            $clicks++; $uc[$vk] = 1;
            $mid = !empty($e['m']) ? $e['m'] : '—';
            if (!isset($byM[$mid])) $byM[$mid] = ['n' => isset($e['mn']) ? $e['mn'] : '—', 'c' => 0, 'ov' => 0, 'oc' => 0];
            $byM[$mid]['c']++;
            if (!empty($e['o'])) $byM[$mid]['oc']++;
            $byS[$s][1]++; $byD[$dv][1]++;
            if (!empty($e['c'])) { if (!isset($byC[$e['c']])) $byC[$e['c']] = [0, 0]; $byC[$e['c']][1]++; }
            if (isset($buckets[$bk])) $buckets[$bk][2]++;
            $recent[] = $e;
        } else {
            $visits++; $uv[$vk] = 1;
            if (!empty($e['m'])) {
                if (!isset($byM[$e['m']])) $byM[$e['m']] = ['n' => $e['m'], 'c' => 0, 'ov' => 0, 'oc' => 0];
                $byM[$e['m']]['ov']++;
            }
            $byS[$s][0]++; $byD[$dv][0]++;
            if (!empty($e['c'])) { if (!isset($byC[$e['c']])) $byC[$e['c']] = [0, 0]; $byC[$e['c']][0]++; }
            if (isset($buckets[$bk])) $buckets[$bk][1]++;
        }
    }
    // Менеджер аттарын қазіргі баптаулардан аламыз
    foreach ($cfg['managers'] as $m) if (isset($byM[$m['id']])) $byM[$m['id']]['n'] = $m['name'];
    uasort($byM, function ($a, $b) { return $b['c'] - $a['c']; });
    uasort($byS, function ($a, $b) { return ($b[0] + $b[1]) - ($a[0] + $a[1]); });
    uasort($byD, function ($a, $b) { return ($b[0] + $b[1]) - ($a[0] + $a[1]); });
    uasort($byC, function ($a, $b) { return ($b[0] + $b[1]) - ($a[0] + $a[1]); });
    $recent = array_reverse(array_slice($recent, -30));
    $conv = count($uv) ? round(count($uc) / count($uv) * 100, 1) : 0;
    $srcNames = ['tiktok' => 'TikTok', 'instagram' => 'Instagram', 'facebook' => 'Facebook', 'google' => 'Google', 'direct' => 'Тікелей / белгісіз', 'youtube' => 'YouTube', 'telegram' => 'Telegram', 'vk' => 'VK', 'yandex' => 'Яндекс'];
    $sn = function ($s) use ($srcNames) { return isset($srcNames[$s]) ? $srcNames[$s] : $s; };
    $qs = function ($k) use ($r) { return $r === $k ? 'on' : ''; };
?>
  <div class="ranges">
    <a href="?tab=stats&r=today" class="<?= $qs('today') ?>">Бүгін</a>
    <a href="?tab=stats&r=yesterday" class="<?= $qs('yesterday') ?>">Кеше</a>
    <a href="?tab=stats&r=7" class="<?= $qs('7') ?>">7 күн</a>
    <a href="?tab=stats&r=30" class="<?= $qs('30') ?>">30 күн</a>
    <a href="?tab=stats&r=all" class="<?= $qs('all') ?>">Барлығы</a>
    <form method="get">
      <input type="hidden" name="tab" value="stats"><input type="hidden" name="r" value="custom">
      <input type="date" name="from" value="<?= h(ldate('Y-m-d', $from)) ?>">
      <span class="muted">—</span>
      <input type="date" name="to" value="<?= h(ldate('Y-m-d', $to)) ?>">
      <button class="btn sm gray">Көрсету</button>
    </form>
  </div>

  <div class="grid kpis" style="margin-bottom:16px">
    <div class="kpi"><div class="l">Сайтқа кірулер</div><div class="v"><?= $visits ?></div></div>
    <div class="kpi"><div class="l">Бірегей келушілер</div><div class="v"><?= count($uv) ?></div></div>
    <div class="kpi hi"><div class="l">WhatsApp-қа басулар</div><div class="v"><?= $clicks ?></div></div>
    <div class="kpi"><div class="l">Конверсия</div><div class="v"><?= $conv ?>%</div></div>
  </div>

  <div class="card">
    <h2><?= h($rangeLabel) ?> — <?= $hourly ? 'сағат бойынша' : 'күн бойынша' ?></h2>
    <div class="legend"><span><i style="background:#bbf7d0"></i>Кірулер</span><span><i style="background:#16a34a"></i>WhatsApp басулар</span></div>
    <?php
    $n = count($buckets); $max = 1;
    foreach ($buckets as $b) $max = max($max, $b[1], $b[2]);
    $W = 26; $H = 170; $pad = 22; $w = max($n * $W, 300);
    $every = max(1, (int)ceil($n / 15));
    echo '<svg class="chart" viewBox="0 0 ' . $w . ' ' . ($H + $pad) . '" preserveAspectRatio="none" style="max-height:260px">';
    for ($g = 0; $g <= 4; $g++) { $y = $H - $H * $g / 4; echo '<line x1="0" x2="' . $w . '" y1="' . $y . '" y2="' . $y . '" stroke="#eef2f6"/>'; }
    $i = 0;
    foreach ($buckets as $b) {
        $x = $i * ($w / $n) + 3; $bw = ($w / $n - 6) / 2;
        $h1 = $b[1] / $max * ($H - 10); $h2 = $b[2] / $max * ($H - 10);
        echo '<g><title>' . h($b[0]) . ': кіру ' . $b[1] . ', басу ' . $b[2] . '</title>';
        echo '<rect x="' . round($x, 1) . '" y="' . round($H - $h1, 1) . '" width="' . round($bw, 1) . '" height="' . round($h1, 1) . '" rx="2" fill="#bbf7d0"/>';
        echo '<rect x="' . round($x + $bw, 1) . '" y="' . round($H - $h2, 1) . '" width="' . round($bw, 1) . '" height="' . round($h2, 1) . '" rx="2" fill="#16a34a"/></g>';
        if ($i % $every === 0) echo '<text x="' . round($x + $bw, 1) . '" y="' . ($H + 15) . '" font-size="10" text-anchor="middle" fill="#64748b">' . h($b[0]) . '</text>';
        $i++;
    }
    echo '<text x="2" y="10" font-size="10" fill="#94a3b8">' . $max . '</text></svg>';
    ?>
  </div>

  <div class="grid two">
    <div class="card">
      <h2>👥 Менеджерлерге түскен өтінімдер</h2>
      <?php if (!$byM): ?><p class="muted">Бұл кезеңде басулар әлі жоқ</p><?php else: ?>
      <table><tr><th>Менеджер</th><th class="n">Барлық басу</th><th class="n" title="Жеке сілтемеден: кіру / басу">Жеке сілтеме</th></tr>
      <?php foreach ($byM as $m): $p = $clicks ? round($m['c'] / $clicks * 100) : 0; ?>
        <tr><td><?= h($m['n']) ?><div class="bar"><b style="width:<?= $p ?>%"></b></div></td><td class="n"><b><?= $m['c'] ?></b> <span class="muted"><?= $p ?>%</span></td><td class="n"><?= $m['ov'] || $m['oc'] ? $m['ov'] . ' / <b>' . $m['oc'] . '</b>' : '<span class="muted">—</span>' ?></td></tr>
      <?php endforeach; ?></table>
      <p class="muted" style="margin-top:8px">«Жеке сілтеме» бағаны: менеджердің өз сілтемесімен кірген / WhatsApp басқан адамдар.</p><?php endif; ?>
    </div>

    <div class="card">
      <h2>🌐 Қайдан келді</h2>
      <?php if (!$byS): ?><p class="muted">Деректер әлі жоқ</p><?php else: ?>
      <table><tr><th>Дереккөз</th><th class="n">Кіру</th><th class="n">Басу</th><th class="n">Конв.</th></tr>
      <?php foreach ($byS as $k => $v): ?>
        <tr><td><?= h($sn($k)) ?></td><td class="n"><?= $v[0] ?></td><td class="n"><b><?= $v[1] ?></b></td><td class="n"><?= $v[0] ? round($v[1] / $v[0] * 100) . '%' : '—' ?></td></tr>
      <?php endforeach; ?></table><?php endif; ?>
    </div>

    <div class="card">
      <h2>📱 Құрылғылар</h2>
      <?php if (!$byD): ?><p class="muted">Деректер әлі жоқ</p><?php else: ?>
      <table><tr><th>Құрылғы</th><th class="n">Кіру</th><th class="n">Басу</th></tr>
      <?php foreach ($byD as $k => $v): ?>
        <tr><td><?= h($k) ?></td><td class="n"><?= $v[0] ?></td><td class="n"><b><?= $v[1] ?></b></td></tr>
      <?php endforeach; ?></table><?php endif; ?>
    </div>

    <div class="card">
      <h2>🎯 Науқандар (utm_campaign)</h2>
      <?php if (!$byC): ?><p class="muted">Жарнама сілтемесіне <code>?utm_campaign=атауы</code> қоссаңыз, осында әр жарнама бөлек көрінеді.</p><?php else: ?>
      <table><tr><th>Науқан</th><th class="n">Кіру</th><th class="n">Басу</th><th class="n">Конв.</th></tr>
      <?php foreach ($byC as $k => $v): ?>
        <tr><td><?= h($k) ?></td><td class="n"><?= $v[0] ?></td><td class="n"><b><?= $v[1] ?></b></td><td class="n"><?= $v[0] ? round($v[1] / $v[0] * 100) . '%' : '—' ?></td></tr>
      <?php endforeach; ?></table><?php endif; ?>
    </div>
  </div>

  <div class="card">
    <h2>🕒 Соңғы басулар</h2>
    <?php if (!$recent): ?><p class="muted">Бұл кезеңде басулар әлі жоқ</p><?php else: ?>
    <div style="overflow-x:auto"><table><tr><th>Уақыт</th><th>Менеджер</th><th>Дереккөз</th><th>Құрылғы</th></tr>
    <?php foreach ($recent as $e): $mn = isset($e['mn']) ? $e['mn'] : '—';
        foreach ($cfg['managers'] as $m) if (isset($e['m']) && $m['id'] === $e['m']) $mn = $m['name']; ?>
      <tr><td><?= ldate('d.m H:i', $e['t']) ?></td><td><?= h($mn) ?></td><td><?= h($sn(!empty($e['s']) ? $e['s'] : 'direct')) ?><?= !empty($e['c']) ? ' <span class="muted">· ' . h($e['c']) . '</span>' : '' ?></td><td><?= h(isset($e['d']) ? $e['d'] : '') ?></td></tr>
    <?php endforeach; ?></table></div><?php endif; ?>
  </div>

<?php
/* ================= МЕНЕДЖЕРЛЕР ================= */
elseif ($tab === 'managers'):
    $todayCl = [];
    foreach (load_events(day_start(time()), time()) as $e) if ($e['e'] === 'c' && !empty($e['m'])) $todayCl[$e['m']] = (isset($todayCl[$e['m']]) ? $todayCl[$e['m']] : 0) + 1;
    $activeCount = 0; foreach ($cfg['managers'] as $m) if (!empty($m['active'])) $activeCount++;
?>
  <div class="card">
    <h2>➕ Жаңа менеджер қосу</h2>
    <form method="post" class="row">
      <input type="hidden" name="csrf" value="<?= h($CSRF) ?>"><input type="hidden" name="a" value="add">
      <input type="text" name="name" placeholder="<?= count($cfg['managers']) + 1 ?>-менеджер (аты)" style="flex:1;min-width:160px" maxlength="40">
      <input type="tel" name="phone" placeholder="WhatsApp нөмірі: 87051234567" required style="flex:1;min-width:180px">
      <button class="btn">Қосу</button>
    </form>
  </div>

  <div class="card">
    <h2>👥 Менеджерлер тізімі</h2>
    <p class="muted" style="margin-bottom:6px">
      Өтінімдер <b>белсенді</b> менеджерлерге <?= $cfg['mode'] === 'random' ? '<b>кездейсоқ</b>' : '<b>кезекпен</b> (1 → 2 → 3 → 1 …)' ?> таратылады.
      Қазір белсенді: <b><?= $activeCount ?></b>. Бір клиент қайта басса — сол менеджерге түседі.
    </p>
    <p class="muted" style="margin-bottom:6px">🔗 <b>Жеке сілтеме</b> — менеджер оны өз жарнамасына, сторисіне немесе клиентке жібере алады. Сол сілтемемен кірген клиент кезекке түспей, <b>тікелей сол менеджерге</b> жазады. Менеджер тоқтатылған болса, клиент әдеттегідей кезекпен бөлінеді.</p>
    <?php if (!$cfg['managers']): ?><p class="muted">Менеджер жоқ. Жоғарыдан қосыңыз.</p><?php endif; ?>
    <?php foreach ($cfg['managers'] as $i => $m): $on = !empty($m['active']); $fid = 'f' . h($m['id']); ?>
      <div class="mg <?= $on ? '' : 'off' ?>">
        <div class="num"><?= $i + 1 ?></div>
        <input type="text" name="name" value="<?= h($m['name']) ?>" form="<?= $fid ?>" maxlength="40">
        <input type="tel" name="phone" value="<?= h($m['phone']) ?>" form="<?= $fid ?>">
        <div class="acts">
          <span class="pill <?= $on ? 'on' : 'off' ?>"><?= $on ? 'Белсенді' : 'Тоқтатылған' ?></span>
          <span class="muted" title="Бүгінгі басулар">бүгін: <b><?= isset($todayCl[$m['id']]) ? $todayCl[$m['id']] : 0 ?></b></span>
          <form method="post" id="<?= $fid ?>"><input type="hidden" name="csrf" value="<?= h($CSRF) ?>"><input type="hidden" name="a" value="save"><input type="hidden" name="id" value="<?= h($m['id']) ?>"><button class="btn sm">Сақтау</button></form>
          <form method="post"><input type="hidden" name="csrf" value="<?= h($CSRF) ?>"><input type="hidden" name="a" value="toggle"><input type="hidden" name="id" value="<?= h($m['id']) ?>"><button class="btn sm gray"><?= $on ? '⏸ Тоқтату' : '▶ Қосу' ?></button></form>
          <form method="post"><input type="hidden" name="csrf" value="<?= h($CSRF) ?>"><input type="hidden" name="a" value="up"><input type="hidden" name="id" value="<?= h($m['id']) ?>"><button class="btn sm gray" title="Жоғары">↑</button></form>
          <form method="post"><input type="hidden" name="csrf" value="<?= h($CSRF) ?>"><input type="hidden" name="a" value="down"><input type="hidden" name="id" value="<?= h($m['id']) ?>"><button class="btn sm gray" title="Төмен">↓</button></form>
          <a class="btn sm gray" style="text-decoration:none" target="_blank" rel="noopener" href="https://wa.me/<?= h($m['phone']) ?>" title="Нөмірді тексеру">WA</a>
          <form method="post" onsubmit="return confirm('<?= h($m['name']) ?> өшірілсін бе?')"><input type="hidden" name="csrf" value="<?= h($CSRF) ?>"><input type="hidden" name="a" value="del"><input type="hidden" name="id" value="<?= h($m['id']) ?>"><button class="btn sm red">Өшіру</button></form>
        </div>
        <div class="mlink">
          <span class="muted">🔗 Жеке сілтеме:</span>
          <input type="text" readonly value="<?= h($land . '?m=' . $m['id']) ?>" onclick="this.select()">
          <button type="button" class="btn sm gray" onclick="cp(this)">Көшіру</button>
        </div>
      </div>
    <?php endforeach; ?>
  </div>

<?php
/* ================= БАПТАУЛАР ================= */
else: ?>
  <div class="card">
    <h2>💬 WhatsApp баптаулары</h2>
    <form method="post">
      <input type="hidden" name="csrf" value="<?= h($CSRF) ?>"><input type="hidden" name="a" value="settings">
      <label>Клиент WhatsApp ашқанда автоматты жазылатын мәтін</label>
      <textarea name="message" maxlength="500"><?= h($cfg['message']) ?></textarea>
      <label>Өтінімдерді тарату тәсілі</label>
      <label class="radio"><input type="radio" name="mode" value="rr" <?= $cfg['mode'] !== 'random' ? 'checked' : '' ?>> <span><b>Кезекпен</b> — 1-менеджер, 2-менеджер, 3-менеджер, қайтадан 1… (әділ бөлінеді)</span></label>
      <label class="radio"><input type="radio" name="mode" value="random" <?= $cfg['mode'] === 'random' ? 'checked' : '' ?>> <span><b>Кездейсоқ</b> — әр басуда кездейсоқ менеджер</span></label>
      <button class="btn" style="margin-top:10px">Сақтау</button>
    </form>
  </div>

  <div class="card">
    <h2>📈 Пиксельдер</h2>
    <p class="muted">ID жазып «Сақтау» басыңыз — сайтқа бірден қосылады. Бірнеше пиксель болса, үтірмен бөліп жазыңыз. Өшіру үшін өрісті бос қалдырыңыз.</p>
    <form method="post">
      <input type="hidden" name="csrf" value="<?= h($CSRF) ?>"><input type="hidden" name="a" value="pixels">
      <label>TikTok Pixel ID</label>
      <input type="text" name="tt_ids" value="<?= h($cfg['tt_ids']) ?>" placeholder="Мысалы: DBCM57BC77U4J2JQ97R0" autocomplete="off">
      <label>TikTok: батырма басылғандағы оқиға</label>
      <select name="tt_event">
        <?php foreach (['ClickButton' => 'ClickButton — батырманы басу', 'Contact' => 'Contact — байланыс', 'SubmitForm' => 'SubmitForm — өтінім жіберу', 'Lead' => 'Lead — лид', 'CompleteRegistration' => 'CompleteRegistration — тіркелу'] as $k => $v): ?>
          <option value="<?= $k ?>" <?= $cfg['tt_event'] === $k ? 'selected' : '' ?>><?= h($v) ?></option>
        <?php endforeach; ?>
      </select>
      <label style="margin-top:16px">Meta (Facebook / Instagram) Pixel ID</label>
      <input type="text" name="fb_ids" value="<?= h($cfg['fb_ids']) ?>" placeholder="Мысалы: 1234567890123456" inputmode="numeric" autocomplete="off">
      <label>Meta: батырма басылғандағы оқиға</label>
      <select name="fb_event">
        <?php foreach (['Lead' => 'Lead — лид', 'Contact' => 'Contact — байланыс', 'SubmitApplication' => 'SubmitApplication — өтінім', 'CompleteRegistration' => 'CompleteRegistration — тіркелу'] as $k => $v): ?>
          <option value="<?= $k ?>" <?= $cfg['fb_event'] === $k ? 'selected' : '' ?>><?= h($v) ?></option>
        <?php endforeach; ?>
      </select>
      <p class="muted" style="margin-top:10px">Сайт ашылғанда PageView, WhatsApp батырмасы басылғанда — таңдалған оқиға жіберіледі.</p>
      <button class="btn" style="margin-top:10px">Сақтау</button>
    </form>
  </div>

  <div class="card">
    <h2>🔗 Жарнамаға арналған сілтемелер</h2>
    <p>Сайттың негізгі сілтемесі: <a href="<?= h($land) ?>" target="_blank"><b><?= h($land) ?></b></a></p>
    <p class="muted" style="margin-top:8px">Әр жарнамаға осындай сілтеме қойсаңыз, аналитикада қайдан келгені нақты көрінеді:</p>
    <p style="margin-top:8px"><code><?= h($land) ?>?utm_source=tiktok&amp;utm_campaign=bel1</code></p>
    <p style="margin-top:6px"><code><?= h($land) ?>?utm_source=instagram&amp;utm_campaign=reels2</code></p>
  </div>

  <div class="card">
    <h2>🔒 Құпиясөзді өзгерту</h2>
    <form method="post" style="max-width:380px">
      <input type="hidden" name="csrf" value="<?= h($CSRF) ?>"><input type="hidden" name="a" value="pass">
      <label>Қазіргі құпиясөз</label><input type="password" name="old" required autocomplete="current-password">
      <label>Жаңа құпиясөз</label><input type="password" name="p1" required minlength="6" autocomplete="new-password">
      <label>Жаңа құпиясөзді қайталаңыз</label><input type="password" name="p2" required minlength="6" autocomplete="new-password">
      <button class="btn" style="margin-top:12px">Өзгерту</button>
    </form>
  </div>

  <div class="card">
    <h2>🗑 Статистиканы тазалау</h2>
    <p class="muted">Барлық кірулер мен басулар өшіріледі. Менеджерлер мен баптаулар сақталады.</p>
    <form method="post" class="row" style="margin-top:10px" onsubmit="return confirm('Барлық статистика өшірілсін бе?')">
      <input type="hidden" name="csrf" value="<?= h($CSRF) ?>"><input type="hidden" name="a" value="reset">
      <input type="text" name="confirm" placeholder="Растау үшін ИӘ деп жазыңыз" style="max-width:260px" required>
      <button class="btn red">Тазалау</button>
    </form>
  </div>
<?php endif; ?>
</div>
<?php foot_html();
