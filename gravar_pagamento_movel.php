<?php
// gravar_pagamento_movel.php
header("Content-Type: application/json; charset=utf-8");
include_once("Conexao.php");

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

date_default_timezone_set('Africa/Luanda');

$input = json_decode(file_get_contents("php://input"), true);

$nome = isset($input['nome']) ? trim($input['nome']) : 'Consumidor Final';
$telefone = isset($input['telefone']) ? trim($input['telefone']) : '';
$valor_base = isset($input['valor_base']) ? floatval($input['valor_base']) : 0;
$desconto = isset($input['desconto']) ? floatval($input['desconto']) : 0;
$valor_pago = isset($input['valor_pago']) ? floatval($input['valor_pago']) : 0;
$canal = isset($input['canal']) ? trim($input['canal']) : 'Movel';

if (empty($telefone) || $valor_base <= 0) {
    echo json_encode(["status" => "erro", "mensagem" => "Dados inconsistentes para gravação."]);
    exit;
}

try {
    // 🛡️ CORREÇÃO DEFINITIVA: Removido o campo 'status' do INSERT para evitar o erro de coluna desconhecida
    $sql = "INSERT INTO pagamentos (cliente, valor, desconto, valor_liquido, servico, profissional, data_servico, hora_servico) 
            VALUES (?, ?, ?, ?, ?, 'Automático Móvel', ?, ?)";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        $nome,
        $valor_base,
        $desconto,
        $valor_pago,
        "Atendimento via " . $canal . " (Tel: " . $telefone . ") - Pago com Sucesso", // O estado de sucesso fica documentado de forma clara e segura no texto do serviço
        date('Y-m-d'),
        date('H:i:s')
    ]);

    $id_gerado = $pdo->lastInsertId();

    echo json_encode([
        "status" => "sucesso", 
        "id_pagamento" => $id_gerado,
        "mensagem_push" => "👑 AURELIUS: Confirmar pagamento de " . number_format($valor_pago, 0, '', ' ') . " Kz via " . $canal
    ]);
} catch (PDOException $e) {
    echo json_encode(["status" => "erro", "mensagem" => "Erro ao persistir transação: " . $e->getMessage()]);
}
?>