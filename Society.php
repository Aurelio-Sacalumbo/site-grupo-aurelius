
<!DOCTYPE html>
<html lang="pt-PT">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Barbearia LookNovo</title>
    <style>
        body { font-family: sans-serif; background-color: #0c0a09; display: flex; justify-content: center; align-items: center; min-height: 100vh; margin: 0; overflow: hidden; }
        .diamond-outer { width: 460px; height: 440px; background: #1c1917; transform: rotate(45deg); display: flex; justify-content: center; align-items: center; border: 2px solid #f59e0b; box-shadow: 0 0 20px rgba(245, 158, 11, 0.3); animation: glowOuro 3.5s infinite alternate; }
        @keyframes glowOuro { 0% { box-shadow: 0 0 15px rgba(245, 158, 11, 0.2); border-color: #d97706; } 100% { box-shadow: 0 0 35px rgba(245, 158, 11, 0.6); border-color: #fbbf24; } }
        .diamond-content { transform: rotate(-45deg); width: 100%; text-align: center; color: white; padding: 20px; box-sizing: border-box; }
        h2 { font-size: 18px; color: #fbbf24; text-transform: uppercase; margin: 0 0 15px 0; }
        .campo-form { width: 80%; margin: 0 auto 10px auto; text-align: left; }
        .campo-form label { display: block; font-size: 11px; color: #fde047; text-transform: uppercase; margin-bottom: 4px; text-align: center; font-weight: bold; }
        input { width: 100%; padding: 10px; border: 1px solid #2e2a24; background: #0c0a09; color: white; outline: none; box-sizing: border-box; font-size: 13px; text-align: center; }
        input:focus { border-color: #fbbf24; }
        .btn-enviar { width: 80%; padding: 11px; background: linear-gradient(135deg, #f59e0b, #d97706); color: #0c0a09; border: none; font-weight: bold; cursor: pointer; text-transform: uppercase; font-size: 13px; margin-top: 5px; }
        .erro-msg { color: #f87171; font-size: 11px; margin-bottom: 8px; font-weight: bold; }
        .links { margin-top: 15px; font-size: 11px; }
        .links a { color: #fbbf24; text-decoration: none; font-weight: bold; }
        .links p { margin: 3px 0; }

         /* ❌ BOTÃO X FLUTUANTE ADAPTADO CELESTIAL */
         .btn-fechar-top {
            position: absolute;
            top: 5px; /* Centralizado no topo da ponta superior da estrela */
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
    </style>
</head>
<body>

<div class="diamond-outer">
    <div class="diamond-content">
        <h2>Acessar ao site</h2>

        <?php if(isset($erro) && is_array($erro) && count($erro) > 0) { foreach($erro as $msg) { echo "<p class='erro-msg'>⚠️ " . htmlspecialchars($msg) . "</p>"; } } ?>

        <form action="" method="POST">
        <a href="Principal.php" class="btn-fechar-top" title="Voltar ao Portal">✕</a>
            <div class="campo-form">
                <label for="email">E-mail do Cliente:</label>
                <input type="email" name="email" id="email" required placeholder="Digite o seu e-mail" value="<?php echo isset($_POST['email'])? htmlspecialchars($_POST['email']) : ''; ?>">
            </div>
            <div class="campo-form">
                <label for="senha">Palavra-passe:</label>
                <input type="password" name="senha" id="senha" required placeholder="Digite a sua senha">
            </div>
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