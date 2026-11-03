<?php
// TikTok жарнама шығынын қабылдайды (Make.com → HTTP сұраныс).
// Кез келген форматты түсінеді: бір жол, жолдар массиві, {rows:[...]}, TikTok API жауабы {data:{list:[...]}}.
define('APP', 1);
require __DIR__ . '/lib.php';
nocache();
header('Content-Type: application/json');

$c = cfg();
$k = isset($_GET['k']) ? (string)$_GET['k'] : '';
if (empty($c['spend_secret']) || !hash_equals($c['spend_secret'], $k)) { http_response_code(403); echo '{"ok":false,"error":"bad key"}'; exit; }

$raw = file_get_contents('php://input');
$body = json_decode($raw, true);
if (!is_array($body)) $body = $_POST ?: $_GET;

// Жолдарды табу
function sp_rows($b) {
    if (isset($b['data']['list']) && is_array($b['data']['list'])) return $b['data']['list'];
    foreach (['rows', 'list', 'data', 'items', 'array'] as $key) if (isset($b[$key]) && is_array($b[$key]) && isset($b[$key][0])) return $b[$key];
    if (isset($b[0]) && is_array($b[0])) return $b;
    return [$b];
}
// Бір жолдан өрісті алу (жалпақ немесе dimensions/metrics ішінде)
function sp_get($r, $names) {
    foreach ($names as $n) {
        if (isset($r[$n]) && $r[$n] !== '' && !is_array($r[$n])) return $r[$n];
        foreach (['dimensions', 'metrics'] as $g) if (isset($r[$g][$n]) && $r[$g][$n] !== '' && !is_array($r[$g][$n])) return $r[$g][$n];
    }
    return null;
}

$saved = 0; $errors = [];
$rows = sp_rows($body);
$upd = [];
foreach ($rows as $i => $r) {
    if (!is_array($r)) continue;
    $spend = sp_get($r, ['spend', 'cost', 'amount', 'расход']);
    $date = sp_get($r, ['stat_time_day', 'date', 'day', 'stat_datetime', 'дата']);
    $cid = sp_get($r, ['campaign_id', 'utm_id', 'id']);
    $cname = sp_get($r, ['campaign_name', 'campaign', 'name', 'кампания']);
    $cur = sp_get($r, ['currency']);
    if ($spend === null) { $errors[] = "row $i: no spend"; continue; }
    $spend = (float)str_replace([' ', ','], ['', '.'], (string)$spend);
    $d = $date ? substr(preg_replace('/[^0-9\-]/', '', str_replace('/', '-', (string)$date)), 0, 10) : ldate('Y-m-d', time());
    if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $d)) $d = ldate('Y-m-d', time());
    $cid = $cid !== null ? substr(preg_replace('/\D/', '', (string)$cid), 0, 25) : '';
    $cname = $cname !== null ? mb_substr(trim(strip_tags((string)$cname)), 0, 100) : '';
    if ($cid === '' && $cname === '') $cname = 'TikTok';
    $key = $d . '|' . ($cid !== '' ? $cid : 'n:' . mb_strtolower($cname));
    $upd[$key] = ['d' => $d, 'ci' => $cid, 'cn' => $cname, 's' => round($spend, 2), 'cur' => $cur ? substr((string)$cur, 0, 5) : '', 'src' => 'make', 't' => time()];
    $saved++;
}

if ($upd) {
    $sp = store_read('spend') ?: ['rows' => []];
    foreach ($upd as $key => $row) {   // сол күн/кампания қайта келсе — жаңартылады (атауы бос болса, бұрынғысы қалады)
        if ($row['cn'] === '' && isset($sp['rows'][$key]['cn'])) $row['cn'] = $sp['rows'][$key]['cn'];
        if ($row['cn'] === '') foreach ($sp['rows'] as $o) if ($o['ci'] === $row['ci'] && $o['cn'] !== '') { $row['cn'] = $o['cn']; break; }
        $sp['rows'][$key] = $row;
    }
    $sp['last'] = time();
    store_write('spend', $sp);
}
echo json_encode(['ok' => $saved > 0, 'saved' => $saved, 'errors' => array_slice($errors, 0, 5)], JSON_UNESCAPED_UNICODE);
