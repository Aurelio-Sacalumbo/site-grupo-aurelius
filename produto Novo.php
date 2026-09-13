<?php
if (session_status() === PHP_SESSION_NONE) { 
    session_start(); 
}
date_default_timezone_set('Africa/Luanda');

// Conexão Dinâmica Híbrida (Local + Nuvem)
$db_host = getenv('DB_HOST') ?: "127.0.0.1";
$db_user = getenv('DB_USER') ?: "root";
$db_pass = getenv('DB_PASSWORD') ?: "";
$db_name = getenv('DB_NAME') ?: "aurelius_salao";

$mysqli = @new mysqli($db_host, $db_user, $db_pass, $db_name);

if ($mysqli->connect_error) { 
    die("<div style='padding:20px; background:#ffdddd; color:#aa0000; font-family:sans-serif;'>
            <strong>Erro de Infraestrutura:</strong> Ligação ao banco de dados recusada no Render.
         </div>"); 
}
$mysqli->set_charset("utf8mb4");

$mensagem_feedback = "";

// =========================================================================
// 🚀 PROCESSADOR REATIVO: CADASTRO DO PRODUTO (SINTAXE 100% CORRIGIDA)
// ==========================================================================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['nome_produto'])) {
    $empresa_id   = intval($_POST['loja_destino_id']);
    $nome_produto = $mysqli->real_escape_string(trim($_POST['nome_produto']));
    $preco        = floatval($_POST['preco']);
    $stock_atual  = intval($_POST['stock_atual']);
    
    // Tratamento de Upload de Imagem — Constante corrigida para UPLOAD_ERR_OK
    $nome_imagem = "default.png";
    if (isset($_FILES['foto_produto']) && $_FILES['foto_produto']['error'] === UPLOAD_ERR_OK) {
        $extensao = pathinfo($_FILES['foto_produto']['name'], PATHINFO_EXTENSION);
        $nome_imagem = "prod_" . time() . "_" . uniqid() . "." . $extensao;
        
        // Garante a existência da pasta upload
        if (!is_dir("upload")) { mkdir("upload", 0777, true); }
        move_uploaded_file($_FILES['foto_produto']['tmp_name'], "upload/" . $nome_imagem);
    }

    if ($empresa_id > 0 && !empty($nome_produto)) {
        // Inserção exata alinhada às colunas mapeadas no seu phpMyAdmin
        $sql_insert = "INSERT INTO produtos_cosmeticos (empresa_id, nome_produto, preco, stock_atual, imagem, data_cadastro) 
                       VALUES ($empresa_id, '$nome_produto', $preco, $stock_atual, '$nome_imagem', NOW())";
        
        if ($mysqli->query($sql_insert)) {
            echo "<script>
                    alert('✓ Produto direcionado e publicado com sucesso no stock da empresa!'); 
                    window.location.href='" . $_SERVER['PHP_SELF'] . "';
                  </script>";
            exit();
        } else {
            $mensagem_feedback = "Erro ao registar produto: " . $mysqli->error;
        }
    } else {
        $mensagem_feedback = "Por favor, preencha todos os campos obrigatórios.";
    }
}


// =========================================================================
// 🔌 QUERY COESORA: MONTA O SELETOR COMBINANDO AS TABELAS DO SEU SAAS
// =========================================================================
// Captura os códigos e nomes das tabelas 'usuario' e 'lojas' para não misturar dados
$sql_seletor = "
    (SELECT codigo as id, nome as nome_loja FROM usuario WHERE nivel = 'parceiro_hospedado' AND transacao_status = 'Confirmado')
    UNION
    (SELECT id as id, nome_loja as nome_loja FROM lojas WHERE visivel_no_site = 1)
    ORDER BY nome_loja ASC
