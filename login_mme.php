<?php
// =========================================================================
// 🔥 LOGIN_MME.PHP - DESIGN PREMIUM DE FOGO OPERACIONAL (CÓDIGO 10)
// =========================================================================
if(!isset($_SESSION)){ session_start(); }
include("conect.php");
$erro = array(); 

if($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['email'])){
    $email = $mysqli->escape_string(trim($_POST['email']));
    $senha = md5(md5($_POST['senha']));

    // Verifica estritamente o código 10 da empresa MMMM no seu banco de dados
    $sql = "SELECT * FROM `usuario` WHERE email = '$email' AND senha = '$senha' AND codigo = 10";
    $query = $mysqli->query($sql);
    
    if ($query && $query->num_rows > 0) {
        $dado = $query->fetch_assoc();
        $_SESSION['parceiro_id']   = $dado['codigo'];
        $_SESSION['parceiro_nome'] = $dado['nome'];
        header("Location: dashboard.php");
        exit();
    } else { 
        $erro[] = "Credenciais inválidas para o painel de fogo MMMM."; 
    }
}
?>
<!DOCTYPE html>
<html lang="pt-PT">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - MMMM Fire Premium</title>
    <style>
        body { 
            font-family: 'Segoe UI', Arial, sans-serif; 
            background-color: #050200; /* Fundo cinza vulcânico profundo */
            display: flex; 
            justify-content: center; 
            align-items: center; 
            min-height: 100vh; 
            margin: 0; 
            overflow-x: hidden;
        }
        
        /* 🔮 CAIXA COESORA COM RADIÂNCIA DE FOGO VIVO E ANIMAÇÃO DE BRASAS */
        .login-fire { 
            position: relative;
            background: #140800; /* Preto brasado */
            padding: 50px 40px; 
            border-radius: 20px; 
            border: 2px solid #ff4500; /* Laranja fogo */
            text-align: center; 
            color: white; 
            width: 100%; 
            max-width: 440px; 
            box-sizing: border-box;
            box-shadow: 0 0 20px rgba(255, 69, 0, 0.4), inset 0 0 15px rgba(255, 165, 0, 0.1); 
            animation: pulsarFogo 3.5s infinite alternate; 
        }

        /* Animação CSS que simula a pulsação de calor das chamas */
        @keyframes pulsarFogo { 
            0% { box-shadow: 0 0 15px rgba(255, 69, 0, 0.3), 0 0 30px rgba(255, 140, 0, 0.1); border-color: #d32f2f; } 
            100% { box-shadow: 0 0 30px rgba(255, 69, 0, 0.7), 0 0 55px rgba(255, 165, 0, 0.4); border-color: #ff8c00; } 
        }
        
        /* ❌ BOTOÃO X FLUTUANTE ESTILO BRASA ACESA */
        .btn-fechar-top {
            position: absolute;
            top: -15px;
            right: -15px;
            width: 36px;
            height: 36px;
            background: linear-gradient(135deg, #ff4500, #b30000);
            color: white;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            text-decoration: none;
            font-weight: bold;
            font-size: 16px;
            box-shadow: 0 0 12px rgba(255, 69, 0, 0.6);
            border: 2px solid #140800;
            transition: 0.3s ease;
        }
        .btn-fechar-top:hover {
            transform: scale(1.1) rotate(90deg);
            background: #ff6624;
            box-shadow: 0 0 20px #ff4500;
        }

        h2 { color: #ff8c00; text-transform: uppercase; margin: 0 0 5px 0; font-size: 22px; letter-spacing: 1px; }
        .campo-form { margin-bottom: 22px; text-align: left; }
        .campo-form label { display: block; font-size: 12px; color: #ffa500; text-transform: uppercase; margin-bottom: 6px; font-weight: bold; letter-spacing: 0.5px; }
        
        input { 
            width: 100%; 
            padding: 15px; 
            border-radius: 8px; 
            border: 1px solid #3e1c00; 
            background: #090300; 
            color: white; 
            outline: none; 
            box-sizing: border-box; 
            font-size: 15px; 
            text-align: center;
            transition: 0.3s;
        }
        input:focus { border-color: #ff8c00; box-shadow: 0 0 10px rgba(255, 140, 0, 0.4); background: #140700; }
        
        /* Botão Gradiente de Magma Incandescente */
        .btn-enviar { 
            width: 100%; 
            padding: 16px; 
            background: linear-gradient(135deg, #ff4500, #ff8c00); 
            color: white; 
            border: none; 
            border-radius: 8px; 
            font-weight: bold; 
            cursor: pointer; 
            text-transform: uppercase; 
            font-size: 15px; 
            margin-top: 10px;
            box-shadow: 0 4px 12px rgba(255, 69, 0, 0.3);
            transition: 0.3s;
            letter-spacing: 0.5px;
        }
        .btn-enviar:hover { background: linear-gradient(135deg, #ff6624, #ffb300); transform: translateY(-2px); box-shadow: 0 6px 18px rgba(255, 69, 0, 0.5); }
        
        .erro-msg { background: rgba(239, 68, 68, 0.15); border: 1px solid #ef4444; color: #f87171; padding: 12px; border-radius: 6px; font-size: 13px; margin-bottom: 20px; font-weight: bold; text-align: left; }
        
        .links { margin-top: 30px; font-size: 13px; border-top: 1px dashed #3e1c00; padding-top: 20px; }
        .links a { color: #ff8c00; text-decoration: none; font-weight: bold; }
        .links a:hover { color: #ffb300; text-decoration: underline; }
        .links p { margin: 5px 0; }
    </style>
</head>
<body>

    <div class="login-fire">
        <!-- ❌ BOTÃO X DE VOLTAR REATIVO -->
        <a href="Principal.php" class="btn-fechar-top" title="Voltar ao Portal">✕</a>

        <h2>Acessar ao site</h2>
        <p style="font-size: 11px; color: #94a3b8; margin: 0 0 25px 0; font-weight: bold; text-transform: uppercase; letter-spacing: 0.5px;">🔥 MMMM Estética Incandescente</p>

        <?php if(count($erro) > 0) { foreach($erro as $msg) { echo "<p class='erro-msg'>⚠️ $msg</p>"; } } ?>
        
        <form action="" method="POST">
            <div class="campo-form">
                <label for="email">E-mail do Cliente:</label>
                <input type="email" name="email" id="email" required placeholder="Digite o seu e-mail" value="<?php echo isset($_POST['email'])? htmlspecialchars($_POST['email']) : ''; ?>">
            </div>
            <div class="campo-form">
                <label for="senha">Palavra-passe:</label>
                <input type="password" name="senha" id="senha" required placeholder="Digite a sua senha">
            </div>
            <button type="submit" class="btn-enviar">Entrar no Sistema</button>
            
            <div class="links">
                <p><a href="recuperar.php">Esqueceu a sua senha?</a></p>
                <p style="color: #64748b;">Não tem conta de cliente? <a href="Cadastrar.php">Registe-se aqui</a></p>
            </div>
        </form>
    </div>

</body>
</html>