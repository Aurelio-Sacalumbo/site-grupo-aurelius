<?php
if (session_status() === PHP_SESSION_NONE) { session_start(); }
require_once __DIR__ . "/config/Banco.php";

$id_pagamento = isset($_GET['id_pagamento']) ? intval($_GET['id_pagamento']) : 1;

// Procura os dados reais do faturamento unificado
$stmt = $pdo->prepare("SELECT * FROM `pagamentos` WHERE `id_pagamento` = ?");
$stmt->execute([$id_pagamento]);
$fatura = $stmt->fetch();

if (!$fatura) {
    die("<p style='color:red; text-align:center;'>Fatura não encontrada no sistema local.</p>");
}

// Calcula os dados reversos de faturamento para exibição limpa
$taxa_aurelius = $fatura['desconto']; // O valor gravado da comissão
$valor_bruto = $fatura['valor'];
$valor_liquido_parceiro = $fatura['valor_liquido'];
?>
<!DOCTYPE html>
<html lang="pt-PT">
<head>
    <meta charset="UTF-8">
    <title>Recibo Comercial - Grupo Aurelius</title>
    <style>
        body { background: #f1f5f9; color: #000; font-family: 'Courier New', Courier, monospace; padding: 10px; }
        .cupao-fiscal { max-width: 300px; margin: 0 auto; background: #fff; padding: 15px; border: 1px solid #cbd5e1; box-shadow: 0 2px 5px rgba(0,0,0,0.05); }
        .centro { text-align: center; }
        .linha-tracejada { border-top: 1px dashed #000; margin: 10px 0; }
        .row-item { display: flex; justify-content: space-between; font-size: 12px; margin-bottom: 5px; }
        .total-bloco { font-size: 14px; font-weight: bold; background: #eee; padding: 6px; margin-top: 10px; }
        @media print { body { background: none; padding: 0; } .btn-print { display: none; } }
    </style>
</head>
<body>

    <div class="cupao-fiscal">
        <div class="centro">
            <h3 style="margin: 0;">GRUPO AURÉLIUS SAAS</h3>
            <span style="font-size: 11px;">Módulo de Faturamento Centralizado</span><br>
            <span style="font-size: 11px;">Luanda - Huambo - Huíla</span>
        </div>

        <div class="linha-tracejada"></div>

        <!-- Dados de Rastreio Operacional -->
        <div class="row-item"><span>Doc Nº:</span><strong>FAC-<?= $fatura['id_pagamento'] ?></strong></div>
        <div class="row-item"><span>Data Transação:</span><span><?= date('d/m/Y H:i', strtotime($fatura['data_registro'])) ?></span></div>
        <div class="row-item"><span>Estado Atendimento:</span><span style="text-transform: uppercase;"><b><?= $fatura['status_atendimento'] ?></b></span></div>
        <div class="row-item"><span>Estado Entrega:</span><span style="text-transform: uppercase;"><b><?= $fatura['status_trabalho'] ?></b></span></div>

        <div class="linha-tracejada"></div>

        <!-- Especificação das Partes Envolvidas -->
        <div class="row-item"><span>Operador / Canal:</span><span><?= htmlspecialchars($fatura['profissional']) ?></span></div>
        <div class="row-item"><span>Cliente Comprador:</span><span><?= htmlspecialchars($fatura['cliente']) ?></span></div>
        <div class="row-item"><span>Assinatura Registo:</span><span><?= htmlspecialchars($fatura['cliente_telephone'] ?? $fatura['cliente_telefone']) ?></span></div>
        
        <?php if(!empty($fatura['assinatura_cliente'])): ?>
            <div class="row-item" style="color: green;"><span>Assinatura Balcão:</span><span><b><?= htmlspecialchars($fatura['assinatura_cliente']) ?></b></span></div>
        <?php endif; ?>

        <div class="linha-tracejada"></div>

        <!-- Descrição Contabilística -->
        <div style="font-size: 12px; font-weight: bold; margin-bottom: 8px;">ARTIGO / SERVIÇO:</div>
        <div style="font-size: 13px; margin-bottom: 10px;"><?= htmlspecialchars($fatura['servico']) ?></div>
        
        <div class="row-item"><span>Preço Bruto:</span><span><?= number_format($valor_bruto, 2, ',', '.') ?> AOA</span></div>
        <div class="row-item" style="color: #ef4444;"><span>Taxa Aurelius (10%):</span><span>-<?= number_format($taxa_aurelius, 2, ',', '.') ?> AOA</span></div>
        
        <div class="total-bloco row-item">
            <span>REPASSE LÍQUIDO:</span>
            <span><?= number_format($valor_liquido_parceiro, 2, ',', '.') ?> AOA</span>
        </div>

        <div class="linha-tracejada"></div>
        <p class="centro" style="font-size: 10px; margin: 0;">Obrigado por utilizar a rede Aurelius!</p>
        
        <div class="centro" style="margin-top: 15px;">
            <button class="btn-print" onclick="window.print();" style="padding: 5px 15px; font-family: monospace; font-weight: bold; cursor: pointer;">🖨️ Imprimir Cupão</button>
        </div>
    </div>

</body>
</html>
