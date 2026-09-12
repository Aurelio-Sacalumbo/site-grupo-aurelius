<?php
// =========================================================================
// 🖨️ PROCESSADOR MESTRE: UPLOAD E PERSISTÊNCIA DE IMAGENS DE FUNCIONÁRIOS
// =========================================================================
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
include_once("Conexao.php"); // Garante a sua ligação ativa ao banco

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['ficheiro_perfil'])) {
    
    $id_funcionario = intval($_POST['id_funcionario_upload']);
    $ficheiro = $_FILES['ficheiro_perfil'];
    
    // Captura a extensão real da fotografia (jpg, png, webp)
    $nome_original = $ficheiro['name'];
    $extensao = strtolower(pathinfo($nome_original, PATHINFO_EXTENSION));
    
    // Validação estrita de formatos de imagem suportados pelo browser
    $formatos_permitidos = ['jpg', 'jpeg', 'png', 'webp'];
    
    if (!in_array($extensao, $formatos_permitidos)) {
        echo "<script>alert('❌ Erro: Formato inválido! Escolha uma imagem JPG, PNG ou WEBP.'); window.history.back();</script>";
        exit();
    }
    
    // Cria um nome encriptado único para evitar duplicados na pasta uploads
    $novo_nome_foto = "func_" . $id_funcionario . "_" . time() . "." . $extensao;
    
    // Diretório de destino físico no seu XAMPP Win64
    $pasta_destino = "uploads/" . $novo_nome_foto;
    
    // 🟢 Garante de forma inteligente que a pasta uploads existe no disco local
    if (!is_dir("uploads")) {
        mkdir("uploads", 0777, true);
    }
    
    // Move o ficheiro temporário do PHP para a pasta uploads definitiva
    if (move_uploaded_file($ficheiro['tmp_name'], $pasta_destino)) {
        try {
           // 🟢 CORREÇÃO CRÍTICA: Alinhado com a coluna id_funcionario real do seu phpMyAdmin
$stmt_update_foto = $pdo->prepare("UPDATE `funcionarios` SET `foto_url` = ? WHERE `id_funcionario` = ?");
$stmt_update_foto->execute([$novo_nome_foto, $id_funcionario]);


            
            echo "<script>
                    alert('🎉 Sucesso! A nova foto do profissional foi guardada no sistema!');
                    window.location.href = 'Admini.php';
                  </script>";
            exit();
            
        } catch (PDOException $e) {
            die("🚨 Erro Crítico ao atualizar o banco de dados: " . $e->getMessage());
        }
    } else {
        echo "<script>alert('❌ Erro: Falha local do Apache ao mover o ficheiro.'); window.history.back();</script>";
    }
} else {
    // Redirecionamento de segurança caso tentem aceder ao ficheiro diretamente pela URL
    header("Location: Admini.php");
    exit();
}
?>