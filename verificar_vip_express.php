<?php
// verificar_vip_express.php
header("Content-Type: application/json; charset=utf-8");
include_once("Conexao.php");

$telefone = isset($_GET['telefone']) ? preg_replace('/\D/', '', $_GET['telefone']) : '';

if (strlen($telefone) === 9) {
    try {
        // Varre a tabela para saber se o cliente possui uma subscrição com status Ativo
        $stmt = $pdo->prepare("SELECT id_assinatura FROM assinaturas WHERE telefone_express = ? AND status = 'Ativo' LIMIT 1");
        $stmt->execute([$telefone]);
        
        if ($stmt->fetch()) {
            echo json_encode(["status" => "vip"]);
            exit;
        }
    } catch (PDOException $e) {
        // Silencia erros para não quebrar a resposta JSON
    }
}

echo json_encode(["status" => "normal"]);
?>