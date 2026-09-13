<?php
if (session_status() === PHP_SESSION_NONE) { 
    session_start(); 
}
date_default_timezone_set('Africa/Luanda');

// 🟢 ANTI-CACHE MESTRE: Força o navegador a buscar sempre os dados reais do banco
header("Cache-Control: no-cache, must-revalidate"); // HTTP 1.1
header("Expires: Sat, 26 Jul 1997 05:00:00 GMT"); // Data no passado
header("Pragma: no-cache"); // HTTP 1.0

if (session_status() === PHP_SESSION_NONE) { 
    session_start(); 
}
date_default_timezone_set('Africa/Luanda');

// 🟢 MOTOR HÍBRIDO BLINDADO: Configuração de Nuvem e Localhost sem travamentos
$h_host = getenv('DB_HOST') ?: "altaria.proxy.rlwy.net";
$h_port = getenv('DB_PORT') ?: "52030";
$h_name = getenv('DB_NAME') ?: "railway";
$h_user = getenv('DB_USER') ?: "root";
$h_pass = getenv('DB_PASSWORD') ?: "tPzDwXGkyczyyYdcyvLmHLSMmfZmnMIZ";

$mysqli = mysqli_init();

// O segredo está no '@' e no bloco 'if': Se a ligação à nuvem falhar ou o host for desconhecido, 
// o PHP ignora o erro silenciosamente e ativa imediatamente o teu XAMPP Local.
if (!isset($_ENV['DB_HOST']) || !@mysqli_real_connect($mysqli, $h_host, $h_user, $h_pass, $h_name, (int)$h_port)) {
    // 💻 Fallback de contingência mestre para o teu MySQL Local do XAMPP
    $mysqli = @mysqli_connect("127.0.0.1", "root", "", "aurelius_salao");
}

