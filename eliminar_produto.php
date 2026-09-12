<?php
include_once("Conexao.php");
session_start();

$id_produto = isset($_GET['id']) ? intval($_GET['id']) : 0;

$usuario_atual_id = isset($_SESSION['codigo_usuario']) ? intval($_SESSION['codigo_usuario']) : (isset($_SESSION['id_usuario']) ? intval($_SESSION['id_usuario']) : 0);
$is_admin = (isset($_SESSION['gerente_autenticado']) && $_SESSION['gerente_autenticado'] === true) ? true : false;

if ($id_produto === 0 || $usuario_atual_id === 0) {
    header("Location: Principal.php");
    exit();
}

try {
    // Busca o produto para validar quem é o dono real
    $stmt = $pdo->prepare("SELECT id_salao FROM College_ou_tabela_onde_guarda_id WHERE id = :id LIMIT 1");
    // Como a tabela varia, usamos a busca direta na 'produtos_cosmeticos'
    $stmtCheck = $pdo->prepare("SELECT id, id_salao FROM produtos_cosmeticos WHERE id = :id LIMIT 1");
    $stmtCheck->execute([':id' => $id_produto]);
    $prod = $stmtCheck->fetch(PDO::FETCH_ASSOC);

    if ($prod) {
        // Se for admin ou se o ID logado bater com o ID que postou, executa a remoção
        if ($is_admin || intval($prod['id_salao']) === $usuario_atual_id) {
            $stmtDel = $pdo->prepare("DELETE FROM produtos_cosmeticos WHERE id = :id");
            $stmtDel->execute([':id' => $id_produto]);
        }
    }
} catch (PDOException $e) {}

header("Location: Principal.php");
exit();