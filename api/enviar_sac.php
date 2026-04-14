<?php
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../db.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['ok' => false, 'error' => 'Método não permitido.']);
    exit;
}

$nome     = trim($_POST['nome'] ?? '');
$email    = trim($_POST['email'] ?? '');
$whatsapp = trim($_POST['whatsapp'] ?? '');
$assunto  = trim($_POST['assunto'] ?? '');
$mensagem = trim($_POST['mensagem'] ?? '');
$mid      = trim($_POST['musica_id'] ?? '');

if (empty($nome) || empty($email) || empty($assunto) || empty($mensagem)) {
    echo json_encode(['ok' => false, 'error' => 'Por favor, preencha todos os campos obrigatórios.']);
    exit;
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    echo json_encode(['ok' => false, 'error' => 'E-mail inválido.']);
    exit;
}

// Verifica se o ID da música é válido se fornecido
if ($mid && !preg_match('/^[0-9a-f-]{36}$/i', $mid)) {
    $mid = null;
}

try {
    $stmt = db()->prepare('INSERT INTO sac_mensagens (nome, email, whatsapp, assunto, mensagem, musica_id) VALUES (?, ?, ?, ?, ?, ?)');
    $stmt->execute([$nome, $email, $whatsapp ?: null, $assunto, $mensagem, $mid ?: null]);
    
    echo json_encode(['ok' => true, 'message' => 'Sua mensagem foi enviada com sucesso! Responderemos em breve.']);
} catch (Exception $e) {
    logger("Erro ao salvar mensagem SAC: " . $e->getMessage());
    echo json_encode(['ok' => false, 'error' => 'Ocorreu um erro ao enviar sua mensagem. Tente novamente mais tarde.']);
}
