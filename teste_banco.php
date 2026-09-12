<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once __DIR__ . "/config/Banco.php";

echo "<h2>======= DIAGNÓSTICO DA BASE DE DADOS ONLINE =======</h2>";

// 1. Verificar quais credenciais o servidor está a ler
echo "<h3>1. Credenciais Ativas no Servidor:</h3>";
echo "<b>Host detetado:</b> " . (getenv('DB_HOST') ?: "altaria.proxy.rlwy.net (Railway Padrão)") . "<br>";
echo "<b>Base de Dados detetada:</b> " . (getenv('DB_NAME') ?: "railway") . "<br>";

// 2. Verificar a Conexão e contar os registos brutos
if (isset($pdo)) {
    try {
        echo "<p style='color:green;'><b>LIGADO COM SUCESSO VIA PDO!</b></p>";
        
        // Contagem direta de linhas na tabela online
        $stmt = $pdo->query("SELECT COUNT(*) FROM `usuario` WHERE `nivel` = 'parceiro_hospedado' AND `transacao_status` = 'Confirmado'");
        $total_linhas_banco = $stmt->fetchColumn();
        echo "<b>Total de linhas encontradas na tabela 'usuario' ONLINE:</b> <span style='font-size:16px; color:red;'>" . $total_linhas_banco . "</span><br>";
        
        // Listar os IDs para ver se há chaves duplicadas
        echo "<h3>2. Lista de IDs e Nomes no Banco Online:</h3>";
        $stmt_list = $pdo->query("SELECT codigo, nome, endereco FROM `usuario` WHERE `nivel` = 'parceiro_hospedado' AND `transacao_status` = 'Confirmado'");
        while ($row = $stmt_list->fetch(PDO::FETCH_ASSOC)) {
            echo "• ID: " . $row['codigo'] . " | Nome: " . $row['nome'] . " | Local: " . $row['endereco'] . "<br>";
        }

    } catch (PDOException $e) {
        echo "<p style='color:red;'>Erro na consulta: " . $e->getMessage() . "</p>";
    }
} else {
    echo "<p style='color:red;'>A variável \$pdo não está definida no teu ecossistema.</p>";
}
?>