<?php
// =========================================================================
// 📹 MOTOR CENTRAL SAAS — APENAS PARA VÍDEOS (GUARDAR_VIDEO.PHP)
// =========================================================================
if (session_status() === PHP_SESSION_NONE) { 
    session_start(); 
}
date_default_timezone_set('Africa/Luanda');

require_once "config/Banco.php";

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_FILES['ficheiro_foto'])) {
    $titulo  = trim($_POST['titulo_foto'] ?? 'Novo Reel Aurélius');
    $arquivo = $_FILES['ficheiro_foto'];
    
    $id_barbearia = intval($_SESSION['loja_contexto'] ?? $_SESSION['empresa_codigo'] ?? 20);

    $extensoesPermitidas = ['mp4', 'mov', 'avi', 'mpeg', 'webm'];
    $extensao = strtolower(pathinfo($arquivo['name'], PATHINFO_EXTENSION));

    if (!in_array($extensao, $extensoesPermitidas)) {
        die("<script>alert('Erro: Apenas formatos de vídeo são aceites.'); window.location.href='Dashboard.php#photos';</script>");
    }

    $nomeUnico = "vid_" . time() . "_" . uniqid() . "." . $extensao;
    
    if (!is_dir("upload")) { mkdir("upload", 0777, true); }
    $pastaDestino = "upload/" . $nomeUnico;

    if (move_uploaded_file($arquivo['tmp_name'], $pastaDestino)) {
        try {
            // 🟢 SINCRONIZADO COM O TEU PHPMYADMIN: Usa data_publicacao e tipo_media = 'video'
            $sql = "INSERT INTO anuncios (id_barbearia, titulo, imagem, ativo, likes_adoro, likes_ncurto, data_publicacao, tipo_media) 
                    VALUES (:id_barbearia, :titulo, :imagem, 1, 0, 0, NOW(), 'video')";
            
            $stmt = $pdo->prepare($sql);
            $stmt->execute([
                ':id_barbearia' => $id_barbearia,
                ':titulo'       => $titulo,
                ':imagem'       => $nomeUnico 
            ]);

            echo "<script>alert('🎉 Vídeo guardado com sucesso!'); window.location.href='Dashboard.php#photos';</script>";
            exit();
        } catch (PDOException $e) {
            echo "Erro ao registrar no MySQL: " . $e->getMessage();
        }
    }
}
?>