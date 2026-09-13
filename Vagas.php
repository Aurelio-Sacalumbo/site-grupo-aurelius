<?php
// =========================================================================
// 💼 PORTAL UNIFICADO DE EMPREGO SaaS — ECOSSISTEMA GRUPO AURÉLIUS
// =========================================================================
if (session_status() === PHP_SESSION_NONE) { 
    session_start(); 
}
date_default_timezone_set('Africa/Luanda');

include_once("Conexao.php");

// Garante a ligação ativa com a Aiven ou XAMPP
$conexao_link = $conexao_aurelius ?? $conexao ?? $link ?? $conn ?? $pdo ?? null;
if (!$conexao_link || !($conexao_link instanceof mysqli)) {
    $conexao_link = @mysqli_connect("127.0.0.1", "root", "", "aurelius_salao");
}

if (!$conexao_link || mysqli_connect_errno()) { 
    die("<div style='padding:20px; background:#0f172a; color:#ef4444; font-family:sans-serif;'>Erro de conexão com a base de dados central.</div>"); 
}
$conexao_link->set_charset("utf8mb4");

// 🟢 1. REQUISIÇÃO: CAPTURA AS VAGAS FILTRADAS POR AUDITORIA (Atendendo à regra de 30 dias)
// Mostra apenas vagas aprovadas (Confirmado) ou criadas nos últimos 30 dias
$sql_vagas = "
    SELECT v.*, u.nome AS nome_salao, u.logo_empresa 
    FROM vagas_trabalho v 
    LEFT JOIN usuario u ON v.id_barbearia = u.codigo 
    WHERE v.status_auditoria = 'Confirmado' 
    AND v.data_criacao >= NOW() - INTERVAL 30 DAY
    ORDER BY v.id DESC
";
$vagas_ativas = mysqli_query($conexao_link, $sql_vagas);

// Pega a lista de salões para o seletor
$barbearias_lista = mysqli_query($conexao_link, "SELECT codigo, nome FROM usuario WHERE transacao_status = 'Confirmado' ORDER BY nome ASC");

