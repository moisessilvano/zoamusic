<?php
// ============================================================
// LOUVOR.NET - Checkout e PIX (Tela 2)
// ============================================================

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/includes/asaas.php';

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

if ($musica['status'] === 'processando') {
    header('Location: processando.php?uid=' . urlencode($uid)); exit;
}

$pix_error  = '';

// --- Processa o envio do Nome, E-mail e CPF ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['cpf'])) {
    $nome = trim($_POST['nome'] ?? '');
    $cpf = preg_replace('/\D/', '', $_POST['cpf']);
    $email = filter_var(trim(strtolower($_POST['email'] ?? '')), FILTER_VALIDATE_EMAIL);

    if (empty($nome)) {
        $pix_error = 'Por favor, informe seu nome.';
    } elseif (strlen($cpf) !== 11 && strlen($cpf) !== 14) {
        $pix_error = 'Por favor, informe um CPF ou CNPJ válido.';
    } elseif (!$email) {
        $pix_error = 'Por favor, informe um e-mail válido.';
    } else {
        try {
            $pix = asaas_criar_pix($uid, $cpf, $email, $nome);
            db()->prepare('UPDATE musicas SET nome=?, cpf=?, email=?, asaas_id=?, pix_key=?, qr_code_img=? WHERE id=?')
                ->execute([$nome, $cpf, $email, $pix['asaas_id'], $pix['pix_key'], $pix['qr_code_image'], $uid]);
            
            // Recarrega dados da música
            $musica['nome'] = $nome;
            $musica['cpf'] = $cpf;
            $musica['email'] = $email;
            $musica['asaas_id']    = $pix['asaas_id'];
            $musica['pix_key']     = $pix['pix_key'];
            $musica['qr_code_img'] = $pix['qr_code_image'];
        } catch (Exception $e) {
            $pix_error = 'Erro na API: ' . $e->getMessage();
        }
    }
}

$pix_gerado = !empty($musica['asaas_id']) && !empty($musica['qr_code_img']);

