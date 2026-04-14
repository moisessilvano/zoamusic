<?php
// ============================================================
// LOUVOR.NET - API: Polling Suno (chamado pelo frontend)
// Verifica UMA VEZ o status do job no Suno e atualiza o banco.
// Timeout curto (4s) para não bloquear o servidor.
// ============================================================

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../db.php';
require_once __DIR__ . '/../includes/suno.php';
require_once __DIR__ . '/../includes/shortlink.php';
require_once __DIR__ . '/../includes/zenvia.php';

header('Content-Type: application/json');

$uid = trim($_GET['uid'] ?? '');

if (!preg_match('/^[0-9a-f]{8}-[0-9a-f]{4}-4[0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$/i', $uid)) {
    echo json_encode(['ok' => false]);
    exit;
}

$stmt = db()->prepare('SELECT task_id, audio_url, status, nome, telefone, sms_enviado, titulo FROM musicas WHERE id = ?');
$stmt->execute([$uid]);
$musica = $stmt->fetch();

// Nada a fazer se já concluído, com erro ou sem task_id
if (!$musica || $musica['status'] === 'concluido' || $musica['status'] === 'erro' || empty($musica['task_id'])) {
    echo json_encode(['ok' => true, 'skipped' => true]);
    exit;
}

// Consulta o Suno com timeout curto — libera o servidor rápido
$result = suno_verificar_status($musica['task_id'], $uid, 4);

if ($result['status'] === 'concluido' && $result['audio_url']) {
    $stmt = db()->prepare("UPDATE musicas SET audio_url = ?, status = 'concluido' WHERE id = ?");
    $stmt->execute([$result['audio_url'], $uid]);
    logger("poll_suno [{$uid}]: Áudio concluído! URL: {$result['audio_url']}");

    // Gera/recupera o link encurtado
    $long_url = rtrim(BASE_URL, '/') . '/ouvir.php?uid=' . urlencode($uid);
    $code     = shortlink_criar($long_url, $uid);
    $link     = shortlink_url($code);

    // Salva o short_code no DB
    db()->prepare('UPDATE musicas SET short_code = ? WHERE id = ?')->execute([$code, $uid]);

    // Dispara SMS pelo Zenvia (apenas uma vez)
    if (!empty($musica['telefone']) && empty($musica['sms_enviado'])) {
        $nome = $musica['nome'] ?: 'amigo(a)';
        $tit  = $musica['titulo'] ?: 'Sua música';
        if (zenvia_notificar_musica_pronta($nome, $musica['telefone'], $tit, $link)) {
            db()->prepare('UPDATE musicas SET sms_enviado = 1 WHERE id = ?')->execute([$uid]);
        }
    }

    echo json_encode(['ok' => true, 'done' => true]);
    exit;
}

if ($result['status'] === 'erro') {
    $stmt = db()->prepare("UPDATE musicas SET status = 'erro' WHERE id = ?");
    $stmt->execute([$uid]);
    logger("poll_suno [{$uid}]: Suno retornou erro.");
    echo json_encode(['ok' => true, 'done' => true, 'error' => true]);
    exit;
}

echo json_encode(['ok' => true, 'done' => false]);
