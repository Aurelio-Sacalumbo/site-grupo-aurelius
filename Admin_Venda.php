

<?php
// =========================================================================
// 🚀 TOPO ABSOLUTO DO FICHEIRO ADMIN_VENDA.PHP - CONTROLO DE ACESSO E SESSÃO
// =========================================================================
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
date_default_timezone_set('Africa/Luanda');

// IMPORTANTE: Se o seu redirecionamento da linha 69 for uma validação de Login, 
// ele DEVE ser processado aqui em cima, antes de carregar qualquer HTML ou Banco.php!
if (isset($_GET['marcar_lido'])) {
    $seccao = trim($_GET['marcar_lido']);
    $_SESSION['visto_' . $seccao] = date('Y-m-d H:i:s');
    
    $rotas = [
        'vagas'       => 'Vagas.php', 
        'lojas'       => 'Lojas.php', 
        'barbearias'  => 'Principal.php', 
        'sino'        => 'Admin_Venda.php'
    ];
    
    if (isset($rotas[$seccao])) { 
        header("Location: " . $rotas[$seccao]); 
        exit(); 
    }
}

// Agora sim, importa o banco com segurança
require_once __DIR__ . "/config/Banco.php";
?>
<?php
if (session_status() === PHP_SESSION_NONE) { 
    session_start(); 
}

// 🚪 1. MOTOR DE LOGOUT INSTANTÂNEO
if (isset($_GET['logout'])) {
    session_unset();
    $_SESSION = array();
    session_destroy();
    header("Location: login_parceiros.php");
    exit();
}

require_once __DIR__ . "/config/Banco.php";

$pedidos_reais_banco = array();
$nome_parceiro_ativo = "Parceiro Aurélius"; 

// 🔐 2. FILTRAGEM MULTI-INQUILINO & CAPTURA DO NOME DA EMPRESA
if (isset($_SESSION['loja_id'])) {
    $id_dono = $_SESSION['loja_id'];
    
    $stmt_loja = $pdo->prepare("SELECT nome_loja FROM `lojas` WHERE `id` = ? LIMIT 1");
    $stmt_loja->execute([$id_dono]);
    $loja_info = $stmt_loja->fetch();
    if ($loja_info) { $nome_parceiro_ativo = $loja_info['nome_loja']; }

    $stmt = $pdo->prepare("SELECT * FROM `triagem_pedidos` WHERE `loja_id` = ? ORDER BY id DESC");
    $stmt->execute([$id_dono]);
    $pedidos_reais_banco = $stmt->fetchAll(PDO::FETCH_ASSOC);

} elseif (isset($_SESSION['empresa_codigo'])) {
    $id_dono = $_SESSION['empresa_codigo'];
    
    $stmt_barber = $pdo->prepare("SELECT nome FROM `usuario` WHERE `codigo` = ? LIMIT 1");
    $stmt_barber->execute([$id_dono]);
    $barber_info = $stmt_barber->fetch();
    if ($barber_info) { $nome_parceiro_ativo = $barber_info['nome']; }

    $stmt = $pdo->prepare("SELECT * FROM `triagem_pedidos` WHERE `usuario_codigo` = ? ORDER BY id DESC");
    $stmt->execute([$id_dono]);
    $pedidos_reais_banco = $stmt->fetchAll(PDO::FETCH_ASSOC);
} else {
    header("Location: login_parceiros.php");
    exit();
}

// 🗂️ 3. CAPTURA AUTOMÁTICA DO IBAN DO GRUPO AURÉLIUS (DA TABELA CONFIGURACOES)
$stmt_config = $pdo->prepare("SELECT `valor` FROM `configuracoes_plataforma` WHERE `chave` = 'iban_plataforma' LIMIT 1");
$stmt_config->execute();
$config_info = $stmt_config->fetch();
$iban_grupo_aurelius = $config_info ? $config_info['valor'] : "NÃO CONFIGURADO";

