<?php
// =========================================================================
// 📹 MOTOR ULTRA-BLINDADO NUVEM/LOCAL: CRIPTOGRAFIA TEXTUAL BASE64 (VÍDEOS)
// =========================================================================
if (session_status() === PHP_SESSION_NONE) { session_start(); }
date_default_timezone_set('Africa/Luanda');
require_once "config/Banco.php";

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_FILES['ficheiro_foto'])) {
    $titulo  = trim($_POST['titulo_foto'] ?? 'Novo Reel Aurélius');
    $arquivo = $_FILES['ficheiro_foto'];
    $id_barbearia = intval($_SESSION['loja_contexto'] ?? $_SESSION['empresa_codigo'] ?? 20);

    $extensao = strtolower(pathinfo($arquivo['name'], PATHINFO_EXTENSION));

    // 🟢 ENGENHARIA DE NUVEM: Converte o vídeo MP4/MOV num link de texto Base64 universal
    $dados_binarios = file_get_contents($arquivo['tmp_name']);
    $base64_texto = 'data:video/' . $extensao . ';base64,' . base64_encode($dados_binarios);

    try {
        $sql = "INSERT INTO anuncios (id_barbearia, titulo, imagem, ativo, likes_adoro, likes_ncurto, data_publicacao, tipo_media) 
                VALUES (:id_barbearia, :titulo, :imagem, 1, 0, 0, NOW(), 'video')";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            ':id_barbearia' => $id_barbearia,
            ':titulo'       => $titulo,
            ':imagem'       => $base64_texto // O vídeo agora viaja como texto puro e o Render não bloqueia!
        ]);

        echo "<script>alert('🎉 Sucesso! O seu vídeo foi integrado e já está ativo no feed de Reels online!'); window.location.href='Dashboard.php#photos';</script>";
        exit();
    } catch (PDOException $e) {
        die("Erro ao registrar no MySQL: " . $e->getMessage());
    }
}
?>