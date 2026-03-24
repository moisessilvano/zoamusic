<?php
// ============================================================
// LOUVOR.NET - Admin Login
// ============================================================

session_start();

define('ADMIN_PASSWORD', 'Teste123@');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $senha = $_POST['senha'] ?? '';
    if (hash_equals(ADMIN_PASSWORD, $senha)) {
        $_SESSION['admin_auth'] = true;
        $_SESSION['admin_at']   = time();
        header('Location: index.php');
        exit;
    }
    $erro = 'Senha incorreta.';
}

// Já autenticado?
if (!empty($_SESSION['admin_auth'])) {
    header('Location: index.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>LOUVOR.NET — Admin</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        body { background: linear-gradient(135deg, #1E293B 0%, #0f172a 100%); }
        .gold-text { color: #D4AF37; }
        .btn-gold { background-color: #D4AF37; }
        .btn-gold:hover { background-color: #B8962E; }
        input:focus { border-color: #D4AF37; box-shadow: 0 0 0 3px rgba(212,175,55,0.2); outline: none; }
    </style>
</head>
<body class="min-h-screen flex items-center justify-center px-4">
    <div class="w-full max-w-sm">
        <div class="text-center mb-8">
            <svg class="w-12 h-12 mx-auto mb-3" viewBox="0 0 32 32" fill="none">
                <circle cx="16" cy="16" r="15" stroke="#D4AF37" stroke-width="2"/>
                <path d="M16 8 L18 13 L23 13 L19 16 L21 21 L16 18 L11 21 L13 16 L9 13 L14 13 Z" fill="#D4AF37"/>
            </svg>
            <h1 class="text-2xl font-bold tracking-widest text-white">
                LOUVOR<span class="gold-text">.NET</span>
            </h1>
            <p class="text-gray-400 text-sm mt-1">Painel Administrativo</p>
        </div>

        <div class="bg-white/5 backdrop-blur-sm border border-white/10 rounded-2xl p-8">
            <?php if (!empty($erro)): ?>
            <div class="bg-red-500/20 border border-red-500/40 text-red-300 rounded-xl px-4 py-3 mb-5 text-sm flex items-center gap-2">
                <span>⚠</span> <?= htmlspecialchars($erro) ?>
            </div>
            <?php endif; ?>

            <form method="POST" action="">
                <div class="mb-5">
                    <label class="block text-gray-300 text-sm font-semibold mb-2">Senha</label>
                    <input type="password" name="senha" autofocus
                        class="w-full bg-white/10 border border-white/20 text-white rounded-xl px-4 py-3 transition-all"
                        placeholder="••••••••••">
                </div>
                <button type="submit"
                    class="btn-gold w-full py-3 rounded-xl text-white font-bold tracking-wide transition-colors">
                    Entrar
                </button>
            </form>
        </div>

        <p class="text-center text-gray-600 text-xs mt-6">
            <a href="../" class="hover:text-gray-400 transition-colors">← Voltar ao site</a>
        </p>
    </div>
</body>
</html>
