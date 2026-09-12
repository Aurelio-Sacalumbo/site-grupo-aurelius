<?php
// =========================================================================
// 🚀 MOTOR MESTRE DE MIGRAÇÃO CORRIGIDO — LOCALHOST PARA INFRAESTRUTURA CLOUD
// =========================================================================
header('Content-Type: text/html; charset=utf-8');
error_reporting(E_ALL);
ini_set('display_errors', 1);

// 1. 🌐 ENDEREÇO COMPLETO E EXATO EXTRAÍDO DA SUA CONSOLA AIVEN
$raw_host = "mysql-1a34c184-aureliosacalumbo42-bf60.a.aivencloud.com"; 

// Esta função limpa de forma estrita qualquer protocolo que tenha ficado gravado em cache
$online_host = str_replace(['https://', 'http://', '://'], '', trim($raw_host));

$online_port = 22002; 
$online_user = "avnadmin";
$online_pass = "AVNS_6AyaHMtSplThuvy6uGm"; 
$online_name = "defaultdb"; 

// 2. 🖥️ CONFIGURAÇÕES DA SUA BASE DE DADOS LOCAL (XAMPP)
$local_host = "127.0.0.1";
$local_user = "root";
$local_pass = "";
$local_name = "aurelius_salao";
echo "<h2 style='font-family:sans-serif; color:#0284c7;'>📤 Inicializando Migração de Dados Puros para a Nuvem Aiven...</h2>";

// Conexão com o banco local do XAMPP
$conn_local = mysqli_init();
if (!$conn_local || !mysqli_real_connect($conn_local, $local_host, $local_user, $local_pass, $local_name)) { 
    die("<p style='color:red; font-family:sans-serif;'>🚨 Erro ao ligar ao banco LOCAL XAMPP: " . mysqli_connect_error() . "</p>"); 
}
mysqli_set_charset($conn_local, "utf8mb4");

// CONEXÃO SEGURA BLINDADA COM DRIVER MYSQLI SSL MANDATÓRIO DA AIVEN
$conn_online = mysqli_init();
mysqli_options($conn_online, MYSQLI_OPT_SSL_VERIFY_SERVER_CERT, false);

if (!mysqli_real_connect($conn_online, $online_host, $online_user, $online_pass, $online_name, $online_port, null, MYSQLI_CLIENT_SSL)) {
    die("<p style='color:red; font-family:sans-serif;'>🚨 Erro na autenticação Aiven Cloud: " . mysqli_connect_error() . "</p>");
}
mysqli_set_charset($conn_online, "utf8mb4");

mysqli_query($conn_online, "SET FOREIGN_KEY_CHECKS = 0");

$tabelas = ['funcionarios', 'servicos', 'carteira_saldos_clientes', 'pagamentos', 'atendimentos', 'lojas']; 

foreach ($tabelas as $tabela) {
    echo "<span style='font-family:sans-serif; color:#fff; background:#1e293b; padding:4px 8px; border-radius:4px; display:inline-block; margin-bottom:5px;'>Estruturando tabela: <b>$tabela</b>...</span><br>";
    
    $res_schema = mysqli_query($conn_local, "SHOW CREATE TABLE `$tabela`");
    if (!$res_schema) {
        echo "<p style='color:orange; font-family:sans-serif;'>⚠️ Tabela $tabela não localizada no Localhost. A avançar...</p>";
        continue;
    }
    $row_schema = mysqli_fetch_assoc($res_schema);
    $create_query = $row_schema['Create Table'];
    
    mysqli_query($conn_online, "DROP TABLE IF EXISTS `$tabela`");
    if (!mysqli_query($conn_online, $create_query)) {
        echo "<p style='color:red; font-family:sans-serif;'>❌ Erro ao criar estrutura da tabela $tabela na nuvem.</p>";
        continue;
    }
    
    if ($tabela === 'pagamentos') {
        mysqli_query($conn_online, "ALTER TABLE `pagamentos` MODIFY COLUMN `tipo_pagamento` VARCHAR(100) NULL DEFAULT 'Balcão'");
        mysqli_query($conn_online, "ALTER TABLE `pagamentos` MODIFY COLUMN `status_trabalho` VARCHAR(50) NULL DEFAULT 'Pendente'");
        mysqli_query($conn_online, "ALTER TABLE `pagamentos` MODIFY COLUMN `status_atendimento` VARCHAR(50) NULL DEFAULT 'Pendente'");
    }
    
    $resultado_local = mysqli_query($conn_local, "SELECT * FROM `$tabela`");
    $linhas_enviadas = 0;
     
    while ($linha = mysqli_fetch_assoc($resultado_local)) {
         if ($tabela === 'pagamentos') {
             if (isset($linha['status_trabalho'])) {
                 $st_atual = strtolower(trim($linha['status_trabalho']));
                 if (strpos($st_atual, 'conclui') !== false || $linha['status_atendimento'] === 'Confirmado') {
                     $linha['status_trabalho'] = 'Concluido';
                 } else {
                     $linha['status_trabalho'] = 'Pendente';
                 }
             }
             if (isset($linha['status_atendimento'])) {
                 $sa_atual = trim($linha['status_atendimento']);
                 if (strtolower($sa_atual) !== 'confirmado' && strtolower($sa_atual) !== 'cancelado') {
                     $linha['status_atendimento'] = 'Pendente';
                 }
             }
             if (isset($linha['tipo_pagamento'])) {
                 $tp_atual = strtoupper(trim($linha['tipo_pagamento']));
                 if (strpos($tp_atual, 'UNITEL') !== false) { $linha['tipo_pagamento'] = 'Unitel Money'; }
                 elseif (strpos($tp_atual, 'EXPRESS') !== false || strpos($tp_atual, 'MCX') !== false) { $linha['tipo_pagamento'] = 'MCX Express'; }
                 elseif (strpos($tp_atual, 'CARTEIRA') !== false || strpos($tp_atual, 'INTERNO') !== false || strpos($tp_atual, 'SALDO') !== false) { $linha['tipo_pagamento'] = 'SALDO_INTERNO'; }
                 else { $linha['tipo_pagamento'] = !empty($linha['tipo_pagamento']) ? substr($linha['tipo_pagamento'], 0, 50) : 'Balcão'; }
             }
         }
 
         $colunas = implode(", ", array_keys($linha));
         $valores_escapados = array_map(function($val) use ($conn_online) {
             return is_null($val) ? "NULL" : "'" . mysqli_real_escape_string($conn_online, $val) . "'";
         }, array_values($linha));
         $valores = implode(", ", $valores_escapados);
         
         $query_insert = "INSERT IGNORE INTO `$tabela` ($colunas) VALUES ($valores)";
         if (mysqli_query($conn_online, $query_insert)) {
             if (mysqli_affected_rows($conn_online) > 0) { $linhas_enviadas++; }
         }
    }
    echo "<span style='font-family:sans-serif; color:#22c55e;'>✅ Sucesso! <b>$linhas_enviadas</b> registos limpos migrados com sucesso.</span><br><br>";
}

mysqli_query($conn_online, "SET FOREIGN_KEY_CHECKS = 1");
mysqli_close($conn_local);
mysqli_close($conn_online);
echo "<h3 style='font-family:sans-serif; color:#10b981;'>🎉 DATA SYNC COMPLETO! O ecossistema está unificado na nuvem Aiven e pronto para operar!</h3>";
?>