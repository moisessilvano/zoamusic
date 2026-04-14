<?php
// ============================================================
// LOUVOR.NET - API: Verifica status da música (Polling)
// Apenas lê o banco — o worker (gerar_musica.php) é o único
// responsável por fazer polling na Suno e atualizar o status.
// Chamar a API da Suno aqui bloquearia o servidor a cada
// requisição do browser (a cada 5s).
// ============================================================

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../db.php';

header('Content-Type: application/json');

$uid = trim($_GET['uid'] ?? '');

if (!preg_match('/^[0-9a-f]{8}-[0-9a-f]{4}-4[0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$/i', $uid)) {
    echo json_encode(['status' => 'erro', 'message' => 'UID inválido']);
    exit;
}

$stmt = db()->prepare('SELECT status, letra, audio_url, task_id FROM musicas WHERE id = ?');
$stmt->execute([$uid]);
$musica = $stmt->fetch();

if (!$musica) {
    echo json_encode(['status' => 'erro', 'message' => 'Não encontrado']);
    exit;
}

echo json_encode([
    'status'    => $musica['status'],
    'audio_url' => $musica['audio_url'],
    'has_letra' => !empty($musica['letra']),
    'has_task'  => !empty($musica['task_id']),
]);
