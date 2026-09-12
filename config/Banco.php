<?php
// =========================================================================
// 🔮 ECOSSISTEMA MESTRE - MOTOR CONFIG BANCO (PONTE GLOBAL DE INFRAESTRUTURA)
// =========================================================================

// 1. Inclui a central de conexões garantindo o caminho correto
include_once __DIR__ . '/../Conexao.php';

// 2. Sistema de redundância e mapeamento de variáveis de segurança
if (!isset($mysqli)) {
    if (isset($conexao_aurelius)) { $mysqli = $conexao_aurelius; }
    elseif (isset($conexao_link)) { $mysqli = $conexao_link; }
    elseif (isset($conexao))      { $mysqli = $conexao; }
    elseif (isset($link))         { $mysqli = $link; }
}

// 3. Validação final para travar o ecossistema caso a conexão venha a falhar
if (!isset($mysqli) || !$mysqli) {
    die("Erro do Ecossistema: A conexão no arquivo 'config/Banco.php' não foi encontrada ou é inválida.");
}
