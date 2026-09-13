<?php
// =========================================================================
// 🌍 CENTRAL DE COMPRAS & MARKETPLACE MULTI-LOJAS SAAS - GRUPO AURÉLIUS
// =========================================================================
if (session_status() === PHP_SESSION_NONE) { 
    session_start(); 
}
date_default_timezone_set('Africa/Luanda');

// Conexão Dinâmica Híbrida (Local + Nuvem)
$db_host = getenv('DB_HOST') ?: "127.0.0.1";
$db_user = getenv('DB_USER') ?: "root";
$db_pass = getenv('DB_PASSWORD') ?: "";
$db_name = getenv('DB_NAME') ?: "aurelius_salao";
$db_port = getenv('DB_PORT') ?: "3306";

$mysqli = mysqli_init();
if (!@mysqli_real_connect($mysqli, $db_host, $db_user, $db_pass, $db_name, (int)$db_port)) {
    $mysqli = @mysqli_connect("127.0.0.1", "root", "", "aurelius_salao");
}

if (!$mysqli || mysqli_connect_errno()) { 
    die("<div style='padding:20px; background:#0f172a; color:#ef4444; font-family:sans-serif;'>
            <strong>Erro de Infraestrutura:</strong> Ligação ao banco de dados recusada.
         </div>"); 
}
$mysqli->set_charset("utf8mb4");

$mensagem_feedback = "";

// =========================================================================
// 🚀 PROCESSADOR REATIVO: CADASTRO DO PRODUTO (SINTAXE GERAL CORRIGIDA)
// ==========================================================================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['nome_produto'])) {
    // 🟢 ALINHAMENTO CRÍTICO: Captura os nomes exatos enviados pelos inputs do formulário HTML
    $id_empresa   = intval($_POST['loja_destino_id']);
    $nome_produto = $mysqli->real_escape_string(trim($_POST['nome_produto']));
    $preco        = floatval($_POST['preco']);
    $quantidade   = intval($_POST['stock_atual']); 
    
    // Nome padrão caso não seja feito o upload de imagem
    $imagem_nome = "default_cosmetico.jpg"; 
    
    // Processamento real do upload do ficheiro binário de imagem
    if (isset($_FILES['foto_produto']) && $_FILES['foto_produto']['error'] === UPLOAD_ERR_OK) {
        $extensao = strtolower(pathinfo($_FILES['foto_produto']['name'], PATHINFO_EXTENSION));
        if (in_array($extensao, ['jpg', 'jpeg', 'png', 'webp', 'gif'])) {
            $imagem_nome = "prod_" . time() . "_" . uniqid() . "." . $extensao;
            $pasta_destino = __DIR__ . "/uploads/";
            if (!is_dir($pasta_destino)) {
                mkdir($pasta_destino, 0777, true);
            }
            move_uploaded_file($_FILES['foto_produto']['tmp_name'], $pasta_destino . $imagem_nome);
        }
    }
    
    if ($id_empresa > 0 && !empty($nome_produto) && $preco > 0) {
        // 🟢 ENGINE UNIFICADO: Injeta o stock real simultaneamente em 'stock_atual' e em 'stock'
        $sql_inserir_novo = "INSERT INTO `produtos_cosmeticos` 
            (`empresa_id`, `nome_produto`, `preco`, `stock_atual`, `stock`, `imagem`, `tamanho`, `cor_branca`, `data_cadastro`) 
            VALUES 
            ('$id_empresa', '$nome_produto', '$preco', '$quantidade', '$quantidade', '$imagem_nome', 'Padrão', 'Tem', NOW())";
        
        if ($mysqli->query($sql_inserir_novo) || $stmt_baixa->affected_rows > 0) {
            // 🟢 Injeta um token de tempo único no redirecionamento para quebrar a cache
            $token_atualizacao = time();
            echo "<script>alert('✓ Transação processada e stock atualizado no balcão!'); window.location.href='Principal.php?refresh=" . $token_atualizacao . "';</script>";
            exit();
        } else {
            $mensagem_feedback = "🚨 Erro ao gravar na base de dados: " . $mysqli->error;
        }
    } else {
        $mensagem_feedback = "🚨 Por favor, preencha todos os campos obrigatórios com valores válidos.";
    }
}

// =========================================================================
// 🔌 QUERY COESORA: MONTA O SELETOR COMBINANDO AS TABELAS DO SEU SAAS
// =========================================================================
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