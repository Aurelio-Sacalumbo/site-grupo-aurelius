<?php
// Cadastrar.php - Formulário Oficial de Cadastro de Clientes (Grupo Aurélius)
if (!isset($_SESSION)) {
    session_start();
}
date_default_timezone_set('Africa/Luanda');
include("conect.php");

$erro = array(); 
$sucesso = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['cadastrar_cliente'])) {
    $nome  = $mysqli->escape_string(trim($_POST['nome']));
    $email = $mysqli->escape_string(trim($_POST['email']));
    $fone  = $mysqli->escape_string(trim($_POST['telefone']));
    $senha = $_POST['senha'];

    if (empty($nome) || empty($email) || empty($senha)) {
        $erro[] = "Nome, E-mail e Palavra-passe são obrigatórios.";
    }

    $sql_check = "SELECT codigo FROM clientes WHERE email = '$email'";
    $query_check = $mysqli->query($sql_check);
    if ($query_check && $query_check->num_rows > 0) {
        $erro[] = "Este e-mail de cliente já se encontra registado.";
    }

    if (count($erro) == 0) {
        $senha_cripto = md5(md5($senha));
        // Inserção direta passando o formato correto para não dar o erro do aviso amarelo do MySQL
        $sql_code = "INSERT INTO clientes (nome, email, telefone, senha, endereco, data_cadastro) 
                     VALUES ('$nome', '$email', '$fone', '$senha_cripto', 'Huambo', CURRENT_DATE())";

        if ($mysqli->query($sql_code)) {
            $sucesso = true;
        } else {
            $erro[] = "Falha ao gravar no banco: " . $mysqli->error;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="pt-PT">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Registe-se - Grupo Aurélius</title>
    <style>
        body { font-family: 'Segoe UI', Arial, sans-serif; background-color: #0b0f19; margin: 0; display: flex; justify-content: center; align-items: center; min-height: 100vh; color: white; }
        .container { width: 100%; max-width: 460px; background: #111827; padding: 45px; border-radius: 16px; border: 2px solid #38bdf8; box-shadow: 0 0 20px rgba(56, 189, 248, 0.4); text-align: center; animation: pulsar 3s infinite alternate; }
        @keyframes pulsar { 0% { box-shadow: 0 0 12px #0284c7; } 100% { box-shadow: 0 0 25px #38bdf8; } }
        h2 { margin-top:0; border-bottom: 2px solid rgba(56, 189, 248, 0.3); padding-bottom: 12px; font-size: 20px; text-transform: uppercase; color: #38bdf8; }
        .campo { margin-bottom: 15px; text-align: left; }
        .campo label { display: block; font-size: 11px; margin-bottom: 5px; text-transform: uppercase; color: #94a3b8; font-weight: bold; }
        input { width: 100%; padding: 12px 15px; border-radius: 8px; border: 1px solid #374151; background: #0b0f19; color: white; outline: none; box-sizing: border-box; font-size: 14px; }
        input:focus { border-color: #38bdf8; }
        button { width: 100%; padding: 15px; background: linear-gradient(135deg, #38bdf8, #0284c7); color: #0b0f19; border: none; border-radius: 8px; font-weight: bold; cursor: pointer; text-transform: uppercase; margin-top: 10px; }
        .erro-msg { background: rgba(239, 68, 68, 0.15); border: 1px solid #ef4444; color: #f87171; padding: 10px; border-radius: 6px; font-size: 13px; margin-bottom: 15px; font-weight: bold; text-align: left; }
        .links { margin-top: 25px; font-size: 13px; border-top: 1px dashed #374151; padding-top: 15px; }
        .links a { color: #38bdf8; text-decoration: none; font-weight: bold; }
    </style>
</head>
<body>
<div class="container">
    <h2>Criar Nova Conta</h2>
    <?php if(count($erro) > 0) { foreach($erro as $msg) { echo "<p class='erro-msg'>⚠️ $msg</p>"; } } ?>
    <?php if($sucesso) { echo "<script>alert('Conta criada com sucesso!'); window.location.href='Login.php';</script>"; exit(); } ?>
    <form action="" method="POST">
        <div class="campo"><label>Nome Completo:</label><input type="text" name="nome" required placeholder="Digite o seu nome"></div>
        <div class="campo"><label>E-mail Pessoal:</label><input type="email" name="email" required placeholder="exemplo@gmail.com"></div>
        <div class="campo"><label>Telefone / WhatsApp:</label><input type="text" name="telefone" placeholder="Ex: 925347370"></div>
        <div class="campo"><label>Defina uma Palavra-passe:</label><input type="password" name="senha" required placeholder="Crie uma senha de acesso"></div>
        <button type="submit" name="cadastrar_cliente">Finalizar Registo</button>
        <div class="links">
            <p style="color:#94a3b8;">Já tem uma conta ativa? <a href="Login.php">Faça login aqui</a></p>
        </div>
    </form>
</div>
</body>
</html>