<?php
// =========================================================================
// 🤖 MOTOR COGNITIVO PREMIUM MULTI-ESTILO — AURELIUS IA INTEGRADO (BLINDADO)
// =========================================================================
header('Content-Type: application/json; charset=utf-8');

include_once("Conexao.php");

$conexao_link = $conexao_aurelius ?? $conexao ?? $link ?? $conn ?? $pdo ?? null;
if (!$conexao_link || !($conexao_link instanceof mysqli)) {
    $conexao_link = @mysqli_connect("127.0.0.1", "root", "", "aurelius_salao");
}

$mensagem_usuario = isset($_GET['mensagem']) ? trim($_GET['mensagem']) : '';

// 🟢 FUNÇÃO DE HIGIENIZAÇÃO: Remove acentos e caracteres especiais para entender escrita casual
function normalizarTextoIA($texto) {
    $texto = mb_strtolower($texto, 'UTF-8');
    $procurar = array('ã','á','à','â','é','è','ê','í','ì','î','ó','ò','ô','õ','ú','ù','û','ç');
    $substituir = array('a','a','a','a','e','e','e','i','i','i','o','o','o','o','u','u','u','c');
    return str_replace($procurar, $substituir, $texto);
}

$txt_normalizado = normalizarTextoIA($mensagem_usuario);
$resposta_ia = "";

// 🟢 1. DETECTOR DE TELEFONE (CONSULTA VIP DINÂMICA)
$telefone_limpo = preg_replace('/\D/', '', $mensagem_usuario);
if (strlen($telefone_limpo) >= 9) {
    $telefone_banco = mysqli_real_escape_string($conexao_link, substr($telefone_limpo, -9));
    $q_vip = @mysqli_query($conexao_link, "SELECT * FROM `clientes_vip` WHERE `telefone` LIKE '%$telefone_banco%' LIMIT 1");
    
    if ($q_vip && mysqli_num_rows($q_vip) > 0) {
        $dados_vip = mysqli_fetch_assoc($q_vip);
        $desconto_calculado = $dados_vip['desconto'] ?? '20%';
        $resposta_ia = "🎉 <b>CÓDIGO VIP LOCALIZADO!</b><br>O número <b>{$telefone_banco}</b> está registado no nosso sistema mestre. Tens um <b>Desconto VIP Ativo de {$desconto_calculado}</b> para usar em agendamentos ou cosméticos! Queres marcar um horário?";
    } else {
        $resposta_ia = "🔍 O número <b>{$telefone_banco}</b> foi consultado nas tabelas, mas ainda não é Premium. <b>Criei um cupão para ti!</b> Usa o código <code>AURELIUS10</code> para ganhar 10% de desconto imediato na primeira marcação ao domicílio no Huambo.";
    }
}

