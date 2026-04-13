<?php
// ============================================================
// LOUVOR.NET - API: Verifica status da música (Polling)
// ============================================================

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../db.php';
require_once __DIR__ . '/../includes/suno.php';

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

// Se já tem audio_url, está concluído
if ($musica['status'] === 'concluido' && $musica['audio_url']) {
    echo json_encode([
        'status'    => 'concluido',
        'audio_url' => $musica['audio_url'],
        'has_letra' => !empty($musica['letra']),
    ]);
    exit;
}

// Se tem task_id, verifica no Suno
if (!empty($musica['task_id'])) {
    try {
        $suno_status = suno_verificar_status($musica['task_id'], $uid);

        if ($suno_status['status'] === 'concluido' && $suno_status['audio_url']) {
            // Atualiza o banco com a URL do áudio
            $stmt = db()->prepare(
                "UPDATE musicas SET audio_url = ?, status = 'concluido' WHERE id = ?"
            );
            $stmt->execute([$suno_status['audio_url'], $uid]);

            echo json_encode([
                'status'    => 'concluido',
                'audio_url' => $suno_status['audio_url'],
                'has_letra' => !empty($musica['letra']),
            ]);
            exit;
        }

        if ($suno_status['status'] === 'erro') {
            $stmt = db()->prepare("UPDATE musicas SET status = 'erro' WHERE id = ?");
            $stmt->execute([$uid]);
        }
    } catch (RuntimeException $e) {
        // Erro de rede, mantém processando
    }
}

echo json_encode([
    'status'    => $musica['status'],
    'audio_url' => $musica['audio_url'],
    'has_letra' => !empty($musica['letra']),
]);
