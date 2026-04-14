<?php
// ============================================================
// LOUVOR.NET - Configuração Central (lê do .env)
// ============================================================

// Carrega o .env se ainda não estiver no ambiente
if (!getenv('DB_HOST')) {
    $env_file = __DIR__ . '/.env';
    if (!file_exists($env_file)) {
        throw new RuntimeException('.env não encontrado. Copie .env.example para .env e preencha as credenciais.');
    }
    foreach (file($env_file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line) {
        if (str_starts_with(trim($line), '#') || !str_contains($line, '=')) continue;
        [$key, $value] = explode('=', $line, 2);
        $_ENV[trim($key)] = trim($value);
        putenv(trim($key) . '=' . trim($value));
    }
}

function env(string $key, string $default = ''): string {
    return $_ENV[$key] ?? getenv($key) ?: $default;
}

// --- Banco de Dados ---
define('DB_HOST',    env('DB_HOST', 'localhost'));
define('DB_PORT',    env('DB_PORT', '3306'));
define('DB_NAME',    env('DB_NAME', 'louvor_net'));
define('DB_USER',    env('DB_USER', 'root'));
define('DB_PASS',    env('DB_PASS', ''));
define('DB_CHARSET', 'utf8mb4');

// --- Anthropic ---
define('ANTHROPIC_API_KEY', env('ANTHROPIC_API_KEY'));
define('ANTHROPIC_MODEL',   env('ANTHROPIC_MODEL', 'claude-sonnet-4-6'));

// --- PiAPI ---
define('PIAPI_KEY',      env('PIAPI_KEY'));
define('PIAPI_BASE_URL', env('PIAPI_BASE_URL', 'https://api.piapi.ai/api/v1'));

// --- Asaas ---
define('ASAAS_API_KEY',    env('ASAAS_API_KEY'));
define('ASAAS_ENV',        env('ASAAS_ENV', 'sandbox'));
define('ASAAS_SANDBOX_URL', env('ASAAS_SANDBOX_URL', 'https://api-sandbox.asaas.com/v3'));
define('ASAAS_PROD_URL',   env('ASAAS_PROD_URL', 'https://api.asaas.com/api/v3'));

// --- Suno Direto ---
define('SUNO_API_KEY', env('SUNO_API_KEY'));
define('SUNO_API_URL', env('SUNO_API_URL'));

// --- Zenvia (SMS) ---
define('ZENVIA_TOKEN', env('ZENVIA_TOKEN'));
define('ZENVIA_FROM',  env('ZENVIA_FROM', 'LOUVORNET'));

// --- App ---
define('BASE_URL',           env('BASE_URL', 'http://localhost'));
define('MUSICA_PRICE',       (float) env('MUSICA_PRICE', '19.90'));
define('MUSICA_DESCRIPTION', env('MUSICA_DESCRIPTION', 'LOUVOR.NET - Música Cristã Personalizada'));
define('ASAAS_WEBHOOK_TOKEN', env('ASAAS_WEBHOOK_TOKEN', ''));
define('INSTAGRAM_HANDLE',    env('INSTAGRAM_HANDLE', 'louvor.net'));

// --- Google Analytics ---
define('GTAG_ID', env('GTAG_ID', '')); // Ex: G-XXXXXXXXXX — deixe vazio para desativar

// --- Cloudflare Turnstile (Anti-Bot) ---
define('CF_TURNSTILE_SITE_KEY',   env('CF_TURNSTILE_SITE_KEY', ''));
define('CF_TURNSTILE_SECRET_KEY', env('CF_TURNSTILE_SECRET_KEY', ''));

// --- Trava de Segurança: Apenas IPs do Brasil no Admin ---
if (str_contains($_SERVER['REQUEST_URI'], '/portal-adoracao/')) {
    $country = $_SERVER['HTTP_CF_IPCOUNTRY'] ?? '';
    
    // Se não for BR e não for ambiente de desenvolvimento local (vazio ou XX)
    if (!empty($country) && !in_array($country, ['BR', 'XX', 'T1'])) {
        header("HTTP/1.1 404 Not Found");
        echo "<!DOCTYPE HTML PUBLIC \"-//IETF//DTD HTML 2.0//EN\">
              <html><head><title>404 Not Found</title></head>
              <body><h1>Not Found</h1><p>The requested URL was not found on this server.</p></body></html>";
        exit;
    }
}

function asaas_url(): string {
    return ASAAS_ENV === 'production' ? ASAAS_PROD_URL : ASAAS_SANDBOX_URL;
}

/**
 * Registra logs do sistema em logs/app.log
 */
function logger(string $message, ?string $uid = null): void {
    $dir = __DIR__ . '/logs';
    if (!is_dir($dir)) mkdir($dir, 0777, true);
    
    $time = date('Y-m-d H:i:s');
    $log_msg = "[{$time}] {$message}" . PHP_EOL;

    // Log geral (sempre mantém)
    file_put_contents($dir . '/app.log', $log_msg, FILE_APPEND);
    
    // Log específico por música
    if ($uid && preg_match('/^[0-9a-f-]{36}$/i', $uid)) {
        file_put_contents($dir . "/{$uid}.log", $log_msg, FILE_APPEND);
    }
}
