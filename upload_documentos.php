<?php
include_once("conexao.php"); // Conector Mestre do Grupo Aurélius

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $id_usuario = (int)$_POST['id_usuario'];
    $pasta_destino = "uploads/";

    // Cria a pasta uploads se ela não existir no XAMPP
    if (!file_exists($pasta_destino)) {
        mkdir($pasta_destino, 0777, true);
    }

    // Processamento da foto da Frente do BI
    if (!empty($_FILES['bi_frente']['name'])) {
        $nome_frente = time() . "_frente_" . $_FILES['bi_frente']['name'];
        if (move_uploaded_file($_FILES['bi_frente']['tmp_name'], $pasta_destino . $nome_frente)) {
            $mysqli->query("UPDATE usuario SET bi_frente = '$nome_frente' WHERE codigo = $id_usuario");
        }
    }

    // Processamento da foto do Verso do BI
    if (!empty($_FILES['bi_verso']['name'])) {
        $nome_verso = time() . "_verso_" . $_FILES['bi_verso']['name'];
        if (move_uploaded_file($_FILES['bi_verso']['tmp_name'], $pasta_destino . $nome_verso)) {
            $mysqli->query("UPDATE usuario SET bi_verso = '$nome_verso' WHERE codigo = $id_usuario");
        }
    }

    // Redireciona de volta para o painel com as fotos salvas
    header("Location: Admin.php");
    exit();
}
?>