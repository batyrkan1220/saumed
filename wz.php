<?php
// Wazzup24 webhook: клиент WhatsApp-қа жазғанда және менеджер жауап бергенде шақырылады.
define('APP', 1);
require __DIR__ . '/lib.php';
nocache();
header('Content-Type: application/json');

$c = cfg();
$k = isset($_GET['k']) ? (string)$_GET['k'] : '';
if (empty($c['wz_key']) || empty($c['wz_secret']) || !hash_equals($c['wz_secret'], $k)) { http_response_code(403); echo '{}'; exit; }

$body = json_decode(file_get_contents('php://input'), true);
if (!is_array($body) || !empty($body['test'])) { echo '{"ok":true}'; exit; }   // Wazzup тексеру сұранысы
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
    $text = isset($msg['text']) ? $msg['text'] : '';

    if ($inbound) {
        // Клиенттен келген хабарлама: бірінші рет жазса — жаңа өтінім
        $isNew = wz_chats_update(function (&$d) use ($key, $now) {
            if (isset($d[$key])) { $d[$key]['last'] = $now; return false; }
            $d[$key] = ['t' => $now, 'last' => $now];
            return true;
        });
        if ($isNew) {
            list($P, $M) = wz_route($c, $phone, $text);
            if (!$P) continue;
            $pid = $P['id'];
            wz_chats_update(function (&$d) use ($key, $pid, $M) { $d[$key]['p'] = $pid; $d[$key]['m'] = $M ? $M['id'] : ''; });
            log_event(['t' => $now, 'e' => 'w', 'p' => $pid, 'm' => $M ? $M['id'] : '', 'mn' => $M ? $M['name'] : $phone,
                       'cl' => $client, 'tx' => mb_substr((string)$text, 0, 60), 'ck' => $key]);
        }
    } else {
        // Менеджер жауап берді: алғашқы жауап уақытын жазамыз
        $res = wz_chats_update(function (&$d) use ($key, $now) {
            if (!isset($d[$key]) || !empty($d[$key]['r'])) return null;
            $d[$key]['r'] = $now;
            return $d[$key];
        });
        if ($res && !empty($res['p'])) {
            log_event(['t' => $now, 'e' => 'r', 'p' => $res['p'], 'm' => isset($res['m']) ? $res['m'] : '',
                       'rs' => max(0, $now - (int)$res['t']), 'ck' => $key]);
        }
    }
}
echo '{"ok":true}';
