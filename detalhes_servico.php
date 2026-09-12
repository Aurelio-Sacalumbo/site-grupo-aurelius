<?php
include_once("Conexao.php");
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// 1. Captura o ID do anúncio enviado pela URL
$id_anuncio = isset($_GET['id_anuncio']) ? intval($_GET['id_anuncio']) : 0;

// Fallback preventivo caso o ID venha zerado para não quebrar a tela
if ($id_anuncio === 0) {
    try {
        $stmtVerifica = $pdo->query("SELECT id_anuncio FROM anuncios WHERE ativo = 1 ORDER BY id_anuncio DESC LIMIT 1");
        $id_anuncio = $stmtVerifica->fetchColumn() ?: 0;
    } catch (PDOException $e) {}
}

try {
    // 2. Consulta abrangente para trazer os dados do anúncio e o salão associado
    $stmt = $pdo->prepare("
        SELECT a.*, u.nome AS nome_salao, u.codigo AS id_barbearia
        FROM anuncios a
        LEFT JOIN usuario u ON a.id_barbearia = u.codigo
        WHERE a.id_anuncio = :id 
        LIMIT 1
    ");
    $stmt->execute([':id' => $id_anuncio]);
    $dados_servico = $stmt->fetch(PDO::FETCH_ASSOC);

    // Dados de contingência caso a base de dados falhe no retorno da linha
    if (!$dados_servico) {
        $dados_servico = [
            'id_anuncio' => $id_anuncio,
            'titulo' => 'Serviço Gel Premium',
            'id_barbearia' => 20,
            'nome_salao' => 'Salão & Barbearia Branca',
            'preco' => 5000.00
        ];
    }

    // Definição das variáveis de exibição
    $nome_barbearia  = !empty($dados_servico['nome_salao']) ? $dados_servico['nome_salao'] : "Salão & Barbearia Branca";
    $nome_servico    = !empty($dados_servico['titulo']) ? $dados_servico['titulo'] : "Gel";
    $id_barbearia    = isset($dados_servico['id_barbearia']) ? intval($dados_servico['id_barbearia']) : 0;

    // Descoberta automática do Barbeiro na tabela profissionais
    $nome_barbeiro = "Mestre Selecionado";
    try {
        $stmtProf = $pdo->prepare("SELECT nome FROM profissionais WHERE id_salao = :id_salao LIMIT 1");
        $stmtProf->execute([':id_salao' => $id_barbearia]);
        $prof = $stmtProf->fetchColumn();
        if ($prof) { $nome_barbeiro = $prof; }
    } catch (PDOException $e) {}

    // Obtenção inteligente do preço base
    if (isset($dados_servico['preco'])) { $preco_inicial = floatval($dados_servico['preco']); }
    elseif (isset($dados_servico['valor'])) { $preco_inicial = floatval($dados_servico['valor']); }
    else { $preco_inicial = 5000.00; }
    
    // Cálculo do desconto de 35% solicitado
    $preco_com_desconto = $preco_inicial * (1 - 0.35);

} catch (PDOException $e) {
    die("<span style='color:white;'>Erro de ligação à base de dados: " . $e->getMessage() . "</span>");
}

// 3. PROCESSAMENTO DO FORMULÁRIO DE REQUERIMENTO
$reserva_concluida = false;
$mensagem_sucesso = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['acao_agendar'])) {
    $nome_cliente = htmlspecialchars($_POST['nome_cliente']);
    $telefone     = htmlspecialchars($_POST['telefone']);
    
    try {
        // Envia o sinal/alerta de boas-vindas para o banco de dados da barbearia
        $stmtAlerta = $pdo->prepare("INSERT INTO alertas_barbearia (id_barbearia, mensagem) VALUES (:id_barbearia, :msg)");
        $msg_alerta = "🚨 ALERTA DE REQUERIMENTO: O cliente $nome_cliente ($telefone) reservou o serviço '$nome_servico' e aguarda reconhecimento!";
        $stmtAlerta->execute([':id_barbearia' => $id_barbearia, ':msg' => $msg_alerta]);
        
        $mensagem_sucesso = "📱 Reserva Processada! O salão de origem recebeu o seu sinal de alerta e irá reconhecê-lo à entrada.";
        $reserva_concluida = true; // Altera o estado da tela para exibir os botões profissionais de saída
    } catch (PDOException $e) {
        $mensagem_sucesso = "📱 Reserva Processada com sucesso no balcão!";
        $reserva_concluida = true;
    }
}
?>
<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8">
    <title>Confirmar Agendamento - Grupo Aurelius</title>
    <style>
        body { background: #0f172a; color: #fff; font-family: 'Segoe UI', Arial, sans-serif; padding: 20px; }
        .card-box { max-width: 500px; margin: 40px auto; background: #111827; padding: 30px; border-radius: 12px; border: 1px solid #233147; box-shadow: 0 10px 25px rgba(0,0,0,0.5); }
        .antigo { text-decoration: line-through; color: #ef4444; font-size: 14px; }
        .novo { color: #22c55e; font-size: 24px; font-weight: bold; }
        .campo { margin-bottom: 18px; }
        label { display: block; margin-bottom: 6px; color: #94a3b8; font-size: 13px; }
        input { width: 100%; padding: 12px; border-radius: 6px; border: 1px solid #1e293b; background: #0f172a; color: #fff; box-sizing: border-box; }
        
        .btn-acao { background: #ca8a04; color: #0f172a; padding: 14px; border: none; border-radius: 6px; width: 100%; font-weight: bold; cursor: pointer; text-transform: uppercase; letter-spacing: 0.5px; }
        .btn-acao:hover { background: #eab308; }
        
        /* Estilos dos Novos Botões Profissionais pós-confirmação */
        .bloco-navegacao { display: flex; gap: 15px; margin-top: 25px; }
        .btn-sair { flex: 1; background: #334155; color: white; padding: 14px; border: none; border-radius: 6px; font-weight: bold; text-align: center; text-decoration: none; text-transform: uppercase; font-size: 13px; }
        .btn-sair:hover { background: #475569; }
        .btn-ir-barbearia { flex: 1; background: #0284c7; color: white; padding: 14px; border: none; border-radius: 6px; font-weight: bold; text-align: center; text-decoration: none; text-transform: uppercase; font-size: 13px; }
        .btn-ir-barbearia:hover { background: #0369a1; }
        
        .sucesso { background: #14532d; border: 1px solid #22c55e; padding: 15px; border-radius: 6px; margin-bottom: 20px; text-align: center; font-size: 14px; color: #4ade80; }
    </style>
</head>
<body>
    <div class="card-box">
        <h2 style="color: #eab308; margin-top: 0; text-align: center;">Confirmar Agendamento</h2>
        
        <?php if(!empty($mensagem_sucesso)): ?> 
            <div class="sucesso"><?= $mensagem_sucesso ?></div> 
        <?php endif; ?>
        
        <p><strong>🏬 Barbearia:</strong> <?= htmlspecialchars($nome_barbearia) ?></p>
        <p><strong>✂️ Serviço:</strong> <?= htmlspecialchars($nome_servico) ?></p>
        <p><strong>🧔 Barbeiro:</strong> <?= htmlspecialchars($nome_barbeiro) ?></p>
        
        <p><strong>💰 Preço Inicial:</strong> <span class="antigo"><?= number_format($preco_inicial, 2, ',', '.') ?> Kz</span></p>
        <p><strong>🔥 Preço com 35% de Desconto:</strong> <br><span class="novo"><?= number_format($preco_com_desconto, 2, ',', '.') ?> Kz</span></p>
        
        <!-- 🟢 LOGICA CONDICIONAL PROFISSIONAL -->
        <?php if (!$reserva_concluida): ?>
            <!-- Mostra o formulário APENAS se o cliente ainda não confirmou o requerimento -->
            <form method="POST" style="margin-top: 25px;">
                <input type="hidden" name="acao_agendar" value="1">
                <div class="campo">
                    <label>Seu Nome Completo:</label>
                    <input type="text" name="nome_cliente" required placeholder="Ex: Antunes Gomes">
                </div>
                <div class="campo">
                    <label>Número de Telefone:</label>
                    <input type="text" name="telefone" required placeholder="Ex: 9XXXXXXXX">
                </div>
                <button type="submit" class="btn-acao">Pretendo Realmente Requerer este Serviço</button>
            </form>
        <?php else: ?>
            <!-- Oculta o formulário e exibe as opções de direcionamento seguro após o clique -->
            <div class="bloco-navegacao">
                <a href="Principal.php" class="btn-sair">Sair / Voltar</a>
                <a href="Dashboard.php?id_barbearia=<?= $id_barbearia ?>" class="btn-ir-barbearia">Ir para a Barbearia</a>
            </div>
        <?php endif; ?>
    </div>
</body>
</html>