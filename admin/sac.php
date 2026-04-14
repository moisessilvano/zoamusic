<?php
session_start();
if (empty($_SESSION['admin_auth'])) { header('Location: login.php'); exit; }

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../db.php';

// --- Ações ---
if (isset($_GET['lido'])) {
    $id = (int)$_GET['lido'];
    db()->prepare("UPDATE sac_mensagens SET lido = 1 WHERE id = ?")->execute([$id]);
    header('Location: sac.php'); exit;
}
if (isset($_GET['deletar'])) {
    $id = (int)$_GET['deletar'];
    db()->prepare("DELETE FROM sac_mensagens WHERE id = ?")->execute([$id]);
    header('Location: sac.php'); exit;
}

$mensagens = db()->query("SELECT * FROM sac_mensagens ORDER BY created_at DESC")->fetchAll();
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Mensagens SAC — LOUVOR.NET</title>
<script src="https://cdn.tailwindcss.com"></script>
<style>
    body { background: #0F172A; color: #F1F5F9; font-family: 'Inter', sans-serif; }
    .card { background: #1E293B; border: 1px solid rgba(255,255,255,0.06); border-radius: 16px; }
    .badge-unread { background: #ef4444; color: #fff; padding: 2px 8px; border-radius: 99px; font-size: 10px; font-weight: bold; }
</style>
</head>
<body class="p-6">
    <div class="max-w-5xl mx-auto">
        <div class="flex items-center justify-between mb-8">
            <h1 class="text-2xl font-bold">📩 Mensagens de Suporte (SAC)</h1>
            <a href="index.php" class="text-sm text-slate-400 hover:text-white">← Voltar ao Dashboard</a>
        </div>

        <?php if (empty($mensagens)): ?>
            <div class="card p-12 text-center text-slate-400">Nenhuma mensagem recebida ainda.</div>
        <?php else: ?>
            <div class="space-y-4">
                <?php foreach ($mensagens as $m): ?>
                <div class="card p-6 <?= $m['lido'] ? 'opacity-60' : 'border-l-4 border-l-yellow-500' ?>">
                    <div class="flex justify-between items-start mb-4">
                        <div>
                            <div class="flex items-center gap-3">
                                <h3 class="font-bold text-lg"><?= htmlspecialchars($m['assunto']) ?></h3>
                                <?php if (!$m['lido']): ?><span class="badge-unread">NOVA</span><?php endif; ?>
                            </div>
                            <p class="text-sm text-slate-400">De: <b><?= htmlspecialchars($m['nome']) ?></b> (<?= htmlspecialchars($m['email']) ?>) em <?= date('d/m/Y H:i', strtotime($m['created_at'])) ?></p>
                            <?php if ($m['whatsapp']): ?>
                                <p class="text-xs text-green-400 mt-1">WhatsApp: <a href="https://wa.me/<?= preg_replace('/\D/','',$m['whatsapp']) ?>" target="_blank" class="hover:underline"><?= htmlspecialchars($m['whatsapp']) ?></a></p>
                            <?php endif; ?>
                        </div>
                        <div class="flex gap-2">
                            <?php if (!$m['lido']): ?>
                                <a href="?lido=<?= $m['id'] ?>" class="px-3 py-1 bg-slate-700 hover:bg-slate-600 rounded text-xs">Marcar lida</a>
                            <?php endif; ?>
                            <a href="?deletar=<?= $m['id'] ?>" onclick="return confirm('Deletar?')" class="px-3 py-1 bg-red-900/30 hover:bg-red-900/50 text-red-400 rounded text-xs">Deletar</a>
                        </div>
                    </div>
                    <div class="bg-slate-900/50 p-4 rounded-xl text-sm text-slate-300 leading-relaxed whitespace-pre-wrap"><?= htmlspecialchars($m['mensagem']) ?></div>
                    <?php if ($m['musica_id']): ?>
                        <div class="mt-4 text-xs text-slate-500">
                            Relacionado à música: <a href="index.php?q=<?= $m['musica_id'] ?>" class="text-yellow-500 hover:underline"><?= $m['musica_id'] ?></a>
                        </div>
                    <?php endif; ?>
                </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</body>
</html>
