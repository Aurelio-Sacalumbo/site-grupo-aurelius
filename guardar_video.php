<?php
// =========================================================================
// 📹 ENGINE SaaS ULTRA-LEVE DE VÍDEOS: ARMAZENAMENTO EM /TMP (GUARDAR_VIDEO.PHP)
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
        die("<script>alert('Erro: Formato de vídeo não suportado.'); window.location.href='Dashboard.php#photos';</script>");
    }

    // 🟢 ENGENHARIA DE NUVEM: Cria o ficheiro físico na pasta /tmp livre do Linux
    $nomeUnico = "vid_" . time() . "_" . uniqid() . "." . $extensao;
    $pastaDestino = "/tmp/" . $nomeUnico;

    // Move o ficheiro para a área de escrita livre do Render sem estourar a memória RAM
    if (move_uploaded_file($arquivo['tmp_name'], $pastaDestino)) {
        try {
            $sql = "INSERT INTO anuncios (id_barbearia, titulo, imagem, ativo, likes_adoro, likes_ncurto, data_publicacao, tipo_media) 
                    VALUES (:id_barbearia, :titulo, :imagem, 1, 0, 0, NOW(), 'video')";
            
            $stmt = $pdo->prepare($sql);
            $stmt->execute([
                ':id_barbearia' => $id_barbearia,
                ':titulo'       => $titulo,
                ':imagem'       => $nomeUnico // Guarda apenas o nome limpo no banco
            ]);

            echo "<script>alert('🎉 Sucesso! O seu vídeo foi processado e já está ativo online!'); window.location.href='Dashboard.php#photos';</script>";
            exit();
        } catch (PDOException $e) {
            die("Erro ao registrar no MySQL: " . $e->getMessage());
        }
    } else {
        die("Erro ao processar upload. Ficheiro muito pesado ou sem espaço em disco.");
    }
}
?>