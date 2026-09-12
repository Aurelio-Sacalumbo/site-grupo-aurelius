<?php
if (!isset($_SESSION)) { session_start(); }
$mysqli = new mysqli("127.0.0.1", "root", "", "aurelius_salao");

if (!isset($_GET['id'])) { header("Location: Dashboard.php"); exit(); }

$id_produto = (int)$_GET['id'];
$metodo = isset($_GET['metodo']) ? $_GET['metodo'] : 'multicaixa';

$query = $mysqli->query("SELECT * FROM `produtos_cosmeticos` WHERE `id` = '$id_produto'");
$produto = $query->fetch_assoc();

if (!$produto) { die("Produto indisponível."); }

$preco_final = $produto['preco'];
if ($metodo == 'unitel') {
    $preco_final = $produto['preco'] * 0.80; // Aplica o desconto VIP Unitel
}
?>
<!DOCTYPE html>
<html lang="pt-PT">
<head>
    <meta charset="UTF-8">
    <title>Aurelius - Checkout Seguro</title>
    <style>
        body { background-color: #0b1a30; color: #fff; font-family: sans-serif; padding: 40px; }
        .box { max-width: 450px; margin: 0 auto; background: #0f172a; padding: 30px; border-radius: 12px; border: 1px solid #1e293b; text-align: center; }
        .btn { display: block; background: #22c55e; color: white; padding: 12px; border-radius: 6px; font-weight: bold; text-decoration: none; margin-top: 15px; border: none; width: 100%; cursor: pointer; }
        .input { width: 100%; padding: 12px; background: #1e293b; border: 1px solid #38bdf8; border-radius: 6px; color: white; margin-top: 10px; text-align: center; font-size: 16px; }
    </style>
</head>
<body>

<div class="box">
    <h2 style="color: #38bdf8;">Confirmar Pagamento</h2>
    <p style="margin: 15px 0;">Está a adquirir: <strong><?php echo htmlspecialchars($produto['nome_produto']); ?></strong></p>
    <p style="font-size: 22px; color: #eab308; font-weight: bold;"><?php echo number_format($preco_final, 2, ',', '.'); ?> Kz</p>

    <?php if ($metodo == 'unitel'): ?>
        <!-- FLUXO UNITEL MONEY -->
        <div style="background: #111e36; padding: 15px; border-radius: 8px; border: 1px solid #22c55e; margin-top: 20px;">
            <p style="font-size: 14px; color: #22c55e;">📱 Pagamento Instantâneo via Carteira Móvel</p>
            <form action="processar_carteira.php" method="POST">
                <input type="hidden" name="produto_id" value="<?php echo $id_produto; ?>">
                <input type="hidden" name="valor" value="<?php echo $preco_final; ?>">
                <input type="tel" name="telefone" class="input" placeholder="Nº de Telefone Unitel (Ex: 925...)" required pattern="(925|935)[0-9]{6}">
                <button type="submit" class="btn">Pagar via Unitel Money</button>
            </form>
        </div>
    <?php else: ?>
        <!-- FLUXO MULTICAIXA -->
        <div style="background: #111e36; padding: 15px; border-radius: 8px; border: 1px solid #0088cc; margin-top: 20px; text-align: left;">
            <p style="font-size: 14px; color: #38bdf8; text-align: center; font-weight: bold; margin-bottom: 10px;"> Referência Multicaixa Gerada</p>
            <p><strong>Entidade:</strong> 00321</p>
            <p><strong>Referência:</strong> <?php echo rand(100,999) . " " . rand(100,999) . " " . rand(100,999); ?></p>
            <p><strong>Valor:</strong> <?php echo number_format($preco_final, 2, ',', '.'); ?> Kz</p>
            <p style="font-size: 11px; color: #a1a1aa; margin-top: 10px; text-align: center;">Após efetuar o pagamento no ATM ou Multicaixa Express, o seu comprovativo é validado e o produto fica disponível no balcão.</p>
        </div>
    <?php endif; ?>
    
    <a href="Dashboard.php" style="display: block; margin-top: 20px; color: #a1a1aa; font-size: 13px; text-decoration: none;">← Voltar à Barbearia</a>
</div>

</body>
</html>