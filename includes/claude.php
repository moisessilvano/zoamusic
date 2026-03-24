<?php
// ============================================================
// LOUVOR.NET - Integração Claude (Anthropic)
// ============================================================

require_once __DIR__ . '/../config.php';

/**
 * Gera letra de música cristã baseada na inspiração do usuário.
 *
 * @param string $inspiracao Texto de inspiração do usuário
 * @return array{titulo: string, letra: string}
 * @throws RuntimeException em caso de falha na API
 */
function claude_gerar_letra(string $inspiracao): array {
    $system_prompt = <<<EOT
Você é um compositor cristão ungido, com profundo conhecimento das escrituras bíblicas.
Sua missão é transformar a história, dor, pedido ou versículo do usuário em uma letra
de música cristã contemporânea, de louvor e adoração.

REGRAS DE ESTRUTURA (OBRIGATÓRIO):
- Use EXCLUSIVAMENTE estas tags para as seções: [Intro], [Verse], [Chorus], [Bridge], [Outro].
- Não use "Estrofe 1", "Refrão", etc. Use apenas as tags acima em colchetes.
- O IDIOMA deve ser exclusivamente Português do Brasil (PT-BR).
- ESTILO: Louvor e Adoração Contemporâneo (Worship Brasileiro).
- DECISÃO DE VOZ: Com base na letra, decida se a música soaria melhor com "male vocalist" ou "female vocalist".

FORMATO DE RESPOSTA (JSON obrigatório):
{
  "titulo": "Título da Música",
  "vocal": "male vocalist" ou "female vocalist",
  "letra": "[Intro]\n(Instrumental piano)\n\n[Verse]\n[verso 1]\n\n[Chorus]\n[refrão]\n\n[Verse]\n[verso 2]\n\n[Chorus]\n[refrão]\n\n[Bridge]\n[ponte]\n\n[Chorus]\n[refrão final]\n\n[Outro]\n(Fim suave)"
}
EOT;

    $payload = [
        'model'      => ANTHROPIC_MODEL,
        'max_tokens' => 1024,
        'system'     => $system_prompt,
        'messages'   => [
            [
                'role'    => 'user',
                'content' => "Minha inspiração: {$inspiracao}",
            ],
        ],
    ];

    $ch = curl_init('https://api.anthropic.com/v1/messages');
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST           => true,
        CURLOPT_POSTFIELDS     => json_encode($payload),
        CURLOPT_HTTPHEADER     => [
            'Content-Type: application/json',
            'x-api-key: ' . ANTHROPIC_API_KEY,
            'anthropic-version: 2023-06-01',
        ],
        CURLOPT_TIMEOUT        => 60,
    ]);

    $response = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curl_error = curl_error($ch);
    curl_close($ch);

    if ($curl_error) {
        logger("Claude cURL error: {$curl_error}");
        throw new RuntimeException("Claude cURL error: {$curl_error}");
    }
    if ($http_code !== 200) {
        logger("Claude API error {$http_code}: {$response}");
        throw new RuntimeException("Claude API error {$http_code}: {$response}");
    }

    $data = json_decode($response, true);
    $content = $data['content'][0]['text'] ?? '';

    // Extrai o JSON da resposta (pode vir com texto extra)
    if (preg_match('/\{.*\}/s', $content, $matches)) {
        $parsed = json_decode($matches[0], true);
        if ($parsed && isset($parsed['titulo'], $parsed['letra'], $parsed['vocal'])) {
            return $parsed;
        }
    }

    throw new RuntimeException("Claude retornou formato inválido: {$content}");
}
