<?php
include_once("Conexao.php");
$mensagem = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nome     = htmlspecialchars(trim($_POST['nome_empresa']));
    $telefone = htmlspecialchars(trim($_POST['telefone_empresa']));
    $endereco = htmlspecialchars(trim($_POST['endereco_fisico']));
    $slug     = strtolower(preg_replace('/[^a-zA-Z0-8]/', '', $nome)); // Cria o link do salão
    
    // Captura as coordenadas que o JavaScript recolheu do local físico da loja
    $lat = !empty($_POST['lat_gps']) ? floatval($_POST['lat_gps']) : -12.775800;
    $lon = !empty($_POST['lon_gps']) ? floatval($_POST['lon_gps']) : 15.739400;

    try {
        // Insere o parceiro real na tua tabela usuario com os parâmetros de aprovação
        $stmt = $pdo->prepare("
            INSERT INTO usuario (nome, endereco, telefone, slug, latitude, longitude, transacao_status, visivel_no_site) 
            VALUES (:nome, :end, :tel, :slug, :lat, :lon, 'Confirmado', 1)
        ");
        $stmt->execute([
            ':nome' => $nome, ':end' => $endereco, ':tel' => $telefone,
            ':slug' => $slug, ':lat' => $lat, ':lon' => $lon
        ]);
        $mensagem = "🎉 Cadastro efetuado com sucesso! A tua empresa já está ativa no Marketplace.";
    } catch (PDOException $e) {
        $mensagem = "❌ Erro ao registar empresa: " . $e->getMessage();
    }
}
?>
<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8">
    <title>Oficializar Parceiro - Grupo Aurelius</title>
    <style>
        body { background: #0f172a; color: #fff; font-family: sans-serif; padding: 20px; }
        .box-registo { max-width: 500px; margin: 30px auto; background: #111827; padding: 30px; border-radius: 12px; border: 1px solid #233147; }
        input { width: 100%; padding: 12px; margin: 6px 0 16px 0; background: #0f172a; color: #fff; border: 1px solid #1e293b; border-radius: 6px; box-sizing: border-box; }
        button { background: #22c55e; color: white; padding: 14px; border: none; border-radius: 6px; width: 100%; font-weight: bold; cursor: pointer; text-transform: uppercase; }
    </style>
</head>
<body>
    <div class="box-registo">
        <h2>Adicionar Empresa Real</h2>
        <?php if($mensagem): ?><p style="color:#38bdf8; font-weight:bold;"><?= $mensagem ?></p><?php endif; ?>
        
        <form method="POST">
            <input type="hidden" id="lat_gps" name="lat_gps" value="">
            <input type="hidden" id="lon_gps" name="lon_gps" value="">

            <label>Nome do Salão ou Fornecedor:</label>
            <input type="text" name="nome_empresa" required placeholder="Ex: Barbearia Branca Central">

            <label>Contacto de Telefone Oficial (Angola):</label>
            <input type="text" name="telefone_empresa" required placeholder="Ex: 9XXXXXXXX">

            <label>Endereço Físico Completo:</label>
            <input type="text" name="endereco_fisico" required placeholder="Bairro, Rua, Cidade...">

            <button type="submit">Oficializar Conta de Vendas</button>
        </form>
    </div>

    <script>
        // Captura a localização exata do salão no momento em que o dono se regista
        if (navigator.geolocation) {
            navigator.geolocation.getCurrentPosition(function(pos) {
                document.getElementById('lat_gps').value = pos.coords.latitude;
                document.getElementById('lon_gps').value = pos.coords.longitude;
            });
        }
    </script>
</body>
</html>