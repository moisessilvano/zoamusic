<?php
// ============================================================
// LOUVOR.NET - Painel Administrativo (Renovado)
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

// --- Ações POST ---
$flash = $flash_type = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $uid    = trim($_POST['uid'] ?? '');
    $valid_uid = preg_match('/^[0-9a-f]{8}-[0-9a-f]{4}-4[0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$/i', $uid);

    if ($action === 'forcar_concluido' && $valid_uid) {
        $audio = trim($_POST['audio_url'] ?? '');
        if ($audio) {
            db()->prepare("UPDATE musicas SET status='concluido', audio_url=? WHERE id=?")->execute([$audio, $uid]);
            $flash = "✅ Música marcada como concluída."; $flash_type = 'green';
        }
    } elseif ($action === 'forcar_pagamento' && $valid_uid) {
        // Muda para processando
        db()->prepare("UPDATE musicas SET status='processando' WHERE id=?")->execute([$uid]);
        
        // Dispara o worker via CURL (fire and forget)
        $secret = hash_hmac('sha256', $uid, ASAAS_API_KEY);
        $worker_url = rtrim(BASE_URL, '/') . "/api/gerar_musica.php?uid={$uid}&secret={$secret}";
        $ch = curl_init($worker_url);
        curl_setopt($ch, CURLOPT_TIMEOUT, 1);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, false);
        curl_exec($ch);
        curl_close($ch);

        $flash = "⚡ Pagamento forçado! A geração da música foi iniciada."; $flash_type = 'yellow';
    } elseif ($action === 'reprocessar' && $valid_uid) {
        db()->prepare("UPDATE musicas SET status='processando', task_id=NULL, letra=NULL, audio_url=NULL WHERE id=?")->execute([$uid]);
        $flash = "🔄 Música enviada para reprocessamento."; $flash_type = 'blue';
    } elseif ($action === 'deletar' && $valid_uid) {
        db()->prepare("DELETE FROM musicas WHERE id=?")->execute([$uid]);
        $flash = "🗑 Música removida."; $flash_type = 'red';
    }
}

// --- Filtros ---
$status_filter = $_GET['status'] ?? '';
$search        = trim($_GET['q'] ?? '');
$page          = max(1, (int)($_GET['page'] ?? 1));
$per_page      = 20;
$offset        = ($page - 1) * $per_page;

$where = []; $params = [];
if ($status_filter) { $where[] = 'status = ?'; $params[] = $status_filter; }
if ($search) {
    $where[] = '(inspiracao LIKE ? OR titulo LIKE ? OR id = ? OR email LIKE ?)';
    array_push($params, "%$search%", "%$search%", $search, "%$search%");
}
$where_sql = $where ? 'WHERE ' . implode(' AND ', $where) : '';

$total = (int) db()->prepare("SELECT COUNT(*) FROM musicas $where_sql")->execute($params) ? 0 : 0;
$ts = db()->prepare("SELECT COUNT(*) FROM musicas $where_sql"); $ts->execute($params);
$total = (int) $ts->fetchColumn();
$total_pages = max(1, ceil($total / $per_page));

$ls = db()->prepare("SELECT id, inspiracao, titulo, status, asaas_id, audio_url, email, email_enviado, created_at FROM musicas $where_sql ORDER BY created_at DESC LIMIT $per_page OFFSET $offset");
$ls->execute($params);
$musicas = $ls->fetchAll();

