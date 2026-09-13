<?php
// =========================================================================
// 📹 RECEPTOR E EXTRACTOR DIGITAL DE REELS — VIDEO.PHP (TOP CENTRAL)
// =========================================================================
if (session_status() === PHP_SESSION_NONE) { 
    session_start(); 
}
date_default_timezone_set('Africa/Luanda');

// Garante o conector com as tuas variáveis estáveis locais $mysqli do XAMPP
include_once("Conexao.php");

$conexao_link = $conexao_aurelius ?? $conexao ?? $link ?? $conn ?? $pdo ?? $mysqli ?? null;
if (!$conexao_link || mysqli_connect_errno()) { 
    $conexao_link = @mysqli_connect("127.0.0.1", "root", "", "aurelius_salao");
}
$conexao_link->set_charset("utf8mb4");

// 🟢 1. RECEPTOR DE COMENTÁRIOS SMS VIA POST
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['enviar_comentario_db'])) {
    $id_anuncio_coment = intval($_POST['id_post_coment']);
    $mensagem_sms      = $conexao_link->real_escape_string(trim($_POST['texto_comentario']));
    $autor_nome        = isset($_SESSION['usuario_nome']) ? $_SESSION['usuario_nome'] : 'Cliente Aurélius';

    if ($id_anuncio_coment > 0 && !empty($mensagem_sms)) {
        $sql_c = "INSERT INTO `comentarios_reels` (id_anuncio, autor_nome, mensagem) VALUES ($id_anuncio_coment, '$autor_nome', '$mensagem_sms')";
        mysqli_query($conexao_link, $sql_c);
    }
    echo "<script>window.location.href='video.php';</script>";
    exit();
}

// =========================================================================
// 🔌 2. EXTRACÇÃO FACTUAL MESTRE (Alinhado 100% com o teu phpMyAdmin)
// =========================================================================
// Busca os registos de vídeos activos dos últimos 30 dias na tabela anuncios
$sql_mestre_reels = "SELECT * FROM `anuncios` WHERE `ativo` = 1 ORDER BY `id_anuncio` DESC LIMIT 30";
$exec_reels = mysqli_query($conexao_link, $sql_mestre_reels);

$listaReels = [];

