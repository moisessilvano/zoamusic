<?php
// ============================================================
// LOUVOR.NET - Integração E-mail
// Usa PHPMailer via SMTP se configurado, senão cai no mail()
// ============================================================

require_once __DIR__ . '/../config.php';

/**
 * Envia e-mail de notificação de música pronta.
 *
 * @param string $nome    Nome do destinatário
 * @param string $email   E-mail do destinatário
 * @param string $titulo  Título da música
 * @param string $link    Link encurtado da música
 */
function email_notificar_musica_pronta(string $nome, string $email, string $titulo, string $link): bool {
    if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        logger("Email: endereço inválido ou vazio — {$email}");
        return false;
    }

    $nome_curto = explode(' ', trim($nome))[0];
    if (!$nome_curto) $nome_curto = 'amigo(a)';

    $assunto = "🎵 Sua música \"{$titulo}\" está pronta! — LOUVOR.NET";

    $html = email_template_musica_pronta($nome_curto, $titulo, $link);

    // Tenta Resend API primeiro (recomendado)
    if (defined('RESEND_API_KEY') && RESEND_API_KEY) {
        return email_enviar_resend($email, $nome, $assunto, $html);
    }

    // Fallback: mail() nativo do PHP
    return email_enviar_native($email, $nome, $assunto, $html);
}

/**
 * Envio via Resend API (cURL)
 */
function email_enviar_resend(string $para, string $nome_para, string $assunto, string $html): bool {
    $from_email = defined('MAIL_FROM_EMAIL') && MAIL_FROM_EMAIL ? MAIL_FROM_EMAIL : 'contato@louvor.net';
    $from_name  = defined('MAIL_FROM_NAME')  && MAIL_FROM_NAME  ? MAIL_FROM_NAME  : 'LOUVOR.NET';

    $payload = [
        'from'    => "{$from_name} <{$from_email}>",
        'to'      => [$para],
        'subject' => $assunto,
        'html'    => $html
    ];

    $ch = curl_init('https://api.resend.com/emails');
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST           => true,
        CURLOPT_POSTFIELDS     => json_encode($payload),
        CURLOPT_HTTPHEADER     => [
            'Content-Type: application/json',
            'Authorization: Bearer ' . RESEND_API_KEY,
        ],
        CURLOPT_TIMEOUT => 15,
    ]);

    $response  = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curl_err  = curl_error($ch);
    curl_close($ch);

    if ($curl_err) {
        logger("Resend cURL error: {$curl_err}");
        return false;
    }

    if ($http_code >= 200 && $http_code < 300) {
        logger("Email enviado com sucesso via Resend para {$para}");
        return true;
    }

    logger("Resend erro HTTP {$http_code}: {$response}");
    return false;
}

/**
 * Envio via mail() nativo
 */
function email_enviar_native(string $para, string $nome_para, string $assunto, string $html): bool {
    $from_email = defined('MAIL_FROM_EMAIL') && MAIL_FROM_EMAIL ? MAIL_FROM_EMAIL : 'no-reply@louvor.net';
    $from_name  = defined('MAIL_FROM_NAME')  && MAIL_FROM_NAME  ? MAIL_FROM_NAME  : 'LOUVOR.NET';

    $headers  = "MIME-Version: 1.0\r\n";
    $headers .= "Content-type: text/html; charset=UTF-8\r\n";
    $headers .= "From: {$from_name} <{$from_email}>\r\n";
    $headers .= "Reply-To: {$from_email}\r\n";
    $headers .= "X-Mailer: PHP/" . phpversion() . "\r\n";

    $ok = mail($para, $assunto, $html, $headers);

    if ($ok) {
        logger("Email enviado com sucesso (mail()) para {$para}");
    } else {
        logger("Email falhou (mail()) para {$para}");
    }

    return $ok;
}

/**
 * Template HTML do e-mail de música pronta
 */
