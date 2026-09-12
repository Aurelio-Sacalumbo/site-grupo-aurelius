<?php
// recibo_salario.php
header("Access-Control-Allow-Origin: *");
header('Content-Type: text/html; charset=utf-8');
session_start();

include_once("Conexao.php");

$id_func = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($id_func === 0) {
    die("<h3 style='text-align:center; font-family:sans-serif; margin-top:50px; color:#d32f2f;'>Erro: Funcionário não selecionado.</h3>");
}

try {
    $sql = "SELECT f.nome, dp.* 
            FROM funcionarios f 
            JOIN funcionarios_dados_pessoais dp ON f.id_funcionario = dp.id_funcionario 
            WHERE f.id_funcionario = ?";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$id_func]);
    $dados = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$dados) {
        die("<h3 style='text-align:center; font-family:sans-serif; margin-top:50px; color:#ca8a04;'>Aviso: Ficha cadastral não encontrada.</h3>");
    }

    // Lógica Financeira de Retribuição
    $salario_base = floatval($dados['salario_base']);
    $bonus_extras = floatval($dados['bonus_horas_extras'] ?? 0);
    
    // INSS incide apenas sobre a remuneração base (Padrão de Angola: 3%)
    $desconto_inss = $salario_base * 0.03;
    $salario_liquido = ($salario_base + $bonus_extras) - $desconto_inss;

} catch (PDOException $e) {
    die("Erro no servidor: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="pt-PT">
<head>
    <meta charset="UTF-8">
    <title>Recibo_Salario_<?php echo $id_func; ?></title>
    <style>
        body { background-color: #f1f5f9; margin: 0; padding: 20px; font-family: 'Segoe UI', sans-serif; }
        .recibo-container { background: #fff; max-width: 600px; margin: 30px auto; padding: 40px; border: 1px solid #cbd5e1; border-top: 12px solid #0b1a30; }
        .topo { display: flex; justify-content: space-between; align-items: center; border-bottom: 2px solid #e2e8f0; padding-bottom: 15px; margin-bottom: 20px; }
        .tabela-valores { width: 100%; border-collapse: collapse; margin-top: 20px; text-align: left; }
        .tabela-valores th { background: #0b1a30; color: #fff; padding: 10px; font-size: 11px; text-transform: uppercase; }
        .tabela-valores td { padding: 12px 10px; border-bottom: 1px solid #e2e8f0; font-size: 13px; color: #334155; }
        .bloco-assinaturas { display: flex; justify-content: space-between; margin-top: 50px; padding-top: 20px; }
        .no-print { text-align: center; margin-top: 20px; }
        .btn { background: #0b1a30; color: white; border: none; padding: 12px 30px; font-weight: bold; cursor: pointer; text-transform: uppercase; font-size: 12px; }
        @media print {
            .no-print { display: none !important; }
            body { background: #fff; padding: 0; }
            .recibo-container { border: none; margin: 0; padding: 0; }
        }
    </style>
</head>
<body>

<div class="recibo-container">
    <div class="topo">
        <div>
            <h2 style="color: #d32f2f; margin: 0; font-size: 22px; font-weight: 800;">🎌GRUPO AURELIUS</h2>
            <p style="color: #64748b; font-size: 11px; margin: 3px 0 0 0; text-transform: uppercase; font-weight: bold;">Recibos de Retribuição Mensal</p>
            <small style="color: #94a3b8;">Huambo - Angola</small>
        </div>
        <div style="text-align: right; font-size: 12px; color: #475569;">
            <strong>Período:</strong> <?php echo date('m/Y'); ?><br>
            <strong>Emitido em:</strong> <?php echo date('d/m/Y'); ?>
        </div>
    </div>

    <div style="font-size: 13px; color: #334155; background: #f8fafc; padding: 14px; margin-bottom: 25px; line-height: 1.6; border-left: 4px solid #d32f2f;">
        <strong>Funcionário:</strong> <?php echo htmlspecialchars($dados['nome']); ?><br>
        <strong>BI nº:</strong> <?php echo htmlspecialchars($dados['numero_bi']); ?> | <strong>Telefone:</strong> <?php echo htmlspecialchars($dados['telefone_pessoal']); ?><br>
        <strong>Nível Académico:</strong> <?php echo htmlspecialchars($dados['nivel_academico']); ?><br>
        <strong>Morada:</strong> Bairro <?php echo htmlspecialchars($dados['morada_bairro']); ?>, Huambo
    </div>

    <table class="tabela-valores">
        <thead>
            <tr>
                <th>Descrição da Verba</th>
                <th>Vencimentos</th>
                <th style="text-align: right;">Descontos</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td>Salário Base Mensal</td>
                <td><?php echo number_format($salario_base, 2, ',', '.'); ?> Kz</td>
                <td style="text-align: right; color: #94a3b8;">-</td>
            </tr>
            <tr>
                <td>Bónus / Horas Extras Efetuadas</td>
                <td style="color: #16a34a; font-weight: bold;">+ <?php echo number_format($bonus_extras, 2, ',', '.'); ?> Kz</td>
                <td style="text-align: right; color: #94a3b8;">-</td>
            </tr>
            <tr>
                <td>Retenção para Segurança Social (INSS 3%)</td>
                <td style="color: #94a3b8;">-</td>
                <td style="text-align: right; color: #dc2626; font-weight: bold;">- <?php echo number_format($desconto_inss, 2, ',', '.'); ?> Kz</td>
            </tr>
            <tr style="background: #f8fafc; font-size: 15px; font-weight: bold; color: #10b981;">
                <td>LÍQUIDO A RECEBER:</td>
                <td><?php echo number_format($salario_liquido, 2, ',', '.'); ?> Kz</td>
                <td style="text-align: right; color: #94a3b8;">-</td>
            </tr>
        </tbody>
    </table>

    <div class="bloco-assinaturas">
        <div style="width: 45%; text-align: center; border-top: 1px solid #94a3b8; padding-top: 8px; font-size: 11px; color: #64748b;">
            Assinatura da Direção
        </div>
        <div style="width: 45%; text-align: center; border-top: 1px solid #94a3b8; padding-top: 8px; font-size: 11px; color: #64748b;">
            Assinatura do Funcionário
        </div>
    </div>
    
    <p style="text-align: center; font-size: 10px; color: #94a3b8; margin-top: 40px; border-top: 1px dashed #cbd5e1; padding-top: 15px;">
        Barbearia Branca localizado no Huambo junto a igreja Ieca. Tuapandula.
    </p>
</div>

<div class="no-print">
    <button class="btn" onclick="window.print()">🖨️ Guardar em PDF / Imprimir Recibo</button>
</div>

</body>
</html>