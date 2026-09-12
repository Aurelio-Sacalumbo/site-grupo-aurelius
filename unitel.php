<?php
// =========================================================================
// 🚀 ECOSSISTEMA MESTRE - unitel.php (MÓDULO DE CHECKOUT E GATEWAY REATIVO)
// =========================================================================
include_once("config/Banco.php"); // Conector Mestre Central do Ecossistema

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
date_default_timezone_set('Africa/Luanda');

// =========================================================================
// 🟢 FLUXO 1: AJAX REATIVO — BUSCA AUTOMÁTICA DE FATURA ATIVA & STATUS VIP
// =========================================================================
if (isset($_GET['pesquisa_automatica_cliente'])) {
    $nome_busca = trim($_GET['nome']);
    $data_hoje_com_hora = date('Y-m-d H:i:s');
    
    // 1. Localiza a última fatura com estado 'Pendente' associada a este cliente
    $stmt_fatura = $pdo->prepare("
        SELECT id_pagamento, valor, servico, id_parceiro, tipo_parceiro 
        FROM `pagamentos` 
        WHERE `cliente` = ? AND `status_atendimento` = 'Pendente' 
        ORDER BY id_pagamento DESC 
        LIMIT 1
    ");
    $stmt_fatura->execute([$nome_busca]);
    $fatura = $stmt_fatura->fetch(PDO::FETCH_ASSOC);

    if ($fatura) {
        $id_pag_encontrado = $fatura['id_pagamento'];
        $preco_servico = floatval($fatura['valor']);
        $nome_servico = $fatura['servico'];
        
        // 2. Auditoria de Assinatura VIP: Valida se o cliente possui desconto ativo
        $check_vip = $pdo->prepare("
            SELECT id_assinatura, telefone_express 
            FROM `assinaturas` 
            WHERE `cliente` = ? AND `status` = 'Ativo' AND `data_fim` >= ? 
            LIMIT 1
        ");
        $check_vip->execute([$nome_busca, $data_hoje_com_hora]);
        $assinatura = $check_vip->fetch(PDO::FETCH_ASSOC);
        
        $is_vip = $assinatura ? true : false;
        $telefone_cliente = $assinatura ? $assinatura['telefone_express'] : '';

        // 3. Carteira Virtual: Resgata saldos ou trocos acumulados em sessões passadas
        $saldo_carteira = 0.00;
        if (!empty($telefone_cliente)) {
            $stmt_saldo = $pdo->prepare("
                SELECT saldo_acumulado 
                FROM `carteira_saldos_clientes` 
                WHERE `telefone_cliente` = ? 
                LIMIT 1
            ");
            $stmt_saldo->execute([$telefone_cliente]);
            $saldo_carteira = (float)$stmt_saldo->fetchColumn();
        }

        // Retorna o JSON limpo para o motor JavaScript injetar nos inputs do ecrã
        echo json_encode([
            'status' => 'encontrado',
            'id_pagamento' => $id_pag_encontrado,
            'servico' => $nome_servico,
            'preco_base' => $preco_servico,
            'vip' => $is_vip,
            'telefone' => $telefone_cliente,
            'saldo_interno' => $saldo_carteira
        ]);
    } else {
        echo json_encode(['status' => 'nao_encontrado']);
    }
    exit();
}

// =========================================================================
// 🟢 FLUXO 2: PROCESSAMENTO DE CAIXA — CONFIRMAÇÃO E DEDUÇÃO DE VALORES
// =========================================================================
// =========================================================================
// 🟢 FLUXO 2: PROCESSAMENTO DE CAIXA CORRIGIDO — SINCRONISMO DE CAIXA REAL
// =========================================================================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['executar_venda_final'])) {
    $id_pagamento_real = intval($_POST['id_pagamento_real']);
    $cliente_nome = trim($_POST['nome_cliente']);
    $cliente_telefone = trim($_POST['cliente_telefone']);
    $valor_entregue = floatval($_POST['valor_entregue']);
    $metodo_pagamento = htmlspecialchars($_POST['metodo_gateway']); 

    // Revalida a fatura original contra fraudes
    $stmt_check = $pdo->prepare("SELECT valor, servico, id_parceiro, tipo_parceiro FROM `pagamentos` WHERE `id_pagamento` = ?");
    $stmt_check->execute([$id_pagamento_real]);
    $fatura_real = $stmt_check->fetch();

    if ($fatura_real) {
        $preco_base = floatval($fatura_real['valor']);
        
        // Validação VIP (Desconto Cortesia)
        $data_com_hora = date('Y-m-d H:i:s');
        $stmt_vip_check = $pdo->prepare("SELECT COUNT(*) FROM `assinaturas` WHERE `telefone_express` = ? AND `status` = 'Ativo' AND `data_fim` >= ?");
        $stmt_vip_check->execute([$cliente_telefone, $data_com_hora]);
        $is_vip = ($stmt_vip_check->fetchColumn() > 0);

        $desconto_vip = $is_vip ? ($preco_base * 0.2) : 0.00;
        $total_liquido_cliente = $preco_base - $desconto_vip;

        // Taxas administrativas SaaS (10%)
        $taxa_plataforma = $total_liquido_cliente * 0.2;
        $liquido_parceiro = $total_liquido_cliente - $taxa_plataforma;

        // Gestão de saldos e trocos na carteira virtual
        if ($metodo_pagamento === 'SALDO_INTERNO') {
            $pdo->prepare("UPDATE `carteira_saldos_clientes` SET `saldo_acumulado` = `saldo_acumulado` - ? WHERE `telefone_cliente` = ?")->execute([$total_liquido_cliente, $cliente_telefone]);
        } else {
            $sobra_troco = $valor_entregue - $total_liquido_cliente;
            if ($sobra_troco > 0) {
                $check_c = $pdo->prepare("SELECT COUNT(*) FROM `carteira_saldos_clientes` WHERE `telefone_cliente` = ?");
                $check_c->execute([$cliente_telefone]);
                
                if ($check_c->fetchColumn() > 0) {
                    $pdo->prepare("UPDATE `carteira_saldos_clientes` SET `saldo_acumulado` = `saldo_acumulado` + ? WHERE `telefone_cliente` = ?")->execute([$sobra_troco, $cliente_telefone]);
                } else {
                    $pdo->prepare("INSERT INTO `carteira_saldos_clientes` (telefone_cliente, nome_cliente, saldo_acumulado) VALUES (?, ?, ?)")->execute([$cliente_telefone, $cliente_nome, $sobra_troco]);
                }
            }
        }

        // ✨ A CORREÇÃO: Força status_atendimento = 'Confirmado' E status_trabalho = 'concluido'
        // Isso muda a Cadeira de 'NÃO PAGO' para 'PAGO' ou 'ADIANTADO' instantaneamente!
        $servico_atualizado_nome = $fatura_real['servico'] . " (" . $metodo_pagamento . ")";
        
        $update_pag = $pdo->prepare("UPDATE `pagamentos` SET 
            `cliente_telefone` = ?, 
            `servico` = ?, 
            `valor` = ?, 
            `desconto` = ?, 
            `valor_liquido` = ?, 
            `status_atendimento` = 'Confirmado', 
            `status_trabalho` = 'concluido', 
            `tipo_pagamento` = ?,
            `visto_admin` = 0 
            WHERE `id_pagamento` = ?");
        
        $update_pag->execute([
            $cliente_telefone, $servico_atualizado_nome, $preco_base, $desconto_vip, $liquido_parceiro, $metodo_pagamento, $id_pagamento_real
        ]);

        echo "<script>alert('⚡ Transação Sincronizada e Paga com Sucesso!'); window.location.href='Dashboard.php';</script>";
        exit();
    } else {
        die("<script>alert('Erro: Fatura não localizada.'); window.history.back();</script>");
    }
}
?>




<!DOCTYPE html>
<html lang="pt-PT">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Módulo de Pagamentos - Grupo Aurélius</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; font-family: 'Segoe UI', Arial, sans-serif; }
        body { width: 100%; height: auto; background: linear-gradient(135deg, #090514, #120b24); text-align: center; padding-bottom: 40px; color: #f8fafc; min-height: 100vh; }
        nav { padding: 15px 30px; background: #120b24; border-bottom: 2px solid #38bdf8; box-shadow: 0 0 15px rgba(56, 189, 248, 0.3); display: flex; justify-content: space-between; align-items: center; }
        .logo h1 { font-size: 22px; font-weight: 800; color: #ef4444; text-transform: uppercase; }
        .logo h1 span { color: #f8fafc; }
        .logo h6 { color: #64748b; font-size: 11px; margin-top: 4px; text-transform: uppercase; }
        .seccao-cadastro { position: relative; background: #1e293b; padding: 35px; width: 92%; max-width: 520px; margin: 40px auto; border-radius: 20px; text-align: left; border: 2px solid #38bdf8; box-shadow: 0 0 20px rgba(56, 189, 248, 0.4), inset 0 0 15px rgba(56, 189, 248, 0.1); animation: pulsarGlow 4s infinite alternate; }
        @keyframes pulsarGlow { 0% { box-shadow: 0 0 12px rgba(56, 189, 248, 0.3); border-color: #0284c7; } 100% { box-shadow: 0 0 25px rgba(56, 189, 248, 0.7); border-color: #38bdf8; } }
        .btn-fechar-top { position: absolute; top: -15px; right: -15px; width: 36px; height: 36px; background: linear-gradient(135deg, #ef4444, #dc2626); color: white; border-radius: 50%; display: flex; align-items: center; justify-content: center; text-decoration: none; font-weight: bold; border: 2px solid #1e293b; }
        .seccao-cadastro h2 { margin-bottom: 20px; color: #38bdf8; font-size: 20px; border-bottom: 2px solid #334155; padding-bottom: 10px; text-transform: uppercase; font-weight: bold; }
        .campo-grupo { margin-bottom: 18px; display: flex; flex-direction: column; }
        .campo-grupo label { font-weight: bold; font-size: 12px; margin-bottom: 6px; color: #94a3b8; text-transform: uppercase; }
        .campo-grupo input { padding: 14px; border: 1px solid #475569; border-radius: 8px; font-size: 15px; background: #0f172a; color: white; outline: none; }
        .botoes-pagamento { display: flex; gap: 10px; margin-top: 15px; flex-wrap: wrap; }
        .btn-pagar { flex: 1; min-width: 120px; padding: 14px; border: none; border-radius: 8px; font-weight: bold; cursor: pointer; color: white; font-size: 12px; text-transform: uppercase; }
        .btn-unitel { background: linear-gradient(135deg, #ff6600, #cc5200); }
        .btn-express { background: linear-gradient(135deg, #003399, #002266); }
        .btn-saldo-interno { background: linear-gradient(135deg, #22c55e, #16a34a); color: #000; width: 100%; display: block; margin-top: 15px; font-weight: bold; }
        .fatura-box { background: #0b0f19; padding: 16px; border-radius: 12px; border: 1px solid #22314d; margin-bottom: 18px; font-size: 13px; }
        .linha-fatura { display: flex; justify-content: space-between; border-bottom: 1px dashed #334155; padding-bottom: 6px; margin-bottom: 6px; }
        .linha-fatura.total-row { border-bottom: none; font-weight: bold; font-size: 16px; color: #22c55e; margin-top: 10px; }
        .badge-vip-alerta { background: rgba(234, 179, 8, 0.1); border: 1px solid #eab308; color: #eab308; padding: 12px; border-radius: 8px; font-size: 13px; font-weight: bold; margin-bottom: 18px; text-align: center; display: none; }
        .erro-fatura-msg { background: rgba(239, 68, 68, 0.1); border: 1px solid #ef4444; color: #f87171; padding: 12px; border-radius: 8px; font-weight: bold; margin-bottom: 15px; text-align: center; display: none; }
    </style>
</head>
<body>

    <nav>
        <div class="logo" onclick="window.location.href='Dashboard.php'">
            <h1>Aurélius <span>Módulo</span></h1>
            <h6>Formas de Pagamento dos Servíoços ja efectuado</h6>
        </div>
    </nav>

    <div class="seccao-cadastro">
        <a href="Dashboard.php" class="btn-fechar-top">&times;</a>
        <h2>Checkout de Caixa Sincronizado</h2>
        
        <!-- Avisos dinâmicos de auditoria real -->
        <div id="msg_erro_fatura" class="erro-fatura-msg">⚠️ OPERAÇÃO REJEITADA: Este cliente não possui faturas pendentes!</div>
        <div id="status_carteira_box" class="badge-vip-alerta"></div>

        <div id="caixa_detalhe_pedido" style="background: #0f172a; padding: 14px; border-radius: 10px; margin-bottom: 18px; border-left: 4px solid #38bdf8; font-size: 13px; display: none;">
            <span>Fatura Pendente Localizada:</span>
            <strong style="display:block; color:#fff; font-size:15px; margin-top:3px;" id="txt_lbl_servico">---</strong>
        </div>

        <form id="form_unitel_real" method="POST" action="unitel.php">
    
    <!-- ✨ A CORREÇÃO: O name correto que o motor do unitel.php espera para ativar o POST -->
    <input type="hidden" name="executar_venda_final" value="1">
    <input type="hidden" name="id_pagamento_real" id="id_pagamento_real" value="0">
    <input type="hidden" name="metodo_gateway" id="txt_gateway_metodo" value="Unitel Money">

    <div class="campo-grupo">
        <label>Nome do Cliente (Deve bater certo com a marcação):</label>
        <input type="text" name="nome_cliente" id="nome_input" placeholder="Insira o nome exato do agendamento" onkeyup="sincronizarFaturaPorNome(this.value)" required autocomplete="off">
    </div>

    <div class="campo-grupo">
        <label>Telefone (Assinatura VIP):</label>
        <input type="tel" name="cliente_telefone" id="telefone_input" placeholder="Insira o número" required pattern="[0-9]{9,15}">
    </div>

    <div class="campo-grupo" id="wrapper_valor_pago">
        <label>Valor Entregue / Pago (AKZ):</label>
        <input type="number" step="0.01" name="valor_entregue" id="valor_entregue_input" value="0.00" min="0" required oninput="calcularTrocoMesa(this.value)">
    </div>

    <div class="fatura-box">
        <div class="linha-fatura"><span>Valor do Serviço (AKZ):</span><span id="f_servico">0,00 AKZ</span></div>
        <div class="linha-fatura"><span>Subtotal Base:</span><span id="f_subtotal">0,00 AKZ</span></div>
        <div class="linha-fatura" id="linha_desconto_vip" style="display:none; color:#eab308;"><span>Desconto VIP Aplicado (20%):</span><span id="txt_desc_vip">0,00 AKZ</span></div>
        <div class="linha-fatura" style="color:#64748b;"><span>Desconto Cortesia (Taxa App 2%):</span><span id="f_taxa">-0,00 AKZ</span></div>
        <div class="linha-fatura" id="linha_troco_caixa" style="display:none; color:#4ade80;"><span>Troco a Devolver / Guardar:</span><span id="lbl_troco_caixa">0,00 AKZ</span></div>
        <div class="linha-fatura total-row"><span>Total Líquido a Pagar:</span><span id="txt_total_liquido">0,00 AKZ</span></div>
    </div>

    <!-- MÉTODOS DE REDE MÓVEL / REFERÊNCIA -->
    <div class="botoes-pagamento" id="gateways_externos_bloco">
        <button type="button" class="btn-pagar btn-unitel" onclick="processarEnvioGateway('Unitel Money')" style="margin-bottom: 10px;">Unitel Money</button>
        <button type="button" class="btn-pagar btn-express" onclick="processarEnvioGateway('MCX Express')">MCX Express</button>
    </div>

    <!-- SALDO INTERNO / CRÉDITO ACUMULADO -->
    <button type="button" class="btn-pagar btn-saldo-interno" id="btn_carteira_click" style="display:none; width: 100%; margin-top: 10px;" onclick="processarEnvioGateway('SALDO_INTERNO')">⚡ Confirmar Pagamento com Saldo Guardado</button>
</form>

<!-- =========================================================================
     🟩 ENGINE JAVASCRIPT: CONTROLO DE SUBMISSÃO REATIVO
     ========================================================================= -->
<script>
function processarEnvioGateway(metodo) {
    // 1. Atualiza o input hidden com o método correto selecionado pelo operador
    document.getElementById('txt_gateway_metodo').value = metodo;
    
    // 2. Captura o ID da fatura para garantir que não vai a zeros
    const idFatura = document.getElementById('id_pagamento_real').value;
    if(idFatura == 0 || idFatura == "") {
        alert("⚠️ Selecione primeiro um cliente válido com fatura pendente!");
        return;
    }
    
    // 3. Executa a submissão física do formulário para o unitel.php
    document.getElementById('form_unitel_real').submit();
}
</script>
    </div>

    <!-- 🟢 ENGINE BANCÁRIO REATIVO -->
    <script>
    let precoOriginalServico = 0;
    let totalComDescontoVip = 0;
    let clientePossuiVip = false;

    // Busca assíncrona que sincroniza a fatura com base no nome digitado
    function sincronizarFaturaPorNome(nome) {
        if (nome.length >= 3) {
            fetch('unitel.php?pesquisa_automatica_cliente=1&nome=' + encodeURIComponent(nome).trim())
            .then(res => res.json())
            .then(data => {
                if (data.status === 'encontrado') {
                    // Oculta avisos de erro e abre os dados reais da pauta
                    document.getElementById('msg_erro_fatura').style.display = 'none';
                    document.getElementById('caixa_detalhe_pedido').style.display = 'block';
                    document.getElementById('txt_lbl_servico').innerText = data.servico;
                    document.getElementById('id_pagamento_real').value = data.id_pagamento;
                    
                    if (data.telefone) {
                        document.getElementById('telefone_input').value = data.telefone;
                    }

                    precoOriginalServico = data.preco_base;
                    clientePossuiVip = data.vip;

                    // Exibe blocos informativos de carteira e plano VIP
                    let msgBox = document.getElementById('status_carteira_box');
                    if (data.vip || data.saldo_interno > 0) {
                        msgBox.style.display = 'block';
                        let htmlAlert = '';
                        if (data.vip) htmlAlert += '👑 ASSINATURA VIP ATIVA (2% OFF)! ';
                        if (data.saldo_interno > 0) htmlAlert += '💰 Saldo Guardado: ' + data.saldo_interno.toLocaleString('pt-PT', {minimumFractionDigits: 2}) + ' Kz';
                        msgBox.innerHTML = htmlAlert;

                        // Se o dinheiro guardado cobrir a conta, ativa liquidação de 1 clique
                        if (data.saldo_interno >= (precoOriginalServico * 0.8)) {
                            document.getElementById('btn_carteira_click').style.display = 'block';
                            document.getElementById('gateways_externos_bloco').style.display = 'none';
                            document.getElementById('wrapper_valor_pago').style.display = 'none';
                        }
                    } else {
                        msgBox.style.display = 'none';
                    }

                    recalcularFaturamentoInterface();
                } else {
                    removerDadosDaTela();
                }
            });
        } else {
            removerDadosDaTela();
        }
    }

    function removerDadosDaTela() {
        precoOriginalServico = 0;
        totalComDescontoVip = 0;
        clientePossuiVip = false;
        document.getElementById('msg_erro_fatura').style.display = 'block';
        document.getElementById('caixa_detalhe_pedido').style.display = 'none';
        document.getElementById('status_carteira_box').style.display = 'none';
        document.getElementById('btn_carteira_click').style.display = 'none';
        document.getElementById('gateways_externos_bloco').style.display = 'flex';
        document.getElementById('wrapper_valor_pago').style.display = 'flex';
        
        document.getElementById('f_servico').innerText = '0,00 AKZ';
        document.getElementById('f_subtotal').innerText = '0,00 AKZ';
        document.getElementById('f_taxa').innerText = '-0,00 AKZ';
        document.getElementById('txt_total_liquido').innerText = '0,00 AKZ';
    }

    function recalcularFaturamentoInterface() {
        const desconto = clientePossuiVip ? (precoOriginalServico * 0.2) : 0;
        totalComDescontoVip = precoOriginalServico - desconto;
        const taxaApp = totalComDescontoVip * 0.2;

        document.getElementById('f_servico').innerText = precoOriginalServico.toLocaleString('pt-PT', {minimumFractionDigits: 2}) + ' AKZ';
        document.getElementById('f_subtotal').innerText = precoOriginalServico.toLocaleString('pt-PT', {minimumFractionDigits: 2}) + ' AKZ';
        document.getElementById('f_taxa').innerText = '-' + taxaApp.toLocaleString('pt-PT', {minimumFractionDigits: 2}) + ' AKZ';
        document.getElementById('txt_total_liquido').innerText = totalComDescontoVip.toLocaleString('pt-PT', {minimumFractionDigits: 2}) + ' AKZ';
        
        if (clientePossuiVip) {
            document.getElementById('linha_desconto_vip').style.display = 'flex';
            document.getElementById('txt_desc_vip').innerText = '-' + desconto.toLocaleString('pt-PT', {minimumFractionDigits: 2}) + ' AKZ';
        } else {
            document.getElementById('linha_desconto_vip').style.display = 'none';
        }

        const inputPago = document.getElementById('valor_entregue_input');
        if (inputPago.value == "0" || inputPago.value == "0.00") {
            inputPago.value = totalComDescontoVip;
        }
        calcularTrocoMesa(inputPago.value);
    }

    function calcularTrocoMesa(valor) {
        const entregue = parseFloat(valor) || 0;
        const troco = entregue - totalComDescontoVip;

        if (troco > 0) {
            document.getElementById('linha_troco_caixa').style.display = 'flex';
            document.getElementById('lbl_troco_caixa').innerText = troco.toLocaleString('pt-PT', {minimumFractionDigits: 2}) + ' AKZ';
        } else {
            document.getElementById('linha_troco_caixa').style.display = 'none';
        }
    }

    function setGateway(metodo) { 
        document.getElementById('txt_gateway_metodo').value = metodo; 
    }
    </script>
</body>
</html>