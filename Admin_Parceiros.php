<?php
// Admin_Parceiros.php - Consolidação de Métricas Reais do Grupo Aurélius
if (!isset($_SESSION)) { 
    session_start(); 
}
date_default_timezone_set('Africa/Luanda');

// 🔑 CONEXÃO EXPLÍCITA À BASE CENTRAL PARA LER TODOS OS 25 REGISTOS
$mysqli = new mysqli("127.0.0.1", "root", "", "aurelius_salao");
if ($mysqli->connect_error) { 
    die("Erro de ligação mestre: " . $mysqli->connect_error); 
}
$mysqli->set_charset("utf8");

// =========================================================================
// 📊 SENSOR MATRICIAL COMPLETO: SOMA REAL SEM VALORES FIXOS
// =========================================================================
$total_parceiros = 0; 
$validados = 0; 
$suspensos = 0; 
$faturamento_plataforma = 0;

// Executa a busca total na tabela usuario onde residem os 25 registos
$query_contas = $mysqli->query("SELECT * FROM `usuario`");

if ($query_contas) {
    // 🔮 CAPTURA REAL: Conta fisicamente todas as linhas registadas (vai dar 25)
    $total_parceiros = $query_contas->num_rows;

    while ($p = $query_contas->fetch_assoc()) {
        // Limpa espaços em branco para evitar erros de leitura de string
        $st = isset($p['transacao_status']) ? trim($p['transacao_status']) : 'Aguardando Validação';
        
        // Se a empresa estiver validada como Confirmado, entra no cálculo de ativos e faturamento
        if ($st === 'Confirmado') { 
            $validados++; 
            // 💰 SUMARIZAÇÃO DE TAXA: Captura o valor real digitado no preço do cadastro
            $faturamento_plataforma += (float)($p['preco'] ?? 0); 
        } 
        // Conta as empresas suspensas ou rejeitadas pelo administrador
        elseif ($st === 'Suspenso' || $st === 'Rejeitado / Pendente') { 
            $suspensos++; 
        }
    }
}

