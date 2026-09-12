
<?php
if (!isset($_SESSION)) { 
    session_start(); 
}

// 1. Verifica se o utilizador está sequer logado no sistema
if (!isset($_SESSION['usuario_tipo'])) {
    header("Location: login.php"); // Manda para o login se for um visitante curioso
    exit();
}

// 2. Proteção para o Relatório Geral da Aurelius (Apenas o Administrador da Plataforma entra)
// (Assumindo que grava o tipo 'admin' ou 'dono' na sessão de login da Aurelius)
if ($_SESSION['usuario_tipo'] !== 'admin_master') {
    die("<h1 style='color:red; text-align:center; margin-top:50px;'>Erro 403: Acesso Negado. Esta área é exclusiva da administração da Aurelius.</h1>");
}
?>

<?php
if (!isset($_SESSION)) { session_start(); }
$mysqli = new mysqli("127.0.0.1", "root", "", "aurelius_salao");
$mysqli->set_charset("utf8");

// Identifica que salão está autenticado
if (!isset($_SESSION['empresa_codigo'])) { die("Acesso negado."); }
$id_barbearia = $_SESSION['empresa_codigo'];

// Carrega a listagem de todas as vendas desta barbearia em particular
$query_extrato = $mysqli->query("SELECT h.*, p.nome_produto 
    FROM `historico_vendas` h
    JOIN `produtos_cosmeticos` p ON h.produto_id = p.id
    WHERE h.empresa_id = '$id_barbearia' 
    ORDER BY h.data_venda DESC");
?>
<!DOCTYPE html>
<html lang="pt-PT">
<head>
    <meta charset="UTF-8">
    <title>Painel do Barbeiro - Extrato de Vendas</title>
    <style>
        body { background-color: #0b1a30; color: #fff; font-family: sans-serif; padding: 30px; }
        table { width: 100%; border-collapse: collapse; margin-top: 20px; background: #0f172a; border-radius: 8px; overflow: hidden; }
        th, td { padding: 15px; text-align: left; border-bottom: 1px solid #1e293b; }
        th { background-color: #1e293b; color: #38bdf8; }
    </style>
</head>
<body>

<div style="max-width: 1000px; margin: 0 auto;">
    <h2>🧾 O Meu Extrato de Cosméticos (Salão ID: <?php echo $id_barbearia; ?>)</h2>
    
    <table>
        <thead>
            <tr>
                <th>Data</th>
                <th>Produto</th>
                <th>Método</th>
                <th>Valor Total</th>
                <th>Taxa App (10%)</th>
                <th>O Meu Ganho Líquido</th>
            </tr>
        </thead>
        <tbody>
            <?php if ($query_extrato->num_rows > 0): ?>
                <?php while($venda = $query_extrato->fetch_assoc()): ?>
                    <tr>
                        <td><?php echo date('d/m/Y H:i', strtotime($venda['data_venda'])); ?></td>
                        <td><?php echo htmlspecialchars($venda['nome_produto']); ?></td>
                        <td><?php echo $venda['metodo_pagamento']; ?></td>
                        <td><?php echo number_format($venda['valor_total'], 2, ',', '.'); ?> AOA</td>
                        <td style="color: #f43f5e;">-<?php echo number_format($venda['comissao_aurelius'], 2, ',', '.'); ?> AOA</td>
                        <td style="color: #22c55e; font-weight: bold;"><?php echo number_format($venda['valor_barbearia'], 2, ',', '.'); ?> AOA</td>
                    </tr>
                <?php endwhile; ?>
            <?php else: ?>
                <tr>
                    <td colspan="6" style="text-align: center; color: #a1a1aa; font-style: italic;">Nenhum produto foi vendido online por este salão até ao momento.</td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>

</body>
</html>