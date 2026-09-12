<?php
include_once("config/Banco.php");
if (isset($_POST['id_deletar'])) {
    $id = intval($_POST['id_deletar']);
    $stmt = $pdo->prepare("DELETE FROM pagamentos WHERE id_pagamento = ?");
    $stmt->execute([$id]);
    echo "OK";
}
?>