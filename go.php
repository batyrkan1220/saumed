<?php
// Батырма басылғанда: менеджерді таңдап, басуды тіркеп, WhatsApp-қа жібереді.
define('APP', 1);
require __DIR__ . '/lib.php';
nocache();

$cfg = cfg();
$active = [];
foreach ($cfg['managers'] as $m) {
    if (!empty($m['active']) && !empty($m['phone'])) $active[] = $m;
}

$vid = clean_id(isset($_GET['vid']) ? $_GET['vid'] : (isset($_COOKIE['vid']) ? $_COOKIE['vid'] : ''));
$src = clean_src(isset($_GET['src']) ? $_GET['src'] : '');
if ($src === '') $src = guess_src();
$cmp = clean_src(isset($_GET['cmp']) ? $_GET['cmp'] : '');

$mgr = null;
$own = 0;
// Менеджердің жеке сілтемесі: ?m=m2 — тікелей сол менеджерге (белсенді болса)
$want = clean_id(isset($_GET['m']) ? $_GET['m'] : '');
if ($want !== '') {
    foreach ($active as $a) if ($a['id'] === $want) { $mgr = $a; $own = 1; }
}
if ($mgr) {
    // жеке сілтеме табылды
} elseif ($active) {
    // Бір адам қайта басса — сол менеджерге түседі
    $sticky = isset($_COOKIE['mgr']) ? $_COOKIE['mgr'] : '';
    foreach ($active as $a) if ($a['id'] === $sticky) $mgr = $a;
    if (!$mgr) {
        $i = ($cfg['mode'] === 'random') ? mt_rand(0, count($active) - 1) : next_rr(count($active));
        $mgr = $active[$i];
    }
} elseif (!empty($cfg['managers'][0]['phone'])) {
    $mgr = $cfg['managers'][0];
}

$phone = $mgr ? $mgr['phone'] : $cfg['fallback'];

if (!is_bot()) {
    log_event([
        't' => time(), 'e' => 'c', 'v' => $vid, 's' => $src, 'c' => $cmp, 'd' => device(),
        'm' => $mgr ? $mgr['id'] : '', 'mn' => $mgr ? $mgr['name'] : '—', 'o' => $own,
    ]);
}
if ($mgr) setcookie('mgr', $mgr['id'], time() + 30 * 86400, '/');

header('Location: https://wa.me/' . $phone . '?text=' . rawurlencode($cfg['message']), true, 302);
exit;
