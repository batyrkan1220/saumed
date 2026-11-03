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
if (empty($_SESSION['csrf'])) $_SESSION['csrf'] = bin2hex(random_bytes(16));
$CSRF = $_SESSION['csrf'];

function go_to($q = '', $msg = '') {
    if ($msg !== '') $_SESSION['flash'] = $msg;
    header('Location: admin.php' . ($q ? '?' . $q : ''));
    exit;
}
function post($k, $d = '') { return isset($_POST[$k]) ? trim((string)$_POST[$k]) : $d; }
function csrf_field() { global $CSRF; return '<input type="hidden" name="csrf" value="' . h($CSRF) . '">'; }

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
.grid>*{min-width:0}
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
.psw{display:flex;align-items:center;gap:8px;flex-wrap:wrap}
.psw select{width:auto;max-width:230px;padding:7px 10px;font-weight:700}
.preview{display:flex;gap:14px;align-items:center;flex-wrap:wrap}
.preview img{width:110px;height:110px;object-fit:contain;background:#f3f4f6;border-radius:12px}
.pj{display:grid;grid-template-columns:1fr auto;gap:10px;align-items:center;padding:12px 0;border-bottom:1px solid var(--b)}
.pj:last-child{border-bottom:0}
.pj.cur{background:#f0fdf4;margin:0 -16px;padding:12px 16px}
.sec{font-size:15px;margin:18px 0 4px;padding-top:14px;border-top:1px solid var(--b)}
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
        if (password_verify(post('p'), $cfg['password'])) { session_regenerate_id(true); $_SESSION['ok'] = 1; go_to(); }
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

ensure_all_stubs($cfg);

/* ---------------- Ағымдағы проект ---------------- */
if (isset($_GET['p']) && proj_index($cfg, 'id', $_GET['p']) >= 0) $_SESSION['p'] = $_GET['p'];
$PI = proj_index($cfg, 'id', isset($_SESSION['p']) ? $_SESSION['p'] : '');
if ($PI < 0) $PI = 0;
$P = $cfg['projects'][$PI];

$base = (!empty($_SERVER['HTTPS']) ? 'https' : 'http') . '://' . (isset($_SERVER['HTTP_HOST']) ? $_SERVER['HTTP_HOST'] : '') . rtrim(dirname($_SERVER['SCRIPT_NAME']), '/\\') . '/';
$land = $base . $P['slug'] . '/';

/* ---------------- Әрекеттер (POST) ---------------- */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!hash_equals($CSRF, post('csrf'))) go_to('', 'Бетті жаңартып, қайта көріңіз');
    $a = post('a');
    $M = &$cfg['projects'][$PI]['managers'];
    $id = post('id');
    $idx = -1;
    foreach ($M as $i => $m) if ($m['id'] === $id) $idx = $i;

    /* --- Менеджерлер --- */
    if ($a === 'add') {
        $phone = norm_phone(post('phone'));
        if (!$phone) go_to('tab=managers', '⚠️ Нөмір дұрыс емес. Мысалы: 87051234567');
        $max = 0;
        foreach ($M as $m) $max = max($max, (int)substr($m['id'], 1));
        $name = post('name') ?: ((count($M) + 1) . '-менеджер');
        $M[] = ['id' => 'm' . ($max + 1), 'name' => mb_substr($name, 0, 40), 'phone' => $phone, 'active' => true];
        cfg_save($cfg);
        go_to('tab=managers', 'Менеджер қосылды ✓');
    }
    if ($a === 'save' && $idx >= 0) {
        $phone = norm_phone(post('phone'));
        if (!$phone) go_to('tab=managers', '⚠️ Нөмір дұрыс емес');
        $M[$idx]['name'] = mb_substr(post('name') ?: $M[$idx]['name'], 0, 40);
        $M[$idx]['phone'] = $phone;
        cfg_save($cfg);
        go_to('tab=managers', 'Сақталды ✓');
    }
    if ($a === 'toggle' && $idx >= 0) {
        $M[$idx]['active'] = empty($M[$idx]['active']);
        cfg_save($cfg);
        go_to('tab=managers', $M[$idx]['active'] ? 'Менеджер қосылды — өтінімдер түседі' : 'Менеджер тоқтатылды — өтінімдер түспейді');
    }
    if ($a === 'del' && $idx >= 0) {
        array_splice($M, $idx, 1);
        cfg_save($cfg);
        go_to('tab=managers', 'Менеджер өшірілді');
    }
    if (($a === 'up' || $a === 'down') && $idx >= 0) {
        $j = $a === 'up' ? $idx - 1 : $idx + 1;
        if (isset($M[$j])) { $t = $M[$idx]; $M[$idx] = $M[$j]; $M[$j] = $t; cfg_save($cfg); }
        go_to('tab=managers');
    }

    /* --- Сайт мәтіні, фото, пиксель --- */
    if ($a === 'site') {
        $X = &$cfg['projects'][$PI];
        $X['name']     = mb_substr(post('name') ?: $X['name'], 0, 60);
        $X['top']      = mb_substr(post('top'), 0, 120);
        $X['title']    = mb_substr(post('title'), 0, 200);
        $X['title_hl'] = mb_substr(post('title_hl'), 0, 100);
        $X['button']   = mb_substr(post('button') ?: 'WHATSAPP-ҚА ЖАЗУ', 0, 40);
        $X['hint']     = mb_substr(post('hint'), 0, 120);
        $X['message']  = mb_substr(post('message'), 0, 500);
        $X['mode']     = post('mode') === 'random' ? 'random' : 'rr';
        $X['active']   = post('active') === '1';
        $X['tt_ids']   = implode(', ', px_list(strtoupper(post('tt_ids')), '/^[A-Z0-9]{8,30}$/'));
        $X['fb_ids']   = implode(', ', px_list(post('fb_ids'), '/^\d{8,20}$/'));
        $X['tt_event'] = in_array(post('tt_event'), ['ClickButton', 'Contact', 'SubmitForm', 'Lead', 'CompleteRegistration'], true) ? post('tt_event') : 'ClickButton';
        $X['fb_event'] = in_array(post('fb_event'), ['Lead', 'Contact', 'SubmitApplication', 'CompleteRegistration'], true) ? post('fb_event') : 'Lead';
        $msg = 'Сайт сақталды ✓ Өзгерістер бірден көрінеді';
        // Сілтеме (slug) өзгерсе
        $slug = strtolower(post('slug'));
        if ($slug !== $X['slug']) {
            if (!valid_slug($slug)) go_to('tab=site', '⚠️ Сілтеме тек латын әріп, сан және «-» болсын (2–30 таңба)');
            if (proj_index($cfg, 'slug', $slug) >= 0) go_to('tab=site', '⚠️ Бұл сілтеме бос емес');
            if (is_dir(ROOT . '/' . $slug) && !file_exists(ROOT . '/' . $slug . '/index.php')) go_to('tab=site', '⚠️ Мұндай папка серверде бар, басқа атау таңдаңыз');
            $old = $X['slug'];
            $X['slug'] = $slug;
            ensure_stub($slug);
            if ($old && file_exists(ROOT . '/' . $old . '/index.php')) @unlink(ROOT . '/' . $old . '/index.php');
            $msg = 'Сайт сақталды ✓ Жаңа сілтеме: ' . $base . $slug . '/';
        }
        // Фото
        if (!empty($_FILES['photo']['name'])) {
            $r = save_upload($_FILES['photo'], $X['slug']);
            if (is_string($r)) { cfg_save($cfg); go_to('tab=site', '⚠️ Фото: ' . $r); }
            foreach (['img', 'img_webp'] as $k) if ($X[$k] && strpos($X[$k], 'up/') === 0) @unlink(ROOT . '/' . $X[$k]);
            $X = array_merge($X, $r);
        } elseif (post('photo_del') === '1') {
            foreach (['img', 'img_webp'] as $k) if ($X[$k] && strpos($X[$k], 'up/') === 0) @unlink(ROOT . '/' . $X[$k]);
            $X['img'] = $X['img_webp'] = ''; $X['img_w'] = $X['img_h'] = 0;
        }
        cfg_save($cfg);
        go_to('tab=site', $msg);
    }

    /* --- Проекттер --- */
    if ($a === 'proj_add') {
        $name = mb_substr(post('name'), 0, 60);
        $slug = strtolower(post('slug'));
        if ($name === '') go_to('tab=projects', '⚠️ Проект атын жазыңыз');
        if (!valid_slug($slug)) go_to('tab=projects', '⚠️ Сілтеме тек латын әріп, сан және «-» болсын (2–30 таңба). Мысалы: tize, kolen, massajer');
        if (proj_index($cfg, 'slug', $slug) >= 0) go_to('tab=projects', '⚠️ Бұл сілтеме бос емес');
        if (is_dir(ROOT . '/' . $slug) && !file_exists(ROOT . '/' . $slug . '/index.php')) go_to('tab=projects', '⚠️ Мұндай папка серверде бар, басқа атау таңдаңыз');
        $max = 0;
        foreach ($cfg['projects'] as $p) $max = max($max, (int)substr($p['id'], 1));
        $np = project_default('p' . ($max + 1), $slug, $name);
        $phone = norm_phone(post('phone'));
        if ($phone) $np['managers'][] = ['id' => 'm1', 'name' => '1-менеджер', 'phone' => $phone, 'active' => true];
        $cfg['projects'][] = $np;
        cfg_save($cfg);
        ensure_stub($slug);
        $_SESSION['p'] = $np['id'];
        go_to('tab=site', 'Жаңа проект ашылды ✓ Енді мәтінді, фотоны және пиксельді баптаңыз');
    }
    if ($a === 'proj_del') {
        if (count($cfg['projects']) < 2) go_to('tab=projects', '⚠️ Соңғы проектті өшіруге болмайды');
        if (post('confirm') !== $P['slug']) go_to('tab=projects', '⚠️ Растау үшін проект сілтемесін дәл жазыңыз: ' . $P['slug']);
        if (file_exists(ROOT . '/' . $P['slug'] . '/index.php')) @unlink(ROOT . '/' . $P['slug'] . '/index.php');
        foreach (['img', 'img_webp'] as $k) if ($P[$k] && strpos($P[$k], 'up/') === 0) @unlink(ROOT . '/' . $P[$k]);
        array_splice($cfg['projects'], $PI, 1);
        cfg_save($cfg);
        unset($_SESSION['p']);
        go_to('tab=projects', 'Проект өшірілді');
    }

    /* --- Wazzup24 --- */
    if ($a === 'wz_connect') {
        $key = preg_replace('/[^A-Za-z0-9_\-]/', '', post('wz_key'));
        if ($key === '') go_to('tab=settings', '⚠️ Wazzup API кілтін жазыңыз');
        list($ch, $err) = wz_channels($key);
        if ($ch === null) go_to('tab=settings', '⚠️ Wazzup кілтті қабылдамады. Кілтті тексеріңіз. (' . mb_substr($err, 0, 120) . ')');
        $hook = $base . 'wz.php?k=' . $cfg['wz_secret'];
        list($code, $j, $err) = wz_api($key, 'PATCH', 'webhooks', ['webhooksUri' => $hook, 'subscriptions' => ['messagesAndStatuses' => true, 'contactsAndDealsCreation' => false]]);
        if ($code !== 200) go_to('tab=settings', '⚠️ Webhook орнатылмады (' . $code . '): ' . mb_substr($err, 0, 160));
        $cfg['wz_key'] = $key; $cfg['wz_channels'] = $ch;
        cfg_save($cfg);
        go_to('tab=settings', 'Wazzup қосылды ✓ Енді клиент жазғанда статистикада көрінеді');
    }
    if ($a === 'wz_refresh' && $cfg['wz_key']) {
        list($ch, $err) = wz_channels($cfg['wz_key']);
        if ($ch === null) go_to('tab=settings', '⚠️ ' . $err);
        $cfg['wz_channels'] = $ch; cfg_save($cfg);
        go_to('tab=settings', 'Каналдар жаңартылды ✓');
    }
    if ($a === 'wz_off') {
        $cfg['wz_key'] = ''; cfg_save($cfg);
        go_to('tab=settings', 'Wazzup ажыратылды');
    }

    /* --- Жалпы баптаулар --- */
    if ($a === 'pass') {
        if (!password_verify(post('old'), $cfg['password'])) go_to('tab=settings', '⚠️ Қазіргі құпиясөз қате');
        if (strlen(post('p1')) < 6 || post('p1') !== post('p2')) go_to('tab=settings', '⚠️ Жаңа құпиясөз кемінде 6 таңба және екеуі бірдей болуы керек');
        $cfg['password'] = password_hash(post('p1'), PASSWORD_DEFAULT);
        cfg_save($cfg);
        go_to('tab=settings', 'Құпиясөз өзгертілді ✓');
    }
    if ($a === 'reset' && post('confirm') === 'ИӘ') {
        purge_events($P['id']);
        go_to('tab=settings', '«' . $P['name'] . '» статистикасы тазаланды');
    }
    go_to();
}

