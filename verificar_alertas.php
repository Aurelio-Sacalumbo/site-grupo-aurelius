<?php
include_once("Conexao.php");
session_start();

// Garante que a resposta seja interpretada como JSON puro pelo JavaScript
header('Content-Type: application/json');

// Procura o código da barbearia que está logada na sessão atual do XAMPP
// Ajuste o nome da variável de sessão se no seu sistema for diferente (ex: $_SESSION['id_usuario'])
$id_barbearia = isset($_SESSION['codigo_usuario']) ? intval($_SESSION['codigo_usuario']) : 0;

// Caso não haja uma sessão de empresa ativa, impede o processamento por segurança
if ($id_barbearia === 0) {
    echo json_encode(['novo_alerta' => false, 'erro' => 'Sessão expirada ou inválida.']);
    exit();
}

try {
    // 1. Procura o alerta mais recente não lido específico para esta barbearia
    $stmt = $pdo->prepare("
        SELECT id_alerta, mensagem 
        FROM alertas_barbearia 
        WHERE id_barbearia = :id_barbearia AND lido = 0 
        ORDER BY id_alerta ASC 
        LIMIT 1
    ");
    $stmt->execute([':id_barbearia' => $id_barbearia]);
    $alerta = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($alerta) {
        // 2. IMPORTANTE: Atualiza o status do alerta para lido (1) imediatamente 
        // para evitar que o sistema continue a apitar infinitamente com a mesma mensagem
        $stmtUpdate = $pdo->prepare("UPDATE alertas_barbearia SET lido = 1 WHERE id_alerta = :id_alerta");
        $stmtUpdate->execute([':id_alerta' => $alerta['id_alerta']]);

        // Retorna a mensagem para o painel em formato JSON
        echo json_encode([
            'novo_alerta' => true,
            'mensagem' => $alerta['mensagem']
        ]);
    } else {
        echo json_encode(['novo_alerta' => false]);
    }

} catch (PDOException $e) {
    echo json_encode(['novo_alerta' => false, 'erro' => $e->getMessage()]);
}
?>