function email_template_musica_pronta(string $nome, string $titulo, string $link): string {
    $base_url    = rtrim(BASE_URL, '/');
    $logo_star   = '<span style="color:#C9A84C;font-size:28px;">★</span>';
    $year        = date('Y');

    return <<<HTML
<!DOCTYPE html>
<html lang="pt-BR">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Sua música está pronta! — LOUVOR.NET</title>
</head>
<body style="margin:0;padding:0;background:#F5F0E8;font-family:'Helvetica Neue',Arial,sans-serif;">

  <!-- Wrapper -->
  <table width="100%" cellpadding="0" cellspacing="0" border="0" style="background:#F5F0E8;padding:40px 16px;">
    <tr>
      <td align="center">
        <table width="600" cellpadding="0" cellspacing="0" border="0" style="max-width:600px;width:100%;background:#FFFFFF;border-radius:20px;overflow:hidden;box-shadow:0 8px 40px rgba(0,0,0,0.10);">

          <!-- Header dourado -->
          <tr>
            <td style="background:linear-gradient(135deg,#C9A84C 0%,#D4AF37 50%,#B8922A 100%);padding:40px 48px;text-align:center;">
              <p style="margin:0 0 8px;font-size:36px;line-height:1;">★</p>
              <h1 style="margin:0;font-size:28px;font-weight:700;color:#FFFFFF;letter-spacing:0.12em;font-family:Georgia,serif;">
                LOUVOR<span style="font-style:italic;">.NET</span>
              </h1>
              <p style="margin:8px 0 0;font-size:14px;color:rgba(255,255,255,0.85);letter-spacing:0.05em;">
                Sua história em um louvor eterno
              </p>
            </td>
          </tr>

          <!-- Corpo principal -->
          <tr>
            <td style="padding:48px 48px 32px;">
              <p style="margin:0 0 12px;font-size:22px;font-weight:700;color:#1C1917;font-family:Georgia,serif;">
                🎵 Sua música ficou pronta, {$nome}!
              </p>
              <p style="margin:0 0 24px;font-size:15px;line-height:1.7;color:#5C4A2A;">
                Nossa IA transformou sua história em um louvor exclusivo. A composição <strong>"{$titulo}"</strong> está pronta e esperando por você.
              </p>

              <!-- Card da música -->
              <table width="100%" cellpadding="0" cellspacing="0" border="0" style="background:linear-gradient(160deg,#FDFBF5,#FBF6E9);border:1.5px solid #E8D9A8;border-radius:16px;margin:0 0 32px;">
                <tr>
                  <td style="padding:24px 28px;text-align:center;">
                    <p style="margin:0 0 6px;font-size:12px;font-weight:700;color:#C9A84C;letter-spacing:0.1em;text-transform:uppercase;">Sua música exclusiva</p>
                    <p style="margin:0 0 20px;font-size:20px;font-weight:700;color:#1C1917;font-family:Georgia,serif;">"{$titulo}"</p>
                    <a href="{$link}"
                       style="display:inline-block;background:linear-gradient(135deg,#C9A84C,#D4AF37,#B8922A);color:#FFFFFF;font-weight:700;font-size:16px;text-decoration:none;padding:16px 40px;border-radius:50px;letter-spacing:0.05em;">
                      🎵 Ouvir Minha Música
                    </a>
                    <p style="margin:16px 0 0;font-size:12px;color:#A08060;">
                      Ou acesse: <a href="{$link}" style="color:#C9A84C;word-break:break-all;">{$link}</a>
                    </p>
                  </td>
                </tr>
              </table>

              <!-- Versículo -->
              <table width="100%" cellpadding="0" cellspacing="0" border="0" style="background:rgba(201,168,76,0.07);border-left:3px solid #C9A84C;border-radius:0 8px 8px 0;margin:0 0 32px;">
                <tr>
                  <td style="padding:16px 20px;">
                    <p style="margin:0;font-size:14px;font-style:italic;color:#8B6914;font-family:Georgia,serif;">
                      "Cantai ao Senhor um cântico novo, cantai ao Senhor todas as terras."
                    </p>
                    <p style="margin:6px 0 0;font-size:12px;font-weight:700;color:#B8922A;">— Salmos 96:1</p>
                  </td>
                </tr>
              </table>

              <p style="margin:0;font-size:14px;line-height:1.7;color:#6B5B3E;">
                Você pode baixar o MP3 e compartilhar com quem ama. Que essa música toque corações!
              </p>
            </td>
          </tr>

          <!-- Footer -->
          <tr>
            <td style="background:#FDFBF5;border-top:1px solid #F0E8CC;padding:24px 48px;text-align:center;">
              <p style="margin:0 0 8px;font-size:13px;font-weight:700;color:#1C1917;letter-spacing:0.1em;">LOUVOR.NET</p>
              <p style="margin:0;font-size:12px;color:#A08060;line-height:1.6;">
                Você recebeu este e-mail porque solicitou a criação de uma música personalizada em louvor.net.<br>
                © {$year} LOUVOR.NET — Todos os direitos reservados.
              </p>
            </td>
          </tr>

        </table>
      </td>
    </tr>
  </table>

</body>
</html>
HTML;
}
