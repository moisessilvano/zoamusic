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
function piapi_build_style_prompt(string $vocal_type): string {
    $vt = strtolower(trim($vocal_type));
    $natural = str_contains($vt, 'female')
        ? 'warm female Brazilian gospel lead singer, natural relaxed phrasing, spoken-to-sung transitions, human and intimate, not robotic, light natural vibrato'
        : 'warm male Brazilian gospel lead singer, natural relaxed phrasing, spoken-to-sung transitions, human and intimate, not robotic, light natural vibrato';

    // Estilo coerente: muitos "no X / extremely Y / highly produced" no mesmo prompt costumam deixar voz sintética ou forçada.
    // IMPORTANTE: idioma vem primeiro para ter o maior peso na interpretação do modelo.
    $parts = [
        'IDIOMA EXCLUSIVO: português do Brasil (PT-BR), toda a música cantada em português brasileiro, zero palavras em inglês na voz, nenhuma sílaba inventada',
        'louvor gospel evangélico brasileiro contemporâneo, música cristã nacional, louvor e adoração',
        $natural,
        'voz principal solo na frente da mixagem; coral suave apenas nos refrões, bem atrás da voz principal',
        'arranjo: piano acústico, violão, baixo elétrico suave, bateria estilo igreja brasileira',
        'produção: clima de culto ao vivo, dinâmica orgânica, sem compressão excessiva, sem autotune pesado',
        'final: cadência melódica, sustentar última frase brevemente, fade suave e natural',
    ];

    return implode(', ', $parts);
}

function piapi_gerar_audio(string $titulo, string $letra, string $vocal_type = 'male vocalist'): string {
    $style = piapi_build_style_prompt($vocal_type);
    
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
    
    // Se a task falhou/expirou, não aceitamos "parciais" para evitar áudio corrompido ou idioma inválido.
    if (in_array($raw_status, ['failed', 'error', 'timeout'])) {
        return ['status' => 'erro', 'audio_url' => null];
    }

    // Procura por qualquer música gerada com sucesso no output
    $songs = $task_data['output']['songs'] ?? [];
    $selected_url = null;
    $best_score = -PHP_INT_MAX;
    $selected_debug = null;

    foreach ($songs as $i => $song) {
        $url = !empty($song['song_path']) ? $song['song_path'] : (!empty($song['audio_url']) ? $song['audio_url'] : null);
        if (!$url) continue;

        $title = (string)($song['title'] ?? $song['name'] ?? $song['song_title'] ?? '');
        $title_l = mb_strtolower($title);

        // Heurística: evitar versões puramente instrumentais e preferir as que pareçam ter voz/lead.
        $score = 0;
        if (!empty($song['song_path'])) $score += 2;
        if (preg_match('/vocal|lead|lyrics|full/i', $title)) $score += 5;
        if (preg_match('/\binstrumental\b|\binstr\b|\binst\b|acapella|no vocals|without vocals|sidetracks|instrumental version/i', $title_l)) $score -= 6;
        if (preg_match('/(choir|group|background|backing)/i', $title)) $score -= 2; // evita "só backing/ambiente"

        if ($score > $best_score) {
            $best_score = $score;
            $selected_url = $url;
            $selected_debug = ['index' => $i, 'title' => $title, 'score' => $score];
        }
    }

    // Fallback se não achou nada em songs
    $audio_url = $selected_url;

    if ($audio_url) {
        if ($selected_debug) {
            logger("PiAPI selecionou output: " . json_encode($selected_debug, JSON_UNESCAPED_UNICODE));
        }
        return ['status' => 'concluido', 'audio_url' => $audio_url];
    }

    if (in_array($raw_status, ['completed', 'succeeded', 'success'])) {
        $audio_url = $task_data['output']['audio_url'] ?? null;
        if ($audio_url) {
            return ['status' => 'concluido', 'audio_url' => $audio_url];
        }
    }

    return ['status' => 'processando', 'audio_url' => null];
}
