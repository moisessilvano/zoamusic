<?php
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../db.php';

$nome  = 'Administrador';
$email = 'admin@louvor.net';
$senha = 'mudar123'; // mude após o primeiro login

$hash = password_hash($senha, PASSWORD_DEFAULT);

try {
    $stmt = db()->prepare('INSERT INTO admin_users (nome, email, senha_hash) VALUES (?, ?, ?)');
    $stmt->execute([$nome, $email, $hash]);
    echo "Usuário admin criado com sucesso!\n";
    echo "E-mail: {$email}\n";
    echo "Senha: {$senha}\n";
    echo "Importante: delete este arquivo (admin/seed_admin.php) após o uso.\n";
} catch (Exception $e) {
    echo "Erro: " . $e->getMessage() . "\n";
}
