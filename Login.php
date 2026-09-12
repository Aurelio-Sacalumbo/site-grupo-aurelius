<?php
// =========================================================================
// 🌌 LOGIN_AURELIO_TESTE.PHP - DESIGN PREMIUM ESPINHAL OPERACIONAL (103/104)
// =========================================================================
if(!isset($_SESSION)){ session_start(); }
include("conect.php");
$erro = array(); 

if($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['email'])){
    $email = $mysqli->escape_string(trim($_POST['email']));
    $senha = md5(md5($_POST['senha']));

    // Verifica as credenciais na tabela de usuários para os códigos de teste 103 ou 104
    $sql = "SELECT * FROM `usuario` WHERE email = '$email' AND senha = '$senha' AND (codigo = 103 OR codigo = 104)";
    $query = $mysqli->query($sql);
    
    if ($query && $query->num_rows > 0) {
        $dado = $query->fetch_assoc();
        $_SESSION['parceiro_id']   = $dado['codigo'];
        $_SESSION['parceiro_nome'] = $dado['nome'];
        header("Location: SoTrança.php");
        exit();
    } else { 
        $erro[] = "Credenciais inválidas para o painel espinhal Aurélio."; 
    }
}
?>
<!DOCTYPE html>
<html lang="pt-PT">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Aurélio Spinal Premium</title>
    <style>
        body { 
            font-family: 'Segoe UI', Arial, sans-serif; 
            background-color: #040209; /* Fundo do espaço profundo */
            display: flex; 
            justify-content: center; 
            align-items: center; 
            min-height: 100vh; 
            margin: 0; 
            overflow: hidden;
        }
        
        /* 🔮 CAIXA COESORA COM BORDAS DE VÓRTICE ESPINHAL E RADIÂNCIA CÓSMICA */
        .login-spinal { 
            position: relative;
            background: #0d061a; /* Roxo espacial fechado */
            padding: 50px 40px; 
            border-radius: 24px; 
            text-align: center; 
            color: white; 
            width: 100%; 
            max-width: 440px; 
            box-sizing: border-box;
            
            /* Borda em gradiente simulando o disco espinhal */
            border: 2px solid transparent;
            border-image: linear-gradient(to bottom right, #a855f7, #3b82f6, #6366f1) 1;
            
            box-shadow: 0 0 25px rgba(168, 85, 247, 0.4), inset 0 0 20px rgba(59, 130, 246, 0.15); 
            animation: pulsarEspinhal 4s infinite linear; 
        }

        /* Animação CSS que simula a rotação e a pulsação de luz do vórtice espinhal */
        @keyframes pulsarEspinhal { 
            0% { 
                box-shadow: 0 0 15px rgba(168, 85, 247, 0.4), 0 0 30px rgba(59, 130, 246, 0.2);
                filter: hue-rotate(0deg);
            } 
            50% {
                box-shadow: 0 0 30px rgba(236, 72, 153, 0.6), 0 0 50px rgba(168, 85, 247, 0.3);
            }
            100% { 
                box-shadow: 0 0 15px rgba(168, 85, 247, 0.4), 0 0 30px rgba(59, 130, 246, 0.2);
                filter: hue-rotate(360deg); /* Faz as cores girarem em espiral infinitamente */
            } 
        }
        
        /* ❌ BOTÃO X FLUTUANTE ESTILO CONSTELAÇÃO */
        .btn-fechar-top {
            position: absolute;
            top: -15px;
            right: -15px;
            width: 36px;
            height: 36px;
            background: linear-gradient(135deg, #a855f7, #6366f1);
            color: white;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            text-decoration: none;
            font-weight: bold;
            font-size: 16px;
            box-shadow: 0 0 12px rgba(168, 85, 247, 0.6);
            border: 2px solid #0d061a;
            transition: 0.3s ease;
        }
        .btn-fechar-top:hover {
            transform: scale(1.1) rotate(90deg);
            background: #d946ef;
            box-shadow: 0 0 20px #d946ef;
        }

        h2 { color: #f472b6; text-transform: uppercase; margin: 0 0 5px 0; font-size: 22px; letter-spacing: 1px; }
        .campo-form { margin-bottom: 22px; text-align: left; }
        .campo-form label { display: block; font-size: 12px; color: #c084fc; text-transform: uppercase; margin-bottom: 6px; font-weight: bold; letter-spacing: 0.5px; }
        
        input { 
            width: 100%; 
            padding: 15px; 
            border-radius: 8px;
            border: 1px solid #3b0764; 
            background: #040209; 
            color: white; 
            outline: none; 
            box-sizing: border-box; 
            font-size: 15px; 
            text-align: center;
            transition: 0.3s;
        }
        input:focus { border-color: #d946ef; box-shadow: 0 0 10px rgba(217, 70, 239, 0.4); background: #0d061a; }
        
        /* Botão Gradiente Galáctico */
        .btn-enviar { 
            width: 100%; 
            padding: 16px; 
            background: linear-gradient(135deg, #a855f7, #6366f1); 
            color: white; 
            border: none; 
            border-radius: 8px; 
            font-weight: bold; 
            cursor: pointer; 
            text-transform: uppercase; 
            font-size: 15px; 
            margin-top: 10px;
            box-shadow: 0 4px 12px rgba(168, 85, 247, 0.4);
            transition: 0.3s;
            letter-spacing: 0.5px;
        }
        .btn-enviar:hover { background: linear-gradient(135deg, #c084fc, #818cf8); transform: translateY(-2px); box-shadow: 0 6px 18px rgba(168, 85, 247, 0.6); }
        
        .erro-msg { background: rgba(239, 68, 68, 0.15); border: 1px solid #ef4444; color: #f87171; padding: 12px; border-radius: 6px; font-size: 13px; margin-bottom: 20px; font-weight: bold; text-align: left; }
        
        .links { margin-top: 30px; font-size: 13px; border-top: 1px dashed #3b0764; padding-top: 20px; }
        .links a { color: #c084fc; text-decoration: none; font-weight: bold; }
        .links a:hover { color: #f472b6; text-decoration: underline; }
        .links p { margin: 5px 0; }
    </style>
</head>
<body>

    <div class="login-spinal">
        <!-- ❌ BOTÃO X DE VOLTAR REATIVO -->
        <a href="principal.php" class="btn-fechar-top" title="Voltar ao Portal">✕</a>

        <h2>Acessar ao site</h2>
        <p style="font-size: 11px; color: #94a3b8; margin: 0 0 25px 0; font-weight: bold; text-transform: uppercase; letter-spacing: 0.5px;">🌌 Aurélio Sistema Espinhal</p>

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