<?php
// ============================================================
// LOUVOR.NET - Integração SunoAPI.org (Interface Estável)
// ============================================================

require_once __DIR__ . '/../config.php';

/**
 * Dispara a geração de áudio na SunoAPI.org.
 */
function suno_gerar_audio(string $titulo, string $letra, string $vocal_type = 'male vocalist'): string {
    $vocal = str_contains(strtolower($vocal_type), 'female') ? 'f' : 'm';
    $style = "Brazilian Gospel, Contemporary Christian Music, Worship, Soulful, Powerful, Piano, Acoustic Guitar, Studio Quality, Radio-ready";

    $payload = [
        'customMode'   => true,
        'instrumental' => false,
        'model'        => 'V3_5',
        'callBackUrl'  => 'https://example.com/callback',
        'prompt'       => $letra,
        'style'        => $style,
        'title'        => $titulo,
        'vocalGender'  => $vocal
    ];

    $ch = curl_init(SUNO_API_URL . '/generate');
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST           => true,
        CURLOPT_POSTFIELDS     => json_encode($payload),
        CURLOPT_HTTPHEADER     => [
            'Content-Type: application/json',
            'Authorization: Bearer ' . SUNO_API_KEY,
        ],
        CURLOPT_TIMEOUT => 30,
    ]);

    $response = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curl_err = curl_error($ch);
    curl_close($ch);

    if ($curl_err) {
        logger("SunoAPI.org cURL Error: " . $curl_err);
        throw new RuntimeException("Erro de rede ao conectar na SunoAPI: " . $curl_err);
    }

    $data = json_decode($response, true);
    
    $task_id = $data['data']['taskId'] ?? null;

    if ($http_code >= 400 || !$task_id) {
        $msg = $data['msg'] ?? "HTTP $http_code";
        logger("SunoAPI.org Error Response: " . $response);
        throw new RuntimeException("Falha ao iniciar geração: " . $msg);
    }

    return (string) $task_id;
}

/**
 * Verifica o status da geração por taskId.
 */
function suno_verificar_status(string $task_id): array {
    // Busca detalhes da task no endpoint correto
    $ch = curl_init(SUNO_API_URL . '/generate/record-info?taskId=' . urlencode($task_id));
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER     => [
            'Authorization: Bearer ' . SUNO_API_KEY,
        ],
        CURLOPT_TIMEOUT => 15,
    ]);

    $response = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($http_code !== 200) {
        return ['status' => 'processando', 'audio_url' => null];
    }

    $data = json_decode($response, true);
    
    // Na SunoAPI.org, record-info retorna os dados estruturados
    $task_data = $data['data']['data'] ?? $data['data'] ?? [];
    $status_str = strtoupper($task_data['status'] ?? 'PENDING');

    if ($status_str === 'SUCCESS') {
        // Tenta pegar a URL direta ou dentro da lista de clips
        if (!empty($task_data['audioUrl'])) {
            return ['status' => 'concluido', 'audio_url' => $task_data['audioUrl']];
        }

        $clips = $task_data['clips'] ?? [];
        foreach ($clips as $clip) {
            if (!empty($clip['audioUrl'])) {
                return ['status' => 'concluido', 'audio_url' => $clip['audioUrl']];
            }
        }
    }

    if (in_array($status_str, ['FAILED', 'ERROR'])) {
        return ['status' => 'erro', 'audio_url' => null];
    }

    return ['status' => 'processando', 'audio_url' => null];
}
