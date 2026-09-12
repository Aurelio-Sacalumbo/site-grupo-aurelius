<?php
// =========================================================================
// ✨ BRILHO.PHP - DESIGN PREMIUM EM FORMA DE ESTRELA RADIANTE (CÓDIGO 5)
// =========================================================================
if(!isset($_SESSION)){ 
    session_start(); 
}

date_default_timezone_set('Africa/Luanda');

// Liga à base de dados centralizada do Grupo Aurélius
include("conect.php");

$erro = array(); 

if($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['entrar_brilha'])){
    $email = $mysqli->escape_string(trim($_POST['email']));
    $senha = md5(md5($_POST['senha'])); // Criptografia MD5 dupla do projeto

    // Consulta direta focada no Código 5 do Salão Brilha
    $sql = "SELECT * FROM `usuario` WHERE `email` = '$email' AND `senha` = '$senha' AND `codigo` = 5";
    $query = $mysqli->query($sql);
    
    if ($query && $query->num_rows > 0) {
        $dado = $query->fetch_assoc();
        $_SESSION['parceiro_id']   = $dado['codigo'];
        $_SESSION['parceiro_nome'] = $dado['nome'];
        
        // Direciona direto para o painel de controlo
        header("Location: FemisAngola.php");
        exit();
    } else { 
        $erro[] = "Credenciais inválidas para o Salão Brilha."; 
    }
}
?>
<!DOCTYPE html>
<html lang="pt-PT">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Salão Brilha Estrela</title>
    <style>
        body { 
            font-family: 'Segoe UI', Arial, sans-serif; 
            background-color: #05010a; /* Fundo cósmico escuro */
            display: flex; 
            justify-content: center; 
            align-items: center; 
            min-height: 100vh; 
            margin: 0; 
            overflow: hidden;
        }
        
        /* 🔮 ENVELOPE EXTERNO EM FORMA DE ESTRELA DE 5 PONTAS COM GLOW NEON */
        .star-wrap {
            position: relative;
            width: 780px;
            height: 780px;
            background: linear-gradient(135deg, #f472b6, #c084fc, #ec4899);
            padding: 3px; /* Simula a espessura da borda da estrela */
            clip-path: polygon(50% 0%, 61% 35%, 98% 35%, 68% 57%, 79% 91%, 50% 70%, 21% 91%, 32% 57%, 2% 35%, 39% 35%);
            filter: drop-shadow(0 0 25px #f472b6);
            animation: pulsarEstrela 3.5s infinite alternate ease-in-out;
            display: flex;
            justify-content: center;
            align-items: center;
        }

        /* Animação que faz a radiação e a luz da estrela pulsar no ecrã */
        @keyframes pulsarEstrela { 
            0% { filter: drop-shadow(0 0 15px rgba(244, 114, 182, 0.5)); transform: scale(0.98); } 
            100% { filter: drop-shadow(0 0 35px rgba(236, 72, 153, 0.9)); transform: scale(1.02); } 
        }

        /* Contentor Interno da Estrela (Onde fica o formulário direito) */
        .star-inner {
            width: 99%;
            height: 99%;
            background: #11051c; /* Roxo profundo */
            clip-path: polygon(50% 0%, 61% 35%, 98% 35%, 68% 57%, 79% 91%, 50% 70%, 21% 91%, 32% 57%, 2% 35%, 39% 35%);
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            padding: 40px;
            box-sizing: border-box;
            color: white;
        }
        
        /* ❌ BOTÃO X FLUTUANTE ADAPTADO CELESTIAL */
        .btn-fechar-top {
            position: absolute;
            top: 155px; /* Centralizado no topo da ponta superior da estrela */
            width: 32px;
            height: 32px;
            background: linear-gradient(135deg, #ef4444, #dc2626);
            color: white;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            text-decoration: none;
            font-weight: bold;
            font-size: 14px;
            box-shadow: 0 0 10px #ef4444;
            border: 2px solid #11051c;
            transition: 0.3s ease;
            z-index: 1000;
        }
        .btn-fechar-top:hover { transform: scale(1.1) rotate(90deg); background: #ff4545; }

        h2 { color: #f472b6; text-transform: uppercase; margin: 40px 0 5px 0; font-size: 18px; letter-spacing: 0.5px; }
        .campo-form { width: 55%; margin-bottom: 12px; text-align: left; }
        .campo-form label { display: block; font-size: 10px; color: #e9d5ff; text-transform: uppercase; margin-bottom: 4px; font-weight: bold; text-align: center; }
        
        input { 
            width: 100%; 
            padding: 11px; 
            border-radius: 20px; 
            border: 1px solid #4a1d96; 
            background: #05010a; 
            color: white; 
            outline: none; 
            box-sizing: border-box; 
            font-size: 13px; 
            text-align: center; 
        }
        input:focus { border-color: #f472b6; box-shadow: 0 0 8px rgba(244, 114, 182, 0.4); }
        
        .btn-enviar { 
            width: 55%; 
            padding: 13px; 
            background: linear-gradient(135deg, #c084fc, #f472b6); 
            color: #05010a; 
            border: none; 
            border-radius: 20px; 
            font-weight: bold; 
            cursor: pointer; 
            text-transform: uppercase; 
            font-size: 13px; 
            margin-top: 5px; 
            box-shadow: 0 4px 12px rgba(168, 85, 247, 0.3); 
            transition: 0.2s;
        }
        .btn-enviar:hover { background: linear-gradient(135deg, #f472b6, #ec4899); color: white; transform: translateY(-1px); }
        
        .erro-msg { color: #f87171; font-size: 11px; margin-bottom: 10px; font-weight: bold; width: 55%; text-align: center; }
        
        .links { margin-top: 15px; font-size: 11px; width: 55%; text-align: center; }
        .links a { color: #c084fc; text-decoration: none; font-weight: bold; }
        .links a:hover { text-decoration: underline; }
        .links p { margin: 4px 0; }
    </style>
</head>
<body>

    <!-- ❌ BOTÃO X DE VOLTAR COLOCADO FORA PARA ESTAR NA PONTA DA ESTRELA -->
    <a href="Principal.php" class="btn-fechar-top" title="Voltar ao Portal">✕</a>

    <div class="star-wrap">
        <div class="star-inner">
            
            <h2>Acessar ao site</h2>
            <p style="font-size: 10px; color: #94a3b8; margin: 0 0 20px 0; font-weight: bold; text-transform: uppercase; letter-spacing: 0.5px;">✨ Salão Brilha Estelar</p>

            <?php if(count($erro) > 0) { foreach($erro as $msg) { echo "<p class='erro-msg'>⚠️ $msg</p>"; } } ?>
            
            <form action="Brilho.php" method="POST" style="width: 100%; display: flex; flex-direction: column; align-items: center;">
                <div class="campo-form">
                    <label for="email">E-mail do Cliente:</label>
                    <input type="email" name="email" id="email" required placeholder="Digite o seu e-mail" value="<?php echo isset($_POST['email'])? htmlspecialchars($_POST['email']) : ''; ?>">
                </div>
                <div class="campo-form">
                    <label for="senha">Palavra-passe:</label>
                    <input type="password" name="senha" id="senha" required placeholder="Digite a sua senha">
                </div>
                
                <button type="submit" name="entrar_brilha" class="btn-enviar">Entrar no Sistema</button>
                
                <div class="links">
                    <p><a href="recuperar.php">Esqueceu a sua senha?</a></p>
                    <p style="color: #64748b;">Não tem conta de cliente? <a href="FemisAngolaCadastrar.php">Registe-se aqui</a></p>
                </div>
            </form>
            
        </div>
    </div>

</body>
</html>