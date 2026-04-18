<?php
// ============================================================
// ZOA MUSIC - Conexão PDO Singleton
// ============================================================

require_once __DIR__ . '/config.php';

function db(): PDO {
    static $pdo = null;

    if ($pdo === null) {
        $dsn = sprintf(
            'mysql:host=%s;port=%s;dbname=%s;charset=%s',
            DB_HOST, DB_PORT, DB_NAME, DB_CHARSET
        );
        $options = [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ];
        
        try {
            $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
            // Auto-migrate: adiciona coluna estilo se não existir
            try { $pdo->exec("ALTER TABLE musicas ADD COLUMN estilo VARCHAR(100) DEFAULT NULL"); } catch (PDOException $e) { /* já existe */ }
        } catch (PDOException $e) {
            error_log("ZOA MUSIC DB ERROR: " . $e->getMessage());
            die("Desculpe, o sistema está temporariamente indisponível. Por favor, tente novamente em alguns instantes.");
        }
    }

    return $pdo;
}

// Gera UUID v4
function uuid4(): string {
    $data = random_bytes(16);
    $data[6] = chr(ord($data[6]) & 0x0f | 0x40);
    $data[8] = chr(ord($data[8]) & 0x3f | 0x80);
    return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($data), 4));
}
