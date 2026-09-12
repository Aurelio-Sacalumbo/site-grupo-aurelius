<?php
// =========================================================================
// 🔮 INTELIGÊNCIA MESTRE DE MEDIA - SEPARADOR AUTOMÁTICO DE FOTOS E VÍDEOS
// =========================================================================
if (session_status() === PHP_SESSION_NONE) { 
    session_start(); 
}
date_default_timezone_set('Africa/Luanda');

// Importação segura da infraestrutura híbrida centralizada do Banco
require_once __DIR__ . "/config/Banco.php";

// Evita o erro fatal garantindo que o motor PDO está íntegro
if (!isset($pdo) || $pdo === null) {
    die("Erro crítico: O motor de armazenamento PDO não foi instanciado.");
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['ficheiro_foto'])) {
    
    // Captura a identidade da barbearia parceira activa na sessão
    $id_barbearia = $_SESSION['id_usuario'] ?? ($_SESSION['codigo'] ?? ($_SESSION['loja_id'] ?? 237));
    $titulo = isset($_POST['titulo_foto']) ? trim($_POST['titulo_foto']) : 'Trabalho Aurélius';
    
    // =========================================================================
    // ☁️ ROTINA DAS NUVENS: ARQUIVAR AUTOMATICAMENTE MÍDIAS COM MAIS DE 30 DIAS
    // Muda 'ativo' para 0. Sai da página do cliente, mas fica salvo no banco!
    // =========================================================================
    try {
        $stmt_limpeza = $pdo->prepare("UPDATE `anuncios` SET `ativo` = 0 WHERE `data_publicacao` < DATE_SUB(NOW(), INTERVAL 30 DAY)");
        $stmt_limpeza->execute();
    } catch (PDOException $e) {
        error_log("Aviso: Falha na rotina de arquivamento automático: " . $e->getMessage());
    }

    $ficheiro = $_FILES['ficheiro_foto'];
    $nome_original = $ficheiro['name'];
    $extensao = strtolower(pathinfo($nome_original, PATHINFO_EXTENSION));
    $tipo_mime = $ficheiro['type']; // Lê o tipo real do arquivo (Ex: video/mp4 ou image/jpeg)
    
    // Arrays de segurança para validação de formato
    $extensoes_fotos = ['jpg', 'jpeg', 'png', 'webp'];
    $extensoes_videos = ['mp4', 'mov', 'avi', 'mpeg'];
    
    // 🧠 O MOTOR DE DECISÃO UNIFICADO PARA EVITAR TRAVAS NO LINUX DO RENDER
    if (in_array($extensao, $extensoes_videos) || strpos($tipo_mime, 'video/') === 0) {
        // 📁 ROTA DE VÍDEOS: Gravado diretamente na pasta uploads padrão com permissão nativa
        $pasta_destino_final = "uploads/";
        $tipo_media_db = "video";
        $novo_nome_ficheiro = "video_" . time() . "_" . uniqid() . "." . $extensao;
    } else if (in_array($extensao, $extensoes_fotos) || strpos($tipo_mime, 'image/') === 0) {
        // 📁 ROTA DE FOTOS: Gravado diretamente na pasta uploads padrão com permissão nativa
        $pasta_destino_final = "uploads/";
        $tipo_media_db = "foto";
        $novo_nome_ficheiro = "foto_" . time() . "_" . uniqid() . "." . $extensao;
    } else {
        echo "<script>alert('❌ Formato inválido! Carregue apenas imagens (JPG/PNG/WEBP) ou vídeos (MP4/MOV).'); window.history.back();</script>";
        exit;
    }
    
    // 🟢 CORREÇÃO CRÍTICA DE PERMISSÃO LINUX (Garante escrita total no Render/XAMPP)
    if (!is_dir($pasta_destino_final)) {
        mkdir($pasta_destino_final, 0777, true);
        chmod($pasta_destino_final, 0777);
    } else {
        chmod($pasta_destino_final, 0777);
    }
    
    // Define o caminho completo unificado que será guardado na base de dados
    $caminho_completo_disco = $pasta_destino_final . $novo_nome_ficheiro;
    
    // Move o arquivo temporário do telemóvel para a pasta uploads de forma segura
    if (move_uploaded_file($ficheiro['tmp_name'], $caminho_completo_disco)) {
        try {
            // Guarda o registro e carimba na coluna 'tipo_media' se é video ou foto de forma automática
            $stmt = $pdo->prepare("INSERT INTO `anuncios` (id_barbearia, titulo, imagem, tipo_media, ativo, pontos_recompensa, data_publicacao) VALUES (?, ?, ?, ?, 1, 10, NOW())");
            
            // Salvamos o caminho completo purgado (ex: uploads/video_123.mp4) para o seu video.php ler sem errar rotas
            $stmt->execute([$id_barbearia, $titulo, $caminho_completo_disco, $tipo_media_db]);
            
            echo "<script>
                    alert('🎉 Sucesso! O seu ficheiro foi processado e guardado em " . $pasta_destino_final . " e já está ativo no ecossistema!');
                    window.location.href = 'Dashboard.php';
                  </script>";
            exit();
            
        } catch (PDOException $e) {
            error_log("Erro de inserção na tabela anuncios: " . $e->getMessage());
            die("Erro técnico ao registar a mídia na base de dados.");
        }
    } else {
        echo "<script>alert('🚨 Erro: Falha ao mover o ficheiro para o diretório de destino. Verifique as permissões temporárias.'); window.history.back();</script>";
    }
}
?>