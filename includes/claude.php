<?php
// ============================================================
// ZOA MUSIC - Integração Claude (Anthropic)
// ============================================================

require_once __DIR__ . '/../config.php';

function claude_gerar_letra(string $inspiracao, string $estilo = 'Pop Brasil'): array {
    $system_prompt = <<<EOT
Você é um compositor brasileiro especialista em músicas de zoeira e humor. Sua missão é criar letras engraçadas, irônicas e bem-humoradas para "zuar" amigos, colegas e familiares.

ESTILO MUSICAL SOLICITADO: {$estilo}

REGRAS DE QUALIDADE MUSICAL:
1. MÉTRICA E RITMO: Adapte o ritmo e as frases ao estilo solicitado. Funk tem frases curtas e repetitivas, MPB tem frases mais elaboradas, Sertanejo tem dois versos rimados, etc.
2. TOM: Engraçado, irônico e bem-humorado. Como uma brincadeira entre amigos. Não pode ser cruel ou ofensivo de forma séria.
3. ESTRUTURA:
   - [Intro]: Introdução instrumental ou contextualização cômica.
   - [Verse]: Conta a história/situação engraçada com detalhes.
   - [Chorus]: O refrão - a zoada principal, curta e fácil de repetir.
   - [Bridge]: Uma virada cômica ou declaração final mais dramática.
   - [Outro]: Encerramento humorístico.

REGRAS DE CONDUTA:
1. HUMOR RESPEITOSO: Zoeira no estilo "amigos que se bicam". Sem conteúdo racista, homofóbico, misógino ou que incite ódio real.
2. CRIATIVIDADE: Transforme a situação em algo cômico mesmo que o input seja sério. O objetivo é sempre fazer rir.
3. REFERÊNCIAS BRASILEIRAS: Use gírias, referências culturais e expressões típicas do estilo musical escolhido.
4. ADAPTAÇÃO AO ESTILO:
   - Funk Carioca: Frases curtas, batidão, "é o seguinte", referências à quebrada
   - Sertanejo: Dupla, caipira, viola, "meu amor", sofrência cômica
   - MPB: Poético, metáforas criativas, mais elaborado
   - Forró: Nordestino, sanfona, "vixe maria", "oxente"
   - Pagode: Gírias do samba, malandro, "parceiro"
   - Trap BR: Camelot, slang atual, "mano", referências urbanas
   - Pisadinha: Dançante, repetitivo, "rebolando"
   - Rock: Mais rebelde, refs ao rock nacional
   - Axé: Festivo, carnaval, Bahia, movimento
   - Pop BR: Contemporâneo, jovem, relatável

ESTRUTURA (OBRIGATÓRIO):
- Use EXCLUSIVAMENTE estas tags: [Intro], [Verse], [Chorus], [Bridge], [Outro].
- IDIOMA: Português do Brasil (PT-BR) com gírias do estilo escolhido.
- No campo "vocal", prefira "male vocalist" para estilos mais graves (Funk, Pagode, Rock) e "female vocalist" para estilos mais agudos (Axé, Pisadinha, Pop), ajustando pela inspiração.

FORMATO DE RESPOSTA (JSON obrigatório):
{
  "titulo": "Título Engraçado da Música",
  "vocal": "male vocalist" ou "female vocalist",
  "letra": "[Intro]\n(Descrição do clima)\n\n[Verse]\nLinha cômica 1\nLinha cômica 2\n\n[Chorus]\nRefrão engraçado\n\n..."
}
EOT;

    $payload = [
        'model'      => ANTHROPIC_MODEL,
        'max_tokens' => 1024,
        'system'     => $system_prompt,
        'messages'   => [
            [
                'role'    => 'user',
                'content' => "Crie uma música de zoeira com base nessa história: {$inspiracao}",
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

    if (preg_match('/\{.*\}/s', $content, $matches)) {
        $json = trim($matches[0]);
        $parsed = json_decode($json, true);

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
