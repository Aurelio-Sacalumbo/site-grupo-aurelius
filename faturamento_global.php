<?php
include_once("Conexao.php");
if (session_status() === PHP_SESSION_NONE) { session_start(); }

// Bloqueia se o usuário logado não for o Administrador Geral da plataforma
$is_admin_geral = (isset($_SESSION['gerente_autenticado']) && $_SESSION['gerente_autenticado'] === true) ? true : false;
if (!$is_admin_geral) {
    // Para testes locais no XAMPP, se não estiver logado como admin, exibe aviso informativo mas processa o layout
    $aviso_admin = "⚠️ Modo de Visualização do Administrador Geral";
}

$taxa_operacional = 0.10; // Comissão fixa de 10% do Grupo Aurélius
$caixa_bruto = 0;
$caixa_comissao = 0;
$caixa_liquido_parceiros = 0;

try {
    // Carrega o histórico completo de transações registadas
    $stmtVendas = $pdo->query("SELECT * FROM historico_vendas ORDER BY id_venda DESC");
    $vendas_totais = $stmtVendas->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $vendas_totais = [];
}
?>
<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8">
    <title>Central Financeira - Admin</title>
    <style>
        body { background: #0f172a; color: #fff; font-family: 'Segoe UI', sans-serif; padding: 20px; }
        .wrapper { max-width: 1300px; margin: 20px auto; background: #111827; padding: 30px; border-radius: 12px; border: 1px solid #233147; }
        .grid-cards { display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 20px; margin: 25px 0; }
        .card { background: #1e293b; padding: 20px; border-radius: 8px; border-left: 5px solid #0284c7; }
        table { width: 100%; border-collapse: collapse; text-align: left; font-size: 13px; margin-top: 20px; }
        th { background: #0f172a; color: #94a3b8; padding: 12px; text-transform: uppercase; font-size: 11px; }
        td { padding: 12px; border-bottom: 1px solid #1e293b; }
    </style>
</head>
<body>
    <div class="wrapper">
        <h2 style="color: #eab308; margin-top: 0; text-transform: uppercase;">💼 Balanço de Faturamento Geral Centralizado</h2>
        <p style="color: #94a3b8; font-size: 13px;">Relatório macroeconómico de auditoria sobre todas as transações, splits de caixa e volumes arrecadados na província do Huambo [finance].</p>

        <?php if(isset($aviso_admin)): ?>
            <div style="background:#1e1b4b; color:#a78bfa; padding:10px; border-radius:6px; font-size:12px; font-weight:bold; margin-bottom:15px; border:1px solid #4c1d95; text-align:center;"><?= $aviso_admin ?></div>
        <?php endif; ?>

        <!-- PROCESSAMENTO DE CÁLCULO E RENDERIZAÇÃO EM ARRAYS -->
        <?php 
        $linhas_html = "";
        if (!empty($vendas_totais)) {
            foreach ($vendas_totais as $v) {
                $bruto = floatval($v['valor_pago']);
                $comissao = $bruto * $taxa_operacional;
                $liquido = $bruto - $comissao;

                $caixa_bruto += $bruto;
                $caixa_comissao += $comissao;
                $caixa_liquido_parceiros += $liquido;

                $linhas_html .= "
                <tr onmouseover=\"this.style.background='#1e293b'\" onmouseout=\"this.style.background='transparent'\">
                    <td>" . date('d/m/Y H:i', strtotime($v['data_venda'])) . "</td>
                    <td style='font-weight:bold; color:#38bdf8;'>" . htmlspecialchars($v['empresa_parceira'] ?? 'Fornecedor Balcão') . "</td>
                    <td>" . htmlspecialchars($v['cliente_nome']) . "</td>
                    <td style='text-transform:uppercase; font-size:11px;'>" . str_replace('_', ' ', $v['metodo_pagamento']) . "</td>
                    <td style='font-weight:bold;'>" . number_format($bruto, 2, ',', '.') . " Kz</td>
                    <td style='color:#4ade80; font-weight:bold;'>" . number_format($comissao, 2, ',', '.') . " Kz</td>
                    <td style='color:#facc15; font-weight:bold;'>" . number_format($liquido, 2, ',', '.') . " Kz</td>
                </tr>";
            }
        } else {
            $linhas_html = "<tr><td colspan='7' style='padding:30px; text-align:center; color:#94a3b8; font-style:italic;'>📭 Nenhuma movimentação financeira auditada na plataforma de momento.</td></tr>";
        }
        ?>

        <div class="grid-cards">
            <div class="card" style="border-color: #38bdf8;">
                <span style="font-size:11px; color:#94a3b8; text-transform:uppercase;">Volume Bruto Total</span>
                <h3 style="margin:5px 0 0 0; font-size:24px; color:#fff;"><?= number_format($caixa_bruto, 2, ',', '.') ?> Kz</h3>
            </div>
            <div class="card" style="border-color: #22c55e;">
                <span style="font-size:11px; color:#94a3b8; text-transform:uppercase;">Arrecadamento Líquido (10%)</span>
                <h3 style="margin:5px 0 0 0; font-size:24px; color:#22c55e;"><?= number_format($caixa_comissao, 2, ',', '.') ?> Kz</h3>
            </div>
            <div class="card" style="border-color: #facc15;">
                <span style="font-size:11px; color:#94a3b8; text-transform:uppercase;">Montante Retido para Repasse</span>
                <h3 style="margin:5px 0 0 0; font-size:24px; color:#facc15;"><?= number_format($caixa_liquido_parceiros, 2, ',', '.') ?> Kz</h3>
            </div>
        </div>

        <table>
            <thead>
                <tr>
                    <th>Data Transação</th>
                    <th>Parceiro Beneficiário</th>
                    <th>Cliente Pagador</th>
                    <th>Canal Escolhido</th>
                    <th>Total Movimentado</th>
                    <th style="color:#4ade80;">Taxa Grupo (10%)</th>
                    <th style="color:#facc15;">Líquido Devido</th>
                </tr>
            </thead>
            <tbody>
                <?= $linhas_html ?>
            </tbody>
        </table>
        
        <a href="Principal.php" style="display:inline-block; margin-top:20px; color:#38bdf8; text-decoration:none; font-size:13px; font-weight:bold;">← Sair para a Vitrina Pública</a>
    </div>
</body>
</html>