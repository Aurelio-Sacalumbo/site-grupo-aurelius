<?php
// =========================================================================
// 🛍️ MONTRA DE STOCK ROTATIVO SAAS — GRUPO AURÉLIUS (FEED_STOCK.PHP)
// =========================================================================
if (session_status() === PHP_SESSION_NONE) { 
    session_start(); 
}

$total_exibidos = 0;

// Utiliza a ligação $mysqli que já existe no teu Admin.php
if (isset($mysqli) && $mysqli) {
    $sql_feed = "SELECT p.*, u.nome AS nome_salao FROM produtos_cosmeticos p LEFT JOIN usuario u ON p.empresa_id = u.codigo WHERE p.stock_atual > 0 ORDER BY p.id DESC";
    $feed_produtos = mysqli_query($mysqli, $sql_feed);

    if ($feed_produtos) {
        $produtos_array = []; // Inicialização corrigida contra Parse Error
        while($row = mysqli_fetch_assoc($feed_produtos)) { 
            $produtos_array[] = $row; 
        }
        
        // 🔀 DE VEZ EM QUANDO: Embaralha os produtos do stock a cada carregamento
        shuffle($produtos_array); 

        foreach ($produtos_array as $post) {
            $id_post = intval($post['id']);
            
            // Filtro de segurança temporal (45 dias para aceitar o histórico)
            $data_cadastro = !empty($post['data_cadastro']) ? $post['data_cadastro'] : '';
            if (!empty($data_cadastro)) {
                $tempo_vida_dias = floor((time() - strtotime($data_cadastro)) / 86400);
                if ($tempo_vida_dias > 45) { continue; } 
            }

            $total_exibidos++;
            
            // Mapeamento dinâmico das colunas reais do teu phpMyAdmin
            $loja_origem   = htmlspecialchars(!empty($post['nome_salao']) ? $post['nome_salao'] : 'Angelino Comercial', ENT_QUOTES, 'UTF-8');
            $produto_nome  = htmlspecialchars($post['nome_produto'] ?? 'Artigo Cosmético', ENT_QUOTES, 'UTF-8');
            $distribuicao  = htmlspecialchars(!empty($post['tamanho']) ? $post['tamanho'] : 'Kapango (Huambo)');
            $stock_dispo   = intval($post['stock_atual'] ?? 0);
            $preco_real    = number_format(!empty($post['preco']) ? $post['preco'] : 2350, 2, ',', '.');
            
            $id_loja_origem = intval($post['empresa_id'] ?? 237);
            $url_loja_saas = "Dashboard.php?id=" . $id_loja_origem;

            $img_banco = !empty($post['imagem']) ? trim($post['imagem']) : 'default.png';

            // 1. Se o arquivo estiver fisicamente dentro da pasta 'upload/'
            if (file_exists("upload/" . basename($img_banco)) && !is_dir("upload/" . basename($img_banco))) {
                $img_produto_path = "upload/" . basename($img_banco);
            } 
            // 2. Se for uma das fotos soltas que vimos no teu explorador (Raiz do projeto)
            elseif (file_exists(basename($img_banco)) && !is_dir(basename($img_banco))) {
                $img_produto_path = basename($img_banco);
            } 
            // 3. Fallback de segurança para não quebrar o layout se não encontrar em lado nenhum
            else {
                $img_produto_path = "upload/default.png"; 
            }

// 🟢 FAZ O MESMO AJUSTE INTELIGENTE PARA A FOTO DE PERFIL DO LOGÓTIPO
$logo_perfil_banco = !empty($post['logo_empresa']) ? trim($post['logo_empresa']) : $img_banco;
if (file_exists("upload/" . basename($logo_perfil_banco)) && !is_dir("upload/" . basename($logo_perfil_banco))) {
    $foto_perfil_loja = "upload/" . basename($logo_perfil_banco);
} elseif (file_exists(basename($logo_perfil_banco)) && !is_dir(basename($logo_perfil_banco))) {
    $foto_perfil_loja = basename($logo_perfil_banco);
} else {
    $foto_perfil_loja = "upload/default.png";
}

            ?>

            <!-- 🎴 CARD PREMIUM DE PUBLICIDADE DE STOCK -->
            <div class="card-stock-publicidade" data-produto-id="<?php echo $id_post; ?>" style="background: #1e293b; border: 1px solid #334155; border-radius: 16px; padding: 18px; margin-bottom: 24px; box-shadow: 0 4px 15px rgba(0,0,0,0.3); box-sizing: border-box; width: 100%; font-family: 'Segoe UI', -apple-system, sans-serif;">
                
                <div style="text-align: left; margin-bottom: 10px;">
                    <span style="background: rgba(234, 179, 8, 0.1); color: #eab308; font-size: 10px; padding: 4px 8px; border-radius: 4px; font-weight: bold; text-transform: uppercase; border: 1px solid rgba(234, 179, 8, 0.2); display: inline-block;">🏷️ Oferta de Stock Ativa</span>
                </div>

                <div style="display: flex; gap: 15px; align-items: flex-start; margin-bottom: 15px; border-bottom: 1px solid #334155; padding-bottom: 12px;">
                    <div style="width: 100px; height: 100px; border-radius: 10px; overflow: hidden; background: #070b12; border: 1px solid #334155; display: flex; align-items: center; justify-content: center; flex-shrink: 0;">
                        <img src="<?php echo $img_produto_path; ?>" style="width: 100%; height: 100%; object-fit: cover;" onerror="this.src='upload/default.png';">
                    </div>
                    
                    <div style="text-align: left; min-width: 0; flex: 1;">
                        <h3 style="color: #ffffff; font-size: 16px; font-weight: bold; margin: 0 0 4px 0; text-transform: uppercase; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;"><?php echo $produto_nome; ?></h3>
                        <p style="color: #38bdf8; font-weight: 600; margin: 0; font-size: 13px;">🏬 Origem: <?php echo $loja_origem; ?></p>
                    </div>
                </div>

                <div style="background: #070b12; padding: 12px 14px; border-radius: 8px; border: 1px solid #334155; margin-bottom: 15px; font-size: 12.5px; color: #cbd5e1; text-align: left; line-height: 1.5;">
                    <p style="margin: 4px 0;"><b>Código Série:</b> LOTE-COS-241-<?php echo $id_post; ?></p>
                    <p style="margin: 4px 0;"><b>Especificações:</b> <?php echo $distribuicao; ?></p>
                    <p style="margin: 4px 0;"><b>Disponível no Armazém:</b> <span style="color: #22c55e; font-weight: bold;"><?php echo $stock_dispo; ?> un.</span></p>
                </div>

                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 15px; padding: 0 4px;">
                    <span style="color: #94a3b8; font-size: 12.5px;">Preço Balcão:</span>
                    <strong style="color: #22c55e; font-size: 18px; font-family: monospace; font-weight: bold;"><?php echo $preco_real; ?> Kz</strong>
                </div>

                <a href="<?php echo htmlspecialchars($url_loja_saas); ?>" style="text-decoration: none !important; display: block; width: 100%;" onclick="registrarCompraProduto(<?php echo $id_post; ?>)">
                    <button type="button" style="background: linear-gradient(135deg, #22c55e, #15803d); color: #000000 !important; border: none; padding: 12px; border-radius: 8px; font-weight: bold; cursor: pointer; width: 100%; text-transform: uppercase; font-size: 11px; letter-spacing: 0.5px; display: flex; align-items: center; justify-content: center; font-family: inherit;">
                        ⚡ Ver / Comprar Agora
                    </button>
                </a>
            </div>
            <?php 
        }
    }
}