// Se mesmo no XAMPP local houver falha, exibe a mensagem personalizada do sistema
if (!$mysqli || mysqli_connect_errno()) { 
    die("<div style='padding:20px; background:#0f172a; color:#ef4444; font-family:sans-serif; border:1px solid #ef4444; border-radius:12px; margin:20px;'>
            <strong>Erro de Infraestrutura:</strong> O marketplace não conseguiu ligar-se à base de dados centralizada local ou na nuvem.
         </div>"); 
}

$mysqli->set_charset("utf8mb4");

// Desativa o modo estrito para garantir compatibilidade com as queries do marketplace
$mysqli->query("SET SESSION sql_mode=''");

$id_usuario_comprador = isset($_SESSION['codigo_usuario']) ? intval($_SESSION['codigo_usuario']) : 1;
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





<div class="container-hub" style="max-width: 1200px; margin: 0 auto; padding: 15px; font-family: system-ui, -apple-system, sans-serif;">
     
    <div class="barra-topo-lojas" style="display: flex; flex-wrap: wrap; justify-content: space-between; align-items: center; gap: 15px; background: #1e293b; padding: 20px; border-radius: 12px; margin-bottom: 20px;">
        
        <div class="header-market" style="flex: 1; min-width: 250px;">
            <h2 style="color: #fff; margin: 0 0 5px 0; font-size: 20px; font-weight: 600;">🌍 Distribuição de Lojas Nacionais</h2>
            <p style="color: #94a3b8; font-size: 13px; margin: 0;">Lojas de distribuição de compras e Vendas online.</p>
        </div>
        
        <div class="grupo-botoes-hub" style="display: flex; flex-wrap: wrap; gap: 10px;">
            <a class="btn-hub-lojas" href="Principal.php" style="background: #334155; color: #fff; padding: 8px 16px; text-decoration: none; font-size: 13px; font-weight: 500; border-radius: 6px; text-align: center; flex: 1; min-width: 80px;">Voltar</a>
            <a class="btn-hub-lojas" href="Admin_Venda.php" style="background: #334155; color: #fff; padding: 8px 16px; text-decoration: none; font-size: 13px; font-weight: 500; border-radius: 6px; text-align: center; flex: 1; min-width: 130px;">Consultar Vendas</a>
            <a class="btn-hub-lojas" href="produto%20Novo.php" style="background: #0284c7; color: #fff; padding: 8px 16px; text-decoration: none; font-size: 13px; font-weight: 500; border-radius: 6px; text-align: center; flex: 1; min-width: 160px;">Add produtos na Loja</a>
        </div>

    </div>

    <!-- 🟢 ABAS SUPERIORES RESTAURADAS: Lê a tabela puras de lojas sem esconder o visor global -->
    <div class="wrapper-abas" style="display: flex; gap: 10px; overflow-x: auto; padding-bottom: 10px; margin-bottom: 25px; scrollbar-width: thin; -webkit-overflow-scrolling: touch;">
       <?php 
       $lojas_com_produtos = [];
       // 🔒 Retornamos ao mapeamento estável da tabela lojas para garantir que as abas nunca sumam
       $query_abas_dinamicas = $mysqli->query("SELECT DISTINCT id AS codigo, nome_loja AS nome, endereco_armazem AS endereco, especificacoes_json 
                                              FROM lojas 
                                              WHERE visivel_no_site = 1 
                                              ORDER BY nome ASC");
       
       if ($query_abas_dinamicas && $query_abas_dinamicas->num_rows > 0) {
           while ($aba = $query_abas_dinamicas->fetch_assoc()) {
               $lojas_com_produtos[] = $aba;
           }
       }

       if (empty($lojas_com_produtos)): 
       ?>
           <p style="color: #64748b; font-size: 14px; width: 100%; text-align: center; padding: 20px; background: #0f172a; border-radius: 12px;">Nenhuma loja parceira ativa registada no banco de dados.</p>
       <?php 
       else: 
           foreach ($lojas_com_produtos as $index => $aba_loja): 
       ?>
               <button class="aba-loja-btn <?php echo $index === 0 ? 'active' : ''; ?>" onclick="alternarAbaLoja(<?php echo $aba_loja['codigo']; ?>, this)" style="white-space: nowrap; flex: 0 0 auto; background: #1e293b; color: #94a3b8; border: 1px solid #334155; padding: 10px 20px; font-size: 14px; font-weight: 600; border-radius: 8px; cursor: pointer; transition: all 0.2s;">
                   🏬 <?php echo htmlspecialchars($aba_loja['nome']); ?>
               </button>
       <?php 
           endforeach; 
       endif; 
       ?>
    </div>

    <!-- CONTEÚDO DAS VITRINES AUTOMÁTICAS -->
    <div id="contentor_vitrines_SaaS">
       <?php 
       foreach ($lojas_com_produtos as $index => $aba_loja): 
           $id_fornecedor = $aba_loja['codigo'];
           $end_real = !empty($aba_loja['endereco']) ? $aba_loja['endereco'] : "Huambo - Angola";

           $produtos_declarados = [];

          // 🧠 LEITURA DIRETA E REATIVA DO BANCO DE DADOS (Dentro do Lojas.php)
// O filtro 'stock > 0' garante que se o produto zerar no Unitele.php, ele DESAPARECE da tela
$query_reais = $mysqli->query("SELECT * FROM produtos_cosmeticos 
WHERE empresa_id = '$id_fornecedor' 
AND stock > 0 
GROUP BY id 
ORDER BY id DESC");

if ($query_reais && $query_reais->num_rows > 0) {
while ($prod_real = $query_reais->fetch_assoc()) {
// Dentro do loop while ($prod_real = $query_reais->fetch_assoc()) no Lojas.php:
    $produtos_declarados[] = [
        'id'       => $prod_real['id'],
        'nome'     => $prod_real['nome_produto'],
        'serie'    => 'LOTE-COS-' . $id_fornecedor . '-' . $prod_real['id'],
        'tipo'     => (!empty($prod_real['tamanho']) && strpos($prod_real['tamanho'], 'Tam:') !== false) ? $prod_real['tamanho'] : 'Cosmético Comercial / Revenda',
        
        // 🟢 CORREÇÃO DA LINHA 268: Se a coluna 'cor_branca' ou 'cor' estiver vazia no banco, define um padrão
        'cor'      => !empty($prod_real['cor_branca']) ? $prod_real['cor_branca'] : 'Original Embalado',
        
        'validade' => date('d/m/Y', strtotime('+18 months')),
        'stock'    => intval($prod_real['stock']) > 0 ? intval($prod_real['stock']) : intval($prod_real['stock_atual']), 
        'preco'    => floatval($prod_real['preco']),
        'imagem'   => $prod_real['imagem']
    ];
}
}
       ?>
           <div id="vitrine-loja-<?php echo $id_fornecedor; ?>" class="painel-vitrine <?php echo $index === 0 ? 'active' : ''; ?>" style="display: <?php echo $index === 0 ? 'grid' : 'none'; ?>; grid-template-columns: repeat(auto-fill, minmax(280px, 1fr)); gap: 20px; width: 100%;">
               <?php if (!empty($produtos_declarados)): ?>
                   <?php foreach ($produtos_declarados as $prod): ?>
                     <div class="card-produto" id="card_prod_<?php echo $prod['id']; ?>" style="background: #1e293b; border: 1px solid #334155; border-radius: 12px; padding: 16px; display: flex; flex-direction: column; justify-content: space-between; position: relative;">
                           <span class="badge-promo" style="position: absolute; top: 12px; left: 12px; background: #eab308; color: #000; font-size: 11px; font-weight: 700; padding: 4px 8px; border-radius: 6px; z-index: 2;">Desconto +5 Unid.</span>
                           
                           <div style="width: 100%; height: 200px; border-radius: 8px; overflow: hidden; background: #0f172a; display: flex; align-items: center; justify-content: center;">
                               <img src="uploads/<?php echo htmlspecialchars($prod['imagem']); ?>" class="img-produto" alt="Foto" style="width: 100%; height: 100%; object-fit: cover;">
                           </div>
                           
                           <h3 style="color: #fff; font-size: 16px; margin: 12px 0 4px 0; font-weight: 600;"><?php echo htmlspecialchars($prod['nome']); ?></h3>
                           <p style="color: #94a3b8; font-size: 12px; margin: 0;">🏬 Origem: <?php echo htmlspecialchars($aba_loja['nome']); ?></p>
                           <p style="color: #38bdf8; font-size: 12px; margin: 0 0 12px 0;">📍 Distribuição: <?php echo htmlspecialchars($end_real); ?></p>

                           <div class="ficha-tecnica" style="background: #0f172a; padding: 12px; border-radius: 8px; font-size: 12px; color: #cbd5e1; line-height: 1.6; margin-bottom: 12px; border: 1px solid #1e293b;">
                               <strong>Código Série:</strong> <?php echo $prod['serie']; ?><br>
                               <strong>Especificações:</strong> <?php echo htmlspecialchars($prod['tipo']); ?><br>
                               <strong>Especificação Cor:</strong> <?php echo htmlspecialchars($prod['cor']); ?><br>
                               <strong>Fim de Validade:</strong> <span style="color:#f87171; font-weight:600;"><?php echo $prod['validade']; ?></span><br>
                               <strong>Disponível no Armazém:</strong> <span style="font-weight: 600; color: #fff;"><?php echo $prod['stock']; ?> un.</span>
                           </div>

                           <div style="font-size: 18px; font-weight: 700; color: #22c55e; margin-bottom: 12px;">
                               Preço: <span><?php echo number_format($prod['preco'], 2, ',', '.'); ?></span> Kz
                           </div>

                           <div class="form-pedido" style="margin-top: auto;">
                               <a href="Unitele.php?id_produto_comprado=<?php echo $prod['id']; ?>&gateway=mcx_xpress" 
                                  style="display: block; background: #22c55e; color: #000; text-align: center; padding: 12px; text-decoration: none; font-weight: 700; border-radius: 8px; font-size: 14px;">
                                   ⚡ Comprar Agora
                               </a>
                           </div>
                     </div>
                   <?php endforeach; ?>
               <?php else: ?>
                   <div style="color: #64748b; font-size: 14px; grid-column: 1/-1; text-align: center; padding: 50px 20px; background: #0f172a; border-radius: 12px; border: 1px dashed #334155; width: 100%;">
                       <span style="font-size: 32px; display: block; margin-bottom: 10px;">📦</span>
                       Esta loja parceira registou-se com sucesso, mas ainda não adicionou cosméticos ou equipamentos ao catálogo.
                   </div>
               <?php endif; ?>
           </div>
       <?php endforeach; ?>
    </div>
</div>









<!-- 🟢 BLOCO JAVASCRIPT OTIMIZADO E SEM DUPLICAÇÕES OMITIDAS -->
<script>
function alternarAbaLoja(idLoja, botaoClicado) {
    document.querySelectorAll('.aba-loja-btn').forEach(btn => btn.classList.remove('active'));
    document.querySelectorAll('.painel-vitrine').forEach(painel => { 
        painel.classList.remove('active'); 
        painel.style.display = 'none'; 
    });  document.querySelectorAll('.painel-vitrine').forEach(painel => { 
        painel.classList.remove('active'); 
        painel.style.display = 'none'; 
    });
    botaoClicado.classList.add('active');
    var vitrineAlvo = document.getElementById('vitrine-loja-' + idLoja);
    if(vitrineAlvo) { 
        vitrineAlvo.classList.add('active'); 
        vitrineAlvo.style.display = 'grid'; 
    }
}

function ativarPainelCalculo(idProd, precoBase) {
    var painelCheckout = document.getElementById('checkout_box_' + idProd);
    var btnRever = document.getElementById('btn_ativar_checkout_' + idProd);
    
    if (painelCheckout) painelCheckout.style.display = 'block'; 
    if (btnRever) btnRever.style.display = 'none'; 
    
    recalcularPrecoCheckout(idProd, precoBase);
}

function recalcularPrecoCheckout(idProd, precoBase) {
    var card = document.getElementById('card_prod_' + idProd);
    if (!card) return;

    var qtd = parseInt(card.querySelector('.qtd-solicitada').value);
    if (isNaN(qtd) || qtd < 1) qtd = 1;
    var rota = card.querySelector('.provincia-destino').value;
    var plano = card.querySelector('.plano-cliente').value;

    var hidQtd = document.getElementById('hid_qtd_' + idProd);
    var hidProv = document.getElementById('hid_prov_' + idProd);
    if(hidQtd) hidQtd.value = qtd;
    if(hidProv) hidProv.value = rota;

    var subtotal = precoBase * qtd;
    var descontoVolume = qtd >= 5 ? subtotal * 0.10 : 0;
    
    var rowDescVol = card.querySelector('#view_desc_vol_row_' + idProd);
    var txtDescVol = card.querySelector('#view_desc_vol_' + idProd);
    if (descontoVolume > 0) {
        if(rowDescVol) rowDescVol.style.display = 'flex';
        if(txtDescVol) txtDescVol.innerText = "- " + descontoVolume.toLocaleString('pt-PT') + ",00 Kz";
    } else { 
        if(rowDescVol) rowDescVol.style.display = 'none'; 
    }

    var valorFreteBruto = rota === 'distante' ? 5500 : 1500;
    var descontoPremium = plano === 'Premium' ? valorFreteBruto * 0.50 : 0;

    var rowDescPrem = card.querySelector('#view_desc_prem_row_' + idProd);
    var txtDescPrem = card.querySelector('#view_desc_prem_' + idProd);
    if (descontoPremium > 0) {
        if(rowDescPrem) rowDescPrem.style.display = 'flex';
        if(txtDescPrem) txtDescPrem.innerText = "- " + descontoPremium.toLocaleString('pt-PT') + ",00 Kz";
    } else { 
        if(rowDescPrem) rowDescPrem.style.display = 'none'; 
    }

    var totalGeralLiquido = (subtotal - descontoVolume) + (valorFreteBruto - descontoPremium);

    var viewSub = card.querySelector('#view_sub_' + idProd);
    var viewFrete = card.querySelector('#view_frete_' + idProd);
    var viewTotal = card.querySelector('#view_total_' + idProd);
    
    if(viewSub) viewSub.innerText = subtotal.toLocaleString('pt-PT') + ",00 Kz";
    if(viewFrete) viewFrete.innerText = valorFreteBruto.toLocaleString('pt-PT') + ",00 Kz";
    if(viewTotal) viewTotal.innerText = totalGeralLiquido.toLocaleString('pt-PT') + ",00 Kz";
}
</script>
</body>
</html>