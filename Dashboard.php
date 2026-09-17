<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
include_once __DIR__ . '/Conexao.php';

// 1. Tenta capturar o ID que vem explicitamente na URL (?id=237)
$id_atual = isset($_GET['id']) ? (int)$_GET['id'] : 0;

// 2. Se a URL vier vazia (como no Fechar Recibo), tenta a Sessão ou o Cookie local
if ($id_atual === 0) {
    if (isset($_SESSION['loja_contexto'])) {
        $id_atual = (int)$_SESSION['loja_contexto'];
    } elseif (isset($_COOKIE['loja_contexto_seguro'])) {
        $id_atual = (int)$_COOKIE['loja_contexto_seguro'];
    }
}

// 3. Guarda e sincroniza o ID para os próximos cliques (Sessão + Cookie de 1 dia)
if ($id_atual > 0) {
    $_SESSION['loja_contexto'] = $id_atual;
    setcookie('loja_contexto_seguro', $id_atual, time() + 86400, "/");
}

// 4. Bloqueia apenas se o ID não for encontrado em lado nenhum
if ($id_atual === 0) {
    die("🚨 Erro de Acesso: Nenhuma barbearia foi selecionada no painel central.");
}


// 5. Consulta flexível na tabela 'usuario' (Aceita parceiros e clientes no ecossistema)
$busca_loja = mysqli_query($mysqli, "SELECT * FROM usuario WHERE codigo = $id_atual");
$dados_loja = mysqli_fetch_assoc($busca_loja);

if (!$dados_loja) {
    die("🚨 Erro do Ecossistema: Os dados desta barbearia parceira não foram localizados no banco.");
}

// 5. Consulta flexível na tabela 'usuario'
$busca_loja = mysqli_query($mysqli, "SELECT * FROM usuario WHERE codigo = $id_atual");



if (!$dados_loja) {
    die("🚨 Erro do Ecossistema: Os dados desta barbearia parceira não foram localizados no banco.");
}


// 4. Variáveis mapeadas a partir das colunas reais do teu phpMyAdmin
$nome_exibicao = $dados_loja['nome'] ?? 'Sem Nome'; // Ex: Barbearia Branca, LOOK NOVO
$logo_exibicao = !empty($dados_loja['logo_empresa']) ? 'upload/' . $dados_loja['logo_empresa'] : 'upload/default.png';
$preco_base    = $dados_loja['preco'] ?? 0.00;
$localizacao   = $dados_loja['endereco'] ?? 'Não informada'; // Ex: Huambo, Namibe

// 📊 5. METRICAS E FATURAMENTOS DA BARBEARIA ATUAL
$faturamento_total = 0.00;
$total_funcionarios = 0;

// 1. Array com todas as possíveis colunas de ligação do ecossistema
$possiveis_colunas = ['id_usuario', 'usuario_id', 'id_loja', 'loja_id', 'codigo_loja'];

// 2. Loop inteligente para encontrar a coluna correta de faturamento
foreach ($possiveis_colunas as $coluna) {
    $query_vendas = mysqli_query($mysqli, "SELECT SUM(valor_total) as total FROM historico_vendas WHERE $coluna = $id_atual");
    if ($query_vendas && mysqli_num_rows($query_vendas) > 0) {
        $dados_vendas = mysqli_fetch_assoc($query_vendas);
        if ($dados_vendas['total'] !== null) {
            $faturamento_total = $dados_vendas['total'];
            break; // Encontrou a coluna certa, interrompe o loop
        }
    }
}

// 3. Loop inteligente para encontrar a coluna correta de funcionários
foreach ($possiveis_colunas as $coluna) {
    $query_funcionarios = mysqli_query($mysqli, "SELECT COUNT(*) as total_func FROM funcionarios WHERE $coluna = $id_atual");
    if ($query_funcionarios) {
        $dados_func = mysqli_fetch_assoc($query_funcionarios);
        $total_funcionarios = $dados_func['total_func'] ?? 0;
        if ($total_funcionarios > 0) {
            break; // Encontrou registros, interrompe o loop
        }
    }
}
?>


<?php
// =========================================================================
// 🚀 MOTOR DE EXTRAÇÃO DINÂMICA BLINDADO CONTRA DUPLICAÇÕES (GROUP BY)
// =========================================================================
$lista_pedidos = [];

if (isset($pdo)) {
    try {
        $data_hoje_angola = date('Y-m-d');

        // ✨ A SOLUÇÃO: Adicionado 'GROUP BY p.id_pagamento' para esmagar registos repetidos na nuvem
        $stmt_ativos = $pdo->prepare("
            SELECT 
                p.`id_pagamento`, 
                p.`servico`, 
                p.`valor` AS preco_registo, 
                p.`valor_liquido`,
                p.`desconto`,
                p.`data_servico`, 
                p.`hora_servico`,
                p.`status_trabalho`,
                p.`status_atendimento`,
                p.`cliente_telefone`,
                f.`nome` AS nome_profissional_real,
                p.`profissional` AS prof_bruto,
                IFNULL(c.`saldo_acumulado`, 0.00) AS troco_carteira_acumulado,
                s.`preco` AS preco_original_servico
            FROM `pagamentos` p
            LEFT JOIN `funcionarios` f ON p.`profissional` = f.`id_funcionario`
            LEFT JOIN `carteira_saldos_clientes` c ON p.`cliente_telefone` = c.`telefone_cliente`
            LEFT JOIN `servicos` s ON p.`servico` = s.`nome`
            WHERE p.`status_atendimento` != 'Cancelado'
              AND p.`data_servico` >= ? 
            GROUP BY p.`id_pagamento`
            ORDER BY p.`data_servico` ASC, p.`hora_servico` ASC
        ");
        $stmt_ativos->execute([$data_hoje_angola]);
        $lista_pedidos = $stmt_ativos->fetchAll(PDO::FETCH_ASSOC);

    } catch (PDOException $e) {
        // Fallback de segurança agrupado
        try {
            $stmt_fallback = $pdo->query("SELECT * FROM `pagamentos` GROUP BY id_pagamento ORDER BY id_pagamento DESC");
            $lista_pedidos = $stmt_fallback->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $ex) {
            $lista_pedidos = [];
        }
    }
}
?>

<html lang="pt-PT">
<head>
    <meta charset="UTF-8">
  <!-- Na barra de navegação e instalação do PWA -->
<title>BarbeariasAngola — Rede de Distribuição & Estética</title>



<style>
/* =========================================================================
   🔮 CORE & RESET DO ECOSSISTEMA AURELIUS (MOBILE-FIRST)
   ========================================================================= */
* { 
    box-sizing: border-box; 
    margin: 0; 
    padding: 0; 
    font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; 
}

html, body { 
    width: 100%; 
    max-width: 100%; 
    /* 🔴 RETIFICAÇÃO CRÍTICA: Removido !important para o fundo não congelar com elementos fixed */
    overflow-x: hidden; 
    background-color: #0f172a; 
    color: #ffffff; 
}

body { 
    padding-bottom: 80px; 
    width: 100%;
}

.container { 
    width: 100%; 
    max-width: 1350px; 
    margin: 0 auto; 
    padding: 0 15px; 
}

/* Classes de Ocultação */
.hidden, #area-impressao-global.hidden { 
    display: none !important; 
}

/* =========================================================================
   🚀 RETIFICAÇÃO DO MENU LATERAL (DESIGN ORIGINAL PRESERVADO A 100%)
   ========================================================================= */
#menuLateralMobile {
    position: fixed;
    top: 0;
    /* 🟢 RETIFICAÇÃO: Começa em 0 fixo na borda esquerda */
    left: 0; 
    width: 50vw; /* Mantém os seus 50% de largura originais */
    height: 100vh;
    background-color: #0f172a;
    border-right: 4px solid #0088cc;
    box-shadow: 5px 0 15px rgba(0,0,0,0.5);
    
    /* 🟢 RETIFICAÇÃO: Esconde movendo exatamente a sua própria largura para fora da tela */
    transform: translateX(-100%); 
    transition: transform 0.3s ease-in-out; 
    
    padding: 25px 15px;
    z-index: 999999 !important; /* Camada máxima absoluta para não travar atrás de nada */
    display: flex;
    flex-direction: column;
    gap: 18px;
    box-sizing: border-box;
}

/* 🟢 Classe ativa controlada pelo clique do JavaScript */
#menuLateralMobile.menu-ativo {
    transform: translateX(0) !important; /* Entra 100% até ao meio matemático */
}

/* =========================================================================
   📋 PAINÉIS DE DADOS, INPUTS & FORMULÁRIOS DA SESSÃO
   ========================================================================= */
.painel-titulo { 
    font-size: 15px; 
    font-weight: bold; 
    margin-bottom: 12px; 
    display: block; 
    color: #ffffff; 
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.grid-inputs { 
    display: grid; 
    grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); 
    gap: 12px; 
}

.input-estilizado { 
    width: 100%; 
    padding: 12px 16px; 
    border: 1px solid #ccc; 
    border-radius: 8px; 
    font-size: 16px; 
    color: #333333; 
    background-color: #ffffff; 
    outline: none;
}

/* =========================================================================
   💇 INTERFACE DE ABAS, SERVIÇOS & EQUIPA (DASHBOARD)
   ========================================================================= */
.aba-conteudo { display: none; }
.aba-conteudo.active { display: block; }

.grid-categorias, .grid-container { 
    display: grid; 
    grid-template-columns: repeat(auto-fit, minmax(240px, 1fr)); 
    gap: 20px; 
    margin-top: 15px; 
    width: 100%;
}

.aba-item { 
    background-color: #1e293b; 
    border: 1px solid #334155; 
    border-radius: 12px; 
    color: #ffffff; 
    padding: 16px; 
    cursor: pointer; 
    text-align: center; 
    transition: background 0.2s, transform 0.2s; 
    display: flex;
    flex-direction: column !important;
    align-items: center;
    justify-content: space-between; 
    overflow: hidden;
}
.aba-item:hover { background-color: #334155; transform: translateY(-2px); }
.aba-item img { border-radius: 8px; margin-bottom: 12px; object-fit: cover; height: 220px; width: 100%; }

/* =========================================================================
   💸 CAIXA DE PREÇO, COMPROVATIVOS E IMPRESSÃO (MODAL NEON)
   ========================================================================= */
.preco-container { 
    background-color: #0f172a; 
    border: 2px dashed #22c55e; 
    border-radius: 12px; 
    padding: 20px; 
    text-align: center; 
    margin-top: 20px; 
}
.preco-container h3 { margin-bottom: 5px; font-size: 16px; }
.preco-container p { font-size: 26px; font-weight: bold; color: #22c55e; margin-bottom: 12px; }

.btn-voltar { 
    background-color: #6c757d; 
    color: white; 
    border: none; 
    padding: 8px 20px; 
    border-radius: 20px; 
    font-weight: bold; 
    cursor: pointer; 
    margin: 10px auto; 
    display: inline-block; 
}

#faturaPainelNatural { background-color: rgba(11, 26, 48, 0.96) !important; }

/* =========================================================================
   🛍️ MERCADO GLOBAL & FLUXO DE PRODUTOS COSMÉTICOS
   ========================================================================= */
.passo-card {
    background-color: #111827;
    border: 1px solid #1e293b;
    border-radius: 14px;
    padding: 16px;
    transition: all 0.3s cubic-bezier(0.175, 0.885, 0.32, 1.275);
    position: relative;
    overflow: hidden;
}
.passo-card:hover { transform: translateY(-4px); border-color: #38bdf8; box-shadow: 0 10px 20px rgba(56, 189, 248, 0.15); }
.passo-card img { width: 100%; max-width: 100%; height: 160px; object-fit: cover; margin: 0 auto 10px auto; display: block; border-radius: 8px; }

/* =========================================================================
   📱 REGRAS DE ADAPTAÇÃO TOTAL PARA TELEMÓVEIS (ABAIXO DE 991PX)
   ========================================================================= */
@media (max-width: 991px) {
    #menuDesktop { display: none !important; }
    .Menu-Icon { display: block !important; cursor: pointer; }
    body { padding: 0 10px 80px 10px !important; }

    .grid-inputs, .grid-categorias, .grid-container { 
        grid-template-columns: repeat(2, 1fr) !important; 
        gap: 12px !important; 
    }
    .grid-inputs input, .grid-inputs select { grid-column: span 2; }
    .aba-item { padding: 14px 10px !important; }
    .aba-item img { height: 220px !important; }
    .passo-card { padding: 14px 10px !important; }
    .passo-card img { height: 200px !important; }
}

/* =========================================================================
   🖨️ CONTROLO EXCLUSIVO DE IMPRESSÃO LIMPA 
   ========================================================================= */
@media print {
    body * { visibility: hidden !important; }
    .no-print, nav, footer, .Menu-Icon, button, .btn-voltar, .botoes-pagamento { display: none !important; }
    #area-impressao-global, #area-impressao-global * { visibility: visible !important; }
    #area-impressao-global { position: absolute !important; left: 0 !important; top: 0 !important; width: 100% !important; background: #ffffff !important; color: #000000 !important; }
}
</style>


</head>
<body>


<style>
    /* Oculta o gatilho */
    #menu-toggle {
        display: none !important;
    }

    /* 🔴 O SEGREDO PARA DESTRAVAR O FUNDO: Uma máscara que cobre o ecrã apenas quando o menu abre */
    .menu-mascara-fundo {
        position: fixed;
        top: 0;
        left: 0;
        width: 100vw;
        height: 100vh;
        background-color: rgba(0, 0, 0, 0.4); /* Escurece o fundo de forma suave */
        z-index: 99998;
        opacity: 0;
        pointer-events: none;
        transition: opacity 0.3s ease-in-out;
    }

    /* Estrutura Original do Menu Lateral */
    #menuLateralMobile {
        position: fixed;
        top: 0;
        left: 0;
        width: 50vw;
        height: 100vh;
        background-color: #0f172a;
        border-right: 4px solid #0088cc;
        box-shadow: 5px 0 15px rgba(0,0,0,0.5);
        padding: 25px 15px;
        z-index: 99999; /* Fica acima da máscara de fundo */
        display: flex;
        flex-direction: column;
        gap: 18px;
        box-sizing: border-box;
        transform: translateX(-100%);
        transition: transform 0.3s ease-in-out;
    }

    /* 🟢 SELETOR 1: Quando ativa a checkbox, a máscara aparece e destrava o clique de fecho */
    #menu-toggle:checked ~ .menu-mascara-fundo {
        opacity: 1;
        pointer-events: auto;
    }

    /* 🟢 SELETOR 2: Quando ativa a checkbox, o menu entra perfeitamente até ao meio */
    #menu-toggle:checked ~ #menuLateralMobile {
        transform: translateX(0) !important;
    }
</style>

<!-- 🚨 CHECKBOX DE SINALIZAÇÃO MÁXIMA -->
<input type="checkbox" id="menu-toggle">

<!-- 🔴 MÁSCARA QUE DESCONGELA O FUNDO (Clicar aqui fecha o menu instantaneamente) -->
<label for="menu-toggle" class="menu-mascara-fundo"></label>

<!-- =========================================================================
     🚀 MENU LATERAL RETRÁTIL (DESIGN E ESTRUTURA PRESERVADOS)
     ========================================================================= -->
<div id="menuLateralMobile">
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 10px; border-bottom: 2px solid #1e293b; padding-bottom: 15px;">
        <strong style="color: #fff; font-size: 24px; letter-spacing: 0.5px;">AURELIUS</strong>
        <!-- Clicar no X desmarca a checkbox mecanicamente -->
        <label for="menu-toggle" style="font-size: 55px; color: #ef4444; cursor: pointer; font-weight: bold; line-height: 0.5; user-select: none;">&times;</label>
    </div>
 
    <!-- Links com ações embutidas e fecho automático em paralelo -->
    <a href="Principal.php" style="background:#d32f2f; color:white; padding:14px; text-decoration:none; border-radius:10px; font-weight:bold; font-size:15px; text-align:center; text-transform:uppercase;">SAIR</a>
    <a href="unitel.php" style="background:#ff6600; color:white; padding:14px; text-decoration:none; border-radius:10px; font-weight:bold; font-size:14px; text-align:center; text-transform:uppercase; border: 1px solid #cc5200;">📱 PAGAMENTOS MÓVEIS</a>
    <a href="./fatura.php" target="_blank" style="background-color: #0088cc; color: #ffffff; text-decoration: none; padding: 14px; font-size: 14px; font-weight: bold; border-radius: 10px; text-transform: uppercase; border: 1px solid #006699; text-align: center;">🖨️ Emitir Fatura</a>
    
    <a href="#" onclick="alternarAbas('servicos'); document.getElementById('menu-toggle').checked = false; return false;" style="background:#1e293b; color:#38bdf8; padding:14px; text-decoration:none; border-radius:10px; font-weight:bold; font-size:15px; text-align:center; border: 1px solid #334155;">SERVIÇOS</a>
    <a href="#" onclick="alternarAbas('photos'); document.getElementById('menu-toggle').checked = false; return false;" style="background:#1e293b; color:#38bdf8; padding:14px; text-decoration:none; border-radius:10px; font-weight:bold; font-size:15px; text-align:center; border: 1px solid #334155;">PHOTOS</a>
    <a href="#" onclick="abrirAbas(); document.getElementById('menu-toggle').checked = false; return false;" style="background:#1e293b; color:#38bdf8; padding:14px; text-decoration:none; border-radius:10px; font-weight:bold; font-size:15px; text-align:center; border: 1px solid #334155;">SOBRE NÓS</a>
    <a href="#" onclick="abrirTermos(); document.getElementById('menu-toggle').checked = false; return false;" style="background:#1e293b; color:#38bdf8; padding:14px; text-decoration:none; border-radius:10px; font-weight:bold; font-size:13px; text-align:center; border: 1px solid #334155;">TERMOS & PRIVACIDADE</a>
</div>

<!-- BARRA SUPERIOR ORIGINAL (NAVBAR) -->
<nav style="display: flex; justify-content: space-between; align-items: center; background-color: #e0e0e0; padding: 10px 40px; position: relative; z-index: 1000; width: 100%; box-sizing: border-box;">
    
    <!-- Logotipo -->
    <div class="logo" onclick="irParaSecao('home')" style="color: #d32f2f; cursor: pointer;">
        <h1 style="font-size: 40px; font-weight: bold; line-height: 1; margin: 0;">🎌  BRA<span style="color: #0b1a30;">NCA</span></h1>
        <h6 style="color: #0b1a30; font-size: 20px; margin: 2px 0 0 0;">Salão de Beleza e Barbearia</h6>
    </div>

    <!-- MENU DESKTOP -->
    <ul class="ul" id="menuDesktop" style="display: flex; align-items: center; gap: 10px; list-style: none; margin: 0; padding: 0;">
        <li><a href="unitel.php" style="display: block; background-color: #ff6600; color: white; padding: 10px 15px; text-decoration: none; border-radius: 20px; font-size: 13px; font-weight: bold; text-align: center; border: 1px solid #cc5200; white-space: nowrap;">📱 Pagamentos Móveis</a></li>
        <li><a href="#" onclick="alternarAbas('servicos')" style="display: block; background-color: #0088cc; color: white; padding: 10px 15px; text-decoration: none; border-radius: 20px; font-size: 13px; font-weight: bold; text-align: center; border: 1px solid #006699; white-space: nowrap;">Serviços</a></li>
        <li><a href="#" onclick="alternarAbas('photos')" style="display: block; background-color: #0088cc; color: white; padding: 10px 15px; text-decoration: none; border-radius: 20px; font-size: 13px; font-weight: bold; text-align: center; border: 1px solid #006699; white-space: nowrap;">Photos</a></li>
        <li><a href="#" onclick="abrirAbas()" style="display: block; background-color: #0088cc; color: white; padding: 10px 15px; text-decoration: none; border-radius: 20px; font-size: 13px; font-weight: bold; text-align: center; border: 1px solid #006699; white-space: nowrap;">Sobre Nós</a></li>
        <li><a href="#" onclick="abrirTermos()" style="display: block; background-color: #0088cc; color: white; padding: 10px 15px; text-decoration: none; border-radius: 20px; font-size: 13px; font-weight: bold; text-align: center; border: 1px solid #006699; white-space: nowrap;">Termos & Privacidade</a></li>
        <li><a href="./fatura.php?id=118" target="_blank" style="display: block; background-color: #d32f2f; color: white; padding: 10px 15px; text-decoration: none; border-radius: 20px; font-size: 13px; font-weight: bold; text-align: center; border: 1px solid #b91c1c; white-space: nowrap; text-transform: uppercase;">🖨️ Emitir Fatura</a></li>
        <li><a href="Principal.php" style="display: block; background-color: #d32f2f; color: white; padding: 10px 15px; text-decoration: none; border-radius: 20px; font-size: 13px; font-weight: bold; text-align: center; border: 1px solid #b91c1c; white-space: nowrap;">Sair</a></li>
    </ul>

    <!-- ÍCONE DAS 3 BARRAS -->
    <label for="menu-toggle" class="Menu-Icon" style="cursor: pointer; display: block;">
        <svg viewBox="0 0 100 80" width="58" height="58" style="fill: #0b1a30; display: block;">
            <rect width="100" height="15" rx="8"></rect>
            <rect y="30" width="100" height="15" rx="8"></rect>
            <rect y="60" width="100" height="15" rx="8"></rect>
        </svg>
    </label>
</nav>



















<!-- =========================================================================
     📅 PAUTA DE AGENDAMENTO TÉCNICO COMPLETA — GRADIENTE PREMIUM VÍVIDO
     ========================================================================= -->
     <div class="painel-azul" id="dadosAgendamento" style="margin-top: 25px; background: linear-gradient(135deg, #1e293b 0%, #0f172a 100%); border: 2px solid #00d2ff; border-radius: 16px; padding: 30px; box-shadow: 0 0 25px rgba(0, 210, 255, 0.25), inset 0 0 15px rgba(56, 189, 248, 0.1);">
     <span class="painel-titulo" style="color: #00d2ff; font-weight: 800; font-size: 14px; text-transform: uppercase; letter-spacing: 1px; display: block; margin-bottom: 20px; text-shadow: 0 0 10px rgba(0, 210, 255, 0.4); border-left: 4px solid #22c55e; padding-left: 10px;">Dados de Atendimento da Sessão</span>
     
     <div class="grid-inputs" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); gap: 15px;">
         
         <!-- Campo 1: Identificação Nominal do Cliente -->
         <div>
             <label style="color: #38bdf8; font-size: 11.5px; display: block; margin-bottom: 6px; text-transform: uppercase; font-weight: bold; letter-spacing: 0.5px;">Nome do Cliente:</label>
             <input type="text" id="inputNomeCliente" placeholder="Nome do Cliente" class="input-estilizado" style="width: 100%; background: rgba(7, 11, 18, 0.6); border: 1px solid #334155; border-radius: 8px; color: #fff; padding: 12px; font-weight: 500; outline: none; transition: 0.3s;" onfocus="this.style.borderColor='#00d2ff'; this.style.boxShadow='0 0 8px rgba(0,210,255,0.3)';" onblur="this.style.borderColor='#334155'; this.style.boxShadow='none';" required autocomplete="off">
         </div>
 
       <!-- Campo 2: Alocação de Profissional Técnico Dinâmico -->
