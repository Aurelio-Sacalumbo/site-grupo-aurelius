



<?php
// =========================================================================
// 📡 MOTOR PHP DE CONEXÃO E PERSISTÊNCIA REAL — ANGELINO COMERCIAL CORE
// =========================================================================
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
date_default_timezone_set('Africa/Luanda');

include_once("config/Banco.php"); 

// Garante estabilidade na ligação ao MySQLi do XAMPP
$conexao_link = $conexao_aurelius ?? $conexao ?? $link ?? $conn ?? $pdo ?? null;
if (!$conexao_link || !($conexao_link instanceof mysqli)) {
    $conexao_link = @mysqli_connect("127.0.0.1", "root", "", "aurelius_salao");
}

if ($conexao_link) {
    mysqli_set_charset($conexao_link, "utf8mb4");
}

// 🟢 1. EXTRAÇÃO REAL DE DADOS PARA ALIMENTAR O JAVASCRIPT
$barbearias_reais_db = [];
$servicos_reais_db     = [];

if ($conexao_link) {
    // Busca todas as filiais registadas e confirmadas na tabela lojas
    $query_filiais = mysqli_query($conexao_link, "SELECT id, nome_loja as nome, endereco_armazem as localizacao, 'download (5).png' as img FROM `lojas` WHERE `visivel_no_site` = 1");
    if ($query_filiais) {
        while ($f = mysqli_fetch_assoc($query_filiais)) {
            $barbearias_reais_db[] = [
                "id" => intval($f['id']),
                "nome" => htmlspecialchars($f['nome']),
                "localizacao" => htmlspecialchars($f['localizacao']),
                "img" => $f['img']
            ];
        }
    }

    // Busca todos os serviços e produtos ativos partilhados da tabela produtos_cosmeticos
    $query_servicos = mysqli_query($conexao_link, "SELECT id, 'Cortes' as categoria, nome_produto as nome, preco FROM `produtos_cosmeticos` WHERE `stock_atual` > 0");
    if ($query_servicos) {
        while ($s = mysqli_fetch_assoc($query_servicos)) {
            $servicos_reais_db[] = [
                "id" => intval($s['id']),
                "categoria" => "Serviços", // Mapeamento unificado
                "nome" => htmlspecialchars($s['nome']),
                "preco" => floatval($s['preco'])
            ];
        }
    }
}

