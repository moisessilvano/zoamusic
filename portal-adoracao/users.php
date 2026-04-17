<?php
// ============================================================
// LOUVOR.NET - Administradores
// ============================================================
session_start();

if (empty($_SESSION['admin_auth'])) {
    header('Location: login.php'); exit;
}

if (!empty($_SESSION['admin_reset_password'])) {
    header('Location: trocar_senha.php'); exit;
}

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../db.php';

$flash = $flash_type = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'criar') {
        $nome  = trim($_POST['nome'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $senha = $_POST['senha'] ?? '';

        if ($nome && filter_var($email, FILTER_VALIDATE_EMAIL) && strlen($senha) >= 6) {
            try {
                $hash = password_hash($senha, PASSWORD_DEFAULT);
                db()->prepare("INSERT INTO admin_users (nome, email, senha_hash) VALUES (?, ?, ?)")
                    ->execute([$nome, $email, $hash]);
                $flash = "✅ Novo administrador criado com sucesso. O 2FA será configurado no primeiro login.";
                $flash_type = 'green';
            } catch (PDOException $e) {
                $flash = "❌ Erro ao criar: O e-mail já existe ou dados informados são inválidos.";
                $flash_type = 'red';
            }
        } else {
            $flash = "❌ Preencha todos os campos corretamente (senha mínima de 6 chars).";
            $flash_type = 'red';
        }
    } 
    elseif ($action === 'resetar_2fa' && !empty($_POST['id'])) {
        db()->prepare("UPDATE admin_users SET totp_secret = NULL WHERE id = ?")->execute([$_POST['id']]);
        $flash = "🔄 Duplo fator resetado! O usuário terá que recadastrar no próximo login.";
        $flash_type = 'blue';
    } 
    elseif ($action === 'deletar' && !empty($_POST['id'])) {
        if ($_POST['id'] == $_SESSION['admin_user_id']) {
            $flash = "❌ Você não pode deletar a sua própria conta.";
            $flash_type = 'red';
        } else {
            db()->prepare("DELETE FROM admin_users WHERE id = ?")->execute([$_POST['id']]);
            $flash = "🗑 Administrador removido do sistema.";
            $flash_type = 'green';
        }
    }
}

// Lista de admins
$admins = db()->query("SELECT id, nome, email, totp_secret, created_at FROM admin_users ORDER BY id ASC")->fetchAll();
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Administradores — LOUVOR.NET</title>
<script src="https://cdn.tailwindcss.com"></script>
<style>
    * { font-family: 'Inter', system-ui, sans-serif; }
    body { background: #0F172A; color: #F1F5F9; }

    /* Sidebar */
    .sidebar { background: #1E293B; border-right: 1px solid rgba(255,255,255,0.06); }
    .nav-link { transition: all 0.15s; border-radius: 10px; }
    .nav-link:hover, .nav-link.active { background: rgba(212,175,55,0.12); color: #D4AF37; }

    /* Table */
    .data-table { background: #1E293B; border-radius: 16px; border: 1px solid rgba(255,255,255,0.06); overflow: hidden; }
    .data-table thead th { background: #162032; color: #94A3B8; font-size: 11px; text-transform: uppercase; letter-spacing: .06em; }
    .data-table tbody tr:hover td { background: rgba(255,255,255,0.03); }
    .data-table tbody td { border-top: 1px solid rgba(255,255,255,0.04); }

    /* Badges */
    .badge-green { background: rgba(34,197,94,0.15);  color: #4ADE80; }
    .badge-red   { background: rgba(239,68,68,0.15);  color: #F87171; }

    .topbar { background: #1E293B; border-bottom: 1px solid rgba(255,255,255,0.06); }
    #mobile-menu { display: none; }
    #mobile-menu.open { display: flex; }
    ::-webkit-scrollbar { width: 5px; height: 5px; }
    ::-webkit-scrollbar-track { background: #0F172A; }
    ::-webkit-scrollbar-thumb { background: rgba(212,175,55,0.3); border-radius: 10px; }
    #modal { display: none; }
    #modal.open { display: flex; }

    @media (max-width: 768px) {
        .sidebar { transform: translateX(-100%); transition: transform 0.25s ease; position: fixed; z-index: 100; width: 240px; height: 100vh; }
        .sidebar.open { transform: translateX(0); }
        .main-content { margin-left: 0 !important; }
    }
</style>
</head>
<body class="min-h-screen">

<!-- ═══ SIDEBAR ═══ -->
<aside class="sidebar w-60 flex-shrink-0 flex flex-col h-screen fixed left-0 top-0" id="sidebar">
    <div class="px-5 py-5 border-b border-white/5 flex items-center justify-between">
        <a href="../" class="flex items-center gap-2.5">
            <img src="../assets/logo.jpeg" alt="LOUVOR.NET" class="w-8 h-8 rounded-full object-cover border border-[#D4AF37]/30">
            <span class="font-bold tracking-widest text-base text-white">LOUVOR<span style="color:#D4AF37">.NET</span></span>
        </a>
        <button class="md:hidden text-slate-400 hover:text-white" onclick="closeSidebar()">
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
        </button>
    </div>
    <p class="text-slate-400 text-xs mt-2 ml-14 md:block hidden">Admin</p>

    <nav class="flex-1 px-3 py-4 space-y-0.5 overflow-y-auto">
        <p class="text-slate-400 text-xs font-semibold uppercase tracking-wider px-3 py-2">Painel</p>
        <a href="index.php" class="nav-link flex items-center gap-3 px-3 py-2.5 text-slate-300 text-sm">
            <span>📊</span> Dashboard
        </a>

        <p class="text-slate-400 text-xs font-semibold uppercase tracking-wider px-3 py-2 mt-4">Sistema</p>
        <a href="users.php" class="nav-link active flex items-center gap-3 px-3 py-2.5 text-slate-200 text-sm">
            <span>👥</span> Administradores
        </a>
    </nav>
</aside>

<!-- Overlay mobile -->
<div id="sidebar-overlay" class="fixed inset-0 bg-black/50 z-50 hidden md:hidden" onclick="closeSidebar()"></div>

<!-- ═══ MAIN ═══ -->
<div class="ml-0 md:ml-60 min-h-screen flex flex-col">

    <!-- TOPBAR -->
    <header class="topbar sticky top-0 z-40 px-4 md:px-6 py-4 flex items-center gap-4">
        <button class="md:hidden text-slate-300 hover:text-white" onclick="toggleSidebar()">
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/>
            </svg>
        </button>
        <h1 class="text-lg font-bold text-white flex-1">
            Membros da Equipe
            <span class="hidden md:inline text-slate-400 font-normal text-sm ml-3 border-l border-slate-700 pl-3">Controle de acesso restrito (2FA)</span>
        </h1>
        <div class="flex items-center gap-4">
            <div class="hidden sm:block text-xs text-slate-300"><?= date('d/m/Y H:i') ?></div>
            <a href="logout.php" class="px-3 py-1.5 bg-red-500/10 border border-red-500/20 text-red-400 text-xs font-semibold rounded-lg hover:bg-red-500/20 transition-all">🚪 Sair</a>
        </div>
    </header>

    <div class="flex-1 p-4 md:p-6 space-y-6">

        <!-- FLASH -->
        <?php if ($flash): ?>
        <div class="rounded-xl px-5 py-3 text-sm font-medium
            <?= $flash_type==='green' ? 'bg-green-500/10 border border-green-500/30 text-green-400' : ($flash_type==='red' ? 'bg-red-500/10 border border-red-500/30 text-red-400' : 'bg-blue-500/10 border border-blue-500/30 text-blue-400') ?>">
            <?= htmlspecialchars($flash) ?>
        </div>
        <?php endif; ?>

        <!-- ══ LISTA DE USUÁRIOS ══ -->
        <div class="data-table">
            <div class="px-5 py-4 border-b border-white/5 flex flex-wrap items-center justify-between gap-3">
                <div>
                    <h2 class="font-bold text-slate-100">Administradores</h2>
                    <p class="text-xs text-slate-400">Total: <?= count($admins) ?></p>
                </div>
                <button type="button" onclick="document.getElementById('modal').classList.add('open')"
                    class="px-5 py-2.5 rounded-xl text-sm font-semibold text-slate-900 shadow-md hover:shadow-yellow-500/20"
                    style="background:linear-gradient(135deg,#C9A84C,#D4AF37);">+ Adicionar Admin</button>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead>
                        <tr>
                            <th class="text-left px-5 py-3 text-slate-300 w-12">ID</th>
                            <th class="text-left px-4 py-3 text-slate-300">Nome / E-mail</th>
                            <th class="text-left px-4 py-3 text-slate-300">Segurança (2FA)</th>
                            <th class="text-center px-4 py-3 text-slate-300">Ações</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($admins as $u): ?>
                        <tr>
                            <td class="px-5 py-4 text-slate-500 text-xs font-mono">#<?= $u['id'] ?></td>
                            <td class="px-4 py-4">
                                <p class="font-semibold text-slate-100 flex items-center gap-2">
                                    <?= htmlspecialchars($u['nome']) ?>
                                    <?php if ($u['id'] == $_SESSION['admin_user_id']): ?>
                                        <span class="text-[10px] bg-slate-700/50 text-slate-300 px-1.5 py-0.5 rounded border border-slate-600">Você</span>
                                    <?php endif; ?>
                                </p>
                                <p class="text-slate-400 text-xs"><?= htmlspecialchars($u['email']) ?></p>
                            </td>
                            <td class="px-4 py-4 whitespace-nowrap">
                                <?php if ($u['totp_secret']): ?>
                                    <span class="badge-green px-2.5 py-1 rounded-full text-xs font-semibold">✅ 2FA Ativo</span>
                                <?php else: ?>
                                    <span class="badge-red px-2.5 py-1 rounded-full text-xs font-semibold">⚠ Pendente (No próx. login)</span>
                                <?php endif; ?>
                            </td>
                            <td class="px-4 py-4 text-center">
                                <div class="flex items-center justify-center gap-2">
                                    <?php if ($u['totp_secret']): ?>
                                    <form method="POST" class="inline" onsubmit="return confirm('Deseja realmente resetar o 2FA deste usuário? Ele terá que configurar novamente no próximo login.')">
                                        <input type="hidden" name="action" value="resetar_2fa">
                                        <input type="hidden" name="id" value="<?= $u['id'] ?>">
                                        <button type="submit" class="px-3 py-1.5 bg-slate-800 text-slate-300 text-xs rounded-lg border border-slate-700 hover:text-white hover:border-slate-500 transition-colors" title="Desvincular Autenticador">Resetar 2FA</button>
                                    </form>
                                    <?php endif; ?>
                                    <?php if ($u['id'] != $_SESSION['admin_user_id']): ?>
                                    <form method="POST" class="inline" onsubmit="return confirm('ATENÇÃO! Deletar este administrador permanentemente?')">
                                        <input type="hidden" name="action" value="deletar">
                                        <input type="hidden" name="id" value="<?= $u['id'] ?>">
                                        <button type="submit" class="p-1.5 text-slate-500 hover:text-red-400 hover:bg-red-500/10 rounded-lg transition-colors" title="Remover Conta">🗑</button>
                                    </form>
                                    <?php endif; ?>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>

    </div>
</div>

<!-- ═══ MODAL ADICIONAR ═══ -->
<div id="modal" class="fixed inset-0 bg-black/70 backdrop-blur-sm z-50 items-center justify-center px-4">
    <div class="bg-slate-800 border border-slate-700 rounded-2xl shadow-2xl w-full max-w-md p-6">
        <h3 class="text-lg font-bold text-white mb-2">Novo Administrador</h3>
        <p class="text-sm text-slate-400 mb-5">A conta será protegida por 2FA automaticamente quando este usuário logar pela primeira vez.</p>
        
        <form method="POST">
            <input type="hidden" name="action" value="criar">
            <div class="mb-4">
                <label class="block text-xs font-semibold text-slate-400 mb-1.5">Nome Completo</label>
                <input type="text" name="nome" required placeholder="João Silva" autofocus
                    class="w-full bg-slate-900 border border-slate-700 rounded-xl px-4 py-2.5 text-sm text-slate-200 focus:border-yellow-500/50 focus:outline-none">
            </div>
            <div class="mb-4">
                <label class="block text-xs font-semibold text-slate-400 mb-1.5">E-mail</label>
                <input type="email" name="email" required placeholder="joao@louvor.net"
                    class="w-full bg-slate-900 border border-slate-700 rounded-xl px-4 py-2.5 text-sm text-slate-200 focus:border-yellow-500/50 focus:outline-none">
            </div>
            <div class="mb-6">
                <label class="block text-xs font-semibold text-slate-400 mb-1.5">Senha Inicial</label>
                <input type="text" name="senha" required minlength="6" placeholder="Definir uma senha segura"
                    class="w-full bg-slate-900 border border-slate-700 rounded-xl px-4 py-2.5 text-sm text-slate-200 focus:border-yellow-500/50 focus:outline-none">
                <p class="text-[10px] text-slate-500 mt-1.5">No mínimo 6 caracteres. O usuário não poderá alterar a senha depois (peça para um admin alterar).</p>
            </div>
            <div class="flex gap-3">
                <button type="button" onclick="fecharModal()" class="flex-1 py-3 rounded-xl border border-slate-600 text-slate-300 font-semibold hover:bg-slate-700">Cancelar</button>
                <button type="submit" class="flex-1 py-3 rounded-xl text-slate-900 font-bold"
                    style="background:linear-gradient(135deg,#C9A84C,#D4AF37);">Criar Conta</button>
            </div>
        </form>
    </div>
</div>

<script>
function toggleSidebar() { document.getElementById('sidebar').classList.toggle('open'); document.getElementById('sidebar-overlay').classList.toggle('hidden'); }
function closeSidebar() { document.getElementById('sidebar').classList.remove('open'); document.getElementById('sidebar-overlay').classList.add('hidden'); }
function fecharModal() { document.getElementById('modal').classList.remove('open'); }
document.getElementById('modal').addEventListener('click', function(e) { if (e.target === this) fecharModal(); });
</script>
</body>
</html>
