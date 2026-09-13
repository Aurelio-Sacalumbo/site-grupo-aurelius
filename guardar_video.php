<?php
// =========================================================================
// 📹 MOTOR DE UPLOAD BLINDADO: APENAS VÍDEOS — COMPATÍVEL COM RENDER & XAMPP
// =========================================================================
if (session_status() === PHP_SESSION_NONE) { 
    session_start(); 
}
date_default_timezone_set('Africa/Luanda');

// Importa a ligação estável PDO do teu ecossistema
require_once "config/Banco.php";

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_FILES['ficheiro_foto'])) {
    $titulo  = trim($_POST['titulo_foto'] ?? 'Novo Reel Aurélius');
    $arquivo = $_FILES['ficheiro_foto'];
    
    // Captura com segurança o ID do parceiro logado na sessão
    $id_barbearia = intval($_SESSION['loja_contexto'] ?? $_SESSION['empresa_codigo'] ?? 20);

    // 1. Filtro estrito de extensões de vídeo aceites pelos telemóveis
    $extensoesPermitidas = ['mp4', 'mov', 'avi', 'mpeg', 'webm'];
    $extensao = strtolower(pathinfo($arquivo['name'], PATHINFO_EXTENSION));

    if (!in_array($extensao, $extensoesPermitidas)) {
        die("<script>alert('Erro: Apenas formatos de vídeo (MP4, MOV, WEBM) são aceites.'); window.location.href='Dashboard.php#photos';</script>");
    }

    // 2. Cria o nome único do arquivo de vídeo
    $nomeUnico = "vid_" . time() . "_" . uniqid() . "." . $extensao;
    
    // 🟢 DEFINE O CAMINHO ABSOLUTO SEGURO COMPATÍVEL COM LINUX (RENDER) E WINDOWS (XAMPP)
    $diretorio_base = __DIR__ . "/upload";
    $pastaDestino = $diretorio_base . "/" . $nomeUnico;

    // Cria o diretório e força as permissões máximas de escrita no Linux
    if (!is_dir($diretorio_base)) { 
        @mkdir($diretorio_base, 0777, true); 
    }
    @chmod($diretorio_base, 0777);

    // 3. Move o arquivo temporário do PHP para a pasta física real
    if (@move_uploaded_file($arquivo['tmp_name'], $pastaDestino)) {
        @chmod($pastaDestino, 0755);
        try {
            // 🟢 GRAVAÇÃO COMPATÍVEL COM AS COLUNAS REAIS DO TEU PHPMYADMIN
            $sql = "INSERT INTO anuncios (id_barbearia, titulo, imagem, ativo, likes_adoro, likes_ncurto, data_publicacao, tipo_media) 
                    VALUES (:id_barbearia, :titulo, :imagem, 1, 0, 0, NOW(), 'video')";
            
            $stmt = $pdo->prepare($sql);
            $stmt->execute([
                ':id_barbearia' => $id_barbearia,
                ':titulo'       => $titulo,
                ':imagem'       => $nomeUnico 
            ]);

            // ✨ EVITA A TELA BRANCA: Alerta e redireciona imediatamente
            echo "<script>
                    alert('🎉 Sucesso! O seu vídeo foi publicado e já está ativo no feed de Reels!'); 
                    window.location.href='Dashboard.php#photos';
                  </script>";
            exit();
        } catch (PDOException $e) {
            die("Erro ao registrar no MySQL: " . $e->getMessage());
        }
    } else {
        die("Erro fatal: O servidor não possui permissões para gravar o arquivo na pasta upload.");
    }
} else {
    header("Location: Dashboard.php#photos");
    exit();
}
?>