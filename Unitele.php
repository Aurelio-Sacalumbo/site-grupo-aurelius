<?php
// =========================================================================
// 💳 GATEWAY DE RECONHECIMENTO VIP, LOGÍSTICA E PRESTÍGIO — UNITELE.PHP
// =========================================================================
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
date_default_timezone_set('Africa/Luanda');

include_once("config/Banco.php"); 

if (!isset($mysqli) && isset($pdo)) {
    $mysqli = new mysqli("127.0.0.1", "root", "", "aurelius_salao");
    $mysqli->set_charset("utf8mb4");
}

// 🟢 1. INICIALIZAÇÃO DE SEGURANÇA E CAPTURA DE ROTAS MULTI-TENANT
$id_produto_get     = isset($_GET['id_produto_comprado']) ? intval($_GET['id_produto_comprado']) : 26; 
$quantidade_inicial = isset($_GET['quantidade']) ? intval($_GET['quantidade']) : 1;
$gateway_atual      = isset($_GET['gateway']) ? htmlspecialchars($_GET['gateway']) : 'unitel_money';

$nome_produto       = "Bolinhos";
$preco_tabela       = 2350.00; 
$stock_maximo       = 10;
$id_parceiro_real   = 20;

// Consulta do produto na base de dados MariaDB
$query_db_prod = $mysqli->query("SELECT * FROM `produtos_cosmeticos` WHERE `id` = '$id_produto_get' LIMIT 1");
if ($query_db_prod && $query_db_prod->num_rows > 0) {
    $dados_prod = $query_db_prod->fetch_assoc();
    $nome_produto = $dados_prod['nome_produto'];
    $preco_tabela = floatval($dados_prod['preco']);
    $stock_maximo = intval($dados_prod['stock_atual']);
    $id_parceiro_real = intval($dados_prod['empresa_id']);
}

// Captura do nome do salão/loja dono
$nome_parceiro_real = "Barbearia Branca";
$tipo_parceiro_final = "loja";
$busca_loja = $mysqli->query("SELECT nome_loja, endereco_armazem FROM `lojas` WHERE id = '$id_parceiro_real' LIMIT 1");
if ($busca_loja && $busca_loja->num_rows > 0) {
    $nome_parceiro_real = $busca_loja->fetch_assoc()['nome_loja'];
}

// Auditoria de Perfil CRM e Controlo de PIN Existente
$status_crm_cliente = "Cliente Novo";
$cor_crm = "#94a3b8";
$telefone_busca = isset($_POST['cliente_telefone']) ? trim($_POST['cliente_telefone']) : (isset($_GET['tel_ref']) ? trim($_GET['tel_ref']) : '');
$pin_existente_db = "";
$acao_requerida_pin = "criar"; // 'criar' ou 'validar'

if (!empty($telefone_busca)) {
    // 🔍 Procura o cliente e o seu PIN único na tabela clientes
    $check_cliente = $mysqli->query("SELECT * FROM `clientes` WHERE `telefone` = '$telefone_busca' LIMIT 1");
    if ($check_cliente && $check_cliente->num_rows > 0) {
        $dados_c = $check_cliente->fetch_assoc();
        $pin_existente_db = trim($dados_c['senha'] ?? $dados_c['pin'] ?? '');
        $status_crm_cliente = ($dados_c['nivel'] === 'VIP') ? "🔥 CLIENTE ATIVO VIP" : "Cliente Regular Registado";
        $cor_crm = ($dados_c['nivel'] === 'VIP') ? "#22c55e" : "#38bdf8";
        
        if (!empty($pin_existente_db)) {
            $acao_requerida_pin = "validar";
        }
    }
}

// 🟢 2. GATILHO REATIVO DE REDEFINIÇÃO DE PIN (FORÇADO PELO GESTOR)
if (isset($_GET['redefinir_pin_urgente']) && !empty($_GET['tel_ref'])) {
    $tel_reset = $mysqli->real_escape_string($_GET['tel_ref']);
    $mysqli->query("UPDATE `clientes` SET `senha` = NULL WHERE `telefone` = '$tel_reset'");
    echo "<script>alert('🔒 PIN ANULADO! O cliente deve agora criar obrigatoriamente uma nova credencial no visor.'); window.location.href='Unitele.php?id_produto_comprado=$id_produto_get&gateway=$gateway_atual&tel_ref=$tel_reset';</script>";
    exit();
}

