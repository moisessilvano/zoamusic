<?php
// ============================================================
// LOUVOR.NET - Script de Simulação de Geração (CLI)
// ============================================================

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/includes/claude.php';
require_once __DIR__ . '/includes/suno.php';

echo "--- Iniciando Simulação de Geração LOUVOR.NET ---\n";

$inspiracao = "Gostaria de um musica sobre gratidão pela vida, sobre dificuldades financeiras e na familia";
$uid = uuid4();

echo "[1/4] Inserindo registro no banco de dados (ID: $uid)...\n";
$stmt = db()->prepare('INSERT INTO musicas (id, inspiracao, status) VALUES (?, ?, ?)');
$stmt->execute([$uid, $inspiracao, 'processando']);

try {
    // ETAPA 1: Letra com Claude
    echo "[2/4] Solicitando letra ao Claude (Anthropic)...\n";
    $resultado = claude_gerar_letra($inspiracao);
    $titulo = $resultado['titulo'];
    $letra  = $resultado['letra'];
    $vocal   = $resultado['vocal'] ?? 'male vocalist';
    
    echo "  - Título: $titulo\n";
    echo "  - Vocal: {$vocal}\n";
    echo "  - Letra gerada com sucesso.\n";

    $stmt = db()->prepare('UPDATE musicas SET titulo = ?, letra = ? WHERE id = ?');
    $stmt->execute([$titulo, $letra, $uid]);

    // ETAPA 2: Áudio com PiAPI
    echo "[3/4] Solicitando áudio ao Suno (Direto Oficial)...\n";
    $task_id = suno_gerar_audio($titulo, $letra, $vocal);
    echo "  - Task ID: $task_id\n";

    $stmt = db()->prepare('UPDATE musicas SET task_id = ? WHERE id = ?');
    $stmt->execute([$task_id, $uid]);

    // ETAPA 3: Polling
    echo "[4/4] Iniciando polling (esperando áudio ficar pronto)...\n";
    $max_attempts = 120; // 10 minutos
    $done = false;
    for ($attempt = 1; $attempt <= $max_attempts; $attempt++) {
        sleep(5);
        $status = suno_verificar_status($task_id);
        
        echo "  - Tentativa $attempt: Status = " . $status['status'] . "\n";

        if ($status['status'] === 'concluido' && $status['audio_url']) {
            $stmt = db()->prepare("UPDATE musicas SET audio_url = ?, status = 'concluido' WHERE id = ?");
            $stmt->execute([$status['audio_url'], $uid]);
            echo "\n✨ MÚSICA GERADA COM SUCESSO!\n";
            echo "URL do Áudio: " . $status['audio_url'] . "\n";
            $done = true;
            break;
        }

        if ($status['status'] === 'erro') {
            $stmt = db()->prepare("UPDATE musicas SET status = 'erro' WHERE id = ?");
            $stmt->execute([$uid]);
            echo "\n❌ ERRO NA GERAÇÃO DO ÁUDIO.\n";
            break;
        }

        if ($attempt === $max_attempts) {
            echo "\n⌛ TIMEOUT ATINGIDO.\n";
        }
    }

    if (!$done) {
        $stmt = db()->prepare("UPDATE musicas SET status = 'erro' WHERE id = ?");
        $stmt->execute([$uid]);
    }

} catch (Exception $e) {
    echo "\n❌ EXCEÇÃO: " . $e->getMessage() . "\n";
    $stmt = db()->prepare("UPDATE musicas SET status = 'erro' WHERE id = ?");
    $stmt->execute([$uid]);
}

echo "--- Fim da Simulação ---\n";
