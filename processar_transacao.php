<?php
if (session_status() === PHP_SESSION_NONE) { session_start(); }
require_once __DIR__ . "/config/Banco.php";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Captura dados do formulário de checkout
    $cliente = trim($_POST['cliente']);
    $telefone = trim($_POST['cliente_telefone']);
    $profissional = trim($_POST['profissional']);
    $funcionario = $profissional; // Fallback para a tua estrutura
    $servico = trim($_POST['servico']);
    $valor_original = (float)$_POST['valor'];
    
    // Identificadores de Rota
    $id_parceiro = (int)$_POST['id_parceiro']; 
    $tipo_origem = $_POST['tipo_origem']; // 'barbearia' ou 'loja'

    // Regra Comercial da Aurelius: Todo o dinheiro entra 100% na conta central.
    // Retenção automática de 10% de comissão da plataforma.
    $comissao_plataforma = $valor_original * 0.10;
    $valor_liquido_parceiro = $valor_original - $comissao_plataforma;
    
    $data_servico = date('Y-m-d');
    $hora_servico = date('H:i:s');

    // Inserção Direta na tua tabela real `pagamentos` (visto_admin = 0 faz o sino tocar)
    $stmt = $pdo->prepare("INSERT INTO `pagamentos` 
        (cliente, cliente_telefone, profissional, funcionario, data_servico, horario, hora_servico, servico, valor, status_atendimento, desconto, valor_liquido, visto_admin) 
        VALUES (?, ?, ?, ?, ?, '00:00:00', ?, ?, ?, 'Pendente', ?, ?, 0)");
    
    $stmt->execute([
        $cliente, $telefone, $profissional, $funcionario, 
        $data_servico, $hora_servico, $servico, $valor_original, 
        $comissao_plataforma, $valor_liquido_parceiro
    ]);

    echo "<script>
            alert('⚡ Transação Processada com Sucesso! Fundos centralizados na Tesouraria Aurelius.');
            window.location.href = 'Principal.php';
          </script>";
    exit();
}