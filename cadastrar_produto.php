<?php
include_once("Conexao.php");
if (session_status() === PHP_SESSION_NONE) { session_start(); }

// 🔑 Captura o ID da barbearia ativa na sessão (Ajustado para o teu Salão ID 20)
$id_barbearia_logada = isset($_SESSION['codigo_usuario']) ? intval($_SESSION['codigo_usuario']) : (isset($_SESSION['id_usuario']) ? intval($_SESSION['id_usuario']) : 20);

$mensagem = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['executar_cadastro_produto'])) {
    
    // 🟢 UNIÃO E SINCRONIA: O PHP lê exatamente os mesmos nomes definidos nas tags do formulário HTML
    $nome_produto   = isset($_POST['nome_produto']) ? htmlspecialchars(trim($_POST['nome_produto'])) : 'Cosmético Geral';
    $preco          = isset($_POST['preco']) ? floatval($_POST['preco']) : 0.00;
    $stock_atual    = isset($_POST['stock_atual']) ? intval($_POST['stock_atual']) : 1;
    $tamanho        = isset($_POST['tamanho']) ? htmlspecialchars(trim($_POST['tamanho'])) : 'Padrão';
    $cor_branca     = isset($_POST['cor_branca']) ? htmlspecialchars($_POST['cor_branca']) : 'Tem';
    $stock_status   = isset($_POST['stock_status']) ? htmlspecialchars($_POST['stock_status']) : 'Disponível';
    $categoria      = isset($_POST['categoria_cosmetico']) ? htmlspecialchars($_POST['categoria_cosmetico']) : 'Geral';
    $outras_cores   = isset($_POST['outras_cores']) ? htmlspecialchars(trim($_POST['outras_cores'])) : '';
    $local_retirada = isset($_POST['local_retirada']) ? htmlspecialchars(trim($_POST['local_retirada'])) : 'Empresa X';

    // 🖼️ Upload de Imagem Única
    $nome_imagem = "default_cosmetico.jpg";
    if (isset($_FILES['imagem']) && $_FILES['imagem']['error'] === 0) {
        $extensao = strtolower(pathinfo($_FILES['imagem']['name'], PATHINFO_EXTENSION));
        $nome_imagem = "prod_" . time() . "_" . uniqid() . "." . $extensao;
        if (!is_dir("uploads")) { mkdir("uploads", 0775, true); }
        move_uploaded_file($_FILES['imagem']['tmp_name'], "uploads/" . $nome_imagem);
    }

    try {
        // Formata os campos adicionais dinâmicos dentro da coluna tamanho para filtros na vitrina
        $detalhes_unificados = "Tam: " . $tamanho . " | Cat: " . $categoria . " | Cores: " . $outras_cores . " | Locais: " . $local_retirada;

        /* 
           🟢 GRAVAÇÃO DIRETA NA SUA TABELA REAL:
           Usa exatamente as colunas oficiais do seu banco de dados:
           empresa_id, nome_produto, preco, stock_atual, imagem, tamanho, cor_branca, stock, desconto_relampago
        */
        $sqlInsert = "INSERT INTO produtos_cosmeticos (empresa_id, nome_produto, preco, stock_atual, imagem, tamanho, cor_branca, stock, desconto_relampago) 
                      VALUES (:empresa_id, :nome_produto, :preco, :stock_atual, :imagem, :tamanho, :cor, :stock, 0)";
        
        $stmt = $pdo->prepare($sqlInsert);
        $stmt->execute([
            ':empresa_id'   => $id_barbearia_logada,
            ':nome_produto' => $nome_produto,
            ':preco'        => $preco,
            ':stock_atual'  => $stock_atual,
            ':imagem'       => $nome_imagem,
            ':tamanho'      => $detalhes_unificados,
            ':cor'          => $cor_branca,
            ':stock'        => $stock_status
        ]);
        
        $mensagem = "✅ Produto registado com sucesso! " . $stock_atual . " unidades adicionadas ao stock da Barbearia.";
    } catch (PDOException $e) {
        $mensagem = "❌ Erro ao processar o cadastro: " . $e->getMessage();
    }
}
?>
<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8">
    <title>Cadastrar Produto - Grupo Aurelius</title>
    <style>
        body { background: #0f172a; color: #fff; font-family: 'Segoe UI', sans-serif; padding: 20px; }
        .box-form { max-width: 550px; margin: 20px auto; background: #111827; padding: 30px; border-radius: 12px; border: 1px solid #233147; box-shadow: 0 10px 25px rgba(0,0,0,0.4); }
        .campo { margin-bottom: 16px; text-align: left; }
        label { display: block; margin-bottom: 6px; color: #94a3b8; font-size: 12px; text-transform: uppercase; font-weight: bold; }
        input, select { width: 100%; padding: 12px; background: #0f172a; color: #fff; border: 1px solid #1e293b; border-radius: 6px; box-sizing: border-box; }
        button { background: #0284c7; color: white; padding: 14px; border: none; border-radius: 6px; width: 100%; font-weight: bold; cursor: pointer; text-transform: uppercase; }
        .alert { background: #1e293b; padding: 12px; border-radius: 6px; border: 1px solid #38bdf8; color: #38bdf8; text-align: center; font-weight: bold; margin-bottom: 15px; }
    </style>
</head>
<body>
    <div class="box-form">
        <h2 style="color: #38bdf8; margin-top: 0; text-align: center; font-size: 18px; text-transform: uppercase; letter-spacing: 0.5px;">📦 ADICIONAR COSMÉTICO</h2>
        <hr style="border-color: #233147; margin: 15px 0;">

        <?php if(!empty($mensagem)): ?> 
            <div class="alert"><?= $mensagem ?></div> 
        <?php endif; ?>
        
        <form action="cadastrar_produto.php" method="POST" enctype="multipart/form-data">
            <input type="hidden" name="executar_cadastro_produto" value="1">

            <div class="campo">
                <label>Designação do Produto:</label>
                <input type="text" name="nome_produto" required placeholder="Ex: Cera Modeladora Efeito Matte">
            </div>
            <div class="campo">
                <label>Preço de Venda (Kz):</label>
                <input type="number" step="0.01" name="preco" required placeholder="Ex: 4500">
            </div>
            <div class="campo">
                <label>Quantidade em Stock Inicial:</label>
                <input type="number" name="stock_atual" value="10" min="1" required>
            </div>
            <div class="campo">
                <label>Categoria de Filtro:</label>
                <select name="categoria_cosmetico">
                    <option value="Ceras">Ceras & Pomadas</option>
                    <option value="Oleos">Óleos de Barba / Cabelo</option>
                    <option value="Shampoo">Champôs e Tratamentos</option>
                    <option value="Acessorios">Linhas e Agulhas</option>
                </select>
            </div>
            <div class="campo">
                <label>Volume / Tamanho:</label>
                <input type="text" name="tamanho" required placeholder="Ex: 100g, 150g, 30ml">
            </div>
            <div class="campo">
                <label>Disponibilidade da Cor Branca:</label>
                <select name="cor_branca">
                    <option value="Tem">Tem em Stock</option>
                    <option value="Não Tem">Não Tem</option>
                </select>
            </div>
            <div class="campo">
                <label>Outras Cores (Opcional):</label>
                <input type="text" name="outras_cores" placeholder="Ex: Preto, Azul, Castanho">
            </div>
            <div class="campo">
                <label>Locais Disponíveis de Distribuição:</label>
                <input type="text" name="local_retirada" value="Disponível nas Empresas X, Y e Z">
            </div>
            <div class="campo">
                <label>Estado do Stock:</label>
                <select name="stock_status">
                    <option value="Disponível">Disponível</option>
                    <option value="Esgotado">Esgotado</option>
                </select>
            </div>
            <div class="campo">
                <label>Fotografia Única do Produto:</label>
                <input type="file" name="imagem" accept="image/*" required>
            </div>
            <button type="submit">Cadastrar no Sistema</button>
            <a href="Dashboard.php" style="display:block; text-align:center; margin-top:15px; color:#94a3b8; text-decoration:none; font-size:13px;">← Voltar ao Painel da Barbearia</a>
        </form>
    </div>
</body>
</html>