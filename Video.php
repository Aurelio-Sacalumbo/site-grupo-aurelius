<?php
// =========================================================================
// 🎬 AURELIUS REELS & PEDESTAL VIRTUAL MESTRE (FICHEIRO CORE UNIFICADO)
// =========================================================================
if (session_status() === PHP_SESSION_NONE) { 
    session_start(); 
}

include_once("Conexao.php");

$conexao_link = $conexao_aurelius ?? $conexao ?? $link ?? $conn ?? $pdo ?? null;

if (!$conexao_link || !($conexao_link instanceof mysqli) || @mysqli_ping($conexao_link) === false) {
    // Tenta primeiro conectar ao banco de dados virtual online do Railway
    $conexao_link = @mysqli_connect("altaria.proxy.rlwy.net", "root", "tPzDwXGkyczyyYdcyvLmHLSMmfZmnMIZ", "railway", 52030);
    
    // Se o Railway falhar ou estiver sem internet, usa o teu XAMPP local como fallback
    if (!$conexao_link) {
        $conexao_link = @mysqli_connect("127.0.0.1", "root", "", "aurelius_salao");
    }
}

if (!isset($_SESSION['videos_vistos_feed'])) { $_SESSION['videos_vistos_feed'] = []; }
if (isset($_GET['reset']) || count($_SESSION['videos_vistos_feed']) > 40) {
    $_SESSION['videos_vistos_feed'] = [];
    header("Location: Video.php"); exit();
}

// 🟢 1. PROCESSAMENTO DE REAÇÕES EM TEMPO REAL COM IMPULSO NO PEDESTAL
if (isset($_GET['acao_reacao']) && isset($_GET['id_post'])) {
    $id_anuncio = intval($_GET['id_post']);
    $reacao = trim($_GET['acao_reacao']);
    
    if ($id_anuncio > 0 && $conexao_link) {
        if ($reacao === 'adoro') {
            mysqli_query($conexao_link, "UPDATE anuncios SET likes_adoro = likes_adoro + 1, pontos_recompensa = pontos_recompensa + 10 WHERE id_anuncio = $id_anuncio");
        } elseif ($reacao === 'ncurto') {
            mysqli_query($conexao_link, "UPDATE anuncios SET likes_ncurto = likes_ncurto + 1, pontos_recompensa = pontos_recompensa - 2 WHERE id_anuncio = $id_anuncio");
        } elseif ($reacao === 'partilha') {
            mysqli_query($conexao_link, "UPDATE anuncios SET contagem_partilhas = contagem_partilhas + 1, pontos_recompensa = pontos_recompensa + 25 WHERE id_anuncio = $id_anuncio");
        }
    }
    header("Location: Video.php#reel-" . $id_anuncio);
    exit();
}

// 🟢 2. INSERÇÃO DE COMENTÁRIOS COM ACUMULAÇÃO DE ENGAJAMENTO (+15 PONTOS)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['enviar_comentario_db'])) {
    $id_anuncio_coment = intval($_POST['id_post_coment']);
    $mensagem_coment = mysqli_real_escape_string($conexao_link, trim($_POST['texto_comentario']));
    
    if (!empty($mensagem_coment) && $id_anuncio_coment > 0 && $conexao_link) {
        mysqli_query($conexao_link, "INSERT INTO `comentarios_reels` (id_anuncio, autor_nome, mensagem) VALUES ($id_anuncio_coment, 'Utilizador Aurelius', '$mensagem_coment')");
        mysqli_query($conexao_link, "UPDATE anuncios SET pontos_recompensa = pontos_recompensa + 15 WHERE id_anuncio = $id_anuncio_coment");
    }
    header("Location: Video.php#reel-" . $id_anuncio_coment);
    exit();
}

$ids_ignorados = !empty($_SESSION['videos_vistos_feed']) ? implode(',', array_map('intval', $_SESSION['videos_vistos_feed'])) : '0';
$listaReels = [];

if ($conexao_link) {
    mysqli_set_charset($conexao_link, "utf8mb4");

    // 🟢 QUERY COESORA: Pedestal virtual calculado com base na interação do utilizador
    $sql_videos = "
    SELECT a.*, 
           u.nome AS nome_loja, u.endereco AS endereco_armazem, 
           (a.likes_adoro * 10 + a.contagem_partilhas * 25) AS ranking_pedestal
    FROM `anuncios` a 
    LEFT JOIN `usuario` u ON a.id_barbearia = u.codigo 
    WHERE a.ativo = 1 AND a.id_anuncio NOT IN ($ids_ignorados)
    ORDER BY (a.likes_adoro * 10 + a.contagem_partilhas * 25) DESC, RAND()
";
    
    $res_vids = mysqli_query($conexao_link, $sql_videos);
    if ($res_vids) {
        while ($r = mysqli_fetch_assoc($res_vids)) { 
            $listaReels[] = $r; 
        }
    }
}
?>
<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
   <!-- Na barra de navegação e instalação do PWA -->
