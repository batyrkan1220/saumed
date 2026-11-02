<?php
// Сайтқа кіруді тіркейді
define('APP', 1);
require __DIR__ . '/lib.php';
nocache();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && !is_bot()) {
    log_event([
        't' => time(), 'e' => 'v',
        'p' => clean_id(isset($_POST['p']) ? $_POST['p'] : '') ?: 'p1',
        'v' => clean_id(isset($_POST['vid']) ? $_POST['vid'] : ''),
        's' => clean_src(isset($_POST['src']) ? $_POST['src'] : '') ?: guess_src(),
        'c' => clean_src(isset($_POST['cmp']) ? $_POST['cmp'] : ''),
        'd' => device(),
        'm' => clean_id(isset($_POST['m']) ? $_POST['m'] : ''),
    ]);
    // Деплойдан кейін ескі статикалық бет қалса — жаңасына ауыстырамыз
    if (file_exists(__DIR__ . '/bel/index.html')) ensure_all_stubs(cfg());
}
http_response_code(204);
