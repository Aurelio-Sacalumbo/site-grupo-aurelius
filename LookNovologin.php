<?php
if (!isset($_SESSION)) { session_start(); }
include("conect.php");
$erro = array();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['entrar_angel'])) {
    $email = $mysqli->escape_string(trim($_POST['email']));
    $senha = md5(md5($_POST['senha']));

    // Verifica estritamente as credenciais do Salão Angel (ID 9)
    $sql = "SELECT * FROM usuario WHERE email = '$email' AND senha = '$senha' AND codigo = 9";
    $query = $mysqli->query($sql);

    if ($query && $query->num_rows > 0) {
        $user = $query->fetch_assoc();
        $_SESSION['parceiro_id'] = $user['codigo'];
        $_SESSION['parceiro_nome'] = $user['nome'];
        header("Location: LookNovologin.php");
        exit();
    } else {
        $erro[] = "Credenciais inválidas para o Salão Angel.";
    }
}
?>
<!DOCTYPE html>
<html lang="pt-PT">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Salão Angel</title>
    <style>
        body { font-family: 'Courier New', sans-serif; background-color: #111827; display: flex; justify-content: center; align-items: center; min-height: 100vh; margin: 0; }
        
        /* 🧱 DESIGN RIGIDAMENTE QUADRADO COM RADIÂNCIA OURO PULSANTE */
        .login-square { 
            background: #1f2937; 
            padding: 40px; 
            border-radius: 0px; 
            border: 4px solid #f59e0b; 
            text-align: left; 
            color: white; 
            width: 100%; 
            max-width: 420px; 
            box-sizing: border-box;
            box-shadow: 0 0 15px rgba(245, 158, 11, 0.4);
            animation: glowAngel 3s infinite alternate;
        }

        @keyframes glowAngel {
            0% { box-shadow: 0 0 12px rgba(245, 158, 11, 0.3); border-color: #d97706; }
            100% { box-shadow: 0 0 25px rgba(245, 158, 11, 0.7); border-color: #fbbf24; }
        }

        .login-square h2 {  
            border: 2px solid white;
            margin: 0 0 30px 0;
            color: #f59e0b; 
            text-align: center;
            font-size: 24px;
            padding: 10px;
            text-transform: uppercase;
        }

        .campo-form { 
            margin-bottom: 22px; 
            text-align: left;
        }

        .campo-form label {  
            font-weight: bold; 
            display: block; 
            margin-bottom: 10px; 
            font-size: 14px;
            color: #fde047;
            text-transform: uppercase;
        }

        input { 
            width: 100%;  
            padding: 14px; 
            border: 2px solid #4b5563; 
            border-radius: 0px; 
            background: #111827; 
            color: white; 
            outline: none; 
            box-sizing: border-box; 
            font-size: 15px; 
        }

        input:focus { 
            border-color: #fbbf24; 
            box-shadow: 0 0 8px rgba(245, 158, 11, 0.4);
        }

        .btn-enviar { 
            width: 100%; 
            padding: 16px; 
            background: linear-gradient(135deg, #f59e0b, #d97706); 
            color: #111827; 
            border: none; 
            border-radius: 0px; 
            font-weight: bold; 
            cursor: pointer; 
            text-transform: uppercase; 
            font-size: 16px; 
            margin-top: 10px;
            box-sizing: border-box;
        }
        .btn-fechar-top {
            position: absolute;
            top:100px;
            width: 32px;
            height: 32px;
            background: linear-gradient(135deg, #ec4899, #a855f7);
            color: white;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            text-decoration: none;
            font-weight: bold;
            font-size: 14px;
            box-shadow: 0 0 12px rgba(236, 72, 153, 0.6);
            border: 2px solid #120b24;
            transition: 0.3s ease;
            z-index: 1000;
        }
        .btn-enviar:hover { 
            background: #fbbf24; 
            transform: translateY(-1px);
        }

        .erro-msg { 
            background: rgba(239, 68, 68, 0.15);
            border: 1px solid #ef4444;
            color: #f87171; 
            padding: 12px;
            border-radius: 0px;
            font-size: 13px; 
            margin-bottom: 20px;
            font-weight: bold;
        }

        .links { 
            text-align: center; 
            margin-top: 25px; 
            font-size: 13px;
            border-top: 1px dashed #4b5563;
            padding-top: 15px;
        }

        .links a { 
            color: #fbbf24; 
            text-decoration: none; 
            font-weight: bold;
        }

        .links a:hover { 
            text-decoration: underline; 
        }
    </style>
</head>
<body>

<div class="login-square">
    <h2>Acessar ao site</h2>

    <?php
    if(isset($erro) && is_array($erro) && count($erro) > 0){
        foreach($erro as $msg){
            echo "<p class='erro-msg'>⚠️ " . htmlspecialchars($msg) . "</p>";
        }
    }
    ?>

    <form action="" method="POST">
        
        <div class="campo-form">
        <a href="Principal.php" class="btn-fechar-top" title="Voltar ao Portal">✕</a>
            <label for="email">E-mail do Cliente:</label>
            <input type="email" name="email" id="email" required placeholder="Digite o seu e-mail" value="<?php echo isset($_POST['email'])? htmlspecialchars($_POST['email']) : ''; ?>">
        </div>

        <div class="campo-form">
            <label for="senha">Palavra-passe:</label>
            <input type="password" name="senha" id="senha" required placeholder="Digite a sua senha">
        </div>

        <button type="submit" name="entrar_angel" class="btn-enviar">Entrar no Sistema</button>
        
        <div class="links">
            <p><a href="recuperar.php">Esqueceu a sua senha?</a></p>
            <p style="color: #9ca3af;">Não tem conta de cliente? <a href="LookNovoCadastro.php">Registe-se aqui</a></p>
        </div>
    </form>
</div>

</body>
</html>