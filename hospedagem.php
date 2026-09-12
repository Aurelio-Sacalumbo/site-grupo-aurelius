<?php
// =========================================================================
// 🌍 MOTOR DE HOSPEDAGEM AUTOMÁTICA SAAS - GRUPO AURÉLIUS (HOSPEDAGEM.PHP)
// =========================================================================
if (session_status() === PHP_SESSION_NONE) { 
    session_start(); 
}
date_default_timezone_set('Africa/Luanda');

// 🟢 1. Controle de Erros em Produção/Desenvolvimento
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// 🟢 2. Ligação Segura à Base de Dados Mestre (Adaptada para Nuvem e Local)
include_once(__DIR__ . "/Conexao.php");

// Reaproveita conexões globais existentes se houver
$mysqli_hospedagem = $conexao_link ?? $conexao_aurelius ?? null;

if (!$mysqli_hospedagem || !($mysqli_hospedagem instanceof mysqli)) {
    // Busca as credenciais das Variáveis de Ambiente (Render/Railway/Deploys)
    $db_host = getenv('DB_HOST') ?: "127.0.0.1";
    $db_user = getenv('DB_USER') ?: "root";
    $db_pass = getenv('DB_PASSWORD') ?: "";
    $db_name = getenv('DB_NAME') ?: "aurelius_salao";
    
    $mysqli_hospedagem = @new mysqli($db_host, $db_user, $db_pass, $db_name);
}

