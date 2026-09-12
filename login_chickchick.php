<?php
if (!isset($_SESSION)) { 
    session_start(); 
}
include("conect.php");
$erro = array();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['entrar_chickchick'])) {
    $email = $mysqli->escape_string(trim($_POST['email']));
    $senha = md5(md5($_POST['senha']));

    // Consulta direta focada no Código 6 do Salão ChickChick
    $sql = "SELECT * FROM usuario WHERE email = '$email' AND senha = '$senha' AND codigo = 6";
    $query = $mysqli->query($sql);

    if ($query && $query->num_rows > 0) {
        $user = $query->fetch_assoc();
        $_SESSION['parceiro_id'] = $user['codigo'];
        $_SESSION['parceiro_nome'] = $user['nome'];
        header("Location: ChickChick.php");
        exit();
    } else {
        $erro[] = "Credenciais inválidas para o Salão ChickChick.";
    }
}
?>
<!DOCTYPE html>
<html lang="pt-PT">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Salão ChickChick</title>
    <style>
        body { 
            font-family: sans-serif; 
            background-color: #090514; 
            display: flex; 
            justify-content: center; 
            align-items: center; 
            min-height: 100vh; 
            margin: 0; 
        }
        
        /* 🔮 CONTENEDOR HEXAGONAL PRESERVADO COM RADIÂNCIA MAGENTA PULSANTE */
        .hexagon-wrap { 
            position: relative; 
            width: 460px; 
            height: 460px; 
            background: linear-gradient(135deg, #a855f7, #ec4899); 
            padding: 3px; 
            clip-path: polygon(50% 0%, 100% 25%, 100% 75%, 50% 100%, 0% 75%, 0% 25%); 
            animation: glowRoxo 3s infinite alternate; 
        }
        
        @keyframes glowRoxo { 
            0% { filter: drop-shadow(0 0 10px rgba(168, 85, 247, 0.5)); } 
            100% { filter: drop-shadow(0 0 30px rgba(236, 72, 153, 0.9)); } 
        }
        
        .hexagon-inner { 
            width: 100%; 
            height: 100%; 
            background: #120b24; 
            clip-path: polygon(50% 0%, 100% 25%, 100% 75%, 50% 100%, 0% 75%, 0% 25%); 
            display: flex; 
            flex-direction: column; 
            justify-content: center; 
            align-items: center; 
            padding: 40px; 
            box-sizing: border-box; 
            color: white; 
        }
        
        /* ❌ BOTÃO X FLUTUANTE ADAPTADO EXCLUSIVO PARA O TOPO DO HEXÁGONO */
        .btn-fechar-top {
            position: absolute;
            top: 40px;
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
        .btn-fechar-top:hover {
            transform: scale(1.1) rotate(90deg);
            background: #ef4444;
            box-shadow: 0 0 15px #ef4444;
        }

        h2 { font-size: 18px; color: #f472b6; text-transform: uppercase; margin: 25px 0 5px 0; letter-spacing: 1px; }
        .campo-form { width: 75%; margin-bottom: 12px; text-align: left; }
        .campo-form label { display: block; font-size: 11px; color: #f472b6; text-transform: uppercase; margin-bottom: 4px; font-weight: bold; text-align: center; }
        
        input { 
            width: 100%; 
            padding: 12px; 
            border: 1px solid #2d1b4e; 
            background: #090514; 
            color: white; 
            outline: none; 
            box-sizing: border-box; 
            font-size: 13px; 
            text-align: center; 
            border-radius: 4px; 
            transition: 0.3s;
        }
        input:focus { border-color: #ec4899; box-shadow: 0 0 8px rgba(236, 72, 153, 0.4); }
        
        .btn-enviar { width: 75%; padding: 14px; background: linear-gradient(135deg, #a855f7, #ec4899); color: white; border: none; font-weight: bold; cursor: pointer; text-transform: uppercase; font-size: 13px; margin-top: 5px; border-radius: 4px; transition: 0.2s; }
        .btn-enviar:hover { background: linear-gradient(135deg, #c084fc, #f472b6); transform: translateY(-1px); }
        
        .erro-msg { color: #f87171; font-size: 11px; margin-bottom: 8px; font-weight: bold; width: 75%; text-align: center; }
        .links { margin-top: 15px; font-size: 11px; text-align: center; }
        .links a { color: #ec4899; text-decoration: none; font-weight: bold; }
        .links a:hover { text-decoration: underline; }
        .links p { margin: 4px 0; }
    </style>
</head>
<body>

<div class="hexagon-wrap">
    <!-- ❌ BOTÃO X EMBUTIDO DENTRO DA ÁREA DO POLÍGONO -->
    <a href="Principal.php" class="btn-fechar-top" title="Voltar ao Portal">✕</a>

    <div class="hexagon-inner">
        <h2>Acessar ao site</h2>
        <p style="font-size: 10px; color: #64748b; margin: 0 0 15px 0; text-transform: uppercase; font-weight: bold;">✨ ChickChick Estética Neon</p>

        <?php if(isset($erro) && is_array($erro) && count($erro) > 0) { foreach($erro as $msg) { echo "<p class='erro-msg'>⚠️ " . htmlspecialchars($msg) . "</p>"; } } ?>

        <form action="" method="POST" style="width: 100%; display: flex; flex-direction: column; align-items: center;">
            <div class="campo-form">
                <label for="email">E-mail do Cliente:</label>
                <input type="email" name="email" id="email" required placeholder="Digite o seu e-mail" value="<?php echo isset($_POST['email'])? htmlspecialchars($_POST['email']) : ''; ?>">
            </div>
            <div class="campo-form">
                <label for="senha">Palavra-passe:</label>
                <input type="password" name="senha" id="senha" required placeholder="Digite a sua palavra-passe">
            </div>
            
            <button type="submit" name="entrar_chickchick" class="btn-enviar">Entrar no Sistema</button>
            
            <div class="links">
                <p><a href="recuperar.php">Esqueceu a sua senha?</a></p>
                <p style="color: #64748b;">Não tem conta de cliente? <a href="Cadastrar.php">Registe-se aqui</a></p>
            </div>
        </form>
    </div>
</div>

</body>
</html>