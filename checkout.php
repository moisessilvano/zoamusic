<?php
// ============================================================
// LOUVOR.NET - Checkout e PIX (Tela 2)
// ============================================================

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/includes/asaas.php';

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

// Se já foi processada, redireciona para o player
if ($musica['status'] === 'concluido') {
    header('Location: ouvir.php?uid=' . urlencode($uid));
    exit;
}

// Gera o PIX se ainda não foi gerado
$pix_gerado = !empty($musica['asaas_id']);
$pix_error = '';

if (!$pix_gerado) {
    try {
        $pix = asaas_criar_pix($uid);
        $stmt = db()->prepare(
            'UPDATE musicas SET asaas_id = ?, pix_key = ?, qr_code_img = ? WHERE id = ?'
        );
        $stmt->execute([$pix['asaas_id'], $pix['pix_key'], $pix['qr_code_image'], $uid]);
        $musica['asaas_id']   = $pix['asaas_id'];
        $musica['pix_key']    = $pix['pix_key'];
        $musica['qr_code_img'] = $pix['qr_code_image'];
        $pix_gerado = true;
    } catch (RuntimeException $e) {
        $pix_error = $e->getMessage();
        // Em modo sandbox, exibe dados fictícios para não travar o PoC
        $musica['pix_key']    = '00020126580014br.gov.bcb.pix0136' . $uid . '5204000053039865802BR5925LOUVOR NET MUSICAS LTDA6009SAO PAULO62070503***630456A8';
        $musica['qr_code_img'] = '';
    }
}

