<?php
require_once __DIR__ . "/config/Banco.php";

// Captura o termo de pesquisa se existir
$pesquisa = isset($_GET['busca_cliente']) ? trim($_GET['busca_cliente']) : '';

// Define o ano selecionado (padrão é o ano atual)
$ano_selecionado = isset($_GET['filtro_ano']) ? (int)$_GET['filtro_ano'] : (int)date('Y');

// Define a data de hoje para os filtros de comando inteligente
$data_hoje_banco = date('Y-m-d');

try {
    // 1. TENTA IDENTIFICAR A COLUNA CORRETA DE LIGAÇÃO DINAMICAMENTE
    $colunasQuery = $pdo->query("SHOW COLUMNS FROM pagamentos");
    $colunas = $colunasQuery->fetchAll(PDO::FETCH_COLUMN);
    
    $coluna_relacao = '';
    if (in_array('id_funcionario', $colunas)) { $coluna_relacao = 'id_funcionario'; } 
    elseif (in_array('id_profissional', $colunas)) { $coluna_relacao = 'id_profissional'; } 
    elseif (in_array('id_usuario', $colunas)) { $coluna_relacao = 'id_usuario'; } 
    elseif (in_array('funcionario', $colunas)) { $coluna_relacao = 'funcionario'; } 
    elseif (in_array('profissional', $colunas)) { $coluna_relacao = 'profissional'; }

    if (!empty($coluna_relacao)) {
        $sqlVendas = "SELECT p.*, f.nome AS nome_profissional 
                      FROM pagamentos p
                      LEFT JOIN funcionarios f ON p.$coluna_relacao = f.id_funcionario";
    } else {
        $sqlVendas = "SELECT *, NULL AS nome_profissional FROM pagamentos";
    }
  
    
    $termo_minusc = mb_strtolower($pesquisa, 'UTF-8');
    $pesquisou_hoje = ($termo_minusc === 'dia de hoje' || $termo_minusc === 'atual' || $termo_minusc === 'hoje');

    if ($pesquisou_hoje) {
        $sqlVendas .= " WHERE p.data_servico = :data_hoje";
    } elseif (!empty($pesquisa)) {
        $sqlVendas .= " WHERE p.cliente LIKE :pesquisa";
    }
    
    $sqlVendas .= " ORDER BY p.id_pagamento DESC";
    
    $query = $pdo->prepare($sqlVendas);
    
    if ($pesquisou_hoje) {
        $query->bindValue(':data_hoje', $data_hoje_banco);
    } elseif (!empty($pesquisa)) {
        $query->bindValue(':pesquisa', '%' . $pesquisa . '%');
    }
    
    $query->execute();
    $vendas = $query->fetchAll();
    
    // 2. Faturamento dinâmico baseado nos dados listados na tela
    $totalGeral = 0;
    foreach ($vendas as $v) {
        $totalGeral += (float)($v['valor'] ?? 0);
    }

    // 3. MÉTRICAS DE SERVIÇOS DO DIA / DA BUSCA (Atualiza conforme a pesquisa)
    if ($pesquisou_hoje) {
        $queryServicos = $pdo->prepare("SELECT servico, COUNT(*) as qtd_pedidos FROM pagamentos WHERE data_servico = :data_hoje GROUP BY servico ORDER BY qtd_pedidos DESC");
        $queryServicos->execute([':data_hoje' => $data_hoje_banco]);
    } else {
        $queryServicos = $pdo->query("SELECT servico, COUNT(*) as qtd_pedidos FROM pagamentos GROUP BY servico ORDER BY qtd_pedidos DESC");
    }
    $estatisticas_servicos = $queryServicos->fetchAll();

} catch (PDOException $e) {
    die("Erro ao carregar histórico: " . $e->getMessage());
}
?>

