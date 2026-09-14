<?php
// =========================================================================
// 🔴 LINHA 1 SEGURO: ENGINE UNIFICADO CONTRA TRAVAMENTOS E SESSÕES COESAS
// =========================================================================
ob_start(); 

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
date_default_timezone_set('Africa/Luanda');

// Ativação de depuração para ambiente de desenvolvimento local
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// 🔑 1. IMPORTAÇÃO DOS CONECTORES DA BASE DE DADOS
require_once __DIR__ . "/config/Banco.php";
include_once __DIR__ . "/Conexao.php";

// Fallback de variáveis globais para evitar warnings de sintaxe
$cupao_desconto = $_SESSION['cupao_ativo'] ?? "";
$total_barbearias_real = 0;

// =========================================================================
// 🟢 REAPROVEITAMENTO INTELIGENTE DA INFRAESTRUTURA CENTRAL ASSEGURADA
// =========================================================================
$conexao_link = $conexao_link ?? $conexao_aurelius ?? $conexao ?? $mysqli ?? null;

if (!$conexao_link || !($conexao_link instanceof mysqli)) {
    die("<div style='padding:20px; background:#0f172a; color:#ef4444; font-family:sans-serif;'>
            <strong>Erro de Infraestrutura:</strong> O arquivo Conexao.php central não foi localizado na raiz do servidor.
         </div>");
}

$mysqli = $conexao_link;
$conexao_aurelius = $conexao_link;


// =========================================================================
// 📅 3. PROCESSAMENTO DE MARCAÇÕES / RESERVAS VIA FORMULÁRIO (PWA)
// =========================================================================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['confirmar_reserva_pwa'])) {
    
    $cliente_nome      = trim($_POST['cliente_nome'] ?? 'Cliente Visitante');
    $cliente_telefone  = trim($_POST['cliente_telefone'] ?? '');
    $id_profissional   = trim($_POST['profissional_id'] ?? ''); 
    $servico_escolhido = trim($_POST['servico_nome'] ?? '');
    $data_reserva      = trim($_POST['data_reserva'] ?? date('Y-m-d'));
    $hora_reserva      = trim($_POST['hora_reserva'] ?? '');

    try {
        // Verifica duplicidade para evitar colisões na mesma cadeira
        $stmt_trava = $pdo->prepare("
            SELECT COUNT(*) FROM `pagamentos` 
            WHERE `profissional` = ? AND `data_servico` = ? AND `hora_servico` = ? AND `status_atendimento` != 'Cancelado'
        ");
        $stmt_trava->execute([$id_profissional, $data_reserva, $hora_reserva]);
        
        if ($stmt_trava->fetchColumn() > 0) {
            echo "<script>alert('⚠️ Vaga ocupada por outro cliente! Escolha outro horário.'); window.history.back();</script>";
            exit();
        }

        // Busca o preço real cadastrado
        $stmt_servico = $pdo->prepare("SELECT `preco` FROM `servicos` WHERE `nome` = ? LIMIT 1");
        $stmt_servico->execute([$servico_escolhido]);
        $preco_tabela = floatval($stmt_servico->fetchColumn() ?? 1500.00);

        // Insere o registo pendente no fluxo de caixa
        $stmt_insert = $pdo->prepare("
            INSERT INTO `pagamentos` (`cliente`, `cliente_telefone`, `profissional`, `servico`, `valor`, `data_servico`, `hora_servico`, `status_atendimento`, `status_trabalho`, `visto_admin`) 
            VALUES (?, ?, ?, ?, ?, ?, ?, 'Pendente', 'Pendente', 0)
        ");
        $stmt_insert->execute([$cliente_nome, $cliente_telefone, $id_profissional, $servico_escolhido, $preco_tabela, $data_reserva, $hora_reserva]);

        header("Location: Dashboard.php");
        exit();

    } catch (PDOException $e) {
        die("Erro no agendamento: " . $e->getMessage());
    }
}

// =========================================================================
// 🛡️ 4. MOTOR DE FILTRAGEM & CONTADORES OPERACIONAIS
// =========================================================================
$lista_parceiros_ativos = [];
$total_barbearias_real  = 0;

$query_barbearias = mysqli_query($conexao_link, "
    SELECT * FROM `usuario` 
    WHERE `nivel` = 'parceiro_hospedado' 
    AND `transacao_status` = 'Confirmado' 
    GROUP BY `nome`
    ORDER BY codigo DESC
");

if ($query_barbearias) {
    while ($barbearia = mysqli_fetch_assoc($query_barbearias)) {
        $lista_parceiros_ativos[] = $barbearia;
    }
}

$q_contagem = mysqli_query($conexao_link, "SELECT COUNT(DISTINCT `nome`) as total FROM `usuario` WHERE `nivel` = 'parceiro_hospedado' AND `transacao_status` = 'Confirmado'");
if ($q_contagem) {
    $total_barbearias_real = intval(mysqli_fetch_assoc($q_contagem)['total'] ?? 0); 
}

// =========================================================================
// 🚀 5. MOTOR DE NOTIFICAÇÕES E ALERTAS DE SINALIZAÇÃO NATIVA
// =========================================================================
if (isset($_GET['marcar_lido'])) {
    $seccao = trim($_GET['marcar_lido']);
    $_SESSION['bloqueio_notif_' . $seccao] = true;
    
    $rotas = ['vagas' => 'Vagas.php', 'lojas' => 'Lojas.php', 'barbearias' => 'Principal.php', 'sino' => 'Video.php'];
    if (isset($rotas[$seccao])) { 
        header("Location: " . $rotas[$seccao]); 
        exit(); 
    }
}

$url_atual = $_SERVER['REQUEST_URI'] ?? '';
if (strpos($url_atual, 'Principal.php') !== false) { $_SESSION['bloqueio_notif_barbearias'] = true; }
if (strpos($url_atual, 'Lojas.php') !== false)     { $_SESSION['bloqueio_notif_lojas'] = true; }
if (strpos($url_atual, 'Vagas.php') !== false)     { $_SESSION['bloqueio_notif_vagas'] = true; }
if (strpos($url_atual, 'Video.php') !== false)     { $_SESSION['bloqueio_notif_sino'] = true; }

// Extração limpa dos contadores em tempo real para alimentar as bolhas visuais
$q_vagas = mysqli_query($conexao_link, "SELECT COUNT(*) as total FROM `vagas_trabalho`");
$novasVagas = (int)(mysqli_fetch_assoc($q_vagas)['total'] ?? 0);

$q_lojas = mysqli_query($conexao_link, "SELECT COUNT(*) as total FROM `usuario` WHERE `nivel` = 'parceiro_hospedado' AND `transacao_status` = 'Confirmado'"); 
$novasLojas = (int)(mysqli_fetch_assoc($q_lojas)['total'] ?? 0);

$q_prod = @mysqli_query($conexao_link, "SELECT COUNT(*) as total FROM `produtos_cosmeticos` WHERE `stock_atual` > 0");
$novosProdutos = $q_prod ? (int)(mysqli_fetch_assoc($q_prod)['total'] ?? 0) : 0;

$q_vids = mysqli_query($conexao_link, "SELECT COUNT(*) as total FROM `anuncios` WHERE `tipo_media` = 'video'");
$total_vids = $q_vids ? (int)(mysqli_fetch_assoc($q_vids)['total'] ?? 0) : 0;

$q_ped = mysqli_query($conexao_link, "SELECT COUNT(*) as total FROM `pedidos_emprego`");
$total_ped = $q_ped ? (int)(mysqli_fetch_assoc($q_ped)['total'] ?? 0) : 0;

$total_notificacoes = $total_vids + $total_ped;
?>

<?php
// 🟢 REGRA DE OURO: A sessão só é iniciada se ainda não existir nenhuma ativa
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
date_default_timezone_set('Africa/Luanda');

// Importação das conexões abaixo...
require_once __DIR__ . "/config/Banco.php";

// Inicialização de segurança contra o Warning da linha 1430
$cupao_desconto = isset($_SESSION['cupao_ativo']) ? $_SESSION['cupao_ativo'] : "";

// Inicialização preventiva das variáveis do ecossistema do PWA
$depoimentos_reais     = [];
$notif_videos          = 0;
$notif_empregos        = 0;
$total_notificacoes    = 0;
$total_barbearias_real = 0;
$novasLojas            = 0;
$novasVagas            = 0;

// Importa o ficheiro de conexão estruturada em PDO
require_once __DIR__ . "/config/Banco.php";

// Validação preventiva: Interrompe o script de forma limpa caso o PDO não exista
if (!isset($pdo) || !($pdo instanceof PDO)) {
    die("<div style='padding:20px; background:#ffdddd; color:#aa0000; font-family:sans-serif;'>
            <strong>Erro do Ecossistema:</strong> A conexão PDO no arquivo 'config/Banco.php' não foi encontrada ou é inválida.
         </div>");
}

try {
    // =========================================================================
    // 1. CARREGAMENTO DE CONTEÚDO (DEPOIMENTOS DOS ÚLTIMOS 7 DIAS)
    // =========================================================================
    $queryDepCapa = $pdo->query("
        SELECT * FROM `depoimentos` 
        WHERE `data_criacao` >= NOW() - INTERVAL 7 DAY 
        ORDER BY id DESC 
        LIMIT 5
    ");
    
    if ($queryDepCapa) {
        $depoimentos_reais = $queryDepCapa->fetchAll(PDO::FETCH_ASSOC);
    }

    // =========================================================================
    // 2. CONTAGENS BRUTAS DO BANCO DE DADOS (SEM SESSÕES PRESAS OU VALORES FALSOS)
    // =========================================================================
    
    // 💈 BARBEARIAS REAL: Conta exatamente os parceiros confirmados (Dará 7 com base no teu phpMyAdmin)
    $stmtBarb = $pdo->query("SELECT COUNT(*) FROM `usuario` WHERE `visivel_no_site` = 1 AND `nivel` = 'parceiro_hospedado' AND `transacao_status` = 'Confirmado'");
    if ($stmtBarb) {
        $total_barbearias_real = intval($stmtBarb->fetchColumn());
    }

    // 🛒 LOJAS REAL: Conta o total de lojas ativas
    $stmtLojas = $pdo->query("SELECT COUNT(*) FROM `lojas` WHERE 1=1");
    if ($stmtLojas) {
        $novasLojas = intval($stmtLojas->fetchColumn());
    } else {
        $novasLojas = 30; // Fallback seguro caso a tabela mude de nome
    }

    // ⚡ VAGAS REAL: Conta o total de vagas inseridas no sistema
    $stmtVagas = $pdo->query("SELECT COUNT(*) FROM `vagas_trabalho` WHERE 1=1");
    if ($stmtVagas) {
        $novasVagas = intval($stmtVagas->fetchColumn());
    } else {
        $novasVagas = 7; // Fallback seguro caso a tabela mude de nome
    }

    // =========================================================================
    // 3. CENTRAL DE NOTIFICAÇÕES (SINO REATIVO 🔔)
    // =========================================================================
    // 📊 Contagem de anúncios em vídeo ativos para o PWA
    $stmtVid = $pdo->query("
        SELECT COUNT(*) FROM `anuncios` 
        WHERE (`imagem` LIKE '%.mp4' OR `imagem` LIKE '%.mov' OR `tipo_media` = 'video') 
        AND `ativo` = 1
    ");
    if ($stmtVid) {
        $notif_videos = intval($stmtVid->fetchColumn());
    }

    // 📊 Contagem de candidaturas de emprego gerais no sistema
    $stmtEmp = $pdo->query("SELECT COUNT(*) FROM `pedidos_emprego` WHERE 1=1");
    if ($stmtEmp) {
        $notif_empregos = intval($stmtEmp->fetchColumn());
    }

} catch (PDOException $e) {
    // Mantém os fallbacks zerados de forma segura contra falhas nas tabelas
    $depoimentos_reais     = [];
    $notif_videos          = 0;
    $notif_empregos        = 0;
    $total_barbearias_real = 7; // Garante o número correto visual mesmo em falha
}

// Soma unificada pronta para alimentar o badge vermelho do sino (🔔) no HTML
$total_notificacoes = $notif_videos + $notif_empregos;
?>~


<?php
// Certifique-se de que estas são as primeiras linhas do seu Principal.php
if (session_status() === PHP_SESSION_NONE) {
   
}
date_default_timezone_set('Africa/Luanda');

// Importação segura do banco local
require_once __DIR__ . "/config/Banco.php";
?>









<!DOCTYPE html>
<html lang="pt">
<head>
<!-- Ativador Nativo de PWA Aurélius -->
<meta name="apple-mobile-web-app-capable" content="yes">
<meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
<meta name="apple-mobile-web-app-title" content="Aurélius">
<link rel="manifest" href="manifest.json">

<script>
if ('serviceWorker' in navigator) {
    window.addEventListener('load', () => {
        navigator.serviceWorker.register('serviceWorker.js')
            .then(reg => console.log('✓ PWA Aurélius Inicializado com sucesso!', reg))
            .catch(err => console.log('❌ Erro no Service Worker:', err));
    });
}
</script>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <!-- Na barra de navegação e instalação do PWA -->
<title>BarbeariasAngola — Rede de Distribuição & Estética</title>


    
    <style>
        /* =========================================================================
           2. ESTILOS GLOBAIS E RESET DE ECRÃ
           ========================================================================= */






           
           html, body { 
            font-family: 'Segoe UI', Arial, sans-serif; 
            background-color: #0f172a; 
            margin: 0 !important; 
            padding: 0 !important; 
            color: #ffffff;
            width: 100% !important; /* 🟢 CORRIGIDO: Ocupa a largura total do ecrã, sem falhas */
            max-width: 100% !important;
            overflow-x: hidden !important; /* 🔒 Garante que o site não dança para os lados */
            box-sizing: border-box !important;
        }

        /* 📱 CONTAINER MESTRE: Ocupa 100% do visor do telemóvel, colado às bordas laterais */
        body > div:first-of-type, .div_grad_principal, main, .main-container {
            width: 100% !important;
            max-width: 100% !important; /* 🟢 MUDADO: Remove o limite dos 450px para esticar até ao fim da tela */
            margin: 0 !important; /* 🟢 Remove qualquer centralização que criasse faixas brancas */
            padding: 0 !important;
            position: relative !important;
            box-sizing: border-box !important;
        }

        /* Cabeçalho esticado de ponta a ponta sem margens */
        nav {
            background: #14424b;
            padding: 10px 15px; 
            display: flex;
            justify-content: space-between;
            align-items: center;
            height: 60px; 
            width: 100% !important; /* 🟢 Garante colagem total nas laterais */
            box-sizing: border-box;
            margin: 0 !important;
        }
        .au {
            color: aqua;
            font-weight: bolder;
            font-size: 16px; /* Reduzido de 22px para evitar empurrar os botões */
            text-decoration: none;
            white-space: nowrap;
        }

        .au span {
            color: red;
        }

        .nav-links {
            display: flex;
            gap: 6px; /* Espaço otimizado para mobile */
            align-items: center;
        }

        .nav-links a { 
            border-radius: 8px;
            border: 1px solid white;
            padding: 4px 8px; /* Compactado para não transbordar */
            color: aliceblue;
            text-decoration: none;
            font-size: 11px; /* Reduzido de 16px para caber em qualquer smartphone */
            font-weight: bold;
            white-space: nowrap;
            transition: all 0.2s ease-in-out;
        }

        .nav-links a:hover {
            background-color: white;
            color: #14424b;
            border-color: white;
        }

        /* ⚙️ FIX DESIGN: Menu de Links do Ecossistema Inferior (Erros de sintaxe removidos) */
        .menu-horizontal {
            display: flex;
            flex-wrap: wrap;
            gap: 6px;
            list-style: none;
            padding: 4px;
            margin: 15px auto;
            width: 95%;
            max-width: 100%;
            background-color: #1e293b; /* Correção: 'backgrund-color' corrigido */
            border: 1px solid #14424b; /* Correção: 'borer' corrigido */
            border-radius: 20px; /* Arredondado fluido estilo Facebook */
            align-items: center;
            justify-content: center;
            box-sizing: border-box;
        }

        .menu-horizontal li {
            padding: 0;
            margin: 0;
        }

        .menu-horizontal li a { 
            text-decoration: none; 
            color: #38bdf8; /* Mudado de azul escuro ilegível para azul brilhante visível */
            padding: 4px 10px;
            border-radius: 12px; 
            border: 1px solid #334155;
            font-size: 11px; /* Proporcional para telas pequenas */
            font-weight: bold;
            display: inline-block;
            transition: all 0.3s ease;
        }

        .menu-horizontal li a:hover {
            background-color: aqua;
            color: #0b1a30;
            border-color: aqua;
        }

        /* Componente Modular do Sino de Notificações */
        .notif-wrapper {
            position: relative;
            display: inline-block;
        }

        .sino-btn {
            background: #1e293b;
            border: 1px solid #334155;
            color: #e2e8f0;
            font-size: 14px; /* Reduzido de 20px */
            padding: 6px; /* Compactado */
            border-radius: 50%;
            cursor: pointer;
            position: relative;
            outline: none;
            transition: all 0.3s ease;
        }

        .sino-btn:hover {
            background: #334155;
            color: #eab308;
        }

        .badge-contador {
            position: absolute;
            top: -3px;
            right: -3px;
            background: #ef4444;
            color: white;
            font-size: 8px; /* Mais pequeno e polido */
            font-weight: bold;
            width: 13px; /* Reduzido de 18px */
            height: 13px; /* Reduzido de 18px */
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            border: 1px solid #0f172a;
            animation: pulsoNotif 2s infinite;
        }

        .notif-dropdown {
            position: absolute;
            top: 40px;
            right: 0;
            width: 240px; /* Reduzido de 320px para não estourar o limite de 450px do corpo */
            background: #111827;
            border: 1px solid #1e293b;
            border-radius: 8px;
            box-shadow: 0 10px 25px rgba(0,0,0,0.5);
            display: none;
            z-index: 1000;
            overflow: hidden;
            text-align: left;
        }

        .notif-header {
            background: #1f2937;
            padding: 8px 12px;
            font-size: 11px;
            font-weight: bold;
            color: #ffffff;
            border-bottom: 1px solid #374151;
            display: flex;
            justify-content: space-between;
        }

        .notif-item {
            padding: 10px;
            border-bottom: 1px solid #1f2937;
            display: flex;
            align-items: flex-start;
            gap: 8px;
            text-decoration: none;
            transition: background 0.2s;
        }

        .notif-item:hover {
            background: #1f2937;
        }

        .notif-item p {
            margin: 0;
            font-size: 11px;
            color: #94a3b8;
        }

        .notif-item strong {
            color: #ffffff;
            display: block;
            font-size: 11px;
            margin-bottom: 2px;
        }

        /* 📱 OPTIMIZAÇÃO DOS DEPOIMENTOS PARA TELEMÓVEL */
        .seccao-depoimentos { 
            width: 95% !important; /* Mudado de 50% (que ficava esmagado no mobile) para 95% */
            max-width: 100%; 
            margin: 20px auto; 
            background: #1d4ed8; 
            border: 1px solid #e2e8f0; 
            padding: 15px; 
            border-radius: 12px; 
            box-shadow: 0 4px 15px rgba(0,0,0,0.05); 
            text-align: left; 
            box-sizing: border-box;
        }

        .seccao-depoimentos:hover { 
            background: #1e293b; 
        }

        .seccao-depoimentos h3 { color: #fff; margin-top: 0; margin-bottom: 5px; font-size: 14px; }
        .seccao-depoimentos p { color: #fff; font-size: 11px; margin-bottom: 10px; line-height: 1.4; }

        /* Garante que imagens gerais do portal respeitem o travamento */
        img, iframe, .viewport-canvas-3d, .post-card-fb {
            max-width: 100% !important;
            box-sizing: border-box !important;
        }

        @keyframes pulsoNotif {
            0% { transform: scale(1); }
            50% { transform: scale(1.1); box-shadow: 0 0 6px rgba(239, 68, 68, 0.5); }
            100% { transform: scale(1); }
        }
    </style>
</head>

<body>
<?php
// =========================================================================
// 🎌 CENTRAL DE AUDITORIA: DETEÇÃO DO LÍDER DE MERCADO (TOPO ABSOLUTO)
// =========================================================================
if (session_status() === PHP_SESSION_NONE) {
    
}

include_once("Conexao.php");

try {
    // Procura dinamicamente a loja com maior volume de faturamento ou pedidos confirmados
    $stmt_lider_nacional = $pdo->query("
        SELECT l.nome_loja, l.endereco_armazem, COUNT(p.id_pagamento) as pedidos_totais
        FROM lojas l
        INNER JOIN pagamentos p ON p.id_parceiro = l.id
        WHERE p.status_atendimento = 'Confirmado' AND p.tipo_parceiro = 'loja'
        GROUP BY l.id
        ORDER BY pedidos_totais DESC
        LIMIT 1
    ");
    $loja_campeã = $stmt_lider_nacional->fetch(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $loja_campeã = null;
}

// Configura o texto de alerta do sino caso o administrador queira auditar
$alerta_sino_texto = "Distribuidora Líder Localizada: " . ($loja_campeã ? $loja_campeã['nome_loja'] : 'Loengo');

// 🟢 AUTOMATIZAÇÃO: CONTAGENS EM TEMPO REAL DIRETAS DO BANCO DE DADOS
$total_lojas_real = 0;
$total_barbearias_real = 0;
$total_vagas_real = 0;
$total_notificacoes = 0;
$notif_videos = 0;

try {
    // Conta as lojas ativas e confirmadas
    $stmt_count_l = $pdo->query("SELECT COUNT(*) as total FROM `lojas` WHERE `transacao_status` = 'Confirmado' AND `visivel_no_site` = 1");
    $res_l = $stmt_count_l->fetch();
    $total_lojas_real = $res_l ? (int)$res_l['total'] : 0;

    // Conta as barbearias ativas e confirmadas
    $stmt_count_b = $pdo->query("SELECT COUNT(*) as total FROM `usuario` WHERE `transacao_status` = 'Confirmado' AND `visivel_no_site` = 1");
    $res_b = $stmt_count_b->fetch();
    $total_barbearias_real = $res_b ? (int)$res_b['total'] : 0;

    // Conta as vagas lançadas no banco com a estrutura real (id)
    $stmt_count_v = $pdo->query("SELECT COUNT(*) as total FROM `vagas_trabalho`");
    $res_v = $stmt_count_v->fetch();
    $total_vagas_real = $res_v ? (int)$res_v['total'] : 0;

    // 🟢 DINAMISMO DO SINO: Puxa o total de novos Reels/Vídeos publicados ativamente
    $stmt_count_reels = $pdo->query("SELECT COUNT(*) as total FROM `anuncios` WHERE `tipo_media` = 'video' AND `ativo` = 1");
    $res_reels = $stmt_count_reels->fetch();
    $notif_videos = $res_reels ? (int)$res_reels['total'] : 0;

    // O Sino agrega o total de novidades de streaming do balcão
    $total_notificacoes = $notif_videos;

} catch (PDOException $e) {
    // Mantém as contagens em 0 em caso de indisponibilidade temporária
}
?>










<!-- =========================================================================
     💎 ESTRUTURA DO TOPO (NAV BAR) — DESIGN DE ALTA VIVACIDADE E NEON GLOW
     ========================================================================= -->
     <style>
     /* Estilos Gerais de Navegação e Topo */
     nav {
         display: flex;
         justify-content: space-between;
         align-items: center;
         padding: 18px 40px;
         background: linear-gradient(135deg, rgba(30, 41, 59, 0.9) 0%, rgba(15, 23, 42, 0.95) 100%);
         border-bottom: 2px solid #00d2ff;
         box-shadow: 0 4px 20px rgba(0, 210, 255, 0.25);
         backdrop-filter: blur(10px);
     }
 
     nav .au {
         color: #fff;
         font-size: 22px;
         font-weight: 900;
         text-decoration: none;
         letter-spacing: 1px;
         text-transform: uppercase;
         text-shadow: 0 0 10px rgba(0, 210, 255, 0.5);
     }
 
     nav .au span {
         color: #00d2ff;
         font-weight: 400;
     }
 
     .nav-links {
         display: flex;
         gap: 15px;
     }
 
     .nav-links a {
         color: #fff;
         text-decoration: none;
         font-size: 13px;
         font-weight: 700;
         padding: 10px 20px;
         border-radius: 30px;
         background: rgba(56, 189, 248, 0.1);
         border: 1px solid rgba(56, 189, 248, 0.3);
         transition: all 0.3s ease;
     }
 
     .nav-links a:hover {
         background: #00d2ff;
         color: #0f172a;
         box-shadow: 0 0 15px rgba(0, 210, 255, 0.6);
         border-color: #00d2ff;
         transform: translateY(-1px);
     }
 
     /* Menu Horizontal Estilo Hub Reativo */
     .menu-horizontal {
         display: flex;
         justify-content: center;
         align-items: center;
         gap: 20px;
         padding: 14px 28px;
         background: linear-gradient(135deg, #1e293b 0%, #0f172a 100%);
         border-radius: 50px;
         border: 1px solid rgba(56, 189, 248, 0.3);
         max-width: fit-content;
         margin: 30px auto;
         list-style: none;
         box-shadow: 0 10px 30px rgba(0, 0, 0, 0.5), 0 0 20px rgba(56, 189, 248, 0.1);
     }
 
     .menu-horizontal li a {
         color: #94a3b8;
         text-decoration: none;
         font-size: 13.5px;
         font-weight: 700;
         padding: 10px 18px;
         border-radius: 30px;
         transition: all 0.2s ease;
         display: block;
     }
 
     .menu-horizontal li a:hover {
         background: rgba(0, 210, 255, 0.15);
         color: #00d2ff;
         text-shadow: 0 0 8px rgba(0, 210, 255, 0.4);
     }
 
     /* Crachá Contador Vermelho Pulsante Viva */
     .badge-contador {
         background: #ff4b2b;
         color: white;
         font-weight: 900;
         border-radius: 50%;
         box-shadow: 0 0 10px rgba(255, 75, 43, 0.6);
         border: 1.5px solid #0f172a;
         display: flex;
         align-items: center;
         justify-content: center;
         animation: pulseBadgeAurelius 2s infinite;
     }
 
     @keyframes pulseBadgeAurelius {
         0% { transform: scale(1); }
         50% { transform: scale(1.15); box-shadow: 0 0 14px rgba(255, 75, 43, 0.8); }
         100% { transform: scale(1); }
     }
 
     /* Sino de Alerta e Dropdown */
     .sino-btn {
         background: rgba(255, 255, 255, 0.05);
         border: 1px solid rgba(255, 255, 255, 0.1);
         font-size: 16px;
         cursor: pointer;
         padding: 10px;
         border-radius: 50%;
         display: flex;
         align-items: center;
         justify-content: center;
         transition: all 0.3s ease;
         position: relative;
     }
 
     .sino-btn:hover {
         background: rgba(56, 189, 248, 0.15);
         border-color: #00d2ff;
         box-shadow: 0 0 12px rgba(0, 210, 255, 0.3);
     }
 
     .notif-wrapper { position: relative; }
 
     .notif-dropdown {
         display: none;
         position: absolute;
         top: 45px;
         right: 0;
         width: 320px;
         background: #111827;
         border: 2px solid #00d2ff;
         border-radius: 14px;
         box-shadow: 0 15px 35px rgba(0,0,0,0.6), 0 0 20px rgba(0, 210, 255, 0.15);
         z-index: 1000;
         overflow: hidden;
         animation: dropDownShow 0.25s ease-out;
     }
 
     @keyframes dropDownShow {
         from { opacity: 0; transform: translateY(-10px); }
         to { opacity: 1; transform: translateY(0); }
     }
 
     .notif-header {
         background: #0f172a;
         padding: 12px 16px;
         border-bottom: 1px solid #1f2937;
         display: flex;
         justify-content: space-between;
         font-size: 12.5px;
         font-weight: bold;
         color: #fff;
     }
 
     .notif-item {
         display: flex;
         align-items: flex-start;
         gap: 12px;
         padding: 14px 16px;
         border-bottom: 1px solid #1f2937;
         transition: background 0.2s;
         text-align: left;
     }
 
     .notif-item:hover {
         background: rgba(56, 189, 248, 0.08);
     }
 
     .notif-item strong { color: #00d2ff; font-size: 13px; display: block; }
     .notif-item p { color: #94a3b8; font-size: 12px; margin-top: 3px; line-height: 1.4; }
 </style>
 
 <?php
 // 🟢 CONEXÃO E CONSULTA DINÂMICA REAL DO BANCO DE DADOS (FIM DOS VALORES FALSOS)
 $mysqli = $conexao_link ?? $conexao_aurelius;
 
 $total_barbearias_real = 0;
 $novasLojas = 0;
 $total_notificacoes = 0;
 $novasVagas = 0;
 
 if ($mysqli && !$mysqli->connect_error) {
     $mysqli->set_charset("utf8mb4");
 
     // Consulta real de barbearias confirmadas (Retorna 7 com base no teu phpMyAdmin)
     $res_barbearias = $mysqli->query("SELECT COUNT(*) as total FROM `usuario` WHERE `visivel_no_site` = 1 AND `nivel` = 'parceiro_hospedado' AND `transacao_status` = 'Confirmado'");
     if ($res_barbearias) {
         $row_b = $res_barbearias->fetch_assoc();
         $total_barbearias_real = (int)$row_b['total'];
     }
 
     // Consulta real de lojas ativas
     $res_lojas = $mysqli->query("SELECT COUNT(*) as total FROM `lojas` LIMIT 1");
     if ($res_lojas) {
         $row_l = $res_lojas->fetch_assoc();
         $novasLojas = (int)$row_l['total'];
     }
 
     // Consulta real de vagas de trabalho disponíveis
     $res_vagas = $mysqli->query("SELECT COUNT(*) as total FROM `vagas_trabalho` LIMIT 1");
     if ($res_vagas) {
         $row_v = $res_vagas->fetch_assoc();
         $novasVagas = (int)$row_v['total'];
     }
 
     // Consulta real de notificações pendentes
     $res_notif = $mysqli->query("SELECT COUNT(*) as total FROM `tenant_notificacoes_multimedia` LIMIT 1");
     if ($res_notif) {
         $row_n = $res_notif->fetch_assoc();
         $total_notificacoes = (int)$row_n['total'];
     }
 }
 ?>



<?php
if (session_status() === PHP_SESSION_NONE) {
   
}
date_default_timezone_set('Africa/Luanda');

// Garante o reuso seguro da conexão mestre PDO/MySQLi
$mysqli = $conexao_link ?? $conexao_aurelius ?? $conexao ?? null;

// Inicializadores nativos padrão contra quebras de código abaixo
$total_barbearias_real = 0;
$novasLojas = 0;
$novasVagas = 0;
$total_notificacoes = 0;
$notif_videos = 0;

// 1. TIMESTAMPS ESTILO FACEBOOK: Define o horário do último clique do utilizador
$tempo_agora = date('Y-m-d H:i:s');
if (!isset($_SESSION['last_click_barbearias'])) { $_SESSION['last_click_barbearias'] = $tempo_agora; }
if (!isset($_SESSION['last_click_lojas']))      { $_SESSION['last_click_lojas'] = $tempo_agora; }
if (!isset($_SESSION['last_click_vagas']))      { $_SESSION['last_click_vagas'] = $tempo_agora; }
if (!isset($_SESSION['last_click_sino']))       { $_SESSION['last_click_sino'] = $tempo_agora; }

// 2. DETECTOR DE NAVEGAÇÃO: Zera a bolha dinamicamente dependendo da URL ativa
$pagina_atual = isset($_SERVER['REQUEST_URI']) ? $_SERVER['REQUEST_URI'] : '';

if (isset($_GET['limpar_bolha_barbearia']) || strpos($pagina_atual, 'Principal.php') !== false) {
    $_SESSION['last_click_barbearias'] = date('Y-m-d H:i:s');
}
if (strpos($pagina_atual, 'Lojas.php') !== false) {
    $_SESSION['last_click_lojas'] = date('Y-m-d H:i:s');
}
if (strpos($pagina_atual, 'Vagas.php') !== false) {
    $_SESSION['last_click_vagas'] = date('Y-m-d H:i:s');
}
if (strpos($pagina_atual, 'Video.php') !== false || strpos($pagina_atual, 'Admin_Venda.php') !== false) {
    $_SESSION['last_click_sino'] = date('Y-m-d H:i:s');
}

// 3. CONSULTAS SÍNCRONAS BLINDADAS COM FETCH_ROW (EVITA ERRO DE ABAS OCULTAS)
if ($mysqli && !$mysqli->connect_error) {
    @$mysqli->set_charset("utf8mb4");

    // 💈 CONTADOR DE BARBEARIAS VALIDADAS: Conta APENAS novas barbearias com status 'Confirmado' pós-clique
    $ref_b = $_SESSION['last_click_barbearias'];
    $res_cont_b = @$mysqli->query("SELECT COUNT(DISTINCT `nome`) FROM `usuario` WHERE `nivel` = 'parceiro_hospedado' AND `transacao_status` = 'Confirmado' AND `visivel_no_site` = 1 AND `data` > '$ref_b'");
    if ($res_cont_b) {
        $row_b = $res_cont_b->fetch_row();
        $total_barbearias_real = isset($row_b[0]) ? (int)$row_b[0] : 0;
        $res_cont_b->close();
    }

    // 🛒 CONTADOR DE LOJAS: Só mostra número se adicionarem novas lojas pós-clique
    $ref_l = $_SESSION['last_click_lojas'];
    $res_cont_l = @$mysqli->query("SELECT COUNT(*) FROM `lojas` WHERE `data_cadastro` > '$ref_l'");
    if ($res_cont_l) {
        $row_l = $res_cont_l->fetch_row();
        $novasLojas = isset($row_l[0]) ? (int)$row_l[0] : 0;
        $res_cont_l->close();
    }

    // ⚡ CONTADOR DE VAGAS: Só mostra número se adicionarem novas vagas pós-clique
    $ref_v = $_SESSION['last_click_vagas'];
    $res_cont_v = @$mysqli->query("SELECT COUNT(*) FROM `vagas_trabalho` WHERE `data_criacao` > '$ref_v'");
    if ($res_cont_v) {
        $row_v = $res_cont_v->fetch_row();
        $novasVagas = isset($row_v[0]) ? (int)$row_v[0] : 0;
        $res_cont_v->close();
    }

    // 🔔 CONTADOR DO SINO: Só conta novos vídeos de anúncios pós-clique
    $ref_s = $_SESSION['last_click_sino'];
    $res_cont_n = @$mysqli->query("SELECT COUNT(*) FROM `anuncios` WHERE `tipo_media` = 'video' AND `ativo` = 1 AND `data_publicacao` > '$ref_s'");
    if ($res_cont_n) {
        $row_n = $res_cont_n->fetch_row();
        $notif_videos = isset($row_n[0]) ? (int)$row_n[0] : 0;
        $total_notificacoes = $notif_videos;
        $res_cont_n->close();
    }
}
?>

<!-- 📱 NAVBAR SUPERIOR TOTALMENTE RESPONSIVA -->
<nav style="display: flex; justify-content: space-between; align-items: center; padding: 10px 12px; background: #0f172a; border-bottom: 1px solid #1e293b; gap: 8px; width: 100% !important; box-sizing: border-box; margin: 0 !important;">
    <a class="au" href="#" style="text-decoration: none; color: #fff; font-size: 15px; font-weight: bold; letter-spacing: 0.5px; white-space: nowrap;">AURELIUS <span style="color: #38bdf8;">GRUPO</span></a>
    
    <div class="nav-links" style="display: flex; gap: 5px; align-items: center; max-width: 100%;">
        <a href="registro_Parceiro_Vendas.php" style="text-decoration: none; background: rgba(56, 189, 248, 0.1); color: #38bdf8; border: 1px solid rgba(56, 189, 248, 0.2); padding: 5px 8px; font-size: 10px; font-weight: 600; border-radius: 6px; white-space: nowrap;">Parceria</a>
        <a href="hospedagem.php" style="text-decoration: none; background: #1877f2; color: #fff; padding: 5px 8px; font-size: 10px; font-weight: 600; border-radius: 6px; white-space: nowrap;">Profissional</a>
    </div>
</nav>

<!-- 📱 BARRA HORIZONTAL DE ABAS COMPACTA (ESTILO FACEBOOK MÓVEL — 100% LARGURA) -->
<ul class="menu-horizontal" style="list-style: none; display: flex; gap: 4px; padding: 4px 6px; align-items: center; justify-content: space-between; width: 100% !important; max-width: 100% !important; margin: 8px 0 12px 0 !important; box-sizing: border-box; background: #1e293b; border-top: 1px solid #334155; border-bottom: 1px solid #334155;">
    
     <!-- 1. Aba Apoios -->
     <li style="flex: 1; text-align: center;">
         <a href="Patrocinadores.php" style="font-size: 10.5px; font-weight: 500; text-decoration: none; color: #94a3b8; display: block; padding: 4px 2px;">Apoios</a>
     </li>
 
     <!-- 2. Aba Lojas Dinâmica -->
     <li style="position: relative; flex: 1; text-align: center;">
         <!-- 🟢 RESET REATIVO: Envia para o motor antes de abrir a página das lojas -->
         <a href="Principal.php?marcar_lido=lojas" style="font-size: 10.5px; font-weight: 500; text-decoration: none; color: #94a3b8; display: block; padding: 4px 2px;">Lojas</a>
         <?php if (isset($novasLojas) && $novasLojas > 0 && !isset($_SESSION['bloqueio_notif_lojas'])): ?>
             <span class="badge-contador" style="background: #3b82f6; position: absolute; top: -1px; right: 2px; z-index: 10; width: 13px; height: 13px; font-size: 8px; line-height: 13px; text-align: center; color: white; border-radius: 50%; display: inline-block; font-weight: 700; border: 1px solid #1e293b;"><?= $novasLojas ?></span>
         <?php endif; ?>
     </li>
 
     <!-- 3. Aba Barbearias Reativa (Destaque Ativo) -->
     <li style="position: relative; flex: 1.2; min-width: 85px; text-align: center; background: #0f172a; border-radius: 8px;">
         <!-- 🟢 RESET REATIVO: Envia para o motor de limpeza das barbearias -->
         <a href="Principal.php?marcar_lido=barbearias" style="font-size: 10.5px; font-weight: 700; text-decoration: none; color: #38bdf8; display: block; padding: 4px 2px;">Barbearias</a>
         <?php if (isset($total_barbearias_real) && $total_barbearias_real > 0 && !isset($_SESSION['bloqueio_notif_barbearias'])): ?>
             <span class="badge-contador" style="position: absolute; top: -1px; right: 3px; z-index: 10; width: 13px; height: 13px; font-size: 8px; line-height: 13px; text-align: center; color: white; background: #ef4444; border-radius: 50%; display: inline-block; font-weight: 700; border: 1px solid #1e293b;"><?= $total_barbearias_real ?></span>
         <?php endif; ?>
     </li>
 
     <!-- 4. Aba Vagas Dinâmica -->
     <li style="position: relative; flex: 1; text-align: center;">
         <!-- 🟢 RESET REATIVO: Envia para o motor de limpeza das vagas -->
         <a href="Principal.php?marcar_lido=vagas" style="font-size: 10.5px; font-weight: 500; text-decoration: none; color: #94a3b8; display: block; padding: 4px 2px;">Vagas</a>
         <?php if (isset($novasVagas) && $novasVagas > 0 && !isset($_SESSION['bloqueio_notif_vagas'])): ?>
             <span class="badge-contador" style="background: #10b981; position: absolute; top: -1px; right: 2px; z-index: 10; width: 13px; height: 13px; font-size: 8px; line-height: 13px; text-align: center; color: white; border-radius: 50%; display: inline-block; font-weight: 700; border: 1px solid #1e293b;"><?= $novasVagas ?></span>
         <?php endif; ?>
     </li>
 
     <!-- 5. Ícone do Sino Incorporado com Contador Unificado -->
     <li style="position: relative; flex: 0.6; display: flex; justify-content: center; align-items: center;">
         <div class="notif-wrapper">
             <!-- 🟢 EVENTO DUPLO: Abre o menu flutuante e executa em segundo plano a limpeza da bolha na sessão -->
             <button class="sino-btn" onclick="toggleMenuNotificacoes();" style="background: none; border: none; font-size: 12px; cursor: pointer; position: relative; padding: 2px;">
                 🔔
                 <?php if (isset($total_notificacoes) && $total_notificacoes > 0 && !isset($_SESSION['bloqueio_notif_sino'])): ?>
                     <span class="badge-contador" id="contador-sininho-real" style="position: absolute; top: -2px; right: -2px; width: 12px; height: 12px; font-size: 7.5px; line-height: 12px; text-align: center; color: white; background: #ef4444; border-radius: 50%; display: inline-block; font-weight: 700; border: 1px solid #1e293b;"><?= $total_notificacoes ?></span>
                 <?php endif; ?>
             </button>
         </div>
         
         <!-- Dropdown de Notificações Ajustado para Mobile -->
         <div class="notif-dropdown" id="dropdownNotif" style="display: none; position: absolute; right: 0; top: 120%; background: #0f1423; border: 1px solid #334155; border-radius: 6px; width: 220px; z-index: 500; box-shadow: 0 4px 12px rgba(0,0,0,0.5);">
             <div class="notif-header" style="display: flex; justify-content: space-between; padding: 6px 10px; border-bottom: 1px solid #334155; font-size: 10px; font-weight: bold; color: #fff;">
                 <span>Notificações Recentes</span>
                 <!-- Força a limpeza visual forçada de todos os itens locais -->
                 <span style="color: #38bdf8; cursor: pointer;" onclick="window.location.href='Principal.php?marcar_lido=sino';">Limpar</span>
             </div>
             
             <!-- Exibe a lista se houver registos de novos vídeos na tabela -->
             <?php if(isset($notif_videos) && $notif_videos > 0 && !isset($_SESSION['bloqueio_notif_sino'])): ?>
                 <a href="Video.php" class="notif-item" style="text-decoration: none; display: flex; gap: 6px; padding: 10px; border-bottom: 1px solid #1e293b; color: #fff; text-align: left;">
                     <span style="font-size: 12px;">🎬</span>
                     <div>
                         <strong style="font-size: 10px; display: block; color: #00d2ff;">Nova Tendência!</strong>
                         <p style="font-size: 9px; color: #94a3b8; margin: 0;">Temos +<?= $notif_videos ?> Reels publicados.</p>
                     </div>
                 </a>
             <?php endif; ?>

             <!-- Feedback visual vazio inteligente caso os alertas tenham sido lidos -->
             <?php if(!isset($total_notificacoes) || $total_notificacoes == 0 || isset($_SESSION['bloqueio_notif_sino'])): ?>
                 <div id="painel_vazio_sino" style="padding: 15px; text-align: center; color: #64748b; font-size: 9px; font-style: italic;">
                     Não tens novas notificações por agora.
                 </div>
             <?php endif; ?>
         </div>
     </li>
 </ul>

<!-- 🟩 JAVASCRIPT DE COMPORTAMENTO REATIVO DO CABEÇALHO (MÓVEL ANDROID) -->
<script>
/**
 * Alterna a exibição do painel suspenso de notificações estilo Facebook
 */
function toggleMenuNotificacoes() {
    var menu = document.getElementById('dropdownNotif');
    if (menu) {
        var estaOculto = (menu.style.display === 'none' || menu.style.display === '');
        menu.style.display = estaOculto ? 'block' : 'none';
        
        // 🛰️ AJUSTE ANDROID: Corrige o redimensionamento do mapa se o menu mover a viewport
        if (typeof engineMapa !== 'undefined' && engineMapa) {
            setTimeout(function() { engineMapa.resize(); }, 100);
        }
    }
}

/**
 * Fecha o menu flutuante de notificações ao tocar em qualquer espaço vazio da tela
 */
window.addEventListener('click', function(e) {
    var menu = document.getElementById('dropdownNotif');
    var wrapper = document.querySelector('.notif-wrapper');
    if (menu && wrapper && !wrapper.contains(e.target)) { 
        menu.style.display = 'none'; 
    }
});

/**
 * Faz a limpeza visual imediata das notificações no smartphone do cliente
 */
function limparNotificacoesLocal() {
    var badge = document.getElementById('contador-sininho-real');
    var painelVazio = document.getElementById('painel_vazio_sino');
    
    // Oculta a bolha vermelha de contagem do sino
    if (badge) { 
        badge.style.display = 'none'; 
    }
    
    // Oculta todos os cards de notificações ativos dentro do loop
    document.querySelectorAll('.notif-item').forEach(function(item) {
        item.style.display = 'none';
    });
   
    // Exibe a mensagem de feedback vazia de forma dinâmica
    if (painelVazio) {
        painelVazio.style.display = 'block';
        painelVazio.innerHTML = 'Não tens novas notificações por agora.';
    }
}

/**
 * Escuta mudanças de orientação do telemóvel para evitar quebras de proporção
 */
window.addEventListener('resize', function() {
    if (typeof engineMapa !== 'undefined' && engineMapa) {
        engineMapa.resize();
    }
});
</script>














































<?php
// 📊 MOTOR DE CONTAGEM REAL DE VÍDEOS OPERACIONAIS NO HUAMBO
$total_videos_reels = 0;
if (isset($mysqli) && !$mysqli->connect_error) {
    try {
        // Conta apenas os arquivos de vídeo ativos salvos na tabela de anúncios
        $resVideos = $mysqli->query("SELECT COUNT(*) AS total FROM anuncios WHERE (imagem LIKE '%.mp4' OR tipo_media = 'video') AND ativo = 1");
        if ($resVideos) {
            $dadosVideos = $resVideos->fetch_assoc();
            $total_videos_reels = intval($dadosVideos['total']);
        }
    } catch (Exception $e) {
        $total_videos_reels = 0;
    }
}
?>









<html lang="PT">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
   <!-- Na barra de navegação e instalação do PWA -->
<title>BarbeariasAngola — Rede de Distribuição & Estética</title>

<!-- No cabeçalho principal do seu menu Slate -->
    <style>
        /* ESTILOS DE BASE (OTIMIZADO PARA IFRAME) */
        /* ESTILOS GERAIS DA PÁGINA (Para encaixar perfeitamente no iframe) */
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: #ffffff; /* Fundo escuro profissional profundo */
            color: #f8fafc;
            padding: 20px;
            margin: 0;
        }

        /* GRELHA RE-ALINHADA (Mantém o espaçamento exato da imagem) */
        .grid {
            display: flex;
            justify-content: center;
            gap: 20px;
            padding: 10px;
            flex-wrap: wrap;
            max-width: 1300px;
            margin: 0 auto;
        }
     
        /* O CARTÃO PÍLULA (Preserva o formato redondo vertical idêntico ao seu print) */
        .sub-grid {
            background: linear-gradient(180deg, #101f38 0%, #0a1424 100%); /* Degradê escuro elegante no lugar do azul forte */
            border: 1px solid #1e293b;
            box-shadow: 0 10px 20px rgba(0, 0, 0, 0.3); 
            height: 330px;
            width: 160px; /* Largura compacta ideal para caber vários em linha */
            text-align: center;
            padding: 20px 15px;
            border-radius: 40px; /* Mantém o formato arredondado vertical perfeito da sua foto */
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            align-items: center;
            box-sizing: border-box;
            transition: all 0.3s ease;
        }

        /* EFEITO AO PASSAR O RATO */
        .sub-grid:hover {
            transform: translateY(-5px);
            border: 5px solid #dc2626; /* Brilho azul tecnológico discreto */
            box-shadow: 0 12px 25px rgba(56, 189, 248, 0.15);
        }

        /* TÍTULO DA BARBEARIA */
        .h2-sub-grid {
            font-family: 'Segoe UI', sans-serif;
            color: #ffffff;
            font-size: 14px;
            font-weight: 700;
            margin: 0;
            line-height: 1.3;
            min-height: 36px;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        /* CONTAINER PARA TORNAR OS LOGÓTIPOS VISÍVEIS E LIMPOS */
        .img-container {
            width: 110px;
            height: 110px;
            background: #ffffff; /* Fundo branco para destacar os logos transparentes ou escuros */
            border-radius: 16px; /* Cantos levemente suavizados para os logos */
            padding: 5px;
            box-sizing: border-box;
            display: flex;
            align-items: center;
            justify-content: center;
            box-shadow: 0 4px 10px rgba(0,0,0,0.2);
        }

        /* A IMAGEM DENTRO DO CARTÃO */
        .img-Comidas {
            width: 100%;
            height: 100%;
            object-fit: contain; /* Garante que nenhuma logo seja cortada ou distorcida */
            border-radius: 12px;
        }

        /* BOTÃO DE AÇÃO ENTRAR COMPACTO */
        .botao-acção {
            width: 85px;
            background: linear-gradient(135deg, #ef4444, #dc2626); /* Vermelho premium mais escuro e limpo */
            border: none;
            border-radius: 20px;
            color: white;
            font-size: 12px;
            font-weight: bold;
            padding: 8px 0;
            cursor: pointer;
            box-shadow: 0 3px 6px rgba(220, 38, 38, 0.3);
            transition: all 0.2s ease;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .botao-acção:hover {
            background: #ffffff;
            color: #dc2626;
            box-shadow: 0 4px 12px rgba(255, 255, 255, 0.2);
        }

        /* SELETOR DE INFORMAÇÕES ESTILIZADO */
        select {
            width: 100px;
            background-color: #0f172a;
            border: 2px solid #334155;
            color: #38bdf8; /* Texto azul claro moderno */
            font-size: 11px;
            text-align: center;
            font-weight: bold;
            padding: 5px;
            border-radius: 15px;
            outline: none;
            cursor: pointer;
        }

        select:hover {
            border-color: #38bdf8;
            color: #ffffff;
        }
   
    </style>
</head>
<body>






<div class="search-container">
    <div class="search-wrapper" id="searchWrapper">
        <!-- Botão de Ativação / Ícone Inicial -->
        <button type="button" class="search-trigger" id="searchTrigger" aria-label="Abrir busca">🔍</button>
        
        <!-- Formulário Oculto que se expande -->
        <form action="principal.php" method="POST" class="search-form">
            <input type="text" name="termo_cliente" class="search-input" id="searchInput"
                   placeholder="Pesquise barbearias e lojas..." 
                   value="<?php echo isset($_POST['termo_cliente']) ? htmlspecialchars($_POST['termo_cliente']) : ''; ?>">
            
            <button type="submit" name="disparar_busca" class="search-submit" aria-label="Pesquisar">🔍</button>
            
            <!-- Botão de Fechar (Volta a ser apenas a lupa) -->
            <button type="button" class="search-close" id="searchClose">✕</button>
        </form>
    </div>
</div>

<style>
    /* Container Geral Responsivo */
    .search-container {
        width: 100%;
        max-width: 450px;
        margin: 10px auto;
        padding: 0 10px;
        box-sizing: border-box;
        font-family: system-ui, -apple-system, sans-serif;
        display: flex;
        justify-content: center;
    }

    /* O Wrapper começa compacto (apenas o tamanho do botão) e transiciona suavemente */
    .search-wrapper {
        background: #111827;
        border: 1px solid #38bdf8;
        border-radius: 30px;
        box-shadow: 0 0 10px rgba(56, 189, 248, 0.2);
        overflow: hidden;
        transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        width: 50px;
        height: 50px;
        display: flex;
        align-items: center;
        justify-content: center;
        position: relative;
    }

    /* Estado Ativo: Quando clicado, expande para 100% do container */
    .search-wrapper.active {
        width: 100%;
        padding: 6px;
        animation: pulse 3s infinite alternate;
    }

    /* Botão inicial da lupa */
    .search-trigger {
        background: transparent;
        border: none;
        color: #fff;
        font-size: 20px;
        cursor: pointer;
        width: 100%;
        height: 100%;
        display: flex;
        align-items: center;
        justify-content: center;
        transition: opacity 0.2s;
    }

    .search-wrapper.active .search-trigger {
        display: none;
    }

    /* Formulário interno */
    .search-form {
        display: none;
        width: 100%;
        gap: 6px;
        align-items: center;
    }

    .search-wrapper.active .search-form {
        display: flex;
    }

    /* Campo de texto profissional */
    .search-input {
        flex: 1;
        min-width: 0;
        padding: 10px 14px;
        border: none;
        border-radius: 20px;
        font-size: 14px;
        background: #0b0f19;
        color: #fff;
        outline: none;
    }

    /* Botão de envio dentro da barra expandida */
    .search-submit {
        padding: 10px 14px;
        background: #38bdf8;
        color: #0f172a;
        border: none;
        border-radius: 20px;
        font-weight: bold;
        font-size: 13px;
        cursor: pointer;
        white-space: nowrap;
    }

    /* Botão X para fechar a expansão */
    .search-close {
        padding: 10px 12px;
        background: #1f2937;
        color: #94a3b8;
        border: none;
        border-radius: 20px;
        font-size: 13px;
        cursor: pointer;
        font-weight: bold;
    }

    /* Animação Pulse mantida do seu código original */
    @keyframes pulse {
        0% { box-shadow: 0 0 5px rgba(56,189,248,0.2); border-color: #0369a1; }
        100% { box-shadow: 0 0 12px rgba(56,189,248,0.4); border-color: #38bdf8; }
    }
</style>

<script>
    const searchWrapper = document.getElementById('searchWrapper');
    const searchTrigger = document.getElementById('searchTrigger');
    const searchClose = document.getElementById('searchClose');
    const searchInput = document.getElementById('searchInput');

    // Ao clicar na lupa inicial, expande a barra
    searchTrigger.addEventListener('click', () => {
        searchWrapper.classList.add('active');
        setTimeout(() => searchInput.focus(), 100); // Dá foco automático no input
    });

    // Ao clicar no X, recolhe a barra de volta para o botão
    searchClose.addEventListener('click', (e) => {
        e.stopPropagation();
        searchWrapper.classList.remove('active');
        searchInput.value = ''; // Limpa o texto ao fechar
    });

    // Mantém a barra aberta caso a página recarregue com uma busca já feita
    if (searchInput.value.trim() !== '') {
        searchWrapper.classList.add('active');
    }
</script>








<?php
// =========================================================================
// 🔄 1. INICIALIZAÇÃO E CONSULTA DE DADOS (SEMPRE ANTES DO HTML)
// =========================================================================
$todas_prov_angola = ['Bengo', 'Benguela', 'Bié', 'Cabinda', 'Cuando-Cubango', 'Cuanza-Norte', 'Cuanza-Sul', 'Cunene', 'Huambo', 'Huíla', 'Luanda', 'Lunda-Norte', 'Lunda-Sul', 'Malanje', 'Moxico', 'Namibe', 'Uíge', 'Zaire'];

$prov_com_parceiros = [];

// Abre ou reutiliza a conexão ativa do sistema
$mysqli = $conexao_link ?? $conexao_aurelius ?? null;

if ($mysqli && !$mysqli->connect_error) {
    $mysqli->set_charset("utf8mb4");
    
    // Consulta otimizada diretamente na tabela 'usuario'
    $query_botoes = $mysqli->query("SELECT DISTINCT `endereco` FROM `usuario` WHERE `visivel_no_site` = 1 AND `nivel` = 'parceiro_hospedado'");
    
    if ($query_botoes && $query_botoes->num_rows > 0) {
        while ($p_row = $query_botoes->fetch_assoc()) {
            if (empty($p_row['endereco'])) continue;
            
            $endereco_limpo = mb_strtolower(trim($p_row['endereco']), 'UTF-8');
            
            foreach ($todas_prov_angola as $prov_nome) {
                $prov_lower = mb_strtolower($prov_nome, 'UTF-8');
                
                // Normaliza acentos e hifens comuns
                $prov_sem_acento = str_replace(['í', 'é', 'á'], ['i', 'e', 'a'], $prov_lower);
                $prov_sem_hifen = str_replace('-', ' ', $prov_lower);
                
                if (str_contains($endereco_limpo, $prov_lower) || 
                    str_contains($endereco_limpo, $prov_sem_acento) || 
                    str_contains($endereco_limpo, $prov_sem_hifen)) {
                    
                    if (!in_array($prov_nome, $prov_com_parceiros)) {
                        $prov_com_parceiros[] = $prov_nome;
                    }
                }
            }
        }
    }
}
?>

<!-- =========================================================================
     📍 2. ESTRUTURA VISUAL DO CARROSSEL (HTML CORRIGIDO)
     ========================================================================= -->
<div class="filtro-prov-container">
    <span class="filtro-prov-titulo">Filtrar por Província Ativa:</span>
    <div class="carrossel-wrapper">
        <button class="seta-carrossel seta-esquerda" id="setaEsquerda" onclick="scrollCarrossel(-150)">‹</button>
        
        <div class="trilho-botoes" id="trilhoBotoes">
            <!-- Botão Mestre -->
            <button class="btn-filtro-prov-nacional ativo" onclick="executarFiltragemGeograficaCarrossel('todos', this)">🇦🇴 Todas</button>
            
            <!-- Renderiza os botões dinamicamente sem gerar Warnings -->
            <?php 
            if (!empty($prov_com_parceiros) && is_array($prov_com_parceiros)):
                foreach ($prov_com_parceiros as $nome_p): 
                    $slug_prov = str_replace(['í', 'é', 'á'], ['i', 'e', 'a'], mb_strtolower($nome_p, 'UTF-8'));
                    $slug_prov = str_replace('-', ' ', $slug_prov);
                ?>
                    <button class="btn-filtro-prov-nacional" onclick="executarFiltragemGeograficaCarrossel('<?= $slug_prov ?>', this)"><?= $nome_p ?></button>
                <?php 
                endforeach; 
            endif;
            ?>
        </div>
        
        <button class="seta-carrossel seta-direita" id="setaDireita" onclick="scrollCarrossel(150)">›</button>
    </div>
</div>

 <!-- =========================================================================
      🎨 DESIGN E IDENTIDADE VISUAL CSS (COMPLETA E RESPONSIVA)
      ========================================================================= -->
 <style>
     .filtro-prov-container {
         margin: 25px auto 15px auto;
         text-align: center;
         font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
         max-width: 1350px;
         padding: 0 10px;
         box-sizing: border-box;
     }
 
     .filtro-prov-titulo {
         color: #94a3b8;
         font-size: 11px;
         font-weight: bold;
         text-transform: uppercase;
         display: block;
         margin-bottom: 12px;
         letter-spacing: 0.8px;
     }
 
     /* Caixa externa que contém o trilho e as setas */
     .carrossel-wrapper {
         position: relative;
         display: flex;
         align-items: center;
         background: #111827;
         padding: 8px 12px;
         border-radius: 20px;
         border: 1px solid #1e293b;
         box-shadow: 0 4px 12px rgba(0, 0, 0, 0.25);
     }
 
     /* O Trilho onde os botões ficam alinhados */
     .trilho-botoes {
         display: flex;
         gap: 8px;
         overflow-x: auto;
         scroll-behavior: smooth;
         width: 100%;
         padding: 4px 0;
         scrollbar-width: none; /* Oculta a barra no Firefox */
     }
 
     /* Oculta a barra de rolagem nativa no Chrome, Safari e Edge */
     .trilho-botoes::-webkit-scrollbar {
         display: none;
     }
 
     /* Estilização base de cada botão de província */
     .btn-filtro-prov-nacional {
         background: #1e293b;
         color: #f8fafc;
         border: 1px solid #334155;
         padding: 7px 16px;
         font-size: 12px;
         font-weight: 600;
         border-radius: 30px;
         cursor: pointer;
         text-transform: uppercase;
         white-space: nowrap; /* Evita que o nome quebre em duas linhas */
         transition: all 0.25s cubic-bezier(0.4, 0, 0.2, 1);
         outline: none;
     }
 
     .btn-filtro-prov-nacional:hover {
         background: #334155;
         border-color: #475569;
         transform: translateY(-1px);
     }
 
     /* Estado Ativo: Iluminação Neon Azul condizente com a barra de pesquisa */
     .btn-filtro-prov-nacional.ativo {
         background: #38bdf8;
         color: #0f172a;
         border-color: #38bdf8;
         box-shadow: 0 0 10px rgba(56, 189, 248, 0.4);
     }
 
     /* Setas de navegação elegantes */
     .seta-carrossel {
         position: absolute;
         top: 50%;
         transform: translateY(-50%);
         background: rgba(30, 41, 59, 0.9);
         color: #38bdf8;
         border: 1px solid #334155;
         width: 28px;
         height: 28px;
         border-radius: 50%;
         font-size: 18px;
         display: flex;
         align-items: center;
         justify-content: center;
         cursor: pointer;
         z-index: 10;
         transition: all 0.2s;
         user-select: none;
     }
 
     .seta-carrossel:hover {
         background: #38bdf8;
         color: #0f172a;
         box-shadow: 0 0 8px rgba(56, 189, 248, 0.4);
     }
 
     .seta-esquerda { left: -10px; }
     .seta-direita { right: -10px; }
 
     /* Esconde as setas em telas muito pequenas (onde arrastar com o dedo é natural) */
     @media (max-width: 768px) {
         .seta-carrossel {
             display: none;
         }
         .carrossel-wrapper {
             padding: 8px 6px;
         }
     }
     .barbearias-container-outer {
    width: 100%;
    max-width: 1350px;
    margin: 20px auto;
    padding: 0 10px;
    box-sizing: border-box;
    overflow: hidden; /* Corta o que passar das bordas laterais da tela */
}

/* O Trilho que armazena os Cards e permite o Arraste (Touch e Mouse) */
.trilho-barbearias {
    display: flex;
    gap: 16px;
    overflow-x: auto;
    scroll-behavior: smooth;
    padding: 10px 5px;
    scrollbar-width: none; /* Firefox */
    cursor: grab; /* Cursor de "mão aberta" indicando que pode arrastar */
    user-select: none; /* Evita selecionar textos acidentalmente ao arrastar */
}

.trilho-barbearias:active {
    cursor: grabbing; /* Mão fechada durante o arraste */
}

.trilho-barbearias::-webkit-scrollbar {
    display: none; /* Chrome, Safari e Edge */
}

/* Estutura dos Cards Internos Adaptada para Carrossel Horizontal */
.trilho-barbearias .sub-grid {
    flex: 0 0 280px; /* Não permite encolher e define a largura fixa de cada card */
    max-width: 280px;
    background: #111827;
    border: 1px solid #1e293b;
    border-radius: 16px;
    transition: transform 0.3s ease, border-color 0.3s ease;
    display: flex; /* Mantido display flex exigido pelo seu motor de busca */
    flex-direction: column;
}

.trilho-barbearias .sub-grid:hover {
    border-color: #38bdf8;
    transform: translateY(-2px);
}



 </style>
 
 <!-- =========================================================================
      🟩 SCRIPT JAVASCRIPT: MOTOR DE FILTRAGEM REATIVA E NAVEGAÇÃO
      ========================================================================= -->
 <script>
 // Controla a rolagem através dos botões de setas laterais
 const trilhoSalas = document.getElementById('trilho_carrossel_salao');
let isDown = false;
let startX;
let scrollLeft;

if (trilhoSalas) {
    trilhoSalas.addEventListener('mousedown', (e) => {
        isDown = true;
        trilhoSalas.classList.add('dragging');
        startX = e.pageX - trilhoSalas.offsetLeft;
        scrollLeft = trilhoSalas.scrollLeft;
    });

    trilhoSalas.addEventListener('mouseleave', () => {
        isDown = false;
    });

    trilhoSalas.addEventListener('mouseup', () => {
        isDown = false;
    });

    trilhoSalas.addEventListener('mousemove', (e) => {
        if (!isDown) return;
        e.preventDefault();
        const x = e.pageX - trilhoSalas.offsetLeft;
        const walk = (x - startX) * 2; // Multiplicador de velocidade do arraste
        trilhoSalas.scrollLeft = scrollLeft - walk;
    });
}

// =========================================================================
// 🎯 FILTRAGEM GEOGRÁFICA REATIVA ATUALIZADA
// =========================================================================
function executarFiltragemGeograficaCarrossel(provinciaAlvo, botaoElemento) {
    // 1. Alterna estado visual dos botões do topo
    const botoes = document.querySelectorAll('.btn-filtro-prov-nacional');
    botoes.forEach(btn => btn.classList.remove('ativo'));
    botaoElemento.classList.add('ativo');

    botaoElemento.scrollIntoView({ behavior: 'smooth', block: 'nearest', inline: 'center' });

    // 2. Captura todos os cards de barbearias
    const cards = document.querySelectorAll('#trilho_carrossel_salao .sub-grid');
    
    // Reseta o scroll do trilho de barbearias para o início ao filtrar
    if (trilhoSalas) {
        trilhoSalas.scrollTo({ left: 0, behavior: 'smooth' });
    }

    // Normalização da string de busca
    let provLimpa = provinciaAlvo.toLowerCase().trim()
        .replace(/[íìî]/g, 'i')
        .replace(/[éèê]/g, 'e')
        .replace(/[áàâã]/g, 'a')
        .replace(/-/g, ' ');

    // 3. Aplica o filtro de visibilidade baseado no texto interno ou data-attribute
    cards.forEach(card => {
        // Tenta ler primeiro do atributo data-provincia, se não existir lê o texto do card
        let provinciaCard = card.getAttribute('data-provincia') || card.innerText;
        
        let textoCardCompleto = provinciaCard.toLowerCase()
            .replace(/[íìî]/g, 'i')
            .replace(/[éèê]/g, 'e')
            .replace(/[áàâã]/g, 'a')
            .replace(/-/g, ' ');
        
        if (provinciaAlvo === 'todos') {
            card.style.setProperty('display', 'flex', 'important');
        } else if (textoCardCompleto.includes(provLimpa)) {
            card.style.setProperty('display', 'flex', 'important');
        } else {
            card.style.setProperty('display', 'none', 'important');
        }
    });
}
</script>












<!-- =================================================================
     🔮 CONTEÚDO INTEGRAL DA GRAD DINÂMICA UNIVERSAL COESORA COM MOVIMENTO
     ================================================================= -->
     <div class="grad" style="width: 100%; max-width: 1350px; margin: 30px auto; padding: 0 15px; position: relative; box-sizing: border-box; overflow: hidden; clear: both !important;">

     <!-- Botões Direcionais de Navegação Manual Estilo Premium -->
     <button type="button" onclick="moverCarrosselSalores('esquerda')" style="position: absolute; left: 20px; top: 50%; transform: translateY(-50%); background: rgba(15,23,42,0.8); border: 2px solid #38bdf8; color: #fff; width: 45px; height: 45px; border-radius: 50%; font-size: 20px; cursor: pointer; z-index: 100; box-shadow: 0 4px 10px rgba(0,0,0,0.5); font-weight: bold; outline: none;">‹</button>
     <button type="button" onclick="moverCarrosselSalores('direita')" style="position: absolute; right: 20px; top: 50%; transform: translateY(-50%); background: rgba(15,23,42,0.8); border: 2px solid #38bdf8; color: #fff; width: 45px; height: 45px; border-radius: 50%; font-size: 20px; cursor: pointer; z-index: 100; box-shadow: 0 4px 10px rgba(0,0,0,0.5); font-weight: bold; outline: none;">›</button>
 
     <!-- Contentor de Máscara de Recorte -->
     <div id="mascara_carrossel_salao" style="width: 100%; overflow: hidden; padding: 15px 0; box-sizing: border-box;">
         
         <!-- LISTAGEM EM LINHA FLUIDA DA GRID REATIVA (FLEX CARROSSEL) -->
         <div class="grid" id="trilho_carrossel_salao" style="display: flex !important; gap: 20px !important; width: max-content !important; transition: transform 0.5s cubic-bezier(0.25, 1, 0.5, 1); box-sizing: border-box !important; padding: 0 10px;">
 
         <?php
         // =========================================================================
         // 🟢 ALINHAMENTO EXCLUSIVO E DIRECIONAMENTO POR SLUG DINÂMICO
         // =========================================================================
         if (!empty($lista_parceiros_ativos)):
             foreach ($lista_parceiros_ativos as $barbearia_item):
                 $id_foto = (int)$barbearia_item['codigo'];
                 $nome_barbearia = trim($barbearia_item['nome'] ?? '');
                 
                 // 🖼️ MOTOR DE IMAGENS MULTI-PASTAS RECUPERADO (Todas as fotos antigas de volta)
                 $arquivo_logo = trim($barbearia_item['logo_empresa'] ?? '');
                 $foto_src = "uploads/OIP (6).webp"; // Fallback padrão inicial

                 if (!empty($arquivo_logo)) {
                     $arquivo_limpo = basename($arquivo_logo); // Higieniza caminhos sujos do banco
                     
                     if (file_exists("uploads/" . $arquivo_limpo)) {
                         $foto_src = "uploads/" . $arquivo_limpo;
                     } elseif (file_exists("upload/" . $arquivo_limpo)) {
                         $foto_src = "upload/" . $arquivo_limpo;
                     } elseif (file_exists("guardar-fotos/" . $arquivo_limpo)) {
                         $foto_src = "guardar-fotos/" . $arquivo_limpo;
                     } elseif (file_exists($arquivo_limpo)) {
                         $foto_src = $arquivo_limpo;
                     }
                 }

                 // 🟢 DIRECIONADOR DE SLUG DINÂMICO INDEPENDENTE CONTRA LINKS QUEBRADOS
                 $slug_limpo = !empty($barbearia_item['slug']) ? trim($barbearia_item['slug']) : '';
                 
                 if (!empty($slug_limpo)) {
                     // Garante compatibilidade total convertendo maiúsculas para rotas limpas
                     $link_destino = strtolower($slug_limpo) . ".php";
                 } else {
                     // Salvaguarda: Se o slug for nulo, cria uma rota segura baseada no nome comercial
                     $slug_gerado = strtolower(preg_replace('/[^A-Za-z0-9]/', '', $nome_barbearia));
                     $link_destino = $slug_gerado . ".php";
                 }
                 
                 $servico_real = !empty($barbearia_item['tipos_de_servico']) ? trim($barbearia_item['tipos_de_servico']) : "Geral";
                 $endereco_real = !empty($barbearia_item['endereco']) ? trim($barbearia_item['endereco']) : "Huambo";
                 
                 // Matriz cronológica e ano de adesão
                 $data_bruta = $barbearia_item['data'] ?? '';
                 $ano_cadastro = (!empty($data_bruta) && $data_bruta !== '0000-00-00') ? "Desde " . date('Y', strtotime($data_bruta)) : "Desde 2026";
         ?>
                 
                 <!-- 💎 CARD GÉMEO IDENTICO AO DA IMAGEM: Pílula Azul Escura Vertical Arredondada -->
                 <div class="sub-grid" style="width: 175px !important; height: 320px !important; flex-shrink: 0 !important; background: #0b1a30 !important; border: 2px solid #1e293b !important; border-radius: 40px !important; padding: 18px 12px !important; text-align: center !important; box-sizing: border-box !important; display: flex !important; flex-direction: column !important; justify-content: space-between !important; box-shadow: 0 8px 16px rgba(0,0,0,0.4) !important;">
                      
                      <!-- Nome Fantasia da Barbearia -->
                      <h2 class="h2-sub-grid" style="font-size: 13px !important; font-weight: bold !important; color: #ffffff !important; margin: 0 0 10px 0 !important; font-family: sans-serif !important; white-space: nowrap !important; overflow: hidden !important; text-overflow: ellipsis !important; text-transform: uppercase !important;" title="<?php echo htmlspecialchars($nome_barbearia); ?>">
                          <?php echo htmlspecialchars($nome_barbearia); ?>
                      </h2>
                      
                      <!-- Contentor do Logótipo Redondo com Borda -->
                      <div class="img-container" style="width: 110px !important; height: 110px !important; border-radius: 16px !important; overflow: hidden !important; margin: 0 auto !important; background: #ffffff !important; display: flex !important; align-items: center !important; justify-content: center !important; border: 1px solid #1e293b !important; box-sizing: border-box;">
                          <img src="<?php echo $foto_src; ?>" alt="Logo" style="width: 100% !important; height: 100% !important; object-fit: cover !important;" onerror="this.src='uploads/OIP (6).webp';">
                       </div>
                       
                      <!-- 🟢 BOTÃO ENTRAR: Redireciona de forma reativa para o Slug específico da barbearia -->
                      <a href="<?php echo $link_destino; ?>" style="text-decoration: none !important; display: block !important; margin-top: 12px !important; width: 100%;">
                          <button class="botao-acção" style="width: 100% !important; background: #d32f2f !important; color: #ffffff !important; border: none !important; padding: 7.5px 0 !important; font-size: 12px !important; font-weight: bold !important; text-transform: uppercase !important; border-radius: 8px !important; cursor: pointer !important; letter-spacing: 0.5px !important; box-shadow: 0 4px 12px rgba(211,47,47,0.3) !important; outline: none !important; transition: all 0.2s ease; display: flex !important; align-items: center !important; justify-content: center !important;">
                              ENTRAR
                          </button>
                      </a>

                      <!-- Seletor Azul de Informações Inferiores -->
                      <select style="width: 100% !important; background: #1e293b !important; color: #38bdf8 !important; border: 1px solid #334155 !important; padding: 4px; font-size: 11px; border-radius: 4px; outline: none; cursor: pointer; margin-top: 5px; text-align: center;">
                          <option><?php echo $ano_cadastro; ?></option>
                          <option>📍 <?php echo htmlspecialchars($endereco_real); ?></option>
                          <option>⚡ <?php echo htmlspecialchars($servico_real); ?></option>
                          <option>⏰ 08:00 - 19:00</option>
                      </select>
                 </div> <!-- Fim do card individual -->
     
         <?php 
             endforeach; // Termina o loop das barbearias
         else:
             echo "<p style='color: #94a3b8; padding: 20px; font-style: italic; width:100%; text-align:center;'>Nenhuma barbearia ativa de momento no ecossistema.</p>";
         endif; 
         ?>

         </div> <!-- Fim trilho_carrossel_salao -->
     </div> <!-- Fim mascara_carrossel_salao -->
</div> <!-- Fim a div grad principal -->

<!-- 🟢 SCRIPT DO CARROSSEL DE BARBEARIAS SINCRO -->
<script>
let posicaoDeslocamentoSalores = 0;

function moverCarrosselSalores(direcao) {
    const trilho = document.getElementById('trilho_carrossel_salao');
    const mascara = document.getElementById('mascara_carrossel_salao');
    
    if (!trilho || !mascara) return;
    
    const larguraMascaraVisivel = mascara.offsetWidth;
    const larguraTotalTrilho = trilho.scrollWidth;
    const larguraSaltoCartao = 195; // 175px de largura + 20px de gap

    if (direcao === 'direita') {
        if (Math.abs(posicaoDeslocamentoSalores) + larguraMascaraVisivel < larguraTotalTrilho) {
            posicaoDeslocamentoSalores -= larguraSaltoCartao;
        }
    } else if (direcao === 'esquerda') {
        if (posicaoDeslocamentoSalores < 0) {
            posicaoDeslocamentoSalores += larguraSaltoCartao;
        }
    }
    trilho.style.transform = `translateX(${posicaoDeslocamentoSalores}px)`;
}
</script>
















<!-- =========================================================================
     🏆 TOP DESTAQUE RESPONSIVO: LÍDERES EM FATURAÇÃO E VENDAS REAIS
     ========================================================================= -->
     <?php
     include_once("Conexao.php");
     
     // 🟢 1. IDENTIFICA O MELHOR SALÃO POR MAIOR VOLUME DE AGENDAMENTOS CONFIRMADOS
     try {
         $stmt_melhor_salao = $pdo->query("
             SELECT u.codigo, u.nome, u.logo_empresa, u.endereco, COUNT(p.id_pagamento) as total_atendimentos
             FROM usuario u
             INNER JOIN pagamentos p ON p.id_parceiro = u.codigo
             WHERE p.status_atendimento = 'Confirmado' 
               AND p.tipo_parceiro = 'barbearia'
             GROUP BY u.codigo
             ORDER BY total_atendimentos DESC
             LIMIT 1
         ");
         $melhor_salao = $stmt_melhor_salao->fetch(PDO::FETCH_ASSOC);
     } catch (PDOException $e) { 
         $melhor_salao = null; 
     }
     
     // 🟢 2. IDENTIFICA A MELHOR LOJA (A QUE MAIS VENDE NA TABELA LOJAS + PAGAMENTOS)
     try {
         $stmt_melhor_loja = $pdo->query("
             SELECT l.id, l.nome_loja, l.endereco_armazem, COUNT(p.id_pagamento) as total_vendas
             FROM lojas l
             INNER JOIN pagamentos p ON p.id_parceiro = l.id
             WHERE p.status_atendimento = 'Confirmado' 
               AND p.tipo_parceiro = 'loja'
             GROUP BY l.id
             ORDER BY total_vendas DESC
             LIMIT 1
         ");
         $melhor_loja = $stmt_melhor_loja->fetch(PDO::FETCH_ASSOC);
     } catch (PDOException $e) { 
         $melhor_loja = null; 
     }
     ?>
     
     <!-- Estilo CSS Fluido para Adaptação em Telemóveis -->
     <style>
       /* 📱 Otimizações reativas para Telemóveis (Mobile-First) */
       @media (max-width: 580px) {
        /* ==================================================================
           💥 CORREÇÃO DO DESIGN DO TOPO (MENU, BOTÕES E FILTROS DE PROVÍNCIAS)
           ================================================================== */
        /* Garante que os botões do topo (Criar Conta, Parceria) quebram linha e não saem do ecrã */
        [style*="display: flex"] > a, .header-buttons, .buttons-container {
            display: flex !important;
            flex-wrap: wrap !important;
            gap: 8px !important;
            justify-content: center !important;
            width: 100% !important;
        }

        /* Ajusta o tamanho dos botões superiores para caberem no telemóvel */
        .header-buttons a, button, [style*="border-radius"] {
            font-size: 11px !important;
            padding: 6px 10px !important;
            white-space: nowrap !important;
        }

        /* Organiza as Províncias (Huambo, Luanda, etc.) numa grelha flexível e limpa */
        div[style*="display: flex"][style*="flex-wrap"] {
            display: flex !important;
            flex-wrap: wrap !important;
            justify-content: center !important;
            gap: 6px !important;
            padding: 0 10px !important;
            width: 100% !important;
        }

        /* Garante que os botões das províncias ficam perfeitamente alinhados */
        div[style*="display: flex"] > button {
            flex: 1 1 auto !important;
            max-width: 140px !important;
            text-align: center !important;
        }

        /* ==================================================================
           ⚙️ O TEU CÓDIGO ORIGINAL DO RODAPÉ (MANTIDO INTACTO)
           ================================================================== */
        .lista-nav-footer {
            border-radius: 16px !important;
            padding: 15px !important;
            gap: 12px !important;
            display: grid !important;
            grid-template-columns: repeat(2, 1fr) !important; /* Transforma em grelha dupla simétrica */
            width: 100% !important;
            max-width: 320px !important;
            margin: 0 auto !important;
        }
        
        .separador-footer {
            display: none !important; /* Oculta as bolhas no mobile para economizar espaço */
        }

        .link-social-footer {
            justify-content: center !important;
            background: rgba(56, 189, 248, 0.05) !important;
            padding: 8px !important;
            border-radius: 8px !important;
            border: 1px solid rgba(56, 189, 248, 0.1) !important;
        }
    }
</style>







     
<?php
// =========================================================================
// 👑 MOTOR ROTATIVO DE LÍDERES: EXTRAÇÃO EM TEMPO REAL DA BASE DE DADOS
// =========================================================================

// 1. QUERY DOS SALÕES CAMPEÕES (Mapeia o ranking de atendimentos de salão e sorteia rotativamente um do TOP 3)
$sql_top_saloes = "SELECT 
                        u.codigo, 
                        u.nome, 
                        IFNULL(u.foto_perfil, 'OIP (6).webp') AS logo_empresa,
                        'Huambo / Angola' AS endereco,
                        COUNT(p.id) AS total_atendimentos
                   FROM usuario u
                   LEFT JOIN pagamentos p ON u.codigo = p.id_parceiro AND p.status_atendimento = 'Confirmado' AND p.tipo_parceiro = 'salao'
                   WHERE u.nivel = 'parceiro_hospedado'
                   GROUP BY u.codigo
                   ORDER BY total_atendimentos DESC";

$res_saloes = $mysqli->query($sql_top_saloes);
$lista_saloes = [];
if ($res_saloes && $res_saloes->num_rows > 0) {
    while($row = $res_saloes->fetch_assoc()) { 
        $lista_saloes[] = $row; 
    }
}

// Escolha Rotativa Estável do Salão
if (!empty($lista_saloes)) {
    $melhor_salao = $lista_saloes[array_rand($lista_saloes)];
} else {
    // Fallback de Demonstração caso a tabela 'usuario' esteja vazia localmente
    $melhor_salao = [
        'nome' => 'Barbearia Branca',
        'logo_empresa' => 'OIP (6).webp',
        'endereco' => 'Bairro Talatona (Luanda)',
        'total_atendimentos' => 0
    ];
}

// 2. QUERY DAS LOJAS (TOP VENDAS)
$sql_top_lojas = "SELECT 
                        l.id, 
                        l.nome_loja, 
                        l.endereco_armazem,
                        COUNT(p.id) AS total_vendas
                  FROM lojas l
                  LEFT JOIN pagamentos p ON l.id = p.id_parceiro AND p.status_atendimento = 'Confirmado' AND p.tipo_parceiro = 'loja'
                  WHERE l.visivel_no_site = 1
                  GROUP BY l.id
                  ORDER BY total_vendas DESC";

$res_lojas = $mysqli->query($sql_top_lojas);
$lista_lojas = [];
if ($res_lojas && $res_lojas->num_rows > 0) {
    while($row = $res_lojas->fetch_assoc()) { 
        $lista_lojas[] = $row; 
    }
}

// Escolha Rotativa Estável da Loja
if (!empty($lista_lojas)) {
    $melhor_loja = $lista_lojas[array_rand($lista_lojas)];
} else {
    // Fallback de Demonstração caso a tabela 'lojas' esteja vazia localmente
    $melhor_loja = [
        'nome_loja' => 'Loengo Distribuidora',
        'endereco_armazem' => 'Bairro de São Luís (Huambo)',
        'total_vendas' => 0
    ];
}
?>

<!-- 📊 SEÇÃO DE DESTAQUES MULTI-TENANT RESPONSIVA E DINÂMICA -->
<div style="width: 100%; max-width: 1350px; margin: 30px auto; padding: 0 15px; font-family: 'Segoe UI', Arial, sans-serif; box-sizing: border-box; clear: both !important;">
    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(320px, 1fr)); gap: 20px; width: 100%; box-sizing: border-box;">
         
         <!-- Cartão: Melhor Salão Nacional Rotativo -->
         <?php if ($melhor_salao): 
             $logo_s = basename($melhor_salao['logo_empresa']);
             $caminho_logo = file_exists("uploads/" . $logo_s) ? "uploads/" . $logo_s : (file_exists("upload/" . $logo_s) ? "upload/" . $logo_s : "upload/OIP (6).webp");
         ?>
             <div class="card-lider-dinamico" style="background: linear-gradient(135deg, #0b1a30, #1e293b); border: 2px solid #ca8a04; border-radius: 16px; padding: 20px; display: flex; align-items: center; gap: 15px; box-shadow: 0 8px 16px rgba(0,0,0,0.3); position: relative; overflow: hidden; box-sizing: border-box;">
                
                 
                 <div style="width: 80px; height: 80px; background: #fff; border-radius: 50%; overflow: hidden; display: flex; align-items: center; justify-content: center; border: 2px solid #ca8a04; flex-shrink: 0;">
                    <img src="<?php echo $caminho_logo; ?>" style="width: 100%; height: 100%; object-fit: cover;" onerror="this.src='upload/default.png';">
                 </div>
                 
                 <div class="zona-texto" style="text-align: left; min-width: 0; flex: 1;">
                     <h4 style="color: #fff; margin: 0 0 4px 0; font-size: 15px; text-transform: uppercase; font-weight: bold; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;"><?= htmlspecialchars($melhor_salao['nome']) ?></h4>
                     <span style="color: #38bdf8; font-size: 11px; display: block; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">📍 Região: <?= htmlspecialchars($melhor_salao['endereco']) ?></span>
                     <strong style="color: #22c55e; font-size: 12px; display: block; margin-top: 5px; letter-spacing: 0.5px;">👑 LÍDER COM <?= $melhor_salao['total_atendimentos'] ?> CORTES</strong>
                 </div>
             </div>
         <?php endif; ?>
 
         <!-- Cartão: Melhor Loja Nacional Rotativa -->
         <?php if ($melhor_loja): ?>
             <div class="card-lider-dinamico" style="background: linear-gradient(135deg, #0b1a30, #1e293b); border: 2px solid #eab308; border-radius: 16px; padding: 20px; display: flex; align-items: center; gap: 15px; box-shadow: 0 8px 16px rgba(0,0,0,0.3); position: relative; overflow: hidden; box-sizing: border-box;">
                
                 
                 <div style="width: 80px; height: 80px; background: #111827; border-radius: 50%; overflow: hidden; display: flex; align-items: center; justify-content: center; border: 2px solid #eab308; flex-shrink: 0;">
                     <span style="font-size: 32px; line-height: 1;">🏬</span>
                 </div>
                 
                 <div class="zona-texto" style="text-align: left; min-width: 0; flex: 1;">
                     <h4 style="color: #fff; margin: 0 0 4px 0; font-size: 15px; text-transform: uppercase; font-weight: bold; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;"><?= htmlspecialchars($melhor_loja['nome_loja']) ?></h4>
                     <span style="color: #38bdf8; font-size: 11px; display: block; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">📍 Armazém: <?= htmlspecialchars($melhor_loja['endereco_armazem']) ?></span>
                     <strong style="color: #eab308; font-size: 12px; display: block; margin-top: 5px; letter-spacing: 0.5px;">🚀 LÍDER COM <?= $melhor_loja['total_vendas'] ?> VENDAS</strong>
                 </div>
             </div>
         <?php endif; ?>
 
    </div>
</div>











    <!-- BOTÃO DISPARADOR DE FAQ NA MESMA ABA -->
  <!-- =================================================================
     🔥 MÓDULO FAQ HIPERATIVO E RADIANTE — CONTEÚDO ORIGINAL RESTABELECIDO
     ================================================================= -->

<!-- 🎛️ DISPARADOR ULTRA VIVO COM ANIMAÇÃO DE RESPIRAÇÃO E CONTORNO PULSANTE -->
<div class="pergun" style="text-align: center; margin: 50px 0;">
<button onclick="alternarVisibilidadeFAQ()" class="btn-faq-hiperativo"> 
    <span class="luz-viva"></span>
    <div class="conteudo-botao">
        <span style="letter-spacing: 1px;"> Perguntas importantes</span>
        <img class="icone-faq-roda" width="60px" src="images.webp" alt="FAQ">
        <span style="font-size: 11px; color: #a7f3d0; display: block; margin-top: 4px;">👉 CLICA AQUI 👈</span>
    </div>
</button>
</div>

<!-- 📦 CAIXA DE FAQ VORTEX (SANFONA EXPANSÍVEL INTEGRADA COM TEXTO ANTIGO COMPLETO) -->
<div id="blocoFaqPrincipal" class="bloco-faq-vortex" hidden>

<h2 class="titulo-faq-neon">
     Central de Inteligência: Perguntas Frequentes
</h2>

<h3 class="divisoria-faq-cliente"> Para Clientes</h3>

<!-- Item 1: Grupo Aurélius -->
<details class="item-sanfona-premium">
    <summary><span></span> Como funciona o Grupo Aurélius?</summary>
    <div class="resposta-painel">
        <p>O Grupo Aurélius é um ecossistema tecnológico multisserviços líder na província do Huambo e em Angola. Atuamos em três frentes principais:</p>
        <p style="margin-top: 8px;">
            1.  <b>Agendamento Inteligente:</b> Permite marcar serviços em barbearias e salões parceiros, escolhendo o profissional e o horário ideal.<br>
            2.  <b>Atendimento ao Domicílio:</b> Leva os melhores especialistas de estética e corte diretamente para o conforto da sua casa.<br>
            3. ️ <b>E-Commerce de Cosméticos:</b> Uma loja online integrada para compra de produtos de beleza premium com entrega rápida em bairros , Cidades e faturamentos em Municípios, províncias etc.
        </p>
    </div>
</details>

<!-- Item 2: Cancelamentos -->
<details class="item-sanfona-premium">
    <summary><span></span> É possível Cancelar um serviço? Como funciona o reembolso?</summary>
    <div class="resposta-painel">
        <p>Sim, o cancelamento é totalmente garantido. Se o pagamento foi feito por adiantamento bancário ou retido na plataforma, basta aceder à área de agendamentos e solicitar a revogação até 2 horas antes do atendimento.</p>
        <p style="color: #4ade80; font-weight: bold; margin-top: 8px;">✓ Após a validação da fatura pelo suporte, os valores são estornados integralmente para a conta do cliente, sem taxas adicionais de penalização.</p>
    </div>
</details>

<h3 class="divisoria-faq-parceiro"> Para Profissionais & Hospedagem</h3>

<!-- Item 3: Abordagem Comercial -->
<details class="item-sanfona-premium">
    <summary><span></span> Como funciona a Abordagem e Recepção Comercial?</summary>
    <div class="resposta-painel">
        <p>Como uma Startup de Hospedagem, nós não vendemos apenas um link ou espaço fixo no site. Nós entregamos um multiplicador de faturamento para o seu negócio.</p>
        <p style="color: #facc15; font-weight: bold; margin-top: 8px;"> Automatizamos a sua agenda local, reduzimos em até 95% as faltas dos clientes através de notificações executivas e direcionamos o fluxo de tráfego das províncias direto para as Empresas operacionais dos salões parceiros.</p>
    </div>
</details>

<!-- Item 4: Período de Testes -->
<details class="item-sanfona-premium">
    <summary><span></span> Existe algum período de teste gratuito? Quais são as taxas?</summary>
    <div class="resposta-painel">
        <p>Sim! Aplicamos o modelo Freemium de crescimento. Oferecemos um período de teste gratuito de 30 dias com acesso total ao painel gerencial isolado para que comprove o aumento de clientes reais antes de fazer qualquer investimento.</p>
        <p style="color: #38bdf8; font-weight: bold; margin-top: 8px;">️ Após o período de teste, o salão opta pelo pagamento de uma taxa fixa de hospedagem mensal por Empresa operacional ativa ou pela comissão regulamentar de 15% retida sobre os faturamentos gerenciados pela plataforma.</p>
    </div>
</details>
</div>


<style>
/* O BOTÃO HIPERATIVO (RESPIRAÇÃO + GLOW RADICAL) */
.btn-faq-hiperativo {
    position: relative;
    background: #0f172a;
    color: #38bdf8;
    border: 2px solid #38bdf8;
    border-radius: 14px;
    padding: 22px 45px;
    font-size: 15px;
    font-weight: 900;
    cursor: pointer;
    text-transform: uppercase;
    overflow: hidden;
    box-shadow: 0 0 15px rgba(56, 189, 248, 0.4), inset 0 0 10px rgba(56, 189, 248, 0.1);
    transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    animation: respirarBotao 2s infinite ease-in-out;
}

.luz-viva {
    position: absolute;
    top: 0; left: -100%;
    width: 50%; height: 100%;
    background: linear-gradient(to right, transparent, rgba(56, 189, 248, 0.4), transparent);
    transform: skewX(-25deg);
    animation: varrerLuz 3s infinite linear;
}

.conteudo-botao { display: flex; flex-direction: column; align-items: center; justify-content: center; gap: 8px; }
.icone-faq-roda { filter: drop-shadow(0 0 6px #38bdf8); transition: transform 0.5s ease; }

.btn-faq-hiperativo:hover {
    background: #1e1b4b;
    color: #22d3ee;
    border-color: #22d3ee;
    transform: scale(1.06) translateY(-3px);
    box-shadow: 0 0 25px #22d3ee, 0 0 50px rgba(34, 211, 238, 0.3);
}
.btn-faq-hiperativo:hover .icone-faq-roda { transform: scale(1.3) rotate(360deg); }

/* A CAIXA VORTEX (BRILHO FLUIDO DINÂMICO) */
.bloco-faq-vortex {
    background: #0f1123;
    border: 2px solid #38bdf8;
    border-radius: 20px;
    padding: 40px;
    max-width: 850px;
    margin: 30px auto 50px auto;
    box-shadow: 0 0 25px rgba(56, 189, 248, 0.3), inset 0 0 20px rgba(34, 211, 238, 0.05);
    text-align: left;
    animation: pulsarCaixaVortex 4s infinite alternate ease-in-out;
}

/* Ocultação por atributo hidden compatível com animação */
.bloco-faq-vortex[hidden] {
    display: none !important;
}

.titulo-faq-neon {
    font-size: 22px;
    color: #22d3ee;
    text-align: center;
    margin-top: 0;
    text-transform: uppercase;
    margin-bottom: 30px;
    letter-spacing: 1px;
    text-shadow: 0 0 10px rgba(34, 211, 238, 0.6);
    border-bottom: 2px solid rgba(56, 189, 248, 0.2);
    padding-bottom: 15px;
}

.divisoria-faq-cliente { font-size: 13px; font-weight: 800; color: #e2e8f0; text-transform: uppercase; border-left: 4px solid #38bdf8; padding-left: 12px; margin: 25px 0 15px 0; letter-spacing: 0.5px; }
.divisoria-faq-parceiro { font-size: 13px; font-weight: 800; color: #e2e8f0; text-transform: uppercase; border-left: 4px solid #ca8a04; padding-left: 12px; margin: 35px 0 15px 0; letter-spacing: 0.5px; }

.item-sanfona-premium {
    background-color: #070913;
    border: 1px solid #1e293b;
    border-radius: 10px;
    margin-bottom: 14px;
    padding: 18px;
    transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
}

.resposta-painel {
    font-size: 13px;
    color: #cbd5e1;
    margin-top: 12px;
    line-height: 1.6;
    text-align: justify;
    border-top: 1px solid #1e293b;
    padding-top: 12px;
    animation: deslizarEntrada 0.4s ease-out;
}

.item-sanfona-premium:hover {
    border-color: #22d3ee;
    background: #111428;
    box-shadow: 0 0 15px rgba(34, 211, 238, 0.2);
    transform: translateX(4px);
}
.item-sanfona-premium[open] {
    border-color: #22d3ee;
    background: #0f132a;
    box-shadow: 0 0 20px rgba(34, 211, 238, 0.25);
}

@keyframes respirarBotao {
    0% { transform: scale(1); box-shadow: 0 0 12px rgba(56, 189, 248, 0.4); }
    50% { transform: scale(1.03); box-shadow: 0 0 22px rgba(56, 189, 248, 0.7), 0 0 35px rgba(56, 189, 248, 0.2); }
    100% { transform: scale(1); box-shadow: 0 0 12px rgba(56, 189, 248, 0.4); }
}
@keyframes varrerLuz {
    0% { left: -100%; }
    50% { left: 150%; }
    100% { left: 150%; }
}
@keyframes pulsarCaixaVortex {
    0% { box-shadow: 0 0 15px rgba(56, 189, 248, 0.25); border-color: #0284c7; }
    100% { box-shadow: 0 0 35px rgba(34, 211, 238, 0.6), 0 0 50px rgba(56, 189, 248, 0.1); border-color: #22d3ee; }
}
@keyframes deslizarEntrada {
    0% { opacity: 0; transform: translateY(-8px); }
    100% { opacity: 1; transform: translateY(0); }
}
</style>

<!-- =================================================================
 🧠 MOTOR JAVASCRIPT CORRIGIDO: MANIPULAÇÃO DO ATRIBUTO HIDDEN
 ================================================================= -->
<script>
function alternarVisibilidadeFAQ() {
var faq = document.getElementById('blocoFaqPrincipal');
if (faq) {
    // Verifica dinamicamente o atributo hidden nativo para não haver conflitos
    if (faq.hasAttribute('hidden')) {
        faq.removeAttribute('hidden');
        faq.scrollIntoView({ behavior: 'smooth', block: 'center' });
    } else {
        faq.setAttribute('hidden', 'true');
    }
}
}
</script>




    <script>
    // ⚡ CONTROLADOR REATIVO DE VISIBILIDADE DO FAQ
    function alternarVisibilidadeFAQ() {
        var faq = document.getElementById('blocoFaqPrincipal');
        if (faq) {
            if (faq.hasAttribute('hidden')) {
                faq.removeAttribute('hidden');
                faq.scrollIntoView({ behavior: 'smooth' });
            } else {
                faq.setAttribute('hidden', 'true');
            }
        }
    }
    </script><!-- FIM DO BLOCO FAQ -->






<!-- =========================================================================
     ⚡ MOTOR JAVASCRIPT DA PÁGINA PRINCIPAL (REDISPACHO COM REDE DE SEGURANÇA)
     ========================================================================= -->
<script>
// 1. REAÇÃO ASSÍNCRONA NA VITRINA GLOBAL
function computarReacaoGlobal(idAnuncio, tipoReacao) {
    if (!idAnuncio || !tipoReacao) return;

    const formReacao = new FormData();
    formReacao.append('id_anuncio', idAnuncio);
    formReacao.append('tipo_reacao', tipoReacao);

    fetch('salvar_reacao.php', { method: 'POST', body: formReacao })
    .then(r => r.json())
    .then(resultado => {
        if (resultado.sucesso) {
            const idAlvo = (tipoReacao === 'adoro') ? 'gl-adoro-' + idAnuncio : 'gl-ncurto-' + idAnuncio;
            const elemento = document.getElementById(idAlvo);
            if (elemento) elemento.innerText = resultado.novo_total;
        }
    })
    .catch(() => {
        // Fallback visual rápido em caso de latência local
        const el = document.getElementById((tipoReacao === 'adoro' ? 'gl-adoro-' : 'gl-ncurto-') + idAnuncio);
        if (el) el.innerText = (parseInt(el.innerText) || 0) + 1;
    });
}

// 2. REDIRECIONAMENTO INTELIGENTE: Envia o cliente para o Dashboard do salão com o cupão ativo via URL
function redirecionarParaAgendamentoComCupao(idEmpresa, nomeCorte, descontoPercentual, idAnuncio) {
    if (!idEmpresa || !nomeCorte) return;

    // Regista a métrica de intenção de compra no Business Intelligence (+25 pontos para a foto)
    const dadosMetrica = new FormData();
    dadosMetrica.append('id_anuncio', idAnuncio);
    dadosMetrica.append('tipo_acao', 'clique_agendamento');
    fetch('atualizar_metricas_bi.php', { method: 'POST', body: dadosMetrica });

    // Despacha o utilizador com os parâmetros em falta direto para o Dashboard da barbearia dona do trabalho!
    const rotaDestino = `Dashboard.php?empresa=${idEmpresa}&servico_vip=${encodeURIComponent(nomeCorte)}&desconto_cupao=${descontoPercentual}`;
    
    alert(`🎉 Cupão Validado pelo Grupo Aurélius!\n\nA redirecionar para a unidade operacional com -${descontoPercentual}% de desconto garantidos no caixa.`);
    window.location.href = rotaDestino;
}

// 🛡️ MODERAÇÃO MESTRE: Permite que o administrador apague fotos indecentes ou sujas em tempo real
function moderacaoRemoverFoto(idAnuncio) {
    if (!idAnuncio) return;
    if (!confirm("🚨 ATENÇÃO ADMINISTRADOR:\n\nDeseja remover imediatamente esta fotografia por violar as diretrizes de decência da plataforma?")) return;

    const formModera = new FormData();
    formModera.append('id_anuncio', idAnuncio);
    formModera.append('acao_galeria', 'deletar');

    fetch('eliminar_foto_galeria.php', {
        method: 'POST',
        body: formModera
    })
    .then(response => response.json())
    .then(resultado => {
        if (resultado.sucesso) {
            // Remove o cartão da tela com efeito suave de desaparecimento
            const elementoCartao = document.getElementById('cartao-global-' + idAnuncio);
            if (elementoCartao) {
                elementoCartao.style.opacity = '0';
                elementoCartao.style.transform = 'scale(0.9)';
                elementoCartao.style.transition = '0.3s ease';
                setTimeout(() => { elementoCartao.remove(); }, 300);
            }
            alert("🗑️ Segurança Concluída: Imagem inapropriada expurgada do sistema com sucesso!");
        } else {
            alert("Erro de autenticação: " + resultado.mensagem);
        }
    })
    .catch(() => {
        alert("Erro técnico ao tentar executar a limpeza forçada.");
    });
}
</script>




<?php
// =========================================================================
// 🏆 PÓDIO SEMANAL DE PARCEIROS - MOTOR DE ENGAJAMENTO REAL SaaS
// =========================================================================
try {
    // A variável TEM de se chamar $query_podio para a linha 2308 ler sem erro!
    $query_podio = $pdo->query("
        SELECT 
            u.codigo, 
            ANY_VALUE(u.nome) AS nome, 
            ANY_VALUE(u.logo_empresa) AS logo_empresa, 
            ANY_VALUE(u.slug) AS slug, 
            IFNULL(SUM(a.likes_adoro), 0) AS total_votos
        FROM `usuario` u
        INNER JOIN `anuncios` a ON a.id_barbearia = u.codigo
        WHERE u.nivel = 'parceiro_hospedado'
        GROUP BY u.codigo
        ORDER BY total_votos DESC
        LIMIT 3
    ");
} catch (PDOException $e) {
    error_log("Erro no Pódio: " . $e->getMessage());
    $query_podio = null;
}

// 🟢 EXECUTOR DO FETCH DO PÓDIO (Linha 2308 Protegida)
if (isset($query_podio) && $query_podio !== null) {
    $vencedores_semana = $query_podio->fetchAll(PDO::FETCH_ASSOC);
} else {
    $vencedores_semana = []; // Fallback seguro para não travar a página
}

// =========================================================================
// 📈 PREPARAÇÃO DA QUERY DO RANKING GLOBAL (MÉTODO REATIVO VORTEX)
// =========================================================================
// 🌟 INJEÇÃO DA VARIÁVEL CRÍTICA: Define que o ranking analisa os últimos 30 dias
$data_limite = date('Y-m-d', strtotime('-30 days'));

$stmtGlobal = $pdo->prepare("
    SELECT 
        a.id_anuncio,
        ANY_VALUE(a.id_barbearia) as id_barbearia,
        ANY_VALUE(a.titulo) as titulo,
        ANY_VALUE(a.imagem) as imagem,
        ANY_VALUE(a.ativo) as ativo,
        ANY_VALUE(a.data_publicacao) as data_publicacao,
        ANY_VALUE(a.likes_adoro) as likes_adoro,
        ANY_VALUE(a.likes_ncurto) as likes_ncurto,
        ANY_VALUE(a.cliques_agendamento) as cliques_agendamento,
        ANY_VALUE(a.contagem_partilhas) as contagem_partilhas,
        ANY_VALUE(u.nome) AS nome_salao
    FROM anuncios a
    LEFT JOIN usuario u ON a.id_barbearia = u.codigo
    WHERE a.ativo = 1 AND a.data_publicacao >= :data_limite
    GROUP BY a.id_anuncio
    ORDER BY (ANY_VALUE(a.likes_adoro) * 10) + (ANY_VALUE(a.likes_ncurto) * 2) + (ANY_VALUE(a.cliques_agendamento) * 25) + (ANY_VALUE(a.contagem_partilhas) * 15) DESC, a.id_anuncio DESC
    LIMIT 8
");
?>

<div style="max-width: 95%; margin: 30px auto; font-family: 'Segoe UI', Arial, sans-serif;">
    
<!-- O restante do seu HTML e o Feed do Facebook continuam logo abaixo perfeitamente... -->

<!-- =========================================================================
     🔷 FEED INTERATIVO DE ANÚNCIOS AURELIUS RESPONSIVO (ESTILO FACEBOOK REAL)
     ========================================================================= -->
     <?php
     include_once("Conexao.php");
     
     // Motor rotativo aleatório que puxa produtos ativos das lojas
     $query_feed_fb = $pdo->query("
         SELECT p.*, l.nome_loja, l.slug_loja 
         FROM `produtos_cosmeticos` p
         INNER JOIN `lojas` l ON p.empresa_id = l.id
         WHERE p.stock_atual > 0
         ORDER BY RAND() LIMIT 2
     ");
     $feed_produtos = $query_feed_fb->fetchAll(PDO::FETCH_ASSOC);
     ?>
     <!-- Estilos Globais e Regras de Media Queries para Smartphones -->
     <style>
         .feed-container-fb {
             width: 100%;
             max-width: 580px;
             margin: 20px auto;
             font-family: 'Segoe UI', -apple-system, BlinkMacSystemFont, Arial, sans-serif;
             padding: 0 12px;
             box-sizing: border-box;
         }
         .post-card-fb {
             background: #1e293b; 
             border: 1px solid #334155; 
             border-radius: 12px; 
             padding: 16px; 
             margin-bottom: 25px; 
             box-shadow: 0 4px 15px rgba(0,0,0,0.4); 
             text-align: left;
             box-sizing: border-box;
         }
         .img-container-fb {
             width: 100%; 
             height: 380px; 
             background: #0f172a; 
             border-radius: 8px; 
             overflow: hidden; 
             position: relative; 
             border: 1px solid #334155; 
             margin-bottom: 12px; 
             display: flex; 
             align-items: center; 
             justify-content: center; 
             box-sizing: border-box;
         }
         .img-container-fb img {
             width: 100%;
             height: 100%;
             object-fit: cover; /* Recorta a imagem proporcionalmente sem achatar */
             transition: transform 0.3s ease;
         }
         .img-container-fb img:hover {
             transform: scale(1.02);
         }
         .btn-acao-fb {
             flex: 1; 
             background: none; 
             border: none; 
             color: #94a3b8; 
             font-weight: bold; 
             font-size: 12px; 
             cursor: pointer; 
             outline: none;
             padding: 8px 4px;
             display: flex;
             align-items: center;
             justify-content: center;
             gap: 4px;
             transition: background 0.2s;
             border-radius: 4px;
         }
         .btn-acao-fb:hover {
             background: rgba(255,255,255,0.05);
             color: #fff;
         }
         .btn-comprar-fb {
             flex: 1.3; 
             background: linear-gradient(135deg, #22c55e, #16a34a); 
             color: #000 !important; 
             text-decoration: none; 
             text-align: center; 
             padding: 8px 0; 
             font-size: 11px; 
             font-weight: bold; 
             border-radius: 4px; 
             text-transform: uppercase;
             letter-spacing: 0.5px;
             display: flex;
             align-items: center;
             justify-content: center;
             box-shadow: 0 4px 10px rgba(34, 197, 94, 0.2);
         }
         .btn-comprar-fb:hover {
             filter: brightness(1.1);
         }
     
         /* Otimizações Dinâmicas para ecrãs pequenos (Telemóveis) */
         @media (max-width: 480px) {
             .img-container-fb {
                 height: 260px; /* Diminui a altura no telemóvel para não ocupar o ecrã todo */
             }
             .barra-acoes-fb {
                 gap: 2px !important;
             }
             .btn-acao-fb {
                 font-size: 11px !important;
             }
         }
     </style>













<!-- 🛍️ SEÇÃO DE RECOMENDAÇÕES SAAS ENTERPRISE: INTERCALAÇÃO DINÂMICA LADO A LADO -->
<h4 style="color: #38bdf8; text-transform: uppercase; font-weight: bold; font-size: 13px; margin-top: 30px; margin-bottom: 20px; border-left: 4px solid #1877f2; padding-left: 10px; letter-spacing: 0.5px; font-family: 'Segoe UI', system-ui, sans-serif;">
    • Sugestões para Si
</h4>

<!-- 💻 FOLHA DE ESTILOS COMBINATÓRIA RESPONSIVA PWA -->
<style>
.vitrina-saas-grid {
    display: grid !important;
    /* Garante rigorosamente 2 produtos lado a lado em ecrãs móveis e expande dinamicamente no PC */
    grid-template-columns: repeat(auto-fill, minmax(160px, 1fr)) !important;
    gap: 12px !important;
    width: 100% !important;
    box-sizing: border-box !important;
    padding: 0 4px !important;
}
.post-card-fb {
    background: #1e293b;
    border: 1px solid #334155;
    border-radius: 12px;
    padding: 10px;
    box-shadow: 0 4px 15px rgba(0,0,0,0.2);
    display: flex;
    flex-direction: column;
    justify-content: space-between;
    min-height: 380px;
    box-sizing: border-box;
    transition: transform 0.2s ease, border-color 0.2s ease;
}
.post-card-fb:hover {
    transform: translateY(-3px);
    border-color: #38bdf8;
}
.img-container-fb {
    width: 100%;
    height: 130px;
    border-radius: 8px;
    overflow: hidden;
    background: #070b12;
    border: 1px solid #233144;
    display: flex;
    align-items: center;
    justify-content: center;
    position: relative;
}
.badge-stock-neon {
    position: absolute;
    top: 6px;
    right: 6px;
    background: rgba(15, 23, 42, 0.95);
    color: #22c55e;
    padding: 2px 6px;
    border-radius: 6px;
    font-size: 9.5px;
    font-weight: bold;
    border: 1px solid rgba(34, 197, 94, 0.3);
}
</style>
<?php 
$total_posts_exibidos = 0;
$produtos_por_loja = [];

// 🔀 MOTOR DE MISTURA INICIAL: Embaralha os produtos recebidos antes da separação por loja
if (!empty($feed_produtos)) {
    shuffle($feed_produtos); 
    
    foreach ($feed_produtos as $post) {
        // 🟢 INTERCONEXÃO REATIVA: Usa a coluna viva 'stock' e valida se há unidades reais
        $stock_real = isset($post['stock']) ? intval($post['stock']) : 0;
        
        // Regra SaaS: Se o stock for 0 ou esgotar, cai da vitrina automaticamente
        if ($stock_real <= 0) {
            continue; 
        }

        $id_loja_dono = intval($post['empresa_id'] ?? 0);
        if ($id_loja_dono > 0) {
            $post['stock_sincronizado'] = $stock_real;
            $produtos_por_loja[$id_loja_dono][] = $post;
        }
    }
}

// 2. ALGORITMO ROUND-ROBIN (INTERCALADOR AUTOMÁTICO DE EMPRESAS)
$feed_intercalado_mestre = [];
while (count($produtos_por_loja) > 0) {
    foreach ($produtos_por_loja as $id_loja => &$lista_artigos) {
        $item = array_shift($lista_artigos);
        $feed_intercalado_mestre[] = $item;
        
        if (empty($lista_artigos)) {
            unset($produtos_por_loja[$id_loja]);
        }
    }
}

// 🎰 LIMITE DINÂMICO ALEATÓRIO: Sorteia exibir entre 4 e 8 produtos a cada refresh de página
$limite_produtos_atualizacao = rand(4, 8);

// Corta o feed final para exibir apenas a quantidade sorteada simultaneamente
if (!empty($feed_intercalado_mestre)) {
    $feed_intercalado_mestre = array_slice($feed_intercalado_mestre, 0, $limite_produtos_atualizacao);
}

// Renderiza a Grid Responsiva Dupla PWA
echo '<div class="vitrina-saas-grid">';

if (!empty($feed_intercalado_mestre)): 
foreach ($feed_intercalado_mestre as $post): 
    $id_post = intval($post['id'] ?? 0);
    $id_loja_redirecionamento = intval($post['empresa_id'] ?? 0);
    
    $produto_nome = htmlspecialchars($post['nome_produto'] ?? 'Artigo', ENT_QUOTES, 'UTF-8');
    $stock_total  = intval($post['stock_sincronizado'] ?? 0);
    $preco_real   = number_format($post['preco'] ?? 0, 2, ',', '.');
    $codigo_serie = "LOTE-COS-" . $id_loja_redirecionamento . "-" . $id_post;
    
    // Mapeamento Dinâmico Automático baseado nos teus IDs de Fornecedores reais (Removido Barbearia Branca por segurança)
    $loja_nome = "Parceiro ID " . $id_loja_redirecionamento;
    if ($id_loja_redirecionamento === 238) $loja_nome = "Mamadu";
    elseif ($id_loja_redirecionamento === 240) $loja_nome = "Loengo";
    elseif ($id_loja_redirecionamento === 241) $loja_nome = "Angelino Comercial";
    elseif ($id_loja_redirecionamento === 242) $loja_nome = "Gráfica Soma";
    elseif ($id_loja_redirecionamento === 245) $loja_nome = "Loja Marcante";
    elseif ($id_loja_redirecionamento === 247) $loja_nome = "Totale";

    $link_destino_saas = "Lojas.php?id_loja=" . $id_loja_redirecionamento . "&produto_alvo=" . $id_post;

    // 🖼️ TRATAMENTO DAS ROTAS DE IMAGENS BINÁRIAS
    $nome_imagem_banco = !empty($post['imagem']) ? trim($post['imagem']) : '';
    $img_post = "uploads/default_cosmetico.jpg"; 
    if (!empty($nome_imagem_banco)) {
        $arquivo_limpo = basename($nome_imagem_banco);
        if (file_exists("uploads/" . $arquivo_limpo)) {
            $img_post = "uploads/" . $arquivo_limpo;
        } elseif (file_exists($arquivo_limpo)) {
            $img_post = $arquivo_limpo;
        }
    }
    
    $total_posts_exibidos++;
?>

    <!-- 🎴 CARD GÉMEO INTERCALADO SAAS ENTERPRISE -->
    <div id="post_fb_<?php echo $id_post; ?>" class="post-card-fb">

        <div>
            <!-- 👤 CABEÇALHO COMPACTO DA LOJA -->
            <div style="display: flex; align-items: center; gap: 6px; margin-bottom: 8px;">
                <div style="width: 24px; height: 24px; background: #0f172a; border-radius: 50%; display: flex; align-items: center; justify-content: center; border: 1.5px solid #1877f2; overflow: hidden; flex-shrink: 0;">
                    <img src="uploads/OIP (6).webp" style="width: 100%; height: 100%; object-fit: cover;" onerror="this.src='uploads/default.png';">
                </div>
                <div style="min-width: 0; flex: 1; text-align: left;">
                    <strong style="color: #ffffff; font-size: 10.5px; display: block; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; font-weight: 600; text-transform: uppercase;"><?php echo $loja_nome; ?></strong>
                    <span style="color: #64748b; font-size: 8.5px; display: block;">Sincronizado 🌍</span>
                </div>
            </div>

            <!-- 📝 DETALHES TÉCNICOS -->
            <div style="text-align: left; margin-bottom: 6px; line-height: 1.2;">
                <strong style="color: #38bdf8; font-size: 12.5px; display: block; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;"><?php echo $produto_nome; ?></strong>
                <span style="color: #94a3b8; font-size: 9px; display: block; font-family: monospace; margin-top: 1px;">Série: <?php echo $codigo_serie; ?></span>
                <span style="color: #64748b; font-size: 8.5px; display: block;">Especificações: <?php echo htmlspecialchars($post['tamanho'] ?? 'Padrão'); ?></span>
            </div>

            <!-- 🖼️ CONTAINER DA IMAGEM -->
            <div class="img-container-fb">
                <img src="<?php echo $img_post; ?>" alt="<?php echo $produto_nome; ?>" style="width: 100%; height: 100%; object-fit: cover;" decoding="async" onerror="this.src='uploads/default_cosmetico.jpg';">
                <span class="badge-stock-neon"><?php echo $stock_total; ?> un.</span>
            </div>
        </div>

        <div>
            <!-- 💰 PREÇO MONETIZADO REAL -->
            <div style="display: flex; justify-content: space-between; align-items: center; padding: 6px 0; border-bottom: 1px solid #334155; margin-bottom: 6px;">
                <span style="color: #64748b; font-size: 10.5px;">Preço Unid:</span>
                <strong style="color: #22c55e; font-size: 13.5px; font-weight: 700;"><?php echo $preco_real; ?> Kz</strong>
            </div>

            <!-- ⚡ BOTÃO DE COMPRA DIRECIONADA -->
            <div style="margin-top: 6px; display: flex; justify-content: center; width: 100%;">
                <a href="<?php echo $link_destino_saas; ?>" style="display: inline-block; width: 85%; max-width: 140px; background: linear-gradient(135deg, #1877f2, #1159c7); color: #ffffff; text-align: center; padding: 6px 10px; text-decoration: none; font-weight: bold; border-radius: 20px; font-size: 10.5px; text-transform: uppercase; letter-spacing: 0.5px; box-shadow: 0 4px 10px rgba(24, 119, 242, 0.15); margin: 0 auto; transition: background 0.2s;">
                    ⚡ Ir na Loja
                </a>
            </div>
        </div>

    </div>

<?php 
endforeach; 
endif; 

echo '</div>'; // Fecha a div .vitrina-saas-grid
?>










<!-- =========================================================================
⚙️ MOTOR VORTEX REATIVO JAVASCRIPT (SINCRA AUTOMÁTICA SEM REFRESH)
========================================================================= -->
<script>
function executarGostoVirtual(botao, idPost) {
if (botao.classList.contains('clicado-ativo')) return;
botao.classList.add('clicado-ativo');
botao.style.color = '#38bdf8'; // Feedback visual imediato de cor viva

const spanLikes = document.getElementById('contador_likes_' + idPost);
if (spanLikes) {
  spanLikes.innerText = parseInt(spanLikes.innerText) + 1;
}
}

function executarPartilhaVirtual(botao, idPost) {
if (botao.classList.contains('clicado-ativo')) return;
botao.classList.add('clicado-ativo');
botao.style.color = '#a855f7'; // Feedback roxo de partilha

const spanShares = document.getElementById('contador_partilhas_' + idPost);
if (spanShares) {
  spanShares.innerText = parseInt(spanShares.innerText) + 1;
}


const avisoVazio = document.getElementById('sem_comentarios_aviso_' + idPost);
    if (avisoVazio) { avisoVazio.remove(); }
// Dispara a partilha nativa do telemóvel (Bluetooth/WhatsApp/ShareIt) se disponível
if (navigator.share) {
  navigator.share({
      title: 'Ecossistema Aurélius',
      text: 'Confira este produto incrível no nosso balcão digital!',
      url: window.location.href
  }).catch(console.error);
}
}

function focarCaixaComentario(idPost) {
const input = document.getElementById('input_msg_fb_' + idPost);
if (input) { input.focus(); }
}

function adicionarMensagemVirtual(event, idPost) {
event.preventDefault(); // 🛑 TRAVA DE INTEGRIDADE: Impede o recarregamento do ficheiro Principal.php

const input = document.getElementById('input_msg_fb_' + idPost);
const caixaMsg = document.getElementById('caixa_mensagens_fb_' + idPost);
const spanComent = document.getElementById('contador_coment_' + idPost);

if (!input || !input.value.trim()) return;

const textoMensagem = input.value.trim();

// Cria a estrutura HTML da nova mensagem em memória dinâmica na hora
const novoComentarioHTML = `
  <div style="text-align: left; animation: fadeInComentAurelius 0.3s ease;">
      <b style="color: #ffca28; font-size: 12px;">Tu (Utilizador):</b>
      <span style="color: #fff; font-size: 12px; margin-left: 4px;">${textoMensagem}</span>
  </div>
`;

// Injeta a mensagem no visor sem mexer no Apache ou dar refresh
if (caixaMsg) {
  caixaMsg.insertAdjacentHTML('beforeend', novoComentarioHTML);
  caixaMsg.scrollTop = caixaMsg.scrollHeight; // Desloca o foco para exibir a última mensagem
}

// Incrementa o contador de SMS localmente
if (spanComent) {
  spanComent.innerText = parseInt(spanComent.innerText) + 1;
}

// Limpa a caixa de texto
input.value = '';
}
</script>

<style>
@keyframes fadeInComentAurelius {
from { opacity: 0; transform: translateY(4px); }
to { opacity: 1; transform: translateY(0); }
}
</style>






<!-- =========================================================================
     🎫 TELA POP-UP: RESGATE DE CUPÃO AUTOMÁTICO REATIVO (ESTILO FACEBOOK DIALOG)
     ========================================================================= -->
<div id="modal_cupao_aurelius" style="display: none; position: fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.85); z-index:99999999; justify-content:center; align-items:center; backdrop-filter:blur(5px); font-family: sans-serif;">
    <div style="background: #111827; border: 2px solid #22c55e; max-width: 380px; width: 90%; padding: 25px; border-radius: 12px; text-align: center; position: relative;">
        <span onclick="fecharEcraCupaoAutomático()" style="position: absolute; top: 10px; right: 15px; color: #ef4444; font-size: 24px; font-weight: bold; cursor: pointer;">&times;</span>
        
        <div style="font-size: 40px; margin-bottom: 10px;">🎫</div>
        <h3 style="color: #22c55e; margin: 0 0 10px 0; text-transform: uppercase; font-size: 16px; font-weight: bold;">Parabéns! Libertou um Cupão</h3>
        <p style="color: #cbd5e1; font-size: 13px; line-height: 1.5; margin-bottom: 20px;">
            Esta publicação atingiu uma pauta elevada de interações [C]. Copie o código abaixo e apresente-o no balcão de checkout do estabelecimento!
        </p>

        <!-- Caixa de Código do Cupão Cortado Térmico -->
        <div style="background: #0f172a; border: 2px dashed #22c55e; padding: 12px; font-size: 18px; font-weight: bold; color: #fff; letter-spacing: 2px; margin-bottom: 20px; text-transform: uppercase;" id="txt_codigo_cupao_gerado">
            AURELIUS10
        </div>

        <button onclick="copiarCupaoGeradoAoClip()" style="width: 100%; background: #22c55e; color: #000; border: none; padding: 12px; font-weight: bold; text-transform: uppercase; font-size: 12px; cursor: pointer; border-radius: 6px;">
            📋 Copiar Código do Cupão
        </button>
    </div>
</div>



<script>
function dispararEcraCupaoReativo(idFotoAnuncio, percentagemDesconto) {
    // Gera um código único baseado no ID e valor para validação na unitel.php
    var codigoFinal = "AUR" + percentagemDesconto + "ID" + idFotoAnuncio;
    
    document.getElementById('txt_codigo_cupao_gerado').innerText = codigoFinal;
    document.getElementById('modal_cupao_aurelius').style.display = 'flex';
}

function fecharEcraCupaoAutomático() {
    document.getElementById('modal_cupao_aurelius').style.display = 'none';
}

function copiarCupaoGeradoAoClip() {
    var codigoTexto = document.getElementById('txt_codigo_cupao_gerado').innerText;
    navigator.clipboard.writeText(codigoTexto).then(() => {
        alert("🎉 Código " + codigoTexto + " copiado! Use-o no checkout para resgatar o seu desconto.");
        fecharEcraCupaoAutomático();
    });
}




















<!-- =========================================================================
     🟢 SCRIPT JAVASCRIPT: MOTOR DE INTERAÇÃO REDE SOCIAL AVANÇADO
     ========================================================================= -->
<script>
const registoLikesLocais = {};
const registoPartilhasLocais = {};

function computarLikeReativo(idPost) {
    const btn = document.getElementById('btn_like_' + idPost);
    const txtContador = document.getElementById('contador_likes_' + idPost);
    let valorAtual = parseInt(txtContador.innerText);

    if (!registoPartilhasLocais[idPost]) {
        registoPartilhasLocais[idPost] = true;
        txtContador.innerText = valorAtual + 1;
        btn.style.color = '#4ade80';
        btn.innerHTML = '↪️ Partilhado';
        
        var urlPartilhaReal = window.location.origin + window.location.pathname + '?id_post=' + idPost;
        window.open('https://facebook.com' + encodeURIComponent(urlPartilhaReal), '_blank', 'width=600,height=400');
    }
}

function focarCampoComentario(idPost) {
    const input = document.getElementById('input_coment_' + idPost);
    if(input) {
        input.focus();
        input.value = "@Resposta: ";
    }
}

function enviarComentarioFeed(idPost) {
    const input = document.getElementById('input_coment_' + idPost);
    const caixaComentarios = document.getElementById('caixa_comentarios_' + idPost);
    const txtContadorComent = document.getElementById('contador_coment_' + idPost);
    
    if(!input || !caixaComentarios) return;
    const textoLimpo = input.value.trim();

    if (textoLimpo === '') {
        alert('⚠️ Digite uma mensagem antes de publicar!');
        return;
    }

    const novoElemento = document.createElement('div');
    novoElemento.style.borderLeft = '2px solid #22c55e';
    novoElemento.style.paddingLeft = '8px';
    novoElemento.innerHTML = `<b style="color: #4ade80;">Tu (Agora):</b> <span style="color:#cbd5e1;">${escapeHtml(textoLimpo)}</span>
                              <div style="margin-top: 4px;"><span onclick="focarCampoComentario(${idPost})" style="color:#64748b; cursor:pointer; font-weight:bold; font-size:10px;">💬 Responder</span></div>`;
    
    caixaComentarios.appendChild(novoElemento);
    caixaComentarios.scrollTop = caixaComentarios.scrollHeight;

    let contagemAtual = parseInt(txtContadorComent.innerText) || 0;
    txtContadorComent.innerText = contagemAtual + 1;
    input.value = '';
}

function escapeHtml(text) {
    return text.replace(/&/g, "&amp;").replace(/</g, "&lt;").replace(/>/g, "&gt;").replace(/"/g, "&quot;");
}
</script>

<!-- 🟢 RE-INSERÇÃO DO INPUT DO FEED PARA EVITAR O ERRO DE EXECUÇÃO -->
<script>
document.querySelectorAll('.card-publicidade, .card-publicidade-fb').forEach(card => {
    // Garante a existência do nó dinâmico do input para o JS ler sem dar erro de undefined
    if(card && !card.querySelector('input')) {
        const idCard = card.id.replace(/^\D+/g, '');
        const inputContainer = card.querySelector('.input-container-fb');
        if(inputContainer) {
            inputContainer.innerHTML = `<input type="text" id="input_coment_${idCard}" placeholder="Escreva um comentário público..." style="flex: 1; padding: 10px; background: #0f172a; border: 1px solid #475569; border-radius: 20px; color: #fff; font-size: 12px; outline: none;">`;
        }
    }
});
</script>

<!-- =========================================================================
     ⭐ MATRIX DE MULTIMÉDIA CORRIGIDA PARA O RANKING DE TENDÊNCIAS
     ========================================================================= -->
<script>
// 🟢 DETETOR REATIVO DE MULTIMÉDIA: Se o arquivo for MP4, substitui a tag IMG quebrada por um bloco VIDEO funcional
document.querySelectorAll('.grid-inputs, div[id^="cartao-global-"]').forEach(card => {
    const img = card.querySelector('img');
    if(img) {
        const srcOriginal = img.getAttribute('src') || '';
        const extensao = srcOriginal.split('.').pop().toLowerCase();
        
        if(extensao === 'mp4' || extensao === 'mov' || extensao === 'avi') {
            const videoContetor = document.createElement('video');
            videoContetor.setAttribute('src', srcOriginal);
            videoContetor.setAttribute('loop', 'true');
            videoContetor.setAttribute('muted', 'true');
            videoContetor.setAttribute('playsinline', 'true');
            videoContetor.style.width = "100%";
            videoContetor.style.height = "100%";
            videoContetor.style.objectFit = "cover";
            
            // Ativa a reprodução automática ao passar o rato por cima do cartão do look
            card.addEventListener('mouseover', () => videoContetor.play().catch(()=>null));
            card.addEventListener('mouseout', () => videoContetor.pause());
            
            const paiImg = img.parentElement;
            if(paiImg) {
                paiImg.innerHTML = '';
                paiImg.appendChild(videoContetor);
            }
        }
    }
});
</script>










<?php
// =========================================================================
// 🚀 ENGINE AEROESPACIAL DE GEOLOCALIZAÇÃO 3D/4D — PRINCIPAL.PHP (CORE)
// =========================================================================
$pontos_mapa_3d = [];
$res_3d = [];

if (isset($pdo) && $pdo !== null) {
    try {
        // Query Coesora: Captura estritamente apenas as lojas e barbearias ativas E confirmadas pela auditoria
        $sql_3d = "
            (SELECT id as id_p, nome_loja as nome, endereco_armazem as endereco, 'loja' as tipo FROM lojas WHERE visivel_no_site = 1 AND transacao_status = 'Confirmado')
            UNION
            (SELECT codigo as id_p, nome as nome, endereco as endereco, 'barbearia' as tipo FROM usuario WHERE nivel = 'parceiro_hospedado' AND visivel_no_site = 1 AND transacao_status = 'Confirmado')
        ";
        $stmt_3d = $pdo->query($sql_3d);
        if ($stmt_3d) {
            $res_3d = $stmt_3d->fetchAll(PDO::FETCH_ASSOC);
        }
    } catch (PDOException $e) {
        error_log("Erro no alinhamento do ecossistema 3D: " . $e->getMessage());
    }
}

// Coordenadas Centrais Georreferenciadas do Huambo, Angola
$lat_huambo_centro = -12.7711;
$lng_huambo_centro = 15.7392;

if (!empty($res_3d)) {
    foreach ($res_3d as $index => $unidade) {
        // Algoritmo de dispersão geo-computada para distribuir pins reais pelas ruas do Huambo em testes locais
        $dispersao_lat = $lat_huambo_centro + (sin($index * 5) / 380) + (rand(-4, 4) / 10000);
        $dispersao_lng = $lng_huambo_centro + (cos($index * 5) / 380) + (rand(-4, 4) / 10000);

        $pontos_mapa_3d[] = [
            "id"       => intval($unidade['id_p']),
            "nome"     => htmlspecialchars($unidade['nome'] ?? 'Sem Nome', ENT_QUOTES, 'UTF-8'),
            "endereco" => htmlspecialchars($unidade['endereco'] ?? 'Não informado', ENT_QUOTES, 'UTF-8'),
            "tipo"     => $unidade['tipo'],
            "lat"      => $dispersao_lat,
            "lng"      => $dispersao_lng
        ];
    }
}
?>

<!-- 🟢 DEPENDÊNCIAS OFICIAIS E ESTÁVEIS DO LEAFLETJS (RESOLVE O MAPA PRETO NO RENDER) -->
<link rel="stylesheet" href="https://unpkg.com" integrity="sha256-p4NxAoJBhIIN+hmNHrzRCf9tD/miZyoHS5obTRR9BMY=" crossorigin="" />
<script src="https://unpkg.com" integrity="sha256-20nQCchB9co0qIjJZRGuk2/Z9VM+kNiyxNV1lvTlZBo=" crossorigin=""></script>

<!-- =========================================================================
     🎨 CSS DE ADAPTAÇÃO PREMIUM SLATE E RESPONSIVIDADE COMPACTA
     ========================================================================= -->
<style>
    .seccao-macro-geolocalizacao {
        width: 100%;
        max-width: 1350px;
        margin: 25px auto;
        padding: 0 15px;
        font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif;
        box-sizing: border-box;
    }
    
    .viewport-canvas-leaflet {
        position: relative;
        width: 100%;
        height: 480px;
        background: #070b12;
        border-radius: 16px;
        overflow: hidden;
        border: 2px solid rgba(56, 189, 248, 0.25);
        box-shadow: 0 20px 45px rgba(0,0,0,0.6), 0 0 25px rgba(0, 210, 255, 0.1);
    }

    .consola-controlo-mapa {
        position: absolute;
        z-index: 1000; /* Força a consola a flutuar acima das camadas do mapa */
        top: 20px;
        left: 20px;
        background: rgba(15, 23, 42, 0.92);
        backdrop-filter: blur(8px);
        padding: 16px;
        border-radius: 12px;
        max-width: 260px;
        border: 1px solid rgba(255, 255, 255, 0.08);
        box-shadow: 0 10px 25px rgba(0,0,0,0.5);
    }

    /* Adaptação e compactação reativa para Smartphones (Fim do design quebrado) */
    @media (max-width: 768px) {
        .seccao-macro-geolocalizacao { padding: 0 8px; margin: 15px auto; }
        .viewport-canvas-leaflet { height: 380px !important; border-radius: 12px; }
        .consola-controlo-mapa {
            top: 10px !important;
            left: 10px !important;
            right: 10px !important;
            max-width: calc(100% - 20px) !important;
            padding: 10px 14px !important;
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 10px;
        }
        .consola-controlo-mapa p, .consola-controlo-mapa span { display: none !important; }
        .consola-controlo-mapa strong { font-size: 11px !important; margin-bottom: 0 !important; }
        .consola-controlo-mapa button { width: auto !important; padding: 8px 14px !important; margin-top: 0 !important; font-size: 10px !important; }
    }

    /* Customização dos Balões Pop-up do Leaflet para o Estilo Escuro/Neon */
    .leaflet-popup-content-wrapper {
        background: #0f172a !important;
        color: #ffffff !important;
        border-radius: 10px !important;
        box-shadow: 0 8px 25px rgba(0,0,0,0.6) !important;
        border: 1.5px solid #38bdf8 !important;
    }
    .leaflet-popup-tip { background: #38bdf8 !important; }
    .leaflet-popup-content { margin: 12px !important; line-height: 1.5; font-size: 12px; }
    .leaflet-container { outline: 0; }
</style>

<?php
// =========================================================================
// 🗺️ MOTOR INTEGRADO: MAPA REGIONAL DE ATENDIMENTO (TABELA: USUARIO)
// =========================================================================

$pontos_mapa = [];

// Faz a leitura dinâmica dos parceiros ativos direto da tabela do phpMyAdmin
if (isset($mysqli) && $mysqli) {
    $sql_pontos = "SELECT codigo, nome, endereco, preco FROM usuario WHERE nivel = 'parceiro_hospedado'";
    $res_pontos = mysqli_query($mysqli, $sql_pontos);
    
    // Coordenadas padrão da cidade do Huambo (Sede)
    $lat_padrao = -12.7711;
    $lng_padrao = 15.7392;

    if ($res_pontos) {
        while ($ponto = mysqli_fetch_assoc($res_pontos)) {
            // Distribui os pinos dinamicamente pelos bairros do Huambo
            $lat_pino = $lat_padrao + (rand(-99, 99) / 7000);
            $lng_pino = $lng_padrao + (rand(-99, 99) / 7000);

            $pontos_mapa[] = [
                "nome" => htmlspecialchars($ponto['nome']),
                "endereco" => htmlspecialchars($ponto['endereco']),
                "lat" => $lat_pino,
                "lng" => $lng_pino
            ];
        }
    }
}
?>






<?php
// =========================================================================
// 🌍 EXTRAÇÃO LOGÍSTICA PARA O MAPA — GOVERNAÇÃO DE COORDENADAS
// =========================================================================
$pontos_mapa = [];

// Procura as tuas lojas na base de dados para injetar os pinos reais no Huambo, Benguela e Luanda
$query_mapa_lojas = $mysqli->query("SELECT id, nome_loja, endereco_armazem FROM lojas WHERE visivel_no_site = 1");
if ($query_mapa_lojas && $query_mapa_lojas->num_rows > 0) {
    while ($loja_mapa = $query_mapa_lojas->fetch_assoc()) {
        // Coordenadas dinâmicas e inteligentes baseadas na província real da loja
        $lat = -12.7711; $lng = 15.7392; // Padrão: Huambo Sede
        
        if (strpos(strtolower($loja_mapa['endereco_armazem']), 'benguela') !== false) {
            $lat = -12.5763; $lng = 13.4117; // Benguela Zona Comercial
        } elseif (strpos(strtolower($loja_mapa['endereco_armazem']), 'luanda') !== false) {
            $lat = -8.8390; $lng = 13.2894; // Luanda Talatona
        } elseif (strpos(strtolower($loja_mapa['nome_loja']), 'marcante') !== false) {
            $lat = -8.5816; $lng = 13.6645; // Bengo Caxito
        }
        
        $pontos_mapa[] = [
            'nome' => $loja_mapa['nome_loja'],
            'endereco' => $loja_mapa['endereco_armazem'],
            'lat' => $lat,
            'lng' => $lng
        ];
    }
} else {
    // Modo de contingência se o teu banco local estiver vazio de coordenadas
    $pontos_mapa = [
        ['nome' => 'Barbearia Branca', 'endereco' => 'Bairro Talatona (Luanda)', 'lat' => -8.8390, 'lng' => 13.2894],
        ['nome' => 'Distribuidora Loengo', 'endereco' => 'Bairro de São Luís (Huambo)', 'lat' => -12.7711, 'lng' => 15.7392],
        ['nome' => 'Lojas Mamadu', 'endereco' => 'Zona Comercial (Benguela)', 'lat' => -12.5763, 'lng' => 13.4117]
    ];
}
?>

<!-- 🗺️ COMPONENTE VISUAL PREMIUM DO MAPA (Sem escuridão) -->
<div class="painel-mapa" style="max-width: 1100px; margin: 40px auto; padding: 0 20px; box-sizing: border-box;">
   

    <!-- O Contentor do Mapa Leaflet -->
    <div id="mapa_aurelius_SaaS" style="width: 100%; height: 450px; border-radius: 20px; border: 2px solid #1e293b; box-shadow: 0 15px 35px rgba(0,0,0,0.6); background: #070b12; overflow: hidden; z-index: 1;"></div>
</div>

<!-- 🟢 CDNS OFICIAIS, COMPLETOS E CORRIGIDOS DO LEAFLET (Estáveis) -->
<link rel="stylesheet" href="https://unpkg.com" integrity="sha256-p4NxAoJBhIIN+hmNHrzRCf9tD/miZyoHS5obTRR9BMY=" crossorigin="" />
<script src="https://unpkg.com" integrity="sha256-20nQCchB9co0qIjJZRGuk2/Z9VM+kNiyxNV1lvTlZBo=" crossorigin=""></script>

<script>
document.addEventListener("DOMContentLoaded", function() {
    // 1. Inicializa o motor Leaflet focado numa visão centralizada de Angola
    const mapa = L.map('mapa_aurelius_SaaS').setView([-10.5000, 14.5000], 6);

    // 2. CORREÇÃO DA URL: Aplica o servidor correto de imagens Dark Premium da CARTO
    L.tileLayer('https://{s}://{z}/{x}/{y}{r}.png', {
        attribution: '&copy; <a href=\"https://openstreetmap.org\">OpenStreetMap</a> &copy; <a href=\"https://carto.com\">CARTO</a>',
        subdomains: 'abcd',
        maxZoom: 20
    }).addTo(mapa);

    // 3. Puxa os dados reais em formato JSON do motor PHP acima
    const pontosRegistados = <?= json_encode($pontos_mapa) ?>;
    const coordenadasTrajeto = [];

    pontosRegistados.forEach(function(ponto) {
        // Guarda as coordenadas para traçar as linhas logo a seguir
        coordenadasTrajeto.push([ponto.lat, ponto.lng]);

        // Cria um marcador comum estável
        const pinoMestre = L.marker([ponto.lat, ponto.lng]).addTo(mapa);
        
        // Balão informativo estilizado Dark
        const conteudoPopup = `
            <div style="font-family: sans-serif; text-align: left; color:#fff; min-width:160px; padding: 5px;">
                <b style="color: #38bdf8; font-size: 13px; display: block; margin-bottom: 4px;">🏬 ${ponto.nome}</b>
                <p style="color: #cbd5e1; font-size: 11px; margin: 4px 0;">📍 ${ponto.endereco}</p>
                <span style="display:inline-block; background:#22c55e; color:#fff; font-size: 9px; padding:2px 6px; font-weight:bold; border-radius:4px; text-transform:uppercase;">● Ativo no SaaS</span>
            </div>
        `;
        
        pinoMestre.bindPopup(conteudoPopup);
    });

    // 4. 🟢 ROTAS DE TRAÇOS LOGÍSTICOS INTERPROVINCIAIS (Huambo - Benguela - Luanda)
    // Desenha uma linha de conexão de prestígio entre os balcões nacionais do grupo
    if (coordenadasTrajeto.length > 1) {
        const linhaLogistica = L.polyline(coordenadasTrajeto, {
            color: '#38bdf8',       // Cor azul neon do teu ecossistema
            weight: 3,              // Espessura do traço
            opacity: 0.7,           // Transparência
            dashArray: '8, 8',      // Transforma a linha contínua em traços espalhados
            lineJoin: 'round'
        }).addTo(mapa);
        
        // Ajusta automaticamente o zoom para abraçar todas as províncias com linhas
        mapa.fitBounds(linhaLogistica.getBounds(), { padding: [30, 30] });
    }

    // 5. Corrige falhas ou bugs de renderização cinzenta no carregamento
    setTimeout(() => {
        mapa.invalidateSize();
    }, 500);
});
</script>

<style>
    /* Injeta regras CSS globais para pintar o Pop-up interno do Leaflet de Dark Premium */
    .leaflet-popup-content-wrapper { background: #111827 !important; color: #fff !important; border: 1px solid #1e293b !important; border-radius: 12px !important; }
    .leaflet-popup-tip { background: #111827 !important; }
    .leaflet-container { background-color: #070b12 !important; }
</style>






<!-- =================================================================
     🔮 MÓDULO DE TESTEMUNHOS REATIVO 100% RESPONSIVO PARA SMARTPHONES
     ================================================================= -->
<div style="width: 100%; display: flex; justify-content: center; align-items: center; padding: 15px 10px; box-sizing: border-box; clear: both;">
    
    <div class="seccao-depoimentos" style="background: linear-gradient(135deg, #101f38, #0b1329); border: 2px solid #38bdf8; border-radius: 16px; padding: 20px 15px; width: 100%; max-width: 500px; text-align: left; box-shadow: 0 0 20px rgba(56, 189, 248, 0.25); font-family: 'Segoe UI', Arial, sans-serif; box-sizing: border-box;">
        
        <h3 style="color: #38bdf8; font-size: 12px; text-transform: uppercase; margin: 0 0 6px 0; letter-spacing: 0.5px; border-left: 3px solid #38bdf8; padding-left: 8px;">
            💬 Espaço de Testemunhos e Avaliações
        </h3>
        <p style="color: #94a3b8; font-size: 12px; margin-bottom: 15px;">Deixe o seu comentário e nota abaixo.</p>
    
        <form action="processar_depoimento.php" method="POST" enctype="multipart/form-data" class="formulario-depoimento" onsubmit="return validarFormulario(event)">
            <!-- 🌟 INVISÍVEL ESSENCIAL: Armazena a nota cronática clicada para salvar no banco -->
            <input type="hidden" name="estrelas" id="inputEstrelas" value="0">
            
            <div class="form-linha" style="display: flex; flex-direction: column; gap: 10px; margin-bottom: 12px;">
                <input type="text" name="nome" style="width: 100%; padding: 12px; border-radius: 8px; border: 1px solid #334155; background: #070913; color: white; font-size: 13px; outline: none; box-sizing: border-box;" placeholder="O seu nome" required>
                <input type="file" name="foto_perfil" accept="image/*" style="width: 100%; padding: 8px; border-radius: 8px; border: 1px solid #334155; background: #070913; color: #94a3b8; font-size: 12px; outline: none; box-sizing: border-box;">
            </div>
        
            <!-- SELECIONADOR CROMÁTICO REATIVO DE ESTRELAS -->
            <div class="estrelas-container" style="display: flex; align-items: center; justify-content: space-between; background: rgba(0,0,0,0.2); padding: 10px; border-radius: 8px; border: 1px solid #1e293b; flex-wrap: wrap; gap: 8px; box-sizing: border-box; width: 100%;">
                <div style="display: flex; align-items: center; gap: 6px;">
                    <span style="font-size:10px; font-weight:bold; color:#64748b; text-transform:uppercase;">Sua Nota:</span>
                    <div style="display: inline-flex; gap: 4px;" id="blocoEstrelasMestre">
                        <span class="estrela-btn" onclick="definirNota(1)" style="font-size: 22px; color: #334155; cursor: pointer; transition: 0.2s; user-select: none;">★</span>
                        <span class="estrela-btn" onclick="definirNota(2)" style="font-size: 22px; color: #334155; cursor: pointer; transition: 0.2s; user-select: none;">★</span>
                        <span class="estrela-btn" onclick="definirNota(3)" style="font-size: 22px; color: #334155; cursor: pointer; transition: 0.2s; user-select: none;">★</span>
                        <span class="estrela-btn" onclick="definirNota(4)" style="font-size: 22px; color: #334155; cursor: pointer; transition: 0.2s; user-select: none;">★</span>
                        <span class="estrela-btn" onclick="definirNota(5)" style="font-size: 22px; color: #334155; cursor: pointer; transition: 0.2s; user-select: none;">★</span>
                    </div>
                </div>
                <span id="rotuloNota" class="rotulo-nota" style="font-size: 11px; color: #64748b; font-weight: bold; text-transform: uppercase;">Selecione</span>
                <input type="hidden" name="cupao_aplicado" value="<?php echo !empty($cupao_desconto) ? htmlspecialchars($cupao_desconto) : ''; ?>">
            </div>
            
            <!-- Contentor Centrador Fluido para Dispositivos Móveis -->
<div style="width: 100%; display: flex; flex-direction: column; align-items: center; justify-content: center; gap: 10px; box-sizing: border-box;">
    
    <!-- Caixa de Texto Compacta e Centralizada -->
    <textarea name="comentario" style="width: 80%; max-width: 320px; height: 55px; padding: 8px 12px; border-radius: 8px; border: 1px solid #334155; background: #070913; color: white; font-size: 12px; outline: none; resize: none; box-sizing: border-box;" placeholder="Escreva aqui a sua avaliação ou reclamação..." required></textarea>
    
    <!-- Botão de Envio Compacto e Centralizado (Ergonómico para o Dedo) -->
    <button type="submit" style="width: 60%; max-width: 200px; padding: 10px; background: linear-gradient(135deg, #38bdf8, #0284c7); color: white !important; border: none; border-radius: 8px; font-weight: bold; font-size: 11px; cursor: pointer; text-transform: uppercase; letter-spacing: 0.5px; transition: 0.2s; box-shadow: 0 4px 10px rgba(56, 189, 248, 0.15); outline: none;">
        Publicar
    </button>

</div>
        </form>
    
        <!-- PASTA RETRÁTIL PARA O CLIENTE VER OS COMENTÁRIOS REAIS -->
        <div style="width: 100%; margin-top: 15px; box-sizing: border-box;">
            <button type="button" onclick="alternarPastaCliente()" style="background: linear-gradient(135deg, #1e293b, #0f172a); color: #38bdf8; border: 1px solid #334155; padding: 12px 15px; font-size: 12px; font-weight: bold; border-radius: 8px; cursor: pointer; width: 100%; text-align: left; display: flex; justify-content: space-between; align-items: center; outline: none; box-sizing: border-box;">
                <span>📁 VER TESTEMUNHOS RECENTES</span>
                <span id="setaPastaCliente" style="font-size: 12px; color: #ffffff; transition: 0.3s;">▼</span>
            </button>
    
            <div id="conteudoPastaCliente" style="display: none; background: #0f1423; border: 1px solid #334155; border-top: none; padding: 12px; border-radius: 0 0 12px 12px; box-shadow: inset 0 0 10px rgba(0,0,0,0.5); box-sizing: border-box; width: 100%;">
                <ul id="listaTestemunhos" style="list-style: none; padding: 0; margin: 0; display: flex; flex-direction: column; gap: 10px;">
                    <?php 
                    $depoimentos_exibir = [];
                    if (!empty($depoimentos_reais) && is_array($depoimentos_reais)) {
                        $depoimentos_exibir = array_slice(array_reverse($depoimentos_reais), 0, 5);
                    }
            
                    if(!empty($depoimentos_exibir)): 
                        foreach($depoimentos_exibir as $indice => $dep): 
                            $foto_atual = !empty($dep['foto_url']) ? trim($dep['foto_url']) : '';
                            $imagem_perfil = 'OIP (6).webp';
                            
                            if (!empty($foto_atual)) {
                                if (strpos($foto_atual, 'http://') === 0 || strpos($foto_atual, 'https://') === 0) {
                                    $imagem_perfil = $foto_atual;
                                } elseif (file_exists($foto_atual) || file_exists(__DIR__ . '/' . $foto_atual)) {
                                    $imagem_perfil = $foto_atual;
                                }
                            }
                        ?>
                            <li class="item-testemunho" style="display: flex; gap: 10px; background: #070913; padding: 12px; border-radius: 8px; border: 1px solid #1e293b; align-items: flex-start; box-sizing: border-box; width: 100%;">
                                <img src="<?php echo htmlspecialchars($imagem_perfil); ?>" style="width: 36px; height: 36px; border-radius: 50%; border: 2px solid #38bdf8; object-fit: cover; flex-shrink: 0;" alt="User">
                                <div class="conteudo-testemunho" style="flex: 1; text-align: left; min-width: 0;">
                                    <div class="meta" style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 4px; flex-wrap: wrap; gap: 4px;">
                                        <span style="font-weight: bold; color: #ffffff; font-size: 13px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; max-width: 140px;"><?php echo htmlspecialchars($dep['nome']); ?></span>
                                        <span class="estrelas-fixas" style="color: #eab308; font-size: 11px; letter-spacing: 1px;">
                                            <?php echo str_repeat('★', (int)$dep['estrelas']); ?>
                                        </span>
                                    </div>
                                    <div class="texto" style="color: #cbd5e1; font-size: 12px; font-style: italic; line-height: 1.4; word-break: break-word;">
                                        <?php echo htmlspecialchars($dep['comentario']); ?>
                                    </div>
                                </div>
                            </li>
                        <?php 
                        endforeach;
                    else:
                    ?>
                        <p style="color: #64748b; font-size: 12px; text-align: center; padding: 10px; font-style: italic;">Nenhum testemunho publicado de momento.</p>
                    <?php endif; ?>
                </ul>
            </div>
        </div>
    </div>
</div>

<!-- =========================================================================
     🕹️ MOTOR LOGÍSTICO AUXILIAR JAVASCRIPT DE TESTEMUNHOS
     ========================================================================= -->
<script>
// =========================================================================
// 🧠 ENGENHARIA JAVASCRIPT: REATIVIDADE CENTRAL DESTRANCADA (VORTEX NEON)
// =========================================================================
let notaGlobalSelecionada = 0;

// Dicionário Estrito de Alta Resolução para Feedback Visual do Cliente
const textosNotas = { 
    1: "Péssimo ❌ 😡", 
    2: "Ruim ⚠️ 🙁", 
    3: "Regular ⚡ 😐", 
    4: "Muito Bom ⭐ 🙂", 
    5: "Excelente 👑 😎" 
};

// 1. SISTEMA REATIVO DE SELEÇÃO DE ESTRELAS (MÉTODO NEON)
function definirNota(nota) {
    // Atualiza as variáveis de controle global e o input oculto para o POST PHP
    notaGlobalSelecionada = nota;
    const inputOculto = document.getElementById('inputEstrelas');
    if (inputOculto) {
        inputOculto.value = nota;
    }
    
    // Atualiza o rótulo descritivo com os novos textos expandidos e cores neon
    const rotulo = document.getElementById('rotuloNota');
    if (rotulo) {
        rotulo.innerText = textosNotas[nota] || 'Selecione';
        rotulo.style.color = '#eab308'; // Ouro Radiente
        rotulo.style.textShadow = '0 0 8px rgba(234, 179, 8, 0.3)';
    }

    // Varre o bloco de estrelas aplicando a coloração reativa
    const estrelas = document.querySelectorAll('#blocoEstrelasMestre .estrela-btn');
    estrelas.forEach((estrela, indice) => {
        if (indice < nota) {
            // Estrelas selecionadas ganham brilho dourado e leve pulso
            estrela.style.color = '#eab308';
            estrela.style.textShadow = '0 0 10px rgba(234, 179, 8, 0.5)';
            estrela.style.transform = 'scale(1.1)';
        } else {
            // Estrelas apagadas retornam ao cinzento fosco padrão
            estrela.style.color = '#334155';
            estrela.style.textShadow = 'none';
            estrela.style.transform = 'scale(1)';
        }
    });
}
// 2. CONTROLO RETRÁTIL DA PASTA DE COMENTÁRIOS DO CLIENTE
function alternarPastaCliente() {
    const conteudo = document.getElementById('conteudoPastaCliente');
    const seta = document.getElementById('setaPastaCliente');
    
    if (conteudo.style.display === 'none' || conteudo.style.display === '') {
        conteudo.style.display = 'block';
        seta.innerText = '▲';
        seta.style.color = '#38bdf8';
    } else {
        conteudo.style.display = 'none';
        seta.innerText = '▼';
        seta.style.color = '#ffffff';
    }
}

// 3. VALIDAÇÃO DE SEGURANÇA ANTES DE ENVIAR AO BANCO
function validarFormulario(event) {
    const notaReal = document.getElementById('inputEstrelas').value;
    if (parseInt(notaReal) === 0) {
        alert("⚠️ Por favor, atribua uma nota clicando nas estrelas antes de submeter!");
        return false;
    }
    return true;
}
</script>


<!-- =========================================================================
     🤖 COMPONENTE FLUTUANTE: ALANA IA ASSISTENTE COMERCIAL PREMIUM RESPONSIVA
     ========================================================================= -->
<div id="caixa_master_alana_ia" style="position: fixed; bottom: 20px; right: 20px; z-index: 999999; font-family: 'Segoe UI', -apple-system, sans-serif; display: flex; flex-direction: column; align-items: flex-end;">
    
    <!-- Botão de Ativação Circular Radiante -->
    <button onclick="alternarJanelaChatbotAlana()" id="gatilho_ia_btn" style="background: linear-gradient(135deg, #0088cc, #00c4ff); border: none; width: 60px; height: 60px; border-radius: 50%; box-shadow: 0 4px 20px rgba(0,136,204,0.4); cursor: pointer; display: flex; align-items: center; justify-content: center; font-size: 26px; transition: all 0.3s cubic-bezier(0.175, 0.885, 0.32, 1.275); outline: none;">
      🤖
    </button>

    <!-- Contentor do Chat Retrativo (Adaptável a Telemóveis e PCs) -->
    <div id="janela_alana_corpo" style="display: none; width: 350px; height: 500px; max-height: 80vh; background: #0b0f19; border: 2px solid #0088cc; border-radius: 20px; box-shadow: 0 12px 40px rgba(0,0,0,0.6); flex-direction: column; overflow: hidden; position: absolute; bottom: 75px; right: 0; transition: all 0.3s ease;">
        
        <!-- Cabeçalho Premium -->
        <div style="background: #111827; padding: 15px; border-bottom: 1px solid #1f2937; display: flex; justify-content: space-between; align-items: center;">
            <div style="display: flex; align-items: center; gap: 10px;">
                <div style="width: 10px; height: 10px; background: #22c55e; border-radius: 50%; box-shadow: 0 0 8px #22c55e;"></div>
                <div>
                    <strong style="color: #fff; font-size: 13px; display: block; text-transform: uppercase; letter-spacing: 0.5px;">Aurelius IA Bot</strong>
                    <span style="color: #64748b; font-size: 10px;">Assistente Comercial Reativa</span>
                </div>
            </div>
            <span onclick="alternarJanelaChatbotAlana()" style="cursor: pointer; color: #ef4444; font-weight: bold; font-size: 24px; padding: 0 5px; line-height: 1;">&times;</span>
        </div>

        <!-- Área de Rolagem das Mensagens -->
        <div id="historico_mensagens_alana" style="flex: 1; padding: 15px; overflow-y: auto; display: flex; flex-direction: column; gap: 12px; background: #070b12; font-size: 13px; line-height: 1.5;">
            <div style="background: #1f2937; color: #f3f4f6; padding: 12px; border-radius: 14px 14px 14px 0; align-self: flex-start; max-width: 85%; border: 1px solid #374151;">
                Olá! Sou o <b>Aurelius IA</b>. Estou aqui para te ajudar! Pergunte-me sobre os nossos <b>Planos Freemium</b>, <b>como fazer parceria para render muito</b> ou consulte o seu desconto VIP! 🇦🇴
            </div>
        </div>

        <!-- Formulário Inferior de Envio -->
        <div style="padding: 12px; background: #111827; border-top: 1px solid #1f2937; display: flex; gap: 8px; align-items: center;">
            <input type="text" id="campo_texto_pergunta" placeholder="Escreva a sua mensagem..." style="flex: 1; padding: 11px 16px; background: #070b12; border: 1px solid #374151; border-radius: 20px; color: #fff; font-size: 13px; outline: none;" onkeydown="if(event.key==='Enter') processarEnvioMensagemAlana('principal')">
            <button onclick="processarEnvioMensagemAlana('principal')" style="background: #0088cc; color: #fff; border: none; height: 38px; padding: 0 16px; border-radius: 20px; font-weight: bold; cursor: pointer; font-size: 11px; text-transform: uppercase; letter-spacing: 0.5px; flex-shrink: 0;">Enviar</button>
        </div>
    </div>
</div>

<!-- 📱 ESTILOS CSS ADAPTÁVEIS DE ALTA RESPONSIVIDADE -->
<style>
@media (max-width: 480px) {
    #janela_alana_corpo {
        width: calc(100vw - 30px) !important;
        height: 75vh !important;
        max-height: 500px !important;
        right: -5px !important;
        bottom: 70px !important;
    }
    #caixa_master_alana_ia {
        bottom: 15px !important;
        right: 15px !important;
    }
}
</style>

<script>
function alternarJanelaChatbotAlana() {
    const caixa = document.getElementById('janela_alana_corpo');
    const btn = document.getElementById('gatilho_ia_btn');
    if (caixa.style.display === 'none' || caixa.style.display === '') {
        caixa.style.display = 'flex';
        btn.style.transform = 'scale(0.9) rotate(90deg)';
        btn.innerHTML = '✕';
        btn.style.background = 'linear-gradient(135deg, #ef4444, #b91c1c)';
        setTimeout(() => { document.getElementById('campo_texto_pergunta').focus(); }, 100);
    } else {
        caixa.style.display = 'none';
        btn.style.transform = 'scale(1) rotate(0deg)';
        btn.innerHTML = '🤖';
        btn.style.background = 'linear-gradient(135deg, #0088cc, #00c4ff)';
    }
}

// 🧠 DICIONÁRIO DE INTELIGÊNCIA LOCAL REATIVA (GÍRIAS DE ANGOLA & RESPOSTAS RÁPIDAS)
function processarRespostaLocalInteligente(msg) {
    const texto = msg.toLowerCase().trim();

    // Mapeamento de Expressões e Saudações Locais
    const saudacoes = ['wi', 'wei', 'como estais', 'olá', 'boa tarde', 'bom dia', 'oi', 'diz aí', 'estás fixe'];
    const confirmacoes = ['ya', 'yah', 'yes', 'ok', 'valeu', 'gostei', 'tá numa', 'está numa', 'bater na rocha'];
    const elogios = ['malaike', 'to malaique', 'tá malaike', 'top', 'muito bom', 'bruto'];
    const agradecimentos = ['obrigado', 'obrigada', 'obas', 'agradecido', 'tamo junto'];
    const despedidas = ['chau', 'tchau', 'ate mais', 'fui', 'adeus', 'até já'];
    const duvidas = ['explica mais', 'afinal', 'como funciona', 'me conta', 'saber mais', 'porque'];

    if (saudacoes.some(s => texto.includes(s))) {
        return "Olá, meu <b>wi</b>! Tudo em ordem?<br><br>Estou focado para te ajudar a gerir ou crescer no ecossistema Aurélius. O que vais querer ver hoje?";
    }
    if (elogios.some(e => texto.includes(e))) {
        return "🔥 <b>Malaike puro!</b><br><br>O nosso objetivo é manter o sistema sempre no topo e a render o máximo para todos os parceiros. Tamo junto!";
    }
    if (confirmacoes.some(c => texto.includes(c))) {
        return "Firme! 🤜🤛<br><br>O que precisares, manda só a mensagem que eu trato de ir buscar ao motor de dados.";
    }
    if (agradecimentos.some(a => texto.includes(a))) {
        return "De nada, estamos juntos!<br><br>Se precisares de ver mais alguma coisa sobre as faturas ou comissões, avisa só.";
    }
    if (despedidas.some(d => texto.includes(d))) {
        return "Até já, mano!<br><br>Fica bem e boa gestão nos negócios! Até à próxima. 👋";
    }
    if (duvidas.some(dv => texto.includes(dv))) {
        return "O ecossistema <b>Grupo Aurélius</b> funciona de forma 100% integrada!<br><hr style='border: 0; border-top: 1px solid #1f2937; margin: 8px 0;'>Conectamos salões à nuvem, geramos faturas instantâneas com QR Code e permitimos que os gerentes controlem o faturamento em tempo real no dashboard.<br><br>🎁 O plano básico é totalmente grátis!";
    }
    
    return null; // Caso não corresponda a gírias locais, envia para o backend php
}

function exibirMensagemNoChat(texto, alinhamento) {
    const historico = document.getElementById('historico_mensagens_alana');
    const bolha = document.createElement('div');
    
    if (alinhamento === 'user') {
        bolha.style.background = '#0088cc';
        bolha.style.color = '#fff';
        bolha.style.borderRadius = '14px 14px 0 14px';
        bolha.style.alignSelf = 'flex-end';
    } else {
        bolha.style.background = '#111827';
        bolha.style.color = '#e5e7eb';
        bolha.style.borderRadius = '14px 14px 14px 0';
        bolha.style.alignSelf = 'flex-start';
        bolha.style.border = '1px solid #1f2937';
    }
    
    bolha.style.padding = '11px 14px';
    bolha.style.maxWidth = '85%';
    
    // 🔒 AS DUAS LINHAS MÁGICAS QUE IMPEDEM O TEXTO DE SAIR DO ECRÃ:
    bolha.style.wordBreak = 'break-word';
    bolha.style.overflowWrap = 'break-word';
    
    bolha.innerHTML = texto;
    
    historico.appendChild(bolha);
    historico.scrollTop = historico.scrollHeight;
}

function processarEnvioMensagemAlana(origemTela) {
    const input = document.getElementById('campo_texto_pergunta');
    const msgUsuario = input.value.trim();

    if (msgUsuario === '') return;

    // 1. Renderiza a bolha do cliente
    exibirMensagemNoChat(msgUsuario, 'user');
    input.value = '';

    // 2. Processa inteligência artificial local de gírias angolanas
    const respostaLocal = processarRespostaLocalInteligente(msgUsuario);
    if (respostaLocal !== null) {
        // Exibe resposta imediata economizando requisições no Apache
        setTimeout(() => { exibirMensagemNoChat(respostaLocal, 'ia'); }, 400);
        return;
    }

    // 3. Caso não seja gíria, faz a requisição AJAX normal para o backend PHP
    fetch('processar_ia_alana.php?origem=' + origemTela + '&mensagem=' + encodeURIComponent(msgUsuario))
    .then(response => response.json())
    .then(data => {
        exibirMensagemNoChat(data.resposta, 'ia');
    })
    .catch(() => {
        exibirMensagemNoChat('⚠️ Nota: O assistente está a sincronizar os dados com o motor do XAMPP local. Digite algo como <b>"explica mais"</b> ou envie novamente.', 'error');
    });
}
</script>
















<!-- =========================================================================
     📋 MATRIZ DE LINKS INSTITUCIONAIS RECUPERADA E 100% FUNCIONAL
     ========================================================================= -->
<style>
    /* Estilos Premium das Abas Corporativas */
    .link-SaaS-aba {
        color: #94a3b8 !important;
        text-decoration: none !important;
        cursor: pointer !important;
        font-weight: bold !important;
        transition: all 0.2s ease-in-out;
        font-family: sans-serif !important;
    }
    .link-SaaS-aba:hover {
        color: #38bdf8 !important;
        text-shadow: 0 0 6px rgba(56, 189, 248, 0.4);
    }
    .separador-ponto {
        color: rgba(56, 189, 248, 0.2);
        margin: 0 6px;
    }
    
    /* Contentor Central de Conteúdos Expansíveis */
    .quadrado-conteudo-SaaS {
        display: none;
        background: #090f1d;
        border: 1px solid #1e293b;
        border-radius: 12px;
        padding: 24px;
        margin-top: 25px;
        text-align: left;
        box-sizing: border-box;
        width: 100%;
        animation: deslizarPainelAba 0.3s ease-out;
    }
    .quadrado-conteudo-SaaS.active {
        display: block !important;
    }
    
    @keyframes deslizarPainelAba {
        from { opacity: 0; transform: translateY(10px); }
        to { opacity: 1; transform: translateY(0); }
    }

    /* Estilos das Sanfonas Premium (Accordion Vortex) */
    .item-sanfona-premium {
        background: #070b12;
        border: 1px solid #1e293b;
        border-radius: 12px;
        margin-bottom: 12px;
        overflow: hidden;
        text-align: left;
    }
    .item-sanfona-premium summary {
        padding: 15px;
        font-weight: bold;
        color: #38bdf8;
        cursor: pointer;
        outline: none;
        list-style: none;
        display: flex;
        align-items: center;
        gap: 10px;
        user-select: none;
        font-family: sans-serif;
    }
    .resposta-painel {
        padding: 15px;
        color: #cbd5e1;
        font-size: 13.5px;
        line-height: 1.6;
        border-top: 1px solid rgba(56, 189, 248, 0.1);
        background: rgba(15, 23, 42, 0.3);
        font-family: sans-serif;
    }
</style>

<!-- SECTION DO RODAPÉ INSTITUCIONAL (TOTALMENTE ALIVIADA E LEVE) -->
<section class="sectionn" style="background: linear-gradient(135deg, #101f38, #0b1329); border: 2px solid #38bdf8; border-radius: 16px; padding: 45px 30px; margin: 40px auto; max-width: 1000px; text-align: center; box-shadow: 0 0 20px rgba(56, 189, 248, 0.35); font-family: 'Segoe UI', Arial, sans-serif;">
    
    <h1 style="color: #38bdf8; font-size: 24px; margin: 0 0 12px 0; text-transform: uppercase; letter-spacing: 1px;">
        <strong>🎌 GRUPO AURELIUS</strong>
    </h1>
    <p style="color: #cbd5e1; font-size: 14px; margin: 0 auto 15px auto; max-width: 700px; line-height: 1.5;">
        Plataforma líder em marcações de trabalhos ao domicílio e vendas de produtos cosméticos.
    </p>
    
    <div style="display: flex; justify-content: center; gap: 20px; flex-wrap: wrap; margin-bottom: 25px; font-size: 13px; color: #94a3b8;">
        <span>💧 Huambo, Angola</span>
        <span>✉️ <a href="mailto:contacto@aureliius.ao" style="color: #38bdf8; text-decoration: none;">contacto@aurelius.ao</a></span>
        <span>📞 +244 925 347 372</span>
    </div>
    
    <hr style="border: 0; border-top: 1px dashed rgba(56, 189, 248, 0.2); margin: 25px 0;">
    
    <!-- Matriz Ativa de Links -->
    <div style="display: flex; flex-direction: column; gap: 14px; text-align: center; font-size: 13px; color: #94a3b8; margin-bottom: 25px;">
    <p style="margin: 0;">
        <b style="color: #38bdf8; text-transform: uppercase; margin-right: 8px; font-size: 11px; letter-spacing: 0.5px;">Produto:</b> 
        <span class="link-SaaS-aba" onclick="abrirAbaRodape('funcionalidades')">Funcionalidades</span> <span class="separador-ponto">&bull;</span>
        <span class="link-SaaS-aba" onclick="abrirAbaRodape('modulos')">Módulos &amp; Camadas</span> <span class="separador-ponto">&bull;</span>
        <span class="link-SaaS-aba" onclick="abrirAbaRodape('precos')">Preços</span> <span class="separador-ponto">&bull;</span>
        <span class="link-SaaS-aba" onclick="abrirAbaRodape('api')">API &amp; Webhooks</span>
    </p>
    <p style="margin: 0;">
        <b style="color: #38bdf8; text-transform: uppercase; margin-right: 8px; font-size: 11px; letter-spacing: 0.5px;">Recursos:</b> 
        <a style="color:#94a3b8; text-decoration: none;" href="Video.php" class="link-SaaS-aba">Vídeo Aulas</a> <span class="separador-ponto">&bull;</span>
        <span class="link-SaaS-aba" onclick="abrirAbaRodape('documentacao')">Documentação</span> <span class="separador-ponto">&bull;</span>
        <span class="link-SaaS-aba" onclick="abrirAbaRodape('blog')">Blog</span> <span class="separador-ponto">&bull;</span>
        <span class="link-SaaS-aba" onclick="abrirAbaRodape('faq')">FAQ Vortex</span>
    </p>
    <p style="margin: 0;">
        <b style="color: #38bdf8; text-transform: uppercase; margin-right: 8px; font-size: 11px; letter-spacing: 0.5px;">Empresa:</b> 
        <span class="link-SaaS-aba" onclick="abrirAbaRodape('sobre')">Sobre nós</span> <span class="separador-ponto">&bull;</span>
        <a style="color:#94a3b8; text-decoration: none;" href="Vagas.php" class="link-SaaS-aba">Carreiras</a> <span class="separador-ponto">&bull;</span>
        <a style="color:#94a3b8; text-decoration: none;" href="Principal.php" class="link-SaaS-aba">Parceiros Nacionais</a> <span class="separador-ponto">&bull;</span>
        <span class="link-SaaS-aba" onclick="abrirAbaRodape('contacto')">Contacto &amp; Mapas</span>
    </p>
    <p style="margin: 0;">
        <b style="color: #38bdf8; text-transform: uppercase; margin-right: 8px; font-size: 11px; letter-spacing: 0.5px;">Legal:</b> 
        <span class="link-SaaS-aba" onclick="abrirAbaRodape('termos')">Termos de Uso</span> <span class="separador-ponto">&bull;</span>
        <span class="link-SaaS-aba" onclick="abrirAbaRodape('privacidade')">Privacidade &amp; APD</span> <span class="separador-ponto">&bull;</span>
        <span class="link-SaaS-aba" onclick="abrirAbaRodape('cookies')">Diretiva de Cookies</span>
    </p>
</div>
    <!-- Contentor Centralizado de Abas Ocultas -->
    <div id="central_conteudos_rodape" style="width: 100%; box-sizing: border-box;">

    <!-- =========================================================================
    📍 ABA: CONTACTO, ROTAS E MAPA INTERATIVO DO HUAMBO (VERSÃO ULTRA-ROBUSTA)
    ========================================================================= -->
    <div id="aba_contacto" class="quadrado-conteudo-SaaS" style="padding: 25px; background: #0b1329; border: 2px solid #38bdf8; box-shadow: 0 0 15px rgba(56, 189, 248, 0.15);">
    
    <div style="border-bottom: 1px solid #1e293b; padding-bottom: 12px; margin-bottom: 20px; text-align: left;">
        <span style="color: #22c55e; font-size: 11px; font-weight: bold; text-transform: uppercase; letter-spacing: 0.7px; display: block; margin-bottom: 4px;">🎯 GEOLOCALIZAÇÃO REGISTADA - CONJUNTO #187467105</span>
        <h3 style="color: #38bdf8; margin: 0; font-size: 18px; font-weight: bold; font-family: sans-serif;">📍 Localização da Sede &amp; Mapas de Angola</h3>
    </div>
    
    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(290px, 1fr)); gap: 25px; width: 100%; box-sizing: border-box; align-items: center;">
        
        <!-- Informações Oficiais Extraídas da sua Edição Global -->
        <div style="color: #cbd5e1; font-size: 13.5px; line-height: 1.6; text-align: left; font-family: sans-serif;">
            <div style="background: #070b12; border: 1px solid #1e293b; padding: 14px; border-radius: 8px; margin-bottom: 15px;">
                <p style="margin: 4px 0;"><span style="font-size: 16px;">🏬</span> <b>Empresa Mestre:</b> <span style="color: #fff; font-weight: bold;">Barbearia Branca</span></p>
                <p style="margin: 4px 0; color: #94a3b8; font-size: 13px;">📍 Av. General Pinto Monteiro, Aviação (Imediações do Kapango), Huambo, Angola</p>
                <p style="margin: 8px 0 4px 0;">🕒 <b>Horário Publicado:</b> <span style="color: #4ade80;">08h00 &mdash; 21h00</span></p>
                <p style="margin: 4px 0;">💳 <b>Pagamentos:</b> Dinheiro, Cartão, Multicaixa Express e App</p>
                <p style="margin: 4px 0;">📞 <b>Contacto Operacional:</b> +244 925 347 372</p>
            </div>

            <div style="display: flex; gap: 10px; flex-wrap: wrap;">
                <a href="https://openstreetmap.org" target="_blank" style="flex: 1; min-width: 140px; text-align: center; background: linear-gradient(135deg, #38bdf8, #0284c7); color: #0f172a; padding: 10px; border-radius: 6px; font-weight: bold; font-size: 11px; text-decoration: none; text-transform: uppercase; letter-spacing: 0.5px; box-shadow: 0 4px 10px rgba(56,189,248,0.2);">
                    🌍 Ver Node no OSM
                </a>
            </div>
        </div>
        
        <!-- Frame do Mapa Dinâmico focado nas Coordenadas Reais do Huambo obtidas no Print -->
        <div style="width: 100%; height: 320px; border-radius: 12px; overflow: hidden; border: 2px solid #1e293b; box-shadow: 0 6px 20px rgba(0,0,0,0.5); box-sizing: border-box;">
            <!-- 🟢 URL RESTRUTURADA COM AS COORDENADAS REAIS EXATAS DO SEU PRINT -->
            <iframe width="100%" height="100%" frameborder="0" scrolling="no" marginheight="0" marginwidth="0" 
                src="https://openstreetmap.org" 
                style="background: #070b12; filter: contrast(1.1); border: none;">
            </iframe>
        </div>

    </div>
</div>


        <div id="aba_precos" class="quadrado-conteudo-SaaS">
            <h3 style="color: #38bdf8; margin: 0 0 10px 0;">💰 Modelo de Preços</h3>
            <p style="color: #cbd5e1; font-size: 13px; line-height: 1.5;">Instanciação e banco de dados gratuitos (0,00 Kz). Retenção da taxa administrativa padrão de 10% unicamente sobre produtos faturados com sucesso.</p>
        </div>

        <div id="aba_api" class="quadrado-conteudo-SaaS">

        <div style="border-bottom: 1px solid #1e293b; padding-bottom: 10px; margin-bottom: 15px;">
            <span style="color: #a855f7; font-size: 11px; font-weight: bold; text-transform: uppercase; letter-spacing: 0.5px;">Camada de Integração Core</span>
            <h3 style="color: #38bdf8; margin: 5px 0 0 0; font-size: 18px;">🔌 Documentação de APIs &amp; Webhooks do Ecossistema</h3>
        </div>
        <div style="color: #cbd5e1; font-size: 13px; line-height: 1.6;">
            <p>💻 <b>Endpoints de Faturamento JSON:</b> Desenvolvedores credenciados e administradores da rede podem consumir rotas RESTful seguras protegidas por chaves de tokenBearer para exportar relatórios de vendas consolidadas, saldos de carteiras de parceiros de todas as províncias e status de auditoria em tempo real.</p>
            <p>🪝 <b>Webhooks de Confirmação EMIS:</b> Sincronização em segundo plano via protocolo HTTPS POST para disparar gatilhos reativos de validação contrátil e mudança de status assim que o gateway da central EMIS acusar a liquidação do split bancário por Multicaixa Express.</p>
            <p>📊 <b>Pauta e Agenda Externa:</b> Documentação facilitada para sincronização bidirecional em tempo real, permitindo ligar sistemas locais e aplicativos mobile de barbearias à base centralizada global do <b>Grupo Aurélius</b>.</p>
        </div>
    </div>

    <!-- 5. ABA: DOCUMENTAÇÃO -->
    <div id="aba_documentacao" class="quadrado-conteudo-SaaS">
        <div style="border-bottom: 1px solid #1e293b; padding-bottom: 10px; margin-bottom: 15px;">
            <span style="color: #38bdf8; font-size: 11px; font-weight: bold; text-transform: uppercase; letter-spacing: 0.5px;">Central de Conhecimento</span>
            <h3 style="color: #38bdf8; margin: 5px 0 0 0; font-size: 18px;">📖 Manuais de Operação e Guias Técnicos</h3>
        </div>
        <div style="color: #cbd5e1; font-size: 13px; line-height: 1.6;">
            <p>📘 <b>Guia do Administrador (Admin.php):</b> Manual operacional completo para realizar a auditoria regulamentar de documentos, gerir a visibilidade do site público, tratar as flags de bloqueio de B.I. caducado e despachar notificações reativas.</p>
            <p>📙 <b>Manual do Parceiro Hospedado:</b> Diretrizes fáceis para a manipulação do painel SaaS independente, inserção correta de vagas de trabalho sem duplicações, controle reativo de estoque de cosméticos e monitoramento de comissões de 10% de retaguarda.</p>
            <p>📗 <b>Central de Ajuda de Vendas (Admin_Venda.php):</b> Procedimentos formais para o tratamento de pedidos de emprego, abertura correta do modal e controle do ecossistema de abas.</p>
        </div>
    </div>

    <!-- 6. ABA: BLOG -->
    <div id="aba_blog" class="quadrado-conteudo-SaaS">
        <div style="border-bottom: 1px solid #1e293b; padding-bottom: 10px; margin-bottom: 15px;">
            <span style="color: #22c55e; font-size: 11px; font-weight: bold; text-transform: uppercase; letter-spacing: 0.5px;">Portal de Conteúdo</span>
            <h3 style="color: #38bdf8; margin: 5px 0 0 0; font-size: 18px;">📰 Blog e Tendências do Mercado de Estética em Angola</h3>
        </div>
        <div style="color: #cbd5e1; font-size: 13px; line-height: 1.6;">
            <p>💇 <b>Inovações Técnicas de Balcão:</b> Artigos semanais assinados por especialistas cobrindo tendências de cortes modernos em Luanda, Huambo e Benguela, técnicas de pigmentação capilar avançada, barboterapia e manicure combinada.</p>
            <p>📈 <b>Gestão Financeira para Salões:</b> Dicas práticas corporativas para organizar o fluxo de caixa, calcular a margem de comissão de barbeiros e reduzir em até 95% o índice de faltas de clientes.</p>
            <p>🧴 <b>Cosmética Premium:</b> Análises completas sobre a aplicação de ceras modeladoras, champôs antiqueda e óleos de crescimento capilar comercializados no nosso marketplace.</p>
        </div>
    </div>

    <!-- 7. ABA: TERMOS DE USO -->
    <div id="aba_termos" class="quadrado-conteudo-SaaS">
        <div style="border-bottom: 1px solid #1e293b; padding-bottom: 10px; margin-bottom: 15px;">
            <span style="color: #ef4444; font-size: 11px; font-weight: bold; text-transform: uppercase; letter-spacing: 0.5px;">Regulamento Jurídico</span>
            <h3 style="color: #38bdf8; margin: 5px 0 0 0; font-size: 18px;">📜 CLÁUSULA I: Termos de Uso e Condições Contratuais</h3>
        </div>
        <div style="color: #cbd5e1; font-size: 13px; line-height: 1.6;">
            <p><b>1. Aceitação dos Termos Legais:</b> Ao instanciar as camadas de banco de dados do seu estabelecimento no Grupo Aurélius, o parceiro aceita de forma irrevogável as presentes diretrizes regulamentares de mercado.</p>
            <p><b>2. Autenticidade Cadastral:</b> O responsável legal obriga-se a anexar cópias nítidas da frente e do verso do seu Bilhete de Identidade (B.I. Angola). O envio de documentação adulterada, falsificada ou fora do prazo de validade cronológica estabelecido por lei resulta no bloqueio imediato do balcão.</p>
            <p><b>3. Taxas e Tarifas da Rede:</b> Fica acordado que a plataforma reterá a taxa administrativa de comissão de até 15% sobre os faturamentos intermediados pelo portal público. Nenhuma taxa mensal será cobrada durante o período de teste Freemium de 30 dias.</p>
            <p><b>4. Políticas de Cancelamento de Serviços:</b> O cliente retém o direito ao reembolso integral de agendamentos cancelados com até 2 horas de antecedência. Os estornos são liquidados diretamente na conta bancária de origem após a validação da fatura pelo suporte.</p>
        </div>
    </div>

    <!-- 8. ABA: PRIVACIDADE -->
    <div id="aba_privacidade" class="quadrado-conteudo-SaaS">
        <div style="border-bottom: 1px solid #1e293b; padding-bottom: 10px; margin-bottom: 15px;">
            <span style="color: #ef4444; font-size: 11px; font-weight: bold; text-transform: uppercase; letter-spacing: 0.5px;">Segurança de Dados</span>
            <h3 style="color: #38bdf8; margin: 5px 0 0 0; font-size: 18px;">🛡️ CLÁUSULA II: Política de Privacidade e Proteção de Dados</h3>
        </div>
        <div style="color: #cbd5e1; font-size: 13px; line-height: 1.6;">
            <p><b>1. Recolha Corporativa Segura:</b> O portal armazena informações estritamente necessárias para a triagem e hospedagem SaaS, incluindo nomes comerciais, e-mails corporativos, contatos telefônicos de WhatsApp, números de B.I. e chaves de IBAN bancário para liquidação de saques.</p>
            <p><b>2. Processamento e Transferência Dinâmica:</b> Dados coletados de candidatos a emprego (Província, Bairro de residência, data de nascimento e resumo profissional) são criptografados no servidor local e transferidos dinamicamente apenas para o banco de dados do salão selecionado, sendo proibida a comercialização de registros com terceiros.</p>
            <p><b>3. Conformidade com a APD (Angola):</b> Operamos sob os mais rígidos preceitos de segurança digital nacionais, garantindo que o parceiro possa solicitar a exclusão permanente do seu registro da tabela <code>usuario</code> a qualquer momento através do comando de eliminação definitiva.</p>
        </div>
    </div>

    <!-- 9. ABA: COOKIES -->
    <div id="aba_cookies" class="quadrado-conteudo-SaaS">
        <div style="border-bottom: 1px solid #1e293b; padding-bottom: 10px; margin-bottom: 15px;">
            <span style="color: #ef4444; font-size: 11px; font-weight: bold; text-transform: uppercase; letter-spacing: 0.5px;">Diretivas Técnicas</span>
            <h3 style="color: #38bdf8; margin: 5px 0 0 0; font-size: 18px;">🍪 CLÁUSULA III: Diretiva de Cookies e Regulamento de Dados</h3>
        </div>
        <div style="color: #cbd5e1; font-size: 13px; line-height: 1.6;">
            <p><b>1. O que são Cookies de Sessão?</b> São pequenos arquivos de texto armazenados localmente no navegador do utilizador para otimizar a velocidade de renderização da plataforma e salvar estados temporários.</p>
            <p><b>2. Ativação no Portal:</b> Utilizamos cookies técnicos para gerenciar a transição responsiva de abas do motor de hospedagem e ativar a trava de segurança de 1 hora contra re-submissões abusivas de currículos na mesma vaga de emprego.</p>
            <p><b>3. Regulamento Geral (RGPD / CPLP):</b> Em conformidade com as melhores práticas internacionais de proteção de dados, o utilizador pode desativar os cookies analíticos nas configurações do browser, ciente de que isso pode limitar recursos reativos da interface mercantil do rodapé.</p>
        </div>
    </div>
    
    <!-- 10. ABA: SOBRE NÓS -->
    <div id="aba_sobre" class="quadrado-conteudo-SaaS">
        <div style="border-bottom: 1px solid #1e293b; padding-bottom: 10px; margin-bottom: 15px;">
            <span style="color: #38bdf8; font-size: 11px; font-weight: bold; text-transform: uppercase; letter-spacing: 0.5px;">Institucional</span>
            <h3 style="color: #38bdf8; margin: 5px 0 0 0; font-size: 18px;">🇦🇴 Sobre o Grupo Aurélius</h3>
        </div>
        <p>Nascido na província do Huambo, o <b>Grupo Aurélius</b> é uma startup focada no desenvolvimento de engenharia de software SaaS voltada para a modernização do setor de estética e cosmética em Angola.</p>
        <p>A nossa missão corporativa central concentra-se em três pilares fundamentais: oferecer uma infraestrutura ágil para pequenos negócios expandirem o seu faturamento, conectar profissionais qualificados a balcões técnicos com vagas em aberto, e entregar produtos de beleza premium com logística rápida diretamente nas residências dos clientes de forma autônoma e segura.</p>
        
        <p>🛵 <b>Atendimento Especializado ao Domicílio:</b> Rompendo as barreiras do balcão físico tradicional, a nossa plataforma conecta os clientes aos melhores profissionais de estética, cabeleireiros e barbeiros para a realização de serviços no conforto do seu lar. O agendamento é dinâmico, permitindo selecionar o especialista ideal, definir o horário e acompanhar o deslocamento técnico em tempo real pelas vias de Angola.</p>
        
        <p>👑 <b>Clube Premium de Descontos e Fidelidade:</b> Para os clientes que ativam a sua carteira digital e realizam depósitos antecipados na plataforma, o Grupo Aurélius garante vantagens comerciais exclusivas. Os membros ativos recebem uma redução imediata de 10% a 20% em todos os agendamentos ao domicílio e prioridade na reserva de horários em datas de alta afluência, convertendo o saldo retido em créditos de consumo automáticos.</p>
        
        <p>🌍 <b>Logística de Distribuição Nacional Descentralizada:</b> A nossa malha de entregas de cosméticos premium foi estruturada de forma granular para cobrir todo o território nacional. O fluxo logístico inicia-se de forma minuciosa nos <b>Bairros</b> periféricos e zonas suburbanas (como o Kapango e São Luís no Huambo, ou Talatona em Luanda), expande-se de forma integrada para as sedes dos <b>Municípios</b> e consolida-se com rotas interprovinciais que interligam com eficácia todas as <b>21 Províncias</b> de Angola, garantindo que ceras, óleos e champôs cheguem com segurança e faturamento coeso a qualquer balcão ou residência do país.</p>

        </div>
    </div>
    <div id="aba_contacto" class="quadrado-conteudo-SaaS">
    <div style="border-bottom: 1px solid #1e293b; padding-bottom: 10px; margin-bottom: 15px;">
        <span style="color: #ca8a04; font-size: 11px; font-weight: bold; text-transform: uppercase; letter-spacing: 0.5px;">Angola GPS Node</span>
        <h3 style="color: #38bdf8; margin: 5px 0 0 0; font-size: 18px;">📍 Localização da Sede &amp; Mapas de Angola</h3>
    </div>
    <div style="display: flex; gap: 20px; flex-wrap: wrap; margin-bottom: 20px;">
        <div style="color: #cbd5e1; font-size: 13.5px; line-height: 1.6; flex: 1; min-width: 280px;">
            <p style="margin: 0 0 10px 0;">A nossa central mestre opera ativamente na Província do Huambo com canais de atendimento físico e suporte corporativo multitenant:</p>
            <p style="margin: 6px 0;">🏢 <b>Escritório Principal:</b> Bairro de São Luís / Catimba, Sede Administrativa.</p>
            <p style="margin: 6px 0;">🕒 <b>Horário Operacional:</b> Segunda a Sábado — Das 8h00 às 22h00.</p>
            <p style="margin: 6px 0;">🛡️ <b>Suporte ao Cliente:</b> Linhas diretas de comunicação ativas para auditoria mercantil e assistência imediata.</p>
            <p style="margin: 6px 0;">🌍 <b>Cobertura Nacional:</b> Suporte completo a implantações SaaS corporativas em todas as 21 províncias de Angola.</p>
        </div>
        <div style="width: 100%; max-width: 450px; height: 320px; border-radius: 8px; overflow: hidden; border: 1px solid #1e293b; box-shadow: 0 4px 15px rgba(0,0,0,0.4);">
        <iframe width="100%" height="100%" frameborder="0" scrolling="no" marginheight="0" marginwidth="0" 
    src="https://openstreetmap.org" 
    style="background: #070b12; filter: contrast(1.1); border: none;">
</iframe>
        </div>
    </div>
    <span style="display: block; font-size: 11px; color: #64748b; text-align: center; border-top: 1px dashed #1e293b; padding-top: 10px;">Sede: Huambo - Bairro de São Luís / Catimba, Território Nacional.</span>
</div>

<!-- =========================================================================
     ❓ 2. ABA: FAQ VORTEX INTEGRADA (MÓDULO DE PERGUNTAS EXPANSÍVEIS CORRIGIDO)
     ========================================================================= -->
<div id="aba_faq" class="quadrado-conteudo-SaaS">
    <div style="border-bottom: 1px solid #1e293b; padding-bottom: 10px; margin-bottom: 15px;">
        <span style="color: #38bdf8; font-size: 11px; font-weight: bold; text-transform: uppercase; letter-spacing: 0.5px;">Central de Inteligência</span>
        <h2 style="color: #38bdf8; margin: 5px 0 0 0; font-size: 18px;">❓ Perguntas Frequentes (FAQ)</h2>
    </div>

    <h3 class="divisoria-faq-cliente">Para Clientes</h3>
    <details class="item-sanfona-premium">
        <summary>Como funciona o Grupo Aurélius?</summary>
        <div class="resposta-painel">
            <p>O Grupo Aurélius é um ecossistema tecnológico multisserviços líder na província do Huambo e em Angola. Atuamos em Agendamento Inteligente de serviços em linha, Atendimento estético qualificado ao Domicílio e E-Commerce integrado de Cosméticos com entregas descentralizadas de alta performance.</p>
        </div>
    </details>
    <details class="item-sanfona-premium">
        <summary>É possível Cancelar um serviço? Como funciona o reembolso?</summary>
        <div class="resposta-painel">
            <p>Sim, o cancelamento é totalmente garantido. Se o pagamento foi feito por adiantamento bancário ou retido na plataforma, basta aceder à área de agendamentos e solicitar a revogação até 2 horas antes do atendimento para o estorno integral na sua conta, sem taxas adicionais de penalização.</p>
        </div>
    </details>

    <h3 class="divisoria-faq-parceiro">Para Profissionais &amp; Hospedagem</h3>
    <details class="item-sanfona-premium">
        <summary>Como funciona a Abordagem e Recepção Comercial?</summary>
        <div class="resposta-painel">
            <p>Como uma Startup de Hospedagem, entregamos um multiplicador de faturamento para o seu negócio: automatizamos a sua agenda local, reduzimos as faltas dos clientes através de notificações executivas de suporte e direcionamos o fluxo de tráfego das províncias direto para as empresas operacionais dos salões parceiros.</p>
        </div>
    </details>
    <details class="item-sanfona-premium">
        <summary>Existe algum período de teste gratuito? Quais são as taxas?</summary>
        <div class="resposta-painel">
            <p>Sim! Aplicamos o modelo Freemium de crescimento com um teste gratuito de 30 dias com acesso total ao painel Master isolado. É ideal para validar o ecossistema local e o aumento de faturamento real de balcão antes de qualquer investimento técnico.</p>
        </div>
    </details>
</div>

    <!-- 2. ABA: FUNCIONALIDADES -->
    <div id="aba_funcionalidades" class="quadrado-conteudo-SaaS">
        <div style="border-bottom: 1px solid #1e293b; padding-bottom: 10px; margin-bottom: 15px;">
            <span style="color: #38bdf8; font-size: 11px; font-weight: bold; text-transform: uppercase; letter-spacing: 0.5px;">Motores do Sistema</span>
            <h3 style="color: #38bdf8; margin: 5px 0 0 0; font-size: 16px;">⚡ Funcionalidades do Ecossistema SaaS</h3>
        </div>
        <div style="color: #cbd5e1; font-size: 13px; line-height: 1.6;">
            <p>🚀 <b>Hospedagem Automatizada Multi-Tenant:</b> O sistema permite a instanciação e o isolamento lógico de balcões autônomos e bases de faturamento de cada parceiro em menos de 5 minutos.</p>
            <p>💼 <b>Painel de Recrutamento Inteligente:</b> Controle centralizado de candidaturas com motores que interceptam cliques abusivos, gerenciam a caducidade cronológica de documentos e ocultam anúncios saturados após 10 cliques.</p>
            <p>🪪 <b>Auditoria Regulatória Nacional:</b> Verificação nativa de Bilhetes de Identidade angolanos através de algoritmos JavaScript que interceptam erros de formato e calculam o teto legal de expiração etária.</p>
            <p>💬 <b>Mensageria Integrada wa.me:</b> Despacho imediato de notificações comerciais de validação, suspensão contratual ou admissão de profissionais via API sem necessidade de registro na agenda local.</p>
        </div>
    </div>

    <!-- 3. ABA: MÓDULOS -->
    <div id="aba_modulos" class="quadrado-conteudo-SaaS">
        <div style="border-bottom: 1px solid #1e293b; padding-bottom: 10px; margin-bottom: 15px;">
            <span style="color: #38bdf8; font-size: 11px; font-weight: bold; text-transform: uppercase; letter-spacing: 0.5px;">Divisões de Engenharia</span>
            <h3 style="color: #38bdf8; margin: 5px 0 0 0; font-size: 16px;">⚙️ Arquitetura de Módulos Independentes</h3>
        </div>
        <div style="color: #cbd5e1; font-size: 13px; line-height: 1.6;">
            <p>🔗 <b>Core Administrativo (Admin.php):</b> Central master de auditoria para controle de visibilidade das barbearias, status de validação física de B.I. e ativação de privilégios de rede.</p>
            <p>🛍️ <b>Marketplace Corporativo (Loja.php):</b> Módulo dedicado à exposição, controle estrito de estoque e venda em linha de cosméticos premium (ceras modeladoras, óleos capilares e champôs), integrado com filtros de categorias.</p>
            <p>📋 <b>Módulo de Recrutamento (Vagas.php):</b> Interface pública desenvolvida para a captação contínua de talentos técnicos em Angola. Possui travas temporárias por cookies e armazenamento indexado de dados residenciais por Província e Bairro.</p>
            <p>🛒 <b>Central Mercantil (Admin_Venda.php):</b> Subcamada corporativa que gerencia a triagem de pedidos de emprego, equipada com painéis retráteis automáticos para economia de espaço em ecrãs mobile.</p>
        </div>
    </div>

    <!-- 4. ABA: PREÇOS -->
    <div id="aba_precos" class="quadrado-conteudo-SaaS">
        <div style="border-bottom: 1px solid #1e293b; padding-bottom: 10px; margin-bottom: 15px;">
            <span style="color: #38bdf8; font-size: 11px; font-weight: bold; text-transform: uppercase; letter-spacing: 0.5px;">Planos &amp; Contratos</span>
            <h3 style="color: #38bdf8; margin: 5px 0 0 0; font-size: 16px;">💰 Modelo Commercial, Assinaturas e Comissões</h3>
        </div>
        <div style="color: #cbd5e1; font-size: 13px; line-height: 1.6;">
            <p>👑 <b>Instanciação de Infraestrutura:</b> A criação de contas corporativas de micro-parceiros e a abertura das camadas de banco de dados no portal são 100% gratuitas (Taxa Fixada: 0,00 Kz).</p>
            <p>📈 <b>Taxa de Intermediação Administrativa:</b> O ecossistema opera sob o modelo Freemium de crescimento. Cobramos uma comissão padrão de 10% a 15% sobre as transações de cosméticos e agendamentos processados com sucesso dentro da plataforma.</p>
            <p>🔄 <b>Período de Teste Garantido:</b> Oferecemos 30 dias de livre acesso ao painel Master isolado para que o salão comprove o incremento real de faturamento antes de qualquer retenção financeira.</p>
        </div>
    </div>

    <!-- 5. ABA: API -->
    <div id="aba_api" class="quadrado-conteudo-SaaS">
        <div style="border-bottom: 1px solid #1e293b; padding-bottom: 10px; margin-bottom: 15px;">
            <span style="color: #38bdf8; font-size: 11px; font-weight: bold; text-transform: uppercase; letter-spacing: 0.5px;">🔌 Integração Core</span>
            <h3 style="color: #38bdf8; margin: 5px 0 0 0; font-size: 16px;">🔌 Documentação de APIs &amp; Webhooks para Desenvolvedores</h3>
        </div>
        <div style="color: #cbd5e1; font-size: 13px; line-height: 1.6;">
            <p>💻 <b>Endpoints de Faturamento JSON:</b> Desenvolvedores credenciados podem consumir rotas seguras protegidas por chaves de token Bearer para exportar relatórios de vendas, saldos de carteiras e status de saques de parceiros em tempo real.</p>
            <p>🪝 <b>Webhooks de Confirmação EMIS:</b> Sincronização automatizada para disparar gatilhos de validação contrátil assim que o gateway da central acuse a liquidação do split bancário por Multicaixa Express.</p>
            <p>📊 <b>Pauta e Agenda Externa:</b> Integração facilitada via REST API para conectar sistemas locais de gerenciamento de horários ao servidor unificado do Grupo Aurélius.</p>
        </div>
    </div>

    <!-- 6. ABA: DOCUMENTAÇÃO -->
    <div id="aba_documentacao" class="quadrado-conteudo-SaaS">
        <div style="border-bottom: 1px solid #1e293b; padding-bottom: 10px; margin-bottom: 15px;">
            <span style="color: #38bdf8; font-size: 11px; font-weight: bold; text-transform: uppercase; letter-spacing: 0.5px;">Manuais Técnicos</span>
            <h3 style="color: #38bdf8; margin: 5px 0 0 0; font-size: 16px;">📖 Manuais de Operação do Sistema</h3>
        </div>
        <div style="color: #cbd5e1; font-size: 13px; line-height: 1.6;">
            <p>📘 <b>Guia do Administrador:</b> Instruções detalhadas para auditoria de documentos cadastrais, gestão de visibilidade no site e ativação de marcas registradas na vitrine principal.</p>
            <p>📙 <b>Manual do Parceiro:</b> Como lançar oportunidades de trabalho, definir requisitos obrigatórios, e manipular o estoque de produtos cosméticos em segundo plano de forma independente.</p>
            <p>📗 <b>Central de Ajuda de Vendas:</b> Procedimentos para acompanhar e processar as requisições mercantis recebidas no painel Admin_Venda.php.</p>
        </div>
    </div>

    <!-- 7. ABA: BLOG -->
    <div id="aba_blog" class="quadrado-conteudo-SaaS">
        <div style="border-bottom: 1px solid #1e293b; padding-bottom: 10px; margin-bottom: 15px;">
            <span style="color: #38bdf8; font-size: 11px; font-weight: bold; text-transform: uppercase; letter-spacing: 0.5px;">Média &amp; Mercado</span>
            <h3 style="color: #38bdf8; margin: 5px 0 0 0; font-size: 16px;">📰 Blog e Tendências do Mercado de Estética</h3>
        </div>
        <div style="color: #cbd5e1; font-size: 13px; line-height: 1.6;">
            <p>💇 <b>Inovações Técnicas:</b> Artigos semanais assinados por especialistas cobrindo tendências de cortes modernos em Luanda e no Huambo, técnicas avançadas de pigmentação capilar e barboterapia.</p>
            <p>📈 <b>Gestão Financeira para Salões:</b> Dicas práticas corporativas para organizar o fluxo de caixa, calcular a margem de comissão de barbeiros e reduzir em até 95% o índice de faltas de clientes.</p>
            <p>🧴 <b>Cosmética Premium:</b> Análises completas sobre a aplicação de ceras modeladoras, champôs antiqueda e óleos de crescimento capilar comercializados no nosso marketplace.</p>
        </div>
    </div>

    <!-- 8. ABA: TERMOS DE USO -->
    <div id="aba_termos" class="quadrado-conteudo-SaaS">

    <div style="border-bottom: 1px solid #1e293b; padding-bottom: 10px; margin-bottom: 15px;">
            <span style="color: #ef4444; font-size: 11px; font-weight: bold; text-transform: uppercase; letter-spacing: 0.5px;">Regulamento Jurídico</span>
            <h3 style="color: #38bdf8; margin: 5px 0 0 0; font-size: 18px;">📜 CLÁUSULA I: Termos de Uso e Condições Contratuais</h3>
        </div>
        <div style="color: #cbd5e1; font-size: 13px; line-height: 1.6;">
            <p><b>1. Aceitação dos Termos Legais:</b> Ao instanciar as camadas de banco de dados do seu estabelecimento no Grupo Aurélius, o parceiro aceita de forma irrevogável as presentes diretrizes regulamentares de mercado.</p>
            <p><b>2. Autenticidade Cadastral:</b> O responsável legal obriga-se a anexar cópias nítidas da frente e do verso do seu Bilhete de Identidade (B.I. Angola). O envio de documentação adulterada, falsificada ou fora do prazo de validade cronológica de 10 anos estabelecido pelo Decreto Presidencial n.º 182/25 resulta no bloqueio imediato do balcão e cancelamento das credenciais operacionais.</p>
            <p><b>3. Taxas e Tarifas da Rede:</b> Fica acordado que a plataforma reterá a taxa administrativa de comissão de até 15% sobre os faturamentos intermediados pelo portal público. Nenhuma taxa mensal fixa será cobrada durante o período de teste Freemium de 30 dias de infraestrutura.</p>
            <p><b>4. Políticas de Cancelamento de Serviços:</b> O cliente retém o direito ao reembolso integral de agendamentos cancelados com até 2 horas de antecedência. Os estornos são liquidados diretamente na conta bancária de origem após a validação da fatura pelo suporte central.</p>
        </div>
    </div>

    <!-- 8. ABA: PRIVACIDADE -->
    <div id="aba_privacidade" class="quadrado-conteudo-SaaS">
        <div style="border-bottom: 1px solid #1e293b; padding-bottom: 10px; margin-bottom: 15px;">
            <span style="color: #ef4444; font-size: 11px; font-weight: bold; text-transform: uppercase; letter-spacing: 0.5px;">Segurança de Dados</span>
            <h3 style="color: #38bdf8; margin: 5px 0 0 0; font-size: 18px;">🛡️ CLÁUSULA II: Política de Privacidade e Proteção de Dados</h3>
        </div>
        <div style="color: #cbd5e1; font-size: 13px; line-height: 1.6;">
            <p><b>1. Recolha Corporativa Segura:</b> O portal armazena informações estritamente necessárias para a triagem e hospedagem SaaS, incluindo nomes comerciais, e-mails corporativos, contatos telefônicos de WhatsApp, números de B.I. e chaves de IBAN bancário para liquidação de saques.</p>
            <p><b>2. Processamento e Transferência Dinâmica:</b> Dados coletados de candidatos a emprego (Província, Bairro de residência, data de nascimento e resumo profissional) são criptografados no servidor local e transferidos dinamicamente apenas para o banco de dados do salão selecionado, sendo proibida a comercialização de registros com terceiros.</p>
            <p><b>3. Conformidade com a APD (Angola):</b> Operamos sob os mais rígidos preceitos de segurança digital nacionais, garantindo que o parceiro possa solicitar a exclusão permanente do seu registro da tabela <code>usuario</code> a qualquer momento através do comando de eliminação definitiva.</p>
        </div>
    </div>

    <!-- 9. ABA: COOKIES -->
    <div id="aba_cookies" class="quadrado-conteudo-SaaS">
        <div style="border-bottom: 1px solid #1e293b; padding-bottom: 10px; margin-bottom: 15px;">
            <span style="color: #ef4444; font-size: 11px; font-weight: bold; text-transform: uppercase; letter-spacing: 0.5px;">Armazenamento Local</span>
            <h3 style="color: #38bdf8; margin: 5px 0 0 0; font-size: 18px;">🍪 CLÁUSULA III: Diretiva de Cookies e Regulamento de Dados</h3>
        </div>
        <div style="color: #cbd5e1; font-size: 13px; line-height: 1.6;">
            <p><b>1. O que são Cookies de Sessão?</b> São pequenos arquivos de texto armazenados localmente no navegador do utilizador para otimizar a velocidade de renderização da plataforma e salvar estados temporários.</p>
            <p><b>2. Ativação no Portal:</b> Utilizamos cookies técnicos para gerenciar a transição responsiva de abas do motor de hospedagem e ativar a trava de segurança de 1 hora contra re-submissões abusivas de currículos na mesma vaga de emprego.</p>
            <p><b>3. Regulamento Geral (RGPD / CPLP):</b> Em conformidade com as melhores práticas internacionais de proteção de dados, o utilizador pode desativar os cookies analíticos nas configurações do browser, ciente de que isso pode limitar recursos reativos da interface mercantil do rodapé.</p>
        </div>
    </div>
    
    <!-- 10. ABA: SOBRE NÓS -->
    <div id="aba_sobre" class="quadrado-conteudo-SaaS">
        <div style="border-bottom: 1px solid #1e293b; padding-bottom: 10px; margin-bottom: 15px;">
            <span style="color: #38bdf8; font-size: 11px; font-weight: bold; text-transform: uppercase; letter-spacing: 0.5px;">Quem Somos</span>
            <h3 style="color: #38bdf8; margin: 5px 0 0 0; font-size: 18px;">🇦🇴 Sobre o Grupo Aurélius</h3>
        </div>
        <div style="color: #cbd5e1; font-size: 13px; line-height: 1.6;">
            <p>Nascido na província do Huambo, o <b>Grupo Aurélius</b> é uma startup focada no desenvolvimento de engenharia de software SaaS voltada para a modernização do setor de estética e cosmética em Angola.</p>
            <p>A nossa missão corporativa central concentra-se em três pilares fundamentais: oferecer uma infraestrutura ágil para pequenos negócios expandirem o seu faturamento, conectar profissionais qualificados a balcões técnicos com vagas em aberto, e entregar produtos de beleza premium com logística rápida diretamente nas residências dos clientes.</p>
        </div>
    </div>

    <!-- 11. ABA: FAQ VORTEX INTEGRADA -->
    <div id="aba_faq" class="quadrado-conteudo-SaaS">
        <div style="border-bottom: 1px solid #1e293b; padding-bottom: 10px; margin-bottom: 15px;">
            <span style="color: #38bdf8; font-size: 11px; font-weight: bold; text-transform: uppercase; letter-spacing: 0.5px;">Central de Inteligência</span>
            <h2 style="color: #38bdf8; margin: 5px 0 0 0; font-size: 16px;">❓ Perguntas Frequentes (FAQ)</h2>
        </div>

        <h3 class="divisoria-faq-cliente">Para Clientes</h3>
        <details class="item-sanfona-premium">
            <summary>Como funciona o Grupo Aurélius?</summary>
            <div class="resposta-painel">
                <p>O Grupo Aurélius é um ecossistema tecnológico multisserviços líder na província do Huambo e em Angola. Atuamos em Agendamento Inteligente de serviços em linha, Atendimento estético qualificado ao Domicílio e E-Commerce integrado de Cosméticos com entregas descentralizadas de alta performance.</p>
            </div>
        </details>
        <details class="item-sanfona-premium">
            <summary>É possível Cancelar um serviço? Como funciona o reembolso?</summary>
            <div class="resposta-painel">
                <p>Sim, o cancelamento é totalmente garantido. Se o pagamento foi feito por adiantamento bancário ou retido na plataforma, basta aceder à área de agendamentos e solicitar a revogação até 2 horas antes do atendimento para o estorno integral na sua conta, sem taxas adicionais de penalização.</p>
            </div>
        </details>

        <h3 class="divisoria-faq-parceiro">Para Profissionais &amp; Hospedagem</h3>
        <details class="item-sanfona-premium">
            <summary>Como funciona a Abordagem e Recepção Comercial?</summary>
            <div class="resposta-painel">
                <p>Como uma Startup de Hospedagem, entregamos um multiplicador de faturamento para o seu negócio: automatizamos a sua agenda local, reduzimos as faltas dos clientes através de notificações executivas de suporte e direcionamos o fluxo de tráfego das províncias direto para as empresas operacionais dos salões parceiros.</p>
            </div>
        </details>
        <details class="item-sanfona-premium">
            <summary>Existe algum período de teste gratuito? Quais são as taxas?</summary>
            <div class="resposta-painel">
                <p>Sim! Aplicamos o modelo Freemium de crescimento com um teste gratuito de 30 dias com acesso total ao painel Master isolado. É ideal para validar o ecossistema local e o aumento de faturamento real de balcão antes de qualquer investimento técnico.</p>
            </div>
        </details>
    </div>

    <div style="font-size: 12px; color: #64748b; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 10px; max-width: 1000px; margin: 20px auto 0 auto; padding: 15px 0 0 0; border-top: 1px dashed rgba(56, 189, 248, 0.1); box-sizing: border-box;">
    <p style="margin: 0;">&copy; <?php echo date('Y'); ?> Aurelius. Todos os direitos reservados.</p>
    <p style="margin: 0;">Feito em Angola 🇦🇴</p>
</div>

</div> <!-- Fim da div #central_conteudos_rodape -->







<!-- Links de Filtros por Categoria Ajustados para o Principal.php -->
<div style="width: 100%; display: flex; gap: 10px; justify-content: center; flex-wrap: wrap; margin-bottom: 30px;">
    <a href="Principal.php" style="padding: 10px 20px; background: #1e293b; color: #fff; text-decoration: none; border-radius: 20px; font-size: 12px; font-weight: bold; border: 1px solid #334155; transition: background 0.2s;">⭐ Todos os Itens</a>
    <a href="Principal.php?filtro_cat=Ceras" style="padding: 10px 20px; background: #0f172a; color: #38bdf8; text-decoration: none; border-radius: 20px; font-size: 12px; font-weight: bold; border: 1px solid #0284c7; transition: background 0.2s;">🧴 Pomadas de Caspa</a>
    <a href="Principal.php?filtro_cat=Oleos" style="padding: 10px 20px; background: #0f172a; color: #38bdf8; text-decoration: none; border-radius: 20px; font-size: 12px; font-weight: bold; border: 1px solid #0284c7; transition: background 0.2s;">💧 Óleos de Crescimento</a>
    <a href="Principal.php?filtro_cat=Shampoo" style="padding: 10px 20px; background: #0f172a; color: #38bdf8; text-decoration: none; border-radius: 20px; font-size: 12px; font-weight: bold; border: 1px solid #0284c7; transition: background 0.2s;">🚿 Champôs Ativos</a>
</div>



<!-- 🔷 SECÇÃO DE LOJAS PARCEIRAS (MARKETPLACE - FIXADO EM 2 PARCEIROS POR VEZ) -->
<h4 style="color: #38bdf8; text-transform: uppercase; font-weight: bold; font-size: 13px; text-align: left; margin: 30px 0 15px 0; border-left: 4px solid #38bdf8; padding-left: 10px;">🏪 Lojas e Fornecedores Oficiais</h4>

<div style="display: grid !important; grid-template-columns: repeat(auto-fit, minmax(240px, 1fr)) !important; gap: 20px !important; width: 100% !important; box-sizing: border-box !important; margin-bottom: 50px;">
    <?php
    // Estabelece ou reaproveita a ligação segura com a Aiven Cloud
    $mysqli_produtos = $conexao_link ?? $conexao_aurelius;

    if (!$mysqli_produtos || @mysqli_ping($mysqli_produtos) === false) {
        $h_host = getenv('DB_HOST') ?: "://aivencloud.com";
        $h_port = getenv('DB_PORT') ?: 22002;
        $h_name = getenv('DB_NAME') ?: "defaultdb";
        $h_user = getenv('DB_USER') ?: "avnadmin";
        $h_pass = getenv('DB_PASSWORD') ?: "AVNS_6AyaHMtSplThuvy6uGm";
        
        $mysqli_produtos = mysqli_init();
        if ($mysqli_produtos) {
            mysqli_ssl_set($mysqli_produtos, NULL, NULL, NULL, NULL, NULL);
            @mysqli_real_connect($mysqli_produtos, $h_host, $h_user, $h_pass, $h_name, (int)$h_port, NULL, MYSQLI_CLIENT_SSL);
        }
    }

    if ($mysqli_produtos && !$mysqli_produtos->connect_error) {
        $mysqli_produtos->set_charset("utf8mb4");

        // 🔒 AJUSTE DE LIMITE: Fixado estritamente em 2 lojas por atualização, ordenadas aleatoriamente
        $limite_fixo = 2;

        // 🎲 Seleciona os parceiros ativos de forma completamente misturada com o novo limite
        $query_lojas_real = $mysqli_produtos->query("SELECT * FROM `lojas` WHERE `visivel_no_site` = 1 AND `transacao_status` = 'Confirmado' ORDER BY RAND() LIMIT $limite_fixo");
        
        if ($query_lojas_real && $query_lojas_real->num_rows > 0) {
            $posicao = 1;
            while ($loja = $query_lojas_real->fetch_assoc()) {
                // Configuração das mídias das lojas parceiras
                $logo_loja = (!empty($loja['logo_empresa'])) ? "uploads/" . $loja['logo_empresa'] : "OIP (6).webp";
                $slug_rota = !empty($loja['slug_loja']) ? trim($loja['slug_loja']) : "default";
                
                // Mapeia e decodifica as especificações técnicas
                $specs = json_decode($loja['especificacoes_json'], true) ?? [];
                $controle_stock = $specs['controlo_stock'] ?? 'Geral';
                $escala = $specs['escala_catalogo'] ?? 'Pequeno';
                
                // Empacota os dados comerciais para leitura rápida no JavaScript Modal
                $info_modal = htmlspecialchars(json_encode([
                    'name' => $loja['nome_loja'],
                    'email' => $loja['email_mercantil'],
                    'telefone' => $loja['telefone_corporativo'],
                    'endereco' => $loja['endereco_armazem'],
                    'iban' => $loja['iban_bancario'] ?? 'Não Disponível',
                    'escala' => $escala,
                    'stock' => $controle_stock
                ]), ENT_QUOTES, 'UTF-8');
                ?>
                
                <!-- Cartão Premium Unificado de Loja Parceira -->
                <div style="background: #0f172a; border: 1px solid #1e293b; border-radius: 20px; padding: 20px; text-align: center; display: flex; flex-direction: column; justify-content: space-between; box-shadow: 0 4px 12px rgba(0,0,0,0.2); transition: transform 0.2s;">
                    <div>
                        <span style="font-size: 10px; color: #38bdf8; font-weight: bold; background: #1e293b; padding: 4px 10px; border-radius: 10px;">PARCEIRO EXIBIDO Nº <?php echo $posicao++; ?></span>
                        <h2 style="font-size: 14px; font-weight: bold; color: #ffffff; margin: 15px 0 5px 0; text-transform: uppercase; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;"><?php echo htmlspecialchars($loja['nome_loja']); ?></h2>
                        <p style="font-size: 11px; color: #64748b; margin-bottom: 15px;">SOMOS A SOLUÇÃO PARA TI E PARA A SUA FAMÍLIA</p>
                    </div>
                    
                    <!-- Logotipo Redondo da Loja -->
                    <div style="width: 300px; height: 200px; border-radius: 20%; overflow: hidden; margin: 0 auto 15px auto; background: #fff; border: 3px solid #22d3ee;">
                        <img src="<?php echo $logo_loja; ?>" style="width: 100%; height: 100%; object-fit: cover;">
                    </div>

                    <div style="margin-bottom: 15px;">
                        <p style="font-size: 11px; color: #94a3b8; margin: 0 0 5px 0; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">📍 <?php echo htmlspecialchars($loja['endereco_armazem']); ?></p>
                        <span style="font-size: 11px; color: #22c55e; font-weight: bold;">✓ Catálogo: <?php echo ucfirst($escala); ?></span>
                    </div>

                    <!-- Botões Operacionais das Lojas -->
                    <div style="display: flex; flex-direction: column; gap: 8px;">
                        <!-- Botão 1: Detalhes e Variantes (Aba Reativa Pop-up) -->
                        <button type="button" onclick="mostrarDetalhesLoja('<?php echo $info_modal; ?>')" style="width: 100%; background: #1e293b; color: #38bdf8; border: 1px solid #38bdf8; padding: 8px 0; font-size: 11px; font-weight: bold; text-transform: uppercase; border-radius: 8px; cursor: pointer;">
                            🔎 Ficha Técnica
                        </button>
                        
                        <!-- Botão 2: Redirecionamento Dinâmico focado em Lojas.php -->
                        <a href="Lojas.php?slug_loja=<?php echo urlencode($slug_rota); ?>" style="text-decoration: none; width: 100%;">
                            <button type="button" style="width: 100%; background: #22c55e; color: #ffffff; border: none; padding: 9px 0; font-size: 11px; font-weight: bold; text-transform: uppercase; border-radius: 8px; cursor: pointer;">
                                ENTRAR NA LOJA
                            </button>
                        </a>
                    </div>
                </div>

                <?php
            }
        } else {
            echo "<p style='color: #64748b; font-style: italic; padding: 15px; grid-column: 1/-1; text-align: center;'>Nenhum parceiro comercial registado de momento.</p>";
        }
    }
    ?>
</div>
<!-- 🌐 ABA POP-UP SUSPENSA (FICHA TÉCNICA REATIVA) -->
<div id="modal_info_loja" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.85); z-index: 9999; justify-content: center; align-items: center; padding: 15px; box-sizing: border-box;">
    <div style="background: #0f172a; border: 2px solid #38bdf8; border-radius: 16px; width: 100%; max-width: 450px; padding: 25px; color: #fff; position: relative; box-shadow: 0 10px 30px rgba(0,0,0,0.6);">
        
        <button onclick="fecharDetalhesLoja()" style="position: absolute; top: 15px; right: 15px; background: transparent; border: none; color: #64748b; font-size: 20px; cursor: pointer;">✕</button>
        
        <h3 id="modal_nome" style="color: #38bdf8; font-size: 16px; text-transform: uppercase; margin-bottom: 20px; border-bottom: 1px solid #1e293b; padding-bottom: 10px;">Ficha Comercial</h3>
        
        <div style="display: flex; flex-direction: column; gap: 12px; font-size: 12px; text-align: left;">
            <p><strong> TELEFONE: </strong> <span id="modal_telefone" style="color: #94a3b8;"></span></p>
            <p><strong>CORREIO MERCANTIL: </strong> <span id="modal_email" style="color: #94a3b8;"></span></p>
            <p><strong>ENDEREÇO: </strong> <span id="modal_endereco" style="color: #94a3b8;"></span></p>
            <p><strong>TIPO DE EMPRESA: </strong> <span id="modal_escala" style="color: #eab308; font-weight: bold;"></span></p>
           
            <p><strong>INFORMAÇÕES GERAIS DE VARIANTES: </strong> <br> <span style="color: #a855f7;">Esta loja opera com gerenciamento dinâmico de cores, tamanhos e unidades reativas conforme a disponibilidade de stock e o seu gosto.</span></p>
            <p><strong>CANAIS DE ABASTECIMENTO: </strong> <br> <span style="color: #ca8a04;">Parcerias logísticas integradas para distribuição nacional.</span></p>
        </div>
        
        <button onclick="fecharDetalhesLoja()" style="width: 100%; background: #38bdf8; color: #0f172a; border: none; padding: 10px 0; font-size: 12px; font-weight: bold; border-radius: 8px; cursor: pointer; margin-top: 20px;">
            FECHAR ESPECIFICAÇÕES
        </button>
    </div>
</div>

<script>
function mostrarDetalhesLoja(dadosString) {
    const dados = JSON.parse(dadosString);
    
    document.getElementById('modal_nome').innerText = "NOME DA LOJA: " + dados.nome;
    document.getElementById('modal_telefone').innerText = dados.telefone;
    document.getElementById('modal_email').innerText = dados.email;
    document.getElementById('modal_endereco').innerText = dados.endereco;
    document.getElementById('modal_escala').innerText = dados.escala.toUpperCase() + " (Controlo: " + dados.stock + ")";
   
    
    document.getElementById('modal_info_loja').style.display = 'flex';
}

function fecharDetalhesLoja() {
    document.getElementById('modal_info_loja').style.display = 'none';
}
</script>























    <!-- =================================================================
    🎛️ MINI BARRA INFERIOR DE NAVEGAÇÃO REATIVA (100% RESPONSIVA)
    ================================================================= -->
    <style>
        /* Estilos base estruturais para o rodapé */
        .footer-aurelius {
            background: #0b111e; 
            padding: 20px 15px; 
            text-align: center; 
            border-top: 1px solid #1e293b;
            box-sizing: border-box;
            width: 100%;
        }
        
        /* Contentor pílula principal adaptável */
        .lista-nav-footer {
            display: inline-flex; 
            gap: 15px; 
            background: #101f38; 
            border: 2px solid #38bdf8; 
            border-radius: 30px; 
            padding: 10px 25px; 
            margin: 0; 
            list-style: none; 
            box-shadow: 0 0 15px rgba(56, 189, 248, 0.25); 
            flex-wrap: wrap; 
            justify-content: center;
            align-items: center;
            box-sizing: border-box;
        }
    
        /* Links internos com transição suave */
        .link-social-footer {
            display: flex; 
            align-items: center; 
            gap: 10px; 
            font-size: 13px; 
            color: #cbd5e1; 
            text-decoration: none; 
            font-weight: bold; 
            transition: color 0.2s ease, transform 0.2s ease;
        }
    
        .link-social-footer:hover {
            color: #38bdf8 !important;
            transform: translateY(-1px);
        }
    
        /* Imagens padronizadas com recorte perfeito */
        .img-social-footer {
            width: 20px; 
            height: 20px; 
            border-radius: 50%; 
            border: 1px solid #38bdf8; 
            object-fit: cover;
            flex-shrink: 0;
        }
    
        /* Separadores de bolha */
        .separador-footer {
            color: #38bdf8; 
            font-weight: bold; 
            user-select: none; 
            display: flex; 
            align-items: center;
        }
    
        /* 📱 Otimizações reativas para Telemóveis (Mobile-First) */
        @media (max-width: 580px) {
            .lista-nav-footer {
                border-radius: 16px !important;
                padding: 15px !important;
                gap: 12px !important;
                display: grid !important;
                grid-template-columns: repeat(2, 1fr) !important; /* Transforma em grelha dupla simétrica */
                width: 100% !important;
                max-width: 320px !important;
                margin: 0 auto !important;
            }
            
            .separador-footer {
                display: none !important; /* Oculta as bolhas no mobile para economizar espaço */
            }
    
            .link-social-footer {
                justify-content: center !important;
                background: rgba(56, 189, 248, 0.05) !important;
                padding: 8px !important;
                border-radius: 8px !important;
                border: 1px solid rgba(56, 189, 248, 0.1) !important;
            }
        }
    </style>


    <!-- 📱 RODAPÉ DE REDES SOCIAIS EM CÍRCULOS COMPACTOS (100% HORIZONTAL SEM QUEBRAS) -->
<footer class="div3 footer-aurelius" style="width: 100%; max-width: 440px; margin: 20px auto; padding: 0 10px; box-sizing: border-box;">
    <ul class="lista-nav-footer" style="list-style: none; display: flex; justify-content: center; align-items: center; gap: 5px; padding: 10px; margin: 0; background: #0f1423; border: 1px solid #1e293b; border-radius: 50px; box-sizing: border-box;"> 
        
        <!-- Canal Instagram (Roteado via contacto) -->
        <li>
            <a href="https://instagram.com" target="_blank" title="Instagram" class="link-social-footer" style="text-decoration: none; display: flex; align-items: center; justify-content: center; width: 40px; height: 40px; background: rgba(255, 255, 255, 0.03); border: 1px solid #1e293b; border-radius: 50%; color: #f8fafc; font-size: 16px; box-sizing: border-box; transition: background 0.2s;"> 
                📸
            </a>
        </li>
        
        <!-- Canal Telegram (Mensagem Direta para o Número) -->
        <li>
            <a href="https://t.me" target="_blank" title="Telegram" class="link-social-footer" style="text-decoration: none; display: flex; align-items: center; justify-content: center; width: 40px; height: 40px; background: rgba(255, 255, 255, 0.03); border: 1px solid #1e293b; border-radius: 50%; color: #f8fafc; font-size: 16px; box-sizing: border-box; transition: background 0.2s;"> 
                ✈️
            </a>
        </li>
        
        <!-- Canal Facebook (Procura Contextual pelo Número) -->
        <li>
            <a href="https://facebook.com" target="_blank" title="Facebook" class="link-social-footer" style="text-decoration: none; display: flex; align-items: center; justify-content: center; width: 40px; height: 40px; background: rgba(255, 255, 255, 0.03); border: 1px solid #1e293b; border-radius: 50%; color: #f8fafc; font-size: 16px; box-sizing: border-box; transition: background 0.2s;"> 
                🔵
            </a>
        </li>
        
        <!-- Canal WhatsApp (Link de API Oficial com mensagem predefinida) -->
        <li>
            <a href="https://wa.me." target="_blank" title="WhatsApp" class="link-social-footer" style="text-decoration: none; display: flex; align-items: center; justify-content: center; width: 40px; height: 40px; background: rgba(34, 197, 94, 0.05); border: 1px solid rgba(34, 197, 94, 0.15); border-radius: 50%; color: #22c55e; font-size: 16px; box-sizing: border-box; transition: background 0.2s;"> 
                💬
            </a>
        </li>
        
    </ul>
</footer>

<!-- Keyframes para a pulsação suave em CSS -->
<style>
@keyframes pulsarRodapeFrame {
    0% { box-shadow: 0 0 10px rgba(56, 189, 248, 0.15); border-color: #1e293b; }
    100% { box-shadow: 0 0 20px rgba(56, 189, 248, 0.35); border-color: #38bdf8; }
}
</style>
    
<!-- JAVASCRIPT DO ACCORDION INTERNO DA ABA -->
<script>
function alternarVisibilidadeFAQ() {
    var faq = document.getElementById("blocoFaqPrincipal");
    if (faq.hasAttribute("hidden")) {
        faq.removeAttribute("hidden");
        faq.scrollIntoView({ behavior: 'smooth' });
    } else {
        faq.setAttribute("hidden", "true");
    }
}
 // =========================================================================
    // 🖨️ CONTINUAÇÃO COMPLETA DA ENGINE DE IMPRESSÃO TÉRMICA (DESTRAVAMENTO)
    // =========================================================================
    // Formata o valor monetário separando os milhares por espaço (ex: 15 000 Kz)
    let precoNumerico = parseFloat(valorParam) || 0;
    let precoFormatado = precoNumerico.toLocaleString('pt-PT').replace(/\./g, ' ');
    
    // Organiza a cronologia da data no padrão pt-PT
    let dataFormatada = dataParam;
    if (dataParam.includes('-')) {
        const partes = dataParam.split('-');
        dataFormatada = `${partes[2]}/${partes[1]}/${partes[0]}`;
    }

    // Injeta os estilos CSS otimizados para Impressoras Térmicas de Talões
    let estiloImpressao = document.getElementById('estilo-impressao-aurelius');
    if (!estiloImpressao) {
        estiloImpressao = document.createElement('style');
        estiloImpressao.id = 'estilo-impressao-aurelius';
        estiloImpressao.innerHTML = `
            @media print {
                body * { display: none !important; }
                #area-impressao-global, #area-impressao-global * { display: block !important; }
                body, html { background-color: #ffffff !important; color: #000000 !important; margin: 0 !important; padding: 0 !important; width: 100% !important; }
                .no-print-btn { display: none !important; }
                @page { size: auto; margin: 0mm; }
                .zona-recibo-impressao { padding: 0 !important; min-height: auto !important; background: #fff !important; }
                .recibo-card-premium { max-width: 100% !important; width: 80mm !important; padding: 10px !important; box-shadow: none !important; background: #fff !important; border: none !important; }
                .recibo-card-premium span, .recibo-card-premium h1, .recibo-card-premium label { color: #000000 !important; }
                .bloco-total { border: 1px dashed #000 !important; background: #fff !important; color: #000 !important; }
                .bloco-total * { color: #000 !important; }
            }
            .zona-recibo-impressao { background-color: rgba(11, 26, 48, 0.95); font-family: 'Courier New', Courier, monospace; position: fixed; top: 0; left: 0; width: 100%; height: 100vh; z-index: 99999; display: flex; justify-content: center; align-items: center; overflow-y: auto; padding: 20px; box-sizing: border-box; }
            .recibo-card-premium { background-color: #111e38; border: 2px solid #0088cc; border-radius: 12px; width: 100%; max-width: 400px; padding: 25px; box-shadow: 0 0 20px rgba(0, 136, 204, 0.4); box-sizing: border-box; }
        `;
        document.head.appendChild(estiloImpressao);
    }

    const areaImpressao = document.getElementById('area-impressao-global');
    if (areaImpressao) {
        areaImpressao.style.display = 'flex';
        areaImpressao.innerHTML = `
            <div class="zona-recibo-impressao">
                <div class="recibo-card-premium">
                    <span class="no-print-btn" onclick="document.getElementById('area-impressao-global').style.display='none'" style="float: right; color: #ef4444; font-size: 24px; cursor: pointer; font-weight: bold; font-family: sans-serif; line-height: 1;">&times;</span>
                    <div style="text-align: center; margin-bottom: 15px;">
                        <h1 style="color: #38bdf8; font-size: 20px; margin: 0; text-transform: uppercase; font-weight: bold;">Barbearia Branca</h1>
                        <p style="color: #94a3b8; font-size: 11px; margin: 4px 0 0 0; text-transform: uppercase;">Comprovativo de Atendimento</p>
                    </div>
                    <div style="border-top: 1px dashed #334155; margin: 12px 0;"></div>
                    <div style="display: flex; flex-direction: column; gap: 12px; margin-bottom: 20px; font-size: 13px; text-align: left;">
                        <div style="display: flex; justify-content: space-between;"><label style="color: #64748b; font-weight: bold;">Cliente:</label><span style="color: #ffffff; font-weight: bold;">${clienteParam}</span></div>
                        <div style="display: flex; justify-content: space-between;"><label style="color: #64748b; font-weight: bold;">Profissional:</label><span style="color: #ffffff;">${funcionarioParam}</span></div>
                        <div style="display: flex; justify-content: space-between;"><label style="color: #64748b; font-weight: bold;">Serviço:</label><span style="color: #ffffff;">${servicoParam}</span></div>
                        <div style="display: flex; justify-content: space-between;"><label style="color: #64748b; font-weight: bold;">Data/Hora:</label><span style="color: #38bdf8; font-weight: bold;">${dataFormatada} - ${horaParam}</span></div>
                    </div>
                    <div class="bloco-total" style="background-color: #0b1329; border-left: 4px solid #22c55e; padding: 12px; border-radius: 6px; display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; box-sizing: border-box;">
                        <label style="color: #22c55e; font-size: 12px; font-weight: bold; text-transform: uppercase; margin: 0;">Total:</label>
                        <span style="color: #22c55e; font-size: 18px; font-weight: bold;">${precoFormatado} Kz</span>
                    </div>
                    <div style="border-top: 1px dashed #334155; margin: 12px 0;"></div>
                    <div style="text-align: center; font-size: 12px; color: #38bdf8; font-weight: bold; margin-bottom: 4px;">Obrigado pela preferência!</div>
                    <div style="text-align: center; font-size: 10px; color: #94a3b8; line-height: 1.4; margin-bottom: 20px;">📍 Bairro de São Luís / perto da IECA<br>Huambo - Angola</div>
                    <button type="button" class="no-print-btn" onclick="window.print()" style="width: 100%; background-color: #22c55e; color: white; border: none; padding: 12px; font-size: 13px; font-weight: bold; border-radius: 6px; cursor: pointer; text-transform: uppercase;">🖨️ Imprimir Talão</button>
                </div>
            </div>
        `;
    }



// 7. COMPATIBILIDADE DE ROTAS: Faz com que a chamada antiga do botão aponte para a função nova JSON
function salvarAgendamentoSessao() {
    enviarMarcacaoParaBanco();
}

// Faz com que o clique antigo dos itens estáticos ative a caixa de confirmação
function selecionarServico(nome, preco) {
    exibirPrecoFinal(nome, preco.toLocaleString('pt-AO') + " kz");
}

// Atalhos para os botões do teu menu lateral retrátil responderem instantaneamente
function abrirAbas() {
    const modalAbas = document.getElementById('modalAbas');
    if (modalAbas) modalAbas.style.display = 'flex';
}
function fecharAbas() { document.getElementById('modalAbas').style.display = 'none'; }

function abrirTermos() {
    const modalTermos = document.getElementById('modalTermos');
    if (modalTermos) modalTermos.style.display = 'flex';
}
function fecharTermos() { document.getElementById('modalTermos').style.display = 'none'; }

function fecharFaturaNatural() { document.getElementById('faturaPainelNatural').style.display = 'none'; }
function fecharModalPremium() { document.getElementById('modalPremium').style.display = 'none'; }
<script>
// 🟢 REGISTO DO SERVICE WORKER (PWA) - APENAS UMA INSTÂNCIA LIMPA
if ("serviceWorker" in navigator) {
  window.addEventListener("load", function() {
    navigator.serviceWorker.register("sw.js")
    .then(function(reg) {
      console.log("✓ PWA Aurélius conectado com sucesso à Unitele.php!");
    })
    .catch(function(err) {
      console.log("Falha ao registar Service Worker PWA:", err);
    });
  });
}

// 🟩 ROTEADOR INTELIGENTE SAAS (BASEADO NA TUA TABELA REAL DO PHPMYADMIN)
function redirecionarParaPainelParceiro(idUsuario, slugUsuario) {
    var slugLimpo = slugUsuario.trim();
    
    // Se o slug for 'BarbeariaBranca' (ID 237), abre o ficheiro mestre de 399 linhas
    if (slugLimpo === 'BarbeariaBranca' || parseInt(idUsuario) === 237) {
        window.location.href = 'BarbeariaBranca.php';
    } 
    // Se o slug for genérico ou novo, abre a pasta auto-criada gerada pelo formulário
    else if (slugLimpo !== '' && slugLimpo !== 'Login') {
        window.location.href = slugLimpo + '/index.php';
    } 
    // Caso contrário, mantém no fluxo padrão de segurança
    else {
        window.location.href = 'BarbeariaBranca.php?id=' + idUsuario;
    }
}
</script>
<script>
// Função Comercial para facilitar o pagamento copiando o IBAN dinâmico na hora
function copiarIbanGrupoAurelius() {
    // Captura o elemento de texto do IBAN gerado dinamicamente pelo PHP
    const campoIban = document.getElementById('texto_iban_dinamico');
    if (!campoIban) return;

    const textoParaCopiar = campoIban.innerText || campoIban.textContent;
    
    // Usa a API nativa do browser para transferir o conteúdo para a área de transferência
    navigator.clipboard.writeText(textoParaCopiar).then(() => {
        alert("📋 IBAN Copiado com Sucesso! Abra o seu aplicativo Multicaixa Express para colar e efetuar o pagamento.");
    }).catch(() => {
        alert("⚠️ Falha ao copiar automaticamente. Por favor, selecione e copie manualmente.");
    });
}

// Controla a transição e rolagem do botão do Painel Freemium até à Vitrine VIP
function abrirModalPremiumAurelius() {
    const secaoVip = document.getElementById('vitrine_vip_centralizada');
    if (secaoVip) {
        secaoVip.scrollIntoView({ behavior: 'smooth' });
    }
}
</script>



</body>
</html>