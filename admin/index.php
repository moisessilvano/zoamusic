<?php
// ============================================================
// LOUVOR.NET - Painel Administrativo
// ============================================================

session_start();

if (empty($_SESSION['admin_auth'])) {
    header('Location: login.php');
    exit;
}

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../db.php';

// --- Ações POST ---
$flash = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $uid    = trim($_POST['uid'] ?? '');

    // Valida UID
    $valid_uid = preg_match('/^[0-9a-f]{8}-[0-9a-f]{4}-4[0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$/i', $uid);

    if ($action === 'forcar_concluido' && $valid_uid) {
        $audio = trim($_POST['audio_url'] ?? '');
        if ($audio) {
            db()->prepare("UPDATE musicas SET status='concluido', audio_url=? WHERE id=?")->execute([$audio, $uid]);
            $flash = "✅ Música marcada como concluída.";
        }
    } elseif ($action === 'reprocessar' && $valid_uid) {
        db()->prepare("UPDATE musicas SET status='processando', task_id=NULL, letra=NULL, audio_url=NULL WHERE id=?")->execute([$uid]);
        $flash = "🔄 Música enviada para reprocessamento.";
    } elseif ($action === 'deletar' && $valid_uid) {
        db()->prepare("DELETE FROM musicas WHERE id=?")->execute([$uid]);
        $flash = "🗑 Música removida.";
    }
}

// --- Filtros ---
$status_filter = $_GET['status'] ?? '';
$search        = trim($_GET['q'] ?? '');
$page          = max(1, (int)($_GET['page'] ?? 1));
$per_page      = 20;
$offset        = ($page - 1) * $per_page;

// --- Query principal ---
$where  = [];
$params = [];

if ($status_filter) {
    $where[]  = 'status = ?';
    $params[] = $status_filter;
}
if ($search) {
    $where[]  = '(inspiracao LIKE ? OR titulo LIKE ? OR id = ?)';
    $params[] = "%{$search}%";
    $params[] = "%{$search}%";
    $params[] = $search;
}

$where_sql = $where ? 'WHERE ' . implode(' AND ', $where) : '';

$total_stmt = db()->prepare("SELECT COUNT(*) FROM musicas {$where_sql}");
$total_stmt->execute($params);
$total = (int) $total_stmt->fetchColumn();
$total_pages = max(1, ceil($total / $per_page));

$list_stmt = db()->prepare(
    "SELECT id, inspiracao, titulo, status, asaas_id, audio_url, created_at
     FROM musicas {$where_sql}
     ORDER BY created_at DESC
     LIMIT {$per_page} OFFSET {$offset}"
);
$list_stmt->execute($params);
$musicas = $list_stmt->fetchAll();

