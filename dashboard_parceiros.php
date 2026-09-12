<?php
// dashboard_parceiros.php - Painel de Gráficos e Controle Analítico de Parceiros
include_once("Conexao.php");

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

date_default_timezone_set('Africa/Luanda');

// 🔒 VALIDAÇÃO DE ACESSO DO GERENTE
if (!isset($_SESSION['gerente_autenticado']) || $_SESSION['gerente_autenticado'] !== true) {
    echo "<script>alert('Acesso Restrito à Gerência!'); window.location.href='admini.php';</script>";
    exit;
}

// Uniformiza o objeto de conexão PDO
if (!isset($pdo) && isset($conn)) { $pdo = $conn; }

// Métrica iniciais
$total_contratos = 0;
$ativos_validados = 0;
$suspensos_bloqueados = 0;
$pendentes_validacao = 0;
$faturamento_bruto_taxas = 0;
$comissoes_retidas_aurelius = 0;

$grafico_mensal = array_fill(1, 12, 0.00);
$distribuicao_tipo_empresa = [];

if (isset($pdo)) {
    try {
        // 📊 1. CARREGAMENTO DAS MÉTRICAS GLOBAIS DE PARCEIROS
        $stmt = $pdo->query("SELECT * FROM saloes_parceiros");
        $todos_parceiros = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $total_contratos = count($todos_parceiros);

        foreach ($todos_parceiros as $p) {
            $status = $p['transacao_status'] ?? 'Aguardando Validação';
            $preco_taxa = floatval($p['preco'] ?? 0);
            $tipo = !empty($p['tipos_de_servico']) ? trim($p['tipos_de_servico']) : 'Geral';

            // Contagem por Status de Ativação
            if ($status === 'Confirmado') {
                $ativos_validados++;
                $faturamento_bruto_taxas += $preco_taxa;
                
                // Regra de Negócio: Plataforma Aurelius retém 15% de comissão sobre a taxa acordada de hospedagem
                $comissoes_retidas_aurelius += ($preco_taxa * 0.15);

                // Processamento do fluxo mensal para o Gráfico de Barras
                if (!empty($p['data'])) {
                    $mes = (int)date('m', strtotime($p['data']));
                    $grafico_mensal[$mes] += $preco_taxa;
                }
            } elseif ($status === 'Suspenso') {
                $suspensos_bloqueados++;
            } else {
                $pendentes_validacao++;
            }

            // Agrupamento por Tipo de Empresa para o Gráfico de Setores
            if (!isset($distribuicao_tipo_empresa[$tipo])) {
                $distribuicao_tipo_empresa[$tipo] = 0;
            }
            $distribuicao_tipo_empresa[$tipo]++;
        }

    } catch (PDOException $e) {
        // Prevenção contra tabelas vazias
    }
}

