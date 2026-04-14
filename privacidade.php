<?php
require_once __DIR__ . '/config.php';
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Política de Privacidade — LOUVOR.NET</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:wght@600;700&family=Inter:wght@400;500;600&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Inter', sans-serif; background: #FDFBF5; color: #1C1917; }
        .font-display { font-family: 'Cormorant Garamond', serif; }
    </style>
    <?php require_once __DIR__ . '/includes/gtag.php'; ?>
</head>
<body class="min-h-screen py-12 px-6">
    <div class="max-w-3xl mx-auto bg-white rounded-3xl p-8 md:p-12 shadow-sm border border-[#E8D9A8]">
        <div class="mb-10 text-center">
            <a href="/" class="inline-flex items-center gap-2 mb-6">
                <span class="text-xl font-bold tracking-widest">LOUVOR<span class="text-[#C9A84C]">.NET</span></span>
            </a>
            <h1 class="font-display text-4xl font-bold">Política de Privacidade</h1>
            <p class="text-sm text-[#8B7355] mt-2">Última atualização: <?= date('d/m/Y') ?></p>
        </div>

        <div class="prose prose-stone max-w-none space-y-6 text-sm md:text-base leading-relaxed text-[#44403C]">
            <section>
                <h2 class="text-lg font-bold text-[#1C1917] mb-2">1. Informações Coletadas</h2>
                <p>Coletamos as informações que você nos fornece voluntariamente ao usar o serviço, incluindo:</p>
                <ul class="list-disc pl-5 space-y-1 mt-2">
                    <li>Texto de inspiração da música;</li>
                    <li>Seu nome (opcional);</li>
                    <li>Seu número de telefone/WhatsApp (opcional, para envio de notificações);</li>
                    <li>Dados de pagamento (processados de forma segura pelo Asaas).</li>
                </ul>
            </section>

            <section>
                <h2 class="text-lg font-bold text-[#1C1917] mb-2">2. Uso das Informações</h2>
                <p>Utilizamos seus dados exclusivamente para:</p>
                <ul class="list-disc pl-5 space-y-1 mt-2">
                    <li>Gerar a música solicitada;</li>
                    <li>Enviar a música via SMS/WhatsApp quando solicitado;</li>
                    <li>Processar pagamentos e garantir a segurança das transações;</li>
                    <li>Melhorar nossos algoritmos de composição.</li>
                </ul>
            </section>

            <section>
                <h2 class="text-lg font-bold text-[#1C1917] mb-2">3. Compartilhamento com Terceiros</h2>
                <p>Seus dados são compartilhados apenas com parceiros essenciais para a prestação do serviço:</p>
                <ul class="list-disc pl-5 space-y-1 mt-2">
                    <li><strong>Anthropic (Claude):</strong> O texto da inspiração é enviado de forma anônima para gerar a letra;</li>
                    <li><strong>PiAPI (Suno):</strong> A letra gerada é enviada para compor a melodia;</li>
                    <li><strong>Asaas:</strong> Informações de pagamento são processadas por este gateway;</li>
                    <li><strong>Zenvia:</strong> Para envio de notificações via SMS.</li>
                </ul>
                <p>Nós NÃO vendemos seus dados para fins publicitários de terceiros.</p>
            </section>

            <section>
                <h2 class="text-lg font-bold text-[#1C1917] mb-2">4. Segurança dos Dados</h2>
                <p>Implementamos medidas de segurança técnicas e organizacionais para proteger seus dados contra acesso não autorizado, perda ou alteração. Os logs de músicas são armazenados de forma segura.</p>
            </section>

            <section>
                <h2 class="text-lg font-bold text-[#1C1917] mb-2">5. Seus Direitos</h2>
                <p>Em conformidade com a LGPD, você tem o direito de solicitar o acesso, correção ou exclusão de seus dados pessoais armazenados em nosso sistema a qualquer momento através de nossos canais de SAC.</p>
            </section>
        </div>

        <div class="mt-12 pt-8 border-top border-[#F0E8CC] text-center">
            <a href="/" class="text-[#C9A84C] font-semibold hover:underline">← Voltar para a página inicial</a>
        </div>
    </div>
</body>
</html>
