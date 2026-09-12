<?php
if (session_status() === PHP_SESSION_NONE) { session_start(); }
date_default_timezone_set('Africa/Luanda');

// 🟢 CORREÇÃO DA PONTE DE CONEXÃO: Compatível com Localhost e Render.com
$h_host = getenv('DB_HOST') ?: "altaria.proxy.rlwy.net";
$h_port = getenv('DB_PORT') ?: "52030";
$h_name = getenv('DB_NAME') ?: "railway";
$h_user = getenv('DB_USER') ?: "root";
$h_pass = getenv('DB_PASSWORD') ?: "tPzDwXGkyczyyYdcyvLmHLSMmfZmnMIZ";

$mysqli = mysqli_init();
if (!@mysqli_real_connect($mysqli, $h_host, $h_user, $h_pass, $h_name, (int)$h_port)) {
    // Fallback silencioso para o ambiente local XAMPP
    $mysqli = @mysqli_connect("127.0.0.1", "root", "", "aurelius_salao");
}

if (!$mysqli || mysqli_connect_errno()) { 
    die("<div style='padding:20px; background:#0f172a; color:#ef4444; font-family:sans-serif; border:1px solid #ef4444; border-radius:12px; margin:20px;'>
            <strong>Erro de Conexão:</strong> Não foi possível sincronizar o portal de vagas com a base de dados central.
         </div>"); 
}
$mysqli->set_charset("utf8mb4");

// 🟢 GRAVAÇÃO CORRIGIDA DE CANDIDATURA (FIM DA FALHA DE REGISTO)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['acao_candidatura'])) {
    $id_barbearia = intval($_POST['id_barbearia_destino']);
    $nome         = $mysqli->real_escape_string($_POST['nome_barbeiro']);
    $telefone     = $mysqli->real_escape_string($_POST['telefone_barbeiro']);
    $experiencia  = $mysqli->real_escape_string($_POST['experiencia_texto']);

    $sqlInsert = "INSERT INTO pedidos_emprego (id_barbearia, nome_candidato, telefone, experiencia, data_envio) 
                  VALUES ($id_barbearia, '$nome', '$telefone', '$experiencia', NOW())";
    
    if ($mysqli->query($sqlInsert)) {
        echo "<script>alert('📋 Candidatura enviada com sucesso! O gerente foi notificado.'); window.location.href='vagas_emprego.php';</script>";
        exit;
    } else {
        die("Erro ao registar no banco: " . $mysqli->error);
    }
}

// 🟢 FILTRO CRÍTICO: Exibe apenas vagas cujo intervalo entre a criação e HOJE seja menor ou igual a 15 dias
$sql_vagas_filtradas = "
    SELECT v.*, u.nome AS nome_salao 
    FROM vagas_trabalho v 
    LEFT JOIN usuario u ON v.id_barbearia = u.codigo 
    WHERE v.data_criacao >= NOW() - INTERVAL 15 DAY
    ORDER BY v.id DESC
";