$maior_caixa_mes = max($grafico_mensal) > 0 ? max($grafico_mensal) : 1;
?>
<!DOCTYPE html>
<html lang="pt-PT">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Analítico de Parceiros - Aurelius</title>
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; font-family: 'Segoe UI', Arial, sans-serif; }
        body { background-color: #0f172a; color: #ffffff; padding: 25px; text-align: center; }
        .container { width: 100%; max-width: 1200px; margin: 0 auto; }
        
        /* Menu de rotas */
        nav { display: flex; gap: 10px; margin-bottom: 25px; }
        .voltar-btn { display: inline-block; background-color: #475569; color: white; padding: 10px 20px; text-decoration: none; border-radius: 6px; font-weight: bold; font-size: 13px; transition: 0.2s; }
        .voltar-btn:hover { background-color: #334155; }
        .btn-ouro { background-color: #ca8a04; color: #0f172a; }
        .btn-ouro:hover { background-color: #a16207; }

        .topo-painel { background: #1e293b; padding: 20px; border-radius: 12px; border: 1px solid #334155; margin-bottom: 25px; text-align: left; }
        .topo-painel h1 { color: #38bdf8; font-size: 22px; text-transform: uppercase; }
        
        /* Cards de Métricas */
        .grid-cards { display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 15px; margin-bottom: 25px; }
        .card { background: #111e35; border: 1px solid #1e3a8a; padding: 20px; border-radius: 10px; text-align: left; position: relative; }
        .card h3 { font-size: 11px; color: #94a3b8; text-transform: uppercase; letter-spacing: 0.5px; margin-bottom: 6px; }
        .card p { font-size: 24px; font-weight: bold; color: #fff; }
        .card .sub-txt { font-size: 11px; color: #64748b; margin-top: 4px; display: block; }

        /* Gráficos em Linha e Grelhas */
        .layout-graficos { display: grid; grid-template-columns: 2fr 1fr; gap: 20px; margin-bottom: 25px; }
        @media (max-width: 900px) { .layout-graficos { grid-template-columns: 1fr; } }
        
        .box-grafico-barra { background: #1e293b; border: 1px solid #334155; padding: 20px; border-radius: 12px; text-align: left; }
        .box-lista-distribuicao { background: #1e293b; border: 1px solid #334155; padding: 20px; border-radius: 12px; text-align: left; }
        
        .grafico-linhas-container { display: grid; grid-template-columns: repeat(12, 1fr); gap: 8px; align-items: flex-end; height: 200px; border-bottom: 2px solid #334155; padding-top: 20px; }
        .coluna-mes { display: flex; flex-direction: column; align-items: center; justify-content: flex-end; height: 100%; position: relative; }
        .barra-interna { width: 100%; border-radius: 4px 4px 0 0; background: linear-gradient(180deg, #ca8a04, #854d0e); border: 1px solid #eab308; transition: height 0.5s ease-in-out; }
        
        .tabela-distribuicao { width: 100%; border-collapse: collapse; margin-top: 10px; font-size: 13px; }
        .tabela-distribuicao tr { border-bottom: 1px solid #334155; }
        .tabela-distribuicao td { padding: 8px 4px; color: #cbd5e1; }

        .regra-caixa { background: rgba(234, 179, 8, 0.05); border: 1px dashed #eab308; padding: 15px; border-radius: 8px; text-align: left; font-size: 12px; color: #cbd5e1; line-height: 1.5; }
    </style>
</head>
<body>

<div class="container">
    <nav class="no-print">
        <a href="Admin_Parceiros.php" class="voltar-btn btn-ouro">🏪 Voltar ao Painel Operacional</a>
        <a href="Admini.php" class="voltar-btn">👤 Administração Clientes</a>
        <a href="historico.php" class="voltar-btn">📋 Histórico de Caixa</a>
    </nav>

    <div class="topo-painel">
        <h1>Grupo Aurélius — Métricas Avançadas & Faturamento de SaaS</h1>
        <div class="info-gerente" style="margin-top: 5px; font-size: 12px; color: #94a3b8;">
            <span><strong>Auditor de Contratos:</strong> Aurélio Sacalumbo</span> | 
            <span><strong>Região de Cobertura:</strong> Huambo - Catimba</span>
        </div>
    </div>

    <!-- 📊 BLOCKS DE INDICAÇÃO COMERCIAL EM TEMPO REAL -->
    <div class="grid-cards">
        <div class="card" style="border-left: 4px solid #3b82f6;">
            <h3>Total Hospedados</h3>
            <p><?php echo $total_contratos; ?> Empresas</p>
            <span class="sub-txt">Base cadastral submetida</span>
        </div>
        <div class="card" style="border-left: 4px solid #22c55e;">
            <h3>Contratos Validados</h3>
            <p><?php echo $ativos_validados; ?> Ativos</p>
            <span class="sub-txt" style="color: #22c55e;">✓ Estado: Pago e Feito</span>
        </div>
        <div class="card" style="border-left: 4px solid #eab308;">
            <h3>Aguardando Análise</h3>
            <p><?php echo $pendentes_validacao; ?> Pendentes</p>
            <span class="sub-txt" style="color: #eab308;">⚠️ Estado: Não Terminado</span>
        </div>
        <div class="card" style="border-left: 4px solid #ef4444;">
            <h3>Parceiros Suspensos</h3>
            <p><?php echo $suspensos_bloqueados; ?> Bloqueados</p>
            <span class="sub-txt" style="color: #ef4444;">✕ Acesso cortado na rede</span>
        </div>
    </div>

    <div class="grid-cards">
        <div class="card" style="border-left: 4px solid #10b981; background: #064e3b;">
            <h3>Faturamento Bruto Contratual</h3>
            <p><?php echo number_format($faturamento_taxas_bruto = $faturamento_bruto_taxas, 0, '', ' '); ?> Kz</p>
            <span class="sub-txt" style="color: #34d399;">Volume total de cadeiras ativas</span>
        </div>
        <div class="card" style="border-left: 4px solid #a855f7; background: #3b0764;">
            <h3>Comissões Líquidas Aurélius</h3>
            <p><?php echo number_format($comissoes_retidas_aurelius, 0, '', ' '); ?> Kz</p>
            <span class="sub-txt" style="color: #c084fc;">💥 Margem líquida de 15% retida</span>
        </div>
    </div>

    <!-- 📈 SECCÃO CENTRAL: GRÁFICO DE COMPORTAMENTO E DISTRIBUIÇÃO -->
    <div class="layout-graficos">
        <div class="box-grafico-barra">
            <span style="font-size: 12px; color: #94a3b8; font-weight: bold; text-transform: uppercase; display: block; margin-bottom: 10px;">📊 Evolução Cronológica de Arrecadação de Taxas (<?php echo date('Y'); ?>):</span>
            
            <div class="grafico-linhas-container">
                <?php 
                $meses_short = [1=>"Jan", 2=>"Fev", 3=>"Mar", 4=>"Abr", 5=>"Mai", 6=>"Jun", 7=>"Jul", 8=>"Ago", 9=>"Set", 10=>"Out", 11=>"Nov", 12=>"Dez"];
                foreach($grafico_mensal as $m_num => $total_mes_caixa): 
                    $altura_barra = ($total_mes_caixa / $maior_caixa_mes) * 100;
                ?>
                    <div class="coluna-mes">
                        <?php if($total_mes_caixa > 0): ?>
                            <span style="font-size: 8px; font-weight: bold; color: #22c55e; position: absolute; bottom: calc(<?php echo $altura_barra; ?>% + 4px); white-space: nowrap;">
                                <?php echo number_format($total_mes_caixa, 0, '', ' '); ?>
                            </span>
                        <?php endif; ?>
                        <div class="barra-interna" style="height: <?php echo $altura_barra; ?>%;"></div>
                        <div class="barra-interna" style="height: <?php echo $altura_barra; ?>%;"></div>
                        <span style="font-size: 9px; color: #94a3b8; margin-top: 6px; font-weight: bold;"><?php echo $meses_short[$m_num]; ?></span>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>

        <!-- 🏢 LISTA LATERAL: SEGMENTAÇÃO DE MERCADO DOS PARCEIROS -->
        <div class="box-lista-distribuicao">
            <span style="font-size: 12px; color: #94a3b8; font-weight: bold; text-transform: uppercase; display: block; margin-bottom: 10px;">🏢 Segmentos de Mercado Ativos:</span>
            <table class="tabela-distribuicao">
                <tbody>
                    <?php if(empty($distribuicao_tipo_empresa)): ?>
                        <tr><td style="color: #64748b; text-align: center; padding: 20px;">Nenhum segmento catalogado no banco.</td></tr>
                    <?php else: ?>
                        <?php foreach($distribuicao_tipo_empresa as $segmento => $qtd): ?>
                            <tr>
                                <td style="font-weight: bold; text-align: left; padding: 8px 4px;">🔹 <?php echo htmlspecialchars($segmento); ?></td>
                                <td style="text-align: right; font-weight: bold; color: #38bdf8; padding: 8px 4px;"><?php echo $qtd; ?> Unidades</td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div> <!-- Fecha .layout-graficos -->

    <!-- 💡 DIRETRIZES REFORÇADAS E REGRAS DE MOTIVAÇÃO COMERCIAIS -->
    <div class="regra-caixa">
        <strong style="color: #ca8a04; display: block; margin-bottom: 8px; text-transform: uppercase; font-size: 13px;">💡 Regras de Influência e Motivação Gerencial — Grupo Aurélius</strong>
        <p style="margin-bottom: 6px;">• <strong>Controle de Adimplência</strong>: Parceiros com faturamento zerado ou que não efetuaram o repasse contratual da taxa mudam automaticamente para o estado <em>"Contrato Não Terminado"</em> no painel gerencial.</p>
        <p style="margin-bottom: 6px;">• <strong>Estímulo de Escala</strong>: Salões parceiros que mantiverem o status de <em>"Confirmado"</em> por mais de 6 meses consecutivos ganham destaque automático com selo de verificação Ouro na aplicação do cliente.</p>
        <p style="margin-bottom: 0;">• <strong>Bloqueio Automático</strong>: Perfis marcados como <em>"Suspenso"</em> perdem imediatamente as rotas de agendamento online e o recebimento de notificações push via WhatsApp corporativo.</p>
    </div>
</div> <!-- Fecha .container -->

</body>
</html>