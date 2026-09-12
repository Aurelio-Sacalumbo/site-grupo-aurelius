<?php
// =========================================================================
// 💈 ENGINE DE ESCALAS SAAS PDO - AUDITORIA DE INTERVALO & AUSÊNCIAS (FIXED)
// =========================================================================
header('Content-Type: application/json; charset=utf-8');
date_default_timezone_set('Africa/Luanda');

// Força a exibição de erros ocultos caso falte alguma tabela no seu phpMyAdmin
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

include_once("Conexao.php");

// Garante o objeto PDO instanciado de forma limpa e transparente
if (!isset($pdo)) {
    try {
        $pdo = new PDO("mysql:host=127.0.0.1;dbname=aurelius_salao;charset=utf8", "root", "");
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    } catch (PDOException $e) {
        echo json_encode(['sucesso' => false, 'mensagem' => 'Falha de conexão PDO: ' . $e->getMessage()]);
        exit;
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    
    // Captura e higienização rigorosa dos dados do front-end
    $nome_cliente = isset($_POST['nome_cliente']) ? htmlspecialchars(trim($_POST['nome_cliente']), ENT_QUOTES, 'UTF-8') : 'Cliente Geral';
    $profissional = isset($_POST['funcionario']) ? htmlspecialchars(trim($_POST['funcionario']), ENT_QUOTES, 'UTF-8') : null;
    $data_servico = isset($_POST['data_servico']) ? htmlspecialchars(trim($_POST['data_servico']), ENT_QUOTES, 'UTF-8') : null;
    $hora_servico = isset($_POST['hora_servico']) ? htmlspecialchars(trim($_POST['hora_servico']), ENT_QUOTES, 'UTF-8') : null;
    $preco_tabela = isset($_POST['preco_base']) ? floatval($_POST['preco_base']) : 3000; 
    $servico      = isset($_POST['servico']) ? htmlspecialchars(trim($_POST['servico']), ENT_QUOTES, 'UTF-8') : 'Corte de Adultos';
    $tel_cliente  = isset($_POST['cliente_telefone']) ? htmlspecialchars(trim($_POST['cliente_telefone']), ENT_QUOTES, 'UTF-8') : '900000000';

    if (empty($profissional) || empty($data_servico) || empty($hora_servico)) {
        echo json_encode(['sucesso' => false, 'mensagem' => '🚨 Dados em falta: Selecione o mestre e o horário no formulário do topo.']);
        exit;
    }

    if (strlen($tel_cliente) !== 9 || substr($tel_cliente, 0, 1) !== '9') {
        echo json_encode(['sucesso' => false, 'mensagem' => '❌ O terminal telefónico deve conter exatamente 9 dígitos e começar com o prefixo 9 (Angola).']);
        exit;
    }

    // 🟢 REQUISITO 1: VALIDAÇÃO DE AUSÊNCIA DO PROFISSIONAL NO BANCO DE DADOS
    try {
        $stmt_func = $pdo->prepare("SELECT status_atividade FROM profissionais WHERE nome_profissional = ? OR id = ? LIMIT 1");
        $stmt_func->execute([$profissional, $profissional]);
        $dados_func = $stmt_func->fetch(PDO::FETCH_ASSOC);

        if ($dados_func && ($dados_func['status_atividade'] === 'Ausente' || $dados_func['status_atividade'] === 'Indisponível')) {
            echo json_encode([
                'sucesso' => false,
                'mensagem' => "⚠️ Escala Fechada: O mestre " . htmlspecialchars($profissional) . " encontra-se Ausente ou Indisponível hoje. O sistema barrou a operação para evitar dados falsos."
            ]);
            exit;
        }
    } catch (PDOException $e) {
        // Se a tabela profissionais ainda não tiver sido populada localmente, ignora e segue o fluxo
    }

    // 🟢 REQUISITO 2: ALGORITMO INTEGRADO DE CHOQUE DE HORÁRIOS (1 OPERAÇÃO POR HORA RIGOROSA)
    // Converte a hora solicitada de texto ("22:15") para minutos totais do dia usando os índices corretos do array
    $partes_solicitadas = explode(':', $hora_servico);
    $horas_solicitadas   = isset($partes_solicitadas[0]) ? intval($partes_solicitadas[0]) : 0;
    $minutos_solicitados = isset($partes_solicitadas[1]) ? intval($partes_solicitadas[1]) : 0;
    $minutos_totais_solicitados = ($horas_solicitadas * 60) + $minutos_solicitados;

    try {
        $stmt_check = $pdo->prepare("SELECT hora_servico FROM pagamentos WHERE (profissional = ? OR funcionario = ?) AND data_servico = ?");
        $stmt_check->execute([$profissional, $profissional, $data_servico]);
        $agendamentos_existentes = $stmt_check->fetchAll(PDO::FETCH_ASSOC);

        foreach ($agendamentos_existentes as $agend) {
            if (empty($agend['hora_servico'])) continue;

            $partes_gravadas = explode(':', $agend['hora_servico']);
            $horas_gravadas   = isset($partes_gravadas[0]) ? intval($partes_gravadas[0]) : 0;
            $minutos_gravados = isset($partes_gravadas[1]) ? intval($partes_gravadas[1]) : 0;
            $minutos_totais_gravados = ($horas_gravadas * 60) + $minutos_gravados;

            // Calcula a distância absoluta em minutos na linha do tempo
            $distancia_tempo = abs($minutos_totais_solicitados - $minutos_totais_gravados);

            // ⚠️ BLOQUEIO RÍGIDO: Se a distância for inferior a 60 minutos (50 min corte + 10 min descanso), rejeita
            if ($distancia_tempo < 60) {
                
                // 📊 GERADOR DE HORÁRIOS ALTERNATIVOS LIVRES
                $grade_horarios_padrao = ['08:00', '09:00', '10:00', '11:00', '12:00', '13:00', '14:00', '15:00', '16:00', '17:00', '18:00', '19:00', '20:00', '21:00', '22:00'];
                $sugestoes_livres = [];

                foreach ($grade_horarios_padrao as $h_teste) {
                    $p_teste = explode(':', $h_teste);
                    $mt_teste = (intval($p_teste[0]) * 60) + intval($p_teste[1]);
                    
                    $choque_detectado = false;
                    foreach ($agendamentos_existentes as $a_verificar) {
                        $p_verif = explode(':', $a_verificar['hora_servico']);
                        $mt_verif = (intval($p_verif[0]) * 60) + intval($p_verif[1]);
                        if (abs($mt_teste - $mt_verif) < 60) {
                            $choque_detectado = true;
                            break;
                        }
                    }
                    if (!$choque_detectado) { 
                        $sugestoes_livres[] = $h_teste; 
                    }
                }

                // Isola até 4 sugestões de horários vagos para mostrar no ecrã
                $grade_print = implode(' | ', array_slice($sugestoes_livres, 0, 4));

                echo json_encode([
                    'sucesso' => false,
                    'mensagem' => "❌ Conflito de Escala! O profissional " . htmlspecialchars($profissional) . " já possui um atendimento marcado para as " . $agend['hora_servico'] . ".\n\nRegra Técnica: Máximo de 1 atendimento por hora (50min de serviço + 10min de descanso obrigatório).\n\nGama de Horários Disponíveis para hoje:\n[ " . $grade_print . " ]"
                ]);
                exit;
            }
        }
    } catch (PDOException $e) {
        // Segue o fluxo caso colunas de strings antigas estejam vazias
    }

    // 🟢 REQUISITO 3: RECONHECIMENTO VIP DO HUAMBO COM TELEFONE COMPLETO (PREFIXO 925)
    if (substr($tel_cliente, 0, 3) === '925') {
        $desconto_vip = $preco_tabela * 0.20; // 20% de dedução direta
        $preco_final = $preco_tabela - $desconto_vip;
        $mensagem_caixa = "🚀 CLIENTE VIP RECONHECIDO NO HUAMBO! Desconto de 20% aplicado com sucesso.";
    } else {
        $preco_final = $preco_tabela;
        $mensagem_caixa = "Agendamento aceito e registado com sucesso no sistema Grupo Aurélius!";
    }

    // 🟢 GRAVAÇÃO DEFINITIVA NA SUA TABELA REAL 'PAGAMENTOS'
    try {
        $sql_insert = "INSERT INTO pagamentos (cliente, cliente_telefone, profissional, funcionario, data_servico, horario, hora_servico, servico, valor, data_registro) 
                       VALUES (?, ?, ?, ?, ?, '00:00:00', ?, ?, ?, NOW())";
        
        $stmt_insert = $pdo->prepare($sql_insert);
        $executou = $stmt_insert->execute([$nome_cliente, $tel_cliente, $profissional, $profissional, $data_servico, $hora_servico, $servico, $preco_final]);

        if ($executou) {
            echo json_encode([
                'status' => 'sucesso',
                'sucesso' => true, 
                'mensagem' => $mensagem_caixa,
                'valor_cobrado' => $preco_final,
                'id_pagamento' => $pdo->lastInsertId()
            ]);
        }
    } catch (PDOException $err) {
        // Fallback secundário seguro se a coluna 'cliente_telefone' não existir na estrutura local
        $sql_fb = "INSERT INTO pagamentos (cliente, profesional, funcionario, data_servico, horario, hora_servico, servico, valor, data_registro) VALUES (?, ?, ?, ?, '00:00:00', ?, ?, ?, NOW())";
        
        // Verifica se na sua tabela a coluna se chama 'profisional' com um 's' por erro antigo de sintaxe
        $sql_fb = "INSERT INTO pagamentos (cliente, profissional, funcionario, data_servico, horario, hora_servico, servico, valor, data_registro) VALUES (?, ?, ?, ?, '00:00:00', ?, ?, ?, NOW())";
        
        $stmt_fb = $pdo->prepare($sql_fb);
        $exec_fb = $stmt_fb->execute([$nome_cliente, $profissional, $profissional, $data_servico, $hora_servico, $servico, $preco_final]);

        if ($exec_fb) {
            echo json_encode([
                'status' => 'sucesso',
                'sucesso' => true, 
                'mensagem' => $mensagem_caixa . " (Sincronizado sem coluna de telefone)",
                'valor_cobrado' => $preco_final,
                'id_pagamento' => $pdo->lastInsertId()
            ]);
        } else {
            echo json_encode(['sucesso' => false, 'mensagem' => 'Erro estrutural de banco PDO: ' . $err->getMessage()]);
        }
    }
    exit;
}