$vagas_ativas = $mysqli->query($sql_vagas_filtradas);
$barbearias_lista = $mysqli->query("SELECT codigo, nome FROM usuario WHERE transacao_status = 'Confirmado' ORDER BY nome ASC");
?>
<!DOCTYPE html>
<html lang="pt-PT">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>Bolsa de Emprego Aurélius - Huambo</title>
    <style>
        body { background-color: #0f172a; color: #f8fafc; font-family: 'Segoe UI', Arial, sans-serif; padding: 15px 10px; }
        .container-vagas { max-width: 900px; margin: 0 auto; width: 100%; box-sizing: border-box; }
        .header-vagas { text-align: center; margin-bottom: 25px; background: linear-gradient(135deg, #1e3a8a, #0f172a); padding: 20px 15px; border-radius: 12px; border: 2px solid #38bdf8; box-shadow: 0 4px 15px rgba(0,0,0,0.3); }
        .tabs-control { display: flex; gap: 8px; margin-bottom: 20px; border-bottom: 1px solid #334155; padding-bottom: 10px; width: 100%; }
        .tab-btn { flex: 1; background: #1e293b; border: 1px solid #475569; color: #94a3b8; padding: 12px 6px; border-radius: 8px; cursor: pointer; font-weight: bold; font-size: 11.5px; text-transform: uppercase; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
        .tab-btn.active { background: #38bdf8; color: #0f172a; border-color: #38bdf8; box-shadow: 0 0 10px rgba(56, 189, 248, 0.2); }
        .painel-conteudo { display: none; }
        .painel-conteudo.active { display: block; }
        .card-vaga { background: #111827; border: 1px solid #1e293b; border-radius: 10px; padding: 16px; margin-bottom: 15px; text-align: left; box-sizing: border-box; width: 100%; }
        .form-control { width: 100%; background: #0f172a; border: 1px solid #334155; border-radius: 8px; padding: 12px; color: white; box-sizing: border-box; margin-top: 5px; margin-bottom: 15px; font-size: 15px; outline: none; }
        .btn-submeter { background: linear-gradient(135deg, #38bdf8, #0284c7); color: #fff !important; font-weight: bold; border: none; padding: 14px; border-radius: 8px; font-weight: bold; cursor: pointer; width: 100%; text-transform: uppercase; font-size: 12px; letter-spacing: 0.5px; box-shadow: 0 4px 10px rgba(56, 189, 248, 0.15); }
    </style>
</head>
<body>
<div class="container-vagas">
    <div class="header-vagas">
        <h2 style="color: #38bdf8; margin: 0 0 6px 0; font-size: 18px; text-transform: uppercase; letter-spacing: 0.5px;">💼 Bolsa de Emprego Aurélius</h2>
        <p style="color: #94a3b8; font-size: 12px; margin: 0; line-height: 1.4;">Liga profissionais de estética aos salões líderes da província do Huambo.</p>
    </div>

    <div class="tabs-control">
        <button id="btn-vagas" class="tab-btn active" onclick="mudarAba('vagas')">📢 Vagas Ativas</button>
        <button id="btn-candidatar" class="tab-btn" onclick="mudarAba('candidatar')">✍️ Candidatar-me</button>
    </div>

    <div id="aba-vagas" class="painel-conteudo active">
        <?php if ($vagas_ativas && $vagas_ativas->num_rows > 0): ?>
            <?php while($vaga = $vagas_ativas->fetch_assoc()): ?>
                <div class="card-vaga">
                    <h3 style="color: #38bdf8; margin: 0 0 4px 0; font-size: 16px; text-transform: uppercase; font-weight: bold;"><?= htmlspecialchars($vaga['cargo']); ?></h3>
                    <h4 style="color: #eab308; margin: 0 0 10px 0; font-size: 13px;">💈 Salão: <?= htmlspecialchars($vaga['nome_salao']); ?></h4>
                    <p style="font-size: 12.5px; margin: 0 0 6px 0; color: #cbd5e1;">💰 <strong>Remuneração:</strong> <?= htmlspecialchars($vaga['salario']); ?></p>
                    <p style="font-size: 12.5px; color: #94a3b8; margin: 0; line-height: 1.4; word-break: break-word;">📝 <strong>Requisitos:</strong> <?= nl2br(htmlspecialchars($vaga['requisitos'])); ?></p>
                    
                    <button class="btn-submeter" style="margin-top: 12px; padding: 10px; font-size: 11px;" onclick="candidatarSeParaVaga(<?= $vaga['id_barbearia']; ?>)">Iniciar Processo de Candidatura</button>
                </div>
            <?php endwhile; ?>
        <?php else: ?>
            <p style="color: #64748b; text-align: center; padding: 30px; font-style: italic; background: #111827; border-radius: 10px; font-size: 12.5px; border: 1px dashed #1e293b;">Nenhuma vaga em aberto no Huambo nas últimas 2 semanas.</p>
        <?php endif; ?>
    </div>

    <div id="aba-candidatar" class="painel-conteudo">
        <div class="card-vaga">
            <h3 style="color: #38bdf8; margin-bottom: 15px; text-transform: uppercase; font-size: 14px; font-weight: bold; border-left: 3px solid #38bdf8; padding-left: 6px;">✍️ Enviar Ficha ao Recrutamento</h3>
            <form action="vagas_emprego.php" method="POST">
                <input type="hidden" name="acao_candidatura" value="1">
                
                <label style="font-size: 12px; color: #cbd5e1; font-weight: bold;">Selecione o Salão de Destino:</label>
                <select name="id_barbearia_destino" id="id_barbearia_destino" class="form-control" required style="color: #fff; background: #070913;">
                    <option value="" style="color:#64748b;">Selecione o salão...</option>
                    <?php if($barbearias_lista): while($b = $barbearias_lista->fetch_assoc()): ?>
                        <option value="<?= $b['codigo']; ?>"><?= htmlspecialchars($b['nome']); ?></option>
                    <?php endwhile; endif; ?>
                </select>
                
                <label style="font-size: 12px; color: #cbd5e1; font-weight: bold;">Seu Nome Completo:</label>
                <input type="text" name="nome_barbeiro" class="form-control" placeholder="Ex: Aurélio Jamba" required autocomplete="off">
                
                <label style="font-size: 12px; color: #cbd5e1; font-weight: bold;">Contacto Telefónico (9xx):</label>
                <input type="number" name="telefone_barbeiro" class="form-control" placeholder="925347372" required>
                
                <label style="font-size: 12px; color: #cbd5e1; font-weight: bold;">Resumo de Competências & Experiência:</label>
                <textarea name="experiencia_texto" class="form-control" rows="4" placeholder="Descreva os tipos de cortes e especialidades que domina..." required></textarea>
                
                <button type="submit" class="btn-submeter">Submeter Ficha Técnica</button>
            </form>
        </div>
    </div>
</div>
<script>
function mudarAba(nomeAba) {
    document.querySelectorAll('.painel-conteudo').forEach(function(p) { p.classList.remove('active'); });
    document.querySelectorAll('.tab-btn').forEach(function(b) { b.classList.remove('active'); });
    const aba = document.getElementById('aba-' + nomeAba);
    const btn = document.getElementById('btn-' + nomeAba);
    if(aba) aba.classList.add('active');
    if(btn) btn.classList.add('active');
}
function candidatarSeParaVaga(id) { 
    mudarAba('candidatar'); 
    const seletor = document.getElementById('id_barbearia_destino');
    if(seletor) seletor.value = id; 
}
</script>
</body>
</html>