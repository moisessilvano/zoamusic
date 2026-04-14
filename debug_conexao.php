<?php
// ============================================================
// LOUVOR.NET - Ferramenta de Diagnóstico de Conexão
// ============================================================

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/db.php';

header('Content-Type: text/plain; charset=utf-8');

echo "🔍 INICIANDO DIAGNÓSTICO DO LOUVOR.NET\n";
echo "------------------------------------------\n\n";

// 1. Verificando Configurações do .env
echo "1. Verificando Constantes:\n";
echo "[DB_HOST] " . (defined('DB_HOST') ? "Definido (" . DB_HOST . ")" : "❌ NÃO DEFINIDO") . "\n";
echo "[DB_NAME] " . (defined('DB_NAME') ? "Definido (" . DB_NAME . ")" : "❌ NÃO DEFINIDO") . "\n";
echo "[BASE_URL] " . (defined('BASE_URL') ? "Definido (" . BASE_URL . ")" : "❌ NÃO DEFINIDO") . "\n";
echo "[ANTHROPIC_API_KEY] " . (!empty(ANTHROPIC_API_KEY) ? "✅ Preenchida" : "⚠️ Vazia") . "\n";
echo "[PIAPI_KEY] " . (!empty(PIAPI_KEY) ? "✅ Preenchida" : "⚠️ Vazia") . "\n";
echo "[ASAAS_API_KEY] " . (!empty(ASAAS_API_KEY) ? "✅ Preenchida" : "⚠️ Vazia") . "\n";
echo "[ASAAS_ENV] " . (defined('ASAAS_ENV') ? ASAAS_ENV : "❌ NÃO DEFINIDO") . "\n\n";

// 2. Testando Conexão com o Banco
echo "2. Testando Banco de Dados:\n";
try {
    $pdo = db();
    echo "✅ Conexão estabelecida com sucesso!\n\n";

    // 3. Verificando Tabelas
    echo "3. Verificando Tabelas no Banco:\n";
    $stmt = $pdo->query("SHOW TABLES");
    $tabelas = $stmt->fetchAll(PDO::FETCH_COLUMN);

    if (empty($tabelas)) {
        echo "⚠️  O banco está conectado, mas está VAZIO (nenhuma tabela encontrada).\n";
        echo "👉 Rode o script db/preparar_banco.php para criar as tabelas.\n";
    } else {
        echo "Tabelas encontradas:\n";
        foreach ($tabelas as $t) {
            $count = $pdo->query("SELECT COUNT(*) FROM $t")->fetchColumn();
            echo "- $t ($count registros)\n";
        }
    }

} catch (Exception $e) {
    echo "❌ FALHA NA CONEXÃO:\n";
    echo "Erro: " . $e->getMessage() . "\n";
    echo "\nDica: Verifique se o host, usuário e senha no .env estão corretos e se o IP do seu servidor web está liberado no 'Remote MySQL' do banco de dados.\n";
}

echo "\n------------------------------------------\n";
echo "FIM DO DIAGNÓSTICO.\n";
echo "🔐 Por segurança, delete este arquivo após validar a conexão.";
