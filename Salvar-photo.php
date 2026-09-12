<?php
// salvar_foto.php
require_once "config/Banco.php";

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_FILES['ficheiro_foto'])) {
    $titulo = $_POST['titulo_foto'] ?? 'Sem título';
    $arquivo = $_FILES['ficheiro_foto'];

    // 1. Validações de segurança básicas do arquivo
    $extensoesPermitidas = ['jpg', 'jpeg', 'png', 'webp'];
    $extensao = strtolower(pathinfo($arquivo['name'], PATHINFO_EXTENSION));

    if (!in_array($extensao, $extensoesPermitidas)) {
        die("<script>alert('Erro: Apenas imagens JPG, JPEG, PNG ou WEBP são aceitas.'); window.location.href='Dashboard.php#photos';</script>");
    }

    // 2. Cria um nome único para o arquivo para evitar que fotos com o mesmo nome se apaguem
    $nomeUnico = uniqid("img_", true) . "." . $extensao;
    $pastaDestino = "uploads/" . $nomeUnico;

    // 3. Move o arquivo temporário do PHP para a pasta física real do seu Apache Windows
    if (move_uploaded_file($arquivo['tmp_name'], $pastaDestino)) {
        try {
            // Mapeando diretamente para a sua tabela 'anuncios' (ajuste as colunas 'title' e 'image_url' se os nomes no banco forem diferentes)
            $sql = "INSERT INTO anuncios (title, image_url) VALUES (:title, :image_url)";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([
                ':title' => $titulo,
                ':image_url' => $pastaDestino
            ]);

            echo "<script>alert('Foto guardada com sucesso no sistema!'); window.location.href='Dashboard.php#photos'; window.location.reload();</script>";
        } catch (PDOException $e) {
            echo "Erro ao registrar o caminho da imagem no MySQL: " . $e->getMessage();
        }
    } else {
        echo "Erro ao mover o ficheiro para a pasta uploads. Verifique as permissões de gravação da pasta no Windows.";
    }
}
?>
