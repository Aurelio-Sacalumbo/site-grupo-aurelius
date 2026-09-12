<?php
if (!isset($_SESSION)) { 
    session_start(); 
}

$mysqli = new mysqli("127.0.0.1", "root", "", "aurelius_salao");
$mysqli->set_charset("utf8");

// Verifica se os dados do formulário foram submetidos de forma segura
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['produto_id']) && isset($_POST['telefone'])) {
    
    $id_produto = (int)$_POST['produto_id'];
    $telefone   = trim($_POST['telefone']);
    $valor_pago = (float)$_POST['valor'];
    
    // 1. Verifica se o produto existe e tem stock no balcão
    $query_verificar = $mysqli->query("SELECT * FROM `produtos_cosmeticos` WHERE `id` = '$id_produto'");
    $produto = $query_verificar->fetch_assoc();
    
    if ($produto && $produto['stock_atual'] > 0) {
        
        // Simulação da aprovação do pagamento Unitel Money
        $pagamento_com_sucesso = true; 
        
        if ($pagamento_com_sucesso) {
            // Deduz 1 unidade no stock do balcão automaticamente
            $mysqli->query("UPDATE `produtos_cosmeticos` SET `stock_atual` = `stock_atual` - 1 WHERE `id` = '$id_produto'");
            
            // Calcula a comissão da Aurelius (10% de taxa da plataforma)
            $taxa_plataforma = 0.10;
            $comissao = $valor_pago * $taxa_plataforma;
            $valor_liquido_barbeiro = $valor_pago - $comissao;
            $empresa_id = $produto['empresa_id'];
            $metodo_nome = "Unitel Money";
        
            // Insere o registo financeiro na tabela de auditoria
            $stmt_venda = $mysqli->prepare("INSERT INTO `historico_vendas` (produto_id, empresa_id, metodo_pagamento, valor_total, comissao_aurelius, valor_barbearia) VALUES (?, ?, ?, ?, ?, ?)");
            $stmt_venda->bind_param("iisddd", $id_produto, $empresa_id, $metodo_nome, $valor_pago, $comissao, $valor_liquido_barbeiro);
            $stmt_venda->execute();
            
            echo "<script>
                    alert('📱 Pagamento Unitel Money Confirmado! Apresente o seu telemóvel ($telefone) no balcão para levantar o produto.');
                    window.location.href = 'Dashboard.php';
                  </script>";
            exit();
            
        } else {
            echo "<script>
                    alert('Erro: Saldo insuficiente ou transação recusada na carteira móvel.');
                    window.location.href = 'Dashboard.php';
                  </script>";
            exit();
        }
        
    } else {
        echo "<script>
                alert('Infelizmente este produto acabou de esgotar no balcão!');
                window.location.href = 'Dashboard.php';
              </script>";
        exit();
    }
} else {
    header("Location: Dashboard.php");
    exit();
}
?>