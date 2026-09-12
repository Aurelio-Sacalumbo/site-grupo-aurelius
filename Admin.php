<?php
// =========================================================================
// 🔌 1. CONECTOR MESTRE LOCAL (XAMPP / MYSQL) - GRUPO AURÉLIUS
// =========================================================================
if (session_status() === PHP_SESSION_NONE) { 
    session_start(); 
}

$host     = "127.0.0.1"; 
$dbname   = "aurelius_salao";
$user     = "root";
$password = ""; 

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $user, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
    
    $mysqli = new mysqli($host, $user, $password, $dbname);
    $mysqli->set_charset("utf8mb4");
    
    if ($mysqli->connect_error) {
        throw new Exception("Falha no fallback MySQLi: " . $mysqli->connect_error);
    }
} catch (Exception $e) {
    die("<div style='background:#7f1d1d; color:#fff; padding:20px; font-family:sans-serif; text-align:center; border-radius:8px; margin:50px auto; max-width:500px;'>
            🚨 <b>Erro do Banco de Dados Local:</b> Não foi possível ligar ao MySQL do XAMPP.
         </div>");
}

// =========================================================================
// 🎯 2. MOTOR DE BUSCA / FILTRO INTELIGENTE
// =========================================================================
$termo_pesquisa = "";
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['pesquisar_busca'])) {
    $termo_pesquisa = trim($_POST['busca_termo'] ?? '');
}

// Construção da Query com ou sem Filtro
if (!empty($termo_pesquisa)) {
    $busca_safe = $mysqli->real_escape_string($termo_pesquisa);
    $sql_busca = "SELECT * FROM usuario WHERE 
                  nome LIKE '%$busca_safe%' OR 
                  nome_funcionario LIKE '%$busca_safe%' OR 
                  email LIKE '%$busca_safe%' OR 
                  telefone LIKE '%$busca_safe%' OR 
                  transacao_status LIKE '%$busca_safe%'
                  ORDER BY codigo DESC";
} else {
    $sql_busca = "SELECT * FROM usuario ORDER BY codigo DESC";
}

$query_usuarios = $mysqli->query($sql_busca);

