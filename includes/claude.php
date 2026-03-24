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
- ESTILO (OBRIGATÓRIO): Gospel / louvor evangélico BRASILEIRO — melodia vocal cantável (frases claras, como canções de igreja no Brasil), tom emotivo mas congregacional; evite cadência ou léxico que soe “worship americano traduzido”.
- DECISÃO DE VOZ: Com base na letra, decida se a música soaria melhor com "male vocalist" ou "female vocalist".
- [Outro] deve conter UMA FRASE FINAL CANTADA (letra), que encerre com cadência. Não coloque apenas instruções tipo "(Fim suave)" ou "(encerramento)".
- No `[Outro]`, NÃO inclua parênteses nem instruções de produção; apenas a letra cantada.
- A letra deve ser escrita como se fosse para um solista principal (sem indicar coros/grupos na letra).

FORMATO DE RESPOSTA (JSON obrigatório):
Responda SOMENTE com JSON válido (sem blocos ``` e sem texto fora do JSON).
No campo "letra", use `\\n` para representar as quebras de linha (isto é: caracteres “\” + “n” dentro da string).
NÃO insira quebras de linha reais dentro da string entre aspas.
{
  "titulo": "Título da Música",
  "vocal": "male vocalist" ou "female vocalist",
  "letra": "[Intro]\n(Instrumental piano)\n\n[Verse]\n[verso 1]\n\n[Chorus]\n[refrão]\n\n[Verse]\n[verso 2]\n\n[Chorus]\n[refrão]\n\n[Bridge]\n[ponte]\n\n[Chorus]\n[refrão final]\n\n[Outro]\nAmém, amém...\n"
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
        $json = trim($matches[0]);
        $parsed = json_decode($json, true);

        // Fallback: às vezes o Claude coloca quebras de linha reais dentro da string "letra",
        // o que torna o JSON inválido. Tentamos converter isso para \n dentro da string.
        if (!$parsed) {
            $fixed = preg_replace_callback(
                '/("letra"\s*:\s*")(.+?)(")(\s*,|\s*})/s',
                function ($m) {
                    $val = $m[2];
                    $val = str_replace(["\r\n", "\r", "\n"], "\\n", $val);
                    return $m[1] . $val . $m[3] . $m[4];
                },
                $json,
                1
            );
            if (is_string($fixed) && $fixed !== $json) {
                $parsed = json_decode($fixed, true);
            }
        }
        if ($parsed && isset($parsed['titulo'], $parsed['letra'], $parsed['vocal'])) {
            return $parsed;
        }
    }

    throw new RuntimeException("Claude retornou formato inválido: {$content}");
}