<title>BarbeariasAngola — Rede de Distribuição & Estética</title>

<!-- No cabeçalho principal do seu menu Slate -->
<h4 style="color: #eab308; text-transform: uppercase; font-weight: bold; font-size: 14px; letter-spacing: 0.5px;">
    🎯 BarbeariasAngola • Painel Operacional
</h4>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { background: #070b12; color: #fff; font-family: system-ui, sans-serif; overflow-x: hidden; }
        .seletor-formato-container { display: flex; justify-content: center; gap: 15px; margin: 20px auto; max-width: 480px; padding: 0 15px; }
        .btn-filtro-media { flex: 1; background: #111827; border: 2px solid #1f2937; color: #94a3b8; padding: 12px; border-radius: 30px; font-weight: bold; font-size: 12px; cursor: pointer; text-transform: uppercase; transition: 0.3s; }
        .btn-filtro-media.ativo { background: linear-gradient(135deg, #00d2ff, #0088cc); border-color: #00c4ff; color: #fff; box-shadow: 0 4px 15px rgba(0, 210, 255, 0.3); }
        .wrapper-vertical-snap { display: flex; flex-direction: column; align-items: center; gap: 35px; padding: 20px 15px; width: 100%; scroll-snap-type: y mandatory; }
        .reel-card-vertical { position: relative; width: 100%; max-width: 350px; height: 590px; background: #000; border-radius: 24px; overflow: hidden; border: 2px solid #1e293b; box-shadow: 0 15px 35px rgba(0,0,0,0.6); scroll-snap-align: start; }
        .video-vertical-src { width: 100%; height: 100%; object-fit: cover; cursor: pointer; }
        .grid-horizontal-cinema { display: none; grid-template-columns: repeat(auto-fit, minmax(320px, 1fr)); gap: 25px; max-width: 1200px; margin: 0 auto; padding: 20px; }
        .card-video-cinema { background: #111827; border: 2px solid #1f2937; border-radius: 16px; padding: 16px; box-shadow: 0 10px 25px rgba(0,0,0,0.4); }
        .video-horizontal-src { width: 100%; aspect-ratio: 16/9; border-radius: 10px; background: #000; object-fit: contain; }
        .barra-lateral-acoes { position: absolute; right: 12px; bottom: 125px; display: flex; flex-direction: column; gap: 14px; z-index: 110; }
        .btn-circulo-vivo { background: rgba(15, 23, 42, 0.85); border: 1px solid rgba(255,255,255,0.1); color: #fff; width: 46px; height: 44px; border-radius: 50%; display: flex; flex-direction: column; align-items: center; justify-content: center; cursor: pointer; text-decoration: none; font-size: 16px; transition: transform 0.2s; }
        .btn-circulo-vivo:hover { transform: scale(1.1); }
        .txt-cont-viva { font-size: 10px; font-weight: bold; margin-top: 2px; color: #cbd5e1; }
        .info-overlay-inferior { position: absolute; bottom: 0; left: 0; width: 100%; background: linear-gradient(to top, rgba(0,0,0,0.95) 0%, transparent 100%); padding: 25px 20px 20px 20px; box-sizing: border-box; z-index: 100; text-align: left; pointer-events: none; }
        .gaveta-comentarios { display: none; position: absolute; bottom: 0; left: 0; width: 100%; height: 45vh; background: #0b0f19; border-top: 2px solid #00d2ff; border-radius: 20px 20px 0 0; z-index: 200; padding: 15px; box-sizing: border-box; flex-direction: column; }
        .comentario-item { background: #1e293b; padding: 6px 10px; border-radius: 6px; font-size: 12px; margin-bottom: 6px; border-left: 3px solid #00d2ff; text-align: left; }
        .btn-sair { position: fixed; top: 20px; left: 20px; z-index: 99999; background: rgba(15, 23, 42, 0.85); border: 2px solid #ef4444; color: #fff; padding: 10px 20px; border-radius: 30px; font-size: 11px; font-weight: bold; text-decoration: none; text-transform: uppercase; }
        .selo-pedestal-ouro { display: inline-block; background: linear-gradient(135deg, #ca8a04, #eab308); color: #000; font-size: 9px; font-weight: bold; padding: 2px 6px; border-radius: 4px; text-transform: uppercase; margin-bottom: 4px; box-shadow: 0 0 10px rgba(234,179,8,0.4); }
    </style>
</head>
<body>

<a href="Principal.php" class="btn-sair">✕ VOLTAR</a>

<div class="seletor-formato-container" style="margin-top: 80px;">
    <button class="btn-filtro-media ativo" id="btn_aba_vertical" onclick="mudarFormatoExibicao('vertical')">📱 Reels Verticais</button>
    <button class="btn-filtro-media" id="btn_aba_horizontal" onclick="mudarFormatoExibicao('horizontal')">🖥️ Vídeos de Aula</button>
</div>

<!-- ABA 1: REELS VERTICAIS -->
<div id="aba_conteudo_vertical" class="wrapper-vertical-snap" style="display: flex;">
    <?php 
    $reels_exibidos = 0;
    if (!empty($listaReels)):
        foreach ($listaReels as $reel): 
            $id_anuncio_real = intval($reel['id_anuncio']);
            $id_barb_real = intval($reel['id_barbearia']);
            $titulo_v = htmlspecialchars($reel['titulo']);
            $endereco_loja = !empty($reel['endereco_armazem']) ? htmlspecialchars($reel['endereco_armazem']) : 'Huambo, Angola';
            $nome_loja_dona = !empty($reel['nome_loja']) ? htmlspecialchars($reel['nome_loja']) : 'Parceiro Aurélius';
            $ranking = intval($reel['ranking_pedestal']);
            
            if (strlen($titulo_v) < 22 && $reels_exibidos < 6):
                $_SESSION['videos_vistos_feed'][] = $id_anuncio_real;
                $reels_exibidos++;
    ?>
        <div id="reel-<?= $id_anuncio_real ?>" class="reel-card-vertical">
        <video controls autoplay muted playsinline loop style="width: 100%; height: 100%; object-fit: cover; background: #000000;">
        <source src="<?= htmlspecialchars($reel['imagem'] ?? $r['imagem']) ?>" type="video/mp4">
        <source src="<?= htmlspecialchars($reel['imagem'] ?? $r['imagem']) ?>" type="video/quicktime">
        O seu telemóvel não suporta a reprodução deste vídeo.
    </video>
            
            <div class="barra-lateral-acoes">
                <!-- ❤️ BOTÃO GOSTO ASSÍNCRONO -->
                <div class="btn-circulo-vivo" onclick="enviarReacaoAssincrona(<?= $id_anuncio_real ?>, 'adoro')" style="border-color:#ef4444; cursor: pointer;">
                    ❤️<span class="txt-cont-viva" id="cont_like_<?= $id_anuncio_real ?>"><?= intval($reel['likes_adoro']) ?></span>
                </div>
                
                <!-- ❌ BOTÃO NÃO CURTO ASSÍNCRONO -->
                <div class="btn-circulo-vivo" onclick="enviarReacaoAssincrona(<?= $id_anuncio_real ?>, 'ncurto')" style="cursor: pointer;">
                    ❌<span class="txt-cont-viva" id="cont_dislike_<?= $id_anuncio_real ?>"><?= intval($reel['likes_ncurto']) ?></span>
                </div>
                
                <div class="btn-circulo-vivo" onclick="abrirGavetaComentarios(<?= $id_anuncio_real ?>)" style="cursor: pointer;">
                    💬<span class="txt-cont-viva">SMS</span>
                </div>
                
                <div class="btn-circulo-vivo" style="background:#00d2ff; color:#000; cursor: pointer;" onclick="dispararPartilhaSaaS('<?= htmlspecialchars($titulo_v) ?>', <?= $id_anuncio_real ?>, <?= $id_barb_real ?>)">
                    🚀<span class="txt-cont-viva">Partilhar</span>
                </div>

                <!-- 🛒 CORRIGIDO: Rota dinâmica baseada no ID real do Parceiro da foto para não quebrar a pauta -->
                <a href="Principal.php?id_parceiro=<?= $id_barb_real ?>#nivel1" class="btn-circulo-vivo" style="background: #22c55e; border-color: #16a34a; box-shadow: 0 4px 12px rgba(34,197,94,0.4); text-decoration: none;">
                    🛒<span class="txt-cont-viva">Loja</span>
                </a>
            </div>

            <!-- Painel Inferior de Informações e Metadados do Pedestal -->
            <div class="info-overlay-inferior">
                <?php if($ranking >= 30): ?>
                    <span class="selo-pedestal-ouro">🏆 Melhor Barbearia da Rede (★ <?= $ranking ?>)</span>
                <?php else: ?>
                    <span class="selo-pedestal-ouro" style="background: #1e293b; color: #fff; box-shadow: none;">⭐ Escalão: <?= $ranking ?> pts</span>
                <?php endif; ?>
                
                <strong style="color: #00d2ff; font-size: 14px; display: block; font-weight: bold; text-shadow: 0 2px 4px rgba(0,0,0,0.8);">👑 <?= $nome_loja_dona ?></strong>
                <span style="font-size: 10px; color: #22c55e; display: block; font-weight: bold; margin: 3px 0;">📍 Endereço: <?= $endereco_loja ?></span>
                <p style="margin: 4px 0 0 0; font-size: 12px; color: #fff; font-weight: 500; text-shadow: 0 1px 3px rgba(0,0,0,0.9); white-space: nowrap; overflow: hidden; text-overflow: ellipsis;" title="<?= $titulo_v ?>"><?= $titulo_v ?></p>
            </div>

            <!-- Gaveta Coesiva de Diálogo Comercial (SMS) -->
            <div id="gaveta_<?= $id_anuncio_real ?>" class="gaveta-comentarios">
                <div class="topo-gaveta" style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 10px;">
                    <strong style="font-size: 11px; color: #00d2ff; text-transform: uppercase; letter-spacing: 0.5px;">Comentários Públicos</strong>
                    <span onclick="fecharGavetaComentarios(<?= $id_anuncio_real ?>)" style="cursor: pointer; font-size: 22px; color: #ef4444; font-weight: bold; line-height: 1;">&times;</span>
                </div>
                
                <div style="flex: 1; overflow-y: auto; padding-right: 2px;" id="lista_comentarios_<?= $id_anuncio_real ?>">
                    <?php
                    $q_c = mysqli_query($conexao_link, "SELECT * FROM `comentarios_reels` WHERE id_anuncio = $id_anuncio_real ORDER BY id_comentario ASC");
                    if ($q_c && mysqli_num_rows($q_c) > 0) {
                        while($c = mysqli_fetch_assoc($q_c)) {
                            echo "<div class='comentario-item'><b>" . htmlspecialchars($c['autor_nome']) . ":</b> " . htmlspecialchars($c['mensagem']) . "</div>";
                        }
                    } else {
                        echo "<p style='color: #64748b; font-size: 11px; font-style: italic; padding: 15px; text-align: center;'>Nenhum comentário. Seja o primeiro a interagir!</p>";
                    }
                    ?>
                </div>

                <!-- Formulário de Envio Conectado à Tabela comentarios_reels -->
                <form method="POST" action="video.php" style="display: flex; gap: 6px; margin-top: 10px; background: #0b0f19;">
                    <input type="hidden" name="enviar_comentario_db" value="1">
                    <input type="hidden" name="id_post_coment" value="<?= $id_anuncio_real ?>">
                    <input type="text" name="texto_comentario" placeholder="Escreva aqui..." class="input-msg" style="flex: 1; padding: 9px 14px; background: #070b12; border: 1px solid #334155; border-radius: 20px; color: #fff; font-size: 12px; outline: none; box-sizing: border-box;" required autocomplete="off">
                    <button type="submit" style="background: #00d2ff; color: #0f172a; border: none; padding: 0 14px; border-radius: 20px; font-weight: bold; font-size: 11px; cursor: pointer; text-transform: uppercase;">OK</button>
                </form>
            </div>
        </div>
    <?php 
            endif;
        endforeach;
    endif; 
    ?>
</div>

               
<!-- =========================================================================
     🖥️ SEÇÃO 2: ABA DE VÍDEOS HORIZONTAIS (16:9 - CINEMA/AULAS)
     ========================================================================= -->
<div id="aba_conteudo_horizontal" class="grid-horizontal-cinema">
    <?php 
    $cinemas_exibidos = 0;
    if (!empty($listaReels)):
        foreach ($listaReels as $reel): 
            $id_anuncio_real = intval($reel['id_anuncio']);
            $titulo_v = htmlspecialchars($reel['titulo']);
            $endereco_loja = !empty($reel['endereco_armazem']) ? htmlspecialchars($reel['endereco_armazem']) : 'Huambo, Angola';
            
            if (strlen($titulo_v) >= 22 && $cinemas_exibidos < 6):
                $_SESSION['videos_vistos_feed'][] = $id_anuncio_real;
                $cinemas_exibidos++;
    ?>
        <div class="card-video-cinema">
        <video controls autoplay muted playsinline loop style="width: 100%; height: 100%; object-fit: cover; background: #000000;">
        <source src="<?= htmlspecialchars($reel['imagem'] ?? $r['imagem']) ?>" type="video/mp4">
        <source src="<?= htmlspecialchars($reel['imagem'] ?? $r['imagem']) ?>" type="video/quicktime">
        O seu telemóvel não suporta a reprodução deste vídeo.
    </video>
            <h4 style="color: #00d2ff; margin: 12px 0 4px 0; font-size: 14px; font-weight: bold; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;" title="<?= $titulo_v ?>"><?= $titulo_v ?></h4>
            <span style="color: #22c55e; font-size: 11px; display: block; font-weight: bold;">📍 Local: <?= $endereco_loja ?></span>
            <span style="color: #64748b; font-size: 11px; display: block; margin-top: 2px;">🏢 Empresa: <?= htmlspecialchars($reel['nome_loja'] ?? 'Grupo Aurélius') ?></span>
        </div>
    <?php 
            endif;
        endforeach;
    endif; 
    ?>
</div>

<!-- Módulo do Botão de Embaralhar e Recarregar Feed (Estilo Facebook) -->
<div style="text-align: center; margin: 40px 0 60px 0;">
    <a href="Video.php?reset=1" style="background: rgba(0, 210, 255, 0.05); border: 1px solid #00d2ff; color: #00d2ff; padding: 12px 30px; border-radius: 50px; font-weight: bold; font-size: 12px; text-decoration: none; text-transform: uppercase; letter-spacing: 0.5px; box-shadow: 0 4px 15px rgba(0, 210, 255, 0.1); transition: all 0.3s ease;">🔄 Atualizar e Misturar Feed</a>
</div>

<!-- =========================================================================
     ⚙️ MOTORES JAVASCRIPT DE INTERAÇÃO ASSÍNCRONA E CONTROLE
     ========================================================================= -->
     <script>
let paginaAtualdoFeed = 1;
let carregandoProximosVideos = false;

// 📱 1. AUTO-PLAY & GESTÃO DE MEMÓRIA INTELIGENTE (Intersection Observer)
// Desliga automaticamente vídeos antigos que saíram do ecrã para libertar memória RAM
const observadorDeFocoVideo = new IntersectionObserver((entradas) => {
    entradas.forEach(entrada => {
        const video = entrada.target.querySelector('video');
        if (video) {
            if (entrada.isIntersecting) {
                // Entrou no foco do ecrã: Dá Play nativo instantâneo
                video.play().catch(() => {});
            } else {
                // Saiu do ecrã: Dá Pausa imediatamente para poupar processador e memória
                video.pause();
                video.currentTime = 0; // Reseta o progresso
            }
        }
    });
}, { threshold: 0.6 }); // Requer 60% do card visível para ativar

// Inicializa a monitorização nos cartões carregados pelo PHP
document.querySelectorAll('.reel-card-vertical').forEach(card => observadorDeFocoVideo.observe(card));

// 🔄 2. DETETOR DE ROLAGEM INFINITA (Infinite Scroll ao estilo TikTok)
const containerDeScroll = document.getElementById('aba_conteudo_vertical');
if (containerDeScroll) {
    containerDeScroll.addEventListener('scroll', () => {
        // Se o utilizador chegou perto do fundo da rolagem e não há nenhuma requisição pendente
        if (containerDeScroll.scrollTop + containerDeScroll.clientHeight >= containerDeScroll.scrollHeight - 600) {
            carregarMaisVideosDoServidor();
        }
    });
}


// 📡 ENGINE DE REAÇÃO: Atualiza o número no ecrã e salva no banco de dados em segundo plano
function enviarReacaoAssincrona(idPost, tipoReacao) {
    // 1. Localiza o contador correto no ecrã (Like ou Dislike)
    const spanContador = document.getElementById(tipoReacao === 'adoro' ? 'cont_like_' + idPost : 'cont_dislike_' + idPost);
    if (!spanContador) return;
    
    // 2. Incrementa o número no ecrã na hora (Feedback visual imediato)
    let valorAtual = parseInt(spanContador.innerText) || 0;
    spanContador.innerText = valorAtual + 1;

    // 3. Envia o voto em background para o servidor (Zero refresh / Mantém o utilizador no mesmo vídeo)
    fetch(`Video.php?acao_reacao=${tipoReacao}&id_post=${idPost}`, { method: 'GET' })
    .then(response => {
        if (!response.ok) {
            console.warn("Aviso: O servidor registou a reação com atraso.");
        }
    })
    .catch(err => console.error("Falha de rede ao registar reação assíncrona:", err));
}
function carregarMaisVideosDoServidor() {
    if (carregandoProximosVideos) return;
    carregandoProximosVideos = true;
    paginaAtualdoFeed++;

    // Faz uma requisição assíncrona em background para obter os próximos vídeos estruturados
    fetch(`Video.php?acao=obter_proxima_pagina&page=${paginaAtualdoFeed}`)
    .then(res => res.json())
    .then(dadosNovos => {
        if (dadosNovos && dadosNovos.length > 0) {
            dadosNovos.forEach(reel => {
                // Instancia o novo elemento HTML estruturado dinamicamente
                const novoCard = criarEstruturaDoCardReel(reel);
                containerDeScroll.appendChild(novoCard);
                observadorDeFocoVideo.observe(novoCard); // Vincula o controle de memória ao novo vídeo
            });
            carregandoProximosVideos = false;
        }
    })
    .catch(err => {
        console.warn("Fim do feed ou paginação concluída.");
        carregandoProximosVideos = false;
    });
}

// 🛠️ Função Auxiliar: Cria o Card de Vídeo dinamicamente no Navegador
function criarEstruturaDoCardReel(reel) {
    const div = document.createElement('div');
    div.className = 'reel-card-vertical';
    div.id = `reel-${reel.id_anuncio}`;
    div.setAttribute('data-id', reel.id_anuncio);
    
    div.innerHTML = `

    <video controls autoplay muted playsinline loop style="width: 100%; height: 100%; object-fit: cover; background: #000000;">
    <source src="<?= htmlspecialchars($reel['imagem'] ?? $r['imagem']) ?>" type="video/mp4">
    <source src="<?= htmlspecialchars($reel['imagem'] ?? $r['imagem']) ?>" type="video/quicktime">
    O seu telemóvel não suporta a reprodução deste vídeo.
</video>


        <div class="barra-lateral-acoes">
            <div class="btn-circulo-vivo" onclick="processarReacaoAssincrona(${reel.id_anuncio}, 'adoro')">❤️<span class="txt-cont-viva" id="cont_like_${reel.id_anuncio}">${reel.likes_adoro}</span></div>
            <div class="btn-circulo-vivo" onclick="processarReacaoAssincrona(${reel.id_anuncio}, 'ncurto')">❌</div>
            <div class="btn-circulo-vivo" onclick="abrirGavetaComentarios(${reel.id_anuncio})">💬</div>
        </div>
        <div class="info-overlay-inferior">
            <strong style="color: #00d2ff; font-size: 14px; display: block;">👑 ${reel.nome_loja || 'Parceiro'}</strong>
            <p style="margin: 4px 0 0 0; font-size: 12px; color: #fff;">${reel.titulo}</p>
        </div>
    `;
    return div;
}

// 📡 ENGINE: Reações Assíncronas Rápidas (Like/Dislike) sem travar a navegação
function processarReacaoAssincrona(idPost, tipoReacao) {
    const spanContador = document.getElementById(tipoReacao === 'adoro' ? 'cont_like_' + idPost : 'cont_dislike_' + idPost);
    if (spanContador) {
        let valorAtual = parseInt(spanContador.innerText) || 0;
        spanContador.innerText = valorAtual + 1; // Incremento na hora (Reatividade)
    }
    fetch(`Video.php?acao_reacao=${tipoReacao}&id_post=${idPost}`, { method: 'GET' });
}

function abrirGavetaComentarios(id) {
    const gaveta = document.getElementById('gaveta_' + id);
    if (gaveta) gaveta.style.display = 'flex';
}
function fecharGavetaComentarios(id) {
    const gaveta = document.getElementById('gaveta_' + id);
    if (gaveta) gaveta.style.display = 'none';
}
function gerenciarPlayVideo(v) { v.paused ? v.play() : v.pause(); }
</script>
</body>
</html>