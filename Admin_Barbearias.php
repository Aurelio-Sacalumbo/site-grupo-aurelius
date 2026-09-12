<?php
if (session_status() === PHP_SESSION_NONE) { 
    session_start(); 
}
require_once __DIR__ . "/config/Banco.php";

// 🛰️ SIMULADOR LOCAL: Força a troca de sessão para testar as barbearias sem ser expulso no ambiente de testes
if (isset($_GET['forcar_barbearia'])) {
    $_SESSION['empresa_codigo'] = intval($_GET['forcar_barbearia']);
    unset($_SESSION['loja_id']); // Limpa resíduos do painel de lojas mercantis
    header("Location: Admin_Barbearias.php");
    exit();
}

// 🛡️ CADEADO DE SEGURANÇA MESTRE MULTI-TENANT: Ativa o redirecionamento imediato se não houver sessão ativa
if (!isset($_SESSION['empresa_codigo']) || empty($_SESSION['empresa_codigo'])) {
    header("Location: login_parceiros.php");
    exit();
}

// Captura única, trancada e blindada vinda da sessão do barbeiro autenticado no servidor XAMPP
$id_barbearia_logada = intval($_SESSION['empresa_codigo']);

// MOTOR DE PROCESSAMENTO E ASSINATURA DIGITAL (MANTIDO E ISOLADO)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['finalizar_trabalho'])) {
    $id_pag = intval($_POST['id_pagamento']);
    $assinatura = trim($_POST['assinatura_cliente']);

    // 🔒 BARREIRA ANTIFRAUDE: Confirma se a assinatura condiz com o registo de segurança do agendamento
    $verificar = $pdo->prepare("SELECT `cliente_telefone` FROM `pagamentos` WHERE `id_pagamento` = ? AND `id_parceiro` = ? AND `tipo_parceiro` = 'barbearia'");
    $verificar->execute([$id_pag, $id_barbearia_logada]);
    $resultado = $verificar->fetch();

    if ($resultado && $assinatura === $resultado['cliente_telefone']) {
        // Atualiza o estado do atendimento e aciona o aviso luminoso no painel master (visto_admin = 0)
        $update = $pdo->prepare("UPDATE `pagamentos` SET `status_trab` = 'Concluido', `status_atendimento` = 'Confirmado', `assinatura_cliente` = ?, `visto_admin` = 0 WHERE `id_pagamento` = ?");
        $update->execute([$assinatura, $id_pag]);
        
        echo "<script>
                alert('✓ Atendimento assinado e finalizado com sucesso! A gerar o Cupão de Fecho de Caixa.'); 
                window.location.href='fatura_cupao.php?id_pagamento=$id_pag';
              </script>";
        exit();
    } else {
        echo "<script>alert('❌ Erro de Segurança: A assinatura digital (Telefone/BI) não confere com o titular agendado!');</script>";
    }
}

// 🟢 ISOLAMENTO MESTRE: Procura os agendamentos pendentes apenas desta barbearia ativa
$query = $pdo->prepare("SELECT * FROM `pagamentos` WHERE `id_parceiro` = ? AND `tipo_parceiro` = 'barbearia' AND `status_trab` = 'Pendente' ORDER BY id_pagamento DESC");
$query->execute([$id_barbearia_logada]);
$agendamentos = $query->fetchAll(PDO::FETCH_ASSOC);

