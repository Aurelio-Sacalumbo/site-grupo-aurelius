<?php
// =========================================================================
// 🌍 CENTRAL DE COMPRAS & MARKETPLACE MULTI-LOJAS SAAS - GRUPO AURÉLIUS (LOJAS.PHP)
// =========================================================================
if (session_status() === PHP_SESSION_NONE) { 
    session_start(); 
}
date_default_timezone_set('Africa/Luanda');

// 🟢 CORREÇÃO CRÍTICA: Motor Híbrido Unificado para Localhost e Produção Online
$h_host = getenv('DB_HOST') ?: "altaria.proxy.rlwy.net";
$h_port = getenv('DB_PORT') ?: "52030";
$h_name = getenv('DB_NAME') ?: "railway";
$h_user = getenv('DB_USER') ?: "root";
$h_pass = getenv('DB_PASSWORD') ?: "tPzDwXGkyczyyYdcyvLmHLSMmfZmnMIZ";

$mysqli = mysqli_init();
if (!@mysqli_real_connect($mysqli, $h_host, $h_user, $h_pass, $h_name, (int)$h_port)) {
    // Fallback de contingência automática para o XAMPP local caso os tokens de nuvem falhem
    $mysqli = @mysqli_connect("127.0.0.1", "root", "", "aurelius_salao");
}