// --- Estatísticas rápidas ---
$stats = db()->query("
    SELECT
        COUNT(*) AS total,
        SUM(status = 'concluido') AS concluidas,
        SUM(status = 'processando') AS processando,
        SUM(status = 'aguardando_pagamento') AS aguardando,
        SUM(status = 'erro') AS com_erro
    FROM musicas
")->fetch();

// --- Helpers ---
$status_labels = [
    'aguardando_pagamento' => ['label' => 'Aguardando PIX', 'cls' => 'bg-yellow-100 text-yellow-700'],
    'processando'          => ['label' => 'Processando',   'cls' => 'bg-blue-100 text-blue-700'],
    'concluido'            => ['label' => 'Concluído',     'cls' => 'bg-green-100 text-green-700'],
    'erro'                 => ['label' => 'Erro',          'cls' => 'bg-red-100 text-red-700'],
];

function build_url(array $overrides = []): string {
    $params = array_merge($_GET, $overrides);
    return '?' . http_build_query(array_filter($params, fn($v) => $v !== ''));
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin — LOUVOR.NET</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        body { background-color: #f1f5f9; color: #1E293B; }
        .gold-text { color: #D4AF37; }
        .sidebar { background: #1E293B; }
        .nav-link { transition: background 0.15s; }
        .nav-link:hover, .nav-link.active { background: rgba(212,175,55,0.15); color: #D4AF37; }
        .btn-gold { background-color: #D4AF37; }
        .btn-gold:hover { background-color: #B8962E; }
        tr:hover td { background-color: #f8fafc; }
        /* Modal */
        #modal { display: none; }
        #modal.open { display: flex; }
    </style>
</head>
<body class="min-h-screen flex">

<!-- SIDEBAR -->
<aside class="sidebar w-56 flex-shrink-0 flex flex-col min-h-screen sticky top-0 h-screen">
    <div class="px-5 py-6 border-b border-white/10">
        <a href="../" class="flex items-center gap-2">
            <svg class="w-7 h-7" viewBox="0 0 32 32" fill="none">
                <circle cx="16" cy="16" r="15" stroke="#D4AF37" stroke-width="2"/>
                <path d="M16 8 L18 13 L23 13 L19 16 L21 21 L16 18 L11 21 L13 16 L9 13 L14 13 Z" fill="#D4AF37"/>
            </svg>
            <span class="text-lg font-bold tracking-widest text-white">LOUVOR<span class="gold-text">.NET</span></span>
        </a>
        <p class="text-gray-500 text-xs mt-1 ml-9">Admin</p>
    </div>

    <nav class="flex-1 px-3 py-4 space-y-1">
        <a href="index.php" class="nav-link active flex items-center gap-3 px-4 py-2.5 rounded-xl text-gray-300 text-sm font-medium">
            <span>📊</span> Dashboard
        </a>
        <a href="index.php?status=concluido" class="nav-link flex items-center gap-3 px-4 py-2.5 rounded-xl text-gray-400 text-sm">
            <span>✅</span> Concluídas
        </a>
        <a href="index.php?status=processando" class="nav-link flex items-center gap-3 px-4 py-2.5 rounded-xl text-gray-400 text-sm">
            <span>⏳</span> Processando
        </a>
        <a href="index.php?status=aguardando_pagamento" class="nav-link flex items-center gap-3 px-4 py-2.5 rounded-xl text-gray-400 text-sm">
            <span>💳</span> Aguardando PIX
        </a>
        <a href="index.php?status=erro" class="nav-link flex items-center gap-3 px-4 py-2.5 rounded-xl text-gray-400 text-sm">
            <span>❌</span> Com Erro
        </a>
    </nav>

    <div class="px-3 py-4 border-t border-white/10">
        <a href="../" class="nav-link flex items-center gap-3 px-4 py-2.5 rounded-xl text-gray-400 text-sm">
            <span>🌐</span> Ver site
        </a>
        <a href="logout.php" class="nav-link flex items-center gap-3 px-4 py-2.5 rounded-xl text-red-400 text-sm mt-1">
            <span>🚪</span> Sair
        </a>
    </div>
</aside>

<!-- MAIN -->
<main class="flex-1 overflow-auto">

    <!-- TOPBAR -->
    <header class="bg-white border-b border-gray-200 px-8 py-4 flex items-center justify-between sticky top-0 z-10">
        <h1 class="text-xl font-bold text-gray-800">Dashboard</h1>
        <div class="text-sm text-gray-400">
            <?= date('d/m/Y H:i') ?>
        </div>
    </header>

    <div class="px-8 py-6">

        <!-- FLASH -->
        <?php if ($flash): ?>
        <div class="bg-green-50 border border-green-200 text-green-700 rounded-xl px-5 py-3 mb-6 flex items-center gap-2">
            <?= htmlspecialchars($flash) ?>
        </div>
        <?php endif; ?>

        <!-- STATS CARDS -->
        <div class="grid grid-cols-2 lg:grid-cols-4 gap-4 mb-8">
            <?php $cards = [
                ['Total', $stats['total'],      '#6366f1', '🎵'],
                ['Concluídas', $stats['concluidas'],  '#22c55e', '✅'],
                ['Processando', $stats['processando'], '#3b82f6', '⏳'],
                ['Com Erro', $stats['com_erro'],   '#ef4444', '❌'],
            ]; ?>
            <?php foreach ($cards as [$label, $value, $color, $icon]): ?>
            <div class="bg-white rounded-2xl p-5 shadow-sm border border-gray-100">
                <div class="flex items-start justify-between mb-3">
                    <span class="text-2xl"><?= $icon ?></span>
                    <span class="text-2xl font-bold" style="color:<?= $color ?>"><?= $value ?></span>
                </div>
                <p class="text-sm text-gray-500 font-medium"><?= $label ?></p>
            </div>
            <?php endforeach; ?>
        </div>

        <!-- FILTROS E BUSCA -->
        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 mb-6">
            <div class="px-6 py-4 border-b border-gray-100 flex items-center justify-between flex-wrap gap-3">
                <h2 class="font-bold text-gray-700">
                    Músicas
                    <span class="text-gray-400 font-normal text-sm ml-2">(<?= $total ?> encontradas)</span>
                </h2>

                <form method="GET" class="flex gap-3 flex-wrap">
                    <input type="hidden" name="status" value="<?= htmlspecialchars($status_filter) ?>">
                    <input type="text" name="q" value="<?= htmlspecialchars($search) ?>"
                        placeholder="Buscar por inspiração, título ou UUID..."
                        class="border border-gray-200 rounded-xl px-4 py-2 text-sm w-64 focus:border-[#D4AF37] focus:outline-none">
                    <button type="submit"
                        class="btn-gold px-4 py-2 rounded-xl text-white text-sm font-semibold">
                        Buscar
                    </button>
                    <?php if ($search || $status_filter): ?>
                    <a href="index.php" class="px-4 py-2 rounded-xl border border-gray-200 text-gray-500 text-sm hover:bg-gray-50">
                        Limpar
                    </a>
                    <?php endif; ?>
                </form>
            </div>

            <!-- TABELA -->
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="border-b border-gray-100">
                            <th class="text-left px-6 py-3 text-gray-400 font-semibold text-xs uppercase tracking-wider">Data</th>
                            <th class="text-left px-6 py-3 text-gray-400 font-semibold text-xs uppercase tracking-wider">Título / Inspiração</th>
                            <th class="text-left px-4 py-3 text-gray-400 font-semibold text-xs uppercase tracking-wider">Status</th>
                            <th class="text-left px-4 py-3 text-gray-400 font-semibold text-xs uppercase tracking-wider">Asaas ID</th>
                            <th class="text-center px-4 py-3 text-gray-400 font-semibold text-xs uppercase tracking-wider">Ações</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-50">
                        <?php if (empty($musicas)): ?>
                        <tr>
                            <td colspan="5" class="px-6 py-12 text-center text-gray-400">
                                Nenhuma música encontrada.
                            </td>
                        </tr>
                        <?php endif; ?>
                        <?php foreach ($musicas as $m): ?>
                        <?php
                            $sl = $status_labels[$m['status']] ?? ['label' => $m['status'], 'cls' => 'bg-gray-100 text-gray-600'];
                            $inspiracao_short = mb_strimwidth($m['inspiracao'], 0, 70, '...');
                        ?>
                        <tr class="cursor-default">
                            <td class="px-6 py-4 text-gray-400 whitespace-nowrap text-xs">
                                <?= date('d/m/y H:i', strtotime($m['created_at'])) ?>
                            </td>
                            <td class="px-6 py-4 max-w-xs">
                                <?php if ($m['titulo']): ?>
                                <p class="font-semibold text-gray-800 truncate"><?= htmlspecialchars($m['titulo']) ?></p>
                                <?php endif; ?>
                                <p class="text-gray-400 text-xs truncate"><?= htmlspecialchars($inspiracao_short) ?></p>
                                <p class="text-gray-300 text-xs font-mono mt-0.5"><?= htmlspecialchars($m['id']) ?></p>
                            </td>
                            <td class="px-4 py-4 whitespace-nowrap">
                                <span class="px-2.5 py-1 rounded-full text-xs font-semibold <?= $sl['cls'] ?>">
                                    <?= $sl['label'] ?>
                                </span>
                            </td>
                            <td class="px-4 py-4 text-xs text-gray-400 font-mono">
                                <?= htmlspecialchars($m['asaas_id'] ?? '—') ?>
                            </td>
                            <td class="px-4 py-4 text-center">
                                <div class="flex items-center justify-center gap-2">
                                    <!-- Ver música -->
                                    <?php if ($m['status'] === 'concluido'): ?>
                                    <a href="../ouvir.php?uid=<?= urlencode($m['id']) ?>" target="_blank"
                                        title="Ouvir"
                                        class="p-1.5 rounded-lg text-gray-400 hover:text-green-600 hover:bg-green-50 transition-colors">
                                        🎵
                                    </a>
                                    <?php endif; ?>

                                    <!-- Ver checkout -->
                                    <a href="../checkout.php?uid=<?= urlencode($m['id']) ?>" target="_blank"
                                        title="Ver checkout"
                                        class="p-1.5 rounded-lg text-gray-400 hover:text-blue-600 hover:bg-blue-50 transition-colors">
                                        💳
                                    </a>

                                    <!-- Reprocessar -->
                                    <?php if (in_array($m['status'], ['erro', 'processando'])): ?>
                                    <form method="POST" class="inline" onsubmit="return confirm('Reprocessar esta música?')">
                                        <input type="hidden" name="action" value="reprocessar">
                                        <input type="hidden" name="uid" value="<?= htmlspecialchars($m['id']) ?>">
                                        <button type="submit" title="Reprocessar"
                                            class="p-1.5 rounded-lg text-gray-400 hover:text-orange-600 hover:bg-orange-50 transition-colors">
                                            🔄
                                        </button>
                                    </form>
                                    <?php endif; ?>

                                    <!-- Forçar concluído (modal) -->
                                    <?php if ($m['status'] !== 'concluido'): ?>
                                    <button type="button"
                                        onclick="abrirModalConcluir('<?= htmlspecialchars($m['id']) ?>', '<?= htmlspecialchars(addslashes($m['titulo'] ?? '')) ?>')"
                                        title="Marcar como concluído"
                                        class="p-1.5 rounded-lg text-gray-400 hover:text-green-600 hover:bg-green-50 transition-colors">
                                        ✅
                                    </button>
                                    <?php endif; ?>

                                    <!-- Deletar -->
                                    <form method="POST" class="inline" onsubmit="return confirm('Deletar permanentemente?')">
                                        <input type="hidden" name="action" value="deletar">
                                        <input type="hidden" name="uid" value="<?= htmlspecialchars($m['id']) ?>">
                                        <button type="submit" title="Deletar"
                                            class="p-1.5 rounded-lg text-gray-400 hover:text-red-600 hover:bg-red-50 transition-colors">
                                            🗑
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <!-- PAGINAÇÃO -->
            <?php if ($total_pages > 1): ?>
            <div class="px-6 py-4 border-t border-gray-100 flex items-center justify-between">
                <p class="text-sm text-gray-400">
                    Página <?= $page ?> de <?= $total_pages ?>
                </p>
                <div class="flex gap-2">
                    <?php if ($page > 1): ?>
                    <a href="<?= build_url(['page' => $page - 1]) ?>"
                        class="px-4 py-2 rounded-xl border border-gray-200 text-sm text-gray-600 hover:bg-gray-50">
                        ← Anterior
                    </a>
                    <?php endif; ?>
                    <?php if ($page < $total_pages): ?>
                    <a href="<?= build_url(['page' => $page + 1]) ?>"
                        class="px-4 py-2 rounded-xl border border-gray-200 text-sm text-gray-600 hover:bg-gray-50">
                        Próxima →
                    </a>
                    <?php endif; ?>
                </div>
            </div>
            <?php endif; ?>
        </div>

    </div>
</main>

<!-- MODAL: Forçar Concluído -->
<div id="modal" class="fixed inset-0 bg-black/50 z-50 items-center justify-center px-4">
    <div class="bg-white rounded-2xl shadow-2xl w-full max-w-md p-6">
        <h3 class="text-lg font-bold text-gray-800 mb-1">Marcar como Concluído</h3>
        <p class="text-sm text-gray-400 mb-5">Informe a URL do áudio para finalizar manualmente.</p>

        <form method="POST">
            <input type="hidden" name="action" value="forcar_concluido">
            <input type="hidden" name="uid" id="modal-uid">

            <div class="mb-4">
                <label class="block text-sm font-semibold text-gray-600 mb-1.5">Título</label>
                <input type="text" id="modal-titulo" readonly
                    class="w-full border border-gray-200 rounded-xl px-4 py-2.5 text-sm text-gray-500 bg-gray-50">
            </div>
            <div class="mb-5">
                <label class="block text-sm font-semibold text-gray-600 mb-1.5">URL do Áudio (MP3)</label>
                <input type="url" name="audio_url" required
                    placeholder="https://cdn.exemplo.com/musica.mp3"
                    class="w-full border border-gray-200 rounded-xl px-4 py-2.5 text-sm focus:border-[#D4AF37] focus:outline-none">
            </div>
            <div class="flex gap-3">
                <button type="button" onclick="fecharModal()"
                    class="flex-1 py-3 rounded-xl border border-gray-200 text-gray-600 font-semibold hover:bg-gray-50">
                    Cancelar
                </button>
                <button type="submit"
                    class="flex-1 py-3 rounded-xl btn-gold text-white font-semibold">
                    Confirmar
                </button>
            </div>
        </form>
    </div>
</div>

<script>
function abrirModalConcluir(uid, titulo) {
    document.getElementById('modal-uid').value = uid;
    document.getElementById('modal-titulo').value = titulo || uid;
    document.getElementById('modal').classList.add('open');
}
function fecharModal() {
    document.getElementById('modal').classList.remove('open');
}
document.getElementById('modal').addEventListener('click', function(e) {
    if (e.target === this) fecharModal();
});
</script>
</body>
</html>
