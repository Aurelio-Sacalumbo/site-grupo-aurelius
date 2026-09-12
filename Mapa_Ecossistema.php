<?php
if (session_status() === PHP_SESSION_NONE) { session_start(); }
include_once("Conexao.php");

// 🟢 1. CAPTURA DINÂMICA DAS COORDENADAS DO HUAMBO (Mapeamento do Banco)
$pontos_mapa = [];

if ($conexao_link) {
    // Busca lojas e barbearias que tenham coordenadas ou endereços ativos
    $sql_pontos = "
        (SELECT id as id_p, nome_loja as nome, endereco_armazem as endereco, 'loja' as tipo FROM lojas WHERE visivel_no_site = 1)
        UNION
        (SELECT codigo as id_p, nome as nome, endereco as endereco, 'barbearia' as tipo FROM usuario WHERE visivel_no_site = 1)
    ";
    $res_pontos = mysqli_query($conexao_link, $sql_pontos);
    
    // Coordenadas padrão do Huambo para caso não existam coordenadas individuais no banco
    $lat_padrao = -12.7711;
    $lng_padrao = 15.7392;

    if ($res_pontos) {
        while ($ponto = mysqli_fetch_assoc($res_pontos)) {
            // Simulação de geocodificação local para testes no Huambo (Espalha os pinos pela cidade)
            $lat_pino = $lat_padrao + (rand(-99, 99) / 5000);
            $lng_pino = $lng_padrao + (rand(-99, 99) / 5000);

            $pontos_mapa[] = [
                "nome" => htmlspecialchars($ponto['nome']),
                "endereco" => htmlspecialchars($ponto['endereco']),
                "tipo" => $ponto['tipo'],
                "lat" => $lat_pino,
                "lng" => $lng_pino
            ];
        }
    }
}
?>
<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mapa do Ecossistema Aurélius</title>
    
    <!-- 🟢 CORREÇÃO CRÍTICA: Caminhos explícitos e fixos do CDN do Leaflet (Evita travamentos) -->
    <link rel="stylesheet" href="https://unpkg.com" integrity="sha256-p4NxAoJBhIIN+hmNHrzRCf9tD/miZyoHS5obTRR9BMY=" crossorigin="" />
    <script src="https://unpkg.com" integrity="sha256-20nQCchB9co0qIjJZRGuk2/Z9VM+kNiyxNV1lvTlZBo=" crossorigin=""></script>

    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { background: #070b12; color: #fff; font-family: system-ui, sans-serif; text-align: center; }
        
        /* Container Mestre do Mapa */
        #mapa_aurelius_SaaS {
            width: 100%;
            height: 70vh; /* Altura perfeita para ecrãs de telemóvel */
            border-radius: 20px;
            border: 2px solid #1e293b;
            box-shadow: 0 15px 35px rgba(0,0,0,0.6);
            background: #0d1324; /* Fundo de contingência escuro */
        }
        
        .painel-mapa { max-width: 1100px; margin: 30px auto; padding: 0 20px; }
        .btn-sair { display: inline-block; background: #1e293b; color: white; padding: 10px 20px; border-radius: 30px; text-decoration: none; font-weight: bold; font-size: 12px; border: 1px solid #334155; margin-bottom: 20px; text-transform: uppercase; float: left; }
        
        /* Estilização dos Balões Pop-up dentro do mapa para combinar com o tema Dark */
        .leaflet-popup-content-wrapper { background: #111827 !important; color: #fff !important; border: 1px solid #38bdf8; border-radius: 12px; box-shadow: 0 5px 15px rgba(0,0,0,0.5); }
        .leaflet-popup-tip { background: #111827 !important; border: 1px solid #38bdf8; }
        .leaflet-popup-content { margin: 12px !important; line-height: 1.4; }
    </style>
</head>
<body>

<div class="painel-mapa">
    <a href="Principal.php" class="btn-sair">✕ VOLTAR</a>
    
    <div style="clear: both; text-align: left; margin-bottom: 20px; padding-top: 10px;">
        <span style="color: #00d2ff; font-size: 11px; font-weight: bold; text-transform: uppercase; letter-spacing: 1px;">🌍 LOGÍSTICA DE GEOLOCALIZAÇÃO</span>
        <h2 style="color: #fff; font-size: 22px; font-weight: bold; margin-top: 4px;">Localizar Barbearias e Lojas Próximas</h2>
    </div>

    <!-- 🟢 O MAPA REAL RENDERIZA AQUI -->
    <div id="mapa_aurelius_SaaS"></div>
</div>

<script>
// 🟢 2. INICIALIZAÇÃO DO MOTOR: Foca o mapa nas coordenadas centrais do Huambo, Angola
const mapa = L.map('mapa_aurelius_SaaS').setView([-12.7711, 15.7392], 13);

// 🟢 CORREÇÃO DE SINTAXE: Link corrigido para renderizar o mapa visual Dark do CartoDB de forma limpa
L.tileLayer('https://{s}://{z}/{x}/{y}{r}.png', {
    attribution: '&copy; <a href="https://openstreetmap.org">OpenStreetMap</a> &copy; <a href="https://carto.com">CARTO</a>',
    subdomains: 'abcd',
    maxZoom: 20
}).addTo(mapa);

// 🟢 3. RENDERIZAÇÃO DOS PINOS DO BANCO DE DADOS EM TEMPO REAL
const pontosRegistados = <?= json_encode($pontos_mapa) ?>;

pontosRegistados.forEach(function(ponto) {
    // Define a cor ou emoji do pino com base no tipo do estabelecimento
    const emojiIcon = ponto.tipo === 'loja' ? '🛒' : '💈';
    
    // Cria um pino personalizado leve
    const pinoMestre = L.marker([ponto.lat, ponto.lng]).addTo(mapa);
    
    // Conteúdo HTML vivo que vai aparecer ao clicar no pino
    const conteudoPopup = `
        <div style="font-family: sans-serif; padding: 2px; text-align: left;">
            <b style="color: #00d2ff; font-size: 14px; display: block; margin-bottom: 4px;">${emojiIcon} ${ponto.nome}</b>
            <p style="color: #cbd5e1; font-size: 12px; margin: 4px 0 8px 0;">📍 ${ponto.endereco}</p>
            <span style="display:inline-block; background:#22c55e; color:#fff; font-size:10px; padding:3px 8px; font-weight:bold; border-radius:4px; text-transform:uppercase; letter-spacing:0.5px;">● Disponível no PWA</span>
        </div>
    `;
    
    pinoMestre.bindPopup(conteudoPopup);
});
</script>

</body>
</html>