// 🟢 3. PROCESSAMENTO DE COMPRA E VALIDAÇÃO EXCLUSIVA DE PIN ÚNICO
$exibir_fatura_final = false;
$fatura_dados = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['executar_venda_final'])) {
    $cliente_nome     = trim($_POST['nome_cliente']);
    $cliente_telefone = trim($_POST['cliente_telefone']); 
    
    // 🟢 RESOLUÇÃO DO WARNING DA LINHA 81: Varre de forma inteligente todos os nomes possíveis que o HTML pode enviar
    $quantidade_comprada = 1;
    if (isset($_POST['quantidade_selecionada'])) {
        $quantidade_comprada = intval($_POST['quantidade_selecionada']);
    } elseif (isset($_POST['quantidade_pretendida'])) {
        $quantidade_comprada = intval($_POST['quantidade_pretendida']);
    } elseif (isset($_POST['quantidade'])) {
        $quantidade_comprada = intval($_POST['quantidade']);
    }
    
    // Proteção estrita de inventário
    if ($quantidade_comprada <= 0) { $quantidade_comprada = 1; }

    $modalidade_entrega = trim($_POST['modalidade_entrega'] ?? 'buscar');
    $tipo_pagamento     = trim($_POST['tipo_pagamento'] ?? 'total');
    $canal_pagamento    = trim($_POST['canal_pagamento'] ?? 'unitel_money');
    $pin_digitado       = trim($_POST['pin_unitel_confirmacao'] ?? '');

    $valor_bruto = $preco_tabela * $quantidade_comprada;
    $taxa_frete = ($modalidade_entrega === 'levar') ? 1500.00 : 0.00;
    $valor_com_frete = $valor_bruto + $taxa_frete;

    $is_vip = (str_starts_with($cliente_telefone, '925') || str_starts_with($cliente_telefone, '935'));
    $desconto_real = $is_vip ? ($valor_bruto * 0.20) : 0.00;
    $valor_final_venda = $valor_com_frete - $desconto_real;

    if ($canal_pagamento === 'unitel_money') {
        // 🔒 CONTROLO ANTIFRAUDE DE PROPRIEDADE DO PIN
        if ($acao_requerida_pin === 'validar') {
            // Se o PIN não coincidir, barra com o aviso e oferece o gatilho reativo calibrado
            if ($pin_digitado !== $pin_existente_db) {
                die("<div style='background:#7f1d1d; color:#fff; padding:30px; font-family:sans-serif; text-align:center; border-radius:12px; margin:50px auto; max-width:520px; box-shadow:0 10px 25px rgba(0,0,0,0.5); border:1px solid #ef4444;'>
                        <span style='font-size:40px; display:block; margin-bottom:10px;'>🚨</span>
                        <b>Erro de Autenticação Unitel Money:</b> O PIN digitado não confere com o código único registado para este número.<br><br>
                        <p style='font-size:13px; color:#cbd5e1;'>Se o cliente esqueceu a senha, o gestor pode forçar a redefinição imediata abaixo.</p>
                        <a href='Unitele.php?redefinir_pin_urgente=1&tel_ref=$cliente_telefone&id_produto_comprado=$id_produto_get' style='background:#eab308; color:#000; padding:12px 24px; text-decoration:none; font-weight:bold; border-radius:30px; display:inline-block; margin-top:15px; text-transform:uppercase; font-size:12px; letter-spacing:0.5px;'>Obrigar Redefinição de Senha</a>
                     </div>");
            }
        } else {
            // Se for cliente novo ou sem PIN, cria e salva o novo PIN único no banco de dados
            if (strlen($pin_digitado) < 4) {
                die("<script>alert('❌ Erro: O novo PIN deve conter pelo menos 4 caracteres.'); window.history.back();</script>");
            }
             $check_ex = $mysqli->query("SELECT id FROM `clientes` WHERE `telefone` = '$cliente_telefone' LIMIT 1");
             if ($check_ex && $check_ex->num_rows > 0) {
                 $mysqli->query("UPDATE `clientes` SET `senha` = '$pin_digitado' WHERE `telefone` = '$cliente_telefone'");
             } else {
                 $mysqli->query("INSERT INTO `clientes` (`nome`, `telefone`, `senha`, `nivel`) VALUES ('$cliente_nome', '$cliente_telefone', '$pin_digitado', 'Regular')");
             }
        }
    }
    $status_trabalho = ($tipo_pagamento === 'adiantado') ? 'Adiantado PARCIAL' : 'Pago TOTAL';
    $comissao_aurelius = $valor_final_venda * 0.10;
    $valor_liquido_parceiro = $valor_final_venda - $comissao_aurelius;

    // 📊 Validação segura baseada no stock máximo atualizado do banco
    if ($stock_maximo >= $quantidade_comprada) {
        $stmt_pag = $mysqli->prepare("INSERT INTO `pagamentos` (id_parceiro, tipo_parceiro, cliente, cliente_telefone, profissional, funcionario, data_servico, hora_servico, servico, valor, desconto, valor_liquido, visto_admin, status_atendimento, status_trabalho) VALUES (?, ?, ?, ?, ?, ?, NOW(), NOW(), ?, ?, ?, ?, 0, 'Confirmado', ?)");
        $serv_label = $nome_produto . " [" . strtoupper($modalidade_entrega) . "]";
        $stmt_pag->bind_param("issssssddds", $id_parceiro_real, $tipo_parceiro_final, $cliente_nome, $cliente_telefone, $status_trabalho, $status_trabalho, $serv_label, $valor_final_venda, $desconto_real, $valor_liquido_parceiro, $status_trabalho);
        $stmt_pag->execute();
        $stmt_pag->close();

        // 🟢 CORREÇÃO DA REGRA DO -X: 
        // 1. Atualiza a coluna real 'stock' E 'stock_atual' ao mesmo tempo.
        // 2. A trava 'WHERE id = ... AND stock >= ...' impede que o stock caia abaixo de 0.
        $stmt_baixa_real = $mysqli->prepare("UPDATE `produtos_cosmeticos` 
                                             SET `stock` = `stock` - ?, `stock_atual` = `stock_atual` - ? 
                                             WHERE `id` = ? AND `stock` >= ?");
        $stmt_baixa_real->bind_param("iiii", $quantidade_comprada, $quantidade_comprada, $id_produto_get, $quantidade_comprada);
        $stmt_baixa_real->execute();
        $stmt_baixa_real->close();

        // Prepara os dados para imprimir a fatura final no ecrã
        $exibir_fatura_final = true;
        $fatura_dados = [
            "num_fatura" => "FT-" . rand(10000, 99999),
            "salao" => $nome_parceiro_real,
            "cliente" => $cliente_nome,
            "telefone" => $cliente_telefone,
            "produto" => $nome_produto,
            "qtd" => $quantidade_comprada,
            "bruto" => $valor_bruto,
            "frete" => $taxa_frete,
            "desconto" => $desconto_real,
            "total" => $valor_final_venda,
            "pago_agora" => ($tipo_pagamento === 'adiantado') ? ($valor_final_venda * 0.50) : $valor_final_venda,
            "status" => $status_trabalho,
            "canal" => strtoupper(str_replace('_', ' ', $canal_pagamento))
        ];
    } else {
        die("<script>alert('🚨 Operação Cancelada: Quantidade solicitada superior ao stock disponível no armazém!'); window.history.back();</script>");
    }
}

$label_gateway = ($gateway_atual === 'mcx_xpress') ? 'Multicaixa Express' : 'Unitel Money';
$cor_tema      = ($gateway_atual === 'mcx_xpress') ? '#0066cc' : '#ff6600';
?><!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8">
    <title>Checkout Corporativo Aurélius</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        
        body { 
            background: #070b12; 
            color: #fff; 
            font-family: system-ui, -apple-system, sans-serif; 
            padding: 12px 10px; /* Reduzido para dar mais espaço útil no ecrã do telemóvel */
            min-height: 100vh;
        }
        
        /* 📱 CONTAINER DE CADASTRO/CHECKOUT RESPONSIVO */
        .seccao-cadastro { 
            background: #111827; 
            border: 2px solid <?= $cor_tema ?>; 
            border-radius: 16px; 
            width: 100%;
            max-width: 550px; 
            margin: 15px auto; /* Reduzida a margem para não flutuar longe do topo */
            padding: 20px 15px; /* Espaçamento interno otimizado para mobile */
            box-shadow: 0 15px 35px rgba(0,0,0,0.6); 
        }
        
        .campo-grupo { 
            display: flex; 
            flex-direction: column; 
            gap: 6px; 
            margin-bottom: 15px; 
            text-align: left; 
        }
        
        .campo-grupo label { 
            font-size: 12.5px; 
            color: #94a3b8; 
            font-weight: 600; 
        }
        
        /* 🟢 PREVINE ZOOM DO TELEMÓVEL: font-size em 16px impede o zoom automático do navegador no input */
        .campo-grupo input, 
        .campo-grupo select { 
            padding: 12px 14px; 
            background: #070b12; 
            border: 1px solid #374151; 
            border-radius: 8px; 
            color: #fff; 
            font-size: 16px; 
            outline: none; 
            width: 100%; 
            color-scheme: dark; 
            transition: border-color 0.2s;
        }
        
        .campo-grupo input:focus,
        .campo-grupo select:focus {
            border-color: <?= $cor_tema ?>;
        }
        
        .fatura-box { 
            background: #070b12; 
            padding: 15px; 
            border-radius: 10px; 
            border: 1px solid #1f2937; 
            margin-bottom: 20px; 
        }
        
        .linha-fatura { 
            display: flex; 
            justify-content: space-between; 
            font-size: 13px; 
            color: #94a3b8; 
            margin-bottom: 8px; 
        }
        
        .linha-fatura span:last-child { 
            font-weight: bold; 
            color: #fff; 
        }
        
        .total-row { 
            border-top: 1px solid #1f2937; 
            padding-top: 10px; 
            margin-top: 10px; 
            font-size: 15px; 
            font-weight: bold; 
        }
        
        .total-row span:last-child { 
            color: #eab308 !important; 
            font-size: 17px; 
        }
        
        .btn-pagar { 
            width: 100%; 
            background: <?= $cor_tema ?>; 
            color: white; 
            border: none; 
            padding: 14px; 
            font-size: 14px; 
            font-weight: bold; 
            border-radius: 8px; 
            cursor: pointer; 
            text-transform: uppercase; 
            letter-spacing: 0.5px; 
            box-shadow: 0 4px 15px rgba(0,0,0,0.2); 
            transition: 0.2s; 
        }
        
        .btn-pagar:hover { 
            opacity: 0.9; 
            transform: translateY(-1px); 
        }
        
        .btn-sair { 
            display: block; /* Mudado para block para melhor alinhamento em ecrãs pequenos */
            width: fit-content;
            background: #1e293b; 
            color: white; 
            padding: 8px 16px; 
            border-radius: 20px; 
            text-decoration: none; 
            font-weight: bold; 
            font-size: 11px; 
            border: 1px solid #334155; 
            margin-bottom: 15px; 
            text-transform: uppercase; 
        }
        
        /* 📱 RECIBO DE FATURA RESPONSIVO */
        .fatura-recibo-container { 
            background: #fff; 
            color: #000; 
            padding: 20px 15px; /* Reduzido o padding para não cortar o texto nas laterais */
            border-radius: 12px; 
            width: 100%;
            max-width: 500px; 
            margin: 20px auto; 
            box-shadow: 0 10px 25px rgba(255,255,255,0.05); 
            font-family: monospace; 
            border-top: 8px solid #22c55e; 
            text-align: left; 
            box-sizing: border-box;
        }

        /* 🟢 MEDIA QUERIES: Regras exclusivas para otimização em telemóveis */
        @media (max-width: 480px) {
            body {
                padding: 8px 6px;
            }
            .seccao-cadastro {
                margin: 10px auto;
                padding: 16px 12px;
                border-radius: 12px;
            }
            .fatura-recibo-container {
                margin: 10px auto;
                padding: 18px 12px;
                font-size: 12px; /* Encolhe ligeiramente a fonte tipográfica do recibo para caber faturas grandes */
            }
            /* Ajuste para grids com múltiplos campos lado a lado */
            .grid-dupla-mobile {
                display: grid !important;
                grid-template-columns: 1fr 1fr !important;
                gap: 10px !important;
            }
        }
    </style>
</head>
<body>

    <?php if ($exibir_fatura_final): ?>
        <!-- =========================================================================
             🖨️ EMISSÃO DA FATURA RECIBO PREMIUM SÍNCRONA
             ========================================================================= -->
        <div class="fatura-recibo-container">
            <div style="text-align: center; margin-bottom: 20px; border-bottom: 2px dashed #000; padding-bottom: 15px;">
                <h3 style="font-size: 16px; font-weight: bold; letter-spacing: 1px;">HOLDING ECOSSISTEMA AURÉLIUS</h3>
                <p style="font-size: 11px; margin-top: 4px;">Huambo - Angola</p>
                <p style="font-size: 12px; font-weight: bold; margin-top: 10px; color:#22c55e;"><?= $fatura_dados['num_fatura'] ?></p>
            </div>
            
            <p style="margin-bottom: 6px;"><b>Estabelecimento:</b> <?= $fatura_dados['salao'] ?></p>
            <p style="margin-bottom: 6px;"><b>Cliente:</b> <?= $fatura_dados['cliente'] ?></p>
            <p style="margin-bottom: 15px;"><b>Telefone:</b> <?= $fatura_dados['telefone'] ?></p>
            
            <div style="border-bottom: 1px solid #000; padding-bottom: 5px; margin-bottom: 10px; font-weight: bold; display: flex; justify-content: space-between;">
                <span>DESCRIÇÃO DOS ARTIGOS</span>
                <span>TOTAL</span>
            </div>
            <div style="display: flex; justify-content: space-between; margin-bottom: 15px; font-size: 13px;">
                <span><?= $fatura_dados['produto'] ?> (x<?= $fatura_dados['qtd'] ?>)</span>
                <span><?= number_format($fatura_dados['bruto'], 2, ',', '.') ?> Kz</span>
            </div>
            
            <div style="border-top: 1px dashed #000; padding-top: 10px; font-size: 13px;">
                <div style="display: flex; justify-content: space-between; margin-bottom: 5px;"><span>Taxa de Logística/Frete:</span><span>+ <?= number_format($fatura_dados['frete'], 2, ',', '.') ?> Kz</span></div>
                <div style="display: flex; justify-content: space-between; margin-bottom: 5px; color:#b91c1c;"><span>Desconto VIP (-20%):</span><span>- <?= number_format($fatura_dados['desconto'], 2, ',', '.') ?> Kz</span></div>
                <div style="display: flex; justify-content: space-between; margin-bottom: 5px; font-weight: bold; font-size: 14px; border-top: 1px solid #000; padding-top: 5px;">
                    <span>Total da Operação:</span><span><?= number_format($fatura_dados['total'], 2, ',', '.') ?> Kz</span>
                </div>
                <div style="display: flex; justify-content: space-between; margin-bottom: 5px; font-weight: bold; color: #16a34a; font-size: 14px;">
                    <span>VALOR LIQUIDADO AGORA:</span><span><?= number_format($fatura_dados['pago_agora'], 2, ',', '.') ?> Kz</span>
                </div>
            </div>

            <div style="text-align: center; margin-top: 30px; border-top: 2px dashed #000; padding-top: 15px; font-size: 11px;">
                <p><b>Método:</b> <?= $fatura_dados['canal'] ?> (<?= $fatura_dados['status'] ?>)</p>
                <p style="margin-top: 10px; font-weight: bold;">✓ OBRIGADO PELA PREFERÊNCIA EM ANGOLA!</p>
                <a href="Lojas.php" style="display:block; margin-top:20px; background:#000; color:#fff; padding:10px; text-decoration:none; font-weight:bold; border-radius:6px; text-align:center;">Fechar e Concluir</a>
            </div>
        </div>
    <?php else: ?>

        <a href="Lojas.php" class="btn-sair">✕ VOLTAR</a>

        <div class="seccao-cadastro">
            <h2>Checkout: <?= $label_gateway ?></h2>

            <div class="crm-badge" style="background: <?= $cor_crm ?>15; color: <?= $cor_crm ?>; border-color: <?= $cor_crm ?>30;">
                Perfil: <?= $status_crm_cliente ?>
            </div>

            <div style="background: #0f172a; padding: 15px; border-radius: 10px; margin-bottom: 18px; border-left: 4px solid <?= $cor_tema ?>; text-align: left;">
                <strong style="color: #00d2ff; font-size: 14px; display: block;"><?= htmlspecialchars($nome_parceiro_real) ?></strong>
                <span style="font-size: 14px; color: #fff; display: block; margin-top: 4px; font-weight: bold;"><?= htmlspecialchars($nome_produto) ?></span>
                <p style="font-size: 11px; color: #94a3b8; margin-top: 6px;">Inventário Restante no Balcão: <strong style="color: #fff;"><?= $stock_maximo ?> unidade.</strong></p>
            </div>

            <form id="form_checkout_real" method="POST" action="">
                <input type="hidden" name="executar_venda_final" value="1">
                
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px; margin-bottom: 15px;">
                    <div class="campo-grupo">
                        <label>Nome do Cliente:</label>
                        <input type="text" name="nome_cliente" value="<?= isset($_POST['nome_cliente']) ? htmlspecialchars($_POST['nome_cliente']) : '' ?>" required>
                    </div>
                    <div class="campo-grupo">
                    <label>Telefone do Cliente:</label>
                    <div style="display: flex; gap: 6px;">
                        <!-- 🟢 LOCK NUMÉRICO: inputmode abre o teclado de números e o oninput remove letras instantaneamente -->
                        <input type="tel" 
                               name="cliente_telefone" 
                               id="telefone_input" 
                               value="<?= htmlspecialchars($telefone_busca) ?>" 
                               inputmode="numeric" 
                               pattern="[0-9]*" 
                               oninput="this.value = this.value.replace(/[^0-9]/g, ''); verificarEstatutoVip(this.value);" 
                               required 
                               autocomplete="off" 
                               style="flex: 1;">
                    </div>
                </div>
                </div>

                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px; margin-bottom: 15px;">
                    <div class="campo-grupo">
                        <label>Modalidade:</label>
                        <select name="modalidade_entrega" id="frete_select" onchange="atualizarFaturaReal()">
                        <option value="buscar">Selecione </option>
                            <option value="buscar">Buscar no local (Preço Normal)</option>
                            <option value="levar">Vêem ao meu encontro/Frete (+ 1.500 Kz)</option>
                        </select>
                    </div>
                    <div class="campo-grupo">
                        <label>Condição de Faturamento:</label>
                        <select name="tipo_pagamento" id="pagamento_select" onchange="atualizarFaturaReal()">
                        <option value="total">Selecione </option>
                            <option value="total">Pagar Valor Total (100%)</option>
                            <option value="adiantado">Pagar Adiantado (Sinal de 50%)</option>
                        </select>
                    </div>
                </div>

                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px; margin-bottom: 15px;">
                    <div class="campo-grupo">
                        <label>Canal de Pagamento Digital:</label>
                        <select name="canal_pagamento" id="canal_select" onchange="atualizarFaturaReal()">
                            <option value="unitel_money" <?= $gateway_atual === 'unitel_money' ? 'selected' : '' ?>>📱 Carteira Unitel Money Express</option>
                            <option value="mcx_xpress" <?= $gateway_atual === 'mcx_xpress' ? 'selected' : '' ?>> Multicaixa Express (EMIS)</option>
                            <option value="referencia_bancaria">🏦 Gerar Referência Única Interbancária</option>
                        </select>
                    </div>
                    <div class="campo-grupo">
                        <label>Quantidade Pretendida:</label>
                        <select name="quantidade_selecionada" id="qtd_select" onchange="atualizarFaturaReal()">
                            <?php for($i = 1; $i <= $stock_maximo; $i++): ?>
                                <option value="<?= $i ?>" <?= $i == $quantidade_inicial ? 'selected' : '' ?>><?= $i ?> unidade.</option>
                            <?php endfor; ?>
                        </select>
                    </div>
                </div>

                <div class="campo-grupo" id="bloco_pin_unitel_money">
                <?php if ($acao_requerida_pin === 'validar'): ?>
                <label style="color: #38bdf8; font-size: 12.5px; font-weight: 600;">🔒 Insira o seu PIN de Confirmação:</label>
                <input type="password" 
                       name="pin_unitel_confirmacao" 
                       id="pin_input" 
                       placeholder="Insira o seu PIN cadastrado" 
                       inputmode="numeric"
                       pattern="[0-9]*"
                       maxlength="6"
                       oninput="this.value = this.value.replace(/[^0-9]/g, '');"
                       autocomplete="off" 
                       style="width: 100%; padding: 12px 14px; background: #070b12; border: 1px solid #374151; border-radius: 8px; color: #fff; font-size: 16px; outline: none; margin-top: 5px;">
                
                <span style="font-size: 11px; color: #eab308; display: block; margin-top: 6px; line-height: 1.4; text-align: left;">
                    💡 Esqueceu o código? Clique aqui: 
                    <a href="Unitele.php?redefinir_pin_urgente=1&tel_ref=<?= urlencode($telefone_busca) ?>&id_produto_comprado=<?= $id_produto_get ?>" style="color:#f87171; font-weight:bold; text-decoration:none; border-bottom: 1px dashed #f87171;">Redefinir PIN de Segurança</a>
                </span>

            <?php else: ?>
                <label style="color: #22c55e; font-size: 12.5px; font-weight: 600;">✨ Crie o seu PIN Único (Primeiro Acesso):</label>
                <input type="password" 
                       name="pin_unitel_confirmacao" 
                       id="pin_input_novo" 
                       placeholder="Crie um PIN numérico (Mínimo 4 dígitos)" 
                       inputmode="numeric"
                       pattern="[0-9]*"
                       maxlength="6"
                       oninput="this.value = this.value.replace(/[^0-9]/g, '');"
                       autocomplete="off" 
                       style="width: 100%; padding: 12px 14px; background: #070b12; border: 1px solid #374151; border-radius: 8px; color: #fff; font-size: 16px; outline: none; margin-top: 5px;">
                
                <span style="font-size: 10.5px; color: #64748b; display: block; margin-top: 4px; text-align: left;">Este código será solicitado nas tuas próximas compras para evitar fraudes com o teu número.</span>
            <?php endif; ?>
                </div>

                <?php 
                    $entidade_aurelius = "00112"; 
                    $referencia_gerada_id = "92" . str_pad($id_produto_get . $id_parceiro_real . rand(100, 999), 7, "0", STR_PAD_LEFT);
                ?>
                <div id="bloco_referencia_bancaria" style="display:none; background: #070b12; border: 2px dashed #ca8a04; padding: 15px; border-radius: 10px; margin-bottom: 20px; text-align: left;">
                    <span style="color: #eab308; font-size: 11px; font-weight: bold; text-transform: uppercase; display: block; margin-bottom: 6px;">🏦 DADOS DE COMPENSAÇÃO DE CAIXA</span>
                    <p style="font-size: 13px;"><b>Entidade:</b> <span style="color:#ca8a04; font-family: monospace; font-weight: bold;"><?= $entidade_aurelius ?></span></p>
                    <p style="font-size: 13px;"><b>Referência:</b> <span style="color:#ca8a04; font-family: monospace; font-weight: bold;"><?= chunk_split($referencia_gerada_id, 3, ' ') ?></span></p>
                </div>

                <div class="fatura-box">
                    <div class="linha-fatura"><span>Produtos Subtotal:</span><span id="txt_bruto">0,00 Kz</span></div>
                    <div class="linha-fatura" id="linha_frete" style="color:#38bdf8;"><span>Taxas de Frete:</span><span id="txt_frete_val">0,00 Kz</span></div>
                    <div class="linha-fatura" id="linha_desc_vip" style="display:none; color:#4ade80;"><span>Desconto VIP Especial (20%):</span><span id="txt_desc_vip">-0,00 Kz</span></div>
                    <div class="linha-fatura" style="border-bottom: 1px dashed #1f2937; padding-bottom: 8px; margin-bottom: 8px;"><span>Taxa Intermediação (10%):</span><span style="color: #f87171;" id="txt_taxa">-0,00 Kz</span></div>
                    <div class="linha-fatura total-row" style="color: #fff; font-size: 15px; font-weight: bold;"><span>Total Geral da Operação:</span><span id="txt_total" style="color: #eab308; font-size:18px;">0,00 Kz</span></div>
                    <div class="linha-fatura total-row" id="linha_adiantado" style="display:none; border-top:1px dashed #334155; color:#00ff87; margin-top: 10px; padding-top: 10px;"><span>Sinal Requerido (50%):</span><span id="txt_adiantado_val">0,00 Kz</span></div>
                </div>

                <button type="submit" name="executar_venda_final" onclick="return validarPinUnitelAntesDeSubmeter()" class="btn-pagar">⚡ Finalizar Transação</button>
            </form>
        </div>
        <script>
        const precoUnitario = <?= floatval($preco_tabela) ?>;
        let clienteE_Vip = false;

        function verificarEstatutoVip(telefone) {
            if (!telefone) return;
            // Valida os prefixos VIP de Angola (925 ou 935)
            if (telefone.startsWith('925') || telefone.startsWith('935')) {
                clienteE_Vip = true;
                if(document.getElementById('linha_desc_vip')) document.getElementById('linha_desc_vip').style.display = 'flex';
            } else {
                clienteE_Vip = false;
                if(document.getElementById('linha_desc_vip')) document.getElementById('linha_desc_vip').style.display = 'none';
            }
            atualizarFaturaReal();
        }

        function atualizarFaturaReal() {
            // Mapeamento e Captura Segura dos Inputs (Evita ler elementos nulos)
            const e_qtd       = document.getElementById('quantidade_selecionada') || document.getElementById('qtd_select');
            const e_frete     = document.getElementById('modalidade_entrega') || document.getElementById('frete_select');
            const e_pagamento = document.getElementById('tipo_pagamento') || document.getElementById('pagamento_select');
            const e_canal     = document.getElementById('canal_pagamento') || document.getElementById('canal_select');

            const qtd              = e_qtd ? (parseInt(e_qtd.value) || 1) : 1;
            const freteOpcao       = e_frete ? e_frete.value : 'buscar';
            const pagamentoOpcao   = e_pagamento ? e_pagamento.value : 'total';
            const canalPagamento   = e_canal ? e_canal.value : 'unitel_money';
            
            const subtotalProdutos = precoUnitario * qtd;
            const custoFrete       = (freteOpcao === 'levar') ? 1500 : 0;
            
            const desconto         = clienteE_Vip ? (subtotalProdutos * 0.20) : 0;
            const totalGeral       = (subtotalProdutos + custoFrete) - desconto;
            const taxaPlataforma   = totalGeral * 0.10;

            // Atualização dos elementos na Interface com verificação de existência
            if(document.getElementById('txt_bruto')) {
                document.getElementById('txt_bruto').innerText = subtotalProdutos.toLocaleString('pt-PT', {minimumFractionDigits: 2}) + ' Kz';
            }
            if(document.getElementById('txt_frete_val')) {
                document.getElementById('txt_frete_val').innerText = '+ ' + custoFrete.toLocaleString('pt-PT', {minimumFractionDigits: 2}) + ' Kz';
            }
            if(document.getElementById('txt_desc_vip')) {
                document.getElementById('txt_desc_vip').innerText = '-' + desconto.toLocaleString('pt-PT', {minimumFractionDigits: 2}) + ' Kz';
            }
            if(document.getElementById('txt_taxa')) {
                document.getElementById('txt_taxa').innerText = '-' + taxaPlataforma.toLocaleString('pt-PT', {minimumFractionDigits: 2}) + ' Kz';
            }
            if(document.getElementById('txt_total')) {
                document.getElementById('txt_total').innerText = totalGeral.toLocaleString('pt-PT', {minimumFractionDigits: 2}) + ' Kz';
            }

            // Gestão Dinâmica dos Blocos de Gateways (Unitel Money PIN vs Referência)
            const blocoPin = document.getElementById('bloco_pin_unitel_money') || document.getElementById('unitel-passo2');
            const pinInput = document.getElementById('pin_input') || document.getElementById('pinUnitel');
            const blocoRef = document.getElementById('bloco_referencia_bancaria') || document.getElementById('pay-ref');

            if (canalPagamento === 'unitel_money') {
                if(blocoPin) blocoPin.style.setProperty('display', 'block', 'important');
                if(pinInput) pinInput.required = true;
                if(blocoRef) blocoRef.style.display = 'none';
            } else if (canalPagamento === 'referencia_bancaria' || canalPagamento === 'ref') {
                if(blocoPin) blocoPin.style.setProperty('display', 'none', 'important');
                if(pinInput) pinInput.required = false;
                if(blocoRef) blocoRef.style.display = 'block';
            } else {
                if(blocoPin) blocoPin.style.setProperty('display', 'none', 'important');
                if(pinInput) pinInput.required = false;
                if(blocoRef) blocoRef.style.display = 'none';
            }

            // Tratamento da linha de adiantamento parcial (50%)
            const linhaAdiantado = document.getElementById('linha_adiantado');
            if (linhaAdiantado) {
                if (pagamentoOpcao === 'adiantado') {
                    const adiantadoSinal = totalGeral * 0.50;
                    linhaAdiantado.style.display = 'flex';
                    if(document.getElementById('txt_adiantado_val')) {
                        document.getElementById('txt_adiantado_val').innerText = adiantadoSinal.toLocaleString('pt-PT', {minimumFractionDigits: 2}) + ' Kz';
                    }
                } else {
                    linhaAdiantado.style.display = 'none';
                }
            }
        }
 
        function validarPinUnitelAntesDeSubmeter() {
            // Procura o seletor do canal de pagamento usando os IDs novos ou antigos do teu projeto
            const e_canal = document.getElementById('canal_pagamento') || document.getElementById('canal_select');
            // Procura o input do PIN (seja de validação ou de primeiro acesso)
            const e_pin   = document.getElementById('pin_input') || document.getElementById('pin_input_novo') || document.getElementById('pinUnitel');
            
            const canal = e_canal ? e_canal.value : '';
            const pin   = e_pin ? e_pin.value.trim() : '';
            
            // Só valida obrigatoriedade se o método selecionado for estritamente o Unitel Money
            if (canal === 'unitel_money' || canal === 'unitel') {
                if (pin.length < 4) {
                    alert("❌ Erro de Autenticação Unitel Money: Introduza o seu PIN ou token de segurança único para validar a cobrança!");
                    if(e_pin) e_pin.focus(); // Coloca o cursor diretamente no campo para facilitar o toque
                    return false; // Trava o formulário
                }
            }
            
            return true; // Liberta o formulário para faturamento se estiver tudo correto
        }

        // Inicialização protegida contra elementos nulos
        document.addEventListener("DOMContentLoaded", function() {
            const inputTel = document.getElementById('cliente_telefone') || document.getElementById('telefone_input') || document.getElementById('telUnitel');
            
            if (inputTel) {
                // Escuta alterações em tempo real no telefone para ativar o VIP dinamicamente
                inputTel.addEventListener('input', function() {
                    verificarEstatutoVip(this.value.trim());
                });
                // Executa a primeira verificação com o valor inicial
                verificarEstatutoVip(inputTel.value.trim());
            } else {
                atualizarFaturaReal();
            }

            // Vincula ouvintes de alteração nos selects para recalcular tudo de imediato
            const seletores = ['quantidade_selecionada', 'qtd_select', 'modalidade_entrega', 'frete_select', 'tipo_pagamento', 'pagamento_select', 'canal_pagamento', 'canal_select'];
            seletores.forEach(id => {
                const el = document.getElementById(id);
                if(el) el.addEventListener('change', atualizarFaturaReal);
            });
        });
        </script>
    <?php endif; ?>
</body>
</html>