<?php
// ============================================================
// ZOA MUSIC - Tela de Processamento (Tela 3)
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
    // O disparo agora é feito de forma assíncrona pelo Javascript da tela (frontend).
    // Isso evita o travamento de single-threads no (php -S) e não usa exec() que cPanel bloqueia.
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ZOA MUSIC — Compondo sua zoeira...</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:ital,wght@0,600;0,700;1,400&family=Inter:wght@400;500;600&display=swap" rel="stylesheet">
    <link rel="icon" type="image/svg+xml" href="assets/logo.svg">
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        *, body { font-family: 'Inter', system-ui, sans-serif; }
        .font-display { font-family: 'Cormorant Garamond', Georgia, serif; }

        body {
            background:
                radial-gradient(ellipse 70% 40% at 50% 0%, rgba(255,45,120,0.12) 0%, transparent 60%),
                radial-gradient(ellipse 50% 30% at 80% 60%, rgba(191,90,242,0.08) 0%, transparent 50%),
                #080808;
            min-height: 100vh;
            color: #F0F0F0;
        }

        .navbar {
            background: rgba(8,8,8,0.9);
            backdrop-filter: blur(20px);
            border-bottom: 1px solid rgba(255,255,255,0.08);
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

        .card { background:#141414; border:1px solid rgba(255,255,255,0.08); box-shadow:0 4px 24px rgba(0,0,0,0.3); }
    </style>
    <?php require_once __DIR__ . '/includes/gtag.php'; ?>
</head>
<body class="flex flex-col min-h-screen">

<!-- NAVBAR -->
<nav class="navbar px-6 py-4 flex items-center gap-3">
    <img src="assets/logo.svg" alt="ZOA MUSIC" class="w-8 h-8 rounded-full spin-logo">
    <a href="/" class="font-bold text-xl tracking-tight">
        ZOA<span style="color:#FF2D78"> MUSIC</span>
    </a>
</nav>

<!-- CONTEÚDO -->
<div class="flex-1 flex items-center justify-center px-6 py-16">
    <div class="max-w-lg w-full text-center">

        <!-- Título -->
        <h1 class="font-display text-5xl md:text-6xl font-bold mb-3" style="color:#F0F0F0;">
            Compondo a
            <span style="background:linear-gradient(135deg,#FF2D78,#FF6B9D,#FFD60A);-webkit-background-clip:text;-webkit-text-fill-color:transparent;background-clip:text;">
                zoeira... 😈
            </span>
        </h1>
        <p class="text-base mb-12" style="color:#888;">
            Nossa IA está escrevendo a letra e compondo a melodia agora mesmo.
        </p>

        <!-- Barras de onda -->
        <div class="flex items-end justify-center gap-2 mb-12" style="height:64px;">
            <?php for ($i=0;$i<7;$i++): ?>
            <div class="wave-bar w-3 rounded-full" style="background:linear-gradient(to top,#FF2D78,#BF5AF2); height:100%;"></div>
            <?php endfor; ?>
        </div>

        <!-- Card de etapas -->
        <div class="card rounded-2xl p-7 mb-8 text-left">
            <p class="font-semibold text-sm mb-5 text-center" style="color:#1C1917;">O que está acontecendo:</p>
            
            <!-- Barra de Progresso Nova -->
            <div class="w-full h-2 rounded-full mb-8 overflow-hidden" style="background:#1E1E1E;">
                <div id="progress-bar" class="h-full transition-all duration-1000 ease-out" style="width:5%; background:linear-gradient(to right,#FF2D78,#BF5AF2);"></div>
            </div>

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
                    <span class="text-sm" style="color:#44403C;">Escrevendo a letra da sua música...</span>
                </div>
                <div class="step-anim flex items-center gap-3" style="animation-delay:1.2s" id="step-suno">
                    <div class="w-7 h-7 rounded-full flex items-center justify-center flex-shrink-0 border-2" style="border-color:#E8D9A8;" id="step-suno-icon"></div>
                    <span class="text-sm" style="color:#B8A07A;" id="step-suno-text">Compondo a melodia com voz...</span>
                </div>
                <div class="step-anim flex items-center gap-3" style="animation-delay:1.7s" id="step-final">
                    <div class="w-7 h-7 rounded-full flex items-center justify-center flex-shrink-0 border-2" style="border-color:#E8D9A8;" id="step-final-icon"></div>
                    <span class="text-sm" style="color:#B8A07A;">Preparando seu player exclusivo</span>
                </div>
            </div>
        </div>

        <p class="text-xs mb-4" style="color:#8B7355; opacity: 0.8;" id="waiting-hint">
            ⚠️ O processo leva de 2 a 4 minutos. Por favor, não feche esta página.
        </p>

        <!-- Mensagem rotativa -->
        <div class="rounded-2xl px-6 py-4 mb-6" style="background:rgba(255,45,120,0.07); border:1px solid rgba(255,45,120,0.18);">
            <p class="italic text-base" style="color:#FF6B9D;" id="verse-text">
                "A vingança mais criativa tem melodia..." 😂
            </p>
        </div>

    </div>
</div>

<script>
const uid = <?= json_encode($uid) ?>;
const secret = <?= json_encode(hash_hmac('sha256', $uid, ASAAS_API_KEY)) ?>;
const needsTrigger = <?= ($musica['status'] === 'processando' && empty($musica['task_id']) && empty($musica['letra'])) ? 'true' : 'false' ?>;

// Se o worker ainda não foi disparado, o JS dispara agora
if (needsTrigger) {
    fetch(`api/gerar_musica.php?uid=${encodeURIComponent(uid)}&secret=${encodeURIComponent(secret)}`, { keepalive: true }).catch(() => {});
}

const verses = [
    '"A vingança mais criativa tem melodia..." 😂',
    '"Seu amigo vai ouvir isso e nunca mais chegar atrasado." ⏰',
    '"A IA está escolhendo o tom certo pra humilhar com classe." 🎤',
    '"Toda grande zoeira começa com uma boa letra." 🤣',
    '"Alguns erros merecem ser imortalizados em música." 🎵',
    '"Preparando a obra-prima do constrangimento..." 😈',
];
let vi = 0;
setInterval(() => {
    vi = (vi + 1) % verses.length;
    document.getElementById('verse-text').textContent = verses[vi];
}, 6000);

function markDone(id) {
    const icon = document.getElementById(id + '-icon');
    const text = document.getElementById(id + '-text');
    if (!icon) return;
    icon.style.borderColor = '#22c55e';
    icon.style.background  = '#22c55e';
    icon.innerHTML = '<span style="color:#fff;font-size:11px;font-weight:bold;">✓</span>';
    if (text) text.style.color = '#44403C';
}
function markActive(id, label) {
    const icon = document.getElementById(id + '-icon');
    const text = document.getElementById(id + '-text');
    if (!icon) return;
    icon.style.borderColor = '#C9A84C';
    icon.innerHTML = '<div style="width:8px;height:8px;border-radius:50%;background:#C9A84C;" class="animate-ping"></div>';
    if (text) { text.style.color = '#44403C'; if (label) text.textContent = label; }
}

let lastLetra = false;
let lastTask  = false;
let progress  = 5;

// Faz a barra de progresso andar lentamente
const progressInterval = setInterval(() => {
    if (progress < 92) {
        progress += (progress < 50) ? 0.5 : 0.2;
        document.getElementById('progress-bar').style.width = progress + '%';
    }
}, 1000);

const statusMessages = [
    'Compondo a melodia base...',
    'Afinando instrumentos...',
    'Sincronizando vozes e harmonia...',
    'Aplicando efeitos de estúdio...',
    'Finalizando a mixagem...',
    'Preparando seu link exclusivo...',
];
let msgIndex = 0;
const msgInterval = setInterval(() => {
    const sunoText = document.getElementById('step-suno-text');
    if (lastTask && sunoText && msgIndex < statusMessages.length) {
        sunoText.textContent = statusMessages[msgIndex];
        msgIndex++;
    }
}, 15000); // Muda a cada 15 segundos

async function checkStatus() {
    try {
        const res  = await fetch('api/check_status.php?uid=' + encodeURIComponent(uid));
        const data = await res.json();

        // Atualiza indicadores de passo
        if (data.has_letra && !lastLetra) {
            markDone('step-claude');
            lastLetra = true;
            progress = Math.max(progress, 30);
        }
        if (data.has_task && !lastTask) {
            markActive('step-suno', 'Compondo a melodia com voz...');
            lastTask = true;
            progress = Math.max(progress, 45);
        }

        if (data.status === 'concluido') {
            // Dupla validação: se status concluído, garantimos o redirecionamento
            if (data.audio_url) {
                redirectDone(uid);
                return;
            } else {
                console.log("Status concluído mas sem URL, tentando novamente em 2s...");
                setTimeout(checkStatus, 2000);
                return;
            }
        }

        // Se tem task_id mas ainda não concluído: dispara poll do Suno
        if (data.has_task && data.status !== 'concluido') {
            fetch('api/poll_suno.php?uid=' + encodeURIComponent(uid), { keepalive: true }).catch(() => {});
        }

        if (data.status === 'erro') {
            document.getElementById('status-msg').textContent = '❌ Ocorreu um erro. Entre em contato com o suporte.';
            return;
        }

    } catch (e) { console.error("Erro no polling:", e); }

    setTimeout(checkStatus, 5000);
}

function redirectDone(uid) {
    if (window.alreadyRedirected) return;
    window.alreadyRedirected = true;

    clearInterval(progressInterval);
    clearInterval(msgInterval);
    document.getElementById('progress-bar').style.width = '100%';
    markDone('step-claude');
    markDone('step-suno');
    markDone('step-final');
    
    document.getElementById('status-msg').textContent = '🤣 Zoeira pronta! Redirecionando...';
    setTimeout(() => { window.location = 'ouvir.php?uid=' + encodeURIComponent(uid); }, 1500);
}

// Fallback de segurança: Se após 4 minutos não concluiu, mostra botão de check manual
setTimeout(() => {
    const hint = document.getElementById('waiting-hint');
    if (hint) {
        const btn = document.createElement('div');
        btn.style.marginTop = '20px';
        btn.innerHTML = `<button onclick="window.location.reload()" 
            style="background:transparent; border:1px solid #C9A84C; color:#C9A84C; padding:10px 24px; border-radius:30px; font-size:13px; font-weight:600; cursor:pointer; transition:all 0.2s;"
            onmouseover="this.style.background='rgba(201,168,76,0.1)'" onmouseout="this.style.background='transparent'">
            Ainda processando? Clique para atualizar
        </button>`;
        hint.parentNode.insertBefore(btn, hint.nextSibling);
    }
}, 240000);

// Primeira verificação após 3s
setTimeout(checkStatus, 3000);
</script>

<?php require_once __DIR__ . '/includes/mini_player.php'; ?>
</body>
</html>

