<?php
// ============================================================
// ZOA MUSIC - Integração Zenvia (SMS)
// Docs: https://zenvia.github.io/zenvia-openapi-spec/v2/
// ============================================================

require_once __DIR__ . '/../config.php';

/**
 * Envia SMS via Zenvia.
 *
 * @param string $para    Número no formato internacional sem + (ex: 5511999999999)
 * @param string $mensagem Texto do SMS (máx 160 caracteres por segmento)
 * @return bool true em caso de sucesso
 */
function zenvia_enviar_sms(string $para, string $mensagem): bool {
    $token = ZENVIA_TOKEN;
    if (!$token || $token === 'SEU_TOKEN_ZENVIA_AQUI') {
        logger("Zenvia: token não configurado — SMS não enviado para {$para}");
        return false;
    }

    // Remove tudo que não é dígito e garante DDI 55
    $numero = preg_replace('/\D/', '', $para);
    if (strlen($numero) <= 11) {
        $numero = '55' . ltrim($numero, '0');
    }

    $payload = [
        'from'     => ZENVIA_FROM,
        'to'       => $numero,
        'contents' => [
            ['type' => 'text', 'text' => $mensagem],
        ],
    ];

    $ch = curl_init('https://api.zenvia.com/v2/channels/sms/messages');
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST           => true,
        CURLOPT_POSTFIELDS     => json_encode($payload),
        CURLOPT_HTTPHEADER     => [
            'Content-Type: application/json',
            'X-API-TOKEN: ' . $token,
        ],
        CURLOPT_TIMEOUT => 10,
    ]);

    $response  = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curl_err  = curl_error($ch);
    curl_close($ch);

    if ($curl_err) {
        logger("Zenvia cURL error: {$curl_err}");
        return false;
    }

    if ($http_code >= 200 && $http_code < 300) {
        logger("Zenvia SMS enviado com sucesso para {$numero} (HTTP {$http_code})");
        return true;
    }

    logger("Zenvia erro HTTP {$http_code}: {$response}");
    return false;
}

/**
 * Monta e envia o SMS de notificação de música pronta.
 *
 * @param string $nome     Nome do destinatário
 * @param string $telefone Telefone (qualquer formato BR)
 * @param string $titulo   Título da música
 * @param string $link     Link encurtado da música
 */
function zenvia_notificar_musica_pronta(string $nome, string $telefone, string $titulo, string $link): bool {
    $nome_curto = explode(' ', trim($nome))[0]; // só o primeiro nome
    $mensagem = "Oi {$nome_curto}! 🎵 Sua música \"{$titulo}\" está pronta no ZOA MUSIC! Ouça agora: {$link}";

    // Trunca para 160 chars se necessário
    if (mb_strlen($mensagem) > 160) {
        $mensagem = mb_substr($mensagem, 0, 157) . '...';
    }

    return zenvia_enviar_sms($telefone, $mensagem);
}
