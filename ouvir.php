<?php
// ============================================================
// LOUVOR.NET - Player Final (Tela 4)
// ============================================================

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/db.php';

$uid = trim($_GET['uid'] ?? '');

if (!preg_match('/^[0-9a-f]{8}-[0-9a-f]{4}-4[0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$/i', $uid)) {
    http_response_code(404);
    die('Página não encontrada.');
}

$stmt = db()->prepare('SELECT * FROM musicas WHERE id = ?');
$stmt->execute([$uid]);
$musica = $stmt->fetch();

if (!$musica || $musica['status'] !== 'concluido') {
    // Ainda processando
    if ($musica && $musica['status'] === 'processando') {
        header('Location: processando.php?uid=' . urlencode($uid));
        exit;
    }
    http_response_code(404);
    die('Música não encontrada ou ainda não concluída.');
}

// Formata a letra em estrofes para exibição HTML
function formatar_letra(string $letra): string {
    $paragrafos = preg_split('/\n{2,}/', trim($letra));
    $html = '';
    foreach ($paragrafos as $paragrafo) {
        $linhas = nl2br(htmlspecialchars(trim($paragrafo)));
        // Detecta se é um título de seção (Estrofe, Refrão, etc.)
        if (preg_match('/^(Estrofe|Refrão|Bridge|Verso|Pré-Refrão|Coda)/i', trim($paragrafo))) {
            $parts = explode("\n", trim($paragrafo), 2);
            $secao = htmlspecialchars(trim($parts[0]));
            $conteudo = isset($parts[1]) ? nl2br(htmlspecialchars(trim($parts[1]))) : '';
            $html .= '<div class="mb-8">';
            $html .= '<p class="text-xs font-bold tracking-widest text-[#D4AF37] uppercase mb-3">' . $secao . '</p>';
            $html .= '<p class="text-gray-700 leading-relaxed text-lg italic">' . $conteudo . '</p>';
            $html .= '</div>';
        } else {
            $html .= '<p class="text-gray-700 leading-relaxed text-lg italic mb-6">' . $linhas . '</p>';
        }
    }
    return $html;
}

