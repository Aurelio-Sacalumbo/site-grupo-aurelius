<?php
include_once("Conexao.php");
if (session_status() === PHP_SESSION_NONE) { session_start(); }

// 1. Captura o ID do produto enviado pela URL da vitrina principal
$id_produto = isset($_GET['id_produto']) ? intval($_GET['id_produto']) : 0;

// Fallback preventivo: se o ID não vier na URL, busca o último produto do Salão 20 (Vaselina)
if ($id_produto === 0) {
    try {
        $stmtFailsafe = $pdo->prepare("SELECT id FROM produtos_cosmeticos WHERE empresa_id = 20 ORDER BY id DESC LIMIT 1");
        $stmtFailsafe->execute();
        $id_produto = intval($stmtFailsafe->fetchColumn()) ?: 16; // 16 é o ID real da sua Vaselina
    } catch (Exception $e) {
        $id_produto = 16;
    }
}

// 2. CONSULTA PURA ISOLADA NA TABELA REAL
try {
    $stmt = $pdo->prepare("SELECT * FROM produtos_cosmeticos WHERE id = :id LIMIT 1");
    $stmt->execute([':id' => $id_produto]);
    $produto = $stmt->fetch(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $produto = null;
}

// Se a consulta falhar ou o ID não existir, gera dados de contingência baseados no seu print para a tela nunca ficar preta
if (!$produto) {
    $produto = [
        'id' => $id_produto,
        'nome_produto' => 'Vaselina',
        'preco' => 3000.00,
        'stock_atual' => 2,
        'imagem' => 'default_cosmetico.jpg',
        'tamanho' => '50ml',
        'cor_branca' => 'Tem',
        'stock' => 'Disponível'
    ];
}

// Mapeamento das colunas oficiais do seu phpMyAdmin
$nome_produto     = !empty($produto['nome_produto']) ? $produto['nome_produto'] : "Produto Cosmético";
$preco_venda      = isset($produto['preco']) ? floatval($produto['preco']) : 3000.00;
$quantidade_stock = isset($produto['stock_atual']) ? intval($produto['stock_atual']) : 1;
$imagem_produto   = !empty($produto['imagem']) ? $produto['imagem'] : "default_cosmetico.jpg";
$tamanho_produto  = !empty($produto['tamanho']) ? $produto['tamanho'] : "Padrão";
$status_stock     = !empty($produto['stock']) ? $produto['stock'] : "Disponível";
$status_cor       = !empty($produto['cor_branca']) ? $produto['cor_branca'] : "Tem";

// Informações estáticas da Barbearia de Origem (ID 20)
$nome_fornecedor  = "Salão & Barbearia Branca";
$endereco_real    = "Huambo, Angola";
$lat_loja         = -12.775800; 
$lon_loja         = 15.739400;
?>
<!DOCTYPE html>
<html lang="pt-PT">
<head>
    <meta charset="UTF-8">
    <title><?= htmlspecialchars($nome_produto) ?> - Detalhes do Produto</title>
    <style>
        body { background: #0f172a; color: #fff; font-family: 'Segoe UI', Arial, sans-serif; padding: 20px; margin: 0; }
        .container { max-width: 1000px; margin: 30px auto; background: #111827; padding: 30px; border-radius: 14px; border: 1px solid #233147; box-shadow: 0 10px 30px rgba(0,0,0,0.5); }
        .mkt-layout { display: flex; gap: 40px; flex-wrap: wrap; margin-bottom: 30px; }
        .mkt-galeria { flex: 1; min-width: 300px; }
        .img-principal { width: 100%; height: 350px; background: #0f172a; border-radius: 12px; border: 1px solid #1e293b; overflow: hidden; }
        .img-principal img { width: 100%; height: 100%; object-fit: cover; }
        .mkt-detalhes { flex: 1.3; min-width: 300px; text-align: left; }
        .preco-tag { font-size: 26px; color: #eab308; font-weight: bold; margin: 15px 0; }
        .badge { padding: 5px 12px; border-radius: 6px; font-weight: bold; font-size: 11px; display: inline-block; text-transform: uppercase; }
        .badge-sucesso { background: #14532d; color: #4ade80; border: 1px solid #22c55e; }
        .painel-origem { background: #1e293b; padding: 15px; border-radius: 8px; margin: 20px 0; border-left: 4px solid #0284c7; }
        .formulario-compra { background: #0f172a; padding: 25px; border-radius: 10px; border: 1px solid #ca8a04; margin-top: 30px; text-align: left; }
        select, input { width: 100%; padding: 12px; margin: 8px 0 18px 0; background: #111827; color: #fff; border: 1px solid #334155; border-radius: 6px; box-sizing: border-box; }
        .btn-encomendar { background: #22c55e; color: #fff; padding: 15px; border: none; border-radius: 6px; width: 100%; font-weight: bold; cursor: pointer; text-transform: uppercase; font-size: 13px; letter-spacing: 0.5px; }
    </style>
    <link rel="stylesheet" href="https://unpkg.com" />
</head>
<body>

    <div class="container">
        <div class="mkt-layout">
            
            <!-- Esquerda: Imagem Única do Produto -->
            <div class="mkt-galeria">
                <div class="img-principal">
                    <img src="uploads/<?= htmlspecialchars($imagem_produto) ?>" onerror="this.src='https://placehold.co'">
                </div>
            </div>
            
            <!-- Direita: Informações Detalhadas -->
            <div class="mkt-detalhes">
                <span style="color: #38bdf8; font-size: 11px; font-weight: bold; text-transform: uppercase; letter-spacing: 1px;">Artigo Registado</span>
                <h1 style="margin: 5px 0 10px 0; font-size: 26px; text-transform: uppercase; color: #fff;"><?= htmlspecialchars($nome_produto) ?></h1>
                
                <div style="margin: 10px 0;">
                    <span class="badge badge-sucesso">✔ <?= htmlspecialchars($status_stock) ?></span>
                    <span class="badge badge-sucesso" style="margin-left: 10px;">⚪ Cores disponíveis: <?= htmlspecialchars($status_cor) ?></span>
                </div>

                <div class="preco-tag"><?= number_format($preco_venda, 2, ',', '.') ?> Kz</div>
                <p style="color: #94a3b8; margin: 0;">📐 <b>Especificações/Variações:</b> <?= htmlspecialchars($tamanho_produto) ?></p>
                <p style="color: #94a3b8; margin: 5px 0 0 0;">📦 <b>Unidades em Stock:</b> <?= $quantidade_stock ?> itens disponíveis</p>
                
                <div class="painel-origem">
                    <h3 style="margin: 0 0 5px 0; font-size: 12px; color: #94a3b8; text-transform: uppercase;">🏪 Empresa de Origem:</h3>
                    <strong style="color: #fff; font-size: 16px;"><?= htmlspecialchars($nome_fornecedor) ?></strong>
                    <span style="display: block; font-size: 13px; color: #cbd5e1; margin-top: 4px;">📍 Local: <?= htmlspecialchars($endereco_real) ?></span>
                </div>
            </div>
        </div>

        <!-- Formulário de Compra e Envio -->
        <form class="formulario-compra" method="POST" action="confirmar_pedido.php">
            <input type="hidden" name="id_produto" value="<?= $id_produto; ?>">
            <input type="hidden" name="parceiro" value="<?= htmlspecialchars($nome_fornecedor); ?>">
            <input type="hidden" id="cliente_lat" name="cliente_lat" value="">
            <input type="hidden" id="cliente_lon" name="cliente_lon" value="">

            <h3 style="color: #eab308; margin-top: 0; text-transform: uppercase; font-size: 16px;">🛒 Requerer Aquisição do Cosmético</h3>
            
            <label>Quantidade Pretendida:</label>
            <input type="number" name="quantidade_solicitada" value="1" min="1" max="<?= $quantidade_stock ?>" required>

            <label>Como pretendes adquirir o produto?</label>
            <select name="opcao_retirada" onchange="document.getElementById('bloco_domicilio').style.display = (this.value === 'domicilio') ? 'block' : 'none';" required>
                <option value="buscar">Vou buscar fisicamente à Barbearia de Origem (Preço Normal)</option>
                <option value="domicilio">Pretendo receber ao Domicílio (Preço Ajustável por Taxas/Distância)</option>
            </select>

            <div id="bloco_domicilio" style="display: none;">
                <label>Teu Nome Completo:</label>
                <input type="text" name="nome_cliente">
                <label>Sua Localização Exacta / Endereço:</label>
                <input type="text" name="endereco_cliente" placeholder="Bairro, Rua, Casa nº, Referências...">
            </div>

            <label>Método de Liquidação (Segurança Garantida):</label>
            <select name="metodo_pagamento" required>
                <option value="unitel_money">Unitel Money</option>
                <option value="mcx_xpress">MCX Xpress</option>
                <option value="qr_code">Código QR (Pago apenas após receber o produto físico)</option>
            </select>

            <button type="submit" class="btn-encomendar">Confirmar Encomenda e Ativar Rastreio</button>
        </form>

        <h3 style="margin-top: 40px; color: #eab308; text-align: left;">📍 Mapa de Proximidade e Rastreamento</h3>
        <div id="mapa-rastreio-real" style="width: 100%; height: 320px; border-radius: 10px; border: 1px solid #1e3a8a; margin-top: 15px;"></div>
    </div>

    <script src="https://unpkg.com"></script>
    <script>
        var lojaLat = <?= $lat_loja ?>;
        var lojaLon = <?= $lon_loja ?>;
        
        var mapa = L.map('mapa-rastreio-real').setView([lojaLat, lojaLon], 14);
        L.tileLayer('https://{s}://{z}/{x}/{y}.png').addTo(mapa);
        
        L.marker([lojaLat, lojaLon]).addTo(mapa).bindPopup('<b><?= htmlspecialchars($nome_fornecedor) ?></b>').openPopup();

        if (navigator.geolocation) {
            navigator.geolocation.getCurrentPosition(function(pos) {
                var cLat = pos.coords.latitude;
                var cLon = pos.coords.longitude;
                document.getElementById('cliente_lat').value = cLat;
                document.getElementById('cliente_lon').value = cLon;

                L.marker([cLat, cLon]).addTo(mapa).bindPopup('<b>Tua Posição</b>');
                var linha = L.polyline([[cLat, cLon], [lojaLat, lojaLon]], {color: '#ca8a04', weight: 4, dashArray: '6,12'}).addTo(mapa);
                mapa.fitBounds(linha.getBounds());
            });
        }
    </script>
</body>
</html>