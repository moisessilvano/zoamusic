<?php
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../db.php';

echo "Iniciando preparação do novo banco de dados...\n";

try {
    $pdo = db();
    $sql = file_get_contents(__DIR__ . '/schema.sql');
    
    // O PDO não gosta de executar múltiplos statements em uma única chamada de exec() por padrão.
    // Vamos tentar separar por ';'
    $commands = explode(';', $sql);
    
    foreach ($commands as $cmd) {
        $cmd = trim($cmd);
        if (empty($cmd)) continue;
        $pdo->exec($cmd);
    }
    echo "✅ Tabelas criadas com sucesso.\n";

    // Agora o Seed do Admin
    $nome  = 'Administrador';
    $email = 'admin@louvor.net';
    $senha = 'mudar123';
    $hash  = password_hash($senha, PASSWORD_DEFAULT);

    $stmt = $pdo->prepare('INSERT INTO admin_users (nome, email, senha_hash) VALUES (?, ?, ?)');
    $stmt->execute([$nome, $email, $hash]);
    echo "✅ Usuário admin criado: admin@louvor.net / mudar123\n";

    echo "\n🚀 Banco de dados PRONTO PARA USO!\n";
    echo "Delete este arquivo após rodar.\n";

} catch (Exception $e) {
    echo "❌ ERRO: " . $e->getMessage() . "\n";
}