<html lang="pt-PT">
<head>
    <meta charset="UTF-8">
    <title>Histórico de Faturamento - Aurelius</title>
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; font-family: sans-serif; }
        body { width:80%; background-color: #0b1a30; color: #ffffff; padding: 30px; align-items:center; text-align:center; margin-left:auto; margin-right:auto;}
        .voltar-btn { display: inline-block; background-color: #1d4ed8; color: white; padding: 10px 20px; text-decoration: none; border-radius: 6px; font-weight: bold; margin-bottom: 20px; margin-right: 5px; }
        
        /* Atalhos rápidos */
        .btn-atalho { display: inline-block; background-color: #1e293b; border: 1px solid #334155; color: #38bdf8; padding: 6px 12px; text-decoration: none; border-radius: 4px; font-size: 12px; font-weight: bold; transition: 0.2s; cursor: pointer; }
        .btn-atalho:hover { background-color: #334155; color: white; }

        .busca-container { margin-bottom: 10px; text-align: left; background: #1e293b; padding: 15px; border-radius: 8px; display: flex; gap: 10px; width: 100%; max-width: 1000px; margin-left: auto; margin-right: auto; }
        .busca-input { flex: 1; padding: 10px; border-radius: 6px; border: 1px solid #334155; background-color: #0f172a; color: white; font-size: 14px; }
        .busca-btn { background-color: #3b82f6; color: white; border: none; padding: 10px 20px; border-radius: 6px; cursor: pointer; font-weight: bold; }
        
        .grid-topo { display: flex; flex-direction: column; gap: 15px; margin-bottom: 25px; align-items: center; width: 100%; max-width: 1000px; margin-left: auto; margin-right: auto; }
        .card-servicos { background: linear-gradient(135deg, #1e293b, #0f172a); border-left: 4px solid #38bdf8; padding: 18px; border-radius: 8px; width: 100%; text-align: left; }
        .card-faturamento { background: linear-gradient(135deg, #1e293b, #0f172a); border-left: 4px solid #22c55e; padding: 15px; border-radius: 8px; width: 100%; text-align: left; }
        
        .lista-servicos { font-size: 13px; margin-top: 8px; list-style: none; max-height: 120px; overflow-y: auto; padding-right: 5px; }
        .lista-servicos li { display: flex; justify-content: space-between; padding: 5px 0; border-bottom: 1px solid #334155; }

        table { width: 100%; border-collapse: collapse; margin-top: 20px; background-color: #111d35; border-radius: 8px; overflow: hidden; max-width: 1000px; margin-left: auto; margin-right: auto; }
        th, td { padding: 12px 15px; text-align: left; border-bottom: 1px solid #1e293b; }
        th { background-color: #14424b; color: #fff; font-weight: bold; font-size: 14px; text-transform: uppercase; }
        td { font-size: 14px; color: #cbd5e1; }
        .btn-reimprimir { background-color: #3b82f6; color: white; border: none; padding: 6px 12px; border-radius: 4px; cursor: pointer; font-size: 12px; font-weight: bold; }

        .print-only-border { margin: 25px auto; background-color: #1e293b; padding: 20px; border-radius: 12px; border: 1px solid #334155; box-shadow: 0 4px 15px rgba(0,0,0,0.2); max-width: 1000px; text-align: left; }
        .faturamento-mensal-container { display: flex; gap: 15px; flex-wrap: wrap; }
        .bloco-grafico { flex: 1.6; background: #0f172a; padding: 15px; border-radius: 8px; border: 1px solid #1e293b; }
        .bloco-lista-mensal { flex: 1; background: #0f172a; padding: 15px; border-radius: 8px; border: 1px solid #1e293b; height: 282px; overflow-y: auto; }
        .tabela-mensal { width: 100%; border-collapse: collapse; }
        .tabela-mensal td { padding: 6px 4px; border-bottom: 1px solid #1e293b; font-size: 12px; text-align: left; }
        .seletor-ano { background-color: #0f172a; color: #38bdf8; border: 1px solid #334155; padding: 4px 8px; border-radius: 6px; font-weight: bold; cursor: pointer; font-size: 13px; }
    </style>
</head>
<body>

    <div class="no-print" style="margin-bottom: 20px;">
     
        <a href="Admini.php" class="voltar-btn">Admin_Cliente</a>
        <a href="Admin_parceiros.php" class="voltar-btn">Admin_parceiros</a>
        <a href="Admin.php" class="voltar-btn">Admin_Profissionais</a>
    </div>

    <h2> Histórico Geral de Atendimentos</h2>
    <p style="color: #94a3b8; font-size: 13px; margin-bottom: 20px;">Relatório consolidado de auditoria e fluxo de caixa.</p>

    <!-- BARRA DE PESQUISA COM SUPORTE A PALAVRAS-CHAVE -->
    <form method="GET" action="" class="busca-container no-print" style="margin-bottom:5px;">
        <input type="text" name="busca_cliente" class="busca-input" placeholder="Pesquise por nome ou digite 'atual' / 'dia de hoje'..." value="<?php echo htmlspecialchars($pesquisa); ?>">
        <button type="submit" class="busca-btn">🔍 Pesquisar</button>
        <?php if(!empty($pesquisa)): ?>
            <a href="?" class="voltar-btn" style="margin-bottom:0; padding: 9px 15px; margin-left: 5px;">Limpar</a>
        <?php endif; ?>
    </form>
    
    <!-- BOTÕES DE FILTRO RÁPIDO POR CLIQUE -->
    <div class="no-print" style="text-align: left; width: 100%; max-width: 1000px; margin: 0 auto 25px auto; display: flex; gap: 8px;">
        <a href="?busca_cliente=atual" class="btn-atalho">📅 Filtrar: Dia de Hoje</a>
        <a href="?busca_cliente=hoje" class="btn-atalho">⚡ Filtrar: Atual</a>
    </div>

    <?php
    // LÓGICA DO GRÁFICO ANUAL POR CALENDÁRIO SEPARADO (DIA-MÊS-ANO)
    $faturamento_anual = [];
    for ($m = 1; $m <= 12; $m++) { $faturamento_anual[$m] = 0.00; }
    
    if (!empty($vendas)) {
        foreach ($vendas as $venda_row) {
            $valor_servico = isset($venda_row['valor']) ? (float)$venda_row['valor'] : 0;
            $data_servico = isset($venda_row['data_servico']) ? trim($venda_row['data_servico']) : "";
            
            if ($valor_servico > 0 && !empty($data_servico)) {
                $dia_identificado = 0; $mes_identificado = 0; $ano_identificado = 0;
                
                if (strpos($data_servico, '-') !== false) {
                    $partes = explode('-', $data_servico);
                    if (count($partes) === 3) {
                        if (strlen($partes[0]) === 4) {
                            $ano_identificado = (int)$partes[0]; $mes_identificado = (int)$partes[1]; $dia_identificado = (int)$partes[2];
                        } else {
                            $dia_identificado = (int)$partes[0]; $mes_identificado = (int)$partes[1]; $ano_identificado = (int)$partes[2];
                        }
                    }
                } elseif (strpos($data_servico, '/') !== false) {
                    $partes = explode('/', $data_servico);
                    if (count($partes) === 3) {
                        if (strlen($partes[0]) === 4) {

                            $ano_identificado = (int)$partes[0]; $mes_identificado = (int)$partes[1]; $dia_identificado = (int)$partes[2];
                        } else {
                            $dia_identificado = (int)$partes[0]; $mes_identificado = (int)$partes[1]; $ano_identificado = (int)$partes[2];
                        }
                    }
                }
    
                if ($ano_identificado === $ano_selecionado && $mes_identificado >= 1 && $mes_identificado <= 12) {
                    $faturamento_anual[$mes_identificado] += $valor_servico;
                }
            }
        }
    }
    $maior_faturamento = max($faturamento_anual);
    if ($maior_faturamento <= 0) $maior_faturamento = 1;
    
    $meses_extenso = [
        1 => "Janeiro", 2 => "Fevereiro", 3 => "Março", 4 => "Abril",
        5 => "Maio", 6 => "Junho", 7 => "Julho", 8 => "Agosto",
        9 => "Setembro", 10 => "Outubro", 11 => "Novembro", 12 => "Dezembro"
    ];
    ?>

    <!-- CONTAINER DO GRÁFICO MENSAL NO TOPO ABSOLUTO -->
    <div class="print-only-border">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 15px; border-bottom: 2px solid #334155; padding-bottom: 10px;">
            <h3 style="font-size: 13px; font-weight: bold; margin: 0; color: #38bdf8; text-transform: uppercase; display: flex; align-items: center; gap: 8px;">
                 Faturamento do Ano: 
                <form method="GET" action="" style="margin: 0; display: inline;" class="no-print">
                    <?php if(!empty($pesquisa)): ?>
                        <input type="hidden" name="busca_cliente" value="<?php echo htmlspecialchars($pesquisa); ?>">
                    <?php endif; ?>
                    <select name="filtro_ano" class="seletor-ano" onchange="this.form.submit()">
                        <?php
                        $ano_base = 2024; $ano_limite = (int)date('Y') + 1;
                        for ($i = $ano_base; $i <= $ano_limite; $i++) {
                            $selected = ($i === $ano_selecionado) ? 'selected' : '';
                            echo "<option value='{$i}' {$selected}>{$i}</option>";
                        }
                        ?>
                    </select>
                </form>
            </h3>
            <button class="no-print" onclick="window.print()" style="background: linear-gradient(135deg, #065f46, #047857); color: white; border: 1px solid #059669; padding: 6px 12px; font-size: 11px; font-weight: bold; border-radius: 6px; cursor: pointer;">
                🖨️ IMPRIMIR PDF
            </button>
        </div>
    
        <div class="faturamento-mensal-container">
            <div class="bloco-grafico">
                <span style="font-size: 11px; color: #94a3b8; font-weight: bold; text-transform: uppercase; display: block; margin-bottom: 15px;"> Evolução Mensal em <?php echo $ano_selecionado; ?>:</span>
                <div style="display: grid; grid-template-columns: repeat(12, 1fr); gap: 6px; align-items: flex-end; height: 190px; padding-top: 25px; border-bottom: 2px solid #334155;">
                    <?php foreach($faturamento_anual as $m_num => $total_m): 
                        $altura_coluna = ($total_m / $maior_faturamento) * 100;
                        $estilo_coluna = $total_m > 0 
                            ? "background: linear-gradient(180deg, #38bdf8, #1d4ed8); border: 1px solid #38bdf8;" 
                            : "background: rgba(51, 65, 85, 0.1); border: 1px dashed rgba(51, 65, 85, 0.3);";
                        $mes_curto = [1=>"Jan", 2=>"Fev", 3=>"Mar", 4=>"Abr", 5=>"Mai", 6=>"Jun", 7=>"Jul", 8=>"Ago", 9=>"Set", 10=>"Out", 11=>"Nov", 12=>"Dez"];
                    ?>
                        <div style="display: flex; flex-direction: column; align-items: center; justify-content: flex-end; height: 100%; position: relative;">
                            <?php if($total_m > 0): ?>
                                <span style="font-size: 8px; font-weight: bold; color: #22c55e; position: absolute; bottom: calc(<?php echo $altura_coluna; ?>% + 4px); white-space: nowrap;">
                                    <?php echo number_format($total_m, 0, ',', '.'); ?>
                                </span>
                            <?php endif; ?>
                            <div style="width: 100%; height: <?php echo $altura_coluna; ?>%; <?php echo $estilo_coluna; ?> border-radius: 3px 3px 0 0; min-height: <?php echo $total_m > 0 ? '3px' : '0'; ?>;"></div>
                            <span style="font-size: 9px; color: #94a3b8; margin-top: 6px; font-weight: bold;"><?php echo $mes_curto[$m_num]; ?></span>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <div class="bloco-lista-mensal">
                <span style="font-size: 11px; color: #94a3b8; font-weight: bold; text-transform: uppercase; display: block; margin-bottom: 10px;">💵 Caixa Mensal (<?php echo $ano_selecionado; ?>):</span>
                <table class="tabela-mensal">
                    <tbody>
                        <?php foreach($faturamento_anual as $m_num => $total_m): ?>
                            <tr>
                                <td style="font-weight: bold; color: #cbd5e1;"><?php echo $meses_extenso[$m_num]; ?></td>
                                <td style="text-align: right; font-weight: bold; color: <?php echo $total_m > 0 ? '#22c55e' : '#64748b'; ?>;">
                                    <?php echo number_format($total_m, 2, ',', '.'); ?> Kz
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>





















    

    <!-- MÉTRICAS DE SERVIÇOS E ACUMULADO LOGO ABAIXO DO GRÁFICO -->
    <div class="grid-topo">
        <div class="card-servicos">
            <span style="font-size: 11px; color: #94a3b8; font-weight: bold; text-transform: uppercase;">Serviços Solicitados </span>
            <ul class="lista-servicos">
                <?php if(!empty($estatisticas_servicos)): ?>
                    <?php foreach($estatisticas_servicos as $servico_item): ?>
                        <li>
                            <span>✂️ <?php echo htmlspecialchars($servico_item['servico']); ?></span>
                            <strong style="color: #38bdf8;"><?php echo $servico_item['qtd_pedidos']; ?> pedido</strong>
                        </li>
                    <?php endforeach; ?>
                <?php else: ?>
                    <li style="color: #64748b;">Nenhum serviço registado neste período.</li>
                <?php endif; ?>
            </ul>
        </div>

        <div class="card-faturamento">
            <span style="font-size: 11px; color: #94a3b8; font-weight: bold; text-transform: uppercase;">Faturamento Total Listado</span>
            <h3 style="font-size: 22px; color: #22c55e; margin-top: 5px;"><?php echo number_format($totalGeral, 2, ',', '.'); ?> Kz</h3>
        </div>
    </div>









    <!-- LISTAGEM EM TABELA DOS HISTÓRICOS ALINHADOS -->
    <table>
        <thead>
            <tr>
                <th>ID</th>
                <th>Cliente</th>
                <th>Profissional</th>
                <th>Serviço</th>
                <th>Preço</th>
                <th>Data / Hora</th>
                <th class="no-print">Ações</th>
            </tr>
        </thead>
        <tbody>
            <?php if(!empty($vendas)): ?>
                <?php foreach($vendas as $venda_row): ?>
                    <tr>
                        <td>#<?php echo htmlspecialchars($venda_row['id_pagamento']); ?></td>
                        <td><?php echo htmlspecialchars($venda_row['cliente'] ?? 'Não informado'); ?></td>
                        <td>
                            <?php 
                            if (!empty($venda_row['nome_profissional'])) {
                                echo htmlspecialchars($venda_row['nome_profissional']);
                            } else {
                                echo htmlspecialchars($venda_row['profissional'] ?? $venda_row['funcionario'] ?? $venda_row['id_funcionario'] ?? 'Não associado'); 
                            }
                            ?>
                        </td>
                        <td><?php echo htmlspecialchars($venda_row['servico']); ?></td>
                        <td style="color: #22c55e; font-weight: bold;"><?php echo number_format($venda_row['valor'] ?? 0, 2, ',', '.'); ?> Kz</td>
                        <td>
                            <?php 
                            $hora = (!empty($venda_row['hora_servico']) && $venda_row['hora_servico'] !== '00:00:00') 
                                ? date('H:i', strtotime($venda_row['hora_servico'])) 
                                : '00:00';
                            echo htmlspecialchars($venda_row['data_servico']) . ' às ' . $hora; 
                            ?>
                        </td>
                        <td class="no-print">
                            <button class="btn-reimprimir" onclick="imprimirApenasLinha(this)">🖨️ Reimprimir</button>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php else: ?>
                <tr>
                    <td colspan="7" style="text-align: center; color: #64748b;">Nenhum registo encontrado para o filtro aplicado.</td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>

    <!-- JAVASCRIPT: POPUP DO RECIBO BARBEARIA BRANCA -->
    <script>
    function imprimirApenasLinha(botao) {
        var row = botao.closest('tr');
        var id = row.cells[0].innerText.replace('#', '');
        var cliente = row.cells[1].innerText;
        var profissional = row.cells[2].innerText;
        var servico = row.cells[3].innerText;
        var preco = row.cells[4].innerText;
        var dataHora = row.cells[5].innerText;

        var largura = 450; var altura = 600;
        var esquerda = (screen.width - largura) / 2; var topo = (screen.height - altura) / 2;

        var janelaImpressao = window.open('', '', 'width=' + largura + ',height=' + altura + ',top=' + topo + ',left=' + esquerda + ',toolbar=no,location=no,status=no,menubar=no,scrollbars=yes,resizable=no');
        janelaImpressao.document.write(`
            <html>
            <head>
                <title>Recibo - Barbearia Branca</title>
                <style>
                    * { box-sizing: border-box; margin: 0; padding: 0; font-family: sans-serif; }
                    body { background-color: #0b1a30; color: #ffffff; padding: 25px; display: flex; justify-content: center; align-items: center; }
                    .recibo-card { background: linear-gradient(135deg, #1e293b, #0f172a); border: 1px solid #334155; border-top: 5px solid #38bdf8; border-bottom: 5px solid #38bdf8; padding: 25px; border-radius: 12px; width: 100%; box-shadow: 0 10px 25px rgba(0,0,0,0.5); }
                    .header { text-align: center; margin-bottom: 20px; border-bottom: 2px dashed #334155; padding-bottom: 15px; }
                    .header h2 { color: #38bdf8; font-size: 20px; font-weight: bold; text-transform: uppercase; }
                    .header p { color: #94a3b8; font-size: 12px; margin-top: 4px; }
                    .campo-grupo { margin-bottom: 14px; display: flex; flex-direction: column; gap: 3px; text-align: left; }
                    .label { font-size: 11px; text-transform: uppercase; color: #64748b; font-weight: bold; }
                    .valor { font-size: 14px; color: #e2e8f0; }
                    .total-box { background-color: #111d35; border-left: 4px solid #22c55e; padding: 12px; border-radius: 6px; margin-top: 20px; margin-bottom: 20px; display: flex; justify-content: space-between; align-items: center; }
                    .total-box .label { color: #22c55e; }
                    .total-box .valor-total { font-size: 18px; color: #22c55e; font-weight: bold; }
                    .footer { text-align: center; border-top: 2px dashed #334155; padding-top: 15px; }
                    .footer .agradecimento { font-size: 13px; color: #38bdf8; font-weight: 500; margin-bottom: 8px; }
                    .footer .localizacao { font-size: 10px; color: #64748b; line-height: 1.4; }
                    @media print {
                        body { background: white !important; color: black !important; padding: 0 !important; }
                        .recibo-card { border: none !important; box-shadow: none !important; background: white !important; color: black !important; width: 100% !important; padding: 10px !important; }
                        .valor, .valor-total { color: black !important; }
                        .label, .header p, .footer .localizacao { color: #555 !important; }
                        .header h2, .total-box .valor-total, .footer .agradecimento { color: #000 !important; }
                        .total-box { background: #f3f4f6 !important; border-left: 4px solid #000 !important; }
                        .no-print-btn { display: none !important; }
                    }
                </style>
            </head>
            <body>
                <div class="recibo-card">
                    <div class="header">
                        <h2>BARBEARIA BRANCA</h2>
                        <p>Comprovativo de Atendimento Geral</p>
                    </div>
                    <div class="campo-grupo"><span class="label">Registo:</span><span class="valor">nº ${id}</span></div>
                    <div class="campo-grupo"><span class="label">Cliente:</span><span class="valor">${cliente}</span></div>
                    <div class="campo-grupo"><span class="label">Profissional:</span><span class="valor">${profissional}</span></div>
                    <div class="campo-grupo"><span class="label">Serviço:</span><span class="valor">${servico}</span></div>
                    <div class="campo-grupo"><span class="label">Data/Hora:</span><span class="valor">${dataHora}</span></div>
                    <div class="total-box"><span class="label">Total Pago:</span><span class="valor-total">${preco}</span></div>
                    <div class="footer">
                        <p class="agradecimento">Obrigado pela preferência, tua pandula!</p>
                        <p class="localizacao">📍 Estamos localizados em Huambo<br>Bairro de São Luís / perto da IECA</p>
                        <button class="no-print-btn" onclick="window.print()" style="margin-top: 20px; background: #38bdf8; color: #0f172a; border: none; padding: 8px 16px; font-weight: bold; border-radius: 6px; cursor: pointer; width: 100%; text-transform: uppercase;">🖨️ Confirmar Impressão</button>
                    </div>
                </div>
            </body>
            </html>
        `);
        janelaImpressao.document.close();
    }
    </script>
</body>
</html>