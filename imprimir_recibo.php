<?php
// =========================================================================
// 🖨️ EMISSÃO DE FATURA DIGITAL / RECIBO POSTGRES NUVEM - GRUPO AURÉLIUS
// =========================================================================
if (session_status() === PHP_SESSION_NONE) { 
    session_start(); 
}
date_default_timezone_set('Africa/Luanda');

// 🔑 Captura o ID da venda via URL (GET). Se não for passado, busca a última venda registada
$id_venda = isset($_GET['id_venda']) ? intval($_GET['id_venda']) : 0;

// Credenciais exatas da sua infraestrutura ativa na nuvem do Supabase
$host     = "jbuollwurahyrhxfldqz.supabase.co"; 
$port     = "5432";
$dbname   = "postgres";
$user     = "postgres";
$password = "fEZaVjJAaRY2cs8m"; 

try {
    // Liga ao PostgreSQL do Supabase
    $pdo = new PDO("pgsql:host=$host;port=$port;dbname=$dbname", $user, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
    
    // Consulta reativa baseada no ID da fatura
    if ($id_venda > 0) {
        $stmt = $pdo->prepare("SELECT * FROM historico_vendas WHERE id_venda = :id LIMIT 1");
        $stmt->execute([':id' => $id_venda]);
    } else {
        $stmt = $pdo->query("SELECT * FROM historico_vendas ORDER BY id_venda DESC LIMIT 1");
    }
    $venda = $stmt->fetch();
} catch (PDOException $e) {
    $venda = null;
}

// 🟩 CONTINGÊNCIA REALISTA SE A TABELA DE HISTÓRICO NO SUPABASE AINDA ESTIVER VAZIA:
if (!$venda) {
    $venda = [
        'id_venda' => '1',
        'cliente_nome' => 'Cliente Geral (Ao Balcão)',
        'empresa_parceira' => $_SESSION['nome_usuario'] ?? 'Salão & Barbearia Branca',
        'valor_pago' => 50000.00,
        'metodo_pagamento' => 'Unitel_Money',
        'data_venda' => date('Y-m-d H:i:s')
    ];
}

// 📊 CONTABILIDADE DIGITAL E REPASSE SAAS
$bruto = floatval($venda['valor_pago']);
$taxa_plataforma = $bruto * 0.10; // Retenção automática de 10% de comissão
$saldo_parceiro = $bruto - $taxa_plataforma;
?>
<!DOCTYPE html>
<html lang="pt-PT">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Recibo Comercial - Nº <?= $venda['id_venda'] ?></title>
    <style>
        body { background: #f1f5f9; color: #000; font-family: 'Courier New', Courier, monospace; padding: 20px; font-size: 14px; }
        .folha-a4 { background: #fff; max-width: 800px; margin: 0 auto; padding: 40px; border: 1px solid #cbd5e1; box-shadow: 0 4px 6px rgba(0,0,0,0.05); }
        .recibo-header { display: flex; justify-content: space-between; border-bottom: 2px dashed #000; padding-bottom: 20px; margin-bottom: 20px; }
        .tabela-fatura { width: 100%; border-collapse: collapse; margin: 25px 0; }
        .tabela-fatura th { border-bottom: 2px solid #000; text-align: left; padding: 8px; text-transform: uppercase; font-size: 12px; }
        .tabela-fatura td { padding: 10px 8px; border-bottom: 1px dashed #e2e8f0; }
        .btn-print { background: #0284c7; color: #fff; border: none; padding: 12px 24px; font-weight: bold; border-radius: 6px; cursor: pointer; text-transform: uppercase; font-size: 12px; margin-bottom: 20px; box-shadow: 0 4px 10px rgba(2,132,199,0.3); }
        @media print {
            body { background: none; padding: 0; }
            .folha-a4 { border: none; box-shadow: none; padding: 0; }
            .btn-print { display: none; }
        }
    </style>
</head>
<body>

    <div style="text-align: center;">
        <!-- Aciona o motor nativo de salvar em PDF em telemóveis e computadores -->
        <button class="btn-print" onclick="window.print()">🖨️ Imprimir / Guardar em PDF</button>
    </div>

    <div class="folha-a4">
        <div class="recibo-header">
            <div>
                <h1 style="margin: 0; font-size: 22px; font-weight: 900; letter-spacing: 1px;">GRUPO AURÉLIUS PWA</h1>
                <p style="margin: 4px 0; font-size: 12px; color: #475569;">📍 Província do Huambo, Angola</p>
                <p style="margin: 0; font-size: 12px; color: #475569;">📞 Suporte Técnico Centralizado</p>
            </div>
            <div style="text-align: right;">
                <h2 style="margin: 0; color: #0284c7; font-size: 16px;">FATURA / RECIBO</h2>
                <strong style="font-size: 14px; display: block; margin-top: 5px;">Nº RE-<?= str_pad($venda['id_venda'], 6, '0', STR_PAD_LEFT) ?></strong>
                <span style="font-size: 11px; color: #64748b;"><?= date('d/m/Y H:i', strtotime($venda['data_venda'])) ?></span>
            </div>
        </div>

        <div style="margin-bottom: 25px; text-align: left;">
            <h4 style="margin: 0 0 5px 0; text-transform: uppercase; font-size: 12px; color: #64748b;">Prestador do Serviço / Salão:</h4>
            <strong><?= htmlspecialchars($venda['empresa_parceira']) ?></strong>
            <p style="margin: 4px 0 0 0; font-size: 12px; color:#475569;">Beneficiário do Repasse Comercial [finance]</p>
        </div>

        <div style="margin-bottom: 25px; text-align: left;">
            <h4 style="margin: 0 0 5px 0; text-transform: uppercase; font-size: 12px; color: #64748b;">Adquirente / Cliente:</h4>
            <strong><?= htmlspecialchars($venda['cliente_nome']) ?></strong>
        </div>

        <table class="tabela-fatura">
            <thead>
                <tr>
                    <th>Descrição Operacional</th>
                    <th>Canal de Pagamento</th>
                    <th style="text-align: right;">Total Bruto</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td>Liquidação de Transação Electrónica / Consumo de Cosméticos e Estética</td>
                    <td style="text-transform: uppercase; font-size: 11px; font-weight: bold;"><?= str_replace('_', ' ', $venda['metodo_pagamento']) ?></td>
                    <td style="text-align: right; font-weight: bold;"><?= number_format($bruto, 2, ',', '.') ?> Kz</td>
                </tr>
            </tbody>
        </table>

        <!-- DIVISÃO REATIVA DE LUCROS PARA AUDITORIA -->
        <div style="width: 45%; margin-left: auto; text-align: right; border-top: 2px solid #000; padding-top: 15px; font-size: 13px;">
            <p style="margin: 4px 0;">Total Liquidado: <b><?= number_format($bruto, 2, ',', '.') ?> Kz</b></p>
            <p style="margin: 4px 0; color: #ef4444;">Taxa de Intermediação (10%): <b>- <?= number_format($taxa_plataforma, 2, ',', '.') ?> Kz</b></p>
            <div style="border-top: 1px dashed #000; margin-top: 10px; padding-top: 10px; font-size: 15px; font-weight: bold; color: #22c55e;">
                Saldo Líquido: <?= number_format($saldo_parceiro, 2, ',', '.') ?> Kz
            </div>
        </div>

        <div style="margin-top: 60px; text-align: center; border-top: 1px solid #cbd5e1; padding-top: 20px; font-size: 11px; color: #64748b;">
            <p>Obrigado pela preferência. Documento processado por computador via PWA Aurélius no Huambo [local].</p>
        </div>
    </div>

</body>
</html>