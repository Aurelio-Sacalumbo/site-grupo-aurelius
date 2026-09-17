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
    <title>Login - Barbearia LookNovo</title>
    <style>
        body { 
            font-family: 'Segoe UI', Arial, sans-serif; 
            background-color: #0c0a09; /* Fundo pedra escurecido */
            display: flex; 
            justify-content: center; 
            align-items: center; 
            min-height: 100vh; 
            margin: 0; 
            overflow: hidden; 
        }
        
        /* 🔮 CONTAINER EM LOSANGO/DIAMANTE COM RADIÂNCIA DOIRADA NEON PULSANTE */
        .diamond-outer {
            position: relative;
            width: 480px;
            height: 480px;
            background: #1c1917;
            transform: rotate(45deg); /* Gira a moldura externa para fazer a forma de losango */
            display: flex;
            justify-content: center;
            align-items: center;
            border: 2px solid #f59e0b;
            box-shadow: 0 0 20px rgba(245, 158, 11, 0.4), 0 0 40px rgba(245, 158, 11, 0.1);
            animation: glowOuro 3.5s infinite alternate;
        }

        @keyframes glowOuro {
            0% { box-shadow: 0 0 15px rgba(245, 158, 11, 0.3); border-color: #d97706; }
            100% { box-shadow: 0 0 35px rgba(245, 158, 11, 0.7); border-color: #fbbf24; }
        }

        /* Desfaz a rotação no conteúdo interno para o formulário e textos ficarem direitos */
        .diamond-content {
            transform: rotate(-45deg);
            width: 100%;
            text-align: center;
            color: white;
            padding: 30px;
            box-sizing: border-box;
        }
        
        /* ❌ BOTOÃO X REATIVO EM FORMATO LOSANGO PEQUENO ALINHADO */
        .btn-fechar-top {
            position: absolute;
            top: 25px;
            right: 25px;
            width: 32px;
            height: 32px;
            background: linear-gradient(135deg, #ef4444, #dc2626);
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            text-decoration: none;
            font-weight: bold;
            font-size: 14px;
            box-shadow: 0 0 10px rgba(239, 68, 68, 0.5);
            border: 1px solid #f59e0b;
            transition: 0.3s ease;
            z-index: 1000;
        }
        .btn-fechar-top:hover {
            transform: scale(1.1) rotate(90deg);
            background: #ef4444;
        }

        h2 { font-size: 18px; color: #fbbf24; text-transform: uppercase; margin: 0 0 15px 0; letter-spacing: 0.5px; }
        .campo-form { width: 85%; margin: 0 auto 12px auto; text-align: left; }
        .campo-form label { display: block; font-size: 11px; color: #fde047; text-transform: uppercase; margin-bottom: 4px; text-align: center; font-weight: bold; }
        
        input { 
            width: 100%; 
            padding: 12px; 
            border: 1px solid #2e2a24; 
            background: #0c0a09; 
            color: white; 
            outline: none; 
            box-sizing: border-box; 
            font-size: 14px; 
            text-align: center; 
            border-radius: 0px; /* Mantém os inputs retos combinando com o losango */
        }
        input:focus { border-color: #fbbf24; box-shadow: 0 0 8px rgba(245, 158, 11, 0.4); }
        
        .btn-enviar { 
            width: 85%; 
            padding: 14px; 
            background: linear-gradient(135deg, #f59e0b, #d97706); 
            color: #0c0a09; 
            border: none; 
            font-weight: bold; 
            cursor: pointer; 
            text-transform: uppercase; 
            font-size: 14px; 
            margin-top: 10px; 
            box-shadow: 0 4px 10px rgba(234,179,8,0.2);
            transition: 0.2s; 
        }
        .btn-enviar:hover { background: #fbbf24; transform: translateY(-1px); box-shadow: 0 6px 15px rgba(234,179,8,0.4); }
        
        .erro-msg { color: #f87171; font-size: 12px; margin-bottom: 15px; font-weight: bold; }
        
        /* 📋 LINKS ATIVOS INTEGRADOS NA ESTRUTURA INTERNA */
        .links { margin-top: 20px; font-size: 12px; border-top: 1px dashed #2e2a24; padding-top: 15px; }
        .links a { color: #fbbf24; text-decoration: none; font-weight: bold; }
        .links a:hover { text-decoration: underline; }
        .links p { margin: 4px 0; }
    </style>
</head>
<body>

<div class="diamond-outer">
    <div class="diamond-content">
        <!-- ❌ BOTÃO X DE VOLTAR PARA A TELA INICIAL -->
        <a href="Principal.php" class="btn-fechar-top" title="Voltar ao Portal Público">✕</a>

        <h2>Acessar ao site</h2>
        <p style="font-size: 10px; color: #a8a29e; margin: 0 0 20px 0; text-transform: uppercase; font-weight: bold; letter-spacing: 0.5px;">💈 LookNovo Clássica</p>

        <?php if(count($erro) > 0) { foreach($erro as $msg) { echo "<p class='erro-msg'>⚠️ " . htmlspecialchars($msg) . "</p>"; } } ?>

        <form action="login_looknovo.php" method="POST">
            <div class="campo-form">
                <label for="email">E-mail do Cliente:</label>
                <input type="email" name="email" id="email" required placeholder="Digite o seu e-mail" value="<?php echo isset($_POST['email'])? htmlspecialchars($_POST['email']) : ''; ?>">
            </div>
            <div class="campo-form">
                <label for="senha">Palavra-passe:</label>
                <input type="password" name="senha" id="senha" required placeholder="••••••••">
            </div>
            
            <!-- 🚀 GATILHO COMPACTADO COM O ATRIBUTO NAME ATIVO -->
            <button type="submit" name="entrar_looknovo" class="btn-enviar">Entrar no Sistema</button>
            
            <div class="links">
                <p><a href="recuperar.php">Esqueceu a sua senha?</a></p>
                <p style="color: #a8a29e;">Não tem conta de cliente? <a href="Cadastrar.php">Registe-se aqui</a></p>
            </div>
        </form>
    </div>
</div>

</body>
</html>