// --- Stats gerais ---
$stats = db()->query("
    SELECT
        COUNT(*) AS total,
        SUM(status = 'concluido') AS concluidas,
        SUM(status = 'processando') AS processando,
        SUM(status = 'aguardando_pagamento') AS aguardando,
        SUM(status = 'erro') AS com_erro,
        SUM(email IS NOT NULL AND email != '') AS com_email
    FROM musicas
")->fetch();

// --- Faturamento ---
$preco = MUSICA_PRICE;
$fat = db()->query("
    SELECT
        SUM(status='concluido' AND DATE(created_at) = CURDATE()) AS hoje,
        SUM(status='concluido' AND YEARWEEK(created_at,1) = YEARWEEK(NOW(),1)) AS semana,
        SUM(status='concluido' AND YEAR(created_at)=YEAR(NOW()) AND MONTH(created_at)=MONTH(NOW())) AS mes,
        SUM(status='concluido' AND created_at >= NOW() - INTERVAL 30 DAY) AS ultimos30,
        SUM(status='concluido') AS total_pago
    FROM musicas
")->fetch();

// --- Músicas por dia (últimos 14 dias) ---
$chart_data = db()->query("
    SELECT DATE(created_at) as dia,
           COUNT(*) as total,
           SUM(status='concluido') as pagas
    FROM musicas
    WHERE created_at >= NOW() - INTERVAL 14 DAY
    GROUP BY dia ORDER BY dia ASC
")->fetchAll();

// --- Top horas ---
$top_horas = db()->query("
    SELECT HOUR(created_at) as hora, COUNT(*) as qtd
    FROM musicas GROUP BY hora ORDER BY hora ASC
")->fetchAll();

$status_labels = [
    'aguardando_pagamento' => ['Aguardando PIX', 'gold'],
    'processando'          => ['Processando',    'blue'],
    'concluido'            => ['Concluído',      'green'],
    'erro'                 => ['Erro',           'red'],
];

function build_url(array $overrides = []): string {
    return '?' . http_build_query(array_filter(array_merge($_GET, $overrides), fn($v) => $v !== ''));
}

function fmt_brl(float $val): string {
    return 'R$ ' . number_format($val, 2, ',', '.');
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Admin — LOUVOR.NET</title>
<link rel="icon" type="image/jpeg" href="../assets/logo.jpeg">
<script src="https://cdn.tailwindcss.com"></script>
<style>
    * { font-family: 'Inter', system-ui, sans-serif; }
    body { background: #0F172A; color: #F1F5F9; }

    /* Sidebar */
    .sidebar { background: #1E293B; border-right: 1px solid rgba(255,255,255,0.06); }
    .nav-link { transition: all 0.15s; border-radius: 10px; }
    .nav-link:hover, .nav-link.active { background: rgba(212,175,55,0.12); color: #D4AF37; }

    /* Cards */
    .stat-card { background: #1E293B; border: 1px solid rgba(255,255,255,0.06); border-radius: 16px; transition: transform 0.2s, box-shadow 0.2s; }
    .stat-card:hover { transform: translateY(-2px); box-shadow: 0 8px 32px rgba(0,0,0,0.3); }

    /* Table */
    .data-table { background: #1E293B; border-radius: 16px; border: 1px solid rgba(255,255,255,0.06); overflow: hidden; }
    .data-table thead th { background: #162032; color: #94A3B8; font-size: 11px; text-transform: uppercase; letter-spacing: .06em; }
    .data-table tbody tr:hover td { background: rgba(255,255,255,0.03); }
    .data-table tbody td { border-top: 1px solid rgba(255,255,255,0.04); }

    /* Badges */
    .badge-gold  { background: rgba(212,175,55,0.15); color: #FDE047; }
    .badge-green { background: rgba(34,197,94,0.15);  color: #4ADE80; }
    .badge-blue  { background: rgba(59,130,246,0.15); color: #60A5FA; }
    .badge-red   { background: rgba(239,68,68,0.15);  color: #F87171; }

    /* Revenue card */
    .rev-card { background: linear-gradient(135deg, #1E293B 0%, #1a2744 100%); border: 1px solid rgba(212,175,55,0.2); }

    /* Chart bars */
    .chart-bar { background: linear-gradient(to top, #C9A84C, #E8CC80); border-radius: 4px 4px 0 0; min-width: 20px; transition: opacity 0.2s; }
    .chart-bar:hover { opacity: 0.8; }

    /* Topbar */
    .topbar { background: #1E293B; border-bottom: 1px solid rgba(255,255,255,0.06); }

    /* Mobile menu */
    #mobile-menu { display: none; }
    #mobile-menu.open { display: flex; }

    /* Scrollbar */
    ::-webkit-scrollbar { width: 5px; height: 5px; }
    ::-webkit-scrollbar-track { background: #0F172A; }
    ::-webkit-scrollbar-thumb { background: rgba(212,175,55,0.3); border-radius: 10px; }

    /* Modal */
    #modal { display: none; }
    #modal.open { display: flex; }

    @media (max-width: 768px) {
        .sidebar { display: none; }
        .sidebar.open { display: flex; position: fixed; z-index: 100; width: 240px; height: 100vh; }
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
        <a href="index.php" class="nav-link <?= !$status_filter ? 'active' : '' ?> flex items-center gap-3 px-3 py-2.5 text-slate-200 text-sm">
            <span>📊</span> Dashboard
        </a>

        <p class="text-slate-400 text-xs font-semibold uppercase tracking-wider px-3 py-2 mt-3">Músicas</p>
        <a href="index.php?status=concluido" class="nav-link <?= $status_filter==='concluido' ? 'active' : '' ?> flex items-center gap-3 px-3 py-2.5 text-slate-300 text-sm">
            <span>✅</span> Concluídas
            <span class="ml-auto badge-green text-xs px-2 py-0.5 rounded-full font-semibold"><?= $stats['concluidas'] ?></span>
        </a>
        <a href="index.php?status=processando" class="nav-link <?= $status_filter==='processando' ? 'active' : '' ?> flex items-center gap-3 px-3 py-2.5 text-slate-300 text-sm">
            <span>⏳</span> Processando
            <span class="ml-auto badge-blue text-xs px-2 py-0.5 rounded-full font-semibold"><?= $stats['processando'] ?></span>
        </a>
        <a href="index.php?status=aguardando_pagamento" class="nav-link <?= $status_filter==='aguardando_pagamento' ? 'active' : '' ?> flex items-center gap-3 px-3 py-2.5 text-slate-300 text-sm">
            <span>💳</span> Aguardando PIX
            <span class="ml-auto badge-gold text-xs px-2 py-0.5 rounded-full font-semibold"><?= $stats['aguardando'] ?></span>
        </a>
        <a href="index.php?status=erro" class="nav-link <?= $status_filter==='erro' ? 'active' : '' ?> flex items-center gap-3 px-3 py-2.5 text-slate-300 text-sm">
            <span>❌</span> Com Erro
            <span class="ml-auto badge-red text-xs px-2 py-0.5 rounded-full font-semibold"><?= $stats['com_erro'] ?></span>
        </a>

        <p class="text-slate-400 text-xs font-semibold uppercase tracking-wider px-3 py-2 mt-4">Atendimento</p>
        <a href="sac.php" class="nav-link flex items-center gap-3 px-3 py-2.5 text-slate-300 text-sm">
            <span>📩</span> Mensagens (SAC)
        </a>

        <p class="text-slate-400 text-xs font-semibold uppercase tracking-wider px-3 py-2 mt-4">Sistema</p>
        <a href="users.php" class="nav-link flex items-center gap-3 px-3 py-2.5 text-slate-300 text-sm">
            <span>👥</span> Administradores
        </a>
    </nav>

    <div class="px-3 py-4 border-t border-white/5 space-y-0.5">
        <a href="../" class="nav-link flex items-center gap-3 px-3 py-2.5 text-slate-300 text-sm">
            <span>🌐</span> Ver site
        </a>
        <a href="logout.php" class="nav-link flex items-center gap-3 px-3 py-2.5 text-red-400 text-sm">
            <span>🚪</span> Sair
        </a>
    </div>
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
            Dashboard
            <span class="hidden md:inline text-slate-400 font-normal text-sm ml-3 border-l border-slate-700 pl-3">Bem-vindo(a), <?= htmlspecialchars(explode(' ', $_SESSION['admin_user_nome'] ?? 'Admin')[0]) ?></span>
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

        <!-- ══ FATURAMENTO ══ -->
        <div>
            <h2 class="text-xs font-bold tracking-widest uppercase text-slate-400 mb-3">💰 Faturamento</h2>
            <div class="grid grid-cols-2 lg:grid-cols-4 gap-3">
                <?php $fat_cards = [
                    ['Hoje',          $fat['hoje']      * $preco, $fat['hoje'],      '📅'],
                    ['Esta semana',   $fat['semana']    * $preco, $fat['semana'],    '📆'],
                    ['Este mês',      $fat['mes']       * $preco, $fat['mes'],       '🗓'],
                    ['Últimos 30d',   $fat['ultimos30'] * $preco, $fat['ultimos30'], '📈'],
                ]; ?>
                <?php foreach ($fat_cards as [$lbl, $valor, $qtd, $ico]): ?>
                <div class="rev-card rounded-2xl p-5">
                    <div class="flex items-center gap-2 mb-3">
                        <span class="text-xl"><?= $ico ?></span>
                        <p class="text-xs text-slate-300 font-medium"><?= $lbl ?></p>
                    </div>
                    <p class="text-2xl font-bold" style="color:#D4AF37"><?= fmt_brl((float)$valor) ?></p>
                    <p class="text-xs text-slate-400 mt-1"><?= (int)$qtd ?> música<?= $qtd != 1 ? 's' : '' ?> pagas</p>
                </div>
                <?php endforeach; ?>
            </div>
        </div>

        <!-- ══ STATS GERAIS ══ -->
        <div>
            <h2 class="text-xs font-bold tracking-widest uppercase text-slate-400 mb-3">📊 Volume</h2>
            <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-5 gap-3">
                <?php $vol_cards = [
                    ['Total criadas', $stats['total'],        '#818CF8', '🎵'],
                    ['Concluídas',    $stats['concluidas'],   '#4ADE80', '✅'],
                    ['Processando',   $stats['processando'],  '#60A5FA', '⏳'],
                    ['Com Erro',      $stats['com_erro'],     '#F87171', '❌'],
                    ['Com E-mail',    $stats['com_email'],    '#D4AF37', '✉️'],
                ]; ?>
                <?php foreach ($vol_cards as [$lbl, $val, $color, $ico]): ?>
                <div class="stat-card p-5">
                    <div class="flex items-center justify-between mb-2">
                        <span class="text-xl"><?= $ico ?></span>
                        <span class="text-2xl font-bold" style="color:<?= $color ?>"><?= $val ?></span>
                    </div>
                    <p class="text-xs text-slate-300"><?= $lbl ?></p>
                </div>
                <?php endforeach; ?>
            </div>
        </div>

        <!-- ══ GRÁFICO 14 DIAS ══ -->
        <div class="stat-card p-5">
            <div class="flex items-center justify-between mb-4">
                <h2 class="font-bold text-slate-100">Atividade — Últimos 14 dias</h2>
                <div class="flex items-center gap-4 text-xs text-slate-300">
                    <span class="flex items-center gap-1.5"><span style="background:#C9A84C;width:10px;height:10px;border-radius:2px;display:inline-block;"></span>Criadas</span>
                    <span class="flex items-center gap-1.5"><span style="background:#4ADE80;width:10px;height:10px;border-radius:2px;display:inline-block;"></span>Pagas</span>
                </div>
            </div>
            <?php if (empty($chart_data)): ?>
            <p class="text-slate-400 text-sm text-center py-8">Sem dados ainda.</p>
            <?php else:
                $max_total = max(array_column($chart_data, 'total') ?: [1]);
            ?>
            <div class="flex items-end gap-1.5" style="height:120px; overflow-x:auto;">
                <?php foreach ($chart_data as $d):
                    $h = max(4, round(($d['total'] / $max_total) * 100));
                    $hp = max(2, round(($d['pagas'] / $max_total) * 100));
                    $day = date('d/M', strtotime($d['dia']));
                ?>
                <div class="flex flex-col items-center gap-1 flex-1 min-w-[30px]" title="<?= $day ?>: <?= $d['total'] ?> criadas, <?= $d['pagas'] ?> pagas">
                    <div class="w-full flex items-end gap-0.5" style="height:100px;">
                        <div class="chart-bar flex-1" style="height:<?= $h ?>%;"></div>
                        <div class="flex-1 rounded-t" style="height:<?= $hp ?>%; background:linear-gradient(to top,#22c55e,#4ade80); border-radius:4px 4px 0 0; min-width:8px;"></div>
                    </div>
                    <p class="text-slate-300" style="font-size:9px; white-space:nowrap;"><?= $day ?></p>
                </div>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>
        </div>

        <!-- ══ LISTA DE MÚSICAS ══ -->
        <div class="data-table">
            <div class="px-5 py-4 border-b border-white/5 flex flex-wrap items-center justify-between gap-3">
                <div>
                    <h2 class="font-bold text-slate-100">Músicas</h2>
                    <p class="text-xs text-slate-400"><?= $total ?> encontradas</p>
                </div>
                <form method="GET" class="flex gap-2 flex-wrap">
                    <input type="hidden" name="status" value="<?= htmlspecialchars($status_filter) ?>">
                    <input type="text" name="q" value="<?= htmlspecialchars($search) ?>"
                        placeholder="Buscar..."
                        class="bg-slate-800 border border-slate-600 rounded-xl px-4 py-2 text-sm text-slate-100 placeholder-slate-400 focus:border-yellow-500/50 focus:outline-none w-48 md:w-64">
                    <button type="submit" class="px-4 py-2 rounded-xl text-sm font-semibold text-white"
                        style="background:linear-gradient(135deg,#C9A84C,#D4AF37);">Buscar</button>
                    <?php if ($search || $status_filter): ?>
                    <a href="index.php" class="px-4 py-2 rounded-xl border border-slate-600 text-slate-300 text-sm hover:bg-slate-700">Limpar</a>
                    <?php endif; ?>
                </form>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead>
                        <tr>
                            <th class="data-table text-left px-5 py-3 text-slate-300">Data</th>
                            <th class="data-table text-left px-4 py-3 text-slate-300">Título / Inspiração</th>
                            <th class="data-table text-left px-4 py-3 text-slate-300">Status</th>
                            <th class="data-table text-left px-4 py-3 hidden md:table-cell text-slate-300">E-mail</th>
                            <th class="data-table text-center px-4 py-3 text-slate-300">Ações</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($musicas)): ?>
                        <tr><td colspan="5" class="px-5 py-12 text-center text-slate-400">Nenhuma música encontrada.</td></tr>
                        <?php endif; ?>
                        <?php foreach ($musicas as $m):
                            [$lbl, $cls] = $status_labels[$m['status']] ?? [$m['status'], 'gold'];
                            $insp = mb_strimwidth($m['inspiracao'], 0, 60, '...');
                        ?>
                        <tr>
                            <td class="px-5 py-3.5 text-slate-300 text-xs whitespace-nowrap">
                                <?= date('d/m/y H:i', strtotime($m['created_at'])) ?>
                            </td>
                            <td class="px-4 py-3.5 max-w-xs">
                                <?php if ($m['titulo']): ?>
                                <p class="font-semibold text-slate-100 truncate"><?= htmlspecialchars($m['titulo']) ?></p>
                                <?php endif; ?>
                                <p class="text-slate-400 text-xs truncate"><?= htmlspecialchars($insp) ?></p>
                                <p class="text-slate-500 text-xs font-mono mt-0.5 truncate"><?= $m['id'] ?></p>
                            </td>
                            <td class="px-4 py-3.5 whitespace-nowrap">
                                <span class="badge-<?= $cls ?> px-2.5 py-1 rounded-full text-xs font-semibold"><?= $lbl ?></span>
                            </td>
                            <td class="px-4 py-3.5 text-xs text-slate-300 hidden md:table-cell">
                                <?php if ($m['email']): ?>
                                    <div class="flex flex-col items-start gap-1">
                                        <span class="font-medium whitespace-nowrap"><?= htmlspecialchars($m['email']) ?></span>
                                        <?php if ($m['email_enviado']): ?>
                                            <span class="badge-green text-[10px] px-1.5 py-0.5 rounded uppercase tracking-wide">E-mail Enviado ✓</span>
                                        <?php elseif ($m['status'] === 'concluido'): ?>
                                            <span class="badge-red text-[10px] px-1.5 py-0.5 rounded uppercase tracking-wide">E-mail Falhou ❌</span>
                                        <?php else: ?>
                                            <span class="badge-gold text-[10px] px-1.5 py-0.5 rounded uppercase tracking-wide">E-mail Pendente ⏳</span>
                                        <?php endif; ?>
                                    </div>
                                <?php else: ?>
                                    <span class="text-slate-500 italic">—</span>
                                <?php endif; ?>
                            </td>
                            <td class="px-4 py-3.5">
                                <div class="flex items-center justify-center gap-1.5">
                                    <?php if ($m['status'] === 'concluido'): ?>
                                    <a href="../ouvir.php?uid=<?= urlencode($m['id']) ?>" target="_blank"
                                        class="p-1.5 rounded-lg text-slate-400 hover:text-green-400 hover:bg-green-500/10 transition-colors" title="Ouvir">🎵</a>
                                    <?php endif; ?>
                                    <?php if ($m['status'] === 'aguardando_pagamento'): ?>
                                    <form method="POST" class="inline" onsubmit="return confirm('Forçar pagamento manual e iniciar geração?')">
                                        <input type="hidden" name="action" value="forcar_pagamento">
                                        <input type="hidden" name="uid" value="<?= htmlspecialchars($m['id']) ?>">
                                        <button type="submit" class="p-1.5 rounded-lg text-slate-400 hover:text-yellow-400 hover:bg-yellow-500/10 transition-colors" title="Forçar Pagamento">⚡</button>
                                    </form>
                                    <?php endif; ?>
                                    <?php if (in_array($m['status'], ['erro','processando'])): ?>
                                    <form method="POST" class="inline" onsubmit="return confirm('Reprocessar?')">
                                        <input type="hidden" name="action" value="reprocessar">
                                        <input type="hidden" name="uid" value="<?= htmlspecialchars($m['id']) ?>">
                                        <button type="submit" class="p-1.5 rounded-lg text-slate-400 hover:text-orange-400 hover:bg-orange-500/10 transition-colors" title="Reprocessar">🔄</button>
                                    </form>
                                    <?php endif; ?>
                                    <?php if ($m['status'] !== 'concluido'): ?>
                                    <button type="button"
                                        onclick="abrirModalConcluir('<?= htmlspecialchars($m['id']) ?>', '<?= htmlspecialchars(addslashes($m['titulo'] ?? '')) ?>')"
                                        class="p-1.5 rounded-lg text-slate-400 hover:text-green-400 hover:bg-green-500/10 transition-colors" title="Marcar concluído">✅</button>
                                    <?php endif; ?>
                                    <form method="POST" class="inline" onsubmit="return confirm('Deletar permanentemente?')">
                                        <input type="hidden" name="action" value="deletar">
                                        <input type="hidden" name="uid" value="<?= htmlspecialchars($m['id']) ?>">
                                        <button type="submit" class="p-1.5 rounded-lg text-slate-400 hover:text-red-400 hover:bg-red-500/10 transition-colors" title="Deletar">🗑</button>
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
            <div class="px-5 py-4 border-t border-white/5 flex flex-wrap items-center justify-between gap-3">
                <p class="text-xs text-slate-400">Página <?= $page ?> de <?= $total_pages ?></p>
                <div class="flex gap-2">
                    <?php if ($page > 1): ?>
                    <a href="<?= build_url(['page' => $page - 1]) ?>" class="px-4 py-2 rounded-xl border border-slate-600 text-sm text-slate-300 hover:bg-slate-700">← Anterior</a>
                    <?php endif; ?>
                    <?php if ($page < $total_pages): ?>
                    <a href="<?= build_url(['page' => $page + 1]) ?>" class="px-4 py-2 rounded-xl border border-slate-600 text-sm text-slate-300 hover:bg-slate-700">Próxima →</a>
                    <?php endif; ?>
                </div>
            </div>
            <?php endif; ?>
        </div>

        <!-- Rodapé admin -->
        <p class="text-center text-xs text-slate-500 pb-4">
            LOUVOR.NET Admin • Total faturado: <strong style="color:#C9A84C"><?= fmt_brl($stats['concluidas'] * $preco) ?></strong>
        </p>
    </div>
</div>

<!-- ═══ MODAL ═══ -->
<div id="modal" class="fixed inset-0 bg-black/70 backdrop-blur-sm z-50 items-center justify-center px-4">
    <div class="bg-slate-800 border border-slate-700 rounded-2xl shadow-2xl w-full max-w-md p-6">
        <h3 class="text-lg font-bold text-white mb-1">Marcar como Concluído</h3>
        <p class="text-sm text-slate-400 mb-5">Informe a URL do áudio para finalizar manualmente.</p>
        <form method="POST">
            <input type="hidden" name="action" value="forcar_concluido">
            <input type="hidden" name="uid" id="modal-uid">
            <div class="mb-4">
                <label class="block text-xs font-semibold text-slate-400 mb-1.5">Título</label>
                <input type="text" id="modal-titulo" readonly class="w-full bg-slate-900 border border-slate-700 rounded-xl px-4 py-2.5 text-sm text-slate-300">
            </div>
            <div class="mb-5">
                <label class="block text-xs font-semibold text-slate-400 mb-1.5">URL do Áudio (MP3)</label>
                <input type="url" name="audio_url" required placeholder="https://cdn.exemplo.com/musica.mp3"
                    class="w-full bg-slate-900 border border-slate-600 rounded-xl px-4 py-2.5 text-sm text-white focus:border-yellow-500/50 focus:outline-none">
            </div>
            <div class="flex gap-3">
                <button type="button" onclick="fecharModal()" class="flex-1 py-3 rounded-xl border border-slate-600 text-slate-200 font-semibold hover:bg-slate-700">Cancelar</button>
                <button type="submit" class="flex-1 py-3 rounded-xl text-white font-semibold"
                    style="background:linear-gradient(135deg,#C9A84C,#D4AF37);">Confirmar</button>
            </div>
        </form>
    </div>
</div>

<script>
function toggleSidebar() {
    const sb = document.getElementById('sidebar');
    const ov = document.getElementById('sidebar-overlay');
    sb.classList.toggle('open');
    ov.classList.toggle('hidden');
}
function closeSidebar() {
    document.getElementById('sidebar').classList.remove('open');
    document.getElementById('sidebar-overlay').classList.add('hidden');
}
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
