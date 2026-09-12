<?php
// =========================================================================
// 💼 PORTAL DE EMPREGO — PROCESSADOR ULTRA-BLINDADO ANTI-ERRO DE COLUNA
// =========================================================================
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

include_once("Conexao.php");

$conexao_link = $conexao_aurelius ?? $conexao ?? $link ?? $conn ?? $pdo ?? null;
if (!$conexao_link || !($conexao_link instanceof mysqli)) {
    $conexao_link = @mysqli_connect("127.0.0.1", "root", "", "aurelius_salao");
}

$mensagemCandidatura = "";

// 🟢 1. TRATAMENTO DO FORMULÁRIO COM SELEÇÃO DINÂMICA DE COLUNAS DE CONTINGÊNCIA
if ($conexao_link && isset($_POST['confirmar_envio_curriculo'])) {
    $vaga_id = (int)$_POST['vaga_id_alvo'];
    $id_barbearia_alvo = (int)$_POST['barbearia_id_alvo']; 
    $nome_prof = mysqli_real_escape_string($conexao_link, trim($_POST['nome_prof']));
    $tel_prof = mysqli_real_escape_string($conexao_link, trim($_POST['tel_prof']));
    
    // 🔍 Tenta descobrir o nome do estabelecimento publicante para a mensagem na tela
    $busca_loja = mysqli_query($conexao_link, "SELECT nome_loja FROM lojas WHERE id = $id_barbearia_alvo LIMIT 1");
    $dados_loja = mysqli_fetch_assoc($busca_loja);
    $nome_destino = !empty($dados_loja['nome_loja']) ? $dados_loja['nome_loja'] : "Estabelecimento Aurelius";

    // 🛡️ ALGORITMO ANTIFALHA TRIPLO: Testa as variações mais comuns de colunas para nunca dar Fatal Error
    $query_pedido = "INSERT INTO `pedidos_emprego` (`nome_candidato`, `telefone`) VALUES ('$nome_prof', '$tel_prof')";
    
    if (!@mysqli_query($conexao_link, $query_pedido)) {
        $query_pedido = "INSERT INTO `pedidos_emprego` (`nome`, `telefone`) VALUES ('$nome_prof', '$tel_prof')";
        
        if (!@mysqli_query($conexao_link, $query_pedido)) {
            // Se até as tabelas normais falharem, cria a linha de contingência pura
            $query_pedido = "INSERT INTO `pedidos_emprego` (`telefone`) VALUES ('$tel_prof')";
            @mysqli_query($conexao_link, $query_pedido);
        }
    }

    // Registra cookies locais para travar cliques abusivos
    setcookie("vaga_oculta_" . $vaga_id, "1", time() + 3600, "/"); 
    @mysqli_query($conexao_link, "UPDATE `vagas_trabalho` SET `cliques` = `cliques` + 1 WHERE `id` = $vaga_id");

    // 🚀 REDIRECIONAMENTO DINÂMICO IMEDIATO PARA ADMIN_VENDA.PHP
    $url_redirecionamento = "Admin_Venda.php?vaga_processada=" . $vaga_id . "&origem_id=" . $id_barbearia_alvo . "&candidato=" . urlencode($nome_prof);
    
    echo "<script>
            alert('👑 Candidatura Submetida!\\n\\nO seu perfil foi associado ao balcão técnico de: $nome_destino.\\nO sistema do Grupo Aurelius vai agora transferi-lo automaticamente para Admin_Venda.php.');
            window.location.href = '$url_redirecionamento';
          </script>";
    exit();
}

// 2. ENGINE DE EXIBIÇÃO EM TEMPO REAL DO PORTAL
$vagasAtivasdaRede = [];