$flash = isset($_SESSION['flash']) ? $_SESSION['flash'] : '';
unset($_SESSION['flash']);
$tab = isset($_GET['tab']) ? $_GET['tab'] : 'stats';
if (!in_array($tab, ['stats', 'managers', 'site', 'projects', 'settings'], true)) $tab = 'stats';

head_html('Админ — ' . $P['name']);
?>
<div class="top"><div class="top-in">
  <div class="psw">
    <span class="brand"><i></i></span>
    <form method="get"><input type="hidden" name="tab" value="<?= h($tab) ?>">
      <select name="p" onchange="this.form.submit()" aria-label="Проект">
        <?php foreach ($cfg['projects'] as $p): ?><option value="<?= h($p['id']) ?>" <?= $p['id'] === $P['id'] ? 'selected' : '' ?>><?= h($p['name']) ?></option><?php endforeach; ?>
      </select>
    </form>
    <a class="btn sm gray" style="text-decoration:none" href="?tab=projects">＋ Проект</a>
  </div>
  <nav class="tabs">
    <a href="?tab=stats" class="<?= $tab === 'stats' ? 'on' : '' ?>">📊 Аналитика</a>
    <a href="?tab=managers" class="<?= $tab === 'managers' ? 'on' : '' ?>">👥 Менеджерлер</a>
    <a href="?tab=site" class="<?= $tab === 'site' ? 'on' : '' ?>">✏️ Сайт</a>
    <a href="?tab=projects" class="<?= $tab === 'projects' ? 'on' : '' ?>">📁 Проекттер</a>
    <a href="?tab=settings" class="<?= $tab === 'settings' ? 'on' : '' ?>">⚙️</a>
    <a href="<?= h($land) ?>" target="_blank">🌐</a>
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
    elseif ($r === 'all') { $from = day_start(first_event_ts($P['id'])); $rangeLabel = 'Барлық уақыт'; }
    elseif ($r === 'custom') {
        $df = isset($_GET['from']) ? $_GET['from'] : ''; $dt = isset($_GET['to']) ? $_GET['to'] : '';
        if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $df) && preg_match('/^\d{4}-\d{2}-\d{2}$/', $dt)) {
            $from = strtotime($df . ' 00:00:00 UTC') - TZ;
            $to = strtotime($dt . ' 00:00:00 UTC') - TZ + 86399;
            if ($to < $from) { $x = $from; $from = $to - 86399; $to = $x + 86399; }
            $rangeLabel = ldate('d.m.Y', $from) . ' — ' . ldate('d.m.Y', $to);
        } else $r = 'today';
    }

    $ev = load_events($from, $to, $P['id']);
    $visits = 0; $clicks = 0; $uv = []; $uc = [];
    $byM = []; $byS = []; $byD = []; $byC = []; $recent = [];
    $leads = 0; $leadList = []; $rsAll = [];
    $WZ = !empty($cfg['wz_key']);
    $mInit = function ($n) { return ['n' => $n, 'c' => 0, 'ov' => 0, 'oc' => 0, 'w' => 0, 'rs' => []]; };
    $hourly = ($to - $from) <= 86400;
    $buckets = [];
    if ($hourly) { for ($i = 0; $i < 24; $i++) $buckets[$i] = [sprintf('%02d', $i), 0, 0, 0]; }
    else { for ($d = day_start($from); $d <= $to; $d += 86400) $buckets[ldate('Y-m-d', $d)] = [ldate('d.m', $d), 0, 0, 0]; }

    foreach ($ev as $e) {
        // Wazzup: клиент шынымен жазды / менеджер жауап берді
        if ($e['e'] === 'w' || $e['e'] === 'r') {
            $mid = !empty($e['m']) ? $e['m'] : '—';
            if (!isset($byM[$mid])) $byM[$mid] = $mInit(isset($e['mn']) ? $e['mn'] : '—');
            if ($e['e'] === 'w') {
                $leads++; $byM[$mid]['w']++; $leadList[] = $e;
                $bk = $hourly ? (int)ldate('G', $e['t']) : ldate('Y-m-d', $e['t']);
                if (isset($buckets[$bk])) $buckets[$bk][3]++;
            } else { $rsAll[] = (int)$e['rs']; $byM[$mid]['rs'][] = (int)$e['rs']; }
            continue;
        }
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
            if (!isset($byM[$mid])) $byM[$mid] = $mInit(isset($e['mn']) ? $e['mn'] : '—');
            $byM[$mid]['c']++;
            if (!empty($e['o'])) $byM[$mid]['oc']++;
            $byS[$s][1]++; $byD[$dv][1]++;
            if (!empty($e['c'])) { if (!isset($byC[$e['c']])) $byC[$e['c']] = [0, 0]; $byC[$e['c']][1]++; }
            if (isset($buckets[$bk])) $buckets[$bk][2]++;
            $recent[] = $e;
        } else {
            $visits++; $uv[$vk] = 1;
            if (!empty($e['m'])) {
                if (!isset($byM[$e['m']])) $byM[$e['m']] = $mInit($e['m']);
                $byM[$e['m']]['ov']++;
            }
            $byS[$s][0]++; $byD[$dv][0]++;
            if (!empty($e['c'])) { if (!isset($byC[$e['c']])) $byC[$e['c']] = [0, 0]; $byC[$e['c']][0]++; }
            if (isset($buckets[$bk])) $buckets[$bk][1]++;
        }
    }
    foreach ($P['managers'] as $m) if (isset($byM[$m['id']])) $byM[$m['id']]['n'] = $m['name'];
    uasort($byM, function ($a, $b) { return $b['c'] - $a['c']; });
    uasort($byS, function ($a, $b) { return ($b[0] + $b[1]) - ($a[0] + $a[1]); });
    uasort($byD, function ($a, $b) { return ($b[0] + $b[1]) - ($a[0] + $a[1]); });
    uasort($byC, function ($a, $b) { return ($b[0] + $b[1]) - ($a[0] + $a[1]); });
    $recent = array_reverse(array_slice($recent, -30));
    $conv = count($uv) ? round(count($uc) / count($uv) * 100, 1) : 0;
    // Wazzup көрсеткіштері
    $chats = ($WZ || $leads) ? wz_chats() : [];
    $noReply = 0;
    foreach ($leadList as $e) if (!empty($e['ck']) && empty($chats[$e['ck']]['r'])) $noReply++;
    $avgRs = $rsAll ? (int)round(array_sum($rsAll) / count($rsAll)) : null;
    $leadList = array_reverse(array_slice($leadList, -50));
    $dur = function ($sec) {
        if ($sec === null) return '—';
        if ($sec < 60) return $sec . ' сек';
        if ($sec < 3600) return round($sec / 60) . ' мин';
        return floor($sec / 3600) . ' сағ ' . round(($sec % 3600) / 60) . ' мин';
    };
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
<?php if ($WZ || $leads): ?>
  <div class="grid kpis" style="margin-bottom:16px">
    <div class="kpi hi" style="background:linear-gradient(135deg,#15803d,#166534)"><div class="l">✍️ Нақты жазғандар</div><div class="v"><?= $leads ?></div></div>
    <div class="kpi"><div class="l">Басқандардың жазғаны</div><div class="v"><?= count($uc) ? min(100, round($leads / count($uc) * 100)) . '%' : '—' ?></div></div>
    <div class="kpi"><div class="l">Орташа жауап уақыты</div><div class="v" style="font-size:22px"><?= h($dur($avgRs)) ?></div></div>
    <div class="kpi" <?= $noReply ? 'style="background:#fee2e2;border-color:#fecaca"' : '' ?>><div class="l">Жауапсыз қалғандар</div><div class="v" <?= $noReply ? 'style="color:#dc2626"' : '' ?>><?= $noReply ?></div></div>
  </div>
