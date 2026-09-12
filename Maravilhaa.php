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

    // 5. BUSCA AS FOTOS SALVAS NA GALERIA (Agora na posição correta, abaixo do $pdo)
    $queryGaleria = $pdo->query("SELECT titulo, imagem FROM anuncios ORDER BY id_anuncio DESC"); 
    $listaFotos = $queryGaleria->fetchAll();

} catch (PDOException $e) {
    // Caso o banco falhe, define valores padrão seguros para não travar o sistema
    $caixaDoDia = 0;
    $sessoesHoje = 0;
    $totalProfissionais = 7;
    $listaFuncionarios = [];
    $listaFotos = [];
}
?>
<html lang="pt-PT">
<head>
    <meta charset="UTF-8">
    <title>Aurelius - Salão de Beleza e Barbearia</title>
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; font-family: sans-serif; }
        body { background-color: #0b1a30; color: #ffffff; padding-bottom: 80px; }
        
        /* Menu Superior */
        nav { display: flex; justify-content: space-between; align-items: center; background-color: #e0e0e0; padding: 10px 20px; }
        /* --- REGRAS DE ADAPTAÇÃO PARA TELEMÓVEL --- */
@media (max-width: 900px) {
    /* Esconde os botões azuis grandes do topo */
    #menuDesktop {
        display: flex;
        list-style: none;
        gap: 15px; /* Espaço entre os botões */
        margin-right: 20px; /* Alasta o último botão da borda direita */
    }
    /* Mostra o ícone de 3 barras no canto direito */
    .Menu-Icon {
        display: block !important;
    }
}
        .logo { color: #d32f2f; cursor: pointer; }
        .logo h6 { color: #0b1a30; font-size: 11px; margin-top: -2px; }
        .ul {
            display: flex;
            align-items: center;
            list-style: none;
            margin: 0;
            padding: 0;
            margin-right: 25px; /* 👈 Cria o afastamento da borda direita da tela */
            gap: 10px;          /* 👈 Controla o espaço entre os botões para não esmagar */
        }
        
        .ul li {
            list-style: none;
            margin: 0;
        }
        
        .ul li a { 
            display: block; 
            background-color: #0088cc; 
            color: white; 
            padding: 10px 15px; /* 👈 Reduzido ligeiramente para caber melhor em ecrãs menores */
            text-decoration: none; 
            border-radius: 12px; 
            font-size: 13px; 
            font-weight: bold; 
            text-align: center; 
            min-width: 100px;   /* 👈 Ajustado de 110px para 100px para dar mais folga */
            border: 1px solid #006699; 
            white-space: nowrap; /* 👈 Evita que o texto quebre em duas linhas dentro do botão */
        }
        
        /* Conteedores Principais */
        .container { max-width: 1200px; margin: 20px auto; padding: 0 15px; }
        .painel-azul { background-color: #21409a; border: 2px dashed #0088cc; border-radius: 15px; padding: 20px; margin-bottom: 20px; }
        .painel-titulo { font-size: 16px; font-weight: bold; margin-bottom: 12px; display: block; color: #fff; }
        
        /* Grid de Inputs do Topo */
        .grid-inputs { display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 15px; }
        .input-estilizado { width: 100%; padding: 12px; border: 1px solid #ccc; border-radius: 8px; font-size: 14px; color: #333; background-color: #fff; }
        
        /* Bloco Conta Grátis */
        .flex-freemium { display: flex; justify-content: space-between; align-items: flex-start; }
        .info-freemium p { font-size: 13px; color: #cbd5e1; margin-top: 4px; }
        .badge-premium { background-color: #ffcc00; color: #000; padding: 4px 8px; font-weight: bold; font-size: 12px; border-radius: 4px; }
        .btn-upgrade { background-color: #ff9900; color: white; border: none; padding: 10px 20px; font-weight: bold; border-radius: 5px; cursor: pointer; margin-top: 10px; }
        @media print {
    body * { display: none !important; }
    #area-impressao-global, #area-impressao-global * { display: block !important; background: #fff !important; color: #000 !important; }
}
        /* Abas de Categorias e Itens */
        .aba-conteudo { display: none; }
        .aba-conteudo.active { display: block; }
        .grid-categorias, .grid-container { display: grid; grid-template-columns: repeat(auto-fit, minmax(140px, 1fr)); gap: 15px; margin-top: 15px; }
        
        .aba-item { background-color: #1e293b; border: 1px solid #334155; border-radius: 10px; color: white; padding: 15px; cursor: pointer; text-align: center; transition: 0.2s; }
        .aba-item:hover { background-color: #334155; }
        .aba-item img { border-radius: 6px; margin-bottom: 8px; object-fit: cover; height: 110px; width: 100%; }
        
        /* Caixa de Confirmação de Preço */
        .preco-container { background-color: #0f172a; border: 2px dashed #22c55e; border-radius: 10px; padding: 20px; text-align: center; margin-top: 20px; }
        .preco-container h3 { margin-bottom: 5px; font-size: 18px; }
        .preco-container p { font-size: 28px; font-weight: bold; color: #22c55e; margin-bottom: 15px; }
        .btn-confirmar { background-color: #22c55e; color: white; border: none; padding: 12px 35px; font-size: 16px; font-weight: bold; border-radius: 6px; cursor: pointer; width: 100%; max-width: 300px; }
        .btn-voltar { background-color: #475569; color: white; border: none; padding: 8px 16px; border-radius: 6px; cursor: pointer; margin-bottom: 15px; font-weight: bold; }
        
        /* Rodapé e Parcerias */
        .secao-parcerias { text-align: center; margin: 25px 0; font-size: 12px; color: #94a3b8; border-top: 1px dashed #334155; padding-top: 15px; }
        .lista-parceiros { display: flex; justify-content: center; gap: 20px; margin-top: 8px; font-style: italic; }
        .bloco-institucional { text-align: center; padding: 30px 15px; background-color: #0f172a; border-radius: 12px; margin-top: 20px; }
        .contactos-footer { font-size: 14px; color: #94a3b8; margin-top: 15px; line-height: 1.6; }
        
        /* Cookies */
        .banner-consentimento { position: fixed; bottom: 0; left: 0; right: 0; background-color: rgba(15, 23, 42, 0.95); border-top: 1px solid #334155; padding: 12px 20px; display: flex; justify-content: space-between; align-items: center; font-size: 11px; color: #94a3b8; z-index: 9999; }
        .btn-aceitar { background-color: #22c55e; color: white; border: none; padding: 8px 16px; font-weight: bold; border-radius: 4px; cursor: pointer; }
    </style>
</head>
<body>

    <!-- MENUBAR SUPERIOR -->
   <!-- Altere de 20px para 40px no final do padding inline -->
<nav style="display: flex; justify-content: space-between; align-items: center; background-color: #e0e0e0; padding: 10px 40px; position: relative; z-index: 1000;background-color: #e0e0e0; padding: 10px 20px; position: relative; z-index: 1000;">
    <div class="logo" onclick="irParaSecao('home')" style="color: #d32f2f; cursor: pointer;">
        <h1 style="font-size: 22px; font-weight: bold; line-height: 1;">🎌AURE<span>LIUS</span></h1>
        <h6 style="color: #0b1a30; font-size: 11px; margin-top: 2px;">Salão de Beleza e Barbearia</h6>
    </div>
    
  <!-- Menu Horizontal Desktop Corrigido -->
<ul class="ul" id="menuDesktop">
<li><a href="Principal.php">Sair</a></li>
<li><a href="historico.php">Histórico</a></li>

<!-- Cada botão chama a sua respetiva aba -->
<li><a href="javascript:void(0)" onclick="alternarAbas('dadosAgendamento')">Serviços</a></li>
<li><a href="javascript:void(0)" onclick="alternarAbas('secao-photos')">Photos</a></li>
<li><a href="javascript:void(0)" onclick="alternarAbas('secao-funcionarios')">Funcionários</a></li>

<li><a href="javascript:void(0)" onclick="abrirTermos()">Termos & Privacidade</a></li>
</ul>

    <!-- ÍCONE DAS 3 BARRAS (Aparece apenas em Telemóveis) -->
    <div class="Menu-Icon" onclick="toggleMenu()" style="cursor: pointer; display: none;">
        <svg viewBox="0 0 100 80" width="28" height="28" style="fill: #0b1a30;">
            <rect width="100" height="15" rx="8"></rect>
            <rect y="30" width="100" height="15" rx="8"></rect>
            <rect y="60" width="100" height="15" rx="8"></rect>
        </svg>
    </div>

    <!-- MENU LATERAL RETRÁTIL (Mobile Overlay) -->
    <div id="menuLateralMobile" style="position: fixed; top: 0; left: -280px; width: 280px; height: 100vh; background-color: #0f172a; border-right: 2px solid #0088cc; box-shadow: 5px 0 15px rgba(0,0,0,0.5); transition: 0.3s ease; padding: 20px; z-index: 9999; display: flex; flex-direction: column; gap: 15px;">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; border-bottom: 1px solid #1e293b; padding-bottom: 10px;">
            <strong style="color: #fff; font-size: 18px;">Menu Aurelius</strong>
            <!-- Botão de fechar (X) -->
            <span onclick="toggleMenu()" style="font-size: 24px; color: #ef4444; cursor: pointer; font-weight: bold;">&times;</span>
        </div>
        <a href="Principal.php" style="background:#ef4444; color:white; padding:12px; text-decoration:none; border-radius:8px; font-weight:bold; text-align:center;">Sair</a>
        <a href="historico.php" style="background:#0088cc; color:white; padding:12px; text-decoration:none; border-radius:8px; font-weight:bold; text-align:center;">Histórico</a>
        <a href="#servicos" onclick="irParaSecaoMobile('servicos')" style="background:#0088cc; color:white; padding:12px; text-decoration:none; border-radius:8px; font-weight:bold; text-align:center;">Serviços</a>
        <a href="#photos" onclick="irParaSecaoMobile('photos')" style="background:#0088cc; color:white; padding:12px; text-decoration:none; border-radius:8px; font-weight:bold; text-align:center;">Photos</a>
        <a href="#funcionarios" onclick="irParaSecaoMobile('funcionarios')" style="background:#0088cc; color:white; padding:12px; text-decoration:none; border-radius:8px; font-weight:bold; text-align:center;">Funcionários</a>
        <a href="#termos" onclick="irParaSecaoMobile('termos')" style="background:#0088cc; color:white; padding:12px; text-decoration:none; border-radius:8px; font-weight:bold; text-align:center;">Termos & Privacidade</a>
    </div>
</nav>


    <div class="container">
        
        <!-- Bloco 1: DADOS DE ATENDIMENTO DA SESSÃO -->
        <div class="painel-azul" id="dadosAgendamento">
            <span class="painel-titulo">Dados de Atendimento da Sessão</span>
            <div class="grid-inputs">
                <input type="text" id="inputNomeCliente" placeholder="Nome do Cliente (Obrigatório)" class="input-estilizado">
                <select id="inputFuncionario" class="input-estilizado">
                    <option value="">Selecione um profissional...</option>
                    <option value="Handanga">Handanga (Barbeiro)</option>
                    <option value="Albino">Albino (Esteticista /Barbeiro/ Manicure)</option>
                    <option value="Carlos">Carlos (Manicure)</option>
                    <option value="Analtino">Analtino (Barbeiro)</option>
                    <option value="Aurélio">Aurélio (Cabeleireiro)</option>
                    <option value="Raimundo">Raimundo (Pedicure)</option>
                    <option value="Afonso">Afonso (Cabeleireiro)</option>
                    <option value="Carlota">Carlota (Cabeleireira)</option>
                </select>
                <input type="date" id="inputDataServico" class="input-estilizado">
                <input type="time" id="inputHoraServico" class="input-estilizado">
            </div>




<!-- SEÇÃO: PHOTOS COM EXEMPLOS REAIS E DINÂMICOS -->
<div id="secao-photos" class="aba-conteudo" style="display: none; width:92%; margin:20px auto;">
    
    <div class="aba-galeria" style="background:linear-gradient(135deg, #10383b, #1d4d50); color:white; padding:15px; border-radius:10px; margin-bottom:20px; text-align:center;">
        <h4>Portfólio & Galeria de Tendências</h4>
        <p style="font-size:12px; opacity:0.8; margin-top:4px;">Exemplos de inspirações e trabalhos executados no Huambo.</p>
    </div>

    <!-- GRADE DE IMAGENS AUTOMATIZADA -->
    <span class="painel-titulo">✂️ Inspirações de Cortes e Trabalhos</span>
    <div class="grid-container" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); gap: 15px; width:100%; margin-bottom:40px;">
        
        <?php if (count($listaFotos) > 0): ?>
            <?php foreach ($listaFotos as $fotoItem): ?>
                <div class="aba-item" style="background:#1e293b; padding:10px; border-radius:8px; text-align:center;">
                    <!-- Mostra a imagem guardada na pasta uploads do XAMPP -->
                    <img src="uploads/<?php echo htmlspecialchars($fotoItem['imagem']); ?>" style="width:100%; height:140px; object-fit:cover; border-radius:6px;">
                    <strong style="color: #fff; font-size:12px; display:block; margin-top:8px;"><?php echo htmlspecialchars($fotoItem['titulo']); ?></strong>
                </div>
            <?php endforeach; ?>
        <?php else: ?>
            <!-- Caso não exista nenhuma foto guardada na tabela anuncios -->
            <p style="color: #aaa; text-align: center; grid-column: 1 / -1; padding: 20px;">Nenhuma foto carregada na galeria ainda.</p>
        <?php endif; ?>

    </div>

    <!-- FORMULÁRIO COMPLETO PARA CARREGAR NOVAS FOTOS -->
    <div class="painel-azul" style="background: #0f172a; border: 1px solid #1d4d50; padding: 20px; border-radius: 8px; max-width:500px; margin:0 auto;">
        <span class="painel-titulo">📁 Carregar Nova Foto no Sistema</span>

        <form action="guardar_foto.php" method="POST" enctype="multipart/form-data" style="display:flex; flex-direction:column; gap:12px; text-align:left; margin-top:10px;">
            
            <!-- Campo do Título -->
            <label style="color: #fff; font-size: 14px;">Título do Trabalho:</label>
            <input type="text" name="titulo_foto" placeholder="Título da Imagem (Ex: Barba Alinhada)" class="input-estilizado" required style="color:#333; padding: 10px; border-radius: 4px; border: none;">
            
            <!-- Campo do Ficheiro -->
            <label style="color: #fff; font-size: 14px;">Escolher Foto:</label>
            <input type="file" name="ficheiro_foto" accept="image/*" required style="color: #fff;">
            
            <!-- Botão de Envio -->
            <button type="submit" style="background: #10b981; color: white; border: none; padding: 12px; border-radius: 4px; font-weight: bold; cursor: pointer; margin-top: 8px;">
                🚀 Enviar e Salvar na Galeria
            </button>
            
        </form>
    </div>

</div>





    

    <!-- ÁREA DE EXIBIÇÃO: Onde as fotos salvas vão aparecer depois -->
    <div class="grid-container" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); gap: 15px; margin-top: 30px;">
        <?php
        // Bloco opcional para listar as fotos já guardadas na tabela 'anuncios'
        try {
            $queryFotos = $pdo->query("SELECT * FROM anuncios ORDER BY id_announcement DESC");
            $listaFotos = $queryFotos->fetchAll();
            foreach ($listaFotos as $f):
        ?>
            <div class="aba-item" style="background: #1e293b; padding: 10px; border-radius: 8px; text-align: center;">
                <!-- O campo 'image_url' deve bater com o nome da coluna da sua tabela de anúncios -->
                <img src="<?php echo htmlspecialchars($f['image_url']); ?>" style="width: 100%; height: 130px; object-fit: cover; border-radius: 6px;">
                <strong style="font-size: 12px; display: block; margin-top: 8px;"><?php echo htmlspecialchars($f['title']); ?></strong>
            </div>
        <?php 
            endforeach;
        } catch (PDOException $e) {
            echo "<p style='font-size:12px; opacity:0.5; grid-column: 1/-1;'>Nenhuma foto carregada na galeria ainda.</p>";
        }
        ?>
    </div>
</div>




        <!-- ABA HOME (INCLUI STATUS E SERVIÇOS DIRETOS) -->
        <div id="secao-home" class="aba-conteudo active">
            <div class="painel-azul">
                <div class="flex-freemium">
                    <div class="info-freemium">
                        <h4>Acesso Freemium Ativo</h4>
                        <p>Subscreva o plano Premium para ocultar os anúncios e desbloquear relatórios completo.</p>
                        <button class="btn-upgrade" onclick="alert('Funcionalidade Premium em desenvolvimento!')">Seja Premium - 2.500 kz/mês</button>
                    </div>
                    <span class="badge-premium">CONTA GRÁTIS</span>
                </div>
            </div>
        </div>




<!-- BLOCO DE INDICADORES AUTOMÁTICOS -->
<div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 15px; margin: 20px 0; text-align: left;">
    
    <!-- Cartão 1: Caixa Automatizado -->
    <div style="background: linear-gradient(135deg, #1e293b, #0f172a); border-left: 4px solid #22c55e; padding: 15px; border-radius: 8px; box-shadow: 0 4px 6px rgba(0,0,0,0.1);">
        <span style="font-size: 11px; color: #94a3b8; font-weight: bold; text-transform: uppercase;">Caixa do Dia</span>
        <!-- Adicionado o id="valorCaixa" -->
        <h3 id="valorCaixa" style="font-size: 22px; color: #22c55e; margin-top: 5px;">
            <?php echo number_format($caixaDoDia, 2, ',', '.'); ?> Kz
        </h3>
        <p style="font-size: 10px; color: #64748b; margin-top: 2px;">Atualizado automaticamente</p>
    </div>

    <!-- Cartão 2: Sessões Automatizadas -->
    <div style="background: linear-gradient(135deg, #1e293b, #0f172a); border-left: 4px solid #3b82f6; padding: 15px; border-radius: 8px; box-shadow: 0 4px 6px rgba(0,0,0,0.1);">
        <span style="font-size: 11px; color: #94a3b8; font-weight: bold; text-transform: uppercase;">Atendimentos Hoje</span>
        <!-- Adicionado o id="valorAtendimentos" -->
        <h3 id="valorAtendimentos" style="font-size: 22px; color: #3b82f6; margin-top: 5px;">
            <?php echo $sessoesHoje; ?> <span style="font-size:14px; color:#64748b;">Sessões</span>
        </h3>
        <p style="font-size: 10px; color: #64748b; margin-top: 2px;">Controle de fluxo no Huambo</p>
    </div>

    <!-- Cartão 3: Equipa Ativa Automatizada -->
    <div style="background: linear-gradient(135deg, #1e293b, #0f172a); border-left: 4px solid #ff9900; padding: 15px; border-radius: 8px; box-shadow: 0 4px 6px rgba(0,0,0,0.1);">
        <span style="font-size: 11px; color: #94a3b8; font-weight: bold; text-transform: uppercase;">Equipa Ativa</span>
        <!-- Adicionado o id="valorEquipa" -->
        <h3 id="valorEquipa" style="font-size: 22px; color: #ff9900; margin-top: 5px;">
            <?php echo $totalProfissionais; ?> <span style="font-size:14px; color:#64748b;">Profissionais</span>
        </h3>
        <p style="font-size: 10px; color: #64748b; margin-top: 2px;">Buscado do banco de dados</p>
    </div>

</div>



        <!-- ABA DE SERVIÇOS (NÍVEL 1 E NÍVEL 2) -->
        <div id="secao-servicos" class="aba-conteudo">
            
            <!-- Nível 1: Lista de Categorias Principais -->
            <div id="nivel1">
                <span class="painel-titulo">Selecione uma Categoria:</span>
                <div class="grid-categorias">
                    <div class="aba-item" onclick="mostrarNivel2('cortes')"><img src="1776692284530.jpg">Cortes de Cabelo</div>
                    <div class="aba-item" onclick="mostrarNivel2('pinturas')"><img src="1777986415454.jpg">Pinturas de Cabelo</div>
                    <div class="aba-item" onclick="mostrarNivel2('sobrancelhas')"><img src="54.jpg">Sobrancelhas</div>
                    <div class="aba-item" onclick="mostrarNivel2('maquilhagem')"><img src="54.jpg">Maquilhagens</div>
                    <div class="aba-item" onclick="mostrarNivel2('tratamentos')"><img src="24509.jpg">Tratamentos Capilares</div>
                    <div class="aba-item" onclick="mostrarNivel2('manicure')"><img src="1750281718295.jpg">Manicure</div>
                    <div class="aba-item" onclick="mostrarNivel2('pedicure')"><img src="1754574223389.jpg">Pedicure</div>
                </div>
            </div>
  <!-- Nível 2: Lista Dinâmica de Subcategorias / Serviços -->
  <div id="nivel2" style="display: none;">
        <button class="btn-voltar" onclick="voltarParaNivel1()">← Voltar às Categorias</button>
        
        <!-- Sub-grupo: Cortes -->
        <div id="sub-cortes" class="sub-grupo-container" style="display: none;">
            <div class="grid-container">
                <div class="aba-item" onclick="exibirPrecoFinal('Corte Francês Cheio', '4500')">
                    <img src="1776692903268.jpg"><br>Corte Francês Cheio<br><b>4.500 Kz</b>
                </div>
                <div class="aba-item" onclick="exibirPrecoFinal('Corte Francês Vazio', '4000')">
                    <img src="1777201603721.jpg"><br>Corte Francês Vazio<br><b>4.000 Kz</b>
                </div>
                <div class="aba-item" onclick="exibirPrecoFinal('Corte de Crianças', '2000')">
                    <img src="1777757951670.jpg"><br>Corte de Crianças<br><b>2.000 Kz</b>
                </div>
                <div class="aba-item" onclick="exibirPrecoFinal('Corte de Adultos', '3000')">
                    <img src="1777298458880.jpg"><br>Corte de Adultos<br><b>3.000 Kz</b>
                </div>
                <div class="aba-item" onclick="exibirPrecoFinal('Corte Careca', '1500')">
                    <img src="1777556066924.jpg"><br>Careca<br><b>1.500 Kz</b>
                </div>
                <div class="aba-item" onclick="exibirPrecoFinal('Design e Corte de Barba', '2000')">
                    <img src="1776692182096.jpg"><br>Barba<br><b>2.000 Kz</b>
                </div>
                <div class="aba-item" onclick="exibirPrecoFinal('Outros Estilos de Corte', '3500')">
                    <img src="1777986301625.jpg"><br>Outros Estilos<br><b>3.500 Kz</b>
                </div>
            </div>
        </div>

        <!-- Sub-grupo: Pinturas -->
        <div id="sub-pinturas" class="sub-grupo-container" style="display: none;">
            <div class="grid-container">
                <div class="aba-item" onclick="exibirPrecoFinal('Tintura Geral', '12000')">
                    <img src="Save (21).jpg"><br>Tintura Geral<br><b>12.000 Kz</b>
                </div>
                <div class="aba-item" onclick="exibirPrecoFinal('Mechas / Luzes', '18000')">
                    <img src="1777986265622.jpg"><br>Mechas / Luzes<br><b>18.000 Kz</b>
                </div>
            </div>
        </div>

        <!-- Sub-grupo: Sobrancelhas -->
        <div id="sub-sobrancelhas" class="sub-grupo-container" style="display: none;">
            <div class="grid-container">
                <div class="aba-item" onclick="exibirPrecoFinal('Design Simples', '1500')">
                    <img src="54.jpg"><br>Design Simples<br><b>1.500 Kz</b>
                </div>
                <div class="aba-item" onclick="exibirPrecoFinal('Sobrancelha com Henna', '3000')">
                    <img src="54.jpg"><br>Com Henna<br><b>3.000 Kz</b>
                </div>
            </div>
        </div>

        <!-- Sub-grupo: Maquilhagem -->
        <div id="sub-maquilhagem" class="sub-grupo-container" style="display: none;">
            <div class="grid-container">
                <div class="aba-item" onclick="exibirPrecoFinal('Maquilhagem Simples', '5000')">
                    <img src="54.jpg"><br>Simples<br><b>5.000 Kz</b>
                </div>
                <div class="aba-item" onclick="exibirPrecoFinal('Maquilhagem Festa/Noiva', '15000')">
                    <img src="54.jpg"><br>Festa / Noiva<br><b>15.000 Kz</b>
                </div>
            </div>
        </div>

        <!-- Sub-grupo: Tratamentos -->
        <div id="sub-tratamentos" class="sub-grupo-container" style="display: none;">
            <div class="grid-container">
                <div class="aba-item" onclick="exibirPrecoFinal('Hidratação Simples', '4000')">
                    <img src="24509.jpg"><br>Hidratação<br><b>4.000 Kz</b>
                </div>
                <div class="aba-item" onclick="exibirPrecoFinal('Lavagem Completa', '2500')">
                    <img src="24509.jpg"><br>Lavagem<br><b>2.500 Kz</b>
                </div>
            </div>
        </div>

        <!-- Sub-grupo: Manicure -->
        <div id="sub-manicure" class="sub-grupo-container" style="display: none;">
            <div class="grid-container">
                <div class="aba-item" onclick="exibirPrecoFinal('Manicure Simples', '2000')">
                    <img src="1750281718295.jpg"><br>Manicure Simples<br><b>2.000 Kz</b>
                </div>
                <div class="aba-item" onclick="exibirPrecoFinal('Unhas de Gel', '7000')">
                    <img src="1750281718295.jpg"><br>Unhas de Gel<br><b>7.000 Kz</b>
                </div>
            </div>
        </div>

        <!-- Sub-grupo: Pedicure -->
        <div id="sub-pedicure" class="sub-grupo-container" style="display: none;">
                <div class="grid-container">
                    <div class="aba-item" onclick="exibirPrecoFinal('Pedicure Completa', '3000')">
                        <img src="1754574223389.jpg"><br>Pedicure Completa<br><b>3.000 Kz</b>
                    </div>
                    <div class="aba-item" onclick="exibirPrecoFinal('Pedicure de Gel', '6000')">
                        <img src="1754574223389.jpg"><br>Sessão Espelho/Gel<br><b>6.000 Kz</b>
                    </div>
                </div>
            </div>
    
        </div> <!-- Fim do id="nivel2" -->
    
         <!-- Bloco 3: Painel de Registro de Faturamento e Envio para o MySQL -->
         <div id="caixa-preco" class="preco-container" style="display: none; background-color: #0f172a; border: 2px dashed #22c55e; border-radius: 10px; padding: 20px; text-align: center; margin-top: 20px;">
         <h3>Serviço: <span id="nome-servico">-</span></h3>
         <p style="font-size: 28px; font-weight: bold; color: #22c55e; margin-bottom: 15px;"><span id="valor-servico">0</span> Kz</p>
         
         <!-- IMPORTANTE: O nome do arquivo no action deve ser EXATAMENTE igual ao nome do arquivo na sua pasta do Windows -->

        


         <form method="POST" action="guardar_pagamento.php" onsubmit="return prepararEnvio();">
             <input type="hidden" name="cliente" id="envioCliente">
             <input type="hidden" name="funcionario" id="envioFuncionario">
             <input type="hidden" name="data" id="envioData">
             <input type="hidden" name="hora" id="envioHora">
             <input type="hidden" name="servico" id="envioServico">
             <input type="hidden" name="valor" id="envioValor">
             
             <button type="submit" class="btn-confirmar" style="background-color: #22c55e; color: white; border: none; padding: 12px 35px; font-size: 16px; font-weight: bold; border-radius: 6px; cursor: pointer; width: 100%; max-width: 300px; margin-bottom: 10px;">
                 Confirmar e Registrar
             </button>
         </form>

         <!-- O botão de imprimir DEVE ficar aqui dentro para aparecer junto com o preço do serviço -->
         <button type="button" class="btn-imprimir-tudo" onclick="imprimirRelatorioCompleto()" style="background-color: #3b82f6; color: white; border: none; padding: 12px 35px; font-size: 16px; font-weight: bold; border-radius: 6px; cursor: pointer; width: 100%; max-width: 300px;">
             🖨️ Imprimir Tudo (Fatura Completa)
         </button>
     </div> 

 </div> <!-- Fim do id="secao-servicos" -->


   


 
     <!-- Procure por esta linha no seu arquivo e substitua a partir daqui -->
<div id="secao-funcionarios" class="aba-conteudo" style="width:92%; margin:20px auto;">
    
<div class="aba-galeria" style="background:linear-gradient(135deg, #14424b, #255861); color:white; padding:15px; border-radius:10px; margin-bottom:15px;">
    <h4>Painel Corporativo de Auditoria Operacional</h4>
    <p style="font-size:12px; opacity:0.8; margin-top:4px;">Gestão de equipas ativas e escala de atendimento para a cidade do Huambo.</p>
    
    <!-- O botão secreto do administrador entra exatamente aqui, antes do fechamento desta div -->
    <button onclick="toggleModoAdmin()" style="margin-top:10px; background:#ff9900; color:white; border:none; padding:6px 12px; border-radius:4px; font-weight:bold; cursor:pointer; font-size:11px;">
        ⚙️ Modo Administrator (Alterar Folgas/Disponibilidade)
    </button>
</div> <!-- Esta tag </div> fecha o cabeçalho acima de forma correta -->

<!-- Grade de Exibição dos Funcionários -->
<div class="grid-container" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 15px; padding:0; background:transparent; width:100%;">
    
    <!-- Funcionário 1 -->
    <div class="aba-item" style="display:flex; flex-direction:column; justify-content:space-between; padding:15px; text-decoration:none;">
        <a href="Aurelio.php" onclick="Funcionario()" style="color:white; text-decoration:none; display:block;">
            <strong>Mestre Hanganga</strong><br>
            <span id="status-hanganga" class="status-badge" style="font-size:11px; color:#22c55e; display:block; margin-top:4px;">Disponível (Cadeira 1)</span>
        </a>
        <select class="select-admin" onchange="atualizarStatus('hanganga', this.value)" style="display:none; margin-top:8px; width:100%; padding:4px; border-radius:4px;">
            <option value="Disponível (Cadeira 1)">Disponível (Cadeira 1)</option>
            <option value="Em Atendimento">Em Atendimento</option>
            <option value="Ausente (Folga)">Ausente (Folga)</option>
        </select>
    </div>

    <!-- Funcionário 2 -->
    <div class="aba-item" style="display:flex; flex-direction:column; justify-content:space-between; padding:15px; text-decoration:none;">
        <a href="Aurelio.php" onclick="Funcionario()" style="color:white; text-decoration:none; display:block;">
            <strong>Barbeiro Analtin</strong><br>
            <span id="status-analtin" class="status-badge" style="font-size:11px; color:#ffaa00; display:block; margin-top:4px;">Em Atendimento</span>
        </a>
        <select class="select-admin" onchange="atualizarStatus('analtin', this.value)" style="display:none; margin-top:8px; width:100%; padding:4px; border-radius:4px;">
            <option value="Disponível (Cadeira 2)">Disponível (Cadeira 2)</option>
            <option value="Em Atendimento" selected>Em Atendimento</option>
            <option value="Ausente (Folga)">Ausente (Folga)</option>
        </select>
    </div>

    <!-- Funcionário 3 -->
    <div class="aba-item" style="display:flex; flex-direction:column; justify-content:space-between; padding:15px; text-decoration:none;">
        <a href="Aurelio.php" onclick="Funcionario()" style="color:white; text-decoration:none; display:block;">
            <strong>Cabelereiro Aurelio</strong><br>
            <span id="status-aurelio" class="status-badge" style="font-size:11px; color:#ef4444; display:block; margin-top:4px;">Ausente (Folga)</span>
        </a>
        <select class="select-admin" onchange="atualizarStatus('aurelio', this.value)" style="display:none; margin-top:8px; width:100%; padding:4px; border-radius:4px;">
            <option value="Disponível (Cadeira 3)">Disponível (Cadeira 3)</option>
            <option value="Em Atendimento">Em Atendimento</option>
            <option value="Ausente (Folga)" selected>Ausente (Folga)</option>
        </select>
    </div>

    <!-- Funcionário 4 -->
    <div class="aba-item" style="display:flex; flex-direction:column; justify-content:space-between; padding:15px; text-decoration:none;">
        <a href="Aurelio.php" onclick="Funcionario()" style="color:white; text-decoration:none; display:block;">
            <strong>Manicure Fernandinho</strong><br>
            <span id="status-fernandinho" class="status-badge" style="font-size:11px; color:#ef4444; display:block; margin-top:4px;">Ausente (Folga)</span>
        </a>
        <select class="select-admin" onchange="atualizarStatus('fernandinho', this.value)" style="display:none; margin-top:8px; width:100%; padding:4px; border-radius:4px;">
            <option value="Disponível (Mesa 1)">Disponível (Mesa 1)</option>
            <option value="Em Atendimento">Em Atendimento</option>
            <option value="Ausente (Folga)" selected>Ausente (Folga)</option>
        </select>
    </div>

    <!-- Funcionário 5 -->
    <div class="aba-item" style="display:flex; flex-direction:column; justify-content:space-between; padding:15px; text-decoration:none;">
        <a href="Aurelio.php" onclick="Funcionario()" style="color:white; text-decoration:none; display:block;">
            <strong>Manicure Raimundo</strong><br>
            <span id="status-raimundo" class="status-badge" style="font-size:11px; color:#ef4444; display:block; margin-top:4px;">Ausente (Folga)</span>
        </a>
        <select class="select-admin" onchange="atualizarStatus('raimundo', this.value)" style="display:none; margin-top:8px; width:100%; padding:4px; border-radius:4px;">
            <option value="Disponível (Mesa 2)">Disponível (Mesa 2)</option>
            <option value="Em Atendimento">Em Atendimento</option>
            <option value="Ausente (Folga)" selected>Ausente (Folga)</option>
        </select>
    </div>

    <!-- Funcionário 6 -->
    <div class="aba-item" style="display:flex; flex-direction:column; justify-content:space-between; padding:15px; text-decoration:none;">
        <a href="Aurelio.php" onclick="Funcionario()" style="color:white; text-decoration:none; display:block;">
            <strong>Manicure Zidane</strong><br>
            <span id="status-zidane" class="status-badge" style="font-size:11px; color:#22c55e; display:block; margin-top:4px;">Disponível (Cadeira 4)</span>
        </a>
        <select class="select-admin" onchange="atualizarStatus('zidane', this.value)" style="display:none; margin-top:8px; width:100%; padding:4px; border-radius:4px;">
            <option value="Disponível (Cadeira 4)" selected>Disponível (Cadeira 4)</option>
            <option value="Em Atendimento">Em Atendimento</option>
            <option value="Ausente (Folga)">Ausente (Folga)</option>
        </select>
    </div>

    <!-- Funcionário 7 -->
    <div class="aba-item" style="display:flex; flex-direction:column; justify-content:space-between; padding:15px; text-decoration:none;">
        <a href="Aurelio.php" onclick="Funcionario()" style="color:white; text-decoration:none; display:block;">
            <strong>Esteticista Albino</strong><br>
            <span id="status-albino" class="status-badge" style="font-size:11px; color:#22c55e; display:block; margin-top:4px;">Disponível (Cadeira 3)</span>
        </a>
        <select class="select-admin" onchange="atualizarStatus('albino', this.value)" style="display:none; margin-top:8px; width:100%; padding:4px; border-radius:4px;">
            <option value="Disponível (Cadeira 3)" selected>Disponível (Cadeira 3)</option>
            <option value="Em Atendimento">Em Atendimento</option>
            <option value="Ausente (Folga)">Ausente (Folga)</option>
        </select>
    </div>

</div> <!-- Fecha a grid-container -->
</div> <!-- Fecha a secao-funcionarios de forma limpa -->

 <div id="area-impressao-global" style="display: none; padding: 20px; font-family: monospace;"></div>
<!-- JANELA POP-UP (MODAL) DOS TERMOS -->
<div id="modalTermos" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background-color: rgba(0, 0, 0, 0.7); z-index: 10000; justify-content: center; align-items: center; padding: 20px;">
    
    <div style="background-color: #0f172a; border: 1px solid #334155; width: 100%; max-width: 500px; border-radius: 12px; padding: 25px; box-shadow: 0 10px 25px rgba(0,0,0,0.5); position: relative;">
        
        <!-- Botão de Fechar (X) -->
        <span onclick="fecharTermos()" style="position: absolute; top: 15px; right: 20px; color: #ef4444; font-size: 28px; font-weight: bold; cursor: pointer;">&times;</span>
        
        <!-- Título -->
        <h3 style="color: #fff; border-bottom: 1px solid #334155; padding-bottom: 12px; margin-top: 0; font-size: 18px;">
            ⚠️ Termos & Política de Privacidade
        </h3>
        
        <!-- Conteúdo -->
        <div style="color: #94a3b8; font-size: 13px; line-height: 1.6; max-height: 300px; overflow-y: auto; margin-top: 15px; padding-right: 5px;">
            <p style="margin-bottom: 12px;"><strong>1. Gestão Interna:</strong> Este painel destina-se exclusivamente ao registo e controlo de fluxos de atendimento do Salão & Barbearia Aurelius no Huambo.</p>
            <p style="margin-bottom: 12px;"><strong>2. Dados dos Clientes:</strong> Os dados inseridos no formulário (Nome, Funcionário e Horário) são confidenciais e usados unicamente para a organização da agenda diária.</p>
            <p style="margin-bottom: 0;"><strong>3. Cancelamentos:</strong> Alterações ou eliminações de registos no histórico devem ser validadas com o administrador do sistema.</p>
        </div>
        
        <!-- Botão Entendido -->
        <div style="text-align: right; margin-top: 20px;">
            <button onclick="fecharTermos()" style="background-color: #0088cc; color: white; border: none; padding: 8px 20px; font-weight: bold; border-radius: 6px; cursor: pointer;">Entendido</button>
        </div>
    </div>
</div>
  <!-- BANNER DE COOKIES (Colocar logo acima da tag </body>) -->
  <div class="banner-consentimento" id="cookieBanner" style="position: fixed; bottom: 0; left: 0; right: 0; background-color: rgba(15, 23, 42, 0.95); border-top: 1px solid #334155; padding: 12px 20px; display: flex; justify-content: space-between; align-items: center; font-size: 11px; color: #94a3b8; z-index: 9999;">
        <div style="padding-right: 20px;">
            O Grupo Aurelius, Salão de beleza e Barbearia recolhe dados estatísticos de navegação anonimizados para fins publicitários e análise de tendências de mercado em Angola.
        </div>
        <div>
            <button class="btn-aceitar" onclick="fecharBannerCookies()" style="background-color: #22c55e; color: white; border: none; padding: 8px 16px; font-weight: bold; border-radius: 4px; cursor: pointer; white-space: nowrap;">
                Aceitar e Permitir Rastreamento
</button>
</div>
</div>












        <script>
        let nomeServicoGlobal = "";
        let valorServicoGlobal = 0;
    
        function irParaSecao(idSecao) {
            // 1. Oculta todas as abas do sistema primeiro
            document.querySelectorAll('.aba-conteudo').forEach(div => {
                div.style.display = 'none';
                div.classList.remove('active');
            });
        
            // 2. Localiza a seção desejada (ex: secao-funcionarios)
            const alvo = document.getElementById('secao-' + idSecao);
            if (alvo) {
                // 3. Torna a seção visível e ativa na tela
                alvo.style.display = 'block';
                alvo.classList.add('active');
            }
            
            // 4. Atualiza o endereço na barra do navegador (#funcionarios)
            window.location.hash = idSecao;
        }
        function irParaSecao(idSecao) {
    var secaoAlvo = document.getElementById(idSecao);
    if (secaoAlvo) {
        secaoAlvo.scrollIntoView({ behavior: 'smooth' });
    }
}

function irParaSecaoMobile(idSecao) {
    irParaSecao(idSecao);
    if (typeof toggleMenu === "function") {
        toggleMenu();
    }
}

function abrirTermos() {
    var modal = document.getElementById('modalTermos');
    if (modal) modal.style.display = 'flex';
}

function fecharTermos() {
    var modal = document.getElementById('modalTermos');
    if (modal) modal.style.display = 'none';
}

function atualizarIndicadores() {
    fetch('buscar_dados_dashboard.php')
        .then(response => {
            if (!response.ok) throw new Error('Erro na resposta do servidor');
            return response.json();
        })
        .then(dados => {
            if (document.getElementById('valorCaixa')) {
                document.getElementById('valorCaixa').innerHTML = dados.caixa;
            }
            if (document.getElementById('valorAtendimentos')) {
                document.getElementById('valorAtendimentos').innerHTML = dados.atendimentos;
            }
            if (document.getElementById('valorEquipa')) {
                document.getElementById('valorEquipa').innerHTML = dados.equipa;
            }
        })
        .catch(error => console.error('Erro na atualização automática:', error));
}

document.addEventListener("DOMContentLoaded", function() {
    atualizarIndicadores(); 
    setInterval(atualizarIndicadores, 4000);
});


        function mostrarNivel2(cat) {
            document.getElementById('nivel1').style.display = 'none';
            document.getElementById('nivel2').style.display = 'block';
            
            // Oculta todos os blocos de subgrupo para não misturar os serviços
            document.querySelectorAll('.sub-grupo-container').forEach(div => div.style.display = 'none');
            
            // Ativa apenas a categoria clicada
            const subGrupo = document.getElementById('sub-' + cat);
            if (subGrupo) subGrupo.style.display = 'block';
        }
    
        function voltarParaNivel1() {
            document.getElementById('nivel1').style.display = 'block';
            document.getElementById('nivel2').style.display = 'none';
            document.getElementById('caixa-preco').style.display = 'none';
        }
    
        function exibirPrecoFinal(nome, valor) {
            nomeServicoGlobal = nome;
            valorServicoGlobal = valor;
            
            document.getElementById('nome-servico').innerText = nome;
            document.getElementById('valor-servico').innerText = Number(valor).toLocaleString('pt-PT');
            document.getElementById('caixa-preco').style.display = 'block';
        }
        function imprimirRelatorioCompleto() {
    // 1. Captura os dados digitados nos campos do topo da página
    const cliente = document.getElementById('inputNomeCliente').value.trim() || "Consumidor Final";
    const funcionario = document.getElementById('inputFuncionario').value || "Não Informado";
    const dataRaw = document.getElementById('inputDataServico').value;
    const hora = document.getElementById('inputHoraServico').value || "--:--";

    if (!nomeServicoGlobal) {
        alert("Por favor, escolha um serviço antes de imprimir!");
        return;
    }

    // 2. Converte a data do formato do sistema (AAAA-MM-DD) para o padrão de Angola (DD/MM/AAAA)
    let dataFormatada = "--/--/----";
    if (dataRaw) {
        const partes = dataRaw.split("-");
        if (partes.length === 3) {
            dataFormatada = `${partes[2]}/${partes[1]}/${partes[0]}`;
        }
    }

    // 3. Monta o visual do cupom de forma limpa dentro da área de impressão
    const areaImpressao = document.getElementById('area-impressao-global');
    areaImpressao.innerHTML = `
        <div style="width: 100%; max-width: 300px; margin: 0 auto; text-align: center; color: #000;">
            <h2>🎌 AURELIUS SALÃO 🎌</h2>
            <p style="font-size: 11px;">Huambo, Angola</p>
            <p>----------------------------------</p>
            <div style="text-align: left; font-size: 13px; line-height: 1.6;">
                <b>Cliente:</b> ${cliente}<br>
                <b>Profissional:</b> ${funcionario}<br>
                <b>Data da Sessão:</b> ${dataFormatada}<br>
                <b>Horário:</b> ${hora} h<br>
            </div>
            <p>----------------------------------</p>
            <table style="width: 100%; border-collapse: collapse; font-size: 13px; text-align: left;">
                <thead>
                    <tr style="border-bottom: 1px dashed #000;">
                        <th>Serviço</th>
                        <th style="text-align: right;">Preço</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td>${nomeServicoGlobal}</td>
                        <td style="text-align: right;">${Number(valorServicoGlobal).toLocaleString('pt-PT')} Kz</td>
                    </tr>
                </tbody>
            </table>
            <p>----------------------------------</p>
            <h3 style="text-align: right;">VALOR TOTAL: ${Number(valorServicoGlobal).toLocaleString('pt-PT')} Kz</h3>
            <p style="margin-top: 25px; font-size: 11px;">Obrigado pela preferência!<br>Volte sempre.</p>
        </div>
    `;

    // 4. Executa o comando de impressão do navegador
    window.print();
}
    
        function prepararEnvio() {
            // Captura os dados do topo da página
            const cliente = document.getElementById('inputNomeCliente').value.trim();
            const funcionario = document.getElementById('inputFuncionario').value;
            const data = document.getElementById('inputDataServico').value;
            const hora = document.getElementById('inputHoraServico').value;
    
            // Validação obrigatória antes de submeter ao arquivo PHP
            if (!cliente || !funcionario || !data || !hora || !nomeServicoGlobal) {
                alert("Por favor, preencha todos os campos do atendimento no topo e selecione um serviço!");
                return false;
            }
    
            // Passa os valores para os inputs ocultos do formulário
            document.getElementById('envioCliente').value = cliente;
            document.getElementById('envioFuncionario').value = funcionario;
            document.getElementById('envioData').value = data;
            document.getElementById('envioHora').value = hora;
            document.getElementById('envioServico').value = nomeServicoGlobal;
            document.getElementById('envioValor').value = valorServicoGlobal;
    
            return true;
        }

           
            // Carrega automaticamente os estados guardados ao abrir a página
            window.addEventListener('DOMContentLoaded', () => {
                const funcionariosIds = ['hanganga', 'analtin', 'aurelio', 'fernandinho', 'raimundo', 'zidane', 'albino', 'afonso'];
                
                funcionariosIds.forEach(id => {
                    const statusGuardado = localStorage.getItem('status-' + id);
                    if (statusGuardado) {
                        const elemento = document.getElementById('status-' + id);
                        if (elemento) {
                            elemento.innerText = statusGuardado;
                            
                            // Trata e atualiza a cor do texto conforme o estado guardado
                            if (statusGuardado.includes('Ausente') || statusGuardado.includes('Folga')) {
                                elemento.style.color = '#ef4444';
                            } else if (statusGuardado.includes('Atendimento')) {
                                elemento.style.color = '#ffaa00';
                            } else {
                                elemento.style.color = '#22c55e';
                            }
                        }
                    }
                });
            });

            
function atualizarIndicadores() {
    // Faz a chamada para o ficheiro que procura os dados atualizados no banco de dados
    fetch('buscar_dados_dashboard.php')
        .then(response => response.json())
        .then(dados => {
            // Atualiza o texto mantendo a estrutura visual limpa
            document.getElementById('valorCaixa').innerHTML = dados.caixa;
            document.getElementById('valorAtendimentos').innerHTML = dados.atendimentos;
            document.getElementById('valorEquipa').innerHTML = dados.equipa;
        })
        .catch(error => console.error('Erro na atualização automática:', error));
}

// Inicializa a verificação recorrente
document.addEventListener("DOMContentLoaded", function() {
    // Atualiza a cada 4 segundos (4000 milissegundos)
    setInterval(atualizarIndicadores, 4000);
});




            // Mostra ou oculta as opções de alteração para o Gerente/Administrador
            function toggleModoAdmin() {
                const paineis = document.querySelectorAll('.select-admin');
                paineis.forEach(painel => {
                    painel.style.display = (painel.style.display === 'none' || painel.style.display === '') ? 'block' : 'none';
                });
            }

            // Altera o estado dinamicamente e guarda na memória local do navegador (LocalStorage)
            function atualizarStatus(idFuncionario, novoValor) {
                const elemento = document.getElementById('status-' + idFuncionario);
                if (elemento) {
                    elemento.innerText = novoValor;
                    localStorage.setItem('status-' + idFuncionario, novoValor);
                    
                    // Trata as cores em tempo real ao selecionar
                    if (novoValor.includes('Ausente') || novoValor.includes('Folga')) {
                        elemento.style.color = '#ef4444';
                    } else if (novoValor.includes('Atendimento')) {
                        elemento.style.color = '#ffaa00';
                    } else {
                        elemento.style.color = '#22c55e';
                    }
                }
            }
            

            function Funcionario() {
                console.log("Visualizando portfólio e informações do profissional.");
            }


// Cole esta função dentro do seu bloco de scripts atual
function fecharBannerCookies() {
    const banner = document.getElementById('cookieBanner');
    if (banner) {
        // Esconde o banner da tela imediatamente ao clicar no botão verde
        banner.style.display = 'none';
        
        // Salva na memória do navegador que o usuário já aceitou, 
        // para que o banner não fique reaparecendo toda hora que atualizar a página
        localStorage.setItem('cookiesAceitos', 'sim');
    }
}

// Bloquinho extra de segurança: Verifica se o usuário já aceitou antes. 
// Se já aceitou anteriormente, o banner nem aparece ao carregar a página.
window.addEventListener('DOMContentLoaded', () => {
    // Se tinha algum código seu aqui para carregar estados, cole-o aqui!
    console.log("Página e estados carregados com sucesso!");
});
// Abre e fecha o menu lateral mudando a posição do painel 'left'
function toggleMenu() {
    const menuLateral = document.getElementById('menuLateralMobile');
    if (menuLateral.style.left === '0px') {
        menuLateral.style.left = '-280px'; // Esconde o menu
    } else {
        menuLateral.style.left = '0px'; // Desliza o menu para dentro da tela
    }
}

// Executa a navegação de abas no telemóvel e fecha o menu lateral logo em seguida
function irParaSecaoMobile(idSecao) {
    irParaSecao(idSecao); // Chama a função padrão que você já tem
    toggleMenu(); // Fecha o menu lateral de forma automática para o usuário ver o conteúdo
}
   // Função para mostrar a janela ao clicar no menu
   function abrirTermos() {
        document.getElementById('modalTermos').style.display = 'flex';
    }

    // Função para esconder a janela ao clicar no X ou no botão
    function fecharTermos() {
        document.getElementById('modalTermos').style.display = 'none';
    }

    // ESTE É O CÓDIGO QUE VOCÊ PERGUNTOU (Corrigido):
    // Ele fecha a janela automaticamente se clicar na parte escura fora da caixa
    window.onclick = function(event) {
        var modal = document.getElementById('modalTermos');
        if (event.target == modal) {
            modal.style.display = 'none';
        }
    }
    

</script>
   
    

    </body>
    </html>