// 🟢 2. INSERÇÃO ASSÍNCRONA VIA AJAX POST: GRAVAÇÃO E BAIXA DE INVENTÁRIO
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['executar_agendamento_real'])) {
    $id_parceiro_post = intval($_POST['barbearia_id']);
    $id_produto_post  = intval($_POST['servico_id']);
    $cliente_nome     = mysqli_real_escape_string($conexao_link, trim($_POST['cliente_nome']));
    $cliente_tel      = mysqli_real_escape_string($conexao_link, trim($_POST['numero_telefone']));
    $valor_final_venda = floatval($_POST['total_kzs']);
    $canal_pagamento   = mysqli_real_escape_string($conexao_link, trim($_POST['canal_pagamento']));
    $modalidade_log    = mysqli_real_escape_string($conexao_link, trim($_POST['modalidade_logistica']));
    $condicao_caixa    = mysqli_real_escape_string($conexao_link, trim($_POST['condicao_caixa']));

    $status_trabalho = ($condicao_caixa === 'adiantado') ? 'Adiantado PARCIAL' : 'Pago TOTAL';
    $servico_label   = "Serviço ID " . $id_produto_post . " [" . strtoupper($modalidade_log) . " - " . strtoupper($canal_pagamento) . "]";
    
    // Calcula comissão fixa de 10% retida pela holding Aurelius
    $comissao_aurelius = $valor_final_venda * 0.10;
    $valor_liquido     = $valor_final_venda - $comissao_aurelius;

    $data_atual = date('Y-m-d');
    $hora_atual = date('H:i:s');

    // Executa a persistência atómica no MariaDB
    $stmt_pag = $conexao_link->prepare("INSERT INTO `pagamentos` 
        (id_parceiro, tipo_parceiro, cliente, cliente_telefone, profissional, funcionario, data_servico, hora_servico, servico, valor, desconto, valor_liquido, visto_admin, status_atendimento, status_trabalho) 
        VALUES (?, 'loja', ?, ?, ?, ?, ?, ?, ?, ?, 0.00, ?, 0, 'Confirmado', ?)");
    
    $stmt_pag->bind_param("isssssssdds", 
        $id_parceiro_post, $cliente_nome, $cliente_tel, $status_trabalho, 
        $status_trabalho, $data_atual, $hora_atual, $servico_label, 
        $valor_final_venda, $valor_liquido, $status_trabalho
    );
    
    if ($stmt_pag->execute()) {
        // ⚡ REGRA DO -X: Reduz o stock do produto selecionado de forma isolada
        mysqli_query($conexao_link, "UPDATE `produtos_cosmeticos` SET `stock_atual` = `stock_atual` - 1 WHERE `id` = $id_produto_post");
        
        echo json_encode(["status" => "SUCCESS", "mensagem" => "Transação faturada no MariaDB!"]);
        exit();
    } else {
        echo json_encode(["status" => "FAILED"]);
        exit();
    }
}
?>





<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Angelino Comercial — Hub Aeroespacial de Estética</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; font-family: system-ui, -apple-system, sans-serif; }
        body { background: #040814; color: #fff; padding-bottom: 50px; overflow-x: hidden; }

        /* 🧭 NAV BAR HORIZONTAL LÍQUIDA (OURO SOLAR & AURORA) */
        nav {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 20px 45px;
            background: linear-gradient(135deg, rgba(16, 26, 56, 0.85) 0%, rgba(6, 11, 25, 0.95) 100%);
            border-bottom: 2px solid #eab308;
            box-shadow: 0 6px 30px rgba(234, 179, 8, 0.2);
            backdrop-filter: blur(14px);
            position: sticky;
            top: 0;
            z-index: 1000;
        }
        nav .logo-cyber { font-size: 22px; font-weight: 900; text-transform: uppercase; letter-spacing: 1px; color: #fff; }
        nav .logo-cyber span { color: #eab308; text-shadow: 0 0 15px rgba(234, 179, 8, 0.6); }
        
        .menu-horizontal-cyber { display: flex; list-style: none; gap: 20px; align-items: center; }
        .menu-horizontal-cyber li a { color: #94a3b8; text-decoration: none; font-size: 13.5px; font-weight: 800; padding: 10px 22px; border-radius: 40px; transition: all 0.3s cubic-bezier(0.25, 1, 0.5, 1); background: rgba(255,255,255,0.01); border: 1px solid rgba(255,255,255,0.04); text-transform: uppercase; }
        .menu-horizontal-cyber li a:hover { background: rgba(234, 179, 8, 0.2); color: #eab308; box-shadow: 0 0 15px rgba(234, 179, 8, 0.4); transform: translateY(-1px); }

        /* 🚀 PALCO DA CENTRAL DE COMANDO SPA */
        .central-viewport-cyber { max-width: 800px; margin: 40px auto; padding: 0 25px; }
        .card-palco-visao { background: linear-gradient(145deg, #0b1528 0%, #050b18 100%); border: 1px solid rgba(56, 189, 248, 0.15); border-radius: 24px; padding: 30px; box-shadow: 0 20px 50px rgba(0,0,0,0.6), 0 0 40px rgba(0, 210, 255, 0.05); position: relative; }
        
        .fase-view { display: none; animation: auroraFadeIn 0.4s cubic-bezier(0.25, 1, 0.5, 1); }
        .fase-view.active { display: block; }
        @keyframes auroraFadeIn { from { opacity: 0; transform: scale(0.98) translateY(10px); } to { opacity: 1; transform: scale(1) translateY(0); } }

        /* 📋 BOTÕES RADIAIS DO MENU DE ENTRADA */
        .painel-grade-botoes { display: flex; flex-direction: column; gap: 14px; margin-top: 25px; }
        .btn-hub-comando { background: #0c152b; border: 1px solid #1e293b; border-left: 5px solid #38bdf8; color: #cbd5e1; padding: 18px 24px; border-radius: 12px; font-size: 14.5px; font-weight: 700; cursor: pointer; text-align: left; transition: all 0.25s ease; display: flex; justify-content: space-between; align-items: center; }
        .btn-hub-comando:hover { border-color: #eab308; border-left-color: #eab308; color: #eab308; box-shadow: 0 0 20px rgba(234, 179, 8, 0.15); transform: translateX(4px); }

        /* 💈 GRID DE ESTABELECIMENTOS INTEGRADOS */
        .grade-isometrica-lojas { display: grid; grid-template-columns: repeat(auto-fit, minmax(160px, 1fr)); gap: 20px; margin-top: 20px; }
        .card-isometrico { background: #0d162d; border: 1px solid #1e293b; border-radius: 18px; padding: 14px; cursor: pointer; transition: all 0.3s ease; position: relative; text-align: center; }
        .card-isometrico:hover { border-color: #00d2ff; box-shadow: 0 0 25px rgba(0, 210, 255, 0.3); transform: translateY(-3px); }
        .card-isometrico img { width: 100%; height: 120px; object-fit: cover; border-radius: 12px; margin-bottom: 12px; border: 1px solid #1e293b; }
        .card-isometrico strong { font-size: 13.5px; color: #fff; display: block; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
        .card-isometrico p { font-size: 11px; color: #64748b; margin-top: 4px; font-weight: 600; }

        /* 🏷️ SISTEMA DE ABAS E CARDS DE PREÇO PARTILHADOS */
        .btn-retrocesso { background: #1e293b; color: #94a3b8; border: 1px solid #334155; padding: 9px 20px; border-radius: 40px; font-size: 11px; font-weight: 800; cursor: pointer; margin-bottom: 25px; text-transform: uppercase; float: left; transition: 0.2s; }
        .btn-retrocesso:hover { background: #334155; color: #fff; }
        
        .capsula-filtro-horizontal { display: flex; gap: 10px; overflow-x: auto; padding-bottom: 12px; margin-bottom: 20px; }
        .aba-capsula { background: #0d162d; border: 1px solid #1e293b; color: #94a3b8; padding: 10px 22px; border-radius: 40px; font-size: 13px; font-weight: bold; cursor: pointer; white-space: nowrap; transition: 0.2s; }
        .aba-capsula.active { background: #eab308; color: #040814; border-color: #eab308; box-shadow: 0 0 15px rgba(234, 179, 8, 0.4); }

        .lista-cartoes-servicos { display: flex; flex-direction: column; gap: 12px; margin-bottom: 25px; }
        .cartao-linha-servico { background: #0c142a; border: 1px solid #1e293b; padding: 16px; border-radius: 14px; display: flex; justify-content: space-between; align-items: center; text-align: left; cursor: pointer; transition: 0.2s; }
        .cartao-linha-servico:hover { border-color: #00d2ff; background: #111e38; }
        .cartao-linha-servico.selected { border-color: #22c55e; background: rgba(34, 197, 94, 0.04); box-shadow: 0 0 15px rgba(34,197,94,0.1); }

        /* 💵 CONSOLA DE TESOURARIA INTEGRADA MULTI-GATEWAY */
        .painel-checkout-futuro { background: linear-gradient(135deg, #101c38 0%, #060b19 100%); border: 2px solid #eab308; border-radius: 16px; padding: 20px; text-align: left; box-shadow: 0 10px 30px rgba(0,0,0,0.5); }
        .grupo-campo-cyber { display: flex; flex-direction: column; gap: 6px; margin-bottom: 15px; }
        .grupo-campo-cyber label { font-size: 12px; color: #94a3b8; font-weight: bold; text-transform: uppercase; letter-spacing: 0.5px; }
        .grupo-campo-cyber input, .grupo-campo-cyber select { padding: 12px 16px; background: #040814; border: 1px solid #1e293b; border-radius: 8px; color: #fff; font-size: 14px; outline: none; transition: 0.3s; color-scheme: dark; }
        .grupo-campo-cyber input:focus { border-color: #00d2ff; box-shadow: 0 0 8px rgba(0,210,255,0.25); }

        .alternador-canais-grid { display: grid; grid-template-columns: repeat(3, 1fr); gap: 10px; margin-bottom: 20px; }
        .btn-canal-digital { padding: 12px; border: 1px solid #1e293b; color: #fff; font-weight: bold; border-radius: 8px; cursor: pointer; font-size: 11px; text-transform: uppercase; transition: 0.2s; background: rgba(255,255,255,0.01); }
        .btn-canal-digital.active-channel { background: #eab308 !important; color: #040814 !important; border-color: #eab308 !important; box-shadow: 0 0 15px rgba(234, 179, 8, 0.4); }

        .btn-mestre-submissao { width: 100%; background: #22c55e; color: #fff; border: none; padding: 15px; border-radius: 8px; font-size: 14px; font-weight: 800; cursor: pointer; text-transform: uppercase; letter-spacing: 1px; box-shadow: 0 4px 15px rgba(34,197,94,0.3); transition: 0.2s; margin-top: 15px; }
        .btn-mestre-submissao:hover { background: #16a34a; transform: translateY(-1px); }

        /* 🖨️ RECIBO TÉRMICO DE SUCESSO COESIVO */
        .recibo-termico-aurelius { background: #fff; color: #000; padding: 30px; border-radius: 14px; max-width: 440px; margin: 20px auto; font-family: monospace; border-top: 8px solid #22c55e; box-shadow: 0 20px 40px rgba(0,0,0,0.5); text-align: left; }
    </style>
</head>
<body>

<nav>
    <h1 class="logo-cyber">GRUPO<span>AURELIUS</span><p style="font-size:11px; " > Salão de Beleza Look Novo </p></h1>
    <ul class="menu-horizontal-cyber"> <br>
    
        <li><a href="LookNovo.php">💈 Home</a></li>
        <li><a href="LookNovo.php">Photos</a></li>
        <li><a href="#" onclick="forçarTelaComando(1)">✂️ Serviços</a></li>
        <li><a href="Video.php">🎬 Reels</a></li>
        <li><a href="Principal.php" style="border-color:#ef4444; color:#ef4444;">✕ Sair</a></li>
    </ul>
</nav>

<div class="central-viewport-cyber">
    <main class="card-palco-visao">

        <!-- TELA 1: DIÁLOGO RADIAL DO GESTOR -->
        <div class="fase-view active" id="fase-1">
            <div style="text-align: left; margin-bottom: 25px; border-left: 4px solid #eab308; padding-left: 12px;">
                <span style="color: #eab308; font-size: 11px; font-weight: bold; text-transform: uppercase; letter-spacing: 1px;">● MARIANO COMERCIAL LTD </span>
                <h2 style="color:#fff; font-size: 22px; font-weight: bold; margin-top: 2px;">✨ BEM-VINDO AO SALÃO DE BELEZA LOOK NOVO ✨</h2>
                <p style="font-size: 14px; color: #93c5fd; margin-top: 6px;">Transforme o seu visual com os melhores profissionais do Namibe</p>
            </div>
            
         

            <div class="painel-grade-botoes">
                <button class="btn-hub-comando" onclick="forçarTelaComando(2)"><span>📋 Visualizar Catálogo de Serviços</span> <span>➔</span></button>






                <button class="btn-hub-comando" onclick="forçarTelaComando(2)"><span>💈 Marcar Atendimento Presencial</span> <span>➔</span></button>
                
                <button class="btn-hub-comando" onclick="forçarTelaComando(2)"><span>💅 Menu de Tratamentos &amp; Estética</span> <span>➔</span></button>

                <button class="btn-hub-comando" onclick="forçarTelaComando(2)"><span>💵 Consultar Contas, Preços &amp; Planos</span> <span>➔</span></button>
            </div>
        </div>

        <!-- TELA 2: LISTAGEM ISOMÉTRICA DE ESTABELECIMENTOS -->
        <div class="fase-view" id="fase-2">
            <button class="btn-retrocesso" onclick="voltarFase(1)">← Voltar ao Hub</button>
            <h2 class="titulo-sessao" style="font-size:16px; color:#fff; text-align:left; clear:both; padding-top:10px; margin-bottom:15px; text-transform:uppercase;">Selecione a Filial de Destino</h2>
            <div class="grade-isometrica-lojas" id="container-filiais"></div>
        </div>

        <!-- =========================================================================
             🎯 TELA 3: CONFIGURAR PAUTA DE SERVIÇOS (ABAS & CÁLCULO DE FRETE)
             ========================================================================= -->
        <div class="fase-view" id="fase-3">
            <button class="btn-retrocesso" onclick="voltarFase(2)">← Mudar Unidade</button>
            <h2 class="titulo-sessao" style="font-size:16px; color:#fff; text-align:left; clear:both; padding-top:10px; margin-bottom:15px; text-transform:uppercase;">Montar Pauta de Serviços</h2>
            






            
            <div class="selecao-top-box" style="background: rgba(0, 210, 255, 0.05); border: 1px dashed #00d2ff; padding: 12px 16px; border-radius: 8px; text-align: left; margin-bottom: 15px;">
                <label style="font-size: 11px; color: #38bdf8; text-transform: uppercase; font-weight: bold;">Estabelecimento Alocado:</label>
                <div class="select-fake" id="lbl-filial-alocada" style="font-size: 14px; font-weight: bold; color: #fff; margin-top: 4px;">--</div>
            </div>

            <!-- Abas Rápidas e Lista de Serviços -->
            <div class="capsula-filtro-horizontal" id="container-abas-capsulas"></div>
            <div class="lista-cartoes-servicos" id="container-lista-servicos"></div>

            <!-- Consola de Pagamento e Faturamento com Frete/Adiantamento -->
            <div class="painel-checkout-futuro">
                <div class="alternador-canais-grid">
                    <button type="button" id="chan-unitel" class="btn-canal-digital active-channel" onclick="mudarCanalLiquidação('unitel_money')">Unitel Money</button>
                    <button type="button" id="chan-express" class="btn-canal-digital" onclick="mudarCanalLiquidação('mcx_xpress')">MCX Express</button>
                    <button type="button" id="chan-ref" class="btn-canal-digital" onclick="mudarCanalLiquidação('referencia')">Ref. ATM</button>
                </div>

                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px;">
                    <div class="grupo-campo-cyber">
                        <label>Nome do Cliente:</label>
                        <input type="text" id="txt-nome-cliente" value="Cliente Visitante" required>
                    </div>
                    <div class="grupo-campo-cyber">
                        <label>Telemóvel Titular:</label>
                        <input type="tel" id="txt-telefone-cliente" placeholder="Ex: 923000000" maxlength="9">
                    </div>
                </div>

                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px; margin-top: 10px;">
                    <div class="grupo-campo-cyber">
                        <label>Modalidade Logística:</label>
                        <select id="sel-frete" onchange="computarPrecoFinal()">
                            <option value="buscar">Vou buscar ao vosso encontro (Preço Base)</option>
                            <option value="levar">Prefiro que levem / Frete (+1.500 Kz)</option>
                        </select>
                    </div>
                    <div class="grupo-campo-cyber">
                        <label>Condição de Caixa:</label>
                        <select id="sel-condicao-pagamento" onchange="computarPrecoFinal()">
                            <option value="total">Liquidação Integral (100%)</option>
                            <option value="adiantado">Pagar Adiantado (Sinal 50%)</option>
                        </select>
                    </div>
                </div>

                <!-- Campo de PIN Exclusivo Unitel Money -->
                <div class="grupo-campo-cyber" id="wrapper-pin-autenticacao" style="margin-top: 15px;">
                    <label style="color:#22c55e;">🔑 PIN Único de Autenticação da Carteira:</label>
                    <input type="password" id="txt-pin-seguranca" placeholder="Insira a sua senha ou token alargado" maxlength="12" style="letter-spacing:2px;">
                </div>

                <div style="display:flex; justify-content:space-between; align-items:center; border-top:1px solid #1e293b; padding-top:15px; margin-top:15px;">
                    <span style="font-size:13px; color:#94a3b8;">Total Geral a Descontar:</span>
                    <strong style="font-size:22px; color:#eab308;" id="txt-preco-visor">0.00 Kz</strong>
                </div>

                <button type="button" class="btn-mestre-submissao" onclick="processarSubmissaoFinal()">Submeter Agendamento ✓</button>
            </div>
        </div>

        <!-- =========================================================================
             🖨️ TELA 4: EMISSÃO DO RECIBO TÉRMICO DE SUCESSO (HTML PURO)
             ========================================================================= -->
        <div class="fase-view" id="fase-4">
            <div class="recibo-termico-aurelius">
                <div class="icone-sucesso">✓</div>
                <h3 style="text-align:center; font-size:15px; margin-bottom:15px; font-weight:bold; letter-spacing:0.5px;">ECOSSISTEMA GRUPO AURÉLIUS</h3>
                <p style="margin-bottom:6px;"><b>Filial Alocada:</b> <span id="recibo-salao">--</span></p>
                <p style="margin-bottom:6px;"><b>Item/Serviço:</b> <span id="recibo-servico">--</span></p>
                <p style="margin-bottom:6px;"><b>Cliente:</b> <span id="recibo-cliente">--</span></p>
                <p style="margin-bottom:6px;"><b>Método Digital:</b> <span id="recibo-canal">--</span></p>
                <p style="margin-bottom:15px;"><b>Data Fatura:</b> <?= date('d/m/Y H:i') ?></p>
                
                <div style="border-top:1px dashed #000; padding-top:12px; display:flex; justify-content:space-between; font-weight:bold; font-size:14px; color:#16a34a;">
                    <span>VOLUME LIQUIDADO:</span>
                    <span id="recibo-valor">0.00 Kz</span>
                </div>
                <button type="button" class="btn-mestre-submissao" style="background:#000; margin-top:25px; font-size:12px;" onclick="forçarTelaComando(1)">Concluir e Voltar</button>
            </div>
        </div>

    </main>
</div>




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
             <p>Introduza um terminal Unitel elegível (prefixos 925/935). O gateway calcula e aplica <b>20% de Desconto VIP</b> automáticos no caixa.</p>
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







<footer style="background-color: #0b111e; border-top: 2px solid #ca8a04; padding: 50px 20px 30px 20px; color: #f8fafc; margin-top: 60px; font-family: 'Segoe UI', Arial, sans-serif; text-align: left; box-shadow: 0 -5px 25px rgba(0,0,0,0.5);">
    <div style="max-width: 1100px; margin: 0 auto; display: grid; grid-template-columns: repeat(auto-fit, minmax(240px, 1fr)); gap: 40px; padding-bottom: 30px; border-bottom: 1px solid #1e293b;">
        
        <!-- Coluna 1: Identidade da Marca -->
        <div>
            <h2 style="font-size: 20px; font-weight: 800; color: #ef4444; margin: 0 0 12px 0; text-transform: uppercase; letter-spacing: 0.5px;">
                🎌 GRUPO <span style="color: #ffffff;">AURÉLIUS</span>
            </h2>
            <p style="font-size: 13px; color: #94a3b8; line-height: 1.6; margin: 0 0 15px 0; text-align: justify;">
                A maior infraestrutura e ecossistema multisserviços de estética, barbearia digital e e-commerce de cosméticos premium da província do Huambo.
            </p>
            <span style="background: rgba(202, 138, 4, 0.1); color: #ca8a04; border: 1px solid rgba(202, 138, 4, 0.3); padding: 4px 10px; border-radius: 4px; font-size: 11px; font-weight: bold; text-transform: uppercase; letter-spacing: 0.5px;">
                SaaS Platform v2.1
            </span>
        </div>

        <!-- Coluna 2: Links de Acesso Rápido -->
        <div>
            <h4 style="font-size: 13px; font-weight: bold; color: #ca8a04; text-transform: uppercase; margin-bottom: 15px; letter-spacing: 0.5px;">Navegação Segura</h4>
            <ul style="list-style: none; padding: 0; margin: 0; font-size: 13px; display: flex; flex-direction: column; gap: 10px;">
                <li><a href="Principal.php" style="color: #cbd5e1; text-decoration: none; transition: 0.2s;">🏪 Portal de Barbearias</a></li>
                <li><a href="BrancaCadastar.php" style="color: #cbd5e1; text-decoration: none; transition: 0.2s;">👨🏼‍🦰 Criar Conta de Cliente</a></li>
                <li><a href="BrancaCadastar.php" style="color: #cbd5e1; text-decoration: none; transition: 0.2s;">🔐 Área Administrativa </a></li>
                <li><a href="unitel.php" style="color: #cbd5e1; text-decoration: none; transition: 0.2s;">📱 Gateway de Pagamento Móvel</a></li>
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


<!-- =================================================================
     📋 BANNER DE CONSENTIMENTO E INTELIGÊNCIA DE NEGÓCIO (BI) REAL
     ================================================================= -->
<div class="banner-consentimento" id="cookieBanner" style="position: fixed; bottom: 0; left: 0; right: 0; background-color: rgba(15, 23, 42, 0.98); border-top: 2px solid #ca8a04; padding: 15px 25px; display: none; justify-content: space-between; align-items: center; font-size: 12px; color: #94a3b8; z-index: 9999; box-shadow: 0 -5px 20px rgba(0,0,0,0.5); font-family: sans-serif;">
    <div style="padding-right: 20px; line-height: 1.5; text-align: justify;">
        <strong style="color: #ca8a04;">Controlo de Auditoria PWA:</strong> O Grupo Aurélius recolhe métricas estatísticas de navegação anonimizadas, escolhas de serviços estéticos e volumetria financeira para otimização da agenda diária, cálculo do ranking mensal de produtividade e monetização regionalizada no Huambo.
    </div>
    <div style="display: flex; gap: 10px; align-items: center;">
        <button class="btn-aceitar" onclick="processarConsentimentoRealBI()" style="background: linear-gradient(135deg, #22c55e, #16a34a); color: white; border: none; padding: 10px 20px; font-weight: bold; border-radius: 6px; cursor: pointer; white-space: nowrap; text-transform: uppercase; font-size: 11px; letter-spacing: 0.5px; box-shadow: 0 4px 6px rgba(0,0,0,0.2); transition: 0.2s;">
            Aceitar e Permitir Rastreamento
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






<!-- =================================================================
     📋 BANNER DE CONSENTIMENTO E INTELIGÊNCIA DE NEGÓCIO (BI) REAL
     ================================================================= -->
     <div class="banner-consentimento" id="cookieBanner" style="position: fixed; bottom: 0; left: 0; right: 0; background-color: rgba(15, 23, 42, 0.98); border-top: 2px solid #ca8a04; padding: 15px 25px; display: none; justify-content: space-between; align-items: center; font-size: 12px; color: #94a3b8; z-index: 9999; box-shadow: 0 -5px 20px rgba(0,0,0,0.5); font-family: sans-serif;">
    <div style="padding-right: 20px; line-height: 1.5; text-align: justify;">
        <strong style="color: #ca8a04;">Controlo de Auditoria PWA:</strong> O Grupo Aurélius recolhe métricas estatísticas de navegação anonimizadas, escolhas de serviços estéticos e volumetria financeira para otimização da agenda diária, cálculo do ranking mensal de produtividade e monetização regionalizada no Huambo.
    </div>
    <div style="display: flex; gap: 10px; align-items: center;">
        <button class="btn-aceitar" onclick="processarConsentimentoRealBI()" style="background: linear-gradient(135deg, #22c55e, #16a34a); color: white; border: none; padding: 10px 20px; font-weight: bold; border-radius: 6px; cursor: pointer; white-space: nowrap; text-transform: uppercase; font-size: 11px; letter-spacing: 0.5px; box-shadow: 0 4px 6px rgba(0,0,0,0.2); transition: 0.2s;">
            Aceitar e Permitir Rastreamento
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
















<!-- =========================================================================
     ⚙️ MOTOR DE INTELIGÊNCIA JAVASCRIPT CORE (PROCESSAMENTO SEM REFRESH)
     ========================================================================= -->
<script>
// Dados simulados em memória interligados que espelham a infraestrutura geral do SaaS
const barbeariasMock = [
    { id: 240, nome: 'Angelino Comercial', localizacao: 'Kapango', img: 'download (5).png' },
    { id: 20, nome: 'Barbearia Branca', localizacao: 'Aviação', img: 'download (5).png' },
    { id: 241, nome: 'Grupo Aurélius Holding', localizacao: 'Huambo Centro', img: 'download (5).png' }
];

const servicosMock = [
    { id: 1, categoria: 'Cortes', nome: 'Corte Clássico Francês', preco: 2500.00 },
    { id: 2, categoria: 'Cortes', nome: 'Corte Degradê Moderno', preco: 3500.00 },
    { id: 3, categoria: 'Barba', nome: 'Barba Simples Navalha', preco: 1500.00 },
    { id: 4, categoria: 'Barba', nome: 'Barba Toalha Quente', preco: 2500.00 },
    { id: 5, categoria: 'Combos', nome: 'Cabelo + Barba Imperial', preco: 5000.00 },
    { id: 6, categoria: 'Estética', nome: 'Pedicure Avançado', preco: 4000.00 }
];

let idFilialAtiva = null;
let nomeFilialAtiva = "";
let servicoAtivoObjeto = null;
let categoriaFiltroAtiva = "Cortes";
let canalSelecionadoAtivo = "unitel_money";

document.addEventListener("DOMContentLoaded", () => {
    renderizarFiliaisIsométricas();
});



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













// Navegação entre ecrãs (Fases) do SPA
function forçarTelaComando(numeroFase) {
    document.querySelectorAll('.fase-view').forEach(f => f.classList.remove('active'));
    const alvo = document.getElementById(`fase-${numeroFase}`);
    if (alvo) alvo.classList.add('active');
}

function voltarFase(numeroFase) {
    forçarTelaComando(numeroFase);
}

// Renderização dos cards dos estabelecimentos
function renderizarFiliaisIsométricas() {
    const container = document.getElementById("container-filiais");
    if (!container) return;
    container.innerHTML = barbeariasMock.map(filial => `
        <div class="card-isometrico" onclick="alocarUnidadeFaturamento(${filial.id}, '${filial.nome}')">
            <img src="${filial.img}" alt="${filial.nome}" onerror="this.src='imagens/default_cosmetico.jpg';">
            <strong>${filial.nome}</strong>
            <p>📍 ${filial.localizacao}</p>
        </div>
    `).join('');
}

function alocarUnidadeFaturamento(id, nome) {
    idFilialAtiva = id;
    nomeFilialAtiva = nome;
    
    const lbl = document.getElementById("lbl-filial-alocada");
    if(lbl) lbl.innerText = nome;
    
    renderizarAbasSuperiores();
    renderizarCartoesServicos();
    forçarTelaComando(3);
}

function renderizarAbasSuperiores() {
    const container = document.getElementById("container-abas-capsulas");
    if (!container) return;
    
    const categoriasUnicas = [...new Set(servicosMock.map(s => s.categoria))];
    container.innerHTML = categoriasUnicas.map(cat => `
        <button type="button" class="aba-capsula ${cat === categoriaFiltroAtiva ? 'active' : ''}" onclick="comutarAbaFiltro('${cat}')">
            ${cat}
        </button>
    `).join('');
}

function comutarAbaFiltro(novaCategoria) {
    categoriaFiltroAtiva = novaCategoria;
    renderizarAbasSuperiores();
    renderizarCartoesServicos();
}

function renderizarCartoesServicos() {
    const container = document.getElementById("container-lista-servicos");
    if (!container) return;
    
    const filtrados = servicosMock.filter(s => s.categoria === categoriaFiltroAtiva);
    container.innerHTML = filtrados.map(s => `
    <div class="cartao-linha-servico ${servicoAtivoObjeto && servicoAtivoObjeto.id === s.id ? 'selected' : ''}" onclick="capturarServicoPreco(${s.id})">
            <div>
                <strong style="color:#fff; font-size:13.5px; display:block;">${s.nome}</strong>
                <span style="color:#64748b; font-size:11px;">Mapeamento: ${s.categoria}</span>
            </div>
            <strong style="color:#eab308; font-size:14.5px;">${s.preco.toLocaleString('pt-PT', {minimumFractionDigits:2})} Kz</strong>
        </div>
    `).join('');
}

function capturarServicoPreco(idS) {
    const obj = servicosMock.find(s => s.id === idS);
    if (!obj) return;
    
    servicoAtivoObjeto = obj;
    computarPrecoFinal();
    renderizarCartoesServicos();
}

function mudarCanalLiquidação(canal) {
    canalSelecionadoAtivo = canal;
    document.querySelectorAll('.btn-canal-digital').forEach(b => b.classList.remove('active-channel'));
    
    const wPin = document.getElementById('wrapper-pin-autenticacao');
    
    if (canal === 'unitel_money') {
        document.getElementById('chan-unitel').classList.add('active-channel');
        if(wPin) wPin.style.display = 'block';
    } else if (canal === 'mcx_xpress') {
        document.getElementById('chan-express').classList.add('active-channel');
        if(wPin) wPin.style.display = 'none';
    } else {
        document.getElementById('chan-ref').classList.add('active-channel');
        if(wPin) wPin.style.display = 'none';
    }
}

// 🟢 INTELIGÊNCIA OPERACIONAL: RECALCULA FRETE E ADIANTAMENTO EM TEMPO REAL
function computarPrecoFinal() {
    if (!servicoAtivoObjeto) return;
    
    const freteOpcao = document.getElementById('sel-frete').value;
    const pagamentoOpcao = document.getElementById('sel-condicao-pagamento').value;
    
    let subtotal = parseFloat(servicoAtivoObjeto.preco);
    let adicionalFrete = (freteOpcao === 'levar') ? 1500.00 : 0.00;
    
    let totalGeral = subtotal + adicionalFrete;
    
    if (pagamentoOpcao === 'adiantado') {
        totalGeral = totalGeral * 0.50;
    }
    
    const visor = document.getElementById('txt-preco-visor');
    if (visor) {
        visor.innerText = totalGeral.toLocaleString('pt-PT', {minimumFractionDigits:2}) + " Kz";
    }
}

function processarSubmissaoFinal() {
    const nomeC = document.getElementById('txt-nome-cliente').value.trim();
    const telC = document.getElementById('txt-telefone-cliente').value.trim();
    const pinC = document.getElementById('txt-pin-seguranca').value.trim();
    
    if (!servicoAtivoObjeto) {
        alert("❌ Selecione um serviço técnico no catálogo!"); return;
    }
    if (telC.length < 9) {
        alert("❌ Introduza um telemóvel corporativo válido!"); return;
    }
    if (canalSelecionadoAtivo === 'unitel_money' && pinC.length < 4) {
        alert("❌ Autenticação Recusada: Digite o PIN da sua carteira digital para faturar!"); return;
    }

    const freteOpcao = document.getElementById('sel-frete').value;
    const pagamentoOpcao = document.getElementById('sel-condicao-pagamento').value;
    let finalValor = parseFloat(servicoAtivoObjeto.preco) + ((freteOpcao === 'levar') ? 1500.00 : 0.00);
    if (pagamentoOpcao === 'adiantado') finalValor = finalValor * 0.50;

    // Alimenta o Recibo de Sucesso Térmico
    document.getElementById('recibo-salao').innerText = nomeFilialAtiva;
    document.getElementById('recibo-servico').innerText = servicoAtivoObjeto.nome;
    document.getElementById('recibo-cliente').innerText = nomeC;
    document.getElementById('recibo-canal').innerText = canalSelecionadoAtivo.toUpperCase().replace('_', ' ');
    document.getElementById('recibo-valor').innerText = finalValor.toLocaleString('pt-PT', {minimumFractionDigits:2}) + " Kz";

    forçarTelaComando(4);
}
</script>
</body>
</html>