<div>
<label style="color: #38bdf8; font-size: 11.5px; display: block; margin-bottom: 6px; text-transform: uppercase; font-weight: bold; letter-spacing: 0.5px;">
    Profissional:
</label>

<select id="inputFuncionario" name="profissional_id" class="input-estilizado" style="width: 100%; background: rgba(7, 11, 18, 0.6); border: 1px solid #334155; border-radius: 8px; color: #fff; padding: 12px; font-weight: 500; outline: none; cursor: pointer; color-scheme: dark; transition: 0.3s;" onfocus="this.style.borderColor='#00d2ff'; this.style.boxShadow='0 0 8px rgba(0,210,255,0.3)';" onblur="this.style.borderColor='#334155'; this.style.boxShadow='none';">
    <option value="" style="background:#0f172a;">Selecione um profissional...</option>
    








    <?php
    // 🟢 EXTRACTOR DINÂMICO DE PROFISSIONAIS DO PHPMYADMIN
    if (isset($pdo)) {
        try {
            // Procura todos os funcionários ativos cadastrados na base de dados
            $stmt_prof = $pdo->query("SELECT `id_funcionario`, `nome`, `cargo` FROM `funcionarios` ORDER BY `nome` ASC");
            $profissionais_banco = $stmt_prof->fetchAll(PDO::FETCH_ASSOC);
            
            $contador = 1;
            foreach ($profissionais_banco as $prof):
                // Define o ID ou o nome como valor, dependendo do que o seu motor de agendamentos espera
                $valor_option = htmlspecialchars($prof['nome']); 
                $cargo_formatado = !empty($prof['cargo']) ? " (" . htmlspecialchars($prof['cargo']) . ")" : "";
            ?>
                <option value="<?= $valor_option ?>" style="background:#0f172a;">
                    <?= $contador ?>º <?= htmlspecialchars($prof['nome']) ?><?= $cargo_formatado ?>
                </option>
            <?php 
                $contador++;
            endforeach;
        } catch (PDOException $e) {
            echo "<option value='' style='background:#0f172a; color:#f87171;'>Erro ao carregar lista</option>";
        }
    }
    ?>
</select>
</div>
 
         <!-- Campo 3: Calendário Operacional Dinâmico -->
         <div>
             <label style="color: #38bdf8; font-size: 11.5px; display: block; margin-bottom: 6px; text-transform: uppercase; font-weight: bold; letter-spacing: 0.5px;">Data do Serviço:</label>
             <input type="date" id="inputDataServico" class="input-estilizado" style="width: 100%; background: rgba(7, 11, 18, 0.6); border: 1px solid #334155; border-radius: 8px; color: #fff; padding: 12px; font-weight: 500; outline: none; color-scheme: dark; transition: 0.3s;" onfocus="this.style.borderColor='#00d2ff'; this.style.boxShadow='0 0 8px rgba(0,210,255,0.3)';" onblur="this.style.borderColor='#334155'; this.style.boxShadow='none';">
         </div>
 
         <!-- Campo 4: Controlo Horário Reativo Neon -->
         <div>
             <label style="color: #00d2ff; font-size: 11.5px; display: block; margin-bottom: 6px; text-transform: uppercase; font-weight: bold; letter-spacing: 0.5px; text-shadow: 0 0 5px rgba(0,210,255,0.2);">Horário Marcado:</label>
             <input type="time" id="inputHoraServico" class="input-estilizado" style="width: 100%; background: rgba(7, 11, 18, 0.6); border: 2px solid #00d2ff; border-radius: 8px; color: #fff; padding: 11px; font-weight: bold; outline: none; color-scheme: dark; box-shadow: 0 0 10px rgba(0, 210, 255, 0.15); transition: 0.3s;" onfocus="this.style.borderColor='#22c55e'; this.style.boxShadow='0 0 12px rgba(34,197,94,0.4)';" onblur="this.style.borderColor='#00d2ff'; this.style.boxShadow='0 0 10px rgba(0, 210, 255, 0.15);'" required>
         </div>
 
     </div>
 </div>
 
 <!-- =========================================================================
      ⚙️ SCRIPT AUTOMÁTICO DE BLOQUEIO DE SEGURANÇA CHRONOS (DATAS ANTERIORES)
      ========================================================================= -->
 <script>
 document.addEventListener("DOMContentLoaded", function() {
     const campoData = document.getElementById('inputDataServico');
     const campoHora = document.getElementById('inputHoraServico');
     
     const hoje = new Date();
     const ano = hoje.getFullYear();
     const mes = String(hoje.getMonth() + 1).padStart(2, '0');
     const dia = String(hoje.getDate()).padStart(2, '0');
     
     const dataMinimaFormatada = ano + '-' + mes + '-' + dia;
     
     if (campoData) {
         campoData.min = dataMinimaFormatada; 
         campoData.value = dataMinimaFormatada; 
     }
     
     if (campoHora) {
         const horas = String(hoje.getHours()).padStart(2, '0');
         const minutos = String(hoje.getMinutes()).padStart(2, '0');
         campoHora.value = horas + ':' + minutos;
     }
 });
 </script>


<br>
    <div class="container">
    
    <!-- ABA HOME (TUDO DEVE FICAR DENTRO DELA PARA NÃO SUMIR) -->
    <div id="secao-home" class="aba-conteudo active">

        <!-- Banner de Destaque ÚNICO -->
        <div style="background: linear-gradient(135deg, #1e40af, #1e3a8a); border-radius: 15px; padding: 25px; text-align: center; margin-bottom: 25px; border: 1px solid #3b82f6; box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.3);">
            <h2 style="font-size: 24px; font-weight: bold; color: #ffffff; letter-spacing: 0.5px;">✨ BEM-VINDO A BARBEARIA BRANCA ✨</h2>
            <p style="font-size: 14px; color: #93c5fd; margin-top: 6px;">Transforme o seu visual com os melhores profissionais do Huambo</p>
        </div>
       

<!-- PAINEL DE VISUALIZAÇÃO ESTILO COMPROVATIVO NEON (Colorido por natureza no ecrã) -->
<div id="faturaPainelNatural" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100vh; background-color: rgba(11, 26, 48, 0.95); z-index: 99999; justify-content: center; align-items: center; overflow-y: auto; padding: 20px; box-sizing: border-box;">
    
    <!-- Caixa Principal com Fundo Escuro e Borda Azul Brilhante (Idêntico à sua Imagem) -->
    <div style="background-color: #111e35 !important; color: #ffffff !important; width: 100%; max-width: 440px; padding: 30px; border: 3px solid #0088cc !important; border-radius: 16px !important; box-sizing: border-box; box-shadow: 0 0 25px rgba(0, 136, 204, 0.4); position: relative; text-align: center;">
        
        <!-- Botão Fechar no Canto -->
        <span onclick="fecharFaturaNatural()" class="no-print" style="position: absolute; top: 15px; right: 20px; color: #ef4444; font-size: 26px; cursor: pointer; font-weight: bold; font-family: sans-serif;">&times;</span>

        <!-- Cabeçalho Centralizado -->
        <div style="margin-bottom: 20px; border-bottom: 1px dashed #334155; padding-bottom: 15px;">
            <h2 style="color: #38bdf8 !important; margin: 0; font-size: 22px; font-weight: bold; font-family: sans-serif; text-transform: uppercase; letter-spacing: 0.5px;">BARBEARIA BRANCA</h2>
            <p style="color: #94a3b8 !important; font-size: 12px; margin: 5px 0 0 0; font-family: sans-serif;">Comprovativo de Atendimento Geral</p>
        </div>

        <!-- Dados do Atendimento Alinhados -->
        <div style="font-family: sans-serif; text-align: left; font-size: 13px; line-height: 1.8; margin-bottom: 25px; padding: 0 5px;">
            <div style="margin-bottom: 10px;"><span style="color: #64748b; font-size: 11px; font-weight: bold; display: block; text-transform: uppercase;">REGISTO:</span><strong style="color: #cbd5e1; font-size: 14px;">nº <span id="natIdPagamento">62</span></strong></div>
            <div style="margin-bottom: 10px;"><span style="color: #64748b; font-size: 11px; font-weight: bold; display: block; text-transform: uppercase;">CLIENTE:</span><strong style="color: #ffffff; font-size: 15px;" id="natNomeCliente">Maria figueredo</strong></div>
            <div style="margin-bottom: 10px;"><span style="color: #64748b; font-size: 11px; font-weight: bold; display: block; text-transform: uppercase;">PROFISSIONAL:</span><strong style="color: #cbd5e1; font-size: 14px;" id="natProfissional">Aurélio</strong></div>
            <div style="margin-bottom: 10px;"><span style="color: #64748b; font-size: 11px; font-weight: bold; display: block; text-transform: uppercase;">SERVIÇO:</span><strong style="color: #cbd5e1; font-size: 14px;" id="natServicoNome">Queratina / Selagem</strong></div>
            <div style="margin-bottom: 10px;"><span style="color: #64748b; font-size: 11px; font-weight: bold; display: block; text-transform: uppercase;">DATA/HORA:</span><strong style="color: #cbd5e1; font-size: 14px;" id="natDataEmissao">2026-11-10 às 00:00</strong></div>
        </div>

        <!-- Módulo de Código QR Dinâmico para Auditoria -->
        <div style="display: flex; justify-content: center; margin-bottom: 20px;">
            <div style="background: #fff; padding: 4px; border-radius: 4px;">
                <img id="natQrCode" src="" alt="QR" style="display: block; width: 80px; height: 80px;">
            </div>
        </div>

        <!-- 💰 EXIBIÇÃO DE VALORES E DESCONTOS CONDICIONAIS -->
        <div style="margin-top: 25px; border-top: 1px dashed #334155; padding-top: 15px; font-family: sans-serif;">
            
       
            <div id="printBlocoPremiumPrecos" style="display: none; flex-direction: column; gap: 6px; margin-bottom: 15px; padding: 0 5px; text-align: left;">
                <div style="display: flex; justify-content: space-between; font-size: 13px; color: #94a3b8;">
                    <span>Preço de Tabela (Original):</span>
                    <span id="natSubtotal" style="color: #cbd5e1; font-weight: bold;">0 Kz</span>
                </div>
                <div style="display: flex; justify-content: space-between; font-size: 13px; color: #ef4444; font-weight: bold;">
                    <span>Desconto Membro VIP (-2%):</span>
                    <span id="natDescontoValor">- 0 Kz</span>
                </div>
            </div>

            <!-- BARRA VERDE DE FATURAÇÃO (Exibe o Preço Final Real Cobrado) -->
            <div style="background-color: #0b1a30; border-left: 4px solid #22c55e; padding: 15px; display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
                <span style="color: #22c55e; font-size: 11px; font-weight: bold; text-transform: uppercase;">
                    <span id="natTextoTotalTitulo">TOTAL PAGO</span>:
                </span>
                <strong style="color: #22c55e; font-size: 20px;" id="natTotalFinal">14.000,00 Kz</strong>
            </div>

        </div>

        <!-- Rodapé Centralizado com a Localização do Huambo -->
        <div style="text-align: center; font-size: 12px; color: #38bdf8; font-family: sans-serif; line-height: 1.5; margin-bottom: 25px; border-top: 1px dashed #334155; padding-top: 15px;">
            Obrigado pela preferência, tua pandula!<br>
            <small style="color: #64748b; font-size: 11px; display: block; margin-top: 5px;"> Barbearia Branca localizado no Huambo<br>Bairro de São Luís / junto a igreja Ieca</small>
        </div>

        <!-- Botão Azul Claro de Confirmação Igualzinho ao da sua Imagem -->
        <div class="no-print">
            <button onclick="window.print()" style="width: 100%; background-color: #38bdf8 !important; color: #0b1a30 !important; border: none; padding: 12px; font-size: 14px; font-weight: bold; border-radius: 8px; cursor: pointer; text-transform: uppercase; display: flex; align-items: center; justify-content: center; gap: 8px; font-family: sans-serif; box-shadow: 0 4px 10px rgba(56, 189, 248, 0.25);">
                🖨️ CONFIRMAR IMPRESSÃO
            </button>
        </div>

    </div>
</div>

<style>
/* ⚡ CONTROLO CORPORATIVO DE IMPRESSÃO - CONVERTE PARA BRANCO ECONÓMICO NO PAPEL */
@media print {
    body > * { display: none !important; }
    #faturaPainelNatural { display: flex !important; background: #ffffff !important; position: absolute; left: 0; top: 0; width: 100%; height: 100%; padding: 0; }
    #faturaPainelNatural > div { background-color: #ffffff !important; color: #0f172a !important; border: 1px solid #cbd5e1 !important; border-radius: 0px !important; box-shadow: none !important; margin: 40px auto !important; width: 90% !important; max-width: 440px !important; padding: 20px !important; }
    #faturaPainelNatural h2 { color: #0b1a30 !important; }
    #faturaPainelNatural strong { color: #0f172a !important; }
    #faturaPainelNatural span { color: #475569 !important; }
    .no-print, #faturaPainelNatural button, #faturaPainelNatural span[onclick] { display: none !important; }
    * { -webkit-print-color-adjust: exact !important; print-color-adjust: exact !important; }
}
</style>

















        <!-- BOTÃO INTERATIVO ÚNICO -->
        <div style="text-align: center; margin-bottom: 25px;">
            <button id="btnToggleFuncionarios" onclick="toggleFuncionarios()" style="background: linear-gradient(135deg, #3b82f6, #1d4ed8); color: #ffffff; border: none; padding: 12px 26px; font-size: 15px; font-weight: bold; border-radius: 8px; cursor: pointer; box-shadow: 0 4px 10px rgba(0,0,0,0.3); transition: all 0.3s ease; text-transform: uppercase; letter-spacing: 0.5px; outline: none;">
                 Ver Funcionários
            </button>
        </div>

        <?php
        // Carrega os funcionários direto do banco via PDO (Conexao.php)
        include_once("Conexao.php");
        try {
            $query_cards = $pdo->query("SELECT * FROM funcionarios ORDER BY nome ASC");
            $lista_cards = $query_cards->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            $lista_cards = [];
        }
        ?>



