// Configuração do Botão Voltar Inteligente
$id_retorno = $_SESSION['loja_contexto'] ?? $_SESSION['empresa_codigo'] ?? 0;
$url_voltar = ($id_retorno > 0) ? "Principal.php?id=" . $id_retorno : "Principal.php";
?>
<!DOCTYPE html>
<html lang="pt-PT">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>Portal de Oportunidades — Grupo Aurélius</title>
    <style>
        body { background-color: #070b12; color: #f8fafc; font-family: 'Segoe UI', system-ui, sans-serif; padding: 15px 12px; margin: 0; }
        .container-saas { max-width: 750px; margin: 0 auto; width: 100%; box-sizing: border-box; }
        
        .btn-voltar-premium { display: inline-flex; align-items: center; gap: 8px; background: rgba(30, 41, 59, 0.6); color: #94a3b8; padding: 10px 20px; border-radius: 30px; text-decoration: none; font-weight: bold; font-size: 11px; border: 1px solid #1e293b; margin-bottom: 20px; text-transform: uppercase; letter-spacing: 0.5px; }
        .header-portal { text-align: left; margin-bottom: 25px; background: linear-gradient(135deg, #0f172a, #070b12); padding: 25px 20px; border-radius: 16px; border: 1px solid #1e293b; box-shadow: 0 10px 25px rgba(0,0,0,0.5); position: relative; }
        .header-portal::after { content: ''; position: absolute; top: 0; left: 0; width: 4px; height: 100%; background: linear-gradient(to bottom, #38bdf8, #0284c7); }
        
        .tabs-control { display: flex; gap: 8px; margin-bottom: 25px; background: #0f172a; padding: 6px; border-radius: 12px; border: 1px solid #1e293b; }
        .tab-btn { flex: 1; background: transparent; border: none; color: #64748b; padding: 12px 6px; border-radius: 8px; cursor: pointer; font-weight: bold; font-size: 11px; text-transform: uppercase; transition: all 0.2s; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
        .tab-btn.active { background: linear-gradient(135deg, #1e3a8a, #0284c7); color: #fff; box-shadow: 0 4px 12px rgba(2, 132, 199, 0.3); }
        
        .painel-conteudo { display: none; }
        .painel-conteudo.active { display: block; }
        
        .card-oportunidade { background: #0f172a; border: 1px solid #1e293b; border-radius: 14px; padding: 20px; margin-bottom: 20px; box-shadow: 0 4px 15px rgba(0,0,0,0.3); box-sizing: border-box; transition: transform 0.2s; }
        .card-oportunidade:hover { border-color: #38bdf8; transform: translateY(-2px); }
        
        .label-premium { font-size: 11px; color: #38bdf8; font-weight: bold; text-transform: uppercase; letter-spacing: 0.5px; display: block; margin-bottom: 5px; text-align: left; }
        .form-control { width: 100%; background: #070b12; border: 1px solid #1e293b; border-radius: 8px; padding: 12px 14px; color: white; box-sizing: border-box; margin-bottom: 18px; font-size: 14px; outline: none; font-family: inherit; }
        .form-control:focus { border-color: #38bdf8; box-shadow: 0 0 8px rgba(56, 189, 248, 0.2); }
        
        .btn-submeter { background: linear-gradient(135deg, #38bdf8, #0284c7); color: #fff !important; font-weight: bold; border: none; padding: 14px; border-radius: 8px; cursor: pointer; width: 100%; text-transform: uppercase; font-size: 11px; letter-spacing: 0.5px; box-shadow: 0 4px 12px rgba(2, 132, 199, 0.3); font-family: inherit; outline: none; }
        .btn-submeter:hover { background: linear-gradient(135deg, #4fcbff, #0294e0); }
        
        .vaga-ocultando { opacity: 0; max-height: 0; padding: 0; margin: 0; overflow: hidden; transition: all 0.3s ease; }
    </style>
</head>
<body>

<div class="container-saas">
    
    <a href="<?php echo htmlspecialchars($url_voltar); ?>" class="btn-voltar-premium">✕ Voltar</a>

    <div class="header-portal">
        <h2 style="color: #fff; margin: 0 0 6px 0; font-size: 19px; font-weight: bold; text-transform: uppercase; letter-spacing: 0.5px;">💼 Bolsa de Oportunidades Coesora</h2>
        <p style="color: #64748b; font-size: 12.5px; margin: 0; line-height: 1.4;">Espaço aberto e dinâmico para publicar e preencher vagas técnicas e operacionais no Huambo.</p>
    </div>

    <!-- Abas de Navegação SaaS -->
    <div class="tabs-control">
        <button id="btn-ver" class="tab-btn active" onclick="mudarAba('ver')">📢 Ver Vagas</button>
        <button id="btn-candidatar" class="tab-btn" onclick="mudarAba('candidatar')">✍️ Candidatar-me</button>
        <button id="btn-publicar" class="tab-btn" onclick="mudarAba('publicar')">➕ Publicar Vaga</button>
    </div>

    <!-- ABA 1: VER VAGAS ATIVAS -->
    <div id="aba-ver" class="painel-conteudo active">
        <?php 
        $count = 0;
        if ($vagas_ativas && mysqli_num_rows($vagas_ativas) > 0): 
            while($vaga = mysqli_fetch_assoc($vagas_ativas)): 
                $count++;
                $logo_f = !empty($vaga['logo_empresa']) ? "upload/" . $vaga['logo_empresa'] : "upload/default.png";
        ?>
                <div class="card-oportunidade" data-vaga-id="<?= $vaga['id']; ?>">
                    <div style="display: flex; align-items: center; gap: 12px; margin-bottom: 15px; border-bottom: 1px solid #1e293b; padding-bottom: 12px;">
                        <div style="width: 40px; height: 44px; border-radius: 50%; overflow: hidden; border: 2px solid #ca8a04; background: #fff; display: flex; align-items: center; justify-content: center;">
                            <img src="<?= htmlspecialchars($logo_f) ?>" style="width: 100%; height: 100%; object-fit: cover;" onerror="this.src='upload/default.png';">
                        </div>
                        <div style="text-align: left;">
                            <strong style="color: #fff; font-size: 14px; display: block; text-transform: uppercase;"><?= htmlspecialchars($vaga['nome_salao'] ?? 'Anunciante Geral'); ?></strong>
                            <span style="color: #64748b; font-size: 11px; display: block;">📍 Local: <?= htmlspecialchars($vaga['endereco'] ?? 'Huambo'); ?></span>
                        </div>
                    </div>
                    <div style="text-align: left; background: #070b12; padding: 14px; border-radius: 8px; border: 1px solid #1e293b; margin-bottom: 12px;">
                        <h3 style="color: #38bdf8; margin: 0 0 6px 0; font-size: 15px; text-transform: uppercase; font-weight: bold;"><?= htmlspecialchars($vaga['cargo']); ?></h3>
                        <p style="font-size: 12.5px; margin: 0 0 4px 0; color: #cbd5e1;">💰 <b>Remuneração:</b> <?= htmlspecialchars($vaga['salario']); ?></p>
                        <p style="font-size: 12.5px; margin: 0; color: #94a3b8; line-height: 1.4; word-break: break-word;">📝 <b>Requisitos:</b> <?= nl2br(htmlspecialchars($vaga['requisitos'])); ?></p>
                    </div>
                    <button class="btn-submeter" style="padding: 10px; font-size: 11px;" onclick="iniciarCandidatura(<?= $vaga['id_barbearia']; ?>, <?= $vaga['id']; ?>)">Preencher Ficha de Candidatura</button>
                </div>
            <?php endwhile; ?>
        <?php endif; ?>
        
        <div id="aviso_vazio" style="display: <?= ($count === 0) ? 'block' : 'none'; ?>; color: #64748b; text-align: center; padding: 40px 20px; font-style: italic; background: #0f172a; border-radius: 12px; font-size: 13px; border: 1px dashed #1e293b;">
            Nenhuma oportunidade validada em aberto nesta rota. Publique uma no botão acima!
        </div>
    </div>

    <!-- ABA 2: FORMULÁRIO DE CANDIDATURA (DESPACHA PARA ADMINI.PHP) -->
    <div id="aba-candidatar" class="painel-conteudo">
        <div class="card-oportunidade" style="box-shadow: none;">
            <h3 style="color: #fff; margin-bottom: 20px; text-transform: uppercase; font-size: 14px; font-weight: bold; border-left: 3px solid #38bdf8; padding-left: 8px;">✍️ Enviar Ficha ao Painel de Auditoria</h3>
            <form action="Admini.php" method="POST">
                <input type="hidden" name="requisicao_tipo" value="nova_candidatura_SaaS">
                <input type="hidden" name="vaga_id_referencia" id="vaga_id_referencia" value="0">
                
                <label class="label-premium">Escolha o Salão/Destino:</label>
                <select name="barbearia_codigo_alvo" id="barbearia_codigo_alvo" class="form-control" required style="color:#fff; background:#070b12;">
                    <option value="">Selecione o estabelecimento...</option>
                    <?php if($barbearias_lista): mysqli_data_seek($barbearias_lista, 0); while($b = mysqli_fetch_assoc($barbearias_lista)): ?>
                        <option value="<?= $b['codigo']; ?>"><?= htmlspecialchars($b['nome']); ?></option>
                    <?php endwhile; endif; ?>
                </select>
                
                <label class="label-premium">Teu Nome Completo:</label>
                <input type="text" name="candidato_nome" class="form-control" placeholder="Ex: Aurélio Jamba" required autocomplete="name">
                
                <label class="label-premium">Telemóvel (WhatsApp):</label>
                <input type="tel" name="candidato_telefone" class="form-control" placeholder="Ex: 915658574" required autocomplete="tel">
                
                <label class="label-premium">Resumo das Suas Competências:</label>
                <textarea name="candidato_experiencia" class="form-control" rows="4" placeholder="Quais os cortes, tranças ou químicas que dominas? Deixa aqui o teu mini-portfólio..." required style="resize: none; background: #070b12; color: #fff;"></textarea>
                
                <button type="submit" name="enviar_para_admini_candidatura" class="btn-submeter">Submeter ao Balcão Administrativo</button>
            </form>
        </div>
    </div>

    <!-- ABA 3: FORMULÁRIO DE PUBLICAÇÃO DE VAGAS -->
    <div id="aba-publicar" class="painel-conteudo">
        <div class="card-oportunidade" style="box-shadow: none;">
            <h3 style="color: #fff; margin-bottom: 20px; text-transform: uppercase; font-size: 14px; font-weight: bold; border-left: 3px solid #ca8a04; padding-left: 8px;">➕ Propor Nova Publicação de Vaga</h3>
            <form action="Admini.php" method="POST">
                <input type="hidden" name="requisicao_tipo" value="nova_vaga_proposta">
                
                <label class="label-premium">O Teu Código ou Código do Salão (Se tiver):</label>
                <input type="number" name="editor_loja_id" class="form-control" placeholder="Ex: 237 (Deixe vazio caso seja Cliente Geral)">
                
                <label class="label-premium">Cargo / Título do Trabalho:</label>
                <input type="text" name="vaga_cargo" class="form-control" placeholder="Ex: Barbeiro Profissional, Manicure Esteticista" required>
                
                <label class="label-premium">Remuneração Proposta (Kz ou Percentagem):</label>
                <input type="text" name="vaga_salario" class="form-control" placeholder="Ex: 50.000,00 Kz + 10% ou À Percentagem" required>
                
                <label class="label-premium">Requisitos Detalhados:</label>
                <textarea name="vaga_requisitos" class="form-control" rows="5" placeholder="Quais as condições, horários e portfólio que exiges do profissional?" required style="resize: none; background: #070b12; color: #fff;"></textarea>
                
                <button type="submit" name="enviar_para_admini_vaga" class="btn-submeter" style="background: linear-gradient(135deg, #ca8a04, #eab308);">Disparar Proposta ao Administrador</button>
            </form>
        </div>
    </div>

</div> <!-- Fecha container-saas -->

<script>
// 🤖 MOTOR REATIVO: GERENCIA O HISTÓRICO DE REFRESH/ATUALIZAÇÃO DE PÁGINA EM LOCALSTORAGE
document.addEventListener("DOMContentLoaded", function() {
    let visualizadas = JSON.parse(localStorage.getItem('vagas_refresh_aurelius') || '[]');
    let visiveis_agora = 0;

    document.querySelectorAll('.card-oportunidade[data-vaga-id]').forEach(card => {
        let id = parseInt(card.getAttribute('data-vaga-id'));
        if (visualizadas.includes(id)) {
            card.classList.add('vaga-ocultando');
            setTimeout(() => { card.style.display = 'none'; }, 300);
        } else {
            visiveis_agora++;
            visualizadas.push(id); // Agenda para ocultar apenas no PRÓXIMO refresh
        }
    });

    localStorage.setItem('vagas_refresh_aurelius', JSON.stringify(visualizadas));
    if (visiveis_agora === 0) {
        document.getElementById('aviso_vazio').style.display = 'block';
    }
});

function mudarAba(aba) {
    document.querySelectorAll('.painel-conteudo').forEach(p => p.classList.remove('active'));
    document.querySelectorAll('.tab-btn').forEach(b => b.classList.remove('active'));
    
    document.getElementById('aba-' + aba).classList.add('active');
    document.getElementById('btn-' + aba).classList.add('active');
}

function iniciarCandidatura(idLoja, idVaga) {
    const select = document.getElementById('barbearia_codigo_alvo');
    if (select) { select.value = idLoja; }
    document.getElementById('vaga_id_referencia').value = idVaga;
    mudarAba('candidatar');
    document.getElementById('barbearia_codigo_alvo').scrollIntoView({ behavior: 'smooth', block: 'center' });
}
</script>
</body>
</html>