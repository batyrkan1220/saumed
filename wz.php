<?php
// Wazzup24 webhook: клиент WhatsApp-қа жазғанда және менеджер жауап бергенде шақырылады.
define('APP', 1);
require __DIR__ . '/lib.php';
nocache();
header('Content-Type: application/json');

$c = cfg();
$k = isset($_GET['k']) ? (string)$_GET['k'] : '';
if (empty($c['wz_secret']) || !hash_equals($c['wz_secret'], $k)) { http_response_code(403); echo '{}'; exit; }

$body = json_decode(file_get_contents('php://input'), true);
// Wazzup тексеру сұранысы ({test:true}) — кілт әлі сақталмаса да 200 қайтарамыз
if (!is_array($body) || !empty($body['test'])) { http_response_code(200); echo '{"ok":true}'; exit; }
if (empty($c['wz_key'])) { echo '{"ok":true}'; exit; }   // ажыратылған болса — ештеңе жазбаймыз
if (empty($body['messages']) || !is_array($body['messages'])) { echo '{"ok":true}'; exit; }

// Админде тіркелген менеджерлердің нөмірлері
$myPhones = [];
foreach ($c['projects'] as $pp) foreach ($pp['managers'] as $mm) if (!empty($mm['phone'])) $myPhones[$mm['phone']] = 1;
if (!$myPhones) { echo '{"ok":true}'; exit; }

$channels = isset($c['wz_channels']) && is_array($c['wz_channels']) ? $c['wz_channels'] : [];

foreach ($body['messages'] as $msg) {
    $type = isset($msg['chatType']) ? $msg['chatType'] : '';
    if ($type !== 'whatsapp' && $type !== 'wapi') continue;            // тек жеке WhatsApp чаттар
    if (!empty($msg['isEdited']) || !empty($msg['isDeleted'])) continue;
    $chId = isset($msg['channelId']) ? $msg['channelId'] : '';
    $client = preg_replace('/\D/', '', isset($msg['chatId']) ? $msg['chatId'] : '');
    if ($client === '' || $chId === '') continue;

    // Канал нөмірі белгісіз болса — тізімді жаңартамыз
    if (!isset($channels[$chId])) {
        list($ch, $err) = wz_channels($c['wz_key']);
        if ($ch) {
            $channels = $ch;
            $c2 = cfg(); $c2['wz_channels'] = $ch; cfg_save($c2);
        }
    }
    $phone = isset($channels[$chId]['phone']) ? $channels[$chId]['phone'] : '';
    // Тек админде тіркелген менеджер нөмірлері — басқа каналдар мүлде есептелмейді
    if ($phone === '' || !isset($myPhones[$phone])) continue;
    $key = $chId . ':' . $client;
    $now = time();
    $inbound = empty($msg['isEcho']) && (isset($msg['status']) ? $msg['status'] === 'inbound' : true);
    $text = isset($msg['text']) ? (string)$msg['text'] : '';
    $mtype = isset($msg['type']) ? $msg['type'] : 'text';
    $name = isset($msg['contact']['name']) ? mb_substr((string)$msg['contact']['name'], 0, 60) : '';
    $route = wz_route($c, $phone, $inbound ? $text : '');
    $preview = $text !== '' ? $text : ['image' => '📷 Фото', 'video' => '🎥 Видео', 'audio' => '🎤 Аудио', 'document' => '📎 Файл', 'missing_call' => '📞 Пропущенный звонок', 'geo' => '📍 Геолокация', 'vcard' => '👤 Контакт'][$mtype] ?? '💬 Сообщение';

    // Чат базасын жаңарту: соңғы хабарлама, бірінші кіріс хабарлама, алғашқы жауап
    $res = wz_chats_update(function (&$d) use ($key, $now, $inbound, $client, $chId, $name, $route, $preview) {
        $new = !isset($d[$key]);
        if ($new) $d[$key] = ['t' => $now, 'cl' => $client, 'ch' => $chId];
        $x = &$d[$key];
        if (!$new && !isset($x['fi']) && !empty($x['p'])) $x['fi'] = $x['t'];   // ескі жазбалар
        if (empty($x['p']) && $route[0]) { $x['p'] = $route[0]['id']; $x['m'] = $route[1] ? $route[1]['id'] : ''; }
        if (empty($x['cl'])) $x['cl'] = $client;
        $x['last'] = $now;
        $x['lt'] = mb_substr($preview, 0, 90);
        $x['ld'] = $inbound ? 'in' : 'out';
        if ($inbound && $name !== '') $x['nm'] = $name;
        $firstIn = false; $firstReply = false;
        if ($inbound && empty($x['fi'])) { $x['fi'] = $now; $firstIn = true; }
        if (!$inbound && !empty($x['fi']) && empty($x['r'])) { $x['r'] = $now; $firstReply = true; }
        return [$firstIn, $firstReply, $x];
    });
    if (!$res) continue;
    list($firstIn, $firstReply, $chat) = $res;
    if (empty($chat['p'])) continue;
    $pid = $chat['p'];

    // Хабарламаны чат тарихына сақтау
    msg_log(['t' => $now, 'ck' => $key, 'p' => $pid, 'd' => $inbound ? 'in' : 'out', 'ty' => $mtype,
             'tx' => mb_substr($text, 0, 4000), 'u' => isset($msg['contentUri']) ? (string)$msg['contentUri'] : '',
             'au' => isset($msg['authorName']) ? mb_substr((string)$msg['authorName'], 0, 60) : '']);

    if ($firstIn) {
        // Клиент алғаш рет жазды — нақты өтінім
        $P = null; foreach ($c['projects'] as $pp) if ($pp['id'] === $pid) $P = $pp;
        $M = null; if ($P) foreach ($P['managers'] as $mm) if ($mm['id'] === (isset($chat['m']) ? $chat['m'] : '')) $M = $mm;
        $tn = norm_text($text); $mn0 = norm_text($P ? $P['message'] : '');
        $st = ($mn0 !== '' && $tn !== '' && (strpos($tn, $mn0) === 0 || strpos($mn0, $tn) === 0)) ? 1 : 0;   // сайттан (автоматты мәтін)
        log_event(['t' => $now, 'e' => 'w', 'p' => $pid, 'st' => $st, 'm' => $M ? $M['id'] : '', 'mn' => $M ? $M['name'] : $phone,
                   'cl' => $client, 'tx' => mb_substr($text, 0, 60), 'ck' => $key]);
    }
    if ($firstReply) {
        // Менеджердің алғашқы жауабы — жауап уақыты
        log_event(['t' => $now, 'e' => 'r', 'p' => $pid, 'm' => isset($chat['m']) ? $chat['m'] : '',
                   'rs' => max(0, $now - (int)$chat['fi']), 'ck' => $key]);
    }
}
echo '{"ok":true}';
