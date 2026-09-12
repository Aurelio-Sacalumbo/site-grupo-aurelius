
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

// 1. Painel de Métricas Rápidas (Mês Atual)
$mes = date('m'); $ano = date('Y');
$fin = $mysqli->query("SELECT SUM(valor_total) as bruto, SUM(comissao_aurelius) as lucro FROM `historico_vendas` WHERE MONTH(data_venda)='$mes' AND YEAR(data_venda)='$ano'") ->fetch_assoc();

// 2. Procura as vendas dos últimos 7 dias para alimentar o gráfico de barras
$query_grafico = $mysqli->query("SELECT DATE(data_venda) as dia, SUM(comissao_aurelius) as total_dia 
                                 FROM `historico_vendas` 
                                 WHERE data_venda >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)
                                 GROUP BY DATE(data_venda) 
                                 ORDER BY dia ASC");

$dias = []; $valores = [];
while($row = $query_grafico->fetch_assoc()) {
    $dias[] = date('d/m', strtotime($row['dia']));
    $valores[] = (float)$row['total_dia'];
}
?>
<!DOCTYPE html>
<html lang="pt-PT">
<head>
    <meta charset="UTF-8">
    <title>Aurelius - Gráficos Executivos</title>
    <!-- Inclui a biblioteca oficial de gráficos -->
    <script src="https://jsdelivr.net"></script>
    <style>
        body { background-color: #0b1a30; color: #fff; font-family: sans-serif; padding: 30px; }
        .grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 20px; }
        .card { background: #0f172a; padding: 20px; border-radius: 12px; border: 1px solid #1e293b; text-align: center; }
        .chart-box { background: #0f172a; padding: 25px; border-radius: 12px; border: 1px solid #1e293b; margin-top: 30px; }
    </style>
</head>
<body>

<div style="max-width: 900px; margin: 0 auto;">
    <h2>📊 Painel de Faturamento e Gráficos Globais</h2>
    <p style="color: #a1a1aa; margin-bottom: 25px;">Desempenho operacional da plataforma Aurelius.</p>

    <div class="grid">
        <div class="card">
            <span style="color: #a1a1aa;">Volume Geral</span>
            <h3 style="font-size: 22px; color: #fff; margin-top: 5px;"><?php echo number_format($fin['bruto'] ?? 0, 2, ',', '.'); ?> AOA</h3>
        </div>
        <div class="card" style="border-color: #22c55e;">
            <span style="color: #22c55e;">Comissões Ganhas</span>
            <h3 style="font-size: 22px; color: #22c55e; margin-top: 5px;"><?php echo number_format($fin['lucro'] ?? 0, 2, ',', '.'); ?> AOA</h3>
        </div>
    </div>

    <!-- Contentor do Gráfico de Barras -->
    <div class="chart-box">
        <h4 style="margin-bottom: 15px; color: #38bdf8;">Evolução das Comissões nos Últimos 7 Dias (AOA)</h4>
        <canvas id="graficoVendas" style="max-height: 300px;"></canvas>
    </div>
</div>

<script>
const ctx = document.getElementById('graficoVendas').getContext('2d');
new Chart(ctx, {
    type: 'bar', // Tipo de gráfico: barras
    data: {
        labels: <?php echo json_encode($dias); ?>, // Passa os dias do PHP para o JavaScript
        datasets: [{
            label: 'Ganho da Aurelius (AOA)',
            data: <?php echo json_encode($valores); ?>, // Passa os valores das comissões
            backgroundColor: '#38bdf8',
            borderColor: '#0088cc',
            borderWidth: 1,
            borderRadius: 6
        }]
    },
    options: {
        plugins: { legend: { labels: { color: '#fff' } } },
        scales: {
            x: { ticks: { color: '#fff' }, grid: { color: '#1e293b' } },
            y: { ticks: { color: '#fff' }, grid: { color: '#1e293b' }, beginAtZero: true }
        }
    }
});
</script>

</body>
</html>