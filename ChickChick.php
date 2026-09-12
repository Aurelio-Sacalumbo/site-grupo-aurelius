<?php
if(!isset($_SESSION)){
    session_start();
}

date_default_timezone_set('Africa/Luanda');

// Utiliza o arquivo de conexão mestre
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
        $erro[] = "E-mail e Senha são obrigatórios.";
    }

    // 🔮 CORREÇÃO 1: Verifica se o e-mail já existe na tabela de CLIENTES
    $sql_check = "SELECT id FROM clientes WHERE email = '$email'";
    $query_check = $mysqli->query($sql_check);
    if($query_check && $query_check->num_rows > 0){
        $erro[] = "Este e-mail já está cadastrado como cliente.";
    }

    if(count($erro) == 0){
        // Criptografia MD5 dupla padrão do ecossistema Aurélius
        $senha_cripto = md5(md5($senha));

        // 🔮 CORREÇÃO 2: Grava estritamente na tabela CLIENTES com as colunas certas do seu banco
        $sql_code = "INSERT INTO clientes (nome, email, telefone, senha, endereco, data_cadastro) 
                     VALUES ('$nome', '$email', '$telefone', '$senha_cripto', '$endereco', CURRENT_DATE())";

        if($mysqli->query($sql_code)){
            $sucesso = true;
        } else {
            $erro[] = "Falha ao cadastrar cliente no banco de dados: " . $mysqli->error;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cadastro de Usuário</title>
    <style>
        body { font-family: Arial, sans-serif; padding: 20px;  background-image: url('1755959614013.jpg'); background-size: 100%; background-repeat:none; }
        .container { max-width: 500px; background:transparent; padding: 20px; border-radius: 8px; box-shadow: 0 0 10px rgba(0,0,0,0.1); margin: 0 auto; border: 1px solid black;}
        .campo-form { margin-bottom: 15px; }
        .campo-form label { font-weight: bold; display: block; margin-bottom: 5px; }
        .campo-form input { width: 100%; padding: 8px; border: 1px solid #ccc; border-radius: 4px; box-sizing: border-box; }
        .erro-msg { color: red; font-weight: bold; }
        .sucesso-msg { color: green; font-weight: bold; }
        .btn-enviar { background-color:rgb(2, 162, 255); color: white; border: none; cursor: pointer; font-size: 16px; margin-top: 10px; margin-left: 140px; width: 50%; padding: 10px; border-radius: 4px; }
        .btn-enviar:hover { background-color: #218838;
        button a
        }
    </style>
</head>
<body>

<div class="container">
    <h2>Formulário de Cadastro</h2>

    <?php
    if(count($erro) > 0){
        foreach($erro as $msg){
            echo "<p class='erro-msg'>$msg</p>";
        }
    }

    if($sucesso){
        echo "<script>alert('Cadastro efetuado com sucesso!'); location.href='Login_ChickChick.php';</script>";
    }
    ?>

    <form action="index.php" method="POST">
        
        <div class="campo-form">
            <label for="nome">Nome Completo:</label>
            <input type="text" name="nome" id="nome" required placeholder="">
        </div>

        <div class="campo-form">
            <label for="email">E-mail:</label>
            <input type="email" name="email" id="email" required placeholder="">
        </div>

        <div class="campo-form">
            <label for="telefone">Telefone:</label>
            <input type="text" name="telefone" id="telefone" placeholder="">
        </div>

        <div class="campo-form">
            <label for="senha">Palavra-passe:</label>
            <input type="password" name="senha" id="senha" required placeholder="">
        </div>

        <div class="campo-form">
            <label for="endereco">Endereço:</label>
            <input type="text" name="endereco" id="endereco" placeholder="">
        </div>

        <input type="submit" name="cadastrar" value="Cadastrar" class="btn-enviar"> <br>
        <button><a href="Principal.php"> Voltar</a> </button>
        
    </form>
</div>

</body>
</html>