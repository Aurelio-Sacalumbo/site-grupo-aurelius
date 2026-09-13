<?php
if (session_status() === PHP_SESSION_NONE) { 
    session_start(); 
}
date_default_timezone_set('Africa/Luanda');

// Importação segura da infraestrutura do Banco
require_once __DIR__ . "/config/Banco.php";

// Captura o ID da fatura vindo da URL (ex: fatura.php?id=237)
$id_pagamento = isset($_GET['id']) ? intval($_GET['id']) : 0;

// 2. Se nenhum ID veio na URL (novo atendimento feito na App), força a busca do ÚLTIMO registado
if ($id_pagamento === 0 && isset($pdo) && $pdo !== null) {
    try {
        // Tenta descobrir o ID mais alto inserido no segundo anterior para o cliente atual
        $stmt_u = $pdo->query("SELECT id_pagamento FROM `pagamentos` ORDER BY id_pagamento DESC LIMIT 1");
        if (!$stmt_u) { $stmt_u = $pdo->query("SELECT id_venda FROM `pagamentos` ORDER BY id_venda DESC LIMIT 1"); }
        if (!$stmt_u) { $stmt_u = $pdo->query("SELECT id FROM `pagamentos` ORDER BY id DESC LIMIT 1"); }
        
        $ultimo_reg = $stmt_u->fetch(PDO::FETCH_ASSOC);
        if ($ultimo_reg) { 
            $id_pagamento = intval(current($ultimo_reg)); 
        }
    } catch (PDOException $e) { 
        error_log("Falha ao detetar o último ID: " . $e->getMessage()); 
    }
}


// Fallback de segurança para o template não quebrar caso o banco esteja limpo
if ($id_pagamento === 0) { $id_pagamento = 1; }

// 3. Captura os dados completos do atendimento/pagamento atual na base de dados
$pagamento = [];
if (isset($pdo) && $pdo !== null) {
    $colunas_id = ['id_pagamento', 'id_venda', 'id'];
    foreach ($colunas_id as $coluna) {
        try {
            $stmt_p = $pdo->prepare("SELECT * FROM `pagamentos` WHERE $coluna = ? LIMIT 1");
            $stmt_p->execute([$id_pagamento]);
            $pagamento = $stmt_p->fetch(PDO::FETCH_ASSOC);
            if ($pagamento) { break; } // Se localizou a linha certa, interrompe o loop
        } catch (PDOException $e) { 
            continue; 
        }
    }
}

// 🟢 CORREÇÃO DO PROFISSIONAL: Cruza o ID com a tabela funcionários
$atendente_final = 'Aurélio';
if (!empty($pagamento)) {
    $id_func_raw = $pagamento['profissional'] ?? ($pagamento['atendente'] ?? ($pagamento['funcionario_id'] ?? ''));
    if (is_numeric($id_func_raw) && isset($pdo)) {
        try {
            $stmt_f = $pdo->prepare("SELECT nome FROM `funcionarios` WHERE id_funcionario = ? LIMIT 1");
            $stmt_f->execute([$id_func_raw]);
            $func = $stmt_f->fetch(PDO::FETCH_ASSOC);
            if ($func && !empty($func['nome'])) {
                $atendente_final = $func['nome'];
            } else {
                $atendente_final = "Profissional #" . $id_func_raw;
            }
        } catch (Exception $e) {
            $atendente_final = "Profissional #" . $id_func_raw;
        }
    } elseif (!empty($id_func_raw)) {
        $atendente_final = $id_func_raw;
    }
}

// 🟢 RESOLVE O NOME DO CLIENTE DESTINATÁRIO
$cliente_nome_final = "Consumidor Geral";
if (!empty($pagamento)) {
    if (!empty($pagamento['nome_candidato'])) {
        $cliente_nome_final = $pagamento['nome_candidato'];
    } elseif (!empty($pagamento['nome_autor'])) {
        $cliente_nome_final = $pagamento['nome_autor'];
    } elseif (!empty($pagamento['cliente'])) {
        $cliente_nome_final = $pagamento['cliente'];
    } elseif (!empty($pagamento['nome'])) {
        $cliente_nome_final = $pagamento['nome'];
    }
}

