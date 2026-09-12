<?php
// =========================================================================
// 💧 ARELIULOGIN.PHP - LOGIN EXCLUSIVO DA EMPRESA AURÉLIO JB (SEM COBRANÇAS)
// =========================================================================
if(!isset($_SESSION)){
    session_start();
}

date_default_timezone_set('Africa/Luanda');

// Liga à base de dados centralizada do Grupo Aurélius
include("conect.php");

$erro = array(); 

if($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['entrar_aurelio'])){
    
    $email = $mysqli->escape_string(trim($_POST['email']));
    $senha = md5(md5($_POST['senha'])); // Criptografia MD5 dupla padrão do projeto

    // 🔮 MOTOR PREMIUM COM SELECT * (Busca estritamente os e-mails associados aos IDs 11 e 12 da Aurélio Jb)
    $sql_login = "SELECT * FROM `usuario` WHERE `email` = '$email' AND `senha` = '$senha' AND (`codigo` = 11 OR `codigo` = 12)";
    $query_login = $mysqli->query($sql_login);
    
    if ($query_login && $query_login->num_rows > 0) {
        $dado = $query_login->fetch_assoc();
        
        // Guarda as chaves dinâmicas na sessão do servidor
        $_SESSION['parceiro_id']   = $dado['codigo'];
        $_SESSION['parceiro_nome'] = $dado['nome'];
        
        // 🚀 LOGOU, ENTRA DIRETO NO DASHBOARD DA BARBEARIA
        header("Location: dashboard.php");
        exit();
    } else {
        $erro[] = "E-mail ou Palavra-passe incorretos para a empresa Aurélio JB.";
    }
}
?>
<!DOCTYPE html>
<html lang="pt-PT">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Aurélio JB Premium</title>
    <style>
        body { 
            font-family: 'Segoe UI', Arial, sans-serif;
            background-color: #020617; /* Fundo oceânico profundo */
            margin: 0;
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            box-sizing: border-box;
        }

        /* 🔮 CAIXA COESORA COM RADIÂNCIA DE ÁGUA LÍQUIDA E ANIMAÇÃO DE FLUXO */
        .container {
            position: relative;
            width: 100%;
            max-width: 440px;
            background: #070f2e; /* Azul marinho fechado */
            padding: 50px 40px; 
            border-radius: 24px; 
            box-sizing: border-box;
            text-align: center;
            color: white;
            border: 2px solid #06b6d4; /* Azul Ciano Fluido */
            box-shadow: 0 0 20px rgba(6, 182, 212, 0.4), inset 0 0 20px rgba(6, 182, 212, 0.1); 
            animation: pulsarAgua 4s infinite ease-in-out; 
        }

        /* Animação CSS que simula o movimento da água a escorrer e irradiar luz */
        @keyframes pulsarAgua { 
            0% { 
                box-shadow: 0 0 15px rgba(6, 182, 212, 0.3), inset 0 0 10px rgba(6, 182, 212, 0.2); 
                border-radius: 24px 40px 24px 40px;
                border-color: #0284c7;
            } 
            50% {
                border-color: #22d3ee;
            }
            100% { 
                box-shadow: 0 0 35px rgba(34, 211, 238, 0.6), inset 0 0 25px rgba(34, 211, 238, 0.3); 
                border-radius: 40px 24px 40px 24px;
                border-color: #06b6d4;
            } 
        }
        
        /* ❌ BOTÃO X FLUTUANTE ESTILO GOTA CRISTALINA COM RETORNO PARA O PORTAL */
        .btn-fechar-top {
            position: absolute;
            top: -15px;
            right: -15px;
            width: 36px;
            height: 36px;
            background: linear-gradient(135deg, #0284c7, #0369a1);
            color: white;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            text-decoration: none;
            font-weight: bold;
            font-size: 16px;
            box-shadow: 0 0 12px rgba(6, 182, 212, 0.6);
            border: 2px solid #070f2e;
            transition: 0.3s ease;
        }
        .btn-fechar-top:hover {
            transform: scale(1.1) rotate(90deg);
            background: #22d3ee;
            color: #070f2e;
            box-shadow: 0 0 20px #22d3ee;
        }

        .container h2 {  
            margin: 0 0 35px 0;
            color: #38bdf8; 
            font-size: 22px;
            padding: 10px;
            text-transform: uppercase;
            letter-spacing: 1px;
            border-bottom: 2px solid rgba(6, 182, 212, 0.3);
        }

        .campo-form { 
            margin-bottom: 22px; 
            text-align: left;
        }

        .campo-form label {  
            font-weight: bold; 
            display: block; 
            margin-bottom: 10px; 
            font-size: 13px;
            color: #7dd3fc;
            text-transform: uppercase;
        }

        .campo-form input { 
            width: 100%;  
            padding: 15px 18px; 
            border: 1px solid #1e293b; 
            border-radius: 20px; 
            box-sizing: border-box; 
            font-size: 15px; 
            background: #020617;
            color: #ffffff;
            outline: none;
            transition: 0.3s;
            text-align: center;
        }

        .campo-form input:focus {
            border-color: #38bdf8;
            box-shadow: 0 0 10px rgba(56, 189, 248, 0.4);
        }

        /* Botão Gradiente de Onda Líquida Ciano */
        .btn-enviar { 
            width: 100%;  
            background: linear-gradient(135deg, #0284c7, #38bdf8);
            color: #020617; 
            border: none; 
            padding: 16px; 
            cursor: pointer; 
            font-size: 16px; 
            border-radius: 20px; 
            font-weight: bold;
            margin-top: 15px;
            text-transform: uppercase;
            box-sizing: border-box;
            transition: 0.3s;
        }

        .btn-enviar:hover { 
            background: linear-gradient(135deg, #38bdf8, #22d3ee);
            transform: translateY(-2px);
            box-shadow: 0 6px 18px rgba(34, 211, 238, 0.6);
        }

        .erro-msg {
            background: rgba(239, 68, 68, 0.15);
            border: 1px solid #ef4444;
            color: #f87171;
            padding: 12px;
            border-radius: 6px;
            font-size: 13px;
            margin-bottom: 20px;
            font-weight: bold;
        }

        .links { 
            text-align: center; 
            margin-top: 30px; 
            font-size: 13px;
            border-top: 1px dashed #1e293b;
            padding-top: 20px;
        }

        .links a { 
            color: #38bdf8; 
            text-decoration: none; 
            font-weight: bold;
        }
        .links a:hover { text-decoration: underline; }
    </style>
</head>
<body>

<div class="container">
    <!-- ❌ BOTÃO X DE RETORNO FÍSICO IMEDIATO -->
    <a href="Principal.php" class="btn-fechar-top" title="Voltar ao Portal Público">✕</a>

    <h2>Acessar ao site <br><span style="font-size:11px; color:#94a3b8; text-transform: uppercase;">💧 Aurélio JB Estética Fluida</span></h2>

    <?php
    if(isset($erro) && is_array($erro) && count($erro) > 0){
        foreach($erro as $msg){
            echo "<p class='erro-msg'>⚠️ " . htmlspecialchars($msg) . "</p>";
        }
    }
    ?>

    <form action="SoTrança.php" method="POST">
        
        <div class="campo-form">
            <label for="email">E-mail de Acesso:</label>
            <input type="email" name="email" id="email" required placeholder="Digite o seu e-mail" value="<?php echo isset($_POST['email'])? htmlspecialchars($_POST['email']) : ''; ?>">
        </div>

        <div class="campo-form">
            <label for="senha">Palavra-passe:</label>
            <input type="password" name="senha" id="senha" required placeholder="Digite a sua senha">
        </div>

        <!-- 🚀 GATILHO SINCRONIZADO COM O PHP DO TOPO -->
        <button type="submit" name="entrar_aurelio" class="btn-enviar">Entrar no Sistema</button>
        
        <div class="links">
            <p><a href="recuperar.php">Esqueceu a sua senha?</a></p>
            <p style="color: #64748b; margin-top:5px;">Não tem conta de cliente? <a href="SotrançaCadastro.php">Registe-se aqui</a></p>
        </div>
    </form>
</div>

</body>
</html>