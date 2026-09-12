<?php
// verificar_inscricao_mes.php
header("Content-Type: application/json; charset=utf-8");
include_once("Conexao.php");

date_default_timezone_set('Africa/Luanda');

$telefone = isset($_GET['telefone']) ? preg_replace('/\D/', '', $_GET['telefone']) : '';

if (strlen($telefone) === 9) {
    try {
        // Captura o primeiro e o último dia do mês atual de forma automatizada
        $primeiro_dia_mes = date('Y-m-01 00:00:00');
        $ultimo_dia_mes = date('Y-m-t 23:59:59');

        // Varre se existe alguma assinatura Ativa registada para este número dentro do mês corrente
        $stmt = $pdo->prepare("SELECT id_assinatura FROM assinaturas 
                               WHERE telefone_express = ? 
                               AND status = 'Ativo' 
                               AND data_inicio BETWEEN ? AND ? 
                               LIMIT 1");
        $stmt->execute([$telefone, $primeiro_dia_mes, $ultimo_dia_mes]);
        
        if ($stmt->fetch()) {
            // Se encontrou, devolve o estado de bloqueio para o JavaScript mudar a cor para vermelho
            echo json_encode(["status" => "duplicado"]);
            exit;
        }
    } catch (PDOException $e) {
        // Silencia exceções estruturais
    }
}

echo json_encode(["status" => "livre"]);
?>