// Cálculos percentuais exatos para renderizar os gráficos de barra CSS do ecrã
$perc_validados = $total_parceiros > 0 ? ($validados / $total_parceiros) * 100 : 0;
$perc_suspensos = $total_parceiros > 0 ? ($suspensos / $total_parceiros) * 100 : 0;
?>
<!DOCTYPE html>
<html lang="pt-PT">
<head>
    <meta charset="UTF-8">
    <title>Grupo Aurélius - Consolidação de Métricas</title>
    <style>
        body { font-family: 'Segoe UI', sans-serif; background-color: #0f172a; padding: 30px 20px; margin: 0; color: #f8fafc; }
        .admin-container { max-width: 1100px; margin: 0 auto; background: #1e293b; padding: 25px; border-radius: 12px; border: 1px solid #334155; }
        .topo-painel { border-bottom: 2px solid #334155; padding-bottom: 15px; margin-bottom: 20px; }
        .topo-painel h1 { margin: 0; font-size: 22px; color: #ca8a04; font-weight: bold; text-transform: uppercase; }
        .grid-metricas { display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 15px; margin-bottom: 25px; }
        .card-metrica { background: #111e35; border: 1px solid #1e3a8a; padding: 20px; border-radius: 10px; }
        .card-metrica h3 { margin: 0 0 6px 0; color: #64748b; font-size: 11px; text-transform: uppercase; }
        .card-metrica p { margin: 0; font-size: 24px; font-weight: bold; color: #ffffff; }
        .barra-grafico-container { background: #1e293b; border: 1px solid #334155; width: 100%; height: 8px; border-radius: 4px; overflow: hidden; margin-top: 10px; }
        .voltar-btn { display: inline-block; background-color: #475569; color: white; padding: 10px 20px; text-decoration: none; border-radius: 6px; font-weight: bold; margin-bottom: 20px; font-size: 13px; margin-right: 5px; }
    </style>
</head>
<body>

<nav>
<a href=" produto Novo.php" class="voltar-btn">Vender Novo Produto</a>
    <a href="historico.php" class="voltar-btn">Histórico</a>
    <a href="Admini.php" class="voltar-btn">Admin_Cliente</a>
    <a href="Admin_Parceiros.php" class="voltar-btn" style="background-color: #ca8a04; color: #0f172a;">Admin_Parceiros</a>

</nav>

<div class="admin-container">
    <div class="topo-painel">
        <h1>Grupo Aurélius — Consolidação de Métricas</h1>
    </div>

    <!-- 📈 VISUALIZAÇÃO GRÁFICA DAS MÉTRICAS EM TEMPO REAL -->
    <div class="grid-metricas">
        <div class="card-metrica" style="border-left: 4px solid #3b82f6;">
            <h3>Empresas Registadas</h3>
            <p><?php echo $total_parceiros; ?> Contratos</p>
            <div class="barra-grafico-container"><div style="background: #3b82f6; width: 100%; height: 100%;"></div></div>
        </div>
        <div class="card-metrica" style="border-left: 4px solid #22c55e;">
            <h3>Validados / Ativos</h3>
            <p><?php echo $validados; ?> Aceites</p>
            <div class="barra-grafico-container"><div style="background: #22c55e; width: <?php echo $perc_validados; ?>%; height: 100%;"></div></div>
        </div>
        <div class="card-metrica" style="border-left: 4px solid #dc2626;">
            <h3>Parceiros Suspensos</h3>
            <p><?php echo $suspensos; ?> Bloqueados</p>
            <div class="barra-grafico-container"><div style="background: #dc2626; width: <?php echo $perc_suspensos; ?>%; height: 100%;"></div></div>
        </div>
        <div class="card-metrica" style="border-left: 4px solid #10b981;">
            <h3>Faturamento de Taxas</h3>
            <p><?php echo number_format($faturamento_plataforma, 0, '', ' '); ?> Kz</p>
            <div class="barra-grafico-container"><div style="background: #10b981; width: 100%; height: 100%;"></div></div>
        </div>
    </div>
    
    <p style="text-align: center; color: #64748b; font-size: 13px; margin-top: 30px;">Métricas consolidadas sincronizadas com o banco de dados principal de Huambo.</p>
</div>












<!-- 📊 INTERFACE REAL DE CONTROLO DE STOCK DO SALÃO -->
<div style="background: #111827; border: 1px solid #233147; padding: 25px; border-radius: 14px; font-family: 'Segoe UI', Arial, sans-serif; max-width: 1320px; margin: 30px auto; box-shadow: 0 8px 16px rgba(0,0,0,0.3); clear: both !important; text-align: left;">
    
    <div style="border-bottom: 2px solid #1e293b; padding-bottom: 15px; margin-bottom: 20px;">
        <h3 style="color: #38bdf8; margin: 0; text-transform: uppercase; font-size: 18px; letter-spacing: 0.5px;">📦 Teu Inventário de Produtos Cadastrados</h3>
        <p style="color: #94a3b8; font-size: 13px; margin: 4px 0 0 0;">Gerencia o preço, disponibilidade de cores e status de stock dos teus artigos diretamente no Marketplace.</p>
    </div>

    <div style="overflow-x: auto;">
        <table style="width: 100%; border-collapse: collapse; text-align: left; font-size: 13px; color: #cbd5e1;">
            <thead>
                <tr style="background: #0f172a; color: #94a3b8; text-transform: uppercase; font-size: 11px; letter-spacing: 0.5px;">
                    <th style="padding: 14px; border-bottom: 2px solid #334155;">Código</th>
                    <th style="padding: 14px; border-bottom: 2px solid #334155;">Designação do Artigo</th>
                    <th style="padding: 14px; border-bottom: 2px solid #334155;">Preço de Venda</th>
                    <th style="padding: 14px; border-bottom: 2px solid #334155;">Volume/Tam</th>
                    <th style="padding: 14px; border-bottom: 2px solid #334155;">Status no Stock</th>
                    <th style="padding: 14px; border-bottom: 2px solid #334155;">Cor Branca</th>
                </tr>
            </thead>
            <tbody>
                <?php 
                if (!empty($listaMeusProdutos)):
                    foreach ($listaMeusProdutos as $meuProd): 
                        $is_esgotado = (isset($meuProd['stock']) && $meuProd['stock'] === 'Esgotado');
                        $id_p = intval($meuProd['id']);
                ?>
                        <tr style="border-bottom: 1px solid #1e293b; transition: background 0.2s;" onmouseover="this.style.background='#1e293b'" onmouseout="this.style.background='transparent'">
                            <td style="padding: 14px; color: #64748b; font-weight: bold;">#<?= $id_p ?></td>
                            <td style="padding: 14px; font-weight: bold; color: #fff; text-transform: uppercase;"><?= htmlspecialchars($meuProd['nome_produto'] ?? 'Cosmético') ?></td>
                            <td style="padding: 14px; font-weight: bold; color: #eab308;"><?= number_format(($meuProd['preco'] ?? 0), 2, ',', '.') ?> Kz</td>
                            <td style="padding: 14px;"><?= htmlspecialchars($meuProd['tamanho'] ?? 'Regular') ?></td>
                            <td style="padding: 14px变量;">
                                <span style="padding: 4px 8px; border-radius: 4px; font-size: 11px; font-weight: bold; text-transform: uppercase; <?= $is_esgotado ? 'background:#7f1d1d; color:#f87171;' : 'background:#14532d; color:#4ade80;' ?>">
                                    <?= htmlspecialchars($meuProd['stock'] ?? 'Disponível') ?>
                                </span>
                            </td>
                            <td style="padding: 14px; font-weight: bold; color: <?= (isset($meuProd['cor_branca']) && $meuProd['cor_branca'] === 'Tem') ? '#4ade80' : '#f87171' ?>;">
                                <?= htmlspecialchars($meuProd['cor_branca'] ?? 'Tem') ?>
                            </td>
                        </tr>
                <?php 
                    endforeach;
                else:
                    ?>
                    <tr>
                        <td colspan="6" style="padding: 30px; text-align: center; color: #94a3b8; font-style: italic;">
                            📭 Nenhum produto localizado para o Salão 20 na base de dados.
                        </td>
                    </tr>
                    <?php
                endif; 
                ?>
            </tbody>
        </table>
    </div>
</div>
 <!-- 🟢 BOTÃO DE ACESSO AO CADASTRO REAL CORRIGIDO -->
 <div style="text-align: right; margin-bottom: 15px; max-width: 1320px; margin: 0 auto 15px auto;">
    <a href="cadastrar_produto.php" style="display: inline-block; background: #0284c7; color: white; padding: 12px 24px; border-radius: 6px; font-weight: bold; text-decoration: none; text-transform: uppercase; font-size: 12px; letter-spacing: 0.5px; transition: background 0.2s;" onmouseover="this.style.background='#0369a1'" onmouseout="this.style.background='#0284c7'">
        ➕ Cadastrar Novo Produto
    </a>
</div>











<?php
// 🔑 MÓDULO ADMIN - CONTROLO E VALIDAÇÃO DE PARCEIROS REAIS
// Processa a ativação ou bloqueio do parceiro de forma imediata
if (isset($_POST['alterar_status_parceiro'])) {
    $id_empresa_mod = intval($_POST['id_empresa_mod']);
    $novo_status    = htmlspecialchars($_POST['status_mod']);
    
    // Atualiza se o parceiro está confirmado e visível no portal público
    $visivel = ($novo_status === 'Confirmado') ? 1 : 0;
    
    $stmtMod = $mysqli->prepare("UPDATE usuario SET transacao_status = ?, visivel_no_site = ? WHERE codigo = ?");
    $stmtMod->bind_param("sii", $novo_status, $visivel, $id_empresa_mod);
    if ($stmtMod->execute()) {
        echo "<div style='background:#14532d; color:#4ade80; padding:12px; border-radius:8px; margin:15px auto; max-width:1320px; font-weight:bold; text-align:center;'>✓ O estatuto da empresa foi atualizado com sucesso no ecossistema!</div>";
    }
    $stmtMod->close();
}

// Puxa todos os salões cadastrados em Angola para auditoria do Administrador
$query_parceiros_geral = $mysqli->query("SELECT codigo, nome, endereco, telefone, transacao_status FROM usuario ORDER BY codigo DESC");
?>

<div style="background: #111827; border: 1px solid #233147; padding: 25px; border-radius: 12px; font-family: 'Segoe UI', sans-serif; margin: 30px auto; max-width: 1320px; clear: both !important;">
    <h2 style="color: #38bdf8; margin-top: 0; text-transform: uppercase; letter-spacing: 0.5px;">🛡️ Moderação e Auditoria de Parceiros Comerciais</h2>
    <p style="color: #94a3b8; font-size: 13px; margin-bottom: 20px;">Valida a documentação e a existência física das empresas antes de libertar a publicação de produtos na vitrina principal.</p>

    <div style="overflow-x: auto;">
        <table style="width: 100%; border-collapse: collapse; text-align: left; font-size: 13px; color: #cbd5e1;">
            <thead>
                <tr style="background: #1e293b; color: #eab308; text-transform: uppercase; font-size: 11px;">
                    <th style="padding: 12px; border-bottom: 2px solid #334155;">Cód</th>
                    <th style="padding: 12px; border-bottom: 2px solid #334155;">Nome da Empresa / Salão</th>
                    <th style="padding: 12px; border-bottom: 2px solid #334155;">Localização Registada</th>
                    <th style="padding: 12px; border-bottom: 2px solid #334155;">Contacto Telefónico</th>
                    <th style="padding: 12px; border-bottom: 2px solid #334155;">Estatuto Atual</th>
                    <th style="padding: 12px; border-bottom: 2px solid #334155; text-align: center;">Ação Administrativa</th>
                </tr>
            </thead>
            <tbody>
                <?php 
                if ($query_parceiros_geral && $query_parceiros_geral->num_rows > 0):
                    while ($parceiro = $query_parceiros_geral->fetch_assoc()):
                        $status_cor = ($parceiro['transacao_status'] === 'Confirmado') ? '#22c55e' : '#f87171';
                ?>
                        <tr style="border-bottom: 1px solid #1e293b;" onmouseover="this.style.background='#1e293b'" onmouseout="this.style.background='transparent'">
                            <td style="padding: 12px; color: #64748b;">#<?= $parceiro['codigo'] ?></td>
                            <td style="padding: 12px; font-weight: bold; color: #fff;"><?= htmlspecialchars($parceiro['nome']) ?></td>
                            <td style="padding: 12px; color: #94a3b8;"><?= htmlspecialchars($parceiro['endereco']) ?></td>
                            <td style="padding: 12px;"><?= htmlspecialchars($parceiro['telefone']) ?></td>
                            <td style="padding: 12px; font-weight: bold; color: <?= $status_cor ?>; text-transform: uppercase; font-size: 11px;">
                                <?= !empty($parceiro['transacao_status']) ? $parceiro['transacao_status'] : 'Pendente' ?>
                            </td>
                            <td style="padding: 12px; text-align: center;">
                                <form method="POST" style="display: inline-flex; gap: 6px; margin: 0;">
                                    <input type="hidden" name="id_empresa_mod" value="<?= $parceiro['codigo'] ?>">
                                    
                                    <select name="status_mod" style="padding: 6px; background: #0f172a; color: #fff; border: 1px solid #334155; border-radius: 4px; font-size: 12px;">
                                        <option value="Pendente" <?= ($parceiro['transacao_status'] !== 'Confirmado') ? 'selected' : '' ?>>Pendente / Bloquear</option>
                                        <option value="Confirmado" <?= ($parceiro['transacao_status'] === 'Confirmado') ? 'selected' : '' ?>>Aprovar / Ativar</option>
                                    </select>
                                    
                                    <button type="submit" name="alterar_status_parceiro" style="padding: 6px 12px; background: #0284c7; color: white; border: none; border-radius: 4px; font-weight: bold; font-size: 11px; cursor: pointer; text-transform: uppercase;">Aplicar</button>
                                </form>
                            </td>
                        </tr>
                <?php 
                    endwhile;
                else:
                    echo "<tr><td colspan='6' style='padding: 20px; text-align: center; color: #94a3b8;'>Nenhum parceiro comercial registado no banco de dados.</td></tr>";
                endif; 
                ?>
            </tbody>
        </table>
    </div>
</div>










</body>
</html>