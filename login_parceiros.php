<?php
// 🟢 GARANTIA MESTRE DO RENDER: Deve ser a linha 1 absoluta do arquivo!
if (session_status() === PHP_SESSION_NONE) { 
    @session_start(); 
}
date_default_timezone_set('Africa/Luanda');

// 🛡️ INICIALIZAÇÃO DE SEGURANÇA: Evita avisos de chaves indefinidas no primeiro acesso
if (!isset($_SESSION['tentativas_login'])) { $_SESSION['tentativas_login'] = 0; }
if (!isset($_SESSION['bloqueio_tempo'])) { $_SESSION['bloqueio_tempo'] = 0; }

// Se fores incluir o Conexao.php ou config, faz logo abaixo
include_once("Conexao.php"); 

// Verifica se o utilizador ainda está dentro do tempo de bloqueio (15 minutos = 900 segundos)
$tempo_atual = time();
if ($_SESSION['tentativas_login'] >= 3 && ($tempo_atual - $_SESSION['bloqueio_tempo']) < 900) {
    $tempo_restante = ceil((900 - ($tempo_atual - $_SESSION['bloqueio_tempo'])) / 60);
    $bloqueado = true;
    $erro = "Excedeu o limite de 3 tentativas. Bloqueado. Restam " . $tempo_restante . " minuto(s).";
} else {
    $bloqueado = false;
    // Se o tempo passou, reseta o contador
    if ($_SESSION['tentativas_login'] >= 3) {
        $_SESSION['tentativas_login'] = 0;
    }
}

// Mecanismo de Logout Seguro
if (isset($_GET['logout'])) {
    session_unset();
    session_destroy();
    header("Location: login_parceiros.php");
    exit();
}

$erro = isset($erro) ? $erro : null;
$sucesso = null;

// PROCESSAMENTO DO LOGIN REATIVO SINCRO
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['acao_login']) && !$bloqueado) {
    // Garante que o índice existe antes de fazer o trim
    $pin_acesso = isset($_POST['pin_acesso']) ? trim($_POST['pin_acesso']) : '';

    if (!empty($pin_acesso)) {
        // 1. Tenta autenticar na tabela de Lojas Mercantis
        $stmt = $pdo->prepare("SELECT id, nome_loja FROM `lojas` WHERE pin_acesso = ? LIMIT 1");
        $stmt->execute([$pin_acesso]);
        $loja = $stmt->fetch();

        if ($loja) {
            $_SESSION['tentativas_login'] = 0; 
            $_SESSION['loja_id'] = $loja['id'];
            header("Location: Admin_Venda.php");
            exit();
        }

        // 2. Tenta autenticar na tabela de Utilizadores (Barbearias)
        $stmt2 = $pdo->prepare("SELECT codigo, nome FROM `usuario` WHERE pin_acesso = ? LIMIT 1");
        $stmt2->execute([$pin_acesso]);
        $barber = $stmt2->fetch();

        if ($barber) {
            $_SESSION['tentativas_login'] = 0; 
            $_SESSION['empresa_codigo'] = $barber['codigo'];
            header("Location: Admin_Barbearias.php");
            exit();
        }

        // Se falhar o PIN: Incrementa o contador de forma isolada
        $_SESSION['tentativas_login']++;
        
        if ($_SESSION['tentativas_login'] >= 3) {
            $_SESSION['bloqueio_tempo'] = time();
            $bloqueado = true;
            $erro = "Excedeu o limite de 3 tentativas. O painel foi bloqueado por segurança.";
        } else {
            $tentativas_restantes = 3 - $_SESSION['tentativas_login'];
            $erro = "PIN inválido. Tem mais $tentativas_restantes tentativa(s).";
        }
    } else {
        $erro = "Por favor, introduza o seu PIN de acesso.";
    }
}
?>
<!DOCTYPE html>
<html lang="pt-PT">
<head>
<!-- Configurações nativas para PWA no iOS e Android -->
<meta name="apple-mobile-web-app-capable" content="yes">
<meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
<meta name="apple-mobile-web-app-title" content="Aurélius">
<link rel="manifest" href="manifest.json">

