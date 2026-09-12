<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
date_default_timezone_set('Africa/Luanda');

require_once __DIR__ . "/config/Banco.php";
$mysqli = $conexao_link ?? $conexao_aurelius ?? null;

$mensagem_sucesso = "";
$erro = "";

if (isset($_POST['recuperar'])) {
    $email = isset($_POST['email']) ? mysqli_real_escape_string($mysqli, trim($_POST['email'])) : '';

    if (empty($email)) {
        $erro = "Por favor, digite o seu e-mail cadastrado.";
    } else {
        // Verifica se o e-mail existe na base de dados global do portal
        $check_user = mysqli_query($mysqli, "SELECT codigo, nome FROM `usuario` WHERE `email` = '$email' LIMIT 1");
        
        if ($check_user && mysqli_num_rows($check_user) > 0) {
            $user_data = mysqli_fetch_assoc($check_user);
            $id_cliente = $user_data['codigo'];
            $nome_cliente = $user_data['nome'];

            // 🟢 AUTOMÁTICO: Gera uma nova senha aleatória de 6 dígitos numéricos
            $nova_senha_numero = rand(100000, 999999);
            $nova_senha_cripto = md5($nova_senha_numero);

            // Atualiza a tabela do cliente no Railway com a nova credencial limpa
            $update = mysqli_query($mysqli, "UPDATE `usuario` SET `senha` = '$nova_senha_cripto' WHERE `codigo` = '$id_cliente'");

            if ($update) {
                $mensagem_sucesso = "Olá <b>$nome_cliente</b>!<br>O teu ID Único de Acesso é: <span style='color:#38bdf8; font-size:16px;'>#$id_cliente</span><br>A tua Nova Palavra-passe temporária é: <span style='color:#4ade80; font-size:16px; font-family:monospace;'>$nova_senha_numero</span>";
            } else {
                $erro = "Falha interna ao processar redefinição no servidor.";
            }
        } else {
            $erro = "Este endereço de e-mail não foi localizado na nossa base de dados.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="pt-PT">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
   <!-- Na barra de navegação e instalação do PWA -->
<title>BarbeariasAngola — Rede de Distribuição & Estética</title>

<!-- No cabeçalho principal do seu menu Slate -->
<h4 style="color: #eab308; text-transform: uppercase; font-weight: bold; font-size: 14px; letter-spacing: 0.5px;">
    🎯 BarbeariasAngola • Painel Operacional
</h4>
    <style>
        html, body { width: 100% !important; max-width: 100% !important; overflow-x: hidden !important; margin: 0 !important; padding: 0 !important; box-sizing: border-box !important; }
        body { font-family: 'Segoe UI', Arial, sans-serif; background-color: #0b0f19; display: flex; justify-content: center; align-items: center; min-height: 100vh; padding: 15px !important; }
        .container { width: 100% !important; max-width: 440px !important; background: #111827; padding: 35px 20px; border-radius: 24px; text-align: center; color: white; border: 2px solid #ef4444; box-shadow: 0 0 20px rgba(239, 68, 68, 0.15); box-sizing: border-box; }
        .container h2 { margin: 0 0 10px 0; color: #ffffff; font-size: 18px; font-weight: 900; text-transform: uppercase; }
        .campo-form { margin-bottom: 18px; text-align: left; }
        .campo-form label { font-weight: bold; display: block; margin-bottom: 8px; font-size: 12px; color: #f87171; text-transform: uppercase; }
        .campo-form input { width: 100%; padding: 13px 16px; border: 1px solid #374151; border-radius: 20px; box-sizing: border-box; font-size: 14px; background: #0b0f19; color: #ffffff; outline: none; }
        .btn-enviar { width: 100%; background: linear-gradient(135deg, #ef4444, #b91c1c); color: white; border: none; padding: 14px; cursor: pointer; font-size: 14px; border-radius: 20px; font-weight: bold; text-transform: uppercase; }
        .btn-voltar { display: block; width: 100%; text-align: center; background: #374151; color: white; border: 1px solid #4b5563; padding: 11px; font-size: 13px; border-radius: 20px; font-weight: bold; text-decoration: none; margin-top: 15px; text-transform: uppercase; }
    </style>
</head>
<body>

<div class="container">
    <h2>REATIVAR CHAVES DE CLIENTE</h2>
    <p style="font-size:11px; color:#64748b; text-transform:uppercase; margin-bottom:20px; border-bottom:1px solid rgba(239,68,68,0.2); padding-bottom:10px;">Recuperação Automática de Acesso Unificado</p>

    <?php if (!empty($mensagem_sucesso)): ?>
        <div style="background: rgba(34, 197, 94, 0.12); border: 1px solid #22c55e; color: #cbd5e1; padding: 15px; border-radius: 14px; font-size: 13px; margin-bottom: 20px; text-align: left; line-height: 1.5;">
            🎉 <b>REDEFINIÇÃO GERADA COM SUCESSO!</b><br><br>
            <?php echo $mensagem_sucesso; ?><br><br>
            <span style="font-size: 11px; color: #94a3b8;">Use estes dados novos para fazer o login no formulário de acesso agora.</span>
        </div>
    <?php endif; ?>

    <?php if (!empty($erro)): ?>
        <div style="background: rgba(239, 68, 68, 0.12); border: 1px solid #ef4444; color: #f87171; padding: 10px; border-radius: 12px; font-size: 12px; margin-bottom: 15px; text-align: left; font-weight: bold;">
            ⚠️ <?php echo $erro; ?>
        </div>
    <?php endif; ?>

    <form action="" method="POST">
        <div class="campo-form">
            <label for="email">Insira o seu E-mail de Registo:</label>
            <input type="email" name="email" id="email" required placeholder="Ex: teu-email@gmail.com">
        </div>

        <button type="submit" name="recuperar" class="btn-enviar">Gerar Novo ID e Senha →</button>
        <a href="Login.php" class="btn-voltar">← Voltar ao Login</a>
    </form>
</div>

</body>
</html>