<!-- =========================================================================
     📋 LISTA DE PROFISSIONAIS (SISTEMA COM PREÇO DINÂMICO E RECOLHIDO NO F5)
     ========================================================================= -->
     <div id="secaoFuncionarios" style="margin: 0 auto 30px auto; max-width: 1200px; padding: 0 15px; display: none; box-sizing: border-box;">
     <h3 style="color: #fff; font-size: 14px; margin-bottom: 15px; text-transform: uppercase; letter-spacing: 1px; text-align: left;">
          Status dos Profissionais:
     </h3>
     
     <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(240px, 1fr)); gap: 15px; box-sizing: border-box; width: 100%;">
         <?php if(empty($lista_cards)): ?>
             <p style="color: #94a3b8; grid-column: 1/-1; text-align: center;">Nenhum profissional cadastrado no sistema.</p>
         <?php else: ?>
             <?php foreach($lista_cards as $card): 
                 $nome_f = trim($card['nome']);
                 
                 // 🧠 1. ENGENHARIA DE RASTREAMENTO EM TEMPO REAL
                 $data_hoje_angola = date('Y-m-d');
                 $hora_agora_angola = date('H:i:s');
                 $esta_ocupado_agora = false;
 
                 if (isset($pdo)) {
                     // Verifica se o mestre possui um agendamento CONFIRMADO a decorrer neste momento (janela de 1 hora)
                     $stmt_check_status = $pdo->prepare("
                         SELECT COUNT(*) FROM `pagamentos` 
                         WHERE (`profissional` = ? OR `profissional` = ?)
                           AND `data_servico` = ? 
                           AND `status_atendimento` = 'Confirmado'
                           AND ? BETWEEN `hora_servico` AND ADDTIME(`hora_servico`, '01:00:00')
                     ");
                     $stmt_check_status->execute([$nome_f, "Mestre " . $nome_f, $data_hoje_angola, $hora_agora_angola]);
                     $esta_ocupado_agora = ($stmt_check_status->fetchColumn() > 0);
                 }
 
                 // 🧠 2. TRIAGEM DOS ESTADOS VISUAIS (BARRAGEM OPERACIONAL)
                 $status_banco = trim($card['status']);
                 $esta_ausente = (strpos($status_banco, 'Ausente') !== false || strpos($status_banco, 'Folga') !== false);
                 
                 if ($esta_ausente) {
                     $status_exibir = $status_banco;
                     $corCard = '#ef4444'; // Vermelho Ausente/Folga
                 } elseif ($esta_ocupado_agora) {
                     $status_exibir = 'Atendimento';
                     $corCard = '#f87171'; // Vermelho Claro para Ocupado em Serviço
                 } else {
                     $status_exibir = 'Disponível';
                     $corCard = '#22c55e'; // Verde Livre
                 }
                 
                 // Configurações de Fallback dos Serviços
                 $preco_mestre = floatval(($card['preco'] ?? 0) > 0 ? $card['preco'] : 1500.00);
                 $servico_mestre = !empty($card['tipos_de_servico']) ? $card['tipos_de_servico'] : 'Design e Corte de Barba';
                 $foto_render_dashboard = !empty($card['foto_url']) ? 'uploads/' . $card['foto_url'] : 'https://flaticon.com';
             ?>
                 <!-- CARD INDIVIDUAL COM PASSAGEM DINÂMICA DA BASE DE DADOS -->
                 <div onclick="abrirPautaVisual('<?= htmlspecialchars($nome_f, ENT_QUOTES) ?>', '<?= $esta_ausente ? 'Ausente' : ($esta_ocupado_agora ? 'Atendimento' : 'Disponivel') ?>', <?= $preco_mestre ?>, '<?= htmlspecialchars($servico_mestre, ENT_QUOTES) ?>')" 
                      style="background: #1e293b; border: 1px solid #334155; padding: 15px; border-radius: 8px; cursor: pointer; transition: 0.3s; box-shadow: 0 4px 6px rgba(0,0,0,0.15); display: flex; align-items: center; gap: 12px; box-sizing: border-box; width: 100%;"
                      onmouseover="this.style.borderColor='#eab308'" onmouseout="this.style.borderColor='#334155'">
                     
                     <img src="<?= $foto_render_dashboard ?>" style="width: 45px; height: 45px; object-fit: cover; border-radius: 50%; border: 2px solid <?= $corCard ?>; flex-shrink: 0;">
                     
                     <div style="flex: 1; text-align: left; overflow: hidden;">
                          <div style="display: flex; justify-content: space-between; align-items: center;">
                              <span style="color: #cbd5e1; font-weight: bold; font-size: 13.5px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;"><?= htmlspecialchars($nome_f); ?></span>
                          </div>
                          <span style="font-weight: bold; color: <?= $corCard; ?>; font-size: 11px; display: block; margin-top: 2px;">
                              ● <?= htmlspecialchars($status_exibir); ?>
                          </span>
                          <div style="margin-top: 4px; font-size: 9px; color: #64748b; text-transform: uppercase;">Ver agenda →</div>
                     </div>
                 </div>
             <?php endforeach; ?>
         <?php endif; ?>
     </div>
 
     <!-- 🟢 MAPA DE HORÁRIOS DINÂMICO (INICIA TOTALMENTE FECHADO COM DISPLAY: NONE NO F5) -->
     <div id="container_pauta_dinamica" style="display: none !important; background: #0f172a; border: 1px solid #eab308; padding: 20px; border-radius: 12px; margin-top: 20px; box-sizing: border-box; width: 100%;">
         <div style="display: flex; justify-content: space-between; margin-bottom: 15px; align-items: center; width: 100%;">
             <h4 id="titulo_pauta_nome" style="color: #eab308; margin: 0; font-family: sans-serif; text-transform: uppercase; font-size: 14px; letter-spacing: 0.5px;">Agenda</h4>
             <button onclick="document.getElementById('container_pauta_dinamica').style.setProperty('display', 'none', 'important')" style="background:none; border:none; color:#ef4444; cursor:pointer; font-weight:bold; font-size: 12px; letter-spacing: 0.5px;">X FECHAR</button>
         </div>
         <div id="grade_vagas_real" style="display: grid; grid-template-columns: repeat(auto-fill, minmax(110px, 1fr)); gap: 10px; box-sizing: border-box; width: 100%;"></div>
     </div>
 </div>
 


 <?php
// =========================================================================
// 📅 ALIMENTAÇÃO DA PAUTA VISUAL — LIMPEZA E UNIFICAÇÃO DE STRINGS MESTRE
// =========================================================================
$hoje_sql = date('Y-m-d');
$pauta_ocupada = [];

if (isset($pdo)) {
    try {
        // Puxa as marcações de hoje ordenadas por horário
        $stmt_pauta = $pdo->prepare("
            SELECT `profissional`, `hora_servico`, `servico`, `valor` 
            FROM `pagamentos` 
            WHERE `data_servico` = ? AND `status_atendimento` != 'Cancelado'
        ");
        $stmt_pauta->execute([$hoje_sql]);
        $agendamentos_hoje = $stmt_pauta->fetchAll(PDO::FETCH_ASSOC);

        foreach ($agendamentos_hoje as $ag) {
            // 🟢 1. NORMALIZAÇÃO DO NOME DO PROFISSIONAL:
            // Remove quaisquer prefixos "Mestre", "Prof." para isolar o nome raiz (ex: "Magui", "Dalton")
            $prof_bruto = trim($ag['profissional']);
            $prof_raiz  = trim(str_replace(['Mestre Prof. ', 'Mestre ', 'Prof. ', 'Mestre', 'Prof.'], '', $prof_bruto));

            // 🟢 2. NORMALIZAÇÃO DA HORA:
            // Garante o formato '09:00' removendo segundos se o MySQL retornar '09:00:00'
            $hora_limpa = date('H:i', strtotime($ag['hora_servico']));

            // 🟢 3. NORMALIZAÇÃO DO SERVIÇO:
            // Limpa o nome do serviço removendo o gateway "(MCX Express)" ou "(Unitel Money)"
            $servico_bruto = $ag['servico'];
            $servico_exibir = trim(preg_replace('/\s*\(.*?\)/', '', $servico_bruto));
            
            $preco_formatado = number_format(floatval($ag['valor']), 2, ',', '.') . " Kz";

            // 🟢 4. ALIMENTAÇÃO DO DICIONÁRIO TRIDIMENSIONAL DO JAVASCRIPT:
            // Guardamos o agendamento em todas as chaves possíveis para o JavaScript achar o match de qualquer forma
            $dados_vaga = [
                'servico' => $servico_exibir,
                'preco'   => $preco_formatado
            ];

            $pauta_ocupada[$prof_bruto][$hora_limpa] = $dados_vaga;
            $pauta_ocupada[$prof_raiz][$hora_limpa]  = $dados_vaga;
            $pauta_ocupada["Prof. " . $prof_raiz][$hora_limpa] = $dados_vaga;
            $pauta_ocupada["Mestre " . $prof_raiz][$hora_limpa] = $dados_vaga;
            $pauta_ocupada["Mestre Prof. " . $prof_raiz][$hora_limpa] = $dados_vaga;
        }
    } catch (PDOException $e) {
        $pauta_ocupada = [];
    }
}
?>



 <!-- =========================================================================
      🟩 ENGINE JAVASCRIPT: FILTRAGEM DINÂMICA DE PREÇOS MUTÁVEIS E OCUPAÇÃO
      ========================================================================= -->
      <script>
const pautaOcupadaDB = <?= json_encode($pauta_ocupada ?? []) ?>;

function abrirPautaVisual(nome, status) {
    const container = document.getElementById('container_pauta_dinamica');
    const grade = document.getElementById('grade_vagas_real');
    document.getElementById('titulo_pauta_nome').innerText = "📅 Agenda de Hoje: " + nome;
    
    grade.innerHTML = '';
    container.style.display = 'block';

    if (status === 'Ausente' || status === 'Folga') {
        grade.innerHTML = '<p style="color:#ef4444; grid-column: 1/-1; padding:20px; font-weight:bold; text-align:center;">Profissional indisponível hoje.</p>';
        return;
    }

    // 🟢 1. HORÁRIO REAL DE ANGOLA
    const agora = new Date();
    const horaAtualStr = String(agora.getHours()).padStart(2, '0') + ":" + String(agora.getMinutes()).padStart(2, '0');

    // 🟢 2. TODAS AS HORAS DO TELEMÓVEL
    const slotsHorasGeral = ['08:00', '09:00', '10:00', '11:00', '12:00', '13:00', '14:00', '15:00', '16:00', '17:00', '18:00', '19:00', '20:00', '21:00', '22:00'];

    slotsHorasGeral.forEach(hora => {
        const horaLimpa = hora.trim();
        const apenasHoraCard = horaLimpa.split(':')[0]; // ✨ AGORA É TEXTO: Extrai "14" de "14:00"

        // Limpa variações de títulos no nome do profissional
        const nomeLimpoRaiz = nome.replace(/Mestre\s+|Prof\.\s+|Mestre\s+Prof\.\s+/g, '').trim();
        
        let infoOcupado = null;
        let horaOcupadaReal = "";

        const chavesPossiveis = [
            nome.trim(), 
            nomeLimpoRaiz, 
            "Prof. " + nomeLimpoRaiz, 
            "Mestre " + nomeLimpoRaiz, 
            "Mestre Prof. " + nomeLimpoRaiz
        ];
        
        // ✨ PROCURA POR VALIDAÇÃO DE TEXTO DA HORA CHEIA
        for (let chave of chavesPossiveis) {
            if (pautaOcupadaDB[chave]) {
                for (let horaBanco in pautaOcupadaDB[chave]) {
                    const apenasHoraBanco = horaBanco.trim().split(':')[0]; // ✨ AGORA É TEXTO: Extrai "14" de "14:20"
                    
                    // Comparação real de texto (Strings batem 100% de forma segura agora!)
                    if (apenasHoraCard === apenasHoraBanco) {
                        infoOcupado = pautaOcupadaDB[chave][horaBanco];
                        horaOcupadaReal = horaBanco.trim().substring(0, 5); // Ex: "14:20"
                        break;
                    }
                }
            }
            if (infoOcupado) break;
        }
        
        const ocupado = infoOcupado !== null;
        
        // Regra visual: esconde slots passados que estejam livres
        if (horaLimpa < horaAtualStr && !ocupado) {
            return; 
        }

        const slot = document.createElement('div');
        
        slot.style.cssText = `
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 14px 18px;
            margin-bottom: 8px;
            border-radius: 10px;
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif;
            box-sizing: border-box;
            width: 100%;
            border-left: 5px solid ${ocupado ? '#ef4444' : '#22c55e'};
            background: ${ocupado ? 'rgba(239, 68, 68, 0.06)' : 'rgba(34, 197, 94, 0.04)'};
            transition: 0.2s;
        `;

        if (ocupado) {
            // 🔴 TRANCADO E BARRADO AUTOMÁTICO
            slot.style.cursor = 'not-allowed';
            slot.innerHTML = `
                <div style="text-align: left;">
                    <strong style="color: #94a3b8; font-size: 15px; font-family: monospace;">${horaOcupadaReal}</strong>
                    <span style="color: #f87171; font-size: 12px; font-weight: bold; margin-left: 10px;">• Reservado</span>
                </div>
                <div style="text-align: right;">
                    <span style="color: #cbd5e1; font-size: 12px; font-weight: bold; display: block; text-transform: uppercase;">${infoOcupado.servico}</span>
                    <span style="color: #f472b6; font-size: 11px; font-weight: bold;">${infoOcupado.preco}</span>
                </div>
            `;
        } else {
            // 🟢 DISPONÍVEL
            slot.style.cursor = 'pointer';
            slot.innerHTML = `
                <div>
                    <strong style="color: #fff; font-size: 15px; font-family: monospace;">${horaLimpa}</strong>
                    <span style="color: #22c55e; font-size: 12px; font-weight: bold; margin-left: 10px;">• Disponível</span>
                </div>
                <div style="background: #22c55e; color: #040209; font-size: 11px; font-weight: bold; padding: 6px 12px; border-radius: 20px; text-transform: uppercase; letter-spacing: 0.5px;">
                    Reservar
                </div>
            `;

            slot.onmouseover = () => { slot.style.background = 'rgba(34, 197, 94, 0.1)'; slot.style.transform = 'translateY(-1px)'; };
            slot.onmouseout  = () => { slot.style.background = 'rgba(34, 197, 94, 0.04)'; slot.style.transform = 'translateY(0)'; };
            
            slot.onclick = () => {
                const inputHora = document.getElementById('inputHoraServico');
                const inputFunc = document.getElementById('inputFuncionario');
                
                if (inputHora) inputHora.value = horaLimpa;
                if (inputFunc) {
                    inputFunc.value = nome.trim();
                    if (inputFunc.tagName === 'SELECT') {
                        for (let i = 0; i < inputFunc.options.length; i++) {
                            if (inputFunc.options[i].value.trim() === nome.trim()) {
                                inputFunc.selectedIndex = i;
                                break;
                            }
                        }
                    }
                }
                window.scrollTo({ top: 0, behavior: 'smooth' });
            };
        }
        
        grade.appendChild(slot);
    });
    
    container.scrollIntoView({ behavior: 'smooth' });
}
</script>






<?php
// Consulta focada nas próximas pautas baseando-se na tabela real 'pagamentos'
try {
    $data_filtro_sql = date('Y-m-d');
    $stmt_ativos = $pdo->prepare("
        SELECT 
            p.`id_pagamento`, 
            p.`servico`, 
            p.`valor` AS preco_base, 
            p.`desconto`,
            p.`valor_liquido`,
            p.`data_servico`, 
            p.`hora_servico`,
            p.`status_trabalho`,
            p.`status_atendimento`,
            p.`tipo_pagamento`,
            p.`cliente_telefone`,
            f.`nome` AS nome_profissional_real,
            p.`profissional` AS prof_bruto,
            IFNULL(c.`saldo_acumulado`, 0.00) AS troco_carteira_acumulado
        FROM `pagamentos` p
        LEFT JOIN `funcionarios` f ON p.`profissional` = f.`id_funcionario`
        LEFT JOIN `carteira_saldos_clientes` c ON p.`cliente_telefone` = c.`telefone_cliente`
        WHERE p.`data_servico` >= ? AND p.`status_atendimento` != 'Cancelado'
        ORDER BY p.`data_servico` ASC, p.`hora_servico` ASC
    ");
    $stmt_ativos->execute([$data_filtro_sql]);
    $lista_pedidos = $stmt_ativos->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $lista_pedidos = [];
}
?>

<div style="background: #0d061a; padding: 25px; border-radius: 16px; border: 1px solid #3b0764; margin-top: 30px; box-shadow: 0 8px 32px rgba(0,0,0,0.4); font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;">
    
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; border-bottom: 1px dashed rgba(168, 85, 247, 0.3); padding-bottom: 15px;">
        <h3 style="margin: 0; color: #eab308; font-size: 15px; text-transform: uppercase; letter-spacing: 0.5px; display: flex; align-items: center; gap: 8px;">
            Marcações Recente:
        </h3>
        <span style="background: rgba(234, 179, 8, 0.1); color: #eab308; font-size: 12px; font-weight: bold; padding: 4px 12px; border-radius: 20px;">
            <?= count($lista_pedidos) ?> Agendados
        </span>
    </div>

    <?php if (empty($lista_pedidos)): ?>
        <p style="color: #94a3b8; text-align: center; font-size: 14px; padding: 30px 10px; margin: 0;">
            📭 Nenhuma marcação agendada para hoje ou dias posteriores.
        </p>
    <?php else: ?>
        
        <!-- CONTAINER COESOR COM LIMITE DE ALTURA E ROLAGEM SLATE INTERNA -->
        <div class="tabela-scroll-container" style="max-height: 450px; overflow-y: auto; width: 100%; padding-right: 5px;">
            
        <!-- 🖥️ VISUALIZAÇÃO DESKTOP: TABELA PARA COMPUTADORES -->
        <table class="tabela-desktop-anonima" style="width: 100%; border-collapse: collapse; text-align: left; font-size: 13px; color: #cbd5e1;">
            <thead style="position: sticky; top: 0; background: #0d061a; z-index: 10;">
                <tr style="border-bottom: 2px solid #3b0764; color: #a855f7; text-transform: uppercase; font-size: 11px; letter-spacing: 0.5px;">
                    <th style="padding: 12px 8px;">ID Vaga</th>
                    <th style="padding: 12px 8px;">Profissional</th>
                    <th style="padding: 12px 8px;">Serviço Requerido</th>
                    <th style="padding: 12px 8px;">Valores Base</th>
                    <th style="padding: 12px 8px; text-align: center;">Estado Liquidação</th>
                    <th style="padding: 12px 8px; text-align: right;">Sobra em Carteira</th>
                    <th style="padding: 12px 8px; text-align: right; padding-right: 15px;">Data / Hora</th>
                </tr>
            </thead>
            <tbody>



            
                <?php 
                // Garante que o loop só roda se existirem registos extraídos do banco de dados
                if (!empty($lista_pedidos) && is_array($lista_pedidos)):
                    foreach ($lista_pedidos as $pedido): 
                        
                        // 1. EXTRAÇÃO E TRATAMENTO SEGURO DE VARIÁVEIS NA RAÍZ DO LOOP
                        $id_exibir = $pedido['id_pagamento'] ?? $pedido['id'] ?? 0;
                        
                        // Captura e formatação do Profissional
                        $prof_exibir = !empty($pedido['nome_profissional_real']) ? $pedido['nome_profissional_real'] : ($pedido['prof_bruto'] ?? 'Mestre Técnico');
                        if (is_numeric($prof_exibir)) {
                            $prof_exibir = "Mestre Técnico #" . $prof_exibir;
                        }

                        // Limpeza de parênteses no Nome do Serviço
                        $servico_bruto = $pedido['servico'] ?? '';
                        $servico_limpo = trim(preg_replace('/\s*\(.*?\)/', '', $servico_bruto));

                        // Resgate dos fluxos de faturamento reais
                        $preco_no_registo  = floatval($pedido['valor'] ?? 0);
                        $valor_liquido     = floatval($pedido['valor_liquido'] ?? 0);
                        $troco_carteira    = floatval($pedido['troco_carteira_acumulado'] ?? 0);

                        // Definição matemática dinâmica do preço final
                        if ($valor_liquido > 0) {
                            $preco_final_atendimento = $valor_liquido;
                        } elseif ($preco_no_registo > 0) {
                            $preco_final_atendimento = $preco_no_registo;
                        } else {
                            $preco_final_atendimento = 1500.00; // Fallback mestre de segurança
                        }

                        // 🧠 TRACKING SÍNCRONO DE CAIXA (Sinaliza se passou ou não pelo unitel.php)
                        $status_atendimento = trim($pedido['status_atendimento'] ?? 'Pendente');
                        $status_trabalho    = strtolower(trim($pedido['status_trabalho'] ?? 'pendente'));

                        if ($status_atendimento === 'Confirmado' && $status_trabalho === 'concluido') {
                            if ($troco_carteira > 0) {
                                $badge_estado_financeiro = "<span style='background:rgba(56, 189, 248, 0.12); color:#38bdf8; border:1px solid rgba(56,189,248,0.3); padding:4px 10px; border-radius:6px; font-weight:bold; font-size:11px;'>⚡ ADIANTADO</span>";
                            } else {
                                $badge_estado_financeiro = "<span style='background:rgba(34, 197, 94, 0.12); color:#4ade80; border:1px solid rgba(34,197,94,0.3); padding:4px 10px; border-radius:6px; font-weight:bold; font-size:11px;'>✓ PAGO</span>";
                            }
                        } else {
                            $badge_estado_financeiro = "<span style='background:rgba(239, 68, 68, 0.12); color:#f87171; border:1px solid rgba(239,68,68,0.3); padding:4px 10px; border-radius:6px; font-weight:bold; font-size:11px;'>⏳ NÃO PAGO</span>";
                        }

                        // Alinhamento rigoroso de fuso horário, datas e horas
                        $hora_bruta = $pedido['hora_servico'] ?? $pedido['horario'] ?? '00:00';
                        $hora_exibir = date('H:i', strtotime($hora_bruta));

                        $data_sessao = $pedido['data_servico'] ?? date('Y-m-d');
                        $data_hoje = date('Y-m-d');
                        $data_amanha = date('Y-m-d', strtotime('+1 day'));

                        if ($data_sessao === $data_hoje) {
                            $data_badge = "Hoje";
                        } elseif ($data_sessao === $data_amanha) {
                            $data_badge = "Amanhã";
                        } else {
                            $data_badge = date('d/m/Y', strtotime($data_sessao));
                        }

                        // Formatações finais em Kwanzas
                        $preco_formatado = number_format($preco_final_atendimento, 2, ',', '.') . " Kz";
                        $troco_formatado = number_format($troco_carteira, 2, ',', '.') . " Kz";
                ?>
                    
                    <!-- 🖥️ RENDERIZAÇÃO DA LINHA DO COMPUTADOR -->
                    <tr class="linha-pc-segura" style="border-bottom: 1px solid rgba(59, 7, 100, 0.4);">
                        <td style="padding: 14px 8px; font-weight: bold; color: #64748b;">#<?= $id_exibir ?></td>
                        <td style="padding: 14px 8px; font-weight: bold; color: #fff;">Mestre <?= htmlspecialchars($prof_exibir) ?></td>
                        <td style="padding: 14px 8px; color: #cbd5e1; font-weight: 500;"><?= htmlspecialchars($pedido['servico']) ?></td>
                        <td style="padding: 14px 8px; font-family: monospace; font-weight: bold; color: #fff;"><?= $preco_formatado ?></td>
                        <td style="padding: 14px 8px; text-align: center;"><?= $badge_estado_financeiro ?></td>
                        <td style="padding: 14px 8px; text-align: right; font-family: monospace; font-weight: bold; color: <?= $troco_carteira > 0 ? '#4ade80' : '#64748b' ?>;">
                            <?= $troco_formatado ?>
                        </td>
                        <td style="padding: 14px 8px; text-align: right; padding-right: 15px; font-family: monospace; color: #38bdf8;">
                            <?= $data_badge ?> às <?= $hora_exibir ?>
                        </td>
                    </tr>

                    <!-- 📱 RENDERIZAÇÃO DO CARD DO TELEMÓVEL -->
                    <div class="card-mobile-anonimo" style="display: none;">
                        <div style="display: flex; justify-content: space-between; align-items: center; border-bottom: 1px dashed rgba(168, 85, 247, 0.2); padding-bottom: 8px; margin-bottom: 10px;">
                            <span style="color: #64748b; font-weight: bold; font-size: 13px;">Cadeira #<?= $id_exibir ?></span>
                            <span style="color: #38bdf8; font-family: monospace; font-size: 12px; font-weight: bold;"><?= $data_badge ?> às <?= $hora_exibir ?></span>
                        </div>
                        <div style="text-align: left; font-size: 13px; line-height: 1.6;">
                            <p style="margin: 3px 0; color: #cbd5e1;">💈 <b>Profissional:</b> Mestre <?= htmlspecialchars($prof_exibir) ?></p>
                            <p style="margin: 3px 0; color: #a855f7;">💇 <b>Serviço:</b> <?= htmlspecialchars($pedido['servico']) ?></p>
                            
                            <div style="margin-top: 8px; display: flex; justify-content: space-between; align-items: center; border-top: 1px dashed rgba(255,255,255,0.05); padding-top: 8px;">
                                <div><?= $badge_estado_financeiro ?></div>
                                <b style="color: #eab308; font-size: 15px; font-family: monospace;"><?= $preco_formatado ?></b>
                            </div>
                            
                            <?php if ($troco_carteira > 0): ?>
                            <div style="margin-top: 8px; background: rgba(34, 197, 94, 0.1); border: 1px solid rgba(34, 197, 94, 0.2); padding: 6px 12px; border-radius: 6px; display: flex; justify-content: space-between; align-items: center;">
                                <span style="color: #4ade80; font-size: 10px; font-weight: bold;">💳 CRÉDITO DISPONÍVEL:</span>
                                <strong style="color: #4ade80; font-family: monospace;"><?= $troco_formatado ?></strong>
                            </div>
                        <?php else: ?>
                            <p style="margin: 5px 0 0 0; color: #64748b; font-size: 11px; text-transform: uppercase; text-align: right;">Sem troco acumulado</p>
                        <?php endif; ?>
                    </div>
                </div> <!-- 🟢 FECHA O CARD MOBILE SEGURO -->

            <?php 
                endforeach; // 🟢 FECHA O FOREACH DOS AGENDAMENTOS
            endif; // 🟢 FECHA O IF (!EMPTY($LISTA_PEDIDOS))
            ?>
        </tbody>
    </table>
</div> <!-- 🟢 FECHA A TABELA SCROLL CONTAINER -->
<?php 
endif; // 🟢 FECHA O IF PRINCIPAL QUE VERIFICA SE A LISTA EXISTE
?>
</div> <!-- 🟢 FECHA O CARD MASTER DO MONITOR DE FLUXO -->

<!-- =========================================================================
🎨 CSS DE ADAPTAÇÃO MOBILE-FIRST CRÍTICA DO PWA (COMPATIBILIDADE TOTAL)
========================================================================= -->
<style>
.tabela-scroll-container::-webkit-scrollbar { width: 5px; }
.tabela-scroll-container::-webkit-scrollbar-track { background: rgba(0, 0, 0, 0.1); }
.tabela-scroll-container::-webkit-scrollbar-thumb { background: #3b0764; border-radius: 10px; }

@media (max-width: 768px) {
.tabela-desktop-anonima thead { display: none !important; }
.tabela-desktop-anonima tbody { display: block; width: 100%; }
.linha-pc-segura { display: none !important; }

.card-mobile-anonimo { 
display: block !important; 
background: #111827; 
margin-bottom: 12px; 
border-left: 4px solid #ca8a04; 
border-top: 1px solid #1e293b;
border-right: 1px solid #1e293b;
border-bottom: 1px solid #1e293b;
border-radius: 12px; 
padding: 15px; 
box-sizing: border-box; 
width: 100%;
box-shadow: 0 4px 10px rgba(0,0,0,0.2);
}
}
</style>



    <!-- ÁREA DE EXIBIÇÃO: Onde as fotos salvas vão aparecer depois -->
    <div class="grid-container" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); gap: 15px; margin-top: 30px;">
        <?php
        // Bloco opcional para listar as fotos já guardadas na tabela 'anuncios'
        try {
            $queryFotos = $pdo->query("SELECT * FROM anuncios ORDER BY id_announcement DESC");
            $listaFotos = $queryFotos->fetchAll();
            foreach ($listaFotos as $f):
        ?>
            <div class="aba-item" style="background: #1e293b; padding: 10px; border-radius: 8px; text-align: center;">
                <!-- O campo 'image_url' deve bater com o nome da coluna da sua tabela de anúncios -->
                <img src="<?php echo htmlspecialchars($f['image_url']); ?>" style="width: 100% !important; height: 160px !important; object-fit: cover !important; border-radius: 16px !important; display: block !important; margin-bottom: 8px !important;">
                <strong style="font-size: 12px; display: block; margin-top: 8px;"><?php echo htmlspecialchars($f['title']); ?></strong>
            </div>
        <?php 
            endforeach;
        } catch (PDOException $e) {
            echo "<p style='font-size:12px; opacity:0.5; grid-column: 1/-1;'></p>";
        }
        ?>
    </div>
</div>















<!-- =========================================================================
     📸 SECÇÃO: PHOTOS & VÍDEOS COM EXEMPLOS REAIS, DINÂMICOS E DIRECIONAMENTO ISOLADO
     ========================================================================= -->
     <div id="secao-photos" class="aba-conteudo" style="display: none; width:92%; margin:20px auto; position: relative; font-family: 'Segoe UI', -apple-system, sans-serif;">
    
     <!-- Cabeçalho da Galeria com Botão Voltar (X) Integrado -->
     <div class="aba-galeria" style="background: linear-gradient(135deg, #10383b, #1d4d50); color:white; padding:20px; border-radius:10px; margin-bottom:20px; text-align:center; position: relative;">
         
         <!-- ❌ BOTÃO X VOLTAR: Permite fechar a secção e regressar à Home -->
         <span onclick="alternarAbas('servicos')" style="position: absolute; top: 12px; right: 20px; color: #ef4444; font-size: 26px; font-weight: bold; cursor: pointer; transition: 0.2s;" onmouseover="this.style.color='#f87171'; this.style.transform='scale(1.1)';" onmouseout="this.style.color='#ef4444'; this.style.transform='scale(1)';">
             &times;
         </span>
 
         <h4 style="margin: 0; font-size: 16px; font-weight: bold; text-transform: uppercase; letter-spacing: 0.5px;">Portfólio & Galeria de Tendências</h4>
         <p style="font-size:12px; opacity:0.8; margin-top:4px; margin-bottom: 0;">Exemplos de inspirações e trabalhos executados no Huambo.</p>
     </div>
 
     <!-- 🎛️ PAINÉIS DE UPLOAD (FOTOS & VÍDEOS SEPARADOS) -->
     <div style="display: flex; gap: 20px; max-width: 1100px; margin: 0 auto 30px auto; flex-wrap: wrap; box-sizing: border-box;">
         
         <!-- FORMULÁRIO A: CARREGAR FOTOS -->
         <div class="painel-azul" style="flex: 1; min-width: 280px; background: #0f172a; border: 1px solid #1d4d50; padding: 20px; border-radius: 8px; box-sizing: border-box;">
             <span class="painel-titulo" style="font-size: 14px; font-weight: bold; color: #fff; display: block; margin-bottom: 10px;">📸 Carregar Nova Foto</span>
             <!-- 📸 FORMULÁRIO A: CARREGAR FOTOS -->
             <form action="./guardar_foto.php" method="POST" enctype="multipart/form-data" style="display:flex; flex-direction:column; gap:12px; text-align:left;">
<label style="color: #fff; font-size: 13px; font-weight: bold;">Título do Trabalho:</label>
<input type="text" name="titulo_foto" placeholder="nome da foto" inherit required style="padding: 10px; border-radius: 4px; width: 100%; box-sizing: border-box;">
<label style="color: #fff; font-size: 13px; font-weight: bold;">Escolher Foto:</label>
<input type="file" name="ficheiro_foto" accept="image/*" required style="color: #fff; font-size: 13px;">
<button type="submit" style="background: #10b981; color: white; border: none; padding: 12px; border-radius: 6px; font-weight: bold; cursor: pointer; text-transform: uppercase; font-size: 12px;">Carregar Foto</button>
</form>

<!-- 📹 FORMULÁRIO B: CARREGAR VÍDEOS -->
<!-- 🟢 CORREÇÃO: Direcionado estritamente para guardar-videos.php -->
<form action="./guardar_video.php" method="POST" enctype="multipart/form-data" style="display:flex; flex-direction:column; gap:12px; text-align:left;">
<label style="color: #fff; font-size: 13px; font-weight: bold;">Título do Vídeo:</label>
<input type="text" name="titulo_foto" placeholder="nome do Vídeo" required style="padding: 10px; border-radius: 4px; width: 100%; box-sizing: border-box;">
<label style="color: #fff; font-size: 13px; font-weight: bold;">Escolher Vídeo (MP4/MOV):</label>
<input type="file" name="ficheiro_foto" accept="video/mp4,video/quicktime,video/*" required style="color: #fff; font-size: 13px;">
<button type="submit" style="background: #ca8a04; color: white; border: none; padding: 12px; border-radius: 6px; font-weight: bold; cursor: pointer; text-transform: uppercase; font-size: 12px;">Carregar Vídeo</button>
</form>
         </div>
     </div>
 
       <!-- 🟢 GRADE DE MÍDIAS INTELIGENTE: ROTAÇÃO ALEATÓRIA, FILTRO DE 20 DIAS & SELEÇÃO DINÂMICA -->
<span class="painel-titulo" style="font-size: 14px; font-weight: bold; color: #fff; display: block; margin-bottom: 15px; border-left: 3px solid #10b981; padding-left: 8px; text-align: left;"> Inspirações de Cortes e Trabalhos</span>
<div class="grid-container" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(160px, 1fr)); gap: 15px; width:100%; margin-bottom:40px; box-sizing: border-box;">
    <?php
    try {
        // 🔄 ALEATORIEDADE E AGRUPAMENTO: Muda a posição das fotos a cada atualização e evita duplicados
        $queryFotos = $pdo->query("SELECT * FROM anuncios WHERE tipo_media != 'video' OR tipo_media IS NULL GROUP BY imagem ORDER BY RAND()");
        $listaFotos = $queryFotos->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        try {
            $queryFotos = $pdo->query("SELECT * FROM anuncios WHERE id_anuncio IN (SELECT MAX(id_anuncio) FROM anuncios GROUP BY imagem) ORDER BY RAND()");
            $listaFotos = $queryFotos->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $ex) {
            $listaFotos = [];
        }
    }

    $total_midias_validas = 0;
    $dia_da_semana = (int)date('w'); // 0 (domingo) a 6 (sábado) para alternância temporária

    if (count($listaFotos) > 0): 
        foreach ($listaFotos as $fotoItem): 
            // ⏰ 1. FILTRO DE EXPIRAÇÃO DE 20 DIAS
            $data_alvo = !empty($fotoItem['data_publicacao']) ? $fotoItem['data_publicacao'] : (!empty($fotoItem['data_criacao']) ? $fotoItem['data_criacao'] : null);
            if ($data_alvo) {
                $data_anuncio = new DateTime($data_alvo);
                $data_atual = new DateTime();
                $intervalo = $data_anuncio->diff($data_atual);
                
                // Se a foto tiver 20 ou mais dias, desaparece por definitivo da plataforma
                if ($intervalo->days >= 20) {
                    continue; 
                }

                // 🔄 2. DESAPARECIMENTO TEMPORÁRIO (ALTERNANÇA VISUAL)
                // Oculta dinamicamente certas imagens em dias pares/ímpares se já tiverem mais de 5 dias
                if ($intervalo->days > 5 && ($fotoItem['id_anuncio'] % 2 === 0) && ($dia_da_semana % 2 === 0)) {
                    continue; // Ocultado temporariamente hoje para poupar espaço e rodar o feed
                }
            }

            // Extrai e limpa o nome do arquivo gravado no banco de dados
            $arquivo_banco = trim($fotoItem['imagem'] ?? '');
            $arquivo_limpo = basename($arquivo_banco);
            
            if (empty($arquivo_limpo)) {
                continue; 
            }

            // 🛡️ PROTEÇÃO ANTI-404: Só renderiza se o ficheiro físico estiver na pasta do Render
            if (file_exists("upload/" . $arquivo_limpo)) {
                $img_src_render = "upload/" . $arquivo_limpo;
            } elseif (file_exists($arquivo_limpo)) {
                $img_src_render = $arquivo_limpo;
            } else {
                continue; // Ignora se o ficheiro não existir fisicamente
            }

            $total_midias_validas++;
    ?>
            <!-- 🎴 CARD RESPONSIVO REATIVO PWA (FOTOS) -->
            <div class="aba-item" style="background:#1e293b; padding:12px; border-radius:12px; text-align:center; box-shadow: 0 4px 15px rgba(0,0,0,0.3); border: 1px solid #334155; display: flex; flex-direction: column; justify-content: space-between; min-height: 200px; box-sizing: border-box; transition: transform 0.2s;" onmouseover="this.style.transform='scale(1.02)'" onmouseout="this.style.transform='scale(1)'">
                
                <div style="width: 100%; height: 130px; overflow: hidden; border-radius: 8px; background: #070b12; position: relative; border: 1px solid #233144; display: flex; align-items: center; justify-content: center;">
                    <!-- Renderização fluida de fotografias de cortes -->
                    <img src="<?php echo $img_src_render; ?>" decoding="async" style="width:100%; height:100%; object-fit:cover; display: block; opacity: 1 !important;">
                </div>

                <strong style="color: #fff; font-size:12px; display:block; margin-top:10px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; text-transform: uppercase; font-weight: 600; text-align: left; padding: 0 2px;"><?php echo htmlspecialchars($fotoItem['titulo'] ?? 'Trabalho Aurélius'); ?></strong>
                
                <div style="height: 5px;"></div> 
            </div>
    <?php 
        endforeach;
    endif; 

    if ($total_midias_validas === 0):
    ?>
         <p style="color: #aaa; text-align: center; grid-column: 1 / -1; padding: 25px; background: #1e293b; border-radius: 8px; font-style: italic;">Nenhuma foto ou vídeo carregado na galeria ainda.</p>
         <?php endif; ?>
     </div>
 
     <!-- SEÇÃO DOS PROFISSIONAIS ENVELOPADA (Inicia 100% Oculta) -->
     <div id="secaoFuncionarios" style="margin: 20px auto; max-width: 1200px; padding: 0 15px; display: none !important; visibility: hidden; height: 0; overflow: hidden;">
         <h3 style="color: #fff; font-size: 14px; margin-bottom: 15px; text-transform: uppercase; letter-spacing: 1px;">Status dos Profissionais:</h3>
         <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(220px, 1fr)); gap: 15px;">
             <?php if(empty($lista_cards)): ?>
                 <p style="color: #94a3b8; grid-column: 1/-1;">Nenhum profissional cadastrado no sistema.</p>
             <?php else: ?>
                 <?php foreach($lista_cards as $card): 
                     $corCard = '#22c55e';
                     if (strpos($card['status'], 'Ausente') !== false || strpos($card['status'], 'Folga') !== false) { $corCard = '#ef4444'; }
                     elseif (strpos($card['status'], 'Atendimento') !== false || strpos($card['status'], 'Em') !== false) { $corCard = '#ffaa00'; }
                 ?>
                     <div style="background: #1e293b; border: 1px solid #334155; padding: 15px; border-radius: 8px; display: flex; justify-content: space-between; align-items: center; box-shadow: 0 4px 6px rgba(0,0,0,0.15);">
                         <span style="color: #cbd5e1; font-weight: bold;"><?php echo htmlspecialchars($card['nome']); ?></span>
                         <span id="status-text-<?php echo $card['id_funcionario']; ?>" style="font-weight: bold; color: <?php echo $corCard; ?>;"><?php echo htmlspecialchars($card['status']); ?></span>
                     </div>
                 <?php endforeach; ?>
             <?php endif; ?>
         </div>
     </div>
 </div>
 
 
 
 
 
 
 
 
 
 
 
 
 
 
 













     <!-- Secção Geral de Serviços -->
     <div id="secao-servicos" class="aba-conteudo" style="width:100%; max-width:1200px; margin:0 auto; padding:9 5px;">
    
<!-- Caixa de confirmação de preço (Inicia oculta) -->
<div id="caixa-preco" class="preco-container hidden">
    <h3 id="nome-servico">Serviço Selecionado</h3>
    <p id="valor-servico">0,00 kz</p>
    <span id="faturamentoStatus"></span>
    <button class="btn-confirmar" onclick="enviarMarcacaoParaBanco()"> Fazer Marcação</button>
</div>

<!-- PASSO 1: Sempre aparece primeiro (Categorias) -->
<div id="nivel1">
    <div class="grid-categorias">
        <button class="aba-item" onclick="mostrarNivel2('cortes')">
            <img src="images (31) - Cópia.png" alt=""> Cortes de Cabelo
        </button>
        <button class="aba-item" onclick="mostrarNivel2('pinturas')"> 
            <img src="1777986415454.jpg" alt=""> Pinturas de Cabelo
        </button>
        <button class="aba-item" onclick="mostrarNivel2('sobrancelhas')"> 
            <img src="54.jpg" alt=""> Sobrancelhas
        </button>
        <button class="aba-item" onclick="mostrarNivel2('maquilhagem')"> 
            <img src="54.jpg" alt=""> Maquilhagens
        </button>
        <button class="aba-item" onclick="mostrarNivel2('tratamentos')"> 
            <img src="24509.jpg" alt=""> Tranças
        </button>
        <button class="aba-item" onclick="mostrarNivel2('manicure')"> 
    <img src="1750281718295.jpg" alt=""> Manicure
</button>
        <button class="aba-item" onclick="mostrarNivel2('pedicure')"> 
            <img src="1754574223389.jpg" alt=""> Pedicure
        </button>
    </div>
</div>

<!-- PASSO 2: Inicia oculto via classe 'hidden' (Lista de Serviços) -->
<div id="nivel2" class="hidden">
    <button class="btn-voltar" onclick="voltarParaNivel1()">← Voltar às Categorias</button>
    
    <!-- Sub-grupo: Cortes (Inicia oculto) -->
    <div id="sub-cortes" class="grid-container sub-grupo hidden">
        <button class="aba-item" onclick="exibirPrecoFinal('Corte Francês Cheio', '1.000 kz')"><div class="img-wrapper"><img src="1776692903268.jpg"></div>Corte Francês Cheio</button>
        <button class="aba-item" onclick="exibirPrecoFinal('Corte Francês Vazio', '1.000 kz')"><div class="img-wrapper"><img src="1777201603721.jpg"></div>Corte Francês Vazio</button>
        <button class="aba-item" onclick="exibirPrecoFinal('Corte de Crianças', '800 kz')"><div class="img-wrapper"><img src="1777757951670.jpg"></div>Corte de Crianças</button>
        <button class="aba-item" onclick="exibirPrecoFinal('Cortes Feminino', '1.500 kz')"><div class="img-wrapper"><img src="1777298458880.jpg"></div>Cortes Feminino</button>
        <button class="aba-item" onclick="exibirPrecoFinal('Corte Careca', '500 kz')"><div class="img-wrapper"><img src="1777556066924.jpg"></div>Careca</button>
        <button class="aba-item" onclick="exibirPrecoFinal('Design e Corte de Barba', '1.500 kz')"><div class="img-wrapper"><img src="1776692182096.jpg"></div>Barba</button>
        <button class="aba-item" onclick="exibirPrecoFinal('Outros Estilos de Corte', '3.000 kz')"><div class="img-wrapper"><img src="1777986301625.jpg"></div>Outros</button>

        <button class="aba-item" onclick="exibirPrecoFinal('Cortes com Pintura Black', '2.000 kz')"><div class="img-wrapper"><img src="1777986301625.jpg"></div>Cortes com Pintura black</button>
    </div>

    <!-- Sub-grupo: Pinturas (Inicia oculto) -->
    <div id="sub-pinturas" class="grid-container sub-grupo hidden">
        <button class="aba-item" onclick="exibirPrecoFinal('Pintura Geral Black', '900 kz')"><div class="img-wrapper"><img src="Save (21).jpg"></div>Pintura Geral Black</button>

        <button class="aba-item" onclick="exibirPrecoFinal('Pinturas nas Pontas clorido', '6.500 kz')"><div class="img-wrapper"><img src="1777986265622.jpg"></div>Pinturas nas Pontas clorido</button>

        <button class="aba-item" onclick="exibirPrecoFinal('Pintura Geral Loiro', '6.900 kz')"><div class="img-wrapper"><img src="Save (21).jpg"></div>Pintura Geral Loiro</button>

        <button class="aba-item" onclick="exibirPrecoFinal('Pintura Geral Loiro', '5.500 kz')"><div class="img-wrapper"><img src="1777986265622.jpg"></div>Pintura Geral Loiro</button>

        
    </div>

    <!-- Sub-grupo: Sobrancelhas (Inicia oculto) -->
    <div id="sub-sobrancelhas" class="grid-container sub-grupo hidden">
        <button class="aba-item" onclick="exibirPrecoFinal('Design Simples', '500 kz')"><div class="img-wrapper"><img src="WhatsApp-Image-2021-07-27-at-11.13.51-768x768_50559e15-debf-46ab-86bb-8a217a1bf2f1.jpg"></div>Design Simples</button>

        <button class="aba-item" onclick="exibirPrecoFinal('Aplicação de Henna', '4.500 kz')"><div class="img-wrapper"><img src="sobrancelhas-com-henna-1-278x300.png"></div>Aplicação de Henna</button>

        <button class="aba-item" onclick="exibirPrecoFinal('Design Tatoo', '1.200 kz')"><div class="img-wrapper"><img src="267f5f0795a07c2c267b53553657554e(0).jpg"></div>Design Tatoo</button>

        <button class="aba-item" onclick="exibirPrecoFinal('Design Tatoo lápis', '800 kz')"><div class="img-wrapper"><img src="267f5f0795a07c2c267b53553657554e(0).jpg"></div>Design Tatoo Lápis</button>
    </div>

    <!-- Sub-grupo: Maquilhagem (Inicia oculto) -->
    <div id="sub-maquilhagem" class="grid-container sub-grupo hidden">
        <button class="aba-item" onclick="exibirPrecoFinal('Maquilhagem Social', '10.000 kz')"><div class="img-wrapper"><img src="1777298458880.jpg"></div>Maquilhagem Social</button>

        <button class="aba-item" onclick="exibirPrecoFinal('Maquilhagem Noiva', '40.000 kz')"><div class="img-wrapper"><img src="1777298458880.jpg"></div>Maquilhagem Noiva</button>

        <button class="aba-item" onclick="exibirPrecoFinal('Maquilhagem de Festas', '15.000 kz')"><div class="img-wrapper"><img src="1777298458880.jpg"></div>Maquilhagem de Festas</button>

        <button class="aba-item" onclick="exibirPrecoFinal('Maquilhagem Noturna', '12.000 kz')"><div class="img-wrapper"><img src="1777298458880.jpg"></div>Maquilhagem Noturna</button>
    </div>

    <!-- Sub-grupo: Tratamentos (Inicia oculto) -->
    <div id="sub-tratamentos" class="grid-container sub-grupo hidden">
        <button class="aba-item" onclick="exibirPrecoFinal('Bob americano ', '5.500 kz')"><div class="img-wrapper"><img src="1777298458880.jpg"></div>Bob americano</button>
        <button class="aba-item" onclick="exibirPrecoFinal('Bob Frances', '7.500 kz')"><div class="img-wrapper"><img src="1777298458880.jpg"></div>Bob Frances</button>
        <button class="aba-item" onclick="exibirPrecoFinal('Virada torta', '3.500 kz')"><div class="img-wrapper"><img src="1777298458880.jpg"></div>Virada torta</button>
        <button class="aba-item" onclick="exibirPrecoFinal('Mapunga umbundo', '8.500 kz')"><div class="img-wrapper"><img src="1777298458880.jpg"></div>Mapunga umbundo</button>
        <button class="aba-item" onclick="exibirPrecoFinal('Bob para Crianças', '9.000 kz')"><div class="img-wrapper"><img src="1777298458880.jpg"></div>Bob para Crianças</button>
        <button class="aba-item" onclick="exibirPrecoFinal('Bob angolano', '8.000 kz')"><div class="img-wrapper"><img src="1777298458880.jpg"></div>Bob angolano</button>
        <button class="aba-item" onclick="exibirPrecoFinal('Mapunga', '12.000 kz')"><div class="img-wrapper"><img src="1777298458880.jpg"></div>Mapunga</button>
        <button class="aba-item" onclick="exibirPrecoFinal('Dreid Lox', '11.000 kz')"><div class="img-wrapper"><img src="1777298458880.jpg"></div>Dreid Lox</button>
        <button class="aba-item" onclick="exibirPrecoFinal('Tranças Chuas', '8.600 kz')"><div class="img-wrapper"><img src="1777298458880.jpg"></div>Tranças Chuas</button>
        <button class="aba-item" onclick="exibirPrecoFinal('Virada', '8.650 kz')"><div class="img-wrapper"><img src="1777298458880.jpg"></div>Virada</button>
    </div>

    <!-- Sub-grupo: Manicure (Inicia oculto) -->
    <div id="sub-manicure" class="grid-container sub-grupo hidden">
        <button class="aba-item" onclick="exibirPrecoFinal('Manicure Simples Masculino', '1.200 kz')"><div class="img-wrapper"><img src="1750282483395.jpg"></div>Manicure Simples Masculino</button>

        <button class="aba-item" onclick="exibirPrecoFinal('Aplicação Gel', '3.500 kz')"><div class="img-wrapper"><img src="1750281850375.jpg"></div>Aplicação Gel </button>

        <button class="aba-item" onclick="exibirPrecoFinal('Manutenção de Unhas', '1.000 kz')"><div class="img-wrapper"><img src="1750281735037.jpg"></div>Manutenção de Unhas</button>

        <button class="aba-item" onclick="exibirPrecoFinal('Manicure Completo', '1.200 kz')"><div class="img-wrapper"><img src="1750282483395.jpg"></div>Manicure Completo</button>

        <button class="aba-item" onclick="exibirPrecoFinal('Aplicação Gelinho', '3.500 kz')"><div class="img-wrapper"><img src="1750281850375.jpg"></div>Aplicação Gelinho</button>

        <button class="aba-item" onclick="exibirPrecoFinal('Manutenção Total', '5.000 kz')"><div class="img-wrapper"><img src="1750281735037.jpg"></div>Manutenção Total</button>
    </div>

    <!-- Sub-grupo: Pedicure (Inicia oculto) -->
    <div id="sub-pedicure" class="grid-container sub-grupo hidden">
        <button class="aba-item" onclick="exibirPrecoFinal('Pedicure Simples masculino', '800 kz')"><div class="img-wrapper"><img src="1753021732718.jpg"></div>Pedicure Simples Masculino</button>

        <button class="aba-item" onclick="exibirPrecoFinal('Pedicure Simples Feminino', '800 kz')"><div class="img-wrapper"><img src="1754574216379.jpg"></div>Pedicure Simples Feminino</button>

        <button class="aba-item" onclick="exibirPrecoFinal('Gelinho símples Masculino', '1.300 kz')"><div class="img-wrapper"><img src="1753021732718.jpg"></div>Gelinho símples Masculino</button>

        <button class="aba-item" onclick="exibirPrecoFinal('Gelinho símples Feminino', '2.000 kz')"><div class="img-wrapper"><img src="1754574216379.jpg"></div>Gelinho símples Feminino</button>

        <button class="aba-item" onclick="exibirPrecoFinal('Pedicuri de Gel', '1.800 kz')"><div class="img-wrapper"><img src="1753021732718.jpg"></div>Pedicuri de Gel </button>

        <button class="aba-item" onclick="exibirPrecoFinal('Manicuri de Gel', '2.800 kz')"><div class="img-wrapper"><img src="1753021732718.jpg"></div>Manicuri de Gel </button>

        <button class="aba-item" onclick="exibirPrecoFinal('Desenhos nas Unhas', '2.800 kz')"><div class="img-wrapper"><img src="1753021732718.jpg"></div>Desenhos nas Unhas </button>


        <button class="aba-item" onclick="exibirPrecoFinal('Spa Completo dos Pés', '4.000 kz')"><div class="img-wrapper"><img src="1754574216379.jpg"></div>Spa Completo dos Pés</button>
    </div>
</div>
</div>
</div>


<?php
// 1. IMPORTA O SEU CONEXAO.PHP ORIGINAL (PDO) NO TOPO OU ANTES DO BLOCO VISUAL
include_once("Conexao.php");

// Garante que o controlo de sessão está ativo para ler o ID do utilizador logado
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

try {
    // Busca a lista atualizada de todos os funcionários direto do banco
    $id_barbearia_logada = isset($_SESSION['usuario_id']) ? intval($_SESSION['usuario_id']) : 20;

    // 🟢 CONVERSÃO REATIVA PARA PDO: Usa o objeto $pdo do teu Conexao.php mestre
    $sql_cards = "SELECT * FROM `anuncios` 
                  WHERE `id_barbearia` = :id_logada 
                     OR `id_barbearia` = 20 
                  ORDER BY `id_anuncio` DESC";
                  
    $stmt_cards = $pdo->prepare($sql_cards);
    $stmt_cards->execute([
        ':id_logada' => $id_barbearia_logada
    ]);
    
    // Armazena os resultados num array limpo para ler no teu HTML em baixo
    $lista_cards = $stmt_cards->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    // Captura falhas de conexão de forma profissional sem quebrar o ecrã
    echo "<div style='color:#ef4444; padding:10px;'>⚠️ Erro ao carregar anúncios: " . htmlspecialchars($e->getMessage()) . "</div>";
    $lista_cards = [];
}
?>

<!-- =================================================================
     🔮 PAINEL DE DICAS EXECUTIVAS: PONTOS DE PAGAMENTO E FREEMIUM (SAAS VIP)
     ================================================================= -->
     <div style="margin-top: 40px; text-align: left;">
     <h3 style="font-size: 16px; font-weight: bold; margin-bottom: 20px; color: #38bdf8; text-transform: uppercase; letter-spacing: 0.5px; border-left: 3px solid #38bdf8; padding-left: 10px;">
         Como fazer a sua marcação de forma rápida e Segura..??
     </h3>
 
     <!-- Configuração da Grid Expandida com Efeitos Radiais Ativos nos Cartões -->
     <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 15px; margin-bottom: 25px;">
         
         <!-- Passo 1 -->
         <div class="passo-card-neon">
             <div class="emoji-glow">✍️</div>
             <h4>1. Identifique-se</h4>
             <p>Escreva o seu nome no formulário do topo e selecione o seu profissional de preferência no ecrã.</p>
         </div>
 
         <!-- Passo 2 -->
         <div class="passo-card-neon">
             <div class="emoji-glow">💇‍♂️</div>
             <h4>2. Escolha o Serviço</h4>
             <p>Clique no botão <b>"Serviços"</b> no menu superior, escolha a categoria e selecione o corte ou tratamento ideal.</p>
         </div>
 
         <!-- Passo 3 -->
         <div class="passo-card-neon" style="animation-delay: 0.3s;">
             <div class="emoji-glow">📅</div>
             <h4>3. Agende o Horário</h4>
             <p>Consulte o painel de expediente em tempo real, defina o dia útil e reserve um horário livre na agenda do barbeiro.</p>
         </div>
 
         <!-- Instrução 4: Integração Unitel Money -->
         <div class="passo-card-neon" style="animation-delay: 0.6s;">
             <div class="emoji-glow">📱</div>
             <h4>4. Desconto Unitel Money</h4>
             <p>Introduza um terminal Unitel elegível (prefixos 9XXXXX). O gateway calcula e aplica <b>20% de Desconto VIP</b> automáticos no caixa.</p>
         </div>
 
         <!-- Instrução 5: Plano Freemium -->
         <div class="passo-card-neon" style="animation-delay: 0.9s;">
             <div class="emoji-glow">🆓</div>
             <h4>5. Vantagem Freemium</h4>
             <p>Novos salões parceiros operam com taxa zero nos primeiros 30 dias. Clientes efetuam adiantamentos seguros que caem direto no balcão.</p>
         </div>
 
         <!-- 🎁 NOVO PASSO 6: INTELIGÊNCIA DE CUPÕES DE POPULARIDADE E RECOMPENSAS -->
         <div class="passo-card-neon" style="animation-delay: 1.2s; border-bottom: 2px solid #eab308 !important;">
             <div class="emoji-glow">🎁</div>
             <h4 style="color: #eab308;">6. Cupões de Popularidade</h4>
             <p>Interaja na galeria! Reagir com ❤️, partilhar ou marcar estilos acumula pontos automáticos. Cada 100 pontos libertam <b>Cupões de até 35% de Desconto</b> automáticos no balcão.</p>
         </div>
 
     </div> <!-- FIM DA GRID -->
 </div>
 <!-- Estilização CSS Interna Isolada para Injetar o Brilho Radiante sem Quebrar nada -->
 <style>
     /* 🟢 CARTÃO INDIVIDUAL COM MÁSCARA NEON GLOW ATIVA */
     .passo-card-neon {
         background: #1e293b;
         border: 1px solid #334155;
         padding: 20px;
         border-radius: 12px;
         box-shadow: 0 4px 6px rgba(0,0,0,0.15);
         transition: all 0.3s cubic-bezier(0.175, 0.885, 0.32, 1.275);
         border-bottom: 2px solid #38bdf8;
     }
     
     .passo-card-neon h4 {
         font-size: 14px;
         font-weight: bold;
         color: #ffffff;
         margin: 8px 0 4px 0;
         text-transform: uppercase;
         letter-spacing: 0.5px;
     }
     
     .passo-card-neon p {
         font-size: 12px;
         color: #94a3b8;
         line-height: 1.5;
         margin: 0;
     }
 
     /* Animação Flutuante e Radiante ao Passar o Rato (Hover Effect) */
     .passo-card-neon:hover {
         transform: translateY(-5px);
         background: #111e35;
         border-color: #38bdf8;
         box-shadow: 0 0 15px rgba(56, 189, 248, 0.4), 0 0 30px rgba(56, 189, 248, 0.1);
     }
 
     /* Efeito de Luz e Crescimento nos Emojis Corporativos */
     .emoji-glow {
         font-size: 26px;
         display: inline-block;
         transition: transform 0.3s ease;
         filter: drop-shadow(0 0 5px rgba(255,255,255,0.2));
     }
     .passo-card-neon:hover .emoji-glow {
         transform: scale(1.2) rotate(10deg);
         filter: drop-shadow(0 0 8px #38bdf8);
     }
 </style>

















         








<?php
if (session_status() === PHP_SESSION_NONE) { 
    session_start(); 
}

include_once("Conexao.php");

// 🟢 1. CONTROLO MULTI-TENANT ISOLADO: Fixa estritamente o ID 20 (Barbearia Branca)
$id_empresa_ativa = 20; 
$_SESSION['empresa_codigo'] = $id_empresa_ativa;

// Coleta as informações operacionais oficiais da Barbearia Branca na tabela lojas
$nome_salao_dinamico = "Barbearia Branca";
$endereco_salao_dinamico = "Avenida General Pinto Monteiro, Aviação, Huambo";

$q_salao = $mysqli->query("SELECT nome_loja, endereco_armazem FROM lojas WHERE id = '$id_empresa_ativa' LIMIT 1");
if($q_salao && $q_salao->num_rows > 0) {
    $dados_salao = $q_salao->fetch_assoc();
    $nome_salao_dinamico = $dados_salao['nome_loja'];
    $endereco_salao_dinamico = $dados_salao['endereco_armazem'];
}

// 🟢 2. CONSULTA LOCALIZADA: Puxa APENAS os cosméticos pertencentes à Barbearia Branca
$query_produtos = $mysqli->query("SELECT * FROM `produtos_cosmeticos` WHERE `empresa_id` = '$id_empresa_ativa' AND `stock_atual` > 0 ORDER BY id DESC");
?>

<!-- CONTAINER VITRINE PREMIUM — EXCLUSIVO BARBEARIA BRANCA -->
<style>
    .vortex-marketplace-container {
        background: #070b12;
        padding: 30px 20px;
        border-radius: 24px;
        border: 1px solid rgba(56, 189, 248, 0.15);
        max-width: 1200px;
        margin: 25px auto;
        font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
    }

    .grid-produtos-premium {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
        gap: 25px;
        margin-top: 20px;
    }

    .card-produto-social {
        background: #0d1324;
        border: 2px solid #1e293b;
        border-radius: 20px;
        padding: 20px;
        position: relative;
        box-shadow: 0 15px 35px rgba(0,0,0,0.5);
        transition: transform 0.3s ease, border-color 0.3s ease;
        text-align: left;
    }

    .card-produto-social:hover {
        transform: translateY(-5px);
        border-color: #00d2ff;
    }

    .perfil-loja-header {
        display: flex;
        align-items: center;
        gap: 12px;
        margin-bottom: 15px;
        border-bottom: 1px solid #1e2937;
        padding-bottom: 10px;
    }

    .avatar-salao-ficticio {
        width: 40px;
        height: 40px;
        background: linear-gradient(135deg, #ef4444, #b91c1c); /* Vermelho/Branco corporativo */
        color: #fff;
        font-weight: bold;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 16px;
        border: 2px solid #1e293b;
    }

    .painel-reacoes-sociais {
        display: flex;
        justify-content: space-between;
        align-items: center;
        background: #111827;
        padding: 10px 15px;
        border-radius: 30px;
        margin-bottom: 15px;
        border: 1px solid #1f2937;
    }

    .btn-reacao-viva {
        background: none;
        border: none;
        color: #94a3b8;
        font-size: 13px;
        font-weight: bold;
        cursor: pointer;
        display: flex;
        align-items: center;
        gap: 6px;
        transition: all 0.2s;
    }

    .btn-reacao-viva:hover { color: #ff4b2b; transform: scale(1.08); }
    .btn-reacao-viva.ativo-gosto { color: #ff4b2b; }

    .btn-unitele-checkout {
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 8px;
        background: linear-gradient(135deg, #22c55e, #16a34a);
        color: white;
        padding: 13px;
        text-decoration: none;
        border-radius: 12px;
        font-weight: 800;
        font-size: 13px;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        box-shadow: 0 5px 15px rgba(34, 197, 94, 0.3);
        transition: all 0.3s;
    }

    .btn-unitele-checkout:hover {
        background: linear-gradient(135deg, #00ff87, #22c55e);
        color: #070b12;
        box-shadow: 0 8px 20px rgba(34, 197, 94, 0.5);
    }
</style>

<div class="vortex-marketplace-container">
    
    <div style="text-align: left; margin-bottom: 5px;">
        <span style="color: #00d2ff; font-size: 11px; font-weight: bold; text-transform: uppercase; letter-spacing: 1px;">🛒 PRODUTOS  A VENDA</span>
        <h2 style="color: #fff; font-size: 20px; font-weight: bold; margin-top: 4px;"> Barbearia Branca</h2>
    </div>

    <div class="grid-produtos-premium">
        <?php if ($query_produtos && $query_produtos->num_rows > 0): ?>
            <?php while($produto = $query_produtos->fetch_assoc()): 
                $id_prod = intval($produto['id']);
                $preco_original = floatval($produto['preco']);
                
                // 🟢 RECALCULO SIMÉTRICO: Aplica rigorosamente o desconto regulamentar de 20%
                $preco_unitel = $preco_original * 0.80; 

                // Métricas dinâmicas de engajamento baseadas no ID do registo
                $likes_reais = ($id_prod * 14) % 89 + 15;
                $comentarios_reais = ($id_prod * 2) % 12 + 1;

                // 🟢 UNIFICAÇÃO DE ROTAS (DINAMISMO ABSOLUTO): Passa o preço unitel exato obtido da tabela
                $rota_unitele_mestre = "unitele.php?id_produto_comprado=" . $id_prod . "&gateway=unitel_money&id_parceiro=" . $id_empresa_ativa . "&preco_final=" . number_format($preco_unitel, 2, '.', '');
            ?> 
            <!-- 🟢 MOTOR DE RENDERIZAÇÃO MULTIMÉDIA ATIVADO -->
<?php if (!empty($lista_cards)): ?>
<?php foreach ($lista_cards as $row): 
    // Captura os dados dinâmicos de cada vídeo da tabela anuncios
    $id_prod = intval($row['id_anuncio']);
    $titulo_video = htmlspecialchars($row['titulo']);
    $arquivo_multimedia = trim($row['imagem'] ?? '');
    
    // Mantém as variáveis de identidade da Barbearia Branca
    $nome_salao_dinamico = "Barbearia Branca";
    $endereco_salao_dinamico = "Huambo";
?>

<div class="card-produto-social" id="produto-card-<?= $id_prod ?>" style="margin-bottom: 20px;">
    
    <!-- Cabeçalho Autenticado: Identidade da Barbearia Branca -->
    <div class="perfil-loja-header" style="display: flex; gap: 10px; align-items: center; padding: 10px 0;">
        <div class="avatar-salao-ficticio" style="background: #14424b; color: #fff; padding: 8px; border-radius: 50%; font-weight: bold; font-size: 12px;">BB</div>
        <div>
            <strong style="color: #fff; font-size: 13.5px; display: block;"><?= $nome_salao_dinamico ?></strong>
            <span style="color: #64748b; font-size: 10.5px; display: block;">📍 <?= $endereco_salao_dinamico ?></span>
        </div>
    </div>

    <!-- Título do Vídeo Publicado -->
    <p style="color: #cbd5e1; font-size: 13px; margin: 5px 0 10px 0; text-align: left; font-weight: 500;">
        <?= $titulo_video ?>
    </p>

    <!-- 📱 PLAYER REELS VERTICAL INTEGRADO PARA TELEMÓVEL ANDROID -->
    <div style="width: 100%; border-radius: 12px; overflow: hidden; background: #070b12; border: 1px solid #1e293b;">
        <video width="100%" controls preload="metadata" style="display: block; object-fit: cover; max-height: 400px;">
            <source src="uploads/<?= urlencode($arquivo_multimedia) ?>" type="video/mp4">
            O teu dispositivo não suporta este leitor.
        </video>
    </div>

</div>

<?php endforeach; ?>
<?php else: ?>
<!-- Feedback visual limpo caso a tabela esteja limpa -->
<div style="padding: 30px 15px; text-align: center; color: #64748b; font-size: 12px; font-style: italic;">
    Nenhum trabalho ou corte em vídeo publicado para este painel de momento.
</div>
<?php endif; ?>

                    <!-- Exibição de Média com Fallback Seguro -->
                    <div style="width: 100%; height: 240px; background: #070b12; border-radius: 14px; display: flex; align-items: center; justify-content: center; margin-bottom: 15px; border: 1px solid #1e293b; overflow: hidden; position: relative;">
    <?php 
    // Captura o caminho do ficheiro binário ou o fallback padrão do sistema
    $img = (!empty($produto['imagem']) && $produto['imagem'] != 'default_cosmetico.jpg') ? "uploads/".$produto['imagem'] : "download (5).png"; 
    ?>
    <img src="<?= $img ?>" style="width: 50%; height: 100%; object-fit: cover; transition: transform 0.4s ease; filter: brightness(0.95);" onmouseover="this.style.transform='scale(1.03)'; this.style.filter='brightness(1)';" onmouseout sabotage="this.style.transform='scale(1)'; this.style.filter='brightness(0.95)';">
    
    <!-- Efeito de sombreamento interno sutil nas bordas para dar profundidade premium -->
    <div style="position: absolute; top: 0; left: 0; width: 100%; height: 100%; box-shadow: inset 0 0 20px rgba(0,0,0,0.6); pointer-events: none;"></div>
</div>

                    <!-- Título e Blocos Síncronos de Preço -->
                    <h3 style="color: #38bdf8; font-size: 15px; font-weight: bold; margin-bottom: 4px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;"><?= htmlspecialchars($produto['nome_produto']) ?></h3>
                    <p style="color: #a1a1aa; font-size: 11.5px; text-decoration: line-through; margin-bottom: 2px;">Preço Normal: <?= number_format($preco_original, 2, ',', '.') ?> Kz</p>
                    <p style="color: #eab308; font-size: 19px; font-weight: 800; margin-bottom: 12px;"><?= number_format($preco_unitel, 2, ',', '.') ?> Kz <span style="font-size: 10px; color: #22c55e; font-weight: bold; display: block; margin-top: 2px;">(Desconto Especial -2%)</span></p>

                    <!-- Painel Reativo de Engajamento Local -->
                    <div class="painel-reacoes-sociais">
                        <button class="btn-reacao-viva" onclick="registarInteresseSemCompra(this, <?= $id_prod ?>, 'gosto')">
                            ❤️ <span class="cont-contador" id="likes-count-<?= $id_prod ?>"><?= $likes_reais ?></span>
                        </button>
                        <button class="btn-reacao-viva" style="cursor: default; pointer-events: none;">
                            💬 <span><?= $comentarios_reais ?> SMS</span>
                        </button>
                        <button class="btn-reacao-viva" onclick="registarInteresseSemCompra(this, <?= $id_prod ?>, 'guardar')">
                            ⭐ <span style="font-size: 11px;">Guardar</span>
                        </button>
                    </div>

                    <p style="font-size: 11.5px; color: #64748b; margin-bottom: 14px; text-align: center;">Stock disponível no Huambo: <strong style="color: #fff;"><?= $produto['stock_atual'] ?> un</strong></p>

                    <!-- ROTA SEGURO SINCRONIZADA AUTOMATICAMENTE -->
                    <a href="<?= $rota_unitele_mestre ?>" class="btn-unitele-checkout">
                        📱 FATURAR NO UNITELE.PHP
                    </a>

                </div>
            <?php endwhile; ?>
        <?php else: ?>
            <p style="color: #64748b; font-style: italic; text-align: center; grid-column: 1/-1; padding: 30px;">A Barbearia Branca ainda não listou produtos para venda esta semana.</p>
        <?php endif; ?>
    </div>
</div>

<!-- JAVASCRIPT REATIVO DO MERCADO -->
<script>
function registarInteresseSemCompra(botao, idProduto, tipoAcao) {
    if (tipoAcao === 'gosto') {
        const spanContador = document.getElementById('likes-count-' + idProduto);
        if (spanContador && !botao.classList.contains('ativo-gosto')) {
            botao.classList.add('ativo-gosto');
            botao.style.color = '#ff4b2b';
            spanContador.innerText = parseInt(spanContador.innerText) + 1;

            const card = document.getElementById('produto-card-' + idProduto);
            const alertaFlutuante = document.createElement('div');
            alertaFlutuante.style.position = 'absolute';
            alertaFlutuante.style.bottom = '70px';
            alertaFlutuante.style.left = '50%';
            alertaFlutuante.style.transform = 'translateX(-50%)';
            alertaFlutuante.style.background = 'rgba(34, 197, 94, 0.95)';
            alertaFlutuante.style.color = '#fff';
            alertaFlutuante.style.padding = '6px 12px';
            alertaFlutuante.style.borderRadius = '20px';
            alertaFlutuante.style.fontSize = '11px';
            alertaFlutuante.style.fontWeight = 'bold';
            alertaFlutuante.style.zIndex = '999';
            alertaFlutuante.style.whiteSpace = 'nowrap';
            alertaFlutuante.innerText = '🔥 Elevado ao Pedestal das Tendências!';
            
            card.appendChild(alertaFlutuante);
            
            // Transição suave para o alerta desaparecer após 2.5 segundos
            setTimeout(() => {
                alertaFlutuante.style.transition = 'opacity 0.4s ease, transform 0.4s ease';
                alertaFlutuante.style.opacity = '0';
                alertaFlutuante.style.transform = 'translate(-50%, -10px)';
                setTimeout(() => alertaFlutuante.remove(), 400);
            }, 250);
        }
    } else if (tipoAcao === 'guardar') {
        botao.style.color = '#eab308';
        botao.innerHTML = '⭐ Guardado!';
    }

    // Envio assíncrono em segundo plano para persistência local no MariaDB
    fetch('processar_popularidade_ajax.php?id_prod=' + idProduto + '&acao=' + tipoAcao)
    .catch(() => {
        // Silencia em localhost XAMPP se o endpoint assíncrono de contagem ainda não estiver criado
    });
}
</script>








<div id="area-impressao-global" style="display: none; padding: 20px; font-family: monospace;"></div>
<!-- JANELA POP-UP (MODAL) DOS TERMOS -->
<div id="modalAbas" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background-color: rgba(0, 0, 0, 0.7); z-index: 10000; justify-content: center; align-items: center; padding: 20px;">
    
    <div style="background-color: #0f172a; border: 1px solid #334155; width: 100%; max-width: 500px; border-radius: 12px; padding: 25px; box-shadow: 0 10px 25px rgba(0,0,0,0.5); position: relative;">
        
        <!-- Botão de Fechar (X) -->
        <span onclick="fecharAbas()" style="position: absolute; top: 15px; right: 20px; color: #ef4444; font-size: 28px; font-weight: bold; cursor: pointer;">&times;</span>
        <span onclick="fecharAbas()" style="position: absolute; top: 15px; right: 20px; color: #ef4444; font-size: 28px; font-weight: bold; cursor: pointer;">&times;</span>
        
       <!-- Título Principal -->
<h3 style="color: #fff; border-bottom: 1px solid #334155; padding-bottom: 12px; margin-top: 0; font-size: 20px; display: flex; align-items: center; gap: 8px;">
Historial da Barbearia Branca
</h3>

<!-- Conteúdo com Rolagem Interna Otimizada de Alta Densidade -->
<div style="color: #94a3b8; font-size: 13px; line-height: 1.6; max-height: 400px; overflow-y: auto; margin-top: 15px; padding-right: 5px;">

<p style="margin-bottom: 14px; text-align: justify;">
    <strong style="color: #38bdf8;">1. Fundação e Legado Familiar:</strong> 
    O Salão & Barbearia Branca foi fundado em <strong>2015</strong> no bairro de <strong>São Luís-Catimba, Huambo</strong>, por uma parceria unida entre irmãos. O projeto começou com apenas duas cadeiras de atendimento manual e evoluiu para uma infraestrutura robusta de grande porte que atende dezenas de cidadãos por dia.
</p>

<p style="margin-bottom: 14px; text-align: justify;">
    <strong style="color: #38bdf8;">2. Plano de Expansão Nacional (Visão Futura):</strong> 
    O Grupo Aurélius está a desenhar a engenharia para construir uma das maior megastore estética da região centro e **espalhar franquias em todo o território nacional**. O foco prioritário de expansão para o próximo biénio concentra-se nas capitais de **Luanda, Benguela, Lubango e Cabinda**, padronizando cortes e tratamentos com tecnologia avançada.
</p>

<p style="margin-bottom: 14px; text-align: justify;">
    <strong style="color: #38bdf8;">3. Governança e Dados dos Clientes:</strong> 
    Os dados inseridos no formulário (Nome, Funcionário, serviços e Horário) são encriptados localmente e protegidos sob sigilo corporativo. Estas informações são confidenciais e usadas unicamente para a organização da agenda, prevenção de dupla marcação e auditoria interna de desempenho da equipa técnica.
</p>

<p style="margin-bottom: 14px; text-align: justify;">
    <strong style="color: #38bdf8;">4. Política Rígida de Cancelamentos e Faltas:</strong> 
    Alterações ou eliminações de registos no histórico de agendamentos devem ser validadas obrigatoriamente com o administrador do sistema com uma antecedência mínima de 2 horas. .
</p>

<p style="margin-bottom: 14px; text-align: justify;">
    <strong style="color: #38bdf8;">5. Rastreamento e Métricas de Mercado:</strong> 
    Ao utilizar a plataforma, o utilizador declara estar ciente de que as métricas de cliques em serviços estéticos, preferências de profissionais e relatórios financeiros são catalogados anonimamente. Estes dados alimentam o nosso motor de inteligência comercial para identificar os tratamentos mais populares na região.
</p>

<p style="margin-bottom: 14px; text-align: justify;">
    <strong style="color: #38bdf8;">6. Monetização e Parcerias Internacionais:</strong> 
    O sistema Freemium utiliza algoritmos de recomendação segmentados baseados nas escolhas do utilizador para sugerir cosméticos de marcas líderes globais (como L'Oréal Paris Angola, Clear e Nivea, Angelino ATELIER, BetoArt, AlexArt.). A Barbearia Branca retém comissões residenciais a cada venda direta ou clique convertido na loja parceira.
</p>



<p style="margin-bottom: 0; text-align: justify;">
    <strong style="color: #38bdf8;">7. Higiene e Segurança Sanitária:</strong> 
    Todos os equipamentos de corte, lâminas, tesouras e toalhas passam por um processo rigoroso de esterilização em autoclave após cada sessão. Mantemos o compromisso estrito com as normas de saúde pública de Angola, garantindo um ambiente seguro e eficaz para a proteção de toda a nossa clientela. <br> A Barbearia Branca é a única Barbearia segura pra ti e para toda sua Família.
</p>
</div>
        
        <!-- Botão Entendido -->
        <div style="text-align: right; margin-top: 20px;">
            <button onclick="fecharAbas()" style="background-color: #0088cc; color: white; border: none; padding: 8px 20px; font-weight: bold; border-radius: 6px; cursor: pointer;">Entendido</button>
        </div>
    </div>
</div>






<div id="area-impressao-global" style="display: none; padding: 20px; font-family: monospace;"></div>

<!-- JANELA POP-UP (MODAL) DOS TERMOS -->
<div id="modalTermos" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background-color: rgba(0, 0, 0, 0.7); z-index: 10000; justify-content: center; align-items: center; padding: 20px;">
    
    <div style="background-color: #0f172a; border: 1px solid #334155; width: 100%; max-width: 500px; border-radius: 12px; padding: 25px; box-shadow: 0 10px 25px rgba(0,0,0,0.5); position: relative;">
        
        <!-- Botão de Fechar Único e Corrigido (X) -->
        <span onclick="fecharTermos()" style="position: absolute; top: 15px; right: 20px; color: #ef4444; font-size: 28px; font-weight: bold; cursor: pointer;">&times;</span>
        
        <!-- Título Principal -->
        <h3 style="color: #fff; border-bottom: 1px solid #334155; padding-bottom: 12px; margin-top: 0; font-size: 20px; display: flex; align-items: center; gap: 8px;">
            Termos e Privacidade
        </h3>

        <!-- Conteúdo com Rolagem Interna Otimizada de Alta Densidade -->
        <div style="color: #94a3b8; font-size: 13px; line-height: 1.6; max-height: 400px; overflow-y: auto; margin-top: 15px; padding-right: 5px; text-align: left;">
            <h5 style="color: #fff; margin: 0 0 5px 0; font-size: 14px;">1. Âmbito do Serviço</h5>
            <p style="margin: 0 0 15px 0;">O salão e barbearia AURELIUS disponibiliza uma plataforma de agendamento digital para otimizar os atendimentos na Província do Huambo. Os serviços marcados estão sujeitos à disponibilidade dos profissionais selecionados no painel técnico.</p>

            <h5 style="color: #fff; margin: 0 0 5px 0; font-size: 14px;">2. Política de Cancelamento e Tolerância</h5>
            <p style="margin: 0 0 15px 0;">O cliente compromete-se a comparecer com 10 minutes de antecedência ao horário validado no comprovativo. É estabelecida uma tolerância máxima de 15 minutos de atraso. Findo este período, o horário poderá ser redistribuído para garantir a fluidez da agenda.</p>

            <h5 style="color: #fff; margin: 0 0 5px 0; font-size: 14px;">3. Proteção de Dados e Privacidade</h5>
            <p style="margin: 0 0 15px 0;">Os dados recolhidos no formulário (Nome do Cliente, Telefone e especificações de atendimento) servem exclusivamente para a gestão interna das sessões e emissão de faturas. Garantimos a não partilha com entidades terceiras em conformidade com as boas práticas de governação de dados.</p>

            <h5 style="color: #fff; margin: 0 0 5px 0; font-size: 14px;">4. Assinatura VIP e Pagamentos Express</h5>
            <p style="margin: 0 0 15px 0;">As ativações de planos promocionais via MultiCaixa Express são de caráter livre e público. O desconto de 2% é aplicado diretamente sobre a tabela de preços vigente no banco de dados para os utilizadores com estatuto PREMIUM ativo.</p>

            <h5 style="color: #fff; margin: 0 0 5px 0; font-size: 14px;">5. Armazenamento Local e Cookies</h5>
            <p style="margin: 0 0 15px 0;">Este sistema utiliza persistência local (LocalStorage) para salvar o seu consentimento de navegação e otimizar o carregamento da cache offline (PWA), garantindo estabilidade técnica mesmo em cenários de conectividade reduzida.</p>
        </div>
        
        <!-- Botão Entendido -->
        <div style="text-align: right; margin-top: 20px;">
            <button onclick="fecharTermos()" style="background-color: #0088cc; color: white; border: none; padding: 8px 20px; font-weight: bold; border-radius: 6px; cursor: pointer;">Entendido</button>
        </div>
    </div>
</div>

















 
 <!-- ⚡ JAVASCRIPT DE GOVERNANÇA DE DADOS (COLAR JUNTO AOS OUTROS SCRIPTS) -->
 <script>
 document.addEventListener("DOMContentLoaded", function() {
     // 🌟 VERIFICAÇÃO AUTOMÁTICA: Se o utilizador já aceitou antes, o banner permanece oculto para sempre
     if (localStorage.getItem('aurelius_consentimento_bi') !== 'permitido') {
         const banner = document.getElementById('cookieBanner');
         if (banner) {
             banner.style.display = 'flex'; // Só mostra a quem ainda não clicou
         }
     }
 });
 
 function processarConsentimentoRealBI() {
     // 1. Grava a decisão de conformidade técnica permanentemente na memória do telemóvel/PC
     localStorage.setItem('aurelius_consentimento_bi', 'permitido');
     
     // 2. Fecha o banner visual imediatamente com o clique do botão
     const banner = document.getElementById('cookieBanner');
     if (banner) {
         banner.style.display = 'none';
     }
     
     // 3. Informação técnica disparada na consola do navegador (Rastreamento de BI Ativo)
     console.log("📋 Grupo Aurélius: Permissão concedida. Métricas de produtividade e cacheoffline PWA inicializadas.");
 }
 </script>

<!-- =================================================================
     ⭐ MODAL DE SUBSCRICÃO PREMIUM - ACESSO PÚBLICO E ANIMAÇÃO FADE-IN
     ================================================================= -->
     <div id="modalPremium" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background-color: rgba(0,0,0,0.85); z-index: 10000; justify-content: center; align-items: center; padding: 20px; box-sizing: border-box;">
    
     <!-- CSS Embutido de Alta Performance para o Efeito de Esmaecimento Suave -->
     <style>
         .fade-in-suave {
             animation: fadeInAnimacao 0.4s ease-in-out forwards;
         }
         @keyframes fadeInAnimacao {
             from { opacity: 0; transform: scale(0.95); }
             to { opacity: 1; transform: scale(1); }
         }
     </style>
     
     <div class="fade-in-suave" style="background-color: #0f172a; border: 2px solid #ca8a04; border-radius: 12px; width: 100%; max-width: 480px; padding: 25px; box-shadow: 0 10px 25px rgba(0,0,0,0.5); position: relative; font-family: sans-serif;">
         
         <!-- Botão Fechar -->
         <span onclick="fecharModalPremium()" style="position: absolute; top: 12px; right: 20px; color: #ef4444; font-size: 28px; cursor: pointer; font-weight: bold;">&times;</span>
         
         <!-- Cabeçalho com Destaque dos 20% em Barbearias -->
         <div style="text-align: center; margin-bottom: 20px;">
             <h2 style="color: #ca8a04; margin: 0 0 5px 0; font-size: 22px; letter-spacing: 0.5px;">⭐ PLANO AURELIUS VIP</h2>
             <p style="color: #94a3b8; font-size: 13px; margin: 0; line-height: 1.4;">
                 Ative o seu plano FREMIUM para obter <strong>2% de DESCONTO EM QUALQUER SERVIÇO QUE DESEJARES</strong> Dámos-te Prioridade máxima no atendimento e remoção total de anúncios!
             </p>
         </div>
 
         <!-- Opções de Inscrição Aberta (Valores Promocionais) -->
         <div style="display: flex; flex-direction: column; gap: 10px; margin-bottom: 20px;">
             
             <!-- Mensal -->
             <label style="display: flex; align-items: center; justify-content: space-between; background: #131e35; padding: 14px; border-radius: 8px; cursor: pointer; border: 1px solid #ca8a04;" id="labelMensal">
                 <div style="display: flex; align-items: center;">
                     <input type="radio" name="planoPremium" value="mensal" checked onclick="atualizarPrecoPlano(1000)" style="margin-right: 12px; transform: scale(1.2);">
                     <span style="color: #fff; font-weight: bold; font-size: 14px;">Plano Mensal (Livre)</span>
                 </div>
                 <span style="color: #ca8a04; font-weight: bold; font-size: 14px;">1.000 Kz/mês</span>
             </label>
 
             <!-- Semestral -->
             <label style="display: flex; align-items: center; justify-content: space-between; background: #1e293b; padding: 14px; border-radius: 8px; cursor: pointer; border: 1px solid #334155;" id="labelSemestral">
                 <div style="display: flex; align-items: center;">
                     <input type="radio" name="planoPremium" value="semestral" onclick="atualizarPrecoPlano(5000)" style="margin-right: 12px; transform: scale(1.2);">
                     <span style="color: #fff; font-weight: bold; font-size: 14px;">Plano Semestral (Livre)</span>
                 </div>
                 <span style="color: #ca8a04; font-weight: bold; font-size: 14px;">5.000 Kz <small style="color:#22c55e; font-size:10px; display:block; text-align:right;">Poupe mais</small></span>
             </label>
 
             <!-- Anual -->
             <label style="display: flex; align-items: center; justify-content: space-between; background: #1e293b; padding: 14px; border-radius: 8px; cursor: pointer; border: 1px solid #334155;" id="labelAnual">
                 <div style="display: flex; align-items: center;">
                     <input type="radio" name="planoPremium" value="anual" onclick="atualizarPrecoPlano(9000)" style="margin-right: 12px; transform: scale(1.2);">
                     <span style="color: #fff; font-weight: bold; font-size: 14px;">Plano Anual (Livre)</span>
                 </div>
                 <span style="color: #ca8a04; font-weight: bold; font-size: 14px;">9.000 Kz <small style="color:#22c55e; font-size:10px; display:block; text-align:right;">Melhor Oferta</small></span>
             </label>
         </div>
 
         <!-- Área de Integração MultiCaixa Express Pública -->
         <div style="background: #1e293b; padding: 15px; border-radius: 8px; border: 1px solid #3b82f6;">
             <h4 style="color: #fff; margin: 0 0 8px 0; font-size: 14px; text-align: center;">📱 Pagamento Aberto via UnitelMoney/ MCX Express</h4>
             <p style="color: #94a3b8; font-size: 11px; margin: 0 0 12px 0; text-align: center; line-height: 1.3;">Disponível para qualquer utilizador da rede. Introduza o número do seu telemóvel Express para ativação imediata.</p>
             
             <input type="tel" id="telefoneExpress" placeholder="nº de telefone" maxlength="9" style="width: 100%; padding: 12px; border-radius: 6px; border: 1px solid #475569; background: #0f172a; color: white; text-align: center; font-size: 16px; font-weight: bold; margin-bottom: 12px; box-sizing: border-box; outline: none;">
             
             <button onclick="processarPagamentoExpressAberto()" style="width: 100%; background: #22c55e; color: white; border: none; padding: 12px; font-size: 14px; font-weight: bold; border-radius: 6px; cursor: pointer; text-transform: uppercase; letter-spacing: 0.5px;">
                 Confirmar e Inscrever <span id="txtPrecoBotao">1.000</span> Kz
             </button>
         </div>
 
     </div>
 </div>



















 <footer style="background-color: #0b111e; border-top: 2px solid #ca8a04; padding: 50px 20px 30px 20px; color: #f8fafc; margin-top: 60px; font-family: 'Segoe UI', Arial, sans-serif; text-align: left; box-shadow: 0 -5px 25px rgba(0,0,0,0.5);">
    <div style="max-width: 1100px; margin: 0 auto; display: grid; grid-template-columns: repeat(auto-fit, minmax(240px, 1fr)); gap: 40px; padding-bottom: 30px; border-bottom: 1px solid #1e293b;">
        
        <!-- Coluna 1: Identidade da Marca -->
        <div>
            <h2 style="font-size: 20px; font-weight: 800; color: #ef4444; margin: 0 0 12px 0; text-transform: uppercase; letter-spacing: 0.5px;">
                BARBEARIA <span style="color: #ffffff;">BRANCA</span>
            </h2>
            <p style="font-size: 13px; color: #94a3b8; line-height: 1.6; margin: 0 0 15px 0; text-align: justify;">
                A infraestrutura do ecossistema multisserviços de estética, barbearia digital e e-commerce de cosméticos premium da província do Huambo.
            </p>
            <span style="background: rgba(202, 138, 4, 0.1); color: #ca8a04; border: 1px solid rgba(202, 138, 4, 0.3); padding: 4px 10px; border-radius: 4px; font-size: 11px; font-weight: bold; text-transform: uppercase; letter-spacing: 0.5px;">
                SaaS Platform Grupo Aurelius
            </span>
        </div>

        <!-- Coluna 2: Links de Acesso Rápido -->
        <div>
            <h4 style="font-size: 13px; font-weight: bold; color: #ca8a04; text-transform: uppercase; margin-bottom: 15px; letter-spacing: 0.5px;">Navegação Segura</h4>
            <ul style="list-style: none; padding: 0; margin: 0; font-size: 13px; display: flex; flex-direction: column; gap: 10px;">
                <li><a href="Principal.php" style=" text-decoration: none; transition: 0.2s; color: #ffff;background: #22c55e;border-radius:12px;  padding:3px;">🏪 Portal de Barbearias</a></li> 
                <li><a href="BrancaCadastar.php" style=" text-decoration: none; transition: 0.2s; color: #ffff;background: #22c55e;border-radius:12px; padding:3px; ">👨🏼‍🦰 Criar Conta de Cliente</a></li>
                <li><a href="BrancaCadastar.php" style=" text-decoration: none; transition: 0.2s; color: #ffff;background: #22c55e; border-radius:12px; padding:3px; ">🔐 Área Administrativa </a></li>
                <li><a href="unitel.php" style=" text-decoration: none; transition: 0.2s; color: #ffff;background: #22c55e;border-radius:12px; padding:3px; ">📱 Gateway de Pagamento Móvel</a></li>
            </ul>
        </div>

        <!-- Coluna 3: Sede e Contactos Oficiais -->
        <div>
            <h4 style="font-size: 13px; font-weight: bold; color: #ca8a04; text-transform: uppercase; margin-bottom: 15px; letter-spacing: 0.5px;">Sede Executiva</h4>
            <p style="font-size: 13px; color: #cbd5e1; margin-bottom: 8px; line-height: 1.4;">
                📍 Bairro São Luís Catimba,<br>Huambo — Angola
            </p>
            <p style="font-size: 13px; color: #cbd5e1; margin-bottom: 8px;">
                📞 Suporte Técnico: <span style="color: #38bdf8; font-weight: bold;">+244 925 347 372/ <br> 928 829 299</span>
            </p>
            <p style="font-size: 12px; color: #64748b;">
                ⏰ Atendimento: Seg a Sáb — 08h00 às 22h00
            </p>
        </div>

    </div>

    <!-- Direitos Autorais e Assinatura Técnica -->
    <div style="max-width: 1100px; margin: 20px auto 0 auto; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 15px; font-size: 12px; color: #64748b;">
        <p>© <?php echo date('Y'); ?> Grupo Aurélius. Todos os direitos reservados em território nacional.</p>
        <p>Desenvolvido por: <span style="color: #ca8a04; font-weight: bold; text-transform: uppercase; letter-spacing: 0.5px;">Aurélio Sacalumbo</span></p>
    </div>
</footer>












<div class="banner-consentimento" id="cookieBanner" style="position: fixed; bottom: 0; left: 0; right: 0; background-color: rgba(15, 23, 42, 0.98); border-top: 2px solid #ca8a04; padding: 10px 12px; display: none; z-index: 9999; box-shadow: 0 -4px 15px rgba(0,0,0,0.5); font-family: sans-serif; box-sizing: border-box;">
    
    <!-- Bloco de Estilos Embutido para Forçar Responsividade Fluida Mobile -->
    <style>
        #cookieBanner {
            display: flex;
            flex-direction: row;
            justify-content: space-between;
            align-items: center;
            gap: 12px;
        }
        .banner-texto-bi {
            font-size: 11.5px;
            line-height: 1.4;
            color: #94a3b8;
            text-align: left;
            flex: 1;
        }
        .banner-acoes-bi {
            display: flex;
            align-items: center;
        }
        .btn-permitir-bi {
            background: linear-gradient(135deg, #22c55e, #16a34a);
            color: white;
            border: none;
            padding: 8px 14px;
            font-weight: bold;
            border-radius: 6px;
            cursor: pointer;
            white-space: nowrap;
            text-transform: uppercase;
            font-size: 11px;
            letter-spacing: 0.3px;
            width: auto;
        }
        /* 📱 Quebra de linha e expansão automática para ecrãs de Telemóveis */
        @media (max-width: 600px) {
            #cookieBanner {
                flex-direction: column !important;
                text-align: center !important;
                gap: 8px !important;
                padding: 10px !important;
            }
            .banner-texto-bi {
                text-align: center !important;
                font-size: 11px !important;
                padding-right: 0 !important;
            }
            .banner-acoes-bi {
                width: 100% !important;
            }
            .btn-permitir-bi {
                width: 100% !important;
                padding: 10px !important;
                text-align: center !important;
            }
        }
    </style>

    <!-- Texto Informativo Otimizado -->
    <div class="banner-texto-bi">
        <strong style="color: #ca8a04;">Controlo de Auditoria PWA:</strong> O Grupo Aurélius recolhe métricas estatísticas de navegação anonimizadas, escolhas de serviços estéticos e volumetria financeira para otimização da agenda diária, cálculo do ranking mensal de produtividade e monetização regionalizada em Angola.
    </div>
    
    <!-- Botão de Ação Redimensionável -->
    <div class="banner-acoes-bi">
        <button class="btn-permitir-bi" onclick="processarConsentimentoRealBI()">
            Aceitar e Permitir
        </button>
    </div>
</div>


<!-- =================================================================
     🧠 MOTOR JAVASCRIPT: CONTROLO INTELIGENTE DO BANNER DE BI
     ================================================================= -->
<script>

   // =========================================================================
    // 🎁 GATILHO REATIVO: EXIBE E APLICA O DESCONTO DO RANKING AUTOMATICAMENTE
    // =========================================================================
    // Resgata com segurança as variáveis que o PHP injetou na sessão lá no topo
    const corteVipPre = "<?php echo $_SESSION['servico_pre_selecionado'] ?? ''; ?>";
    const percentualVipPre = parseInt("<?php echo $_SESSION['desconto_cupao_ganho'] ?? 0; ?>");

    if (corteVipPre !== "" && percentualVipPre > 0) {
        console.log(`🎁 Cupão detetado: ${corteVipPre} com -${percentualVipPre}%`);

        // 1. Aciona a tua função blindada para preencher os textos e as caixas de preço
        if (typeof exibirPrecoFinal === "function") {
            // Passa o nome do corte e o texto sinalizando o cálculo do prémio
            exibirPrecoFinal(corteVipPre, "A aplicar prémio... kz");
            
            // Fixa os dados nas variáveis globais masters exigidas pelo teu botão de gravação
            window.nomeServicoGlobal = corteVipPre;
            
            // 2. Localiza o teu botão de agendamento e carimba o selo de desconto ganho
            const btnConfirmar = document.querySelector('.btn-confirmar') || document.querySelector('.btn-confirmar-sessao');
            if (btnConfirmar) {
                btnConfirmar.innerHTML = `📅 Confirmar Atendimento (Cupão -${percentualVipPre}% Ativo!)`;
                btnConfirmar.style.background = "linear-gradient(135deg, #22c55e, #16a34a)"; // Força a cor verde de sucesso
                btnConfirmar.style.color = "#ffffff";
                btnConfirmar.style.fontWeight = "bold";
            }
        }
    }


    document.addEventListener("DOMContentLoaded", function() {
        // Verifica se o cliente já aceitou o rastreamento nesta máquina anteriormente
        let consentimentoBI = localStorage.getItem("aurelius_consentimento_bi");
        
        // Se ainda não existir a chave gravada, o motor força a aparição do banner mudando para flex
        if (!consentimentoBI) {
            document.getElementById("cookieBanner").style.display = "flex";
        }
    });

    // Função executada no clique do botão verde para salvar a escolha e sumir com o bloco
    function processarConsentimentoRealBI() {
        localStorage.setItem("aurelius_consentimento_bi", "aceito_auditado");
        document.getElementById("cookieBanner").style.display = "none";
        console.log("✓ Auditoria BI autorizada com sucesso na rede local.");
    }
</script>

<script>





// ⚡ CONTROLO TOTALMENTE DINÂMICO E ABERTO DO MODAL PREMIUM
const tabelaPrecosPromocionais = { mensal: 1000, semestral: 5000, anual: 9000 };

function atualizarPrecoPlano(valor) {
    const txtBotao = document.getElementById('txtPrecoBotao');
    if (txtBotao) txtBotao.innerText = valor.toLocaleString('pt-PT');
    
    const plano = document.querySelector('input[name="planoPremium"]:checked').value;
    const caixas = {
        mensal: document.getElementById('labelMensal'),
        semestral: document.getElementById('labelSemestral'),
        anual: document.getElementById('labelAnual')
    };
    
    for (const chave in caixas) {
        if (caixas[chave]) {
            if (chave === plano) {
                caixas[chave].style.borderColor = "#ca8a04";
                caixas[chave].style.background = "#131e35";
            } else {
                caixas[chave].style.borderColor = "#334155";
                caixas[chave].style.background = "#1e293b";
            }
        }
    }
}

function fecharModalPremium() {
    document.getElementById('modalPremium').style.display = 'none';
}

function abrirModalPremium() {
    const modal = document.getElementById('modalPremium');
    if (modal) {
        modal.style.display = 'flex';
        atualizarPrecoPlano(tabelaPrecosPromocionais.mensal);
    }
}


function processarPagamentoExpressAberto() {
    const telefone = document.getElementById('telefoneExpress').value.trim();
    const planoEscolhido = document.querySelector('input[name="planoPremium"]:checked').value;
    
    // CAPTURA REAL: Lê o valor exato que o utilizador digitou no input do Painel Azul
    const inputNome = document.getElementById('inputNomeCliente');
    const nomeCliente = inputNome ? inputNome.value.trim() : "";
    
    // Validação de segurança para impedir o nome fixo de teste
    if (nomeCliente === "" || nomeCliente === "Nome do Cliente (Obrigatório)") {
        alert("⚠️ Por favor, digite o Nome do Cliente no formulário (Painel Azul) antes de ativar o plano Premium!");
        return;
    }
    
    if (telefone.length !== 9) {
        alert("⚠️ Grupo Aurélius: Insira um número de telemóvel Express válido com 9 dígitos.");
        return;
    }
    
    // Dispara o envio dos dados dinâmicos reais para o servidor
    fetch('salvar_assinatura.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json'
        },
        body: JSON.stringify({
            telefone: telefone,
            plano: planoEscolhido,
            nome: nomeCliente
        })
    })
    .then(response => response.json())
    .then(dados => {
        if (dados.status === 'sucesso') {
            alert(`🎉 Inscrição Concluída...!\n\nEstatuto PREMIUM ativado para o cliente ${nomeCliente}.\n\nDesconto de 2% já disponível no sistema!`);
            fecharModalPremium();
        } else {
            alert("❌ Erro ao ativar o plano: " + dados.mensagem);
        }
    })
    .catch(erro => {
        console.error("Erro na requisição:", erro);
        alert("❌ Erro de conectividade com o servidor.");
    });
}








    let nomeServicoGlobal = "";
    let valorServicoGlobal = 0;
    document.getElementById('printLinhaDesconto').style.display = 'flex';
document.getElementById('printMensagemAgradecimentoVIP').style.display = 'block';

// Dentro do 'else':
document.getElementById('printLinhaDesconto').style.display = 'none';
document.getElementById('printMensagemAgradecimentoVIP').style.display = 'none';
    function irParaSecao(idSecao) {
        // 1. Oculta todas as abas do sistema primeiro
        document.querySelectorAll('.aba-conteudo').forEach(div => {
            div.style.display = 'none';
            div.classList.remove('active');
        });
    
        // 2. Localiza a seção desejada (ex: secao-funcionarios)
        const alvo = document.getElementById('secao-' + idSecao);
        if (alvo) {
            // 3. Torna a seção visível e ativa na tela
            alvo.style.display = 'block';
            alvo.classList.add('active');
        }
        
        // 4. Atualiza o endereço na barra do navegador (#funcionarios)
        window.location.hash = idSecao;
    }

    // Abre a categoria clicada e esconde o painel principal de categorias
    function alternarCategoria(idCategoria) {
        // Esconde todas as listas de serviços abertas
        document.querySelectorAll('.aba-conteudo').forEach(aba => {
            aba.style.display = 'none';
        });
        
        // Esconde o bloco principal com as 11 categorias para limpar a tela
        document.getElementById('secao-categorias').style.display = 'none';
        
        // Mostra apenas a lista de serviços da categoria que foi clicada
        const alvo = document.getElementById(idCategoria);
        if(alvo) {
            alvo.style.display = 'block';
            alvo.scrollIntoView({ behavior: 'smooth' });
        }
    }

    // CORRIGIDO: Fecha os serviços e faz a grade das categorias reaparecer
    function fecharCategoria() {
        // Oculta todas as listas de serviços
        document.querySelectorAll('.aba-conteudo').forEach(aba => {
            aba.style.display = 'none';
        });

        // Faz a grade principal de categorias reaparecer no ecrã
        if (document.getElementById('secao-categorias')) {
            document.getElementById('secao-categorias').style.display = 'block';
        }
        
        // Oculta as barras de confirmação de preço inferiores
        if (document.getElementById('caixa-confirmacao')) {
            document.getElementById('caixa-confirmacao').style.display = 'none';
        }
        
        const barraVerde = document.getElementById('barra-topo-verde');
        if (barraVerde) barraVerde.style.display = 'none';
    } 

    // UNIFICADO E CORRIGIDO: Controla de forma absoluta a exibição dos funcionários pelo clique
    function toggleFuncionarios() {
        const secao = document.getElementById('secaoFuncionarios');
        const botao = document.getElementById('btnToggleFuncionarios');
        
        if (!secao || !botao) return;

        // Verifica se a propriedade display contém 'none' ou se está oculta nativamente
        if (secao.style.getPropertyValue('display').trim() === 'none' || secao.style.display === '' || window.getComputedStyle(secao).display === 'none') {
            // Remove a trava do display e mostra a lista
            secao.style.setProperty('display', 'block', 'important');
            botao.innerHTML = '❌ Ocultar Funcionários';
            botao.style.background = 'linear-gradient(135deg, #475569, #334155)';
        } else {
            // Adiciona a trava do display e esconde a lista novamente
            secao.style.setProperty('display', 'none', 'important');
            botao.innerHTML = '👥 Ver Funcionários';
            botao.style.background = 'linear-gradient(135deg, #3b82f6, #1d4ed8)';
        }
    }
    const telefoneCliente = document.getElementById('inputTelefoneCliente').value.trim();

// No bloco onde adiciona os campos ao FormData, acrescente:
dadosForm.append('cliente_telefone', telefoneCliente);

    // UNIFICADO: Faz a busca dinâmica e atualiza status e contadores sem duplicar código
    function atualizarIndicadores() {
        fetch('buscar_dados_dashboard.php')
            .then(response => {
                if (!response.ok) throw new Error('Erro na resposta do servidor');
                return response.json();
            })
            .then(dados => {
                // Atualiza sempre os contadores superiores (Caixa, Sessões, Equipa)
                if(document.getElementById('valorCaixa')) document.getElementById('valorCaixa').innerHTML = dados.caixa;
                if(document.getElementById('valorAtendimentos')) document.getElementById('valorAtendimentos').innerHTML = dados.atendimentos;
                if(document.getElementById('valorEquipa')) document.getElementById('valorEquipa').innerHTML = dados.equipa;

                // Atualiza os status ocultos nos bastidores (deixando os dados prontos para quando abrir)
                if (dados.status_funcionarios) {
                    Object.keys(dados.status_funcionarios).forEach(idNum => {
                        const elemento = document.getElementById('status-text-' + idNum);
                        if (elemento) {
                            const textoStatus = dados.status_funcionarios[idNum];
                            elemento.innerText = textoStatus;

                            // Trata e atualiza a cor do texto conforme o estado guardado no banco
                            if (textoStatus.includes('Ausente') || textoStatus.includes('Folga')) {
                                elemento.style.color = '#ef4444'; // Vermelho
                            } else if (textoStatus.includes('Atendimento') || textoStatus.includes('Em')) {
                                elemento.style.color = '#ffaa00'; // Laranja
                            } else {
                                elemento.style.color = '#22c55e'; // Verde
                            }
                        }
                    });
                }
            })
            .catch(error => console.error('Erro na atualização automática de indicadores:', error));
    }

    // Inicialização única do temporizador de 4 segundos
    document.addEventListener("DOMContentLoaded", function() {
        // 15000 milissegundos = 15 segundos (Poupe a memória do seu Apache local)
        setInterval(atualizarIndicadores, 15000);
    });

    // Rolagem suave e navegação mobile
    function irParaSecaoScroll(idSecao) {
        var secaoAlvo = document.getElementById(idSecao);
        if (secaoAlvo) {
            secaoAlvo.scrollIntoView({ behavior: 'smooth' });
        }
    }

    function irParaSecaoMobile(idSecao) {
        irParaSecaoScroll(idSecao);
        if (typeof toggleMenu === "function") {
            toggleMenu();
        }
    }
    function abrirAbas() {
        var modal = document.getElementById('modalAbas');
        if (modal) modal.style.display = 'flex';
    }

    function fecharAbas() {
        var modal = document.getElementById('modalAbas');
        if (modal) modal.style.display = 'none';
    }

    function abrirTermos() {
        var modal = document.getElementById('modalTermos');
        if (modal) modal.style.display = 'flex';
    }

    function fecharTermos() {
        var modal = document.getElementById('modalTermos');
        if (modal) modal.style.display = 'none';
    }
   

  





























function atualizarIndicadores() {
    fetch('buscar_dados_dashboard.php')
        .then(response => {
            if (!response.ok) throw new Error('Erro na resposta do servidor');
            return response.json();
        })
        .then(dados => {
            if (document.getElementById('valorCaixa')) {
                document.getElementById('valorCaixa').innerHTML = dados.caixa;
            }
            if (document.getElementById('valorAtendimentos')) {
                document.getElementById('valorAtendimentos').innerHTML = dados.atendimentos;
            }
            if (document.getElementById('valorEquipa')) {
                document.getElementById('valorEquipa').innerHTML = dados.equipa;
            }
        })
        .catch(error => console.error('Erro na atualização automática:', error));
}

document.addEventListener("DOMContentLoaded", function() {
    atualizarIndicadores(); 
    setInterval(atualizarIndicadores, 4000);
});


function enviarMarcacaoParaBanco() {
    // 1. Captura os dados reais do formulário do topo
    const inputCliente = document.getElementById('inputNomeCliente') || document.querySelector('input[type="text"]');
    const inputFuncionario = document.getElementById('inputFuncionario') || document.querySelector('select');
    const inputData = document.getElementById('inputDataServico') || document.querySelector('input[type="date"]');
    const inputHora = document.getElementById('inputHoraServico') || document.querySelector('input[type="time"]');

    const cliente = inputCliente ? inputCliente.value.trim() : "Consumidor Final";
    const funcionario = inputFuncionario ? inputFuncionario.value : "Não Informado";
    const data = inputData && inputData.value ? inputData.value : new Date().toISOString().split('T')[0];
    const hora = inputHora && inputHora.value ? inputHora.value : "10:00";

    // 2. Validações estritas de segurança
    if (!cliente || cliente === "Consumidor Final") {
        alert("Por favor, digite o Nome do Cliente no formulário do topo!");
        if (inputCliente) inputCliente.focus();
        return;
    }

    if (!funcionario || funcionario.includes("Selecione") || funcionario === "Não Informado") {
        alert("Por favor, selecione um Profissional no formulário do topo!");
        if (inputFuncionario) inputFuncionario.focus();
        return;
    }

    if (!window.nomeServicoGlobal) {
        alert("Por favor, clique numa categoria e selecione o tipo de serviço/corte antes de marcar!");
        return;
    }

    const valorServico = parseFloat(window.valorServicoGlobal) || 0;

    // 3. Montagem do FormData para leitura limpa do $_POST no PHP
    const dadosForm = new FormData();
    dadosForm.append('nome_cliente', cliente);
    dadosForm.append('funcionario', funcionario);
    dadosForm.append('data_servico', data);
    dadosForm.append('hora_servico', hora);
    dadosForm.append('servico', window.nomeServicoGlobal); 
    dadosForm.append('preco_base', valorServico);

    console.log("A enviar dados para a central de tempo...");

    // 🟢 CORREÇÃO CRÍTICA: Rota absoluta para o XAMPP local não se perder nas pastas
    const ficheiroDestino = 'salvar_agendamento.php';
    fetch(ficheiroDestino, {
        method: 'POST',
        body: dadosForm
    })
    .then(response => response.text()) // Captura a resposta crua para limpar espaços vazios
    .then(textoRaw => {
        // Limpa quebras de linha invisíveis que quebram o JSON.parse
        const textoLimpo = textoRaw.trim();
        
        try {
            const resultado = JSON.parse(textoLimpo);
            
            if (resultado.status === 'sucesso') {
                alert("✨ Marcação feita com sucesso e gravada no banco! ✨");
                
                // Emite o comprovativo real com os dados guardados
                imprimirRelatorioCompleto(cliente, funcionario, data, hora, window.nomeServicoGlobal, valorServico);

                // Executa o recarregamento dos indicadores em tempo real
                if (typeof atualizarIndicadores === "function") { atualizarIndicadores(); }
                if (inputCliente) inputCliente.value = "";
                
                // Força a atualização da página para recarregar a tabela do histórico na hora
                setTimeout(() => { window.location.reload(); }, 1000);
            } else {
                // 🚨 EXIBE O BLOQUEIO DE HORÁRIO ATIVO DO PHP DIRETAMENTE NA TELA
                alert(resultado.mensagem);
            }
        } catch (erroJson) {
            console.error("Erro ao processar resposta do PHP. Resposta recebida:", textoRaw);
            alert("⚠️ Sincronização Local ativa: Erro estrutural no interpretador. Gerando talão.");
            imprimirRelatorioCompleto(cliente, funcionario, data, hora, window.nomeServicoGlobal, valorServico);
        }
    })
    .catch(erroRede => {
        console.error("Falha de Rede local:", erroRede);
        alert("⚠️ O servidor local Apache está offline. Gerando talão de contingência.");
        imprimirRelatorioCompleto(cliente, funcionario, data, hora, window.nomeServicoGlobal, valorServico);
    });
}


const inputTelefoneVIP = document.getElementById('inputTelefoneCliente') || document.getElementById('telefone_cliente') || document.getElementById('inputTelefone');
const telefoneValor = inputTelefoneVIP ? inputTelefoneVIP.value.trim() : "900000000";

// Anexe o telefone no FormData para o PDO processar
dadosForm.append('cliente_telefone', telefoneValor);
















     
function mostrarNivel2(cat) {
    // 1. Esconde por completo o Nível 1 (Grade de Categorias)
    const nivel1 = document.getElementById('nivel1');
    if (nivel1) {
        nivel1.classList.add('hidden');
    }
    
    // 2. Torna visível o contêiner do Nível 2 (Área dos Serviços)
    const nivel2 = document.getElementById('nivel2');
    if (nivel2) {
        nivel2.classList.remove('hidden');
    }
    
    // 3. Força TODOS os subgrupos de serviços a receberem a classe hidden
    document.querySelectorAll('.sub-grupo').forEach(subGrupo => {
        subGrupo.classList.add('hidden');
    });
    
    // 4. Mostra exclusivamente o subgrupo da categoria que foi clicada
    const subGrupoAlvo = document.getElementById('sub-' + cat);
    if (subGrupoAlvo) {
        subGrupoAlvo.classList.remove('hidden');
        subGrupoAlvo.scrollIntoView({ behavior: 'smooth' });
    }
}

function voltarParaNivel1() {
    // 1. Faz a grade de categorias (Nível 1) reaparecer na tela
    const nivel1 = document.getElementById('nivel1');
    if (nivel1) {
        nivel1.classList.remove('hidden');
    }
    
    // 2. Esconde o bloco de serviços do Nível 2
    const nivel2 = document.getElementById('nivel2');
    if (nivel2) {
        nivel2.classList.add('hidden');
    }
    
    // 3. Garante que nenhum subgrupo isolado fique aberto
    document.querySelectorAll('.sub-grupo').forEach(subGrupo => {
        subGrupo.classList.add('hidden');
    });
    
    // 4. Limpa e oculta a caixa de confirmação de preço do topo
    const caixaPreco = document.getElementById('caixa-preco');
    if (caixaPreco) {
        caixaPreco.classList.add('hidden');
    }
}

// Botão de retorno para limpar a tela e voltar ao Nível 1
function voltarParaNivel1() {
    const nivel1 = document.getElementById('nivel1');
    if (nivel1) nivel1.classList.remove('hidden');
    
    const nivel2 = document.getElementById('nivel2');
    if (nivel2) nivel2.classList.add('hidden');
    
    const caixaPreco = document.getElementById('caixa-preco');
    if (caixaPreco) caixaPreco.classList.add('hidden');
}
// =========================================================================
// 🎯 CONTROLADOR MESTRE DE CLIQUE DO SERVIÇO (DESTRAVAMENTO GLOBAL DO CAIXA)
// =========================================================================
function exibirPrecoFinal(nome, valorTexto) {
    // 1. Grava obrigatoriamente na memória master absoluta que o teu botão verde lê
    window.nomeServicoGlobal = nome;
    
    // Extrai apenas os números (ex: "4.500 kz" vira 4500) para o PHP não bugar
    let valorLimpo = valorTexto.replace('kz', '').replace(/\./g, '').trim();
    window.valorServicoGlobal = parseFloat(valorLimpo);

    console.log("🎯 Serviço fixado globalmente:", window.nomeServicoGlobal, "por", window.valorServicoGlobal, "Kz");

    // 2. Alimenta TODOS os possíveis elementos de texto no teu HTML (evita erros de ID)
    const IDsNome = ['nome-servico', 'nome-servico-selecionado', 'barra-verde-nome'];
    const IDsPreco = ['valor-servico', 'preco-servico-selecionado', 'barra-verde-preco'];
    const IDsCaixa = ['caixa-preco', 'caixa-confirmacao', 'barra-topo-verde'];

    IDsNome.forEach(id => {
        const el = document.getElementById(id);
        if (el) el.innerText = nome;
    });

    IDsPreco.forEach(id => {
        const el = document.getElementById(id);
        if (el) el.innerText = valorTexto;
    });

    // 3. Força a exibição imediata e remove qualquer classe 'hidden' das caixas de preço
    IDsCaixa.forEach(id => {
        const el = document.getElementById(id);
        if (el) {
            el.classList.remove('hidden');
            el.style.display = 'block';
        }
    });
}


// Vincula a rota antiga dos teus botões dinâmicos/estáticos para a nova engine blindada
function selecionarServico(nome, preco) {
    exibirPrecoFinal(nome, preco.toLocaleString('pt-AO') + " kz");




















}function imprimirRelatorioCompleto(clienteParam, funcionarioParam, dataParam, horaParam, servicoParam, valorParam) {
    // Mecanismo de segurança: se o parâmetro não vier, busca do formulário ou variáveis globais
    const cliente = clienteParam || document.getElementById('inputNomeCliente')?.value || "Consumidor Final";
    const funcionario = funcionarioParam || document.getElementById('inputFuncionario')?.value || "Não Informado";
    
    // CORREÇÃO: Captura segura da data sem quebrar o split
    let dataInput = dataParam || document.getElementById('inputDataServico')?.value;
    const dataRaw = dataInput ? dataInput.split('T')[0] : new Date().toISOString().split('T')[0];
    
    const hora = horaParam || document.getElementById('inputHoraServico')?.value || "--:--";
    const servico = servicoParam || (typeof nomeServicoGlobal !== 'undefined' ? nomeServicoGlobal : "Serviço Geral");
    const valor = valorParam !== undefined ? valorParam : (typeof valorServicoGlobal !== 'undefined' ? valorServicoGlobal : 0);

    // Formata a data do padrão do sistema (AAAA-MM-DD) para DD/MM/AAAA
    let dataFormatada = "--/--/----";
    if (dataRaw && dataRaw.includes("-")) {
        const partes = dataRaw.split("-");
        if (partes.length === 3) {
            dataFormatada = partes[2] + "/" + partes[1] + "/" + partes[0];
        }
    } else {
        dataFormatada = dataRaw;
    }
    
    // Formata o valor monetário separando os milhares por espaço (ex: 15 000 Kz)
    let precoNumerico = parseFloat(valor) || 0;
    let precoFormatado = precoNumerico.toLocaleString('pt-PT').replace(/\./g, ' ');

    // Injeta os estilos CSS otimizados para Impressoras Térmicas de Talões (80mm / 58mm)
    let estiloImpressao = document.getElementById('estilo-impressao-aurelius');
    if (!estiloImpressao) {
        estiloImpressao = document.createElement('style');
        estiloImpressao.id = 'estilo-impressao-aurelius';
        estiloImpressao.innerHTML = `
            @media print {
                /* Esconde tudo o que está no site, exceto a área da fatura */
                body * { display: none !important; }
                #area-impressao-global, #area-impressao-global * { display: block !important; }
                
                body, html {
                    background-color: #ffffff !important;
                    color: #000000 !important;
                    margin: 0 !important;
                    padding: 0 !important;
                    width: 100% !important;
                }
                .no-print-btn { display: none !important; }
                @page { size: auto; margin: 0mm; }
                
                /* Força o visual compacto de talão térmico na bobine */
                .zona-recibo-impressao { padding: 0 !important; min-height: auto !important; background: #fff !important; }
                .recibo-card-premium { 
                    max-width: 100% !important; 
                    width: 80mm !important; /* Padrão de impressora térmica */
                    padding: 10px !important; 
                    box-shadow: none !important; 
                    background: #fff !important;
                    border: none !important;
                }
                /* Alterna os textos vermelhos/azuis para preto legível no papel térmico */
                .recibo-card-premium text, .recibo-card-premium span, .recibo-card-premium h1, .recibo-card-premium label {
                    color: #000000 !important;
                }
                .bloco-total { border: 1px dashed #000 !important; background: #fff !important; color: #000 !important; }
                .bloco-total * { color: #000 !important; }
            }
            
            /* Visualização bonita no Ecrã antes de mandar imprimir */
            .zona-recibo-impressao {
                background-color: rgba(11, 26, 48, 0.95);
                font-family: 'Courier New', Courier, monospace;
                position: fixed;
                top: 0; left: 0; width: 100%; height: 100vh;
                z-index: 99999;
                display: flex;
                justify-content: center;
                align-items: center;
                overflow-y: auto;
                padding: 20px;
                box-sizing: border-box;
            }
            .recibo-card-premium {
                background-color: #111e38;
                border: 2px solid #0088cc;
                border-radius: 12px;
                width: 100%;
                max-width: 400px;
                padding: 25px;
                box-shadow: 0 0 20px rgba(0, 136, 204, 0.4);
                box-sizing: border-box;
            }
        `;
        document.head.appendChild(estiloImpressao);
    }

    const areaImpressao = document.getElementById('area-impressao-global');
    if (areaImpressao) {
        // CORREÇÃO: Torna a div de impressão visível imediatamente no ecrã ao clicar
        areaImpressao.style.display = 'flex';
        
        areaImpressao.innerHTML = `
            <div class="zona-recibo-impressao">
                <div class="recibo-card-premium">
                    
                    <!-- Botão Fechar no Ecrã -->
                    <span class="no-print-btn" onclick="document.getElementById('area-impressao-global').style.display='none'" style="float: right; color: #ef4444; font-size: 24px; cursor: pointer; font-weight: bold; font-family: sans-serif; line-height: 1;">&times;</span>
                    
                    <!-- Cabeçalho do Talão -->
                    <div style="text-align: center; margin-bottom: 15px;">
                        <h1 style="color: #38bdf8; font-size: 20px; margin: 0 0 4px 0; text-transform: uppercase; font-weight: bold;">Barbearia Branca</h1>
                        <p style="color: #94a3b8; font-size: 11px; margin: 0; text-transform: uppercase;">Comprovativo de Atendimento</p>
                    </div>

                    <div style="border-top: 1px dashed #334155; margin: 12px 0;"></div>

                    <!-- Dados Dinâmicos da Fatura -->
                    <div style="display: flex; flex-direction: column; gap: 12px; margin-bottom: 20px; font-size: 13px; text-align: left;">
                        <div style="display: flex; justify-content: space-between;">
                            <label style="color: #64748b; font-weight: bold;">Cliente:</label>
                            <span style="color: #ffffff; font-weight: bold;">` + cliente + `</span>
                        </div>

                        <div style="display: flex; justify-content: space-between;">
                            <label style="color: #64748b; font-weight: bold;">Profissional:</label>
                            <span style="color: #ffffff;">` + funcionario + `</span>
                        </div>

                        <div style="display: flex; justify-content: space-between;">
                            <label style="color: #64748b; font-weight: bold;">Serviço:</label>
                            <span style="color: #ffffff;">` + servico + `</span>
                        </div>

                        <div style="display: flex; justify-content: space-between;">
                            <label style="color: #64748b; font-weight: bold;">Data/Hora:</label>
                            <span style="color: #38bdf8; font-weight: bold;">` + dataFormatada + ` - ` + hora + `</span>
                        </div>
                    </div>

                    <!-- Bloco de Total Pago -->
                    <div class="bloco-total" style="background-color: #0b1329; border-left: 4px solid #22c55e; padding: 12px; border-radius: 6px; display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; box-sizing: border-box;">
                        <label style="color: #22c55e; font-size: 12px; font-weight: bold; text-transform: uppercase; margin: 0;">Total:</label>
                        <span style="color: #22c55e; font-size: 18px; font-weight: bold;">` + precoFormatado + ` Kz</span>
                    </div>

                    <div style="border-top: 1px dashed #334155; margin: 12px 0;"></div>

                    <!-- Rodapé do Talão -->
                    <div style="text-align: center; font-size: 12px; color: #38bdf8; font-weight: bold; margin-bottom: 4px;">Obrigado pela preferência!</div>
                    <div style="text-align: center; font-size: 10px; color: #94a3b8; line-height: 1.4; margin-bottom: 20px;">
                        📍 Bairro de São Luís / perto da IECA<br>
                        Huambo - Angola
                    </div>

                    <!-- Botão de Ação -->
                    <button type="button" class="no-print-btn" onclick="window.print()" style="width: 100%; background-color: #22c55e; color: white; border: none; padding: 12px; font-size: 13px; font-weight: bold; border-radius: 6px; cursor: pointer; text-transform: uppercase; letter-spacing: 0.5px;">
                        🖨️ Imprimir Talão
                    </button>
                    
                </div>
            </div>
        `;
    }
}

























function processarPagamentoExpress() {
    const telefoneInput = document.getElementById('telefoneExpress').value.trim();
    const planoRadio = document.querySelector('input[name="planoPremium"]:checked');
    const planoNome = planoRadio.value;
    const valorPagar = document.getElementById('txtPrecoBotao').innerText;

    // Validação de número móvel em Angola
    if (telefoneInput.length !== 9 || !telefoneInput.startsWith('9')) {
        alert('Por favor, insira um número de telefone válido com 9 dígitos ().');
        return;
    }

    // Informa o utilizador que o processo começou
    alert(`A enviar pedido de ${valorPagar} Kz para o Express do número ${telefoneInput}...\nPor favor, valide a notificação no seu telemóvel.`);

    // Cria os dados que vão ser enviados para o PHP
    const dados = new FormData();
    dados.append('telefone', telefoneInput);
    dados.append('plano', planoNome);

    // Envia os dados para o processar_pagamento.php via AJAX (Fetch API)
    fetch('processar_pagamento.php', {
        method: 'POST',
        body: dados
    })
    .then(resposta => resposta.json())
    .then(resultado => {
        if (resultado.sucesso) {
            alert('Sucesso! 🎉 ' + resultado.mensagem);
            fecharModalPremium();
            location.reload(); // Recarrega a página para atualizar o painel azul na hora
        } else {
            alert('Aviso: ' + resultado.mensagem);
        }
    })
    .catch(erro => {
        console.error('Erro:', erro);
        alert('Ocorreu um erro ao processar o seu plano na base de dados.');
    });
}









        
    
        function prepararEnvio() {
            // Captura os dados do topo da página
            const cliente = document.getElementById('inputNomeCliente').value.trim();
            const funcionario = document.getElementById('inputFuncionario').value;
            const data = document.getElementById('inputDataServico').value;
            const hora = document.getElementById('inputHoraServico').value;
    
            // Validação obrigatória antes de submeter ao arquivo PHP
            if (!cliente || !funcionario || !data || !hora || !nomeServicoGlobal) {
                alert("Por favor, preencha todos os campos do atendimento no topo e selecione um serviço!");
                return false;
            }
    
            // Passa os valores para os inputs ocultos do formulário
            document.getElementById('envioCliente').value = cliente;
            document.getElementById('envioFuncionario').value = funcionario;
            document.getElementById('envioData').value = data;
            document.getElementById('envioHora').value = hora;
            document.getElementById('envioServico').value = nomeServicoGlobal;
            document.getElementById('envioValor').value = valorServicoGlobal;
    
            return true;
        }

           
            // Carrega automaticamente os estados guardados ao abrir a página
            window.addEventListener('DOMContentLoaded', () => {
                const funcionariosIds = ['hanganga', 'angelino', 'aurelio', 'fernandinho', 'raimundo', 'zidane', 'albino', 'tuxa','edna', 'belma', 'dalton', 'magui'];
                
                funcionariosIds.forEach(id => {
                    const statusGuardado = localStorage.getItem('status-' + id);
                    if (statusGuardado) {
                        const elemento = document.getElementById('status-' + id);
                        if (elemento) {
                            elemento.innerText = statusGuardado;
                            
                            // Trata e atualiza a cor do texto conforme o estado guardado
                            if (statusGuardado.includes('Ausente') || statusGuardado.includes('Folga')) {
                                elemento.style.color = '#ef4444';
                            } else if (statusGuardado.includes('Atendimento')) {
                                elemento.style.color = '#ffaa00';
                            } else {
                                elemento.style.color = '#22c55e';
                            }
                        }
                    }
                });
            });

            
function atualizarIndicadores() {
    // Faz a chamada para o ficheiro que procura os dados atualizados no banco de dados
    fetch('buscar_dados_dashboard.php')
        .then(response => response.json())
        .then(dados => {
            // Atualiza o texto mantendo a estrutura visual limpa
            document.getElementById('valorCaixa').innerHTML = dados.caixa;
            document.getElementById('valorAtendimentos').innerHTML = dados.atendimentos;
            document.getElementById('valorEquipa').innerHTML = dados.equipa;
        })
        .catch(error => console.error('Erro na atualização automática:', error));
}

// Inicializa a verificação recorrente
document.addEventListener("DOMContentLoaded", function() {
    // Atualiza a cada 4 segundos (4000 milissegundos)
    setInterval(atualizarIndicadores, 4000);
});




            // Mostra ou oculta as opções de alteração para o Gerente/Administrador
            function toggleModoAdmin() {
                const paineis = document.querySelectorAll('.select-admin');
                paineis.forEach(painel => {
                    painel.style.display = (painel.style.display === 'none' || painel.style.display === '') ? 'block' : 'none';
                });
            }

            // Altera o estado dinamicamente e guarda na memória local do navegador (LocalStorage)
            function atualizarStatus(idFuncionario, novoValor) {
                const elemento = document.getElementById('status-' + idFuncionario);
                if (elemento) {
                    elemento.innerText = novoValor;
                    localStorage.setItem('status-' + idFuncionario, novoValor);
                    
                    // Trata as cores em tempo real ao selecionar
                    if (novoValor.includes('Ausente') || novoValor.includes('Folga')) {
                        elemento.style.color = '#ef4444';
                    } else if (novoValor.includes('Atendimento')) {
                        elemento.style.color = '#ffaa00';
                    } else {
                        elemento.style.color = '#22c55e';
                    }
                }
            }
            

            function Funcionario() {
                console.log("Visualizando portfólio e informações do profissional.");
            }


// Cole esta função dentro do seu bloco de scripts atual
function fecharBannerCookies() {
    const banner = document.getElementById('cookieBanner');
    if (banner) {
        // Esconde o banner da tela imediatamente ao clicar no botão verde
        banner.style.display = 'none';
        
        // Salva na memória do navegador que o usuário já aceitou, 
        // para que o banner não fique reaparecendo toda hora que atualizar a página
        localStorage.setItem('cookiesAceitos', 'sim');
    }
}

// Bloquinho extra de segurança: Verifica se o usuário já aceitou antes. 
// Se já aceitou anteriormente, o banner nem aparece ao carregar a página.
window.addEventListener('DOMContentLoaded', () => {
    // Se tinha algum código seu aqui para carregar estados, cole-o aqui!
    console.log("Página e estados carregados com sucesso!");
});
// Abre e fecha o menu lateral mudando a posição do painel 'left'
function toggleMenu() {
    const menuLateral = document.getElementById('menuLateralMobile');
    if (menuLateral.style.left === '0px') {
        menuLateral.style.left = '-280px'; // Esconde o menu
    } else {
        menuLateral.style.left = '0px'; // Desliza o menu para dentro da tela
    }
}
// Chamado ao clicar no Menu Superior (Garante o Reset para o Nível 1)
function alternarAbas(idSecao) {
    // 1. Oculta todas as abas principais do sistema
    document.querySelectorAll('.aba-conteudo').forEach(div => {
        div.style.display = 'none';
        div.classList.remove('active');
    });

    // 2. Localiza a secção alvo (ex: secao-servicos)
    const alvo = document.getElementById('secao-' + idSecao);
    if (alvo) {
        alvo.style.display = 'block';
        alvo.classList.add('active');
        
        // Se a secção aberta for a de serviços, força a voltar para o Passo 1 (Categorias)
        if (idSecao === 'servicos') {
            voltarParaNivel1();
        } else {
            alvo.scrollIntoView({ behavior: 'smooth' });
        }
    }
}

// Chamado ao clicar numa Categoria (Esconde Nível 1, Mostra Nível 2 e ativa o Sub-grupo)
function mostrarNivel2(cat) {
    // Esconde as categorias do Nível 1
    const nivel1 = document.getElementById('nivel1');
    if (nivel1) nivel1.classList.add('hidden');
    
    // Mostra o contêiner de serviços do Nível 2
    const nivel2 = document.getElementById('nivel2');
    if (nivel2) nivel2.classList.remove('hidden');
    
    // Oculta todos os sub-grupos de serviços para não misturar
    document.querySelectorAll('.sub-grupo').forEach(div => {
        div.classList.add('hidden');
    });
    
    // Mostra exclusivamente o sub-grupo da categoria clicada (ex: sub-cortes)
    const subGrupo = document.getElementById('sub-' + cat);
    if (subGrupo) {
        subGrupo.classList.remove('hidden');
        subGrupo.scrollIntoView({ behavior: 'smooth' });
    }
}

// Chamado ao clicar no botão "Voltar" (Reseta a tela de volta para as Categorias)
function voltarParaNivel1() {
    // Faz a grade de categorias reaparecer
    const nivel1 = document.getElementById('nivel1');
    if (nivel1) nivel1.classList.remove('hidden');
    
    // Esconde o container do Nível 2
    const nivel2 = document.getElementById('nivel2');
    if (nivel2) nivel2.classList.add('hidden');
    
    // Esconde a caixa de confirmação de preço superior
    const caixaPreco = document.getElementById('caixa-preco');
    if (caixaPreco) caixaPreco.classList.add('hidden');
}



function calcularPrecoServico() {
    // Injeta o status da sessão PHP diretamente no JavaScript
    const isPremium = <?php echo (isset($_SESSION['tipo_conta']) && $_SESSION['tipo_conta'] === 'Premium') ? 'true' : 'false'; ?>;
    
    let precoBase = 3000; // Preço normal da Tintura/Serviço Geral
    let precoFinal = precoBase;

    if (isPremium) {
        precoFinal = precoBase - (precoBase * 0.20); // Aplica os 20% -> 2.400 Kz
    }

    // Atualiza o ecrã verde do Dashboard
    const lblPreco = document.getElementById('lblPrecoExibido');
    if (lblPreco) {
        lblPreco.innerText = precoFinal.toLocaleString('pt-PT') + " Kz";
    }
}
// 1. FUNÇÃO PROFISSIONAL PARA FECHAR O MODAL E ATUALIZAR O PAINEL
function fecharFaturaNatural() {
    document.getElementById('faturaPainelNatural').style.display = 'none';
    location.reload(); // Atualiza as listas do salão na hora
}

// 2. DISPARADOR REAL DE IMPRESSÃO FÍSICA
function dispararImpressaoFisica() {
    window.print();
}

function salvarAgendamentoSessao() {
    const nome = document.getElementById('inputNomeCliente').value.trim();
    const funcionario = document.getElementById('inputFuncionario').value;
    const data = document.getElementById('inputDataServico').value;
    const hora = document.getElementById('inputHoraServico').value;
    
    // Captura os dados com base nas variáveis globais selecionadas no ecrã
    const servicoNome = typeof nomeServicoGlobal !== 'undefined' ? nomeServicoGlobal : "Mechas / Luzes"; 
    const precoServicoOriginal = typeof valorServicoGlobal !== 'undefined' ? parseFloat(valorServicoGlobal) : 18000;

    if (!funcionario || !data || !hora) {
        alert('Por favor, selecione o profissional, a data e a hora do atendimento.');
        return;
    }

    // =================================================================
    // 🌍 ALTERNATIVA AUTOMÁTICA: PREMIUM DESCONTA (20%) | GRÁTIS NÃO DESCONTA
    // =================================================================
    const tipoContaCliente = "<?php echo $_SESSION['tipo_conta'] ?? 'Gratis'; ?>";
    
    let precoFinalEnvio = precoServicoOriginal;
    let valorDesconto = 0;

    if (tipoContaCliente === 'Premium') {
        // Alternativa 1: É Premium -> Calcula os 20% de abatimento real
        valorDesconto = precoServicoOriginal * 0.20; 
        precoFinalEnvio = precoServicoOriginal - valorDesconto; 
    } else {
        // Alternativa 2: É Grátis -> Preço permanece normal e cheio
        precoFinalEnvio = precoServicoOriginal;
        valorDesconto = 0;
    }

    const dadosForm = new FormData();
    dadosForm.append('nome_cliente', nome);
    dadosForm.append('funcionario', funcionario);
    dadosForm.append('data_servico', data);
    dadosForm.append('hora_servico', hora);
    dadosForm.append('servico', servicoNome); 
    dadosForm.append('preco_base', precoFinalEnvio); // Grava o valor correto final no MySQL

    fetch('./salvar_agendamento.php', {
        method: 'POST',
        body: dadosForm
    })
    .then(resposta => resposta.json())
    .then(resultado => {
        if (resultado.sucesso) {
            
            // Organiza a cronologia da data no padrão pt-PT
            const partesData = data.split('-');
            const dataFormatada = `${partesData[2]}/${partesData[1]}/${partesData[0]} às ${hora}`;

            // Injeção de metadados no Comprovativo Neon
            document.getElementById('natIdPagamento').innerText = resultado.id_pagamento ? resultado.id_pagamento : '62';
            document.getElementById('natNomeCliente').innerText = nome !== "" ? nome : "Aurelio";
            document.getElementById('natProfissional').innerText = funcionario;
            document.getElementById('natDataEmissao').innerText = dataFormatada;
            document.getElementById('natServicoNome').innerText = servicoNome;

            // =================================================================
            // 🎌 ALTERNATIVA VISUAL DE EXIBIÇÃO E VALIDAÇÃO NA FATURA
            // =================================================================
            if (tipoContaCliente === 'Premium') {
                // Se for Premium: Mostra o Subtotal, a linha vermelha de -20% e altera o título
                document.getElementById('printBlocoPremiumPrecos').style.display = 'flex';
                document.getElementById('natTextoTotalTitulo').innerText = "TOTAL PAGO (Membro VIP)";
                
                document.getElementById('natSubtotal').innerText = precoServicoOriginal.toLocaleString('pt-PT') + " Kz";
                document.getElementById('natDescontoValor').innerText = "- " + valorDesconto.toLocaleString('pt-PT') + " Kz";
                document.getElementById('natTotalFinal').innerText = precoFinalEnvio.toLocaleString('pt-PT') + " Kz";
            } else {
                // Se for Grátis: Oculta os blocos de desconto e exibe apenas o preço normal de tabela
                document.getElementById('printBlocoPremiumPrecos').style.display = 'none';
                document.getElementById('natTextoTotalTitulo').innerText = "TOTAL PAGO";
                
                document.getElementById('natTotalFinal').innerText = precoServicoOriginal.toLocaleString('pt-PT') + " Kz";
            }

            // Atualização do Código QR estável com os valores finais reais
            const textoQR = `Aurelius - Fatura: #00${resultado.id_pagamento || '62'} | Cliente: ${nome} | Total: ${precoFinalEnvio} Kz`;
            document.getElementById('natQrCode').src = "https://qrserver.com" + encodeURIComponent(textoQR);

            // Exibe a fatura no ecrã com o design escuro e neon da imagem
            document.getElementById('faturaPainelNatural').style.display = 'flex';

        } else {
            alert('Aviso do Sistema: ' + resultado.mensagem);
        }
    })
    .catch(erro => {
        console.error('Erro técnico:', erro);
        alert('Erro de comunicação com o servidor local.');
    });
}

// Se o cliente veio da página principal com um cupão ganho, ativa a caixa na hora!
const servicoPre = "<?php echo $_SESSION['servico_pre_selecionado'] ?? ''; ?>";
const descontoPre = parseInt("<?php echo $_SESSION['desconto_cupao_ganho'] ?? 0; ?>");

if (servicoPre !== "" && descontoPre > 0) {
    // Força a ativação do serviço na memória e exibe o preço com o abatimento aplicado
    exibirPrecoFinal(servicoPre, "Calculando... kz");
    
    // Atualiza a variável global com o desconto da plataforma ativo para a gravação
    window.nomeServicoGlobal = servicoPre;
    
    // Altera o texto do botão para o cliente saber que está a usar o cupão premiado
    const btnConfirmar = document.querySelector('.btn-confirmar');
    if (btnConfirmar) btnConfirmar.innerHTML = `📅 Fazer Marcação (Cupão -${descontoPre}% Ativo!)`;
}



</script>
   



<!-- =========================================================================
     🤖 INJETOR COGNITIVO: CONEXÃO AUTOMÁTICA DA LISTA SUSPENSA COM O BANCO
     ========================================================================= -->
<script>
document.addEventListener("DOMContentLoaded", function() {
    // 1. Captura dinamicamente o ID da empresa ativo na URL (?empresa=241)
    const parametrosUrl = new URLSearchParams(window.location.search);
    let idEmpresaSaaS = parametrosUrl.get('empresa') || '241';

    // 2. Localiza o elemento select estrutural no HTML do seu Dashboard
    const seletorProfissionais = document.getElementById('inputFuncionario');
    if (!seletorProfissionais) return;

    // 3. Efetua a chamada assíncrona em segundo plano para a API local
    fetch('obter_funcionarios_ajax.php?empresa=' + idEmpresaSaaS)
    .then(response => response.json())
    .then(funcionarios => {
        // Se o banco retornar dados reais, limpa as opções estáticas antigas
        if (funcionarios && funcionarios.length > 0) {
            seletorProfissionais.innerHTML = '<option value="">Selecione um profissional...</option>';
            
            // 4. Injeta dinamicamente os profissionais linha a linha do banco MariaDB
            funcionarios.forEach((func, indice) => {
                const novaOpcao = document.createElement('option');
                novaOpcao.value = func.id; // ID único do banco
                novaOpcao.innerText = `${indice + 1}º ${func.nome} (${func.cargo})`;
                seletorProfissionais.appendChild(novaOpcao);
            });
            console.log('🎉 Sincronização Sucedida: ' + funcionarios.length + ' profissionais injetados.');
        }
    })
    .catch(error => {
        console.warn('⚠️ Nota: Tabela de funcionários vazia ou inacessível. Mantendo a lista padrão de contingência.');
    });
});
</script>
    

    </body>

    <style>
    /* Grelha responsiva para os botões de horas ficarem alinhados lado a lado */
    .grade-horas-responsiva {
        display: grid !important;
        grid-template-columns: repeat(auto-fill, minmax(80px, 1fr)) !important;
        gap: 8px !important;
        width: 100% !important;
        box-sizing: border-box !important;
        margin-top: 10px !important;
    }
    
    /* Estilo base para cada crachá de horário */
    .slot-hora-card {
        padding: 8px 4px !important;
        border-radius: 6px !important;
        text-align: center !important;
        cursor: pointer !important;
        transition: 0.2s ease !important;
        box-sizing: border-box !important;
    }
</style>

<!-- =========================================================================
     🟩 ENGINE JAVASCRIPT: MOTOR DE MASCARAMENTO ANÓNIMO E SINCRO DE HORAS
     ========================================================================= -->
<script>
// Transmissão segura e higienizada do mapa de ocupação gerado pelo PHP
const pautaOcupadaDB = <?= json_encode($pauta_ocupada ?? []) ?>;

function auditarCompromissosHoraEspecifica(nomeMestre, horaClicada, estaOcupado, precoMestre, servicoMestre) {
    // 🔒 PRIVACIDADE TOTAL: Calcula a volumetria de ocupação diária sem expor o nome do cliente
    const totalReservasHoje = pautaOcupadaDB[nomeMestre] ? pautaOcupadaDB[nomeMestre].length : 0;
    
    let mensagemAuditoria = `📋 PAUTA DE AUDITORIA OPERACIONAL\n`;
    mensagemAuditoria += `-------------------------------------------\n`;
    mensagemAuditoria += `💈 Profissional: Mestre ${nomeMestre}\n`;
    mensagemAuditoria += `⏱ Horário Clicado: ${horaClicada}\n`;
    mensagemAuditoria += `📊 Volume de Ocupação Hoje: ${totalReservasHoje} Atendimentos Marcados\n`;
    mensagemAuditoria += `-------------------------------------------\n`;

    if (estaOcupado) {
        mensagemAuditoria += `❌ ESTADO: BLOQUEADO (Horário já reservado na base de dados)\n\n`;
        mensagemAuditoria += `🔒 Por razões de segurança e privacidade da rede, os dados do cliente destinatário encontram-se encriptados e confidenciais no balcão.`;
        alert(mensagemAuditoria);
        return;
    }

    // Se o horário estiver livre, avisa o balcão e preenche o checkout automaticamente
    mensagemAuditoria += `🟢 ESTADO: LIVRE (Vaga disponível para agendamento)\n`;
    mensagemAuditoria += `💸 Preço de Tabela Base: ${precoMestre.toLocaleString('pt-PT', {minimumFractionDigits: 2})} AOA\n`;
    mensagemAuditoria += `🎯 Serviço Processado: ${servicoMestre}\n\n`;
    mensagemAuditoria += `✓ Clique em OK para carregar estes dados de atendimento no formulário superior!`;

    if (confirm(mensagemAuditoria)) {
        // Injeta automaticamente os valores reais nas caixas do seu formulário superior
        const inputHora = document.getElementById('inputHoraServico');
        const inputFunc = document.getElementById('inputFuncionario');
        
        if (inputHora) inputHora.value = horaClicada;
        
        // Sincroniza o select do profissional de forma inteligente
        if (inputFunc) {
            inputFunc.value = nomeMestre;
            if (inputFunc.tagName === 'SELECT') {
                for (let i = 0; i < inputFunc.options.length; i++) {
                    if (inputFunc.options[i].value.trim() === nomeMestre.trim()) {
                        inputFunc.selectedIndex = i;
                        break;
                    }
                }
            }
        }

        // Atualiza a pauta de preços do seu contêiner reativo lateral
        if(document.getElementById('nome-servico')) document.getElementById('nome-servico').innerText = servicoMestre;
        if(document.getElementById('valor-servico')) document.getElementById('valor-servico').innerText = precoMestre.toLocaleString('pt-PT', {minimumFractionDigits: 2}) + " Kz";
        if(document.getElementById('checkout_preco_raw')) document.getElementById('checkout_preco_raw').value = precoMestre.toFixed(2);

        // Rola a tela suavemente para que o operador veja o formulário preenchido no topo
        window.scrollTo({ top: 0, behavior: 'smooth' });
    }
}
</script>
    </html>