// 🟢 MAPEA O TELEFONE DO CLIENTE DE FORMA TOTALMENTE DINÂMICA
$telefone_cliente_final = "Não Registado";
if (!empty($pagamento)) {
    $telefone_direto = $pagamento['telefone_cliente'] ?? ($pagamento['whatsapp'] ?? ($pagamento['contacto'] ?? ($pagamento['telemovel'] ?? ($pagamento['telefone'] ?? ''))));
    
    if (!empty($telefone_direto) && $telefone_direto !== "925347372") {
        $telefone_cliente_final = $telefone_direto;
    } else {
        if (!empty($cliente_nome_final) && $cliente_nome_final !== "Consumidor Geral" && isset($pdo)) {
            try {
                $stmt_c = $pdo->prepare("SELECT telefone FROM `clientes` WHERE nome LIKE ? LIMIT 1");
                $stmt_c->execute(["%" . $cliente_nome_final . "%"]);
                $cli = $stmt_c->fetch(PDO::FETCH_ASSOC);
                if ($cli && !empty($cli['telefone'])) {
                    $telefone_cliente_final = $cli['telefone'];
                }
            } catch (Exception $e) { 
                $telefone_cliente_final = "Não Registado"; 
            }
        }
    }
}

// 🟢 ALINHAMENTO DECLARATÓRIO DE VARIÁVEIS OPERACIONAIS
$id_final_exibicao       = $pagamento['id_pagamento'] ?? ($pagamento['id_venda'] ?? ($pagamento['id'] ?? $id_pagamento));
$preco_tabela_exibicao   = floatval($pagamento['preco'] ?? ($pagamento['valor'] ?? 1500));
$desconto_kz             = floatval($pagamento['desconto'] ?? 0);
$total_final             = $preco_tabela_exibicao - $desconto_kz;

// 🌟 FIX DA LINHA 368: Criada a variável exata exigida pelo seu validador HTML VIP
$is_premium_cliente      = ($desconto_kz > 0);

// 🟢 GERADOR DO ENDPOINT DE AUTENTICAÇÃO DIGITAL QR CODE (GOOGLE CHARTS)
$dados_qr = "FAC-" . $id_final_exibicao . " | Cliente: " . urlencode($cliente_nome_final) . " | Total: " . $total_final . " AOA";
$texto_qr = "https://onrender.com"; // Altere pelo link dinâmico da sua fatura
// 1. Defina o texto ou link que o QR Code deve conter (Exemplo com o ID da fatura dinâmico)
$id_fatura = 126; // Pode substituir pela sua variável ex: $dados['id_pagamento']
$link_autenticacao = "https://onrender.com" . $id_fatura;
// 1. Deteta automaticamente se o site está a rodar no Localhost ou no Render
$protocolo = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? "https://" : "http://";
$dominio_atual = $_SERVER['HTTP_HOST'];

// 2. Pega a referência da fatura atual (ex: FAC-127) de forma dinâmica
$fatura_ref = $fatura_ref ?? "FAC-127"; 

// 3. Monta o link correto baseado em onde o sistema está a rodar
$link_autenticacao = $protocolo . $dominio_atual . "/fatura.php?ref=" . $fatura_ref;

// 4. Gera o QR Code com a API moderna que roda em qualquer servidor
$url_qrcode = "https://qrserver.com" . urlencode($link_autenticacao);
?>



