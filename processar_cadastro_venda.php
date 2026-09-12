<?php
// =========================================================================
// 🚀 MOTOR CENTRAL DE PROCESSAMENTO E INTERFACE VISUAL DE REDIRECIONAMENTO
// =========================================================================
if (!isset($_SESSION)) { 
    session_start(); 
}
date_default_timezone_set('Africa/Luanda');

$mysqli = new mysqli("127.0.0.1", "root", "", "aurelius_salao");
if ($mysqli->connect_error) { 
    die("Falha na ligação: " . $mysqli->connect_error); 
}
$mysqli->set_charset("utf8");

$mysqli->query("ALTER TABLE usuario ADD COLUMN IF NOT EXISTS especificacoes_json TEXT DEFAULT NULL");

$id_novo_gerado = 0;

// 🟢 GATILHO COMPATÍVEL CORRIGIDO: Escuta qualquer um dos nomes de botões enviados pelo formulário HTML
if ($_SERVER['REQUEST_METHOD'] === 'POST' && (isset($_POST['finalizar_cadastro_mestre']) || isset($_POST['finalizar_registro_mestre']))) {
    
    // 🧠 AUTOMATIZAÇÃO CRONOLÓGICA DE NOMES SEQUENCIAIS (Parceiro 1, 2, 3...)
    $resultado_contagem = $mysqli->query("SELECT COUNT(*) AS total FROM usuario WHERE nivel = 'parceiro_hospedado'");
    $total_atuais = $resultado_contagem ? $resultado_contagem->fetch_assoc()['total'] : 0;
    $proximo_numero = $total_atuais + 1;
    
    $nome_digitado = isset($_POST['nome_salao']) ? trim($_POST['nome_salao']) : '';
    $nome = !empty($nome_digitado) ? $mysqli->escape_string($nome_digitado) : "Parceiro " . $proximo_numero;

    $provincia        = isset($_POST['provincia']) ? $mysqli->escape_string(trim($_POST['provincia'])) : 'Huambo';
    $endereco         = isset($_POST['endereco']) ? $mysqli->escape_string(trim($_POST['endereco'])) : '';
    $nome_funcionario = isset($_POST['nome_gerente']) ? $mysqli->escape_string(trim($_POST['nome_gerente'])) : '';
    $email            = isset($_POST['email']) ? $mysqli->escape_string(trim($_POST['email'])) : '';
    $telefone         = isset($_POST['telefone']) ? $mysqli->escape_string(trim($_POST['telefone'])) : '';
    $senha_cripto     = isset($_POST['senha_login']) ? md5($_POST['senha_login']) : md5('Huambo2026');

    // Mapeamento das especificações técnicas para salvar no banco
    $requisitos_mapeados = array(
        'quantidade_cadeiras'  => isset($_POST['qtd_cadeiras']) ? intval($_POST['qtd_cadeiras']) : 2,
        'sistema_agendamento'  => isset($_POST['sistema_agenda']) ? htmlspecialchars($_POST['sistema_agenda']) : 'Sim',
        'gateway_pagamento'    => isset($_POST['gateway_money']) ? htmlspecialchars($_POST['gateway_money']) : 'Sim',
        'modulo_vagas_rh'      => isset($_POST['modulo_rh']) ? htmlspecialchars($_POST['modulo_rh']) : 'Sim',
        'modulo_reels_video'   => isset($_POST['modulo_videos']) ? htmlspecialchars($_POST['modulo_videos']) : 'Sim',
        'categorias_marcadas'  => isset($_POST['servicos_pretendidos']) ? $_POST['servicos_pretendidos'] : [],
        'estilos_cortes_barba' => isset($_POST['estilos_cortes']) ? $_POST['estilos_cortes'] : [],
        'comentarios_extras'   => isset($_POST['notas_customizacao']) ? htmlspecialchars(trim($_POST['notas_customizacao'])) : ''
    );
    $especificacoes_json = $mysqli->escape_string(json_encode($requisitos_mapeados, JSON_UNESCAPED_UNICODE));

    // Validação contra e-mails duplicados
    $check_email = $mysqli->query("SELECT codigo FROM usuario WHERE email = '$email' LIMIT 1");
    if ($check_email && $check_email->num_rows > 0) {
        echo "<script>alert('🚨 Erro: Este endereço de e-mail já se encontra registado.'); window.history.back();</script>";
        exit;
    }

    // Upload de arquivos
    $logo_nome = "OIP (6).webp"; 
    $bi_frente_nome = "";
    $diretorio_destino = "uploads/";

    if (isset($_FILES['logo_salao']) && $_FILES['logo_salao']['error'] == 0) {
        $ext_l = strtolower(pathinfo($_FILES['logo_salao']['name'], PATHINFO_EXTENSION));
        if (in_array($ext_l, ['jpg', 'jpeg', 'png', 'webp'])) {
            $logo_nome = "logo_" . uniqid() . "." . $ext_l;
            move_uploaded_file($_FILES['logo_salao']['tmp_name'], $diretorio_destino . $logo_nome);
        }
    }

    $slug_rota = str_replace(' ', '', ucwords($nome));
    $data_atual = date('Y-m-d');

    $sql_code = "INSERT INTO usuario (nome, nome_funcionario, email, telefone, endereco, slug, logo_empresa, senha, transacao_status, visivel_no_site, nivel, especificacoes_json, data) 
                 VALUES ('$nome', '$nome_funcionario', '$email', '$telefone', '$endereco ($provincia)', '$slug_rota', '$logo_nome', '$senha_cripto', 'Confirmado', 1, 'parceiro_hospedado', '$especificacoes_json', '$data_atual')";

    if ($mysqli->query($sql_code)) {
        $id_novo_gerado = $mysqli->insert_id;
        $_SESSION['codigo_usuario'] = $id_novo_gerado;
        $_SESSION['nome_usuario']   = $nome;
    } else {
        die("Erro na gravação: " . $mysqli->error);
    }
} else {
    // 🟢 CORREÇÃO DO REDIRECIONAMENTO EXPULSIVO: 
    // Se o POST falhar ou as chaves não entrarem, ele avisa o Dev em vez de expulsar para a Principal.php
    die("<div style='background:#7f1d1d; color:#fff; padding:20px; font-family:sans-serif; text-align:center; border-radius:8px; margin:50px auto; max-width:500px;'>🚨 Erro de Gatilho: O formulário HTML enviou chaves com nomes diferentes das que o arquivo PHP esperava ler. Verifique o atributo 'name' do botão submit.</div>");
}
?>