<?php
require_once __DIR__ . '/config.php';
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Termos de Uso — LOUVOR.NET</title>
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
            <h1 class="font-display text-4xl font-bold">Termos de Uso</h1>
            <p class="text-sm text-[#8B7355] mt-2">Última atualização: <?= date('d/m/Y') ?></p>
        </div>

        <div class="prose prose-stone max-w-none space-y-6 text-sm md:text-base leading-relaxed text-[#44403C]">
            <section>
                <h2 class="text-lg font-bold text-[#1C1917] mb-2">1. Aceitação dos Termos</h2>
                <p>Ao acessar e utilizar o LOUVOR.NET, você concorda em cumprir e estar vinculado a estes Termos de Uso. Se você não concordar com qualquer parte destes termos, não deverá utilizar nossos serviços.</p>
            </section>

            <section>
                <h2 class="text-lg font-bold text-[#1C1917] mb-2">2. Descrição do Serviço</h2>
                <p>O LOUVOR.NET é uma plataforma que utiliza Inteligência Artificial para gerar letras e melodias musicais baseadas em informações fornecidas pelo usuário. O serviço é prestado mediante pagamento único por composição.</p>
            </section>

            <section>
                <h2 class="text-lg font-bold text-[#1C1917] mb-2">3. Pagamento e Reembolso</h2>
                <p>Os pagamentos são processados via PIX através da plataforma Asaas. Devido à natureza digital e personalizada do produto (geração de conteúdo por IA consumindo créditos de processamento), não oferecemos reembolsos após o início da geração da música, exceto em casos comprovados de falha técnica no sistema.</p>
            </section>

            <section>
                <h2 class="text-lg font-bold text-[#1C1917] mb-2">4. Propriedade Intelectual e Uso</h2>
                <p>A música gerada é para uso pessoal do usuário. O usuário possui o direito de compartilhar, baixar e reproduzir a obra para fins não comerciais. O LOUVOR.NET retém o direito de manter a obra em seus servidores e utilizá-la para fins de demonstração da plataforma, preservando a privacidade do usuário quando solicitado.</p>
            </section>

            <section>
                <h2 class="text-lg font-bold text-[#1C1917] mb-2">5. Conteúdo do Usuário</h2>
                <p>O usuário é responsável pelo conteúdo da "inspiração" fornecida. É proibido o uso de linguagem ofensiva, discriminatória ou que viole direitos de terceiros. Reservamo-nos o direito de não processar solicitações que violem nossos princípios éticos.</p>
            </section>

            <section>
                <h2 class="text-lg font-bold text-[#1C1917] mb-2">6. Limitação de Responsabilidade</h2>
                <p>O LOUVOR.NET não garante que a interpretação da IA corresponderá exatamente às expectativas subjetivas do usuário. A IA é uma ferramenta criativa e os resultados podem variar.</p>
            </section>
        </div>

        <div class="mt-12 pt-8 border-top border-[#F0E8CC] text-center">
            <a href="/" class="text-[#C9A84C] font-semibold hover:underline">← Voltar para a página inicial</a>
        </div>
    </div>
</body>
</html>
