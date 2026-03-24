<?php
// ============================================================
// LOUVOR.NET - Integração PiAPI (Suno) para geração de áudio
// ============================================================

require_once __DIR__ . '/../config.php';

/**
 * Dispara a geração de áudio no Suno via PiAPI.
 *
 * @param string $titulo  Título da música
 * @param string $letra   Letra completa gerada pelo Claude
 * @return string         task_id retornado pela PiAPI
 * @throws RuntimeException
 */
function piapi_gerar_audio(string $titulo, string $letra): string {
    $payload = [
        'title'           => $titulo,
        'lyrics'          => $letra,
        'style'           => 'christian worship, contemporary gospel, piano, emotional',
        'make_instrumental' => false,
    ];

    $ch = curl_init(PIAPI_BASE_URL . '/music');
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST           => true,
        CURLOPT_POSTFIELDS     => json_encode($payload),
        CURLOPT_HTTPHEADER     => [
            'Content-Type: application/json',
            'X-API-Key: ' . PIAPI_KEY,
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
    if ($http_code !== 200 && $http_code !== 201) {
        logger("PiAPI API error {$http_code}: {$response}");
        throw new RuntimeException("PiAPI error {$http_code}: {$response}");
    }

    $data = json_decode($response, true);

    // A PiAPI retorna task_id ou id dependendo da versão
    $task_id = $data['task_id'] ?? $data['id'] ?? null;

    if (!$task_id) {
        throw new RuntimeException("PiAPI não retornou task_id: {$response}");
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
    $ch = curl_init(PIAPI_BASE_URL . '/music/' . urlencode($task_id));
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER     => [
            'X-API-Key: ' . PIAPI_KEY,
        ],
        CURLOPT_TIMEOUT        => 15,
    ]);

    $response = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($http_code !== 200) {
        return ['status' => 'erro', 'audio_url' => null];
    }

    $data = json_decode($response, true);

    // Mapear status da PiAPI para status interno
    $raw_status = strtolower($data['status'] ?? 'pending');

    if (in_array($raw_status, ['completed', 'succeeded', 'success'])) {
        // O áudio pode estar em data.output, clips[0].audio_url, etc.
        $audio_url = $data['output']['audio_url']
            ?? $data['clips'][0]['audio_url']
            ?? $data['audio_url']
            ?? null;

        return ['status' => 'concluido', 'audio_url' => $audio_url];
    }

    if (in_array($raw_status, ['failed', 'error'])) {
        return ['status' => 'erro', 'audio_url' => null];
    }

    return ['status' => 'processando', 'audio_url' => null];
}