$inspiracao_preview = mb_strimwidth($musica['inspiracao'], 0, 120, '...');
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>LOUVOR.NET — Finalize seu Pagamento</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:wght@600;700&family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="icon" type="image/jpeg" href="assets/logo.jpeg">
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        *, body { font-family: 'Inter', system-ui, sans-serif; }
        .font-display { font-family: 'Cormorant Garamond', Georgia, serif; }
        body { background: #FDFBF5; color: #1C1917; }

        .navbar {
            background: rgba(255,255,255,0.9);
            backdrop-filter: blur(20px);
            border-bottom: 1px solid rgba(201,168,76,0.15);
        }
        .btn-gold {
            background: linear-gradient(135deg, #C9A84C 0%, #D4AF37 50%, #B8922A 100%);
            box-shadow: 0 4px 24px rgba(201,168,76,0.3);
            transition: transform 0.2s, box-shadow 0.2s;
        }
        .btn-gold:hover { transform: translateY(-2px); box-shadow: 0 8px 32px rgba(201,168,76,0.4); }

        .card { background:#fff; border:1px solid rgba(201,168,76,0.18); box-shadow:0 2px 20px rgba(0,0,0,0.04); }

        .pix-code { font-family:'Courier New',monospace; word-break:break-all; font-size:0.72rem; }

        @keyframes pulse-gold {
            0%   { box-shadow: 0 0 0 0 rgba(201,168,76,0.45); }
            70%  { box-shadow: 0 0 0 14px rgba(201,168,76,0); }
            100% { box-shadow: 0 0 0 0 rgba(201,168,76,0); }
        }
        .pulse-gold { animation: pulse-gold 2s infinite; }

        /* Steps */
        .step-done   { background:#C9A84C; color:#fff; }
        .step-active { background:#fff; color:#1C1917; border:2px solid #C9A84C; }
        .step-pending { background:#F0E8CC; color:#B8A07A; }
        .step-line-done   { background:#C9A84C; }
        .step-line-pending { background:#E8D9A8; }
    </style>
    <?php require_once __DIR__ . '/includes/gtag.php'; ?>
    <script>
      gtag('event', 'begin_checkout', {
        'currency': 'BRL',
        'value': <?= MUSICA_PRICE ?>,
        'items': [{
          'item_id': 'musica_personalizada',
          'item_name': 'Música Cristã Personalizada',
          'price': <?= MUSICA_PRICE ?>,
          'quantity': 1
        }]
      });
    </script>
</head>
<body class="min-h-screen">

<!-- NAVBAR -->
<nav class="navbar fixed top-0 left-0 right-0 z-50 px-6 py-4 flex items-center gap-3">
    <img src="assets/logo.jpeg" alt="LOUVOR.NET" class="w-8 h-8 rounded-full object-cover border border-[#C9A84C]/30">
    <a href="/" class="text-xl font-bold tracking-widest" style="color:#1C1917; letter-spacing:.12em">
        LOUVOR<span style="color:#C9A84C">.NET</span>
    </a>
</nav>

<!-- STEPS -->
<div class="pt-20 pb-6 px-6" style="background:#FDFBF5; border-bottom:1px solid rgba(201,168,76,0.12);">
    <div class="max-w-lg mx-auto flex items-center justify-center">
        <?php foreach ([['1','Inspiração','done'],['2','Pagamento','active'],['3','Criação','pending'],['4','Ouvir','pending']] as $i => [$num,$label,$state]): ?>
            <?php if ($i > 0): ?>
            <div class="flex-1 h-0.5 mx-1 <?= $state !== 'pending' ? 'step-line-done' : 'step-line-pending' ?>"></div>
            <?php endif; ?>
            <div class="flex flex-col items-center gap-1.5">
                <div class="w-9 h-9 rounded-full flex items-center justify-center font-bold text-sm step-<?= $state ?>">
                    <?= $state === 'done' ? '✓' : $num ?>
                </div>
                <span class="text-xs font-medium hidden sm:block"
                      style="color:<?= $state === 'active' ? '#8B6914' : ($state === 'done' ? '#C9A84C' : '#B8A07A') ?>">
                    <?= $label ?>
                </span>
            </div>
        <?php endforeach; ?>
    </div>
</div>

<div class="max-w-lg mx-auto px-6 py-10">

    <!-- HEADER -->
    <div class="text-center mb-8">
        <div class="text-5xl mb-4">🎵</div>
        <h1 class="font-display text-4xl font-bold mb-2" style="color:#1C1917;">
            Sua música está <span style="background:linear-gradient(135deg,#B8922A,#D4AF37,#E8CC80);-webkit-background-clip:text;-webkit-text-fill-color:transparent;background-clip:text;">quase pronta!</span>
        </h1>
        <p class="text-sm" style="color:#6B5B3E;">Conclua o pagamento para iniciar a composição.</p>
    </div>

    <!-- INSPIRAÇÃO -->
    <div class="rounded-2xl p-5 mb-6" style="background:#FBF6E9; border:1px solid #E8D9A8;">
        <p class="text-xs font-bold tracking-widest uppercase mb-2" style="color:#C9A84C;">Sua inspiração</p>
        <p class="text-sm italic" style="color:#44403C;">"<?= htmlspecialchars($inspiracao_preview) ?>"</p>
    </div>

    <?php if (ASAAS_ENV === 'sandbox'): ?>
    <div class="rounded-xl px-5 py-3 mb-5 text-sm" style="background:#FFFBEB; border:1px solid #FDE68A; color:#92400E;">
        ⚠ Modo demonstração (sandbox)
    </div>
    <?php endif; ?>

    <?php if ($pix_error && ASAAS_ENV === 'production'): ?>
    <div class="rounded-xl px-5 py-3 mb-5 text-sm" style="background:#FEF2F2; border:1px solid #FECACA; color:#B91C1C;">
        ⚠ Erro ao gerar PIX: <?= htmlspecialchars($pix_error) ?>
    </div>
    <?php endif; ?>

    <!-- VALOR -->
    <div class="card rounded-2xl px-6 py-4 mb-5 flex items-center justify-between">
        <div>
            <p class="text-xs" style="color:#8B7355;">Música Cristã Personalizada</p>
            <p class="font-semibold text-sm" style="color:#1C1917;">1x Composição com IA</p>
        </div>
        <div class="text-right">
            <p class="font-display text-3xl font-bold" style="color:#1C1917;">
                R$ <?= number_format(MUSICA_PRICE, 2, ',', '.') ?>
            </p>
            <p class="text-xs font-semibold" style="color:#16a34a;">PIX — Aprovação imediata</p>
        </div>
    </div>

    <?php if (!$pix_gerado): ?>
        <!-- FORMULÁRIO DE CPF -->
        <div class="card rounded-3xl p-8 mb-8 text-center border-2" style="border-color:#C9A84C;">
            <div class="w-16 h-16 bg-[#FDFBF5] rounded-full flex items-center justify-center mx-auto mb-4 border border-[#E8D9A8]">
                <span class="text-2xl">🔐</span>
            </div>
            <h2 class="text-lg font-bold mb-2">Quase lá!</h2>
            <p class="text-sm text-[#6B5B3E] mb-6">Para gerar seu QR Code de pagamento, precisamos do seu CPF ou CNPJ conforme exigido pelo Banco Central.</p>
            
            <form method="POST" class="space-y-4">
                <div class="text-left">
                    <label class="block text-xs font-bold uppercase tracking-wide text-[#8B7355] mb-1.5 ml-1">Seu Nome Completo</label>
                    <input type="text" name="nome" required placeholder="João da Silva"
                        class="w-full bg-[#FDFBF5] border-2 border-[#E8D9A8] rounded-2xl px-5 py-4 text-lg font-semibold focus:border-[#C9A84C] outline-none transition-all">
                </div>
                <div class="text-left">
                    <label class="block text-xs font-bold uppercase tracking-wide text-[#8B7355] mb-1.5 ml-1">Seu E-mail</label>
                    <input type="email" name="email" required placeholder="exemplo@gmail.com"
                        class="w-full bg-[#FDFBF5] border-2 border-[#E8D9A8] rounded-2xl px-5 py-4 text-lg font-semibold focus:border-[#C9A84C] outline-none transition-all">
                </div>
                <div class="text-left">
                    <label class="block text-xs font-bold uppercase tracking-wide text-[#8B7355] mb-1.5 ml-1">CPF ou CNPJ</label>
                    <input type="tel" name="cpf" id="input-cpf" required placeholder="000.000.000-00"
                        class="w-full bg-[#FDFBF5] border-2 border-[#E8D9A8] rounded-2xl px-5 py-4 text-lg font-semibold focus:border-[#C9A84C] outline-none transition-all">
                </div>
                <button type="submit" class="btn-gold w-full py-5 rounded-2xl text-white font-bold text-lg shadow-xl mt-2">
                    GERAR PIX AGORA
                </button>
            </form>
            
            <div class="mt-6 flex items-center justify-center gap-4 grayscale opacity-60">
                <img src="https://logodownload.org/wp-content/uploads/2020/02/pix-bc-logo.png" alt="Pix" class="h-4">
                <div class="h-4 w-px bg-slate-300"></div>
                <p class="text-[10px] font-bold text-slate-500 uppercase tracking-widest">Ambiente 100% Seguro</p>
            </div>
        </div>

        <script>
            // Máscara de CPF/CNPJ
            const cpfInput = document.getElementById('input-cpf');
            cpfInput.addEventListener('input', (e) => {
                let v = e.target.value.replace(/\D/g, "");
                if (v.length <= 11) {
                    v = v.replace(/(\d{3})(\d)/, "$1.$2");
                    v = v.replace(/(\d{3})(\d)/, "$1.$2");
                    v = v.replace(/(\d{3})(\d{1,2})$/, "$1-$2");
                } else {
                    v = v.replace(/^(\d{2})(\d)/, "$1.$2");
                    v = v.replace(/^(\d{2})\.(\d{3})(\d)/, "$1.$2.$3");
                    v = v.replace(/\.(\d{3})(\d)/, ".$1/$2");
                    v = v.replace(/(\d{4})(\d)/, "$1-$2");
                }
                e.target.value = v;
            });
        </script>

    <?php else: ?>
        <!-- QR CODE (Já gerado) -->
        <div class="card rounded-2xl p-6 mb-5 text-center" style="border:2px solid #E8D9A8;">
            <p class="font-semibold text-sm mb-5" style="color:#1C1917;">Escaneie o QR Code com seu banco</p>

            <img src="data:image/png;base64,<?= htmlspecialchars($musica['qr_code_img']) ?>"
                 alt="QR Code PIX" class="w-48 h-48 mx-auto mb-5 rounded-xl shadow-sm border-4 border-white">

            <p class="text-sm mb-3" style="color:#6B5B3E;">Ou use o <strong style="color:#1C1917;">Pix Copia e Cola</strong>:</p>
            <div class="pix-code rounded-xl px-4 py-3 text-left mb-3 max-h-20 overflow-y-auto"
                 style="background:#FDFBF5; border:1px solid #E8D9A8; color:#5C4A2A;" id="pix-code">
                <?= htmlspecialchars($musica['pix_key'] ?? '') ?>
            </div>
            <button onclick="copiarPix()" id="btn-copy"
                class="w-full py-3 rounded-xl font-semibold text-sm transition-all"
                style="border:1.5px solid #C9A84C; color:#8B6914; background:transparent;">
                📋 Copiar código PIX
            </button>
        </div>

        <!-- INSTRUÇÕES -->
        <div class="rounded-2xl p-5 mb-8" style="background:#fff; border:1px solid #F0E8CC;">
            <p class="font-semibold text-sm mb-3" style="color:#1C1917;">Como pagar:</p>
            <ol class="space-y-2 text-sm" style="color:#6B5B3E;">
                <?php foreach ([
                    'Abra o app do seu banco',
                    'Escolha pagar via PIX',
                    'Escaneie o QR Code ou cole o código',
                    'Confirme o valor R$ ' . number_format(MUSICA_PRICE,2,',','.') . ' e pague',
                    'Clique em "Já Paguei" abaixo',
                ] as $i => $passo): ?>
                <li class="flex gap-2.5 items-start">
                    <span class="font-bold flex-shrink-0" style="color:#C9A84C;"><?= $i+1 ?>.</span>
                    <?= $passo ?>
                </li>
                <?php endforeach; ?>
            </ol>
        </div>

        <!-- BOTÃO JÁ PAGUEI -->
        <form method="POST" action="confirmar_pagamento.php">
            <input type="hidden" name="uid" value="<?= htmlspecialchars($uid) ?>">
            <button type="submit" class="pulse-gold btn-gold w-full py-5 rounded-2xl text-white font-bold text-xl tracking-wide">
                ✅ JÁ PAGUEI — CRIAR MINHA MÚSICA
            </button>
        </form>
        <p class="text-center text-xs mt-4" style="color:#B8A07A;" id="lbl-esperando">
            ⏳ Aguardando pagamento... A página atualizará automaticamente.
        </p>

        <!-- Polling Automático de Status -->
        <script>
            let tentativas = 0;
            const maxTentativas = 120; // 10 minutos (5s x 120)

            const pollInterval = setInterval(async () => {
                if (tentativas >= maxTentativas) {
                    clearInterval(pollInterval);
                    return;
                }
                tentativas++;
                try {
                    let res = await fetch('api/check_status.php?uid=<?= urlencode($uid) ?>');
                    let data = await res.json();
                    
                    // Se o Webhook do Asaas ou algum cron mudar o status para algo diferente de 'aguardando_pagamento'
                    if (data.status === 'processando' || data.status === 'concluido') {
                        clearInterval(pollInterval);
                        document.getElementById('lbl-esperando').textContent = '✅ Pagamento confirmado! Redirecionando...';
                        document.getElementById('lbl-esperando').style.color = '#16a34a';
                        setTimeout(() => window.location.reload(), 1500);
                    }
                } catch(e) {
                    // ignora erros de rede e continua tentando
                }
            }, 5000); // 5 segundos
        </script>
    <?php endif; ?>
    <p class="text-center text-xs mt-4" style="color:#B8A07A;">
        Após confirmar, a LOUVOR.NET irá compor sua música exclusiva em minutos.
    </p>
</div>

<script>
function copiarPix() {
    navigator.clipboard.writeText(document.getElementById('pix-code').textContent.trim()).then(() => {
        const btn = document.getElementById('btn-copy');
        btn.textContent = '✓ Copiado!';
        btn.style.background = '#C9A84C';
        btn.style.color = '#fff';
        setTimeout(() => {
            btn.textContent = '📋 Copiar código PIX';
            btn.style.background = 'transparent';
            btn.style.color = '#8B6914';
        }, 3000);
    });
}
</script>

<?php require_once __DIR__ . '/includes/mini_player.php'; ?>
</body>
</html>

