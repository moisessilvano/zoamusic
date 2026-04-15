<?php
// ============================================================
// LOUVOR.NET — Teste de Envio de E-mail
// Acesse via browser ou CLI para verificar se o e-mail chega.
// REMOVA este arquivo do servidor de produção após os testes!
// ============================================================

// Segurança mínima: só permite acesso local / por chave
$secret = $_GET['key'] ?? '';
if (php_sapi_name() !== 'cli' && $secret !== 'louvor_test_2025') {
    http_response_code(403);
    die('Acesso negado. Use: ?key=louvor_test_2025');
}

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/includes/email.php';

// ─────────────────────────────────────────────
// ✏️  CONFIGURE AQUI O E-MAIL DE DESTINO E OS DADOS
// ─────────────────────────────────────────────
$email_destino = 'SEU_EMAIL_AQUI@gmail.com';   // <- altere para seu e-mail real
$nome_cliente  = 'Moisés';
$titulo_musica = 'Minha Graça é Suficiente';
$link_musica   = rtrim(BASE_URL, '/') . '/ouvir.php?uid=teste-00000000-0000-0000-0000-000000000001';
// ─────────────────────────────────────────────

echo "<pre style='font-family:monospace; padding:20px; background:#1a1a1a; color:#d4af37;'>\n";
echo "LOUVOR.NET — Teste de E-mail\n";
echo str_repeat('─', 50) . "\n";
echo "Destinatário : {$email_destino}\n";
echo "Nome         : {$nome_cliente}\n";
echo "Título       : {$titulo_musica}\n";
echo "Link         : {$link_musica}\n";
echo str_repeat('─', 50) . "\n";
echo "SMTP Host    : " . (MAIL_SMTP_HOST ?: '(usando mail() nativo)') . "\n";
echo "From         : " . MAIL_FROM_NAME . " <" . MAIL_FROM_EMAIL . ">\n";
echo str_repeat('─', 50) . "\n\n";

if ($email_destino === 'SEU_EMAIL_AQUI@gmail.com') {
    echo "⚠️  ATENÇÃO: Altere a variável \$email_destino para um e-mail real antes de testar!\n";
    exit;
}

echo "Enviando e-mail...\n";
$ok = email_notificar_musica_pronta($nome_cliente, $email_destino, $titulo_musica, $link_musica);

echo "\n";
if ($ok) {
    echo "✅ E-mail enviado com sucesso para: {$email_destino}\n";
    echo "   Verifique sua caixa de entrada (e o spam).\n";
} else {
    echo "❌ Falha ao enviar e-mail. Veja logs/app.log para detalhes.\n";
}

echo "\n" . str_repeat('─', 50) . "\n";
echo "Log path: " . __DIR__ . "/logs/app.log\n";
echo "</pre>";