$share_url   = BASE_URL . '/ouvir.php?uid=' . urlencode($uid);
$whatsapp_msg = urlencode("🎵 Ouça a música cristã que a LOUVOR.NET criou para mim: {$share_url}");
$titulo_safe  = htmlspecialchars($musica['titulo'] ?? 'Minha Música');
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $titulo_safe ?> — LOUVOR.NET</title>

    <!-- Open Graph para compartilhamento -->
    <meta property="og:title" content="<?= $titulo_safe ?> | LOUVOR.NET">
    <meta property="og:description" content="Uma música cristã criada especialmente para mim pela LOUVOR.NET.">
    <meta property="og:type" content="music.song">
    <meta property="og:url" content="<?= htmlspecialchars($share_url) ?>">

    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        body { background-color: #FDFDFD; color: #1E293B; }
        .hero-bg { background: linear-gradient(135deg, #1E293B 0%, #2D3F55 100%); }
        .gold-text { color: #D4AF37; }
        .btn-gold { background-color: #D4AF37; }
        .btn-gold:hover { background-color: #B8962E; }

        /* Player customizado */
        #audio-player {
            width: 100%;
            height: 56px;
            border-radius: 16px;
            outline: none;
        }
        #audio-player::-webkit-media-controls-panel {
            background-color: #1E293B;
            border-radius: 16px;
        }

        /* Visualizador decorativo */
        @keyframes bar-dance {
            0%, 100% { height: 8px; }
            50% { height: 32px; }
        }
        .dance-bar { animation: bar-dance 0.8s ease-in-out infinite; }
        .dance-bar:nth-child(2) { animation-delay: 0.1s; }
        .dance-bar:nth-child(3) { animation-delay: 0.2s; }
        .dance-bar:nth-child(4) { animation-delay: 0.15s; }
        .dance-bar:nth-child(5) { animation-delay: 0.05s; }

        @keyframes fadeIn { from { opacity:0; transform:translateY(12px); } to { opacity:1; transform:translateY(0); } }
        .fade-in { animation: fadeIn 0.6s ease forwards; }
    </style>
</head>
<body class="min-h-screen">

    <!-- NAVBAR -->
    <nav class="hero-bg py-4 px-6 flex items-center justify-between shadow-lg">
        <a href="/" class="flex items-center gap-2">
            <svg class="w-7 h-7" viewBox="0 0 32 32" fill="none">
                <circle cx="16" cy="16" r="15" stroke="#D4AF37" stroke-width="2"/>
                <path d="M16 8 L18 13 L23 13 L19 16 L21 21 L16 18 L11 21 L13 16 L9 13 L14 13 Z" fill="#D4AF37"/>
            </svg>
            <span class="text-xl font-bold tracking-widest text-white">LOUVOR<span class="gold-text">.NET</span></span>
        </a>
        <span class="hidden md:block text-sm text-gray-400 italic">"Cantai ao Senhor um cântico novo"</span>
    </nav>

    <!-- HERO DA MÚSICA -->
    <section class="hero-bg py-16 px-6 text-center">
        <div class="max-w-2xl mx-auto fade-in">
            <p class="text-[#D4AF37] font-semibold tracking-widest uppercase text-xs mb-3">
                ✦ Composição Exclusiva ✦
            </p>
            <h1 class="text-4xl md:text-5xl font-bold text-white mb-4">
                <?= $titulo_safe ?>
            </h1>
            <p class="text-gray-300 text-sm">
                Criado em <?= date('d/m/Y', strtotime($musica['created_at'])) ?> · Música única por LOUVOR.NET
            </p>
        </div>
    </section>

    <div class="max-w-2xl mx-auto px-6 py-10">

        <!-- PLAYER DE ÁUDIO -->
        <div class="bg-[#1E293B] rounded-3xl p-6 mb-10 shadow-xl fade-in">
            <div class="flex items-center gap-3 mb-5">
                <!-- Visualizador decorativo -->
                <div class="flex items-end gap-1 h-8 flex-shrink-0" id="visualizer">
                    <?php for ($i = 0; $i < 5; $i++): ?>
                    <div class="dance-bar w-1.5 bg-[#D4AF37] rounded-full" style="height:8px"></div>
                    <?php endfor; ?>
                </div>
                <div class="flex-1">
                    <p class="text-white font-bold"><?= $titulo_safe ?></p>
                    <p class="text-gray-400 text-xs">LOUVOR.NET · Composição IA</p>
                </div>
                <div class="text-[#D4AF37] text-2xl">🎵</div>
            </div>

            <audio id="audio-player" controls preload="metadata">
                <source src="<?= htmlspecialchars($musica['audio_url']) ?>" type="audio/mpeg">
                Seu navegador não suporta o player de áudio.
            </audio>
        </div>

        <!-- BOTÕES DE AÇÃO -->
        <div class="grid grid-cols-2 gap-4 mb-10">
            <a href="<?= htmlspecialchars($musica['audio_url']) ?>"
               download="<?= htmlspecialchars($musica['titulo'] ?? 'louvor') ?>.mp3"
               class="flex items-center justify-center gap-2 py-4 rounded-2xl bg-[#1E293B] text-white font-semibold hover:bg-gray-800 transition-colors shadow-sm">
                ⬇ Baixar MP3
            </a>
            <a href="https://api.whatsapp.com/send?text=<?= $whatsapp_msg ?>"
               target="_blank"
               class="flex items-center justify-center gap-2 py-4 rounded-2xl bg-green-500 text-white font-semibold hover:bg-green-600 transition-colors shadow-sm">
                <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24">
                    <path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347z"/>
                    <path d="M12 0C5.373 0 0 5.373 0 12c0 2.125.555 4.118 1.528 5.843L0 24l6.335-1.652A11.954 11.954 0 0 0 12 24c6.627 0 12-5.373 12-12S18.627 0 12 0zm0 21.818a9.818 9.818 0 0 1-5.006-1.372l-.36-.213-3.727.977.992-3.63-.234-.373A9.818 9.818 0 1 1 12 21.818z"/>
                </svg>
                Compartilhar
            </a>
        </div>

        <!-- LETRA -->
        <div class="bg-white border border-gray-100 rounded-3xl p-8 shadow-sm mb-10">
            <div class="flex items-center gap-3 mb-6 pb-4 border-b border-gray-100">
                <span class="text-2xl">📜</span>
                <h2 class="text-xl font-bold text-dark-text">Letra da Música</h2>
            </div>
            <div class="font-serif">
                <?= formatar_letra($musica['letra'] ?? '') ?>
            </div>
        </div>

        <!-- COMPARTILHAR URL -->
        <div class="bg-[#D4AF37]/10 border border-[#D4AF37]/30 rounded-2xl p-5 mb-8">
            <p class="text-sm font-semibold text-[#D4AF37] mb-2">🔗 Link para compartilhar</p>
            <div class="flex gap-2">
                <input type="text" id="share-url" readonly
                    value="<?= htmlspecialchars($share_url) ?>"
                    class="flex-1 bg-white border border-gray-200 rounded-xl px-4 py-2.5 text-sm text-gray-600 outline-none">
                <button onclick="copiarLink()"
                    class="px-4 py-2.5 btn-gold text-white rounded-xl font-semibold text-sm hover:bg-[#B8962E] transition-colors">
                    Copiar
                </button>
            </div>
        </div>

        <!-- CTA -->
        <div class="text-center">
            <p class="text-gray-500 text-sm mb-4">Gostou? Crie uma nova música para alguém especial!</p>
            <a href="/"
               class="inline-block btn-gold px-8 py-4 rounded-2xl text-white font-bold text-lg hover:bg-[#B8962E] transition-colors shadow-lg">
                🎵 Criar Nova Música
            </a>
        </div>
    </div>

    <!-- FOOTER -->
    <footer class="hero-bg text-gray-400 py-8 px-6 text-center mt-8">
        <p class="text-white font-bold tracking-widest mb-2">LOUVOR<span class="gold-text">.NET</span></p>
        <p class="text-sm">"Tudo que tem fôlego louve ao Senhor." — Salmos 150:6</p>
        <p class="text-xs mt-4 text-gray-600">© <?= date('Y') ?> LOUVOR.NET. Todos os direitos reservados.</p>
    </footer>

    <script>
        // Controla animação do visualizador baseado no estado do player
        const audio = document.getElementById('audio-player');
        const bars = document.querySelectorAll('.dance-bar');

        function pauseVisualizer() {
            bars.forEach(b => b.style.animationPlayState = 'paused');
        }
        function playVisualizer() {
            bars.forEach(b => b.style.animationPlayState = 'running');
        }

        pauseVisualizer(); // Inicia pausado
        audio.addEventListener('play', playVisualizer);
        audio.addEventListener('pause', pauseVisualizer);
        audio.addEventListener('ended', pauseVisualizer);

        function copiarLink() {
            const input = document.getElementById('share-url');
            navigator.clipboard.writeText(input.value).then(() => {
                const btn = event.target;
                const orig = btn.textContent;
                btn.textContent = '✓ Copiado!';
                setTimeout(() => btn.textContent = orig, 2500);
            });
        }
    </script>
</body>
</html>
