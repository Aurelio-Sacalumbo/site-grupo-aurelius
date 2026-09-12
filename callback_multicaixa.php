<?php
$mysqli = new mysqli("127.0.0.1", "root", "", "aurelius_salao");
$mysqli->set_charset("utf8");

// A gateway envia os dados via POST em formato JSON
$dados_recebidos = json_decode(file_get_contents('php://input'), true);

// Exemplo de campos enviados por gateways em Angola (como ProxyPay)
if (isset($dados_recebidos['reference']) && $dados_recebidos['status'] == 'paid') {
    
    $referencia_paga = trim($dados_recebidos['reference']);
    $valor_pago      = (float)$dados_recebidos['amount'];
    
    // Numa estrutura real, precisaria de uma tabela 'pedidos' para ligar a referência ao produto.
    // Vamos simular localmente buscando um produto com base no valor para demonstrar a redução de stock:
    $query_prod = $mysqli->query("SELECT * FROM `produtos_cosmeticos` WHERE `preco` = '$valor_pago' AND `stock_atual` > 0 LIMIT 1");
    $produto = $query_prod->fetch_assoc();
    
    if ($produto) {
        $id_produto = $produto['id'];
        $empresa_id = $produto['empresa_id'];
        
        // 1. Deduz o stock do produto comprado via caixa ATM
        $mysqli->query("UPDATE `produtos_cosmeticos` SET `stock_atual` = `stock_atual` - 1 WHERE `id` = '$id_produto'");
        
        // 2. Calcula as divisões financeiras
        $comissao = $valor_pago * 0.10; // 10% Aurelius
        $valor_barbearia = $valor_pago - $comissao;
        $metodo = "Multicaixa (ATM/Express)";
        
        // 3. Regista no histórico de faturamento global
        $stmt = $mysqli->prepare("INSERT INTO `historico_vendas` (produto_id, empresa_id, metodo_pagamento, valor_total, comissao_aurelius, valor_barbearia) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("iisddd", $id_produto, $empresa_id, $metodo, $valor_pago, $comissao, $valor_barbearia);
        $stmt->execute();
        
        // Responde à gateway que o seu sistema processou tudo com sucesso
        http_response_code(200);
        echo json_encode(["status" => "sucesso", "mensagem" => "Stock atualizado"]);
        exit();
    }
}

// Se os dados estiverem errados, rejeita a requisição
http_response_code(400);
echo json_encode(["status" => "erro", "mensagem" => "Dados invalidos"]);
?>