if ($total_exibidos === 0):
?>
    <div id="armazem_vazio_aviso" style="color: #64748b; text-align: center; padding: 40px 20px; font-style: italic; background: #111827; border-radius: 12px; font-size: 13px; border: 1px dashed #334155; font-family: sans-serif;">
        Nenhum lote ou cosmético ativo no stock das lojas neste momento.
    </div>
<?php endif; ?>

<!-- 🤖 LOGICA REATIVA: SÓ ESCONDE SE COMPRAR. SE DER REFRESH, ELES ROTACIONAM -->
<!-- 🤖 LOGICA REATIVA: SÓ ESCONDE SE COMPRAR. SE DER REFRESH, ELES ROTACIONAM -->
<script>
document.addEventListener("DOMContentLoaded", function() {
    let comprados = JSON.parse(localStorage.getItem('produtos_comprados_aurelius') || '[]');
    let visiveis_totais = 0;

    document.querySelectorAll('.card-stock-publicidade[data-produto-id]').forEach(card => {
        let idProduto = parseInt(card.getAttribute('data-produto-id'));
        
        if (comprados.includes(idProduto)) {
            card.style.display = 'none'; // Esconde apenas o que já foi comprado pelo cliente
        } else {
            visiveis_totais++;
        }
    });

    if (visiveis_totais === 0) {
        const aviso = document.getElementById('armazem_vazio_aviso');
        if (aviso) { 
            aviso.style.display = 'block'; 
        }
    }
});

function registrarCompraProduto(idProduto) {
    let comprados = JSON.parse(localStorage.getItem('produtos_comprados_aurelius') || '[]');
    if (!comprados.includes(idProduto)) {
        comprados.push(idProduto);
        localStorage.setItem('produtos_comprados_aurelius', JSON.stringify(comprados));
    }
}
</script>