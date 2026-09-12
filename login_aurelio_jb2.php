<?php
// =========================================================================
// ✨ BRILHO.PHP - DESIGN PREMIUM SHIMMER GLOW (CÓDIGO 5)
// =========================================================================
if(!isset($_SESSION)){ session_start(); }
include("conect.php");
$erro = array(); 

if($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['email'])){
    $email = $mysqli->escape_string(trim($_POST['email']));
    $senha = md5(md5($_POST['senha']));

    // Busca na tabela de usuários para autenticar o parceiro
    $sql = "SELECT * FROM `usuario` WHERE email = '$email' AND senha = '$senha' AND codigo = 5";
    $query = $mysqli->query($sql);
    
    if ($query && $query->num_rows > 0) {
        $dado = $query->fetch_assoc();
        $_SESSION['parceiro_id']   = $dado['codigo'];
        $_SESSION['parceiro_nome'] = $dado['nome'];
        header("Location: dashboard.php");
        exit();
    } else { 
        $erro[] = "Credenciais inválidas para o painel Salão Brilha."; 
    }
}
?>
<!DOCTYPE html>
<html lang="pt-PT">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Salão Brilha Premium</title>
    <style>
        body { font-family: 'Segoe UI', Arial, sans-serif; background-color: #06020b; display: flex; justify-content: center; align-items: center; min-height: 100vh; margin: 0; overflow: hidden; }
        
        /* 🔮 CAIXA COESORA COM BRILHO RADIANTE DE PURPURINA E DIAMANTE */
        .login-shimmer { 
            position: relative;
            background: #11051c; 
            padding: 50px 40px; 
            border-radius: 20px; 
            border: 2px solid #e9d5ff; 
            text-align: center; 
            color: white; 
            width: 100%; 
            max-width: 440px; 
            box-sizing: border-box;
            box-shadow: 0 0 20px rgba(233, 213, 255, 0.4), inset 0 0 15px rgba(233, 213, 255, 0.1); 
            animation: pulsarBrilho 3s infinite alternate; 
        }

        @keyframes pulsarBrilho { 
            0% { box-shadow: 0 0 15px rgba(233, 213, 255, 0.3); border-color: #c084fc; } 
            100% { box-shadow: 0 0 35px rgba(217, 70, 239, 0.6), 0 0 50px rgba(168, 85, 247, 0.2); border-color: #f472b6; } 
        }
        
        .btn-fechar-top {
            position: absolute;
            top: -15px;
            right: -15px;
            width: 36px;
            height: 36px;
            background: linear-gradient(135deg, #c084fc, #f472b6);
            color: #06020b;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            text-decoration: none;
            font-weight: bold;
            font-size: 16px;
            box-shadow: 0 0 12px #c084fc;
            border: 2px solid #11051c;
            transition: 0.3s ease;
        }
        .btn-fechar-top:hover { transform: scale(1.1) rotate(90deg); background: #f472b6; color: white; }

        h2 { color: #f472b6; text-transform: uppercase; margin: 0 0 5px 0; font-size: 22px; letter-spacing: 1px; }
        .campo-form { margin-bottom: 22px; text-align: left; }
        .campo-form label { display: block; font-size: 12px; color: #e9d5ff; text-transform: uppercase; margin-bottom: 6px; font-weight: bold; letter-spacing: 0.5px; }
        
        input { width: 100%; padding: 15px; border-radius: 8px; border: 1px solid #4a1d96; background: #06020b; color: white; outline: none; box-sizing: border-box; font-size: 15px; text-align: center; }
        input:focus { border-color: #f472b6; box-shadow: 0 0 10px rgba(244, 114, 182, 0.4); }
        
        .btn-enviar { width: 100%; padding: 16px; background: linear-gradient(135deg, #c084fc, #f472b6); color: #06020b; border: none; border-radius: 8px; font-weight: bold; cursor: pointer; text-transform: uppercase; font-size: 15px; margin-top: 10px; box-shadow: 0 4px 12px rgba(168, 85, 247, 0.3); }
        .btn-enviar:hover { background: linear-gradient(135deg, #f472b6, #ec4899); color: white; transform: translateY(-2px); }
        .erro-msg { background: rgba(239, 68, 68, 0.15); border: 1px solid #ef4444; color: #f87171; padding: 12px; border-radius: 6px; font-size: 13px; margin-bottom: 20px; font-weight: bold; }
        
        .links { margin-top: 30px; font-size: 13px; border-top: 1px dashed #4a1d96; padding-top: 20px; }
        .links a { color: #c084fc; text-decoration: none; font-weight: bold; }
    </style>
</head>
<body>

    <div class="login-shimmer">
        <a href="principal.php" class="btn-fechar-top" title="Voltar ao Portal">✕</a>
        <h2>Acessar ao site</h2>
        <p style="font-size: 11px; color: #94a3b8; margin: 0 0 25px 0; font-weight: bold; text-transform: uppercase; letter-spacing: 0.5px;">✨ Salão Brilha Diamante</p>

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