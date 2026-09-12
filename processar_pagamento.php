<?php
// processar_pagamento.php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST, GET, OPTIONS");
header("Access-Control-Allow-Headers: *");
header('Content-Type: application/json; charset=utf-8');
session_start();

include_once("Conexao.php");

// ⚡ CORREÇÃO PARA O APACHE LOCAL: Se for uma requisição de verificação (OPTIONS), responde com sucesso e sai
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// Recupera o nome do cliente logado ou define "Aurelio" se estiver em testes locais
$nome_cliente = $_SESSION['cliente_nome'] ?? 'Aurelio'; 

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    
    $telefone = isset($_POST['telefone']) ? htmlspecialchars(trim($_POST['telefone']), ENT_QUOTES, 'UTF-8') : null;
    $plano    = isset($_POST['plano']) ? htmlspecialchars(trim($_POST['plano']), ENT_QUOTES, 'UTF-8') : null;

    if (empty($telefone) || empty($plano)) {
        echo json_encode(['sucesso' => false, 'mensagem' => 'Dados de pagamento incompletos.']);
        exit;
    }

    // Definição de datas e valores dos planos
    $data_inicio = date('Y-m-d H:i:s');
    $data_atual = new DateTime();
    
    switch ($plano) {
        case 'semestral':
            $data_atual->modify('+6 months');
            $valor_plano = 13500;
            break;
        case 'anual':
            $data_atual->modify('+1 year');
            $valor_plano = 24000;
            break;
        case 'mensal':
default:
            $data_atual->modify('+1 month');
            $valor_plano = 2500;
            break;
    }
    
    $data_fim = $data_atual->format('Y-m-d H:i:s');

    try {
        // Gravação segura na nova tabela estruturada
        $sql = "INSERT INTO assinaturas (cliente, plano, valor, data_inicio, data_fim, status, telefone_express) VALUES (?, ?, ?, ?, ?, 'Ativo', ?)";
        $stmt = $pdo->prepare($sql);
        $executou = $stmt->execute([$nome_cliente, $plano, $valor_plano, $data_inicio, $data_fim, $telefone]);

        if ($executou) {
            // Ativa os privilégios VIP imediatamente na sessão do navegador
            $_SESSION['tipo_conta'] = 'Premium';
            $_SESSION['desconto_percentual'] = 20;

            echo json_encode([
                'sucesso' => true, 
                'mensagem' => 'Plano Premium ativado com sucesso! Aproveite os seus 20% de desconto.'
            ]);
        } else {
            echo json_encode(['sucesso' => false, 'mensagem' => 'Erro ao executar a gravação do plano.']);
        }

    } catch (PDOException $e) {
        echo json_encode([
            'sucesso' => false, 
            'mensagem' => 'Erro no servidor: ' . $e->getMessage()
        ]);
    }
} else {
    echo json_encode(['sucesso' => false, 'mensagem' => 'Método inválido.']);
}