// Inspira
$inspiracao_preview = mb_substr($musica['inspiracao'], 0, 120);
if (mb_strlen($musica['inspiracao']) > 120) $inspiracao_preview .= '...';
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>LOUVOR.NET — Finalize seu Pagamento</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        body { background-color: #FDFDFD; color: #1E293B; }
        .hero-bg { background: linear-gradient(135deg, #1E293B 0%, #2D3F55 100%); }
        .btn-gold { background-color: #D4AF37; transition: background-color 0.2s; }
        .btn-gold:hover { background-color: #B8962E; }
        .gold-text { color: #D4AF37; }
        .pix-code {
            font-family: 'Courier New', monospace;
            word-break: break-all;
            font-size: 0.75rem;
        }
        @keyframes pulse-ring {
            0% { box-shadow: 0 0 0 0 rgba(212,175,55,0.4); }
            70% { box-shadow: 0 0 0 15px rgba(212,175,55,0); }
            100% { box-shadow: 0 0 0 0 rgba(212,175,55,0); }
        }
        .pulse-gold { animation: pulse-ring 2s infinite; }
    </style>
</head>
<body class="min-h-screen">

    <!-- NAVBAR -->
    <nav class="hero-bg py-4 px-6 flex items-center gap-3 shadow-lg">
        <svg class="w-7 h-7" viewBox="0 0 32 32" fill="none">
            <circle cx="16" cy="16" r="15" stroke="#D4AF37" stroke-width="2"/>
            <path d="M16 8 L18 13 L23 13 L19 16 L21 21 L16 18 L11 21 L13 16 L9 13 L14 13 Z" fill="#D4AF37"/>
        </svg>
        <a href="/" class="text-xl font-bold tracking-widest text-white">
            LOUVOR<span class="gold-text">.NET</span>
        </a>
    </nav>

    <!-- STEPS -->
    <div class="hero-bg py-6 px-6">
        <div class="max-w-2xl mx-auto flex items-center justify-center gap-0">
            <?php $steps = [['1','Inspiração','done'],['2','Pagamento','active'],['3','Criação','pending'],['4','Ouvir','pending']]; ?>
            <?php foreach ($steps as $i => [$num, $label, $state]): ?>
                <?php if ($i > 0): ?>
                <div class="flex-1 h-px <?= $state !== 'pending' ? 'bg-[#D4AF37]' : 'bg-gray-600' ?> mx-1"></div>
                <?php endif; ?>
                <div class="flex flex-col items-center gap-1">
                    <div class="w-9 h-9 rounded-full flex items-center justify-center font-bold text-sm
                        <?= $state === 'done' ? 'bg-[#D4AF37] text-white' : ($state === 'active' ? 'bg-white text-dark-text ring-2 ring-[#D4AF37]' : 'bg-gray-700 text-gray-400') ?>">
                        <?= $state === 'done' ? '✓' : $num ?>
                    </div>
                    <span class="text-xs <?= $state === 'active' ? 'text-white font-semibold' : 'text-gray-500' ?> hidden sm:block"><?= $label ?></span>
                </div>
            <?php endforeach; ?>
        </div>
    </div>

    <div class="max-w-2xl mx-auto px-6 py-10">
        <!-- HEADER -->
        <div class="text-center mb-8">
            <div class="text-5xl mb-3">🎵</div>
            <h1 class="text-3xl font-bold text-dark-text mb-2">
                Sua música está <span class="gold-text">quase pronta!</span>
            </h1>
            <p class="text-gray-500">Identificamos sua inspiração. Conclua o pagamento para iniciar a composição.</p>
        </div>

        <!-- INSPIRAÇÃO PREVIEW -->
        <div class="bg-blue-50 border border-blue-100 rounded-2xl p-5 mb-8">
            <p class="text-xs font-semibold text-blue-400 uppercase tracking-widest mb-2">Sua inspiração</p>
            <p class="text-dark-text italic">"<?= htmlspecialchars($inspiracao_preview) ?>"</p>
        </div>

        <?php if ($pix_error): ?>
        <div class="bg-yellow-50 border border-yellow-200 text-yellow-700 rounded-xl px-5 py-3 mb-6 text-sm">
            ⚠ Modo demonstração (sandbox): <?= htmlspecialchars($pix_error) ?>
        </div>
        <?php endif; ?>

        <!-- VALOR -->
        <div class="flex items-center justify-between bg-gray-50 rounded-2xl px-6 py-4 mb-6 border border-gray-100">
            <div>
                <p class="text-sm text-gray-500">Música Cristã Personalizada</p>
                <p class="font-bold text-dark-text">1x Composição com IA</p>
            </div>
            <div class="text-right">
                <p class="text-3xl font-bold text-dark-text">
                    R$ <?= number_format(MUSICA_PRICE, 2, ',', '.') ?>
                </p>
                <p class="text-xs text-green-600 font-semibold">PIX — Aprovação Imediata</p>
            </div>
        </div>

        <!-- QR CODE -->
        <div class="bg-white border-2 border-[#D4AF37] rounded-2xl p-6 mb-6 text-center">
            <p class="font-bold text-dark-text mb-4">Escaneie o QR Code com seu banco</p>

            <?php if (!empty($musica['qr_code_img'])): ?>
                <img src="data:image/png;base64,<?= htmlspecialchars($musica['qr_code_img']) ?>"
                     alt="QR Code PIX" class="w-48 h-48 mx-auto mb-4 rounded-xl">
            <?php else: ?>
                <!-- Placeholder QR Code para demonstração -->
                <div class="w-48 h-48 mx-auto mb-4 bg-gray-100 rounded-xl flex items-center justify-center border-2 border-dashed border-gray-300">
                    <div class="text-center">
                        <span class="text-4xl">📱</span>
                        <p class="text-xs text-gray-400 mt-2">QR Code PIX</p>
                        <p class="text-xs text-gray-400">(Configure Asaas)</p>
                    </div>
                </div>
            <?php endif; ?>

            <p class="text-sm text-gray-500 mb-3">Ou use o <strong>Pix Copia e Cola</strong>:</p>
            <div class="relative">
                <div class="pix-code bg-gray-50 border border-gray-200 rounded-xl px-4 py-3 text-left text-gray-600 max-h-24 overflow-y-auto" id="pix-code">
                    <?= htmlspecialchars($musica['pix_key'] ?? '(Configure sua chave Asaas)') ?>
                </div>
                <button onclick="copiarPix()" id="btn-copy"
                    class="mt-3 w-full py-2.5 rounded-xl border-2 border-[#D4AF37] text-[#D4AF37] font-semibold text-sm hover:bg-[#D4AF37] hover:text-white transition-all">
                    📋 Copiar código PIX
                </button>
            </div>
        </div>

        <!-- INSTRUÇÕES -->
        <div class="bg-gray-50 rounded-2xl p-5 mb-8">
            <p class="font-semibold text-dark-text mb-3">Como pagar:</p>
            <ol class="space-y-2 text-sm text-gray-600">
                <li class="flex gap-2"><span class="font-bold text-[#D4AF37]">1.</span> Abra o app do seu banco</li>
                <li class="flex gap-2"><span class="font-bold text-[#D4AF37]">2.</span> Escolha pagar via PIX</li>
                <li class="flex gap-2"><span class="font-bold text-[#D4AF37]">3.</span> Escaneie o QR Code ou cole o código</li>
                <li class="flex gap-2"><span class="font-bold text-[#D4AF37]">4.</span> Confirme o valor R$ <?= number_format(MUSICA_PRICE, 2, ',', '.') ?> e pague</li>
                <li class="flex gap-2"><span class="font-bold text-[#D4AF37]">5.</span> Clique em "Já Paguei" abaixo</li>
            </ol>
        </div>

        <!-- BOTÃO JÁ PAGUEI -->
        <form method="POST" action="confirmar_pagamento.php">
            <input type="hidden" name="uid" value="<?= htmlspecialchars($uid) ?>">
            <button type="submit"
                class="pulse-gold btn-gold w-full py-5 rounded-2xl text-white font-bold text-xl tracking-wide shadow-xl">
                ✅ JÁ PAGUEI — CRIAR MINHA MÚSICA
            </button>
        </form>

        <p class="text-center text-xs text-gray-400 mt-4">
            Após confirmar, a LOUVOR.NET irá compor sua música exclusiva em minutos.
        </p>
    </div>

    <script>
        function copiarPix() {
            const code = document.getElementById('pix-code').textContent.trim();
            navigator.clipboard.writeText(code).then(() => {
                const btn = document.getElementById('btn-copy');
                btn.textContent = '✓ Copiado!';
                btn.classList.add('bg-green-500', 'border-green-500', 'text-white');
                setTimeout(() => {
                    btn.textContent = '📋 Copiar código PIX';
                    btn.classList.remove('bg-green-500', 'border-green-500', 'text-white');
                }, 3000);
            });
        }
    </script>
</body>
</html>
