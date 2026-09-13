<?php
// =========================================================================
// 📸 MOTOR DE UPLOAD BLINDADO: APENAS FOTOS — COMPATÍVEL COM RENDER & XAMPP
// =========================================================================
if (session_status() === PHP_SESSION_NONE) { 
    session_start(); 
}
date_default_timezone_set('Africa/Luanda');

// Importa a ligação estável PDO do teu ecossistema
require_once "config/Banco.php";

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_FILES['ficheiro_foto'])) {
    $titulo = trim($_POST['titulo_foto'] ?? 'Nova Inspiração Aurélius');
    $arquivo = $_FILES['ficheiro_foto'];
    
    // Captura com segurança o ID do parceiro logado na sessão
    $id_barbearia = intval($_SESSION['loja_contexto'] ?? $_SESSION['empresa_codigo'] ?? 20);

    // 1. Filtro estrito de imagens aceites
    $extensoesPermitidas = ['jpg', 'jpeg', 'png', 'webp'];
    $extensao = strtolower(pathinfo($arquivo['name'], PATHINFO_EXTENSION));

    if (!in_array($extensao, $extensoesPermitidas)) {
        die("<script>alert('Erro: Apenas imagens (JPG, JPEG, PNG, WEBP) são aceites.'); window.location.href='Dashboard.php#photos';</script>");
    }

    // 2. Cria o nome único do arquivo
    $nomeUnico = "foto_" . time() . "_" . uniqid() . "." . $extensao;
    
    // 🟢 DEFINE O CAMINHO ABSOLUTO SEGURO COMPATÍVEL COM LINUX (RENDER) E WINDOWS (XAMPP)
    $diretorio_base = __DIR__ . "/upload";
    $pastaDestino = $diretorio_base . "/" . $nomeUnico;

    // Cria o diretório e força as permissões máximas de escrita no Linux
    if (!is_dir($diretorio_base)) { 
        @mkdir($diretorio_base, 0777, true); 
    }
    @chmod($diretorio_base, 0777);

    // 3. Move o arquivo temporário para a pasta física real
    if (@move_uploaded_file($arquivo['tmp_name'], $pastaDestino)) {
        @chmod($pastaDestino, 0755);
        try {
            // 🟢 GRAVAÇÃO COMPATÍVEL COM AS COLUNAS REAIS DO TEU PHPMYADMIN
            $sql = "INSERT INTO anuncios (id_barbearia, titulo, imagem, ativo, likes_adoro, likes_ncurto, data_publicacao, tipo_media) 
                    VALUES (:id_barbearia, :titulo, :imagem, 1, 0, 0, NOW(), 'foto')";
            
            $stmt = $pdo->prepare($sql);
            $stmt->execute([
                ':id_barbearia' => $id_barbearia,
                ':titulo'       => $titulo,
                ':imagem'       => $nomeUnico 
            ]);

            // ✨ EVITA A TELA BRANCA: Alerta e redireciona imediatamente
            echo "<script>
                    alert('🎉 Sucesso! A sua fotografia foi publicada e já está ativa na galeria!'); 
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