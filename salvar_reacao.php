<?php
// =========================================================================
// 🌐 SALVAR_REACAO.PHP - CONVERSOR MATEMÁTICO DE REAÇÕES REAL (SaaS)
// =========================================================================
header('Content-Type: application/json');

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

include_once("Conexao.php");

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['id_anuncio']) && isset($_POST['tipo_reacao'])) {
    $id_anuncio = intval($_POST['id_anuncio']);
    $tipo_reacao = trim($_POST['tipo_reacao']);

    $coluna = ($tipo_reacao === 'adoro') ? 'likes_adoro' : (($tipo_reacao === 'ncurto') ? 'likes_ncurto' : '');

    if ($id_anuncio > 0 && !empty($coluna) && isset($pdo)) {
        try {
            // 1. Incrementa de forma atómica (+1) a reação na base de dados
            $stmt = $pdo->prepare("UPDATE anuncios SET `$coluna` = `$coluna` + 1 WHERE id_anuncio = ?");
            $stmt->execute([$id_anuncio]);

            // 2. Recapitula todas as métricas da linha para atualizar a tabela consolidada de pontos
            $stmt_recalc = $pdo->prepare("SELECT likes_adoro, likes_ncurto, cliques_agendamento, contagem_partilhas FROM anuncios WHERE id_anuncio = ?");
            $stmt_recalc->execute([$id_anuncio]);
            $res = $stmt_recalc->fetch(PDO::FETCH_ASSOC);

            if ($res) {
                // Algoritmo Real: ❤️=10pts | 👎=2pts | ✂️=25pts | 🔗=15pts
                $total_pts = ($res['likes_adoro'] * 10) + ($res['likes_ncurto'] * 2) + ($res['cliques_agendamento'] * 25) + ($res['contagem_partilhas'] * 15);
                $novo_desconto = min(floor($total_pts / 100) * 5, 35);

                // Persiste a matemática consolidada de pontos de recompensa no MySQL
                $stmt_up = $pdo->prepare("UPDATE anuncios SET pontos_recompensa = ?, percentual_desconto_ganho = ? WHERE id_anuncio = ?");
                $stmt_up->execute([$total_pts, $novo_desconto, $id_anuncio]);

                // Devolve a resposta em formato JSON limpo para o JavaScript ler sem quebras
                echo json_encode([
                    'sucesso' => true, 
                    'novo_total' => intval($res[$coluna]),
                    'novo_total_pontos' => $total_pts
                ]);
                exit;
            }
        } catch (PDOException $e) {
            echo json_encode(['sucesso' => false, 'mensagem' => $e->getMessage()]);
            exit;
        }
    }
}

echo json_encode(['sucesso' => false, 'mensagem' => 'Requisição inválida ou parâmetros ausentes.']);
exit;