// 🟢 2. DICIONÁRIO COGNITIVO EXPANDIDO (CASUAL, FORMAL E INFORMAL)
if (empty($resposta_ia)) {
    
    // GATILHOS: Curtas, Agradecimentos e OKs (Fim das respostas repetidas mecânicas)
    if (in_array($txt_normalizado, ['ok', 'okay', 'tá bem', 'ta bem', 'sim', 'entendi', 'perfeito', 'top', 'fixe', 'ya'])) {
        $resposta_ia = "Excelente! 👍 Fico feliz que tenha ficado claro. Se precisares de saber mais sobre preços, entregas ou sobre como cadastrar o teu salão, é só digitares aqui!";
    }
    elseif (in_array($txt_normalizado, ['obrigado', 'obrigada', 'valeu', 'agradecido', 'thx', 'obg'])) {
        $resposta_ia = "De nada! 😊 É sempre um prazer ajudar. O **Grupo Aurélius** está aqui para facilitar a tua rotina de beleza e faturamento. Se precisares de mais alguma coisa, estou por aqui!";
    }
    elseif (in_array($txt_normalizado, ['fale mais', 'fale mas', 'continua', 'mais info', 'saber mais', 'mais detalhe', 'ver mais'])) {
        $resposta_ia = "Com certeza! 🚀 O nosso ecossistema une **Trabalho ao Domicílio** com um **Marketplace de Cosméticos**. Sabias que se ativares e depositares fundos na tua carteira digital ganhas descontos de até 20% automáticos? Quer saber mais sobre essa vantagem?";
    }
    
    // GATILHOS: Saudações
    elseif (str_contains($txt_normalizado, 'ola') || str_contains($txt_normalizado, 'oi') || str_contains($txt_normalizado, 'bom dia') || str_contains($txt_normalizado, 'boa tarde') || str_contains($txt_normalizado, 'boa noite')) {
        $resposta_ia = "Olá! 👋 Sou o **Aurelius IA**, o assistente digital da rede. Estou pronto para te ajudar, seja de forma rápida ou detalhada. Fala comigo sobre:<br>• 👑 **Plano Premium & Descontos**<br>• 🪙 **Comissão de 10%**<br>• 🛵 **Serviços ao Domicílio**<br>• 🏍️ **Entregas nas Províncias**";
    }
    
    // GATILHOS: Trabalho ao Domicílio
    elseif (str_contains($txt_normalizado, 'domicil') || str_contains($txt_normalizado, 'casa') || str_contains($txt_normalizado, 'lar') || str_contains($txt_normalizado, 'atendimento')) {
        $resposta_ia = "🛵 <b>Trabalho ao Domicílio (Estética & Corte):</b><br><br>Esqueça as filas! Esta funcionalidade permite chamar barbeiros, trancistas e manicures direto para a sua residência. O sistema envia alertas automáticos via WhatsApp para o profissional e reduz as faltas em até 95%.";
    }
    
    // GATILHOS: Logística e Entregas (Provincias/Bairros)
    elseif (str_contains($txt_normalizado, 'entrega') || str_contains($txt_normalizado, 'logistica') || str_contains($txt_normalizado, 'enviar') || str_contains($txt_normalizado, 'bairro') || str_contains($txt_normalizado, 'provinc') || str_contains($txt_normalizado, 'huambo') || str_contains($txt_normalizado, 'luanda')) {
        $resposta_ia = "🏍️ <b>Logística e Distribuição Nacional:</b><br><br>Enviamos produtos cosméticos para todo o país! O nosso fluxo dinâmico começa nos <b>Bairros</b> (como Kapango, Catimba, Talatona), passa pelas sedes dos <b>Municípios</b> e distribui mercadorias de forma segura por todas as <b>21 Províncias de Angola</b>.";
    }
    
    // GATILHOS: Planos Premium e Freemium
    elseif (str_contains($txt_normalizado, 'plano') || str_contains($txt_normalizado, 'premium') || str_contains($txt_normalizado, 'freemium') || str_contains($txt_normalizado, 'pagar') || str_contains($txt_normalizado, 'preco')) {
        $resposta_ia = "👑 <b>Planos e Parcerias Comerciais:</b><br><br>• <b>Modo Freemium:</b> O cadastro de balcões e uso das agendas é 100% gratuito (0,00 Kz) por 30 dias.<br>• <b>Plano Premium:</b> Libera a carteira digital de depósitos antecipados. Clientes que depositam fundos ganham descontos automáticos de 10% a 20% nas marcações.";
    }
    
    // GATILHOS: Comissões e Taxas
    elseif (str_contains($txt_normalizado, 'comiss') || str_contains($txt_normalizado, 'taxa') || str_contains($txt_normalizado, 'percentagem') || str_contains($txt_normalizado, 'lucro') || str_contains($txt_normalizado, 'render')) {
        $resposta_ia = "🪙 <b>Módulo de Comissões (Taxa de 10%):</b><br><br>Não cobramos mensalidades fixas abusivas dos salões parceiros. O modelo base retém de forma automática uma <b>taxa administrativa de 10%</b> sobre as vendas de cosméticos e 15% sobre os agendamentos gerenciados com sucesso.";
    }
    
    // GATILHOS: O que é o grupo / SaaS
    elseif (str_contains($txt_normalizado, 'saas') || str_contains($txt_normalizado, 'grupo') || str_contains($txt_normalizado, 'plataforma') || str_contains($txt_normalizado, 'sistema')) {
        $resposta_ia = "🚀 <b>O que é o Grupo Aurélius?</b><br><br>Somos uma plataforma SaaS Multi-Tenant nascida no Huambo. O sistema permite que cada barbearia ou loja crie a sua conta e gerencie de forma totalmente independente as suas vagas de emprego, faturamento, comissões de retaguarda e estoque.";
    }
    
    // FALLBACK: Resposta Genérica Cortês Inteligente
    else {
        $resposta_ia = "🤖 Entendi perfeitamente! Como sou focado na gestão comercial do **Grupo Aurélius**, preferes que eu te explique sobre as vantagens do **Plano Premium**, o funcionamento das **Comissões de 10%**, ou preferes ver como funciona as **Entregas de Cosméticos** nos bairros?";
    }
}

// Despacha o pacote em formato JSON limpo
echo json_encode(['resposta' => $resposta_ia]);
exit();
?>