<?php
// =========================================================================
// 🌍 MOTOR DE HOSPEDAGEM AUTOMÁTICA SAAS - GRUPO AURÉLIUS (HOSPEDAGEM.PHP)
// =========================================================================
if (session_status() === PHP_SESSION_NONE) { 
    session_start(); 
}
date_default_timezone_set('Africa/Luanda');

// 1. Controle de Erros Ativo para Desenvolvimento Local
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// 2. Ligação Segura à Base de Dados Mestre
include_once(__DIR__ . "/Conexao.php");
$mysqli = $conexao_link ?? $conexao_aurelius ?? null;

if (!$mysqli || !($mysqli instanceof mysqli)) {
    $db_host = getenv('DB_HOST') ?: "127.0.0.1";
    $db_user = getenv('DB_USER') ?: "root";
    $db_pass = getenv('DB_PASSWORD') ?: "";
    $db_name = getenv('DB_NAME') ?: "aurelius_salao";
    $mysqli = @new mysqli($db_host, $db_user, $db_pass, $db_name);
}

if ($mysqli->connect_error) {
    die("🚨 Falha de Infraestrutura local ou na nuvem.");
}
$mysqli->set_charset("utf8mb4");

// Variáveis de feedback reativo
$mensagem_erro = "";
$mensagem_sucesso = "";

