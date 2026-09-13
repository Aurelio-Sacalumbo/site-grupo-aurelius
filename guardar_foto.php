<?php
// =========================================================================
// 📸 MOTOR CENTRAL SAAS — APENAS PARA FOTOS (GUARDAR_FOTO.PHP)
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

    if (!in_array($extensao, $extensoesPermitidas)) {
        die("<script>alert('Erro: Apenas imagens (JPG, JPEG, PNG, WEBP) são aceites.'); window.location.href='Dashboard.php#photos';</script>");
    }

    $nomeUnico = "foto_" . uniqid() . "." . $extensao;
    
    // 🟢 CORREÇÃO MESTRE RENDER: Define o caminho absoluto usando a raiz do servidor Linux
    $diretorio_base = $_SERVER['DOCUMENT_ROOT'] . "/upload";
    $pastaDestino = $diretorio_base . "/" . $nomeUnico;

    // 🟢 CORREÇÃO: Só cria a pasta se ela REALMENTE não existir fisicamente
    if (!is_dir($diretorio_base)) { 
        @mkdir($diretorio_base, 0777, true); 
        @chmod($diretorio_base, 0777); // Força permissão total de escrita no Linux
    }

    // 3. Move o arquivo temporário para a pasta física real
    if (@move_uploaded_file($arquivo['tmp_name'], $pastaDestino)) {
        try {
            // Guarda apenas o nome limpo na base de dados para o feed ler dinamicamente
            $sql = "INSERT INTO anuncios (id_barbearia, titulo, imagem, ativo, likes_adoro, likes_ncurto, data_publicacao, tipo_media) 
                    VALUES (:id_barbearia, :titulo, :imagem, 1, 0, 0, NOW(), 'foto')";
            
            $stmt = $pdo->prepare($sql);
            $stmt->execute([
                ':id_barbearia' => $id_barbearia,
                ':titulo'       => $titulo,
                ':imagem'       => $nomeUnico 
            ]);

            echo "<script>alert('🎉 Fotografia guardada com sucesso!'); window.location.href='Dashboard.php#photos';</script>";
            exit();
        } catch (PDOException $e) {
            echo "Erro ao registrar no MySQL: " . $e->getMessage();
        }
    }
}
?>