<?php
// =========================================================================
// 📸 MOTOR ULTRA-BLINDADO NUVEM/LOCAL: CRIPTOGRAFIA TEXTUAL BASE64 (FOTOS)
// =========================================================================
if (session_status() === PHP_SESSION_NONE) { session_start(); }
date_default_timezone_set('Africa/Luanda');
require_once "config/Banco.php";

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_FILES['ficheiro_foto'])) {
    $titulo = trim($_POST['titulo_foto'] ?? 'Nova Inspiração Aurélius');
    $arquivo = $_FILES['ficheiro_foto'];
    $id_barbearia = intval($_SESSION['loja_contexto'] ?? $_SESSION['empresa_codigo'] ?? 20);

    $extensao = strtolower(pathinfo($arquivo['name'], PATHINFO_EXTENSION));

    // 🟢 ENGENHARIA DE NUVEM: Converte a imagem num link de texto Base64 legível por qualquer navegador
    $dados_binarios = file_get_contents($arquivo['tmp_name']);
    $base64_texto = 'data:image/' . $extensao . ';base64,' . base64_encode($dados_binarios);

    try {
        // Grava a string de texto diretamente na coluna imagem do teu phpMyAdmin
        $sql = "INSERT INTO anuncios (id_barbearia, titulo, imagem, ativo, likes_adoro, likes_ncurto, data_publicacao, tipo_media) 
                VALUES (:id_barbearia, :titulo, :imagem, 1, 0, 0, NOW(), 'foto')";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            ':id_barbearia' => $id_barbearia,
            ':titulo'       => $titulo,
            ':imagem'       => $base64_texto // Texto puro guardado com sucesso na nuvem!
        ]);

        echo "<script>alert('🎉 Sucesso! A sua fotografia foi convertida e ativada online com segurança!'); window.location.href='Dashboard.php#photos';</script>";
        exit();
    } catch (PDOException $e) {
        die("Erro ao registrar no MySQL: " . $e->getMessage());
    }
}
?>