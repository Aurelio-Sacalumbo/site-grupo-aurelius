<?php
// =========================================================================
// 🚀 MOTOR ISOLADO BACKEND: PROCESSAMENTO ASSÍNCRONO TOTAL (ANTI-DESAPARECIMENTO)
// =========================================================================
if (session_status() === PHP_SESSION_NONE) { 
    session_start(); 
}
include_once("Conexao.php");

$conexao_link = $conexao_aurelius ?? $conexao ?? $link ?? $conn ?? $pdo ?? null;
if (!$conexao_link || !($conexao_link instanceof mysqli)) {
    $conexao_link = @mysqli_connect("127.0.0.1", "root", "", "aurelius_salao");
}

if ($conexao_link) {
    mysqli_set_charset($conexao_link, "utf8mb4");

    // 🟢 1. REAÇÃO ASSÍNCRONA (GOSTAR / PARTILHAR)
    if (isset($_GET['reacao_produto']) && isset($_GET['id_prod_real'])) {
        $id_prod_reacao = intval($_GET['id_prod_real']);
        $tipo_reacao = trim($_GET['reacao_produto']);
        
        if ($id_prod_reacao > 0) {
            if ($tipo_reacao === 'gostar') {
                mysqli_query($conexao_link, "UPDATE `produtos_cosmeticos` SET `likes_adoro` = `likes_adoro` + 1 WHERE `id` = $id_prod_reacao");
            } elseif ($tipo_reacao === 'partilhar') {
                mysqli_query($conexao_link, "UPDATE `produtos_cosmeticos` SET `contagem_partilhas` = `contagem_partilhas` + 1 WHERE `id` = $id_prod_reacao");
            }
        }
        echo json_encode(["status" => "sucesso_reacao"]);
        exit();
    }

    // 🟢 2. COMENTÁRIO ASSÍNCRONO (SUBMISSÃO VIA AJAX POST)
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['enviar_comentario_feed_prod'])) {
        $id_prod_coment = intval($_POST['id_prod_coment']);
        $texto_mensagem = mysqli_real_escape_string($conexao_link, trim($_POST['texto_comentario']));
        $autor_anonimo = "Utilizador Aurelius";

        if ($id_prod_coment > 0 && !empty($texto_mensagem)) {
            mysqli_query($conexao_link, "INSERT INTO `comentarios_reels` (`id_anuncio`, `autor_nome`, `mensagem`) VALUES ($id_prod_coment, '$autor_anonimo', '$texto_mensagem')");
            
            // Retorna o HTML do novo comentário para o JavaScript injetar no ecrã na hora
            echo '<div style="text-align: left; margin-bottom: 4px;">';
            echo '<b style="color: #38bdf8; font-size: 12px; display: inline-block;">' . $autor_anonimo . ':</b> ';
            echo '<span class="item-comentario-linha" style="display: inline-block; margin-left: 4px;">' . htmlspecialchars($texto_mensagem) . '</span>';
            echo '</div>';
            exit();
        }
    }
}
echo json_encode(["status" => "erro"]);
exit();
?>