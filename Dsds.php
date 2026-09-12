<?php
// =========================================================================
// 💧 LOGIN_AURELIO_JB.PHP - DESIGN PREMIUM DE ÁGUA OPERACIONAL (CÓDIGO 11)
// =========================================================================
if(!isset($_SESSION)){ session_start(); }
include("conect.php");
$erro = array(); 

if($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['email'])){
    $email = $mysqli->escape_string(trim($_POST['email']));
    $senha = md5(md5($_POST['senha']));

    // Verifica as credenciais na tabela de usuários
    $sql = "SELECT * FROM `usuario` WHERE email = '$email' AND senha = '$senha' AND (codigo = 11 OR codigo = 12)";
    $query = $mysqli->query($sql);
    
    if ($query && $query->num_rows > 0) {
        $dado = $query->fetch_assoc();
        $_SESSION['parceiro_id']   = $dado['codigo'];
        $_SESSION['parceiro_nome'] = $dado['nome'];
        header("Location: dashboard.php");
        exit();
    } else { 
        $erro[] = "Credenciais inválidas para o painel de água Aurélio JB."; 
    }
}
?>
<!DOCTYPE html>
<html lang="pt-PT">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Aurélio JB Water Premium</title>
    <style>
        body { 
            font-family: 'Segoe UI', Arial, sans-serif; 
            background-color: #020617; /* Fundo oceânico profundo */
            display: flex; 
            justify-content: center; 
            align-items: center; 
            min-height: 100vh; 
            margin: 0; 
            overflow-x: hidden;
        }
        
        /* 🔮 CAIXA COESORA COM RADIÂNCIA DE ÁGUA LÍQUIDA E ANIMAÇÃO DE FLUXO */
        .login-water { 
            position: relative;
            background: #070f2e; /* Azul marinho fechado */
            padding: 50px 40px; 
            border-radius: 24px; 
            border: 2px solid #06b6d4; /* Azul Ciano Fluido */
            text-align: center; 
            color: white; 
            width: 100%; 
            max-width: 440px; 
            box-sizing: border-box;
            box-shadow: 0 0 20px rgba(6, 182, 212, 0.4), inset 0 0 20px rgba(6, 182, 212, 0.1); 
            animation: pulsarAgua 4s infinite ease-in-out; 
        }

        /* Animação CSS que simula a pulsação e o movimento da água a escorrer */
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
        
        /* ❌ BOTÃO X FLUTUANTE ESTILO GOTA DE ÁGUA CRISTALINA */
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

        h2 { color: #38bdf8; text-transform: uppercase; margin: 0 0 5px 0; font-size: 22px; letter-spacing: 1px; }
        .campo-form { margin-bottom: 22px; text-align: left; }
        .campo-form label { display: block; font-size: 12px; color: #7dd3fc; text-transform: uppercase; margin-bottom: 6px; font-weight: bold; letter-spacing: 0.5px; }
        
        input { 
            width: 100%; 
            padding: 15px; 
            border-radius: 20px; /* inputs arredondados como gotas */
            border: 1px solid #1e293b; 
            background: #020617; 
            color: white; 
            outline: none; 
            box-sizing: border-box; 
            font-size: 15px; 
            text-align: center;
            transition: 0.3s;
        }
        input:focus { border-color: #38bdf8; box-shadow: 0 0 10px rgba(56, 189, 248, 0.4); background: #070f2e; }
        
        /* Botão Gradiente de Onda Líquida Ciano */
        .btn-enviar { 
            width: 100%; 
            padding: 16px; 
            background: linear-gradient(135deg, #0284c7, #38bdf8); 
            color: #020617; 
            border: none; 
            border-radius: 20px; 
            font-weight: bold; 
            cursor: pointer; 
            text-transform: uppercase; 
            font-size: 15px; 
            margin-top: 10px;
            box-shadow: 0 4px 12px rgba(6, 182, 212, 0.3);
            transition: 0.3s;
            letter-spacing: 0.5px;
        }
        .btn-enviar:hover { background: linear-gradient(135deg, #38bdf8, #22d3ee); transform: translateY(-2px); box-shadow: 0 6px 18px rgba(34, 211, 238, 0.6); }
        
        .erro-msg { background: rgba(239, 68, 68, 0.15); border: 1px solid #ef4444; color: #f87171; padding: 12px; border-radius: 6px; font-size: 13px; margin-bottom: 20px; font-weight: bold; text-align: left; }
        
        .links { margin-top: 30px; font-size: 13px; border-top: 1px dashed #1e293b; padding-top: 20px; }
        .links a { color: #38bdf8; text-decoration: none; font-weight: bold; }
        .links a:hover { color: #22d3ee; text-decoration: underline; }
        .links p { margin: 5px 0; }
    </style>
</head>
<body>

    <div class="login-water">
        <!-- ❌ BOTÃO X DE VOLTAR REATIVO -->
        <a href="principal.php" class="btn-fechar-top" title="Voltar ao Portal">✕</a>

        <h2>Acessar ao site</h2>
        <p style="font-size: 11px; color: #94a3b8; margin: 0 0 25px 0; font-weight: bold; text-transform: uppercase; letter-spacing: 0.5px;">💧 Aurélio JB Estética Fluida</p>

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