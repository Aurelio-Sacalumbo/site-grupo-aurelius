<?php
// =========================================================================
// 💳 MOTOR FINANCEIRO: LIQUIDAÇÃO DE COMISSÕES POR BARBEIRO (SÓ CONCLUÍDOS)
// =========================================================================
if (!isset($_SESSION)) { 
    session_start(); 
}
date_default_timezone_set('Africa/Luanda');

include_once("Conexao.php");

if (!isset($pdo)) {
    try {
        $pdo = new PDO("mysql:host=127.0.0.1;dbname=aurelius_salao;charset=utf8", "root", "");
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    } catch (PDOException $e) {
        die("Erro na infraestrutura de rede: " . $e->getMessage());
    }
}

// 🟢 REGRA DE NEGÓCIO: Configuração do percentual padrão de comissão para a equipa (Ex: 40% para o barbeiro)
$percentual_comissao_barbeiro = 0.40; 

// 🧠 QUERY REATIVAL REAL: Agrupa o faturamento por mestre apenas dos serviços 'Concluido_Pago'
$sql_comissoes = "
    SELECT 
        profissional AS nome_mestre,
        COUNT(id_pagamento) AS total_atendimentos,
        SUM(valor) AS faturamento_bruto
    FROM pagamentos 
    WHERE status_atendimento = 'Concluido_Pago'
    GROUP BY profissional 
    ORDER BY faturamento_bruto DESC
";

try {
    $stmt_comissoes = $pdo->query($sql_comissoes);
    $lista_comissoes = $stmt_comissoes->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $lista_comissoes = [];
}

// Estatísticas globais do balcão (Apenas dinheiro limpo e verificado)
$total_bruto_salao = 0;
$total_comissoes_pagas = 0;
foreach ($lista_comissoes as $c) {
    $total_bruto_salao += floatval($c['faturamento_bruto']);
    $total_comissoes_pagas += floatval($c['faturamento_bruto']) * $percentual_comissao_barbeiro;
}
$lucro_liquido_empresa = $total_bruto_salao - $total_comissoes_pagas;
?>
<!DOCTYPE html>
<html lang="pt-PT">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Folha de Comissões - Grupo Aurélius</title>
    <style>
        body { font-family: 'Segoe UI', Arial, sans-serif; background: #0f172a; color: #f8fafc; padding: 30px; margin: 0; }
        .container-financeiro { max-width: 1100px; margin: 0 auto; background: #1e293b; padding: 30px; border-radius: 16px; border: 1px solid #334155; box-shadow: 0 15px 30px rgba(0,0,0,0.4); }
        .cabecalho-fin h2 { margin: 0; color: #fff; font-size: 24px; }
        .grid-cards { display: grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap: 20px; margin: 25px 0; }
        .card-fin { background: #0f172a; padding: 20px; border-radius: 12px; border-left: 5px solid #334155; }
        .card-fin.bruto { border-left-color: #38bdf8; }
        .card-fin.comissao { border-left-color: #eab308; }
        .card-fin.lucro { border-left-color: #22c55e; }
        .card-fin span { display: block; font-size: 11px; color: #94a3b8; text-transform: uppercase; font-weight: bold; margin-bottom: 5px; }
        .card-fin strong { font-size: 22px; }
        .tabela-comissoes { width: 100%; border-collapse: collapse; margin-top: 15px; font-size: 14px; text-align: left; }
        .tabela-comissoes th { background: #0f172a; padding: 14px; color: #38bdf8; text-transform: uppercase; font-size: 11px; font-weight: bold; letter-spacing: 0.5px; }
        .tabela-comissoes td { padding: 14px; border-bottom: 1px solid #334155; background: #111827; vertical-align: middle; }
    </style>
</head>
<body>

<div class="container-financeiro">
    <div class="cabecalho-fin">
        <h2>💳 Extrato de Comissões e Repasses Técnicos</h2>
        <p style="color: #94a3b8; font-size: 13px; margin: 4px 0 0 0;">Filtro Anti-Fraude Ativo: Apenas serviços validados como Concluído e Pago são contabilizados neste relatório [finance].</p>
    </div>

    <!-- 📊 CARDS DE INDICADORES REALISTAS -->
    <div class="grid-cards">
        <div class="card-fin bruto">
            <span>Faturamento Bruto Balcão</span>
            <strong style="color: #38bdf8;"><?= number_format($total_bruto_salao, 2, ',', '.') ?> Kz</strong>
        </div>
        <div class="card-fin comissao">
            <span>Total Comissões Devidas (40%)</span>
            <strong style="color: #eab308;"><?= number_format($total_comissoes_pagas, 2, ',', '.') ?> Kz</strong>
        </div>
        <div class="card-fin lucro">
            <span>Retenção Líquida da Empresa</span>
            <strong style="color: #22c55e;"><?= number_format($lucro_liquido_empresa, 2, ',', '.') ?> Kz</strong>
        </div>
    </div>

    <!-- 🧾 HISTÓRICO EM REDE POR PROFISSIONAL -->
    <table class="tabela-comissoes">
        <thead>
            <tr>
                <th>Profissional / Mestre Responsável</th>
                <th>Cortes/Serviços Validados</th>
                <th>Produção Bruta (Kz)</th>
                <th>Repasse do Barbeiro (40%)</th>
                <th>Lucro do Salão (60%)</th>
            </tr>
        </thead>
        <tbody>
            <?php if (!empty($lista_comissoes)): ?>
                <?php foreach ($lista_comissoes as $mestre): 
                    $bruto_mestre = floatval($mestre['faturamento_bruto']);
                    $comissao_mestre = $bruto_mestre * $percentual_comissao_barbeiro;
                    $retencao_salao = $bruto_mestre - $comissao_mestre;
                ?>
                    <tr>
                        <td><strong style="color: #fff; font-size: 15px;">💈 <?= htmlspecialchars($mestre['nome_mestre']) ?></strong></td>
                        <td style="color: #cbd5e1; font-weight: 600;"><?= $mestre['total_atendimentos'] ?> atendimentos</td>
                        <td style="color: #fff; font-weight: bold;"><?= number_format($bruto_mestre, 2, ',', '.') ?> Kz</td>
                        <td style="color: #eab308; font-weight: bold;"><?= number_format($comissao_mestre, 2, ',', '.') ?> Kz</td>
                        <td style="color: #22c55e; font-weight: bold;"><?= number_format($retencao_salao, 2, ',', '.') ?> Kz</td>
                    </tr>
                <?php endforeach; ?>
            <?php else: ?>
                <tr>
                    <td colspan="5" style="text-align: center; color: #64748b; padding: 40px; font-style: italic;">Nenhuma comissão liquidada. Valide os agendamentos pendentes na Central de Auditoria [finance].</td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>

</body>
</html>