if (!$mysqli || mysqli_connect_errno()) { 
    die("<div style='padding:20px; background:#0f172a; color:#ef4444; font-family:sans-serif; border:1px solid #ef4444; border-radius:12px; margin:20px;'>
            <strong>Erro de Infraestrutura:</strong> O marketplace não conseguiu ligar-se à base de dados centralizada.
         </div>"); 
}

$mysqli->set_charset("utf8mb4");

$id_usuario_comprador = isset($_SESSION['codigo_usuario']) ? intval($_SESSION['codigo_usuario']) : 1;

// Carrega as abas superiores lendo a tabela exclusiva de lojas
$query_lojas = $mysqli->query("SELECT id AS codigo, nome_loja AS nome, endereco_armazem AS endereco, especificacoes_json FROM lojas WHERE visivel_no_site = 1 ORDER BY id DESC");

$lojas_parceiras = [];
if ($query_lojas) {
    while ($row = $query_lojas->fetch_assoc()) {
        $lojas_parceiras[] = $row;
    }
}
?>
<!DOCTYPE html>
<html lang="pt-PT">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Marketplace de Lojas Parceiras - Grupo Aurélius</title>
    <style>
        body { font-family: 'Segoe UI', Arial, sans-serif; background: #0f172a; color: #f8fafc; padding: 20px; margin: 0; box-sizing: border-box; }
        .container-hub { max-width: 1200px; margin: 0 auto; background: #1e293b; padding: 30px; border-radius: 20px; box-shadow: 0 20px 40px rgba(0,0,0,0.5); border: 1px solid #334155; }
        .header-market { border-bottom: 2px solid #334155; padding-bottom: 15px; margin-bottom: 25px; text-align: center; }
        .wrapper-abas { display: flex; gap: 12px; overflow-x: auto; padding-bottom: 10px; margin-bottom: 30px; border-bottom: 1px solid #334155; }
        .aba-loja-btn { background: #0f172a; color: #94a3b8; border: 1px solid #334155; padding: 12px 24px; border-radius: 30px; font-weight: bold; cursor: pointer; white-space: nowrap; transition: all 0.3s ease; }
        .aba-loja-btn.active { background: #eab308; color: #000; border-color: #eab308; box-shadow: 0 4px 12px rgba(234, 179, 8, 0.2); }
        .painel-vitrine { display: none; }
        .card-produto { background: #0f172a; border-radius: 14px; border: 1px solid #334155; padding: 16px; position: relative; transition: transform 0.3s; }
        .card-produto:hover { transform: translateY(-4px); border-color: #38bdf8; }
        .img-produto { width: 100%; height: 180px; object-fit: cover; border-radius: 8px; background: #1e293b; }
        .badge-promo { background: #22c55e; color: #000; font-size: 10px; font-weight: bold; padding: 4px 8px; border-radius: 6px; position: absolute; top: 15px; right: 15px; }
        .ficha-tecnica { background: #111827; padding: 10px; border-radius: 8px; font-size: 12px; color: #cbd5e1; margin: 12px 0; line-height: 1.5; border: 1px solid #1e293b; }
        .form-pedido { margin-top: 15px; }
        .form-campo { margin-bottom: 12px; text-align: left; }
        .grid-custom { display: grid; grid-template-columns: 1fr 1fr; gap: 16px; }
        label { display: block; font-size: 10px; color: #38bdf8; font-weight: bold; text-transform: uppercase; margin-bottom: 4px; letter-spacing: 0.5px; }
        input, select, textarea { width: 100%; padding: 10px; background: #1e293b; border: 1px solid #334155; border-radius: 6px; color: white; font-size: 13px; box-sizing: border-box; }
        .checkout-box { background: #0b0f19; padding: 15px; border-radius: 10px; border: 1px solid #22314d; margin-top: 15px; display: none; }
        .btn-acao { width: 100%; padding: 12px; background: #0284c7; color: white; border: none; border-radius: 6px; font-weight: bold; text-transform: uppercase; cursor: pointer; margin-top: 10px; }
    </style>
</head>
<body>
<!-- =========================================================================
     🔷 PAINEL DE DISTRIBUIÇÃO DE LOJAS NACIONAIS (100% RESPONSIVO)
     ========================================================================= -->
     <style>
     /* Contentor Geral Flexível */
     .container-hub {
         width: 100%;
         max-width: 1350px;
         margin: 20px auto;
         padding: 0 15px;
         font-family: 'Segoe UI', Arial, sans-serif;
         box-sizing: border-box;
     }
 
     /* Barra de Topo Inteligente: Ajusta-se conforme o ecrã */
     .barra-topo-lojas {
         display: flex;
         justify-content: space-between;
         align-items: center;
         background: #111827;
         border: 1px solid #1f2937;
         border-radius: 16px;
         padding: 20px;
         margin-bottom: 25px;
         flex-wrap: wrap;
         gap: 20px;
         box-shadow: 0 4px 15px rgba(0,0,0,0.3);
         box-sizing: border-box;
     }
 
     .header-market {
         text-align: left;
         flex: 1;
         min-width: 280px;
     }
 
     /* Grupo de Botões Alinhado */
     .grupo-botoes-hub {
         display: flex;
         gap: 12px;
         flex-wrap: wrap;
         align-items: center;
     }
 
     /* Estilização Uniforme dos Links Preservando o Design Original */
     .btn-hub-lojas {
         text-decoration: none !important;
         color: #ffffff !important;
         padding: 10px 20px !important;
         border-radius: 20px !important;
         border: 1px solid #ffffff !important;
         background: blue !important;
         font-size: 13px !important;
         font-weight: bold !important;
         display: inline-block !important;
         text-align: center !important;
         transition: transform 0.2s, background-color 0.2s !important;
         box-sizing: border-box !important;
         white-space: nowrap !important;
     }
 
     .btn-hub-lojas:hover {
         transform: translateY(-2px) !important;
         background-color: #0000cd !important; /* Azul ligeiramente mais escuro no hover */
     }
 
     /* 📱 Otimizações para Smartphones e Ecrãs Pequenos */
     @media (max-width: 640px) {
         .barra-topo-lojas {
             flex-direction: column !important;
             align-items: stretch !important;
             padding: 15px !important;
             border-radius: 12px !important;
         }
         .header-market {
             text-align: center !important;
             min-width: 100% !important;
         }
         .grupo-botoes-hub {
             flex-direction: column !important;
             width: 100% !important;
             gap: 10px !important;
         }
         .btn-hub-lojas {
             width: 100% !important; /* Botões ocupam a largura total no telemóvel */
             white-space: normal !important;
         }
     }
 </style>
 
 <div class="container-hub">
     
     <div class="barra-topo-lojas">
         
         <!-- Bloco de Texto Informativo -->
         <div class="header-market">
             <h2 style="color: #fff; margin: 0 0 5px 0; font-size: 20px; font-weight: 600;">🌍 Distribuição de Lojas Nacionais</h2>
             <p style="color: #94a3b8; font-size: 13px; margin: 0;">Lojas de distribuição de compras e Vendas online.</p>
         </div>
         
         <!-- Bloco de Ações e Links Dinâmicos -->
         <div class="grupo-botoes-hub">
             <a class="btn-hub-lojas" href="Principal.php">Voltar</a>
             <a class="btn-hub-lojas" href="Admin_Venda.php">Consultar Vendas</a>
             <a class="btn-hub-lojas" href="produto%20Novo.php">Add produtos na Loja</a>
         </div>
 
     </div>
 
     <!-- O conteúdo dinâmico da sua fita de lojas (grades ou mapas) entra logo abaixo desta linha -->
 </div>
  <br> <br>
    <!-- ABAS SUPERIORES -->
    <div class="wrapper-abas">
        <?php if (empty($lojas_parceiras)): ?>
            <p style="color: #64748b; font-size: 14px; width: 100%; text-align: center; padding: 20px;">Nenhuma loja parceira ativa registada no banco de dados.</p>
        <?php else: ?>
            <?php foreach ($lojas_parceiras as $index => $loja): ?>
                <button class="aba-loja-btn <?php echo $index === 0 ? 'active' : ''; ?>" onclick="alternarAbaLoja(<?php echo $loja['codigo']; ?>, this)">
                    🏬 <?php echo htmlspecialchars($loja['nome']); ?>
                </button>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>

    <!-- CONTEÚDO DAS VITRINES -->
    <div id="contentor_vitrines_SaaS">
        <?php foreach ($lojas_parceiras as $index => $loja): 
            $id_fornecedor = $loja['codigo'];
            $config_loja = json_decode($loja['especificacoes_json'], true);
            
            $produtos_declarados = [];

            // 🧠 SINTONIA DIRETA: Procura na tabela usando a coluna empresa_id
            $query_reais = $mysqli->query("SELECT * FROM produtos_cosmeticos WHERE empresa_id = '$id_fornecedor' ORDER BY id DESC");
            if ($query_reais && $query_reais->num_rows > 0) {
                while ($prod_real = $query_reais->fetch_assoc()) {
                    $produtos_declarados[] = [
                        'id'       => $prod_real['id'],
                        'nome'     => $prod_real['nome_produto'],
                        'serie'    => 'LOTE-COS-' . $id_fornecedor . '-' . $prod_real['id'],
                        'tipo'     => 'Cosmético Comercial / Revenda',
                        'cor'      => 'Original Embalado',
                        'validade' => date('d/m/Y', strtotime('+18 months')),
                        'stock'    => intval($prod_real['stock_atual']),
                        'preco'    => floatval($prod_real['preco']),
                        'imagem'   => $prod_real['imagem']
                    ];
                }
            }
        ?>
            <div id="vitrine-loja-<?php echo $id_fornecedor; ?>" class="painel-vitrine <?php echo $index === 0 ? 'active' : ''; ?>" style="display: <?php echo $index === 0 ? 'grid' : 'none'; ?>; grid-template-columns: repeat(auto-fill, minmax(280px, 1fr)); gap: 20px; width: 100%;">
                <?php if (!empty($produtos_declarados)): ?>
                    <?php foreach ($produtos_declarados as $prod): 
                        $id_js_limpo = $prod['id']; 
                    ?>
                      <div class="card-produto" id="card_prod_<?php echo $prod['id']; ?>">
                            <span class="badge-promo">Desconto +5 Unid.</span>
                            <img src="uploads/<?php echo htmlspecialchars($prod['imagem']); ?>" class="img-produto" alt="Foto">
                            
                            <h3 style="color: #fff; font-size: 15px; margin: 12px 0 4px 0;"><?php echo htmlspecialchars($prod['nome']); ?></h3>
                            <p style="color: #64748b; font-size: 11px; margin: 0;">🏬 Origem: <?php echo htmlspecialchars($loja['nome']); ?></p>
                            <p style="color: #38bdf8; font-size: 11px; margin: 0 0 10px 0;">📍 Distribuição: <?php echo htmlspecialchars($loja['endereco']); ?></p>

                            <div class="ficha-tecnica">
                                <strong>Código Série:</strong> <?php echo $prod['serie']; ?><br>
                                <strong>Tipo / Categoria:</strong> <?php echo htmlspecialchars($prod['tipo']); ?><br>
                                <strong>Especificação Cor:</strong> <?php echo htmlspecialchars($prod['cor']); ?><br>
                                <strong>Fim de Validade:</strong> <span style="color:#f87171; font-weight:600;"><?php echo $prod['validade']; ?></span><br>
                                <strong>Disponível no Armazém:</strong> <?php echo $prod['stock']; ?> un.
                            </div>

                            <div style="font-size: 16px; font-weight: bold; color: #22c55e; margin-bottom: 12px;">
                                Preço: <span><?php echo number_format($prod['preco'], 2, ',', '.'); ?></span> Kz
                            </div>

                            <div class="form-pedido" style="margin-top: 15px;">
                                <!-- 🔐 REDIRECIONAMENTO LIMPO E INTEGRADO: Envia o ID para o Unitele.php processar -->
                                <a href="Unitele.php?id_produto_comprado=<?php echo $prod['id']; ?>&gateway=mcx_xpress" 
                                style="display: block; background: #22c55e; color: #000; text-align: center; padding: 10px; text-decoration: none; font-weight: bold; border-radius: 6px;">
                                ⚡ Comprar Agora
                             </a>
                            </div>
                        </div> <!-- 🟢 Fecha a div .card-produto de forma isolada -->
                    <?php endforeach; ?>
                <?php else: ?>
                    <p style="color: #64748b; font-size: 13px; grid-column: 1/-1; text-align: center; padding: 40px; background: #0f172a; border-radius: 12px; border: 1px dashed #334155; width: 100%;">Esta loja parceira registou-se com sucesso, mas ainda não adicionou cosméticos ou equipamentos ao catálogo.</p>
                <?php endif; ?>
            </div> <!-- 🟢 Fecha a div .painel-vitrine da respetiva loja -->
        <?php endforeach; ?>
    </div> <!-- 🟢 Fecha a div #contentor_vitrines_SaaS -->
</div> <!-- 🟢 Fecha o container-hub principal -->
<script>
function alternarAbaLoja(idLoja, botaoClicado) {
    document.querySelectorAll('.aba-loja-btn').forEach(btn => btn.classList.remove('active'));
    document.querySelectorAll('.painel-vitrine').forEach(painel => { painel.classList.remove('active'); painel.style.display = 'none'; });
    botaoClicado.classList.add('active');
    var vitrineAlvo = document.getElementById('vitrine-loja-' + idLoja);
    if(vitrineAlvo) { vitrineAlvo.classList.add('active'); vitrineAlvo.style.display = 'grid'; }
}

function ativarPainelCalculo(idProd, precoBase) {
    // Localiza e exibe o bloco de checkout correspondente ao produto clicado
    var painelCheckout = document.getElementById('checkout_box_' + idProd);
    if (painelCheckout) { 
        painelCheckout.style.display = 'block'; 
    }
    
    // Oculta o botão azul para evitar cliques duplos
    var btnRever = document.getElementById('btn_ativar_checkout_' + idProd);
    if (btnRever) { 
        btnRever.style.display = 'none'; 
    }
    
    // Dispara a árvore de cálculo em tempo real
    recalcularPrecoCheckout(idProd, precoBase);
}

function activarPainelCalculo(idProd, precoBase) {
    document.getElementById('checkout_box_' + idProd).style.display = 'block';
    document.getElementById('btn_ativar_checkout_' + idProd).style.display = 'none';
    recalcularPrecoCheckout(idProd, precoBase);
}

function recalcularPrecoCheckout(idProd, precoBase) {
    var card = document.getElementById('card_prod_' + idProd);
    if (!card) return;

    var qtd = parseInt(card.querySelector('.qtd-solicitada').value);
    if (isNaN(qtd) || qtd < 1) qtd = 1;
    var rota = card.querySelector('.provincia-destino').value;
    var plano = card.querySelector('.plano-cliente').value;

    document.getElementById('hid_qtd_' + idProd).value = qtd;
    document.getElementById('hid_prov_' + idProd).value = rota;

    var subtotal = precoBase * qtd;
    var descontoVolume = qtd >= 5 ? subtotal * 0.10 : 0;
    
    if (descontoVolume > 0) {
        card.querySelector('#view_desc_vol_row_' + idProd).style.display = 'flex';
        card.querySelector('#view_desc_vol_' + idProd).innerText = "- " + descontoVolume.toLocaleString('pt-PT') + ",00 Kz";
    } else { card.querySelector('#view_desc_vol_row_' + idProd).style.display = 'none'; }

    var valorFreteBruto = rota === 'distante' ? 5500 : 1500;
    var descontoPremium = plano === 'Premium' ? valorFreteBruto * 0.50 : 0;

    if (descontoPremium > 0) {
        card.querySelector('#view_desc_prem_row_' + idProd).style.display = 'flex';
        card.querySelector('#view_desc_prem_' + idProd).innerText = "- " + descontoPremium.toLocaleString('pt-PT') + ",00 Kz";
    } else { card.querySelector('#view_desc_prem_row_' + idProd).style.display = 'none'; }

    var totalGeralLiquido = (subtotal - descontoVolume) + (valorFreteBruto - descontoPremium);

    card.querySelector('#view_sub_' + idProd).innerText = subtotal.toLocaleString('pt-PT') + ",00 Kz";
    card.querySelector('#view_frete_' + idProd).innerText = valorFreteBruto.toLocaleString('pt-PT') + ",00 Kz";
    card.querySelector('#view_total_' + idProd).innerText = totalGeralLiquido.toLocaleString('pt-PT') + ",00 Kz";
}
</script>
</body>
</html>