<!DOCTYPE html>
<html lang="pt-PT">
<head>
    <meta charset="UTF-8">
    <title>Fatura_Premium_#FAC-<?php echo $id_pagamento; ?></title>
    <style>
    body { 
        background: radial-gradient(circle at top, #0f172a 0%, #070a13 100%); 
        margin: 0; 
        padding: 0; 
        font-family: 'Segoe UI', system-ui, sans-serif; 
        min-height: 100vh; 
        box-sizing: border-box;
    }
    
    .topo-acoes-fatura { 
        max-width: 440px; 
        margin: 20px auto 0 auto; 
        display: flex; 
        justify-content: flex-end; 
        padding: 0 15px; 
        width: 92%;
        box-sizing: border-box;
    }
    
    .btn-fechar-recibo { 
        background: #ef4444; 
        color: white; 
        padding: 9px 20px; 
        border-radius: 30px; 
        font-weight: bold; 
        text-decoration: none; 
        font-size: 11px; 
        text-transform: uppercase; 
        border: 1px solid #dc2626; 
        box-shadow: 0 4px 14px rgba(239, 68, 68, 0.3); 
        transition: 0.2s; 
    }
    .btn-fechar-recibo:hover { 
        background: #dc2626; 
        transform: translateY(-1.5px); 
    }

    /* 👑 PAINEL RESPONSIVO MOBILE-FIRST */
    .conteudo-fatura { 
        background: #ffffff; 
        color: #0f172a; 
        width: 92%; 
        max-width: 440px; 
        margin: 15px auto 50px auto; 
        padding: 30px 20px; /* Reduzido levemente para ecrãs pequenos de telemóveis */
        border-radius: 16px; 
        box-shadow: 0 20px 50px rgba(0, 210, 255, 0.15); 
        border-top: 10px solid #eab308; 
        box-sizing: border-box; 
        position: relative; 
        overflow: hidden; 
    }
    .conteudo-fatura::before { 
        content: ''; 
        position: absolute; 
        top: 0; 
        left: 0; 
        width: 100%; 
        height: 100%; 
        background: linear-gradient(180deg, rgba(234, 179, 8, 0.03) 0%, rgba(255,255,255,0) 100%); 
        pointer-events: none; 
    }
    
    .topo-centro { 
        text-align: center; 
        margin-bottom: 25px; 
    }
    
    /* Centralização perfeita para a imagem do QR Code em ecrãs móveis */
    .topo-centro img {
        max-width: 150px;
        width: 100%;
        height: auto;
        margin: 10px auto 0 auto;
        display: block;
    }
    
    .linha-pontilhada { 
        border-top: 2px dashed #cbd5e1; 
        margin: 18px 0; 
        position: relative; 
    }
    
    /* Correção de quebra de texto em ecrãs pequenos */
    .row-item { 
        display: flex; 
        justify-content: space-between; 
        align-items: center;
        font-size: 13.5px; 
        margin-bottom: 10px; 
        color: #334155; 
        gap: 10px;
    }
    .row-item span {
        word-break: break-word; /* Evita que nomes longos estraguem o layout */
    }
    .row-item strong { 
        color: #0f172a; 
        font-weight: 700; 
        text-align: right;
        word-break: break-word;
    }
    
    /* BLOCO DE TOTAL COM RESPANDOR NEON */
    .bloco-total { 
        background: linear-gradient(135deg, #0f172a 0%, #1e293b 100%); 
        border: 1px solid #334155; 
        padding: 15px; 
        margin-top: 20px; 
        font-size: 14px; 
        font-weight: bold; 
        color: #4ade80; 
        border-radius: 8px; 
        box-shadow: 0 4px 15px rgba(34, 197, 94, 0.15); 
        display: flex;
        justify-content: space-between;
        align-items: center;
    }
    .bloco-total span { 
        color: #fff; 
        text-transform: uppercase; 
        font-size: 11px; 
        letter-spacing: 0.5px; 
    }
    .bloco-total strong { 
        font-size: 18px; 
        color: #22c55e; 
        font-family: monospace; 
    }
    
    .btn-print { 
        display: block; 
        width: 100%; 
        padding: 14px; 
        background: linear-gradient(135deg, #eab308 0%, #ca8a04 100%); 
        color: #000; 
        border: none; 
        font-weight: 800; 
        text-transform: uppercase; 
        cursor: pointer; 
        margin-top: 25px; 
        font-size: 12px; 
        letter-spacing: 1px; 
        border-radius: 8px; 
        box-shadow: 0 4px 15px rgba(234, 179, 8, 0.3); 
        transition: 0.2s; 
    }
    .btn-print:hover { 
        background: linear-gradient(135deg, #ca8a04 0%, #a16207 100%); 
        transform: translateY(-1px); 
    }
    
    /* 🖨️ MEDIA QUERY DE IMPRESSÃO TÉRMICA OTIMIZADA */
    @media print { 
        body { 
            background: white !important; 
            color: black !important;
        } 
        .conteudo-fatura { 
            margin: 0 auto !important; 
            box-shadow: none !important; 
            border-top: none !important; 
            padding: 0 !important; 
            width: 100% !important; 
            max-width: 100% !important;
        } 
        .btn-print, .topo-acoes-fatura, .btn-fechar-recibo { 
            display: none !important; 
        } 
    }
</style>

<body>

<div class="topo-acoes-fatura">
    <!-- Captura o ID da URL atual ou usa o código extraído do pagamento -->
    <?php 
        // Se a fatura foi aberta passando o id da loja na URL (ex: fatura.php?id=125&loja=237)
        $id_retorno = isset($_GET['loja']) ? (int)$_GET['loja'] : ($pagamento['codigo'] ?? '0'); 
    ?>
   <a href="Dashboard.php" class="btn-fechar-recibo">✕ Fechar Recibo</a>
</div>

    <div class="conteudo-fatura" style="max-width: 420px; margin: 0 auto; box-sizing: border-box; width: 100%;">
        <div class="topo-centro">
            <div style="background: rgba(234, 179, 8, 0.1); width: 50px; height: 50px; border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 0 auto 10px auto; border: 1px solid #eab308;">
                <span style="font-size: 24px; color: #eab308;">🎌</span>
            </div>
            <h2 style="margin: 0; font-size: 19px; color: #0f172a; font-weight: 800; letter-spacing: 0.5px;">COMPROVATIVO DE CAIXA</h2>
            <span style="font-size: 10.5px; color: #64748b; text-transform: uppercase; font-weight: bold; letter-spacing: 1px; display: block; margin-top: 3px;">Rede de Distribuição &amp; Estética Aurélius</span>
        </div>

        <div class="linha-pontilhada"></div>

        <!-- Metadados Operacionais -->
        <div class="row-item"><span>Fatura Referência:</span><strong>#FAC-<?= intval($pagamento['id'] ?? $id_pagamento) ?></strong></div>
        <div class="row-item"><span>Data de Emissão:</span><strong>
            <?php 
            $data_final_servico = $pagamento['data_venda'] ?? ($pagamento['data_cadastro'] ?? ($pagamento['data_publicacao'] ?? ''));
            if (!empty($data_final_servico) && $data_final_servico !== '0000-00-00 00:00:00') {
                echo date('d/m/Y H:i', strtotime($data_final_servico));
            } else {
                echo date('d/m/Y H:i'); 
            }
            ?>
        </strong></div>
        <div class="row-item"><span>Estado de Liquidação:</span><span style="color: #16a34a; font-weight: 800;">✓ CONFIRMADO</span></div>
        <div class="row-item"><span>Profissional / Atendente:</span><strong style="color: #2563eb; text-transform: uppercase;"><?= htmlspecialchars($atendente_final) ?></strong></div>

        <div class="linha-pontilhada"></div>

        <!-- 🟢 MÓDULO REATIVO: DADOS DO COMPRADOR / CLIENTE DINÂMICO -->
        <div class="row-item">
            <span>Cliente Destinatário:</span>
            <strong>
                <?php 
                // Varre de forma inteligente quem é o comprador baseado na origem da linha
                $cliente_nome_final = "Consumidor Geral";
                if (!empty($pagamento['nome_candidato'])) {
                    $cliente_nome_final = $pagamento['nome_candidato'];
                } elseif (!empty($pagamento['nome_autor'])) {
                    $cliente_nome_final = $pagamento['nome_autor'];
                } elseif (!empty($pagamento['nome'])) {
                    // Proteção para não exibir o nome da barbearia se o registro for de um cliente
                    $cliente_nome_final = ($pagamento['nivel'] === 'cliente') ? $pagamento['nome'] : "Consumidor Final";
                } elseif (!empty($pagamento['cliente'])) {
                    $cliente_nome_final = $pagamento['cliente'];
                }
                echo htmlspecialchars($cliente_nome_final);
                ?>
            </strong>
        </div>
        <div class="row-item"><span>Terminal Eletrónico:</span><span><?= htmlspecialchars($telefone_cliente_final) ?></span></div>

        <div class="linha-pontilhada"></div>

        <!-- 🟢 MÓDULO REATIVO: ARTIGO OU TIPO DE SERVIÇO PROCESSADO -->
        <div style="font-size: 11px; font-weight: bold; color: #64748b; margin-bottom: 6px; text-transform: uppercase; letter-spacing: 0.5px;">Artigo / Serviço Processado:</div>
        <div style="font-size: 14.5px; font-weight: 800; margin-bottom: 12px; color: #0f172a; border-left: 3px solid #ca8a04; padding-left: 8px; word-break: break-word;">
            <?php 
            // Rastreia e mapeia automaticamente os nomes de serviços de qualquer tabela do ecossistema
            $servico_resolvido = "Atendimento Estético Geral";
            if (!empty($pagamento['servico'])) {
                $servico_resolvido = $pagamento['servico'];
            } elseif (!empty($pagamento['titulo'])) {
                $servico_resolvido = $pagamento['titulo'];
            } elseif (!empty($pagamento['nome_produto'])) {
                $servico_resolvido = "Cosmético: " . $pagamento['nome_produto'];
            } elseif (!empty($pagamento['cargo'])) {
                $servico_resolvido = "Inscrição de Vaga: " . $pagamento['cargo'];
            } elseif (!empty($pagamento['tipos_de_servico'])) {
                $servico_resolvido = $pagamento['tipos_de_servico'];
            }
            echo htmlspecialchars($servico_resolvido);
            ?>
        </div>

        <div class="row-item"><span>Preço de Tabela Base:</span><span><?= number_format($preco_tabela_exibicao, 2, ',', '.') ?> AOA</span></div>
        
        <?php if ($is_premium_cliente || $desconto_kz > 0): ?>
            <div class="row-item" style="color: #ca8a04; font-weight: bold;"><span>Estatuto VIP PWA (Desconto):</span><span>-<?= number_format($desconto_kz, 2, ',', '.') ?> AOA</span></div>
        <?php endif; ?>

        <div class="bloco-total" style="display: flex; justify-content: space-between; align-items: center; margin-top: 15px; background: #0f172a; padding: 12px; border-radius: 6px; color: #fff;">
            <span>LÍQUIDO PAGO NA APP:</span>
            <strong style="font-size: 16px; color: #eab308;"><?= number_format($total_final, 2, ',', '.') ?> AOA</strong>
        </div>

        <!-- Autenticidade QR Code Tratado -->
<div style="text-align: center; margin-top: 25px; background: #f8fafc; padding: 18px; border: 1px dashed #cbd5e1; border-radius: 8px; box-sizing: border-box; width: 100%; display: flex; flex-direction: column; align-items: center; justify-content: center;">
    
<?php if (!empty($url_qrcode)): ?>
    <img src="<?= htmlspecialchars($url_qrcode) ?>" 
         alt="QR Code" 
         style="display: block; width: 130px; height: 130px; box-shadow: 0 4px 10px rgba(0,0,0,0.05); border: 1px solid #ddd; padding: 4px; background: #fff; margin-bottom: 10px;"
         onerror="this.style.display='none'; document.getElementById('qr-erro').style.display='block';">
<?php endif; ?>

<!-- Bloco de contingência caso a imagem falhe no Render -->
<div id="qr-erro" style="display: none; padding: 15px; background: #fee2e2; color: #ef4444; border: 1px solid #fca5a5; border-radius: 6px; font-size: 11px; font-weight: bold; margin-bottom: 10px;">
    ⚠️ Erro ao carregar QR Code dinâmico no servidor.
</div>

<span style="font-size: 10px; color: #64748b; display: block; font-family: sans-serif; line-height: 1.4; max-width: 250px; margin: 0 auto;">
    Passe a câmara do telemóvel para auditar a autenticidade deste cupão único da rede.
</span>
</div>

        <div class="linha-pontilhada"></div>
        
        <p style="text-align: center; font-size: 11px; color: #64748b; margin: 0 0 20px 0; font-weight: 600; letter-spacing: 0.3px; line-height: 1.4;">
            ✓ Autenticação Eletrónica Registada<br>
            Obrigado por escolher os serviços da rede Aurélius!
        </p>
        
        <button class="btn-print" onclick="window.print()" style="width: 100%; padding: 14px; background: #0f172a; color: #fff; font-weight: bold; border: none; border-radius: 6px; cursor: pointer; text-transform: uppercase; font-size: 13px; letter-spacing: 0.5px;">🖨️ Executar Impressão Física</button>
    </div>
</body>
</html>