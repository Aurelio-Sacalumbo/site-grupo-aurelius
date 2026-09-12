<?php
// =========================================================================
// 📂 RETAGUARDA TÉCNICA - HERANÇA FACILITADORA DO ECOSSISTEMA SaaS
// =========================================================================

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Inclui o arquivo Conexao.php que está um nível acima (na raiz)
if (!isset($pdo) || !isset($mysqli)) {
    if (file_exists(__DIR__ . "/../Conexao.php")) {
        include_once(__DIR__ . "/../Conexao.php");
    }
}

// Pontes Globais de Compatibilidade
$conexao_link     = $mysqli;
$conexao_aurelius = $mysqli;
$conexao          = $mysqli;
$link             = $mysqli;
