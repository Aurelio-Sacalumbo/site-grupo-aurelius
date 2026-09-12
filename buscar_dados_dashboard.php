<?php

include("Conexao.php");

try {
    
    $caixa = "0,00 Kz"; 
    $sessoes = "0 Sessões";

   
    $sqlAtivos = "SELECT COUNT(*) as total FROM funcionarios WHERE status NOT LIKE '%Folga%' AND status NOT LIKE '%Ausente%'";
    $queryAtivos = $pdo->query($sqlAtivos);
    $linhaAtivos = $queryAtivos->fetch(PDO::FETCH_ASSOC);
    $totalAtivos = $linhaAtivos['total'] . " Profissionais";

    $statusFuncionarios = [];
    $sqlLista = "SELECT id_funcionario, status FROM funcionarios";
    $queryLista = $pdo->query($sqlLista);
    
    while($row = $queryLista->fetch(PDO::FETCH_ASSOC())) {
        $statusFuncionarios[$row['id_funcionario']] = $row['status'];
    }

   
    echo json_encode([
        "caixa" => $caixa,
        "atendimentos" => $sessoes,
        "equipa" => $totalAtivos,
        "status_funcionarios" => $statusFuncionarios
    ]);

} catch (PDOException $e) {
    echo json_encode(["erro" => $e->getMessage()]);
}
?>