<?php
// ============================================================
// LOUVOR.NET - Tela de Processamento (Tela 3)
// ============================================================

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/db.php';

$uid = trim($_GET['uid'] ?? '');

if (!preg_match('/^[0-9a-f]{8}-[0-9a-f]{4}-4[0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$/i', $uid)) {
    http_response_code(404); die('Sessão inválida.');
}

$stmt = db()->prepare('SELECT * FROM musicas WHERE id = ?');
$stmt->execute([$uid]);
$musica = $stmt->fetch();

if (!$musica) { http_response_code(404); die('Música não encontrada.'); }

if ($musica['status'] === 'concluido') {
    header('Location: ouvir.php?uid=' . urlencode($uid)); exit;
}

if ($musica['status'] === 'processando' && empty($musica['task_id']) && empty($musica['letra'])) {
    $worker_url = BASE_URL . '/api/gerar_musica.php?uid=' . urlencode($uid) . '&secret=' . urlencode(hash_hmac('sha256', $uid, ASAAS_API_KEY));
    $ch = curl_init($worker_url);
    curl_setopt_array($ch, [CURLOPT_RETURNTRANSFER=>false, CURLOPT_TIMEOUT_MS=>500, CURLOPT_NOSIGNAL=>true]);
    curl_exec($ch);
    curl_close($ch);
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>LOUVOR.NET — Compondo sua música...</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:ital,wght@0,600;0,700;1,400&family=Inter:wght@400;500;600&display=swap" rel="stylesheet">
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        *, body { font-family: 'Inter', system-ui, sans-serif; }
        .font-display { font-family: 'Cormorant Garamond', Georgia, serif; }

        body {
            background:
                radial-gradient(ellipse 80% 50% at 50% 0%, rgba(201,168,76,0.12) 0%, transparent 60%),
                radial-gradient(ellipse 60% 40% at 80% 60%, rgba(174,210,255,0.1) 0%, transparent 50%),
                #FDFBF5;
            min-height: 100vh;
        }

        .navbar {
            background: rgba(255,255,255,0.88);
            backdrop-filter: blur(20px);
            border-bottom: 1px solid rgba(201,168,76,0.15);
        }

        /* Barras de onda */
        @keyframes wave {
            0%, 100% { transform: scaleY(0.3); }
            50%       { transform: scaleY(1); }
        }
        .wave-bar { animation: wave 1.1s ease-in-out infinite; transform-origin: bottom; }
        .wave-bar:nth-child(1) { animation-delay: 0s; }
        .wave-bar:nth-child(2) { animation-delay: 0.12s; }
        .wave-bar:nth-child(3) { animation-delay: 0.24s; }
        .wave-bar:nth-child(4) { animation-delay: 0.18s; }
        .wave-bar:nth-child(5) { animation-delay: 0.06s; }
        .wave-bar:nth-child(6) { animation-delay: 0.14s; }
        .wave-bar:nth-child(7) { animation-delay: 0.02s; }

        /* Rotação suave do logo */
        @keyframes spin-gentle { from{transform:rotate(0deg)} to{transform:rotate(360deg)} }
        .spin-logo { animation: spin-gentle 6s linear infinite; }

        /* Steps animados */
        @keyframes slideIn { from{opacity:0;transform:translateX(-8px)} to{opacity:1;transform:translateX(0)} }
        .step-anim { animation: slideIn 0.4s ease forwards; opacity: 0; }

        .card { background:#fff; border:1px solid rgba(201,168,76,0.18); box-shadow:0 4px 24px rgba(0,0,0,0.05); }
    </style>
</head>
<body class="flex flex-col min-h-screen">

<!-- NAVBAR -->
<nav class="navbar px-6 py-4 flex items-center gap-3">
    <svg class="w-8 h-8 spin-logo" viewBox="0 0 40 40" fill="none">
        <circle cx="20" cy="20" r="19" stroke="#C9A84C" stroke-width="1.2" fill="rgba(201,168,76,0.07)"/>
        <path d="M20 9 L22.5 16.5 L30.5 16.5 L24 21.5 L26.5 29 L20 24 L13.5 29 L16 21.5 L9.5 16.5 L17.5 16.5 Z" fill="#C9A84C"/>
    </svg>
    <a href="/" class="text-xl font-bold tracking-widest" style="color:#1C1917; letter-spacing:.12em">
        LOUVOR<span style="color:#C9A84C">.NET</span>
    </a>
</nav>

<!-- CONTEÚDO -->
<div class="flex-1 flex items-center justify-center px-6 py-16">
    <div class="max-w-lg w-full text-center">

        <!-- Título -->
        <h1 class="font-display text-5xl md:text-6xl font-bold mb-3" style="color:#1C1917;">
            Compondo sua
            <span style="background:linear-gradient(135deg,#B8922A,#D4AF37,#E8CC80);-webkit-background-clip:text;-webkit-text-fill-color:transparent;background-clip:text;">
                melodia...
            </span>
        </h1>
        <p class="text-base mb-12" style="color:#6B5B3E;">
            Nossa IA está transformando sua história em adoração.
        </p>

        <!-- Barras de onda -->
        <div class="flex items-end justify-center gap-2 mb-12" style="height:64px;">
            <?php for ($i=0;$i<7;$i++): ?>
            <div class="wave-bar w-3 rounded-full" style="background:linear-gradient(to top,#C9A84C,#E8CC80); height:100%;"></div>
            <?php endfor; ?>
        </div>

        <!-- Card de etapas -->
        <div class="card rounded-2xl p-7 mb-8 text-left">
            <p class="font-semibold text-sm mb-5 text-center" style="color:#1C1917;">O que está acontecendo:</p>
            <div class="space-y-4">
                <div class="step-anim flex items-center gap-3" style="animation-delay:0.2s">
                    <div class="w-7 h-7 rounded-full flex items-center justify-center flex-shrink-0 text-white text-xs font-bold"
                         style="background:#22c55e;">✓</div>
                    <span class="text-sm" style="color:#44403C;">Pagamento confirmado</span>
                </div>
                <div class="step-anim flex items-center gap-3" style="animation-delay:0.7s" id="step-claude">
                    <div class="w-7 h-7 rounded-full flex items-center justify-center flex-shrink-0 border-2" style="border-color:#C9A84C;" id="step-claude-icon">
                        <div class="w-2 h-2 rounded-full animate-ping" style="background:#C9A84C;"></div>
                    </div>
                    <span class="text-sm" style="color:#44403C;">IA escrevendo a letra da sua música...</span>
                </div>
                <div class="step-anim flex items-center gap-3" style="animation-delay:1.2s" id="step-suno">
                    <div class="w-7 h-7 rounded-full flex items-center justify-center flex-shrink-0 border-2" style="border-color:#E8D9A8;" id="step-suno-icon"></div>
                    <span class="text-sm" style="color:#B8A07A;" id="step-suno-text">IA compondo a melodia com voz...</span>
                </div>
                <div class="step-anim flex items-center gap-3" style="animation-delay:1.7s" id="step-final">
                    <div class="w-7 h-7 rounded-full flex items-center justify-center flex-shrink-0 border-2" style="border-color:#E8D9A8;" id="step-final-icon"></div>
                    <span class="text-sm" style="color:#B8A07A;">Preparando seu player exclusivo</span>
                </div>
            </div>
        </div>

        <!-- Versículo rotativo -->
        <div class="rounded-2xl px-6 py-4 mb-6" style="background:rgba(201,168,76,0.07); border:1px solid rgba(201,168,76,0.18);">
            <p class="font-display italic text-base" style="color:#8B6914;" id="verse-text">
                "Crie em mim, ó Deus, um coração puro..." — Salmos 51:10
            </p>
        </div>

        <p class="text-xs" style="color:#B8A07A;" id="status-msg">Verificando a cada 5 segundos...</p>
    </div>
</div>

<script>
const uid = <?= json_encode($uid) ?>;
const verses = [
    '"Crie em mim, ó Deus, um coração puro..." — Salmos 51:10',
    '"Cantai ao Senhor um cântico novo..." — Salmos 96:1',
    '"Louvai ao Senhor com harpa..." — Salmos 33:2',
    '"Tudo que tem fôlego louve ao Senhor." — Salmos 150:6',
    '"Alegrai-vos no Senhor sempre." — Filipenses 4:4',
];
let vi = 0;
setInterval(() => {
    vi = (vi + 1) % verses.length;
    document.getElementById('verse-text').textContent = verses[vi];
}, 6000);

function markDone(id) {
    const icon = document.getElementById(id + '-icon');
    if (!icon) return;
    icon.style.borderColor = '#22c55e';
    icon.style.background = '#22c55e';
    icon.innerHTML = '<span style="color:#fff;font-size:11px;font-weight:bold;">✓</span>';
}
function markActive(id) {
    const icon = document.getElementById(id + '-icon');
    if (!icon) return;
    icon.style.borderColor = '#C9A84C';
    icon.innerHTML = '<div style="width:8px;height:8px;border-radius:50%;background:#C9A84C;" class="animate-ping"></div>';
}

let pollCount = 0;
async function checkStatus() {
    pollCount++;
    document.getElementById('status-msg').textContent = 'Verificando... (tentativa ' + pollCount + ')';
    try {
        const res  = await fetch('api/check_status.php?uid=' + encodeURIComponent(uid));
        const data = await res.json();

        if (data.status === 'concluido' && data.audio_url) {
            markDone('step-claude'); markDone('step-suno'); markDone('step-final');
            document.getElementById('status-msg').textContent = '✨ Música pronta! Redirecionando...';
            setTimeout(() => { window.location = 'ouvir.php?uid=' + encodeURIComponent(uid); }, 1500);
            return;
        }
        if (data.status === 'erro') {
            document.getElementById('status-msg').textContent = '❌ Ocorreu um erro. Entre em contato.';
            return;
        }
        if (data.has_letra) {
            markDone('step-claude');
            markActive('step-suno');
            const t = document.getElementById('step-suno-text');
            if (t) t.style.color = '#44403C';
        }
        setTimeout(checkStatus, 5000);
    } catch(e) {
        setTimeout(checkStatus, 5000);
    }
}
setTimeout(checkStatus, 3000);
</script>
</body>
</html>
