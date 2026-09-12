<?php
if (!isset($_SESSION)) { session_start(); }

$mysqli = new mysqli("127.0.0.1", "root", "", "aurelius_salao");
$mysqli->set_charset("utf8");

// 🛡️ BARREIRA DE ACESSO: Expulsa invasores sem login ativo
if (!isset($_SESSION['parceiro_id']) || $_SESSION['parceiro_nivel'] === 'admin_mestre') {
    header("Location: Login.php");
    exit();
}

$meu_id_sessao = $_SESSION['parceiro_id'];

// 🔮 DESCENTRALIZAÇÃO OPERACIONAL: O banco só retorna a linha correspondente ao ID logado no instante!
$query_perfil = $mysqli->query("SELECT * FROM `usuario` WHERE `codigo` = '$meu_id_sessao'");
$meus_dados = $query_perfil->fetch_assoc();
?>
<!DOCTYPE html>
<html lang="pt-PT">
<head>
    <meta charset="UTF-8">
    <title>Painel - <?php echo htmlspecialchars($meus_dados['nome']); ?></title>
    <style>
        body { font-family: sans-serif; background-color: #0f172a; color: white; padding: 40px; }
        .caixa-restrita { background: #1e293b; padding: 30px; border-radius: 12px; border-left: 4px solid #38bdf8; max-width: 600px; }
    </style>
</head>
<body>

    <div class="caixa-restrita">
        <h2>🏪 Gerência Restrita: <?php echo htmlspecialchars($meus_dados['nome']); ?></h2>
        <p>Logado como responsável: <strong><?php echo htmlspecialchars($meus_dados['nome_funcionario'] ?? 'Gestor'); ?></strong></p>
        <hr style="border-color:#334155;">
        
        <p><strong>📍 Localização cadastrada:</strong> <?php echo htmlspecialchars($meus_dados['endereco']); ?></p>
        <p><strong>📞 Contacto telefónico:</strong> <?php echo htmlspecialchars($meus_dados['telefone']); ?></p>
        <p><strong>🌍 Tipo de Serviço:</strong> <?php echo htmlspecialchars($meus_dados['tipos_de_servico']); ?></p>
        <p><strong>💰 Taxa Comercial Acordada:</strong> <span style="color:#22c55e; font-weight:bold;"><?php echo number_format($meus_dados['preco'], 2, ',', '.'); ?> Kz</span></p>
        
        <br>
        <a href="logout.php" style="background:#ef4444; color:white; padding:10px 20px; border-radius:6px; text-decoration:none; font-weight:bold; font-size:13px; display:inline-block;">✕ Terminar Sessão</a>
    </div>

</body>
</html>