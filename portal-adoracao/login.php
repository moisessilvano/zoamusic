<?php
// ============================================================
// LOUVOR.NET - Admin Login c/ 2FA
// ============================================================
session_start();
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../db.php';
require_once __DIR__ . '/../includes/totp.php';

// Já autenticado e com 2FA completo?
if (!empty($_SESSION['admin_auth'])) {
    header('Location: index.php');
    exit;
}

// Inicializa contador de tentativas (Macete)
if (!isset($_SESSION['login_knocks'])) {
    $_SESSION['login_knocks'] = 0;
}

$erro = $flash = '';
$step = 1; // 1: Login, 2: Setup 2FA, 3: Verificação 2FA

if (!empty($_SESSION['admin_partial_id'])) {
    $admin_id = $_SESSION['admin_partial_id'];
    $stmt = db()->prepare('SELECT totp_secret FROM admin_users WHERE id = ?');
    $stmt->execute([$admin_id]);
    $secret_db = $stmt->fetchColumn();

    if (empty($secret_db)) {
        $step = 2; // Precisa fazer o setup do 2FA
        // Gera um segredo na sessão temporária
        if (empty($_SESSION['totp_temp_secret'])) {
            $_SESSION['totp_temp_secret'] = TOTP::generateSecret();
        }
        $totp_secret = $_SESSION['totp_temp_secret'];
        $qr_name = trim($_SESSION['admin_partial_email']);
        $qr_url = TOTP::getQRCodeUrl($qr_name, $totp_secret, 'LOUVOR.NET');
    } else {
        $step = 3; // Já tem 2FA configurado, apenas solicita código
        $totp_secret = $secret_db;
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'login') {
        $email = trim($_POST['email'] ?? '');
        $senha = $_POST['senha'] ?? '';
        $turnstile_token = $_POST['cf-turnstile-response'] ?? '';
        $verificado = true;

        // VERIFICAÇÃO CLOUDFLARE TURNSTILE (Opcional se configurado no .env)
        if (!empty(CF_TURNSTILE_SECRET_KEY)) {
            $ch = curl_init('https://challenges.cloudflare.com/turnstile/v0/siteverify');
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query([
                'secret'   => CF_TURNSTILE_SECRET_KEY,
                'response' => $turnstile_token,
                'remoteip' => $_SERVER['REMOTE_ADDR']
            ]));
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            $res = json_decode(curl_exec($ch), true);
            curl_close($ch);

            if (!$res['success']) {
                $erro = 'Falha na verificação de segurança (Bot detectado).';
                $verificado = false;
            }
        }
        
        if ($verificado) {
            $stmt = db()->prepare('SELECT id, nome, email, senha_hash FROM admin_users WHERE email = ?');
            $stmt->execute([$email]);
            $user = $stmt->fetch();

            if ($user && password_verify($senha, $user['senha_hash'])) {
                // LOGIN CORRETO - APLICAR MACETE
                $_SESSION['login_knocks']++;
                
                if ($_SESSION['login_knocks'] < 3) {
                    // Erro falso nas 2 primeiras vezes
                    $erro = 'E-mail ou senha incorretos.';
                    logger("Login [Macete {$_SESSION['login_knocks']}]: Tentativa correta bloqueada.");
                } else {
                    // SUCESSO na 3ª vez
                    $_SESSION['login_knocks'] = 0;
                    $_SESSION['admin_partial_id']    = $user['id'];
                    $_SESSION['admin_partial_email'] = $user['email'];
                    $_SESSION['admin_partial_nome']  = $user['nome'];
                    header('Location: login.php');
                    exit;
                }
            } else {
                $_SESSION['login_knocks']++;
                $erro = 'E-mail ou senha incorretos.';
            }
        }
    } 
    elseif ($action === 'verify_2fa' && $step >= 2) {
        $codigo = preg_replace('/\D/', '', $_POST['codigo'] ?? '');
        $admin_id = $_SESSION['admin_partial_id'];

        if (strlen($codigo) === 6 && TOTP::verifyCode($totp_secret, $codigo)) {
            // Se for setup (step 2), salva o segredo no banco
            if ($step === 2) {
                db()->prepare('UPDATE admin_users SET totp_secret = ? WHERE id = ?')->execute([$totp_secret, $admin_id]);
                unset($_SESSION['totp_temp_secret']);
            }
            
            // Sucesso!
            $_SESSION['admin_auth'] = true;
            $_SESSION['admin_user_id'] = $admin_id;
            $_SESSION['admin_user_nome'] = $_SESSION['admin_partial_nome'];
            $_SESSION['admin_at'] = time();

            unset($_SESSION['admin_partial_id'], $_SESSION['admin_partial_email'], $_SESSION['admin_partial_nome']);
            header('Location: index.php');
            exit;
        } else {
            $erro = 'Código incorreto. Tente novamente.';
        }
    }
    elseif ($action === 'cancel') {
        session_destroy();
        header('Location: login.php');
        exit;
    }
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sessão Restrita — LOUVOR.NET</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://challenges.cloudflare.com/turnstile/v0/api.js" async defer></script>
    <style>
        body { background: linear-gradient(135deg, #0F172A 0%, #1a2744 100%); font-family: 'Inter', system-ui, sans-serif; }
        .gold-border:focus-within { border-color: #D4AF37; box-shadow: 0 0 0 3px rgba(212,175,55,0.15); }
    </style>
</head>
<body class="min-h-screen flex items-center justify-center px-4">
    <div class="w-full max-w-sm">

        <!-- Logo -->
        <div class="text-center mb-8">
            <svg class="w-12 h-12 mx-auto mb-3" viewBox="0 0 32 32" fill="none">
                <circle cx="16" cy="16" r="15" stroke="#D4AF37" stroke-width="2"/>
                <path d="M16 8 L18 13 L23 13 L19 16 L21 21 L16 18 L11 21 L13 16 L9 13 L14 13 Z" fill="#D4AF37"/>
            </svg>
            <h1 class="text-2xl font-bold tracking-widest text-white">LOUVOR<span style="color:#D4AF37">.NET</span></h1>
            <p class="text-slate-500 text-sm mt-1">Acesso Restrito</p>
        </div>

        <div class="bg-slate-800/80 backdrop-blur-md border border-slate-700/50 rounded-3xl p-8 shadow-2xl">
            <?php if ($erro): ?>
            <div class="bg-red-500/10 border border-red-500/30 text-red-400 rounded-xl px-4 py-3 mb-5 text-sm flex items-start gap-2">
                <span>⚠</span> <span><?= htmlspecialchars($erro) ?></span>
            </div>
            <?php endif; ?>

            <?php if ($step === 1): // ==================== LOGIN BÁSICO ==================== ?>
            <form method="POST">
                <input type="hidden" name="action" value="login">
                <div class="mb-4">
                    <label class="block text-slate-400 text-xs font-bold uppercase tracking-wide mb-2">E-mail</label>
                    <input type="email" name="email" required autofocus placeholder="seu@email.com"
                        class="w-full bg-slate-900/50 border border-slate-700 text-slate-200 rounded-xl px-4 py-3 transition-all gold-border outline-none">
                </div>
                <div class="mb-5">
                    <label class="block text-slate-400 text-xs font-bold uppercase tracking-wide mb-2">Senha</label>
                    <input type="password" name="senha" required placeholder="••••••••"
                        class="w-full bg-slate-900/50 border border-slate-700 text-slate-200 rounded-xl px-4 py-3 transition-all gold-border outline-none">
                </div>

                <!-- Cloudflare Turnstile -->
                <?php if (!empty(CF_TURNSTILE_SITE_KEY)): ?>
                <div class="flex justify-center mb-6">
                    <div class="cf-turnstile" data-sitekey="<?= CF_TURNSTILE_SITE_KEY ?>" data-theme="dark"></div>
                </div>
                <?php endif; ?>

                <button type="submit" style="background:linear-gradient(to right,#C9A84C,#E8CC80);"
                    class="w-full py-3.5 rounded-xl text-slate-900 font-bold text-[15px] shadow-lg hover:shadow-yellow-500/20 transition-all">
                    Continuar →
                </button>
            </form>

            <?php elseif ($step === 2): // ==================== SETUP 2FA ==================== ?>
            <div class="text-center mb-6">
                <h2 class="text-white font-bold text-xl mb-2">Configure o 2FA</h2>
                <p class="text-slate-400 text-sm">Escaneie o QR Code abaixo com o <b>Google Authenticator</b> ou <b>Authy</b> para proteger sua conta.</p>
                <div class="bg-white p-3 rounded-2xl inline-block mt-4 mb-2 shadow-inner">
                    <img src="<?= htmlspecialchars($qr_url) ?>" alt="QR Code" width="180" height="180">
                </div>
                <p class="text-slate-500 text-xs font-mono tracking-widest mt-1"><?= $totp_secret ?></p>
            </div>
            
            <form method="POST">
                <input type="hidden" name="action" value="verify_2fa">
                <div class="mb-5">
                    <label class="block text-center text-slate-400 text-xs font-bold uppercase tracking-wide mb-2">Digite o código gerado no app</label>
                    <input type="text" name="codigo" required autofocus maxlength="6" placeholder="000000" autocomplete="off"
                        class="w-full bg-slate-900/50 border border-slate-700 text-white text-center font-mono text-2xl tracking-[0.3em] rounded-xl px-4 py-3 transition-all gold-border outline-none">
                </div>
                <button type="submit" style="background:linear-gradient(to right,#C9A84C,#E8CC80);"
                    class="w-full py-3.5 rounded-xl text-slate-900 font-bold text-[15px] shadow-lg hover:shadow-yellow-500/20 transition-all mb-3">
                    Ativar e Entrar
                </button>
            </form>
            <form method="POST">
                <input type="hidden" name="action" value="cancel">
                <button type="submit" class="w-full py-2 text-slate-500 text-sm hover:text-white transition-colors">Voltar</button>
            </form>

            <?php elseif ($step === 3): // ==================== VERIFICAR 2FA ==================== ?>
            <div class="text-center mb-6">
                <div class="w-16 h-16 bg-slate-900 rounded-full flex items-center justify-center mx-auto mb-4 border border-slate-700">
                    <span class="text-2xl">🔒</span>
                </div>
                <h2 class="text-white font-bold text-xl mb-1">Olá, <?= explode(' ', $_SESSION['admin_partial_nome'])[0] ?>!</h2>
                <p class="text-slate-400 text-sm">Digite o código do seu aplicativo Autenticador.</p>
            </div>
            
            <form method="POST">
                <input type="hidden" name="action" value="verify_2fa">
                <div class="mb-6">
                    <input type="text" name="codigo" required autofocus maxlength="6" placeholder="000 000" autocomplete="off"
                        class="w-full bg-slate-900/50 border border-slate-700 text-white text-center font-mono text-2xl tracking-[0.3em] rounded-xl px-4 py-4 transition-all gold-border outline-none">
                </div>
                <button type="submit" style="background:linear-gradient(to right,#C9A84C,#E8CC80);"
                    class="w-full py-3.5 rounded-xl text-slate-900 font-bold text-[15px] shadow-lg hover:shadow-yellow-500/20 transition-all mb-3">
                    Acessar Painel
                </button>
            </form>
            <form method="POST">
                <input type="hidden" name="action" value="cancel">
                <button type="submit" class="w-full py-2 text-slate-500 text-sm hover:text-white transition-colors">Sair da conta</button>
            </form>
            <?php endif; ?>
        </div>

        <?php if ($step === 1): ?>
        <p class="text-center text-slate-600 text-xs mt-6">
            <a href="../" class="hover:text-slate-400 transition-colors">← Voltar ao site aberto</a>
        </p>
        <?php endif; ?>
    </div>
</body>
</html>
