<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
date_default_timezone_set('Africa/Luanda');

// 1. IMPORTAÇÃO DA LIGAÇÃO MESTRE DO ECOSSISTEMA
require_once __DIR__ . "/config/Banco.php";

// Deteta de forma segura a variável ativa do driver híbrido
$mysqli = $conexao_link ?? $conexao_aurelius ?? $conexao ?? null;

if (!$mysqli || !($mysqli instanceof mysqli) || @mysqli_ping($mysqli) === false) {
    $db_host = getenv('DB_HOST') ?: "altaria.proxy.rlwy.net";
    $db_port = getenv('DB_PORT') ?: "52030";
    $db_name = getenv('DB_NAME') ?: "railway";
    $db_user = getenv('DB_USER') ?: "root";
    $db_pass = getenv('DB_PASSWORD') ?: "tPzDwXGkyczyyYdcyvLmHLSMmfZmnMIZ";
    
    $mysqli = @mysqli_connect($db_host, $db_user, $db_pass, $db_name, $db_port);
}

$erro = [];
$sucesso_cadastro = false;

// Determina dinamicamente o nome do arquivo atual para saber em que cadastro o cliente está
// Exemplo: se o arquivo se chamar BrancaCadastar.php, a base de destino será BarbeariaBranca
$script_nome = basename($_SERVER['SCRIPT_NAME'], '.php');
if ($script_nome === 'BrancaCadastar') {
    $barbearia_alvo = 'BarbeariaBranca';
} else {
    // Para futuros arquivos que replicares (ex: LOOKNOVOCadastar -> LOOKNOVO)
    $barbearia_alvo = str_replace('Cadastar', '', $script_nome);
}

