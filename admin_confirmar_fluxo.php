<?php
// =========================================================================
// 📊 CENTRAL DE AUDITORIA OMNICHANNEL - SINCRO REAL DE PRODUTOS & SERVIÇOS (PDO)
// =========================================================================
if (!isset($_SESSION)) { 
    session_start(); 
}
date_default_timezone_set('Africa/Luanda');

include_once("Conexao.php");

// Blindagem de normalização da conexão PDO com o XAMPP local
if (!isset($pdo)) {
    try {
        $pdo = new PDO("mysql:host=127.0.0.1;dbname=aurelius_salao;charset=utf8", "root", "");
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    } catch (PDOException $e) {
        die("Erro de infraestrutura de rede: " . $e->getMessage());
    }
}

$mensagem_central = "";

// ⚡ 1. CONTROLADOR DE FISCALIZAÇÃO FINANCEIRA (VALIDAR OU ESTORNAR PRODUTOS/SERVIÇOS)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['fiscalizar_registro_global'])) {
    $id_registro   = intval($_POST['id_registro']);
    $tabela_alvo   = $_POST['tabela_origem'];
    $status_alvo   = $_POST['status_alvo'];
    
    try {
        if ($tabela_alvo === 'produtos_cosmeticos') {
            // Se o admin agir sobre o stock das lojas parceiras, altera a coluna de controle stock
            $stmt = $pdo->prepare("UPDATE produtos_cosmeticos SET stock = ? WHERE id = ?");
            $stmt->execute([$status_alvo, $id_registro]);
        } else {
            // Age diretamente na tabela pagamentos do balcão
            $stmt = $pdo->prepare("UPDATE pagamentos SET status_atendimento = ? WHERE id_pagamento = ?");
            $stmt->execute([$status_alvo, $id_registro]);
        }
        $mensagem_central = ($status_alvo === 'Concluido_Pago' || $status_alvo === 'Disponível') 
            ? "✅ Transação e stock validados com sucesso! Fluxo de informações sintonizado."
            : "🚨 Registro Estornado! O dinheiro/stock falso foi limpo do ecossistema com sucesso.";
    } catch (PDOException $e) {
        $mensagem_central = "❌ Falha ao atualizar banco de dados: " . $e->getMessage();
    }
}

// 🧠 2. ALGORITMO DE CALCULADORA DE CAIXA DIAL REAL (LÊ AS COLUNAS VALOR E PRECO)
$faturamento_real_acumulado = 0.00;
try {
    // Soma os serviços confirmados e pagos do balcão
    $res_balcao = $pdo->query("SELECT SUM(valor) AS total FROM pagamentos WHERE status_atendimento = 'Concluido_Pago' OR status_atendimento = 'Confirmado'");
    $row_balcao = $res_balcao->fetch(PDO::FETCH_ASSOC);
    $faturamento_real_acumulado += floatval($row_balcao['total']);

    // Soma os produtos reais vendidos e ativos no marketplace das lojas
    $res_market = $pdo->query("SELECT SUM(preco * stock_atual) AS total FROM produtos_cosmeticos WHERE stock = 'Disponível'");
    $row_market = $res_market->fetch(PDO::FETCH_ASSOC);
    $faturamento_real_acumulado += floatval($row_market['total']);
} catch (PDOException $e) {
    // Ignora falhas se a soma vier nula
}

// 💈 3. CONSULTA DOS REGISTOS DO BALCÃO (PAGAMENTOS - LÊ A COLUNA REAL 'VALOR')
try {
    $stmt_balcao = $pdo->query("SELECT id_pagamento AS id, servico AS descricao, valor AS valor, status_atendimento AS estado, 'pagamentos' AS tabela_origem, CONCAT('Mestre: ', profissional) AS categoria_tipo FROM pagamentos ORDER BY id_pagamento DESC LIMIT 50");
    $registros_balcao = $stmt_balcao->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $registros_balcao = [];
}

