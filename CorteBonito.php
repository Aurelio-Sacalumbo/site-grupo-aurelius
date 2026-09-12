<?php
// Inicia a sessão se ela ainda não existir
if(!isset($_SESSION)){
    session_start();
}

// Utiliza o arquivo de conexão que já está a funcionar
include("conect.php");

$erro = array(); 

if(isset($_POST['email']) && strlen($_POST['email']) > 0){
    
    // Protege contra SQL Injection
    $email = $mysqli->escape_string($_POST['email']);
    // Aplica a mesma criptografia dupla utilizada no Cadastro.php
    $senha = md5(md5($_POST['senha']));

    // Busca o usuário na tabela correta utilizando a coluna 'codigo'
    $sql_code = "SELECT codigo, senha, email FROM usuario WHERE email = '$email'";
    $_sql_query = $mysqli->query($sql_code) or die ($mysqli->error);
    
    $dado = $_sql_query->fetch_assoc();
    $total = $_sql_query->num_rows; 

    if($total == 0){
        $erro[] = "Este e-mail não pertence a nenhum usuário.";
    } else {
        // Verifica se a senha criptografada coincide com a do banco
        if($dado['senha'] == $senha){
            
            // Guarda o ID do usuário logado na sessão para controle interno
            $_SESSION['usuario'] = $dado['codigo'];
            $_SESSION['email']   = $dado['email'];

            // Redireciona com sucesso para o seu index.html
            echo "<script>alert('Login efetuado com sucesso!'); location.href='Dashboard.php';</script>";
            exit();

        } else {
            $erro[] = "Senha incorreta.";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login do Sistema</title>
    <style>
    /* Fundo e Centralização Geral */
    body {  border:2px solid white;
        font-family: Arial, sans-serif; 
        background-color: #f4f4f9; 
        margin: 0;
        display: flex;
        justify-content: center;
        align-items: center;
        min-height: 80vh;
    }

    /* Bloco Mãe com espaçamento expandido entre os lados */
    .dois { 
        display: flex;
        width: 100%;
        max-width: 1100px; /* Aumentou a largura total da linha */
        margin: 20px;
        align-items: center;
        justify-content: space-between;
        gap: 80px; /* Maior separação física entre a foto e o formulário */
    }

    /* Lado Esquerdo - Caixa da Foto */
    .div1 { 
        flex: 1;
        display: flex;
        justify-content: center;
        background: #fff;
        padding: 30px;
        border-radius: 12px;
        box-shadow: 0 4px 15px rgba(0,0,0,0.05);
    }

    .imagem img { margin-left:250px;
        width: 100%; 
        max-width: 420px;
        height: auto; 
        border-radius: 8px;
    }

    /* Lado Direito - Caixa do Login Expandida */
    .container {
        flex: 1.2;
        background: #14424b;
        padding: 50px 40px; 
        border-radius: 12px;
        box-shadow: 0 4px 15px rgba(0,0,0,0.05);
    }

    .container h2 {  border:2px solid white;
        margin: 0 0 30px 0;
        color: #fff; text-align: center;
        font-size: 26px;
    }

    .campo-form { 
        margin-bottom: 22px; 
    }

    .campo-form label {  margin-left:250px;
        font-weight: bold; 
        display: block; 
        margin-bottom: 10px; 
        font-size: 15px;
    }

    /* 🌟 INPUTS MAIORES (AUMENTO DE LARGURA, COMPRIMENTO E TEXTO) */
    .campo-form input { 
        width: 60%;  margin-left:250px;
        padding: 16px 20px; /* Aumentou consideravelmente o comprimento vertical (altura) e interno */
        border: 1px solid #ccc; 
        border-radius: 8px; 
        box-sizing: border-box; 
        font-size: 16px; /* Letras maiores para facilitar a leitura */
    }

    .campo-form input:focus {
        border-color: #007bff;
        outline: none;
    }

    /* 🌟 BOTÃO ENTRAR MAIOR E MAIS LONGO */
    .btn-enviar { 
        width: 50%;  margin-left:320px;
        background-color: #007bff; 
        color: white; 
        border: none; 
        padding: 16px; /* Aumentou a altura do botão para acompanhar os inputs */
        cursor: pointer; 
        font-size: 18px; 
        border-radius: 8px; 
        font-weight: bold;
        margin-top: 15px;
    }

    .btn-enviar:hover { 
        background-color: #005; 
    }

    .links { 
        text-align: center; 
        margin-top: 25px; 
        font-size: 14px;
        color: #666;
    }

    .links a { 
        color: #ffff; 
        text-decoration: none; 
        font-weight: bold;
    }

    .links a:hover { 
        text-decoration: underline; 
    }
</style>
</head>
<body>

<div class="container">
    <h2>Aceder ao Sistema</h2>

    <?php
    // Exibe mensagens de erro caso as credenciais falhem
    if(count($erro) > 0){
        foreach($erro as $msg){
            echo "<p class='erro-msg'>$msg</p>";
        }
    }
    ?>

    <form action="" method="POST">
        
        <div class="campo-form">
            <label for="email">E-mail:</label>
            <input type="email" name="email" id="email" required placeholder="e-mail" value="<?php echo isset($_POST['email'])? htmlspecialchars($_POST['email']) : ''; ?>">
        </div>

        <div class="campo-form">
            <label for="senha">Senha:</label>
            <input type="password" name="senha" id="senha" required placeholder="Digite a sua senha">
        </div>

        <button type="submit" class="btn-enviar">Entrar</button>
        
        <div class="links">
            <p><a href="#">Esqueceu a sua senha?</a></p>
            <p>Não tem conta? <a href="Cadastrar.php">Registe-se aqui</a></p>
        </div>
    </form>
</div>

</body>
</html>