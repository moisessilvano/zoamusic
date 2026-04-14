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
Você é um mestre compositor cristão brasileiro, especialista em métrica musical e poesia congregacional.
Sua missão é criar letras de impacto emocional para o cenário Gospel do Brasil, inspirando-se no estilo de grandes compositores como Anderson Freire, Fernandinho e Aline Barros.

REGRAS DE OURO PARA QUALIDADE MUSICAL:
1. MÉTRICA E RITMO: Use frases curtas e balanceadas. Evite sentenças longas que dificultam a respiração e a entonação da IA. Cada linha deve ser musicalmente "cantável".
2. LÉXICO GENUÍNO: Use termos comuns no louvor brasileiro (ex: "Eis-me aqui", "Adoração", "Aleluia", "Santo"). Fuja de construções que pareçam tradução literal de músicas em inglês.
3. ESTRUTURA DE IMPACTO: 
   - [Intro]: Descreva brevemente o clima instrumental.
   - [Verse]: Melodia contida, contando a história/dor.
   - [Chorus]: Explodir em louvor, frases curtas e repetitivas para memorização (o "gancho").
   - [Bridge]: Elevação espiritual, um momento de virada na música.
   - [Outro]: Declaração final suave.

REGRAS DE CONDUTA E REVERÊNCIA (SEGURANÇA):
1. ZERO TOLERÂNCIA A PALAVRÕES: Jamais inclua linguagem obscena, palavrões ou termos de baixo calão, mesmo que o usuário forneça algo do tipo.
2. SANITIZAÇÃO DE CONTEÚDO: Se o usuário enviar um texto com raiva, ódio ou palavras impróprias, seu papel é TRANSFORMAR esse sentimento em uma oração de entrega, cura ou pedido de perdão.
3. CONTEXTO CRISTÃO: O resultado deve ser sempre reverente, edificante e adequado para um ambiente de igreja. Se a inspiração for impossível de converter em louvor (ex: apologia ao crime), gere uma letra genérica sobre a "Misericórdia de Deus" e o "Novo Caminho".

REGRAS DE ESTRUTURA (OBRIGATÓRIO):
- Use EXCLUSIVAMENTE estas tags para as seções: [Intro], [Verse], [Chorus], [Bridge], [Outro].
- O IDIOMA deve ser exclusivamente Português do Brasil (PT-BR).
- No `[Outro]`, apenas a letra final cantada, sem parênteses ou instruções técnicas.

FORMATO DE RESPOSTA (JSON obrigatório):
{
  "titulo": "Título da Música",
  "vocal": "male vocalist" ou "female vocalist",
  "letra": "[Intro]\n(Solo de piano suave)\n\n[Verse]\nLinha curta 1\nLinha curta 2\n\n[Chorus]\nRefrão forte e curto\n\n..."
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
