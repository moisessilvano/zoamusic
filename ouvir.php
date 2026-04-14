<?php
// ============================================================
// LOUVOR.NET - Player Final (Tela 4)
// ============================================================

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/includes/shortlink.php';

$uid = trim($_GET['uid'] ?? '');

if (!preg_match('/^[0-9a-f]{8}-[0-9a-f]{4}-4[0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$/i', $uid)) {
    http_response_code(404); die('Página não encontrada.');
}

$stmt = db()->prepare('SELECT * FROM musicas WHERE id = ?');
$stmt->execute([$uid]);
$musica = $stmt->fetch();

if (!$musica || $musica['status'] !== 'concluido') {
    if ($musica && $musica['status'] === 'processando') {
        header('Location: processando.php?uid=' . urlencode($uid)); exit;
    }
    http_response_code(404); die('Música não encontrada.');
}

function formatar_letra(string $letra): string {
    $html = '';
    // Divide por parágrafos
    $blocos = preg_split('/\n{2,}/', trim($letra));
    
    foreach ($blocos as $bloco) {
        $bloco = trim($bloco);
        if (!$bloco) continue;

        // Verifica se começa com uma tag tipo [Verse], [Chorus] etc
        if (preg_match('/^\[(.*?)\]/i', $bloco, $matches)) {
            $secao = $matches[1];
            // Remove a tag do conteúdo
            $conteudo = trim(preg_replace('/^\[.*?\]/i', '', $bloco));
            
            // Tradução simples para o usuário final
            $map = [
                'Verse' => 'Estrofe',
                'Chorus' => 'Refrão',
                'Bridge' => 'Ponte',
                'Intro' => 'Introdução',
                'Outro' => 'Finalização'
            ];
            $secao_exibicao = $map[ucfirst(strtolower($secao))] ?? $secao;

            $html .= '<div class="mb-8">';
            $html .= '<p class="text-xs font-bold tracking-widest uppercase mb-3" style="color:#C9A84C">' . htmlspecialchars($secao_exibicao) . '</p>';
            if ($conteudo) {
                $html .= '<p class="leading-relaxed text-lg font-display italic" style="color:#44403C">' . nl2br(htmlspecialchars($conteudo)) . '</p>';
            }
            $html .= '</div>';
        } else {
            $html .= '<p class="leading-relaxed text-lg font-display italic mb-6" style="color:#44403C">'
                   . nl2br(htmlspecialchars($bloco)) . '</p>';
        }
    }
    return $html;
}

$long_url = rtrim(BASE_URL, '/') . '/ouvir.php?uid=' . urlencode($uid);
if (!empty($musica['short_code'])) {
    $share_url = shortlink_url($musica['short_code']);
} else {
    $code = shortlink_criar($long_url, $uid);
    db()->prepare('UPDATE musicas SET short_code = ? WHERE id = ?')->execute([$code, $uid]);
    $share_url = shortlink_url($code);
}