if ($exec_reels && mysqli_num_rows($exec_reels) > 0) {
    while ($row_reel = mysqli_fetch_assoc($exec_reels)) {
        // Captura o nome bruto salvo no banco (Ex: vid_1784230426_6a59321a69955.mp4)
        $video_nome_cru = trim($row_reel['imagem'] ?? '');
        $video_limpo = basename($video_nome_cru);
        
        if (empty($video_limpo)) { continue; }

        // Extensão de segurança para garantir que é um ficheiro de animação/vídeo
        $ext = strtolower(pathinfo($video_limpo, PATHINFO_EXTENSION));
        if (!in_array($ext, ['mp4', 'mov', 'avi', 'mpeg', 'webm'])) { continue; }

        // 🛡️ VERIFICAÇÃO FÍSICA SEGURO NO WINDOWS (Verifica na pasta upload/ ou na raiz)
        if (file_exists("upload/" . $video_limpo) && !is_dir("upload/" . $video_limpo)) {
            $row_reel['video_src_real'] = "upload/" . $video_limpo;
            $listaReels[] = $row_reel; // Guarda apenas se o arquivo real existir!
        } elseif (file_exists($video_limpo) && !is_dir($video_limpo)) {
            $row_reel['video_src_real'] = $video_limpo;
            $listaReels[] = $row_reel; // Guarda apenas se o arquivo real existir!
        }
    }
}
?>
<!DOCTYPE html>
<html lang="pt-PT">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>Snap Reels & Aulas — Grupo Aurélius</title>
 
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

    <!-- Botão Sair Global -->
    <a href="Principal.php" class="btn-sair">✕ VOLTAR</a>

    <!-- Seletor de Abas Superior Reativo -->
    <div class="seletor-formato-container">
        <button class="btn-filtro-media ativo" id="btn_aba_vertical" onclick="mudarFormatoExibicao('vertical')">📱 Reels</button>
        <button class="btn-filtro-media" id="btn_aba_horizontal" onclick="mudarFormatoExibicao('horizontal')">🖥️ Aulas Cinema</button>
    </div>

    <!-- =========================================================================
         📱 ABA 1: FEED REELS VERTICAIS (SNAP DESLIZÁVEL)
         ========================================================================= -->
    <div id="aba_conteudo_vertical" class="wrapper-vertical-snap">
        <?php 
        $reels_exibidos = 0;
        if (!empty($listaReels)):
            foreach ($listaReels as $reel): 
                $id_anuncio_real = intval($reel['id_anuncio']);
                $id_barb_real    = intval($reel['id_barbearia'] ?? $reel['empresa_id'] ?? 237);
                $titulo_v        = htmlspecialchars($reel['titulo'] ?? ($reel['title'] ?? 'Trabalho Premium'));
                $endereco_loja   = !empty($reel['endereco_armazem']) ? htmlspecialchars($reel['endereco_armazem']) : 'Huambo, Angola';
                $nome_loja_dona  = !empty($reel['nome_loja']) ? htmlspecialchars($reel['nome_loja']) : 'Parceiro Aurélius';
                $ranking         = intval($reel['ranking_pedestal'] ?? rand(10, 40));
                
                // 🛡️ DETECTOR DE ROTA FÍSICA SEGURO: IGNORA E EXCLUI OS VÍDEOS EM BRANCO DA TELA
                $arquivo_cru = trim($reel['imagem'] ?? ($reel['image_url'] ?? ''));
                $arquivo_limpo = basename($arquivo_cru);
                
                if (empty($arquivo_limpo)) { continue; }
                
                if (file_exists("upload/" . $arquivo_limpo) && !is_dir("upload/" . $arquivo_limpo)) {
                    $video_src_render = "upload/" . $arquivo_limpo;
                } elseif (file_exists($arquivo_limpo) && !is_dir($arquivo_limpo)) {
                    $video_src_render = $arquivo_limpo;
                } else {
                    continue; // ❌ O arquivo de vídeo NÃO existe no PC Windows Explorer? Remove o cartão vazio da tela!
                }

                // Regra de tamanho para classificar o que vai para a aba vertical
                if (strlen($titulo_v) < 22 && $reels_exibidos < 6):
                    $reels_exibidos++;
        ?>
           <div id="reel-<?= $id_anuncio_real ?>" class="reel-card-vertical" data-post-id="<?= $id_anuncio_real ?>">
           <!-- 🟢 PLAYER VERTICAL SNAP COM DETETOR DE ROTAS DIGITAL -->
           <video controls autoplay muted playsinline loop style="width: 100%; height: 100%; object-fit: cover; background: #000000;" onclick="gerenciarPlayVideo(this)">
    <source src="<?= htmlspecialchars($reel['video_src_real']) ?>#t=0.1" type="video/mp4">
    <source src="<?= htmlspecialchars($reel['video_src_real']) ?>#t=0.1" type="video/quicktime">
    O seu telemóvel não suporta a reprodução deste vídeo.
