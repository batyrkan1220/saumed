<?php
// Батырма басылғанда: проекттің менеджерін таңдап, басуды тіркеп, WhatsApp-қа жібереді.
define('APP', 1);
require __DIR__ . '/lib.php';
nocache();

$c = cfg();
$pid = clean_id(isset($_GET['p']) ? $_GET['p'] : '');
$pi = proj_index($c, 'id', $pid);
if ($pi < 0) $pi = 0;                       // белгісіз болса — бірінші проект
$P = $c['projects'][$pi];
$pid = $P['id'];

$active = [];
foreach ($P['managers'] as $m) if (!empty($m['active']) && !empty($m['phone'])) $active[] = $m;

$vid = clean_id(isset($_GET['vid']) ? $_GET['vid'] : (isset($_COOKIE['vid']) ? $_COOKIE['vid'] : ''));
$src = clean_src(isset($_GET['src']) ? $_GET['src'] : '');
if ($src === '') $src = guess_src();
$cmp = clean_src(isset($_GET['cmp']) ? $_GET['cmp'] : '');

$mgr = null; $own = 0;
$want = clean_id(isset($_GET['m']) ? $_GET['m'] : '');
if ($want !== '') foreach ($active as $a) if ($a['id'] === $want) { $mgr = $a; $own = 1; }
$ck = 'mgr_' . $pid;
if (!$mgr && $active) {
    $sticky = isset($_COOKIE[$ck]) ? $_COOKIE[$ck] : '';
    foreach ($active as $a) if ($a['id'] === $sticky) $mgr = $a;
    if (!$mgr) $mgr = $active[$P['mode'] === 'random' ? mt_rand(0, count($active) - 1) : next_rr($pid, count($active))];
}
if (!$mgr && !empty($P['managers'][0]['phone'])) $mgr = $P['managers'][0];

if (!$mgr) {
    http_response_code(503);
    header('Content-Type: text/html; charset=UTF-8');
    echo '<!DOCTYPE html><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><p style="font:18px sans-serif;text-align:center;margin-top:30vh">Кешіріңіз, қазір менеджер жоқ. Кейінірек жазыңыз.</p>';
    exit;
}

if (!is_bot()) {
    log_event(['t' => time(), 'e' => 'c', 'p' => $pid, 'v' => $vid, 's' => $src, 'c' => $cmp, 'd' => device(),
               'm' => $mgr['id'], 'mn' => $mgr['name'], 'o' => $own]);
}
setcookie($ck, $mgr['id'], time() + 30 * 86400, '/');

header('Location: https://wa.me/' . $mgr['phone'] . '?text=' . rawurlencode($P['message']), true, 302);
exit;
