<?php
if (session_status() === PHP_SESSION_NONE) { session_start(); }
require_once __DIR__ . "/config/Banco.php";

// 🔒 SEGURANÇA: Garante que apenas parceiros logados adicionam produtos
if (!isset($_SESSION['loja_id']) && !isset($_SESSION['empresa_codigo'])) {
    die("Acesso negado. Faça login no seu painel de parceiro.");
}

// Captura o ID dinâmico da sessão do parceiro logado (Suporta Loja 238, 239, 240 ou futuras)
$id_parceiro_sessao = isset($_SESSION['loja_id']) ? intval($_SESSION['loja_id']) : intval($_SESSION['empresa_codigo']);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nome_prod = trim($_POST['nome_produto']);
    $preco_prod = (float)$_POST['preco'];
    $stock_prod = (int)$_POST['stock_atual'];
    $imagem_padrao = "default_cosmetico.jpg";

    if (!empty($nome_prod) && $preco_prod > 0) {
        // ⚡ AUTOMAÇÃO FUTURA: O campo empresa_id recebe o ID da sessão de forma cega e segura
        $stmt = $pdo->prepare("INSERT INTO `produtos_cosmeticos` (empresa_id, nome_produto, preco, stock_atual, imagem) VALUES (?, ?, ?, ?, ?)");
        $stmt->execute([$id_parceiro_sessao, $nome_prod, $preco_prod, $stock_prod, $imagem_padrao]);

        echo "<script>alert('✓ Produto cadastrado e vinculado automaticamente ao seu ID de Parceiro!'); window.location.href='extrato_parceiro.php';</script>";
        exit();
    }
}
?>
<!DOCTYPE html>
<html lang="pt-PT">
<head>
    <meta charset="UTF-8">
    <title>Aurelius Business - Cadastrar Cosmético</title>
    <style>
        body { background: #0f172a; color: #fff; font-family: sans-serif; padding: 40px; }
        .box { max-width: 450px; margin: 0 auto; background: #1e293b; padding: 25px; border-radius: 12px; border: 1px solid #334155; }
        .input { width: 100%; padding: 12px; background: #0f172a; border: 1px solid #38bdf8; border-radius: 6px; color: white; margin-bottom: 15px; margin-top: 5px; box-sizing: border-box; }
        .btn { width: 100%; background: #22c55e; color: #000; font-weight: bold; padding: 12px; border: none; border-radius: 6px; cursor: pointer; text-transform: uppercase; }
    </style>
</head>
<body>

<div class="box">
    <h3>🚀 Adicionar Produto ao Seu Catálogo</h3>
    <p style="color:#94a3b8; font-size:12px; margin-bottom:15px;">Identidade de Armazém ativa para o ID: <b><?= $id_parceiro_sessao ?></b></p>
    
    <form method="POST" action="">
        <label>Nome Comercial do Produto:</label>
        <input type="text" name="nome_produto" class="input" placeholder="Ex: Pomada Matte 100g" required>

        <label>Preço de Venda ao Cliente (AOA):</label>
        <input type="number" step="0.01" name="preco" class="input" placeholder="Ex: 4500" required>

        <label>Quantidade Física Disposta no Balcão:</label>
        <input type="number" name="stock_atual" class="input" placeholder="Ex: 20" required>

        <button type="submit" class="btn">Disponibilizar na App</button>
    </form>
</div>

</body>
</html>