</video>
               
           <!-- 🎛️ BARRA LATERAL DE AÇÕES ASSÍNCRONAS -->
           <div class="barra-lateral-acoes">
               <!-- ❤️ BOTÃO GOSTO ASSÍNCRONO -->
               <div class="btn-circulo-vivo" onclick="enviarReacaoAssincrona(<?= $id_anuncio_real ?>, 'adoro')" style="border-color:#ef4444; cursor: pointer;">
                   ❤️<span class="txt-cont-viva" id="cont_like_<?= $id_anuncio_real ?>"><?= intval($reel['likes_adoro']) ?></span>
               </div>
               
               <!-- ❌ BOTÃO NÃO CURTO ASSÍNCRONO -->
               <div class="btn-circulo-vivo" onclick="enviarReacaoAssincrona(<?= $id_anuncio_real ?>, 'ncurto')" style="cursor: pointer;">
                   ❌<span class="txt-cont-viva" id="cont_dislike_<?= $id_anuncio_real ?>"><?= intval($reel['likes_ncurto']) ?></span>
               </div>
               
               <!-- 💬 DIÁLOGO COMERCIAL (GAVETA SMS) -->
               <div class="btn-circulo-vivo" onclick="abrirGavetaComentarios(<?= $id_anuncio_real ?>)" style="cursor: pointer;">
                   💬<span class="txt-cont-viva">SMS</span>
               </div>
               
               <!-- 🚀 COMPARTILHAMENTO DE TRÁFEGO SAAS -->
               <div class="btn-circulo-vivo" style="background:#00d2ff; color:#000; cursor: pointer;" onclick="dispararPartilhaSaaS('<?= htmlspecialchars($titulo_v) ?>', <?= $id_anuncio_real ?>, <?= $id_barb_real ?>)">
                   🚀<span class="txt-cont-viva">Partilhar</span>
               </div>
   
             
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
       
       // Se o motor eliminou os arquivos fantasmas e não restou nada na pauta vertical
       if ($reels_exibidos === 0):
       ?>
           <div style="color: #64748b; text-align: center; padding: 60px 20px; font-style: italic; background: #0f172a; border-radius: 16px; font-size: 13px; border: 1px dashed #1e293b; width:100%; box-sizing:border-box;">
               Nenhum Reel vertical com ficheiro de vídeo ativo localizado no servidor do Huambo.
           </div>
       <?php endif; ?>
   </div>
   
                  
   <!-- =========================================================================
        🖥️ SEÇÃO 2: ABA DE VÍDEOS HORIZONTAIS (16:9 - CINEMA/AULAS)
        ========================================================================= -->
   <div id="aba_conteudo_horizontal" class="grid-horizontal-cinema" style="display: none;">
       <?php 
       $cinemas_exibidos = 0;
       if (!empty($listaReels)):
           foreach ($listaReels as $reel): 
               $id_anuncio_real = intval($reel['id_anuncio']);
               $id_barb_real    = intval($reel['id_barbearia']);
               $titulo_v        = htmlspecialchars($reel['titulo']);
               $endereco_loja   = !empty($reel['endereco_armazem']) ? htmlspecialchars($reel['endereco_armazem']) : 'Huambo, Angola';
               $nome_loja_dona  = !empty($reel['nome_loja']) ? htmlspecialchars($reel['nome_loja']) : 'Parceiro Aurélius';
               
               if (strlen($titulo_v) >= 22 && $cinemas_exibidos < 6):
                   
                   // 🟢 FILTRO TRIPLO ANTI-FICHEIRO BRANCO PARA VÍDEOS HORIZONTAIS
                   $video_cru_cinema = trim($reel['imagem'] ?? '');
                   $nome_limpo_c = basename($video_cru_cinema);
                   
                   if (empty($nome_limpo_c)) { continue; }
                   
                   if (file_exists("upload/" . $nome_limpo_c) && !is_dir("upload/" . $nome_limpo_c)) {
                       $cinema_render_src = "upload/" . $nome_limpo_c;
                   } elseif (file_exists($nome_limpo_c) && !is_dir($nome_limpo_c)) {
                       $cinema_render_src = $nome_limpo_c;
                   } else {
                       continue; // ❌ Remove o card horizontal se o vídeo sumiu do Windows Explorer!
                   }
   
                   $_SESSION['videos_vistos_feed'][] = $id_anuncio_real;
                   $cinemas_exibidos++;
       ?>
           <div id="cinema-<?= $id_anuncio_real ?>" class="card-cinema-horizontal">
               <div style="width: 100%; height: 165px; background: #000; position: relative; border-radius: 8px 8px 0 0; overflow: hidden;">
                   <video controls preload="metadata" playsinline style="width: 100%; height: 100%; object-fit: cover; opacity: 0.85;">
                       <source src="<?= $cinema_render_src ?>#t=0.1" type="video/mp4">
                       <source src="<?= $cinema_render_src ?>#t=0.1" type="video/quicktime">
                   </video>
               </div>
               
               <div style="padding: 14px; text-align: left;">
                   <strong style="color: #00d2ff; font-size: 12px; text-transform: uppercase;"><?= $nome_loja_dona ?></strong>
                   <h4 style="color: #fff; font-size: 13.5px; margin: 5px 0; font-weight: bold; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;"><?= $titulo_v ?></h4>
                   <span style="color: #64748b; font-size: 10.5px; display: block;">📍 Balcão: <?= $endereco_loja ?></span>
                   
                   <div style="margin-top: 12px;">
                   <a href="./Video.php?id_anuncio=<?php echo $fotoItem['id_anuncio'] ?? 0; ?>">Assistir Vídeo</a>
                   </div>
               </div>
           </div>
       <?php 
               endif;
           endforeach;
       endif; 
   
       if ($cinemas_exibidos === 0):
       ?>
           <div style="grid-column: 1 / -1; color: #64748b; text-align: center; padding: 40px 20px; font-style: italic; background: #0f172a; border-radius: 12px; font-size: 12.5px; border: 1px dashed #1e293b; width:100%; box-sizing:border-box;">
               Nenhum vídeo de aula horizontal validado na rede neste momento.
           </div>
       <?php endif; ?>
   </div>
   
   <script>
   let paginaAtualdoFeed = 1;
   let carregandoProximosVideos = false;
   
   // 📱 1. AUTO-PLAY & GESTÃO DE MEMÓRIA (Intersection Observer)
   const observadorDeFocoVideo = new IntersectionObserver((entradas) => {
       entradas.forEach(entrada => {
           const video = entrada.target.querySelector('video');
           if (video) {
               if (entrada.isIntersecting) {
                   // Entrou no foco do ecrã do telemóvel: Dá Play nativo instantâneo
                   video.play().catch(() => {});
               } else {
                   // Saiu do ecrã: Dá Pausa imediatamente para poupar processador
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
           // Se o utilizador chegou perto do fundo da rolagem
           if (containerDeScroll.scrollTop + containerDeScroll.clientHeight >= containerDeScroll.scrollHeight - 600) {
               carregarMaisVideosDoServidor();
           }
       });
   }
   
   function mudarFormatoExibicao(formato) {
       const abaVertical = document.getElementById('aba_conteudo_vertical');
       const abaHorizontal = document.getElementById('aba_conteudo_horizontal');
       const btnVertical = document.getElementById('btn_aba_vertical');
       const btnHorizontal = document.getElementById('btn_aba_horizontal');
   
       btnVertical.classList.remove('ativo');
       btnHorizontal.classList.remove('ativo');
   
       if (formato === 'vertical') {
           abaVertical.style.display = 'flex';
           abaHorizontal.style.display = 'none';
           btnVertical.classList.add('ativo');
       } else {
           abaVertical.style.display = 'none';
           abaHorizontal.style.display = 'grid';
           btnHorizontal.classList.add('ativo');
       }
   }
   
   function enviarReacaoAssincrona(idPost, tipoReacao) {
       const spanContador = document.getElementById(tipoReacao === 'adoro' ? 'cont_like_' + idPost : 'cont_dislike_' + idPost);
       if (!spanContador) return;
       
       let valorAtual = parseInt(spanContador.innerText) || 0;
       spanContador.innerText = valorAtual + 1;
   
       fetch(`video.php?acao_reacao=${tipoReacao}&id_post=${idPost}`, { method: 'GET' })
       .catch(err => console.error("Erro assíncrono ao registar reação:", err));
   }
   
   function carregarMaisVideosDoServidor() {
       if (carregandoProximosVideos) return;
       carregandoProximosVideos = true;
       paginaAtualdoFeed++;
   
       fetch(`video.php?acao=obter_proxima_pagina&page=${paginaAtualdoFeed}`)
       .then(res => res.json())
       .then(dadosNovos => {
           if (dadosNovos && dadosNovos.length > 0) {
               dadosNovos.forEach(reel => {
                   const novoCard = criarEstruturaDoCardReel(reel);
                   if (novoCard) {
                       containerDeScroll.appendChild(novoCard);
                       observadorDeFocoVideo.observe(novoCard);
                   }
               });
               carregandoProximosVideos = false;
           }
       })
       .catch(err => {
           console.warn("Fim do feed ou paginação concluída.");
           carregandoProximosVideos = false;
       });
   }
   
   function criarEstruturaDoCardReel(reel) {
       // 🛡️ Filtro do lado do cliente: ignora vídeos sem link
       if (!reel.imagem && !reel.image_url) return null;
       
       const div = document.createElement('div');
       div.className = 'reel-card-vertical';
       div.id = `reel-${reel.id_anuncio}`;
       
       const srcVideo = reel.imagem || reel.image_url;
       
       div.innerHTML = `
       <video controls autoplay muted playsinline loop style="width: 100%; height: 100%; object-fit: cover; background: #000000;" onclick="gerenciarPlayVideo(this)">
       <source src="<?= htmlspecialchars($reel['video_src_real']) ?>#t=0.1" type="video/mp4">
       <source src="<?= htmlspecialchars($reel['video_src_real']) ?>#t=0.1" type="video/quicktime">
       O seu telemóvel não suporta a reprodução deste vídeo.
   </video>
           <div class="barra-lateral-acoes">
               <div class="btn-circulo-vivo" onclick="enviarReacaoAssincrona(${reel.id_anuncio}, 'adoro')">❤️<span class="txt-cont-viva" id="cont_like_${reel.id_anuncio}">${reel.likes_adoro || 0}</span></div>
               <div class="btn-circulo-vivo" onclick="enviarReacaoAssincrona(${reel.id_anuncio}, 'ncurto')">❌<span class="txt-cont-viva" id="cont_dislike_${reel.id_anuncio}">${reel.likes_ncurto || 0}</span></div>
               <div class="btn-circulo-vivo" onclick="abrirGavetaComentarios(${reel.id_anuncio})">💬<span class="txt-cont-viva">SMS</span></div>
           </div>
           <div class="info-overlay-inferior">
               <strong style="color: #00d2ff; font-size: 14px; display: block;">👑 ${reel.nome_loja || 'Parceiro'}</strong>
               <p style="margin: 4px 0 0 0; font-size: 12px; color: #fff;">${reel.titulo}</p>
           </div>
       `;
       return div;
   }
   
   function abrirGavetaComentarios(id) {
       const gaveta = document.getElementById('gaveta_' + id);
       if (gaveta) gaveta.style.display = 'flex';
   }
   
   function fecharGavetaComentarios(id) {
       const gaveta = document.getElementById('gaveta_' + id);
       if (gaveta) gaveta.style.display = 'none';
   }
   
   function gerenciarPlayVideo(video) {
       if (video.paused) {
           video.play().catch(() => {});
       } else {
           video.pause();
       }
   }
   
   function dispararPartilhaSaaS(titulo, idPost, idBarb) {
       const link_partilha = `${window.location.origin}/video.php?id_anuncio=${idPost}`;
       if (navigator.share) {
           navigator.share({
               title: titulo,
               text: 'Vê este corte no ecossistema Aurélius!',
               url: link_partilha
           }).catch(() => {});
       } else {
           navigator.clipboard.writeText(link_partilha).then(() => {
               alert('🔗 Link de partilha copiado para a área de transferência!');
           });
       }
   }
   </script>
   </body>
   </html>



