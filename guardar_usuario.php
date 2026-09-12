<?php
// 1. Chamar o seu Conector Mestre Local
include_once("conexao.php");

// 2. Receber os dados do formulário (Exemplo com os campos obrigatórios da sua tabela)
$nome     = $_POST['nome'] ?? '';
$email    = $_POST['email'] ?? '';
$telefone = $_POST['telefone'] ?? '';
$data     = date('Y-m-d'); // Data atual obrigatória

// Validar se os campos obrigatórios não estão vazios
if (empty($nome) || empty($email) || empty($telefone)) {
    die("Erro: Os campos Nome, Email e Telefone são obrigatórios!");
}

try {
    // 3. Preparar a inserção usando a variável $pdo que o seu ficheiro criou
    $sql = "INSERT INTO usuario (nome, email, telefone, data) VALUES (:nome, :email, :telefone, :data)";
    $stmt = $pdo->prepare($sql);
    
    // 4. Executar passando os dados em segurança
    $stmt->execute([
        ':nome'     => $nome,
        ':email'    => $email,
        ':telefone' => $telefone,
        ':data'     => $data
    ]);

    echo "Utilizador guardado com sucesso no sistema Aurélius!";

} catch (PDOException $e) {
    // Se a base de dados recusar por algum motivo, este bloco vai mostrar o erro exato
    echo "Erro ao guardar na base de dados: " . $e->getMessage();
}
?>