<script>
// Ativa o Service Worker nos bastidores do navegador do telemóvel
if ('serviceWorker' in navigator) {
    window.addEventListener('load', () => {
        navigator.serviceWorker.register('serviceWorker.js')
            .then(reg => console.log('✓ PWA Aurélius registado com sucesso!', reg))
            .catch(err => console.log('❌ Falha ao registar PWA:', err));
    });
}
</script>
    <meta charset="UTF-8">
    <title>Aurelius - Acesso de Parceiros</title>
    <style>
        body { background: #0f172a; color: #fff; font-family: sans-serif; display: flex; justify-content: center; align-items: center; height: 100vh; margin: 0; }
        .box { background: #1e293b; padding: 30px; border-radius: 12px; border: 1px solid #334155; width: 100%; max-width: 360px; box-shadow: 0 4px 15px rgba(0,0,0,0.3); }
        .input { width: 100%; padding: 12px; background: #0f172a; border: 1px solid #475569; border-radius: 6px; color: white; margin-bottom: 15px; margin-top: 5px; box-sizing: border-box; outline: none; text-align: center; font-size: 18px; letter-spacing: 4px; }
        .input:focus { border-color: #38bdf8; }
        .btn { width: 100%; background: #38bdf8; color: #000; font-weight: bold; padding: 12px; border: none; border-radius: 6px; cursor: pointer; text-transform: uppercase; font-size: 13px; }
        .btn-link { color: #38bdf8; text-decoration: underline; font-size: 13px; display: block; text-align: center; margin-top: 20px; font-weight: bold; }
    </style>
</head>
<body>

<div class="box">

<a class="btn-hub-lojas" href="Lojas.php" style="background: #334155; color: #fff; padding: 8px 16px; text-decoration: none; font-size: 13px; font-weight: 500; border-radius: 6px; text-align: center; flex: 1; min-width: 80px;">Voltar</a>

    <h3 style="color: #38bdf8; margin-bottom: 10px; text-align: center;">Portal de Parceiros Aurelius</h3>
    
    <p style="font-size: 12px; color: #94a3b8; text-align: center; margin-bottom: 20px; line-height: 1.4;">
        <strong>Atenção Parceiro:</strong> Se é o seu primeiro acesso após a atualização, clique no link de recuperação abaixo para gerar o seu PIN.
    </p>
    
    <?php if ($erro): ?>
        <p style="color: #ef4444; font-size: 13px; text-align: center; margin-bottom: 15px; font-weight: bold;">⚠️ <?= $erro ?></p>
    <?php endif; ?>

    <?php if ($bloqueado): ?>
        <div style="text-align: center; background: #7f1d1d; padding: 15px; border-radius: 8px; border: 1px solid #f87171; margin-bottom: 15px;">
            <p style="font-size: 13px; margin: 0 0 10px 0; font-weight: bold;">Painel Bloqueado</p>
            <span style="font-size: 11px; color: #cbd5e1;">Aguarde cerca de <?= isset($tempo_restante) ? $tempo_restante : 5 ?> minutos para tentar novamente.</span>
        </div>
    <?php else: ?>
        <form method="POST" action="">
            <label style="font-size: 12px; color: #94a3b8; font-weight: bold; text-transform: uppercase; display: block; text-align: center;">Introduza o seu PIN:</label>
            <input type="password" name="pin_acesso" class="input" placeholder="••••" inputmode="numeric" pattern="[0-9]*" oninput="if(this.value.length > 9) this.value = this.value.slice(0,9);" required autocomplete="off">

            <button type="submit" name="acao_login" class="btn">Entrar no Painel</button>
        </form>
    <?php endif; ?>

    <!-- Direciona de forma limpa para o ficheiro de recuperação externo -->
    <a href="recuperar.php" class="btn-link">Esqueci-me do meu PIN</a>
</div>

</body>
</html>