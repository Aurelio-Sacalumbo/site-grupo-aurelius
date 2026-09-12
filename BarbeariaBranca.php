<?php
// Ficheiro: C:\xampp\htdocs\grupo-aurelius-pwa\Login.php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
date_default_timezone_set('Africa/Luanda');

// IMPORTAÇÃO PROTEGIDA DO SEU BANCO ORIGINAL
require_once __DIR__ . "/config/Banco.php";

// Deteta de forma segura qual variável híbrida o seu Banco.php gerou
$ligacao_ativa = $conexao_link ?? $mysqli ?? null;

if (!$ligacao_ativa) {
    die("Erro interno: A infraestrutura de ligação híbrida não foi iniciada.");
}

// Captura a intenção de rota do cliente vinda do catálogo
if (isset($_GET['acceder_a'])) {
    $_SESSION['barbearia_alvo_slug'] = mysqli_real_escape_string($ligacao_ativa, trim($_GET['acceder_a']));
}

$erro = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = isset($_POST['email']) ? mysqli_real_escape_string($ligacao_ativa, trim($_POST['email'])) : '';
    $senha_raw = isset($_POST['senha']) ? trim($_POST['senha']) : '';

    if (empty($email) || empty($senha_raw)) {
        $erro[] = "Por favor, introduza o e-mail e a palavra-passe.";
    } else {
        $senha_cripto = md5($senha_raw);

        // Procura o utilizador GLOBALMENTE no ecossistema através do driver estável
        $query_cliente = mysqli_query($ligacao_ativa, "SELECT * FROM `usuario` WHERE `email` = '$email' AND `senha` = '$senha_cripto' LIMIT 1");

        if ($query_cliente && mysqli_num_rows($query_cliente) > 0) {
            $dados_cliente = mysqli_fetch_assoc($query_cliente); 
        
            // 🔒 Injeta os trincos de sessão global (Single Sign-On - SSO)
            $_SESSION['cliente_logado']  = true;
            $_SESSION['cliente_id']      = $dados_cliente['codigo']; 
            $_SESSION['cliente_nome']    = $dados_cliente['nome'];
            $_SESSION['cliente_email']   = $dados_cliente['email'];
            $_SESSION['tipo_conta']      = $dados_cliente['nivel'];

            // Limpa as bolhas de alertas antigos na navegação estilo Facebook
            $_SESSION['bloqueio_bolha_barbearia'] = true;

            // 🟢 REDIRECIONAMENTO UNIFICADO SOLICITADO:
            // Todo cliente autenticado vai direto para o Dashboard.php gerir os seus agendamentos
            header("Location: Dashboard.php");
            exit();
        } else {
            $erro[] = "Credenciais inválidas. Verifique os seus dados de acesso.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="pt-PT">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no, viewport-fit=cover">
    <title>Login Único - Rede Aurélius</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        /* 🔒 ADAPTAÇÃO TOTAL PARA MATRIZ ANDROID MOBILE (ZERO MARGENS EM BRANCO) */
        html, body { 
            width: 100% !important; 
            max-width: 100% !important; 
            overflow-x: hidden !important; 
            box-sizing: border-box !important;
        }
        body { font-family: 'Segoe UI', Arial, sans-serif; background-color: #0b0f19; display: flex; justify-content: center; align-items: center; min-height: 100vh; padding: 15px; }
        .container { width: 100%; max-width: 440px; background: #111827; padding: 35px 20px; border-radius: 24px; text-align: center; color: white; border: 2px solid #38bdf8; box-shadow: 0 0 20px rgba(56, 189, 248, 0.15); }
        h2 { margin-bottom: 5px; font-size: 20px; font-weight: 900; text-transform: uppercase; }
        .sub-tag { font-size: 11px; color: #64748b; text-transform: uppercase; display: block; margin-bottom: 25px; border-bottom: 2px solid rgba(56, 189, 248, 0.2); padding-bottom: 12px; }
        .campo-form { margin-bottom: 18px; text-align: left; }
        .campo-form label { font-weight: bold; display: block; margin-bottom: 8px; font-size: 12px; color: #38bdf8; text-transform: uppercase; }
        .campo-form input { width: 100%; padding: 13px 16px; border: 1px solid #374151; border-radius: 20px; font-size: 16px; background: #0b0f19; color: #ffffff; outline: none; }
        .campo-form input:focus { border-color: #38bdf8; box-shadow: 0 0 8px rgba(56, 189, 248, 0.3); }
        .btn-enviar { width: 100%; background: linear-gradient(135deg, #38bdf8, #0284c7); color: white; border: none; padding: 14px; cursor: pointer; font-size: 14px; border-radius: 20px; font-weight: bold; text-transform: uppercase; outline: none; }
        .erro-msg { background: rgba(239, 68, 68, 0.12); border: 1px solid #ef4444; color: #f87171; padding: 12px; border-radius: 12px; font-size: 13px; margin-bottom: 15px; text-align: left; }
        .links { margin-top: 25px; font-size: 12px; color: #9ca3af; }
        .links p { margin: 6px 0; }
        .links a { color: #38bdf8; text-decoration: none; font-weight: bold; }
    </style>
</head>
<body>
<div class="container">
    <h2>ÁREA DO CLIENTE</h2>
    <span class="sub-tag">Acesso Único ao Ecossistema Aurélius</span>

    <?php if (isset($erro) && is_array($erro) && count($erro) > 0): ?>
        <?php foreach ($erro as $msg): ?>
            <div class="erro-msg">❌ <?= htmlspecialchars($msg); ?></div>
        <?php endforeach; ?>
    <?php endif; ?>

    <form action="" method="POST">
        <div class="campo-form">
            <label>O teu E-mail Cadastrado:</label>
            <input type="email" name="email" id="email" required placeholder="Ex: teu-email@gmail.com" value="<?= isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>">
        </div>
        <div class="campo-form">
            <label>A tua Senha Secreta:</label>
            <input type="password" name="senha" id="senha" required placeholder="••••••••">
        </div>
        <button type="submit" class="btn-enviar">Entrar no Estabelecimento →</button>
        
        <div class="links">
            <!-- 🟢 ADICIONADO: Link direto para recuperação de ID e Senha -->
            <p>Esqueceu as suas credenciais? <a href="recuperar.php">Recuperar Senha / ID</a></p>
            <p>Não tem uma conta? <a href="BrancaCadastar.php">Registe-se na Rede</a></p>
        </div>
    </form>
</div>
</body>
</html>