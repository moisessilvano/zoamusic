<?php
// ============================================================
// ZOA MUSIC - Trocar Senha Obrigatória
// ============================================================
session_start();

if (empty($_SESSION['admin_auth'])) {
    header('Location: login.php'); exit;
}

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../db.php';

$erro = $sucesso = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nova_senha = $_POST['nova_senha'] ?? '';
    $confirmar  = $_POST['confirmar_senha'] ?? '';

    if (strlen($nova_senha) < 8) {
        $erro = 'A nova senha deve ter no mínimo 8 caracteres.';
    } elseif ($nova_senha !== $confirmar) {
        $erro = 'As senhas não coincidem. Verifique e tente novamente.';
    } else {
        $hash = password_hash($nova_senha, PASSWORD_DEFAULT);
        $stmt = db()->prepare("UPDATE admin_users SET senha_hash = ?, reset_password = 0 WHERE id = ?");
        $stmt->execute([$hash, $_SESSION['admin_user_id']]);

        $_SESSION['admin_reset_password'] = 0;
        $sucesso = 'Senha alterada com sucesso! Redirecionando...';
        echo "<script>setTimeout(() => { window.location = 'index.php'; }, 2000);</script>";
    }
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Trocar Senha — ZOA MUSIC</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="icon" type="image/svg+xml" href="../assets/logo.svg">
    <style>
        body { background: linear-gradient(135deg, #0F172A 0%, #1a2744 100%); font-family: 'Inter', system-ui, sans-serif; }
    </style>
</head>
<body class="min-h-screen flex items-center justify-center px-4">
    <div class="w-full max-w-sm">

        <div class="text-center mb-8">
            <img src="../assets/logo.svg" alt="ZOA MUSIC" class="w-16 h-16 mx-auto mb-4 rounded-full object-cover border-2 border-[#D4AF37]/30">
            <h1 class="text-2xl font-bold tracking-widest text-white">LOUVOR<span style="color:#D4AF37">.NET</span></h1>
            <p class="text-slate-400 text-sm mt-2">Atualização de Segurança</p>
        </div>

        <div class="bg-slate-800/80 backdrop-blur-md border border-slate-700/50 rounded-3xl p-8 shadow-2xl">
            <h2 class="text-white font-bold text-lg mb-2">Defina sua nova senha</h2>
            <p class="text-slate-400 text-xs mb-6Leading-relaxed">Você está usando uma senha temporária ou seu acesso foi resetado. Por segurança, escolha uma nova senha agora.</p>

            <?php if ($erro): ?>
            <div class="bg-red-500/10 border border-red-500/30 text-red-400 rounded-xl px-4 py-3 mb-5 text-sm">
                ⚠ <?= htmlspecialchars($erro) ?>
            </div>
            <?php endif; ?>

            <?php if ($sucesso): ?>
            <div class="bg-green-500/10 border border-green-500/30 text-green-400 rounded-xl px-4 py-3 mb-5 text-sm">
                ✅ <?= htmlspecialchars($sucesso) ?>
            </div>
            <?php endif; ?>

            <form method="POST" class="space-y-5">
                <div>
                    <label class="block text-slate-400 text-xs font-bold uppercase tracking-wide mb-2">Nova Senha</label>
                    <input type="password" name="nova_senha" required autofocus minlength="8"
                        class="w-full bg-slate-900/50 border border-slate-700 text-white rounded-xl px-4 py-3 focus:border-[#D4AF37] outline-none transition-all">
                    <p class="text-[10px] text-slate-500 mt-1.5 ml-1">Mínimo de 8 caracteres.</p>
                </div>
                <div>
                    <label class="block text-slate-400 text-xs font-bold uppercase tracking-wide mb-2">Confirme a Senha</label>
                    <input type="password" name="confirmar_senha" required
                        class="w-full bg-slate-900/50 border border-slate-700 text-white rounded-xl px-4 py-3 focus:border-[#D4AF37] outline-none transition-all">
                </div>

                <button type="submit" style="background:linear-gradient(to right,#C9A84C,#E8CC80);"
                    class="w-full py-4 rounded-xl text-slate-900 font-bold text-sm shadow-lg hover:shadow-yellow-500/20 transition-all">
                    Atualizar Senha e Entrar
                </button>
            </form>

            <div class="mt-6 pt-6 border-t border-slate-700/50 text-center">
                <a href="logout.php" class="text-slate-500 text-xs hover:text-red-400 transition-colors flex items-center justify-center gap-2">
                    🚪 Sair da sessão
                </a>
            </div>
        </div>
        
        <p class="text-center text-slate-600 text-[10px] mt-8 uppercase tracking-widest">Acesso Protegido por Criptografia SSL</p>
    </div>
</body>
</html>
