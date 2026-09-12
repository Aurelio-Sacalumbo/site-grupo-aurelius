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
?>
<!DOCTYPE html>
<html lang="pt-PT">
<head>
    <meta charset="UTF-8">
    <title>Aurelius Business - Direcionar Produto</title>
    <style>
        body { background-color: #0b1a30; color: #fff; font-family: sans-serif; padding: 40px; display: flex; align-items: center; justify-content: center; min-height: 100vh; margin: 0; }
        .form-container { max-width: 480px; width: 100%; background: #0f172a; padding: 30px; border-radius: 12px; border: 1px solid #1e293b; box-shadow: 0 10px 30px rgba(0,0,0,0.5); }
        .input-campo { width: 100%; padding: 12px; background: #1e293b; border: 1px solid #38bdf8; border-radius: 6px; color: white; margin-bottom: 15px; margin-top: 5px; box-sizing: border-box; font-size: 14px; }
        .btn-enviar { width: 100%; background: #22c55e; color: #000; font-weight: bold; padding: 14px; border: none; border-radius: 6px; cursor: pointer; font-size: 14px; text-transform: uppercase; letter-spacing: 0.5px; box-shadow: 0 4px 12px rgba(34, 197, 94, 0.2); }
        .btn-enviar:hover { background: #16a34a; }
    </style>
</head>
<body>

<div class="form-container">
    <h3 style="color: #38bdf8; margin-top: 0; margin-bottom: 5px;">🚀 Direcionar Novo Cosmético</h3>
    <p style="color: #94a3b8; font-size: 12px; margin: 0 0 20px 0;">Escolha a loja de destino para isolar os dados e evitar misturas no Marketplace.</p>
    
    <?php if(!empty($mensagem_feedback)): ?>
        <div style="background: rgba(239, 68, 68, 0.1); border: 1px solid #f87171; padding: 10px; border-radius: 6px; color: #f87171; margin-bottom: 15px; font-size: 13px; text-align: center;"><?php echo $mensagem_feedback; ?></div>
    <?php endif; ?>

    <form method="POST" action="" enctype="multipart/form-data">
        
        <!-- 🟢 SELETOR DE LOJAS PARCEIRAS COM FECHAMENTO CORRIGIDO -->
        <label style="color: #eab308; font-weight: bold;">Para qual Loja deseja enviar este produto?</label>
        <select name="loja_destino_id" class="input-campo" style="border-color: #eab308;" required>
            <option value="">-- Escolha o Fornecedor de Destino --</option>
            <?php while($loja_opc = $query_seletor_lojas->fetch_assoc()): ?>
                <option value="<?php echo $loja_opc['id']; ?>">🏬 <?php echo htmlspecialchars($loja_opc['nome_loja']); ?></option>
            <?php endwhile; ?> <!-- 🟢 CORRIGIDO: Agora usa endwhile de forma simétrica -->
        </select>

        <label>Nome do Produto:</label>
        <input type="text" name="nome_produto" class="input-campo" placeholder="Ex: Pomada Efeito Matte Elegance" required>

        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px;">
            <div>
                <label>Preço de Venda (Kz):</label>
                <input type="number" step="0.01" name="preco" class="input-campo" placeholder="4500" required>
            </div>
            <div>
                <label>Quantidade em Stock:</label>
                <input type="number" name="stock_atual" class="input-campo" placeholder="12" required>
            </div>
        </div>

        <label>Fotografia Real do Produto:</label>
        <input type="file" name="foto_produto" class="input-campo" accept="image/*" style="border: 1px dashed #38bdf8; background: transparent; padding: 10px; cursor: pointer;">

        <button type="submit" class="btn-enviar">Disponibilizar na Loja Escolhida ⚡</button>
        
        <div style="text-align: center; margin-top: 15px;">
            <a href="Lojas.php" style="color: #64748b; text-decoration: none; font-size: 12px; font-weight: bold;">← Ver Vitrina Pública</a>
        </div>
    </form>
</div>

</body>
</html>