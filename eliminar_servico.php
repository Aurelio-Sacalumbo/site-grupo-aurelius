<?php
include_once("Conexao.php");
session_start();

$id_anuncio = isset($_GET['id']) ? intval($_GET['id']) : 0;

// Captura credenciais de segurança do utilizador logado no ecossistema
$usuario_atual_id = isset($_SESSION['codigo_usuario']) ? intval($_SESSION['codigo_usuario']) : (isset($_SESSION['id_usuario']) ? intval($_SESSION['id_usuario']) : 0);
$is_admin = (isset($_SESSION['gerente_autenticado']) && $_SESSION['gerente_autenticado'] === true) ? true : false;

if ($id_anuncio === 0 || $usuario_atual_id === 0) {
    header("Location: Principal.php");
    exit();
}

try {
    // 1. Busca o anúncio para verificar a posse legítima da imagem
    $stmtCheck = $pdo->prepare("SELECT id_barbearia FROM anuncios WHERE id_anuncio = :id LIMIT 1");
    $stmtCheck->execute([':id' => $id_anuncio]);
    $anuncio = $stmtCheck->fetch(PDO::FETCH_ASSOC);

    if ($anuncio) {
        // 🔐 VALIDAÇÃO ESTILO FACEBOOK
        if ($is_admin || intval($anuncio['id_barbearia']) === $usuario_atual_id) {
            // Executa a remoção segura do registo
            $stmtDel = $pdo->prepare("DELETE FROM anuncios WHERE id_anuncio = :id");
            $stmtDel->execute([':id' => $id_anuncio]);
        }
    }
} catch (PDOException $e) {}

// Redireciona de volta para a vitrina sem deixar vestígios ou quebrar telas
header("Location: Principal.php");
exit();
?>