// 🔍 4. CONSULTA EXCLUSIVA PARA A ABA MASTER SAAS (DADOS FINANCEIROS GLOBAIS COM INNER JOIN)
$sql_master = "SELECT f.id AS faturamento_id, f.pedido_id, f.valor_bruto, f.comissao_retida, f.valor_liquido, f.status_pagamento, f.data_registro, t.nome AS nome_cliente, t.tipo_atendimento, t.confirmado_na_entrega, l.nome_loja, l.iban_bancario AS iban_loja FROM `faturamento_parceiros` f INNER JOIN `triagem_pedidos` t ON f.pedido_id = t.id INNER JOIN `lojas` l ON f.loja_id = l.id WHERE f.status_pagamento = 'Aguardando_Liberacao_SaaS' ORDER BY f.id ASC";
$auditoria_saas = $pdo->query($sql_master)->fetchAll(PDO::FETCH_ASSOC);

// 🟢 5. MOTOR DE PROCESSAMENTO FINANCEIRO SAAS
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['atualizar_status_SaaS'])) {
    $pedido_id = intval($_POST['pedido_id']);
    $acao      = $_POST['status_acao'];

    if ($acao === 'Aprovado_Pelo_Parceiro') {
        $stmt = $pdo->prepare("UPDATE `triagem_pedidos` SET `status_interno` = 'Aprovado_Aguardando_SaaS' WHERE `id` = ?");
        $stmt->execute([$pedido_id]);

        $stmt_p = $pdo->prepare("SELECT * FROM `triagem_pedidos` WHERE `id` = ? LIMIT 1");
        $stmt_p->execute([$pedido_id]);
        $dados_p = $stmt_p->fetch(PDO::FETCH_ASSOC);

        if ($dados_p) {
            $valor_total = 15000.00; 
            $taxa_saas   = 10.00;    
            $valor_comissao = ($valor_total * $taxa_saas) / 100;
            $valor_liquido  = $valor_total - $valor_comissao;
            $loja_id_alvo   = $dados_p['loja_id'];

            $stmt_fatura = $pdo->prepare("INSERT INTO `faturamento_parceiros` (loja_id, pedido_id, valor_bruto, comissao_retida, valor_liquido, status_pagamento, data_registro) VALUES (?, ?, ?, ?, ?, 'Aguardando_Liberacao_SaaS', NOW())");
            $stmt_fatura->execute([$loja_id_alvo, $pedido_id, $valor_total, $valor_comissao, $valor_liquido]);
        }
        echo "<script>alert('✓ Fatura aprovada e direcionada para a Central Aurélius.'); window.location.href='Admin_Venda.php';</script>";
        exit();
    }
}

