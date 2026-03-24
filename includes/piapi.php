<?php
// ============================================================
// LOUVOR.NET - Integração PiAPI (Udio) para geração de áudio
// ============================================================

require_once __DIR__ . '/../config.php';

/**
 * Dispara a geração de áudio no Udio via PiAPI Task.
 *
 * @param string $titulo      Título da música
 * @param string $letra       Letra completa gerada pelo Claude (com tags [Verse], etc)
 * @param string $vocal_type  Recomendação de voz (male vocalist / female vocalist)
 * @return string             task_id retornado pela PiAPI
 * @throws RuntimeException
 */
function piapi_gerar_audio(string $titulo, string $letra, string $vocal_type = 'male vocalist'): string {
    // Tags ultra-específicas para Udio/Udio v1.5 para garantir voz principal clara e gospel brasileiro
    // 'clear lead solo vocals' e 'front and center' são essenciais para evitar vozes de fundo
    $style = "brazilian portuguese, christian worship, contemporary gospel, {$vocal_type}, lead singer, clear lead solo vocals, front and center voice, acoustic piano, emotional, highly produced, professional studio quality";
    
    $payload = [
        'model'     => 'music-u', 
        'task_type' => 'generate_music',
        'input'     => [
            'title' => $titulo,
            // Enviamos a letra crua com as tags [Verse], [Chorus] etc que o Udio entende nativamente
            'lyrics' => $letra, 
            'gpt_description_prompt' => $style,
            'lyrics_type' => 'user', // Indica que estamos fornecendo a letra exata
            'seed' => -1
        ],
        'config' => [
            'service_mode' => 'public'
        ]
    ];

    $url = PIAPI_BASE_URL . '/task';
    logger("Chamando PiAPI: $url");
    
    $masked_key = substr(PIAPI_KEY, 0, 4) . '...' . substr(PIAPI_KEY, -4);
    logger("Usando Chave PiAPI: $masked_key");

    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST           => true,
        CURLOPT_POSTFIELDS     => json_encode($payload),
        CURLOPT_HTTPHEADER     => [
            'Content-Type: application/json',
            'x-api-key: ' . PIAPI_KEY,
        ],
        CURLOPT_TIMEOUT        => 30,
    ]);

    $response = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curl_error = curl_error($ch);
    curl_close($ch);

    if ($curl_error) {
        logger("PiAPI cURL error: {$curl_error}");
        throw new RuntimeException("PiAPI cURL error: {$curl_error}");
    }
    
    $data = json_decode($response, true);

    if ($http_code >= 400 || ($data['code'] ?? 0) !== 200) {
        $msg = $data['message'] ?? $response;
        logger("PiAPI API error {$http_code}: {$msg}");
        throw new RuntimeException("PiAPI error {$http_code}: {$msg}");
    }

    $task_id = $data['data']['task_id'] ?? null;

    if (!$task_id) {
        throw new RuntimeException("PiAPI não retornou task_id: " . json_encode($data));
    }

    return (string) $task_id;
}

/**
 * Verifica o status de uma task na PiAPI e retorna a URL do áudio se pronto.
 *
 * @param string $task_id
 * @return array{status: string, audio_url: string|null}
 */
function piapi_verificar_status(string $task_id): array {
    $ch = curl_init(PIAPI_BASE_URL . '/task/' . urlencode($task_id));
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER     => [
            'x-api-key: ' . PIAPI_KEY,
        ],
        CURLOPT_TIMEOUT        => 15,
    ]);

    $response = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($http_code !== 200) {
        return ['status' => 'processando', 'audio_url' => null];
    }

    $data = json_decode($response, true);
    $task_data = $data['data'] ?? [];
    
    $raw_status = strtolower($task_data['status'] ?? 'pending');
    
    // Procura por qualquer música gerada com sucesso no output
    $songs = $task_data['output']['songs'] ?? [];
    $audio_url = null;
    
    foreach ($songs as $song) {
        if (!empty($song['song_path'])) {
            $audio_url = $song['song_path'];
            break;
        }
        if (!empty($song['audio_url'])) {
            $audio_url = $song['audio_url'];
            break;
        }
    }

    if ($audio_url) {
        return ['status' => 'concluido', 'audio_url' => $audio_url];
    }

    if (in_array($raw_status, ['completed', 'succeeded', 'success'])) {
        $audio_url = $task_data['output']['audio_url'] ?? null;
        if ($audio_url) {
            return ['status' => 'concluido', 'audio_url' => $audio_url];
        }
    }

    if (in_array($raw_status, ['failed', 'error', 'timeout'])) {
        if (empty($songs)) {
            return ['status' => 'erro', 'audio_url' => null];
        }
    }

    return ['status' => 'processando', 'audio_url' => null];
}