// Interrompe imediatamente se a infraestrutura estiver inacessível
if ($mysqli_hospedagem->connect_error) {
    die("<div style='background:#7f1d1d; color:#fff; padding:20px; font-family:sans-serif;'>
            <h3>🚨 Falha de Ligação</h3>
            <p>O ecossistema não conseguiu ligar ao servidor de dados: " . $mysqli_hospedagem->connect_error . "</p>
         </div>");
}

$mysqli_hospedagem->set_charset("utf8mb4");

// Alias de segurança para compatibilidade com o resto do script
$mysqli = $mysqli_hospedagem;

// =========================================================================
// 🛡️ MOTOR DE PROCESSAMENTO DO FORMULÁRIO DE CADASTRO
// =========================================================================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['cadastrar_parceiro'])) {
    
    // Captura e Higienização Básica de dados do POST
    $email        = trim($_POST['email']);
    $telefone     = trim($_POST['telefone']);
    $nome_gerente = trim($_POST['nome']);
    
    // 🔍 1. MOTOR DE SEGURANÇA CONTRA DUPLICAÇÕES (Convertido de PDO para MySQLi)
    $stmt_checar = $mysqli->prepare("SELECT codigo FROM `usuario` WHERE email = ? OR telefone = ? LIMIT 1");
    $stmt_checar->bind_param("ss", $email, $telefone);
    $stmt_checar->execute();
    $stmt_checar->store_result();
    
    if ($stmt_checar->num_rows > 0) {
        $stmt_checar->close();
        echo "<script>
                alert('⚠️ Erro de Registo: Este e-mail ou número de telefone já está registado!');
                window.history.back();
              </script>";
        exit();
    }
    $stmt_checar->close();

    // 🟢 2. CAPTURA DOS DADOS ESPECÍFICOS DO FORMULÁRIO
    $nome_salao      = isset($_POST['nome_salao']) ? trim($_POST['nome_salao']) : $nome_gerente;
    $provincia       = isset($_POST['provincia']) ? trim($_POST['provincia']) : 'Luanda';
    $endereco_detalhe= isset($_POST['endereco']) ? trim($_POST['endereco']) : '';
    $tipo_servico    = isset($_POST['tipo_servico']) ? trim($_POST['tipo_servico']) : 'Barbearia';
    $qtd_cadeiras    = isset($_POST['cadeiras_operacionais']) ? intval($_POST['cadeiras_operacionais']) : 1;
    $preco_contrato  = isset($_POST['preco']) ? floatval($_POST['preco']) : 0.00;
    $iban_padrao     = isset($_POST['iban_bancario']) ? trim($_POST['iban_bancario']) : '';
    
    $senha_encriptada = md5($_POST['senha']); // Alinhado com o seu padrão de autenticação atual
    $data_atual       = date('Y-m-d');
    $status_inicial   = "Pendente"; // Aguarda ativação pelo painel administrativo
    $nivel_padrao     = "parceiro_hospedado";
    
    // Gera o slug único de navegação
    $slug_padrao = str_replace(' ', '', ucwords(preg_replace('/[^a-zA-Z0-9 ]/', '', $nome_salao)));

    // Concatenação inteligente de endereço
    $endereco_completo = $provincia . " - " . $endereco_detalhe;
    
    // Captura os sub-serviços selecionados e transforma em String/JSON estruturado
    $servicos_selecionados = $_POST['servicos_lista'] ?? [];
    $string_servicos       = !empty($servicos_selecionados) ? implode(", ", $servicos_selecionados) : $tipo_servico;
    $json_specs            = json_encode(["cadeiras_operacionais" => $qtd_cadeiras, "servicos" => $servicos_selecionados], JSON_UNESCAPED_UNICODE);

    // =========================================================================
    // 🟢 3. PROCESSAMENTO FÍSICO DE IMAGENS (UPLOADS)
    // =========================================================================
    $pasta_destino = "uploads/";
    if (!file_exists($pasta_destino)) {
        @mkdir($pasta_destino, 0777, true);
    }

    $nome_logo        = "OIP (6).webp"; // Fallback padrão
    $nome_foto_frente = "";
    $nome_foto_verso  = "";

    if (!empty($_FILES['logo_salao']['name'])) {
        $nome_logo = time() . "_" . basename($_FILES['logo_salao']['name']);
        move_uploaded_file($_FILES['logo_salao']['tmp_name'], $pasta_destino . $nome_logo);
    }
    if (!empty($_FILES['bi_frente']['name'])) {
        $nome_foto_frente = time() . "_frente_" . basename($_FILES['bi_frente']['name']);
        move_uploaded_file($_FILES['bi_frente']['tmp_name'], $pasta_destino . $nome_foto_frente);
    }
    if (!empty($_FILES['bi_verso']['name'])) {
        $nome_foto_verso = time() . "_verso_" . basename($_FILES['bi_verso']['name']);
        move_uploaded_file($_FILES['bi_verso']['tmp_name'], $pasta_destino . $nome_foto_verso);
    }

    // =========================================================================
    // 🟢 4. SALVAMENTO E ESCAPE DE VARIÁVEIS NA TABELA USUARIO
    // =========================================================================
    if (!empty($nome_gerente) && !empty($nome_salao)) {
        
        $gerente_safe  = $mysqli->real_escape_string($nome_gerente);
        $salao_safe    = $mysqli->real_escape_string($nome_salao);
        $email_safe    = $mysqli->real_escape_string($email);
        $tel_safe      = $mysqli->real_escape_string($telefone);
        $end_safe      = $mysqli->real_escape_string($endereco_completo);
        $servico_safe  = $mysqli->real_escape_string($string_servicos);
        $json_safe     = $mysqli->real_escape_string($json_specs);
        $slug_safe     = $mysqli->real_escape_string($slug_padrao);
        $iban_safe     = $mysqli->real_escape_string($iban_padrao);

        // Inserção direta mapeada com a tabela global
        $sql_insert = "INSERT INTO `usuario` (
            `nome`, `nome_funcionario`, `email`, `telefone`, `endereco`, 
            `tipos_de_servico`, `preco`, `transacao_status`, `visivel_no_site`, 
            `nivel`, `slug`, `bi_frente`, `bi_verso`, `logo_empresa`, 
            `senha`, `data`, `especificacoes_json`, `iban_bancario`
        ) VALUES (
            '$gerente_safe', '$salao_safe', '$email_safe', '$tel_safe', '$end_safe', 
            '$servico_safe', $preco_contrato, '$status_inicial', 1, 
            '$nivel_padrao', '$slug_safe', '$nome_foto_frente', '$nome_foto_verso', '$nome_logo', 
            '$senha_encriptada', '$data_atual', '$json_safe', '$iban_safe'
        )";

        if ($mysqli->query($sql_insert) === TRUE) {
            echo "<script>
                    alert('🚀 SUCESSO: Barbearia registada! Aguarde a ativação no Admin corporativo.');
                    window.location.href='hospedagem.php';
                  </script>";
            exit();
        } else {
            die("<div style='background:#7f1d1d; color:#fff; padding:20px; font-family:sans-serif; margin:20px; border-radius:8px;'>
                    <h3>🚨 Erro de Gravação na Base de Dados</h3>
                    <p><b>Mensagem do MySQL:</b> " . $mysqli->error . "</p>
                    <p><b>Query Executada:</b> <pre>" . htmlspecialchars($sql_insert) . "</pre></p>
                 </div>");
        }
    }
}
?>>
<!DOCTYPE html>
<html lang="pt-PT">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Configuração de Espaço Profissional - Grupo Aurélius</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; font-family: 'Segoe UI', Arial, sans-serif; }
        body { font-family: 'Segoe UI', Arial, sans-serif; background: #0f172a; color: #f8fafc; padding: 20px; display: flex; align-items: center; justify-content: center; min-height: 100vh; margin: 0; box-sizing: border-box; }
        .wrapper-card { max-width: 750px; width: 100%; background: #1e293b; padding: 35px; border-radius: 20px; box-shadow: 0 20px 40px rgba(0,0,0,0.5); border: 1px solid #334155; box-sizing: border-box; }
        
        .barra-passos { display: flex; justify-content: space-between; margin-bottom: 25px; background: #0f172a; padding: 12px 20px; border-radius: 30px; border: 1px solid #334155; }
        .passo-txt { font-size: 11px; font-weight: bold; color: #64748b; text-transform: uppercase; letter-spacing: 0.5px; cursor: pointer; }
        .passo-txt.active { color: #38bdf8; }
        
        .aba-painel { display: none; text-align: left; }
        .aba-painel.active { display: block; }
        
        .form-campo { margin-bottom: 16px; display: flex; flex-direction: column; }
        .form-campo label { font-size: 11px; font-weight: bold; color: #94a3b8; text-transform: uppercase; margin-bottom: 6px; letter-spacing: 0.5px; }
        .form-campo input, .form-campo select, .form-campo textarea { padding: 12px; background: #0f172a; border: 1px solid #475569; border-radius: 6px; color: white; font-size: 14px; width: 100%; box-sizing: border-box; }
        .form-campo input:focus, .form-campo select:focus { border-color: #38bdf8; outline: none; }
        
        .gela-servicos-scrolling { display: grid; grid-template-columns: repeat(auto-fill, minmax(240px, 1fr)); gap: 10px; max-height: 220px; overflow-y: auto; background: #0b0f19; padding: 15px; border-radius: 8px; border: 1px solid #334155; margin-top: 5px; }
        .check-item-box { display: flex; align-items: center; gap: 10px; font-size: 13px; color: #cbd5e1; cursor: pointer; padding: 4px; }
        .check-item-box input { width: 16px; height: 16px; accent-color: #38bdf8; cursor: pointer; }
        
        .grid-custom { display: grid; grid-template-columns: 1fr 1fr; gap: 16px; }
        .btn-infraestrutura-trigger { width: 100%; padding: 16px; background: #22c55e; color: #000; border: none; border-radius: 8px; font-weight: bold; font-size: 14px; text-transform: uppercase; cursor: pointer; box-shadow: 0 4px 14px rgba(34, 197, 94, 0.3); margin-top: 15px; }
        .btn-navegacao { padding: 12px 24px; background: #0284c7; color: white; border: none; border-radius: 6px; font-weight: bold; cursor: pointer; text-transform: uppercase; font-size: 12px; }
        .multimedia-box { display: flex; align-items: center; gap: 8px; background: rgba(56, 189, 248, 0.05); border: 1px solid rgba(56, 189, 248, 0.2); padding: 10px; border-radius: 6px; font-size: 11px; color: #38bdf8; margin-bottom: 12px; }

        
    </style>
</head>
<body>

<div class="wrapper-card">
    <div style="text-align: center; margin-bottom: 30px; border-bottom: 1px solid #334155; padding-bottom: 15px;">
        <h2 style="font-size: 22px; color: #fff;">🌍 Motor de Hospedagem Automática SaaS</h2>
        <p style="color: #94a3b8; font-size: 13px; margin-top: 4px;">Instancie a sua barbearia autossustentável em 10 camadas de banco de dados imediatamente [local].</p>
    </div>

    <?php if (!empty($erro_mensagem)): ?>
        <div style="background: rgba(239, 68, 68, 0.1); border: 1px solid #f87171; padding: 12px; border-radius: 6px; color: #f87171; margin-bottom: 20px; font-size: 13px; font-weight: bold;"><?= htmlspecialchars($erro_mensagem) ?></div>
    <?php endif; ?>

    <div class="barra-passos">
        <span class="passo-txt active" id="p1" onclick="mudarPasso(1)">1. Identidade</span>
        <span class="passo-txt" id="p2" onclick="mudarPasso(2)">2. Gestor & BI</span>
        <span class="passo-txt" id="p3" onclick="mudarPasso(3)">3. Arquitetura</span>
    </div>

    <!-- 🔥 Sincronizado para bater no motor PHP local da hospedagem.php -->
    <form method="POST" action="hospedagem.php" enctype="multipart/form-data" id="formInstanciacaoMaster">
        
        <!-- =========================================================================
             ETAPA 1: IDENTIDADE E ARQUIVOS DE DESIGN
             ========================================================================= -->
        <div id="etapa1" class="aba-painel active">
            <div class="grid-custom">
                <div class="form-campo">
                    <label>Nome Comercial do Salão / Barbearia:</label>
                    <input type="text" name="nome_salao" placeholder="Ex: Barbearia LookNovo" required>
                </div>
                <div class="form-campo">
                    <label>Logótipo Oficial da Empresa:</label>
                    <input type="file" name="logo_salao" accept="image/*">
                </div>
            </div>
            
            <div class="multimedia-box">
                📷 <span>O logótipo carregado será exibido automaticamente no card principal do painel multitenant [visual-exploration].</span>
            </div>

            <div class="grid-custom">
                <div class="form-campo">
                    <label>Província Sede (Angola):</label>
                    <select name="provincia" required style="width: 100%; padding: 11px 14px; background: #070b12; border: 1px solid #374151; border-radius: 8px; color: #fff; font-size: 13px; outline: none; box-sizing: border-box; font-family: sans-serif; cursor: pointer;">
                        <option value="" disabled selected>Selecione a Província...</option>
                        <option value="Bengo">Bengo</option>
                        <option value="Benguela">Benguela</option>
                        <option value="Bié">Bié</option>
                        <option value="Cabinda">Cabinda</option>
                        <option value="Cuando-Cubango">Cuando-Cubango</option>
                        <option value="Cuanza-Norte">Cuanza-Norte</option>
                        <option value="Cuanza-Sul">Cuanza-Sul</option>
                        <option value="Cunene">Cunene</option>
                        <option value="Huambo">Huambo</option>
                        <option value="Huíla">Huíla</option>
                        <option value="Luanda">Luanda</option>
                        <option value="Lunda-Norte">Lunda-Norte</option>
                        <option value="Lunda-Sul">Lunda-Sul</option>
                        <option value="Malanje">Malanje</option>
                        <option value="Moxico">Moxico</option>
                        <option value="Namibe">Namibe</option>
                        <option value="Uíge">Uíge</option>
                        <option value="Zaire">Zaire</option>
                    </select>
                </div>
                <div class="form-campo">
                    <label>Endereço, Bairro e Ponto de Referência:</label>
                    <input type="text" name="endereco" placeholder="Ex: Bairro de São Luís / IECA" required>
                </div>
            </div>
            <div style="text-align: right; margin-top: 15px; display: flex; justify-content: flex-end; gap: 10px;">
                <a href="Principal.php" class="btn-navegacao" style="background: #475569; color: white; padding: 10px 20px; border-radius: 8px; text-decoration: none; font-size: 12px; font-weight: bold; display: inline-block; line-height: 1.5; text-transform: uppercase;">Voltar</a>
                <button type="button" class="btn-navegacao" onclick="mudarPasso(2)">Avançar →</button>
            </div>
        </div>
   
        <!-- =========================================================================
             ETAPA 2: GESTOR, CREDENCIAIS E AUDITORIA NACIONAL
             ========================================================================= -->
        <div id="etapa2" class="aba-painel" style="display: none;">
            <div class="grid-custom">
                <div class="form-campo">
                    <label>Nome Completo do Gerente / Diretor:</label>
                    <input type="text" name="nome_gerente" id="nome_gerente_campo" placeholder="Nome do responsável legal" required>
                </div>
                <div class="form-campo">
                    <label>Bilhete de Identidade (BI Angola):</label>
                    <input type="text" name="bi_gestor" id="bi_campo" placeholder="Ex: 004732158LA042" maxlength="14" style="letter-spacing:1px; font-weight:bold; color:#eab308;" required>
                </div>
            </div>
            
            <div class="grid-custom" style="margin-top: 5px; margin-bottom: 15px;">
                <div class="form-campo">
                    <label>📷 Carregar Frente do B.I.:</label>
                    <input type="file" name="bi_frente" accept="image/*" style="padding: 8px;">
                </div>
                <div class="form-campo">
                    <label>📷 Carregar Verso do B.I.:</label>
                    <input type="file" name="bi_verso" accept="image/*" style="padding: 8px;">
                </div>
            </div>

            <div class="grid-custom">
                <div class="form-campo">
                    <label>E-mail de Contacto Corporativo:</label>
                    <input type="email" name="email" id="email_campo" placeholder="gestao@empresa.com" required>
                </div>
                <div class="form-campo">
                    <label>Contacto Telefónico:</label>
                    <input type="number" name="telefone" id="telefone_campo" placeholder="9XXXXXXXX" required>
                </div>
            </div>
            <div class="form-campo">
                <label>Palavra-Passe de Acesso:</label>
                <input type="password" name="senha_login" id="senha_campo" placeholder="Mínimo 6 caracteres" required>
            </div>
            <div style="display: flex; justify-content: space-between; margin-top: 15px;">
                <button type="button" class="btn-navegacao" style="background:#475569;" onclick="mudarPasso(1)">← Voltar</button>
                <button type="button" class="btn-navegacao" onclick="ejecutarAuditoriaGestorEtapa2()">Avançar →</button>
            </div>
        </div>

        <!-- =========================================================================
             ETAPA 3: ARQUITETURA INDEPENDENTE E SUB-SERVIÇOS
             ========================================================================= -->
        <div id="etapa3" class="aba-painel" style="display: none;">
            <div class="grid-custom">
                <div class="form-campo">
                    <label>Quantidade de Cadeiras Operacionais:</label>
                    <input type="number" name="qtd_cadeiras" value="2" min="1" required>
                </div>
                <div class="form-campo">
                    <label style="color: #10b981;">Preço Proposto do Contrato (Kz):</label>
                    <input type="number" name="preco_contrato" step="0.01" value="10000.00" style="border-color: #10b981; font-weight: bold;" required>
                </div>
            </div>
            
            <div class="form-campo" style="margin-top: 12px;">
                <label>Modalidade Operacional / Categoria:</label>
                <input type="text" name="tipo_servico" value="Geral" placeholder="Ex: Premium, Geral, Luxo">
            </div>
            
            <div class="form-campo" style="margin-top: 15px;">
                <label>Gama de Ferramentas e Sub-Serviços Especializados:</label>
                <div class="gela-servicos-scrolling">
                    <span style="grid-column: 1/-1; font-size: 11px; color:#38bdf8; font-weight:bold; text-transform:uppercase;">✂️ Menu de Cortes & Estilos</span>
                    <label class="check-item-box"><input type="checkbox" name="servicos_lista[]" value="Corte Adulto Clássico" checked> Corte Adulto Clássico</label>
                    <label class="check-item-box"><input type="checkbox" name="servicos_lista[]" value="Corte Careca Total"> Corte Careca Total</label>
                    <label class="check-item-box"><input type="checkbox" name="servicos_lista[]" value="Barba Simples" checked> Design de Barba</label>
                    <label class="check-item-box"><input type="checkbox" name="servicos_lista[]" value="Sobrancelhas"> Limpeza de Sobrancelhas</label>
                </div>
            </div>

            <div style="display: flex; justify-content: space-between; margin-top: 25px;">
                <button type="button" class="btn-navegacao" style="background:#475569;" onclick="mudarPasso(2)">← Voltar</button>
                <button type="button" class="btn-navegacao" style="background:#475569;" onclick="mudarPasso(2)">← Voltar</button>
                <button type="submit" name="finalizar_saas" class="btn-infraestrutura-trigger" style="margin-top: 0; width: auto; padding: 12px 24px;">🚀 Concluir Instanciação SaaS</button>
            </div>
        </div> <!-- 🟢 FECHA A ETAPA 3 -->
   
    </form>
</div>

<!-- =========================================================================
     🎮 NAVEGAÇÃO INTERATIVA (MUDAR DE PASSO) E VALIDAÇÕES DO SISTEMA
     ========================================================================= -->
<script>
function mudarPasso(passo) {
    // Controla estritamente a exibição das abas evitando conflitos
    const e1 = document.getElementById('etapa1');
    const e2 = document.getElementById('etapa2');
    const e3 = document.getElementById('etapa3');

    if(e1) e1.style.display = (passo === 1) ? 'block' : 'none';
    if(e2) e2.style.display = (passo === 2) ? 'block' : 'none';
    if(e3) e3.style.display = (passo === 3) ? 'block' : 'none';

    // Atualiza os indicadores de progresso visuais no cabeçalho
    const p1 = document.getElementById('p1');
    const p2 = document.getElementById('p2');
    const p3 = document.getElementById('p3');

    if(p1) p1.classList.toggle('active', passo === 1);
    if(p2) p2.classList.toggle('active', passo === 2);
    if(p3) p3.classList.toggle('active', passo === 3);
}

// 🔥 DESTRAVADOR LOCAL: Garante o avanço imediato para o ecrã de Preços/Arquitetura
function ejecutarAuditoriaGestorEtapa2() {
    mudarPasso(3);
}

// Bloqueia cliques duplos na submissão final enquanto o servidor processa as fotos
document.getElementById('formInstanciacaoMaster').onsubmit = function() {
    const btn = document.querySelector('.btn-infraestrutura-trigger');
    if (btn) {
        btn.innerHTML = "⌛ Instanciando camadas corporativas...";
        btn.style.opacity = "0.7";
    }
    return true;
};
</script>

</body>
</html>