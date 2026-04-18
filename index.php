<?php
// ============================================================
// ZOA MUSIC - Landing Page
// ============================================================

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/db.php';

$erro = '';

$generos = [
    'funk'      => ['emoji' => '🎤', 'label' => 'Funk Carioca', 'sub' => '150 BPM'],
    'sertanejo' => ['emoji' => '🤠', 'label' => 'Sertanejo',    'sub' => 'Universitário'],
    'mpb'       => ['emoji' => '🎵', 'label' => 'MPB',          'sub' => 'Música Popular'],
    'forro'     => ['emoji' => '🪗', 'label' => 'Forró',        'sub' => 'Raiz'],
    'pagode'    => ['emoji' => '🥁', 'label' => 'Pagode',       'sub' => 'Samba'],
    'trap'      => ['emoji' => '🎧', 'label' => 'Trap BR',      'sub' => 'Hip Hop'],
    'pisadinha' => ['emoji' => '👢', 'label' => 'Pisadinha',    'sub' => 'Forró Pisadão'],
    'rock'      => ['emoji' => '🤘', 'label' => 'Rock BR',      'sub' => 'Rock Nacional'],
    'axe'       => ['emoji' => '💃', 'label' => 'Axé',          'sub' => 'Baiano'],
    'pop'       => ['emoji' => '🎹', 'label' => 'Pop Brasil',   'sub' => 'Contemporâneo'],
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $inspiracao = trim($_POST['inspiracao'] ?? '');
    $estilo_key = trim($_POST['estilo_key'] ?? 'pop');
    $turnstile_token = $_POST['cf-turnstile-response'] ?? '';

    $estilo_label = isset($generos[$estilo_key])
        ? $generos[$estilo_key]['label'] . ' ' . $generos[$estilo_key]['sub']
        : 'Pop Brasil';

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
        $erro = 'Conta mais detalhes sobre a zoeira (mínimo 10 caracteres).';
    } else {
        $uid = uuid4();
        db()->prepare('INSERT INTO musicas (id, inspiracao, estilo, status) VALUES (?, ?, ?, ?)')
            ->execute([$uid, $inspiracao, $estilo_label, 'aguardando_pagamento']);

        echo "<script>
            try {
                const uid = " . json_encode($uid) . ";
                const data = new Date().toLocaleDateString('pt-BR', { day:'2-digit', month:'long', year:'numeric' });
                let hist = JSON.parse(localStorage.getItem('zoamusic_historico') || '[]');
                hist = hist.filter(m => m.uid !== uid);
                hist.push({ uid, titulo: 'Aguardando Pagamento', data, status: 'pendente' });
                localStorage.setItem('zoamusic_historico', JSON.stringify(hist));
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
    <title>ZOA MUSIC — Crie músicas para zuar seus amigos com IA</title>
    <link rel="canonical" href="<?= BASE_URL ?>" />

    <script src="https://challenges.cloudflare.com/turnstile/v0/api.js" async defer></script>

    <meta name="description" content="Crie músicas personalizadas para zuar seus amigos com Inteligência Artificial. Funk, Sertanejo, MPB, Forró e muito mais. Letra + melodia em minutos.">
    <meta name="keywords" content="música de zoeira, zoa amigo com música, música personalizada ia, funk zoeira, sertanejo engraçado, mpb humor">
    <meta property="og:title" content="ZOA MUSIC — Crie músicas de zoeira com IA">
    <meta property="og:description" content="A IA escreve a letra e compõe a melodia. Você manda no grupo. Eles nunca mais te perdoam.">
    <meta property="og:image" content="<?= BASE_URL ?>/assets/logo.svg">
    <meta property="og:url" content="<?= BASE_URL ?>">
    <meta name="twitter:card" content="summary_large_image">

    <script type="application/ld+json">
    {
      "@context": "https://schema.org",
      "@type": "SoftwareApplication",
      "name": "ZOA MUSIC",
      "operatingSystem": "All",
      "applicationCategory": "MultimediaApplication",
      "description": "Crie músicas de zoeira personalizadas com Inteligência Artificial.",
      "offers": { "@type": "Offer", "price": "<?= MUSICA_PRICE ?>", "priceCurrency": "BRL" }
    }
    </script>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Space+Grotesk:wght@400;500;600;700;800&family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">

    <link rel="icon" type="image/svg+xml" href="assets/logo.svg">

    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        *, body { font-family: 'Inter', system-ui, sans-serif; }
        .font-display { font-family: 'Space Grotesk', system-ui, sans-serif; }

        :root {
            --pink:    #FF2D78;
            --yellow:  #FFD60A;
            --purple:  #BF5AF2;
            --green:   #34D399;
            --dark:    #080808;
            --dark2:   #111111;
            --dark3:   #1A1A1A;
            --dark4:   #242424;
            --border:  rgba(255,255,255,0.08);
            --text:    #F0F0F0;
            --muted:   #888888;
        }

        html { scroll-behavior: smooth; }

        ::-webkit-scrollbar { width: 5px; }
        ::-webkit-scrollbar-track { background: #111; }
        ::-webkit-scrollbar-thumb { background: #FF2D78; border-radius: 10px; }

        body { background: var(--dark); color: var(--text); }

        /* ── Navbar ── */
        .navbar {
            background: rgba(8,8,8,0.9);
            backdrop-filter: blur(20px);
            -webkit-backdrop-filter: blur(20px);
            border-bottom: 1px solid var(--border);
        }

        /* ── Gradientes de fundo ── */
        .hero-bg {
            background:
                radial-gradient(ellipse 70% 50% at 50% -5%, rgba(255,45,120,0.18) 0%, transparent 60%),
                radial-gradient(ellipse 50% 40% at 85% 30%, rgba(191,90,242,0.12) 0%, transparent 50%),
                radial-gradient(ellipse 40% 30% at 10% 60%, rgba(255,214,10,0.08) 0%, transparent 50%),
                #080808;
        }

        /* ── Texto gradiente ── */
        .pink-text {
            background: linear-gradient(135deg, #FF2D78 0%, #FF6B9D 50%, #FFD60A 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }
        .purple-text {
            background: linear-gradient(135deg, #BF5AF2 0%, #FF2D78 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }

        /* ── Botão principal ── */
        .btn-primary {
            background: linear-gradient(135deg, #FF2D78 0%, #FF6B9D 100%);
            box-shadow: 0 4px 30px rgba(255,45,120,0.4), 0 1px 3px rgba(0,0,0,0.3);
            transition: transform 0.2s, box-shadow 0.2s;
        }
        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 40px rgba(255,45,120,0.55), 0 2px 6px rgba(0,0,0,0.3);
        }
        .btn-primary:active { transform: translateY(0); }

        .btn-outline {
            border: 1.5px solid rgba(255,255,255,0.2);
            color: var(--text);
            transition: all 0.2s;
        }
        .btn-outline:hover {
            background: rgba(255,255,255,0.06);
            border-color: rgba(255,255,255,0.35);
        }

        /* ── Cards ── */
        .card-dark {
            background: var(--dark3);
            border: 1px solid var(--border);
            transition: border-color 0.3s, transform 0.3s, box-shadow 0.3s;
        }
        .card-dark:hover {
            border-color: rgba(255,45,120,0.3);
            transform: translateY(-3px);
            box-shadow: 0 12px 40px rgba(0,0,0,0.3);
        }

        /* ── Genre cards ── */
        .genre-card {
            background: var(--dark3);
            border: 2px solid var(--border);
            cursor: pointer;
            transition: all 0.2s;
        }
        .genre-card:hover {
            border-color: rgba(255,45,120,0.4);
            background: rgba(255,45,120,0.05);
        }
        .genre-card.selected {
            border-color: #FF2D78;
            background: rgba(255,45,120,0.1);
            box-shadow: 0 0 0 3px rgba(255,45,120,0.2);
        }

        /* ── Textarea ── */
        .textarea-dark {
            background: var(--dark3);
            border: 1.5px solid var(--border);
            color: var(--text);
            resize: none;
            transition: border-color 0.2s, box-shadow 0.2s;
        }
        .textarea-dark::placeholder { color: var(--muted); }
        .textarea-dark:focus {
            outline: none;
            border-color: #FF2D78;
            box-shadow: 0 0 0 4px rgba(255,45,120,0.15);
        }

        /* ── Card de preço ── */
        .price-card {
            background: var(--dark3);
            border: 2px solid #FF2D78;
            box-shadow: 0 0 0 8px rgba(255,45,120,0.05), 0 24px 64px rgba(255,45,120,0.12);
        }

        /* ── Depoimentos ── */
        .testimonial-card {
            background: var(--dark3);
            border: 1px solid var(--border);
            transition: border-color 0.3s, transform 0.3s;
        }
        .testimonial-card:hover {
            border-color: rgba(255,45,120,0.25);
            transform: translateY(-3px);
        }

        /* ── FAQ ── */
        details summary { cursor: pointer; list-style: none; }
        details summary::-webkit-details-marker { display: none; }
        details[open] .chevron { transform: rotate(180deg); }
        .chevron { transition: transform 0.25s; }
        .faq-item {
            background: var(--dark3);
            border: 1px solid var(--border);
            transition: border-color 0.2s;
        }
        details[open].faq-item { border-color: rgba(255,45,120,0.3); }

        /* ── Step number ── */
        .step-number {
            background: linear-gradient(135deg, #FF2D78, #BF5AF2);
            color: #fff;
            font-family: 'Space Grotesk', sans-serif;
            font-weight: 800;
        }

        /* ── Divisor ── */
        .divider-pink {
            height: 1px;
            background: linear-gradient(to right, transparent, rgba(255,45,120,0.4), transparent);
        }

        /* ── Animações de entrada ── */
        @keyframes fadeUp {
            from { opacity: 0; transform: translateY(24px); }
            to   { opacity: 1; transform: translateY(0); }
        }
        .anim-1 { animation: fadeUp 0.7s ease forwards; }
        .anim-2 { animation: fadeUp 0.7s ease 0.1s forwards; opacity: 0; }
        .anim-3 { animation: fadeUp 0.7s ease 0.2s forwards; opacity: 0; }
        .anim-4 { animation: fadeUp 0.7s ease 0.3s forwards; opacity: 0; }

        /* ── Badge live ── */
        .badge-live::before {
            content: '';
            display: inline-block;
            width: 6px; height: 6px;
            background: #34D399;
            border-radius: 50%;
            margin-right: 6px;
            animation: blink 2s infinite;
            vertical-align: middle;
        }
        @keyframes blink {
            0%, 100% { opacity: 1; }
            50%       { opacity: 0.3; }
        }

        /* ── Ondas de som ── */
        @keyframes sw { 0%,100%{transform:scaleY(.4)} 50%{transform:scaleY(1)} }
        #sound-waves span {
            display:inline-block; width:3px; height:12px;
            border-radius:2px; background:#FF2D78;
            animation:sw .8s ease-in-out infinite;
            transform-origin:bottom;
        }
        #sound-waves span:nth-child(2){animation-delay:.15s}
        #sound-waves span:nth-child(3){animation-delay:.3s}

        /* ── Sugestão pills ── */
        .tag-pill {
            background: var(--dark4);
            border: 1px solid var(--border);
            color: var(--muted);
            transition: all 0.15s;
        }
        .tag-pill:hover, .tag-pill.active {
            background: rgba(255,45,120,0.1);
            border-color: rgba(255,45,120,0.4);
            color: #FF2D78;
        }

        /* ── Floats animados ── */
        @keyframes float1 { 0%,100%{transform:translateY(0) rotate(-5deg)} 50%{transform:translateY(-14px) rotate(-3deg)} }
        @keyframes float2 { 0%,100%{transform:translateY(0) rotate(5deg)} 50%{transform:translateY(-10px) rotate(3deg)} }
        .float-1 { animation: float1 5s ease-in-out infinite; }
        .float-2 { animation: float2 6s ease-in-out 1s infinite; }

        /* ── Glow ornament ── */
        .glow-pink {
            background: radial-gradient(ellipse 70% 50% at 50% 50%, rgba(255,45,120,0.07) 0%, transparent 70%);
        }

        /* Scrollbar para o grid de gêneros no mobile */
        .genre-grid::-webkit-scrollbar { height: 4px; }
        .genre-grid::-webkit-scrollbar-thumb { background: #FF2D78; border-radius: 4px; }
    </style>
    <?php require_once __DIR__ . '/includes/gtag.php'; ?>
    <?php if (isset($_SESSION['ga_event']) && $_SESSION['ga_event'] === 'generate_lead'): ?>
    <script>
      gtag('event', 'generate_lead', { 'currency': 'BRL', 'value': <?= MUSICA_PRICE ?> });
    </script>
    <?php unset($_SESSION['ga_event']); endif; ?>
</head>
<body>

<!-- Áudio de fundo -->
<audio id="bg-audio" loop preload="auto" playsinline muted>
    <source src="assets/musica.mp3" type="audio/mpeg">
</audio>

<!-- ════════════════════════════════════════
     NAVBAR
════════════════════════════════════════ -->
<nav class="navbar fixed top-0 left-0 right-0 z-50 px-6 md:px-12 py-4 flex items-center justify-between">
    <a href="/" class="flex items-center gap-2.5">
        <img src="assets/logo.svg" alt="ZOA MUSIC" class="w-9 h-9 rounded-full object-cover">
        <span class="font-display text-xl font-bold tracking-tight">
            ZOA<span style="color:#FF2D78"> MUSIC</span>
        </span>
    </a>

    <div class="hidden md:flex items-center gap-8 text-sm font-medium" style="color:#888">
        <a href="#como-funciona" class="hover:text-white transition-colors">Como funciona</a>
        <a href="#depoimentos"   class="hover:text-white transition-colors">Histórias</a>
        <a href="#preco"         class="hover:text-white transition-colors">Preço</a>
    </div>

    <div class="flex items-center gap-3">
        <button id="btn-audio" onclick="toggleAudio()"
            class="flex items-center gap-2 px-3 py-2 rounded-full text-sm font-medium transition-all"
            style="background:rgba(255,45,120,0.08); border:1px solid rgba(255,45,120,0.2); color:#FF2D78;">
            <span id="audio-icon">🔇</span>
            <span id="audio-label" class="hidden md:inline">Som</span>
        </button>
        <button onclick="abrirHistorico()"
            class="hidden md:flex items-center gap-2 px-4 py-2 rounded-full text-sm font-medium transition-all"
            style="background:rgba(255,255,255,0.04); border:1px solid rgba(255,255,255,0.1); color:#aaa;"
            title="Minhas músicas">
            🎵 Minhas músicas
        </button>
        <a href="#criar"
           class="hidden md:inline-flex items-center gap-2 px-5 py-2.5 rounded-full text-sm font-bold text-white btn-primary">
            🎤 Criar agora
        </a>
    </div>
</nav>


<!-- ════════════════════════════════════════
     MODAL DE BOAS-VINDAS (MOBILE ONLY)
════════════════════════════════════════ -->
<div id="welcome-modal" class="fixed inset-0 z-[100] hidden items-center justify-center p-6" style="background:#080808;">
    <div class="max-w-sm w-full text-center">
        <div class="flex justify-center mb-8">
            <img src="assets/logo.svg" alt="ZOA MUSIC" class="w-24 h-24 rounded-full">
        </div>
        <h2 class="font-display text-4xl font-bold mb-4 pink-text">ZOA MUSIC</h2>
        <p class="text-lg font-bold mb-3" style="color:#F0F0F0;">
            Zoou? Vira música! 🎤
        </p>
        <p class="text-sm leading-relaxed mb-8" style="color:#888;">
            Crie músicas de zoeira personalizadas para seus amigos com IA. Funk, Sertanejo, MPB e muito mais.
        </p>
        <div class="rounded-2xl p-5 mb-10 text-sm" style="background:rgba(255,45,120,0.08); border:1px solid rgba(255,45,120,0.2); color:#FF6B9D;">
            "Meu amigo chegou 1h atrasado e ganhou uma música de funk dedicada. Não ria mais." 😂
        </div>
        <button onclick="closeWelcomeModal()"
                class="btn-primary w-full py-5 rounded-2xl text-white font-bold text-xl tracking-wide">
            🤣 Entrar e Zoar
        </button>
        <p class="text-xs mt-6" style="color:#555;">Toque para iniciar</p>
    </div>
</div>


<!-- ════════════════════════════════════════
     HERO
════════════════════════════════════════ -->
<section class="hero-bg min-h-screen flex flex-col items-center justify-center px-6 pt-28 pb-20 text-center relative overflow-hidden">

    <!-- Emojis flutuantes decorativos -->
    <div class="float-1 absolute top-28 left-10 opacity-20 select-none hidden lg:block" style="font-size:5rem">😂</div>
    <div class="float-2 absolute top-36 right-12 opacity-15 select-none hidden lg:block" style="font-size:4rem">🎤</div>
    <div class="float-1 absolute bottom-24 right-20 opacity-10 select-none hidden lg:block" style="font-size:3rem">🎵</div>
    <div class="float-2 absolute bottom-32 left-16 opacity-10 select-none hidden lg:block" style="font-size:3.5rem">🤣</div>

    <div class="relative z-10 max-w-4xl mx-auto">

        <!-- Badge -->
        <div class="anim-1 inline-flex items-center gap-2 px-5 py-2 rounded-full mb-8 text-xs font-bold"
             style="background:rgba(255,45,120,0.1); border:1px solid rgba(255,45,120,0.3); color:#FF6B9D;">
            <span class="badge-live">IA cria em minutos</span>
        </div>

        <!-- Headline -->
        <h1 class="anim-2 font-display font-bold leading-[1.05] mb-6"
            style="font-size:clamp(2.8rem,7vw,5.5rem); color:#F0F0F0;">
            Zoou?<br><span class="pink-text">Vira música!</span> 🎤
        </h1>

        <!-- Subtítulo -->
        <p class="anim-3 text-lg md:text-xl leading-relaxed mb-3 max-w-2xl mx-auto" style="color:#aaa;">
            A IA escreve a <strong style="color:#F0F0F0;">letra</strong> e compõe a <strong style="color:#F0F0F0;">melodia</strong> no estilo que você escolher.<br>
            Você só manda no grupo. Eles nunca mais te perdoam.
        </p>

        <p class="anim-3 font-display italic text-base mb-10" style="color:#FF6B9D;">
            Funk, Sertanejo, MPB, Forró, Pagode, Trap e muito mais 🎶
        </p>

        <!-- CTAs -->
        <div class="anim-4 flex flex-col sm:flex-row items-center justify-center gap-4 mb-14">
            <a href="#criar" class="btn-primary px-10 py-4 rounded-full text-white font-bold text-lg tracking-wide w-full sm:w-auto">
                🤣 CRIAR MINHA MÚSICA DE ZOEIRA
            </a>
            <a href="#como-funciona"
               class="btn-outline px-9 py-4 rounded-full font-medium text-sm w-full sm:w-auto text-center">
                Como funciona ↓
            </a>
        </div>

        <!-- Social proof -->
        <div class="anim-4 inline-flex items-center gap-6 md:gap-8 px-8 py-5 rounded-2xl"
             style="background:rgba(255,255,255,0.04); border:1px solid rgba(255,255,255,0.08);">
            <div class="text-center">
                <p class="font-display text-3xl font-bold" style="color:#F0F0F0">+800</p>
                <p class="text-xs mt-0.5" style="color:#666">amigos zoados</p>
            </div>
            <div class="w-px h-10" style="background:rgba(255,255,255,0.1)"></div>
            <div class="text-center">
                <p class="font-display text-3xl font-bold" style="color:#F0F0F0">★ 4.9</p>
                <p class="text-xs mt-0.5" style="color:#666">avaliação média</p>
            </div>
            <div class="w-px h-10" style="background:rgba(255,255,255,0.1)"></div>
            <div class="text-center">
                <p class="font-display text-3xl font-bold" style="color:#F0F0F0">3 min</p>
                <p class="text-xs mt-0.5" style="color:#666">tempo médio</p>
            </div>
        </div>
    </div>
</section>


<!-- ════════════════════════════════════════
     COMO FUNCIONA
════════════════════════════════════════ -->
<section id="como-funciona" class="py-28 px-6" style="background:#0D0D0D;">
    <div class="max-w-5xl mx-auto">

        <div class="text-center mb-16">
            <p class="text-xs font-bold tracking-widest uppercase mb-3" style="color:#FF2D78">Como funciona</p>
            <h2 class="font-display text-5xl md:text-6xl font-bold mb-4">
                Simples como <span class="pink-text">humilhar</span> alguém
            </h2>
            <p class="text-base max-w-lg mx-auto" style="color:#888;">
                Em 4 passos, a zoeira do seu amigo vira uma música que vai tocar nos grupos pra sempre.
            </p>
        </div>

        <div class="grid md:grid-cols-4 gap-6">
            <?php $steps = [
                ['01', '💬', 'Conta a história', 'Descreva o que o amigo fez de errado. Quanto mais polêmico, melhor a música.'],
                ['02', '🎵', 'Escolhe o estilo', 'Funk, Sertanejo, MPB, Forró... escolha o ritmo certo pra devastar.'],
                ['03', '💳', 'Paga via PIX', 'Apenas R$ '.number_format(MUSICA_PRICE,2,',','.').' e a IA começa a trabalhar imediatamente.'],
                ['04', '🤣', 'Manda no grupo', 'Baixa o MP3 e compartilha. Eles ficam sem reação.'],
            ]; ?>
            <?php foreach ($steps as [$num, $icon, $title, $desc]): ?>
            <div class="card-dark rounded-2xl p-7 flex flex-col">
                <div class="step-number w-10 h-10 rounded-full flex items-center justify-center text-base font-bold mb-4">
                    <?= $num ?>
                </div>
                <span class="text-3xl mb-4"><?= $icon ?></span>
                <h3 class="font-semibold text-base mb-2" style="color:#F0F0F0"><?= $title ?></h3>
                <p class="text-sm leading-relaxed" style="color:#888"><?= $desc ?></p>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>


<!-- ════════════════════════════════════════
     FORM / CTA
════════════════════════════════════════ -->
<section id="criar" class="py-28 px-6 relative overflow-hidden" style="background:#080808;">
    <div class="absolute inset-0 glow-pink pointer-events-none"></div>

    <div class="relative z-10 max-w-2xl mx-auto">

        <div class="text-center mb-12">
            <p class="text-xs font-bold tracking-widest uppercase mb-3" style="color:#FF2D78">Crie agora</p>
            <h2 class="font-display text-5xl md:text-6xl font-bold mb-4">
                Qual o <span class="pink-text">crime</span> do seu amigo?
            </h2>
            <p class="text-base" style="color:#888;">
                Conta a história, escolhe o ritmo, paga o PIX. A IA faz o resto.
            </p>
        </div>

        <?php if ($erro): ?>
        <div class="flex items-start gap-3 px-5 py-4 rounded-2xl mb-6 text-sm"
             style="background:rgba(255,68,68,0.1); border:1px solid rgba(255,68,68,0.3); color:#ff6b6b;">
            <span>⚠</span> <?= htmlspecialchars($erro) ?>
        </div>
        <?php endif; ?>

        <div class="card-dark rounded-3xl p-8 md:p-10">
            <form method="POST" action="/" class="space-y-6">

                <!-- Seletor de gênero -->
                <div>
                    <p class="text-sm font-bold mb-4" style="color:#F0F0F0;">🎶 Escolha o estilo da humilhação:</p>
                    <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-5 gap-2" id="genre-grid">
                        <?php foreach ($generos as $key => $g): ?>
                        <div class="genre-card rounded-xl p-3 text-center select-none"
                             data-key="<?= $key ?>"
                             onclick="selecionarGenero('<?= $key ?>', this)">
                            <div class="text-2xl mb-1"><?= $g['emoji'] ?></div>
                            <div class="text-xs font-bold leading-tight" style="color:#F0F0F0"><?= $g['label'] ?></div>
                            <div class="text-[10px] mt-0.5" style="color:#666"><?= $g['sub'] ?></div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                    <input type="hidden" name="estilo_key" id="estilo_key" value="pop">
                </div>

                <!-- Textarea -->
                <div class="relative">
                    <label class="block text-sm font-bold mb-3" style="color:#F0F0F0;">📖 O que esse amigo fez?</label>
                    <textarea
                        name="inspiracao"
                        rows="5"
                        placeholder="Ex: Meu colega de trabalho rouba meu almoço da geladeira faz 6 meses, chega sempre atrasado e ainda culpa o trânsito sendo que mora a 10 minutos. Quero um funk destruindo a moral dele..."
                        class="textarea-dark w-full rounded-2xl px-6 py-5 text-base leading-relaxed"
                        required minlength="10"
                    ><?= htmlspecialchars($_POST['inspiracao'] ?? '') ?></textarea>
                    <span class="absolute bottom-4 right-5 text-xs" style="color:#555">mín. 10 caracteres</span>
                </div>

                <!-- Sugestões de situação -->
                <div>
                    <p class="text-xs font-semibold mb-2.5" style="color:#666">Ideias de zoeira:</p>
                    <div class="flex flex-wrap gap-2">
                        <?php foreach (['Chegou atrasado','Comeu minha comida','Ronca demais','Ex que vacilou','Chefe chato','Sogra intrometida','Ficou devendo','Sumiu no grupo','Esqueceu aniversário','Perdeu a aposta'] as $tag): ?>
                        <button type="button" onclick="aplicarTag(this)" class="tag-pill px-3 py-1.5 rounded-full text-xs font-medium">
                            <?= $tag ?>
                        </button>
                        <?php endforeach; ?>
                    </div>
                </div>

                <!-- Cloudflare Turnstile -->
                <?php if (!empty(CF_TURNSTILE_SITE_KEY)): ?>
                <div class="flex justify-center mb-4">
                    <div class="cf-turnstile" data-sitekey="<?= CF_TURNSTILE_SITE_KEY ?>" data-theme="dark"></div>
                </div>
                <?php endif; ?>

                <button type="submit" class="btn-primary w-full py-5 rounded-2xl text-white font-bold text-xl tracking-wide">
                    🤣 &nbsp;CRIAR MINHA MÚSICA DE ZOEIRA
                </button>

                <p class="text-center text-xs" style="color:#555;">
                    Pagamento seguro via PIX —
                    <strong style="color:#888;">R$ <?= number_format(MUSICA_PRICE, 2, ',', '.') ?></strong>
                    por música · Aprovação em segundos.
                </p>
            </form>
        </div>
    </div>
</section>


<!-- ════════════════════════════════════════
     PREÇO
════════════════════════════════════════ -->
<section id="preco" class="py-28 px-6" style="background:#0D0D0D;">
    <div class="max-w-lg mx-auto">

        <div class="text-center mb-12">
            <p class="text-xs font-bold tracking-widest uppercase mb-3" style="color:#FF2D78">Investimento</p>
            <h2 class="font-display text-5xl font-bold">Quanto custa a <span class="pink-text">zoeira?</span></h2>
        </div>

        <div class="price-card rounded-3xl p-12 text-center">
            <div class="inline-flex items-center gap-2 px-5 py-2 rounded-full mb-8 text-xs font-bold tracking-widest uppercase"
                 style="background:rgba(255,45,120,0.1); border:1px solid rgba(255,45,120,0.3); color:#FF2D78;">
                🔥 PREÇO ESPECIAL DE LANÇAMENTO
            </div>

            <div class="flex items-center justify-center gap-3 mb-4">
                <span class="text-xl line-through" style="color:#555;">R$ 9,90</span>
                <span class="px-2.5 py-1 rounded-full text-xs font-bold text-white" style="background:#FF2D78;">-50%</span>
            </div>

            <div class="font-display font-bold leading-none mb-4" style="font-size:5.5rem; color:#F0F0F0;">
                R$&nbsp;<?= number_format(MUSICA_PRICE, 2, ',', '.') ?>
            </div>

            <p class="text-sm font-semibold mb-2" style="color:#FF2D78;">🎁 Oferta por tempo limitado</p>
            <p class="text-sm mb-10" style="color:#666;">por música · pagamento único via PIX</p>

            <ul class="space-y-4 text-left mb-10">
                <?php foreach ([
                    'Letra exclusiva escrita por IA com todo humor',
                    'Melodia gerada com voz e instrumentos reais',
                    'Escolha o ritmo: Funk, Sertanejo, MPB e mais 7',
                    'Download do arquivo MP3',
                    'Link público para compartilhar no grupo',
                    'Entrega em até 5 minutos',
                    'Música única — ninguém vai ter igual',
                ] as $f): ?>
                <li class="flex items-center gap-3 text-sm" style="color:#aaa;">
                    <span class="w-5 h-5 rounded-full flex items-center justify-center flex-shrink-0 text-white text-xs font-bold"
                          style="background:#FF2D78;">✓</span>
                    <?= $f ?>
                </li>
                <?php endforeach; ?>
            </ul>

            <a href="#criar" class="btn-primary block w-full py-4 rounded-xl text-white font-bold text-lg tracking-wide">
                Criar minha música agora
            </a>
        </div>
    </div>
</section>


<!-- ════════════════════════════════════════
     DEPOIMENTOS
════════════════════════════════════════ -->
<section id="depoimentos" class="py-28 px-6" style="background:#080808;">
    <div class="max-w-5xl mx-auto">

        <div class="text-center mb-16">
            <p class="text-xs font-bold tracking-widest uppercase mb-3" style="color:#FF2D78">Histórias reais</p>
            <h2 class="font-display text-5xl md:text-6xl font-bold mb-4">
                Amigos <span class="pink-text">zoados</span> 😂
            </h2>
            <p class="text-base max-w-lg mx-auto" style="color:#888;">
                Histórias de quem já usou o ZOA MUSIC pra arruinar (de forma hilária) o dia de alguém.
            </p>
        </div>

        <div class="grid md:grid-cols-3 gap-6">
            <?php foreach ([
                ['Carlos R.',   'São Paulo',        'CR', '😂', 'Meu amigo chegou 1h atrasado no meu aniversário. Criei um funk dedicado pra ele. O grupo inteiro adotou como tema do cara. Até hoje entra tocando.'],
                ['Pedro F.',    'Belo Horizonte',   'PF', '🤣', 'Fiz um sertanejo sobre minha sogra que interfere em tudo. Ela ouviu no almoço de domingo. Foi tenso. Foi épico. Voltaria a fazer.'],
                ['Ana S.',      'Rio de Janeiro',   'AS', '💀', 'Criei uma MPB sobre o colega que rouba meu almoço na empresa. O RH pediu pra eu não mais compartilhar. Tarde demais — já tinha 40 ouvintes.'],
                ['Juliana K.',  'Curitiba',         'JK', '🎉', 'Usei no aniversário do meu pai pra narrar toda a história da calvície em forró nordestino. Ele chorou de rir. Melhor presente da vida.'],
                ['Ricardo M.',  'Salvador',         'RM', '🤡', 'Mandei a música no grupo da firma contando que o chefe sai sempre mais cedo. Fui chamado no RH. Valeu demais.'],
                ['Fernanda L.', 'Fortaleza',        'FL', '🎵', 'Fiz um pagode sobre como meu marido ronca. Ele dormiu no sofá por 3 dias mas virou hit no grupo das mulheres do bairro.'],
            ] as [$nome, $cidade, $ini, $reaction, $texto]): ?>
            <div class="testimonial-card rounded-2xl p-6">
                <div class="flex items-center gap-3 mb-4">
                    <div class="w-10 h-10 rounded-full flex items-center justify-center text-sm font-bold text-white flex-shrink-0"
                         style="background:linear-gradient(135deg,#FF2D78,#BF5AF2)">
                        <?= $ini ?>
                    </div>
                    <div>
                        <p class="font-semibold text-sm" style="color:#F0F0F0"><?= $nome ?></p>
                        <p class="text-xs" style="color:#555"><?= $cidade ?></p>
                    </div>
                    <span class="ml-auto text-2xl"><?= $reaction ?></span>
                </div>
                <p class="text-xs font-bold mb-2" style="color:#FF2D78;">★★★★★</p>
                <p class="text-sm leading-relaxed" style="color:#aaa;">"<?= $texto ?>"</p>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>


<!-- ════════════════════════════════════════
     FAQ
════════════════════════════════════════ -->
<section class="py-24 px-6" style="background:#0D0D0D;">
    <div class="max-w-2xl mx-auto">

        <div class="text-center mb-14">
            <p class="text-xs font-bold tracking-widest uppercase mb-3" style="color:#FF2D78">Dúvidas</p>
            <h2 class="font-display text-5xl font-bold">Perguntas frequentes</h2>
        </div>

        <div class="space-y-3">
            <?php foreach ([
                ['Posso zoar qualquer pessoa?', 'Sim! Ex, amigos, colegas de trabalho, familiares, o chefe... Qualquer um que mereça uma homenagem musical especial. Mais detalhes = melhor música.'],
                ['A música vai soar profissional?', 'Sim. Usamos IA de ponta (a mesma tecnologia dos grandes apps musicais) para gerar letra e melodia com voz real e instrumentos. Vai parecer que você contratou uma banda.'],
                ['Posso escolher o estilo musical?', 'Com certeza! Funk Carioca (150bpm), Sertanejo Universitário, MPB, Forró, Pagode, Trap BR, Pisadinha, Rock Brasileiro, Axé e Pop Brasil. Qual dói mais no amigo?'],
                ['Quanto tempo leva?', 'Uns 3-5 minutinhos após a confirmação do PIX. Rápido o suficiente pra mandar no grupo antes do amigo saber que você tá tramando.'],
                ['A música é realmente única?', 'Totalmente. Ninguém no mundo vai ter a mesma letra ou melodia. A zoeira mais exclusiva que existe.'],
                ['Posso baixar e compartilhar?', 'Sim! Você recebe um link exclusivo para ouvir, pode baixar o MP3 e compartilhar direto no WhatsApp com um clique. O link dura pra sempre.'],
                ['E se a música não ficar boa?', 'Entre em contato pelo nosso SAC. Analisamos caso a caso. Mas dica: quanto mais detalhes e humor você colocar na descrição, melhor o resultado.'],
                ['Preciso criar uma conta?', 'Não! Você recebe o link da sua música direto e pode acessar a qualquer momento. Zero cadastro, zero senha.'],
            ] as [$q, $a]): ?>
            <details class="faq-item rounded-2xl overflow-hidden">
                <summary class="flex items-center justify-between px-6 py-5 font-semibold text-sm" style="color:#F0F0F0;">
                    <?= $q ?>
                    <svg class="chevron w-4 h-4 flex-shrink-0 ml-4" style="color:#FF2D78;" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7"/>
                    </svg>
                </summary>
                <div class="px-6 pb-5 text-sm leading-relaxed" style="color:#888; border-top:1px solid rgba(255,255,255,0.06); padding-top:14px">
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
<section class="py-28 px-6 text-center relative overflow-hidden" style="background:#080808;">
    <div class="absolute inset-0 glow-pink pointer-events-none"></div>
    <div class="relative z-10 max-w-2xl mx-auto">
        <div class="text-6xl mb-6">😈</div>
        <h2 class="font-display text-5xl md:text-6xl font-bold mb-5">
            Seu amigo merece<br>uma <span class="pink-text">música especial</span>
        </h2>
        <p class="text-base mb-10" style="color:#888;">
            A vingança mais criativa que já existiu. E você vai rir por semanas.
        </p>
        <a href="#criar" class="btn-primary inline-block px-12 py-5 rounded-full text-white font-bold text-xl tracking-wide">
            🤣 CRIAR MINHA MÚSICA DE ZOEIRA
        </a>
        <p class="text-xs mt-5" style="color:#555;">
            R$ <?= number_format(MUSICA_PRICE, 2, ',', '.') ?> · PIX · Entrega em minutos
        </p>
    </div>
</section>


<!-- ════════════════════════════════════════
     FOOTER
════════════════════════════════════════ -->
<footer class="px-6 py-16 text-center" style="background:#0A0A0A; border-top:1px solid rgba(255,255,255,0.06);">
    <div class="max-w-5xl mx-auto">
        <div class="grid md:grid-cols-3 gap-8 text-left mb-16 pb-12" style="border-bottom:1px solid rgba(255,255,255,0.06)">
            <div>
                <h4 class="text-[11px] font-bold uppercase tracking-[0.2em] mb-4" style="color:#FF2D78">ZOA MUSIC</h4>
                <p class="text-xs leading-relaxed" style="color:#555">Crie músicas de zoeira personalizadas com Inteligência Artificial. Funk, Sertanejo, MPB, Forró e mais — letra e melodia completas em minutos.</p>
            </div>
            <div>
                <h4 class="text-[11px] font-bold uppercase tracking-[0.2em] mb-4" style="color:#FF2D78">Estilos Disponíveis</h4>
                <p class="text-xs leading-relaxed" style="color:#555">Funk Carioca, Sertanejo Universitário, MPB, Forró Raiz, Pagode, Trap BR, Pisadinha, Rock Brasileiro, Axé Baiano, Pop Brasil.</p>
            </div>
            <div>
                <h4 class="text-[11px] font-bold uppercase tracking-[0.2em] mb-4" style="color:#FF2D78">IA de Ponta</h4>
                <p class="text-xs leading-relaxed" style="color:#555">Tecnologia de geração de música de última geração, adaptada para o humor brasileiro. Cada música é única e impossível de repetir.</p>
            </div>
        </div>

        <div class="flex items-center justify-center gap-2.5 mb-4">
            <img src="assets/logo.svg" alt="ZOA MUSIC" class="w-8 h-8 rounded-full">
            <span class="font-display font-bold text-base tracking-tight">
                ZOA<span style="color:#FF2D78"> MUSIC</span>
            </span>
        </div>
        <p class="font-display italic text-sm mb-4" style="color:#444;">"A melhor vingança tem melodia." 😂</p>
        <div class="flex items-center justify-center gap-6 text-xs mb-4" style="color:#555;">
            <a href="termos" class="hover:text-white transition-colors">Termos de Uso</a>
            <span>·</span>
            <a href="privacidade" class="hover:text-white transition-colors">Privacidade</a>
            <span>·</span>
            <button onclick="abrirSac()" class="hover:text-white transition-colors">Ajuda (SAC)</button>
        </div>
        <p class="text-xs" style="color:#333;">© <?= date('Y') ?> ZOA MUSIC — Todos os direitos reservados.</p>
    </div>
</footer>

<!-- Botão Instagram Flutuante -->
<a href="https://instagram.com/<?= INSTAGRAM_HANDLE ?>" target="_blank"
   style="position:fixed; bottom:24px; right:24px; z-index:150;
          background:linear-gradient(45deg,#f09433,#e6683c,#dc2743,#cc2366,#bc1888);
          color:#fff; width:56px; height:56px; border-radius:50%;
          display:flex; align-items:center; justify-content:center;
          box-shadow:0 10px 30px rgba(0,0,0,0.4); transition:transform 0.2s;"
   onmouseover="this.style.transform='scale(1.1)'"
   onmouseout="this.style.transform='scale(1)'">
    <svg class="w-7 h-7" fill="currentColor" viewBox="0 0 24 24">
        <path d="M12 2.163c3.204 0 3.584.012 4.85.07 3.252.148 4.771 1.691 4.919 4.919.058 1.265.069 1.645.069 4.849 0 3.205-.012 3.584-.069 4.849-.149 3.225-1.664 4.771-4.919 4.919-1.266.058-1.644.07-4.85.07-3.204 0-3.584-.012-4.849-.07-3.26-.149-4.771-1.699-4.919-4.92-.058-1.265-.07-1.644-.07-4.849 0-3.204.013-3.583.07-4.849.149-3.227 1.664-4.771 4.919-4.919 1.266-.057 1.645-.069 4.849-.069zm0-2.163c-3.259 0-3.667.014-4.947.072-4.358.2-6.78 2.618-6.98 6.98-.059 1.281-.073 1.689-.073 4.948 0 3.259.014 3.668.072 4.948.2 4.358 2.618 6.78 6.98 6.98 1.281.058 1.689.072 4.948.072 3.259 0 3.668-.014 4.948-.072 4.354-.2 6.782-2.618 6.979-6.98.059-1.28.073-1.689.073-4.948 0-3.259-.014-3.667-.072-4.947-.196-4.354-2.617-6.78-6.979-6.98-1.281-.059-1.69-.073-4.949-.073zm0 5.838c-3.403 0-6.162 2.759-6.162 6.162s2.759 6.163 6.162 6.163 6.162-2.759 6.162-6.163-2.759-6.162-6.162-6.162zm0 10.162c-2.209 0-4-1.79-4-4 0-2.209 1.791-4 4-4s4 1.791 4 4c0 2.21-1.791 4-4 4zm6.406-11.845c-.796 0-1.441.645-1.441 1.44s.645 1.44 1.441 1.44c.795 0 1.439-.645 1.439-1.44s-.644-1.44-1.439-1.44z"/>
    </svg>
</a>

<!-- Banner Cookies LGPD -->
<div id="cookie-banner" style="display:none; position:fixed; bottom:0; left:0; right:0; z-index:200;
     background:#111; border-top:2px solid #FF2D78; padding:20px 40px; box-shadow:0 -10px 40px rgba(0,0,0,0.3);">
    <div style="max-width:1200px; margin:0 auto; display:flex; flex-wrap:wrap; align-items:center; justify-content:space-between; gap:20px;">
        <p style="font-size:14px; color:#888; flex:1; min-width:280px;">
            Usamos cookies para personalizar sua experiência. Ao continuar, você concorda com nossos <a href="termos.php" style="color:#FF2D78">Termos</a> e <a href="privacidade.php" style="color:#FF2D78">Privacidade</a>.
        </p>
        <button onclick="aceitarCookies()" style="background:#FF2D78; color:#fff; border:none; padding:12px 32px; border-radius:12px; font-weight:700; cursor:pointer;">Aceitar</button>
    </div>
</div>


<!-- Modal SAC -->
<div id="sac-modal" style="display:none;position:fixed;inset:0;z-index:200;
     background:rgba(0,0,0,0.7);backdrop-filter:blur(4px);align-items:center;justify-content:center;padding:20px;"
     onclick="if(event.target===this)fecharSac()">
    <div style="background:#141414;border:1px solid rgba(255,255,255,0.1);border-radius:24px;width:100%;max-width:500px;
                padding:32px;box-shadow:0 20px 60px rgba(0,0,0,0.5);" onclick="event.stopPropagation()">
        <div style="display:flex;align-items:center;margin-bottom:24px;">
            <h3 style="font-size:22px;font-weight:700;color:#F0F0F0;margin:0;flex:1;">Precisa de ajuda?</h3>
            <button onclick="fecharSac()" style="border:none;background:rgba(255,255,255,0.08);border-radius:50%;
                    width:32px;height:32px;cursor:pointer;font-size:14px;color:#888;">✕</button>
        </div>
        <form id="sac-form" onsubmit="enviarSac(event)" style="display:flex;flex-direction:column;gap:16px;">
            <div>
                <label style="display:block;font-size:12px;font-weight:600;color:#888;margin-bottom:6px;">Seu Nome</label>
                <input type="text" name="nome" required style="width:100%;padding:12px 16px;border-radius:12px;border:1px solid rgba(255,255,255,0.1);background:#1E1E1E;color:#F0F0F0;outline:none;" placeholder="Como podemos te chamar?">
            </div>
            <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;">
                <div>
                    <label style="display:block;font-size:12px;font-weight:600;color:#888;margin-bottom:6px;">E-mail</label>
                    <input type="email" name="email" required style="width:100%;padding:12px 16px;border-radius:12px;border:1px solid rgba(255,255,255,0.1);background:#1E1E1E;color:#F0F0F0;outline:none;" placeholder="seu@email.com">
                </div>
                <div>
                    <label style="display:block;font-size:12px;font-weight:600;color:#888;margin-bottom:6px;">WhatsApp (opcional)</label>
                    <input type="tel" name="whatsapp" style="width:100%;padding:12px 16px;border-radius:12px;border:1px solid rgba(255,255,255,0.1);background:#1E1E1E;color:#F0F0F0;outline:none;" placeholder="(00) 00000-0000">
                </div>
            </div>
            <div>
                <label style="display:block;font-size:12px;font-weight:600;color:#888;margin-bottom:6px;">Assunto</label>
                <select name="assunto" required style="width:100%;padding:12px 16px;border-radius:12px;border:1px solid rgba(255,255,255,0.1);background:#1E1E1E;color:#F0F0F0;outline:none;">
                    <option value="">Selecione...</option>
                    <option value="Dúvida sobre o serviço">Dúvida sobre o serviço</option>
                    <option value="Problema no pagamento">Problema no pagamento</option>
                    <option value="Música não carregou">Música não carregou</option>
                    <option value="Elogio ou Sugestão">Elogio ou Sugestão</option>
                    <option value="Outro">Outro</option>
                </select>
            </div>
            <div>
                <label style="display:block;font-size:12px;font-weight:600;color:#888;margin-bottom:6px;">Mensagem</label>
                <textarea name="mensagem" required style="width:100%;padding:12px 16px;border-radius:12px;border:1px solid rgba(255,255,255,0.1);background:#1E1E1E;color:#F0F0F0;outline:none;min-height:100px;resize:none;" placeholder="Como podemos te ajudar?"></textarea>
            </div>
            <button type="submit" id="btn-sac" style="background:linear-gradient(135deg,#FF2D78,#BF5AF2);color:#fff;border:none;padding:16px;border-radius:14px;font-weight:700;cursor:pointer;margin-top:8px;">
                Enviar Mensagem
            </button>
        </form>
    </div>
</div>

<script>
    // ── Seletor de gênero ──
    function selecionarGenero(key, el) {
        document.querySelectorAll('.genre-card').forEach(c => c.classList.remove('selected'));
        el.classList.add('selected');
        document.getElementById('estilo_key').value = key;
    }
    // Seleciona Pop Brasil por padrão
    window.addEventListener('DOMContentLoaded', () => {
        const defaultCard = document.querySelector('[data-key="pop"]');
        if (defaultCard) selecionarGenero('pop', defaultCard);
    });

    // ── SAC ──
    function abrirSac() { document.getElementById('sac-modal').style.display = 'flex'; }
    function fecharSac() { document.getElementById('sac-modal').style.display = 'none'; }
    async function enviarSac(e) {
        e.preventDefault();
        const btn = document.getElementById('btn-sac');
        const form = document.getElementById('sac-form');
        btn.disabled = true;
        btn.textContent = 'Enviando...';
        try {
            const res = await fetch('api/enviar_sac.php', { method:'POST', body: new FormData(form) });
            const data = await res.json();
            if (data.ok) { alert(data.message); form.reset(); fecharSac(); }
            else { alert(data.error || 'Ocorreu um erro.'); }
        } catch { alert('Erro de conexão.'); }
        finally { btn.disabled = false; btn.textContent = 'Enviar Mensagem'; }
    }

    // ── Cookies ──
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
        ta.value = ta.value ? ta.value + ' — ' + tema : tema + ': ';
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
            window.scrollY > 20 ? '0 4px 24px rgba(0,0,0,0.3)' : 'none';
    });

    // ── Áudio de fundo ──
    const audio = document.getElementById('bg-audio');
    const btn   = document.getElementById('btn-audio');
    const icon  = document.getElementById('audio-icon');
    const lbl   = document.getElementById('audio-label');
    audio.volume = 0.3;

    function updateUI() {
        if (!audio.paused && !audio.muted) {
            icon.textContent = '🔊'; lbl.textContent = 'Som'; btn.style.background = 'rgba(255,45,120,0.15)';
        } else {
            icon.textContent = '🔇'; lbl.textContent = 'Som'; btn.style.background = 'rgba(255,45,120,0.08)';
        }
    }
    async function toggleAudio() {
        try {
            if (audio.paused) { audio.muted = false; await audio.play(); }
            else if (audio.muted) { audio.muted = false; }
            else { audio.pause(); }
            updateUI();
        } catch(err) {}
    }

    const isMobile = /Android|iPhone|iPad|iPod/i.test(navigator.userAgent);
    const modal = document.getElementById('welcome-modal');
    function closeWelcomeModal() {
        modal.classList.add('hidden'); modal.classList.remove('flex');
        unmuteAll();
    }
    const startAudio = async () => {
        try {
            if (isMobile) { modal.classList.remove('hidden'); modal.classList.add('flex'); }
            audio.muted = true;
            await audio.play();
            updateUI();
        } catch(err) {}
    };
    const unmuteAll = async () => {
        try {
            if (audio.muted || audio.paused) { audio.muted = false; await audio.play(); updateUI(); }
            document.removeEventListener('click', unmuteAll);
            document.removeEventListener('touchstart', unmuteAll);
        } catch(err) {}
    };
    window.addEventListener('load', startAudio);
    document.addEventListener('click', unmuteAll);
    document.addEventListener('touchstart', unmuteAll);
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
    function renderHistorico() {
        const list = document.getElementById('historico-list');
        let hist = [];
        try { hist = JSON.parse(localStorage.getItem('zoamusic_historico') || '[]'); } catch(e) {}
        if (!hist.length) {
            list.innerHTML = '<p style="text-align:center;color:#555;padding:32px 0;font-size:14px;">Você ainda não criou nenhuma música neste dispositivo.</p>';
            return;
        }
        list.innerHTML = hist.slice().reverse().map(m => {
            const isDone = m.status !== 'pendente';
            const statusTxt = isDone ? '' : ' <span style="font-size:10px;color:#FF2D78;background:rgba(255,45,120,0.1);padding:2px 6px;border-radius:4px;margin-left:4px;font-weight:bold;border:1px solid rgba(255,45,120,0.3);">AGUARDANDO</span>';
            return `<a href="ouvir.php?uid=${encodeURIComponent(m.uid)}" style="display:flex;align-items:center;
              gap:12px;padding:14px 0;border-bottom:1px solid rgba(255,255,255,0.06);text-decoration:none;">
              <div style="width:40px;height:40px;border-radius:50%;background:linear-gradient(135deg,#FF2D78,#BF5AF2);
                          display:flex;align-items:center;justify-content:center;color:#fff;font-size:16px;flex-shrink:0;">🎵</div>
              <div style="flex:1;min-width:0;">
                <p style="font-weight:600;color:#F0F0F0;margin:0;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;font-size:14px;">
                    ${m.titulo || 'Minha música'}${statusTxt}
                </p>
                <p style="font-size:11px;color:#555;margin:4px 0 0;">${m.data || ''}</p>
              </div>
              <span style="color:#FF2D78;font-size:20px;">›</span>
            </a>`;
        }).join('');
    }
</script>

<!-- Modal Histórico -->
<div id="historico-modal" style="display:none;position:fixed;inset:0;z-index:200;
     background:rgba(0,0,0,0.7);backdrop-filter:blur(4px);align-items:flex-end;justify-content:center;"
     onclick="if(event.target===this)fecharHistorico()">
    <div style="background:#141414;border:1px solid rgba(255,255,255,0.1);border-radius:24px 24px 0 0;width:100%;max-width:480px;
                padding:28px 24px;max-height:80vh;overflow-y:auto;
                box-shadow:0 -8px 40px rgba(0,0,0,0.4);" onclick="event.stopPropagation()">
        <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:16px;">
            <h3 style="font-size:18px;font-weight:700;color:#F0F0F0;margin:0;">🎵 Minhas músicas</h3>
            <button onclick="fecharHistorico()" style="border:none;background:rgba(255,255,255,0.08);border-radius:50%;
                    width:30px;height:30px;cursor:pointer;font-size:14px;color:#888;">✕</button>
        </div>
        <p style="font-size:12px;color:#555;margin-bottom:16px;">Músicas criadas neste dispositivo.</p>
        <div id="historico-list"></div>
    </div>
</div>
</body>
</html>