$whatsapp_msg = urlencode('🎵 Ouça a música cristã que a LOUVOR.NET criou pra mim: ' . $share_url);
$titulo_safe  = htmlspecialchars($musica['titulo'] ?? 'Minha Música');
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $titulo_safe ?> — LOUVOR.NET</title>
    <meta property="og:title"       content="<?= $titulo_safe ?> | LOUVOR.NET">
    <meta property="og:description" content="Uma música cristã criada por IA especialmente para mim.">
    <meta property="og:url"         content="<?= htmlspecialchars($share_url) ?>">

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:ital,wght@0,600;0,700;1,400&family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        *, body { font-family: 'Inter', system-ui, sans-serif; }
        .font-display { font-family: 'Cormorant Garamond', Georgia, serif; }

        body {
            background:
                radial-gradient(ellipse 90% 40% at 50% 0%, rgba(201,168,76,0.1) 0%, transparent 60%),
                #FDFBF5;
            color: #1C1917;
        }

        .navbar {
            background: rgba(255,255,255,0.9);
            backdrop-filter: blur(20px);
            border-bottom: 1px solid rgba(201,168,76,0.15);
        }

        .gold-text {
            background: linear-gradient(135deg,#B8922A,#D4AF37,#E8CC80);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }

        .btn-gold {
            background: linear-gradient(135deg,#C9A84C,#D4AF37,#B8922A);
            box-shadow: 0 4px 20px rgba(201,168,76,0.3);
            transition: transform 0.2s, box-shadow 0.2s;
        }
        .btn-gold:hover { transform:translateY(-2px); box-shadow:0 8px 28px rgba(201,168,76,0.4); }

        .card { background:#fff; border:1px solid rgba(201,168,76,0.18); box-shadow:0 4px 24px rgba(0,0,0,0.05); }

        /* Player personalizado */
        audio { width:100%; border-radius:12px; }

        /* Barras de visualizador */
        @keyframes vbar { 0%,100%{height:6px} 50%{height:28px} }
        .v-bar { animation: vbar 0.9s ease-in-out infinite; border-radius:3px; width:4px; }
        .v-bar:nth-child(2){animation-delay:.12s}
        .v-bar:nth-child(3){animation-delay:.24s}
        .v-bar:nth-child(4){animation-delay:.18s}
        .v-bar:nth-child(5){animation-delay:.06s}
    </style>
</head>
<body class="min-h-screen">

<!-- NAVBAR -->
<nav class="navbar fixed top-0 left-0 right-0 z-50 px-6 py-4 flex items-center justify-between">
    <a href="/" class="flex items-center gap-2.5">
        <svg class="w-8 h-8" viewBox="0 0 40 40" fill="none">
            <circle cx="20" cy="20" r="19" stroke="#C9A84C" stroke-width="1.2" fill="rgba(201,168,76,0.07)"/>
            <path d="M20 9 L22.5 16.5 L30.5 16.5 L24 21.5 L26.5 29 L20 24 L13.5 29 L16 21.5 L9.5 16.5 L17.5 16.5 Z" fill="#C9A84C"/>
        </svg>
        <span class="text-xl font-bold tracking-widest" style="color:#1C1917; letter-spacing:.12em">
            LOUVOR<span style="color:#C9A84C">.NET</span>
        </span>
    </a>
    <span class="hidden md:block font-display italic text-sm" style="color:#B8A07A;">"Cantai ao Senhor um cântico novo"</span>
</nav>

<!-- HERO DA MÚSICA -->
<section class="pt-32 pb-16 px-6 text-center relative overflow-hidden">
    <div class="absolute inset-0" style="background:radial-gradient(ellipse 70% 60% at 50% 0%,rgba(201,168,76,0.1) 0%,transparent 70%); pointer-events:none;"></div>
    <div class="relative z-10 max-w-2xl mx-auto">
        <p class="text-xs font-bold tracking-widest uppercase mb-3" style="color:#C9A84C;">✦ Composição Exclusiva</p>
        <h1 class="font-display text-5xl md:text-6xl font-bold mb-4" style="color:#1C1917;">
            <?= $titulo_safe ?>
        </h1>
        <p class="text-sm" style="color:#8B7355;">
            Criado em <?= date('d/m/Y', strtotime($musica['created_at'])) ?> · Música única por LOUVOR.NET
        </p>
    </div>
</section>

<div class="max-w-2xl mx-auto px-6 pb-16">

    <!-- PLAYER -->
    <div class="card rounded-3xl p-6 mb-8">
        <div class="flex items-center gap-4 mb-5">
            <div class="flex items-end gap-1 h-8 flex-shrink-0" id="visualizer">
                <?php for($i=0;$i<5;$i++): ?>
                <div class="v-bar" style="background:linear-gradient(to top,#C9A84C,#E8CC80); height:6px;"></div>
                <?php endfor; ?>
            </div>
            <div class="flex-1 min-w-0">
                <p class="font-semibold truncate" style="color:#1C1917;"><?= $titulo_safe ?></p>
                <p class="text-xs" style="color:#8B7355;">LOUVOR.NET · Composição por IA</p>
            </div>
            <span class="text-2xl">🎵</span>
        </div>
        <audio id="audio-player" controls preload="metadata">
            <source src="<?= htmlspecialchars($musica['audio_url']) ?>" type="audio/mpeg">
        </audio>
    </div>

    <!-- AÇÕES -->
    <div class="grid grid-cols-2 gap-4 mb-8">
        <?php 
            $filename = preg_replace('/[^a-z0-9]+/', '-', strtolower($musica['titulo'] ?? 'musica')) . '-louvor-net.mp3';
        ?>
        <a href="<?= htmlspecialchars($musica['audio_url']) ?>"
           download="<?= $filename ?>"
           class="flex items-center justify-center gap-2 py-4 rounded-2xl font-semibold text-sm transition-all"
           style="background:#fff; border:1.5px solid #E8D9A8; color:#44403C;">
            ⬇ Baixar MP3
        </a>
        <a href="https://api.whatsapp.com/send?text=<?= $whatsapp_msg ?>" target="_blank"
           class="flex items-center justify-center gap-2 py-4 rounded-2xl font-semibold text-sm text-white transition-all"
           style="background:#25d366;">
            <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24">
                <path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347z"/>
                <path d="M12 0C5.373 0 0 5.373 0 12c0 2.125.555 4.118 1.528 5.843L0 24l6.335-1.652A11.954 11.954 0 0 0 12 24c6.627 0 12-5.373 12-12S18.627 0 12 0zm0 21.818a9.818 9.818 0 0 1-5.006-1.372l-.36-.213-3.727.977.992-3.63-.234-.373A9.818 9.818 0 1 1 12 21.818z"/>
            </svg>
            Compartilhar
        </a>
    </div>

    <!-- LETRA -->
    <div class="card rounded-3xl p-8 mb-8">
        <div class="flex items-center gap-3 mb-6 pb-4" style="border-bottom:1px solid #F0E8CC;">
            <span class="text-2xl">📜</span>
            <h2 class="font-semibold text-lg" style="color:#1C1917;">Letra da Música</h2>
        </div>
        <?= formatar_letra($musica['letra'] ?? '') ?>
    </div>

    <!-- COMPARTILHAR LINK -->
    <div class="rounded-2xl p-5 mb-10" style="background:rgba(201,168,76,0.08); border:1px solid rgba(201,168,76,0.25);">
        <p class="text-xs font-bold mb-2" style="color:#C9A84C;">🔗 Link para compartilhar</p>
        <div class="flex gap-2">
            <input type="text" id="share-url" readonly value="<?= htmlspecialchars($share_url) ?>"
                class="flex-1 rounded-xl px-4 py-2.5 text-sm outline-none"
                style="background:#fff; border:1px solid #E8D9A8; color:#44403C;">
            <button onclick="copiarLink(event)"
                class="px-5 py-2.5 rounded-xl text-sm font-semibold text-white btn-gold">
                Copiar
            </button>
        </div>
    </div>

    <!-- CTA nova música -->
    <div class="text-center">
        <p class="text-sm mb-4" style="color:#8B7355;">Gostou? Crie uma nova música para alguém especial!</p>
        <a href="/" class="btn-gold inline-block px-8 py-4 rounded-2xl text-white font-bold text-lg">
            🎵 Criar Nova Música
        </a>
    </div>
</div>

<!-- FOOTER -->
<footer class="px-6 py-8 text-center" style="background:#fff; border-top:1px solid rgba(201,168,76,0.12);">
    <p class="font-display italic text-sm mb-1" style="color:#B8A07A;">"Tudo que tem fôlego louve ao Senhor." — Salmos 150:6</p>
    <p class="text-xs" style="color:#C8B99A;">© <?= date('Y') ?> LOUVOR.NET</p>
</footer>

<script>
const audio = document.getElementById('audio-player');
const bars  = document.querySelectorAll('.v-bar');

function pauseViz() { bars.forEach(b => b.style.animationPlayState = 'paused'); }
function playViz()  { bars.forEach(b => b.style.animationPlayState = 'running'); }

pauseViz();
audio.addEventListener('play',  playViz);
audio.addEventListener('pause', pauseViz);
audio.addEventListener('ended', pauseViz);

function copiarLink(e) {
    navigator.clipboard.writeText(document.getElementById('share-url').value).then(() => {
        const btn = e.target;
        const orig = btn.textContent;
        btn.textContent = '✓ Copiado!';
        setTimeout(() => btn.textContent = orig, 2500);
    });
// Salva esta música no histórico de músicas do dispositivo
try {
    const uid   = <?= json_encode($uid) ?>;
    const titulo = <?= json_encode($musica['titulo'] ?? 'Minha Música') ?>;
    const data   = new Date().toLocaleDateString('pt-BR', { day:'2-digit', month:'long', year:'numeric' });
    let hist = JSON.parse(localStorage.getItem('louvor_historico') || '[]');
    hist = hist.filter(m => m.uid !== uid); // remove duplicata
    hist.push({ uid, titulo, data });
    if (hist.length > 20) hist = hist.slice(-20); // máx 20
    localStorage.setItem('louvor_historico', JSON.stringify(hist));
} catch(e) {}

// Para e esconde o mini-player (usuário ouve a própria música aqui)
try {
    const mp = document.getElementById('mp-audio');
    if (mp) { mp.pause(); mp.src = ''; }
    const mpEl = document.getElementById('mini-player');
    if (mpEl) mpEl.style.display = 'none';
    sessionStorage.removeItem('louvor_mini_player');
} catch(e) {}
</script>
</body>
</html>
