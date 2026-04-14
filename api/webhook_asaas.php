<?php
// ============================================================
// LOUVOR.NET - Webhook Asaas (Recebimento de PIX)
// ============================================================

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../db.php';

// Proteção simples por Token (configure no painel do Asaas)
// Recomendo definir ASAAS_WEBHOOK_TOKEN no seu .env
$token_esperado = ASAAS_WEBHOOK_TOKEN;
$token_recebido = $_SERVER['HTTP_ASAAS_ACCESS_TOKEN'] ?? '';

if (!empty($token_esperado) && $token_recebido !== $token_esperado) {
    http_response_code(401);
    die('Não autorizado.');
}

$payload = json_decode(file_get_contents('php://input'), true);
$event   = $payload['event'] ?? '';
$payment = $payload['payment'] ?? [];
$uid     = $payment['externalReference'] ?? ''; // O UUID que enviamos ao criar o PIX

if (empty($uid) || !in_array($event, ['PAYMENT_RECEIVED', 'PAYMENT_CONFIRMED'])) {
    // Apenas ignora outros eventos (ex: cobrança criada)
    echo "Evento ignorado ou sem referência.";
    exit;
}

logger("Webhook Asaas: Recebido pagamento para música [{$uid}].");

$stmt = db()->prepare('SELECT id, status FROM musicas WHERE id = ?');
$stmt->execute([$uid]);
$musica = $stmt->fetch();

if (!$musica) {
    logger("Webhook Asaas Erro: Música [{$uid}] não encontrada no banco.");
    http_response_code(404);
    exit;
}

// Se já estiver processando ou concluído, não faz nada
if ($musica['status'] !== 'aguardando_pagamento') {
    logger("Webhook Asaas: Música [{$uid}] já está em status '{$musica['status']}'.");
    echo "Já processada.";
    exit;
}

// ATUALIZA STATUS PARA PROCESSANDO
$stmt = db()->prepare("UPDATE musicas SET status = 'processando' WHERE id = ?");
$stmt->execute([$uid]);

// DISPARA O TRABALHO DE GERAÇÃO (CLAUDE + SUNO)
// Como o Webhook roda em background, precisamos de uma URL secreta para o worker
$secret = hash_hmac('sha256', $uid, ASAAS_API_KEY);
$worker_url = rtrim(BASE_URL, '/') . "/api/gerar_musica.php?uid={$uid}&secret={$secret}";

// Chama o worker via CURL de forma não-bloqueante (fire and forget)
$ch = curl_init($worker_url);
curl_setopt($ch, CURLOPT_TIMEOUT, 1);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, false);
curl_exec($ch);
curl_close($ch);

logger("Webhook Asaas: Geração disparada via worker para [{$uid}].");
echo "OK. Pagamento confirmado e geração iniciada.";
