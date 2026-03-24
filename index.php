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

    <!-- Google Fonts: Cormorant Garamond (headings) + Inter (body) -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:ital,wght@0,400;0,600;0,700;1,400;1,600&family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">

    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        gold: '#D4AF37',
                        'gold-dark': '#B8962E',
                        'gold-light': '#F0D980',
                        ink: '#0F172A',
                        'ink-light': '#1E293B',
                    },
                    fontFamily: {
                        display: ['"Cormorant Garamond"', 'Georgia', 'serif'],
                        body: ['Inter', 'system-ui', 'sans-serif'],
                    },
                }
            }
        }
    </script>

    <style>
        *, body { font-family: 'Inter', system-ui, sans-serif; }
        .font-display { font-family: 'Cormorant Garamond', Georgia, serif; }

        :root {
            --gold: #D4AF37;
            --gold-dark: #B8962E;
            --ink: #0F172A;
        }

        /* ── Scrollbar ── */
        ::-webkit-scrollbar { width: 6px; }
        ::-webkit-scrollbar-track { background: #0f172a; }
        ::-webkit-scrollbar-thumb { background: var(--gold); border-radius: 3px; }

        /* ── Hero ── */
        .hero-section {
            background: radial-gradient(ellipse at 60% 0%, #1e3a5f 0%, #0F172A 55%),
                        radial-gradient(ellipse at 10% 80%, #2a1a0f 0%, transparent 50%);
            background-color: #0F172A;
            position: relative;
            overflow: hidden;
        }
        .hero-section::before {
            content: '';
            position: absolute;
            inset: 0;
            background-image:
                radial-gradient(circle at 20% 50%, rgba(212,175,55,0.06) 0%, transparent 50%),
                radial-gradient(circle at 80% 20%, rgba(212,175,55,0.04) 0%, transparent 40%);
            pointer-events: none;
        }
        /* Linhas decorativas de luz */
        .light-beam {
            position: absolute;
            top: -10%;
            left: 50%;
            transform: translateX(-50%);
            width: 1px;
            height: 120%;
            background: linear-gradient(to bottom, transparent, rgba(212,175,55,0.3), transparent);
            filter: blur(0.5px);
        }
        .light-beam-2 {
            position: absolute;
            top: 0; left: 35%;
            width: 1px; height: 100%;
            background: linear-gradient(to bottom, rgba(212,175,55,0.15), transparent 60%);
        }
        .light-beam-3 {
            position: absolute;
            top: 0; left: 65%;
            width: 1px; height: 100%;
            background: linear-gradient(to bottom, rgba(212,175,55,0.1), transparent 50%);
        }

        /* ── Texto gradiente dourado ── */
        .gold-gradient {
            background: linear-gradient(135deg, #F0D980 0%, #D4AF37 50%, #B8962E 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }

        /* ── Botão principal ── */
        .btn-primary {
            background: linear-gradient(135deg, #D4AF37 0%, #B8962E 100%);
            position: relative;
            overflow: hidden;
            transition: transform 0.2s, box-shadow 0.2s;
        }
        .btn-primary::after {
            content: '';
            position: absolute;
            inset: 0;
            background: linear-gradient(135deg, rgba(255,255,255,0.15) 0%, transparent 60%);
        }
        .btn-primary:hover { transform: translateY(-2px); box-shadow: 0 12px 40px rgba(212,175,55,0.4); }
        .btn-primary:active { transform: translateY(0); }

        /* ── Textarea ── */
        .textarea-custom {
            background: rgba(255,255,255,0.04);
            border: 1.5px solid rgba(255,255,255,0.1);
            color: #fff;
            transition: border-color 0.2s, box-shadow 0.2s;
        }
        .textarea-custom::placeholder { color: rgba(255,255,255,0.3); }
        .textarea-custom:focus {
            outline: none;
            border-color: #D4AF37;
            box-shadow: 0 0 0 4px rgba(212,175,55,0.12);
            background: rgba(255,255,255,0.06);
        }

        /* ── Cards de como funciona ── */
        .step-card {
            background: linear-gradient(145deg, rgba(255,255,255,0.04), rgba(255,255,255,0.01));
            border: 1px solid rgba(255,255,255,0.08);
            transition: border-color 0.3s, transform 0.3s;
        }
        .step-card:hover { border-color: rgba(212,175,55,0.4); transform: translateY(-4px); }

        /* ── Seção de depoimentos ── */
        .testimonial-card {
            background: #fff;
            border: 1px solid #f1f5f9;
            transition: box-shadow 0.3s, transform 0.3s;
        }
        .testimonial-card:hover { box-shadow: 0 20px 60px rgba(0,0,0,0.08); transform: translateY(-3px); }

        /* ── Seção preço ── */
        .price-card {
            background: linear-gradient(145deg, #fff 0%, #fffdf5 100%);
            border: 2px solid #D4AF37;
            box-shadow: 0 0 0 6px rgba(212,175,55,0.08), 0 24px 60px rgba(212,175,55,0.15);
        }

        /* ── Divider com ícone ── */
        .section-divider {
            display: flex; align-items: center; gap: 16px;
        }
        .section-divider::before, .section-divider::after {
            content: ''; flex: 1;
            height: 1px;
            background: linear-gradient(to right, transparent, rgba(212,175,55,0.4), transparent);
        }

        /* ── FAQ ── */
        details summary { cursor: pointer; list-style: none; }
        details summary::-webkit-details-marker { display: none; }
        details[open] .chevron { transform: rotate(180deg); }
        .chevron { transition: transform 0.2s; }

        /* ── Animações ── */
        @keyframes fadeUp {
            from { opacity: 0; transform: translateY(24px); }
            to   { opacity: 1; transform: translateY(0); }
        }
        @keyframes shimmer {
            0%   { background-position: -200% center; }
            100% { background-position: 200% center; }
        }
        .anim-1 { animation: fadeUp 0.8s ease forwards; }
        .anim-2 { animation: fadeUp 0.8s ease 0.15s forwards; opacity: 0; }
        .anim-3 { animation: fadeUp 0.8s ease 0.3s forwards; opacity: 0; }
        .anim-4 { animation: fadeUp 0.8s ease 0.45s forwards; opacity: 0; }

        /* Nota pulsando */
        @keyframes float {
            0%, 100% { transform: translateY(0); }
            50% { transform: translateY(-8px); }
        }
        .float-note { animation: float 3s ease-in-out infinite; }
        .float-note-2 { animation: float 3s ease-in-out 1s infinite; }

        /* Botão de áudio ativo */
        #btn-audio.playing {
            background: rgba(212,175,55,0.15) !important;
            border-color: rgba(212,175,55,0.4) !important;
            color: #D4AF37 !important;
        }
        /* Ondas animadas quando tocando */
        @keyframes sound-wave {
            0%, 100% { transform: scaleY(0.4); }
            50%       { transform: scaleY(1); }
        }
        #sound-waves span {
            display: inline-block;
            width: 3px;
            height: 12px;
            border-radius: 2px;
            background: #D4AF37;
            animation: sound-wave 0.8s ease-in-out infinite;
            transform-origin: bottom;
        }
        #sound-waves span:nth-child(2) { animation-delay: 0.15s; }
        #sound-waves span:nth-child(3) { animation-delay: 0.3s; }

        /* Contador de músicas */
        .badge-live::before {
            content: '';
            display: inline-block;
            width: 7px; height: 7px;
            background: #4ade80;
            border-radius: 50%;
            margin-right: 6px;
            animation: pulse 2s infinite;
        }
        @keyframes pulse {
            0%, 100% { opacity: 1; }
            50% { opacity: 0.4; }
        }
    </style>
</head>
<body class="bg-[#0F172A]">

<!-- ═══════════════════════════════════════
     NAVBAR
═══════════════════════════════════════ -->
<nav class="fixed top-0 left-0 right-0 z-50 flex items-center justify-between px-6 md:px-12 py-4"
     style="background: rgba(15,23,42,0.85); backdrop-filter: blur(20px); border-bottom: 1px solid rgba(212,175,55,0.1);">
    <a href="/" class="flex items-center gap-2.5">
        <svg class="w-8 h-8 flex-shrink-0" viewBox="0 0 36 36" fill="none">
            <circle cx="18" cy="18" r="17" stroke="#D4AF37" stroke-width="1.5"/>
            <path d="M18 8 L20.5 15 L28 15 L22 19.5 L24.5 26.5 L18 22 L11.5 26.5 L14 19.5 L8 15 L15.5 15 Z" fill="#D4AF37"/>
        </svg>
        <span class="text-xl font-bold tracking-widest text-white" style="letter-spacing:.15em">
            LOUVOR<span style="color:#D4AF37">.NET</span>
        </span>
    </a>
    <div class="hidden md:flex items-center gap-8">
        <a href="#como-funciona" class="text-sm text-gray-400 hover:text-white transition-colors">Como funciona</a>
        <a href="#depoimentos" class="text-sm text-gray-400 hover:text-white transition-colors">Depoimentos</a>
        <a href="#preco" class="text-sm text-gray-400 hover:text-white transition-colors">Preço</a>
    </div>
    <div class="flex items-center gap-3">
        <!-- Botão mute/unmute -->
        <button id="btn-audio" onclick="toggleAudio()"
            title="Música de fundo"
            class="flex items-center gap-2 px-4 py-2.5 rounded-full text-sm font-medium transition-all"
            style="background:rgba(255,255,255,0.06); border:1px solid rgba(255,255,255,0.1); color:#94a3b8;">
            <span id="audio-icon">🔇</span>
            <span id="audio-label" class="hidden md:inline">Ativar som</span>
        </button>
        <a href="#criar" class="hidden md:inline-flex items-center gap-2 px-5 py-2.5 rounded-full text-sm font-semibold transition-all"
           style="background:rgba(212,175,55,0.15); border:1px solid rgba(212,175,55,0.4); color:#D4AF37;">
            ✦ Criar minha música
        </a>
    </div>
</nav>

<!-- Áudio de fundo em loop -->
<audio id="bg-audio" loop preload="none">
    <source src="assets/musica.mp3" type="audio/mpeg">
</audio>


<!-- ═══════════════════════════════════════
     HERO
═══════════════════════════════════════ -->
<section class="hero-section min-h-screen flex flex-col items-center justify-center px-6 pt-24 pb-16 text-center relative">
    <div class="light-beam"></div>
    <div class="light-beam-2"></div>
    <div class="light-beam-3"></div>

    <!-- Notas musicais flutuantes (decoração) -->
    <div class="absolute top-32 left-12 text-4xl opacity-10 float-note select-none hidden lg:block">♪</div>
    <div class="absolute top-48 right-16 text-6xl opacity-10 float-note-2 select-none hidden lg:block">♫</div>
    <div class="absolute bottom-32 left-20 text-3xl opacity-10 float-note select-none hidden lg:block">♩</div>
    <div class="absolute bottom-40 right-24 text-5xl opacity-10 float-note-2 select-none hidden lg:block">♬</div>

    <div class="relative z-10 max-w-4xl mx-auto">
        <!-- Badge -->
        <div class="anim-1 inline-flex items-center gap-2 px-4 py-2 rounded-full mb-8"
             style="background:rgba(212,175,55,0.1); border:1px solid rgba(212,175,55,0.25);">
            <span class="badge-live text-xs font-semibold text-emerald-400">Música gerada por IA em minutos</span>
        </div>

        <!-- Headline -->
        <h1 class="anim-2 font-display text-6xl md:text-7xl lg:text-8xl font-bold leading-[1.05] mb-6">
            <span class="text-white">Transforme sua</span><br>
            <span class="gold-gradient">dor em adoração</span>
        </h1>

        <!-- Subtítulo -->
        <p class="anim-3 text-lg md:text-xl text-gray-400 max-w-2xl mx-auto mb-4 leading-relaxed">
            Sua oração agora tem uma <strong class="text-gray-200 font-medium">melodia exclusiva</strong>.
            A IA escreve a letra, compõe a música e entrega uma canção que só existe para você.
        </p>

        <p class="anim-3 text-sm text-gray-400 mb-10 font-display italic">
            "Cantai ao Senhor um cântico novo" — Salmos 96:1
        </p>

        <!-- CTAs -->
        <div class="anim-4 flex flex-col sm:flex-row items-center justify-center gap-4">
            <a href="#criar"
               class="btn-primary px-8 py-4 rounded-full text-white font-bold text-lg tracking-wide shadow-2xl w-full sm:w-auto">
                🎵 CRIAR MEU LOUVOR AGORA
            </a>
            <a href="#como-funciona"
               class="px-8 py-4 rounded-full text-gray-300 font-medium text-sm border border-white/10 hover:border-white/25 transition-colors w-full sm:w-auto text-center">
                Ver como funciona ↓
            </a>
        </div>

        <!-- Social proof números -->
        <div class="anim-4 flex items-center justify-center gap-8 mt-14 pt-10"
             style="border-top:1px solid rgba(255,255,255,0.06)">
            <div class="text-center">
                <p class="font-display text-3xl font-bold text-white">+1.200</p>
                <p class="text-xs text-gray-400 mt-1">músicas criadas</p>
            </div>
            <div class="w-px h-10 bg-white/10"></div>
            <div class="text-center">
                <p class="font-display text-3xl font-bold text-white">★ 4.9</p>
                <p class="text-xs text-gray-400 mt-1">avaliação média</p>
            </div>
            <div class="w-px h-10 bg-white/10"></div>
            <div class="text-center">
                <p class="font-display text-3xl font-bold text-white">3 min</p>
                <p class="text-xs text-gray-400 mt-1">tempo médio</p>
            </div>
        </div>
    </div>
</section>


<!-- ═══════════════════════════════════════
     COMO FUNCIONA
═══════════════════════════════════════ -->
<section id="como-funciona" class="py-28 px-6" style="background:#0F172A;">
    <div class="max-w-5xl mx-auto">
        <div class="text-center mb-16">
            <p class="text-xs font-semibold tracking-widest uppercase mb-3" style="color:#D4AF37">Como funciona</p>
            <h2 class="font-display text-5xl md:text-6xl font-bold text-white mb-4">
                Simples como uma oração
            </h2>
            <p class="text-gray-300 max-w-xl mx-auto">
                Em 4 passos, sua história vira uma música que vai tocar corações.
            </p>
        </div>

        <div class="grid md:grid-cols-4 gap-px" style="background:rgba(255,255,255,0.06); border-radius:24px; overflow:hidden;">
            <?php $steps = [
                ['01', '✍️', 'Conte sua história', 'Escreva sobre um momento difícil, uma gratidão, um versículo ou uma oração.'],
                ['02', '💳', 'Pague via PIX', 'Pagamento instantâneo e seguro. Apenas R$ '.number_format(MUSICA_PRICE,2,',','.').' por composição.'],
                ['03', '🤖', 'IA compõe sua música', 'Claude escreve a letra. Suno gera a melodia. Tudo em minutos.'],
                ['04', '🎵', 'Ouça e compartilhe', 'Receba sua música única. Baixe o MP3 e compartilhe com quem você ama.'],
            ]; ?>
            <?php foreach ($steps as [$num, $icon, $title, $desc]): ?>
            <div class="step-card p-8 flex flex-col" style="background:#0F172A">
                <span class="font-display text-6xl font-bold mb-4" style="color:rgba(212,175,55,0.2)"><?= $num ?></span>
                <span class="text-3xl mb-4"><?= $icon ?></span>
                <h3 class="text-white font-semibold text-lg mb-2"><?= $title ?></h3>
                <p class="text-gray-300 text-sm leading-relaxed"><?= $desc ?></p>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>


<!-- ═══════════════════════════════════════
     FORM / CTA PRINCIPAL
═══════════════════════════════════════ -->
<section id="criar" class="py-28 px-6 relative overflow-hidden"
         style="background: radial-gradient(ellipse at center top, #1a2744 0%, #0F172A 70%);">

    <div class="absolute inset-0" style="background-image:radial-gradient(circle at 50% 0%, rgba(212,175,55,0.08) 0%, transparent 60%);"></div>

    <div class="relative z-10 max-w-2xl mx-auto">
        <div class="text-center mb-12">
            <div class="section-divider mb-8">
                <span class="font-display text-2xl" style="color:#D4AF37">✦</span>
            </div>
            <h2 class="font-display text-5xl md:text-6xl font-bold text-white mb-4">
                O que está no seu <span class="gold-gradient">coração?</span>
            </h2>
            <p class="text-gray-300">
                Compartilhe sua história, uma dificuldade, uma gratidão ou um versículo.
                A IA vai transformar em música.
            </p>
        </div>

        <?php if ($erro): ?>
        <div class="flex items-start gap-3 px-5 py-4 rounded-2xl mb-6 text-sm"
             style="background:rgba(239,68,68,0.1); border:1px solid rgba(239,68,68,0.3); color:#fca5a5;">
            <span class="flex-shrink-0 mt-0.5">⚠</span>
            <span><?= htmlspecialchars($erro) ?></span>
        </div>
        <?php endif; ?>

        <form method="POST" action="/" class="space-y-5">
            <!-- Textarea -->
            <div class="relative">
                <textarea
                    name="inspiracao"
                    rows="7"
                    placeholder="Ex: Estou passando por um momento difícil, mas o Salmo 23 me sustenta. Quero uma música que fale da paz de Deus mesmo na tempestade..."
                    class="textarea-custom w-full rounded-2xl px-6 py-5 text-base resize-none font-body leading-relaxed"
                    required minlength="10"
                ><?= htmlspecialchars($_POST['inspiracao'] ?? '') ?></textarea>
                <div class="absolute bottom-4 right-4 text-xs text-gray-400">mín. 10 caracteres</div>
            </div>

            <!-- Sugestões rápidas -->
            <div>
                <p class="text-xs text-gray-300 mb-2 font-medium">Sugestões de tema:</p>
                <div class="flex flex-wrap gap-2">
                    <?php $tags = ['Cura e restauração','Gratidão a Deus','Casamento e amor','Luto e consolo','Novo emprego','Batismo','Aniversário']; ?>
                    <?php foreach ($tags as $tag): ?>
                    <button type="button" onclick="aplicarTag(this)"
                        class="px-3 py-1.5 rounded-full text-xs font-medium transition-all"
                        style="background:rgba(255,255,255,0.05); border:1px solid rgba(255,255,255,0.1); color:#94a3b8;">
                        <?= $tag ?>
                    </button>
                    <?php endforeach; ?>
                </div>
            </div>


            <!-- Botão submit -->
            <button type="submit" class="btn-primary w-full py-5 rounded-2xl text-white font-bold text-xl tracking-wide">
                🎵 &nbsp;CRIAR MEU LOUVOR AGORA
            </button>

            <p class="text-center text-xs text-gray-400">
                Você será redirecionado para o pagamento seguro via PIX —
                <strong class="text-gray-200">R$ <?= number_format(MUSICA_PRICE, 2, ',', '.') ?></strong> por música.
                Aprovação em segundos.
            </p>
        </form>
    </div>
</section>


<!-- ═══════════════════════════════════════
     PREÇO
═══════════════════════════════════════ -->
<section id="preco" class="py-28 px-6" style="background:#070E1A;">
    <div class="max-w-lg mx-auto">
        <div class="text-center mb-12">
            <p class="text-xs font-semibold tracking-widest uppercase mb-3" style="color:#D4AF37">Investimento</p>
            <h2 class="font-display text-5xl font-bold text-white">Simples e justo</h2>
        </div>

        <div class="price-card rounded-3xl p-12 text-center">
            <div class="inline-flex items-center gap-2 px-4 py-1.5 rounded-full mb-8 text-xs font-bold tracking-widest uppercase"
                 style="background:rgba(212,175,55,0.15); color:#D4AF37;">
                ✦ PREÇO ESPECIAL DE LANÇAMENTO
            </div>
            <!-- Preço riscado (original) -->
            <div class="flex items-center justify-center gap-3 mb-3">
                <span class="text-2xl text-gray-400 line-through font-body">R$ 19,90</span>
                <span class="px-2.5 py-1 rounded-full text-xs font-bold text-white"
                      style="background:#ef4444">-50%</span>
            </div>
            <!-- Preço atual -->
            <div class="font-display font-bold leading-none mb-4" style="color:#0F172A; font-size:5.5rem">
                R$&nbsp;<?= number_format(MUSICA_PRICE, 2, ',', '.') ?>
            </div>
            <p class="text-sm font-semibold mb-2" style="color:#B8962E">🎁 Oferta por tempo limitado</p>
            <p class="text-gray-500 mb-10 text-sm">por música • pagamento único via PIX</p>

            <ul class="space-y-4 text-left mb-10">
                <?php $features = [
                    'Letra exclusiva escrita por IA (Claude)',
                    'Melodia gerada com voz e instrumentos (Suno)',
                    'Download do arquivo MP3',
                    'Link público para compartilhar',
                    'Entrega em até 5 minutos',
                    'Sua história, sua música — única no mundo',
                ]; ?>
                <?php foreach ($features as $f): ?>
                <li class="flex items-center gap-3 text-sm" style="color:#1E293B">
                    <span class="w-5 h-5 rounded-full flex items-center justify-center flex-shrink-0 text-white text-xs font-bold"
                          style="background:#D4AF37">✓</span>
                    <?= $f ?>
                </li>
                <?php endforeach; ?>
            </ul>

            <a href="#criar"
               class="btn-primary block w-full py-4 rounded-xl text-white font-bold text-lg tracking-wide text-center">
                Criar minha música agora
            </a>
        </div>
    </div>
</section>


<!-- ═══════════════════════════════════════
     DEPOIMENTOS
═══════════════════════════════════════ -->
<section id="depoimentos" class="py-28 px-6" style="background:#0A1221;">
    <div class="max-w-5xl mx-auto">
        <div class="text-center mb-16">
            <p class="text-xs font-semibold tracking-widest uppercase mb-3" style="color:#D4AF37">Depoimentos</p>
            <h2 class="font-display text-5xl md:text-6xl font-bold text-white mb-4">
                Vidas <span class="gold-gradient">tocadas</span>
            </h2>
            <p class="text-gray-300 max-w-lg mx-auto">
                Histórias reais de pessoas que transformaram momentos marcantes em música.
            </p>
        </div>

        <div class="grid md:grid-cols-3 gap-6 mb-8">
            <?php $depoimentos = [
                ['Ana R.', 'Rio de Janeiro', 'AnaR', 'Criei uma música para o aniversário da minha mãe com toda a história dela. Ela chorou do começo ao fim. Foi o presente mais especial que já dei.', '★★★★★'],
                ['Pr. Marcos S.', 'São Paulo', 'PrMS', 'Usei para criar um louvor exclusivo para o aniversário da nossa igreja. A congregação ficou de pé. Já encomendei a segunda.', '★★★★★'],
                ['Juliana F.', 'Belo Horizonte', 'JulF', 'Perdi meu pai e não conseguia expressar minha dor. A letra que a IA criou sobre minha história com ele me fez finalmente chorar e curar.', '★★★★★'],
                ['Reginaldo C.', 'Curitiba', 'RegC', 'Pedi uma música sobre meu casamento de 30 anos. Minha esposa ouviu e disse que foi o maior presente da vida dela. Valeu muito!', '★★★★★'],
                ['Simone A.', 'Fortaleza', 'SimA', 'Usei para celebrar o batismo do meu filho. A música ficou tão linda que o pastor pediu para usar no culto. Incrível!', '★★★★★'],
                ['Diácono Paulo', 'Brasília', 'DiaP', 'Ferramenta incrível para ministérios. Já criei 5 músicas para datas especiais da nossa célula. A qualidade do áudio surpreende.', '★★★★★'],
            ]; ?>
            <?php foreach ($depoimentos as [$nome, $cidade, $initials, $texto, $estrelas]): ?>
            <div class="testimonial-card rounded-2xl p-6">
                <div class="flex items-start justify-between mb-4">
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 rounded-full flex items-center justify-center text-sm font-bold text-white flex-shrink-0"
                             style="background:linear-gradient(135deg,#D4AF37,#B8962E)">
                            <?= substr($initials, 0, 2) ?>
                        </div>
                        <div>
                            <p class="font-semibold text-sm" style="color:#0F172A"><?= $nome ?></p>
                            <p class="text-xs text-gray-400"><?= $cidade ?></p>
                        </div>
                    </div>
                    <span class="text-xs" style="color:#D4AF37"><?= $estrelas ?></span>
                </div>
                <p class="text-gray-500 text-sm leading-relaxed italic">"<?= $texto ?>"</p>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>


<!-- ═══════════════════════════════════════
     FAQ
═══════════════════════════════════════ -->
<section class="py-24 px-6" style="background:#0F172A;">
    <div class="max-w-2xl mx-auto">
        <div class="text-center mb-14">
            <p class="text-xs font-semibold tracking-widest uppercase mb-3" style="color:#D4AF37">Dúvidas</p>
            <h2 class="font-display text-5xl font-bold text-white">Perguntas frequentes</h2>
        </div>

        <div class="space-y-3">
            <?php $faqs = [
                ['A música é realmente única?', 'Sim. Cada composição é gerada do zero a partir da sua história pessoal. Nenhuma outra pessoa no mundo terá exatamente a mesma letra ou melodia.'],
                ['Quanto tempo leva para ficar pronta?', 'Em média 3 a 5 minutos após a confirmação do PIX. Você acompanha em tempo real na tela de processamento.'],
                ['Posso usar no culto ou compartilhar online?', 'Sim! A música é sua. Você pode ouvir, baixar em MP3, compartilhar no WhatsApp, usar em cultos ou qualquer evento pessoal.'],
                ['O pagamento é seguro?', 'Totalmente. Usamos o Asaas, plataforma de pagamento certificada pelo Banco Central. O PIX é processado em segundos.'],
                ['E se a música não ficar como esperado?', 'Nossa IA é treinada especificamente para composição cristã. Mas se você não gostar, entre em contato — analisamos caso a caso.'],
                ['Preciso criar uma conta?', 'Não! Você recebe o link da sua música direto e pode acessá-lo a qualquer momento pelo link compartilhável.'],
            ]; ?>
            <?php foreach ($faqs as [$q, $a]): ?>
            <details class="group rounded-2xl overflow-hidden" style="border:1px solid rgba(255,255,255,0.07); background:rgba(255,255,255,0.02);">
                <summary class="flex items-center justify-between px-6 py-5 font-semibold text-white text-sm hover:text-[#D4AF37] transition-colors">
                    <?= $q ?>
                    <svg class="chevron w-4 h-4 flex-shrink-0 text-gray-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7"/>
                    </svg>
                </summary>
                <div class="px-6 pb-5 text-gray-300 text-sm leading-relaxed border-t" style="border-color:rgba(255,255,255,0.06); padding-top:16px">
                    <?= $a ?>
                </div>
            </details>
            <?php endforeach; ?>
        </div>
    </div>
</section>


<!-- ═══════════════════════════════════════
     CTA FINAL
═══════════════════════════════════════ -->
<section class="py-28 px-6 text-center relative overflow-hidden"
         style="background: radial-gradient(ellipse at center, #1a2744 0%, #070E1A 70%);">
    <div class="absolute inset-0" style="background-image:radial-gradient(circle at 50% 50%, rgba(212,175,55,0.07) 0%, transparent 60%);"></div>
    <div class="relative z-10 max-w-2xl mx-auto">
        <div class="text-6xl mb-6">🙏</div>
        <h2 class="font-display text-5xl md:text-6xl font-bold text-white mb-5">
            Sua melodia está<br><span class="gold-gradient">esperando por você</span>
        </h2>
        <p class="text-gray-400 mb-10 text-lg">
            "Tudo que tem fôlego louve ao Senhor." — Salmos 150:6
        </p>
        <a href="#criar"
           class="btn-primary inline-block px-10 py-5 rounded-full text-white font-bold text-xl tracking-wide shadow-2xl">
            🎵 CRIAR MEU LOUVOR AGORA
        </a>
        <p class="text-gray-400 text-xs mt-5">
            R$ <?= number_format(MUSICA_PRICE, 2, ',', '.') ?> · PIX · Entrega em minutos
        </p>
    </div>
</section>


<!-- ═══════════════════════════════════════
     FOOTER
═══════════════════════════════════════ -->
<footer class="px-6 py-10 text-center" style="background:#070E1A; border-top:1px solid rgba(255,255,255,0.05)">
    <div class="flex items-center justify-center gap-2.5 mb-3">
        <svg class="w-6 h-6" viewBox="0 0 36 36" fill="none">
            <circle cx="18" cy="18" r="17" stroke="#D4AF37" stroke-width="1.5"/>
            <path d="M18 8 L20.5 15 L28 15 L22 19.5 L24.5 26.5 L18 22 L11.5 26.5 L14 19.5 L8 15 L15.5 15 Z" fill="#D4AF37"/>
        </svg>
        <span class="font-bold tracking-widest text-white" style="letter-spacing:.15em">
            LOUVOR<span style="color:#D4AF37">.NET</span>
        </span>
    </div>
    <p class="text-gray-400 text-xs font-display italic mb-4">"Crie em mim, ó Deus, um coração puro." — Salmos 51:10</p>
    <div class="flex items-center justify-center gap-6 text-xs text-gray-500 mb-4">
        <a href="#" class="hover:text-gray-300 transition-colors">Termos de Uso</a>
        <span>·</span>
        <a href="#" class="hover:text-gray-300 transition-colors">Privacidade</a>
        <span>·</span>
        <a href="admin/login.php" class="hover:text-gray-300 transition-colors">Admin</a>
    </div>
    <p class="text-gray-500 text-xs">© <?= date('Y') ?> LOUVOR.NET — Todos os direitos reservados.</p>
</footer>

<script>
    // ── Sugestões de tema ──
    function aplicarTag(btn) {
        const ta = document.querySelector('textarea[name="inspiracao"]');
        const tema = btn.textContent.trim();
        ta.value = ta.value ? ta.value + '\n\nTema: ' + tema : 'Tema: ' + tema + '. ';
        ta.focus();
        btn.style.background = 'rgba(212,175,55,0.2)';
        btn.style.borderColor = 'rgba(212,175,55,0.5)';
        btn.style.color = '#D4AF37';
    }


    // ── Smooth scroll para âncoras ──
    document.querySelectorAll('a[href^="#"]').forEach(a => {
        a.addEventListener('click', e => {
            const target = document.querySelector(a.getAttribute('href'));
            if (target) {
                e.preventDefault();
                target.scrollIntoView({ behavior: 'smooth', block: 'start' });
            }
        });
    });

    // ── Navbar: adiciona sombra ao scrollar ──
    const nav = document.querySelector('nav');
    window.addEventListener('scroll', () => {
        nav.style.boxShadow = window.scrollY > 20
            ? '0 4px 30px rgba(0,0,0,0.4)'
            : 'none';
    });

    // ── Áudio de fundo ──
    const audio   = document.getElementById('bg-audio');
    const btn     = document.getElementById('btn-audio');
    const icon    = document.getElementById('audio-icon');
    const label   = document.getElementById('audio-label');
    let   playing = false;

    const waveHTML = '<span id="sound-waves" class="flex items-end gap-0.5 h-3">' +
                     '<span></span><span></span><span></span>' +
                     '</span>';

    function setPlaying() {
        playing = true;
        icon.innerHTML = waveHTML;
        label.textContent = 'Pausar som';
        btn.classList.add('playing');
    }
    function setPaused() {
        playing = false;
        icon.textContent = '🔇';
        label.textContent = 'Ativar som';
        btn.classList.remove('playing');
    }

    function toggleAudio() {
        if (!playing) {
            audio.volume = 0.35;
            audio.play().then(setPlaying).catch(() => { label.textContent = 'Clique novamente'; });
        } else {
            audio.pause();
            setPaused();
        }
    }

    // Tenta autoplay imediato ao carregar a página
    audio.volume = 0.35;
    audio.play().then(setPlaying).catch(() => {
        // Autoplay bloqueado (política do browser): aguarda o 1º clique na página
        const unlockAudio = () => {
            audio.play().then(() => { setPlaying(); document.removeEventListener('click', unlockAudio); }).catch(() => {});
        };
        document.addEventListener('click', unlockAudio);
    });
</script>
</body>
</html>
