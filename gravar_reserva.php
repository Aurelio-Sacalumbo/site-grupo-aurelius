<?php
// C:\xampp\htdocs\Bancos\www\gravar_reserva.php
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Origin: *");
error_reporting(0); 

$host = "127.0.0.1"; 
$db   = "aurelius_salao";
$user = "root";
$pass = "";

try {
    $pdo = new PDO("mysql:host=$host;dbname=$db;charset=utf8mb4", $user, $pass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
    ]);
    
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        // 🟢 CORREÇÃO MESTRE: ID 242 Fixo para a Barbearia SóTranças
        $id_parceiro = 242; 
        
        $stmt = $pdo->prepare("INSERT INTO pagamentos 
            (id_parceiro, cliente, funcionario, data_servico, hora_servico, servico, valor, status_atendimento, tipo_parceiro) 
            VALUES (?, ?, ?, ?, ?, ?, ?, 'Confirmado', 'barbearia')");
        
        $stmt->execute([
            $id_parceiro,
            $_POST['cliente'] ?? 'Cliente Reservado',
            $_POST['funcionario'] ?? 'Técnico',
            $_POST['data_servico'] ?? date('Y-m-d'),
            $_POST['hora_servico'] ?? date('H:i'),
            $_POST['servico'] ?? 'Serviço Técnico',
            $_POST['valor'] ?? 0
        ]);

        echo json_encode(["status" => "success", "message" => "Gravado com sucesso na Agenda da Barbearia!"]);
    }
} catch (Exception $e) {
    echo json_encode(["status" => "error", "message" => $e->getMessage()]);
}
?>