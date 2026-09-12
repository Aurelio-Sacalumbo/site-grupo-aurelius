<?php
// 1. Conexão direta com a base limpa
require_once __DIR__ . "/config/Banco.php";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    
    // Captura os dados textuais exatos do formulário do seu Dashboard
    $cliente     = $_POST['cliente'] ?? 'Consumidor Geral';
    $funcionario = $_POST['funcionario'] ?? 'Não Informado';
    $servico     = $_POST['servico'] ?? 'Serviço Geral';
    $data        = $_POST['data'] ?? date('Y-m-d');
    $hora        = $_POST['hora'] ?? date('H:i');
    
    // Converte o valor para número limpo decimal
    $valorRaw    = $_POST['valor'] ?? 0;
    $valor       = (float) preg_replace('/[^0-9.]/', '', $valorRaw);

    try {
        // A query agora vai direto ao ponto, inserindo os dados nas colunas limpas da tabela nova
        $sql = "INSERT INTO pagamentos (cliente, funcionario, data_servico, hora_servico, servico, valor) 
                VALUES (:cliente, :funcionario, :data, :hora, :servico, :valor)";

        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            ":cliente"     => $cliente,
            ":funcionario" => $funcionario,
            ":data"        => $data,
            ":hora"        => $hora,
            ":servico"     => $servico,
            ":valor"       => $valor
        ]);

        // 3. Desenha o Cupom Comercial na tela pronto para impressão física
        echo "
        <div style='max-width: 450px; margin: 40px auto; padding: 25px; border: 2px dashed #22c55e; border-radius: 12px; font-family: monospace; background-color: #fff; color: #000; box-shadow: 0 4px 10px rgba(0,0,0,0.1);'>
            <h2 style='text-align: center; margin-bottom: 5px; color: #15803d;'>AURELIUS SALÃO</h2>
            <p style='text-align: center; font-size: 11px; margin-top: 0;'>Huambo, Angola</p>
            <p style='text-align: center;'>--------------------------------------</p>
            <p><b>✅ STATUS:</b> SALVO NA BASE DE DADOS</p>
            <p><b>👤 CLIENTE:</b> $cliente</p>
            <p><b>💈 PROFISSIONAL:</b> $funcionario</p>
            <p><b>📅 DATA/HORA:</b> " . date('d/m/Y H:i', strtotime("$data $hora")) . " h</p>
            <p><b>✂️ SERVIÇO:</b> $servico</p>
            <p style='text-align: center;'>--------------------------------------</p>
            <h3 style='text-align: right; font-size: 20px;'>TOTAL: " . number_format($valor, 2, ',', '.') . " Kz</h3>
            <p style='text-align: center; font-size: 11px; margin-top: 25px; color: #666;'>Obrigado pela preferência!</p>
            <hr style='border: 0; border-top: 1px dashed #ccc; margin: 20px 0;'>
            <div style='text-align: center;'>
                <a href='Dashboard.php' style='display: inline-block; background-color: #22c55e; color: white; padding: 10px 25px; text-decoration: none; border-radius: 6px; font-weight: bold; font-family: sans-serif; font-size: 14px;'>Voltar ao Painel Principal</a>
            </div>
        </div>
        <script>
            // Dispara o diálogo de impressão de forma automática
            window.onload = function() { window.print(); }
        </script>
        ";

    } catch (PDOException $e) {
        echo "<div style='max-width:600px; margin:30px auto; padding:20px; font-family:sans-serif; background:#fff; color:#000; border:2px solid #ef4444; border-radius:8px;'>";
        echo "<h3 style='color:#ef4444;'>Erro de Gravação:</h3> " . $e->getMessage();
        echo "<br><a href='Dashboard.php' style='background:#475569; color:#fff; padding:8px 16px; text-decoration:none; border-radius:4px;'>Voltar</a></div>";
    }
}
?>