<?php endif; ?>

  <div class="card">
    <h2><?= h($P['name']) ?> · <?= h($rangeLabel) ?> — <?= $hourly ? 'сағат бойынша' : 'күн бойынша' ?></h2>
    <div class="legend"><span><i style="background:#bbf7d0"></i>Кірулер</span><span><i style="background:#4ade80"></i>WhatsApp басулар</span><?php if ($WZ || $leads): ?><span><i style="background:#166534"></i>Нақты жазғандар</span><?php endif; ?></div>
    <?php
    $n = count($buckets); $max = 1;
    foreach ($buckets as $b) $max = max($max, $b[1], $b[2], $b[3]);
    $nb = ($WZ || $leads) ? 3 : 2;
    $H = 170; $pad = 22; $w = max($n * 26, 300);
    $every = max(1, (int)ceil($n / 15));
    echo '<svg class="chart" viewBox="0 0 ' . $w . ' ' . ($H + $pad) . '" preserveAspectRatio="none" style="max-height:260px">';
    for ($g = 0; $g <= 4; $g++) { $y = $H - $H * $g / 4; echo '<line x1="0" x2="' . $w . '" y1="' . $y . '" y2="' . $y . '" stroke="#eef2f6"/>'; }
    $i = 0;
    foreach ($buckets as $b) {
        $x = $i * ($w / $n) + 3; $bw = ($w / $n - 6) / $nb;
        $h1 = $b[1] / $max * ($H - 10); $h2 = $b[2] / $max * ($H - 10); $h3 = $b[3] / $max * ($H - 10);
        echo '<g><title>' . h($b[0]) . ': кіру ' . $b[1] . ', басу ' . $b[2] . ($nb === 3 ? ', жазды ' . $b[3] : '') . '</title>';
        if ($nb === 3) echo '<rect x="' . round($x + 2 * $bw, 1) . '" y="' . round($H - $h3, 1) . '" width="' . round($bw, 1) . '" height="' . round($h3, 1) . '" rx="2" fill="#166534"/>';
        echo '<rect x="' . round($x, 1) . '" y="' . round($H - $h1, 1) . '" width="' . round($bw, 1) . '" height="' . round($h1, 1) . '" rx="2" fill="#bbf7d0"/>';
        echo '<rect x="' . round($x + $bw, 1) . '" y="' . round($H - $h2, 1) . '" width="' . round($bw, 1) . '" height="' . round($h2, 1) . '" rx="2" fill="#4ade80"/></g>';
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
      <div style="overflow-x:auto"><table><tr><th>Менеджер</th><th class="n">Басу</th><?php if ($WZ || $leads): ?><th class="n">Жазды</th><th class="n">Жауап</th><?php endif; ?><th class="n">Жеке сілт.</th></tr>
      <?php foreach ($byM as $m): $pc = $clicks ? round($m['c'] / $clicks * 100) : 0; ?>
        <tr><td><?= h($m['n']) ?><div class="bar"><b style="width:<?= $pc ?>%"></b></div></td><td class="n"><b><?= $m['c'] ?></b> <span class="muted"><?= $pc ?>%</span></td><?php if ($WZ || $leads): ?><td class="n"><b><?= $m['w'] ?></b></td><td class="n"><?= h($dur($m['rs'] ? (int)round(array_sum($m['rs']) / count($m['rs'])) : null)) ?></td><?php endif; ?><td class="n"><?= $m['ov'] || $m['oc'] ? $m['ov'] . ' / <b>' . $m['oc'] . '</b>' : '<span class="muted">—</span>' ?></td></tr>
      <?php endforeach; ?></table></div>
      <p class="muted" style="margin-top:8px"><?php if ($WZ || $leads): ?>«Жазды» — WhatsApp-қа шынымен жазған клиенттер (Wazzup), «Жауап» — менеджердің орташа жауап уақыты. <?php endif; ?>«Жеке сілтеме»: менеджердің өз сілтемесімен кірген / WhatsApp басқан адамдар.</p><?php endif; ?>
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

<?php if ($WZ || $leads): ?>
  <div class="card">
    <h2>✍️ WhatsApp-қа жазған клиенттер</h2>
    <?php if (!$leadList): ?><p class="muted">Бұл кезеңде әлі ешкім жазған жоқ</p><?php else: ?>
    <div style="overflow-x:auto"><table><tr><th>Уақыт</th><th>Клиент</th><th>Менеджер</th><th>Жауап</th></tr>
    <?php foreach ($leadList as $e): $mn = isset($e['mn']) ? $e['mn'] : '—';
        foreach ($P['managers'] as $m) if (isset($e['m']) && $m['id'] === $e['m']) $mn = $m['name'];
        $ch = !empty($e['ck']) && isset($chats[$e['ck']]) ? $chats[$e['ck']] : null; ?>
      <tr><td><?= ldate('d.m H:i', $e['t']) ?></td>
        <td><a href="https://wa.me/<?= h($e['cl']) ?>" target="_blank">+<?= h($e['cl']) ?></a><?php if (!empty($e['tx'])): ?><br><span class="muted"><?= h($e['tx']) ?></span><?php endif; ?></td>
        <td><?= h($mn) ?></td>
        <td><?= $ch && !empty($ch['r']) ? h($dur($ch['r'] - $ch['t'])) : '<span class="pill" style="background:#fee2e2;color:#dc2626">жауапсыз</span>' ?></td></tr>
    <?php endforeach; ?></table></div><?php endif; ?>
  </div>
<?php endif; ?>

  <div class="card">
    <h2>🕒 Соңғы басулар</h2>
    <?php if (!$recent): ?><p class="muted">Бұл кезеңде басулар әлі жоқ</p><?php else: ?>
    <div style="overflow-x:auto"><table><tr><th>Уақыт</th><th>Менеджер</th><th>Дереккөз</th><th>Құрылғы</th></tr>
    <?php foreach ($recent as $e): $mn = isset($e['mn']) ? $e['mn'] : '—';
        foreach ($P['managers'] as $m) if (isset($e['m']) && $m['id'] === $e['m']) $mn = $m['name']; ?>
      <tr><td><?= ldate('d.m H:i', $e['t']) ?></td><td><?= h($mn) ?></td><td><?= h($sn(!empty($e['s']) ? $e['s'] : 'direct')) ?><?= !empty($e['c']) ? ' <span class="muted">· ' . h($e['c']) . '</span>' : '' ?></td><td><?= h(isset($e['d']) ? $e['d'] : '') ?></td></tr>
    <?php endforeach; ?></table></div><?php endif; ?>
  </div>

<?php
/* ================= МЕНЕДЖЕРЛЕР ================= */
elseif ($tab === 'managers'):
    $todayCl = [];
    foreach (load_events(day_start(time()), time(), $P['id']) as $e) if ($e['e'] === 'c' && !empty($e['m'])) $todayCl[$e['m']] = (isset($todayCl[$e['m']]) ? $todayCl[$e['m']] : 0) + 1;
    $activeCount = 0; foreach ($P['managers'] as $m) if (!empty($m['active'])) $activeCount++;
?>
  <div class="card">
    <h2>➕ «<?= h($P['name']) ?>» проектіне менеджер қосу</h2>
    <form method="post" class="row">
      <?= csrf_field() ?><input type="hidden" name="a" value="add">
      <input type="text" name="name" placeholder="<?= count($P['managers']) + 1 ?>-менеджер (аты)" style="flex:1;min-width:160px" maxlength="40">
      <input type="tel" name="phone" placeholder="WhatsApp нөмірі: 87051234567" required style="flex:1;min-width:180px">
      <button class="btn">Қосу</button>
    </form>
  </div>

  <div class="card">
    <h2>👥 Менеджерлер тізімі</h2>
    <p class="muted" style="margin-bottom:6px">
      Өтінімдер <b>белсенді</b> менеджерлерге <?= $P['mode'] === 'random' ? '<b>кездейсоқ</b>' : '<b>кезекпен</b> (1 → 2 → 3 → 1 …)' ?> таратылады.
      Қазір белсенді: <b><?= $activeCount ?></b>. Бір клиент қайта басса — сол менеджерге түседі.
    </p>
    <p class="muted" style="margin-bottom:6px">🔗 <b>Жеке сілтеме</b> — сол сілтемемен кірген клиент кезекке түспей, <b>тікелей сол менеджерге</b> жазады.</p>
    <?php if (!$P['managers']): ?><p class="flash err">Менеджер жоқ — сайттағы батырма жұмыс істемейді. Жоғарыдан қосыңыз.</p><?php endif; ?>
    <?php foreach ($P['managers'] as $i => $m): $on = !empty($m['active']); $fid = 'f' . h($m['id']); ?>
      <div class="mg <?= $on ? '' : 'off' ?>">
        <div class="num"><?= $i + 1 ?></div>
        <input type="text" name="name" value="<?= h($m['name']) ?>" form="<?= $fid ?>" maxlength="40">
        <input type="tel" name="phone" value="<?= h($m['phone']) ?>" form="<?= $fid ?>">
        <div class="acts">
          <span class="pill <?= $on ? 'on' : 'off' ?>"><?= $on ? 'Белсенді' : 'Тоқтатылған' ?></span>
          <span class="muted">бүгін: <b><?= isset($todayCl[$m['id']]) ? $todayCl[$m['id']] : 0 ?></b></span>
          <form method="post" id="<?= $fid ?>"><?= csrf_field() ?><input type="hidden" name="a" value="save"><input type="hidden" name="id" value="<?= h($m['id']) ?>"><button class="btn sm">Сақтау</button></form>
          <form method="post"><?= csrf_field() ?><input type="hidden" name="a" value="toggle"><input type="hidden" name="id" value="<?= h($m['id']) ?>"><button class="btn sm gray"><?= $on ? '⏸ Тоқтату' : '▶ Қосу' ?></button></form>
          <form method="post"><?= csrf_field() ?><input type="hidden" name="a" value="up"><input type="hidden" name="id" value="<?= h($m['id']) ?>"><button class="btn sm gray" title="Жоғары">↑</button></form>
          <form method="post"><?= csrf_field() ?><input type="hidden" name="a" value="down"><input type="hidden" name="id" value="<?= h($m['id']) ?>"><button class="btn sm gray" title="Төмен">↓</button></form>
          <a class="btn sm gray" style="text-decoration:none" target="_blank" rel="noopener" href="https://wa.me/<?= h($m['phone']) ?>">WA</a>
          <form method="post" onsubmit="return confirm('<?= h($m['name']) ?> өшірілсін бе?')"><?= csrf_field() ?><input type="hidden" name="a" value="del"><input type="hidden" name="id" value="<?= h($m['id']) ?>"><button class="btn sm red">Өшіру</button></form>
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
/* ================= САЙТ (мәтін, фото, пиксель) ================= */
elseif ($tab === 'site'): ?>
  <div class="card">
    <h2>✏️ «<?= h($P['name']) ?>» сайты</h2>
    <div class="mlink" style="margin-bottom:6px">
      <span class="muted">Сайт сілтемесі:</span>
      <input type="text" readonly value="<?= h($land) ?>" onclick="this.select()">
      <button type="button" class="btn sm gray" onclick="cp(this)">Көшіру</button>
      <a class="btn sm" style="text-decoration:none" href="<?= h($land) ?>" target="_blank">Ашу</a>
    </div>
    <form method="post" enctype="multipart/form-data">
      <?= csrf_field() ?><input type="hidden" name="a" value="site">

      <h3 class="sec">Негізгі</h3>
      <label>Проект аты (тек админде көрінеді)</label>
      <input type="text" name="name" value="<?= h($P['name']) ?>" maxlength="60" required>
      <label>Сілтеме: <?= h($base) ?><b>…</b>/</label>
      <input type="text" name="slug" value="<?= h($P['slug']) ?>" maxlength="30" pattern="[a-z0-9][a-z0-9\-]{1,29}" required>
      <p class="muted">Тек латын әріп, сан және «-». Өзгертсеңіз, ескі сілтеме жұмыс істемей қалады — жарнамадағы сілтемені де ауыстырыңыз.</p>
      <label class="radio"><input type="checkbox" name="active" value="1" <?= $P['active'] ? 'checked' : '' ?>> <span>Сайт қосулы (белгіні алсаңыз, сайт уақытша жабылады)</span></label>

      <h3 class="sec">Сайттағы мәтіндер</h3>
      <label>Жоғарғы жазу (жасыл)</label>
      <input type="text" name="top" value="<?= h($P['top']) ?>" maxlength="120" placeholder="Мысалы: БЕЛ ЖӘНЕ ОМЫРТҚА АУРУЛАРЫН ЕМДЕЙДІ">
      <label>Тақырып (қара)</label>
      <input type="text" name="title" value="<?= h($P['title']) ?>" maxlength="200">
      <label>Тақырыптың жасыл бөлігі</label>
      <input type="text" name="title_hl" value="<?= h($P['title_hl']) ?>" maxlength="100">
      <label>Батырма жазуы</label>
      <input type="text" name="button" value="<?= h($P['button']) ?>" maxlength="40">
      <label>Батырма астындағы жазу</label>
      <input type="text" name="hint" value="<?= h($P['hint']) ?>" maxlength="120" placeholder="Бос қалдырсаңыз, көрінбейді">
      <p class="muted">Кез келген жазуды бос қалдырсаңыз, сайтта көрінбейді.</p>

      <h3 class="sec">Фото</h3>
      <div class="preview">
        <?php if ($P['img']): ?><img src="<?= h($P['img']) ?>" alt=""><?php else: ?><span class="muted">Фото жоқ</span><?php endif; ?>
        <div>
          <input type="file" name="photo" accept="image/jpeg,image/png,image/webp">
          <p class="muted">JPG, PNG немесе WEBP. Сурет өзі ортасынан кесіліп, <b>540×560</b> өлшемге келтіріледі және жылдам ашылу үшін сығылады (Saumed-тағыдай).</p>
          <?php if ($P['img']): ?><label class="radio"><input type="checkbox" name="photo_del" value="1"> <span>Фотоны алып тастау</span></label><?php endif; ?>
        </div>
      </div>

      <h3 class="sec">WhatsApp</h3>
      <label>Клиент WhatsApp ашқанда автоматты жазылатын мәтін</label>
      <textarea name="message" maxlength="500"><?= h($P['message']) ?></textarea>
      <label>Өтінімдерді тарату тәсілі</label>
      <label class="radio"><input type="radio" name="mode" value="rr" <?= $P['mode'] !== 'random' ? 'checked' : '' ?>> <span><b>Кезекпен</b> — 1, 2, 3, қайтадан 1…</span></label>
      <label class="radio"><input type="radio" name="mode" value="random" <?= $P['mode'] === 'random' ? 'checked' : '' ?>> <span><b>Кездейсоқ</b></span></label>

      <h3 class="sec">📈 Пиксельдер (тек осы проектке)</h3>
      <label>TikTok Pixel ID</label>
      <input type="text" name="tt_ids" value="<?= h($P['tt_ids']) ?>" placeholder="Бірнешеу болса — үтірмен" autocomplete="off">
      <label>TikTok: батырма басылғандағы оқиға</label>
      <select name="tt_event">
        <?php foreach (['ClickButton' => 'ClickButton — батырманы басу', 'Contact' => 'Contact — байланыс', 'SubmitForm' => 'SubmitForm — өтінім жіберу', 'Lead' => 'Lead — лид', 'CompleteRegistration' => 'CompleteRegistration — тіркелу'] as $k => $v): ?>
          <option value="<?= $k ?>" <?= $P['tt_event'] === $k ? 'selected' : '' ?>><?= h($v) ?></option>
        <?php endforeach; ?>
      </select>
      <label>Meta (Facebook / Instagram) Pixel ID</label>
      <input type="text" name="fb_ids" value="<?= h($P['fb_ids']) ?>" placeholder="Мысалы: 1234567890123456" inputmode="numeric" autocomplete="off">
      <label>Meta: батырма басылғандағы оқиға</label>
      <select name="fb_event">
        <?php foreach (['Lead' => 'Lead — лид', 'Contact' => 'Contact — байланыс', 'SubmitApplication' => 'SubmitApplication — өтінім', 'CompleteRegistration' => 'CompleteRegistration — тіркелу'] as $k => $v): ?>
          <option value="<?= $k ?>" <?= $P['fb_event'] === $k ? 'selected' : '' ?>><?= h($v) ?></option>
        <?php endforeach; ?>
      </select>

      <button class="btn" style="margin-top:18px;width:100%;min-height:48px">💾 Барлығын сақтау</button>
    </form>
  </div>

  <div class="card">
    <h2>🔗 Жарнамаға арналған сілтемелер</h2>
    <p class="muted">Әр жарнамаға осындай сілтеме қойсаңыз, аналитикада қайдан келгені көрінеді:</p>
    <p style="margin-top:8px"><code><?= h($land) ?>?utm_source=tiktok&amp;utm_campaign=jarnama1</code></p>
    <p style="margin-top:6px"><code><?= h($land) ?>?utm_source=instagram&amp;utm_campaign=reels1</code></p>
  </div>

<?php
/* ================= ПРОЕКТТЕР ================= */
elseif ($tab === 'projects'):
    $t0 = day_start(time()); $today = load_events($t0, time());
    $stat = [];
    foreach ($today as $e) { $k = ev_pid($e); if (!isset($stat[$k])) $stat[$k] = [0, 0, 0]; $ix = ['v' => 0, 'c' => 1, 'w' => 2]; if (isset($ix[$e['e']])) $stat[$k][$ix[$e['e']]]++; }
?>
  <div class="card">
    <h2>📁 Проекттер</h2>
    <?php foreach ($cfg['projects'] as $p): $s = isset($stat[$p['id']]) ? $stat[$p['id']] : [0, 0, 0]; $mc = 0; foreach ($p['managers'] as $m) if (!empty($m['active'])) $mc++; ?>
      <div class="pj <?= $p['id'] === $P['id'] ? 'cur' : '' ?>">
        <div>
          <b><?= h($p['name']) ?></b> <?= $p['active'] ? '' : '<span class="pill off">жабық</span>' ?><br>
          <a class="muted" href="<?= h($base . $p['slug'] . '/') ?>" target="_blank"><?= h($base . $p['slug'] . '/') ?></a><br>
          <span class="muted">Бүгін: кіру <b><?= $s[0] ?></b> · басу <b><?= $s[1] ?></b><?= $cfg['wz_key'] ? ' · жазды <b>' . $s[2] . '</b>' : '' ?> · белсенді менеджер: <b><?= $mc ?></b></span>
        </div>
        <a class="btn sm <?= $p['id'] === $P['id'] ? 'gray' : '' ?>" style="text-decoration:none" href="?p=<?= h($p['id']) ?>&tab=site"><?= $p['id'] === $P['id'] ? 'Ашық' : 'Ашу' ?></a>
      </div>
    <?php endforeach; ?>
  </div>

  <div class="card">
    <h2>➕ Жаңа проект ашу</h2>
    <form method="post">
      <?= csrf_field() ?><input type="hidden" name="a" value="proj_add">
      <label>Проект аты</label>
      <input type="text" name="name" required maxlength="60" placeholder="Мысалы: Тізе бандажы">
      <label>Сілтеме: <?= h($base) ?><b>…</b>/</label>
      <input type="text" name="slug" required maxlength="30" pattern="[a-z0-9][a-z0-9\-]{1,29}" placeholder="Мысалы: tize">
      <label>Бірінші менеджердің WhatsApp нөмірі (кейін тағы қосасыз)</label>
      <input type="tel" name="phone" placeholder="87051234567">
      <button class="btn" style="margin-top:12px">Проект ашу</button>
    </form>
  </div>

  <?php if (count($cfg['projects']) > 1): ?>
  <div class="card">
    <h2>🗑 «<?= h($P['name']) ?>» проектін өшіру</h2>
    <p class="muted">Сайт жабылады, баптаулары өшеді. Статистикасы файлда қалады.</p>
    <form method="post" class="row" style="margin-top:10px" onsubmit="return confirm('Проект өшірілсін бе?')">
      <?= csrf_field() ?><input type="hidden" name="a" value="proj_del">
      <input type="text" name="confirm" placeholder="Растау үшін жазыңыз: <?= h($P['slug']) ?>" style="max-width:280px" required>
      <button class="btn red">Өшіру</button>
    </form>
  </div>
  <?php endif; ?>

<?php
/* ================= ЖАЛПЫ БАПТАУЛАР ================= */
else: ?>
  <div class="card">
    <h2>💬 Wazzup24 — нақты неше адам жазғанын көру</h2>
    <?php if (!$cfg['wz_key']): ?>
      <p class="muted">Wazzup қосылса, статистикада клиенттің WhatsApp-қа <b>шынымен жазғаны</b> (батырманы басқаны ғана емес), оның нөмірі және менеджердің қанша уақытта жауап бергені көрінеді.</p>
      <p class="muted" style="margin-top:8px">API кілтті алу: Wazzup → <b>Интеграции</b> (Настройки) → <b>API</b> → кілтті көшіріңіз.</p>
      <form method="post" class="row" style="margin-top:10px">
        <?= csrf_field() ?><input type="hidden" name="a" value="wz_connect">
        <input type="text" name="wz_key" placeholder="Wazzup API кілті" autocomplete="off" style="flex:1;min-width:220px" required>
        <button class="btn">Қосу</button>
      </form>
    <?php else: ?>
      <p><span class="pill on">Қосулы</span> Клиент жазғанда статистикаға автоматты түседі.</p>
      <h3 class="sec">Wazzup-тағы WhatsApp нөмірлер</h3>
      <?php
        $allM = [];
        foreach ($cfg['projects'] as $pp) foreach ($pp['managers'] as $mm) $allM[$mm['phone']][] = $pp['name'] . ' → ' . $mm['name'];
      ?>
      <table><tr><th>Нөмір</th><th>Күйі</th><th>Менеджер</th></tr>
      <?php foreach ($cfg['wz_channels'] as $ch): if (!in_array($ch['transport'], ['whatsapp', 'wapi'], true)) continue; ?>
        <tr><td>+<?= h($ch['phone']) ?></td><td><?= $ch['state'] === 'active' ? '<span class="pill on">active</span>' : '<span class="pill off">' . h($ch['state']) . '</span>' ?></td>
        <td><?= isset($allM[$ch['phone']]) ? h(implode(', ', $allM[$ch['phone']])) : '<span class="muted">Есептелмейді — менеджерлерде жоқ</span>' ?></td></tr>
      <?php endforeach; ?></table>
      <p class="muted" style="margin-top:8px">Тек <b>сіз тіркеген менеджерлердің нөмірлері</b> есептеледі, Wazzup-тағы басқа нөмірлер статистикаға кірмейді. Менеджер нөмірі Wazzup-тағы нөмірмен бірдей болуы керек. Бір нөмір бірнеше проектте болса, проект клиенттің автоматты мәтіні бойынша анықталады.</p>
      <div class="row" style="margin-top:10px">
        <form method="post"><?= csrf_field() ?><input type="hidden" name="a" value="wz_refresh"><button class="btn sm gray">🔄 Нөмірлерді жаңарту</button></form>
        <form method="post" onsubmit="return confirm('Wazzup ажыратылсын ба?')"><?= csrf_field() ?><input type="hidden" name="a" value="wz_off"><button class="btn sm red">Ажырату</button></form>
      </div>
    <?php endif; ?>
  </div>

  <div class="card">
    <h2>🔒 Құпиясөзді өзгерту</h2>
    <form method="post" style="max-width:380px">
      <?= csrf_field() ?><input type="hidden" name="a" value="pass">
      <label>Қазіргі құпиясөз</label><input type="password" name="old" required autocomplete="current-password">
      <label>Жаңа құпиясөз</label><input type="password" name="p1" required minlength="6" autocomplete="new-password">
      <label>Жаңа құпиясөзді қайталаңыз</label><input type="password" name="p2" required minlength="6" autocomplete="new-password">
      <button class="btn" style="margin-top:12px">Өзгерту</button>
    </form>
  </div>

  <div class="card">
    <h2>🗑 «<?= h($P['name']) ?>» статистикасын тазалау</h2>
    <p class="muted">Тек осы проекттің кірулері мен басулары өшіріледі. Басқа проекттер, менеджерлер мен баптаулар сақталады.</p>
    <form method="post" class="row" style="margin-top:10px" onsubmit="return confirm('Статистика өшірілсін бе?')">
      <?= csrf_field() ?><input type="hidden" name="a" value="reset">
      <input type="text" name="confirm" placeholder="Растау үшін ИӘ деп жазыңыз" style="max-width:260px" required>
      <button class="btn red">Тазалау</button>
    </form>
  </div>
<?php endif; ?>
</div>
<?php foot_html();