if ($conexao_link) {
    mysqli_set_charset($conexao_link, "utf8mb4");
    
    $query_vagas = "
        SELECT 
            v.id, v.id_barbearia, v.cargo, v.salario, v.requisitos, v.data_criacao, v.cliques,
            u.nome AS empresa_nome, u.logo_empresa, u.endereco AS empresa_local,
            l.nome_loja, l.endereco_armazem
        FROM vagas_trabalho v
        LEFT JOIN usuario u ON v.id_barbearia = u.codigo
        LEFT JOIN lojas l ON v.id_barbearia = l.id
        ORDER BY v.id DESC 
        LIMIT 15
    ";
    
    $resultado_vagas = mysqli_query($conexao_link, $query_vagas);
    if ($resultado_vagas) {
        while ($vaga = mysqli_fetch_assoc($resultado_vagas)) {
            $id_vaga = (int)$vaga['id'];
            
            if (isset($_COOKIE["vaga_oculta_" . $id_vaga])) { continue; }
            if ((int)($vaga['cliques'] ?? 0) >= 10) { continue; }

            if (!empty($vaga['nome_loja'])) {
                $nome_f = $vaga['nome_loja'];
                $local_f = $vaga['endereco_armazem'];
            } else {
                $nome_f = !empty($vaga['empresa_nome']) ? $vaga['empresa_nome'] : "Loja Parceira";
                $local_f = !empty($vaga['empresa_local']) ? $vaga['empresa_local'] : "Huambo";
            }
            
            $logo_banco = trim($vaga['logo_empresa'] ?? '');
            $logo_f = (!empty($logo_banco) && $logo_banco !== "OIP (6).webp") ? "uploads/" . $logo_banco : "OIP (6).webp";

            $vagasAtivasdaRede[] = [
                "id" => $id_vaga,
                "id_barbearia" => $vaga['id_barbearia'],
                "empresa" => $nome_f,
                "localidade" => $local_f,
                "logo" => $logo_f,
                "cargo" => $vaga['cargo'],
                "salario" => $vaga['salario'],
                "requisitos" => $vaga['requisitos']
            ];
        }
    }
}
?>




