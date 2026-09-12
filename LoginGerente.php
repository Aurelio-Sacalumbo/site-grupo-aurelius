<?php
// =========================================================================
// 🌐 LOGIN.PHP - VERSÃO FINAL EXECUTIVA INTEGRAL (GATILHO DE ENVIO REPARADO)
// =========================================================================
if (!isset($_SESSION)) {
    session_start();
}

date_default_timezone_set('Africa/Luanda');

// Conexão direta e blindada à base de dados central
$mysqli = new mysqli("127.0.0.1", "root", "", "aurelius_salao");
if ($mysqli->connect_error) {
    die("Falha na ligação mestre: " . $mysqli->connect_error);
}
$mysqli->set_charset("utf8");

$erro = array();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['entrar_painel'])) {
    
    $email = $mysqli->escape_string(trim($_POST['email']));
    $senha = md5(md5($_POST['senha'])); // Criptografia MD5 Dupla padrão da gerência

    // Procura as credenciais estritamente na tabela de gerência 'usuario'
    $sql_login = "SELECT * FROM `usuario` WHERE `email` = '$email' AND `senha` = '$senha'";
    $query_login = $mysqli->query($sql_login);

    if ($query_login && $query_login->num_rows > 0) {
        $parceiro = $query_login->fetch_assoc();

        $nivel_real = isset($parceiro['nivel']) ? trim($parceiro['nivel']) : 'parceiro_hospedado';

        // 🔮 GRAVAÇÃO DA CHAVE DINÂMICA EM SESSÃO DA PLATAFORMA
        $_SESSION['parceiro_id']    = $parceiro['codigo'];
        $_SESSION['parceiro_nome']  = $parceiro['nome'];
        $_SESSION['parceiro_nivel'] = $nivel_real;

        // 🎛️ CONTROLO DE ROTAS SEGURO E DIRETO
        if ($_SESSION['parceiro_nivel'] === 'admin_mestre') {
            header("Location: Admin.php");
            exit();
        } else {
            header("Location: Admin.php");
            exit();
        }
        
    } else {
        $erro[] = "Credenciais inválidas para o painel de gerência Aurélius.";
    }
}
?>
<!DOCTYPE html>
<html lang="pt-PT">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login de Gerência - Grupo Aurélius</title>
    <style>
        body { 
            font-family: 'Segoe UI', Arial, sans-serif; 
            background-color: #0b0f19; 
            margin: 0; 
            display: flex; 
            justify-content: center; 
            align-items: center; 
            min-height: 100vh; 
            box-sizing: border-box; 
        }

        .container { 
            width: 100%; 
            max-width: 460px; 
            background: #111827; 
            padding: 50px 40px; 
            border-radius: 16px; 
            box-sizing: border-box; 
            text-align: center; 
            color: white; 
            border: 2px solid #ca8a04;
            box-shadow: 0 0 15px rgba(202, 138, 4, 0.3), inset 0 0 15px rgba(56, 189, 248, 0.05);
            animation: pulsarGlowGerencia 4s infinite alternate;
        }

        @keyframes pulsarGlowGerencia {
            0% { box-shadow: 0 0 12px rgba(202, 138, 4, 0.25); border-color: #a16207; }
            100% { box-shadow: 0 0 22px rgba(202, 138, 4, 0.5), 0 0 40px rgba(56, 189, 248, 0.15); border-color: #eab308; }
        }

        .container h2 { 
            margin: 0 0 35px 0; 
            border-bottom: 2px solid rgba(202, 138, 4, 0.3); 
            padding-bottom: 12px; 
            text-transform: uppercase; 
            font-size: 20px; 
            letter-spacing: 1px;
            color: #ffffff;
        }

        .campo-form { margin-bottom: 22px; text-align: left; }
        .campo-form label { font-weight: bold; display: block; margin-bottom: 10px; font-size: 13px; color: #e2e8f0; text-transform: uppercase; letter-spacing: 0.5px; }
        
        .campo-form input { 
            width: 100%; 
            padding: 15px 18px; 
            border: 1px solid #374151; 
            border-radius: 8px; 
            box-sizing: border-box; 
            font-size: 15px; 
            outline: none; 
            background: #0b0f19; 
            color: white;
            transition: 0.3s;
        }
        .campo-form input:focus { 
            border-color: #ca8a04; 
            box-shadow: 0 0 10px rgba(202, 138, 4, 0.3);
            background: #111524;
        }

        .btn-enviar { 
            width: 100%; 
            background: linear-gradient(135deg, #ca8a04, #a16207); 
            color: white; 
            border: none; 
            padding: 16px; 
            cursor: pointer; 
            font-size: 16px; 
            border-radius: 8px; 
            font-weight: bold; 
            margin-top: 15px; 
            text-transform: uppercase; 
            box-sizing: border-box; 
            letter-spacing: 0.5px;
            transition: all 0.3s ease;
        }
        .btn-enviar:hover { 
            background: linear-gradient(135deg, #eab308, #ca8a04); 
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(202, 138, 4, 0.4);
        }

        .erro-msg { background: rgba(239, 68, 68, 0.15); border: 1px solid #ef4444; color: #f87171; padding: 12px; border-radius: 6px; font-size: 13px; margin-bottom: 25px; text-align: left; font-weight: bold; }
        .links { margin-top: 30px; font-size: 13px; border-top: 1px dashed #374151; padding-top: 20px; }
        .links a { color: #38bdf8; text-decoration: none; font-weight: bold; transition: color 0.2s; }
        .links a:hover { color: #22d3ee; text-decoration: underline; }
    </style>
</head>
<body>

<div class="container">
    <h2>Painel Executivo <br><span style="color: #ca8a04; font-size: 14px; font-weight: bold;">Grupo Aurélius</span></h2>

    <?php
    if (isset($erro) && is_array($erro) && count($erro) > 0) {
        foreach ($erro as $msg) {
            echo "<p class='erro-msg'>⚠️ " . htmlspecialchars($msg) . "</p>";
        }
    }
    ?>

    <!-- 🔮 FORÇA O DISPARO: O action aponta explicitamente para o próprio arquivo de execução -->
    <form action="Login.php" method="POST">
        <div style="text-align: center; margin-bottom: 25px;"> 
            <img src="images (13).jpg" alt="Logo" style="max-width: 105px; height: auto; border-radius: 50%; border: 2px solid #ca8a04; box-shadow: 0 4px 10px rgba(0,0,0,0.3);"> 
        </div>

        <div class="campo-form">
            <label for="email">E-mail Corporativo:</label>
            <input type="email" name="email" id="email" required placeholder="Ex: gerente@aurelius.com" value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>">
        </div>

        <div class="campo-form">
            <label for="senha">Palavra-passe:</label>
            <input type="password" name="senha" id="senha" required placeholder="Introduza a sua senha">
        </div>

        <!-- 🚀 GATILHO COMPACTADO: Botão do tipo submit com name indexado ao PHP -->
        <button type="submit" name="entrar_painel" class="btn-enviar">Autenticar Entrada</button>
        
        <div class="links">
            <p><a href="recuperar.php">Recuperar credenciais de acesso</a></p>
            <p style="color: #9ca3af; margin-top: 5px;">Hospedar nova empresa? <a href="cadastrar.php">Registe-se aqui</a></p>
        </div>
    </form>
</div>

</body>
</html>