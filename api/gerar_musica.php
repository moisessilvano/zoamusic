<?php
// ============================================================
// LOUVOR.NET - Worker: Geração de Letra + Submissão Suno
// Responsabilidade: Claude (letra) + Suno submit (task_id).
// NÃO faz polling — o frontend chama poll_suno.php para isso.
// ============================================================

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../db.php';
require_once __DIR__ . '/../includes/claude.php';
require_once __DIR__ . '/../includes/suno.php';

// Libera a conexão HTTP para o browser imediatamente
if (function_exists('fastcgi_finish_request')) {
    fastcgi_finish_request();
} else {
    header('Connection: close');
    header('Content-Length: 0');
    ob_end_flush();
    flush();
}

set_time_limit(120); // Claude + Suno submit: máx 2 min
ignore_user_abort(true);

// Valida
$uid    = trim($_GET['uid'] ?? '');
$secret = trim($_GET['secret'] ?? '');
if (!hash_equals(hash_hmac('sha256', $uid, ASAAS_API_KEY), $secret)) {
    exit;
}

logger("Worker: Iniciado", $uid);

$stmt = db()->prepare('SELECT * FROM musicas WHERE id = ? AND status = ?');
$stmt->execute([$uid, 'processando']);
$musica = $stmt->fetch();

if (!$musica || !empty($musica['task_id'])) {
    logger("Worker ignorado [{$uid}]: já em andamento ou inexistente.");
    exit;
}

try {
    // ETAPA 1: Claude gera a letra
    logger("Worker [{$uid}]: Solicitando letra ao Claude...");
    $resultado = claude_gerar_letra($musica['inspiracao']);
    $titulo = $resultado['titulo'];
    $letra  = $resultado['letra'];
    $vocal  = $resultado['vocal'] ?? 'male vocalist';

    $stmt = db()->prepare('UPDATE musicas SET titulo = ?, letra = ? WHERE id = ?');
    $stmt->execute([$titulo, $letra, $uid]);
    logger("Worker [{$uid}]: Letra salva: {$titulo} (voz: {$vocal})");


    // ETAPA 2: Submete o job ao Suno (não aguarda conclusão)
    logger("Worker [{$uid}]: Submetendo job ao Suno...");
    $task_id = suno_gerar_audio($titulo, $letra, $vocal, $uid);

    $stmt = db()->prepare('UPDATE musicas SET task_id = ? WHERE id = ?');
    $stmt->execute([$task_id, $uid]);
    logger("Worker [{$uid}]: Suno task_id salvo: {$task_id}. Worker encerrado — polling delegado ao frontend.");

} catch (RuntimeException $e) {
    logger("Worker [{$uid}] ERRO: " . $e->getMessage());
    error_log("LOUVOR.NET Worker [{$uid}]: " . $e->getMessage());
    $stmt = db()->prepare("UPDATE musicas SET status = 'erro' WHERE id = ?");
    $stmt->execute([$uid]);
}

