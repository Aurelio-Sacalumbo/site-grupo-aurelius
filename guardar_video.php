<?php
if (session_status() === PHP_SESSION_NONE) { session_start(); }
date_default_timezone_set('Africa/Luanda');
require_once "config/Banco.php";

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_FILES['ficheiro_foto'])) {
    $titulo  = trim($_POST['titulo_foto'] ?? 'Novo Reel Aurélius');
    $arquivo = $_FILES['ficheiro_foto'];
    $id_barbearia = intval($_SESSION['loja_contexto'] ?? $_SESSION['empresa_codigo'] ?? 20);

    $extensoesPermitidas = ['mp4', 'mov', 'avi', 'mpeg', 'webm'];
    $extensao = strtolower(pathinfo($arquivo['name'], PATHINFO_EXTENSION));

    if (!in_array($extensao, $extensoesPermitidas)) {
        die("<script>alert('Erro: Formato inválido.'); window.location.href='Dashboard.php#photos';</script>");
    }

    $nomeUnico = "vid_" . time() . "_" . uniqid() . "." . $extensao;
    
    // 🟢 FORÇA A PASTA UPLOAD EM AMBOS OS AMBIENTES
    $diretorio_base = __DIR__ . "/upload";
    if (!is_dir($diretorio_base)) { 
        @mkdir($diretorio_base, 0777, true); 
    }
    @chmod($diretorio_base, 0777); // 🔑 LINHA MESTRE: Obriga o Render a dar permissão de escrita!

    $pastaDestino = $diretorio_base . "/" . $nomeUnico;

    if (move_uploaded_file($arquivo['tmp_name'], $pastaDestino)) {
        @chmod($pastaDestino, 0755);
        try {
            $sql = "INSERT INTO anuncios (id_barbearia, titulo, imagem, ativo, likes_adoro, likes_ncurto, data_publicacao, tipo_media) 
                    VALUES (:id_barbearia, :titulo, :imagem, 1, 0, 0, NOW(), 'video')";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([':id_barbearia' => $id_barbearia, ':titulo' => $titulo, ':imagem' => $nomeUnico]);

            echo "<script>alert('🎉 Vídeo guardado com sucesso!'); window.location.href='Dashboard.php#photos';</script>";
            exit();
        } catch (PDOException $e) { die("Erro SQL: " . $e->getMessage()); }
    } else {
        die("Erro ao mover o vídeo. O Render barrou as permissões.");
    }
}
?>