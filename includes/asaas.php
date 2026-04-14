<?php
// ============================================================
// LOUVOR.NET - Integração Asaas (PIX)
// ============================================================

require_once __DIR__ . '/../config.php';

/**
 * Cria ou busca o cliente padrão na Asaas (CPF genérico para PoC).
 * Em produção, colete CPF/nome real do usuário.
 *
 * @param string|null $nome Nome do cliente
 * @return string customer_id da Asaas
 */
function asaas_obter_customer(string $cpf, ?string $nome = null): string {
    $payload = [
        'name'  => $nome ? trim($nome) : 'Cliente LOUVOR.NET',
        'email' => 'cliente@louvor.net',
        'cpfCnpj' => preg_replace('/\D/', '', $cpf),
    ];

    $response = asaas_request('POST', '/customers', $payload);

    return $response['id'];
}

/**
 * Cria uma cobrança PIX na Asaas e retorna os dados de pagamento.
 *
 * @param string $musica_uuid UUID da música (usado como referência)
 * @param string $cpf CPF do pagador
 * @return array{asaas_id: string, pix_key: string, qr_code_image: string}
 */
function asaas_criar_pix(string $musica_uuid, string $cpf): array {
    // Busca nome no DB se existir
    $stmt = db()->prepare('SELECT nome FROM musicas WHERE id = ?');
    $stmt->execute([$musica_uuid]);
    $nome = $stmt->fetchColumn();

    $customer_id = asaas_obter_customer($cpf, $nome ?: null);

    $due_date = date('Y-m-d', strtotime('+1 day'));

    $payload = [
        'customer'     => $customer_id,
        'billingType'  => 'PIX',
        'value'        => MUSICA_PRICE,
        'dueDate'      => $due_date,
        'description'  => MUSICA_DESCRIPTION,
        'externalReference' => $musica_uuid,
    ];

    $cobranca = asaas_request('POST', '/payments', $payload);
    $payment_id = $cobranca['id'];

    // Busca o QR Code PIX
    $pix_data = asaas_request('GET', "/payments/{$payment_id}/pixQrCode");

    return [
        'asaas_id'       => $payment_id,
        'pix_key'        => $pix_data['payload'] ?? '',
        'qr_code_image'  => $pix_data['encodedImage'] ?? '',
    ];
}

/**
 * Verifica se um pagamento foi recebido/confirmado no Asaas.
 *
 * @param string $payment_id ID da cobrança no Asaas
 * @return bool True se pago, False caso contrário
 */
function asaas_verificar_pagamento(string $payment_id): bool {
    try {
        $cobranca = asaas_request('GET', "/payments/{$payment_id}");
        $status = $cobranca['status'] ?? '';

        // Status que indicam pagamento confirmado
        return in_array($status, ['RECEIVED', 'CONFIRMED', 'RECEIVED_IN_CASH']);
    } catch (Exception $e) {
        logger("Erro ao verificar pagamento Asaas [{$payment_id}]: " . $e->getMessage());
        return false;
    }
}

/**
 * Helper para requisições à API Asaas.
 *
 * @param string $method  GET | POST
 * @param string $path    Endpoint (ex: /payments)
 * @param array  $body    Body para POST
 * @return array
 */
function asaas_request(string $method, string $path, array $body = []): array {
    $url = asaas_url() . $path;

    $ch = curl_init($url);
    $headers = [
        'Content-Type: application/json',
        'access_token: ' . ASAAS_API_KEY,
        'User-Agent: LOUVOR.NET/1.0',
    ];

    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER     => $headers,
        CURLOPT_TIMEOUT        => 30,
    ]);

    if ($method === 'POST') {
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($body));
    }

    $response = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curl_error = curl_error($ch);
    curl_close($ch);

    if ($curl_error) {
        throw new RuntimeException("Asaas cURL error: {$curl_error}");
    }
    if ($http_code >= 400) {
        $details = json_decode($response, true);
        $msg = "Erro {$http_code}: ";
        if (isset($details['errors']) && is_array($details['errors'])) {
            $msgs = array_map(fn($e) => $m = $e['description'] ?? $e['code'] ?? 'Erro desconhecido', $details['errors']);
            $msg .= implode(" | ", $msgs);
        } else {
            $msg .= $response;
        }
        throw new RuntimeException($msg);
    }

    return json_decode($response, true) ?? [];
}
