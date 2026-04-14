<?php
// ============================================================
// LOUVOR.NET - Redirect de Link Curto (/s/CODIGO)
// ============================================================
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../db.php';
require_once __DIR__ . '/../includes/shortlink.php';

$code = preg_replace('/[^a-zA-Z0-9]/', '', $_GET['code'] ?? '');

if ($code) {
    $url = shortlink_resolver($code);
    if ($url) {
        header('Location: ' . $url, true, 302);
        exit;
    }
}

http_response_code(404);
header('Location: ' . BASE_URL);
exit;