// =========================================================================
// 🚀 MOTOR DE PROCESSAMENTO EXCLUSIVO PARA CLIENTES DA BARBEARIA
// =========================================================================
if (isset($_POST['cadastrar'])) {
    $nome     = isset($_POST['nome']) ? mysqli_real_escape_string($mysqli, trim($_POST['nome'])) : '';
    $email    = isset($_POST['email']) ? mysqli_real_escape_string($mysqli, trim($_POST['email'])) : '';
    $telefone = isset($_POST['telefone']) ? mysqli_real_escape_string($mysqli, trim($_POST['telefone'])) : '';
    $senha_raw = isset($_POST['senha']) ? trim($_POST['senha']) : '';
    $endereco = isset($_POST['endereco']) ? mysqli_real_escape_string($mysqli, trim($_POST['endereco'])) : '';

    if (empty($nome) || empty($email) || empty($senha_raw)) {
        $erro[] = "Por favor, preencha todos os campos obrigatórios.";
    }

    if (empty($erro)) {
        // Valida se o email já existe no ecossistema global
        $check_email = mysqli_query($mysqli, "SELECT codigo FROM `usuario` WHERE `email` = '$email' LIMIT 1");
        if ($check_email && mysqli_num_rows($check_email) > 0) {
            $erro[] = "Este endereço de e-mail já se encontra registado.";
        }
    }

    if (empty($erro)) {
        $senha_criptografada = md5($senha_raw);
        
        // Parâmetros de criação fixados para o nível de 'cliente'
        $status_inicial = 'Confirmado'; // Clientes entram ativos de forma automática
        $visivel = 1;
        $nivel = 'cliente'; 
        $logo_padrao = 'OIP (6).webp';
        $data_hoje = date('Y-m-d');

        // Insere o cliente guardando a amarração da barbearia de origem na coluna slug
        $sql_insert = "INSERT INTO `usuario` 
            (`nome`, `email`, `telefone`, `senha`, `endereco`, `transacao_status`, `visivel_no_site`, `nivel`, `slug`, `logo_empresa`, `data`) 
            VALUES 
            ('$nome', '$email', '$telefone', '$senha_criptografada', '$endereco', '$status_inicial', $visivel, '$nivel', '$barbearia_alvo', '$logo_padrao', '$data_hoje')";

        if (mysqli_query($mysqli, $sql_insert)) {
            $sucesso_cadastro = true;
            
            // Inicia a autenticação automática do cliente na sessão local
            $_SESSION['cliente_logado'] = true;
            $_SESSION['cliente_id']     = mysqli_insert_id($mysqli);
            $_SESSION['cliente_nome']   = $nome;
            $_SESSION['cliente_email']  = $email;
            $_SESSION['cliente_barbearia'] = $barbearia_alvo;
        } else {
            $erro[] = "Erro técnico ao gravar dados no Railway: " . mysqli_error($mysqli);
        }
    }
}
?>
<<!DOCTYPE html>
<html lang="pt-PT">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>Registo de Utilizador - Grupo Aurélius</title>
    <style>
        html, body { width: 100% !important; max-width: 100% !important; overflow-x: hidden !important; margin: 0 !important; padding: 0 !important; box-sizing: border-box !important; }
        body { font-family: 'Segoe UI', Arial, sans-serif; background-color: #0b0f19; display: flex; justify-content: center; align-items: center; min-height: 100vh; padding: 15px !important; }
        .container { width: 100% !important; max-width: 440px !important; background: rgba(0, 24, 39, 0.96); padding: 25px 20px; border-radius: 20px; box-shadow: 0 10px 25px rgba(0,0,0,0.5); border: 2px solid #22c55e; color: white; text-align: center; box-sizing: border-box; }
        .container h2 { margin: 0; color: #ffffff; font-size: 20px; text-transform: uppercase; font-weight: 900; }
        .sub-tag { font-size: 10.5px; color: #64748b; text-transform: uppercase; display: block; margin-bottom: 20px; border-bottom: 2px solid rgba(34, 197, 94, 0.2); padding-bottom: 8px; }
        .campo-form { margin-bottom: 14px; text-align: left; }
        .campo-form label { font-weight: bold; display: block; margin-bottom: 6px; font-size: 11.5px; color: #a7f3d0; text-transform: uppercase; }
        .campo-form input { width: 100%; padding: 11px 14px; border: 1px solid #374151; border-radius: 14px; box-sizing: border-box; font-size: 13.5px; background: #0b0f19; color: #ffffff; outline: none; }
        .campo-form input:focus { border-color: #22c55e; }
        .btn-enviar { width: 100%; background: linear-gradient(135deg, #22c55e, #16a34a); color: white; border: none; padding: 12px; cursor: pointer; font-size: 14px; border-radius: 14px; font-weight: bold; margin-top: 8px; text-transform: uppercase; }
        .btn-voltar { display: block; width: 100%; text-align: center; background: #374151; color: white; border: 1px solid #4b5563; padding: 11px; font-size: 13px; border-radius: 14px; font-weight: bold; text-decoration: none; margin-top: 10px; text-transform: uppercase; }
    </style>
</head>
<body>

<div class="container">
    <h2>Criar Nova Conta</h2>
    <span class="sub-tag">Inscrição de Lojas e Barbearias Parceiras</span>

    <!-- 🟢 SINAL DE SUCESSO DINÂMICO E AUTOMÁTICO -->
    <?php if (isset($sucesso_cadastro) && $sucesso_cadastro): ?>
        <div style="background: rgba(34, 197, 94, 0.2); border: 2px solid #22c55e; color: #4ade80; padding: 15px; border-radius: 16px; font-size: 13px; margin-bottom: 20px; text-align: center; font-weight: bold;">
            🎉 REGISTO FEITO COM SUCESSO!<br>
            <span style="font-size: 11px; color: #cbd5e1; font-weight: normal; display: block; margin-top: 5px;">A entrar na página da barbearia selecionada...</span>
        </div>

        <script>
            setTimeout(function() {
                <?php
                // Constrói o nome do ficheiro que o cliente quer aceder (ex: BarbeariaBranca.php)
                $destino_ficheiro = $barbearia_alvo . ".php";
                
                // Valida na raiz se o ficheiro físico existe de verdade no teu projeto
                if (file_exists(__DIR__ . "/" . $destino_ficheiro)) {
                    // Se o ficheiro existir, redireciona o cliente diretamente para lá
                    echo 'window.location.href = "' . htmlspecialchars($destino_ficheiro) . '";';
                } else {
                    // Se não existir, devolve o cliente à Principal.php com um aviso claro
                    echo 'window.location.href = "Principal.php?erro_pagina=1&nome_tentado=' . urlencode($barbearia_alvo) . '";';
                }
                ?>
            }, 2000);
        </script>
    <?php endif; ?>

    <?php if (isset($erro) && count($erro) > 0): ?>
        <div style="background: rgba(239, 68, 68, 0.12); border: 1px solid #ef4444; color: #f87171; padding: 10px; border-radius: 12px; font-size: 12px; margin-bottom: 15px; text-align: left;">
            <?php foreach ($erro as $e): ?>
                <p style="margin: 3px 0;">❌ <?php echo htmlspecialchars($e); ?></p>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

    <form action="" method="POST">
        <div class="campo-form">
            <label for="nome">Nome do Utilizador / Cliente:</label>
            <input type="text" name="nome" id="nome" required placeholder="Ex: Teu Nome Completo" value="<?php echo isset($_POST['nome']) ? htmlspecialchars($_POST['nome']) : ''; ?>">
        </div>

        <div class="campo-form">
            <label for="email">Teu E-mail de Acesso:</label>
            <input type="email" name="email" id="email" required placeholder="Ex: cliente@gmail.com" value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>">
        </div>

        <div class="campo-form">
            <label for="telefone">Telemóvel de Contacto:</label>
            <input type="text" name="telefone" id="telefone" placeholder="Ex: 923000000" value="<?php echo isset($_POST['telefone']) ? htmlspecialchars($_POST['telefone']) : ''; ?>">
        </div>

        <div class="campo-form">
            <label for="senha">Palavra-passe Privada:</label>
            <input type="password" name="senha" id="senha" required placeholder="Crie uma senha de alta segurança">
        </div>

        <div class="campo-form">
            <label for="endereco">Teu Endereço / Bairro:</label>
            <input type="text" name="endereco" id="endereco" placeholder="Ex: Bairro Macolocolo, Huambo" value="<?php echo isset($_POST['endereco']) ? htmlspecialchars($_POST['endereco']) : ''; ?>">
        </div>

        <button type="submit" name="cadastrar" class="btn-enviar">Concluir Registo</button>
        <a href="Principal.php" class="btn-voltar">✕ Cancelar e Voltar</a>
    </form>
</div>

</body>
</html>