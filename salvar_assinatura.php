<?php
// salvar_assinatura.php
header("Content-Type: application/json; charset=utf-8");
include_once("Conexao.php");

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

date_default_timezone_set('Africa/Luanda');

$input = json_decode(file_get_contents("php://input"), true);
$telefone = isset($input['telefone']) ? trim($input['telefone']) : '';
$plano = isset($input['plano']) ? trim($input['plano']) : 'mensal';
$nome_cliente = isset($input['nome']) && trim($input['nome']) !== '' ? trim($input['nome']) : 'Cliente Individual';

if (strlen($telefone) !== 9) {
    echo json_encode(["status" => "erro", "mensagem" => "Número de telefone Express inválido."]);
    exit;
}

try {
    // 🛡️ REGRA DE SEGURANÇA COMERCIAL: Verifica se o número já tem um plano ativo
    $stmt_check = $pdo->prepare("SELECT id_assinatura FROM assinaturas WHERE telefone_express = ? AND status = 'Ativo' LIMIT 1");
    $stmt_check->execute([$telefone]);
    if ($stmt_check->fetch()) {
        echo json_encode(["status" => "erro", "mensagem" => "Este número de telemóvel já possui uma subscrição PREMIUM ativa de momento!"]);
        exit;
    }

    $valor = 1000; $dias = 30;
    if ($plano === 'semestral') { $valor = 5000; $dias = 180; }
    elseif ($plano === 'anual') { $valor = 9000; $dias = 365; }

    $data_inicio = date('Y-m-d H:i:s');
    $data_fim = date('Y-m-d H:i:s', strtotime("+$dias days"));

    $sql = "INSERT INTO assinaturas (cliente, plano, valor, data_inicio, data_fim, status, telefone_express) VALUES (?, ?, ?, ?, ?, 'Ativo', ?)";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$nome_cliente, $plano, $valor, $data_inicio, $data_fim, $telefone]);

    echo json_encode(["status" => "sucesso", "mensagem" => "Plano Premium ativado com sucesso!"]);
} catch (PDOException $e) {
    echo json_encode(["status" => "erro", "mensagem" => "Erro na base de dados: " . $e->getMessage()]);
}
?>