<?php
// 🔴 DEVE SER A PRIMEIRA LINHA ABSOLUTA DO FICHEIRO ADMINI.PHP:
ob_start(); 

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Tranca de segurança: Se não existir a sessão ativa, barra o acesso
if (!isset($_SESSION['admin_logado']) || $_SESSION['admin_logado'] !== true) {
    header("Location: Login.php");
    exit;
}

// Importa a conexão estruturada do ecossistema
include_once("Conexao.php");
// ⏳ LISTA 1: PENDENTES (Hoje não compareceu até 5 dias OU agendamentos futuros)
$stmt_pendentes = $pdo->prepare("
    SELECT *, DATEDIFF(?, data_servico) as dias_atraso 
    FROM `pagamentos` 
    WHERE `id_parceiro` = ? 
      AND `status_trabalho` = 'Pendente'
      AND `status_atendimento` = 'Confirmado'
      AND (`data_servico` > ? OR (`data_servico` <= ? AND DATEDIFF(?, data_servico) <= 5))
    ORDER BY data_servico ASC
");
$stmt_pendentes->execute([$hoje_sql, $id_empresa_ativa, $hoje_sql, $hoje_sql, $hoje_sql]);
$lista_pendentes = $stmt_pendentes->fetchAll(PDO::FETCH_ASSOC);

// ✅ LISTA 2: SERVIÇOS JÁ TRABALHADOS (Hoje Concluídos ou passados já fechados)
$stmt_concluidos = $pdo->prepare("
    SELECT * FROM `pagamentos` 
    WHERE `id_parceiro` = ? 
      AND (`status_trabalho` = 'Concluído' OR (`data_servico` = ? AND `status_trabalho` = 'Concluído'))
    ORDER BY id_pagamento DESC
");
$stmt_concluidos->execute([$id_empresa_ativa, $hoje_sql]);
$lista_concluidos = $stmt_concluidos->fetchAll(PDO::FETCH_ASSOC);
?>

<?php
// 🔴 DEVE SER A PRIMEIRA LINHA ABSOLUTA DO FICHEIRO ADMINI.PHP:
ob_start(); 

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Tranca de segurança: Se não existir a sessão ativa, barra o acesso
if (!isset($_SESSION['admin_logado']) || $_SESSION['admin_logado'] !== true) {
    header("Location: Login.php");
    exit;
}

// Importa a conexão estruturada do ecossistema
include_once("Conexao.php");

// Garante o alinhamento da variável com o seu arquivo de conexão MySQLi
$conexao_link = $conexao_aurelius ?? $conexao ?? $link ?? null;
if (!$conexao_link) {
    $conexao_link = @mysqli_connect("127.0.0.1", "root", "", "aurelius_salao");
}

$clientes_vip = [];

if ($conexao_link) {
    mysqli_set_charset($conexao_link, "utf8mb4");
    
    // 🟢 SEM FILTROS: Puxa absolutamente tudo da tabela assinaturas ordenando pelo ID mais recente
    $query_vip = "SELECT * FROM `assinaturas` ORDER BY `id_assinatura` DESC";
    $resultado_vip = mysqli_query($conexao_link, $query_vip);
    
    if ($resultado_vip) {
        while ($linha = mysqli_fetch_assoc($resultado_vip)) {
            $clientes_vip[] = $linha;
        }
    }
}
?>


<?php
if (session_status() === PHP_SESSION_NONE) { session_start(); }
include_once __DIR__ . '/Conexao.php';

// Salva a loja selecionada na sessão para uso posterior no redirecionamento post-login
if (isset($_GET['loja'])) {
    $_SESSION['loja_contexto'] = (int)$_GET['loja'];
}
?>


<?php
// Coloque este bloco no topo do seu Admini.php onde faz as consultas SQL

$totalProfissionais = 0;

if ($conexao_link) {
    $resultado_admin = mysqli_query($conexao_link, "SELECT * FROM `funcionarios` WHERE `ativo` = 1 ORDER BY `id_funcionario` ASC");
    if ($resultado_admin) {
        $totalProfissionais = mysqli_num_rows($resultado_admin);
        while ($row = mysqli_fetch_assoc($resultado_admin)) {
            $funcionarios[] = $row; // Injeta a linha completa do phpMyAdmin no array
        }
    }
}
?>

<?php
// 🔴 DEVE SER A PRIMEIRA LINHA ABSOLUTA DO FICHEIRO ADMINI.PHP:
ob_start(); 

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Tranca de segurança: Se não existir a sessão ativa, barra o acesso
if (!isset($_SESSION['admin_logado']) || $_SESSION['admin_logado'] !== true) {
    header("Location: Login.php");
    exit;
}

// Importa a conexão estruturada do ecossistema
include_once("Conexao.php");
$mensagem_vaga = "";

// 📢 PROCESSAR LANÇAMENTO DE NOVA VAGA DE TRABALHO
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['publicar_vaga_gerente'])) {
    $cargo      = htmlspecialchars(trim($_POST['cargo_vaga']));
    $salario    = htmlspecialchars(trim($_POST['salario_vaga']));
    $requisitos = htmlspecialchars(trim($_POST['requisitos_vaga']));

    try {
        $stmtVaga = $pdo->prepare("INSERT INTO vagas_trabalho (id_barbearia, cargo, salario, requisitos, data_criacao) VALUES (:id_barb, :cargo, :sal, :req, NOW())");
        $stmtVaga->execute([':id_barb' => $id_salao_logado, ':cargo' => $cargo, ':sal' => $salario, ':req' => $requisitos]);
        $mensagem_vaga = "✅ Nova oportunidade de emprego lançada na Bolsa do Huambo!";
    } catch (PDOException $e) { 
        $mensagem_vaga = "❌ Erro ao salvar vaga: " . $e->getMessage(); 
    }
}

// 🔍 BUSCA APENAS OS CURRÍCULOS E PEDIDOS DE EMPREGO RECEBIDOS DE CLIENTES
try {
    $stmtCand = $pdo->prepare("SELECT * FROM pedidos_emprego WHERE id_barbearia = :id ORDER BY id DESC");
    $stmtCand->execute([':id' => $id_salao_logado]);
    $listaCandidaturas = $stmtCand->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $listaCandidaturas = [];
}

// 🟢 NOTA DE ENGENHARIA: Nenhum código de loop ou array de vídeos Reels/Mídias foi processado aqui.
// Isto garante que a aba de vídeos antiga seja ocultada permanentemente por omissão lógica.
?>


<?php
// 🔴 DEVE SER A PRIMEIRA LINHA ABSOLUTA DO FICHEIRO ADMINI.PHP:
ob_start(); 

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Tranca de segurança: Se não existir a sessão ativa, barra o acesso
if (!isset($_SESSION['admin_logado']) || $_SESSION['admin_logado'] !== true) {
    header("Location: Login.php");
    exit;
}

// Importa a conexão estruturada do ecossistema
include_once("Conexao.php");

// 🔑 DEFINIÇÃO DAS CREDENCIAIS MESTRE DA GERÊNCIA
define('ADMIN_USER', 'Admin');
define('ADMIN_PASS', 'Huambo2026');

// 🌟 LÓGICA REFORÇADA DE LOGOUT (ENCERRAR SESSÃO)
if (isset($_GET['acao_seguranca']) && $_GET['acao_seguranca'] === 'logout') {
    $_SESSION = array(); // Limpa todas as variáveis de sessão
    if (ini_get("session.use_cookies")) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000,
            $params["path"], $params["domain"],
            $params["secure"], $params["httponly"]
        );
    }
    session_destroy(); // Destrói a sessão no servidor
    echo "<script>window.location.href='admini.php';</script>"; // Recarrega na tela de login
    exit;
}

// Lógica de processamento do formulário de Login
$erro_login = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['entrar_gerencia'])) {
    $utilizador = trim($_POST['usuario_gerente']);
    $palavra_passe = trim($_POST['senha_gerente']);

    if ($utilizador === ADMIN_USER && $palavra_passe === ADMIN_PASS) {
        $_SESSION['gerente_autenticado'] = true;
        $_SESSION['gerente_nome'] = 'Aurélio Sacalumbo';
        
        echo "<script>window.location.href='admini.php';</script>";
        exit;
    } else {
        $erro_login = "⚠️ Credenciais de Gerência Inválidas. Acesso Negado.";
    }
}

// 🔒 TRANCA DO PAINEL: Se não estiver autenticado, renderiza o ecrã de Bloqueio Mestre
if (!isset($_SESSION['gerente_autenticado']) || $_SESSION['gerente_autenticado'] !== true) {
    ?>

<?php
// Admini.php - Processador de Novas Vagas de Emprego
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
include_once("Conexao.php");

$conexao_link = $conexao_aurelius ?? $conexao ?? $link ?? null;
if (!$conexao_link) {
    $conexao_link = @mysqli_connect("127.0.0.1", "root", "", "aurelius_salao");
}

$mensagemVagaSucesso = "";

if ($conexao_link && isset($_POST['publicar_nova_vaga'])) {
    // Captura o ID da empresa logada na sessão (Ex: ID 237) ou assume o padrão de teste
    $id_empresa_sessao = $_SESSION['loja_id'] ?? $_SESSION['empresa_codigo'] ?? 237;
    
    $cargo = mysqli_real_escape_string($conexao_link, $_POST['cargo_vaga']);
    $salario = mysqli_real_escape_string($conexao_link, $_POST['salario_vaga']);
    $exigencias = mysqli_real_escape_string($conexao_link, $_POST['exigencias_vaga']);

    $query_insere_vaga = "INSERT INTO `vagas_trabalho` (`id_parceiro`, `cargo`, `salario_base`, `exigencias`) 
                          VALUES ($id_empresa_sessao, '$cargo', '$salario', '$exigencias')";
    
    if (mysqli_query($conexao_link, $query_insere_vaga)) {
        $mensagemVagaSucesso = "🚀 <b>Oportunidade Publicada!</b> A vaga foi lançada e o contador da página principal atualizou automaticamente.";
    }
}
?>



<?php
// 🔴 DEVE SER A PRIMEIRA LINHA ABSOLUTA DO FICHEIRO ADMINI.PHP:
ob_start(); 

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Tranca de segurança: Se não existir a sessão ativa, barra o acesso
if (!isset($_SESSION['admin_logado']) || $_SESSION['admin_logado'] !== true) {
    header("Location: Login.php");
    exit;
}

// Importa a conexão estruturada do ecossistema
include_once("Conexao.php");

$conexao_link = $conexao_aurelius ?? $conexao ?? $link ?? null;
if (!$conexao_link) {
    $conexao_link = @mysqli_connect("127.0.0.1", "root", "", "aurelius_salao");
}

$mensagemVagaSucesso = "";

if ($conexao_link && isset($_POST['publicar_nova_vaga'])) {
    // Captura o ID real da barbearia logada ou usa o ID 237 (Barbearia Branca) para teste local
    $id_barbearia_sessao = $_SESSION['codigo'] ?? $_SESSION['loja_id'] ?? 237;
    
    // Captura os NAMES exatos vindos do formulário HTML
    $cargo = mysqli_real_escape_string($conexao_link, $_POST['cargo']);
    $salario = mysqli_real_escape_string($conexao_link, $_POST['salario']);
    $requisitos = mysqli_real_escape_string($conexao_link, $_POST['requisitos']);

    // QUERY ALINHADA COM O SEU phpMyAdmin: id_barbearia, cargo, salario, requisitos
    $query_insere_vaga = "INSERT INTO `vagas_trabalho` (`id_barbearia`, `cargo`, `salario`, `requisitos`, `data_criacao`) 
                          VALUES ($id_barbearia_sessao, '$cargo', '$salario', '$requisitos', NOW())";
    
    if (mysqli_query($conexao_link, $query_insere_vaga)) {
        $mensagemVagaSucesso = "🚀 <b>Oportunidade de Trabalho Registada!</b> A vaga de <b>$cargo</b> foi guardada com sucesso no phpMyAdmin.";
    } else {
        $mensagemVagaSucesso = "⚠️ Erro de Sintaxe SQL: " . mysqli_error($conexao_link);
    }
}
?>

<?php
// 🔴 DEVE SER A PRIMEIRA LINHA ABSOLUTA DO FICHEIRO ADMINI.PHP:
ob_start(); 

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Tranca de segurança: Se não existir a sessão ativa, barra o acesso
if (getenv('DB_HOST')) {
    // Se estiver online no Render, busca as variáveis da Railway com a porta correta
    $db_host = getenv('DB_HOST') ?: "altaria.proxy.rlwy.net";
    $db_port = getenv('DB_PORT') ?: "52030";
    $db_user = getenv('DB_USER') ?: "root";
    $db_pass = getenv('DB_PASSWORD') ?: "tPzDwXGkyczyyYdcyvLmHLSMmfZmnMIZ";
    $db_name = getenv('DB_NAME') ?: "railway";
    
    $mysqli = mysqli_init();
    if (!@mysqli_real_connect($mysqli, $db_host, $db_user, $db_pass, $db_name, (int)$db_port)) {
        $mysqli = $conexao_link ?? null; // Fallback se a variável global já existir
    }
} else {
    // Se estiver no seu computador (XAMPP), usa a ligação padrão do Localhost
    $mysqli = @mysqli_connect("127.0.0.1", "root", "", "aurelius_salao");
}

if ($mysqli) {
    $mysqli->set_charset("utf8mb4");
}

    // Interceta o clique no botão de publicação
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['disparar_publicacao_vaga'])) {
        
        // Coleta o ID da barbearia ou loja logada na sessão (Fallback para ID 237 se não houver login)
        $id_empresa_logada = $_SESSION['codigo'] ?? $_SESSION['loja_id'] ?? $_SESSION['empresa_id'] ?? 237;
        
        // Higieniza os inputs mapeados exatamente com o seu HTML
        $cargo_vaga  = $mysqli_vagas->real_escape_string(trim($_POST['cargo_vaga']));
        $salario_vaga = $mysqli_vagas->real_escape_string(trim($_POST['salario_vaga']));
        $requisitos_vaga = $mysqli_vagas->real_escape_string(trim($_POST['requisitos_vaga']));

        // Executa o comando INSERT respeitando o esquema real do seu phpMyAdmin
        $sql_add_vaga = "INSERT INTO `vagas_vagas_trabalho` (`id_barbearia`, `cargo`, `salario`, `requisitos`, `data_criacao`) 
                         VALUES ($id_empresa_logada, '$cargo_vaga', '$salario_vaga', '$requisitos_vaga', NOW())";
        
        // Fallback caso a tabela no banco não possua o prefixo inserido por engano
        if (!$mysqli_vagas->query($sql_add_vaga)) {
            $sql_add_vaga = "INSERT INTO `vagas_trabalho` (`id_barbearia`, `cargo`, `salario`, `requisitos`, `data_criacao`) 
                             VALUES ($id_empresa_logada, '$cargo_vaga', '$salario_vaga', '$requisitos_vaga', NOW())";
            $execucao = $mysqli_vagas->query($sql_add_vaga);
        } else {
            $execucao = true;
        }

        if ($execucao) {
            $mensagemVagaStatus = "<div style='background: #064e3b; border: 1px solid #059669; color: #34d399; padding: 12px; border-radius: 8px; font-size: 13px; margin-bottom: 15px; text-align: center; font-family: sans-serif;'>🚀 <b>Oportunidade Registada com Sucesso!</b> A vaga já foi computada nas métricas do ecossistema.</div>";
        } else {
            $mensagemVagaStatus = "<div style='background: rgba(239, 68, 68, 0.1); border: 1px solid #f87171; padding: 12px; border-radius: 8px; color: #f87171; margin-bottom: 15px; font-size: 13px; text-align: center; font-family: sans-serif;'>❌ Erro ao processar gravação: " . $mysqli_vagas->error . "</div>";
        }
    }
}
?>







<?php
// 🔴 DEVE SER A PRIMEIRA LINHA ABSOLUTA DO FICHEIRO ADMINI.PHP:
ob_start(); 

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Tranca de segurança: Se não existir a sessão ativa, barra o acesso
if (!isset($_SESSION['admin_logado']) || $_SESSION['admin_logado'] !== true) {
    header("Location: login.php");
    exit;
}

// Importa a conexão estruturada do ecossistema
include_once("Conexao.php");
date_default_timezone_set('Africa/Luanda');

