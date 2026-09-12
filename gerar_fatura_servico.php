<?php
if (!isset($_SESSION)) { session_start(); }
$mysqli = new mysqli("127.0.0.1", "root", "", "aurelius_salao");
$mysqli->set_charset("utf8");

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['servico_id'])) {
    
    $fatura_num = "FAC-" . rand(10000, 99999);
    $empresa_id = $_SESSION['empresa_codigo'] ?? 222;
    $cliente_id = $_SESSION['cliente_id'];
    $profissional_id = (int)$_POST['profissional_id'];
    $servico_id = (int)$_POST['servico_id'];
    
    $preco_orig = (float)$_POST['preco_original'];
    $preco_desc = (float)$_POST['preco_desconto'];
    $tipo_cliente = $_SESSION['cliente_tipo'] ?? 'VIP';
    $metodo_pag = $_POST['metodo_pagamento'];
    $data_agendada = $_POST['data_agendada'];
    $hora_agendada = $_POST['hora_agendada'];
    
    // Insere o registo detalhado unificado no histórico financeiro
    $stmt = $mysqli->prepare("INSERT INTO `faturas_agendamentos` 
        (fatura_numero, empresa_id, cliente_id, profesional_id, servico_id, tipo_item, preco_original, preco_desconto, tipo_cliente, metodo_pagamento, data_agendada, hora_agendada) 
        VALUES (?, ?, ?, ?, ?, 'servico', ?, ?, ?, ?, ?, ?)");
    
    $stmt->bind_param("siiiidddsss", $fatura_num, $empresa_id, $cliente_id, $profissional_id, $servico_id, $preco_orig, $preco_desc, $tipo_cliente, $metodo_pag, $data_agendada, $hora_agendada);
    $stmt->execute();
    
    // Carrega dados textuais adicionais para renderizar a fatura impressa
    $servico_nome = $mysqli->query("SELECT nome_servico FROM `servicios_barbearia` WHERE id=$servico_id")->fetch_assoc()['nome_servico'];
    $prof_nome = $mysqli->query("SELECT nome_profissional FROM `profissionais` WHERE id=$profissional_id")->fetch_assoc()['nome_profissional'];
} else {
    header("Location: Dashboard2.php");
    exit();
}
?>
<!DOCTYPE html>
<html lang="pt-PT">
<head>
    <meta charset="UTF-8">
    <title>Aurelius - Fatura Comercial</title>
    <style>
        body { background: #f8fafc; color: #1e293b; font-family: monospace; padding: 20px; }
        .invoice-box { max-width: 600px; margin: 0 auto; background: #fff; padding: 30px; border: 1px solid #e2e8f0; border-radius: 8px; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.1); }
        .header { display: flex; justify-content: space-between; border-bottom: 2px dashed #cbd5e1; padding-bottom: 15px; margin-bottom: 20px; }
        .row-info { display: flex; justify-content: space-between; margin-bottom: 10px; font-size: 14px; }
        .total-box { background: #f1f5f9; padding: 15px; border-radius: 6px; margin-top: 20px; font-weight: bold; font-size: 16px; text-align: right; }
    </style>
</head>
<body>

<div class="invoice-box">
    <div class="header">
        <div>
            <h2 style="color: #0b1a30; margin-bottom: 5px;">GRUPO AURELIUS</h2>
            <span>Luanda, Angola</span>
        </div>
        <div style="text-align: right;">
            <h4 style="color: #0088cc;"><?php echo $fatura_num; ?></h4>
            <span style="font-size: 12px;"><?php echo date('d/m/Y H:i'); ?></span>
        </div>
    </div>

    <h3 style="margin-bottom: 15px; text-transform: uppercase;">Recibo de Agendamento Eletrónico</h3>
    
    <div class="row-info"><span>Cliente:</span> <strong><?php echo htmlspecialchars($_SESSION['cliente_nome']); ?> (<?php echo $tipo_cliente; ?>)</strong></div>
    <div class="row-info"><span>Especialista Mapeado:</span> <strong><?php echo htmlspecialchars($prof_nome); ?></strong></div>
    <div class="row-info"><span>Serviço Adquirido:</span> <strong><?php echo htmlspecialchars($servico_nome); ?></strong></div>
    <div class="row-info"><span>Canal Utilizado:</span> <strong style="color:#22c55e;"><?php echo $metodo_pag; ?></strong></div>
    <div class="row-info"><span>Data Marcada:</span> <strong><?php echo date('d/m/Y', strtotime($data_agendada)); ?></strong></div>
    <div class="row-info"><span>Horário de Atendimento:</span> <strong><?php echo $hora_agendada; ?></strong></div>
    
    <hr style="border: 1px dashed #cbd5e1; margin: 15px 0;">
    
    <div class="row-info"><span>Preço de Tabela Comercial:</span> <span><?php echo number_format($preco_orig, 2, ',', '.'); ?> AOA</span></div>
    <div class="row-info" style="color: #22c55e;"><span>Abatimento de Conta Especial:</span> <span>-<?php echo number_format($preco_orig - $preco_desc, 2, ',', '.'); ?> AOA</span></div>
    
    <div class="total-box">
        VALOR TOTAL PAGO: <?php echo number_format($preco_desc, 2, ',', '.'); ?> AOA
    </div>

    <div style="text-align: center; margin-top: 30px;">
        <button onclick="window.print();" style="background:#0b1a30; color:white; padding:10px 20px; border:none; border-radius:4px; cursor:pointer; font-weight:bold;">🖨️ Imprimir Fatura Comprovativa</button>
        <a href="Dashboard2.php" style="display:block; margin-top:15px; color:#64748b; font-size:12px;">Voltar ao Dashboard</a>
    </div>
</div>

</body>
</html>