<?php
require_once __DIR__ . "/config/Banco.php";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nome = !empty($_POST['nome']) ? trim($_POST['nome']) : 'Anónimo';
    $estrelas = (int)$_POST['estrelas'];
    $comentario = trim($_POST['comentario']);
    $foto_url = null; // Padrão é vazio

    // PROCESSA O FICHEIRO DE IMAGEM ENVIADO PELO TELEMÓVEL
    if (isset($_FILES['foto_perfil']) && $_FILES['foto_perfil']['error'] === UPLOAD_ERR_OK) {
        $diretorio_uploads = 'uploads/';
        
        // Extrai a extensão do ficheiro (ex: jpg, png)
        $extensao = strtolower(pathinfo($_FILES['foto_perfil']['name'], PATHINFO_EXTENSION));
        $extensoes_permitidas = ['jpg', 'jpeg', 'png', 'webp'];

        if (in_array($extensao, $extensoes_permitidas)) {
            // Cria um nome aleatório único para evitar que imagens com o mesmo nome se apaguem
            $novo_nome_ficheiro = uniqid('avatar_', true) . '.' . $extensao;
            $caminho_final = $diretorio_uploads . $novo_nome_ficheiro;

            // Move o ficheiro temporário para a pasta uploads definitiva
            if (move_uploaded_file($_FILES['foto_perfil']['tmp_name'], $caminho_final)) {
                $foto_url = $caminho_final; // Caminho que vai para a base de dados
            }
        }
    }

    if (!empty($comentario) && $estrelas > 0) {
        $stmt = $pdo->prepare("INSERT INTO depoimentos (nome, foto_url, estrelas, comentario) VALUES (:n, :f, :e, :c)");
        $stmt->execute([':n' => $nome, ':f' => $foto_url, ':e' => $estrelas, ':c' => $comentario]);
    }
}

// Redireciona de volta para a capa de forma transparente
header("Location: " . $_SERVER['HTTP_REFERER']);
exit;
?>