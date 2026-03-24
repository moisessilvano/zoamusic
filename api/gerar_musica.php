<?php
// ============================================================
// LOUVOR.NET - Worker: Geração de Letra + Áudio (Background)
// Chamado via fire-and-forget pela processando.php
// ============================================================

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../db.php';
require_once __DIR__ . '/../includes/claude.php';
require_once __DIR__ . '/../includes/piapi.php';

// Fecha a conexão HTTP para liberar o cliente imediatamente (non-blocking)
if (function_exists('fastcgi_finish_request')) {
    fastcgi_finish_request();
} else {
    // Para PHP-FPM ou Apache mod_php
    header('Connection: close');
    header('Content-Length: 0');
    ob_end_flush();
    flush();
}

// Aumenta o tempo máximo de execução para este worker
set_time_limit(300);

// Valida a requisição
$uid    = trim($_GET['uid'] ?? '');
$secret = trim($_GET['secret'] ?? '');
$expected = hash_hmac('sha256', $uid, ASAAS_API_KEY);

if (!preg_match('/^[0-9a-f]{8}-[0-9a-f]{4}-4[0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$/i', $uid)) {
    exit;
}

if (!hash_equals($expected, $secret)) {
    exit; // Segurança: apenas chamadas internas
}

// Busca a música
$stmt = db()->prepare('SELECT * FROM musicas WHERE id = ? AND status = ?');
$stmt->execute([$uid, 'processando']);
$musica = $stmt->fetch();

if (!$musica || !empty($musica['task_id'])) {
    logger("Worker ignorado para [{$uid}]: música não existe ou task_id já presente.");
    exit; // Não existe ou já foi iniciada
}

try {
    logger("Worker iniciado para [{$uid}].");

    // ETAPA 1: Claude gera a letra
    logger("Worker [{$uid}]: Solicitando letra ao Claude...");
    $resultado = claude_gerar_letra($musica['inspiracao']);
    $titulo = $resultado['titulo'];
    $letra  = $resultado['letra'];

    // Salva letra no banco
    $stmt = db()->prepare('UPDATE musicas SET titulo = ?, letra = ? WHERE id = ?');
    $stmt->execute([$titulo, $letra, $uid]);
    logger("Worker [{$uid}]: Letra gerada e salva: {$titulo}");

    // ETAPA 2: PiAPI (Suno) gera o áudio
    logger("Worker [{$uid}]: Solicitando áudio ao PiAPI...");
    $task_id = piapi_gerar_audio($titulo, $letra);

    // Salva task_id no banco
    $stmt = db()->prepare('UPDATE musicas SET task_id = ? WHERE id = ?');
    $stmt->execute([$task_id, $uid]);
    logger("Worker [{$uid}]: Task PiAPI criada: {$task_id}");

    // ETAPA 3: Polling do áudio (até 5 minutos)
    $max_attempts = 60; // 60 × 5s = 5 minutos
    logger("Worker [{$uid}]: Iniciando polling do áudio...");
    for ($attempt = 0; $attempt < $max_attempts; $attempt++) {
        sleep(5);

        $status = piapi_verificar_status($task_id);

        if ($status['status'] === 'concluido' && $status['audio_url']) {
            $stmt = db()->prepare(
                "UPDATE musicas SET audio_url = ?, status = 'concluido' WHERE id = ?"
            );
            $stmt->execute([$status['audio_url'], $uid]);
            logger("Worker [{$uid}]: Áudio concluído com sucesso! URL: {$status['audio_url']}");
            break;
        }

        if ($status['status'] === 'erro') {
            $stmt = db()->prepare("UPDATE musicas SET status = 'erro' WHERE id = ?");
            $stmt->execute([$uid]);
            logger("Worker [{$uid}]: PiAPI retornou erro na geração.");
            break;
        }

        if ($attempt === $max_attempts - 1) {
            logger("Worker [{$uid}]: Timeout atingido após 5 minutos de espera.");
        }
    }

} catch (RuntimeException $e) {
    // Loga o erro e marca como falha
    logger("Worker [{$uid}] ERRO: " . $e->getMessage());
    error_log('LOUVOR.NET Worker Error [' . $uid . ']: ' . $e->getMessage());
    $stmt = db()->prepare("UPDATE musicas SET status = 'erro' WHERE id = ?");
    $stmt->execute([$uid]);
}
