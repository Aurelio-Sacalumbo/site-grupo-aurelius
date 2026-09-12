<?php
// salvar_atendimento.php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Content-Type');

// 1. IMPORTANTE: Aponta para o seu arquivo real de conexão do Salão Aurelius
require_once "config/Banco.php"; 

// Recebe os dados em formato JSON vindos do JavaScript
$dadosJson = file_get_contents("php://input");
$dados = json_decode($dadosJson, true);

if (!$dados) {
    echo json_encode(['sucesso' => false, 'mensagem' => 'Nenhum dado válido recebido pelo PHP.']);
    exit;
}

try {
    // 2. ADAPTAÇÃO DA QUERY PARA A SUA TABELA REAL: 'pagamentos'
    // Como vimos no seu código, a sua tabela de registros chama-se 'pagamentos'
    $sql = "INSERT INTO pagamentos (cliente, profissional, data_servico, horario, servico, valor) 
            VALUES (:cliente, :profissional, :data_servico, :horario, :servico, :valor)";
            
    $stmt = $pdo->prepare($sql);
    
    // Executa blindando os dados com segurança
    $executou = $stmt->execute([
        ':cliente'      => strip_tags(trim($dados['cliente'])),
        ':profissional' => strip_tags(trim($dados['funcionario'])),
        ':data_servico'  => $dados['data'],
        ':horario'      => $dados['hora'],
        ':servico'      => strip_tags(trim($dados['servico'])),
        ':valor'        => (float)$dados['valor']
    ]);

    if ($executou) {
        echo json_encode(['sucesso' => true, 'mensagem' => 'Marcação gravada com sucesso!']);
    } else {
        echo json_encode(['sucesso' => false, 'mensagem' => 'O MySQL recusou a inserção dos dados.']);
    }

} catch (PDOException $e) {
    // Retorna o erro exato do banco de dados caso falte alguma coluna
    echo json_encode(['sucesso' => false, 'mensagem' => 'Erro no Banco: ' . $e->getMessage()]);
}
?>