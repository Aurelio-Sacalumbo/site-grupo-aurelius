<?php
// =========================================================================
// 📸 MOTOR HÍBRIDO SaaS: COMPATÍVEL COM WINDOWS (XAMPP) & LINUX (RENDER)
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

    $nomeUnico = "foto_" . time() . "_" . uniqid() . "." . $extensao;

    // 🟢 DETECTOR DE INFRAESTRUTURA: Identifica se está no Windows (XAMPP) ou Linux (Render)
    if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
        // Se for Windows local, usa a pasta upload/ que criaste
        $diretorio_base = __DIR__ . "/upload";
        if (!is_dir($diretorio_base)) { @mkdir($diretorio_base, 0777, true); }
        $pastaDestino = $diretorio_base . "/" . $nomeUnico;
    } else {
        // Se for Linux online (Render), usa a pasta livre /tmp/
        $pastaDestino = "/tmp/" . $nomeUnico;
    }

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
    } else {
        die("Erro fatal: Não foi possível mover o arquivo. Verifica as permissões de gravação da pasta local.");
    }
}
?>