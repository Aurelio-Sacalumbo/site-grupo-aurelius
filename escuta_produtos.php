<?php
include_once("Conexao.php");
session_start();

header('Content-Type: application/json');

// Captura a barbearia logada (Padrão 20 se estiver a testar em localhost)
$id_barbearia = isset($_SESSION['codigo_usuario']) ? intval($_SESSION['codigo_usuario']) : (isset($_SESSION['id_usuario']) ? intval($_SESSION['id_usuario']) : 20);

if ($id_barbearia === 0) {
    echo json_encode(['novo_pedido' => false, 'erro' => 'Sessão inválida.']);
    exit();
}

try {
    // Procura o alerta mais recente não lido específico de pedidos de produtos para esta barbearia
    $stmt = $pdo->prepare("
        SELECT id_alerta, mensagem 
        FROM alertas_barbearia 
        WHERE id_barbearia = :id_barb AND lido = 0 
        ORDER BY id_alerta ASC 
        LIMIT 1
    ");
    $stmt->execute([':id_barb' => $id_barbearia]);
    $alerta = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($alerta) {
        // Marca imediatamente o alerta como lido (1) para o painel não apitar infinitamente
        $stmtUpdate = $pdo->prepare("UPDATE alertas_barbearia SET lido = 1 WHERE id_alerta = :id_alerta");
        $stmtUpdate->execute([':id_alerta' => $alerta['id_alerta']]);

        echo json_encode([
            'novo_pedido' => true,
            'mensagem' => $alerta['mensagem']
        ]);
    } else {
        echo json_encode(['novo_pedido' => false]);
    }
} catch (PDOException $e) {
    echo json_encode(['novo_pedido' => false, 'erro' => $e->getMessage()]);
}
?>