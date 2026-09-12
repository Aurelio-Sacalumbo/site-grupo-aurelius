<?php
include_once("Conexao.php");
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Bloqueia acessos diretos que não venham do formulário de compra
if ($_SERVER['REQUEST_METHOD'] !== 'POST') { 
    header("Location: Principal.php"); 
    exit(); 
}

// 1. CAPTURA SEGURA DOS DADOS ENVIADOS
$id_produto       = isset($_POST['id_produto']) ? intval($_POST['id_produto']) : 0;
$parceiro_nome    = isset($_POST['parceiro']) ? htmlspecialchars($_POST['parceiro']) : "Salão & Barbearia Branca";
$opcao_retirada   = isset($_POST['opcao_retirada']) ? htmlspecialchars($_POST['opcao_retirada']) : "buscar";
$metodo_pagamento = isset($_POST['metodo_pagamento']) ? htmlspecialchars($_POST['metodo_pagamento']) : "qr_code";
$nome_cliente     = isset($_POST['nome_cliente']) ? htmlspecialchars($_POST['nome_cliente']) : "Cliente Balcão";
$endereco_cliente = isset($_POST['endereco_cliente']) ? htmlspecialchars($_POST['endereco_cliente']) : "Retirada Física";
$qtd_solicitada   = isset($_POST['quantidade_solicitada']) ? intval($_POST['quantidade_solicitada']) : 1;

// 2. BUSCA AUTOMÁTICA DOS VALORES REAIS NA BASE DE DADOS
$preco_base = 3000.00; // Padrão de segurança para a Vaselina se a busca falhar
$titulo_exibicao = "Vaselina";

try {
    $stmtProd = $pdo->prepare("SELECT * FROM produtos_cosmeticos WHERE id = :id LIMIT 1");
    $stmtProd->execute([':id' => $id_produto]);
    $produto = $stmtProd->fetch(PDO::FETCH_ASSOC);

    if ($produto) {
        // Captura o preço real guardado no phpMyAdmin (Ex: 3000.00)
        if (isset($produto['preco'])) { $preco_base = floatval($produto['preco']); }
        elseif (isset($produto['preco_venda'])) { $preco_base = floatval($produto['preco_venda']); }
        
        // Captura o título real guardado no phpMyAdmin
        if (!empty($produto['nome_produto'])) { $titulo_exibicao = htmlspecialchars($produto['nome_produto']); }
        elseif (!empty($produto['nome'])) { $titulo_exibicao = htmlspecialchars($produto['nome']); }
    }
} catch (Exception $e) {
    // Mantém fallbacks seguros em caso de falha de conexão
}

// Multiplica o preço base pela quantidade selecionada pelo cliente
$subtotal = $preco_base * $qtd_solicitada;

// 🟢 REGRA DA TAXA: Só adiciona os 1.500 Kz se for entrega ao domicílio. Retirada física = 0 Kz.
$taxa_adicional = ($opcao_retirada === 'domicilio') ? 1500.00 : 0.00;
$valor_total = $subtotal + $taxa_adicional;

// 3. GRAVAÇÃO DA VENDA NO HISTÓRICO
try {
    $sqlInsert = "INSERT INTO historico_vendas (cliente_nome, localizacao_entrega, tipo_entrega, metodo_pagamento, valor_pago, data_venda) 
                  VALUES (:cliente, :localizacao, :tipo, :pagamento, :total, NOW())";
    
    $stmtVenda = $pdo->prepare($sqlInsert);
    $stmtVenda->execute([
        ':cliente'     => $nome_cliente,
        ':localizacao' => $endereco_cliente . " (Qtd: " . $qtd_solicitada . ")",
        ':tipo'        => $opcao_retirada,
        ':pagamento'   => $metodo_pagamento,
        ':total'       => $valor_total
    ]);

    // 🟢 ATUALIZAÇÃO DA SESSÃO GLOBAL: Passa os valores calculados exatos para a unitel.php
    $_SESSION['checkout_produto']  = $titulo_exibicao;
    $_SESSION['checkout_valor']    = $valor_total;
    $_SESSION['checkout_parceiro'] = $parceiro_nome;

    // Se o pagamento for por carteira digital, avança para o terminal do PIN telefónico
    if ($metodo_pagamento === 'unitel_money' || $metodo_pagamento === 'mcx_xpress') {
        echo "<script>window.location.href = 'unitele.php?gateway=" . $metodo_pagamento . "';</script>";
        exit();
    }

} catch (PDOException $e) {
    if ($metodo_pagamento === 'unitel_money' || $metodo_pagamento === 'mcx_xpress') {
        echo "<script>window.location.href = 'unitele.php?gateway=" . $metodo_pagamento . "';</script>";
        exit();
    }
}
?>
<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8">
    <title>Pedido Confirmado - Grupo Aurelius</title>
    <style>
        body { background: #0f172a; color: #fff; font-family: sans-serif; text-align: center; padding: 50px 20px; }
        .sucesso-box { max-width: 600px; margin: 0 auto; background: #111827; padding: 40px; border-radius: 12px; border: 1px solid #22c55e; box-shadow: 0 10px 25px rgba(0,0,0,0.4); }
        .btn-voltar { display: inline-block; margin-top: 25px; background: #0284c7; color: white; padding: 12px 24px; border-radius: 6px; text-decoration: none; font-weight: bold; text-transform: uppercase; font-size: 12px; }
    </style>
</head>
<body>
    <div class="sucesso-box">
        <h1 style="color: #22c55e; margin-top: 0;">🎉 Requisição Registada!</h1>
        <p>O seu pedido de compra foi processado para a empresa: <strong style="color: #38bdf8;"><?= htmlspecialchars($parceiro_nome) ?></strong>.</p>
        
        <div style="text-align: left; background: #1e293b; padding: 20px; border-radius: 8px; margin: 20px 0;">
            <p style="margin-bottom: 8px;">📦 <strong>Artigo:</strong> <?= $titulo_exibicao ?> (<?= $qtd_solicitada ?> Un.)</p>
            <p>💰 <strong>Total a Pagar:</strong> <span style="color: #eab308; font-weight: bold;"><?= number_format($valor_total, 2, ',', '.') ?> Kz</span></p>
        </div>

        <?php if ($metodo_pagamento === 'qr_code'): ?>
            <div style="margin: 20px 0; padding: 15px; background: #fff; color: #000; display: inline-block; border-radius: 8px; font-weight: bold;">
                🔲 [ CÓDIGO QR DISPONÍVEL NA ENTREGA ]
                <p style="font-size:11px; margin-top:4px; font-weight:normal; color:#555;">Valida o scan apenas após receber o produto físico.</p>
            </div>
        <?php endif; ?>
        
        <br>
        <a href="Principal.php" class="btn-voltar">Voltar à Página Principal</a>
    </div>
</body>
</html>