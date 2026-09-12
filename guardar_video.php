<?php
// =========================================================================
// ⚙️ SISTEMA DE PROCESSAMENTO DE VÍDEO AURELIUS (BLINDADO & REATIVO)
// =========================================================================

// 🟢 1. CONTROLO SEGURO DE SESSÃO AUTOMÁTICO (DEVE SER A PRIMEIRA LINHA ANTES DE QUALQUER ECHO)
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Força o aumento de limite diretamente por código
ini_set('upload_max_filesize', '100M');
ini_set('post_max_size', '100M');
ini_set('max_execution_time', '300'); // Dá 5 minutos para carregar vídeos pesados
ini_set('memory_limit', '256M');

// Ativação de diagnóstico profissional
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

include_once("Conexao.php");

// Captura a barbearia ativa na sessão (Padrão ID 20 para a Barbearia Branca)
$id_barbearia = isset($_SESSION['codigo_usuario']) ? intval($_SESSION['codigo_usuario']) : (isset($_SESSION['id_usuario']) ? intval($_SESSION['id_usuario']) : 20);

echo "<div style='background:#111827; color:#fff; font-family:sans-serif; padding:20px; border-radius:8px; max-width:600px; margin:30px auto; border:1px solid #334155;'>";
echo "<h3 style='color:#ca8a04; margin-top:0;'>⚙️ Sistema de Processamento de Vídeo Aurelius</h3>";

// 1. VERIFICAÇÃO SE O ARQUIVO EXCEDEU O LIMITE DO APACHE/XAMPP
if ($_SERVER['REQUEST_METHOD'] === 'POST' && empty($_FILES) && empty($_POST)) {
    echo "<p style='color:#ef4444; font-weight:bold;'>🚨 ERRO DE LIMITE EXCEDIDO:</p>";
    echo "<p style='color:#94a3b8; font-size:13px; margin-top:5px;'>O arquivo de vídeo que tentaste carregar é muito pesado para as definições atuais do teu XAMPP.</p>";
    echo "<p style='color:#eab308; font-size:12px; margin-top:10px;'>💡 COMO RESOLVER:<br>1. Abre o teu <b>XAMPP Control Panel</b><br>2. Clica no botão <b>Config</b> ao lado do Apache e escolhe <b>php.ini</b><br>3. Procura pelas linhas: <b>upload_max_filesize</b> e <b>post_max_size</b><br>4. Altera ambas para <b>100M</b> (Ex: upload_max_filesize=100M)<br>5. Guarda o arquivo e reinicia o Apache no XAMPP.</p>";
    echo "<br><a href='Dashboard.php' style='color:#38bdf8; text-decoration:none; font-weight:bold;'>← Voltar ao Painel</a></div>";
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['ficheiro_video'])) {
    $titulo_video = isset($_POST['titulo_video']) ? htmlspecialchars(trim($_POST['titulo_video'])) : 'Vídeo Comercial';
    
    // Verifica o código de erro nativo do upload do PHP
    $erro_upload = $_FILES['ficheiro_video']['error'];
    
    if ($erro_upload !== UPLOAD_ERR_OK) {
        echo "<p style='color:#ef4444; font-weight:bold;'>❌ Falha no envio do arquivo binário.</p>";
        echo "<p style='color:#94a3b8; font-size:13px;'>Código do erro PHP: " . $erro_upload . "</p>";
        echo "<br><a href='Dashboard.php' style='color:#38bdf8; text-decoration:none;'>← Tentar Novamente</a></div>";
        exit();
    }

    $nome_original = $_FILES['ficheiro_video']['name'];
    $extensao = strtolower(pathinfo($nome_original, PATHINFO_EXTENSION));
    
    // 🔒 Filtro de validação de extensões multimédia
    if (!in_array($extensao, ['mp4', 'mov', 'avi', 'mpeg'])) {
        echo "<p style='color:#ef4444; font-weight:bold;'>❌ Formato de arquivo recusado.</p>";
        echo "<p style='color:#94a3b8; font-size:13px;'>Apenas são aceites vídeos no formato MP4, MOV ou AVI. O teu arquivo é: ." . $extensao . "</p>";
        echo "<br><a href='Dashboard.php' style='color:#38bdf8; text-decoration:none;'>← Tentar Novamente</a></div>";
        exit();
    }

    // Cria o nome único baseado em timestamp
    $nome_final_video = "vid_" . time() . "_" . uniqid() . "." . $extensao;
    $pasta_destino = "uploads/";

    if (!is_dir($pasta_destino)) { 
        mkdir($pasta_destino, 0775, true); 
    }

    // Move o arquivo temporário para a pasta uploads permanente
    if (move_uploaded_file($_FILES['ficheiro_video']['tmp_name'], $pasta_destino . $nome_final_video)) {
        try {
            // Executa a persistência de dados estruturada na tabela anuncios
            $stmt = $pdo->prepare("
                INSERT INTO anuncios (id_barbearia, titulo, imagem, ativo, data_publicacao, pontos_recompensa, tipo_media) 
                VALUES (:id_barb, :titulo, :video, 1, NOW(), 10, 'video')
            ");
            $stmt->execute([
                ':id_barb' => $id_barbearia,
                ':titulo'  => $titulo_video,
                ':video'   => $nome_final_video
            ]);
            
            // 🟢 ATIVAÇÃO DA BOLHA VERMELHA: Reseta a sessão de leitura para forçar o aparecimento do número 1 no Sino
            unset($_SESSION['visto_sino']);
            
            echo "<p style='color:#22c55e; font-weight:bold;'>✓ Vídeo publicado com sucesso!</p>";
            echo "<p style='color:#94a3b8; font-size:13px;'>O registo foi adicionado à tabela de tendências da Barbearia ID " . $id_barbearia . ".</p>";
            echo "<br><a href='Dashboard.php' style='display:inline-block; background:#0284c7; color:white; padding:10px 20px; border-radius:4px; text-decoration:none; font-weight:bold; font-size:12px; text-transform:uppercase;'>Retornar ao Painel</a>";
        } catch (PDOException $e) {
            echo "<p style='color:#ef4444; font-weight:bold;'>❌ Erro ao gravar o registo no banco de dados:</p>";
            echo "<p style='color:#94a3b8; font-size:13px;'>" . $e->getMessage() . "</p>";
            echo "<br><a href='Dashboard.php' style='color:#38bdf8; text-decoration:none;'>← Voltar</a>";
        }
    } else {
        echo "<p style='color:#ef4444; font-weight:bold;'>❌ Falha crítica ao mover o arquivo para a pasta uploads.</p>";
        echo "<p style='color:#94a3b8; font-size:13px;'>Verifica se a pasta 'Bancos/www/uploads' possui permissões de escrita no Windows.</p>";
        echo "<br><a href='Dashboard.php' style='color:#38bdf8; text-decoration:none;'>← Voltar</a>";
    }
} else {
    echo "<p style='color:#ef4444;'>Nenhum formulário binário foi detetado nesta requisição.</p>";
    echo "<br><a href='Dashboard.php' style='color:#38bdf8; text-decoration:none;'>← Voltar ao Início</a>";
}

echo "</div>";
?>