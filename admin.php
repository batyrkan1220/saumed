<?php
define('APP', 1);
require __DIR__ . '/lib.php';
nocache();
header('X-Robots-Tag: noindex, nofollow');

session_name('adm');
ini_set('session.gc_maxlifetime', (string)(30 * 86400));
session_set_cookie_params(30 * 86400, '/', '', !empty($_SERVER['HTTPS']), true);
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
<html lang="ru">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="robots" content="noindex, nofollow">
<title><?= h($title) ?></title>
<link rel="icon" href="img/favicon.svg" type="image/svg+xml">
<link rel="icon" href="img/favicon-32.png" sizes="32x32" type="image/png">
<link rel="apple-touch-icon" href="img/apple-touch-icon.png">
<link rel="manifest" href="manifest.webmanifest">
<meta name="theme-color" content="#16a34a">
<meta name="apple-mobile-web-app-capable" content="yes">
<meta name="mobile-web-app-capable" content="yes">
<meta name="apple-mobile-web-app-title" content="Taplinker">
<meta name="apple-mobile-web-app-status-bar-style" content="default">
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
.chatwrap{display:grid;grid-template-columns:340px 1fr;gap:12px;align-items:start}
.chatwrap .card{margin:0}
.clist{max-height:78vh;overflow-y:auto;padding:12px}
.ci{display:flex;gap:10px;padding:10px 8px;border-radius:12px;text-decoration:none;color:inherit;border-bottom:1px solid #f1f5f9}
.ci:hover{background:#f8fafc}.ci.cur{background:#dcfce7}
.av{width:40px;height:40px;border-radius:50%;background:#e2e8f0;color:#475569;font-weight:800;display:flex;align-items:center;justify-content:center;flex-shrink:0}
.cb{flex:1;min-width:0}
.ct{display:flex;justify-content:space-between;gap:8px}.ct b{overflow:hidden;text-overflow:ellipsis;white-space:nowrap}.ct span{font-size:12px;color:var(--m);white-space:nowrap}
.cp{font-size:13px;color:#334155;overflow:hidden;text-overflow:ellipsis;white-space:nowrap}
.cm{font-size:12px;color:var(--m);margin-top:2px}
.cview{padding:0;overflow:hidden}
.chd{display:flex;align-items:center;gap:10px;padding:12px 14px;border-bottom:1px solid var(--b);background:#fff}
.chd .back{display:none}
.msgs{height:62vh;overflow-y:auto;padding:14px;background:#efeae2;display:flex;flex-direction:column;gap:6px}
.cview>.muted{padding:0 14px 12px}
.bub{max-width:78%;padding:7px 10px 5px;border-radius:10px;font-size:14.5px;line-height:1.4;box-shadow:0 1px 1px rgba(0,0,0,.08);word-wrap:break-word}
.bub.in{align-self:flex-start;background:#fff;border-top-left-radius:2px}
.bub.out{align-self:flex-end;background:#d9fdd3;border-top-right-radius:2px}
.bub img{max-width:240px;max-height:240px;border-radius:8px;display:block}
.bub .tm{font-size:11px;color:#667781;text-align:right;margin-top:2px}
.bub .au{font-size:12px;font-weight:700;color:#0d7a37}
.day{align-self:center;background:#fff;border-radius:8px;padding:3px 10px;font-size:12px;color:#54656f;margin:6px 0}
@media(max-width:820px){.chatwrap{grid-template-columns:1fr}.chatwrap.has-sel .clist{display:none}.chatwrap:not(.has-sel) .cview{display:none}.chd .back{display:inline-block}.msgs{height:66vh}}
.live{display:inline-flex;align-items:center;gap:6px;font-size:12px;font-weight:800;letter-spacing:.5px;padding:5px 10px;border-radius:999px;text-decoration:none;background:#f1f5f9;color:#94a3b8}
.live i{width:8px;height:8px;border-radius:50%;background:#94a3b8;display:inline-block}
.live.on{background:#fee2e2;color:#dc2626}.live.on i{background:#dc2626;animation:lv 1.4s infinite}
@keyframes lv{0%{box-shadow:0 0 0 0 rgba(220,38,38,.6)}70%{box-shadow:0 0 0 7px rgba(220,38,38,0)}100%{box-shadow:0 0 0 0 rgba(220,38,38,0)}}
.toast{position:fixed;left:50%;bottom:20px;transform:translateX(-50%) translateY(140%);background:#0f172a;color:#fff;padding:12px 18px;border-radius:14px;font-weight:700;z-index:50;transition:transform .3s;box-shadow:0 10px 30px rgba(0,0,0,.25);max-width:92vw}
.toast.show{transform:translateX(-50%) translateY(0)}
.fun{display:flex;align-items:stretch;gap:6px;flex-wrap:wrap}
.fun .st{flex:1;min-width:130px;background:#f8fafc;border:1px solid var(--b);border-radius:14px;padding:12px 14px}
.fun .st.hi{background:linear-gradient(135deg,#15803d,#166534);color:#fff;border:0}
.fun .st .l{font-size:13px;font-weight:700;color:var(--m)}.fun .st.hi .l,.fun .st.hi .s{color:#dcfce7}
.fun .st .v{font-size:30px;font-weight:900;letter-spacing:-.5px;line-height:1.2}
.fun .st .s{font-size:12px;color:var(--m)}
.fun .ar{display:flex;flex-direction:column;align-items:center;justify-content:center;color:#94a3b8;font-size:18px;min-width:34px}
.fun .ar b{font-size:12px;color:#16a34a}
@media(max-width:700px){.fun .st{min-width:calc(50% - 30px)}}
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
<div class="toast" id="toast"></div>
<script>
/* LIVE режим: әр 5 секунд сайын өзгерісті тексереді, жаңа дерек келсе — бетті жаңартады */
(function(){
  var btn=document.getElementById('liveBtn'); if(!btn) return;
  var on=true; try{on=localStorage.getItem('live')!=='0';}catch(e){}
  btn.classList.toggle('on',on);
  btn.addEventListener('click',function(e){e.preventDefault();on=!on;btn.classList.toggle('on',on);try{localStorage.setItem('live',on?'1':'0');}catch(e){}});
  var tab=new URLSearchParams(location.search).get('tab')||'stats';
  var sig=null, wait=null, baseTitle=document.title;
  function beep(){try{var a=new (window.AudioContext||window.webkitAudioContext)(),o=a.createOscillator(),g=a.createGain();o.connect(g);g.connect(a.destination);o.frequency.value=880;g.gain.value=.08;o.start();o.stop(a.currentTime+.18);}catch(e){}}
  function toast(t){var el=document.getElementById('toast');el.textContent=t;el.classList.add('show');setTimeout(function(){el.classList.remove('show')},5000);}
  function busy(){var a=document.activeElement;return a&&(a.tagName==='INPUT'||a.tagName==='TEXTAREA'||a.tagName==='SELECT');}
  function check(){
    if(!on||document.hidden) return;
    fetch('admin.php?live=1',{credentials:'same-origin',cache:'no-store'}).then(function(r){return r.json()}).then(function(d){
      document.title=(d.wait?'('+d.wait+') ':'')+baseTitle;
      if(wait!==null && d.wait>wait){ toast('💬 Новое сообщение от клиента'); beep(); }
      var changed = sig!==null && d.sig!==sig;
      sig=d.sig; wait=d.wait;
      if(changed && (tab==='stats'||tab==='chats') && !busy()){ try{sessionStorage.setItem('liveY',String(window.scrollY));}catch(e){} location.reload(); }
    }).catch(function(){});
  }
  try{var y=sessionStorage.getItem('liveY'); if(y!==null){sessionStorage.removeItem('liveY'); window.scrollTo(0,+y);} }catch(e){}
  check(); setInterval(check,5000);
  document.addEventListener('visibilitychange',check);
})();
function cp(b){var i=b.parentNode.querySelector('input');i.select();i.setSelectionRange(0,999);
var ok=function(){var t=b.textContent;b.textContent='Скопировано ✓';setTimeout(function(){b.textContent=t},1500)};
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
        if (strlen($p1) < 6) $err = 'Пароль должен быть не короче 6 символов';
        elseif ($p1 !== $p2) $err = 'Пароли не совпадают';
        else {
            $cfg['password'] = password_hash($p1, PASSWORD_DEFAULT);
            if (!cfg_save($cfg)) $err = 'Нет доступа к папке data. Дайте папке data права 755 или 775 на хостинге.';
            else { session_regenerate_id(true); $_SESSION['ok'] = 1; remember_set($cfg); go_to('tab=managers', 'Пароль установлен! Теперь добавьте менеджеров.'); }
        }
    }
    head_html('Админка — первая настройка'); ?>
    <div class="login"><div class="card">
      <img src="img/logo.svg" alt="Taplinker" width="56" height="56" style="display:block;margin-bottom:12px">
      <h2>Добро пожаловать в админку 👋</h2>
      <p class="muted">Сначала придумайте пароль для входа в админку.</p>
      <?php if ($err): ?><div class="flash err" style="margin-top:12px"><?= h($err) ?></div><?php endif; ?>
      <form method="post">
        <label>Новый пароль</label><input type="password" name="p1" required minlength="6" autocomplete="new-password">
        <label>Повторите пароль</label><input type="password" name="p2" required minlength="6" autocomplete="new-password">
        <button class="btn" style="width:100%;margin-top:14px">Сохранить</button>
      </form>
    </div></div>
    <?php foot_html(); exit;
}

/* ---------------- «Есте сақтау»: 30 күн қайта кірмей қолдану ---------------- */
function remember_token($cfg, $exp) { return hash_hmac('sha256', 'adm|' . $exp, $cfg['password'] . '|' . $cfg['wz_secret']); }
function remember_set($cfg) {
    $exp = time() + 30 * 86400;
    setcookie('adm_auth', $exp . '.' . remember_token($cfg, $exp), $exp, '/', '', !empty($_SERVER['HTTPS']), true);
}
if (empty($_SESSION['ok']) && !empty($_COOKIE['adm_auth']) && $cfg['password'] !== '') {
    $pp = explode('.', $_COOKIE['adm_auth'], 2);
    if (count($pp) === 2 && (int)$pp[0] > time() && hash_equals(remember_token($cfg, (int)$pp[0]), $pp[1])) {
        session_regenerate_id(true); $_SESSION['ok'] = 1;
    }
}

/* ---------------- Шығу / Кіру ---------------- */
if (isset($_GET['logout'])) { setcookie('adm_auth', '', time() - 3600, '/'); $_SESSION = []; session_destroy(); header('Location: admin.php'); exit; }

if (empty($_SESSION['ok'])) {
    $err = '';
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        if (password_verify(post('p'), $cfg['password'])) { session_regenerate_id(true); $_SESSION['ok'] = 1; setcookie('adm_seen', '1', time() + 365 * 86400, '/'); remember_set($cfg); go_to(); }
        sleep(1);
        $err = 'Неверный пароль';
    }
    head_html('Админка — вход'); ?>
    <div class="login"><div class="card">
      <div style="display:flex;align-items:center;gap:12px;margin-bottom:14px"><img src="img/logo.svg" alt="" width="44" height="44"><div><b style="font-size:20px">Taplinker</b><br><span class="muted">Админка</span></div></div>
      <?php if ($err): ?><div class="flash err"><?= h($err) ?></div><?php endif; ?>
      <form method="post">
        <label>Пароль</label><input type="password" name="p" required autofocus autocomplete="current-password">
        <button class="btn" style="width:100%;margin-top:14px">Войти</button>
      </form>
    </div></div>
    <?php foot_html(); exit;
}

ensure_all_stubs($cfg);
if (isset($_GET['live'])) {
    header('Content-Type: application/json');
    $sig = '';
    foreach (['ev', 'msg'] as $pre) { $f = DATA . '/' . $pre . '-' . gmdate('Y-m', time() + TZ) . '.php'; $sig .= is_file($f) ? filesize($f) . '.' : '0.'; }
    $pid = isset($_GET['p']) ? $_GET['p'] : (isset($_SESSION['p']) ? $_SESSION['p'] : '');
    $wait = 0; $last = 0;
    foreach (wz_chats() as $x) if ((isset($x['p']) ? $x['p'] : '') === $pid || $pid === '') {
        if (isset($x['ld']) && $x['ld'] === 'in') { $wait++; $last = max($last, isset($x['last']) ? $x['last'] : 0); }
    }
    echo json_encode(['sig' => $sig, 'wait' => $wait, 'last' => $last]);
    exit;
}
if (empty($_COOKIE['adm_seen'])) setcookie('adm_seen', '1', time() + 365 * 86400, '/');
// Аналитика режимі: тек жарнама (әдепкі) немесе барлық трафик
if (isset($_GET['tr'])) $_SESSION['tr'] = $_GET['tr'] === 'all' ? 'all' : 'ad';
$TR = isset($_SESSION['tr']) ? $_SESSION['tr'] : 'ad';

/* ---------------- Ағымдағы проект ---------------- */
if (isset($_GET['p']) && proj_index($cfg, 'id', $_GET['p']) >= 0) $_SESSION['p'] = $_GET['p'];
$PI = proj_index($cfg, 'id', isset($_SESSION['p']) ? $_SESSION['p'] : '');
if ($PI < 0) $PI = 0;
$P = $cfg['projects'][$PI];

$isHttps = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') || (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https') || (isset($_SERVER['REQUEST_SCHEME']) && $_SERVER['REQUEST_SCHEME'] === 'https') || (isset($_SERVER['SERVER_PORT']) && $_SERVER['SERVER_PORT'] == 443);
$base = ($isHttps ? 'https' : 'http') . '://' . (isset($_SERVER['HTTP_HOST']) ? $_SERVER['HTTP_HOST'] : '') . rtrim(dirname($_SERVER['SCRIPT_NAME']), '/\\') . '/';
$land = $base . $P['slug'] . '/';

/* ---------------- Әрекеттер (POST) ---------------- */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!hash_equals($CSRF, post('csrf'))) go_to('', 'Обновите страницу и попробуйте снова');
    $a = post('a');
    $M = &$cfg['projects'][$PI]['managers'];
    $id = post('id');
    $idx = -1;
    foreach ($M as $i => $m) if ($m['id'] === $id) $idx = $i;

    /* --- Менеджерлер --- */
    if ($a === 'add') {
        $phone = norm_phone(post('phone'));
        if (!$phone) go_to('tab=managers', '⚠️ Неверный номер. Например: 87051234567');
        $max = 0;
        foreach ($M as $m) $max = max($max, (int)substr($m['id'], 1));
        $name = post('name') ?: ((count($M) + 1) . '-менеджер');
        $M[] = ['id' => 'm' . ($max + 1), 'name' => mb_substr($name, 0, 40), 'phone' => $phone, 'active' => true];
        cfg_save($cfg);
        go_to('tab=managers', 'Менеджер добавлен ✓');
    }
    if ($a === 'save' && $idx >= 0) {
        $phone = norm_phone(post('phone'));
        if (!$phone) go_to('tab=managers', '⚠️ Неверный номер');
        $M[$idx]['name'] = mb_substr(post('name') ?: $M[$idx]['name'], 0, 40);
        $M[$idx]['phone'] = $phone;
        cfg_save($cfg);
        go_to('tab=managers', 'Сохранено ✓');
    }
    if ($a === 'toggle' && $idx >= 0) {
        $M[$idx]['active'] = empty($M[$idx]['active']);
        cfg_save($cfg);
        go_to('tab=managers', $M[$idx]['active'] ? 'Менеджер включён — заявки будут поступать' : 'Менеджер на паузе — заявки не поступают');
    }
    if ($a === 'del' && $idx >= 0) {
        array_splice($M, $idx, 1);
        cfg_save($cfg);
        go_to('tab=managers', 'Менеджер удалён');
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
        $msg = 'Сайт сохранён ✓ Изменения видны сразу';
        // Сілтеме (slug) өзгерсе
        $slug = strtolower(post('slug'));
        if ($slug !== $X['slug']) {
            if (!valid_slug($slug)) go_to('tab=site', '⚠️ Ссылка — только латиница, цифры и «-» (2–30 символов)');
            if (proj_index($cfg, 'slug', $slug) >= 0) go_to('tab=site', '⚠️ Эта ссылка уже занята');
            if (is_dir(ROOT . '/' . $slug) && !file_exists(ROOT . '/' . $slug . '/index.php')) go_to('tab=site', '⚠️ Такая папка уже есть на сервере, выберите другое имя');
            $old = $X['slug'];
            $X['slug'] = $slug;
            ensure_stub($slug);
            if ($old && file_exists(ROOT . '/' . $old . '/index.php')) @unlink(ROOT . '/' . $old . '/index.php');
            $msg = 'Сайт сохранён ✓ Новая ссылка: ' . $base . $slug . '/';
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
        if ($name === '') go_to('tab=projects', '⚠️ Укажите название проекта');
        if (!valid_slug($slug)) go_to('tab=projects', '⚠️ Ссылка — только латиница, цифры и «-» (2–30 символов). Например: tize, kolen, massajer');
        if (proj_index($cfg, 'slug', $slug) >= 0) go_to('tab=projects', '⚠️ Эта ссылка уже занята');
        if (is_dir(ROOT . '/' . $slug) && !file_exists(ROOT . '/' . $slug . '/index.php')) go_to('tab=projects', '⚠️ Такая папка уже есть на сервере, выберите другое имя');
        $max = 0;
        foreach ($cfg['projects'] as $p) $max = max($max, (int)substr($p['id'], 1));
        $np = project_default('p' . ($max + 1), $slug, $name);
        $phone = norm_phone(post('phone'));
        if ($phone) $np['managers'][] = ['id' => 'm1', 'name' => '1-менеджер', 'phone' => $phone, 'active' => true];
        $cfg['projects'][] = $np;
        cfg_save($cfg);
        ensure_stub($slug);
        $_SESSION['p'] = $np['id'];
        go_to('tab=site', 'Новый проект создан ✓ Теперь настройте тексты, фото и пиксели');
    }
    if ($a === 'proj_del') {
        if (count($cfg['projects']) < 2) go_to('tab=projects', '⚠️ Нельзя удалить последний проект');
        if (post('confirm') !== $P['slug']) go_to('tab=projects', '⚠️ Для подтверждения введите ссылку проекта: ' . $P['slug']);
        if (file_exists(ROOT . '/' . $P['slug'] . '/index.php')) @unlink(ROOT . '/' . $P['slug'] . '/index.php');
        foreach (['img', 'img_webp'] as $k) if ($P[$k] && strpos($P[$k], 'up/') === 0) @unlink(ROOT . '/' . $P[$k]);
        array_splice($cfg['projects'], $PI, 1);
        cfg_save($cfg);
        unset($_SESSION['p']);
        go_to('tab=projects', 'Проект удалён');
    }

    /* --- Wazzup24 --- */
    if ($a === 'wz_connect') {
        $key = preg_replace('/[^A-Za-z0-9_\-]/', '', post('wz_key'));
        if ($key === '') go_to('tab=settings', '⚠️ Введите API-ключ Wazzup');
        list($ch, $err) = wz_channels($key);
        if ($ch === null) go_to('tab=settings', '⚠️ Wazzup не принял ключ. Проверьте ключ. (' . mb_substr($err, 0, 120) . ')');
        $hook = $base . 'wz.php?k=' . $cfg['wz_secret'];
        list($code, $j, $err) = wz_api($key, 'PATCH', 'webhooks', ['webhooksUri' => $hook, 'subscriptions' => ['messagesAndStatuses' => true, 'contactsAndDealsCreation' => false]]);
        if ($code !== 200) go_to('tab=settings', '⚠️ Не удалось установить webhook (' . $code . '): ' . mb_substr($err, 0, 160));
        $cfg['wz_key'] = $key; $cfg['wz_channels'] = $ch;
        cfg_save($cfg);
        go_to('tab=settings', 'Wazzup подключён ✓ Теперь сообщения клиентов будут в статистике');
    }
    if ($a === 'wz_refresh' && $cfg['wz_key']) {
        list($ch, $err) = wz_channels($cfg['wz_key']);
        if ($ch === null) go_to('tab=settings', '⚠️ ' . $err);
        $cfg['wz_channels'] = $ch; cfg_save($cfg);
        go_to('tab=settings', 'Каналы обновлены ✓');
    }
    if ($a === 'wz_off') {
        $cfg['wz_key'] = ''; cfg_save($cfg);
        go_to('tab=settings', 'Wazzup отключён');
    }

    /* --- Жалпы баптаулар --- */
    if ($a === 'pass') {
        if (!password_verify(post('old'), $cfg['password'])) go_to('tab=settings', '⚠️ Неверный текущий пароль');
        if (strlen(post('p1')) < 6 || post('p1') !== post('p2')) go_to('tab=settings', '⚠️ Новый пароль — не короче 6 символов, оба поля должны совпадать');
        $cfg['password'] = password_hash(post('p1'), PASSWORD_DEFAULT);
        cfg_save($cfg);
        remember_set($cfg);
        go_to('tab=settings', 'Пароль изменён ✓');
    }
    if ($a === 'reset' && post('confirm') === 'ДА') {
        purge_events($P['id']);
        go_to('tab=settings', '«' . $P['name'] . '» — статистика очищена');
    }
    go_to();
}

$dur = function ($sec) {
    if ($sec === null) return '—';
    if ($sec < 60) return $sec . ' сек';
    if ($sec < 3600) return round($sec / 60) . ' мин';
    return floor($sec / 3600) . ' ч ' . round(($sec % 3600) / 60) . ' мин';
};
$flash = isset($_SESSION['flash']) ? $_SESSION['flash'] : '';
unset($_SESSION['flash']);
$tab = isset($_GET['tab']) ? $_GET['tab'] : 'stats';
if (!in_array($tab, ['stats', 'chats', 'managers', 'site', 'projects', 'settings'], true)) $tab = 'stats';

head_html('Админка — ' . $P['name']);
?>
<div class="top"><div class="top-in">
  <div class="psw">
    <a href="?tab=stats" class="brand" style="text-decoration:none;color:inherit"><img src="img/logo.svg" alt="Taplinker" width="30" height="30"></a>
    <a href="#" id="liveBtn" class="live on" title="Live — обновление в реальном времени"><i></i>LIVE</a>
    <form method="get"><input type="hidden" name="tab" value="<?= h($tab) ?>">
      <select name="p" onchange="this.form.submit()" aria-label="Проект">
        <?php foreach ($cfg['projects'] as $p): ?><option value="<?= h($p['id']) ?>" <?= $p['id'] === $P['id'] ? 'selected' : '' ?>><?= h($p['name']) ?></option><?php endforeach; ?>
      </select>
    </form>
    <a class="btn sm gray" style="text-decoration:none" href="?tab=projects">＋ Проект</a>
  </div>
  <nav class="tabs">
    <a href="?tab=stats" class="<?= $tab === 'stats' ? 'on' : '' ?>">📊 Аналитика</a>
    <a href="?tab=chats" class="<?= $tab === 'chats' ? 'on' : '' ?>">💬 Чаты<?php $wn = 0; foreach (wz_chats() as $x) if ((isset($x['p']) ? $x['p'] : '') === $P['id'] && isset($x['ld']) && $x['ld'] === 'in') $wn++; if ($wn): ?> <span class="pill" style="background:#dc2626;color:#fff;padding:1px 7px"><?= $wn ?></span><?php endif; ?></a>
    <a href="?tab=managers" class="<?= $tab === 'managers' ? 'on' : '' ?>">👥 Менеджеры</a>
    <a href="?tab=site" class="<?= $tab === 'site' ? 'on' : '' ?>">✏️ Сайт</a>
    <a href="?tab=projects" class="<?= $tab === 'projects' ? 'on' : '' ?>">📁 Проекты</a>
    <a href="?tab=settings" class="<?= $tab === 'settings' ? 'on' : '' ?>">⚙️</a>
    <a href="<?= h($land) ?>" target="_blank">🌐</a>
    <a href="?logout=1" class="out">Выйти</a>
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
    $rangeLabel = 'Сегодня';
    if ($r === 'yesterday') { $from = $t0 - 86400; $to = $t0 - 1; $rangeLabel = 'Вчера'; }
    elseif ($r === '7') { $from = $t0 - 6 * 86400; $rangeLabel = 'Последние 7 дней'; }
    elseif ($r === '30') { $from = $t0 - 29 * 86400; $rangeLabel = 'Последние 30 дней'; }
    elseif ($r === 'all') { $from = day_start(first_event_ts($P['id'])); $rangeLabel = 'За всё время'; }
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
    $WZ = !empty($cfg['wz_key']);
    $visits = 0; $clicks = 0; $uv = []; $uc = [];
    $byM = []; $byS = []; $byD = []; $byC = []; $recent = [];
    $leads = 0; $leadsSite = 0; $replied = 0; $repliedSite = 0; $noReply = 0; $leadList = []; $rsAll = [];
    $chats = null; $skipped = 0;
    $msgN = norm_text($P['message']);
    // Клиент сайттың автоматты мәтінімен жазды ма → «сайттан келген»
    $isSite = function ($e) use ($msgN) {
        if (!empty($e['st'])) return true;
        $t = norm_text(isset($e['tx']) ? $e['tx'] : '');
        return $msgN !== '' && $t !== '' && (strpos($t, $msgN) === 0 || strpos($msgN, $t) === 0);
    };
    $mInit = function ($n) { return ['n' => $n, 'c' => 0, 'ov' => 0, 'oc' => 0, 'w' => 0, 'ws' => 0, 'nr' => 0, 'rs' => []]; };
    $hourly = ($to - $from) <= 86400;
    $buckets = [];
    if ($hourly) { for ($i = 0; $i < 24; $i++) $buckets[$i] = [sprintf('%02d', $i), 0, 0, 0]; }
    else { for ($d = day_start($from); $d <= $to; $d += 86400) $buckets[ldate('Y-m-d', $d)] = [ldate('d.m', $d), 0, 0, 0]; }

    foreach ($ev as $e) {
        $bk = $hourly ? (int)ldate('G', $e['t']) : ldate('Y-m-d', $e['t']);
        // Wazzup: клиент шынымен жазды / менеджер жауап берді
        if ($e['e'] === 'w' || $e['e'] === 'r') {
            $mid = !empty($e['m']) ? $e['m'] : '—';
            if (!isset($byM[$mid])) $byM[$mid] = $mInit(isset($e['mn']) ? $e['mn'] : '—');
            if ($e['e'] === 'w') {
                if ($chats === null) $chats = wz_chats();
                $site = $isSite($e);
                $ok = !empty($e['ck']) && !empty($chats[$e['ck']]['r']);
                $e['_site'] = $site; $e['_ok'] = $ok;
                $leads++; $byM[$mid]['w']++;
                if ($site) { $leadsSite++; $byM[$mid]['ws']++; if ($ok) $repliedSite++; }
                if ($ok) $replied++; else { $noReply++; $byM[$mid]['nr']++; }
                $leadList[] = $e;
                if (isset($buckets[$bk])) $buckets[$bk][3]++;
            } else { $rsAll[] = (int)$e['rs']; $byM[$mid]['rs'][] = (int)$e['rs']; }
            continue;
        }
        if ($TR === 'ad' && !is_ad_event($e)) { $skipped++; continue; }
        $isC = $e['e'] === 'c';
        $vk = !empty($e['v']) ? $e['v'] : ('x' . $e['t']);
        $s = !empty($e['s']) ? $e['s'] : 'direct';
        $dv = !empty($e['d']) ? $e['d'] : '—';
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
    $SHOWW = $WZ || $leads;
    foreach ($P['managers'] as $m) if (isset($byM[$m['id']])) $byM[$m['id']]['n'] = $m['name'];
    uasort($byM, function ($a, $b) { return ($b['w'] * 1000 + $b['c']) - ($a['w'] * 1000 + $a['c']); });
    uasort($byS, function ($a, $b) { return ($b[0] + $b[1]) - ($a[0] + $a[1]); });
    uasort($byD, function ($a, $b) { return ($b[0] + $b[1]) - ($a[0] + $a[1]); });
    uasort($byC, function ($a, $b) { return ($b[0] + $b[1]) - ($a[0] + $a[1]); });
    $recent = array_reverse(array_slice($recent, -30));
    $avgRs = $rsAll ? (int)round(array_sum($rsAll) / count($rsAll)) : null;
    // Жазғандар тізімінің сүзгісі
    $lf = isset($_GET['lf']) ? $_GET['lf'] : 'all';
    $leadShow = array_filter($leadList, function ($e) use ($lf) {
        if ($lf === 'site') return $e['_site'];
        if ($lf === 'other') return !$e['_site'];
        if ($lf === 'noreply') return !$e['_ok'];
        return true;
    });
    $leadShow = array_reverse(array_slice(array_values($leadShow), -100));
    $keep = 'tab=stats&r=' . rawurlencode($r) . ($r === 'custom' ? '&from=' . ldate('Y-m-d', $from) . '&to=' . ldate('Y-m-d', $to) : '');
    $pct = function ($a, $b) { return $b ? min(100, round($a / $b * 100)) . '%' : '—'; };
    $dur = function ($sec) {
        if ($sec === null) return '—';
        if ($sec < 60) return $sec . ' сек';
        if ($sec < 3600) return round($sec / 60) . ' мин';
        return floor($sec / 3600) . ' ч ' . round(($sec % 3600) / 60) . ' мин';
    };
    $srcNames = ['tiktok' => 'TikTok', 'instagram' => 'Instagram', 'facebook' => 'Facebook', 'google' => 'Google', 'direct' => 'Прямые / неизвестно', 'youtube' => 'YouTube', 'telegram' => 'Telegram', 'vk' => 'VK', 'yandex' => 'Яндекс'];
    $sn = function ($s) use ($srcNames) { return isset($srcNames[$s]) ? $srcNames[$s] : $s; };
    $qs = function ($k) use ($r) { return $r === $k ? 'on' : ''; };
?>
  <div class="ranges">
    <a href="?tab=stats&r=today" class="<?= $qs('today') ?>">Сегодня</a>
    <a href="?tab=stats&r=yesterday" class="<?= $qs('yesterday') ?>">Вчера</a>
    <a href="?tab=stats&r=7" class="<?= $qs('7') ?>">7 дней</a>
    <a href="?tab=stats&r=30" class="<?= $qs('30') ?>">30 дней</a>
    <a href="?tab=stats&r=all" class="<?= $qs('all') ?>">Всё время</a>
    <form method="get">
      <input type="hidden" name="tab" value="stats"><input type="hidden" name="r" value="custom">
      <input type="date" name="from" value="<?= h(ldate('Y-m-d', $from)) ?>">
      <span class="muted">—</span>
      <input type="date" name="to" value="<?= h(ldate('Y-m-d', $to)) ?>">
      <button class="btn sm gray">Показать</button>
    </form>
  </div>

  <div class="ranges" style="margin-top:-6px">
    <a href="?<?= h($keep) ?>&tr=ad" class="<?= $TR === 'ad' ? 'on' : '' ?>">📢 Только реклама</a>
    <a href="?<?= h($keep) ?>&tr=all" class="<?= $TR === 'all' ? 'on' : '' ?>">Весь трафик</a>
    <span class="muted"><?= $TR === 'ad' ? 'Не учтено (не из рекламы): ' . $skipped : 'Показан весь трафик, включая прямые заходы' ?></span>
  </div>

  <!-- Воронка: сайттан келген адамның жолы -->
  <div class="card">
    <h2>🔻 <?= h($P['name']) ?> · <?= h($rangeLabel) ?></h2>
    <div class="fun">
      <div class="st"><div class="l">👀 Зашли на сайт</div><div class="v"><?= count($uv) ?></div><div class="s"><?= $visits ?> открытий</div></div>
      <div class="ar">→<b><?= $pct(count($uc), count($uv)) ?></b></div>
      <div class="st"><div class="l">👆 Нажали WhatsApp</div><div class="v"><?= count($uc) ?></div><div class="s"><?= $clicks ?> нажатий</div></div>
<?php if ($SHOWW): ?>
      <div class="ar">→<b><?= $pct($leadsSite, count($uc)) ?></b></div>
      <div class="st hi"><div class="l">✍️ Написали с сайта</div><div class="v"><?= $leadsSite ?></div><div class="s">с авто-текстом</div></div>
      <div class="ar">→<b><?= $pct($repliedSite, $leadsSite) ?></b></div>
      <div class="st"><div class="l">💬 Получили ответ</div><div class="v"><?= $repliedSite ?></div><div class="s">из написавших с сайта</div></div>
<?php endif; ?>
    </div>
    <p class="muted" style="margin-top:10px">📢 <b>Реклама</b> — переход с меткой TikTok (ttclid), Meta (fbclid), Google (gclid) или с UTM-меткой. Ваши собственные заходы из браузера админки и боты не учитываются.</p>
<?php if (!$SHOWW): ?>
    <p class="muted" style="margin-top:10px">💡 Чтобы видеть, сколько людей реально <b>написали</b> в WhatsApp, <a href="?tab=settings">подключите Wazzup24</a>.</p>
<?php endif; ?>
  </div>

<?php if ($SHOWW): ?>
  <div class="grid kpis" style="margin-bottom:16px">
    <div class="kpi hi" style="background:linear-gradient(135deg,#15803d,#166534)"><div class="l">✍️ Всего написали</div><div class="v"><?= $leads ?></div><div class="l" style="margin-top:2px">на номера менеджеров</div></div>
    <div class="kpi"><div class="l">С сайта / Из других мест</div><div class="v"><?= $leadsSite ?> <span style="color:#94a3b8;font-weight:700">/ <?= $leads - $leadsSite ?></span></div></div>
    <div class="kpi"><div class="l">Среднее время ответа</div><div class="v" style="font-size:24px"><?= h($dur($avgRs)) ?></div></div>
    <a class="kpi" href="?<?= h($keep) ?>&lf=noreply#leads" style="text-decoration:none;color:inherit;<?= $noReply ? 'background:#fee2e2;border-color:#fecaca' : '' ?>"><div class="l">Без ответа</div><div class="v" <?= $noReply ? 'style="color:#dc2626"' : '' ?>><?= $noReply ?></div><?php if ($noReply): ?><div class="l" style="color:#dc2626">смотреть →</div><?php endif; ?></a>
  </div>
<?php endif; ?>

  <div class="card">
    <h2><?= $hourly ? 'По часам' : 'По дням' ?></h2>
    <div class="legend"><span><i style="background:#bbf7d0"></i>Посещения</span><span><i style="background:#4ade80"></i>Нажатия WhatsApp</span><?php if ($SHOWW): ?><span><i style="background:#166534"></i>Написали</span><?php endif; ?></div>
    <?php
    $n = count($buckets); $max = 1;
    foreach ($buckets as $b) $max = max($max, $b[1], $b[2], $b[3]);
    $nb = $SHOWW ? 3 : 2;
    $H = 170; $pad = 22; $w = max($n * 26, 300);
    $every = max(1, (int)ceil($n / 15));
    echo '<svg class="chart" viewBox="0 0 ' . $w . ' ' . ($H + $pad) . '" preserveAspectRatio="none" style="max-height:260px">';
    for ($g = 0; $g <= 4; $g++) { $y = $H - $H * $g / 4; echo '<line x1="0" x2="' . $w . '" y1="' . $y . '" y2="' . $y . '" stroke="#eef2f6"/>'; }
    $i = 0;
    foreach ($buckets as $b) {
        $x = $i * ($w / $n) + 3; $bw = ($w / $n - 6) / $nb;
        $h1 = $b[1] / $max * ($H - 10); $h2 = $b[2] / $max * ($H - 10); $h3 = $b[3] / $max * ($H - 10);
        echo '<g><title>' . h($b[0]) . ': посещ. ' . $b[1] . ', нажат. ' . $b[2] . ($nb === 3 ? ', написали ' . $b[3] : '') . '</title>';
        echo '<rect x="' . round($x, 1) . '" y="' . round($H - $h1, 1) . '" width="' . round($bw, 1) . '" height="' . round($h1, 1) . '" rx="2" fill="#bbf7d0"/>';
        echo '<rect x="' . round($x + $bw, 1) . '" y="' . round($H - $h2, 1) . '" width="' . round($bw, 1) . '" height="' . round($h2, 1) . '" rx="2" fill="#4ade80"/>';
        if ($nb === 3) echo '<rect x="' . round($x + 2 * $bw, 1) . '" y="' . round($H - $h3, 1) . '" width="' . round($bw, 1) . '" height="' . round($h3, 1) . '" rx="2" fill="#166534"/>';
        echo '</g>';
        if ($i % $every === 0) echo '<text x="' . round($x + $bw, 1) . '" y="' . ($H + 15) . '" font-size="10" text-anchor="middle" fill="#64748b">' . h($b[0]) . '</text>';
        $i++;
    }
    echo '<text x="2" y="10" font-size="10" fill="#94a3b8">' . $max . '</text></svg>';
    ?>
  </div>

  <div class="card">
    <h2>👥 Менеджеры</h2>
    <?php if (!$byM): ?><p class="muted">За этот период данных пока нет</p><?php else: ?>
    <div style="overflow-x:auto"><table>
      <tr><th>Менеджер</th><th class="n">Нажатия</th><?php if ($SHOWW): ?><th class="n">Написали</th><th class="n">С сайта</th><th class="n">Ср. ответ</th><th class="n">Без ответа</th><?php endif; ?><th class="n">Личн. ссылка</th></tr>
      <?php foreach ($byM as $m): ?>
      <tr><td><b><?= h($m['n']) ?></b></td>
        <td class="n"><?= $m['c'] ?></td>
        <?php if ($SHOWW): ?>
        <td class="n"><b><?= $m['w'] ?></b></td>
        <td class="n"><?= $m['ws'] ?></td>
        <td class="n"><?= h($dur($m['rs'] ? (int)round(array_sum($m['rs']) / count($m['rs'])) : null)) ?></td>
        <td class="n"><?= $m['nr'] ? '<b style="color:#dc2626">' . $m['nr'] . '</b>' : '0' ?></td>
        <?php endif; ?>
        <td class="n"><?= $m['ov'] || $m['oc'] ? $m['ov'] . ' / ' . $m['oc'] : '<span class="muted">—</span>' ?></td></tr>
      <?php endforeach; ?>
    </table></div>
    <p class="muted" style="margin-top:8px"><b>Нажатия</b> — нажали кнопку WhatsApp на сайте и были направлены к этому менеджеру.<?php if ($SHOWW): ?> <b>Написали</b> — все клиенты, реально написавшие на номер менеджера, <b>С сайта</b> — из них написавшие с авто-текстом сайта.<?php endif; ?> <b>Личн. ссылка</b> — зашли / нажали по личной ссылке менеджера.</p>
    <?php endif; ?>
  </div>

<?php if ($SHOWW): ?>
  <div class="card" id="leads">
    <h2>✍️ Написавшие клиенты</h2>
    <div class="ranges" style="margin-bottom:8px">
      <?php foreach (['all' => 'Все · ' . $leads, 'site' => 'С сайта · ' . $leadsSite, 'other' => 'Из других мест · ' . ($leads - $leadsSite), 'noreply' => 'Без ответа · ' . $noReply] as $k => $v): ?>
        <a href="?<?= h($keep) ?>&lf=<?= $k ?>#leads" class="<?= $lf === $k ? 'on' : '' ?>"><?= h($v) ?></a>
      <?php endforeach; ?>
    </div>
    <?php if (!$leadShow): ?><p class="muted">В этом списке пока никого нет</p><?php else: ?>
    <div style="overflow-x:auto"><table><tr><th>Время</th><th>Клиент</th><th>Менеджер</th><th>Ответ</th></tr>
    <?php foreach ($leadShow as $e): $mn = isset($e['mn']) ? $e['mn'] : '—';
        foreach ($P['managers'] as $m) if (isset($e['m']) && $m['id'] === $e['m']) $mn = $m['name'];
        $ch = !empty($e['ck']) && isset($chats[$e['ck']]) ? $chats[$e['ck']] : null; ?>
      <tr><td><?= ldate('d.m H:i', $e['t']) ?></td>
        <td><a href="https://wa.me/<?= h($e['cl']) ?>" target="_blank">+<?= h($e['cl']) ?></a>
          <?= $e['_site'] ? '<span class="pill on">с сайта</span>' : '<span class="pill off">другое</span>' ?>
          <?php if (!empty($e['tx'])): ?><br><span class="muted"><?= h($e['tx']) ?></span><?php endif; ?></td>
        <td><?= h($mn) ?></td>
        <td><?= $ch && !empty($ch['r']) ? h($dur($ch['r'] - $ch['t'])) : '<span class="pill" style="background:#fee2e2;color:#dc2626">без ответа</span>' ?></td></tr>
    <?php endforeach; ?></table></div><?php endif; ?>
  </div>
<?php endif; ?>

  <div class="grid two">
    <div class="card">
      <h2>🌐 Источники</h2>
      <?php if (!$byS): ?><p class="muted">Данных пока нет</p><?php else: ?>
      <table><tr><th>Источник</th><th class="n">Посещ.</th><th class="n">Нажат.</th><th class="n">Конв.</th></tr>
      <?php foreach ($byS as $k => $v): ?>
        <tr><td><?= h($sn($k)) ?></td><td class="n"><?= $v[0] ?></td><td class="n"><b><?= $v[1] ?></b></td><td class="n"><?= $v[0] ? round($v[1] / $v[0] * 100) . '%' : '—' ?></td></tr>
      <?php endforeach; ?></table><?php endif; ?>
    </div>
    <div class="card">
      <h2>🎯 Кампании (utm_campaign)</h2>
      <?php if (!$byC): ?><p class="muted">Добавьте к рекламной ссылке <code>?utm_campaign=название</code> — и каждая реклама будет видна здесь отдельно.</p><?php else: ?>
      <table><tr><th>Кампания</th><th class="n">Посещ.</th><th class="n">Нажат.</th><th class="n">Конв.</th></tr>
      <?php foreach ($byC as $k => $v): ?>
        <tr><td><?= h($k) ?></td><td class="n"><?= $v[0] ?></td><td class="n"><b><?= $v[1] ?></b></td><td class="n"><?= $v[0] ? round($v[1] / $v[0] * 100) . '%' : '—' ?></td></tr>
      <?php endforeach; ?></table><?php endif; ?>
    </div>
    <div class="card">
      <h2>📱 Устройства</h2>
      <?php if (!$byD): ?><p class="muted">Данных пока нет</p><?php else: ?>
      <table><tr><th>Устройство</th><th class="n">Посещ.</th><th class="n">Нажат.</th></tr>
      <?php foreach ($byD as $k => $v): ?>
        <tr><td><?= h($k) ?></td><td class="n"><?= $v[0] ?></td><td class="n"><b><?= $v[1] ?></b></td></tr>
      <?php endforeach; ?></table><?php endif; ?>
    </div>
  </div>

  <div class="card">
    <h2>🕒 Последние нажатия</h2>
    <?php if (!$recent): ?><p class="muted">За этот период нажатий пока нет</p><?php else: ?>
    <div style="overflow-x:auto"><table><tr><th>Время</th><th>Менеджер</th><th>Источник</th><th>Устройство</th></tr>
    <?php foreach ($recent as $e): $mn = isset($e['mn']) ? $e['mn'] : '—';
        foreach ($P['managers'] as $m) if (isset($e['m']) && $m['id'] === $e['m']) $mn = $m['name']; ?>
      <tr><td><?= ldate('d.m H:i', $e['t']) ?></td><td><?= h($mn) ?></td><td><?= h($sn(!empty($e['s']) ? $e['s'] : 'direct')) ?><?= !empty($e['c']) ? ' <span class="muted">· ' . h($e['c']) . '</span>' : '' ?></td><td><?= h(isset($e['d']) ? $e['d'] : '') ?></td></tr>
    <?php endforeach; ?></table></div><?php endif; ?>
  </div>

<?php
/* ================= ЧАТЫ ================= */
elseif ($tab === 'chats'):
    $all = wz_chats();
    $cf = isset($_GET['cf']) ? $_GET['cf'] : 'all';
    $cmF = isset($_GET['cm']) ? $_GET['cm'] : '';
    $q = preg_replace('/\D/', '', isset($_GET['q']) ? $_GET['q'] : '');
    $mNames = []; foreach ($P['managers'] as $m) $mNames[$m['id']] = $m['name'];
    $list = []; $waitN = 0;
    foreach ($all as $k => $x) {
        if (empty($x['p']) || $x['p'] !== $P['id']) continue;
        $wait = isset($x['ld']) && $x['ld'] === 'in';
        if ($wait) $waitN++;
        if ($cf === 'wait' && !$wait) continue;
        if ($cmF !== '' && (isset($x['m']) ? $x['m'] : '') !== $cmF) continue;
        if ($q !== '' && strpos(isset($x['cl']) ? $x['cl'] : $k, $q) === false) continue;
        $x['_k'] = $k; $x['_wait'] = $wait;
        $list[] = $x;
    }
    usort($list, function ($a, $b) { return (isset($b['last']) ? $b['last'] : 0) - (isset($a['last']) ? $a['last'] : 0); });
    $list = array_slice($list, 0, 300);
    $sel = isset($_GET['c']) && isset($all[$_GET['c']]) && (isset($all[$_GET['c']]['p']) ? $all[$_GET['c']]['p'] : '') === $P['id'] ? $_GET['c'] : '';
    $qsBase = 'tab=chats&cf=' . rawurlencode($cf) . '&cm=' . rawurlencode($cmF) . '&q=' . rawurlencode($q);
    $ago = function ($ts) {
        $d = time() - $ts;
        if ($d < 60) return 'сейчас';
        if ($d < 3600) return floor($d / 60) . ' мин';
        if (ldate('Y-m-d', $ts) === ldate('Y-m-d', time())) return ldate('H:i', $ts);
        return ldate('d.m', $ts);
    };
?>
<?php if (!$cfg['wz_key'] && !$all): ?>
  <div class="card"><h2>💬 Чаты</h2><p class="muted">Чтобы видеть переписку с клиентами, <a href="?tab=settings">подключите Wazzup24</a>. Сообщения появятся здесь сразу после подключения.</p></div>
<?php else: ?>
  <div class="chatwrap <?= $sel ? 'has-sel' : '' ?>">
    <div class="clist card">
      <form method="get" class="row" style="margin-bottom:8px">
        <input type="hidden" name="tab" value="chats"><input type="hidden" name="cf" value="<?= h($cf) ?>">
        <input type="text" name="q" value="<?= h($q) ?>" placeholder="Поиск по номеру" style="flex:1;min-width:120px;padding:8px 10px">
        <select name="cm" onchange="this.form.submit()" style="width:auto;padding:8px">
          <option value="">Все менеджеры</option>
          <?php foreach ($P['managers'] as $m): ?><option value="<?= h($m['id']) ?>" <?= $cmF === $m['id'] ? 'selected' : '' ?>><?= h($m['name']) ?></option><?php endforeach; ?>
        </select>
      </form>
      <div class="ranges" style="margin-bottom:6px">
        <a href="?tab=chats&cf=all&cm=<?= h(rawurlencode($cmF)) ?>" class="<?= $cf !== 'wait' ? 'on' : '' ?>">Все</a>
        <a href="?tab=chats&cf=wait&cm=<?= h(rawurlencode($cmF)) ?>" class="<?= $cf === 'wait' ? 'on' : '' ?>">Ждут ответа · <?= $waitN ?></a>
      </div>
      <?php if (!$list): ?><p class="muted" style="padding:10px 0">Чатов пока нет. Они появятся, когда клиенты напишут на номера менеджеров.</p><?php endif; ?>
      <?php foreach ($list as $x): $k = $x['_k']; ?>
        <a class="ci <?= $k === $sel ? 'cur' : '' ?>" href="?<?= h($qsBase) ?>&c=<?= h(rawurlencode($k)) ?>">
          <div class="av"><?= h(mb_strtoupper(mb_substr(!empty($x['nm']) ? $x['nm'] : '#', 0, 1))) ?></div>
          <div class="cb">
            <div class="ct"><b><?= h(!empty($x['nm']) ? $x['nm'] : '+' . $x['cl']) ?></b><span><?= h($ago(isset($x['last']) ? $x['last'] : $x['t'])) ?></span></div>
            <div class="cp"><?= isset($x['ld']) && $x['ld'] === 'out' ? '<span class="muted">Вы: </span>' : '' ?><?= h(isset($x['lt']) ? $x['lt'] : '') ?></div>
            <div class="cm"><?= h(isset($mNames[isset($x['m']) ? $x['m'] : '']) ? $mNames[$x['m']] : '') ?><?php if ($x['_wait']): ?> <span class="pill" style="background:#fee2e2;color:#dc2626">ждёт ответа</span><?php endif; ?></div>
          </div>
        </a>
      <?php endforeach; ?>
    </div>

    <div class="cview card">
    <?php if (!$sel): ?>
      <p class="muted" style="text-align:center;margin-top:40px">Выберите чат слева</p>
    <?php else:
      $x = $all[$sel];
      $msgs = chat_msgs($sel, (int)$x['t']);
      $mn = isset($mNames[isset($x['m']) ? $x['m'] : '']) ? $mNames[$x['m']] : '—';
    ?>
      <div class="chd">
        <a class="btn sm gray back" style="text-decoration:none" href="?<?= h($qsBase) ?>">←</a>
        <div style="flex:1;min-width:0">
          <b><?= h(!empty($x['nm']) ? $x['nm'] : '+' . $x['cl']) ?></b><br>
          <span class="muted">+<?= h($x['cl']) ?> · менеджер: <?= h($mn) ?><?= !empty($x['fi']) && !empty($x['r']) ? ' · первый ответ через ' . h($dur($x['r'] - $x['fi'])) : '' ?></span>
        </div>
        <a class="btn sm" style="text-decoration:none" href="https://wa.me/<?= h($x['cl']) ?>" target="_blank">WhatsApp</a>
      </div>
      <div class="msgs" id="msgs">
        <?php if (!$msgs): ?><p class="muted" style="text-align:center">История сообщений появится с новыми сообщениями.</p><?php endif; ?>
        <?php $lastDay = ''; foreach ($msgs as $m):
          $day = ldate('d.m.Y', $m['t']);
          if ($day !== $lastDay) { echo '<div class="day">' . h($day) . '</div>'; $lastDay = $day; } ?>
          <div class="bub <?= $m['d'] === 'in' ? 'in' : 'out' ?>">
            <?php if ($m['d'] === 'out' && !empty($m['au'])): ?><div class="au"><?= h($m['au']) ?></div><?php endif; ?>
            <?php if (!empty($m['u']) && $m['ty'] === 'image'): ?><a href="<?= h($m['u']) ?>" target="_blank"><img src="<?= h($m['u']) ?>" alt="📷 Фото" loading="lazy" onerror="this.outerHTML='📷 Фото'"></a>
            <?php elseif (!empty($m['u'])): ?><a href="<?= h($m['u']) ?>" target="_blank">📎 <?= h(['video' => 'Видео', 'audio' => 'Аудио', 'document' => 'Файл'][$m['ty']] ?? 'Вложение') ?></a>
            <?php elseif ($m['tx'] === '' && $m['ty'] === 'missing_call'): ?>📞 Пропущенный звонок<?php endif; ?>
            <?php if ($m['tx'] !== ''): ?><div><?= nl2br(h($m['tx'])) ?></div><?php endif; ?>
            <div class="tm"><?= ldate('H:i', $m['t']) ?></div>
          </div>
        <?php endforeach; ?>
      </div>
      <p class="muted" style="margin-top:8px">Ответить клиенту можно в WhatsApp или Wazzup — сообщение сразу появится здесь.</p>
    <?php endif; ?>
    </div>
  </div>
  <script>
  (function(){var m=document.getElementById('msgs');if(m)m.scrollTop=m.scrollHeight;})();
  </script>
<?php endif; ?>

<?php
/* ================= МЕНЕДЖЕРЛЕР ================= */
elseif ($tab === 'managers'):
    $todayCl = [];
    foreach (load_events(day_start(time()), time(), $P['id']) as $e) if ($e['e'] === 'c' && !empty($e['m'])) $todayCl[$e['m']] = (isset($todayCl[$e['m']]) ? $todayCl[$e['m']] : 0) + 1;
    $activeCount = 0; foreach ($P['managers'] as $m) if (!empty($m['active'])) $activeCount++;
?>
  <div class="card">
    <h2>➕ Добавить менеджера в «<?= h($P['name']) ?>»</h2>
    <form method="post" class="row">
      <?= csrf_field() ?><input type="hidden" name="a" value="add">
      <input type="text" name="name" placeholder="<?= count($P['managers']) + 1 ?>-менеджер (имя)" style="flex:1;min-width:160px" maxlength="40">
      <input type="tel" name="phone" placeholder="Номер WhatsApp: 87051234567" required style="flex:1;min-width:180px">
      <button class="btn">Добавить</button>
    </form>
  </div>

  <div class="card">
    <h2>👥 Список менеджеров</h2>
    <p class="muted" style="margin-bottom:6px">
      Заявки распределяются между <b>активными</b> менеджерами <?= $P['mode'] === 'random' ? '<b>случайно</b>' : '<b>по очереди</b> (1 → 2 → 3 → 1 …)' ?>.
      Сейчас активных: <b><?= $activeCount ?></b>. Повторное нажатие клиента — к тому же менеджеру.
    </p>
    <p class="muted" style="margin-bottom:6px">🔗 <b>Личная ссылка</b> — клиент, зашедший по ней, минует очередь и пишет <b>напрямую этому менеджеру</b>.</p>
    <?php if (!$P['managers']): ?><p class="flash err">Нет менеджеров — кнопка на сайте не работает. Добавьте выше.</p><?php endif; ?>
    <?php foreach ($P['managers'] as $i => $m): $on = !empty($m['active']); $fid = 'f' . h($m['id']); ?>
      <div class="mg <?= $on ? '' : 'off' ?>">
        <div class="num"><?= $i + 1 ?></div>
        <input type="text" name="name" value="<?= h($m['name']) ?>" form="<?= $fid ?>" maxlength="40">
        <input type="tel" name="phone" value="<?= h($m['phone']) ?>" form="<?= $fid ?>">
        <div class="acts">
          <span class="pill <?= $on ? 'on' : 'off' ?>"><?= $on ? 'Активен' : 'На паузе' ?></span>
          <span class="muted">сегодня: <b><?= isset($todayCl[$m['id']]) ? $todayCl[$m['id']] : 0 ?></b></span>
          <form method="post" id="<?= $fid ?>"><?= csrf_field() ?><input type="hidden" name="a" value="save"><input type="hidden" name="id" value="<?= h($m['id']) ?>"><button class="btn sm">Сохранить</button></form>
          <form method="post"><?= csrf_field() ?><input type="hidden" name="a" value="toggle"><input type="hidden" name="id" value="<?= h($m['id']) ?>"><button class="btn sm gray"><?= $on ? '⏸ Пауза' : '▶ Включить' ?></button></form>
          <form method="post"><?= csrf_field() ?><input type="hidden" name="a" value="up"><input type="hidden" name="id" value="<?= h($m['id']) ?>"><button class="btn sm gray" title="Выше">↑</button></form>
          <form method="post"><?= csrf_field() ?><input type="hidden" name="a" value="down"><input type="hidden" name="id" value="<?= h($m['id']) ?>"><button class="btn sm gray" title="Ниже">↓</button></form>
          <a class="btn sm gray" style="text-decoration:none" target="_blank" rel="noopener" href="https://wa.me/<?= h($m['phone']) ?>">WA</a>
          <form method="post" onsubmit="return confirm('<?= h($m['name']) ?> — удалить?')"><?= csrf_field() ?><input type="hidden" name="a" value="del"><input type="hidden" name="id" value="<?= h($m['id']) ?>"><button class="btn sm red">Удалить</button></form>
        </div>
        <div class="mlink">
          <span class="muted">🔗 Личная ссылка:</span>
          <input type="text" readonly value="<?= h($land . '?m=' . $m['id']) ?>" onclick="this.select()">
          <button type="button" class="btn sm gray" onclick="cp(this)">Копировать</button>
        </div>
      </div>
    <?php endforeach; ?>
  </div>

<?php
/* ================= САЙТ (мәтін, фото, пиксель) ================= */
elseif ($tab === 'site'): ?>
  <div class="card">
    <h2>✏️ Сайт «<?= h($P['name']) ?>»</h2>
    <div class="mlink" style="margin-bottom:6px">
      <span class="muted">Ссылка на сайт:</span>
      <input type="text" readonly value="<?= h($land) ?>" onclick="this.select()">
      <button type="button" class="btn sm gray" onclick="cp(this)">Копировать</button>
      <a class="btn sm" style="text-decoration:none" href="<?= h($land) ?>" target="_blank">Открыть</a>
    </div>
    <form method="post" enctype="multipart/form-data">
      <?= csrf_field() ?><input type="hidden" name="a" value="site">

      <h3 class="sec">Основное</h3>
      <label>Название проекта (видно только в админке)</label>
      <input type="text" name="name" value="<?= h($P['name']) ?>" maxlength="60" required>
      <label>Ссылка: <?= h($base) ?><b>…</b>/</label>
      <input type="text" name="slug" value="<?= h($P['slug']) ?>" maxlength="30" pattern="[a-z0-9][a-z0-9\-]{1,29}" required>
      <p class="muted">Только латиница, цифры и «-». При изменении старая ссылка перестанет работать — замените её и в рекламе.</p>
      <label class="radio"><input type="checkbox" name="active" value="1" <?= $P['active'] ? 'checked' : '' ?>> <span>Сайт включён (снимите галочку, чтобы временно закрыть сайт)</span></label>

      <h3 class="sec">Тексты на сайте</h3>
      <label>Верхняя надпись (зелёная)</label>
      <input type="text" name="top" value="<?= h($P['top']) ?>" maxlength="120" placeholder="Например: БЕЛ ЖӘНЕ ОМЫРТҚА АУРУЛАРЫН ЕМДЕЙДІ">
      <label>Заголовок (чёрный)</label>
      <input type="text" name="title" value="<?= h($P['title']) ?>" maxlength="200">
      <label>Зелёная часть заголовка</label>
      <input type="text" name="title_hl" value="<?= h($P['title_hl']) ?>" maxlength="100">
      <label>Текст кнопки</label>
      <input type="text" name="button" value="<?= h($P['button']) ?>" maxlength="40">
      <label>Текст под кнопкой</label>
      <input type="text" name="hint" value="<?= h($P['hint']) ?>" maxlength="120" placeholder="Если оставить пустым — не показывается">
      <p class="muted">Любой пустой текст не показывается на сайте.</p>

      <h3 class="sec">Фото</h3>
      <div class="preview">
        <?php if ($P['img']): ?><img src="<?= h($P['img']) ?>" alt=""><?php else: ?><span class="muted">Нет фото</span><?php endif; ?>
        <div>
          <input type="file" name="photo" accept="image/jpeg,image/png,image/webp">
          <p class="muted">JPG, PNG или WEBP. Фото автоматически обрезается по центру до <b>540×560</b> и сжимается для быстрой загрузки (как у Saumed).</p>
          <?php if ($P['img']): ?><label class="radio"><input type="checkbox" name="photo_del" value="1"> <span>Удалить фото</span></label><?php endif; ?>
        </div>
      </div>

      <h3 class="sec">WhatsApp</h3>
      <label>Текст, который автоматически подставится клиенту в WhatsApp</label>
      <textarea name="message" maxlength="500"><?= h($P['message']) ?></textarea>
      <label>Распределение заявок</label>
      <label class="radio"><input type="radio" name="mode" value="rr" <?= $P['mode'] !== 'random' ? 'checked' : '' ?>> <span><b>По очереди</b> — 1, 2, 3, снова 1…</span></label>
      <label class="radio"><input type="radio" name="mode" value="random" <?= $P['mode'] === 'random' ? 'checked' : '' ?>> <span><b>Случайно</b></span></label>

      <h3 class="sec">📈 Пиксели (только для этого проекта)</h3>
      <label>TikTok Pixel ID</label>
      <input type="text" name="tt_ids" value="<?= h($P['tt_ids']) ?>" placeholder="Несколько — через запятую" autocomplete="off">
      <label>TikTok: событие при нажатии кнопки</label>
      <select name="tt_event">
        <?php foreach (['ClickButton' => 'ClickButton — нажатие кнопки', 'Contact' => 'Contact — контакт', 'SubmitForm' => 'SubmitForm — отправка заявки', 'Lead' => 'Lead — лид', 'CompleteRegistration' => 'CompleteRegistration — регистрация'] as $k => $v): ?>
          <option value="<?= $k ?>" <?= $P['tt_event'] === $k ? 'selected' : '' ?>><?= h($v) ?></option>
        <?php endforeach; ?>
      </select>
      <label>Meta (Facebook / Instagram) Pixel ID</label>
      <input type="text" name="fb_ids" value="<?= h($P['fb_ids']) ?>" placeholder="Например: 1234567890123456" inputmode="numeric" autocomplete="off">
      <label>Meta: событие при нажатии кнопки</label>
      <select name="fb_event">
        <?php foreach (['Lead' => 'Lead — лид', 'Contact' => 'Contact — контакт', 'SubmitApplication' => 'SubmitApplication — заявка', 'CompleteRegistration' => 'CompleteRegistration — регистрация'] as $k => $v): ?>
          <option value="<?= $k ?>" <?= $P['fb_event'] === $k ? 'selected' : '' ?>><?= h($v) ?></option>
        <?php endforeach; ?>
      </select>

      <button class="btn" style="margin-top:18px;width:100%;min-height:48px">💾 Сохранить всё</button>
    </form>
  </div>

  <div class="card">
    <h2>🔗 Ссылки для рекламы</h2>
    <p class="muted">Ставьте в каждую рекламу такую ссылку — в аналитике будет видно, откуда пришли:</p>
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
    <h2>📁 Проекты</h2>
    <?php foreach ($cfg['projects'] as $p): $s = isset($stat[$p['id']]) ? $stat[$p['id']] : [0, 0, 0]; $mc = 0; foreach ($p['managers'] as $m) if (!empty($m['active'])) $mc++; ?>
      <div class="pj <?= $p['id'] === $P['id'] ? 'cur' : '' ?>">
        <div>
          <b><?= h($p['name']) ?></b> <?= $p['active'] ? '' : '<span class="pill off">закрыт</span>' ?><br>
          <a class="muted" href="<?= h($base . $p['slug'] . '/') ?>" target="_blank"><?= h($base . $p['slug'] . '/') ?></a><br>
          <span class="muted">Сегодня: посещ. <b><?= $s[0] ?></b> · нажат. <b><?= $s[1] ?></b><?= $cfg['wz_key'] ? ' · написали <b>' . $s[2] . '</b>' : '' ?> · активных менеджеров: <b><?= $mc ?></b></span>
        </div>
        <a class="btn sm <?= $p['id'] === $P['id'] ? 'gray' : '' ?>" style="text-decoration:none" href="?p=<?= h($p['id']) ?>&tab=site"><?= $p['id'] === $P['id'] ? 'Открыт' : 'Открыть' ?></a>
      </div>
    <?php endforeach; ?>
  </div>

  <div class="card">
    <h2>➕ Новый проект</h2>
    <form method="post">
      <?= csrf_field() ?><input type="hidden" name="a" value="proj_add">
      <label>Название проекта</label>
      <input type="text" name="name" required maxlength="60" placeholder="Например: Наколенник">
      <label>Ссылка: <?= h($base) ?><b>…</b>/</label>
      <input type="text" name="slug" required maxlength="30" pattern="[a-z0-9][a-z0-9\-]{1,29}" placeholder="Например: tize">
      <label>WhatsApp первого менеджера (остальных добавите потом)</label>
      <input type="tel" name="phone" placeholder="87051234567">
      <button class="btn" style="margin-top:12px">Создать проект</button>
    </form>
  </div>

  <?php if (count($cfg['projects']) > 1): ?>
  <div class="card">
    <h2>🗑 Удалить проект «<?= h($P['name']) ?>»</h2>
    <p class="muted">Сайт закроется, настройки удалятся. Статистика останется в файле.</p>
    <form method="post" class="row" style="margin-top:10px" onsubmit="return confirm('Удалить проект?')">
      <?= csrf_field() ?><input type="hidden" name="a" value="proj_del">
      <input type="text" name="confirm" placeholder="Для подтверждения введите: <?= h($P['slug']) ?>" style="max-width:280px" required>
      <button class="btn red">Удалить</button>
    </form>
  </div>
  <?php endif; ?>

<?php
/* ================= ЖАЛПЫ БАПТАУЛАР ================= */
else: ?>
  <div class="card">
    <h2>💬 Wazzup24 — сколько людей реально написали</h2>
    <?php if (!$cfg['wz_key']): ?>
      <p class="muted">После подключения Wazzup в статистике видно, кто <b>реально написал</b> в WhatsApp (а не только нажал кнопку), его номер и за сколько менеджер ответил.</p>
      <p class="muted" style="margin-top:8px">Где взять ключ: Wazzup → <b>Настройки</b> → <b>Интеграции</b> → <b>API</b> → скопируйте ключ.</p>
      <form method="post" class="row" style="margin-top:10px">
        <?= csrf_field() ?><input type="hidden" name="a" value="wz_connect">
        <input type="text" name="wz_key" placeholder="API-ключ Wazzup" autocomplete="off" style="flex:1;min-width:220px" required>
        <button class="btn">Добавить</button>
      </form>
    <?php else: ?>
      <p><span class="pill on">Подключено</span> Сообщения клиентов автоматически попадают в статистику.</p>
      <h3 class="sec">Номера WhatsApp в Wazzup</h3>
      <?php
        $allM = [];
        foreach ($cfg['projects'] as $pp) foreach ($pp['managers'] as $mm) $allM[$mm['phone']][] = $pp['name'] . ' → ' . $mm['name'];
      ?>
      <table><tr><th>Номер</th><th>Статус</th><th>Менеджер</th></tr>
      <?php foreach ($cfg['wz_channels'] as $ch): if (!in_array($ch['transport'], ['whatsapp', 'wapi'], true)) continue; ?>
        <tr><td>+<?= h($ch['phone']) ?></td><td><?= $ch['state'] === 'active' ? '<span class="pill on">active</span>' : '<span class="pill off">' . h($ch['state']) . '</span>' ?></td>
        <td><?= isset($allM[$ch['phone']]) ? h(implode(', ', $allM[$ch['phone']])) : '<span class="muted">Не учитывается — нет у менеджеров</span>' ?></td></tr>
      <?php endforeach; ?></table>
      <p class="muted" style="margin-top:8px">Учитываются <b>только номера ваших менеджеров</b>, остальные номера в Wazzup в статистику не попадают. Номер менеджера должен совпадать с номером в Wazzup. Если один номер в нескольких проектах, проект определяется по авто-тексту клиента.</p>
      <div class="row" style="margin-top:10px">
        <form method="post"><?= csrf_field() ?><input type="hidden" name="a" value="wz_refresh"><button class="btn sm gray">🔄 Обновить номера</button></form>
        <form method="post" onsubmit="return confirm('Отключить Wazzup?')"><?= csrf_field() ?><input type="hidden" name="a" value="wz_off"><button class="btn sm red">Отключить</button></form>
      </div>
    <?php endif; ?>
  </div>

  <div class="card">
    <h2>🔒 Сменить пароль</h2>
    <form method="post" style="max-width:380px">
      <?= csrf_field() ?><input type="hidden" name="a" value="pass">
      <label>Текущий пароль</label><input type="password" name="old" required autocomplete="current-password">
      <label>Новый пароль</label><input type="password" name="p1" required minlength="6" autocomplete="new-password">
      <label>Повторите новый пароль</label><input type="password" name="p2" required minlength="6" autocomplete="new-password">
      <button class="btn" style="margin-top:12px">Изменить</button>
    </form>
  </div>

  <div class="card">
    <h2>🗑 Очистить статистику «<?= h($P['name']) ?>»</h2>
    <p class="muted">Удаляются только посещения и нажатия этого проекта. Другие проекты, менеджеры и настройки сохранятся.</p>
    <form method="post" class="row" style="margin-top:10px" onsubmit="return confirm('Очистить статистику?')">
      <?= csrf_field() ?><input type="hidden" name="a" value="reset">
      <input type="text" name="confirm" placeholder="Для подтверждения введите ДА" style="max-width:260px" required>
      <button class="btn red">Очистить</button>
    </form>
  </div>
<?php endif; ?>
</div>
<?php foot_html();
