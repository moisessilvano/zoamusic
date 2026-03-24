<?php
// ============================================================
// LOUVOR.NET - Landing Page
// ============================================================

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/db.php';

$erro = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $inspiracao = trim($_POST['inspiracao'] ?? '');
    if (strlen($inspiracao) < 10) {
        $erro = 'Por favor, compartilhe um pouco mais da sua história (mínimo 10 caracteres).';
    } else {
        $uid = uuid4();
        db()->prepare('INSERT INTO musicas (id, inspiracao, status) VALUES (?, ?, ?)')
            ->execute([$uid, $inspiracao, 'aguardando_pagamento']);
        header('Location: checkout.php?uid=' . urlencode($uid));
        exit;
    }
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>LOUVOR.NET — Música Cristã Criada por IA para Você</title>
    <meta name="description" content="Transforme sua história, oração ou versículo em uma música cristã exclusiva com letra e melodia geradas por Inteligência Artificial.">

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:ital,wght@0,400;0,600;0,700;1,400;1,600&family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">

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
</head>
<body>

<!-- Áudio de fundo -->
<audio id="bg-audio" loop preload="auto" playsinline>
    <source src="assets/musica.mp3" type="audio/mpeg">
    Seu navegador não suporta o elemento de áudio.
</audio>

<!-- ════════════════════════════════════════
     NAVBAR
════════════════════════════════════════ -->
<nav class="navbar fixed top-0 left-0 right-0 z-50 px-6 md:px-12 py-4 flex items-center justify-between">
    <a href="/" class="flex items-center gap-2.5">
        <!-- Logo asa/estrela -->
        <svg class="w-9 h-9" viewBox="0 0 40 40" fill="none">
            <circle cx="20" cy="20" r="19" stroke="#C9A84C" stroke-width="1.2" fill="rgba(201,168,76,0.07)"/>
            <path d="M20 9 L22.5 16.5 L30.5 16.5 L24 21.5 L26.5 29 L20 24 L13.5 29 L16 21.5 L9.5 16.5 L17.5 16.5 Z"
                  fill="url(#star-grad)"/>
            <defs>
                <linearGradient id="star-grad" x1="10" y1="9" x2="30" y2="29" gradientUnits="userSpaceOnUse">
                    <stop offset="0%" stop-color="#E8CC80"/>
                    <stop offset="100%" stop-color="#B8922A"/>
                </linearGradient>
            </defs>
        </svg>
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
        <a href="#criar"
           class="hidden md:inline-flex items-center gap-2 px-5 py-2.5 rounded-full text-sm font-semibold text-white btn-gold">
            ✦ Criar minha música
        </a>
    </div>
</nav>


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
                        rows="7"
                        placeholder="Ex: Estou passando por um momento difícil, mas o Salmo 23 me sustenta. Quero uma música que fale da paz de Deus mesmo na tempestade..."
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
                        <button type="button" onclick="aplicarTag(this)"
                            class="tag-pill px-3 py-1.5 rounded-full text-xs font-medium">
                            <?= $tag ?>
                        </button>
                        <?php endforeach; ?>
                    </div>
                </div>

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
        <svg class="w-6 h-6" viewBox="0 0 40 40" fill="none">
            <circle cx="20" cy="20" r="19" stroke="#C9A84C" stroke-width="1.2" fill="rgba(201,168,76,0.07)"/>
            <path d="M20 9 L22.5 16.5 L30.5 16.5 L24 21.5 L26.5 29 L20 24 L13.5 29 L16 21.5 L9.5 16.5 L17.5 16.5 Z" fill="#C9A84C"/>
        </svg>
        <span class="font-bold tracking-widest text-base" style="color:#1C1917; letter-spacing:.12em">
            LOUVOR<span style="color:#C9A84C">.NET</span>
        </span>
    </div>
    <p class="font-display italic text-sm mb-4" style="color:#A08060;">"Crie em mim, ó Deus, um coração puro." — Salmos 51:10</p>
    <div class="flex items-center justify-center gap-6 text-xs mb-4" style="color:#B8A07A;">
        <a href="#" class="hover:text-[#C9A84C] transition-colors">Termos de Uso</a>
        <span>·</span>
        <a href="#" class="hover:text-[#C9A84C] transition-colors">Privacidade</a>
        <span>·</span>
        <a href="admin/login.php" class="hover:text-[#C9A84C] transition-colors">Admin</a>
    </div>
    <p class="text-xs" style="color:#C8B99A;">© <?= date('Y') ?> LOUVOR.NET — Todos os direitos reservados.</p>
</footer>


<script>
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

    // Garante que o volume está baixo por padrão (conforto)
    audio.volume = 0.35;

    const waveHTML = `<span id="sound-waves" class="flex items-end gap-0.5 h-3">
        <span class="w-[3px] h-full bg-[#C9A84C] rounded-full animate-[sw_0.8s_ease-in-out_infinite]"></span>
        <span class="w-[3px] h-full bg-[#C9A84C] rounded-full animate-[sw_0.8s_ease-in-out_0.15s_infinite]"></span>
        <span class="w-[3px] h-full bg-[#C9A84C] rounded-full animate-[sw_0.8s_ease-in-out_0.3s_infinite]"></span>
    </span>`;

    function updateUI() {
        if (!audio.paused) {
            playing = true;
            icon.innerHTML = waveHTML;
            lbl.textContent = 'Pausar som';
            btn.classList.add('playing');
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
                // Safari e Chrome Mobile exigem que play() seja chamado diretamente em um evento de clique
                await audio.play();
            } else {
                audio.pause();
            }
            updateUI();
        } catch (err) {
            console.error("Erro ao reproduzir áudio:", err);
            lbl.textContent = 'Erro ao tocar';
        }
    }

    // Tenta tocar automaticamente (quase sempre falha em navegadores modernos sem clique)
    const autoPlay = async () => {
        try {
            await audio.play();
            updateUI();
            // Se funcionou, removemos os ouvintes de clique "desbloqueadores"
            document.removeEventListener('click', autoPlay);
            document.removeEventListener('touchstart', autoPlay);
        } catch (err) {
            // Falhou (esperado). Aguardando o primeiro clique do usuário no site para liberar.
            console.log("Autoplay bloqueado pelo navegador. Aguardando interação...");
        }
    };

    // Tenta rodar assim que carregar
    window.addEventListener('load', autoPlay);

    // Se o autoplay falhar, qualquer clique/toque no site tenta iniciar o áudio (padrão de "unlock")
    document.addEventListener('click', autoPlay, { once: true });
    document.addEventListener('touchstart', autoPlay, { once: true });

    // Escuta mudanças de estado do próprio elemento áudio
    audio.onplay = updateUI;
    audio.onpause = updateUI;
</script>
</body>
</html>
