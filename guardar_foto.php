<?php
// =========================================================================
// 📸 MOTOR CENTRAL SaaS — APENAS PARA FOTOS (GUARDAR_FOTO.PHP)
// =========================================================================
if (session_status() === PHP_SESSION_NONE) { 
    session_start(); 
}
date_default_timezone_set('Africa/Luanda');

require_once "config/Banco.php";

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_FILES['ficheiro_foto'])) {
    $titulo = trim($_POST['titulo_foto'] ?? 'Nova Inspiração Aurélius');
    $arquivo = $_FILES['ficheiro_foto'];
    $id_barbearia = intval($_SESSION['loja_contexto'] ?? $_SESSION['empresa_codigo'] ?? 20);

    $extensoesPermitidas = ['jpg', 'jpeg', 'png', 'webp'];
    $extensao = strtolower(pathinfo($arquivo['name'], PATHINFO_EXTENSION));

    // 🛡️ TRAVA DE SEGURANÇA: Se não for foto, rejeita imediatamente
    if (!in_array($extensao, $extensoesPermitidas)) {
        die("<script>alert('Erro: Apenas imagens (JPG, JPEG, PNG, WEBP) são aceites neste painel.'); window.location.href='Dashboard.php#photos';</script>");
    }

    $nomeUnico = "foto_" . time() . "_" . uniqid() . "." . $extensao;
    $pastaDestino = "/tmp/" . $nomeUnico; // Gravação livre e leve no Linux do Render

    if (move_uploaded_file($arquivo['tmp_name'], $pastaDestino)) {
        try {
            $sql = "INSERT INTO anuncios (id_barbearia, titulo, imagem, ativo, likes_adoro, likes_ncurto, data_publicacao, tipo_media) 
                    VALUES (:id_barbearia, :titulo, :imagem, 1, 0, 0, NOW(), 'foto')";
            
            $stmt = $pdo->prepare($sql);
            $stmt->execute([
                ':id_barbearia' => $id_barbearia,
                ':titulo'       => $titulo,
                ':imagem'       => $nomeUnico
            ]);

            echo "<script>alert('🎉 Foto guardada com sucesso no ecossistema!'); window.location.href='Dashboard.php#photos';</script>";
            exit();
        } catch (PDOException $e) {
            die("Erro ao registrar no MySQL: " . $e->getMessage());
        }
    }
}
?>