// =========================================================================
// 🚀 PROCESSADOR INTEGRADO COM ALERTA DE SUCESSO OU FALHA
// =========================================================================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['finalizar_saas'])) {
    
    // Captura segura contra Warnings do PHP 8.1+
    $nome_salao      = isset($_POST['nome_barbearia']) ? trim($_POST['nome_barbearia'] ?? '') : '';
    $nome_gerente    = isset($_POST['nome_gerente']) ? trim($_POST['nome_gerente'] ?? '') : $nome_salao;
    $email           = isset($_POST['email']) ? trim($_POST['email'] ?? '') : '';
    $telefone        = isset($_POST['telefone']) ? trim($_POST['telefone'] ?? '') : '';
    $pin_acesso      = isset($_POST['pin_cadastro']) ? trim($_POST['pin_cadastro'] ?? '') : '';
    
    $provincia       = isset($_POST['provincia']) ? trim($_POST['provincia'] ?? '') : 'Luanda';
    $endereco_detalhe= isset($_POST['endereco']) ? trim($_POST['endereco'] ?? '') : '';
    $tipo_servico    = isset($_POST['tipo_servico']) ? trim($_POST['tipo_servico'] ?? '') : 'Geral';
    $qtd_cadeiras    = isset($_POST['qtd_cadeiras']) ? intval($_POST['qtd_cadeiras']) : 2;
    $preco_contrato  = isset($_POST['preco_contrato']) ? floatval($_POST['preco_contrato']) : 10000.00;
    $iban_padrao     = isset($_POST['iban_bancario']) ? trim($_POST['iban_bancario'] ?? '') : '';
    
    $senha_encriptada = md5($pin_acesso); 
    $data_atual       = date('Y-m-d');
    
    // Estado corporativo padrão e imediato no SaaS
    $status_inicial   = "Confirmado"; 
    $nivel_padrao     = "parceiro_hospedado";
    
    $slug_padrao = str_replace(' ', '', ucwords(preg_replace('/[^a-zA-Z0-9 ]/', '', $nome_salao)));
    $endereco_completo = $provincia . " - " . $endereco_detalhe;
    
    $servicos_selecionados = $_POST['servicos_lista'] ?? [];
    $string_servicos       = !empty($servicos_selecionados) ? implode(", ", $servicos_selecionados) : $tipo_servico;
    $json_specs            = json_encode(["cadeiras_operacionais" => $qtd_cadeiras, "servicos" => $servicos_selecionados], JSON_UNESCAPED_UNICODE);

    // Upload de Imagens de Fallback
    $nome_logo = "OIP (6).webp";
    if (!empty($_FILES['logo_salao']['name'])) {
        $nome_logo = time() . "_" . basename($_FILES['logo_salao']['name']);
        move_uploaded_file($_FILES['logo_salao']['tmp_name'], "uploads/" . $nome_logo);
    }

    // Executa a validação e tentativa de gravação
    if (!empty($nome_salao) && !empty($pin_acesso)) {
        $salao_safe    = $mysqli->real_escape_string($nome_salao);
        $gerente_safe  = $mysqli->real_escape_string($nome_gerente);
        $email_safe    = $mysqli->real_escape_string($email);
        $tel_safe      = $mysqli->real_escape_string($telefone);
        $end_safe      = $mysqli->real_escape_string($endereco_completo);
        $servico_safe  = $mysqli->real_escape_string($string_servicos);
        $json_safe     = $mysqli->real_escape_string($json_specs);
        $slug_safe     = $mysqli->real_escape_string($slug_padrao);
        $iban_safe     = $mysqli->real_escape_string($iban_padrao);
        $pin_safe      = $mysqli->real_escape_string($pin_acesso);

        $sql_insert = "INSERT INTO `usuario` (
            `nome`, `nome_funcionario`, `email`, `telefone`, `endereco`, 
            `tipos_de_servico`, `preco`, `transacao_status`, `visivel_no_site`, 
            `nivel`, `slug`, `logo_empresa`, `senha`, `pin_acesso`, `data`, `especificacoes_json`, `iban_bancario`
        ) VALUES (
            '$salao_safe', '$gerente_safe', '$email_safe', '$tel_safe', '$end_safe', 
            '$servico_safe', $preco_contrato, '$status_inicial', 1, 
            '$nivel_padrao', '$slug_safe', '$nome_logo', '$senha_encriptada', '$pin_safe', '$data_atual', '$json_safe', '$iban_safe'
        )";

        if ($mysqli->query($sql_insert) === TRUE) {
            // 🟢 ALERTA DE SUCESSO COESOR: Dispara pop-up na tela e redireciona
            echo "<script>
                    alert('✓ PARABÉNS! Instância criada com sucesso. A tua barbearia já se encontra ativa na vitrina principal do Grupo Aurélius!');
                    window.location.href='Principal.php';
                  </script>";
            exit();
        } else {
            // 🚨 CAPTURA DE ERRO DO BANCO: Registra falha na query
            $mensagem_erro = "❌ ERRO DE GRAVAÇÃO: Não foi possível salvar no banco de dados. Motivo: " . $mysqli->error;
        }
    } else {
        $mensagem_erro = "⚠️ DADOS EM FALTA: O Nome da Barbearia e o PIN de Acesso são campos obrigatórios.";
    }
}
?>
<!DOCTYPE html>
<html lang="pt-PT">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Configuração de Espaço Profissional - Grupo Aurélius</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; font-family: 'Segoe UI', system-ui, -apple-system, sans-serif; }
        
        body { 
            background: #070b12; /* Ajustado para o preto profundo do ecossistema principal */
            color: #f8fafc; 
            padding: 10px; /* Reduzido para dar o máximo de espaço útil em telemóveis */
            display: flex; 
            align-items: center; 
            justify-content: center; 
            min-height: 100vh; 
            box-sizing: border-box; 
        }

        /* 📱 CONTAINER RESPONSIVO EXPANDIDO PARA TELEMÓVEIS */
        .wrapper-card { 
            max-width: 750px; 
            width: 100%; 
            background: #1e293b; 
            padding: 22px 16px; /* Otimizado para os inputs respirarem no mobile */
            border-radius: 16px; 
            box-shadow: 0 20px 40px rgba(0,0,0,0.5); 
            border: 1px solid #334155; 
            box-sizing: border-box; 
        }
        
        /* 🟢 MENUS DE PASSOS COM ROLAGEM MÓVEL INVISÍVEL */
        .barra-passos { 
            display: flex; 
            justify-content: space-between; 
            gap: 10px;
            margin-bottom: 25px; 
            background: #070b12; 
            padding: 12px 16px; 
            border-radius: 30px; 
            border: 1px solid #334155; 
            overflow-x: auto; /* Permite scroll se o ecrã for muito pequeno */
            scrollbar-width: none; /* Oculta a barra no Firefox */
            -webkit-overflow-scrolling: touch;
        }
        .barra-passos::-webkit-scrollbar { display: none; } /* Oculta barra no Chrome/Safari */

        .passo-txt { 
            font-size: 11px; 
            font-weight: 700; 
            color: #64748b; 
            text-transform: uppercase; 
            letter-spacing: 0.5px; 
            cursor: pointer; 
            white-space: nowrap; /* Impede a quebra de texto nos passos */
        }
        .passo-txt.active { color: #38bdf8; }
        
        .aba-painel { display: none; text-align: left; }
        .aba-painel.active { display: block; }
        
        .form-campo { margin-bottom: 16px; display: flex; flex-direction: column; }
        .form-campo label { font-size: 11px; font-weight: 700; color: #94a3b8; text-transform: uppercase; margin-bottom: 6px; letter-spacing: 0.5px; }
        
        /* 🟢 PREVENÇÃO DE ZOOM: font-size em 16px bloqueia o zoom automático de ecrã no iOS e Android */
        .form-campo input, 
        .form-campo select, 
        .form-campo textarea { 
            padding: 13px 14px; 
            background: #070b12; 
            border: 1px solid #475569; 
            border-radius: 8px; 
            color: white; 
            font-size: 16px; 
            width: 100%; 
            box-sizing: border-box; 
            outline: none;
            transition: border-color 0.2s;
            color-scheme: dark;
        }
        .form-campo input:focus, 
        .form-campo select:focus,
        .form-campo textarea:focus { 
            border-color: #38bdf8; 
            box-shadow: 0 0 8px rgba(56, 189, 248, 0.15);
        }
        
        /* Grelha de Serviços Otimizada */
        .gela-servicos-scrolling { 
            display: grid; 
            grid-template-columns: repeat(auto-fill, minmax(200px, 1fr)); 
            gap: 10px; 
            max-height: 220px; 
            overflow-y: auto; 
            background: #070b12; 
            padding: 15px; 
            border-radius: 8px; 
            border: 1px solid #334155; 
            margin-top: 5px; 
        }
        .check-item-box { display: flex; align-items: center; gap: 10px; font-size: 14px; color: #cbd5e1; cursor: pointer; padding: 6px 4px; }
        .check-item-box input { width: 18px; height: 18px; accent-color: #38bdf8; cursor: pointer; }
        
        /* 🟢 ADAPTAÇÃO FLEXÍVEL DE GRIDS PARA MOBILE */
        .grid-custom { 
            display: grid; 
            grid-template-columns: 1fr; /* Padrão Mobile: Uma coluna empilhada */
            gap: 16px; 
        }
        
        /* Botão Mestre Maciço para clique rápido com o Polegar */
        .btn-infraestrutura-trigger { 
            width: 100%; 
            padding: 16px; 
            background: linear-gradient(135deg, #22c55e, #16a34a); 
            color: #000; 
            border: none; 
            border-radius: 8px; 
            font-weight: 700; 
            font-size: 13.5px; 
            text-transform: uppercase; 
            cursor: pointer; 
            letter-spacing: 0.5px;
            box-shadow: 0 4px 14px rgba(34, 197, 94, 0.3); 
            margin-top: 15px; 
            transition: background 0.2s;
        }
        .btn-infraestrutura-trigger:hover { filter: brightness(1.1); }

        .btn-navegacao { 
            padding: 14px 24px; 
            background: #0284c7; 
            color: white; 
            border: none; 
            border-radius: 8px; 
            font-weight: 700; 
            cursor: pointer; 
            text-transform: uppercase; 
            font-size: 12px; 
            letter-spacing: 0.5px;
            width: 100%; /* Botões ocupam largura total e empilham em telas pequenas */
        }
        
        .multimedia-box { display: flex; align-items: center; gap: 8px; background: rgba(56, 189, 248, 0.05); border: 1px solid rgba(56, 189, 248, 0.2); padding: 12px 10px; border-radius: 8px; font-size: 11.5px; color: #38bdf8; margin-bottom: 12px; }

        /* 🟢 MEDIAS QUERIES: Transição responsiva fluida para Tablets e PC */
        @media (min-width: 576px) {
            body { padding: 20px; }
            .wrapper-card { padding: 35px; }
            .grid-custom { grid-template-columns: 1fr 1fr; } /* Divide em 2 colunas lado a lado no PC */
            .btn-navegacao { width: auto; } /* Botões voltam ao tamanho normal no Desktop */
        }
    </style>
</head>
<body>

<div class="wrapper-card">
    <div style="text-align: center; margin-bottom: 30px; border-bottom: 1px solid #334155; padding-bottom: 15px;">
        <h2 style="font-size: 22px; color: #fff;">🌍Crie seu Salão de Beleza online</h2>
        <p style="color: #94a3b8; font-size: 13px; margin-top: 4px;">Instancie a sua barbearia autossustentável em 3 camadas.</p>
    </div>

    <?php if (!empty($erro_mensagem)): ?>
        <div style="background: rgba(239, 68, 68, 0.1); border: 1px solid #f87171; padding: 12px; border-radius: 6px; color: #f87171; margin-bottom: 20px; font-size: 13px; font-weight: bold;"><?= htmlspecialchars($erro_mensagem) ?></div>
    <?php endif; ?>

    <div class="barra-passos">
        <span class="passo-txt active" id="p1" onclick="mudarPasso(1)">1. Identidade</span>
        <span class="passo-txt" id="p2" onclick="mudarPasso(2)">2. Gestor & BI</span>
        <span class="passo-txt" id="p3" onclick="mudarPasso(3)">3. Arquitetura</span>
    </div>

<!-- Coloca isto logo acima do formulário no teu HTML -->
<?php if (!empty($mensagem_erro)): ?>
    <div style="background: rgba(239, 68, 68, 0.15); border: 2px solid #ef4444; padding: 15px; border-radius: 12px; color: #f87171; font-size: 14px; font-weight: bold; text-align: center; margin-bottom: 20px; width: 100%; box-sizing: border-box; font-family: sans-serif;">
        <span style="font-size: 20px; display: block; margin-bottom: 5px;">🚨</span>
        <?= htmlspecialchars($mensagem_erro) ?>
    </div>
<?php endif; ?>
    <!-- 🔥 Sincronizado para bater no motor PHP local da hospedagem.php -->
   <!-- 🔥 Sincronizado para bater no motor PHP local da hospedagem.php -->
<form method="POST" action="hospedagem.php" enctype="multipart/form-data" id="formInstanciacaoMaster">
    
<!-- =========================================================================
     ETAPA 1: IDENTIDADE E ARQUIVOS DE DESIGN
     ========================================================================= -->
<div id="etapa1" class="aba-painel active" style="display: block;">
    <div class="grid-custom">
        <div class="form-campo">
            <label>Nome Comercial do Salão / Barbearia:</label>
            <!-- 🟢 CORREÇÃO: Alterado de nome_salao para nome_barbearia para bater com o PHP -->
            <input type="text" name="nome_barbearia" id="nome_barbearia" required>
        </div>
        <div class="form-campo">
            <label>Logótipo Oficial da Empresa:</label>
            <input type="file" name="logo_salao" accept="image/*">
        </div>
    </div>
    
    <div class="multimedia-box">
        📷 <span>O logótipo carregado será exibido automaticamente no card principal do painel multitenant.</span>
    </div>

    <div class="grid-custom">
        <div class="form-campo">
            <label>Província:</label>
            <select name="provincia" id="provincia" required style="width: 100%; padding: 11px 14px; background: #070b12; border: 1px solid #374151; border-radius: 8px; color: #fff; font-size: 13px; outline: none; box-sizing: border-box; font-family: sans-serif; cursor: pointer;">
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
            <label>Endereço:</label>
            <input type="text" name="endereco" id="endereco" required>
        </div>
    </div>
    <div style="text-align: right; margin-top: 15px; display: flex; justify-content: flex-end; gap: 10px;">
        <a href="Principal.php" class="btn-navegacao" style="background: #475569; color: white; padding: 10px 20px; border-radius: 8px; text-decoration: none; font-size: 12px; font-weight: bold; display: inline-block; line-height: 1.5; text-transform: uppercase; text-align: center;">Voltar</a>
        <button type="button" class="btn-navegacao" onclick="avancarParaEtapa2()">Avançar →</button>
    </div>
</div>

<!-- =========================================================================
     ETAPA 2: GESTOR, CREDENCIAIS E AUDITORIA NACIONAL
     ========================================================================= -->
<div id="etapa2" class="aba-painel" style="display: none;">
    <div class="grid-custom">
        <div class="form-campo">
            <label>Nome do Gerente:</label>
            <input type="text" name="nome_gerente" id="nome_gerente_campo" required>
        </div>
        <div class="form-campo">
            <label>Bilhete de Identidade:</label>
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
            <label>E-mail Mercantil:</label>
            <input type="email" name="email" id="email_campo" required>
        </div>
        <div class="form-campo">
            <label>Contacto Telefónico:</label>
            <input type="tel" name="telefone" id="telefone_campo" maxlength="9" oninput="this.value = this.value.replace(/[^0-9]/g, '');" required>
        </div>
    </div>
    <div class="form-campo">
        <label>Palavra-Passe / PIN de Acesso (Numérico):</label>
        <!-- 🟢 CORREÇÃO: Alterado name para 'pin_cadastro' para sincronizar com o md5() do PHP -->
        <input type="password" name="pin_cadastro" id="senha_campo" maxlength="9" inputmode="numeric" oninput="this.value = this.value.replace(/[^0-9]/g, '');" placeholder="Mínimo 4 algarismos" required>
    </div>
    <div style="display: flex; justify-content: space-between; margin-top: 15px; gap: 10px;">
        <button type="button" class="btn-navegacao" style="background:#475569;" onclick="mudarPasso(1)">← Voltar</button>
        <button type="button" class="btn-navegacao" onclick="ejecutarAuditoriaGestorEtapa2()">Avançar →</button>
    </div>
</div>

<!-- =========================================================================
     ETAPA 3: ARQUITETURA INDEPENDENTE E SUB-SERVIÇOS (RESTAURADA E COMPLETA)
     ========================================================================= -->
<div id="etapa3" class="aba-painel" style="display: none;">
    <div class="grid-custom">
        <div class="form-campo">
            <label>Quantidade de Cadeiras Operacionais:</label>
            <input type="number" name="qtd_cadeiras" id="qtd_cadeiras" value="2" min="1" required>
        </div>
        <div class="form-campo">
            <label style="color: #10b981;">Preço Proposto do Contrato (Kz):</label>
            <input type="number" name="preco_contrato" id="preco_contrato" step="0.01" value="10000.00" style="border-color: #10b981; font-weight: bold;" required>
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

    <div style="display: flex; justify-content: space-between; margin-top: 25px; gap: 10px;">
        <button type="button" class="btn-navegacao" style="background:#475569;" onclick="mudarPasso(2)">← Voltar</button>
        <!-- 🟢 BOTÃO FINAL COESOR COM O NAME CORRETO PARA DISPARAR O ALERTA DO PHP -->
        <button type="submit" name="finalizar_saas" class="btn-infraestrutura-trigger" style="margin-top: 0; width: auto; padding: 12px 24px;">🚀 Ativar Camada SaaS</button>
    </div>
</div>
</form>

<script>
function mudarPasso(passo) {
const e1 = document.getElementById('etapa1');
const e2 = document.getElementById('etapa2');
const e3 = document.getElementById('etapa3');

if(e1) e1.style.display = (passo === 1) ? 'block' : 'none';
if(e2) e2.style.display = (passo === 2) ? 'block' : 'none';
if(e3) e3.style.display = (passo === 3) ? 'block' : 'none';

const p1 = document.getElementById('p1');
const p2 = document.getElementById('p2');
const p3 = document.getElementById('p3');

if(p1) { p1.classList.toggle('active', passo === 1); p1.style.color = (passo === 1) ? '#38bdf8' : '#64748b'; }
if(p2) { p2.classList.toggle('active', passo === 2); p2.style.color = (passo === 2) ? '#38bdf8' : '#64748b'; }
if(p3) { p3.classList.toggle('active', passo === 3); p3.style.color = (passo === 3) ? '#38bdf8' : '#64748b'; }
}

function avancarParaEtapa2() {
if (!document.getElementById('nome_barbearia').value.trim()) { alert('Por favor, introduza o Nome Comercial do Salão/Barbearia.'); return; }
if (!document.getElementById('provincia').value) { alert('Por favor, selecione uma Província.'); return; }
    if (!document.getElementById('endereco').value.trim()) { alert('Por favor, introduza o Endereço de localização.'); return; }
    mudarPasso(2);
}

function ejecutarAuditoriaGestorEtapa2() {
    if (!document.getElementById('nome_gerente_campo').value.trim()) { alert('Por favor, insira o nome do Gerente.'); return; }
    if (document.getElementById('bi_campo').value.length < 14) { alert('O Bilhete de Identidade deve conter exatamente 14 caracteres.'); return; }
    if (!document.getElementById('email_campo').value.trim()) { alert('Por favor, insira o E-mail Mercantil.'); return; }
    if (document.getElementById('telefone_campo').value.length < 9) { alert('O Contacto Telefónico deve conter exatamente 9 dígitos.'); return; }
    
    const senha = document.getElementById('senha_campo').value;
    if (senha.length < 4) { alert('O seu PIN/Senha de acesso deve conter pelo menos 4 caracteres numéricos.'); return; }
    
    mudarPasso(3);
}

document.addEventListener("DOMContentLoaded", function() {
    const form = document.getElementById('formInstanciacaoMaster');
    if (form) {
        form.addEventListener('submit', function() {
            const btn = document.querySelector('.btn-infraestrutura-trigger');
            if (btn) {
                btn.innerHTML = "⌛ Instanciando camadas corporativas...";
                btn.style.opacity = "0.7";
            }
        });
    }
});
</script>
</body>
</html>