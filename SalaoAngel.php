<?php
if(!isset($_SESSION)){
    session_start();
}

include("conect.php");

$erro = array(); 

if($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['email'])){
    $email = $mysqli->escape_string(trim($_POST['email']));
    $senha = md5(md5($_POST['senha']));

    // 🔮 BUSCA ISOLADA: Altere o número do código para corresponder ao ID real desta nova empresa no banco
    $sql_code = "SELECT * FROM `usuario` WHERE email = '$email' AND senha = '$senha'";
    $query = $mysqli->query($sql_code);
    
    if ($query && $query->num_rows > 0) {
        $dado = $query->fetch_assoc();
        $_SESSION['parceiro_id']   = $dado['codigo'];
        $_SESSION['parceiro_nome'] = $dado['nome'];
        
        // Redireciona direto para o painel operacional dela
        header("Location: dashboard.php");
        exit();
    } else {
        $erro[] = "Credenciais incorretas para este painel.";
    }
}
?>
<!DOCTYPE html>
<html lang="pt-PT">
<head>
    <meta charset="UTF-8">
    <title>Login Exclusivo</title>
    <!-- Insira aqui o seu CSS radiante (redondo, quadrado ou curvo) -->
</head>
<body>
    <!-- Estrutura do seu formulário HTML -->
</body>
</html>