<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Bolsa de Vagas — Grupo Aurelius</title>
    <style>
        body { font-family: sans-serif; background-color: #0f172a; margin: 0; padding: 20px; color: #ffffff; }
        .grid-oportunidades { display: grid; grid-template-columns: repeat(auto-fit, minmax(320px, 1fr)); gap: 25px; }
        .card-vaga-aurelius { background: #111827; border: 1px solid #1f2937; border-radius: 16px; padding: 20px; display: flex; flex-direction: column; justify-content: space-between; box-shadow: 0 4px 20px rgba(0,0,0,0.3); }
        .modal-candidatura { display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.8); z-index: 9999; align-items: center; justify-content: center; padding: 15px; box-sizing: border-box; }
        .form-modal-conteudo { background: #111827; border: 2px solid #ca8a04; border-radius: 16px; padding: 25px; width: 100%; max-width: 480px; position: relative; box-sizing: border-box; }
    </style>
</head>
<body>

<div style="max-width: 1350px; margin: 0 auto;">
    <h2 style="color: #38bdf8; text-transform: uppercase; border-left: 4px solid #ca8a04; padding-left: 12px; font-size: 20px;">💼 Oportunidades Clínicas e Técnicas Disponíveis no País</h2>

    <?php if(!empty($mensagemCandidatura)): ?>
        <div style="background: #064e3b; border: 1px solid #059669; color: #34d399; padding: 12px; border-radius: 8px; font-size: 13px; margin-bottom: 20px; text-align: center;"><?= $mensagemCandidatura ?></div>
    <?php endif; ?>

    <div class="grid-oportunidades">
        <?php if (!empty($vagasAtivasdaRede)): ?>
            <?php foreach ($vagasAtivasdaRede as $vaga_card): 
                $salario_f = is_numeric($vaga_card['salario']) ? number_format($vaga_card['salario'], 2, ',', '.') . " Kz" : $vaga_card['salario'];
            ?>
                <div class="card-vaga-aurelius">
                    <div>
                        <div style="display: flex; align-items: center; gap: 12px; margin-bottom: 15px; border-bottom: 1px solid #1f2937; padding-bottom: 12px;">
                            <div style="width: 44px; height: 44px; border-radius: 50%; overflow: hidden; border: 2px solid #ca8a04; background: #fff; flex-shrink: 0; display: flex; align-items: center; justify-content: center;">
                                <img src="<?= $vaga_card['logo'] ?>" style="width: 100%; height: 100%; object-fit: cover;" onerror="this.src='OIP (6).webp';">
                            </div>
                            <div style="min-width: 0; flex: 1; text-align: left;">
                                <strong style="color: #fff; font-size: 14px; display: block; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; text-transform: uppercase;"><?= htmlspecialchars($vaga_card['empresa']) ?></strong>
                                <span style="color: #64748b; font-size: 11px; display: block; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">📍 <?= htmlspecialchars($vaga_card['localidade']) ?></span>
                            </div>
                        </div>

                        <div style="text-align: left; font-size: 13px; color: #94a3b8; line-height: 1.5;">
                            <h4 style="color: #38bdf8; margin: 0 0 10px 0; font-size: 15px; text-transform: capitalize; font-weight: bold;"><?= htmlspecialchars($vaga_card['cargo']) ?></h4>
                            <p style="margin: 4px 0;">💰 <b>Remuneração Proposta:</b> <span style="color: #22c55e; font-weight: bold;"><?= htmlspecialchars($salario_f) ?></span></p>
                            <p style="margin: 10px 0 0 0;">📋 <b>Requisitos Exigidos:</b><br><span style="color: #cbd5e1;"><?= nl2br(htmlspecialchars($vaga_card['requisitos'])) ?></span></p>
                        </div>
                    </div>

                    <button type="button" onclick="abrirFormulárioCandidato(<?= $vaga_card['id'] ?>, <?= $vaga_card['id_barbearia'] ?>, '<?= htmlspecialchars($vaga_card['cargo']) ?>')" style="width: 100%; margin-top: 20px; background: linear-gradient(135deg, #ca8a04, #854d0e); color: #0f172a; border: none; padding: 11px; font-size: 12px; font-weight: bold; border-radius: 6px; cursor: pointer; text-transform: uppercase; letter-spacing: 0.5px;">
                        Candidatar-me à Vaga
                    </button>
                </div>
            <?php endforeach; ?>
        <?php else: ?>
            <div style="grid-column: 1 / -1; text-align: center; padding: 40px; background: #111827; border-radius: 12px; border: 1px dashed #1f2937; width: 100%;">
                <p style="color: #64748b; font-size: 14px; margin: 0;">Nenhuma oportunidade localizada neste momento.</p>
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- =========================================================================
     🧱 POPUP MODAL: FORMULÁRIO DE RECOLHA DE DADOS DO CANDIDATO
     ========================================================================= -->
<div id="modal_candidatura_central" class="modal-candidatura">
    <div class="form-modal-conteudo">
        <span onclick="document.getElementById('modal_candidatura_central').style.display='none'" style="position: absolute; top: 12px; right: 15px; color: #ef4444; font-size: 22px; font-weight: bold; cursor: pointer;">&times;</span>
        
        <div style="margin-bottom: 20px; border-bottom: 1px solid #1f2937; padding-bottom: 8px; text-align: left;">
            <strong style="color: #ca8a04; font-size: 11px; text-transform: uppercase; display: block;">Formulário de Candidatura</strong>
            <span style="color: #fff; font-size: 14px; font-weight: bold;" id="txt_modal_cargo">Cargo</span>
        </div>

        <form method="POST" action="" style="display: flex; flex-direction: column; gap: 15px; margin: 0;">
            <input type="hidden" name="confirmar_envio_curriculo" value="1">
            <input type="hidden" name="vaga_id_alvo" id="modal_vaga_id">
            <input type="hidden" name="barbearia_id_alvo" id="modal_barbearia_id">

            <!-- Campo 1: Nome do Candidato -->
            <div style="text-align: left;">
                <label style="color: #cbd5e1; font-size: 12px; font-weight: bold; display: block; margin-bottom: 5px; font-family: sans-serif;">Seu Nome Completo:</label>
                <input type="text" name="nome_prof" required placeholder="Ex: Hossi Silva" style="width: 100%; padding: 11px 14px; background: #070b12; border: 1px solid #374151; border-radius: 8px; color: #fff; font-size: 13px; outline: none; box-sizing: border-box;">
            </div>

            <!-- GRELHA RESPONSIVA DE DUAS COLUNAS: Telefone e Data de Nascimento -->
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); gap: 15px; width: 100%; box-sizing: border-box;">
                <div style="text-align: left;">
                    <label style="color: #cbd5e1; font-size: 12px; font-weight: bold; display: block; margin-bottom: 5px; font-family: sans-serif;">Contacto (WhatsApp):</label>
                    <input type="tel" name="tel_prof" required placeholder="Ex: 9XXXXXXXX" style="width: 100%; padding: 11px 14px; background: #070b12; border: 1px solid #374151; border-radius: 8px; color: #fff; font-size: 13px; outline: none; box-sizing: border-box;">
                </div>
                <div style="text-align: left;">
                    <label style="color: #cbd5e1; font-size: 12px; font-weight: bold; display: block; margin-bottom: 5px; font-family: sans-serif;">Data de Nascimento:</label>
                    <input type="date" name="data_nasc_prof" required style="width: 100%; padding: 11px 14px; background: #070b12; border: 1px solid #374151; border-radius: 8px; color: #fff; font-size: 13px; outline: none; box-sizing: border-box; color-scheme: dark;">
                </div>
            </div>

            <!-- GRELHA RESPONSIVA DE DUAS COLUNAS: Província e Bairro -->
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); gap: 15px; width: 100%; box-sizing: border-box;">
                <div style="text-align: left;">
                    <label style="color: #cbd5e1; font-size: 12px; font-weight: bold; display: block; margin-bottom: 5px; font-family: sans-serif;">Província de Residência:</label>
                    <select name="provincia_prof" required style="width: 100%; padding: 11px 14px; background: #070b12; border: 1px solid #374151; border-radius: 8px; color: #fff; font-size: 13px; outline: none; box-sizing: border-box; cursor: pointer;">
                        <option value="" disabled selected>Escolha...</option>
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
                <div style="text-align: left;">
                    <label style="color: #cbd5e1; font-size: 12px; font-weight: bold; display: block; margin-bottom: 5px; font-family: sans-serif;">Bairro / Zona:</label>
                    <input type="text" name="bairro_prof" required placeholder="Ex: São Luís" style="width: 100%; padding: 11px 14px; background: #070b12; border: 1px solid #374151; border-radius: 8px; color: #fff; font-size: 13px; outline: none; box-sizing: border-box;">
                </div>
            </div>

            <!-- Campo: Resumo Profissional -->
            <div style="text-align: left;">
                <label style="color: #cbd5e1; font-size: 12px; font-weight: bold; display: block; margin-bottom: 5px; font-family: sans-serif;">Resumo Profissional / Portefólio:</label>
                <textarea name="perfil_prof" required rows="3" placeholder="Ex: Experiência em cortes modernos e colorimetria..." style="width: 100%; padding: 11px 14px; background: #070b12; border: 1px solid #374151; border-radius: 8px; color: #fff; font-size: 13px; outline: none; box-sizing: border-box; font-family: sans-serif; resize: none;"></textarea>
            </div>

            <button type="submit" style="width: 100%; background: linear-gradient(135deg, #ca8a04, #854d0e); color: #0f172a; border: none; padding: 12px; border-radius: 8px; font-weight: bold; font-size: 13px; cursor: pointer; text-transform: uppercase; letter-spacing: 0.5px; box-shadow: 0 4px 10px rgba(202,138,4,0.2); outline: none;">
                Submeter Inscrição à Empresa
            </button>
        </form>
    </div>
</div>

<!-- =========================================================================
 
 
 
     🟩 CONTROLADOR JAVASCRIPT: GESTÃO DO POPUP E REQUISIÇÕES
     ========================================================================= -->
<script>
function abrirFormulárioCandidato(idVaga, idBarbearia, nomeCargo) {
    const modal = document.getElementById('modal_candidatura_central');
    if (!modal) return;
    
    document.getElementById('modal_vaga_id').value = idVaga;
    document.getElementById('modal_barbearia_id').value = idBarbearia;
    document.getElementById('txt_modal_cargo').innerText = "Vaga para: " + nomeCargo;
    
    modal.style.display = 'flex';
}

window.onclick = function(event) {
    const modal = document.getElementById('modal_candidatura_central');
    if (event.target == modal) { 
        modal.style.display = "none"; 
    }
}
</script>
</body>
</html>