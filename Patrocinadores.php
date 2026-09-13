<?php
// =========================================================================
// 🪙 PORTAL DE ALIANÇAS, INVESTIMENTOS E DIVULGAÇÃO SaaS — GRUPO AURÉLIUS
// =========================================================================
if (session_status() === PHP_SESSION_NONE) { 
    session_start(); 
}
date_default_timezone_set('Africa/Luanda');

include_once("Conexao.php");

// Conector tolerante com o XAMPP local
$conexao_link = $conexao_aurelius ?? $conexao ?? $link ?? $conn ?? $pdo ?? null;
if (!$conexao_link || !($conexao_link instanceof mysqli)) {
    $conexao_link = @mysqli_connect("127.0.0.1", "root", "", "aurelius_salao");
}

if (!$conexao_link || mysqli_connect_errno()) { 
    die("<div style='padding:20px; background:#070b12; color:#ef4444; font-family:sans-serif;'>Erro de conexão com a base de dados central.</div>"); 
}
$conexao_link->set_charset("utf8mb4");

// 🟢 1. PROCESSADOR DE PROPOSTAS DE APOIO (POST)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['enviar_proposta_apoio'])) {
    $tipo_apoiante = $mysqli->real_escape_string($_POST['tipo_apoiante']); 
    $nome_apoiante = ($tipo_apoiante === 'Anonimo') ? 'Apoiante Anónimo' : $mysqli->real_escape_string(trim($_POST['nome_apoiante']));
    $contacto      = $mysqli->real_escape_string(trim($_POST['contacto_apoiante']));
    $descricao     = $mysqli->real_escape_string(trim($_POST['descricao_apoio']));
    $valor_apoio   = isset($_POST['valor_apoio_kz']) ? floatval($_POST['valor_apoio_kz']) : 0.00;

    // Inserção reativa na tabela do banco
    $sql_apoio = "INSERT INTO pedidos_emprego (nome_candidato, telefone, experiencia, status_moderacao, data_envio) 
                  VALUES ('[APOIO] $nome_apoiante', '$contacto', 'Valor: $valor_apoio Kz | Proposta: $descricao', 'Pendente', NOW())";
    
    if ($mysqli->query($sql_apoio)) {
        // Se o utilizador digitou um valor monetário, gera a janela reativa do gateway MCX Express
        if ($valor_apoio > 0) {
            $valor_formatado = number_format($valor_apoio, 2, ',', '.') . " Kz";
            echo "<script>
                    alert('🪙 INTENÇÃO DE CONTRIBUIÇÃO REGISTADA!\\n\\nValor: $valor_formatado\\nDestino: Infraestrutura Cloud\\n\\n📱 Pague com segurança no seu Multicaixa Express ou faça a transferência para o IBAN exibido no ecrã.');
                    window.location.href='apoio.php';
                  </script>";
            exit();
        } else {
            echo "<script>alert('✓ Proposta de aliança registada com sucesso!'); window.location.href='apoio.php';</script>";
            exit();
        }
    }
}
// =========================================================================
// 📊 2. MOTOR REATIVO REAL: CONTADORES DINÂMICOS DO BANCO DE DADOS
// =========================================================================

// A. Conta o número REAL de candidaturas que as pessoas submeteram no sistema
$total_candidaturas_reais = 0;
$q_contar_cand = mysqli_query($conexao_link, "SELECT COUNT(*) as total FROM pedidos_emprego WHERE nome_candidato NOT LIKE '[APOIO]%'");
if ($q_contar_cand) {
    $res_cand = mysqli_fetch_assoc($q_contar_cand);
    $total_candidaturas_reais = intval($res_cand['total']);
}

// B. Conta o número REAL de propostas de apoio financeiro/bens recebidas
$total_apoios_recebidos = 0;
$q_contar_apoio = mysqli_query($conexao_link, "SELECT COUNT(*) as total FROM pedidos_emprego WHERE nome_candidato LIKE '[APOIO]%'");
if ($q_contar_apoio) {
    $res_apoio = mysqli_fetch_assoc($q_contar_apoio);
    $total_apoios_recebidos = intval($res_apoio['total']);
}