try {
    // 1. Carrega os cargos e salários de referência
    $stmt_cargos = $pdo->query("SELECT * FROM cargos_salarios ORDER BY id_cargo ASC");
    $lista_referencia_cargos = $stmt_cargos->fetchAll(PDO::FETCH_ASSOC);

    // 2. Carrega os 12 funcionários reais do seu salão
    $stmt_func = $pdo->query("SELECT * FROM funcionarios WHERE ativo = 1 ORDER BY nome ASC");
    $funcionarios_lista = $stmt_func->fetchAll(PDO::FETCH_ASSOC);

    // 3. Monta a Folha de Pagamento Dinâmica cruzando 'especialidade' com 'nome_cargo'
    $folha_equipa = [];
    foreach ($funcionarios_lista as $func) {
        $especialidade = mb_strtolower($func['especialidade'], 'UTF-8');
        $salario_base_encontrado = 50000; // Valor padrão/mínimo caso não encontre correspondência
        $cargo_nome_exibicao = "Profissional Geral";

        // Varre a tabela de salários para associar o ordenado por texto aproximado
        foreach ($lista_referencia_cargos as $cargo) {
            $cargo_low = mb_strtolower($cargo['nome_cargo'], 'UTF-8');
            
            // Se a especialidade contiver palavras como "barbeiro", "cabeleireiro", "pedicure", associa o salário correto
            if (strpos($especialidade, 'barbeiro') !== false && strpos($cargo_low, 'barbeiro') !== false) {
                $salario_base_encontrado = $cargo['salario_base'];
                $cargo_nome_exibicao = $cargo['nome_cargo'];
                break;
            }
            if ((strpos($especialidade, 'cabelereiro') !== false || strpos($especialidade, 'cabeleireira') !== false) && strpos($cargo_low, 'cabeleireiro') !== false) {
                $salario_base_encontrado = $cargo['salario_base'];
                $cargo_nome_exibicao = $cargo['nome_cargo'];
                break;
            }
            if (strpos($especialidade, 'pedicure') !== false && strpos($cargo_low, 'limpeza') === false) {
                $salario_base_encontrado = 60000; // Aloca um piso médio de atendimento
                $cargo_nome_exibicao = "Mestre Estética";
            }
            if (strpos($especialidade, 'manicure') !== false && $cargo_nome_exibicao == "Profissional Geral") {
                $salario_base_encontrado = 60000;
                $cargo_nome_exibicao = "Mestre Estética";
            }
        }

        // Adiciona o funcionário processado à folha ativa com as colunas corretas
        $folha_equipa[] = [
            'id_funcionario' => $func['id_funcionario'],
            'nome' => $func['nome'],
            'nome_cargo' => $cargo_nome_exibicao,
            'salario_base' => $salario_base_encontrado,
            'status_escala' => $func['status']
        ];
    }

    // 4. Carrega o Faturamento Diário Real com base nas sessões do dia
    $hoje = date('Y-m-d');
    $stmt_lucros = $pdo->prepare("SELECT profissional, COUNT(*) as total_atendimentos, SUM(valor_liquido) as total_gerado 
                                  FROM pagamentos 
                                  WHERE data_servico = ? 
                                  GROUP BY profissional 
                                  ORDER BY total_gerado DESC");
    $stmt_lucros->execute([$hoje]);
    $ranking_lucros = $stmt_lucros->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    $lista_referencia_cargos = [];
    $folha_equipa = [];
    $ranking_lucros = [];
}
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
        <meta charset="UTF-8">
        <title>Autenticação de Gerência - Grupo Aurélius</title>
        <style>
            body { background-color: #0f172a; color: #fff; font-family: 'Segoe UI', sans-serif; display: flex; justify-content: center; align-items: center; height: 100vh; margin: 0; }
            .caixa-login { background: #1e293b; padding: 40px 35px; border-radius: 12px; border: 2px solid #ca8a04; width: 100%; max-width: 380px; box-shadow: 0 10px 25px rgba(0,0,0,0.5); text-align: center; }
            .caixa-login h2 { color: #ca8a04; margin: 0 0 8px 0; font-size: 20px; letter-spacing: 0.5px; text-transform: uppercase; }
            .caixa-login p { color: #94a3b8; font-size: 13px; margin: 0 0 25px 0; }
            .campo-input { width: 100%; padding: 12px; margin-bottom: 15px; border-radius: 6px; border: 1px solid #334155; background: #0f172a; color: white; box-sizing: border-box; text-align: center; font-size: 14px; outline: none; }
            .campo-input:focus { border-color: #ca8a04; }
            .btn-entrar { width: 100%; padding: 12px; background: linear-gradient(135deg, #ca8a04, #a16207); border: none; color: #0f172a; font-weight: bold; border-radius: 6px; cursor: pointer; text-transform: uppercase; font-size: 13px; letter-spacing: 0.5px; transition: 0.2s; }
            .btn-entrar:hover { transform: translateY(-1px); box-shadow: 0 4px 12px rgba(202, 138, 4, 0.2); }
            .msg-erro { color: #f87171; background: rgba(248, 113, 113, 0.1); padding: 10px; border-radius: 6px; font-size: 12px; margin-bottom: 15px; font-weight: 500; border: 1px solid rgba(248, 113, 113, 0.2); }
        </style>
    </head>
    <body>
        <div class="caixa-login">
            <h2>🔒 AUDITORIA DE GERÊNCIA</h2>
            <p>Introduza as credenciais restritas do Grupo Aurélius</p>
            
            <?php if (!empty($erro_login)): ?>
                <div class="msg-erro"><?php echo $erro_login; ?></div>
            <?php endif; ?>
            
            <form method="POST" action="">
                <input type="text" name="usuario_gerente" class="campo-input" placeholder="Utilizador Administrativo" required autocomplete="off">
                <input type="password" name="senha_gerente" class="campo-input" placeholder="Palavra-passe de Acesso" required>
                <button type="submit" name="entrar_gerencia" class="btn-entrar">Desbloquear Painel</button>
            </form>
        </div>
    </body>
    </html>
    <?php
    exit;


// 1. CARREGAMENTO OBRIGATÓRIO DA LIGAÇÃO ANTES DE QUALQUER CONSULTA
if (file_exists("Conexao.php")) {
    include_once("Conexao.php");
} elseif (file_exists("config/Banco.php")) {
    include_once("config/Banco.php");
} elseif (file_exists("conect.php")) {
    include_once("conect.php");
}

// 2. IDENTIFICAÇÃO DINÂMICA DO OBJETO DE CONEXÃO ATIVO
$conexao_ativa = null;
if (isset($pdo) && $pdo !== null) {
    $conexao_ativa = $pdo;
} elseif (isset($mysqli) && $mysqli !== null) {
    $conexao_ativa = $mysqli;
}


// =========================================================================
// 👑 CÁLCULO AUTOMÁTICO DO CLIENTE VIP DO ANO
// =========================================================================
$nome_vip_ano = 'Aurélio Sacalumbo';
$pedidos_vip_ano = 12;

// Uniformiza a variável de conexão ativa caso ela use $pdo
if (!isset($conexao_ativa) && isset($pdo)) {
    $conexao_ativa = $pdo;
}

if (isset($conexao_ativa) && $conexao_ativa !== null) {
    try {
        $sql_vip_automatico = "SELECT nome, COUNT(*) as total_pedidos FROM depoimentos GROUP BY nome ORDER BY total_pedidos DESC LIMIT 1";
        
        if ($conexao_ativa instanceof PDO) {
            $stmt_vip_ano = $conexao_ativa->query($sql_vip_automatico);
            $cliente_vip_real = $stmt_vip_ano->fetch(PDO::FETCH_ASSOC);
            if (!empty($cliente_vip_real['nome'])) {
                $nome_vip_ano = $cliente_vip_real['nome'];
                $pedidos_vip_ano = $cliente_vip_real['total_pedidos'];
            }
        } else {
            $res_vip = $conexao_ativa->query($sql_vip_automatico);
            if ($res_vip && $cliente_vip_real = $res_vip->fetch_assoc()) {
                $nome_vip_ano = $cliente_vip_real['nome'];
                $pedidos_vip_ano = $cliente_vip_real['total_pedidos'];
            }
        }
    } catch (Exception $e) {
        $nome_vip_ano = 'Aurélio Sacalumbo';
        $pedidos_vip_ano = 12;
    }
}

$ranking_funcionarios_reais = [];

if (isset($pdo) && $pdo !== null) {
    try {
        $primeiro_dia_mes = date('Y-m-01');
        $ultimo_dia_mes = date('Y-m-t');

        // 🛡️ CORREÇÃO REAL: Mudado de 'SUM(valor_liquido)' para 'SUM(valor)' para ler a sua coluna real do MySQL
        $sql_ranking = "SELECT 
                            profissional AS nome_funcionario, 
                            COUNT(*) AS total_pedidos, 
                            SUM(valor) AS faturamento_gerado 
                        FROM pagamentos 
                        WHERE data_servico BETWEEN ? AND ? 
                        GROUP BY profissional 
                        ORDER BY total_pedidos DESC, faturamento_gerado DESC";

        $stmt_rank = $pdo->prepare($sql_ranking);
        $stmt_rank->execute([$primeiro_dia_mes, $ultimo_dia_mes]);
        $resultados_brutos = $stmt_rank->fetchAll(PDO::FETCH_ASSOC);

        foreach ($resultados_brutos as $linha) {
            if (empty($linha['nome_funcionario'])) continue;

            $nome_lower = mb_strtolower($linha['nome_funcionario'], 'UTF-8');
            $cargo_fake = "Mestre de Atendimento";
            
            // Define o cargo visual com base no nome do profissional listado
            if (strpos($nome_lower, 'handanga') !== false || strpos($nome_lower, 'fernandinho') !== false) {
                $cargo_fake = "Mestre Barbeiro";
            } elseif (strpos($nome_lower, 'aurélio') !== false || strpos($nome_lower, 'angelino') !== false) {
                $cargo_fake = "Mestre Cabeleireiro";
            } elseif (strpos($nome_lower, 'tuxa') !== false || strpos($nome_lower, 'edna') !== false || strpos($nome_lower, 'belma') !== false) {
                $cargo_fake = "Profissional Cabeleireira";
            } elseif (strpos($nome_lower, 'albino') !== false || strpos($nome_lower, 'magui') !== false) {
                $cargo_fake = "Esteticista Especialista";
            } elseif (strpos($nome_lower, 'dalton') !== false || strpos($nome_lower, 'raimundo') !== false || strpos($nome_lower, 'zidane') !== false) {
                $cargo_fake = "Manicure / Pedicure";
            }

            $total_pedidos_real = (int)($linha['total_pedidos'] ?? 0);
            $valor_monetario_real = (float)($linha['faturamento_gerado'] ?? 0.00);

            $ranking_funcionarios_reais[] = [
                'nome_funcionario' => $linha['nome_funcionario'],
                'nome_cargo' => $cargo_fake,
                'total_pedidos' => $total_pedidos_real,
                'faturamento_gerado' => $valor_monetario_real
            ];
        }

        // Reordena o array para garantir o topo correto baseado na produtividade real
        usort($ranking_funcionarios_reais, function($a, $b) {
            if ($a['total_pedidos'] === $b['total_pedidos']) {
                return $b['faturamento_gerado'] <=> $a['faturamento_gerado'];
            }
            return $b['total_pedidos'] <=> $a['total_pedidos'];
        });

    } catch (PDOException $e) {
        $ranking_funcionarios_reais = [];
    }
}
?>


<?php
// Admini.php - Consultas do Plano de Cargos e Salários
include_once("Conexao.php");

try {
    // 1. A sua consulta original da Folha de Pagamento da Equipa (RH)
    $sql_folha = "SELECT 
                    f.nome, 
                    c.nome_cargo, 
                    dp.salario_base as salario_cargo, 
                    dp.bonus_horas_extras, 
                    dp.telefone_pessoal
                  FROM funcionarios_dados_pessoais dp
                  LEFT JOIN funcionarios f ON dp.id_funcionario = f.id_funcionario
                  LEFT JOIN cargos_salarios c ON dp.id_cargo = c.id_cargo
                  ORDER BY dp.salario_base DESC";
    
    $query_folha = $pdo->query($sql_folha);
    $folha_pagamento_equipa = $query_folha->fetchAll(PDO::FETCH_ASSOC);


 
    $mes_pesquisa = (isset($_GET['filtro_mes']) && !empty($_GET['filtro_mes'])) ? htmlspecialchars(trim($_GET['filtro_mes'])) : '2026-06';

    $sql_parceiros = "SELECT 
                        p.id_parceiro,
                        p.nome_estabelecimento, 
                        IFNULL(p.percentual_comissao, 10.00) as percentual_comissao, 
                        IFNULL((SELECT SUM(total_faturado) FROM faturamento_parceiros WHERE id_parceiro = p.id_parceiro), 0.00) as total_faturado, 
                        IFNULL((SELECT SUM(comissao_paga) FROM faturamento_parceiros WHERE id_parceiro = p.id_parceiro), 0.00) as comissao_paga,
                        /* 🌟 NOVO: Puxa o status real da negociação mais recente da empresa */
                        IFNULL((SELECT status_auditoria FROM faturamento_parceiros WHERE id_parceiro = p.id_parceiro ORDER BY id_faturamento DESC LIMIT 1), 'Aguardando') as status_auditoria
                      FROM saloes_parceiros p
                      ORDER BY total_faturado DESC";

    $stmt_p = $pdo->prepare($sql_parceiros);
    $stmt_p->execute();
    $lista_parceiros_reais = $stmt_p->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    // Evita travar a renderização do layout escuro do painel
    $lista_parceiros_reais = [];
}
?>

<?php
require_once __DIR__ . "/config/Banco.php";

// Captura a data de hoje exatamente no formato do seu banco de dados (AAAA-MM-DD)
$hoje = date('Y-m-d');

// PROCESSA ACÇÃO DE ATUALIZAÇÃO DE STATUS SE REQUISITADA
if (isset($_GET['acao']) && $_GET['acao'] === 'atualizar_status' && isset($_GET['id']) && isset($_GET['status'])) {
    try {
        $id_func = (int)$_GET['id'];
        $novo_status = trim($_GET['status']);
        $update = $pdo->prepare("UPDATE funcionarios SET status = :status WHERE id_funcionario = :id");
        $update->execute([':status' => $novo_status, ':id' => $id_func]);
        $pdo = new PDO("mysql:host=localhost;dbname=aurelius_salao;charset=utf8mb4", "root", "");
        echo "<script>window.location.href='admini.php';</script>";
exit;
    } catch (PDOException $e) {
        die("Erro ao atualizar status: " . $e->getMessage());
    }
}

try {

    // Processa a resposta que o gerente acabou de escrever
if (isset($_GET['acao']) && $_GET['acao'] === 'responder_cliente' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $id_dep = (int)$_POST['id_depoimento'];
    $texto_resp = trim($_POST['texto_resposta']);
    
    $stmtResp = $pdo->prepare("UPDATE depoimentos SET resposta_gerente = :resp WHERE id = :id");
    $stmtResp->execute([':resp' => $texto_resp, ':id' => $id_dep]);
    
    header("Location: Admini.php?respondido=1");
    exit;
}

// Puxa todos os comentários do banco para o gerente analisar
$queryDepAdmin = $pdo->query("SELECT * FROM depoimentos ORDER BY id DESC");
$depoimentos_gerente = $queryDepAdmin->fetchAll();
    // 1. CAIXA DO DIA (Soma apenas os pagamentos com a data de hoje)
    $queryCaixa = $pdo->prepare("SELECT SUM(valor) as total_hoje FROM pagamentos WHERE data_servico = :hoje");
    $queryCaixa->execute([':hoje' => $hoje]);
    $caixaDia = $queryCaixa->fetch()['total_hoje'] ?? 0.00;

    // 2. ATENDIMENTOS HOJE (Dupla verificação inteligente)
    $querySessoes = $pdo->prepare("SELECT COUNT(*) as sessoes_hoje FROM agendamentos WHERE data_servico = :hoje");
    $querySessoes->execute([':hoje' => $hoje]);
    $sessoesHoje = $querySessoes->fetch()['sessoes_hoje'] ?? 0;

    if ($sessoesHoje == 0) {
        $querySessoesPag = $pdo->prepare("SELECT COUNT(*) as sessoes_hoje FROM pagamentos WHERE data_servico = :hoje");
        $querySessoesPag->execute([':hoje' => $hoje]);
        $sessoesHoje = $querySessoesPag->fetch()['sessoes_hoje'] ?? 0;
    }

    // 3. CONSULTA DOS FUNCIONÁRIOS (Resolve o erro do painel vazio)
    $queryFunc = $pdo->query("SELECT * FROM funcionarios ORDER BY nome ASC");
    $funcionarios = $queryFunc->fetchAll();
    
    // Total de profissionais registados
    $totalProfissionais = count($funcionarios);

} catch (PDOException $e) {
    die("Erro ao carregar contadores do painel: " . $e->getMessage());
}
?>

<!-- PASTA RETRÁTIL DE FEEDBACK E RESPOSTAS -->
<div style="width: 100%; max-width: 1000px; margin: 30px auto 15px auto; text-align: left;">
    
    <!-- O BOTÃO QUE ABRE E FECHA A PASTA -->
    <button onclick="alternarPastaAdmin()" style="background: linear-gradient(135deg, #1e3a8a, #1e293b); color: #38bdf8; border: 1px solid #334155; padding: 12px 20px; font-size: 14px; font-weight: bold; border-radius: 8px; cursor: pointer; width: 100%; text-align: left; display: flex; justify-content: space-between; align-items: center; outline: none; box-shadow: 0 4px 6px rgba(0,0,0,0.1);">
        <span>CARREGAR PASTA: Feedback e Resposta a Clientes</span>
        <span id="setaPastaAdmin" style="font-size: 16px;">▼</span>
    </button>



    <!-- CONTEÚDO INTERNO DA PASTA -->
    <div id="conteudoPastaAdmin" style="display: none;border: 3px solid #fff; background: #1e293b; border: 1px solid #334155; border-top: none; padding: 20px; border-radius: 0 0 8px 8px; margin-top: -2px;">
        <h3 style="color: #38bdf8; font-size: 15px; text-transform: uppercase; margin-bottom: 15px; border-bottom: 1px solid #334155; padding-bottom: 8px;">📣 Central Atendimento Geral - Auditoria de Feedbacks</h3>
        
        <?php if(empty($depoimentos_gerente)): ?>
            <p style="color: #94a3b8; font-size: 13px;">Nenhuma avaliação ou reclamação registada no banco de dados.</p>
        <?php else: ?>
            <div style="display: flex; flex-direction: column; gap: 15px;">
            
                <?php foreach($depoimentos_gerente as $dep_g): ?>
                    <div style="background: #0f172a; border: 1px solid #334155; padding: 15px; border-radius: 6px; display: flex; gap: 15px; align-items: flex-start;">
                        
                        <!-- FOTO DO DEPOIMENTO CORRIGIDA AQUI (Sem FILTER_VALIDATE_URL para aceitar fotos locais do frame) -->
                        <img src="<?php echo (!empty($dep_g['foto_url'])) ? htmlspecialchars($dep_g['foto_url']) : 'https://flaticon.com'; ?>" style="width: 45px; height: 45px; border-radius: 50%; object-fit: cover; border: 2px solid #38bdf8; background-color: #1e293b; flex-shrink: 0;" alt="User">
                    
                        <div style="flex: 1;">
                            <div style="display: flex; justify-content: space-between; font-size: 13px; font-weight: bold;">
                                <span style="color: #e2e8f0;"><?php echo htmlspecialchars($dep_g['nome']); ?></span>
                                <span style="color: #eab308;"><?php echo str_repeat('★', $dep_g['estrelas']); ?></span>
                            </div>
                            <p style="color: #cbd5e1; font-size: 13px; margin: 5px 0 10px 0;">"<?php echo htmlspecialchars($dep_g['comentario']); ?>"</p>
                            
                            <?php if(!empty($dep_g['resposta_gerente'])): ?>
                                <div style="background: rgba(34, 197, 94, 0.1); border-left: 3px solid #22c55e; padding: 8px; border-radius: 4px; font-size: 12px; color: #22c55e;">
                                    <b>✓ Sua Resposta:</b> <?php echo htmlspecialchars($dep_g['resposta_gerente']); ?>
                                </div>
                            <?php else: ?>
                                <form action="Admini.php?acao=responder_cliente" method="POST" style="margin: 0; display: flex; gap: 8px;">
                                    <input type="hidden" name="id_depoimento" value="<?php echo $dep_g['id']; ?>">
                                    <input type="text" name="texto_resposta" placeholder="Escreva um agradecimento ou solução..." style="flex: 1; background: #1e293b; border: 1px solid #475569; color: white; padding: 6px 10px; border-radius: 4px; font-size: 12px; outline: none;" required>
                                    <button type="submit" style="background: #22c55e; color: white; border: none; padding: 6px 12px; border-radius: 4px; font-size: 12px; font-weight: bold; cursor: pointer;">Responder</button>
                                </form>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</div>

















<?php
// ... (Código de ligação à base de dados anterior)

$sino_notificacoes = [];
if (isset($pdo)) {
    try {
        // Consulta Unificada SaaS Reativa com Filtro de Desconto de Alertas
        $sql_sino = "
            -- 1. Captura APENAS Faturas de Produtos que ainda NÃO foram validadas
            SELECT loja_id AS id_parceiro, 'loja' AS tipo_parceiro, COUNT(*) AS total_pedidos 
            FROM `faturamento_parceiros` 
            WHERE `status_pagamento` = 'Aguardando_Liberacao_SaaS'
            GROUP BY loja_id

            UNION ALL

            -- 2. Captura Cortes/Serviços que ainda NÃO foram liquidados pela gerência
            SELECT id_parceiro, tipo_parceiro, COUNT(*) AS total_pedidos 
            FROM `pagamentos` 
            WHERE `status_trabalho` = 'Concluido' 
              AND `id` NOT IN (SELECT DISTINCT pedido_id FROM `saques_parceiros` WHERE pedido_id IS NOT NULL)
            GROUP BY id_parceiro, tipo_parceiro
        ";
        
        $stmt_sino = $pdo->query($sql_sino);
        $sino_notificacoes = $stmt_sino->fetchAll(PDO::FETCH_ASSOC);
    } catch (Exception $e) {
        $sino_notificacoes = [];
    }
}
?>

<!DOCTYPE html>
<html lang="pt-PT">
<head>
    <meta charset="UTF-8">
    <title>Aurelius Master - Gestão de Conta Única</title>
    <style>
        body { background-color: #0f172a; color: #fff; font-family: 'Segoe UI', sans-serif; padding: 30px; }
        .topo-barra { display: flex; justify-content: space-between; align-items: center; border-bottom: 1px solid #334155; padding-bottom: 15px; }
        .sino-container { position: relative; display: inline-block; }
        .sino-btn { background: #1e293b; border: 1px solid #334155; color: #fff; font-size: 24px; padding: 10px; border-radius: 50%; cursor: pointer; }
        .bolha { position: absolute; top: -2px; right: -2px; background: #ef4444; color: #fff; font-size: 11px; padding: 2px 6px; border-radius: 50%; font-weight: bold; }
        .dropdown-sino { position: absolute; top: 55px; right: 0; width: 300px; background: #111827; border: 1px solid #1e293b; border-radius: 8px; display: none; z-index: 1000; box-shadow: 0 10px 25px rgba(0,0,0,0.5); }
        .dropdown-item { padding: 12px; border-bottom: 1px solid #1f2937; color: #38bdf8; text-decoration: none; display: block; font-size: 13px; }
        .dropdown-item:hover { background: #1f2937; }
        .painel-financas { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-top: 20px; }
        .card-caixa { background: #1e293b; padding: 20px; border-radius: 12px; border: 1px solid #334155; text-align: center; }
        table { width: 100%; border-collapse: collapse; margin-top: 25px; background: #1e293b; border-radius: 8px; overflow: hidden; }
        th, td { padding: 15px; text-align: left; border-bottom: 1px solid #334155; }
        th { background: #14424b; color: aqua; }
        .btn-deposito { background: #22c55e; color: #000; padding: 8px 14px; text-decoration: none; border-radius: 4px; font-weight: bold; font-size: 12px; }
        .destaque-linha { background: rgba(56, 189, 248, 0.1) !important; border: 2px solid #38bdf8 !important; }
    </style>
</head>
<body>

<div class="topo-barra" style="display: flex; justify-content: space-between; align-items: center; background: #111827; padding: 15px 20px; border-radius: 12px; border: 1px solid #1f2937; margin-bottom: 25px;">
<h2 style="margin: 0; font-size: 18px; color: #fff;">🛰️ Tesouraria Centralizada Aurelius</h2>

<!-- SINO INTERATIVO COM DROPDOWN MULTI-SERVIÇOS AUTOMÁTICO -->
<div class="sino-container" style="position: relative;">
    <button class="sino-btn" onclick="toggleSino()" style="background: #1e293b; border: 1px solid #334155; color: #fff; padding: 10px 16px; border-radius: 8px; font-weight: bold; cursor: pointer; display: flex; align-items: center; gap: 8px; font-size: 14px;">
        🔔 Sinalizador Geral 
        <?php if (count($sino_notificacoes) > 0): ?>
            <span class="bolha" style="background: #ef4444; color: #fff; font-size: 10px; padding: 2px 6px; border-radius: 50%; font-weight: bold; animation: pulse 2s infinite;"><?= count($sino_notificacoes) ?></span>
        <?php endif; ?>
    </button>
    
    <div class="dropdown-sino" id="boxSino" style="display: none; position: absolute; right: 0; top: 45px; background: #1e293b; border: 1px solid #334155; border-radius: 8px; width: 320px; box-shadow: 0 10px 15px -3px rgba(0,0,0,0.5); z-index: 999; overflow: hidden;">
        <div style="padding:12px; font-weight:bold; background:#111827; font-size:11px; color: #ca8a04; letter-spacing: 0.5px; text-transform: uppercase;">🔔 Alertas Ativos no Ecossistema:</div>
        
        <div style="max-height: 350px; overflow-y: auto;">
            <?php foreach($sino_notificacoes as $notif): 
                $n_nome = "Parceiro ".$notif['id_parceiro'];
                $rotulo_badge = ($notif['tipo_parceiro'] === 'loja') ? '🛒 Venda Produto' : '💈 Corte / Serviço';
                $cor_badge = ($notif['tipo_parceiro'] === 'loja') ? 'color: #eab308; background: rgba(234, 179, 8, 0.1); border: 1px solid #eab308;' : 'color: #38bdf8; background: rgba(56, 189, 248, 0.1); border: 1px solid #38bdf8;';

                if($notif['tipo_parceiro'] === 'loja') {
                    $p_query = $pdo->query("SELECT nome_loja FROM lojas WHERE id=".intval($notif['id_parceiro']))->fetch();
                    if($p_query) $n_nome = $p_query['nome_loja'];
                } else {
                    $p_query = $pdo->query("SELECT nome FROM usuario WHERE codigo=".intval($notif['id_parceiro']))->fetch();
                    if($p_query) $n_nome = $p_query['nome'];
                }
            ?>
                <a href="Admini.php?parceiro_alerta=<?= intval($notif['id_parceiro']) ?>&tipo_alerta=<?= htmlspecialchars($notif['tipo_parceiro']) ?>" class="dropdown-item" style="display: block; padding: 12px; border-bottom: 1px solid #1f2937; text-decoration: none; color: #cbd5e1; transition: background 0.2s;" onmouseover="this.style.background='#111827';" onmouseout="this.style.background='transparent';">
                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 4px;">
                        <span style="font-size: 13px; color: #fff; font-weight: bold;">🏬 <?= htmlspecialchars($n_nome) ?></span>
                        <span style="font-size: 9px; padding: 1px 5px; border-radius: 4px; font-weight: bold; text-transform: uppercase; <?= $cor_badge ?>"><?= $rotulo_badge ?></span>
                    </div>
                    <span style="color:#94a3b8; font-size:11px; display: block; line-height: 1.4;">Identificadas <b><?= intval($notif['total_pedidos']) ?></b> novas atividades pendentes de processamento fiscal.</span>
                </a>
            <?php endforeach; ?>
        </div>

        <?php if(count($sino_notificacoes) == 0): ?>
            <div style="padding:20px; text-align:center; font-size:12px; color:#64748b; font-style: italic;">Ecossistema estável. Sem novas faturas pendentes de triagem.</div>
        <?php endif; ?>
    </div>
</div>
</div>

<script>
// Motor de abertura e fecho do dropdown do sino
function toggleSino() {
const box = document.getElementById('boxSino');
if (box.style.display === 'none' || box.style.display === '') {
    box.style.display = 'block';
} else {
    box.style.display = 'none';
}
}
</script>




<!-- 🔘 BOTÃO DE CONTROLO DA ABA SANFONA (ACCORDION HEADER) -->
<button type="button" id="btn_toggle_saas" onclick="alternarMesaSaaS()" style="width: 100%; background: #111827; border: 2px solid #ca8a04; padding: 15px 20px; border-radius: 12px; color: #eab308; font-size: 14px; font-weight: bold; text-align: left; display: flex; justify-content: space-between; align-items: center; outline: none; cursor: pointer; margin-bottom: 5px; box-shadow: 0 4px 15px rgba(202, 138, 4, 0.1); font-family: sans-serif;">
    <span>🛡️ MESA CENTRAL DE LIQUIDAÇÃO & AUDITORIA SAAS</span>
    <span id="txt_status_saas">▾ Expandir Painel Financeiro</span>
</button>

<!-- 📦 CONTEÚDO RECOLHÍVEL DA ABA -->
<div id="corpo_mesa_saas" style="display: none; padding: 25px; background: #0f172a; border: 2px solid #ca8a04; border-top: none; border-radius: 0 0 12px 12px; margin-bottom: 40px; box-sizing: border-box;">
    
    <!-- Bloco Informativo de Custódia -->
    <div style="background: rgba(202, 138, 4, 0.05); border: 1px dashed #ca8a04; padding: 20px; border-radius: 12px; margin-bottom: 25px;">
        <span style="color: #eab308; font-size: 11px; font-weight: bold; text-transform: uppercase; letter-spacing: 0.5px;">🏦 CONTA DE CUSTÓDIA CENTRAL (GRUPO AURÉLIUS SAAS):</span>
        <h3 style="margin: 8px 0 0 0; font-family: monospace; font-size: 18px; color: #fff; font-weight: bold; letter-spacing: 1px;">
    💳 IBAN ARRECADAÇÃO: <?= isset($iban_grupo_aurelius) ? htmlspecialchars($iban_grupo_aurelius) : "AO06.0040.0000.9068.8685.1014.8" ?>
</h3>

        <p style="color: #94a3b8; font-size: 12px; margin: 5px 0 0 0;">Controlo unificado de retaguarda. Valide as entradas de capitais locais e ordene os repasses fiscais.</p>
    </div>

    <!-- Grelha da Tabela de Triagem e Liquidação -->
    <div style="overflow-x: auto; width: 100%;">
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
                    <tr>
                        <td colspan="8" style="text-align: center; color: #64748b; padding: 40px; font-style: italic;">
                            Nenhum repasse mercantil aguardando liquidação manual neste ciclo.
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($auditoria_saas as $row): ?>
                        <?php 
                            $is_domicilio = (isset($row['tipo_atendimento']) && $row['tipo_atendimento'] === 'Domicilio');
                            $confirmado_entrega = (isset($row['confirmado_na_entrega']) && $row['confirmado_na_entrega'] == 1);
                            $bloquear_liberacao = ($is_domicilio && !$confirmado_entrega);
                        ?>
                        <tr style="border-bottom: 1px solid #1f2937;">
                            <td style="padding: 12px;">
                                <strong style="color: #fff;"><?= htmlspecialchars($row['nome_loja']) ?></strong><br>
                                <span style="color: #64748b; font-size: 11px;">ID: <?= intval($row['loja_id']) ?></span>
                            </td>
                            <td style="padding: 12px;">
                                <?= htmlspecialchars($row['nome_cliente']) ?><br>
                                <span class="badge-tipo" style="<?= $is_domicilio ? 'background: rgba(168, 85, 247, 0.1); color: #a855f7; border: 1px solid #a855f7;' : 'background: rgba(56, 189, 248, 0.1); color: #38bdf8; border: 1px solid #38bdf8;' ?>">
                                    <?= htmlspecialchars($row['tipo_atendimento']) ?>
                                </span>
                            </td>
                            <td style="padding: 12px; font-weight: bold; color: <?= $confirmado_entrega ? '#22c55e' : ($is_domicilio ? '#ef4444' : '#94a3b8') ?>;">
                                <?= !$is_domicilio ? 'Balcão' : ($confirmado_entrega ? '✓ Sim' : '⏳ Não') ?>
                            </td>
                            <td style="padding: 12px; font-weight: bold; color: #fff;"><?= number_format($row['valor_bruto'], 2, ',', '.') ?> AOA</td>
                            <td style="padding: 12px; color: #ef4444; font-weight: bold;">-<?= number_format($row['comissao_retida'], 2, ',', '.') ?> AOA</td>
                            <td style="padding: 12px; color: #22c55e; font-weight: bold; font-size: 14px;"><?= number_format($row['valor_liquido'], 2, ',', '.') ?> AOA</td>
                            <td style="padding: 12px; color: #eab308; font-family: monospace; font-size: 12px; font-weight: bold;"><?= htmlspecialchars($row['iban_loja']) ?></td>
                            <td style="padding: 12px;">
                                <?php if ($bloquear_liberacao): ?>
                                    <button type="button" class="btn-disabled" disabled>🔒 Aguardar Entrega</button>
                                <?php else: ?>
                                    <div style="display: flex; gap: 6px; justify-content: center;">
                                        <?php if ($row['status_pagamento'] === 'Aguardando_Liberacao_SaaS'): ?>
                                            <!-- 🔎 PASSO 1: Botão Validar de Auditoria de Depósito -->
                                            <form method="POST" style="margin:0; width: 100%;">
                                                <input type="hidden" name="faturamento_id" value="<?= intval($row['faturamento_id']) ?>">
                                                <button type="submit" name="validar_entrada_saas" class="btn-saas-validar">✓ Validar</button>
                                            </form>
                                        <?php elseif ($row['status_pagamento'] === 'Validado_Aguardando_Transferencia'): ?>
                                            <!-- 💸 PASSO 2: Repasse de Liquidação para o IBAN do Parceiro -->
                                            <form method="POST" onsubmit="return confirm('Autoriza a transferência de <?= number_format($row['valor_liquido'], 2, ',', '.') ?> AOA para o IBAN desta Distribuidora?')" style="margin:0; width: 100%;">
                                                <input type="hidden" name="faturamento_id" value="<?= intval($row['faturamento_id']) ?>">
                                                <input type="hidden" name="pedido_id" value="<?= intval($row['pedido_id']) ?>">
                                                <button type="submit" name="liberar_pagamento_saas" class="btn-saas-transferir">💸 Enviar ao IBAN</button>
                                            </form>
                                        <?php endif; ?>
                                    </div>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<script>
function alternarMesaSaaS() {
    const mesa = document.getElementById('corpo_mesa_saas');
    const txt = document.getElementById('txt_status_saas');
    const btn = document.getElementById('btn_toggle_saas');
    
    if (mesa.style.display === 'none' || mesa.style.display === '') {
        mesa.style.display = 'block';
        txt.innerText = '▴ Recolher Painel Financeiro';
        btn.style.borderRadius = "12px 12px 0 0";
    } else {
        mesa.style.display = 'none';
        txt.innerText = '▾ Expandir Painel Financeiro';
        btn.style.borderRadius = "12px";
    }
}

// Tranca absoluta: força a aba a nascer SEMPRE fechada a cada refresh ou clique de formulário
document.addEventListener("DOMContentLoaded", function() {
    const mesa = document.getElementById('corpo_mesa_saas');
    const txt = document.getElementById('txt_status_saas');
    const btn = document.getElementById('btn_toggle_saas');
    
    if (mesa)  mesa.style.display = 'none';
    if (txt)   txt.innerText = '▾ Expandir Painel Financeiro';
    if (btn)   btn.style.borderRadius = "12px";
});
</script>




<!-- =========================================================================
     🔘 1. BOTÃO DE CONTROLO DA TESOURARIA (ACCORDION HEADER)
     ========================================================================= -->
     <button type="button" id="btn_toggle_tesouraria" onclick="alternarPainelTesouraria()" style="width: 100%; background: #111827; border: 2px solid #38bdf8; padding: 15px 20px; border-radius: 12px; color: #38bdf8; font-size: 14px; font-weight: bold; text-align: left; display: flex; justify-content: space-between; align-items: center; outline: none; cursor: pointer; margin-top: 20px; margin-bottom: 5px; box-shadow: 0 4px 15px rgba(56, 189, 248, 0.1); font-family: sans-serif;">
     <span style="display: flex; align-items: center; gap: 10px;">📊 TESOURARIA CENTRALIZADA & CONTAS FISCAIS INDIVIDUAIS</span>
     <span id="txt_status_tesouraria">▾ Expandir Módulo de Contas</span>
 </button>
 
 <!-- =========================================================================
      📦 2. CONTEÚDO RECOLHÍVEL DO PAINEL (CUSTÓDIA E TABELA DE PARCEIROS)
      ========================================================================= -->
 <div id="corpo_painel_tesouraria" style="display: none; padding: 25px; background: #0f172a; border: 2px solid #38bdf8; border-top: none; border-radius: 0 0 12px 12px; margin-bottom: 40px; box-sizing: border-box;">
 
     <!-- Indicadores Financeiros da Tesouraria -->
     <div class="painel-financas" style="display: flex; gap: 20px; margin-bottom: 25px;">
         <div class="card-caixa" style="border: 1px solid #38bdf8; background: #1e293b; padding: 20px; border-radius: 12px; flex: 1; box-shadow: 0 4px 10px rgba(0,0,0,0.2);">
             <span style="font-size: 12px; color: #94a3b8; font-weight: bold; text-transform: uppercase;">Custódia Global Arrecadada (A Pagar aos Parceiros)</span>
             <span style="font-size: 26px; font-weight: bold; display:block; margin-top:5px; color: #fff;">
                 <?= number_format((($financeiro['bruto'] ?? 0) - ($financeiro['lucro_retido'] ?? 0)), 2, ',', '.'); ?> AOA
             </span>
         </div>
         <div class="card-caixa" style="border: 1px solid #22c55e; background: #1e293b; padding: 20px; border-radius: 12px; flex: 1; box-shadow: 0 4px 10px rgba(0,0,0,0.2);">
             <span style="color:#22c55e; font-size: 12px; font-weight: bold; text-transform: uppercase;">Margem Operacional Aurelius Retida (10% Líquido)</span>
             <span style="font-size: 26px; font-weight: bold; color:#22c55e; display:block; margin-top:5px;">
                 <?= number_format($financeiro['lucro_retido'] ?? 0, 2, ',', '.'); ?> AOA
             </span>
         </div>
     </div>
 
     <h3 style="color: #fff; font-size: 16px; margin-bottom: 15px;">🧾 Repasse Individual de Contas Fiscais</h3>
     
     <?php if (isset($id_filtro_sino) && $id_filtro_sino > 0): ?>
         <p style="color:#38bdf8; font-weight:bold; font-size: 13px; margin-bottom: 15px;">[ FILTRO DO SINO ACTIVADO: A mostrar apenas a empresa com atividade pendente ] - <a href="Admini.php" style="color:#ef4444; margin-left:10px; text-decoration: none;">Limpar Filtro</a></p>
     <?php endif; ?>
 
     <div style="overflow-x: auto; width: 100%;">
         <table style="width: 100%; border-collapse: collapse; background: #1e293b; border-radius: 8px; overflow: hidden; border: 1px solid #334155;">
             <thead>
                 <tr style="background: #111827; color: #ca8a04; text-transform: uppercase; font-size: 11px; letter-spacing: 0.5px;">
                     <th style="padding: 12px; text-align: left;">Nome do Estabelecimento / Loja</th>
                     <th style="padding: 12px; text-align: left;">Segmento</th>
                     <th style="padding: 12px; text-align: left;">IBAN Bancário Individual</th>
                     <th style="padding: 12px; text-align: left;">Faturamento Bruto</th>
                     <th style="padding: 12px; text-align: left;">Retido App (10%)</th>
                     <th style="padding: 12px; text-align: left;">Líquido de Repasse</th>
                     <th style="padding: 12px; text-align: center;">Ação de Depósito</th>
                 </tr>
             </thead>
             <tbody>
                 <?php 
                 if (isset($lista_parceiros_completa) && is_array($lista_parceiros_completa)):
                     foreach ($lista_parceiros_completa as $empresa): 
                         $p_id = $empresa['id'];
                         $p_tipo = $empresa['tipo'];
 
                         $stmt = $pdo->prepare("SELECT SUM(valor) as total_bruto FROM `pagamentos` WHERE `id_parceiro` = ? AND `tipo_parceiro` = ? AND `status_trabalho` = 'Concluido'");
                         $stmt->execute([$p_id, $p_tipo]);
                         $bruto = (float)$stmt->fetch()['total_bruto'];
 
                         if (isset($id_filtro_sino) && $id_filtro_sino > 0) {
                             if ($id_filtro_sino !== $p_id || $tipo_filtro_sino !== $p_tipo) {
                                 continue; 
                             }
                         }
 
                         $taxa_aurelius = $bruto * 0.10;
                         $liquido_parceiro = $bruto - $taxa_aurelius;
 
                         $stmtSaques = $pdo->prepare("SELECT SUM(valor_sacado) as total_sacado FROM `saques_parceiros` WHERE `parceiro_id` = ? AND `tipo_parceiro` = ?");
                         $stmtSaques->execute([$p_id, $p_tipo]);
                         $ja_sacado = (float)$stmtSaques->fetch()['total_sacado'];
 
                         $saldo_final_disponivel = $liquido_parceiro - $ja_sacado;
                         if ($saldo_final_disponivel < 0) { $saldo_final_disponivel = 0; }
 
                         $classe_destaque = (isset($id_filtro_sino) && $id_filtro_sino == $p_id && $tipo_filtro_sino == $p_tipo) ? 'destaque-linha' : '';
                 ?>
                     <tr class="<?= $classe_destaque ?>" style="border-bottom: 1px solid #1f2937; font-size: 13px; color: #cbd5e1;">
                         <td style="padding: 12px;"><strong><?= htmlspecialchars($empresa['nome']) ?></strong> <span style="color:#64748b; font-size:11px;">(ID: <?= $p_id ?>)</span></td>
                         <td style="padding: 12px;"><span style="font-size: 11px; background:#334155; padding:3px 6px; border-radius:4px; text-transform:uppercase; color:aqua;"><?= htmlspecialchars($p_tipo) ?></span></td>
                         <td style="padding: 12px; font-family: monospace; color: #38bdf8; font-weight: bold;"><?= htmlspecialchars($empresa['iban_bancario']) ?></td>
                         <td style="padding: 12px; font-weight: bold; color: #fff;"><?= number_format($bruto, 2, ',', '.') ?> AOA</td>
                         <td style="padding: 12px; color:#ef4444; font-weight: bold;">-<?= number_format($taxa_aurelius, 2, ',', '.') ?> AOA</td>
                         <td style="padding: 12px; color:#22c55e; font-weight:bold; font-size:14px;"><?= number_format($saldo_final_disponivel, 2, ',', '.') ?> AOA</td>
                         <td style="padding: 12px; text-align: center;">
                             <?php if ($saldo_final_disponivel > 0): ?>
                                 <?php if (isset($bloqueio_calendario) && $bloqueio_calendario): ?>
                                     <button type="button" style="background:#475569; color:#94a3b8; cursor:not-allowed; border:none; padding: 8px 12px; border-radius: 6px; font-size: 11px; font-weight: bold; text-transform: uppercase;" disabled>🔒 Fora do Prazo</button>
                                 <?php else: ?>
                                     <a href="Admini.php?executar_deposito=<?= $p_id ?>&tipo=<?= $p_tipo ?>&montante=<?= $saldo_final_disponivel ?>" style="background:#22c55e; text-decoration:none; color:#000; padding: 8px 12px; border-radius: 6px; font-size: 11px; font-weight: bold; text-transform: uppercase; display: inline-block;">Fazer Depósito</a>
                                 <?php endif; ?>
                             <?php else: ?>
                                 <span style="color:#64748b; font-style:italic;">Sem Saldo Pendente</span>
                             <?php endif; ?>
                         </td>
                     </tr>
                 <?php 
                     endforeach;
                 endif; 
                 ?>
             </tbody>
         </table>
     </div>
 </div>
 
 <!-- =========================================================================
      🟩 CONTROLO JAVASCRIPT: ECONOMIA RIGIDA DE ESPAÇO DA TESOURARIA
    ========================================================================= -->
 <script>
 function alternarPainelTesouraria() {
     const mesa = document.getElementById('corpo_painel_tesouraria');
     const txt = document.getElementById('txt_status_tesouraria');
     const btn = document.getElementById('btn_toggle_tesouraria');
     
     if (mesa.style.display === 'none' || mesa.style.display === '') {
         mesa.style.display = 'block';
         txt.innerText = '▴ Recolher Módulo de Contas';
         btn.style.borderRadius = "12px 12px 0 0";
     } else {
         mesa.style.display = 'none';
         txt.innerText = '▾ Expandir Módulo de Contas';
         btn.style.borderRadius = "12px";
     }
 }
 
 // Tranca absoluta: força a tesouraria a nascer SEMPRE fechada para poupar espaço
 document.addEventListener("DOMContentLoaded", function() {
     const mesa = document.getElementById('corpo_painel_tesouraria');
     const txt = document.getElementById('txt_status_tesouraria');
     const btn = document.getElementById('btn_toggle_tesouraria');
     
     if (mesa) mesa.style.display = 'none';
     if (txt)  txt.innerText = '▾ Expandir Módulo de Contas';
     if (btn)  btn.style.borderRadius = "12px";
 });
 </script>





   


















<?php
// Admini.php - Área de Gestão de Clientes VIP (CORRIGIDO PARA MYSQLI)
include_once("Conexao.php");

// Alinha a ligação nativa com o seu ficheiro Conexao.php
$conexao_link = $conexao_aurelius ?? $conexao ?? $link ?? null;
if (!$conexao_link) {
    $conexao_link = @mysqli_connect("127.0.0.1", "root", "", "aurelius_salao");
}

$clientes_vip = [];

if ($conexao_link) {
    mysqli_set_charset($conexao_link, "utf8mb4");
    
    // 🟢 CORREÇÃO: Removeu-se o filtro WHERE fixo para puxar Ativos E Pendentes simultaneamente
    $query_vip = "SELECT * FROM `assinaturas` ORDER BY `id_assinatura` DESC";
    $resultado_vip = mysqli_query($conexao_link, $query_vip);
    
    if ($resultado_vip) {
        while ($linha = mysqli_fetch_assoc($resultado_vip)) {
            $clientes_vip[] = $linha;
        }
    }
}
?>

    <!-- =========================================================================
         🏆 PAINEL DE CONTROLO MESTRE AURELIUS: CONTROLO DE AUDITORIA E PLANOS PENDENTES
         ========================================================================= -->
    <div id="abaClientesPremium" style="display: none; transition: all 0.3s ease-in-out;">
        <p style="color: #94a3b8; font-size: 13px; margin-bottom: 20px;">
            Use esta lista para monitorizar os utilizadores frequentes, parabenizá-los nos aniversários ou enviar cupons de desconto anual para incentivar a continuidade.
        </p>
 
        <div style="overflow-x: auto;">
            <table style="width: 100%; border-collapse: collapse; text-align: left; font-size: 13px;">
                <thead>
                    <tr style="border-bottom: 2px solid #1e293b; color: #ca8a04;">
                        <th style="padding: 10px;">Cliente Fiel</th>
                        <th style="padding: 10px;">Plano</th>
                        <th style="padding: 10px;">Contacto Express</th>
                        <th style="padding: 10px;">Válido Até</th>
                        <th style="padding: 10px; text-align: center;">Incentivo Comercial</th>
                    </tr>
                </thead>
                <tbody>
                <?php if(empty($clientes_vip)): ?>
                    <tr>
                        <td colspan="5" style="color: #64748b; padding: 15px; text-align: center;">Nenhum cliente cadastrado no sistema ainda.</td>
                    </tr>
                <?php else: ?>
                    <?php 
                    foreach($clientes_vip as $vip): 
                        $id_assinatura = intval($vip['id_assinatura']);
                        $nome_fidelizado = !empty($vip['cliente']) ? $vip['cliente'] : 'Cliente';
                        $tel_cru = !empty($vip['telefone_express']) ? $vip['telefone_express'] : '';
                        
                        // Limpa o número para os links do sistema
                        $tel_limpo = preg_replace('/\D/', '', $tel_cru);
                        if (!empty($tel_limpo) && strpos($tel_limpo, '244') !== 0) {
                            $tel_limpo = '244' . $tel_limpo;
                        }
            
                        // 🟢 CAPTURA DO CAMPO STATUS DO phpMyAdmin
                        $estado_real = isset($vip['status']) ? trim(strtolower($vip['status'])) : 'ativo';
                    ?>
                        <tr style="border-bottom: 1px solid #1e293b; color: #fff; background: <?php echo ($estado_real === 'pendente') ? 'rgba(234, 179, 8, 0.04)' : 'transparent'; ?>;">
                            
                            <!-- 1. Cliente Fiel -->
                            <td style="padding: 12px; font-weight: bold; color: #ffffff;">
                                <?php echo htmlspecialchars($nome_fidelizado); ?>
                            </td>
                            
                            <!-- 2. Plano -->
                            <td style="padding: 12px; text-transform: uppercase;">
                                <span style="background: <?php echo ($estado_real === 'pendente') ? '#ef4444' : '#1e293b'; ?>; color: #fff; padding: 3px 8px; border-radius: 4px; font-size: 10px; font-weight: bold; letter-spacing: 0.5px;">
                                    <?php echo htmlspecialchars($vip['plano'] ?: 'ANUAL'); ?>
                                    <?php echo ($estado_real === 'pendente') ? ' (PENDENTE)' : ''; ?>
                                </span>
                            </td>
                            
                            <!-- 3. Contacto Express -->
                            <td style="padding: 12px; color: #3b82f6; font-weight: bold;">
                                <?php echo htmlspecialchars($tel_cru); ?>
                            </td>
                            
                            <!-- 4. Válido Até -->
                            <td style="padding: 12px; color: <?php echo ($estado_real === 'pendente') ? '#94a3b8' : '#22c55e'; ?>;">
                                <?php 
                                $data_limite = !empty($vip['data_fim']) ? $vip['data_fim'] : date('Y-m-d H:i:s');
                                echo date('d/m/Y', strtotime($data_limite)); 
                                ?>
                            </td>
                            
                            <!-- 5. Incentivo Comercial / Auditoria Manual -->
                            <td style="padding: 12px; text-align: center;">
                                <?php if ($estado_real === 'pendente'): ?>
                                    
                                    <!-- 🚨 BOTÕES DE CONTROLO ATIVADOS PARA OS REGISTOS PENDENTES -->
                                    <div style="display: inline-flex; gap: 6px; align-items: center;">
    <?php
    // Remove qualquer formatação do telefone vindo do banco de dados (Ex: 912457896)
    $tel_puro = preg_replace('/\D/', '', $vip['telefone_express']);
    
    // Verifica qual é a extensão real do arquivo guardado na pasta local
    $extensoes_possiveis = ['jpg', 'jpeg', 'png', 'pdf'];
    $link_recibo_validado = "sem_comprovativo.jpg"; // Fallback caso não encontre
    
    foreach ($extensoes_possiveis as $ext) {
        $caminho_teste = "uploads/comprovativos/VIP_" . $tel_puro . "." . $ext;
        if (file_exists($caminho_teste)) {
            $link_recibo_validado = $caminho_teste;
            break;
        }
    }
    ?>
    
    <!-- Link corrigido que aponta para o arquivo real e verificado do Apache -->
    <a href="<?php echo $link_recibo_validado; ?>" 
       target="_blank" 
       style="background: #3b82f6; color: white; padding: 6px 12px; text-decoration: none; border-radius: 4px; font-weight: bold; font-size: 11px; display: inline-block;">
        👁️ Recibo
    </a>
    
    <a href="ativar_assinatura.php?id=<?= $id_assinatura ?>" 
       style="background: #eab308; color: #000; padding: 6px 12px; text-decoration: none; border-radius: 4px; font-weight: bold; font-size: 11px; text-transform: uppercase;">
        ✅ Validar
    </a>
</div>
            
                                <?php else: ?>
                                    
                                    <!-- Caso já esteja verificado, usa a rota limpa wa.me do WhatsApp -->
                                    <a href="https://wa.me<?= $tel_limpo ?>?text=Olá%20<?= urlencode($nome_fidelizado) ?>!%20A%20sua%20assinatura%20Aurelius%20Premium%20está%20ativa." 
                                       target="_blank" 
                                       style="background: #22c55e; color: white; padding: 6px 12px; text-decoration: none; border-radius: 4px; font-weight: bold; font-size: 11px; display: inline-block;">
                                        💬 Incentivo
                                    </a>
            
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- =========================================================================
     🟩 INTERAÇÃO JAVASCRIPT: CONTROLO DE VISIBILIDADE DO PAINEL (SLIDE TOGGLE)
     ========================================================================= -->
<script>
function toggleClientesPremium() {
    const aba = document.getElementById('abaClientesPremium');
    const btn = document.getElementById('btnToggleVip');
    
    if (!aba || !btn) return;
    
    if (aba.style.display === 'none' || aba.style.display === '') {
        aba.style.display = 'block';
        btn.innerText = 'Ocultar Clientes';
        btn.style.background = 'linear-gradient(135deg, #ef4444, #991b1b)';
        btn.style.color = '#fff';
    } else {
        aba.style.display = 'none';
        btn.innerText = 'Ver Clientes Premiums';
        btn.style.background = 'linear-gradient(135deg, #ca8a04, #854d0e)';
        btn.style.color = '#0f172a';
    }
}
</script>





























<!-- JAVASCRIPT DO BOTÃO DA PASTA ADMIN -->
<script>
function alternarPastaAdmin() {
    var conteudo = document.getElementById("conteudoPastaAdmin");
    var seta = document.getElementById("setaPastaAdmin");
    if (conteudo.style.display === "none") {
        conteudo.style.display = "block";
        seta.innerText = "▲";
    } else {
        conteudo.style.display = "none";
        seta.innerText = "▼";
    }
}

// Função mestre do seu arquivo corrigida para abrir a pasta de comissões sem travar
function alternarPainel(idComponente) {
    const painel = document.getElementById(idComponente);
    // Tenta encontrar o botão dinâmico para mudar a seta
    const botaoTexto = idComponente === 'pastaComissoesSaaS' ? document.getElementById('icone-pastaComissoesSaaS') : document.getElementById('icone-' + idComponente);
    
    if (painel.style.display === 'none' || painel.style.display === '') {
        painel.style.display = 'block';
        if (botaoTexto) {
            botaoTexto.innerHTML = "▼ RECOLHER GRÁFICO";
            botaoTexto.style.color = "#ef4444"; // Fica vermelho ao abrir para sinalizar ação de fechar
        }
    } else {
        painel.style.display = 'none';
        if (botaoTexto) {
            botaoTexto.innerHTML = "▲ EXPANDIR GRÁFICO";
            botaoTexto.style.color = "#38bdf8"; // Volta ao ciano original da imagem
        }
    }
}
</script>


<html lang="pt">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Painel Admin - Profissionais</title>
    <style>
        body { font-family: 'Segoe UI', Arial, sans-serif; background-color: #0f172a; padding: 20px; margin: 0; color: #f8fafc; }
        .grid-cards { display: grid; grid-template-columns: repeat(auto-fill, minmax(220px, 1fr)); gap: 15px; margin-top: 20px; }
        .card-funcionario { background: #1e293b; border: 1px solid #334155; padding: 15px; border-radius: 8px; box-shadow: 0 4px 6px rgba(0,0,0,0.1); display: flex; flex-direction: column; justify-content: space-between; min-height: 120px; }
        .card-funcionario h3 { margin: 0 0 5px 0; color: #fff; font-size: 16px; }
        .status-atual { font-size: 13px; font-weight: bold; margin-bottom: 12px; display: block; }
        .select-admin { background: #0f172a; color: #fff; border: 1px solid #475569; padding: 6px; border-radius: 6px; width: 100%; cursor: pointer; outline: none; }
        .topo-indicadores { display: grid; grid-template-columns: repeat(3, 1fr); gap: 15px; margin-bottom: 25px; }
        .box-ind { background: #1e40af; padding: 15px; border-radius: 8px; text-align: left; }
        .box-ind h4 { margin: 0; font-size: 12px; color: #93c5fd; text-transform: uppercase; }
        .box-ind span { font-size: 24px; font-weight: bold; display: block; margin-top: 5px; }
        .voltar-btn { display: inline-block; background-color: #475569; color: white; padding: 10px 20px; text-decoration: none; border-radius: 6px; font-weight: bold; margin-bottom: 20px; margin-right: 5px; }
    </style>
</head>
<body>
<br> 
<nav class="no-print" style="display: flex; justify-content: space-between; align-items: center; background: #111827; padding: 14px 20px; border-radius: 12px; border: 1px solid #1f2937; margin-bottom: 25px; box-sizing: border-box;">
    
<!-- 🧭 BLOCO DE LINKS DA PLATAFORMA SAAS -->
<div style="display: flex; gap: 10px; align-items: center;">
    <!-- Botão Histórico (Estilo Padrão) -->
    <a href="historico.php" class="voltar-btn" style="background: #1e293b; color: #cbd5e1; border: 1px solid #334155; padding: 10px 18px; text-decoration: none; border-radius: 8px; font-weight: bold; font-size: 11px; text-transform: uppercase; letter-spacing: 0.5px; transition: all 0.2s;" onmouseover="this.style.background='#334155'; this.style.borderColor='#475569';" onmouseout="this.style.background='#1e293b'; this.style.borderColor='#334155';">
        📈 Histórico Geral
    </a>
    
    <!-- Botão Admin Parceiros (Destaque Dourado Corporativo) -->
    <a href="Admin_Parceiros.php" class="voltar-btn" style="background: #ca8a04; color: #0b0f19; padding: 10px 18px; text-decoration: none; border-radius: 8px; font-weight: bold; font-size: 11px; text-transform: uppercase; letter-spacing: 0.5px; border: 1px solid #a16207; transition: all 0.2s;" onmouseover="this.style.background='#eab308';" onmouseout="this.style.background='#ca8a04';">
        🏬 Gestão de Parceiros
    </a>
    
    <!-- Botão Admin Profissionais (Estilo Padrão) -->
    <a href="Admin.php" class="voltar-btn" style="background: #1e293b; color: #cbd5e1; border: 1px solid #334155; padding: 10px 18px; text-decoration: none; border-radius: 8px; font-weight: bold; font-size: 11px; text-transform: uppercase; letter-spacing: 0.5px; transition: all 0.2s;" onmouseover="this.style.background='#334155'; this.style.borderColor='#475569';" onmouseout="this.style.background='#1e293b'; this.style.borderColor='#334155';">
        💈 Profissionais & Staff
    </a>
</div>

<!-- 🚪 BOTÃO TERMINAR SESSÃO GERÊNCIA -->
<div>
    <a href="admini.php?acao_seguranca=logout" style="background: rgba(220, 38, 38, 0.1); border: 1px solid #dc2626; color: #f87171; padding: 10px 18px; text-decoration: none; border-radius: 8px; font-weight: bold; font-size: 11px; text-transform: uppercase; letter-spacing: 0.5px; display: inline-flex; align-items: center; gap: 6px; transition: all 0.2s; box-shadow: 0 4px 6px rgba(0,0,0,0.15);" onmouseover="this.style.background='#dc2626'; this.style.color='#fff';" onmouseout="this.style.background='rgba(220, 38, 38, 0.1)'; this.style.color='#f87171';">
        ❌ Terminar Sessão Gerência
    </a>
</div>

</nav>




 <!-- Indicadores Superiores Dinâmicos -->
 <div class="topo-indicadores">
 <div class="box-ind" style="background: #065f46;">
     <h4>CAIXA DO DIA</h4>
     <span><?php echo number_format($caixaDia ?? 0, 2, ',', '.'); ?> Kz</span>
 </div>
 <div class="box-ind">
     <h4>ATENDIMENTOS HOJE</h4>
     <span><?php echo $sessoesHoje ?? 0; ?> Sessões</span>
 </div>
 <div class="box-ind">
     <h4>EQUIPA CADASTRADA</h4>
     <span><?php echo $totalProfissionais; ?> Profissionais</span>
 </div>
</div>

<h2 style="font-size: 17px; color: #38bdf8; margin-top: 30px;">⚙ Auditoria Operacional</h2>

<div class="grid-cards" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 15px;">
 <?php if(empty($funcionarios)): ?>
     <p style="grid-column: 1/-1; text-align:center; color:#94a3b8;">Nenhum profissional encontrado na tabela de funcionários.</p>
 <?php else: ?>
     <?php foreach($funcionarios as $func): 
         $corStatus = '#22c55e'; 
         $status_atual = trim($func['status'] ?? 'Disponível');
         if ($status_atual == 'Ausente' || $status_atual == 'Folga') {
             $corStatus = '#ef4444'; 
         } elseif ($status_atual == 'Atendimento') {
             $corStatus = '#ffaa00'; 
         }
         
         $profissao_f = !empty($func['especialidade']) ? $func['especialidade'] : 'Barbeiro';
         
         // 🟢 Definição da Imagem de Perfil do Funcionário vinda do Banco de Dados
         $foto_perfil = !empty($func['foto_url']) ? 'uploads/' . $func['foto_url'] : 'https://flaticon.com';
     ?>
         <!-- Card Individual Dinâmico do Funcionário -->
         <div class="card-funcionario" style="background: #111827; border: 1px solid #1f2937; padding: 15px; border-radius: 10px; display: flex; flex-direction: column; gap: 12px; justify-content: space-between;">
             
             <div style="display: flex; gap: 12px; align-items: center; width: 100%;">
                 <!-- Miniatura de Exibição da Imagem Selecionada -->
                 <img src="<?= $foto_perfil ?>" style="width: 50px; height: 50px; object-fit: cover; border-radius: 50%; border: 2px solid #38bdf8;">
                 
                 <div style="text-align: left; flex: 1;">
                     <h3 style="margin: 0; font-size: 14px; color: #fff;"><?php echo htmlspecialchars($func['nome']); ?></h3>
                     <span style="color: #64748b; font-size: 11px; display: block; margin: 2px 0 4px 0;"><?php echo htmlspecialchars($profissao_f); ?></span>
                     <span class="status-atual" style="color: <?php echo $corStatus; ?>; font-size: 12px; font-weight: bold;">
                         • <?php echo htmlspecialchars($status_atual); ?>
                     </span>
                 </div>
                 
                 <!-- Evento onchange mapeando dinamicamente a coluna id_funcionario real -->
                 <select class="select-admin" style="background: #070b12; color: #fff; padding: 6px; border: 1px solid #374151; border-radius: 6px; font-size: 12px; cursor: pointer; height: fit-content;" onchange="window.location.href='Admini.php?acao=atualizar_status&id=<?php echo $func['id_funcionario']; ?>&status='+encodeURIComponent(this.value)">
                     <option value="Disponível" <?php if($status_atual == 'Disponível') echo 'selected'; ?>>Disponível</option>
                     <option value="Atendimento" <?php if($status_atual == 'Atendimento') echo 'selected'; ?>>Atendimento</option>
                     <option value="Ausente" <?php if($status_atual == 'Ausente') echo 'selected'; ?>>Ausente</option>
                     <option value="Folga" <?php if($status_atual == 'Folga') echo 'selected'; ?>>Folga</option>
                 </select>
             </div>

             <!-- 🟢 FORMULÁRIO ISOLADO: Buscar imagem no ficheiro e atualizar via POST -->
             <form action="guardar_foto_barbeiro.php" method="POST" enctype="multipart/form-data" style="width: 100%; border-top: 1px dashed #1f2937; padding-top: 10px; display: flex; justify-content: space-between; align-items: center; gap: 8px;">
                 <input type="hidden" name="id_funcionario_upload" value="<?php echo $func['id_funcionario']; ?>">
                 <input type="file" name="ficheiro_perfil" accept="image/*" required style="font-size: 10px; color: #64748b; max-width: 150px;">
                 <button type="submit" style="background: #38bdf8; color: #000; border: none; padding: 5px 10px; font-size: 10px; font-weight: bold; border-radius: 4px; cursor: pointer; text-transform: uppercase;">Guardar</button>
             </form>

         </div>
     <?php endforeach; ?>
 <?php endif; ?>
</div>


























<!-- =================================================================
     ⚡ JAVASCRIPT DE SUPORTE GERENCIAL (CONTROLO DE TEXTOS E CORES)
     ================================================================= -->
<script>
function togglePainelGerencial(idAba, botao) {
    const painel = document.getElementById(idAba);
    if (!painel || !botao) return;

    if (painel.style.display === "none" || painel.style.display === "") {
        painel.style.display = "block";
        botao.innerText = "✕ Fechar Tabela";
        botao.style.backgroundColor = "#dc2626"; 
    } else {
        painel.style.display = "none";
        botao.innerText = "Abrir Tabela";
        
        // Restaura as cores originais de cada módulo comercial
        if (idAba === 'abaFaturamentoDiario') botao.style.backgroundColor = "#0088cc";
        if (idAba === 'abaReferenciaCargos') botao.style.backgroundColor = "#ca8a04";
        if (idAba === 'abaFolhaPagamento') botao.style.backgroundColor = "#a855f7";
        if (idAba === 'abaEdicaoSalarios') botao.style.backgroundColor = "#e11d48";
    }
}
</script>



<!-- =================================================================
     ⚡ COMPLEMENTO JAVASCRIPT DAS FUNÇÕES DE EDIÇÃO E SELEÇÃO
     ================================================================= -->
<script>
// Carrega os dados da linha clicada de forma automática para o painel de input superior
function carregarCargoParaEdicao(idCargo, salarioBase) {
    const select = document.getElementById('selectFormCargo');
    const input = document.getElementById('inputFormSalario');
    
    if (select && input) {
        select.value = idCargo;
        input.value = salarioBase;
        // Foca e rola suavemente até ao campo para indicar ao gerente o início da ação
        input.focus();
        document.getElementById('abaEdicaoSalarios').scrollIntoView({ behavior: 'smooth' });
    }
}

// Controla o botão "Marcar Todos" idêntico ao fluxo nativo do phpMyAdmin
function toggleMarcarTodos(masterCheckbox) {
    const checkboxes = document.querySelectorAll('.chk-cargo');
    checkboxes.forEach(cb => {
        cb.checked = masterCheckbox.checked;
    });
}
</script>




<?php
// =========================================================================
// 📥 2. PROCESSAMENTO CORRIGIDO: FICHA CADASTRAL & UPLOAD DE FICHEIROS
// =========================================================================
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['acao_salvar_dados_pessoais'])) {
    $id_funcionario = intval($_POST['id_funcionario'] ?? 0);
    $numero_bi = trim($_POST['numero_bi'] ?? '');
    $data_nascimento = $_POST['data_nascimento'] ?? '';
    $telefone_pessoal = trim($_POST['telefone_pessoal'] ?? '');
    $morada_bairro = trim($_POST['morada_bairro'] ?? '');
    
    // CONCATENAÇÃO DE SEGURANÇA: Junta o nível académico e a formação profissional no mesmo campo
    $nivel_academico = trim($_POST['nivel_academico'] ?? '');
    if (!empty($_POST['formacao_prof'])) {
        $nivel_academico .= " | Formação: " . trim($_POST['formacao_prof']);
    }
    
    $data_admissao = $_POST['data_admissao'] ?? '';
    $salario_base = floatval($_POST['salario_base'] ?? 0);
    $bonus_horas_extras = floatval($_POST['bonus_horas_extras'] ?? 0);

    // Diretório de uploads unificado
    $diretorio_upload = "uploads_rh/";
    if (!is_dir($diretorio_upload)) {
        mkdir($diretorio_upload, 0777, true);
    }

    // 🛡️ CORREÇÃO DA FUNÇÃO: Alterado de move_uploaded_files para move_uploaded_file (singular)
    function processarUploadFicheiro($campo, $diretorio) {
        if (isset($_FILES[$campo]) && $_FILES[$campo]['error'] === UPLOAD_ERR_OK) {
            $extensao = pathinfo($_FILES[$campo]['name'], PATHINFO_EXTENSION);
            $novo_nome = uniqid("doc_", true) . "." . $extensao;
            $caminho_final = $diretorio . $novo_nome;
            if (move_uploaded_file($_FILES[$campo]['tmp_name'], $caminho_final)) {
                return $caminho_final;
            }
        }
        return null;
    }

    $path_bi = processarUploadFicheiro('file_bi', $diretorio_upload);
    $path_certificado = processarUploadFicheiro('file_certificado', $diretorio_upload);
    $path_diploma = processarUploadFicheiro('file_diploma', $diretorio_upload);

    if ($id_funcionario > 0 && isset($pdo)) {
        try {
            // 🛡️ CORREÇÃO REAL DO INSERT: Removidos os campos 'formacao_prof' e 'file_diploma' que causam erro 1054
            $sql_dados = "INSERT INTO funcionarios_dados_pessoais 
                (id_funcionario, numero_bi, file_bi, data_nascimento, telefone_pessoal, morada_bairro, nivel_academico, file_certificado, data_admissao, salario_base, bonus_horas_extras) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
                ON DUPLICATE KEY UPDATE 
                numero_bi = VALUES(numero_bi), 
                file_bi = IFNULL(VALUES(file_bi), file_bi), 
                data_nascimento = VALUES(data_nascimento), 
                telefone_pessoal = VALUES(telefone_pessoal), 
                morada_bairro = VALUES(morada_bairro), 
                nivel_academico = VALUES(nivel_academico), 
                file_certificado = IFNULL(VALUES(file_certificado), file_certificado), 
                data_admissao = VALUES(data_admissao), 
                salario_base = VALUES(salario_base), 
                bonus_horas_extras = VALUES(bonus_horas_extras)";
            
            $stmt_dados = $pdo->prepare($sql_dados);
            $stmt_dados->execute([
                $id_funcionario, $numero_bi, $path_bi, $data_nascimento, $telefone_pessoal, 
                $morada_bairro, $nivel_academico, $path_certificado, $data_admissao, 
                $salario_base, $bonus_horas_extras
            ]);

            // Redireciona e reabre na sub-aba roxa mantendo o foco visual estável
            echo "<script>alert('🎉 Ficha Cadastral e arquivos armazenados com sucesso!'); window.location.href='Admini.php#sub-dados-pessoais';</script>";
            exit;
        } catch (PDOException $e) {
            echo "<script>alert('❌ Erro de persistência de dados: " . addslashes($e->getMessage()) . "');</script>";
        }
    }
}



?>

<!-- 🎛️ BARRA DE NAVEGAÇÃO INTERNA ENTRE AS NOVAS ABAS GERENCIAIS -->
<div style="margin-top: 25px; display: flex; gap: 10px;" class="no-print">
   
    <button onclick="alternarSubAbas('sub-cadastro')" id="btn-sub-cadastro" style="background: #1e293b; color: white; border: none; padding: 10px 20px; font-weight: bold; font-size: 13px; border-radius: 8px; cursor: pointer;">➕ Novo Profissional </button>
   



<!-- ==========================================
     Aba 2: REGISTRO DO 17º PROFISSIONAL
     ========================================== -->
<div id="secao-sub-cadastro" class="sub-aba-conteudo" style="display: none; background: #0f172a; border: 1px solid #1e293b; padding: 20px; margin-top: 15px; border-radius: 12px;">
    <h4 style="color: #ca8a04; margin-top: 0; font-size: 16px;">➕ Cadastrar Novo Funcionário</h4>
    <form method="POST" action="Admini.php" style="margin-top: 15px; display: flex; gap: 15px; align-items: center;">
        <input type="text" name="nome_func" placeholder="Nome Completo do funcionário" style="flex: 1; background: #1e293b; border: 1px solid #334155; padding: 12px; color: white; border-radius: 6px;" required>
        <button type="submit" name="acao_cadastrar_func" style="background: #22c55e; color: white; border: none; padding: 12px 24px; font-weight: bold; border-radius: 6px; cursor: pointer; text-transform: uppercase;">Inserir na Equipa</button>
    </form>
</div>


<!-- ==========================================
     Aba 3: DADOS PESSOAIS E DOCUMENTOS EM ANEXO (Formulário Multipart com Files reais)
     ========================================== -->
     <div id="secao-sub-dados-pessoais" class="sub-aba-conteudo" style="display: none; background: #0f172a; border: 1px solid #1e293b; padding: 20px; margin-top: 15px; border-radius: 12px;">
     <h4 style="color: #a855f7; margin-top: 0; font-size: 16px;">📂 Ficha Cadastral e Arquivo Digital de Ficheiros de RH</h4>
     
     <!-- 🚨 IMPORTANTE: enctype="multipart/form-data" adicionado para upload de ficheiros -->
     <form method="POST" action="Admini.php" enctype="multipart/form-data" style="margin-top: 20px;">
         <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 15px; margin-bottom: 20px;">
             
             <div>
                 <label style="color: #94a3b8; font-size: 12px; display: block; margin-bottom: 5px;">Selecionar Funcionário:</label>
                 <select name="id_funcionario" style="width:100%; background: #1e293b; border: 1px solid #334155; padding: 10px; color: white; border-radius: 6px;" required>
                     <option value="">Selecione o profissional...</option>
                     <option value="">Mestre Aurélio( Cabelereiro)</option>
                     <?php foreach($todos_funcionarios as $f): ?>
                         <option value="<?php echo $f['id_funcionario']; ?>"><?php echo htmlspecialchars($f['nome']); ?></option>
                     <?php endforeach; ?>
                 </select>
             </div>
 
             <div>
                 <label style="color: #94a3b8; font-size: 12px; display: block; margin-bottom: 5px;">Nº Bilhete Identidade (BI):</label>
                 <input type="text" name="numero_bi" placeholder="Ex: 005478964HU042" style="width:100%; background: #1e293b; border: 1px solid #334155; padding: 10px; color: white; border-radius: 6px;" required>
             </div>
 
             <!--  NOVO: Upload do ficheiro digital do BI -->
             <div>
                 <label style="color: #38bdf8; font-size: 12px; display: block; margin-bottom: 5px;">📁 Ficheiro digital do BI (PDF/Imagem):</label>
                 <input type="file" name="file_bi" style="width:100%; background: #1e293b; border: 1px solid #334155; padding: 8px; color: white; border-radius: 6px; font-size: 12px;">
             </div>
 
             <div>
                 <label style="color: #94a3b8; font-size: 12px; display: block; margin-bottom: 5px;">Data de Nascimento:</label>
                 <input type="date" name="data_nascimento" style="width:100%; background: #1e293b; border: 1px solid #334155; padding: 10px; color: white; border-radius: 6px;" required>
             </div>
 
             <div>
                 <label style="color: #94a3b8; font-size: 12px; display: block; margin-bottom: 5px;">Contacto Telefónico:</label>
                 <input type="text" name="telefone_pessoal" placeholder="" style="width:100%; background: #1e293b; border: 1px solid #334155; padding: 10px; color: white; border-radius: 6px;" required>
             </div>
 
             <div>
                 <label style="color: #94a3b8; font-size: 12px; display: block; margin-bottom: 5px;">Bairro de Residência (Huambo):</label>
                 <input type="text" name="morada_bairro" placeholder="" style="width:100%; background: #1e293b; border: 1px solid #334155; padding: 10px; color: white; border-radius: 6px;" required>
             </div>
 
             <div>
                 <label style="color: #94a3b8; font-size: 12px; display: block; margin-bottom: 5px;">Nível Académico:</label>
                 <input type="text" name="nivel_academico" placeholder="" style="width:100%; background: #1e293b; border: 1px solid #334155; padding: 10px; color: white; border-radius: 6px;" required>
             </div>
 
             <!-- 📥 NOVO: Upload do Ficheiro do Certificado Académico -->
             <div>
                 <label style="color: #38bdf8; font-size: 12px; display: block; margin-bottom: 5px;">📁 Ficheiro do Certificado Académico:</label>
                 <input type="file" name="file_certificado" style="width:100%; background: #1e293b; border: 1px solid #334155; padding: 8px; color: white; border-radius: 6px; font-size: 12px;">
             </div>
 
             <div>
                 <label style="color: #94a3b8; font-size: 12px; display: block; margin-bottom: 5px;">Formação Profissional Base:</label>
                 <input type="text" name="formacao_prof" placeholder="" style="width:100%; background: #1e293b; border: 1px solid #334155; padding: 10px; color: white; border-radius: 6px;" required>
             </div>
 
             <!-- 📥 NOVO: Upload do Ficheiro do Diploma do Curso -->
             <div>
                 <label style="color: #38bdf8; font-size: 12px; display: block; margin-bottom: 5px;">📁 Ficheiro do Diploma Profissional:</label>
                 <input type="file" name="file_diploma" style="width:100%; background: #1e293b; border: 1px solid #334155; padding: 8px; color: white; border-radius: 6px; font-size: 12px;">
             </div>
 
             <div>
                 <label style="color: #94a3b8; font-size: 12px; display: block; margin-bottom: 5px;">Data de Admissão:</label>
                 <input type="date" name="data_admissao" style="width:100%; background: #1e293b; border: 1px solid #334155; padding: 10px; color: white; border-radius: 6px;" required>
             </div>
 
             <div>
                 <label style="color: #94a3b8; font-size: 12px; display: block; margin-bottom: 5px;">Salário Mensal (Kz):</label>
                 <input type="number" step="0.01" name="salario_base" placeholder="" style="width:100%; background: #1e293b; border: 1px solid #334155; padding: 10px; color: white; border-radius: 6px;" required>
             </div>
 
             <div>
                 <label style="color: #94a3b8; font-size: 12px; display: block; margin-bottom: 5px;">Horas Extras (Kz):</label>
                 <input type="number" step="0.01" name="bonus_horas_extras" placeholder="" style="width:100%; background: #1e293b; border: 1px solid #334155; padding: 10px; color: white; border-radius: 6px;" required>
             </div>
 
         </div>
 
         <!-- Inputs textareas grandes para Cursos e Experiências -->
         <div style="margin-bottom: 20px;">
             <label style="color: #94a3b8; font-size: 12px; display: block; margin-bottom: 5px;">Outros Cursos e Especializações:</label>
             <textarea name="outros_cursos" rows="3" placeholder="" style="width:100%; background: #1e293b; border: 1px solid #334155; padding: 10px; color: white; border-radius: 6px; font-family: sans-serif; resize: vertical;"></textarea>
         </div>
 
         <div style="margin-bottom: 20px;">
             <label style="color: #94a3b8; font-size: 12px; display: block; margin-bottom: 5px;">Experiências Profissionais Anteriores:</label>
             <textarea name="experiencias_ant" rows="3" placeholder=" " style="width:100%; background: #1e293b; border: 1px solid #334155; padding: 10px; color: white; border-radius: 6px; font-family: sans-serif; resize: vertical;"></textarea>
         </div>
 
         <!-- Área de Ações do Cartão de RH -->
         
             <button type="submit" name="acao_salvar_dados_pessoais" style="background: #a855f7; color: white; border: none; padding: 12px 30px; font-weight: bold; border-radius: 6px; cursor: pointer; text-transform: uppercase; font-size: 13px; letter-spacing: 0.5px; box-shadow: 0 4px 6px rgba(168, 85, 247, 0.2);">
                 🔒 Guardar Perfil e Ficheiros 
             </button>
         </div>
     </form>
 </div>
     </form>

</div> <!-- Fecha o container secao-sub-dados-pessoais -->

<?php
// Admini.php - Extensão de Gestão de Recursos Humanos e Financeiro
include_once("Conexao.php");

// 1. PROCESSAMENTO DE CADASTRO DO 17º PROFISSIONAL
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['acao_cadastrar_func'])) {
    $nome_novo = htmlspecialchars(trim($_POST['nome_func']), ENT_QUOTES, 'UTF-8');
    
    if (!empty($nome_novo)) {
        try {
            $stmt_add = $pdo->prepare("INSERT INTO funcionarios (nome, status) VALUES (?, 'Disponível')");
            $stmt_add->execute([$nome_novo]);
            echo "<script>alert('Profissional cadastrado com sucesso! Equipa atualizada.'); window.location.href='Admini.php';</script>";
        } catch (PDOException $e) {
            echo "<script>alert('Erro ao cadastrar profissional.');</script>";
        }
    }
}
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['acao_salvar_dados_pessoais'])) {
    $id_func      = intval($_POST['id_funcionario']);
    $bi           = htmlspecialchars(trim($_POST['numero_bi']), ENT_QUOTES, 'UTF-8');
    $nascimento   = $_POST['data_nascimento'];
    $telefone     = htmlspecialchars(trim($_POST['telefone_pessoal']), ENT_QUOTES, 'UTF-8');
    $morada       = htmlspecialchars(trim($_POST['morada_bairro']), ENT_QUOTES, 'UTF-8');
    $academico    = htmlspecialchars(trim($_POST['nivel_academico']), ENT_QUOTES, 'UTF-8');
    $formacao     = htmlspecialchars(trim($_POST['formacao_prof']), ENT_QUOTES, 'UTF-8');
    $outros       = htmlspecialchars(trim($_POST['outros_cursos']), ENT_QUOTES, 'UTF-8');
    $experiencia  = htmlspecialchars(trim($_POST['experiencias_ant']), ENT_QUOTES, 'UTF-8');
    $admissao     = $_POST['data_admissao'];
    $salario      = floatval($_POST['salario_base']);
    $bonus        = floatval($_POST['bonus_horas_extras']);

    // Pasta onde os documentos reais serão guardados
    $diretorio_destino = "uploads/";

    // 📂 Função interna para processar cada upload de ficheiro de forma segura
    function processarUploadFicheiro($campo_file, $prefixo, $diretorio) {
        if (isset($_FILES[$campo_file]) && $_FILES[$campo_file]['error'] === UPLOAD_ERR_OK) {
            $nome_original = $_FILES[$campo_file]['name'];
            $extensao = strtolower(pathinfo($nome_original, PATHINFO_EXTENSION));
            
            // Extensões permitidas para documentos profissionais
            $extensoes_validas = ['pdf', 'png', 'jpg', 'jpeg', 'docx'];
            if (in_array($extensao, $extensoes_validas)) {
                // Cria um nome único baseado no tempo para não sobrescrever ficheiros
                $novo_nome = $prefixo . "_" . time() . "." . $extensao;
                $caminho_final = $diretorio . $novo_nome;
                
                if (move_uploaded_files($_FILES[$campo_file]['tmp_name'], $caminho_final)) {
                    return $caminho_final;
                }
            }
        }
        return null;
    }

    // Processa os 3 ficheiros enviados pelo formulário
    $arq_bi          = processarUploadFicheiro('file_bi', 'BI_func', $diretorio_destino);
    $arq_certificado = processarUploadFicheiro('file_certificado', 'Certificado_func', $diretorio_destino);
    $arq_diploma     = processarUploadFicheiro('file_diploma', 'Diploma_func', $diretorio_destino);

    try {
        $check = $pdo->prepare("SELECT id_dado, arquivo_bi, arquivo_certificado, arquivo_diploma FROM funcionarios_dados_pessoais WHERE id_funcionario = ?");
        $check->execute([$id_func]);
        $registro_existente = $check->fetch(PDO::FETCH_ASSOC);
        
        if ($registro_existente) {
            // Mantém os ficheiros antigos se não enviar novos no formulário
            $arq_bi          = $arq_bi ?? $registro_existente['arquivo_bi'];
            $arq_certificado = $arq_certificado ?? $registro_existente['arquivo_certificado'];
            $arq_diploma     = $arq_diploma ?? $registro_existente['arquivo_diploma'];

            $sql_dado = "UPDATE funcionarios_dados_pessoais SET numero_bi = ?, arquivo_bi = ?, data_nascimento = ?, telefone_pessoal = ?, morada_bairro = ?, nivel_academico = ?, arquivo_certificado = ?, formacao_professional = ?, arquivo_diploma = ?, outros_cursos = ?, experiencias_anteriores = ?, data_admissao = ?, salario_base = ?, bonus_horas_extras = ? WHERE id_funcionario = ?";
            $stmt_dado = $pdo->prepare($sql_dado);
            $stmt_dado->execute([$bi, $arq_bi, $nascimento, $telefone, $morada, $academico, $arq_certificado, $formacao, $arq_diploma, $outros, $experiencia, $admissao, $salario, $bonus, $id_func]);
        } else {
            $sql_dado = "INSERT INTO funcionarios_dados_pessoais (id_funcionario, numero_bi, arquivo_bi, data_nascimento, telefone_pessoal, morada_bairro, nivel_academico, arquivo_certificado, formacao_professional, arquivo_diploma, outros_cursos, experiencias_anteriores, data_admissao, salario_base, bonus_horas_extras) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
            $stmt_dado = $pdo->prepare($sql_dado);
            $stmt_dado->execute([$id_func, $bi, $arq_bi, $nascimento, $telefone, $morada, $academico, $arq_certificado, $formacao, $arq_diploma, $outros, $experiencia, $admissao, $salario, $bonus]);
        }
        echo "<script>alert('Ficha cadastral e ficheiros guardados com sucesso!'); window.location.href='Admini.php';</script>";
    } catch (PDOException $e) {
        echo "<script>alert('Erro ao guardar os dados no banco: " . $e->getMessage() . "');</script>";
    }
}

// 3. CONSULTAS PARA EXIBIÇÃO NAS ABAS
try {
    // Lista de profissionais para o Lucro e Dados Pessoais
    $funcionarios_query = $pdo->query("SELECT * FROM funcionarios ORDER BY nome ASC");
    $todos_funcionarios = $funcionarios_query->fetchAll(PDO::FETCH_ASSOC);

    // Consulta de Lucros: Agrupa o total faturado por profissional na tabela pagamentos
    $lucros_query = $pdo->query("SELECT profissional, SUM(valor) as total_gerado, COUNT(id_pagamento) as total_atendimentos FROM pagamentos GROUP BY profissional ORDER BY total_gerado DESC");
    $ranking_lucros = $lucros_query->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $todos_funcionarios = [];
    $ranking_lucros = [];
}
?>

<!-- 🎛️ BARRA DE NAVEGAÇÃO INTERNA ENTRE AS NOVAS ABAS GERENCIAIS -->
<div style="margin-top: 25px; display: flex; gap: 10px;" class="no-print">
    <button onclick="alternarSubAbas('sub-lucros')" id="btn-sub-lucros" style="background: #0088cc; color: white; border: none; padding: 10px 20px; font-weight: bold; font-size: 13px; border-radius: 8px; cursor: pointer;">📊 Lucros Gerados</button>
    <button onclick="alternarSubAbas('sub-cadastro')" id="btn-sub-cadastro" style="background: #1e293b; color: white; border: none; padding: 10px 20px; font-weight: bold; font-size: 13px; border-radius: 8px; cursor: pointer;">➕ Novo Profissional (17º)</button>
    <button onclick="alternarSubAbas('sub-dados-pessoais')" id="btn-sub-dados-pessoais" style="background: #1e293b; color: white; border: none; padding: 10px 20px; font-weight: bold; font-size: 13px; border-radius: 8px; cursor: pointer;">📂 Dados Pessoais dos Funcionários</button>
</div>



<!-- ==========================================
     Aba 2: REGISTRO DO 17º PROFISSIONAL
     ========================================== -->
<div id="secao-sub-cadastro" class="sub-aba-conteudo" style="display: none; background: #0f172a; border: 1px solid #1e293b; padding: 20px; margin-top: 15px; border-radius: 12px;">
    <h4 style="color: #ca8a04; margin-top: 0; font-size: 16px;">➕ Expandir Equipa (Cadastrar Novo Funcionário)</h4>
    <form method="POST" action="Admini.php" style="margin-top: 15px; display: flex; gap: 15px; align-items: center;">
        <input type="text" name="nome_func" placeholder="Nome Completo do Barbeiro/Esteticista" style="flex: 1; background: #1e293b; border: 1px solid #334155; padding: 12px; color: white; border-radius: 6px;" required>
        <button type="submit" name="acao_cadastrar_func" style="background: #22c55e; color: white; border: none; padding: 12px 24px; font-weight: bold; border-radius: 6px; cursor: pointer; text-transform: uppercase;">Inserir na Equipa</button>
    </form>
</div>


<!-- ==========================================
     Aba 3: DADOS PESSOAIS E DOCUMENTOS EM ANEXO (Formulário Multipart com Files reais)
     ========================================== -->
     <div id="secao-sub-dados-pessoais" class="sub-aba-conteudo" style="display: none; background: #0f172a; border: 1px solid #1e293b; padding: 20px; margin-top: 15px; border-radius: 12px;">
     <h4 style="color: #a855f7; margin-top: 0; font-size: 16px;">📂 Ficha Cadastral e Arquivo Digital de Ficheiros de RH</h4>
     
     <!-- 🚨 IMPORTANTE: enctype="multipart/form-data" adicionado para upload de ficheiros -->
     <form method="POST" action="Admini.php" enctype="multipart/form-data" style="margin-top: 20px;">
         <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 15px; margin-bottom: 20px;">
             
             <div>
                 <label style="color: #94a3b8; font-size: 12px; display: block; margin-bottom: 5px;">Selecionar Funcionário:</label>
                 <select name="id_funcionario" style="width:100%; background: #1e293b; border: 1px solid #334155; padding: 10px; color: white; border-radius: 6px;" required>
                     <option value="">Selecione o profissional...</option>
                     <?php foreach($todos_funcionarios as $f): ?>
                         <option value="<?php echo $f['id_funcionario']; ?>"><?php echo htmlspecialchars($f['nome']); ?></option>
                     <?php endforeach; ?>
                 </select>
             </div>
 
             <div>
                 <label style="color: #94a3b8; font-size: 12px; display: block; margin-bottom: 5px;">Nº Bilhete Identidade (BI):</label>
                 <input type="text" name="numero_bi" placeholder="Ex: 005478964HU042" style="width:100%; background: #1e293b; border: 1px solid #334155; padding: 10px; color: white; border-radius: 6px;" required>
             </div>
 
             <!-- 📥 NOVO: Upload do ficheiro digital do BI -->
             <div>
                 <label style="color: #38bdf8; font-size: 12px; display: block; margin-bottom: 5px;">📁 Ficheiro digital do BI (PDF/Imagem):</label>
                 <input type="file" name="file_bi" style="width:100%; background: #1e293b; border: 1px solid #334155; padding: 8px; color: white; border-radius: 6px; font-size: 12px;">
             </div>
 
             <div>
                 <label style="color: #94a3b8; font-size: 12px; display: block; margin-bottom: 5px;">Data de Nascimento:</label>
                 <input type="date" name="data_nascimento" style="width:100%; background: #1e293b; border: 1px solid #334155; padding: 10px; color: white; border-radius: 6px;" required>
             </div>
 
             <div>
                 <label style="color: #94a3b8; font-size: 12px; display: block; margin-bottom: 5px;">Contacto Telefónico:</label>
                 <input type="text" name="telefone_pessoal" placeholder="Ex: 9XXXXXXXX" style="width:100%; background: #1e293b; border: 1px solid #334155; padding: 10px; color: white; border-radius: 6px;" required>
             </div>
 
             <div>
                 <label style="color: #94a3b8; font-size: 12px; display: block; margin-bottom: 5px;">Bairro de Residência (Huambo):</label>
                 <input type="text" name="morada_bairro" placeholder="Ex: São Luís, Fátima" style="width:100%; background: #1e293b; border: 1px solid #334155; padding: 10px; color: white; border-radius: 6px;" required>
             </div>
 
             <div>
                 <label style="color: #94a3b8; font-size: 12px; display: block; margin-bottom: 5px;">Nível Académico:</label>
                 <input type="text" name="nivel_academico" placeholder="Ex: Ensino Médio Técnico" style="width:100%; background: #1e293b; border: 1px solid #334155; padding: 10px; color: white; border-radius: 6px;" required>
             </div>
 
             <!-- 📥 NOVO: Upload do Ficheiro do Certificado Académico -->
             <div>
                 <label style="color: #38bdf8; font-size: 12px; display: block; margin-bottom: 5px;">📁 Ficheiro do Certificado Académico:</label>
                 <input type="file" name="file_certificado" style="width:100%; background: #1e293b; border: 1px solid #334155; padding: 8px; color: white; border-radius: 6px; font-size: 12px;">
             </div>
 
             <div>
                 <label style="color: #94a3b8; font-size: 12px; display: block; margin-bottom: 5px;">Formação Profissional Base:</label>
                 <input type="text" name="formacao_prof" placeholder="Ex: Curso de Barbearia" style="width:100%; background: #1e293b; border: 1px solid #334155; padding: 10px; color: white; border-radius: 6px;" required>
             </div>
 
             <!-- 📥 NOVO: Upload do Ficheiro do Diploma do Curso -->
             <div>
                 <label style="color: #38bdf8; font-size: 12px; display: block; margin-bottom: 5px;">📁 Ficheiro do Diploma Profissional:</label>
                 <input type="file" name="file_diploma" style="width:100%; background: #1e293b; border: 1px solid #334155; padding: 8px; color: white; border-radius: 6px; font-size: 12px;">
             </div>
 
             <div>
                 <label style="color: #94a3b8; font-size: 12px; display: block; margin-bottom: 5px;">Data de Admissão:</label>
                 <input type="date" name="data_admissao" style="width:100%; background: #1e293b; border: 1px solid #334155; padding: 10px; color: white; border-radius: 6px;" required>
             </div>
 
             <div>
                 <label style="color: #94a3b8; font-size: 12px; display: block; margin-bottom: 5px;">Salário Base Mensal (Kz):</label>
                 <input type="number" step="0.01" name="salario_base" placeholder="Ex: 85000" style="width:100%; background: #1e293b; border: 1px solid #334155; padding: 10px; color: white; border-radius: 6px;" required>
             </div>
 
             <div>
                 <label style="color: #94a3b8; font-size: 12px; display: block; margin-bottom: 5px;">Bónus / Horas Extras (Kz):</label>
                 <input type="number" step="0.01" name="bonus_horas_extras" placeholder="Ex: 15000" style="width:100%; background: #1e293b; border: 1px solid #334155; padding: 10px; color: white; border-radius: 6px;" required>
             </div>
 
         </div>
 
         <!-- Inputs textareas grandes para Cursos e Experiências -->
         <div style="margin-bottom: 20px;">
             <label style="color: #94a3b8; font-size: 12px; display: block; margin-bottom: 5px;">Outros Cursos e Especializações:</label>
             <textarea name="outros_cursos" rows="3" placeholder="Ex: Especialização em Visagismo, Atendimento ao Cliente..." style="width:100%; background: #1e293b; border: 1px solid #334155; padding: 10px; color: white; border-radius: 6px; font-family: sans-serif; resize: vertical;"></textarea>
         </div>
 
         <div style="margin-bottom: 20px;">
             <label style="color: #94a3b8; font-size: 12px; display: block; margin-bottom: 5px;">Experiências Profissionais Anteriores:</label>
             <textarea name="experiencias_ant" rows="3" placeholder="Ex: Barbeiro Principal no Salão VIP Huambo (2 anos)..." style="width:100%; background: #1e293b; border: 1px solid #334155; padding: 10px; color: white; border-radius: 6px; font-family: sans-serif; resize: vertical;"></textarea>
         </div>
 
         <!-- Área de Ações do Cartão de RH -->
         <div style="display: flex; justify-content: space-between; align-items: center; margin-top: 25px; border-top: 1px solid #1e293b; padding-top: 20px;" class="no-print">
             <a id="btnImprimirSalarioDinamico" href="#" onclick="validarEmissaoRecibo(event)" target="_blank" style="background: #0b1a30; color: #ffffff; text-decoration: none; padding: 12px 24px; font-weight: bold; font-size: 13px; border-radius: 6px; text-transform: uppercase; letter-spacing: 0.5px; border: 1px solid #1e293b; transition: 0.2s;">
                 🖨️ Gerar PDF do Recibo
             </a>
             <button type="submit" name="acao_salvar_dados_pessoais" style="background: #a855f7; color: white; border: none; padding: 12px 30px; font-weight: bold; border-radius: 6px; cursor: pointer; text-transform: uppercase; font-size: 13px; letter-spacing: 0.5px; box-shadow: 0 4px 6px rgba(168, 85, 247, 0.2);">
                 🔒 Guardar Perfil e Ficheiros no Banco
             </button>
         </div>
     </form>
 </div>
     </form>

</div> <!-- Fecha o container secao-sub-dados-pessoais -->
<?php
// Simulação de dados de faturamento de salões e barbeiros parceiros que usam a sua plataforma
// No futuro, estes valores virão de uma tabela chamada 'saloes_parceiros'
$parceiros_hospedados = [
    ['nome' => 'Salão Irmãs Tchizé (São Luís)', 'total_faturado' => 120000, 'comissao_plataforma' => 12000],
    ['nome' => 'Barbearia Central (Cachiungo)', 'total_faturado' => 95000, 'comissao_plataforma' => 9500],
    ['nome' => 'Clínica de Estética Huambo VIP', 'total_faturado' => 180000, 'comissao_plataforma' => 18000],
    ['nome' => 'Mestre Kiluanje Barber (Fátima)', 'total_faturado' => 50000, 'comissao_plataforma' => 5000]
];
?>

<!-- =================================================================
     📋 3º BLOCO REESTRUTURADO: COMISSÕES REAIS, CLIENTE VIP E RANKING
     ================================================================= -->
<br><br> <br>


<!-- BANNER DE DESTAQUE ATUALIZADO: CLIENTE VIP DO ANO AUTOMÁTICO -->
<div style="background: linear-gradient(135deg, #1e293b, #0f172a); border: 2px dashed #eab308; border-radius: 8px; padding: 15px; margin-bottom: 20px; display: flex; align-items: center; gap: 15px; box-shadow: 0 0 12px rgba(234, 179, 8, 0.15); font-family: sans-serif;">
    <div style="font-size: 32px; background: rgba(234, 179, 8, 0.1); padding: 10px; border-radius: 50%; border: 1px solid #eab308; line-height: 1;">👑</div>
    <div>
        <h4 style="color: #eab308; margin: 0; font-size: 11px; text-transform: uppercase; letter-spacing: 1px;">🏆 DISTINÇÃO MENSAL DE FUNCIONÁRIOS</h4>
        <p style="color: #ffffff; font-size: 13px; margin: 4px 0 0 0; font-weight: bold;">
            <?php 
            if (!empty($ranking_funcionarios_reais)) {
                echo "Líder de Cadeira Atual: <span style='color:#facc15;'>" . htmlspecialchars($ranking_funcionarios_reais[0]['nome_funcionario']) . "</span>";
            } else {
                echo "Nenhum líder computado para o presente mês.";
            }
            ?>
        </p>
    </div>
</div>

<!-- O restante bloco <div id="wrapperRankingOculto"> enviado por si entra exatamente aqui -->

<script>
// ⚡ Função mestre para abrir o ranking dinâmico e inverter o botão de controle
function alternarPastaRankingLocal(idComponente) {
    const painel = document.getElementById(idComponente);
    const botaoTexto = document.getElementById('icone-' + idComponente);
    
    if (!painel) return;

    if (painel.style.display === 'none' || painel.style.display === '') {
        painel.style.display = 'block';
        if (botaoTexto) {
            botaoTexto.innerHTML = "▼ RECOLHER RANKING";
            botaoTexto.style.color = "#ef4444"; // Altera para vermelho indicando ação de fecho
        }
    } else {
        painel.style.display = 'none';
        if (botaoTexto) {
            botaoTexto.innerHTML = "▲ ABRIR RANKING";
            botaoTexto.style.color = "#a855f7"; // Retorna ao roxo original
        }
    }
}
</script>






<br>
<!-- 📈 B. LIGAÇÃO DIRETA AO RANKING DE PRODUTIVIDADE OPERACIONAL (COM GRÁFICO INTEGRADO SEGURO) -->
<div id="wrapperRankingOculto" style="background: #132237; border: 1px solid #1e3a8a; padding: 15px; border-radius: 8px; margin-bottom: 20px; max-width: 95%; margin-left: auto; margin-right: auto; font-family: sans-serif;">
    <div onclick="alternarPastaRankingLocal('pastaRankingOculto')" style="display: flex; justify-content: space-between; align-items: center; cursor: pointer; user-select: none;">
        <div>
            <h4 style="margin: 0; font-size: 13px; color: #a855f7; text-transform: uppercase; display: flex; align-items: center; gap: 6px;">
                 RANKING DE PRODUTIVIDADE ENTRE FUNCIONÁRIOS
            </h4>
            <p style="margin: 2px 0 0 0; font-size: 11px; color: #64748b;">Funcionários com maior número de pedidos e faturamento gerado por cadeira.</p>
        </div>
        <span id="icone-pastaRankingOculto" style="font-size: 10px; color: #a855f7; font-weight: bold; background: #0f172a; padding: 4px 10px; border-radius: 6px; border: 1px solid #334155;">▲ ABRIR RANKING</span>
    </div>

    <!-- Conteúdo do Ranking Dinâmico Automatizado -->
    <div id="pastaRankingOculto" style="display: none; margin-top: 15px; padding-top: 10px; border-top: 1px dashed #334155;">
        <div style="display: flex; flex-direction: column; gap: 12px;">
            
            <?php 
            if (empty($ranking_funcionarios_reais)): 
            ?>
                <p style="color: #64748b; text-align: center; font-size: 12px; padding: 10px 0;">Nenhum atendimento computado para a equipa no mês selecionado.</p>
            <?php 
            else: 
                $posicao = 1;
                
                // 🌟 ENCONTRA O MAIOR ATENDIMENTO DO MÊS PARA CONSTRUIR AS BARRAS PROPORCIONAIS SEM ERRAR
                $todos_atendimentos = array_column($ranking_funcionarios_reais, 'total_pedidos');
                $max_atendimentos_mes = (!empty($todos_atendimentos) && max($todos_atendimentos) > 0) ? max($todos_atendimentos) : 1;

                foreach($ranking_funcionarios_reais as $func): 
                    if ($posicao === 1) {
                        $cor_posicao = "#eab308"; // Ouro para o #01
                        $badge_lider = " <span style='font-size:9px; background:rgba(234,179,8,0.15); color:#eab308; padding:1px 4px; border-radius:3px; margin-left:5px; font-weight:bold;'>MELHOR DO MÊS 🏆</span>";
                    } elseif ($posicao === 2) {
                        $cor_posicao = "#cbd5e1"; // Prata para o #02
                        $badge_lider = "";
                    } else {
                        $cor_posicao = "#94a3b8"; // Bronze/Neutro
                        $badge_lider = "";
                    }
                    
                    $total_pedidos_real = (int)($func['total_pedidos'] ?? 0);
                    $valor_monetario_real = (float)($func['faturamento_gerado'] ?? 0.00);
                    
                    // 🌟 CÁLCULO PROPORCIONAL DO GRÁFICO DE BARRAS NATIVO
                    $largura_barra_grafico = ($total_pedidos_real / $max_atendimentos_mes) * 100;
                    if ($largura_barra_grafico < 3 && $total_pedidos_real > 0) { $largura_barra_grafico = 3; }
                ?>
                    <div style="background: #0f172a; padding: 12px; border-radius: 6px; border: 1px solid #1e293b; max-width: 98%; margin: 0 auto; width: 100%; box-sizing: border-box;">
                        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 6px;">
                            <div style="display: flex; align-items: center; gap: 10px;">
                                <span style="font-weight: bold; color: <?php echo $cor_posicao; ?>; font-size: 12px;">
                                    #<?php echo str_pad($posicao++, 2, '0', STR_PAD_LEFT); ?>
                                </span>
                                <div>
                                    <strong style="color: #fff; font-size: 12px;">
                                        <?php echo htmlspecialchars($func['nome_funcionario']); ?>
                                    </strong>
                                    <?php echo $badge_lider; ?>
                                    <span style="display: block; font-size: 10px; color: #64748b; text-transform: uppercase; margin-top: 1px;">
                                        <?php echo htmlspecialchars($func['nome_cargo']); ?>
                                    </span>
                                </div>
                            </div>
                            <div style="text-align: right; font-size: 11px; color: #cbd5e1;">
                                <span style="font-weight: bold; color: #22c55e;"><?php echo $total_pedidos_real; ?> Atendimentos</span>
                                <span style="display: block; font-size: 10px; color: #94a3b8; margin-top: 1px;">
                                    Volume: <?php echo number_format($valor_monetario_real, 2, ',', '.'); ?> Kz
                                </span>
                            </div>
                        </div>

                        <!-- 📊 NOVO GRÁFICO DE BARRAS DE PRODUTIVIDADE (100% CSS SEGURO) -->
                        <div style="background: #1e293b; width: 100%; height: 6px; border-radius: 3px; overflow: hidden; border: 1px solid #23314f; margin-top: 5px;">
                            <div style="background: linear-gradient(90deg, red, #38bdf8); width: <?php echo $largura_barra_grafico; ?>%; height: 100%; border-radius: 3px; transition: width 0.4s ease-in-out;"></div>
                        </div>
                    </div>
                <?php 
                endforeach; 
            endif; 
            ?>
            
        </div>
    </div>
</div>





 
 

    
</div>
<!-- 🟢 FIM DA PASTA OCULTA: Coloque esta linha abaixo do Lucro Consolidado -->
</div>
<!-- ⚡ JAVASCRIPT LOCAL DE EXPANSÃO (ADICIONAR JUNTO AOS OUTROS SCRIPTS) -->
<script>
function alternarListaEmpresas() {
    const painelLista = document.getElementById('pastaRetratilSaaS');
    const botaoTexto = document.getElementById('btnStatusLista');
    
    if (painelLista.style.display === 'none' || painelLista.style.display === '') {
        painelLista.style.display = 'block';
        if (botaoTexto) {
            botaoTexto.innerHTML = "▼ RECOLHER LISTAGEM DE EMPRESAS";
            botaoTexto.style.color = "#ef4444"; // Muda para vermelho ao abrir
        }
    } else {
        painelLista.style.display = 'none';
        if (botaoTexto) {
            botaoTexto.innerHTML = "▲ EXPANDIR LISTAGEM DE EMPRESAS";
            botaoTexto.style.color = "#38bdf8"; // Volta ao ciano padrão
        }
    }
}
</script>
</div> <br> <br> 




<script>
function alternarPastaRankingLocal(idComponente) {
    const bloco = document.getElementById(idComponente);
    const botaoTexto = document.getElementById('icone-' + idComponente);
    const wrapper = document.getElementById('wrapperRankingOculto');
    
    // Efeito de piscar roxo neon preventivo
    if (wrapper) {
        wrapper.style.boxShadow = "0 0 15px rgba(168, 85, 247, 0.4)";
        setTimeout(() => { wrapper.style.boxShadow = "none"; }, 250);
    }
    
    if (bloco.style.display === 'none' || bloco.style.display === '') {
        bloco.style.display = 'block';
        if (botaoTexto) botaoTexto.innerHTML = "▼ RECOLHER RANKING";
    } else {
        bloco.style.display = 'none';
        if (botaoTexto) botaoTexto.innerHTML = "▲ ABRIR RANKING";
    }
}
</script>





<!-- No seu HTML da tabela dentro do Admini.php altere para conter este ID -->
<tbody id="corpoTabelaVip">
    <!-- O PHP inicial carrega aqui e o AJAX substitui dinamicamente -->
</tbody>

<script>
// Função AJAX para atualizar a lista de clientes a cada 5 segundos sem recarregar a página
function carregarClientesVipAutomatico() {
    const corpoTabela = document.getElementById("corpoTabelaVip");
    if (!corpoTabela) return;

    fetch('atualizar_tabela_vip.php')
        .then(response => response.text())
        .then(html => {
            corpoTabela.innerHTML = html;
        })
        .catch(erro => console.error("Erro na sincronização de dados:", erro));
}

// Inicializa a monitorização contínua de dados em tempo real
document.addEventListener("DOMContentLoaded", function() {
    carregarClientesVipAutomatico(); // Executa ao abrir o ecrã
    setInterval(carregarClientesVipAutomatico, 5000); // Sincroniza a cada 5 segundos de forma silenciosa
});
</script>



<?php
include_once("Conexao.php");
if (session_status() === PHP_SESSION_NONE) { session_start(); }

// 🔑 CAPTURA REAL DA IDENTIDADE DO PARCEIRO (BARBEARIA BRANCA ID 20)
$id_salao_logado = isset($_SESSION['codigo_usuario']) ? intval($_SESSION['codigo_usuario']) : (isset($_SESSION['id_usuario']) ? intval($_SESSION['id_usuario']) : 20);

$mensagem_vaga = "";

// 📢 PROCESSAR LANÇAMENTO DE NOVA VAGA DE TRABALHO
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['publicar_vaga_gerente'])) {
    $cargo      = htmlspecialchars(trim($_POST['cargo_vaga']));
    $salario    = htmlspecialchars(trim($_POST['salario_vaga']));
    $requisitos = htmlspecialchars(trim($_POST['requisitos_vaga']));

    try {
        $stmtVaga = $pdo->prepare("INSERT INTO vagas_trabalho (id_barbearia, cargo, salario, requisitos, data_criacao) VALUES (:id_barb, :cargo, :sal, :req, NOW())");
        $stmtVaga->execute([':id_barb' => $id_salao_logado, ':cargo' => $cargo, ':sal' => $salario, ':req' => $requisitos]);
        $mensagem_vaga = "✅ Nova oportunidade de emprego lançada na Bolsa do Huambo!";
    } catch (PDOException $e) { 
        $mensagem_vaga = "❌ Erro ao salvar vaga: " . $e->getMessage(); 
    }
}

// 🔍 BUSCA AS CANDIDATURAS FILTRADAS E REAIS RECEBIDAS DOS CLIENTES
try {
    $stmtCand = $pdo->prepare("SELECT * FROM pedidos_emprego WHERE id_barbearia = :id ORDER BY id DESC");
    $stmtCand->execute([':id' => $id_salao_logado]);
    $listaCandidaturas = $stmtCand->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $listaCandidaturas = [];
}
?>
<!DOCTYPE html>
<html lang="pt-PT">
<head>
    <meta charset="UTF-8">
    <title>Painel Administrativo do Salão - Grupo Aurélius</title>
    <style>
        body { background: #0f172a; color: #fff; font-family: 'Segoe UI', sans-serif; padding: 20px; }
        .box-admin { max-width: 1200px; margin: 20px auto; background: #111827; padding: 30px; border-radius: 12px; border: 1px solid #233147; box-shadow: 0 8px 20px rgba(0,0,0,0.4); }
        .bloco-flex { display: flex; gap: 25px; flex-wrap: wrap; }
        .painel-metade { flex: 1; min-width: 320px; background: #0f172a; padding: 20px; border-radius: 8px; border: 1px solid #1e293b; text-align: left; }
        .form-campo { margin-bottom: 15px; }
        label { display: block; font-size: 11px; color: #94a3b8; text-transform: uppercase; font-weight: bold; margin-bottom: 4px; letter-spacing: 0.5px; }
        input, textarea { width: 100%; padding: 11px; background: #111727; color: #fff; border: 1px solid #334155; border-radius: 6px; box-sizing: border-box; }
        button { background: #eab308; color: #000; padding: 12px; border: none; border-radius: 6px; width: 100%; font-weight: bold; cursor: pointer; text-transform: uppercase; font-size: 12px; }
        table { width: 100%; border-collapse: collapse; text-align: left; font-size: 13px; margin-top: 15px; }
        th { background: #1e293b; color: #eab308; padding: 10px; text-transform: uppercase; font-size: 11px; }
        td { padding: 12px; border-bottom: 1px solid #1e293b; }
    </style>
</head>
<body>






<?php
// =========================================================================
// 💼 CONEXÃO CENTRAL E PROCESSADOR DE VAGAS INTEGRADO (INQUEBRÁVEL)
// =========================================================================
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$mensagemSucessoAlerta = false;

// Ligação local direta e estável ao MariaDB do XAMPP
$link_vagas_SaaS = @mysqli_connect("127.0.0.1", "root", "", "aurelius_salao");

if ($link_vagas_SaaS) {
    mysqli_set_charset($link_vagas_SaaS, "utf8mb4");

    // Interceta o envio real do formulário
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['cargo'])) {
        
        // Captura o ID da barbearia logada (Fallback para 237 se testado fora da sessão)
        $id_parceiro_banco = $_SESSION['codigo'] ?? $_SESSION['loja_id'] ?? 237;
        
        // Coleta e limpa os dados exatamente com os atributos 'name' do formulário
        $cargo_banco      = mysqli_real_escape_string($link_vagas_SaaS, trim($_POST['cargo']));
        $salario_banco    = mysqli_real_escape_string($link_vagas_SaaS, trim($_POST['salario']));
        $requisitos_banco = mysqli_real_escape_string($link_vagas_SaaS, trim($_POST['requisitos']));

        // Executa a query alinhada com as 6 colunas do seu phpMyAdmin
        $sql_insercao_real = "INSERT INTO `vagas_trabalho` (`id_barbearia`, `cargo`, `salario`, `requisitos`, `data_criacao`) 
                              VALUES ($id_parceiro_banco, '$cargo_banco', '$salario_banco', '$requisitos_banco', NOW())";
        
        if (mysqli_query($link_vagas_SaaS, $sql_insercao_real)) {
            $mensagemSucessoAlerta = true;
        }
    }
}
?>















<?php
// =========================================================================
// 💼 BACKOFFICE: MOTOR DE LANÇAMENTO MULTITENANT COM SELEÇÃO DE EMPRESA
// =========================================================================
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$mensagemSucessoAlerta = false;
$listaBarbeariasDisponiveis = [];

// Ligação mestre à base de dados do XAMPP
$link_vagas_SaaS = @mysqli_connect("127.0.0.1", "root", "", "aurelius_salao");

if ($link_vagas_SaaS) {
    mysqli_set_charset($link_vagas_SaaS, "utf8mb4");

    // 🟢 1. BUSCA DINÂMICA: Puxa todas as barbearias registadas e confirmadas para montar o menu
    $sql_empresas = "SELECT `codigo`, `nome`, `endereco` FROM `usuario` ORDER BY `nome` ASC";
    $res_empresas = mysqli_query($link_vagas_SaaS, $sql_empresas);
    if ($res_empresas) {
        while ($empresa_row = mysqli_fetch_assoc($res_empresas)) {
            $listaBarbeariasDisponiveis[] = $empresa_row;
        }
    }

    // 🟢 2. PROCESSADOR DE INSERÇÃO DE VAGA COM AMARRAÇÃO DE ID SELECIONADO
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['disparar_publicacao_vaga'])) {
        
        // Captura o ID escolhido na lista suspensa do formulário
        $id_barbearia_selecionada = (int)$_POST['id_barbearia_escolhida'];
        
        // Higieniza as inputs contra injeção SQL
        $cargo_banco      = mysqli_real_escape_string($link_vagas_SaaS, trim($_POST['cargo']));
        $salario_banco    = mysqli_real_escape_string($link_vagas_SaaS, trim($_POST['salario']));
        $requisitos_banco = mysqli_real_escape_string($link_vagas_SaaS, trim($_POST['requisitos']));

        // Insere com a amarração simétrica do ID selecionado pelo gestor
        $sql_insercao_real = "INSERT INTO `vagas_trabalho` (`id_barbearia`, `cargo`, `salario`, `requisitos`, `data_criacao`) 
                              VALUES ($id_barbearia_selecionada, '$cargo_banco', '$salario_banco', '$requisitos_banco', NOW())";
        
        if (mysqli_query($link_vagas_SaaS, $sql_insercao_real)) {
            $mensagemSucessoAlerta = true;
        }
    }
}
?>





















<script>



// Função para monitorizar a seleção do funcionário e atualizar o link do PDF em tempo real
document.addEventListener("DOMContentLoaded", function() {
    const selectFuncionario = document.querySelector('select[name="id_funcionario"]');
    const linkPdf = document.getElementById('btnImprimirSalarioDinamico');

    if (selectFuncionario && linkPdf) {
        selectFuncionario.addEventListener('change', function() {
            const idSelecionado = this.value;
            
            if (idSelecionado !== "") {
                // Injeta o ID real selecionado na rota do arquivo
                linkPdf.href = `./recibo_salario.php?id=${idSelecionado}`;
                linkPdf.style.background = '#0088cc'; // Acende o botão em azul para avisar que está pronto
            } else {
                linkPdf.href = '#';
                linkPdf.style.background = '#0b1a30';
            }
        });
    }
});

// Impede a abertura do link caso o gerente não tenha escolhido ninguém na lista
function validarEmissaoRecibo(event) {
    const selectFuncionario = document.querySelector('select[name="id_funcionario"]').value;
    if (selectFuncionario === "") {
        event.preventDefault();
        alert("Por favor, selecione primeiro o funcionário na lista da esquerda para poder gerar o recibo de salário!");
    }
}



// 1. SISTEMA COMPLETO PARA GRAVAR AS ALTERAÇÕES DE STATUS NO BANCO DE DADOS
function atualizarStatus(idFuncionario, novoValor) {
    const elemento = document.getElementById('status-' + idFuncionario);
    if (elemento) {
        elemento.innerText = novoValor;
        localStorage.setItem('status-' + idFuncionario, novoValor);
        
        // Atualiza a cor visual em tempo real
        if (novoValor.includes('Ausente') || novoValor.includes('Folga')) {
            elemento.style.color = '#ef4444';
        } else if (novoValor.includes('Atendimento')) {
            element.style.color = '#ffaa00';
        } else {
            elemento.style.color = '#22c55e';
        }

        // Envia o novo estado assincronamente via AJAX para ser salvo na base de dados
        const dadosStatus = new FormData();
        dadosStatus.append('id_funcionario', idFuncionario);
        dadosStatus.append('status', novoValor);

        fetch('atualizar_status_funcionario.php', {
            method: 'POST',
            body: dadosStatus
        })
        .then(response => response.json())
        .then(res => {
            if (!res.sucesso) {
                console.error("Erro ao sincronizar com o banco: " + res.mensagem);
            }
        })
        .catch(err => console.error("Erro de rede ao salvar status:", err));
    }
}

// 2. CONTROLE DE TRANSIÇÃO ENTRE AS NOVAS SUB-ABAS FINANCEIRAS/RH
function alternarSubAbas(idSubAba) {
    document.querySelectorAll('.sub-aba-conteudo').forEach(div => {
        div.style.display = 'none';
    });
    
    // Lista de botões para redefinir as cores secundárias
    const botoes = ['btn-sub-lucros', 'btn-sub-cadastro', 'btn-sub-dados-pessoais'];
    botoes.forEach(btnId => {
        const btn = document.getElementById(btnId);
        if(btn) btn.style.background = '#1e293b';
    });

    // Ativa a sub-aba clicada e acende o botão correspondente
    document.getElementById('secao-' + idSubAba).style.display = 'block';
    document.getElementById('btn-' + idSubAba).style.background = '#0088cc';
}

 // Lógica robusta para Expandir/Recolher o painel de Clientes VIP
function toggleClientesPremium() {
    var aba = document.getElementById("abaClientesPremium");
    var botao = document.getElementById("btnToggleVip");
    
    if (!aba || !botao) return; // Mecanismo de segurança

    // Verifica explicitamente se está oculto ou vazio
    if (aba.style.display === "none" || aba.style.display === "") {
        aba.style.display = "block";
        botao.innerText = "Ocultar Clientes";
        botao.style.background = "linear-gradient(135deg, #ef4444, #b91c1c)";
        botao.style.color = "#ffffff";
    } else {
        aba.style.display = "none";
        botao.innerText = "Ver Clientes Premiums";
        botao.style.background = "linear-gradient(135deg, #ca8a04, #854d0e)";
        botao.style.color = "#0f172a";
    }
}

// 1. FUNÇÃO DE COLAPSO: Abre e fecha as tabelas para economizar espaço
function togglePainelGerencial(idContainer, botaoElemento) {
    const container = document.getElementById(idContainer);
    if (container.style.display === 'none' || container.style.display === '') {
        container.style.display = container.id === 'painelCargosFolhas' ? 'grid' : 'block';
        botaoElemento.innerText = '❌ Fechar Tabela';
        botaoElemento.style.background = '#ef4444';
        botaoElemento.style.color = '#ffffff';
    } else {
        container.style.display = 'none';
        botaoElemento.innerText = ' Abrir Tabela';
        botaoElemento.style.background = botaoElemento.style.background.includes('ca8a04') ? '#ca8a04' : '#38bdf8';
        botaoElemento.style.color = '#0f172a';
    }
}

// 2. PREPARA O FORMULÁRIO DE CAIXA PARA MODO DE EDIÇÃO
function prepararEdicaoCaixa(dadosMovimento) {
    document.getElementById('caixa_id_despesa').value = dadosMovimento.id_despesa;
    document.getElementById('caixa_descricao').value = dadosMovimento.descricao;
    document.getElementById('caixa_tipo').value = dadosMovimento.tipo;
    document.getElementById('caixa_valor').value = dadosMovimento.valor;
    document.getElementById('caixa_data').value = dadosMovimento.data_movimento;
    
    // Altera o comportamento do botão para modo Editar
    const btnSubmeter = document.getElementById('caixa_btn_submeter');
    btnSubmeter.value = 'editar';
    btnSubmeter.innerText = 'Atualizar';
    btnSubmeter.style.background = '#ca8a04';
    
    // Rola a tela de volta para o formulário de forma fluida
    document.getElementById('caixa_descricao').focus();
}









</script>
</body>
</html>