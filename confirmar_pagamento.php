<?php
// ============================================================
// LOUVOR.NET - Confirmar Pagamento e Disparar Geração (POST)
// ============================================================

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/includes/claude.php';
require_once __DIR__ . '/includes/piapi.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    die('Método não permitido.');
}

$uid = trim($_POST['uid'] ?? '');

if (!preg_match('/^[0-9a-f]{8}-[0-9a-f]{4}-4[0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$/i', $uid)) {
    http_response_code(400);
    die('UID inválido.');
}

$stmt = db()->prepare('SELECT * FROM musicas WHERE id = ?');
$stmt->execute([$uid]);
$musica = $stmt->fetch();

if (!$musica) {
    http_response_code(404);
    die('Música não encontrada.');
}

// Já concluído? Redireciona direto
if ($musica['status'] === 'concluido') {
    header('Location: ouvir.php?uid=' . urlencode($uid));
    exit;
}

// Marca como processando
$stmt = db()->prepare("UPDATE musicas SET status = 'processando' WHERE id = ?");
$stmt->execute([$uid]);

// Redireciona imediatamente para a tela de processamento
// A geração real acontece em background via processando.php
header('Location: processando.php?uid=' . urlencode($uid));
exit;