// C. CÁLCULO DE PROGRESSO REAL DA META (Baseado no engajamento da comunidade)
// Cada ação real dos utilizadores (candidaturas + apoios) faz o gráfico crescer organicamente
$meta_objetivo_pontos = 100; // Meta de engajamento da plataforma
$pontos_atuais = ($total_candidaturas_reais * 5) + ($total_apoios_recebidos * 15); 
if ($pontos_atuais > $meta_objetivo_pontos) { $meta_objetivo_pontos = $pontos_atuais + 20; } // Meta escala dinamicamente
$percentagem_meta = floor(($pontos_atuais / $meta_objetivo_pontos) * 100);
if ($percentagem_meta > 100) { $percentagem_meta = 100; }
?>
<!DOCTYPE html>
<html lang="pt-PT">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>Alianças & Apoios — Grupo Aurélius</title>
    <style>
        body { background-color: #070b12; color: #f8fafc; font-family: 'Segoe UI', system-ui, sans-serif; padding: 15px 12px; margin: 0; }
        .container-apoios { max-width: 750px; margin: 0 auto; width: 100%; box-sizing: border-box; }
        .btn-voltar-premium { display: inline-flex; align-items: center; gap: 8px; background: rgba(30, 41, 59, 0.6); color: #94a3b8; padding: 10px 20px; border-radius: 30px; text-decoration: none; font-weight: bold; font-size: 11px; border: 1px solid #1e293b; margin-bottom: 20px; text-transform: uppercase; letter-spacing: 0.5px; }
        .header-premium { text-align: left; margin-bottom: 25px; background: linear-gradient(135deg, #1e3a8a, #070b12); padding: 25px 20px; border-radius: 16px; border: 1px solid #1e293b; position: relative; overflow: hidden; }
        .header-premium::after { content: ''; position: absolute; top: 0; left: 0; width: 4px; height: 100%; background: linear-gradient(to bottom, #38bdf8, #ca8a04); }
        .tabs-control { display: flex; gap: 8px; margin-bottom: 25px; background: #0f172a; padding: 6px; border-radius: 12px; border: 1px solid #1e293b; }
        .tab-btn { flex: 1; background: transparent; border: none; color: #64748b; padding: 12px 6px; border-radius: 8px; cursor: pointer; font-weight: bold; font-size: 11px; text-transform: uppercase; transition: all 0.2s; }
        .tab-btn.active { background: linear-gradient(135deg, #1e3a8a, #0284c7); color: #fff; }
        .painel-conteudo { display: none; }
        .painel-conteudo.active { display: block; }
        .card-premium { background: #0f172a; border: 1px solid #1e293b; border-radius: 16px; padding: 20px; margin-bottom: 20px; box-sizing: border-box; }
        .label-premium { font-size: 11px; color: #38bdf8; font-weight: bold; text-transform: uppercase; letter-spacing: 0.5px; display: block; margin-bottom: 6px; }
        .form-control { width: 100%; background: #070b12; border: 1px solid #1e293b; border-radius: 8px; padding: 12px 14px; color: white; box-sizing: border-box; margin-bottom: 18px; font-size: 14px; outline: none; font-family: inherit; }
        .btn-submeter { background: linear-gradient(135deg, #38bdf8, #0284c7); color: #fff !important; font-weight: bold; border: none; padding: 14px; border-radius: 8px; cursor: pointer; width: 100%; text-transform: uppercase; font-size: 11px; letter-spacing: 0.5px; font-family: inherit; }
        .barra-fundo { width: 100%; background: #070b12; height: 14px; border-radius: 20px; border: 1px solid #1e293b; overflow: hidden; margin: 12px 0; }
        .barra-preenchida { height: 100%; background: linear-gradient(to right, #22c55e, #38bdf8); border-radius: 20px; transition: width 0.6s ease; }
        .btn-rede-social { display: flex; align-items: center; justify-content: center; gap: 8px; padding: 12px; border-radius: 8px; border: none; color: white; font-weight: bold; font-size: 11px; cursor: pointer; text-transform: uppercase; text-decoration: none; width: 100%; box-sizing: border-box; margin-bottom: 10px; font-family: inherit; }
        .metricas-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 12px; margin-bottom: 15px; }
        .bloco-metrica { background: #070b12; border: 1px solid #1e293b; padding: 14px; border-radius: 10px; text-align: center; }
    </style>
</head>
<body>

<div class="container-apoios">
    
    <a href="Principal.php" class="btn-voltar-premium">✕ Voltar</a>

    <div class="header-premium">
        <h2 style="color: #fff; margin: 0 0 6px 0; font-size: 20px; font-weight: bold; text-transform: uppercase; letter-spacing: 0.5px;">🤝 Alianças e Apoios Corporativos</h2>
        <p style="color: #cbd5e1; font-size: 12.5px; margin: 0; line-height: 1.4;">Espaço aberto à comunidade. Descubra os nossos parceiros estratégicos, contribua de forma sigilosa ou divulgue a rede.</p>
    </div>

    <div class="tabs-control">
        <button id="btn-entidades" class="tab-btn active" onclick="mudarAba('entidades')">🏢 Alianças</button>
        <button id="btn-contribuir" class="tab-btn" onclick="mudarAba('contribuir')">🪙 Contribuir</button>
        <button id="btn-divulgar" class="tab-btn" onclick="mudarAba('divulgar')">📢 Divulgar</button>
    </div>

    <!-- ABA 1: PARCEIROS E QUADRO DE HONRA REAL -->
    <div id="aba-entidades" class="painel-conteudo active">
        <!-- 📈 PAINEL DE MÉTRICAS REAIS E DINÂMICAS -->
        <div class="metricas-grid">
            <div class="bloco-metrica">
                <span style="color: #64748b; font-size: 11px; font-weight: bold; text-transform: uppercase; display: block; margin-bottom: 4px;">👥 Candidatos Inscritos</span>
                <strong style="color: #38bdf8; font-size: 22px; font-family: monospace;"><?= $total_candidaturas_reais; ?></strong>
            </div>
            <div class="bloco-metrica">
                <span style="color: #64748b; font-size: 11px; font-weight: bold; text-transform: uppercase; display: block; margin-bottom: 4px;">💎 Propostas de Apoio</span>
                <strong style="color: #ca8a04; font-size: 22px; font-family: monospace;"><?= $total_apoios_recebidos; ?></strong>
            </div>
        </div>

        <div class="card-premium">
            <h3 style="color: #38bdf8; margin-top:0; margin-bottom: 15px; font-size: 13px; text-transform: uppercase; font-weight: bold; border-left: 3px solid #38bdf8; padding-left: 8px;">🚀 Infraestrutura Tecnológica</h3>
            <p style="color: #94a3b8; font-size: 12px; margin-bottom: 15px; line-height: 1.4;">Integrações e ferramentas que suportam e automatizam as operações SaaS do Grupo Aurélius:</p>
            <div style="background:#070b12; padding:12px; border-radius:8px; border:1px solid #1e293b; margin-bottom:8px; text-align:left; font-size:12.5px;">🏢 <b>AppyPay Gateway</b> • Processamento Multicaixa Express</div>
            <div style="background:#070b12; padding:12px; border-radius:8px; border:1px solid #1e293b; text-align:left; font-size:12.5px;">🧾 <b>SWEG Angola</b> • Faturamento Reativo Homologado</div>
        </div>
    </div>

    <!-- ABA 2: GRÁFICO REAL DE CRESCIMENTO E FORMULÁRIO -->
    <div id="aba-contribuir" class="painel-conteudo">
     <!-- 🟢 FORMULÁRIO DE REGISTO DE ALIANÇAS E INVESTIMENTOS -->
     <div class="card-premium">
            <h3 style="color: #fff; font-size: 13px; text-transform: uppercase; margin-top: 0; margin-bottom: 12px; font-weight: bold; text-align: left; border-left: 3px solid #38bdf8; padding-left: 6px;">✍️ Registar ou Propor Apoio Voluntário</h3>
            <form action="apoio.php" method="POST">
                <input type="hidden" name="enviar_proposta_apoio" value="1">
                
                <label class="label-premium">Privacidade do Registo:</label>
                <select name="tipo_apoiante" id="tipo_apoiante" class="form-control" onchange="ajustarCampoNome()" style="color:#fff; background:#070b12; cursor:pointer;" required>
                    <option value="Singular">Identificado (Aparecer no Quadro de Honra)</option>
                    <option value="Anonimo">Anónimo (Manter Sigilo Absoluto de Identidade)</option>
                </select>
                
                <!-- O JavaScript oculta ou mostra esta div de forma dinâmica para preservar o sigilo -->
                <div id="wrapper_nome_apoio">
                    <label class="label-premium">Teu Nome / Nome da Empresa:</label>
                    <input type="text" name="nome_apoiante" id="nome_apoiante" class="form-control" placeholder="Ex: Gráfica Soma, Angelino Comercial">
                </div>

                <label class="label-premium">Contacto de Retaguarda (WhatsApp / E-mail):</label>
                <input type="text" name="contacto_apoiante" class="form-control" placeholder="Ex: 915658574 ou email@dominio.com" required>

                <label class="label-premium">Descrição do Apoio ou Bens (Dinheiro, Equipamento, Portfólio):</label>
                <textarea name="descricao_apoio" class="form-control" rows="4" placeholder="Descreva de forma pacífica e clara como pretende ajudar a causa do ecossistema..." required style="resize: none; background: #070b12; color: #fff; line-height: 1.5;"></textarea>
                
                <button type="submit" class="btn-submeter">Submeter Registro de Parceria ⚡</button>
            </form>
        </div>
    </div> <!-- Fecha aba-contribuir -->

    <!-- ABA 3: ENGRENAGEM DE DIVULGAÇÃO MASSIVA REAL -->
    <div id="aba-divulgar" class="painel-conteudo">
        <div class="card-premium">
            <h3 style="color: #fff; font-size: 14px; text-transform: uppercase; margin-top: 0; margin-bottom: 8px; font-weight: bold; text-align: left;">📢 Multiplica a Nossa Mensagem!</h3>
            <p style="color: #94a3b8; font-size: 12.5px; margin: 0 0 20px 0; line-height: 1.4; text-align: left;">A melhor forma de ajudar o **Grupo Aurélius** a render e crescer de verdade é fazendo com que mais salões de beleza e clientes conheçam o nosso PWA. Partilhe o link oficial nas suas redes com apenas um clique [local]:</p>
            
            <!-- Links com gatilhos de compartilhamento orgânico -->
            <a href="https://whatsapp.com🚨+Vê+este+SaaS+Incrível!+Conhece+a+Bolsa+de+Emprego+e+o+Marketplace+do+Grupo+Aurélius.+Acede+já:+https://onrender.com" target="_blank" class="btn-rede-social" style="background: #22c55e;">
                🟢 Partilhar no WhatsApp
            </a>
            
            <a href="https://www.facebook.com/sharer/sharer.php?u=https://onrender.com" target="_blank" class="btn-rede-social" style="background: #1877f2;">
                🔵 Partilhar no Facebook
            </a>
            
            <button type="button" onclick="copiarLinkLinkDinamico()" class="btn-rede-social" style="background: #1e293b; border: 1px solid #334155; color: #fff;">
                🔗 Copiar Link Amigável da Plataforma
            </button>
        </div>
    </div>

</div> <!-- Fecha container-apoios -->

<!-- 🤖 MOTOR INTELECTUAL REATIVO JAVASCRIPT -->
<script>
function mudarAba(aba) {
    document.querySelectorAll('.painel-conteudo').forEach(p => p.classList.remove('active'));
    document.querySelectorAll('.tab-btn').forEach(b => b.classList.remove('active'));
    
    document.getElementById('aba-' + aba).classList.add('active');
    document.getElementById('btn-' + aba).classList.add('active');
}

function ajustarCampoNome() {
    const select = document.getElementById('tipo_apoiante');
    const wrapper = document.getElementById('wrapper_nome_apoio');
    const input = document.getElementById('nome_apoiante');
    
    // Oculta dinamicamente o input e remove a obrigatoriedade caso o benfeitor opte por anonimato
    if (select.value === 'Anonimo') {
        wrapper.style.display = 'none';
        input.removeAttribute('required');
        input.value = '';
    } else {
        wrapper.style.display = 'block';
        input.setAttribute('required', 'required');
    }
}

function copiarLinkLinkDinamico() {
    const link_pwa = "https://onrender.com";
    navigator.clipboard.writeText(link_pwa).then(() => {
        alert("✓ Link amigável copiado para a área de transferência! Partilha com barbeiros, trancistas e manicures do Huambo.");
    });
}
</script>

</body>
</html>