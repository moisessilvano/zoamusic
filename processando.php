<?php
// ============================================================
// LOUVOR.NET - Tela de Processamento + Disparo Assíncrono (Tela 3)
// ============================================================

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/db.php';

$uid = trim($_GET['uid'] ?? '');

if (!preg_match('/^[0-9a-f]{8}-[0-9a-f]{4}-4[0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$/i', $uid)) {
    http_response_code(404);
    die('Sessão inválida.');
}

$stmt = db()->prepare('SELECT * FROM musicas WHERE id = ?');
$stmt->execute([$uid]);
$musica = $stmt->fetch();

if (!$musica) {
    http_response_code(404);
    die('Música não encontrada.');
}

// Se já concluído, vai direto para o player
if ($musica['status'] === 'concluido') {
    header('Location: ouvir.php?uid=' . urlencode($uid));
    exit;
}

// Dispara a geração em background (fire & forget) se ainda não foi iniciada
// Verifica se há letra ou task_id gerados
if ($musica['status'] === 'processando' && empty($musica['task_id']) && empty($musica['letra'])) {
    // Dispara o worker de geração de forma não-bloqueante
    $worker_url = BASE_URL . '/api/gerar_musica.php?uid=' . urlencode($uid) . '&secret=' . urlencode(hash_hmac('sha256', $uid, ASAAS_API_KEY));
    $ch = curl_init($worker_url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => false,
        CURLOPT_TIMEOUT_MS     => 500, // Apenas dispara, não espera resposta
        CURLOPT_NOSIGNAL       => true,
    ]);
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
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        body { background: linear-gradient(135deg, #1E293B 0%, #2D3F55 100%); min-height: 100vh; }
        .gold-text { color: #D4AF37; }
        .btn-gold { background-color: #D4AF37; }

        /* Animação do logo girando */
        @keyframes spin-slow { from { transform: rotate(0deg); } to { transform: rotate(360deg); } }
        .spin-slow { animation: spin-slow 4s linear infinite; }

        /* Ondas de som */
        @keyframes wave {
            0%, 100% { transform: scaleY(0.4); }
            50% { transform: scaleY(1); }
        }
        .wave-bar { animation: wave 1.2s ease-in-out infinite; }
        .wave-bar:nth-child(1) { animation-delay: 0s; }
        .wave-bar:nth-child(2) { animation-delay: 0.1s; }
        .wave-bar:nth-child(3) { animation-delay: 0.2s; }
        .wave-bar:nth-child(4) { animation-delay: 0.3s; }
        .wave-bar:nth-child(5) { animation-delay: 0.2s; }
        .wave-bar:nth-child(6) { animation-delay: 0.1s; }
        .wave-bar:nth-child(7) { animation-delay: 0s; }

        /* Step indicator */
        @keyframes fadeInStep { from { opacity:0; transform:translateX(-10px); } to { opacity:1; transform:translateX(0); } }
        .step-item { animation: fadeInStep 0.5s ease forwards; opacity: 0; }
    </style>
</head>
<body class="flex items-center justify-center px-6 py-12">

    <div class="max-w-lg w-full text-center">
        <!-- LOGO -->
        <div class="flex items-center justify-center gap-3 mb-12">
            <div class="spin-slow">
                <svg class="w-12 h-12" viewBox="0 0 32 32" fill="none">
                    <circle cx="16" cy="16" r="15" stroke="#D4AF37" stroke-width="2"/>
                    <path d="M16 8 L18 13 L23 13 L19 16 L21 21 L16 18 L11 21 L13 16 L9 13 L14 13 Z" fill="#D4AF37"/>
                </svg>
            </div>
            <span class="text-3xl font-bold tracking-widest text-white">LOUVOR<span class="gold-text">.NET</span></span>
        </div>

        <!-- TÍTULO -->
        <h1 class="text-4xl font-bold text-white mb-3">
            Compondo sua <span class="gold-text">melodia</span>...
        </h1>
        <p class="text-gray-300 mb-10 text-lg">
            Nossa IA está transformando sua história em adoração.
        </p>

        <!-- ONDAS DE SOM -->
        <div class="flex items-end justify-center gap-1.5 mb-12 h-16">
            <?php for ($i = 0; $i < 7; $i++): ?>
            <div class="wave-bar w-3 bg-[#D4AF37] rounded-full h-full opacity-80"></div>
            <?php endfor; ?>
        </div>

        <!-- ETAPAS -->
        <div class="bg-white/10 backdrop-blur-sm rounded-2xl p-6 mb-8 text-left" id="steps-container">
            <p class="text-white font-semibold mb-4 text-center">O que está acontecendo:</p>
            <div class="space-y-3" id="steps-list">
                <div class="step-item flex items-center gap-3" style="animation-delay:0.2s">
                    <div class="w-6 h-6 rounded-full flex items-center justify-center flex-shrink-0 bg-green-500">
                        <span class="text-white text-xs">✓</span>
                    </div>
                    <span class="text-gray-200 text-sm">Pagamento confirmado</span>
                </div>
                <div class="step-item flex items-center gap-3" style="animation-delay:0.8s" id="step-claude">
                    <div class="w-6 h-6 rounded-full flex items-center justify-center flex-shrink-0 border-2 border-[#D4AF37]" id="step-claude-icon">
                        <div class="w-2 h-2 rounded-full bg-[#D4AF37] animate-ping"></div>
                    </div>
                    <span class="text-gray-200 text-sm">Claude (IA) escrevendo a letra da sua música...</span>
                </div>
                <div class="step-item flex items-center gap-3" style="animation-delay:1.4s" id="step-suno">
                    <div class="w-6 h-6 rounded-full flex items-center justify-center flex-shrink-0 border-2 border-gray-600" id="step-suno-icon">
                    </div>
                    <span class="text-gray-400 text-sm" id="step-suno-text">Suno (IA) compondo a melodia...</span>
                </div>
                <div class="step-item flex items-center gap-3" style="animation-delay:2s" id="step-final">
                    <div class="w-6 h-6 rounded-full flex items-center justify-center flex-shrink-0 border-2 border-gray-600" id="step-final-icon">
                    </div>
                    <span class="text-gray-400 text-sm">Preparando seu player exclusivo</span>
                </div>
            </div>
        </div>

        <!-- VERSÍCULO -->
        <div class="bg-white/5 rounded-2xl p-5 mb-8">
            <p class="text-gray-300 italic text-sm" id="verse-text">"Crie em mim, ó Deus, um coração puro..." — Salmos 51:10</p>
        </div>

        <!-- STATUS -->
        <p class="text-gray-400 text-sm" id="status-msg">Verificando o status a cada 5 segundos...</p>
    </div>

    <script>
    const uid = <?= json_encode($uid) ?>;
    const checkUrl = 'api/check_status.php?uid=' + encodeURIComponent(uid);

    const verses = [
        '"Crie em mim, ó Deus, um coração puro..." — Salmos 51:10',
        '"Cantai ao Senhor um cântico novo..." — Salmos 96:1',
        '"Louvai ao Senhor com harpa..." — Salmos 33:2',
        '"Tudo que tem fôlego louve ao Senhor." — Salmos 150:6',
        '"Alegrai-vos no Senhor sempre." — Filipenses 4:4',
    ];
    let verseIndex = 0;
    setInterval(() => {
        verseIndex = (verseIndex + 1) % verses.length;
        document.getElementById('verse-text').textContent = verses[verseIndex];
    }, 6000);

    let pollCount = 0;
    function updateStep(id, done, active) {
        const icon = document.getElementById(id + '-icon');
        if (!icon) return;
        if (done) {
            icon.className = 'w-6 h-6 rounded-full flex items-center justify-center flex-shrink-0 bg-green-500';
            icon.innerHTML = '<span class="text-white text-xs">✓</span>';
        } else if (active) {
            icon.className = 'w-6 h-6 rounded-full flex items-center justify-center flex-shrink-0 border-2 border-[#D4AF37]';
            icon.innerHTML = '<div class="w-2 h-2 rounded-full bg-[#D4AF37] animate-ping"></div>';
        }
    }

    async function checkStatus() {
        pollCount++;
        document.getElementById('status-msg').textContent = 'Verificando... (tentativa ' + pollCount + ')';
        try {
            const res = await fetch(checkUrl);
            const data = await res.json();

            if (data.status === 'concluido' && data.audio_url) {
                updateStep('step-claude', true, false);
                updateStep('step-suno', true, false);
                updateStep('step-final', true, false);
                document.getElementById('status-msg').textContent = '✨ Música pronta! Redirecionando...';
                setTimeout(() => { window.location = 'ouvir.php?uid=' + encodeURIComponent(uid); }, 1500);
                return;
            }

            if (data.status === 'erro') {
                document.getElementById('status-msg').textContent = '❌ Ocorreu um erro. Entre em contato.';
                return;
            }

            // Atualiza steps baseado no que está disponível
            if (data.has_letra) {
                updateStep('step-claude', true, false);
                updateStep('step-suno', false, true);
                document.getElementById('step-suno-text') && (document.getElementById('step-suno-text').className = 'text-gray-200 text-sm');
            }

            setTimeout(checkStatus, 5000);
        } catch (e) {
            document.getElementById('status-msg').textContent = 'Aguardando resposta...';
            setTimeout(checkStatus, 5000);
        }
    }

    // Inicia polling após 3 segundos
    setTimeout(checkStatus, 3000);
    </script>
</body>
</html>
