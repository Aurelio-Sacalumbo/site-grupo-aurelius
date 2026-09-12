<?php
// painel_parceiro.php - Painel de Gerência Exclusivo do Parceiro Hospedado
include("conect.php");
if (session_status() === PHP_SESSION_NONE) { session_start(); }

// Garante que um parceiro não aceda às cegas sem login
if (!isset($_SESSION['parceiro_id'])) {
    header("Location: Login.php");
    exit();
}

$meu_id = $_SESSION['parceiro_id'];

// 📊 A MAGIA DO ISOLAMENTO: O SUM e o COUNT usam o ID dele, logo ele só vê o faturamento dele!
$query_caixa = $mysqli->prepare("SELECT COUNT(*) as total_atendimentos, SUM(valor) as faturamento FROM pagamentos WHERE id_parceiro = ?");
$query_caixa->bind_param("i", $meu_id);
$query_caixa->execute();
$meu_caixa = $query_caixa->get_result()->fetch_assoc();
?>
<!DOCTYPE html>
<html lang="pt-PT">
<head>
    <title>Minha Barbearia - Gerência</title>
    <style>
        body { background: #0f172a; color: white; font-family: sans-serif; padding: 30px; }
        .box { background: #1e293b; padding: 20px; border-radius: 8px; border-left: 4px solid #38bdf8; }
    </style>
</head>
<body>
    <h2>🏪 Bem-vindo ao seu Painel, <?php echo htmlspecialchars($_SESSION['parceiro_nome']); ?>!</h2>
    <p>Faça a gestão da sua barbearia hospedada na rede Aurélius.</p><br>

    <div class="box">
        <h3>📊 O Meu Faturamento Comercial</h3>
        <p>Atendimentos Realizados: <strong><?php echo $meu_caixa['total_atendimentos']; ?></strong></p>
        <p>Volume em Caixa: <strong style="color: #22c55e;"><?php echo number_format((float)$meu_caixa['faturamento'], 0, '', ' '); ?> Kz</strong></p>
    </div>
</body>
</html>