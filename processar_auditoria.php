<?php
// 🔌 Chamar as credenciais locais do XAMPP
if (session_status() === PHP_SESSION_NONE) { session_start(); }

$host     = "127.0.0.1"; 
$dbname   = "aurelius_salao";
$user     = "root";
$password = ""; 

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $user, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (Exception $e) {
    die("Erro ao conectar à base de dados.");
}

// 🚀 Capturar as variáveis enviadas pelo botão (Ex: acao=aprovar&id=245)
$id   = $_GET['id'] ?? null;
$acao = $_GET['acao'] ?? null;

if ($id && $acao) {
    // Define o novo estado com base no botão clicado
    if ($acao === 'aprovar') {
        $novo_status = 'Confirmado';
    } elseif ($acao === 'rejeitar') {
        $novo_status = 'Suspenso'; // Ou o termo exato que usa na tabela
    } else {
        die("Ação inválida.");
    }

    try {
        // Atualiza a coluna transacao_status da tabela usuario usando o ID (codigo)
        $sql = "UPDATE usuario SET transacao_status = :status WHERE codigo = :id";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            ':status' => $novo_status,
            ':id'     => $id
        ]);

        // Redireciona de volta para o painel de controlo principal automaticamente
        header("Location: Admin.php");
        exit();

    } catch (PDOException $e) {
        echo "Erro ao atualizar o contrato: " . $e->getMessage();
    }
} else {
    echo "Dados em falta para processar a auditoria.";
}
?>