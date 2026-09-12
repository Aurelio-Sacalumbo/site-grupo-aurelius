<?php
// =========================================================================
// 💈 REGISTO UNIFICADO - SINCRONIZAÇÃO COMPLETA DE UTILIZADORES (SAAS)
// =========================================================================
if(!isset($_SESSION)){
    session_start();
}

date_default_timezone_set('Africa/Luanda');

// Liga à base de dados centralizada do Grupo Aurélius
include("conect.php");

$erro = array(); 
$sucesso = false;

if($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['cadastrar'])){
    
    $nome     = $mysqli->escape_string(trim($_POST['nome']));
    $email    = $mysqli->escape_string(trim($_POST['email']));
    $telefone = $mysqli->escape_string(trim($_POST['telefone']));
    $senha    = $_POST['senha'];
    $endereco = $mysqli->escape_string(trim($_POST['endereco']));

    if(empty($email) || empty($senha)){
        $erro[] = "E-mail e Palavra-passe são obrigatórios.";
    }

    // 🎯 SINCRONIZAÇÃO 1: Verifica a duplicação na tabela unificada 'usuario'
    $sql_check = "SELECT codigo FROM `usuario` WHERE `email` = '$email'";
    $query_check = $mysqli->query($sql_check);
    if($query_check && $query_check->num_rows > 0){
        $erro[] = "Este e-mail já está registado na plataforma.";
    }

    if(count($erro) == 0){
        // 🎯 SINCRONIZAÇÃO 2: MD5 Simples idêntico ao validador do teu BrancaLogin.php
        $senha_cripto = md5($senha);

        // 🎯 SINCRONIZAÇÃO 3: Grava diretamente na tabela 'usuario' com status visível
        $sql_code = "INSERT INTO `usuario` (nome, email, telefone, senha, endereco, transacao_status, visivel_no_site) 
                     VALUES ('$nome', '$email', '$telefone', '$senha_cripto', '$endereco', 'Confirmado', 1)";

        if($mysqli->query($sql_code)){
            $sucesso = true;
        } else {
            $erro[] = "Falha ao registar na base de dados: " . $mysqli->error;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="pt-PT">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Registo de Utilizador - Grupo Aurélius</title>
    <style>
        body { font-family: 'Segoe UI', Arial, sans-serif; padding: 20px; background-image: url('1778405875881.jpg'); background-size: cover; background-repeat: no-repeat; background-attachment: fixed; background-color: #0b0f19; display: flex; justify-content: center; align-items: center; min-height: 100vh; margin: 0; box-sizing: border-box; }
        .container { width: 100%; max-width: 450px; background: rgba(0, 24, 39, 0.95); padding: 40px; border-radius: 24px; box-shadow: 0 10px 25px rgba(0,0,0,0.5); border: 2px solid #22c55e; color: white; text-align: center; box-sizing: border-box; }
        .container h2 { margin: 0 0 25px 0; color: #ffffff; font-size: 22px; text-transform: uppercase; border-bottom: 2px solid rgba(34, 197, 94, 0.3); padding-bottom: 10px; }
        .campo-form { margin-bottom: 18px; text-align: left; }
        .campo-form label { font-weight: bold; display: block; margin-bottom: 8px; font-size: 13px; color: #a7f3d0; text-transform: uppercase; }
        .campo-form input { width: 100%; padding: 12px 16px; border: 1px solid #374151; border-radius: 20px; box-sizing: border-box; font-size: 14px; background: #0b0f19; color: #ffffff; outline: none; }
        .campo-form input:focus { border-color: #22c55e; }
        .erro-msg { background: rgba(239, 68, 68, 0.15); border: 1px solid #ef4444; color: #f87171; padding: 10px; border-radius: 15px; font-size: 13px; margin-bottom: 15px; font-weight: bold; text-align: left; }
        .btn-enviar { width: 100%; background: linear-gradient(135deg, #22c55e, #16a34a); color: white; border: none; padding: 14px; cursor: pointer; font-size: 15px; border-radius: 20px; font-weight: bold; margin-top: 10px; text-transform: uppercase; transition: 0.2s; outline: none; }
        .btn-enviar:hover { transform: translateY(-2px); box-shadow: 0 4px 12px rgba(34,197,94,0.3); }
        .btn-voltar { display: block; width: 100%; text-align: center; background: #374151; color: white; border: 1px solid #4b5563; padding: 12px; font-size: 14px; border-radius: 20px; font-weight: bold; text-decoration: none; margin-top: 12px; box-sizing: border-box; text-transform: uppercase; transition: 0.2s; }
        .btn-voltar:hover { background: #4b5563; }
    </style>
</head>
<body>

<div class="container">
    <h2>Criar Nova Conta</h2>

    <?php
    if(count($erro) > 0){
        foreach($erro as $msg){
            echo "<p class='erro-msg'>⚠️ " . htmlspecialchars($msg) . "</p>";
        }
    }

    if($sucesso){
        echo "<script>alert('🎉 Conta criada com sucesso no Grupo Aurélius! Efetue o seu login.'); window.location.href='SoTrançaLogin.php';</script>";
    }
    ?>

    <!-- 🎯 SINCRONIZAÇÃO 4: Submete para si mesmo para validar as credenciais antes de ir ao Dashboard -->
    <form action="" method="POST">
        
        <div class="campo-form">
            <label for="nome">Nome Completo:</label>
            <input type="text" name="nome" id="nome" required placeholder="nome completo">
        </div>

        <div class="campo-form">
            <label for="email">E-mail de Acesso:</label>
            <input type="email" name="email" id="email" required placeholder="email">
        </div>

        <div class="campo-form">
            <label for="telefone">Telemóvel:</label>
            <input type="text" name="telefone" id="telefone" placeholder="telefone">
        </div>

        <div class="campo-form">
            <label for="senha">Palavra-passe:</label>
            <input type="password" name="senha" id="senha" required placeholder="Crie uma senha segura">
        </div>

        <div class="campo-form">
            <label for="endereco">Endereço / Bairro:</label>
            <input type="text" name="endereco" id="endereco" placeholder="Endereço">
        </div>

        <button type="submit" name="cadastrar" class="btn-enviar"> Concluir Registo</button>
        <a href="Principal.php" class="btn-voltar">✕ Cancelar e Voltar</a>
        
    </form>
</div>

</body>
</html>