// 💸 6. MOTOR DE LIQUIDAÇÃO CENTRAL SAAS (REPASSE MANUAL)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['liberar_pagamento_saas'])) {
    $faturamento_id = intval($_POST['faturamento_id']);
    $pedido_id      = intval($_POST['pedido_id']);

    $pdo->prepare("UPDATE `faturamento_parceiros` SET `status_pagamento` = 'Liquido_Transferido_Ao_Parceiro' WHERE `id` = ?")->execute([$faturamento_id]);
    $pdo->prepare("UPDATE `triagem_pedidos` SET `status_interno` = 'Finalizado_Concluido' WHERE `id` = ?")->execute([$pedido_id]);

    echo "<script>alert('💸 Sucesso! Transferência manual efetuada. O valor residual foi enviado para o IBAN do parceiro.'); window.location.href='Admin_Venda.php';</script>";
    exit();
}
?>
<!DOCTYPE html>
<html lang="pt-PT">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Backoffice Aurélius - Triagem de Vendas</title>
    <style>
        body { background: #0f172a; color: #f8fafc; font-family: 'Segoe UI', Arial, sans-serif; margin: 0; padding: 20px; display: flex; justify-content: center; min-height: 100vh; }
        .SaaS-vendas-container { max-width: 1200px; width: 100%; margin: 0 auto; }
        .topo-vendas-SaaS { background: #1e293b; padding: 25px; border-radius: 16px; border: 1px solid #334155; margin-bottom: 20px; box-shadow: 0 4px 20px rgba(0,0,0,0.4); }
        .menu-abas { display: flex; gap: 10px; margin-bottom: 25px; border-bottom: 2px solid #1f2937; padding-bottom: 10px; }
        .aba-btn { background: #111827; border: 1px solid #334155; color: #94a3b8; padding: 10px 20px; border-radius: 8px; cursor: pointer; font-weight: bold; font-size: 13px; }
        .aba-btn.ativa { background: #38bdf8; color: #000; border-color: #38bdf8; }
        .aba-painel { display: none; }
        .aba-painel.ativa { display: block; }
        .grid-vendas-cards { display: grid; grid-template-columns: repeat(auto-fill, minmax(340px, 1fr)); gap: 20px; width: 100%; }
        .card-pedido-SaaS { background: #1e293b; border: 1px solid #334155; border-radius: 16px; padding: 20px; box-shadow: 0 10px 15px -3px rgba(0,0,0,0.3); display: flex; flex-direction: column; justify-content: space-between; }
        .btn-SaaS-acao { border: none; color: #fff; padding: 12px; border-radius: 8px; font-weight: bold; font-size: 11px; cursor: pointer; text-transform: uppercase; }
        .tabela-financeira { width: 100%; border-collapse: collapse; background: #1e293b; border-radius: 12px; overflow: hidden; border: 1px solid #334155; }
        .tabela-financeira th { background: #111827; color: #eab308; text-transform: uppercase; font-size: 11px; padding: 15px; text-align: left; }
        .tabela-financeira td { padding: 15px; border-bottom: 1px solid #1f2937; font-size: 13px; color: #cbd5e1; }
    </style>
</head>
<body>
    
    <div class="SaaS-vendas-container">
        
        <!-- Cabeçalho -->
        <div class="topo-vendas-SaaS" style="display: flex; justify-content: space-between; align-items: center;">
            <div style="text-align: left;">
                <div style="display: flex; align-items: center; gap: 8px; margin-bottom: 5px;">
                    <span style="color: #38bdf8; font-size: 11px; font-weight: bold; text-transform: uppercase;">🛒 Ecosistema Aurélius</span>
                    <span style="background: rgba(234, 179, 8, 0.1); border: 1px solid #eab308; color: #eab308; font-size: 10px; padding: 2px 8px; border-radius: 4px; font-weight: bold;">🏬 <?= htmlspecialchars($nome_parceiro_ativo) ?></span>
                </div>
                <h2 style="color: #fff; margin: 0; font-size: 20px;">📦 Central Unificada de Distribuição</h2>
            </div>
            <a href="?logout=true" style="background: rgba(239, 68, 68, 0.1); border: 1px solid #ef4444; color: #ef4444; padding: 10px 18px; border-radius: 8px; font-size: 12px; font-weight: bold; text-transform: uppercase; text-decoration: none;">🚪 Sair</a>
        </div>

        <!-- 🗂️ Menu de Abas Internas (Imbutido para Economia de Espaço) -->
        <div class="menu-abas">
            <button class="aba-btn ativa" onclick="mudarAbaInterna(1)">📋 As Minhas Vendas / Triagem</button>
            <button class="aba-btn" onclick="mudarAbaInterna(2)" style="border-color: #ca8a04; color: #eab308;">🛡️ Mesa de Controlo SaaS (Grupo Aurélius)</button>
        </div>
    
        <!-- =========================================================================
             Aba 1: Painel do Parceiro (Faturas da Loja)
             ========================================================================= -->
        <div id="aba-interna-1" class="aba-painel ativa">
            <div class="grid-vendas-cards">
                <?php if (empty($pedidos_reais_banco)): ?>
                    <p style="color: #64748b; text-align: center; grid-column: 1/-1; font-style: italic; padding: 40px; background: #111827; border-radius: 12px; border: 1px dashed #1f2937; width: 100%;">Nenhuma fatura localizada para o seu perfil.</p>
                <?php else: ?>
                    <?php foreach ($pedidos_reais_banco as $pedido): ?>
                        <?php 
                            $status_atual = isset($pedido['status_interno']) ? $pedido['status_interno'] : 'Pendente Triagem';
                            $is_domicilio = (isset($pedido['tipo_atendimento']) && $pedido['tipo_atendimento'] === 'Domicilio');
                            $confirmado_entrega = (isset($pedido['confirmado_na_entrega']) && $pedido['confirmado_na_entrega'] == 1);
                        ?>
                        <div class="card-pedido-SaaS">
                            <div style="border-bottom: 1px solid #1f2937; padding-bottom: 10px; margin-bottom: 15px;">
                                <strong style="color: #38bdf8; font-size: 15px; display: block;"><?= htmlspecialchars($pedido['nome']) ?></strong>
                                "><b><?= htmlspecialchars($pedido['nome']) ?></b></strong>
                                <span style="color: #64748b; font-size: 11px;">📅 Data: <?= htmlspecialchars($pedido['data']) ?></span>
                            </div>
                            <div style="font-size: 13px; color: #cbd5e1; line-height: 1.6; margin-bottom: 15px;">
                                <p style="margin: 4px 0;">🏬 <b>Empresa:</b> <?= htmlspecialchars($pedido['loja']) ?></p>
                                <p style="margin: 4px 0;">📍 <b>Sede:</b> <?= htmlspecialchars($pedido['local']) ?></p>
                                <p style="margin: 4px 0;">📞 <b>Contacto:</b> <?= htmlspecialchars($pedido['telefone']) ?></p>
                                <p style="margin: 4px 0;">🚚 <b>Fluxo:</b> <span style="color: #a855f7; font-weight: bold;"><?= $is_domicilio ? 'Domiciliário' : 'Balcão' ?></span></p>
                            </div>
                            
                            <div>
                                <?php if ($status_atual === 'Pendente Triagem' || $status_atual === 'Em Análise'): ?>
                                    <?php if ($is_domicilio && !$confirmado_entrega): ?>
                                        <button type="button" class="btn-SaaS-acao" style="background: #475569; width: 100%; cursor: not-allowed;" disabled>⏳ Aguardar Entrega (Cliente)</button>
                                    <?php else: ?>
                                        <form method="POST">
                                            <input type="hidden" name="pedido_id" value="<?= $pedido['id'] ?>">
                                            <input type="hidden" name="status_acao" value="Aprovado_Pelo_Parceiro">
                                            <button type="submit" name="atualizar_status_SaaS" class="btn-SaaS-acao" style="background: #16a34a; width: 100%;">✓ APROVAR FATURA</button>
                                        </form>
                                    <?php endif; ?>
                                <?php else: ?>
                                    <button type="button" class="btn-SaaS-acao" style="background: #1e293b; color: #64748b; width: 100%; border: 1px solid #334155; cursor: not-allowed;" disabled>🔒 Enviado ao SaaS Central</button>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>

        <!-- =========================================================================
             Aba 2: Mesa Master SaaS (Auditoria Aurélius com IBAN Automático)
             ========================================================================= -->
        <div id="aba-interna-2" class="aba-painel">
            <div style="background: rgba(202, 138, 4, 0.05); border: 1px dashed #ca8a04; padding: 20px; border-radius: 12px; margin-bottom: 25px;">
                <span style="color: #eab308; font-size: 11px; font-weight: bold; text-transform: uppercase; letter-spacing: 0.5px;">🏦 CONTA DE CUSTÓDIA CENTRAL (GRUPO AURÉLIUS SAAS):</span>
                <p style="margin: 8px 0 0 0; font-family: monospace; font-size: 16px; color: #fff; font-weight: bold; letter-spacing: 1px;">💳 IBAN ARRECADAÇÃO: <?= htmlspecialchars($iban_grupo_aurelius) ?></p>
            </div>
            
            <table class="tabela-financeira">
                <thead>
                    <tr>
                        <th>Parceiro / Distribuidora</th>
                        <th>Cliente / Tipo</th>
                        <th>Entrega Conf.</th>
                        <th>Valor Bruto</th>
                        <th>Taxa SaaS (10%)</th>
                        <th>Líquido Parceiro</th>
                        <th>IBAN Destino (Parceiro)</th>
                        <th>Ação Central</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($auditoria_saas)): ?>
                        <tr><td colspan="8" style="text-align: center; color: #64748b; padding: 40px; font-style: italic;">Nenhum repasse mercantil aguardando liquidação neste ciclo.</td></tr>
                    <?php else: ?>
                        <?php foreach ($auditoria_saas as $row): ?>
                            <tr>
                                <td><strong><?= htmlspecialchars($row['nome_loja']) ?></strong></td>
                                <td><?= htmlspecialchars($row['nome_cliente']) ?><br><small style="color:#a855f7; font-weight: bold;"><?= htmlspecialchars($row['tipo_atendimento']) ?></small></td>
                                <td style="color: <?= $row['confirmado_na_entrega'] == 1 ? '#22c55e' : '#ef4444' ?>; font-weight: bold;"><?= $row['tipo_atendimento'] !== 'Domicilio' ? 'Balcão' : ($row['confirmado_na_entrega'] == 1 ? '✓ Sim' : '⏳ Não') ?></td>
                                <td style="font-weight: bold; color: #fff;"><?= number_format($row['valor_bruto'], 2, ',', '.') ?> Kz</td>
                                <td style="color: #ef4444; font-weight: bold;">-<?= number_format($row['comissao_retida'], 2, ',', '.') ?> Kz</td>
                                <td style="color: #22c55e; font-weight: bold; font-size: 14px;"><?= number_format($row['valor_liquido'], 2, ',', '.') ?> Kz</td>
                                <td style="color: #eab308; font-family: monospace; font-weight: bold;"><?= htmlspecialchars($row['iban_loja']) ?></td>
                                <td>
                                    <form method="POST" onsubmit="return confirm('Autorizar transferência de <?= number_format($row['valor_liquido'], 2, ',', '.') ?> Kz para o IBAN cadastrado?');" style="margin:0;">
                                        <input type="hidden" name="faturamento_id" value="<?= $row['faturamento_id'] ?>">
                                        <input type="hidden" name="pedido_id" value="<?= $row['pedido_id'] ?>">
                                        <button type="submit" name="liberar_pagamento_saas" style="background: #22c55e; border: none; padding: 10px 14px; border-radius: 6px; font-weight: bold; cursor: pointer; color: #000; font-size: 11px; text-transform: uppercase; letter-spacing: 0.5px;">💸 Transferir</button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

    </div>

    <!-- =========================================================================
         🟩 CONTROLADOR JAVASCRIPT: NAVEGAÇÃO DE ABAS INTERNAS
       ========================================================================= -->
    <script>
    function mudarAbaInterna(numero) {
        // Remove a classe ativa de todos os painéis e botões
        document.querySelectorAll('.aba-painel').forEach(p => p.classList.remove('ativa'));
        document.querySelectorAll('.aba-btn').forEach(b => b.classList.remove('ativa'));
        
        // Ativa o painel selecionado e o respetivo botão
        document.getElementById('aba-interna-' + numero).classList.add('ativa');
        event.target.classList.add('ativa');
    }
    </script>
</body>
</html>