";
$query_seletor_lojas = $mysqli->query($sql_seletor);
?>
<!DOCTYPE html>
<html lang="pt-PT">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>Aurelius Business - Direcionar Produto</title>
    <style>
        body { background-color: #070b12; color: #fff; font-family: 'Segoe UI', system-ui, -apple-system, sans-serif; padding: 15px 12px; display: flex; align-items: center; justify-content: center; min-height: 100vh; margin: 0; box-sizing: border-box; }
        .form-container { max-width: 480px; width: 100%; background: #0f172a; padding: 25px 20px; border-radius: 16px; border: 1px solid #1e293b; box-shadow: 0 10px 30px rgba(0,0,0,0.5); box-sizing: border-box; }
        .input-campo { width: 100%; padding: 12px 14px; background: #070b12; border: 1px solid #1e293b; border-radius: 8px; color: white; margin-bottom: 18px; margin-top: 5px; box-sizing: border-box; font-size: 14px; outline: none; transition: border-color 0.2s; font-family: inherit; }
        .input-campo:focus { border-color: #38bdf8; box-shadow: 0 0 8px rgba(56, 189, 248, 0.15); }
        .btn-enviar { width: 100%; background: linear-gradient(135deg, #38bdf8, #0284c7); color: #ffffff; font-weight: bold; padding: 14px; border: none; border-radius: 8px; cursor: pointer; font-size: 12px; text-transform: uppercase; letter-spacing: 0.5px; box-shadow: 0 4px 12px rgba(2, 132, 199, 0.3); transition: all 0.2s ease; outline: none; font-family: inherit; }
        .btn-enviar:hover { transform: translateY(-1px); background: linear-gradient(135deg, #4fcbff, #0294e0); }
        .label-premium { font-size: 11px; color: #38bdf8; font-weight: bold; text-transform: uppercase; letter-spacing: 0.5px; display: block; text-align: left; }
    </style>
</head>
<body>

<div class="form-container">
    <h3 style="color: #fff; margin-top: 0; margin-bottom: 5px; font-weight: bold; text-transform: uppercase; font-size: 16px; letter-spacing: 0.5px; border-left: 3px solid #38bdf8; padding-left: 8px;">🚀 Direcionar Novo Cosmético</h3>
    <p style="color: #64748b; font-size: 12.5px; margin: 0 0 22px 0; text-align: left; line-height: 1.4;">Escolha a empresa ou salão de destino para isolar os dados e evitar misturas na vitrina principal.</p>
    
    <?php if(!empty($mensagem_feedback)): ?>
        <div style="background: rgba(239, 68, 68, 0.1); border: 1px solid #f87171; padding: 10px; border-radius: 6px; color: #f87171; margin-bottom: 15px; font-size: 13px; text-align: center;"><?php echo $mensagem_feedback; ?></div>
    <?php endif; ?>

    <form method="POST" action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>" enctype="multipart/form-data">
        
        <!-- 🟢 SELETOR DE LOJAS DINÂMICO CONECTADO ÀS VARIÁVEIS DO BANCO -->
        <label class="label-premium" style="color: #eab308;">Para qual Empresa deseja enviar este produto?</label>
        <select name="loja_destino_id" class="input-campo" style="border-color: #ca8a04; background: #070b12; cursor: pointer;" required>
            <option value="" style="color: #64748b;">-- Escolha a Empresa de Destino --</option>
            <?php 
            if ($query_seletor_lojas && $query_seletor_lojas->num_rows > 0):
                while($loja_opc = $query_seletor_lojas->fetch_assoc()): 
            ?>
                    <option value="<?php echo $loja_opc['id']; ?>">🏬 <?php echo htmlspecialchars($loja_opc['nome_loja']); ?></option>
            <?php 
                endwhile; 
            endif;
            ?>
        </select>

        <label class="label-premium">Nome do Produto / Artigo:</label>
        <input type="text" name="nome_produto" class="input-campo" placeholder="Ex: Pomada Efeito Matte Elegance" required>

        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px;">
            <div>
                <label class="label-premium">Preço de Venda (Kz):</label>
                <input type="number" step="0.01" name="preco" class="input-campo" placeholder="4500" required>
            </div>
            <div>
                <label class="label-premium">Quantidade Stock:</label>
                <input type="number" name="stock_atual" class="input-campo" placeholder="12" required>
            </div>
        </div>

        <label class="label-premium">Fotografia Real do Produto:</label>
        <input type="file" name="foto_produto" class="input-campo" accept="image/*" style="border: 1px dashed #334155; background: #070b12; padding: 10px; cursor: pointer; margin-bottom: 22px;">

        <button type="submit" class="btn-enviar">Disponibilizar no Balcão Escolhido ⚡</button>
        
        <div style="text-align: center; margin-top: 20px;">
            <a href="Principal.php" style="color: #64748b; text-decoration: none; font-size: 11px; font-weight: bold; text-transform: uppercase; letter-spacing: 0.5px; transition: color 0.2s;">← Retornar à Vitrina Pública</a>
        </div>
    </form>
</div>

</body>
</html>