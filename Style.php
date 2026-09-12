<?php
// LINHA 2: ESTA DEVE SER A PRIMEIRA COISA DO ARQUIVO!
require_once "config/Banco.php";

// Definição do fuso horário para bater com o Huambo/Angola
date_default_timezone_set('Africa/Luanda');
$dataHoje = date('Y-m-d');

try {
    // 1. BUSCA OS PROFISSIONAIS PARA O SELECT E PARA A GRADE
    $queryFunc = $pdo->query("SELECT id_funcionario, nome, specialty AS especialidade FROM funcionarios");
    $listaFuncionarios = $queryFunc->fetchAll();

    // 2. AUTOMATIZAÇÃO DO CARTÃO 1: CALCULA O CAIXA DO DIA
    $queryCaixa = $pdo->prepare("SELECT SUM(valor) as total_dia FROM pagamentos WHERE data_servico = :hoje");
    $queryCaixa->execute([':hoje' => $dataHoje]);
    $resultadoCaixa = $queryCaixa->fetch();
    $caixaDoDia = $resultadoCaixa['total_dia'] ?? 0;

    // 3. AUTOMATIZAÇÃO DO CARTÃO 2: CONTA QUANTOS ATENDIMENTOS HOJE
    $querySessoes = $pdo->prepare("SELECT COUNT(*) as total_sessoes FROM pagamentos WHERE data_servico = :hoje");
    $querySessoes->execute([':hoje' => $dataHoje]);
    $resultadoSessoes = $querySessoes->fetch();
    $sessoesHoje = $resultadoSessoes['total_sessoes'] ?? 0;

    // 4. AUTOMATIZAÇÃO DO CARTÃO 3: CONTA QUANTOS PROFISSIONAIS EXISTEM
    $totalProfissionais = count($listaFuncionarios);

    // 5. BUSCA AS FOTOS SALVAS NA GALERIA
    $queryGaleria = $pdo->query("SELECT titulo, imagem FROM anuncios ORDER BY id_anuncio DESC"); 
    $listaFotos = $queryGaleria->fetchAll();

} catch (PDOException $e) {
    $caixaDoDia = 0;
    $sessoesHoje = 0;
    $totalProfissionais = 8; // Alinhado com a tua imagem que mostra 8 Profissionais
    $listaFuncionarios = [];
    $listaFotos = [];
}
?>
<!DOCTYPE html>
<html lang="pt-PT">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Aurelius - Dashboard de Atendimento</title>
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; font-family: sans-serif; }
        body { background-color: #0b1a30; color: #ffffff; padding-bottom: 80px; }
        
        /* Menu Superior */
        nav { display: flex; justify-content: space-between; align-items: center; background-color: #e0e0e0; padding: 12px 40px; position: relative; z-index: 1000; }
        .logo { color: #d32f2f; cursor: pointer; }
        .logo h1 { font-size: 22px; font-weight: bold; line-height: 1; }
        .logo h6 { color: #0b1a30; font-size: 11px; margin-top: -2px; font-weight: bold; }
        
        .ul { display: flex; align-items: center; list-style: none; gap: 10px; }
        .ul li a { display: block; background-color: #0088cc; color: white; padding: 10px 18px; text-decoration: none; border-radius: 12px; font-size: 13px; font-weight: bold; text-align: center; border: 1px solid #006699; white-space: nowrap; }
        .ul li a:hover { background-color: #006699; }
        
        /* Contentores Principais */
        .container { max-width: 1200px; margin: 20px auto; padding: 0 15px; }
        .painel-azul { background-color: #1e3a8a; border: 2px dashed #0088cc; border-radius: 15px; padding: 25px; margin-bottom: 20px; }
        .painel-titulo { font-size: 16px; font-weight: bold; margin-bottom: 15px; display: block; color: #fff; letter-spacing: 0.5px; }
        
        /* Grid de Formulários */
        .grid-inputs { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 15px; }
        .input-estilizado { width: 100%; padding: 12px; border: 1px solid #ccc; border-radius: 8px; font-size: 14px; color: #333; background-color: #fff; }
        
        /* Bloco Freemium */
        .flex-freemium { display: flex; justify-content: space-between; align-items: center; background-color: #1d4ed8; padding: 20px; border-radius: 12px; margin-bottom: 25px; }
        .info-freemium h4 { font-size: 16px; font-weight: bold; color: #fff; }
        .info-freemium p { font-size: 13px; color: #cbd5e1; margin-top: 4px; }
        .badge-premium { background-color: #ffcc00; color: #000; padding: 4px 8px; font-weight: bold; font-size: 12px; border-radius: 4px; float: right; }
        .btn-upgrade { background-color: #ff9900; color: white; border: none; padding: 10px 20px; font-weight: bold; border-radius: 5px; cursor: pointer; margin-top: 10px; transition: 0.2s; }
        .btn-upgrade:hover { background-color: #e08800; }

        /* Cartões de Indicadores */
        .grid-indicadores { display: grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap: 20px; margin-bottom: 25px; }
        .card-indicador { background-color: #0f172a; border-radius: 8px; padding: 20px; border-left: 4px solid #fff; }
        .card-indicador.caixa { border-left-color: #22c55e; }
        .card-indicador.sessoes { border-left-color: #3b82f6; }
        .card-indicador.equipa { border-left-color: #f59e0b; }
        .card-indicador label { font-size: 11px; text-transform: uppercase; color: #94a3b8; font-weight: bold; display: block; margin-bottom: 5px; }
        .card-indicador .valor { font-size: 26px; font-weight: bold; }
        .card-indicador .subtexto { font-size: 11px; color: #64748b; margin-top: 4px; }

        /* Abas de Categorias e Itens */
        .aba-conteudo { display: none; }
        .aba-conteudo.active { display: block; }
        .grid-categorias, .grid-container { display: grid; grid-template-columns: repeat(auto-fit, minmax(150px, 1fr)); gap: 15px; margin-top: 15px; }
        
        .aba-item { background-color: #1e293b; border: 1px solid #334155; border-radius: 10px; color: white; padding: 15px; cursor: pointer; text-align: center; transition: 0.2s; }
        .aba-item:hover { background-color: #334155; border-color: #0088cc; transform: translateY(-2px); }
        .aba-item img { border-radius: 6px; margin-bottom: 8px; object-fit: cover; height: 110px; width: 100%; background-color: #334155; }
        
        /* Caixa de Confirmação de Preço */
        .preco-container { background-color: #0f172a; border: 2px dashed #22c55e; border-radius: 10px; padding: 20px; text-align: center; margin-top: 20px; display: none; }
        .preco-container h3 { margin-bottom: 5px; font-size: 18px; color: #fff; }
        .preco-container p { font-size: 28px; font-weight: bold; color: #22c55e; margin-bottom: 15px; }
        .btn-confirmar { background-color: #22c55e; color: white; border: none; padding: 14px 35px; font-size: 16px; font-weight: bold; border-radius: 6px; cursor: pointer; width: 100%; max-width: 350px; transition: 0.2s; }
        .btn-confirmar:hover { background-color: #16a34a; }
        .btn-voltar { background-color: #475569; color: white; border: none; padding: 8px 16px; border-radius: 6px; cursor: pointer; margin-bottom: 15px; font-weight: bold; }

        /* Rodapé e Consentimento */
        footer { margin-top: 40px; text-align: center; font-size: 13px; color: #94a3b8; border-top: 1px dashed #334155; padding-top: 20px; }
        .banner-consentimento { position: fixed; bottom: 0; left: 0; right: 0; background-color: #16a34a; padding: 12px 40px; display: flex; justify-content: space-between; align-items: center; font-size: 12px; color: white; z-index: 9999; font-weight: bold; }
        .btn-aceitar { background-color: #fff; color: #16a34a; border: none; padding: 8px 18px; font-weight: bold; border-radius: 6px; cursor: pointer; }

        @media (max-width: 900px) {
            nav { padding: 10px 20px; }
            .ul { display: none; }
        }
    </style>
</head>
<body>

    <!-- MENUBAR SUPERIOR (Identêntico à imagem) -->
    <nav>
        <div class="logo">
            <h1>AURELIUS</h1>
            <h6>Salão de Beleza e Barbearia</h6>
        </div>
        <ul class="ul">
            <li><a href="#">Sair</a></li>
            <li><a href="#">Histórico</a></li>
            <li><a href="#secao-categorias">Serviços</a></li>
            <li><a href="#">Photos</a></li>
            <li><a href="#">Funcionários</a></li>
            <li><a href="#">Termos & Privacidade</a></li>
        </ul>
    </nav>

    <!-- FORMULÁRIO PRINCIPAL UNIFICADO -->
    <form action="" method="POST" id="form-fluxo-atendimento">
        
        <!-- INPUTS ESCONDIDOS DO PROCESSO DINÂMICO -->
        <input type="hidden" name="servico_nome" id="input-servico-nome">
        <input type="hidden" name="servico_preco" id="input-servico-preco">

        <div class="container">
            
            <!-- BLOCÃO 1: DADOS DE ATENDIMENTO DA SESSÃO (Réplica exata da imagem) -->
            <div class="painel-azul">
                <span class="painel-titulo">Dados de Atendimento da Sessão</span>
                <div class="grid-inputs">
                    <input type="text" name="nome_cliente" class="input-estilizado" placeholder="Nome do Cliente (Obrigatório)" required>
                    
                    <select name="id_funcionario" class="input-estilizado" required>
                        <option value="">Selecione um profissional...</option>
                        <option value="">profissional...</option>
                        <option value="">Selecione</option>
                        <?php foreach ($listaFuncionarios as $func): ?>
                            <option value="<?php echo $func['id_funcionario']; ?>">
                                <?php echo htmlspecialchars($func['nome'] . " (" . $func['especialidade'] . ")"); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>

                    <input type="date" name="data_servico" class="input-estilizado" value="<?php echo $dataHoje; ?>" required>
                    <input type="time" name="hora_servico" class="input-estilizado" value="<?php echo date('H:i'); ?>" required>
                    </div>
                    <div style="font-size: 11px; color: #94a3b8; margin-top: 15px;">Nenhuma foto carregada na galeria ainda.</div>
                </div>
    
                <!-- BLOCÃO 2: CONTA GRÁTIS / ACESSO FREEMIUM -->
                <div class="flex-freemium" style="background-color: #1d4ed8; padding: 20px; border-radius: 12px; margin-bottom: 25px;">
                    <div class="info-freemium" style="width: 100%;">
                        <span class="badge-premium" style="background-color: #ffcc00; color: #000; padding: 4px 8px; font-weight: bold; font-size: 12px; border-radius: 4px; float: right;">CONTA GRÁTIS</span>
                        <h4 style="font-size: 16px; font-weight: bold; color: #fff;">Acesso Freemium Ativo</h4>
                        <p style="font-size: 13px; color: #cbd5e1; margin-top: 4px;">Subscreva o plano Premium para ocultar os anúncios e desbloquear relatórios completos.</p>
                        <button type="button" class="btn-upgrade">Seja Premium - 2.500 Kz/mês</button>
                    </div>
                </div>
    
                <!-- BLOCÃO 3: CARTÕES INDICADORES DE CAIXA -->
                <div class="grid-container" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap: 20px; margin-bottom: 25px;">
                    <div class="aba-item" style="background-color: #0f172a; border: none; border-left: 4px solid #22c55e; padding: 20px; text-align: left; cursor: default;">
                        <label style="font-size: 11px; text-transform: uppercase; color: #94a3b8; font-weight: bold; display: block; margin-bottom: 5px;">CAIXA DO DIA</label>
                        <div style="font-size: 26px; font-weight: bold; color: #22c55e;"><?php echo number_format($caixaDoDia, 2, ',', '.') . " Kz"; ?></div>
                        <div style="font-size: 11px; color: #64748b; margin-top: 4px;">Atualizado automaticamente</div>
                    </div>
                    <div class="aba-item" style="background-color: #0f172a; border: none; border-left: 4px solid #3b82f6; padding: 20px; text-align: left; cursor: default;">
                        <label style="font-size: 11px; text-transform: uppercase; color: #94a3b8; font-weight: bold; display: block; margin-bottom: 5px;">ATENDIMENTOS HOJE</label>
                        <div style="font-size: 26px; font-weight: bold; color: #3b82f6;"><?php echo $sessoesHoje; ?> Sessões</div>
                        <div style="font-size: 11px; color: #64748b; margin-top: 4px;">Controle de Fuso no Huambo</div>
                    </div>
                    <div class="aba-item" style="background-color: #0f172a; border: none; border-left: 4px solid #f59e0b; padding: 20px; text-align: left; cursor: default;">
                        <label style="font-size: 11px; text-transform: uppercase; color: #94a3b8; font-weight: bold; display: block; margin-bottom: 5px;">EQUIPA ATIVA</label>
                        <div style="font-size: 26px; font-weight: bold; color: #f59e0b;"><?php echo $totalProfissionais; ?> Profissionais</div>
                        <div style="font-size: 11px; color: #64748b; margin-top: 4px;">Buscado da banca de dados</div>
                    </div>
                </div>
    
                <!-- BLOCÃO 4: AS CATEGORIAS ATIVAS -->
                <div class="painel-azul" id="secao-categorias">
                    <span class="painel-titulo">Selecione uma Categoria abaixo para ver os Serviços:</span>
                    <div class="grid-categorias">
                        <div class="aba-item" onclick="alternarCategoria('cat-cabelo')">
                            <img src="img/categoria-cabelo.jpg" alt="Cabelo" onerror="this.src='https://placehold.co'">
                            <strong>Cabelo</strong>
                        </div>
                        <div class="aba-item" onclick="alternarCategoria('cat-barba')">
                            <img src="img/categoria-barba.jpg" alt="Barba" onerror="this.src='https://placehold.co'">
                            <strong>Barba</strong>
                        </div>
                        <div class="aba-item" onclick="alternarCategoria('cat-estetica')">
                            <img src="img/categoria-estetica.jpg" alt="Estética" onerror="this.src='https://placehold.co'">
                            <strong>Estética</strong>
                        </div>
                    </div>
                </div>
    
                <!-- CONTEÚDO DINÂMICO DE CADA CATEGORIA -->
                <div id="cat-cabelo" class="aba-conteudo painel-azul">
                    <button type="button" class="btn-voltar" onclick="fecharCategoria()">← Voltar às Categorias</button>
                    <span class="painel-titulo">Serviços de Cabelo</span>
                    <div class="grid-container">
                        <div class="aba-item" onclick="selecionarServico('Corte Masculino', 3000)">
                            <img src="img/corte-masc.jpg" onerror="this.src='https://placehold.co'">
                            <h5>Corte Masculino</h5>
                            <small>3.000 Kz</small>
                        </div>
                        <div class="aba-item" onclick="selecionarServico('Lavagem e Secagem', 1500)">
                            <img src="img/lavagem.jpg" onerror="this.src='https://placehold.co'">
                            <h5>Lavagem & Secagem</h5>
                            <small>1.500 Kz</small>
                        </div>
                    </div>
                </div>
    
                <div id="cat-barba" class="aba-conteudo painel-azul">
                    <button type="button" class="btn-voltar" onclick="fecharCategoria()">← Voltar às Categorias</button>
                    <span class="painel-titulo">Serviços de Barba</span>
                    <div class="grid-container">
                        <div class="aba-item" onclick="selecionarServico('Barba Simples', 2000)">
                            <img src="img/barba.jpg" onerror="this.src='https://placehold.co+Simples'">
                            <h5>Barba Simples</h5>
                            <small>2.000 Kz</small>
                        </div>
                        <div class="aba-item" onclick="selecionarServico('Barba Terapia', 4500)">
                            <img src="img/barbaterapia.jpg" onerror="this.src='https://placehold.co+Terapia'">
                            <h5>Barba Terapia</h5>
                            <small>4.500 Kz</small>
                        </div>
                    </div>
                </div>
    
                <div id="cat-estetica" class="aba-conteudo painel-azul">
                    <button type="button" class="btn-voltar" onclick="fecharCategoria()">← Voltar às Categorias</button>
                    <span class="painel-titulo">Serviços de Estética</span>
                    <div class="grid-container">
                        <div class="aba-item" onclick="selecionarServico('Limpeza de Pele', 6000)">
                            <img src="img/limpeza.jpg" onerror="this.src='https://placehold.co'">
                            <h5>Limpeza de Pele</h5>
                            <small>6.000 Kz</small>
                        </div>
                    </div>
                </div>
    
                <!-- BLOCÃO 5: CAIXA DE CONFIRMAÇÃO DE PREÇO -->
                <div class="preco-container" id="caixa-confirmacao">
                    <h3 id="nome-servico-selecionado">Nenhum serviço selecionado</h3>
                    <p id="preco-servico-selecionado">0 Kz</p>
                    <button type="submit" name="gravar_atendimento" class="btn-confirmar">Finalizar e Gravar no Caixa</button>
                </div>
    
            </div>
        </form>
    
        <!-- PROCESSAMENTO BACKEND PHP -->
        <?php
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['gravar_atendimento'])) {
            $cliente = $_POST['nome_cliente'] ?? '';
            $idFuncionario = $_POST['id_funcionario'] ?? null;
            $dataServico = $_POST['data_servico'] ?? $dataHoje;
            $servico = $_POST['servico_nome'] ?? '';
            $valor = $_POST['servico_preco'] ?? 0;
    
            if (!empty($cliente) && !empty($idFuncionario) && !empty($servico)) {
                try {
                    $stmt = $pdo->prepare("INSERT INTO pagamentos (id_funcionario, valor, data_servico, descricao) VALUES (:id_func, :valor, :data_s, :desc)");
                    $stmt->execute([
                        ':id_func' => $idFuncionario,
                        ':valor'   => $valor,
                        ':data_s'  => $dataServico,
                        ':desc'    => "Cliente: " . $cliente . " | Serviço: " . $servico
                    ]);
    
                    echo "<script>alert('Sucesso! Atendimento salvo no caixa.'); window.location.href=window.location.pathname;</script>";
                } catch (PDOException $e) {
                    echo "<script>alert('Erro no banco de dados: " . addslashes($e->getMessage()) . "');</script>";
                }
            } else {
                echo "<script>alert('Por favor, certifique-se de preencher o nome do cliente, escolher o profissional e selecionar o serviço.');</script>";
            }
        }
        ?>
    
        <!-- RODAPÉ -->
        <footer class="container">
            <p>O Grupo Aurelius, Salão de Beleza e Barbearia recolhe dados estatísticos de navegação para fins publicitários e análise de tendências no Huambo, Angola.</p>
        </footer>
    
        <!-- BANNER DE RASTREIO -->
        <div class="banner-consentimento" id="cookie-banner">
            <span>O Grupo Aurelius recolhe dados estatísticos para fins analíticos em Angola.</span>
            <button class="btn-aceitar" onclick="document.getElementById('cookie-banner').style.display='none';">Aceitar e Permitir Rastreamento</button>
        </div>
    
        <!-- JAVASCRIPT DE CONTROLO DE INTERAÇÃO -->
        <script>
            function alternarCategoria(idCategoria) {
                document.querySelectorAll('.aba-conteudo').forEach(aba => {
                    aba.classList.remove('active');
                });
                document.getElementById('secao-categorias').style.display = 'none';
                
                const categoriaAlvo = document.getElementById(idCategoria);
                if (categoriaAlvo) {
                    categoriaAlvo.classList.add('active');
                    categoriaAlvo.scrollIntoView({ behavior: 'smooth' });
                }
            }
    
            function fecharCategoria() {
                document.querySelectorAll('.aba-conteudo').forEach(aba => {
                    aba.classList.remove('active');
                });
                document.getElementById('secao-categorias').style.display = 'block';
                document.getElementById('caixa-confirmacao').style.display = 'none';
            }
    
            function selecionarServico(nome, preco) {
                const caixa = document.getElementById('caixa-confirmacao');
                
                document.getElementById('nome-servico-selecionado').innerText = nome;
                document.getElementById('preco-servico-selecionado').innerText = preco.toLocaleString('pt-AO') + " Kz";
                
                document.getElementById('input-servico-nome').value = nome;
                document.getElementById('input-servico-preco').value = preco;
                
                caixa.style.display = 'block';
                caixa.scrollIntoView({ behavior: 'smooth' });
            }
        </script>
    </body>
    </html>
            