// =========================================================================
// 🛡️ 3. PROCESSAMENTO SEGURO DE REGISTOS (EVITA LINHAS VAZIAS NO F5)
// =========================================================================
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['cadastrar_salao'])) {
    $nome     = trim($_POST['nome'] ?? '');
    $email    = trim($_POST['email'] ?? '');
    $telefone = trim($_POST['telefone'] ?? '');
    $data     = date('Y-m-d'); 

    if (!empty($nome) && !empty($email) && !empty($telefone)) {
        $sql_insert = "INSERT INTO usuario (nome, email, telefone, data) VALUES ('$nome', '$email', '$telefone', '$data')";
        if ($mysqli->query($sql_insert) === TRUE) {
            echo "<script>alert('Salão registado com sucesso!'); window.location.href='Admin.php';</script>";
            exit();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Grupo Aurélius — Painel Executivo</title>
    <style>
        :root {
            --bg-principal: #0f172a;
            --bg-card: rgba(30, 41, 59, 0.7);
            --neon-azul: #0ea5e9;
            --neon-verde: #10b981;
            --neon-vermelho: #f43f5e;
            --neon-laranja: #f59e0b;
            --texto-claro: #f8fafc;
            --texto-mutado: #94a3b8;
        }

        body {
            background-color: var(--bg-principal);
            color: var(--texto-claro);
            font-family: 'Segoe UI', system-ui, sans-serif;
            margin: 0;
            padding: 20px;
            display: flex;
            flex-direction: column;
            align-items: center;
        }

        .container {
            max-width: 1200px;
            width: 100%;
            box-sizing: border-box;
        }

        /* 👑 Cabeçalho Executivo */
        .header-painel {
            background: linear-gradient(135deg, #1e293b 0%, #0f172a 100%);
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-top: 4px solid var(--neon-azul);
            box-shadow: 0 0 15px rgba(14, 165, 233, 0.2);
            border-radius: 12px;
            padding: 25px;
            margin-bottom: 25px;
            text-align: center;
        }

        .header-painel h1 {
            margin: 0 0 10px 0;
            font-size: 2rem;
            letter-spacing: 1px;
            text-transform: uppercase;
            text-shadow: 0 0 10px rgba(14, 165, 233, 0.4);
        }

        .info-gerente {
            display: flex;
            justify-content: center;
            gap: 20px;
            flex-wrap: wrap;
            font-size: 0.95rem;
            color: var(--texto-mutado);
        }

        .info-gerente strong { color: var(--texto-claro); }

        /* 🧭 Menu Superior */
        .nav-admin-saas {
            display: flex;
            gap: 10px;
            margin-bottom: 25px;
            flex-wrap: wrap;
            width: 100%;
        }

        .voltar-btn {
            flex: 1;
            min-width: 140px;
            text-align: center;
            padding: 12px;
            background: #1e293b;
            color: var(--texto-claro);
            text-decoration: none;
            border-radius: 8px;
            font-weight: 600;
            font-size: 0.9rem;
            border: 1px solid rgba(255,255,255,0.05);
            transition: background 0.3s;
        }

        .voltar-btn:hover { background: #334155; }

        /* 🎯 Barra de Pesquisa */
        .barra-busca-container {
            width: 100%;
            margin-bottom: 25px;
        }

        .form-busca {
            display: flex;
            gap: 10px;
            width: 100%;
        }

        .input-busca-premium {
            flex: 1;
            padding: 14px;
            background: #1e293b;
            border: 1px solid rgba(255,255,255,0.1);
            border-radius: 8px;
            color: white;
            font-size: 0.95rem;
        }

        .btn-busca-premium {
            padding: 14px 24px;
            background: var(--neon-azul);
            color: white;
            border: none;
            border-radius: 8px;
            font-weight: bold;
            cursor: pointer;
        }

        .btn-limpar-premium {
            padding: 14px 20px;
            background: #475569;
            color: white;
            text-decoration: none;
            border-radius: 8px;
            font-weight: bold;
            display: flex;
            align-items: center;
        }

        /* 🗂️ Sistema de Abas Verticais Fechadas (Accordion) */
        .aba-item-premium {
            margin-bottom: 15px;
            border-radius: 12px;
            overflow: hidden;
            border: 1px solid rgba(255, 255, 255, 0.08);
            background: var(--bg-card);
            backdrop-filter: blur(10px);
            -webkit-backdrop-filter: blur(10px);
            width: 100%;
            box-sizing: border-box;
        }

        .aba-gatilho {
            padding: 18px 24px;
            background: rgba(15, 23, 42, 0.7);
            display: flex;
            justify-content: space-between;
            align-items: center;
            cursor: pointer;
            user-select: none;
        }

        .aba-gatilho:hover { background: rgba(30, 41, 59, 0.9); }

        .aba-conteudo {
            display: none; /* COMEÇA FECHADA AUTOMATICAMENTE */
            padding: 24px;
            border-top: 1px solid rgba(255, 255, 255, 0.05);
            background: rgba(21, 32, 54, 0.3);
            box-sizing: border-box;
            width: 100%;
        }

        /* 📱 Grid Responsiva Interna */
        .grid-dados-SaaS {
            display: grid;
            grid-template-columns: 1fr 340px;
            gap: 25px;
            width: 100%;
            box-sizing: border-box;
        }

        .lista-info-SaaS {
            display: flex;
            flex-direction: column;
            justify-content: space-between;
        }

        .bloco-auditoria-SaaS {
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            border-left: 1px solid rgba(255, 255, 255, 0.1);
            padding-left: 25px;
            text-align: center;
        }

        /* 🪪 Documentos BI */
        .container-bi {
            margin-top: 20px;
            padding-top: 15px;
            border-top: 1px dashed rgba(255, 255, 255, 0.1);
        }

        .bi-flex {
            display: flex;
            gap: 15px;
            margin-top: 10px;
        }

        .bi-box {
            background: rgba(15, 23, 42, 0.5);
            padding: 12px;
            border-radius: 8px;
            flex: 1;
            border: 1px solid rgba(255, 255, 255, 0.05);
            font-size: 13px;
        }

        /* ⚡ Botões Corporativos */
        .btn-acao {
            display: block;
            padding: 14px;
            color: #fff;
            font-size: 0.85rem;
            font-weight: bold;
            text-transform: uppercase;
            text-decoration: none;
            border-radius: 8px;
            margin-bottom: 8px;
            transition: transform 0.2s;
        }

        .btn-acao:hover { transform: scale(1.02); }
        .btn-appr { background: linear-gradient(135deg, #059669 0%, #10b981 100%); box-shadow: 0 4px 12px rgba(16, 185, 129, 0.2); }
        .btn-rejj { background: linear-gradient(135deg, #b91c1c 0%, #f43f5e 100%); box-shadow: 0 4px 12px rgba(244, 63, 94, 0.2); }
        .btn-alana { background: linear-gradient(135deg, #1d4ed8 0%, #2563eb 100%); box-shadow: 0 4px 12px rgba(37, 99, 235, 0.2); }

        /* 📱 RESPONSIVIDADE GLOBAL (Mobile) */
        @media (max-width: 992px) {
            .grid-dados-SaaS { grid-template-columns: 1fr; gap: 20px; }
            .bloco-auditoria-SaaS { border-left: none; border-top: 1px solid rgba(255, 255, 255, 0.1); padding-left: 0; padding-top: 20px; }
            .form-busca { flex-direction: column; }
            .bi-flex { flex-direction: column; }
        }
    </style>
</head>
<body>

    <header class="header-painel">
        <h1>Grupo Aurélius — Painel de Controle</h1>
        <div class="info-gerente">
            <span><strong>Gerente Responsável:</strong> Aurélio Sacalumbo</span>
            <span><strong>Sede:</strong> Huambo-São Luis Catimba</span>
        </div>
    </header>

    <nav class="nav-admin-saas">
        <a href="historico.php" class="voltar-btn">Histórico</a>
        <a href="detalhes_servico.php" class="voltar-btn">Admin_Serviços</a>
        <a href="admin_adicionar_produto.php" class="voltar-btn">Admin_Produtos</a>
        <a href="Admini.php" class="voltar-btn">Admin_Cliente</a>
        <a href="Admin_Parceiros.php" class="voltar-btn" style="background: #475569;">Admin_Parceiros</a>
    </nav>

    <div class="barra-busca-container">
        <form action="Admin.php" method="POST" class="form-busca">
            <input type="text" name="busca_termo" class="input-busca-premium" placeholder="Pesquise por Empresa, Responsável, E-mail, Telefone..." value="<?php echo htmlspecialchars($termo_pesquisa); ?>">
            <button type="submit" name="pesquisar_busca" class="btn-busca-premium">🔍 Pesquisar Banco</button>
            <?php if (!empty($termo_pesquisa)): ?>
                <a href="Admin.php" class="btn-limpar-premium">✕ Limpar</a>
            <?php endif; ?>
        </form>
    </div>

    <div class="grid-contratos">
        <?php 
        if (!$query_usuarios || $query_usuarios->num_rows == 0) { 
            echo "<p style='text-align:center; color:#94a3b8; padding: 20px;'>Nenhum registo de contrato localizado.</p>";
        } else { 
            while ($row = $query_usuarios->fetch_assoc()) { 
                $id_empresa = (int)($row['codigo'] ?? 0);
                
                // Tratamento estático da data
                $data_banco = trim($row['data'] ?? '');
                $data_celebracao = (empty($data_banco) || $data_banco === '0000-00-00') ? date('d/m/Y') : date('d/m/Y', strtotime($data_banco));

                $nome_banco = !empty($row['nome']) ? htmlspecialchars($row['nome'], ENT_QUOTES, 'UTF-8') : 'Empresa Oculta';
                $preco_banco = number_format((float)($row['preco'] ?? 0), 2, ',', '.') . " Kz";
                $status_atual = !empty($row['transacao_status']) ? htmlspecialchars($row['transacao_status']) : 'Aguardando Validação';
                
                // Cores do Badge de Estado
                $badge_cor = 'color: #f59e0b; border: 1px solid #f59e0b; background: rgba(245, 158, 11, 0.1);';
                if(strtolower($status_atual) == 'confirmado') $badge_cor = 'color: #10b981; border: 1px solid #10b981; background: rgba(16, 185, 129, 0.1);';
                if(strtolower($status_atual) == 'suspenso') $badge_cor = 'color: #f43f5e; border: 1px solid #f43f5e; background: rgba(244, 63, 94, 0.1);';

                // Disparador de Mensagem com Prompt Automatizado para a IA Alana
                $num_limpo = preg_replace('/[^0-9]/', '', $row['telefone'] ?? '');
                if (strlen($num_limpo) == 9) { $num_limpo = "244" . $num_limpo; }
                $prompt_alana = "Olá " . $nome_banco . "! Sou a Alana, assistente IA do Grupo Aurélius. O status atual do seu salão no nosso banco de dados é [" . $status_atual . "]. Estamos prontos para iniciar o suporte operacional do servidor local. Como posso ajudar?";
                $link_whatsapp = "https://wa.me" . $num_limpo . "?text=" . urlencode($prompt_alana);
        ?>
                <div class="aba-item-premium">
                    <div class="aba-gatilho" onclick="alternarAba(<?php echo $id_empresa; ?>)">
                        <div style="display:flex; align-items:center; gap:12px;">
                            <span style="font-weight:bold; color:#fff; font-size:1.05rem;">🏢 <?php echo $nome_banco; ?></span>
                            <span style="font-size:11px; padding:3px 10px; border-radius:12px; font-weight:bold; <?php echo $badge_cor; ?>"><?php echo $status_atual; ?></span>
                        </div>
                        <div style="display:flex; align-items:center; gap:15px; color: var(--texto-mutado); font-size:13px;">
                            <span>📆 <?php echo $data_celebracao; ?></span>
                            <span id="seta-<?php echo $id_empresa; ?>">▼</span>
                        </div>
                    </div>

                    <div id="conteudo-<?php echo $id_empresa; ?>" class="aba-conteudo">
                        <div class="grid-dados-SaaS">
                            
                            <div class="lista-info-SaaS">
                                <div style="font-size:14px; line-height:1.6; color:#cbd5e1;">
                                    <p style="margin:5px 0;"><strong>E-mail:</strong> <?php echo htmlspecialchars($row['email'] ?? 'Não inserido'); ?></p>
                                    <p style="margin:5px 0;"><strong>WhatsApp/Contacto:</strong> <?php echo htmlspecialchars($row['telefone'] ?? 'Não inserido'); ?></p>
                                    <p style="margin:5px 0;"><strong>Localidade/Endereço:</strong> <?php echo htmlspecialchars($row['endereco'] ?? 'Huambo'); ?></p>
                                    <p style="margin:5px 0;"><strong>Funcionário Associado:</strong> <span style="color:#f59e0b; font-weight:bold;"><?php echo htmlspecialchars($row['nome_funcionario'] ?? 'Geral'); ?></span></p>
                                    <p style="margin:5px 0;"><strong>Categoria Contratual:</strong> <span style="color:#22d3ee; font-weight:600;"><?php echo htmlspecialchars($row['tipos_de_servico'] ?? 'Geral'); ?></span></p>
                                </div>

                                <div class="container-bi">
                                    <h4 style="margin:0 0 8px 0; font-size:12px; color:#fff; text-transform:uppercase;">🪪 Arquivos de Identificação</h4>
                                    <div class="bi-flex">
                                        <div class="bi-box">Frente BI: <span style="font-weight:bold; color:<?php echo !empty($row['bi_frente']) ? '#10b981':'#f43f5e';?>"><?php echo !empty($row['bi_frente']) ? '✓ DISPONÍVEL':'⚠️ EM FALTA';?></span></div>
                                        <div class="bi-box">Verso BI: <span style="font-weight:bold; color:<?php echo !empty($row['bi_verso']) ? '#10b981':'#f43f5e';?>"><?php echo !empty($row['bi_verso']) ? '✓ DISPONÍVEL':'⚠️ EM FALTA';?></span></div>
                                    </div>
                                </div>
                            </div>

                            <div class="bloco-auditoria-SaaS">
                                <div>
                                    <span style="font-size:11px; text-transform:uppercase; color:var(--texto-mutado);">Faturação Contratual</span>
                                    <div style="font-size:22px; font-weight:700; margin-top:5px; color:#fff;"><?php echo $preco_banco; ?></div>
                                </div>

                                <div style="margin-top:20px; width:100%;">
                                    <a href="processar_auditoria.php?acao=aprovar&id=<?php echo $id_empresa; ?>" class="btn-acao btn-appr">🚀 APROVAR CONTRATO</a>
                                    <a href="processar_auditoria.php?acao=rejeitar&id=<?php echo $id_empresa; ?>" class="btn-acao btn-rejj">❌ REJEITAR CONTRATO</a>
                                    <a href="<?php echo $link_whatsapp; ?>" target="_blank" class="btn-acao btn-alana">💬 CONTACTAR IA ALANA</a>
                                </div>
                            </div>

                        </div>
                    </div>
                </div>
        <?php 
            } 
        } 
        ?>
    </div>
</div>

<script>
// Função mestre que controla a abertura isolada de cada aba vertical
function alternarAba(id) {
    const conteudo = document.getElementById('conteudo-' + id);
    const seta = document.getElementById('seta-' + id);
    
    if (conteudo.style.display === "none" || conteudo.style.display === "") {
        conteudo.style.display = "block";
        seta.style.transform = "rotate(180deg)";
        seta.style.display = "inline-block";
    } else {
        conteudo.style.display = "none";
        seta.style.transform = "rotate(0deg)";
    }
}
</script>
</body>
</html>