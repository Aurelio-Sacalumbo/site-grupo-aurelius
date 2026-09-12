<?php
// cadastrar_funcionario.php
include_once("Conexao.php");

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['acao_cadastrar_colaborador'])) {
    $nome = trim($_POST['nome_func']);
    $especialidade = trim($_POST['especialidade_func']);
    $status = trim($_POST['status_func']);

    if (!empty($nome) && !empty($especialidade)) {
        try {
            // Força o encaixe do objeto PDO
            if (!isset($pdo) && isset($conn)) { $pdo = $conn; }

            // Insere na tabela 'funcionarios' com ativo = 1 (ativo)
            $stmt = $pdo->prepare("INSERT INTO funcionarios (nome, status, specialty, ativo) VALUES (?, ?, ?, 1)");
            
            // Caso sua coluna no MySQL chame-se 'especialidade' em vez de 'specialty', o PHP corrige aqui:
            try {
                $stmt->execute([$nome, $status, $especialidade]);
            } catch (PDOException $err) {
                $stmt_alt = $pdo->prepare("INSERT INTO funcionarios (nome, status, especialidade, ativo) VALUES (?, ?, ?, 1)");
                $stmt_alt->execute([$nome, $status, $especialidade]);
            }

            echo "<script>alert('🎉 Colaborador registado com sucesso no Grupo Aurélius!'); window.location.href='Admini.php';</script>";
            exit;
        } catch (PDOException $e) {
            die("Erro ao salvar funcionário: " . $e->getMessage());
        }
    }
}
header("Location: Admini.php");
exit;
?>