// Coleta o nome corporativo real do utilizador parceiro para o cabeçalho
$busca_nome = $pdo->prepare("SELECT nome FROM `usuario` WHERE codigo = ? LIMIT 1");
$busca_nome->execute([$id_barbearia_logada]);
$salao_info = $busca_nome->fetch();
$nome_salao_atual = $salao_info ? $salao_info['nome'] : "Salão Parceiro";
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
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Aurelius Business - Painel Operacional</title>
    <style>
        body { background: #0f172a; color: #fff; font-family: sans-serif; padding: 0; margin: 0; }
        .barra-testes { background: #1e293b; padding: 10px; text-align: center; border-bottom: 2px dashed #22c55e; font-size: 12px; }
        .barra-testes a { color: #22c55e; text-decoration: none; margin: 0 10px; font-weight: bold; }
        .barra-testes a:hover { color: aqua; }
        .container { max-width: 800px; margin: 40px auto; padding: 0 20px; }
        .card-pedido { background: #1e293b; border: 1px solid #334155; padding: 20px; border-radius: 12px; margin-bottom: 15px; box-shadow: 0 4px 10px rgba(0,0,0,0.1); transition: border-color 0.2s; }
        .card-pedido:hover { border-color: #38bdf8; }
        .input-text { padding: 12px; background: #0f172a; color: white; border: 1px solid #374151; border-radius: 6px; width: 240px; outline: none; }
        .input-text:focus { border-color: #38bdf8; }
        .btn-confirmar { background: #22c55e; color: #000; padding: 12px 20px; border: none; border-radius: 6px; font-weight: bold; cursor: pointer; text-transform: uppercase; font-size: 12px; transition: background 0.2s; }
        .btn-confirmar:hover { background: #4ade80; }
        .header-painel { display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; border-bottom: 1px solid #334155; padding-bottom: 15px; }
        .badge-loja { background: rgba(56, 189, 248, 0.1); padding: 6px 12px; border-radius: 20px; font-size: 12px; color: #38bdf8; font-weight: bold; border: 1px solid rgba(56, 189, 248, 0.2); }
        .btn-sair { background: rgba(239, 68, 68, 0.1); border: 1px solid #ef4444; color: #f87171; padding: 6px 14px; text-decoration: none; border-radius: 6px; font-size: 12px; font-weight: bold; text-transform: uppercase; transition: all 0.2s; }
        .btn-sair:hover { background: #dc2626; color: #fff; }
    </style>
</head>
<body>

    <!-- 🛰️ SIMULADOR DE BARBEARIAS PARA AMBIENTE DE DESENVOLVIMENTO -->
    <div class="barra-testes">
        🛠️ <b>Simulador de Barbearias Aurelius:</b> 
        <a href="Admin_Barbearias.php?forcar_barbearia=237">Barbearia Branca (237)</a> | 
        <a href="Admin_Barbearias.php?forcar_barbearia=238">Aurelio Jamba (238)</a> | 
        <a href="Admin_Barbearias.php?forcar_barbearia=239">Aurelio Jamba (239)</a>
    </div>

    <div class="container">
        <div class="header-painel">
            <h2 style="margin: 0; font-size: 20px;">✂️ Central Operacional do Barbeiro</h2>
            <div style="display: flex; align-items: center;">
                <span class="badge-loja">💈 <?= htmlspecialchars($nome_salao_atual) ?> (ID: <?= $id_barbearia_logada ?>)</span>
                <a href="login_parceiros.php?logout=1" class="btn-sair" style="margin-left: 10px;">Sair</a>
            </div>
        </div>
        <p style="color: #94a3b8; margin-bottom: 25px; font-size: 14px;">Confirme os serviços executados no salão recolhendo a assinatura digital do cliente para libertar o pagamento no SaaS.</p>

        <?php if(!empty($agendamentos)): ?>
            <?php foreach($agendamentos as $row): ?>
                <div class="card-pedido">
                    <h4 style="color: #38bdf8; margin: 0 0 8px 0; font-size: 15px;">📅 Atendimento Agendado Nº <?= intval($row['id_pagamento']) ?></h4>
                    <p style="margin: 5px 0; font-size: 14px;">Serviço Reservado: <strong style="color:#fff;"><?= htmlspecialchars($row['servico']) ?></strong></p>
                    <p style="margin: 5px 0; font-size: 14px;">Nome do Cliente: <b><?= htmlspecialchars($row['cliente']) ?></b></p>
                    <p style="margin: 5px 0; font-size: 14px;">Balanço Custodiado: <strong style="color:#eab308;"><?= number_format($row['valor'], 2, ',', '.') ?> AOA</strong></p>
                    
                    <form method="POST" action="" style="margin-top: 20px; display: flex; flex-wrap: wrap; gap: 10px; align-items: center;">
                        <input type="hidden" name="finalizar_trabalho" value="1">
                        <input type="hidden" name="id_pagamento" value="<?= intval($row['id_pagamento']) ?>">
                        
                        <label style="font-size: 13px; color:#cbd5e1; font-weight: bold;">Assinatura Digital (Telefone ou BI):</label>
                        <input type="tel" name="assinatura_cliente" class="input-text" placeholder="Ex: 925347372" required autocomplete="off">
                        
                        <button type="submit" class="btn-confirmar">Confirmar Execução</button>
                    </form>
                </div>
            <?php endforeach; ?>
        <?php else: ?>
            <div style="text-align:center; padding:40px; background:#1e293b; border-radius:12px; border:1px dashed #334155; color:#94a3b8; font-style:italic; font-size: 14px;">
                Não existem serviços ou agendamentos pendentes na sua agenda de atendimento hoje.
            </div>
        <?php endif; ?>
    </div>

</body>
</html>