// 🏬 4. SINCRO REAL DAS LOJAS: CONSULTA DA SUA TABELA 'PRODUTOS_COSMETICOS' EXIBIDA NO LOG
try {
    $stmt_market = $pdo->query("SELECT id AS id, nome_produto AS descricao, preco AS valor, stock AS estado, 'produtos_cosmeticos' AS tabela_origem, CONCAT('Loja ID: ', empresa_id) AS categoria_tipo, stock_atual, tamanho, cor_branca FROM produtos_cosmeticos ORDER BY id DESC LIMIT 50");
    $registros_market = $stmt_market->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $registros_market = [];
}
?>
<!DOCTYPE html>
<html lang="pt-PT">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Central de Auditoria Global - Grupo Aurélius</title>
    <style>
        body { font-family: 'Segoe UI', Arial, sans-serif; background: #0f172a; color: #f8fafc; padding: 30px; margin: 0; box-sizing: border-box; }
        .container-central { max-width: 1200px; margin: 0 auto; background: #1e293b; padding: 30px; border-radius: 16px; border: 1px solid #334155; box-shadow: 0 15px 30px rgba(0,0,0,0.4); }
        .cabecalho-fluxo { border-bottom: 2px solid #334155; padding-bottom: 15px; margin-bottom: 25px; display: flex; justify-content: space-between; align-items: center; }
        .caixa-indicador { background: #0f172a; padding: 15px 25px; border-radius: 12px; border-left: 5px solid #22c55e; text-align: right; min-width: 250px; }
        
        /* 🎛️ DESIGN DO SISTEMA DE ABAS PROFISSIONAIS */
        .wrapper-tabs { display: flex; gap: 10px; margin-bottom: 25px; border-bottom: 1px solid #334155; padding-bottom: 10px; }
        .tab-btn { background: #0f172a; color: #94a3b8; border: 1px solid #334155; padding: 12px 24px; border-radius: 8px; font-weight: bold; cursor: pointer; transition: all 0.3s ease; text-transform: uppercase; font-size: 12px; }
        .tab-btn.active { background: #38bdf8; color: #000; border-color: #38bdf8; box-shadow: 0 4px 12px rgba(56, 189, 248, 0.2); }
        .painel-aba { display: none; }
        .painel-aba.active { display: block; }

        .tabela-corporativa { width: 100%; border-collapse: collapse; text-align: left; font-size: 14px; }
        .tabela-corporativa th { background: #0f172a; padding: 14px; color: #38bdf8; text-transform: uppercase; font-size: 11px; letter-spacing: 0.7px; font-weight: bold; }
        .tabela-corporativa td { padding: 14px; border-bottom: 1px solid #334155; background: #111827; vertical-align: middle; }
        
        .tag-tipo { display: inline-block; padding: 3px 8px; border-radius: 4px; font-size: 10px; font-weight: bold; text-transform: uppercase; margin-top: 4px; letter-spacing: 0.5px; }
        .tag-vendas { background: rgba(56, 189, 248, 0.15); color: #38bdf8; border: 1px solid #38bdf8; }
        .tag-cortes { background: rgba(168, 85, 247, 0.15); color: #c084fc; border: 1px solid #a855f7; }
        .tag-estetica { background: rgba(236, 72, 153, 0.15); color: #f472b6; border: 1px solid #ec4899; }
        .tag-geral { background: rgba(148, 163, 184, 0.15); color: #cbd5e1; border: 1px solid #94a3b8; }
        .json-badge-box { background: #0b0f19; padding: 8px 12px; border-radius: 6px; border: 1px solid #1e293b; font-family: monospace; font-size: 11px; color: #94a3b8; margin-top: 6px; line-height: 1.4; }
        
        .badge-estado { padding: 5px 10px; border-radius: 6px; font-size: 11px; font-weight: bold; text-transform: uppercase; letter-spacing: 0.5px; }
        .estado-pendente { background: rgba(234, 179, 8, 0.1); color: #eab308; border: 1px solid #eab308; }
        .estado-concluido { background: rgba(34, 197, 94, 0.1); color: #4ade80; border: 1px solid #22c55e; }
        .estado-cancelado { background: rgba(239, 68, 68, 0.1); color: #f87171; border: 1px solid #ef4444; }

        .btn-fiscalizar { padding: 8px 14px; border: none; border-radius: 6px; font-weight: bold; cursor: pointer; font-size: 11px; text-transform: uppercase; transition: transform 0.2s; color: #000; margin-right: 5px; }
        .btn-fiscalizar:hover { transform: scale(1.03); }
        .btn-validar { background: #22c55e; }
        .btn-estorno { background: #ef4444; color: #fff; }
        .msg-alert { background: rgba(34, 197, 94, 0.08); border: 1px solid #22c55e; color: #4ade80; padding: 14px; border-radius: 8px; text-align: center; margin-bottom: 20px; font-weight: bold; font-size: 14px; }
    </style>
</head>
<body>

<div class="container-central">
    <div class="cabecalho-fluxo">
        <div>
            <h2>📊 Central Unificada de Faturação & Auditoria - Aurélius</h2>
            <p style="color: #94a3b8; font-size: 13px; margin: 4px 0 0 0;">REDE INTEGRADA SINTONIZADA: Cruzamento real entre os agendamentos de Balcão e Portfólio de Lojas.</p>
        </div>
        
        <!-- 💰 WIDGET DO CAIXA CORRIGIDO: Calcula dinamicamente com base nas colunas reais -->
        <div class="caixa-indicador">
            <span style="font-size: 11px; color: #94a3b8; text-transform: uppercase; font-weight: bold; display: block; margin-bottom: 2px;">Faturamento Global Legítimo</span>
            <strong style="font-size: 20px; color: #22c55e;" id="total_caixa_real"><?= number_format($faturamento_real_acumulado, 2, ',', '.') ?> Kz</strong>
        </div>
    </div>

    <?php if (!empty($mensagem_central)): ?>
        <div class="msg-alert"><?php echo $mensagem_central; ?></div>
    <?php endif; ?>

    <!-- 🎛️ MENU REATIVO DE SELEÇÃO DE ABAS ECONOMIZADORAS -->
    <div class="wrapper-tabs">
        <button class="tab-btn active" onclick="alternarAmbienteAuditoria('balcao', this)">💈 Serviços do Balcão</button>
        <button class="tab-btn" onclick="alternarAmbienteAuditoria('marketplace', this)">🏬 Stock das Lojas Parceiras</button>
    </div>

    <!-- =========================================================================
         💈 ABA 1: SERVIÇOS DO BALCÃO (PAGAMENTOS REAL)
         ========================================================================= -->
    <div id="aba-balcao" class="painel-aba active">
        <table class="tabela-corporativa">
            <thead>
                <tr>
                    <th>Origem</th>
                    <th>Operação / Mestre Responsável</th>
                    <th>Preço de Venda</th>
                    <th>Estado de Fluxo</th>
                    <th>Fiscalização Antifraude</th>
                </tr>
            </thead>
            <tbody>
            <?php if (!empty($registros_balcao)): ?>
                    <?php foreach ($registros_balcao as $linha): 
                        // Normalização rigorosa do estado de auditoria interna
                        $estado_limpo = !empty($linha['estado']) ? $linha['estado'] : 'Pendente';
                        
                        // Classificação visual temática baseada no tipo de serviço do salão
                        $badge_categoria = "tag-geral";
                        $conteudo_minusculo = strtolower($linha['descricao']);
                        
                        if (strpos($conteudo_minusculo, 'corte') !== false || strpos($conteudo_minusculo, 'barba') !== false) { 
                            $badge_categoria = "tag-cortes"; 
                        } elseif (strpos($conteudo_minusculo, 'unha') !== false || strpos($conteudo_minusculo, 'gel') !== false) { 
                            $badge_categoria = "tag-estetica"; 
                        } elseif (strpos($conteudo_minusculo, 'pintura') !== false || strpos($conteudo_minusculo, 'maquilhagem') !== false) { 
                            $badge_categoria = "tag-estetica"; 
                        }

                        // Mapeamento dinâmico de cores para a auditoria de caixa
                        $classe_estado = 'estado-pendente';
                        if ($estado_limpo === 'Concluido_Pago' || $estado_limpo === 'Confirmado') { $classe_estado = 'estado-concluido'; }
                        if ($estado_limpo === 'Estornado_Cancelado' || $estado_limpo === 'Cancelado') { $classe_estado = 'estado-cancelado'; }
                    ?>
                        <!-- 📦 LINHA DO AGENDAMENTO DE BALCÃO -->
                        <tr>
                            <td style="color: #cbd5e1; font-weight: bold; font-size: 13px;">💈 Balcão</td>
                            
                            <td>
                                <span style="color: #fff; font-weight: 600; font-size: 15px;"><?= htmlspecialchars($linha['descricao']) ?></span><br>
                                <span style="color: #64748b; font-size: 11px; display: inline-block; margin-top: 2px;">📅 Sincronizado: <?= date('d/m/Y H:i') ?></span><br>
                                <span class="tag-tipo <?php echo $badge_categoria; ?>">📍 <?php echo $linha['categoria_tipo']; ?></span>
                            </td>
                            
                            <td style="color: #22c55e; font-weight: bold; font-size: 15px;"><?= number_format($linha['valor'], 2, ',', '.') ?> Kz</td>
                            
                            <td>
                                <span class="badge-estado <?php echo $classe_estado; ?>"><?= str_replace('_', ' ', $estado_limpo) ?></span>
                            </td>
                            
                            <td>
                                <?php if ($estado_limpo === 'Pendente'): ?>
                                    <!-- Ação 1: Validar e integrar dinheiro legítimo ao caixa -->
                                    <form method="POST" action="" style="display: inline-block;">
                                        <input type="hidden" name="id_registro" value="<?php echo $linha['id']; ?>">
                                        <input type="hidden" name="tabela_origem" value="pagamentos">
                                        <input type="hidden" name="status_alvo" value="Concluido_Pago">
                                        <button type="submit" name="fiscalizar_registro_global" class="btn-fiscalizar btn-validar">✓ Validar</button>
                                    </form>
                                    
                                    <!-- Ação 2: Estornar ausências para limpar dados falsos -->
                                    <form method="POST" action="" style="display: inline-block;">
                                        <input type="hidden" name="id_registro" value="<?php echo $linha['id']; ?>">
                                        <input type="hidden" name="tabela_origem" value="pagamentos">
                                        <input type="hidden" name="status_alvo" value="Estornado_Cancelado">
                                        <button type="submit" name="fiscalizar_registro_global" class="btn-fiscalizar btn-estorno">✕ Estornar</button>
                                    </form>
                                <?php else: ?>
                                    <span style="color: #475569; font-size: 12px; font-style: italic; font-weight: 500;">🔒 Trancado</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="5" style="text-align:center; padding:40px; color:#64748b; font-style: italic;">
                            Nenhum serviço ou corte de balcão registado na base de dados para auditoria hoje.
                        </td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <!-- =========================================================================
         🏬 ABA 2: STOCK E PRODUTOS DAS LOJAS PARCEIRAS (PRODUTOS_COSMETICOS REAL)
         ========================================================================= -->
    <div id="aba-marketplace" class="painel-aba">
        <table class="tabela-corporativa">
            <thead>
                <tr>
                    <th>Origem</th>
                    <th>Cosmético Carregado / Ficha Técnica Real</th>
                    <th>Preço Unitário</th>
                    <th>Status Stock</th>
                    <th>Auditoria de Catálogo</th>
                </tr>
            </thead>
            <tbody>
                <?php if (!empty($registros_market)): ?>
                    <?php foreach ($registros_market as $linha): 
                        $estado_limpo = !empty($linha['estado']) ? $linha['estado'] : 'Disponível';
                        
                        $classe_estado = 'estado-concluido';
                        if ($estado_limpo === 'Esgotado' || $estado_limpo === 'Não Tem') { $classe_estado = 'estado-cancelado'; }
                    ?>
                        <!-- 📦 LINHA DO PRODUTO REAL DO PHPMYADMIN -->
                        <tr>
                            <td style="color: #38bdf8; font-weight: bold; font-size: 13px;">🏬 Loja SaaS</td>
                            
                            <td>
                                <span style="color: #fff; font-weight: 600; font-size: 15px;"><?= htmlspecialchars($linha['descricao']) ?></span><br>
                                <span style="color: #64748b; font-size: 11px; display: inline-block; margin-top: 2px;">📦 Volume em Stock: <strong><?= $linha['stock_atual'] ?> un.</strong></span><br>
                                <span class="tag-tipo tag-vendas">📍 <?= $linha['categoria_tipo'] ?></span>
                                
                                <div class="json-badge-box">
                                    🧪 <strong>Ficha Técnica:</strong> <?= htmlspecialchars($linha['tamanho']) ?><br>
                                    🎨 <strong>Especificação:</strong> <?= htmlspecialchars($linha['cor_branca']) ?>
                                </div>
                            </td>
                            
                            <td style="color: #22c55e; font-weight: bold; font-size: 15px;"><?= number_format($linha['valor'], 2, ',', '.') ?> Kz</td>
                            
                            <td>
                                <span class="badge-estado <?php echo $classe_estado; ?>"><?= $estado_limpo ?></span>
                            </td>
                            
                            <td>
                                <?php if ($estado_limpo === 'Disponível' || $estado_limpo === 'Tem'): ?>
                                    <!-- Ação 1: Forçar Esgotado se houver quebra física de stock -->
                                    <form method="POST" action="" style="display: inline-block;">
                                        <input type="hidden" name="id_registro" value="<?php echo $linha['id']; ?>">
                                        <input type="hidden" name="tabela_origem" value="produtos_cosmeticos">
                                        <input type="hidden" name="status_alvo" value="Esgotado">
                                        <button type="submit" name="fiscalizar_registro_global" class="btn-fiscalizar btn-estorno">🚨 Forçar Esgotado</button>
                                    </form>
                                <?php else: ?>
                                    <!-- Ação 2: Reativar item quando o parceiro reabastecer -->
                                    <form method="POST" action="" style="display: inline-block;">
                                        <input type="hidden" name="id_registro" value="<?php echo $linha['id']; ?>">
                                        <input type="hidden" name="tabela_origem" value="produtos_cosmeticos">
                                        <input type="hidden" name="status_alvo" value="Disponível">
                                        <button type="submit" name="fiscalizar_registro_global" class="btn-fiscalizar btn-validar">✓ Reativar Item</button>
                                    </form>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="5" style="text-align:center; padding:40px; color:#64748b; font-style: italic;">
                            Nenhum produto detetado na tabela de cosméticos das lojas parceiras.
                            </td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<script>
// 🕹️ ALTERNADOR DINÂMICO DE AMBIENTES DE FISCALIZAÇÃO SAAS
function alternarAmbienteAuditoria(ambiente, botaoClicado) {
    document.querySelectorAll('.tab-btn').forEach(btn => btn.classList.remove('active'));
    document.querySelectorAll('.painel-aba').forEach(painel => painel.classList.remove('active'));
    
    botaoClicado.classList.add('active');
    var painelAlvo = document.getElementById('aba-' + ambiente);
    if (painelAlvo) {
        painelAlvo.classList.add('active');
    }
}
</script>
</body>
</html>