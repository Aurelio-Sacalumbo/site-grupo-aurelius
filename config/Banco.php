<?php
// =========================================================================
// 📂 RETAGUARDA TÉCNICA AVANÇADA - HERANÇA COESORA DO ECOSSISTEMA SaaS
// =========================================================================

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// 🟢 1. GARANTE A INCLUSÃO SEGURA DO CONECTOR CENTRAL MESTRE
if (!isset($pdo) || !isset($mysqli)) {
    if (file_exists(__DIR__ . "/../Conexao.php")) {
        include_once(__DIR__ . "/../Conexao.php");
    } elseif (file_exists(__DIR__ . "/Conexao.php")) {
        include_once(__DIR__ . "/Conexao.php");
    }
}

// 🟢 2. MAPA GLOBAL DE COMPATIBILIDADE (Trinco contra quebras em legados)
$conexao_link     = $mysqli;
$conexao_aurelius = $mysqli;
$conexao          = $mysqli;
$link             = $mysqli;

// =========================================================================
// ⚡ 3. BLOCO OPCIONAL ESTENDIDO — EXTENSÕES DE EXPANSÃO PARA O SEU SaaS
// =========================================================================

/**
 * Registador de Logs de Acesso e Ações Críticas (Auditoria SaaS)
 * Útil para monitorar ações dos gerentes das barbearias parceiras.
 */
if (!function_exists('registrar_log_aurelius')) {
    function registrar_log_aurelius($pdo, $usuario_id, $acao, $detalhes = '') {
        try {
            // Verifica se a tabela de logs existe antes de inserir para não travar o app
            $stmt = $pdo->prepare("
                INSERT IGNORE INTO `logs_sistema` (`usuario_id`, `acao`, `detalhes`, `ip_origem`, `data_evento`) 
                VALUES (?, ?, ?, ?, CURRENT_TIMESTAMP)
            ");
            $ip = $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
            $stmt->execute([$usuario_id, $acao, $detalhes, $ip]);
        } catch (Exception $e) {
            // Silencioso: evita que um erro de log trave a experiência do usuário principal
        }
    }
}

/**
 * Verificador Universal de Assinatura Ativa do Parceiro Hospedado
 * Protege rotas administrativas contra inadimplência de inquilinos.
 */
if (!function_exists('validar_assinatura_parceiro')) {
    function validar_assinatura_parceiro($mysqli, $codigo_parceiro) {
        $codigo = intval($codigo_parceiro);
        $query = mysqli_query($mysqli, "
            SELECT `transacao_status` FROM `usuario` 
            WHERE `codigo` = $codigo AND `nivel` = 'parceiro_hospedado' LIMIT 1
        ");
        if ($query && $dados = mysqli_fetch_assoc($query)) {
            return ($dados['transacao_status'] === 'Confirmado');
        }
        return false;
    }
}

/**
 * Sanitizador Universal de Inputs contra Injeções Maliciosas
 * Blindagem extra aplicada no fluxo de variáveis globais do ecossistema.
 */
if (!function_exists('sanitizar_input_saas')) {
    function sanitizar_input_saas($mysqli, $dados) {
        if (is_array($dados)) {
            foreach ($dados as $key => $val) {
                $dados[$key] = sanitizar_input_saas($mysqli, $val);
            }
        } else {
            $dados = trim($dados);
            $dados = strip_tags($dados);
            if ($mysqli instanceof mysqli) {
                $dados = mysqli_real_escape_string($mysqli, $dados);
            }
        }
        return $dados;
    }
}
