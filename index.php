<?php
// ============================================================
// LOUVOR.NET - Landing Page
// ============================================================

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/db.php';

$erro = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $inspiracao = trim($_POST['inspiracao'] ?? '');
    $turnstile_token = $_POST['cf-turnstile-response'] ?? '';

    // VERIFICAÇÃO CLOUDFLARE TURNSTILE (Proteção anti-bot)
    if (!empty(CF_TURNSTILE_SECRET_KEY)) {
        $ch = curl_init('https://challenges.cloudflare.com/turnstile/v0/siteverify');
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query([
            'secret'   => CF_TURNSTILE_SECRET_KEY,
            'response' => $turnstile_token,
            'remoteip' => $_SERVER['REMOTE_ADDR']
        ]));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $res = json_decode(curl_exec($ch), true);
        curl_close($ch);

        if (!$res['success']) {
            $erro = 'Falha na verificação de segurança. Por favor, tente novamente.';
            goto fim_post;
        }
    }

    if (strlen($inspiracao) < 10) {
        $erro = 'Por favor, compartilhe um pouco mais da sua história (mínimo 10 caracteres).';
    } else {
        $uid = uuid4();
        db()->prepare('INSERT INTO musicas (id, inspiracao, status) VALUES (?, ?, ?)')
            ->execute([$uid, $inspiracao, 'aguardando_pagamento']);
        
        // Salva no histórico do navegador via Script (passando para o frontend)
        echo "<script>
            try {
                const uid = " . json_encode($uid) . ";
                const data = new Date().toLocaleDateString('pt-BR', { day:'2-digit', month:'long', year:'numeric' });
                let hist = JSON.parse(localStorage.getItem('louvor_historico') || '[]');
                hist = hist.filter(m => m.uid !== uid);
                hist.push({ uid, titulo: 'Aguardando Pagamento', data, status: 'pendente' });
                localStorage.setItem('louvor_historico', JSON.stringify(hist));
            } catch(e) {}
            window.location = 'checkout.php?uid=' + encodeURIComponent(" . json_encode($uid) . ");
        </script>";
        exit;
    }
}
fim_post:
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>LOUVOR.NET — Música Cristã Criada por IA para Você</title>
    
    <script src="https://challenges.cloudflare.com/turnstile/v0/api.js" async defer></script>
    
    <!-- SEO & Social Sharing -->
    <meta name="description" content="Transforme sua história, oração ou versículo em uma música cristã exclusiva com letra e melodia geradas por Inteligência Artificial.">
    <meta property="og:title" content="LOUVOR.NET — Sua história em um louvor eterno">
    <meta property="og:description" content="Crie uma música cristã exclusiva com IA. Sua oração agora tem melodia.">
    <meta property="og:image" content="<?= BASE_URL ?>/assets/logo.jpeg">
    <meta property="og:url" content="<?= BASE_URL ?>">
    <meta name="twitter:card" content="summary_large_image">

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:ital,wght@0,400;0,600;0,700;1,400;1,600&family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">

    <link rel="icon" type="image/jpeg" href="assets/logo.jpeg">

    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        *, body { font-family: 'Inter', system-ui, sans-serif; }
        .font-display { font-family: 'Cormorant Garamond', Georgia, serif; }

        :root {
            --gold: #C9A84C;
            --gold-light: #E8CC80;
            --gold-pale: #F7EDD0;
            --cream: #FDFBF5;
            --white: #FFFFFF;
            --ink: #1C1917;
            --ink-soft: #44403C;
            --sky: #EEF6FF;
        }

        html { scroll-behavior: smooth; }

        ::-webkit-scrollbar { width: 5px; }
        ::-webkit-scrollbar-track { background: #fff; }
        ::-webkit-scrollbar-thumb { background: var(--gold-light); border-radius: 10px; }

        body { background: var(--white); color: var(--ink); }

        /* ── Gradiente angelical de fundo ── */
        .angel-bg {
            background:
                radial-gradient(ellipse 80% 60% at 50% -10%, rgba(201,168,76,0.13) 0%, transparent 70%),
                radial-gradient(ellipse 60% 40% at 80% 20%, rgba(174,210,255,0.18) 0%, transparent 60%),
                radial-gradient(ellipse 50% 50% at 20% 30%, rgba(255,255,255,1) 0%, transparent 70%),
                #FDFBF5;
        }

        /* ── Brilho dourado suave ── */
        .glow-gold {
            background: radial-gradient(ellipse 70% 50% at 50% 50%, rgba(201,168,76,0.12) 0%, transparent 70%);
        }

        /* ── Texto gradiente ouro ── */
        .gold-text {
            background: linear-gradient(135deg, #B8922A 0%, #D4AF37 40%, #E8CC80 70%, #C9A84C 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }

        /* ── Navbar ── */
        .navbar {
            background: rgba(255,255,255,0.88);
            backdrop-filter: blur(24px);
            -webkit-backdrop-filter: blur(24px);
            border-bottom: 1px solid rgba(201,168,76,0.15);
        }

        /* ── Botão principal ── */
        .btn-gold {
            background: linear-gradient(135deg, #C9A84C 0%, #D4AF37 50%, #B8922A 100%);
            box-shadow: 0 4px 24px rgba(201,168,76,0.35), 0 1px 3px rgba(0,0,0,0.1);
            transition: transform 0.2s, box-shadow 0.2s;
            position: relative;
            overflow: hidden;
        }
        .btn-gold::before {
            content: '';
            position: absolute;
            inset: 0;
            background: linear-gradient(135deg, rgba(255,255,255,0.2) 0%, transparent 50%);
        }
        .btn-gold:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 36px rgba(201,168,76,0.45), 0 2px 6px rgba(0,0,0,0.12);
        }
        .btn-gold:active { transform: translateY(0); }

        .btn-outline {
            border: 1.5px solid rgba(201,168,76,0.4);
            color: #A07830;
            transition: all 0.2s;
        }
        .btn-outline:hover {
            background: rgba(201,168,76,0.08);
            border-color: #C9A84C;
        }

        /* ── Cards ── */
        .card-angel {
            background: #fff;
            border: 1px solid rgba(201,168,76,0.15);
            box-shadow: 0 2px 20px rgba(0,0,0,0.04), 0 0 0 0 rgba(201,168,76,0);
            transition: box-shadow 0.3s, transform 0.3s;
        }
        .card-angel:hover {
            box-shadow: 0 12px 40px rgba(0,0,0,0.08), 0 0 0 3px rgba(201,168,76,0.1);
            transform: translateY(-3px);
        }

        /* ── Step number ── */
        .step-number {
            background: linear-gradient(135deg, #F7EDD0 0%, #EDD88A 100%);
            color: #8B6914;
            font-family: 'Cormorant Garamond', serif;
            font-weight: 700;
        }

        /* ── Textarea ── */
        .textarea-angel {
            background: #FDFBF5;
            border: 1.5px solid #E8D9A8;
            color: #1C1917;
            transition: border-color 0.2s, box-shadow 0.2s;
            resize: none;
        }
        .textarea-angel::placeholder { color: #A8967A; }
        .textarea-angel:focus {
            outline: none;
            border-color: #C9A84C;
            box-shadow: 0 0 0 4px rgba(201,168,76,0.12);
            background: #fff;
        }

        /* ── Card de preço ── */
        .price-card {
            background: linear-gradient(160deg, #fff 0%, #FDFBF5 100%);
            border: 2px solid #D4AF37;
            box-shadow:
                0 0 0 8px rgba(212,175,55,0.07),
                0 24px 64px rgba(201,168,76,0.18);
        }

        /* ── Depoimentos ── */
        .testimonial-card {
            background: #fff;
            border: 1px solid #F0E8CC;
            transition: box-shadow 0.3s, transform 0.3s;
        }
        .testimonial-card:hover {
            box-shadow: 0 16px 48px rgba(0,0,0,0.07);
            transform: translateY(-3px);
        }

        /* ── FAQ ── */
        details summary { cursor: pointer; list-style: none; }
        details summary::-webkit-details-marker { display: none; }
        details[open] .chevron { transform: rotate(180deg); }
        .chevron { transition: transform 0.25s; }
        .faq-item {
            background: #fff;
            border: 1px solid #EDE5CC;
            transition: border-color 0.2s;
        }
        details[open].faq-item { border-color: #C9A84C; }

        /* ── Divisor ── */
        .divider-gold {
            height: 1px;
            background: linear-gradient(to right, transparent, rgba(201,168,76,0.4), transparent);
        }

        /* ── Pena / asa SVG flutuante ── */
        @keyframes float-slow {
            0%, 100% { transform: translateY(0) rotate(-8deg); }
            50%       { transform: translateY(-14px) rotate(-6deg); }
        }
        @keyframes float-slow-2 {
            0%, 100% { transform: translateY(0) rotate(8deg) scaleX(-1); }
            50%       { transform: translateY(-10px) rotate(6deg) scaleX(-1); }
        }
        .feather-1 { animation: float-slow   5s ease-in-out infinite; }
        .feather-2 { animation: float-slow-2 6s ease-in-out 1s infinite; }

        /* ── Animações de entrada ── */
        @keyframes fadeUp {
            from { opacity: 0; transform: translateY(20px); }
            to   { opacity: 1; transform: translateY(0); }
        }
        .anim-1 { animation: fadeUp 0.7s ease forwards; }
        .anim-2 { animation: fadeUp 0.7s ease 0.12s forwards; opacity: 0; }
        .anim-3 { animation: fadeUp 0.7s ease 0.24s forwards; opacity: 0; }
        .anim-4 { animation: fadeUp 0.7s ease 0.36s forwards; opacity: 0; }

        /* ── Badge live ── */
        .badge-live::before {
            content: '';
            display: inline-block;
            width: 6px; height: 6px;
            background: #22c55e;
            border-radius: 50%;
            margin-right: 6px;
            animation: blink 2s infinite;
            vertical-align: middle;
        }
        @keyframes blink {
            0%, 100% { opacity: 1; }
            50%       { opacity: 0.3; }
        }

        /* ── Ondas de som (áudio ativo) ── */
        #btn-audio.playing {
            background: rgba(201,168,76,0.12) !important;
            border-color: rgba(201,168,76,0.5) !important;
            color: #8B6914 !important;
        }
        @keyframes sw { 0%,100%{transform:scaleY(.4)} 50%{transform:scaleY(1)} }
        #sound-waves span {
            display:inline-block; width:3px; height:12px;
            border-radius:2px; background:#C9A84C;
            animation:sw .8s ease-in-out infinite;
            transform-origin:bottom;
        }
        #sound-waves span:nth-child(2){animation-delay:.15s}
        #sound-waves span:nth-child(3){animation-delay:.3s}

        /* ── Sugestões de tema ── */
        .tag-pill {
            background: #FBF6E9;
            border: 1px solid #E8D9A8;
            color: #7A6030;
            transition: all 0.15s;
        }
        .tag-pill:hover, .tag-pill.active {
            background: #F7EDD0;
            border-color: #C9A84C;
            color: #5C4615;
        }

        /* ── Hero ornamento ── */
        .hero-ornament {
            position: absolute;
            width: 600px; height: 600px;
            border-radius: 50%;
            background: radial-gradient(circle, rgba(201,168,76,0.09) 0%, transparent 70%);
            pointer-events: none;
        }
    </style>
    <?php require_once __DIR__ . '/includes/gtag.php'; ?>
    <?php if (isset($_SESSION['ga_event']) && $_SESSION['ga_event'] === 'generate_lead'): ?>
    <script>
      gtag('event', 'generate_lead', {
        'currency': 'BRL',
        'value': <?= MUSICA_PRICE ?>
      });
    </script>
    <?php unset($_SESSION['ga_event']); endif; ?>
</head>
<body>

<!-- Áudio de fundo -->
<audio id="bg-audio" loop preload="auto" playsinline muted>
    <source src="assets/musica.mp3" type="audio/mpeg">
    Seu navegador não suporta o elemento de áudio.
</audio>

<!-- ════════════════════════════════════════
     NAVBAR
════════════════════════════════════════ -->
<nav class="navbar fixed top-0 left-0 right-0 z-50 px-6 md:px-12 py-4 flex items-center justify-between">
    <a href="/" class="flex items-center gap-2.5">
        <!-- Logo arredondada -->
        <img src="assets/logo.jpeg" alt="LOUVOR.NET" class="w-9 h-9 rounded-full object-cover border border-[#C9A84C]/30">
        <span class="text-xl font-bold tracking-widest" style="color:#1C1917; letter-spacing:.12em">
            LOUVOR<span style="color:#C9A84C">.NET</span>
        </span>
    </a>

    <div class="hidden md:flex items-center gap-8 text-sm font-medium" style="color:#6B5B3E">
        <a href="#como-funciona" class="hover:text-[#C9A84C] transition-colors">Como funciona</a>
        <a href="#depoimentos"   class="hover:text-[#C9A84C] transition-colors">Depoimentos</a>
        <a href="#preco"         class="hover:text-[#C9A84C] transition-colors">Preço</a>
    </div>

    <div class="flex items-center gap-3">
        <button id="btn-audio" onclick="toggleAudio()"
            class="flex items-center gap-2 px-4 py-2 rounded-full text-sm font-medium transition-all"
            style="background:rgba(201,168,76,0.08); border:1px solid rgba(201,168,76,0.25); color:#8B6914;">
            <span id="audio-icon">🔇</span>
            <span id="audio-label" class="hidden md:inline">Ativar som</span>
        </button>
        <button onclick="abrirHistorico()"
            class="hidden md:flex items-center gap-2 px-4 py-2 rounded-full text-sm font-medium transition-all"
            style="background:rgba(201,168,76,0.06); border:1px solid rgba(201,168,76,0.2); color:#8B6914;"
            title="Minhas músicas">
            🎵 Minhas músicas
        </button>
        <a href="#criar"
           class="hidden md:inline-flex items-center gap-2 px-5 py-2.5 rounded-full text-sm font-semibold text-white btn-gold">
            ✦ Criar minha música
        </a>
    </div>
</nav>


<!-- ════════════════════════════════════════
     MODAL DE BOAS-VINDAS (MOBILE ONLY)
════════════════════════════════════════ -->
<div id="welcome-modal" class="fixed inset-0 z-[100] hidden items-center justify-center p-6 bg-[#FDFBF5]">
    <div class="max-w-sm w-full text-center">
        <!-- Ornamento Superior -->
        <div class="flex justify-center mb-8">
            <div class="relative">
                <div class="hero-ornament absolute inset-0 -m-8 opacity-40"></div>
                <img src="assets/logo.jpeg" alt="LOUVOR.NET" class="w-20 h-20 rounded-full object-cover border-2 border-[#C9A84C]/40 relative z-10 mx-auto">
            </div>
        </div>

        <h2 class="font-display text-4xl font-bold mb-4" style="color:#1C1917;">
            Bem-vindo ao<br><span class="gold-text">LOUVOR.NET</span>
        </h2>

        <p class="text-base leading-relaxed mb-8" style="color:#5C4A2A;">
            Estamos prontos para transformar sua história em um cântico de adoração único.
        </p>

        <div class="rounded-2xl p-5 mb-10 italic text-sm" style="background:rgba(201,168,76,0.06); border:1px solid rgba(201,168,76,0.15); color:#8B6914;">
            "Cantai ao Senhor um cântico novo, cantai ao Senhor todas as terras."<br>
            <span class="font-bold mt-2 block not-italic">— Salmos 96:1</span>
        </div>

        <button onclick="closeWelcomeModal()"
                class="btn-gold w-full py-5 rounded-2xl text-white font-bold text-xl tracking-wide shadow-xl">
            ✨ Entrar e Ouvir
        </button>

        <p class="text-xs mt-6" style="color:#B8A07A;">Toque para iniciar sua experiência</p>
    </div>
</div>


<!-- ════════════════════════════════════════
     HERO
════════════════════════════════════════ -->
<section class="angel-bg min-h-screen flex flex-col items-center justify-center px-6 pt-28 pb-20 text-center relative overflow-hidden">

    <!-- Ornamentos de luz -->
    <div class="hero-ornament" style="top:-200px; left:50%; transform:translateX(-50%);"></div>
    <div class="hero-ornament" style="bottom:-250px; left:-100px; width:400px; height:400px; background:radial-gradient(circle,rgba(174,210,255,0.12) 0%,transparent 70%);"></div>
    <div class="hero-ornament" style="top:30%; right:-150px; width:350px; height:350px;"></div>

    <!-- Penas decorativas -->
    <div class="feather-1 absolute top-28 left-10 opacity-20 select-none hidden lg:block" style="font-size:5rem">🪶</div>
    <div class="feather-2 absolute top-40 right-12 opacity-15 select-none hidden lg:block" style="font-size:4rem">🪶</div>
    <div class="feather-1 absolute bottom-24 right-20 opacity-10 select-none hidden lg:block" style="font-size:3rem">🪶</div>

    <div class="relative z-10 max-w-4xl mx-auto">

        <!-- Badge -->
        <div class="anim-1 inline-flex items-center gap-2 px-5 py-2 rounded-full mb-8 text-xs font-semibold"
             style="background:rgba(201,168,76,0.1); border:1px solid rgba(201,168,76,0.3); color:#8B6914;">
            <span class="badge-live">Música criada em minutos</span>
        </div>

        <!-- Headline -->
        <h1 class="anim-2 font-display font-bold leading-[1.08] mb-6"
            style="font-size:clamp(3rem,7vw,5.5rem); color:#1C1917;">
            Transforme sua história<br>em um <span class="gold-text">louvor eterno</span>
        </h1>

        <!-- Subtítulo -->
        <p class="anim-3 text-lg md:text-xl leading-relaxed mb-3 max-w-2xl mx-auto" style="color:#5C4A2A;">
            Sua oração agora tem uma <strong style="color:#1C1917; font-weight:600;">melodia exclusiva</strong>.
            A IA compõe letra e música a partir da sua história — uma canção que só existe para você.
        </p>

        <p class="anim-3 font-display italic text-base mb-10" style="color:#B8922A;">
            "Cantai ao Senhor um cântico novo" — Salmos 96:1
        </p>

        <!-- CTAs -->
        <div class="anim-4 flex flex-col sm:flex-row items-center justify-center gap-4 mb-14">
            <a href="#criar" class="btn-gold px-9 py-4 rounded-full text-white font-bold text-lg tracking-wide w-full sm:w-auto">
                🎵 CRIAR MEU LOUVOR AGORA
            </a>
            <a href="#como-funciona"
               class="btn-outline px-9 py-4 rounded-full font-medium text-sm bg-white/70 w-full sm:w-auto text-center">
                Como funciona ↓
            </a>
        </div>

        <!-- Social proof -->
        <div class="anim-4 inline-flex items-center gap-8 px-8 py-5 rounded-2xl"
             style="background:rgba(255,255,255,0.8); border:1px solid rgba(201,168,76,0.2); box-shadow:0 4px 24px rgba(0,0,0,0.05);">
            <div class="text-center">
                <p class="font-display text-3xl font-bold" style="color:#1C1917">+1.200</p>
                <p class="text-xs mt-0.5" style="color:#8B7355">músicas criadas</p>
            </div>
            <div class="w-px h-10" style="background:rgba(201,168,76,0.2)"></div>
            <div class="text-center">
                <p class="font-display text-3xl font-bold" style="color:#1C1917">★ 4.9</p>
                <p class="text-xs mt-0.5" style="color:#8B7355">avaliação média</p>
            </div>
            <div class="w-px h-10" style="background:rgba(201,168,76,0.2)"></div>
            <div class="text-center">
                <p class="font-display text-3xl font-bold" style="color:#1C1917">3 min</p>
                <p class="text-xs mt-0.5" style="color:#8B7355">tempo médio</p>
            </div>
        </div>
    </div>
</section>


<!-- ════════════════════════════════════════
     COMO FUNCIONA
════════════════════════════════════════ -->
<section id="como-funciona" class="py-28 px-6" style="background:#FDFBF5;">
    <div class="max-w-5xl mx-auto">

        <div class="text-center mb-16">
            <p class="text-xs font-bold tracking-widest uppercase mb-3" style="color:#C9A84C">Como funciona</p>
            <h2 class="font-display text-5xl md:text-6xl font-bold mb-4" style="color:#1C1917;">
                Simples como uma oração
            </h2>
            <p class="text-base max-w-lg mx-auto" style="color:#6B5B3E;">
                Em 4 passos, sua história vira uma música que vai tocar corações.
            </p>
        </div>

        <div class="grid md:grid-cols-4 gap-6">
            <?php $steps = [
                ['01', '✍️', 'Conte sua história', 'Escreva sobre um momento difícil, uma gratidão, um versículo ou uma oração.'],
                ['02', '💳', 'Pague via PIX', 'Pagamento instantâneo e seguro. Apenas R$ '.number_format(MUSICA_PRICE,2,',','.').' por composição.'],
                ['03', '✨', 'IA compõe sua música', 'Nossa IA escreve a letra e gera a melodia com voz. Tudo em minutos.'],
                ['04', '🎵', 'Ouça e compartilhe', 'Receba sua música única. Baixe o MP3 e compartilhe com quem você ama.'],
            ]; ?>
            <?php foreach ($steps as [$num, $icon, $title, $desc]): ?>
            <div class="card-angel rounded-2xl p-7 flex flex-col">
                <div class="step-number w-10 h-10 rounded-full flex items-center justify-center text-lg font-bold mb-4">
                    <?= $num ?>
                </div>
                <span class="text-3xl mb-4"><?= $icon ?></span>
                <h3 class="font-semibold text-base mb-2" style="color:#1C1917"><?= $title ?></h3>
                <p class="text-sm leading-relaxed" style="color:#6B5B3E"><?= $desc ?></p>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>


<!-- ════════════════════════════════════════
     FORM / CTA
════════════════════════════════════════ -->
<section id="criar" class="py-28 px-6 relative overflow-hidden" style="background:#fff;">
    <div class="absolute inset-0 glow-gold pointer-events-none"></div>

    <div class="relative z-10 max-w-2xl mx-auto">

        <div class="text-center mb-12">
            <p class="text-xs font-bold tracking-widest uppercase mb-3" style="color:#C9A84C">Crie agora</p>
            <h2 class="font-display text-5xl md:text-6xl font-bold mb-4" style="color:#1C1917;">
                O que está no seu <span class="gold-text">coração?</span>
            </h2>
            <p class="text-base" style="color:#6B5B3E;">
                Compartilhe sua história, uma dificuldade, uma gratidão ou um versículo.
            </p>
        </div>

        <?php if ($erro): ?>
        <div class="flex items-start gap-3 px-5 py-4 rounded-2xl mb-6 text-sm"
             style="background:#FEF2F2; border:1px solid #FECACA; color:#B91C1C;">
            <span>⚠</span> <?= htmlspecialchars($erro) ?>
        </div>
        <?php endif; ?>

        <div class="card-angel rounded-3xl p-8 md:p-10">
            <form method="POST" action="/" class="space-y-5">

                <div class="relative">
                    <textarea
                        name="inspiracao"
                        rows="6"
                        placeholder="Ex: Estou passando por um momento difícil, mas o Salmo 23 me sustenta..."
                        class="textarea-angel w-full rounded-2xl px-6 py-5 text-base leading-relaxed"
                        required minlength="10"
                    ><?= htmlspecialchars($_POST['inspiracao'] ?? '') ?></textarea>
                    <span class="absolute bottom-4 right-5 text-xs" style="color:#B8A07A">mín. 10 caracteres</span>
                </div>

                <!-- Sugestões de tema -->
                <div>
                    <p class="text-xs font-semibold mb-2.5" style="color:#8B7355">Sugestões de tema:</p>
                    <div class="flex flex-wrap gap-2">
                        <?php foreach (['Cura e restauração','Gratidão a Deus','Casamento e amor','Luto e consolo','Novo emprego','Batismo','Aniversário'] as $tag): ?>
                        <button type="button" onclick="aplicarTag(this)" class="tag-pill px-3 py-1.5 rounded-full text-xs font-medium">
                            <?= $tag ?>
                        </button>
                        <?php endforeach; ?>
                    </div>
                </div>

                <!-- Cloudflare Turnstile (Proteção Anti-Bot) -->
                <?php if (!empty(CF_TURNSTILE_SITE_KEY)): ?>
                <div class="flex justify-center mb-4">
                    <div class="cf-turnstile" data-sitekey="<?= CF_TURNSTILE_SITE_KEY ?>" data-theme="light"></div>
                </div>
                <?php endif; ?>

                <button type="submit" class="btn-gold w-full py-5 rounded-2xl text-white font-bold text-xl tracking-wide">
                    🎵 &nbsp;CRIAR MEU LOUVOR AGORA
                </button>

                <p class="text-center text-xs" style="color:#A08060;">
                    Pagamento seguro via PIX —
                    <strong style="color:#6B5B3E;">R$ <?= number_format(MUSICA_PRICE, 2, ',', '.') ?></strong>
                    por música · Aprovação em segundos.
                </p>
            </form>
        </div>
    </div>
</section>


<!-- ════════════════════════════════════════
     PREÇO
════════════════════════════════════════ -->
<section id="preco" class="py-28 px-6" style="background:#FDFBF5;">
    <div class="max-w-lg mx-auto">

        <div class="text-center mb-12">
            <p class="text-xs font-bold tracking-widest uppercase mb-3" style="color:#C9A84C">Investimento</p>
            <h2 class="font-display text-5xl font-bold" style="color:#1C1917;">Simples e justo</h2>
        </div>

        <div class="price-card rounded-3xl p-12 text-center">
            <div class="inline-flex items-center gap-2 px-5 py-2 rounded-full mb-8 text-xs font-bold tracking-widest uppercase"
                 style="background:#FBF6E9; border:1px solid #E8D9A8; color:#8B6914;">
                ✦ PREÇO ESPECIAL DE LANÇAMENTO
            </div>

            <!-- Riscado -->
            <div class="flex items-center justify-center gap-3 mb-4">
                <span class="text-xl line-through" style="color:#B8A07A;">R$ 19,90</span>
                <span class="px-2.5 py-1 rounded-full text-xs font-bold text-white" style="background:#ef4444;">-50%</span>
            </div>

            <!-- Preço atual -->
            <div class="font-display font-bold leading-none mb-4" style="font-size:5.5rem; color:#1C1917;">
                R$&nbsp;<?= number_format(MUSICA_PRICE, 2, ',', '.') ?>
            </div>

            <p class="text-sm font-semibold mb-2" style="color:#C9A84C;">🎁 Oferta por tempo limitado</p>
            <p class="text-sm mb-10" style="color:#8B7355;">por música • pagamento único via PIX</p>

            <ul class="space-y-4 text-left mb-10">
                <?php foreach ([
                    'Letra exclusiva escrita por IA',
                    'Melodia gerada por IA com voz e instrumentos',
                    'Download do arquivo MP3',
                    'Link público para compartilhar',
                    'Entrega em até 5 minutos',
                    'Sua história, sua música — única no mundo',
                ] as $f): ?>
                <li class="flex items-center gap-3 text-sm" style="color:#44403C;">
                    <span class="w-5 h-5 rounded-full flex items-center justify-center flex-shrink-0 text-white text-xs font-bold"
                          style="background:#C9A84C;">✓</span>
                    <?= $f ?>
                </li>
                <?php endforeach; ?>
            </ul>

            <a href="#criar" class="btn-gold block w-full py-4 rounded-xl text-white font-bold text-lg tracking-wide">
                Criar minha música agora
            </a>
        </div>
    </div>
</section>


<!-- ════════════════════════════════════════
     DEPOIMENTOS
════════════════════════════════════════ -->
<section id="depoimentos" class="py-28 px-6" style="background:#fff;">
    <div class="max-w-5xl mx-auto">

        <div class="text-center mb-16">
            <p class="text-xs font-bold tracking-widest uppercase mb-3" style="color:#C9A84C">Depoimentos</p>
            <h2 class="font-display text-5xl md:text-6xl font-bold mb-4" style="color:#1C1917;">
                Vidas <span class="gold-text">tocadas</span>
            </h2>
            <p class="text-base max-w-lg mx-auto" style="color:#6B5B3E;">
                Histórias reais de pessoas que transformaram momentos marcantes em música.
            </p>
        </div>

        <div class="grid md:grid-cols-3 gap-6">
            <?php foreach ([
                ['Ana R.',       'Rio de Janeiro', 'AR', 'Criei uma música para o aniversário da minha mãe com toda a história dela. Ela chorou do começo ao fim. Foi o presente mais especial que já dei.'],
                ['Pr. Marcos S.','São Paulo',      'MS', 'Usei para criar um louvor exclusivo para o aniversário da nossa igreja. A congregação ficou de pé. Já encomendei a segunda.'],
                ['Juliana F.',   'Belo Horizonte', 'JF', 'Perdi meu pai e não conseguia expressar minha dor. A letra que a IA criou me fez finalmente chorar e curar.'],
                ['Reginaldo C.', 'Curitiba',       'RC', 'Pedi uma música sobre meu casamento de 30 anos. Minha esposa disse que foi o maior presente da vida dela.'],
                ['Simone A.',    'Fortaleza',       'SA', 'Usei para celebrar o batismo do meu filho. A música ficou tão linda que o pastor pediu para usar no culto!'],
                ['Diácono Paulo','Brasília',        'DP', 'Já criei 5 músicas para datas especiais da nossa célula. A qualidade do áudio surpreende a cada vez.'],
            ] as [$nome, $cidade, $ini, $texto]): ?>
            <div class="testimonial-card rounded-2xl p-6">
                <div class="flex items-center gap-3 mb-4">
                    <div class="w-10 h-10 rounded-full flex items-center justify-center text-sm font-bold text-white flex-shrink-0"
                         style="background:linear-gradient(135deg,#E8CC80,#B8922A)">
                        <?= $ini ?>
                    </div>
                    <div>
                        <p class="font-semibold text-sm" style="color:#1C1917"><?= $nome ?></p>
                        <p class="text-xs" style="color:#A08060"><?= $cidade ?></p>
                    </div>
                    <span class="ml-auto text-sm" style="color:#C9A84C">★★★★★</span>
                </div>
                <p class="text-sm leading-relaxed italic" style="color:#5C4A2A;">"<?= $texto ?>"</p>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>


<!-- ════════════════════════════════════════
     FAQ
════════════════════════════════════════ -->
<section class="py-24 px-6" style="background:#FDFBF5;">
    <div class="max-w-2xl mx-auto">

        <div class="text-center mb-14">
            <p class="text-xs font-bold tracking-widest uppercase mb-3" style="color:#C9A84C">Dúvidas</p>
            <h2 class="font-display text-5xl font-bold" style="color:#1C1917;">Perguntas frequentes</h2>
        </div>

        <div class="space-y-3">
            <?php foreach ([
                ['A música é realmente única?',                'Sim. Cada composição é gerada do zero a partir da sua história pessoal. Nenhuma outra pessoa no mundo terá exatamente a mesma letra ou melodia.'],
                ['Quanto tempo leva para ficar pronta?',       'Em média 3 a 5 minutos após a confirmação do PIX. Você acompanha em tempo real na tela de processamento.'],
                ['Posso usar no culto ou compartilhar online?','Sim! A música é sua. Você pode ouvir, baixar em MP3, compartilhar no WhatsApp, usar em cultos ou qualquer evento pessoal.'],
                ['O pagamento é seguro?',                      'Totalmente. Usamos o Asaas, plataforma certificada pelo Banco Central. O PIX é processado em segundos.'],
                ['E se a música não ficar como esperado?',     'Nossa IA é treinada especificamente para composição cristã. Se não gostar, entre em contato — analisamos caso a caso.'],
                ['Preciso criar uma conta?',                   'Não! Você recebe o link da sua música direto e pode acessá-lo a qualquer momento pelo link compartilhável.'],
            ] as [$q, $a]): ?>
            <details class="faq-item rounded-2xl overflow-hidden">
                <summary class="flex items-center justify-between px-6 py-5 font-semibold text-sm" style="color:#1C1917;">
                    <?= $q ?>
                    <svg class="chevron w-4 h-4 flex-shrink-0 ml-4" style="color:#C9A84C;" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7"/>
                    </svg>
                </summary>
                <div class="px-6 pb-5 text-sm leading-relaxed" style="color:#6B5B3E; border-top:1px solid #F0E8CC; padding-top:14px">
                    <?= $a ?>
                </div>
            </details>
            <?php endforeach; ?>
        </div>
    </div>
</section>


<!-- ════════════════════════════════════════
     CTA FINAL
════════════════════════════════════════ -->
<section class="py-28 px-6 text-center relative overflow-hidden" style="background:#fff;">
    <div class="absolute inset-0 glow-gold pointer-events-none"></div>
    <div class="relative z-10 max-w-2xl mx-auto">
        <div class="text-5xl mb-6">🕊️</div>
        <h2 class="font-display text-5xl md:text-6xl font-bold mb-5" style="color:#1C1917;">
            Sua melodia está<br><span class="gold-text">esperando por você</span>
        </h2>
        <p class="text-base mb-10" style="color:#6B5B3E;">
            "Tudo que tem fôlego louve ao Senhor." — Salmos 150:6
        </p>
        <a href="#criar" class="btn-gold inline-block px-12 py-5 rounded-full text-white font-bold text-xl tracking-wide">
            🎵 CRIAR MEU LOUVOR AGORA
        </a>
        <p class="text-xs mt-5" style="color:#B8A07A;">
            R$ <?= number_format(MUSICA_PRICE, 2, ',', '.') ?> · PIX · Entrega em minutos
        </p>
    </div>
</section>


<!-- ════════════════════════════════════════
     FOOTER
════════════════════════════════════════ -->
<footer class="px-6 py-10 text-center" style="background:#FDFBF5; border-top:1px solid rgba(201,168,76,0.15);">
    <div class="flex items-center justify-center gap-2.5 mb-3">
        <img src="assets/logo.jpeg" alt="LOUVOR.NET" class="w-8 h-8 rounded-full object-cover border border-[#C9A84C]/30">
        <span class="font-bold tracking-widest text-base" style="color:#1C1917; letter-spacing:.12em">
            LOUVOR<span style="color:#C9A84C">.NET</span>
        </span>
    </div>
    <p class="font-display italic text-sm mb-4" style="color:#A08060;">"Crie em mim, ó Deus, um coração puro." — Salmos 51:10</p>
    <div class="flex items-center justify-center gap-6 text-xs mb-4" style="color:#B8A07A;">
        <a href="termos" class="hover:text-[#C9A84C] transition-colors">Termos de Uso</a>
        <span>·</span>
        <a href="privacidade" class="hover:text-[#C9A84C] transition-colors">Privacidade</a>
        <span>·</span>
        <button onclick="abrirSac()" class="hover:text-[#C9A84C] transition-colors">Ajuda (SAC)</button>
    </div>
    <p class="text-xs" style="color:#C8B99A;">© <?= date('Y') ?> LOUVOR.NET — Todos os direitos reservados.</p>
</footer>

<!-- Botão WhatsApp Flutuante (COMENTADO)
<a href="https://api.whatsapp.com/send?phone=5511999999999&text=Olá! Preciso de ajuda com o LOUVOR.NET" 
   target="_blank"
   style="position:fixed; bottom:24px; right:24px; z-index:150;
          background:#25d366; color:#fff; width:60px; height:60px; border-radius:50%;
          display:flex; align-items:center; justify-content:center;
          box-shadow:0 10px 30px rgba(0,0,0,0.2); transition:transform 0.2s;"
   onmouseover="this.style.transform='scale(1.1)'"
   onmouseout="this.style.transform='scale(1)'">
    <svg class="w-8 h-8" fill="currentColor" viewBox="0 0 24 24">
        <path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347z"/>
        <path d="M12 0C5.373 0 0 5.373 0 12c0 2.125.555 4.118 1.528 5.843L0 24l6.335-1.652A11.954 11.954 0 0 0 12 24c6.627 0 12-5.373 12-12S18.627 0 12 0zm0 21.818a9.818 9.818 0 0 1-5.006-1.372l-.36-.213-3.727.977.992-3.63-.234-.373A9.818 9.818 0 1 1 12 21.818z"/>
    </svg>
</a>
-->

<!-- Botão Instagram Flutuante -->
<a href="https://instagram.com/<?= INSTAGRAM_HANDLE ?>" 
   target="_blank"
   style="position:fixed; bottom:24px; right:24px; z-index:150;
          background:linear-gradient(45deg, #f09433 0%,#e6683c 25%,#dc2743 50%,#cc2366 75%,#bc1888 100%); 
          color:#fff; width:60px; height:60px; border-radius:50%;
          display:flex; align-items:center; justify-content:center;
          box-shadow:0 10px 30px rgba(0,0,0,0.2); transition:transform 0.2s;"
   onmouseover="this.style.transform='scale(1.1)'"
   onmouseout="this.style.transform='scale(1)'">
    <svg class="w-8 h-8" fill="currentColor" viewBox="0 0 24 24">
        <path d="M12 2.163c3.204 0 3.584.012 4.85.07 3.252.148 4.771 1.691 4.919 4.919.058 1.265.069 1.645.069 4.849 0 3.205-.012 3.584-.069 4.849-.149 3.225-1.664 4.771-4.919 4.919-1.266.058-1.644.07-4.85.07-3.204 0-3.584-.012-4.849-.07-3.26-.149-4.771-1.699-4.919-4.92-.058-1.265-.07-1.644-.07-4.849 0-3.204.013-3.583.07-4.849.149-3.227 1.664-4.771 4.919-4.919 1.266-.057 1.645-.069 4.849-.069zm0-2.163c-3.259 0-3.667.014-4.947.072-4.358.2-6.78 2.618-6.98 6.98-.059 1.281-.073 1.689-.073 4.948 0 3.259.014 3.668.072 4.948.2 4.358 2.618 6.78 6.98 6.98 1.281.058 1.689.072 4.948.072 3.259 0 3.668-.014 4.948-.072 4.354-.2 6.782-2.618 6.979-6.98.059-1.28.073-1.689.073-4.948 0-3.259-.014-3.667-.072-4.947-.196-4.354-2.617-6.78-6.979-6.98-1.281-.059-1.69-.073-4.949-.073zm0 5.838c-3.403 0-6.162 2.759-6.162 6.162s2.759 6.163 6.162 6.163 6.162-2.759 6.162-6.163-2.759-6.162-6.162-6.162zm0 10.162c-2.209 0-4-1.79-4-4 0-2.209 1.791-4 4-4s4 1.791 4 4c0 2.21-1.791 4-4 4zm6.406-11.845c-.796 0-1.441.645-1.441 1.44s.645 1.44 1.441 1.44c.795 0 1.439-.645 1.439-1.44s-.644-1.44-1.439-1.44z"/>
    </svg>
</a>

<!-- Banner Cookies LGPD -->
<div id="cookie-banner" style="display:none; position:fixed; bottom:0; left:0; right:0; z-index:200;
     background:#fff; border-top:2px solid #C9A84C; padding:20px 40px; box-shadow:0 -10px 40px rgba(0,0,0,0.1);">
    <div style="max-width:1200px; margin:0 auto; display:flex; flex-wrap:wrap; align-items:center; justify-content:space-between; gap:20px;">
        <p style="font-size:14px; color:#6B5B3E; flex:1; min-width:280px;">
            Utilizamos cookies para personalizar sua experiência e processar seus pedidos com segurança. Ao continuar navegando, você concorda com nossos <a href="termos.php" class="underline">Termos</a> e <a href="privacidade.php" class="underline">Privacidade</a>.
        </p>
        <button onclick="aceitarCookies()" style="background:#C9A84C; color:#fff; border:none; padding:12px 32px; border-radius:12px; font-weight:700; cursor:pointer;">Aceitar e Continuar</button>
    </div>
</div>


<!-- Modal SAC -->
<div id="sac-modal" style="display:none;position:fixed;inset:0;z-index:200;
     background:rgba(0,0,0,0.4);backdrop-filter:blur(4px);align-items:center;justify-content:center;padding:20px;"
     onclick="if(event.target===this)fecharSac()">
    <div style="background:#fff;border-radius:24px;width:100%;max-width:500px;
                padding:32px;box-shadow:0 20px 60px rgba(0,0,0,0.15);" onclick="event.stopPropagation()">
        <div style="display:flex;align-items:center;justify-content:between;margin-bottom:24px;">
            <h3 style="font-size:22px;font-weight:700;color:#1C1917;margin:0;flex:1;">Precisa de ajuda?</h3>
            <button onclick="fecharSac()" style="border:none;background:#F0E8CC;border-radius:50%;
                    width:32px;height:32px;cursor:pointer;font-size:14px;color:#8B6914;">✕</button>
        </div>
        
        <form id="sac-form" onsubmit="enviarSac(event)" style="display:flex;flex-direction:column;gap:16px;">
            <div>
                <label style="display:block;font-size:12px;font-weight:600;color:#8B7355;margin-bottom:6px;">Seu Nome</label>
                <input type="text" name="nome" required style="width:100%;padding:12px 16px;border-radius:12px;border:1.5px solid #E8D9A8;outline:none;" placeholder="Como podemos te chamar?">
            </div>
            <div style="display:grid;grid-template-cols:1fr 1fr;gap:12px;">
                <div>
                    <label style="display:block;font-size:12px;font-weight:600;color:#8B7355;margin-bottom:6px;">E-mail</label>
                    <input type="email" name="email" required style="width:100%;padding:12px 16px;border-radius:12px;border:1.5px solid #E8D9A8;outline:none;" placeholder="seu@email.com">
                </div>
                <div>
                    <label style="display:block;font-size:12px;font-weight:600;color:#8B7355;margin-bottom:6px;">WhatsApp (opcional)</label>
                    <input type="tel" name="whatsapp" style="width:100%;padding:12px 16px;border-radius:12px;border:1.5px solid #E8D9A8;outline:none;" placeholder="(00) 00000-0000">
                </div>
            </div>
            <div>
                <label style="display:block;font-size:12px;font-weight:600;color:#8B7355;margin-bottom:6px;">Assunto</label>
                <select name="assunto" required style="width:100%;padding:12px 16px;border-radius:12px;border:1.5px solid #E8D9A8;outline:none;background:#fff;">
                    <option value="">Selecione...</option>
                    <option value="Dúvida sobre o serviço">Dúvida sobre o serviço</option>
                    <option value="Problema no pagamento">Problema no pagamento</option>
                    <option value="Música não carregou">Música não carregou</option>
                    <option value="Elogio ou Sugestão">Elogio ou Sugestão</option>
                    <option value="Outro">Outro</option>
                </select>
            </div>
            <div>
                <label style="display:block;font-size:12px;font-weight:600;color:#8B7355;margin-bottom:6px;">Mensagem</label>
                <textarea name="mensagem" required style="width:100%;padding:12px 16px;border-radius:12px;border:1.5px solid #E8D9A8;outline:none;min-height:100px;resize:none;" placeholder="Como podemos te ajudar?"></textarea>
            </div>
            <button type="submit" id="btn-sac" style="background:linear-gradient(135deg,#C9A84C,#D4AF37);color:#fff;border:none;padding:16px;border-radius:14px;font-weight:700;cursor:pointer;margin-top:8px;">
                Enviar Mensagem
            </button>
        </form>
    </div>
</div>

<script>
    // ── Funções de SAC ──
    function abrirSac() {
        document.getElementById('sac-modal').style.display = 'flex';
    }
    function fecharSac() {
        document.getElementById('sac-modal').style.display = 'none';
    }
    async function enviarSac(e) {
        e.preventDefault();
        const btn = document.getElementById('btn-sac');
        const form = document.getElementById('sac-form');
        const originalText = btn.textContent;
        
        btn.disabled = true;
        btn.textContent = 'Enviando...';
        
        const formData = new FormData(form);
        
        try {
            const res = await fetch('api/enviar_sac.php', {
                method: 'POST',
                body: formData
            });
            const data = await res.json();
            
            if (data.ok) {
                alert(data.message);
                form.reset();
                fecharSac();
            } else {
                alert(data.error || 'Ocorreu um erro.');
            }
        } catch (err) {
            alert('Erro de conexão.');
        } finally {
            btn.disabled = false;
            btn.textContent = originalText;
        }
    }

    // ── Lógica de Cookies ──
    function aceitarCookies() {
        localStorage.setItem('cookies_aceitos', 'true');
        document.getElementById('cookie-banner').style.display = 'none';
    }
    window.addEventListener('load', () => {
        if (!localStorage.getItem('cookies_aceitos')) {
            document.getElementById('cookie-banner').style.display = 'block';
        }
    });

    // ── Sugestões de tema ──
    function aplicarTag(btn) {
        const ta = document.querySelector('textarea[name="inspiracao"]');
        const tema = btn.textContent.trim();
        ta.value = ta.value ? ta.value + '\n\nTema: ' + tema : 'Tema: ' + tema + '. ';
        ta.focus();
        document.querySelectorAll('.tag-pill').forEach(b => b.classList.remove('active'));
        btn.classList.add('active');
    }

    // ── Smooth scroll ──
    document.querySelectorAll('a[href^="#"]').forEach(a => {
        a.addEventListener('click', e => {
            const t = document.querySelector(a.getAttribute('href'));
            if (t) { e.preventDefault(); t.scrollIntoView({ behavior:'smooth', block:'start' }); }
        });
    });

    // ── Navbar sombra ao scrollar ──
    window.addEventListener('scroll', () => {
        document.querySelector('nav').style.boxShadow =
            window.scrollY > 20 ? '0 4px 24px rgba(0,0,0,0.07)' : 'none';
    });

    // ── Áudio de fundo ──
    const audio = document.getElementById('bg-audio');
    const btn   = document.getElementById('btn-audio');
    const icon  = document.getElementById('audio-icon');
    const lbl   = document.getElementById('audio-label');
    let playing = false;

    // Volume confortável
    audio.volume = 0.35;

    const waveHTML = `<span id="sound-waves" class="flex items-end gap-0.5 h-3">
        <style>
            @keyframes sw { 0%,100%{transform:scaleY(.4)} 50%{transform:scaleY(1)} }
        </style>
        <span class="w-[3px] h-full bg-[#C9A84C] rounded-full" style="animation:sw 0.8s ease-in-out infinite; transform-origin:bottom;"></span>
        <span class="w-[3px] h-full bg-[#C9A84C] rounded-full" style="animation:sw 0.8s ease-in-out 0.15s infinite; transform-origin:bottom;"></span>
        <span class="w-[3px] h-full bg-[#C9A84C] rounded-full" style="animation:sw 0.8s ease-in-out 0.3s infinite; transform-origin:bottom;"></span>
    </span>`;

    function updateUI() {
        if (!audio.paused && !audio.muted) {
            playing = true;
            icon.innerHTML = waveHTML;
            lbl.textContent = 'Pausar som';
            btn.classList.add('playing');
        } else if (!audio.paused && audio.muted) {
            playing = true;
            icon.textContent = '🔈';
            lbl.textContent = 'Ativar som';
            btn.classList.remove('playing');
        } else {
            playing = false;
            icon.textContent = '🔇';
            lbl.textContent = 'Ativar som';
            btn.classList.remove('playing');
        }
    }

    async function toggleAudio() {
        try {
            if (audio.paused) {
                audio.muted = false;
                await audio.play();
            } else if (!audio.paused && audio.muted) {
                audio.muted = false;
            } else {
                audio.pause();
            }
            updateUI();
        } catch (err) {
            console.error("Erro ao alternar áudio:", err);
        }
    }

    // ── Boas-vindas Mobile ──
    const isMobile = /Android|iPhone|iPad|iPod/i.test(navigator.userAgent);
    const modal = document.getElementById('welcome-modal');

    function closeWelcomeModal() {
        modal.classList.add('hidden');
        modal.classList.remove('flex');
        // Ao clicar no botão da modal, garantimos a interação para tocar som
        unmuteAll();
    }

    // Estratégia de Autoplay para Android/iOS
    const startAudio = async () => {
        try {
            // Se for mobile, mostra a modal para forçar interação
            if (isMobile) {
                modal.classList.remove('hidden');
                modal.classList.add('flex');
            }

            // Tenta tocar mudo inicialmente
            audio.muted = true;
            await audio.play();
            updateUI();
        } catch (err) {
            console.log("Autoplay mudo bloqueado. Aguardando interação...");
        }
    };

    const unmuteAll = async () => {
        try {
            // 2. No primeiro clique, remove o mudo
            if (audio.muted || audio.paused) {
                audio.muted = false;
                await audio.play();
                updateUI();
            }
            // Remove os ouvintes para não rodar em cada clique
            document.removeEventListener('click', unmuteAll);
            document.removeEventListener('touchstart', unmuteAll);
        } catch (err) {
            console.error("Erro ao desativar mudo:", err);
        }
    };

    // Inicia mudo ao carregar
    window.addEventListener('load', startAudio);

    // Desativa mudo na primeira interação
    document.addEventListener('click', unmuteAll);
    document.addEventListener('touchstart', unmuteAll);

    // Sincroniza UI com eventos do elemento
    audio.onplay = updateUI;
    audio.onpause = updateUI;
    audio.onvolumechange = updateUI;

    // ── Histórico de músicas ──
    window.abrirHistorico = function() {
        document.getElementById('historico-modal').style.display = 'flex';
        renderHistorico();
    };
    window.fecharHistorico = function() {
        document.getElementById('historico-modal').style.display = 'none';
    };

    // ── Máscara de Telefone ──
    const telInput = document.getElementById('input-telefone');
    if (telInput) {
        telInput.addEventListener('input', (e) => {
            let v = e.target.value.replace(/\D/g, "");
            v = v.substring(0, 11); // limita 11 dígitos
            if (v.length > 0) {
                v = "(" + v;
                if (v.length > 3) v = v.substring(0, 3) + ") " + v.substring(3);
                if (v.length > 10) v = v.substring(0, 10) + "-" + v.substring(10);
                else if (v.length > 6) v = v.substring(0, 6) + " " + v.substring(6);
            }
            e.target.value = v;
        });
    }

    function renderHistorico() {
        const list = document.getElementById('historico-list');
        let hist = [];
        try { hist = JSON.parse(localStorage.getItem('louvor_historico') || '[]'); } catch(e) {}
        if (!hist.length) {
            list.innerHTML = '<p style="text-align:center;color:#B8A07A;padding:32px 0;font-size:14px;">Você ainda não criou nenhuma música neste dispositivo.</p>';
            return;
        }
        // Ordena por data (as mais recentes primeiro)
        list.innerHTML = hist.slice().reverse().map(m => {
            const isDone = m.status !== 'pendente';
            const icon = isDone ? '🎵' : '⏳';
            const color = isDone ? 'linear-gradient(135deg,#C9A84C,#E8CC80)' : 'linear-gradient(135deg,#E8D9A8,#F0E8CC)';
            const statusTxt = isDone ? '' : ' <span style="font-size:10px; color:#B8922A; background:#FBF6E9; padding:2px 6px; border-radius:4px; margin-left:4px; font-weight:bold; border:1px solid #E8D9A8;">AGUARDANDO</span>';
            
            return `<a href="ouvir.php?uid=${encodeURIComponent(m.uid)}" style="display:flex;align-items:center;
              gap:12px;padding:14px 0;border-bottom:1px solid #F0E8CC;text-decoration:none;">
              <div style="width:40px;height:40px;border-radius:50%;background:${color};
                          display:flex;align-items:center;justify-content:center;color:#fff;font-size:16px;flex-shrink:0;">${icon}</div>
              <div style="flex:1;min-width:0;">
                <p style="font-weight:600;color:#1C1917;margin:0;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;font-size:14px;">
                    ${m.titulo || 'Minha música'}${statusTxt}
                </p>
                <p style="font-size:11px;color:#A08060;margin:4px 0 0;">${m.data || ''}</p>
              </div>
              <span style="color:#C9A84C;font-size:20px;">›</span>
            </a>`;
        }).join('');
    }
</script>

<!-- Modal Histórico -->
<div id="historico-modal" style="display:none;position:fixed;inset:0;z-index:200;
     background:rgba(0,0,0,0.4);backdrop-filter:blur(4px);align-items:flex-end;justify-content:center;"
     onclick="if(event.target===this)fecharHistorico()">
    <div style="background:#fff;border-radius:24px 24px 0 0;width:100%;max-width:480px;
                padding:28px 24px;max-height:80vh;overflow-y:auto;
                box-shadow:0 -8px 40px rgba(0,0,0,0.15);" onclick="event.stopPropagation()">
        <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:16px;">
            <h3 style="font-size:18px;font-weight:700;color:#1C1917;margin:0;">🎵 Minhas músicas</h3>
            <button onclick="fecharHistorico()" style="border:none;background:#F0E8CC;border-radius:50%;
                    width:30px;height:30px;cursor:pointer;font-size:14px;color:#8B6914;">✕</button>
        </div>
        <p style="font-size:12px;color:#B8A07A;margin-bottom:16px;">Músicas criadas neste dispositivo.</p>
        <div id="historico-list"></div>
    </div>
</div>
</body>
</html>
