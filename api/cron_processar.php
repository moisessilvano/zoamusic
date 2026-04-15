<?php
// ============================================================
// LOUVOR.NET - Cron Job de Processamento Automático
// Responsabilidade: Finalizar músicas e baixar MP3s pendentes.
// ============================================================

// Aumenta tempo de execução para processar várias músicas se necessário
set_time_limit(300); 

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../db.php';
require_once __DIR__ . '/../includes/suno.php';
require_once __DIR__ . '/../includes/shortlink.php';
require_once __DIR__ . '/../includes/zenvia.php';
require_once __DIR__ . '/../includes/email.php';

// Apenas executa se for via CLI ou se uma chave secreta for passada (segurança)
if (php_sapi_name() !== 'cli' && ($_GET['secret'] ?? '') !== ASAAS_WEBHOOK_TOKEN) {
    die("Acesso negado.");
}

$pdo = db();

echo "--- Iniciando Cron Louvor.net: " . date('Y-m-d H:i:s') . " ---\n";

// 1. Procurar músicas que estão 'processando' e já possuem um task_id
$stmt = $pdo->prepare("SELECT * FROM musicas WHERE status = 'processando' AND task_id IS NOT NULL AND audio_url NOT LIKE 'assets/musicas/%'");
$stmt->execute();
$pendentes = $stmt->fetchAll();

echo "Encontradas " . count($pendentes) . " músicas para verificar.\n";

foreach ($pendentes as $m) {
    $uid = $m['id'];
    echo "Verificando [{$uid}]... ";

    // Consulta o Suno (timeout curto)
    $result = suno_verificar_status($m['task_id'], $uid, 5);

    if ($result['status'] === 'concluido' && $result['audio_url']) {
        // Download local do áudio
        $local_path = 'assets/musicas/' . $uid . '.mp3';
        $dir = __DIR__ . '/../assets/musicas';
        if (!is_dir($dir)) mkdir($dir, 0777, true);

        $ch = curl_init($result['audio_url']);
        $fp = fopen(__DIR__ . '/../' . $local_path, 'wb');
        curl_setopt($ch, CURLOPT_FILE, $fp);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 60);
        curl_exec($ch);
        curl_close($ch);
        fclose($fp);

        if (filesize(__DIR__ . '/../' . $local_path) > 0) {
            // Sucesso no download, finaliza música
            $pdo->prepare("UPDATE musicas SET audio_url = ?, status = 'concluido' WHERE id = ?")
                ->execute([$local_path, $uid]);
            
            // Gera link curto se não existir
            $long_url = rtrim(BASE_URL, '/') . '/ouvir.php?uid=' . urlencode($uid);
            $code     = shortlink_criar($long_url, $uid);
            $link     = shortlink_url($code);
            $pdo->prepare('UPDATE musicas SET short_code = ? WHERE id = ?')->execute([$code, $uid]);

            // Notifica via SMS se tiver telefone e ainda não enviado
            if (!empty($m['telefone']) && empty($m['sms_enviado'])) {
                $nome = $m['nome'] ?: 'amigo(a)';
                $tit  = $m['titulo'] ?: 'Sua música';
                if (zenvia_notificar_musica_pronta($nome, $m['telefone'], $tit, $link)) {
                    $pdo->prepare('UPDATE musicas SET sms_enviado = 1 WHERE id = ?')->execute([$uid]);
                }
            }

            // Notifica via E-mail se tiver e-mail e ainda não enviado
            if (!empty($m['email']) && empty($m['email_enviado'])) {
                $nome = $m['nome'] ?: 'amigo(a)';
                $tit  = $m['titulo'] ?: 'Sua música';
                if (email_notificar_musica_pronta($nome, $m['email'], $tit, $link)) {
                    $pdo->prepare('UPDATE musicas SET email_enviado = 1 WHERE id = ?')->execute([$uid]);
                }
            }
            echo "CONCLUÍDO E SALVO.\n";
        } else {
            echo "ERRO NO DOWNLOAD.\n";
        }
    } elseif ($result['status'] === 'erro') {
        $pdo->prepare("UPDATE musicas SET status = 'erro' WHERE id = ?")->execute([$uid]);
        echo "FALHOU NO SUNO.\n";
    } else {
        echo "AINDA PROCESSANDO.\n";
    }
}

echo "--- Fim do Cron ---\n";
