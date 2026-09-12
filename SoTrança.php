



<?php
if (session_status() === PHP_SESSION_NONE) { session_start(); }

// 🟢 BLOCO DE CORREÇÃO: Força a ligação se o $pdo falhar
try {
    $host = "127.0.0.1";
    $db   = "aurelius_salao";
    $user = "root";
    $pass = "";
    
    // Se o seu Banco.php usa outro nome de variável, 
    // este comando garante que o nome correto ($pdo) seja usado
    $pdo = new PDO("mysql:host=$host;dbname=$db;charset=utf8mb4", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Erro: Não foi possível ligar ao MySQL no XAMPP. Certifique-se que o MySQL está ligado.");
}

// ... agora a linha 20 que tem o $pdo->prepare(...) vai funcionar!
?>

<?php
// 1. Inicia a sessão se não existir
if (session_status() === PHP_SESSION_NONE) { session_start(); }

// 2. Tenta incluir a conexão original
@include_once("config/Banco.php"); 

// 3. 🚨 CONEXÃO DE EMERGÊNCIA (Se o $pdo continuar nulo, este bloco resolve)
if (!isset($pdo) || $pdo === null) {
    try {
        $host = "127.0.0.1";
        $db   = "aurelius_salao";
        $user = "root";
        $pass = "";
        $pdo = new PDO("mysql:host=$host;dbname=$db;charset=utf8mb4", $user, $pass);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    } catch (PDOException $e) {
        die("Erro Crítico: Não foi possível ligar à base de dados no XAMPP. Verifique se o MySQL está ativo.");
    }
}

// 4. Configurações da Barbearia
$id_empresa_ativa = 242; // ID SóTranças
$hoje = date('Y-m-d');
$expediente = ['08:00', '09:00', '10:00', '11:00', '12:00', '14:00', '15:00', '16:00', '17:00', '18:00'];
$profissionais = ['Handanga', 'Albino']; 
$agenda_ocupada = [];

// Agora a linha 15 (o prepare) vai funcionar porque o $pdo já existe!
try {
    $stmt = $pdo->prepare("SELECT funcionario, hora_servico FROM pagamentos WHERE id_parceiro = ? AND data_servico = ?");
    $stmt->execute([$id_empresa_ativa, $hoje]);
    $ocupados_bd = $stmt->fetchAll(PDO::FETCH_ASSOC);

    foreach ($ocupados_bd as $row) {
        $hora_limpa = substr($row['hora_servico'], 0, 5);
        $agenda_ocupada[$row['funcionario']][] = $hora_limpa;
    }
} catch (Exception $e) {
    // Silencia erros de pauta para não travar o ecrã
}
?>
<!-- DAQUI PARA BAIXO ADICIONE O SEU DOCTYPE HTML, NAV E ELEMENTOS VISUAIS -->
<!DOCTYPE html>
<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($nome_salao_dinamico) ?> - Painel</title>
    <style>
        /* --- CSS CORRETIVO CRÍTICO PARA AS ABAS --- */
        .tab-content { display: none; }
        .tab-content.active { display: block !important; }
        .hidden { display: none !important; }
        
        /* Estilos dos Cards de Serviços */
        .grid-container { display: grid; grid-template-columns: repeat(auto-fill, minmax(140px, 1fr)); gap: 15px; padding: 15px 0; }
        .aba-item { background: #1e293b; border: 1px solid #334155; color: #fff; padding: 15px 10px; border-radius: 12px; cursor: pointer; transition: 0.3s; font-size: 13px; text-align: center; }
        .aba-item:hover { border-color: #eab308; background: #0f172a; }
        .btn-voltar { background: #334155; color: #fff; border: none; padding: 8px 16px; border-radius: 6px; cursor: pointer; margin-bottom: 15px; font-size: 12px; font-weight: bold; }
        .btn-voltar:hover { background: #eab308; color: #000; }
    </style>
</head>
</head>
<body style="background: linear-gradient(135deg, #090514, #120b24); color: #f8fafc; min-height: 100vh;">

    <!-- 📲 BARRA DE NAVEGAÇÃO PREMIUM NEON -->
    <nav style="padding: 15px 30px; background: #120b24; border-bottom: 2px solid #38bdf8; box-shadow: 0 0 15px rgba(56, 189, 248, 0.3); display: flex; justify-content: space-between; align-items: center;">
        <div class="logo">
            <h1 style="font-size: 22px; font-weight: 800; color: #ef4444; text-transform: uppercase; letter-spacing: 0.5px;">AURE<span style="color: #f8fafc;">LIUS</span></h1>
            <h6 style="color: #64748b; font-size: 11px; margin-top: 4px; text-transform: uppercase;">Módulo de Checkout Sincronizado</h6>
        </div>
        
        <ul class="menu-horizontal" id="navbar-menu" style="display: flex; list-style: none; gap: 15px; margin: 0; padding: 0; align-items: center;">
            <li><a href="SoTrança.php" style="color: #cbd5e1; text-decoration: none; font-size: 13px; font-weight: 600; padding: 8px 16px; transition: 0.3s;">Home</a></li>
            <li><a class="tab-link active" onclick="switchTab(event, 'aba_servicos')" style="color: #38bdf8; background: rgba(56, 189, 248, 0.1); border: 1px solid #38bdf8; border-radius: 6px; text-decoration: none; font-size: 13px; font-weight: 600; padding: 8px 16px; cursor: pointer; transition: 0.3s;">📋 Serviços</a></li>
            <li><a class="tab-link" onclick="switchTab(event, 'pagamentos')" style="color: #cbd5e1; text-decoration: none; font-size: 13px; font-weight: 600; padding: 8px 16px; cursor: pointer; transition: 0.3s;">💳 Pagamentos</a></li>
            <li><a class="tab-link" onclick="switchTab(event, 'agenda')" style="color: #cbd5e1; text-decoration: none; font-size: 13px; font-weight: 600; padding: 8px 16px; cursor: pointer; transition: 0.3s;">📅 Agenda/Marcação</a></li>
            <li><a class="tab-link" onclick="switchTab(event, 'funcionarios')" style="color: #cbd5e1; text-decoration: none; font-size: 13px; font-weight: 600; padding: 8px 16px; cursor: pointer; transition: 0.3s;">💇 Funcionários</a></li>
            <li><a href="Principal.php" style="border: 1px solid #ef4444; color:#ef4444; border-radius: 6px; text-decoration: none; font-size: 13px; font-weight: 600; padding: 6px 14px; transition: 0.3s;">✕ Sair</a></li>
        </ul>
    </nav>

    <!-- 🌐 GRELHAS ESTILIZADAS PARA OS PRODUTOS/SERVIÇOS -->
    <style>
        .grid-servicos { display: grid; grid-template-columns: repeat(auto-fill, minmax(130px, 1fr)); gap: 15px; margin-top: 15px; }
        .card-item-neon { background: #1e293b; border: 1px solid #334155; color: #fff; padding: 15px 10px; border-radius: 12px; cursor: pointer; transition: 0.3s; text-align: center; border-bottom: 3px solid #334155; }
        .card-item-neon:hover { border-color: #38bdf8; background: #0f172a; box-shadow: 0 0 10px rgba(56, 189, 248, 0.4); transform: translateY(-2px); }
        .btn-voltar-neon { background: #1e293b; color: #38bdf8; border: 1px solid #38bdf8; padding: 8px 16px; border-radius: 6px; cursor: pointer; font-size: 12px; font-weight: bold; transition: 0.3s; }
        .btn-voltar-neon:hover { background: #38bdf8; color: #090514; box-shadow: 0 0 10px rgba(56, 189, 248, 0.5); }
    </style>

    <div class="tabs-container" style="max-width: 1200px; margin: 30px auto; padding: 0 20px;">

      
      
      
      
      
      
      
      <!-- =========================================================================
     📋 ABA MESTRE: SERVIÇOS 3 NÍVEIS (Categorias, Subcategorias e Serviços)
     ========================================================================= -->
<div id="aba_servicos" class="tab-content" style="display: block;">
    
<!-- PASSO 1: CATEGORIAS PRINCIPAIS -->
<div id="sub_container_nivel1" style="display: block; text-align: left;">
    <span style="color:#64748b; font-size:12px; display:block; margin-bottom:10px; text-transform: uppercase; font-weight: bold;">PASSO 1: Selecione a Categoria Principal</span>
    <div class="grid-servicos">
        <div class="card-item-neon" onclick="mostrarNivel2('cortes')"> <img style="width:100%; border-radius:6px; margin-bottom:8px;" src="1776692284530.jpg"> <br>💇 Cortes de Cabelo</div>
        <div class="card-item-neon" onclick="mostrarNivel2('pinturas')"> <img style="width:100%; border-radius:6px; margin-bottom:8px;" src="1776692284530.jpg"> <br>🎨 Pinturas de Cabelo</div>
        <div class="card-item-neon" onclick="mostrarNivel2('sobrancelhas')"> <img style="width:100%; border-radius:6px; margin-bottom:8px;" src="1776692284530.jpg"> <br>✨ Design Sobrancelhas</div>
        <div class="card-item-neon" onclick="mostrarNivel2('maquilhagem')"> <img style="width:100%; border-radius:6px; margin-bottom:8px;" src="1776692284530.jpg"> <br>💄 Maquilhagens</div>
        <div class="card-item-neon" onclick="mostrarNivel2('tratamentos')"> <img style="width:100%; border-radius:6px; margin-bottom:8px;" src="1776692284530.jpg"> <br>🌿 Tratamentos Capilares</div>
    </div>
</div>

<!-- PASSO 2: SUBCATEGORIAS -->
<div id="sub_container_nivel2" style="display: none; text-align: left;">
    <button type="button" class="btn-voltar-neon" onclick="voltarParaNivel1()">← Voltar às Categorias</button>
    <span style="color:#64748b; font-size:12px; display:block; margin:15px 0 10px 0; text-transform: uppercase; font-weight: bold;">PASSO 2: Selecione a Subcategoria Específica</span>
    
    <div id="sub-cortes" class="grid-servicos sub-grupo" style="display:none;">
        <div class="card-item-neon" onclick="mostrarNivel3('cortes-masculinos')"> <img style="width:100%; border-radius:6px;" src="1776692284530.jpg"><br>🧔 Cortes Masculinos</div>
        <div class="card-item-neon" onclick="mostrarNivel3('cortes-femininos')"> <img style="width:100%; border-radius:6px;" src="1776692587785.jpg"><br>👩 Cortes Femininos</div>
    </div>

    <div id="sub-pinturas" class="grid-servicos sub-grupo" style="display:none;">
        <div class="card-item-neon" onclick="mostrarNivel3('tintura-global')"> <img style="width:100%; border-radius:6px;" src="1776692284530.jpg"><br>Tintura Geral</div>
        <div class="card-item-neon" onclick="mostrarNivel3('mechas')"> <img style="width:100%; border-radius:6px;" src="1776692284530.jpg"><br>Mechas / Luzes</div>
    </div>

    <div id="sub-sobrancelhas" class="grid-servicos sub-grupo" style="display:none;">
        <div class="card-item-neon" onclick="mostrarNivel3('design-simples')"> <img style="width:100%; border-radius:6px;" src="1776692284530.jpg"><br>Design Simples</div>
        <div class="card-item-neon" onclick="mostrarNivel3('henna')"> <img style="width:100%; border-radius:6px;" src="1776692284530.jpg"><br>Aplicação de Henna</div>
        <div class="card-item-neon" onclick="mostrarNivel3('tatuar-sobrancelhas')"><img style="width:100%; border-radius:6px;" src="1776692284530.jpg"><br>Sobrancelhas Normal</div>
    </div>

    <div id="sub-maquilhagem" class="grid-servicos sub-grupo" style="display:none;">
        <div class="card-item-neon" onclick="mostrarNivel3('make-social')"> <img style="width:100%; border-radius:6px;" src="1776692284530.jpg"><br>Maquilhagem Social</div>
        <div class="card-item-neon" onclick="mostrarNivel3('make-noiva')">💄 <img style="width:100%; border-radius:6px;" src="1776692284530.jpg"><br>Maquilhagem Noivas</div>
    </div>

    <div id="sub-tratamentos" class="grid-servicos sub-grupo" style="display:none;">
        <div class="card-item-neon" onclick="mostrarNivel3('hidratacao')"> <img style="width:100%; border-radius:6px;" src="1776692284530.jpg"><br>🌿 Hidratação Profunda</div>
        <div class="card-item-neon" onclick="mostrarNivel3('queratina')"> <img style="width:100%; border-radius:6px;" src="1776692284530.jpg"><br>💎 Banho de Queratina</div>
    </div>
</div>
 <!-- PASSO 3: TIPOS ESPECÍFICOS DE SERVIÇOS E PREÇOS -->
 <div id="sub_container_nivel3" style="display: none; text-align: left;">
 <button type="button" class="btn-voltar-neon" onclick="voltarParaNivel2()">← Voltar</button>
 <h2 id="titulo-nivel3" style="font-size: 14px; color: #eab308; text-transform: uppercase; margin: 15px 0; font-weight: bold;">PASSO 3: Tipos de Serviços Disponíveis</h2>

 <!-- Serviços: Cortes Masculinos -->
 <div id="opcoes-cortes-masculinos" class="grid-servicos opcao-grupo" style="display:none;">
 <div class="card-item-neon" onclick="atualizarPrecoNoFormulario('Francês Adulto', 2000)"> 
    <img style="width:100%; border-radius:6px;" src="1776692284530.jpg"><br>Francês Adulto<br><b style="color:#22c55e;">2.000 Kz</b>
</div>
<div class="card-item-neon" onclick="atualizarPrecoNoFormulario('Francês Adulto', 2000)"> 
    <img style="width:100%; border-radius:6px;" src="1776692284530.jpg"><br>Francês Adulto<br><b style="color:#22c55e;">2.000 Kz</b>
</div>
     <div class="card-item-neon" onclick="atualizarPrecoNoFormulario('Covinha Adulto', 2000)"> <img style="width:100%; border-radius:6px;" src="1776692284530.jpg"><br>Covinha Adulto<br><b style="color:#22c55e;">2.000 Kz</b></div>
     <div class="card-item-neon" onclick="atualizarPrecoNoFormulario('Covinha Criança', 1500)"> <img style="width:100%; border-radius:6px;" src="1776692284530.jpg"><br>Covinha Criança<br><b style="color:#22c55e;">1.500 Kz</b></div>
     <div class="card-item-neon" onclick="atualizarPrecoNoFormulario('Barba Imperial', 1000)"> <img style="width:100%; border-radius:6px;" src="1776692284530.jpg"><br>Barba Imperial<br><b style="color:#22c55e;">1.000 Kz</b></div>
     <div class="card-item-neon" onclick="atualizarPrecoNoFormulario('Careca Completa', 800)"> <img style="width:100%; border-radius:6px;" src="1776692284530.jpg"><br>Careca Completa<br><b style="color:#22c55e;">800 Kz</b></div>
     <div class="card-item-neon" onclick="atualizarPrecoNoFormulario('Freestyle Arte', 2000)"> <img style="width:100%; border-radius:6px;" src="1776692284530.jpg"><br>Freestyle Arte<br><b style="color:#22c55e;">2.000 Kz</b></div>
     <div class="card-item-neon" onclick="atualizarPrecoNoFormulario('Obama Moderno', 2000)"> <img style="width:100%; border-radius:6px;" src="1776692284530.jpg"><br>Obama Moderno<br><b style="color:#22c55e;">2.000 Kz</b></div>
 </div>

 <!-- Serviços: Cortes Femininos -->
 <div id="opcoes-cortes-femininos" class="grid-servicos opcao-grupo" style="display:none;">
     <div class="card-item-neon" onclick="atualizarPrecoNoFormulario('Corte Bob Premium', 2500)"> <img style="width:100%; border-radius:6px;" src="1776692284530.jpg"><br>Corte Bob Premium<br><b style="color:#22c55e;">2.500 Kz</b></div>
     <div class="card-item-neon" onclick="atualizarPrecoNoFormulario('Franja Estilizada', 1000)"> <img style="width:100%; border-radius:6px;" src="1776692284530.jpg"><br>Apenas Franja<br><b style="color:#22c55e;">1.000 Kz</b></div>
 </div>

 <!-- Serviços: Tintura Geral -->
 <div id="opcoes-tintura-global" class="grid-servicos opcao-grupo" style="display:none;">
     <div class="card-item-neon" onclick="atualizarPrecoNoFormulario('Coloração Total', 4500)"> <img style="width:100%; border-radius:6px;" src="1776692284530.jpg"><br>Coloração Completa<br><b style="color:#22c55e;">4.500 Kz</b></div>
     <div class="card-item-neon" onclick="atualizarPrecoNoFormulario('Retoque de Raiz', 2500)"> <img style="width:100%; border-radius:6px;" src="1776692284530.jpg"><br>Retoque de Raiz<br><b style="color:#22c55e;">2.500 Kz</b></div>
 </div>

 <!-- Serviços: Mechas / Luzes -->
 <div id="opcoes-mechas" class="grid-servicos opcao-grupo" style="display:none;">
     <div class="card-item-neon" onclick="atualizarPrecoNoFormulario('Luzes Platinadas', 7000)"> <img style="width:100%; border-radius:6px;" src="1776692284530.jpg"><br>Luzes Platinadas<br><b style="color:#22c55e;">7.000 Kz</b></div>
     <div class="card-item-neon" onclick="atualizarPrecoNoFormulario('Mechas Californianas', 6500)"> <img style="width:100%; border-radius:6px;" src="1776692284530.jpg"><br>Mechas Californianas<br><b style="color:#22c55e;">6.500 Kz</b></div>
 </div>

 <!-- Serviços: Design Simples Sobrancelhas -->
 <div id="opcoes-design-simples" class="grid-servicos opcao-grupo" style="display:none;">
     <div class="card-item-neon" onclick="atualizarPrecoNoFormulario('Pinça Visagismo', 1200)"> <img style="width:100%; border-radius:6px;" src="1776692284530.jpg"><br>Limpeza com Pinça<br><b style="color:#22c55e;">1.200 Kz</b></div>
     <div class="card-item-neon" onclick="atualizarPrecoNoFormulario('Navalha Rápida', 800)"> <img style="width:100%; border-radius:6px;" src="1776692284530.jpg"><br>Alinhamento Navalha<br><b style="color:#22c55e;">800 Kz</b></div>
 </div>

 <!-- Serviços: Aplicação de Henna -->
 <div id="opcoes-henna" class="grid-servicos opcao-grupo" style="display:none;">
     <div class="card-item-neon" onclick="atualizarPrecoNoFormulario('Henna Efeito Natural', 2500)"> <img style="width:100%; border-radius:6px;" src="1776692284530.jpg"><br>Henna Natural<br><b style="color:#22c55e;">2.500 Kz</b></div>
     <div class="card-item-neon" onclick="atualizarPrecoNoFormulario('Henna Efeito Marcado', 2800)"> <img style="width:100%; border-radius:6px;" src="1776692284530.jpg"><br>Henna Ombré<br><b style="color:#22c55e;">2.800 Kz</b></div>
 </div>

 <!-- Serviços: Tatuar Sobrancelhas -->
 <div id="opcoes-tatuar-sobrancelhas" class="grid-servicos opcao-grupo" style="display:none;">
     <div class="card-item-neon" onclick="atualizarPrecoNoFormulario('Sobrancelha Normal', 1500)"> <img style="width:100%; border-radius:6px;" src="1776692284530.jpg"><br>Design Tradicional<br><b style="color:#22c55e;">1.500 Kz</b></div>
 </div>

 <!-- Serviços: Maquilhagem Social -->
 <div id="opcoes-make-social" class="grid-servicos opcao-grupo" style="display:none;">
     <div class="card-item-neon" onclick="atualizarPrecoNoFormulario('Make Social Festa', 5000)"> <img style="width:100%; border-radius:6px;" src="1776692284530.jpg"><br>Make Social Festa<br><b style="color:#22c55e;">5.000 Kz</b></div>
     <div class="card-item-neon" onclick="atualizarPrecoNoFormulario('Make Express Casual', 3000)"> <img style="width:100%; border-radius:6px;" src="1776692284530.jpg"><br>Make Casual Rápida<br><b style="color:#22c55e;">3.000 Kz</b></div>
 </div>

 <!-- Serviços: Maquilhagem Noivas -->
 <div id="opcoes-make-noiva" class="grid-servicos opcao-grupo" style="display:none;">
     <div class="card-item-neon" onclick="atualizarPrecoNoFormulario('Pacote Noiva Real', 15000)"> <img style="width:100%; border-radius:6px;" src="1776692284530.jpg"><br>🧾 Pacote Noiva Real<br><b style="color:#22c55e;">15.000 Kz</b></div>
 </div>

 <!-- Serviços: Hidratação -->
 <div id="opcoes-hidratacao" class="grid-servicos opcao-grupo" style="display:none;">
     <div class="card-item-neon" onclick="atualizarPrecoNoFormulario('Nutrição de Óleos', 3500)"> <img style="width:100%; border-radius:6px;" src="1776692284530.jpg"><br>Máscara Nutrição<br><b style="color:#22c55e;">3.500 Kz</b></div>
     <div class="card-item-neon" onclick="atualizarPrecoNoFormulario('Cauterização Térmica', 4500)"> <img style="width:100%; border-radius:6px;" src="1776692284530.jpg"><br>Cauterização Térmica<br><b style="color:#22c55e;">4.500 Kz</b></div>
 </div>

 <!-- Serviços: Queratina -->
 <div id="opcoes-queratina" class="grid-servicos opcao-grupo" style="display:none;">
     <div class="card-item-neon" onclick="atualizarPrecoNoFormulario('Reconstrução Queratina', 5000)"> <img style="width:100%; border-radius:6px;" src="1776692284530.jpg"><br>Reconstrução Capilar<br><b style="color:#22c55e;">5.000 Kz</b></div>
 </div>
</div>

<!-- CONTAINER OPERACIONAL DA REDE AURÉLIUS -->
<div class="seccao-viva-container" id="painel_bloco_reserva" style="max-width: 600px; margin: 30px auto; background: #111827; border: 1px solid #1e293b; padding: 30px; border-radius: 16px; box-shadow: 0 10px 30px rgba(0,0,0,0.5);">
    
    <!-- 🟢 Cabeçalho Corrigido Fixo para a Só Tranças -->
    <div style="text-align: left; margin-bottom: 25px; border-bottom: 1px solid #1f2937; padding-bottom: 15px;">
        <span style="color: #eab308; font-size: 11px; font-weight: bold; text-transform: uppercase;">● O MELHOR ESTÁ AQUI</span>
        <h2 style="color: #fff; font-size: 22px; font-weight: bold; margin: 6px 0;">BARBEARIA SÓ TRANÇAS</h2>
        <p style="color: #94a3b8; font-size: 12.5px; margin: 0;">📍 Endereço: Huíla - Lubango</p>
    </div>
 <form method="POST" action="" id="form_checkout_aurelius_saas" onsubmit="gravarAgendamentoEFaturar(event)">
 <!-- 🟢 Inputs Invisíveis Corrigidos e em Local Correto -->
 <input type="hidden" id="txt_servico_nome" name="servico_nome" value="">
 <input type="hidden" id="txt_servico_preco" name="servico_preco" value="0">

 <div class="campo-grupo">
     <label>Nome do Cliente:</label>
     <input type="text" id="inputNomeCliente" placeholder="Introduza o nome para a fatura" required autocomplete="off">
 </div>

 <div class="campo-grupo">
     <label>Profissional Técnico:</label>
     <select id="inputFuncionario" required>
         <option value="">Selecione um profissional...</option>
         <option value="Handanga">🟢 1º Handanga (Turno: 08h00 - 15h00)</option>
         <option value="Albino">🟢 2º Albino (Turno: 13h00 - 22h00)</option>
     </select>
 </div>

 <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px;">
     <div class="campo-grupo">
         <label>Data do Serviço:</label>
         <input type="date" id="inputDataServico" value="<?= date('Y-m-d') ?>" required>
     </div>
     <div class="campo-grupo">
         <label>Horário da Vaga:</label>
         <input type="time" id="inputHorarioVaga" required>
     </div>
 </div>

 <div style="background: rgba(15, 23, 42, 0.6); padding: 15px; border-radius: 12px; text-align: center; margin-bottom: 20px; border: 1px solid #1e293b;">
     <span style="color: #94a3b8; font-size: 11px; text-transform: uppercase; font-weight: bold; display: block; margin-bottom: 4px;">Valor do Serviço</span>
     <strong style="color: #22c55e; font-size: 20px;" id="lbl_valor_exibicao">0,00 Kz</strong>
 </div>

 <button type="submit" style="width: 100%; background: #22c55e; color: #fff; padding: 14px; border: none; border-radius: 8px; font-size: 15px; font-weight: bold; cursor: pointer; text-transform: uppercase;">
     📱 GERAR FATURA DE RESERVA
 </button>
</form>
</div>
</div>


<!-- =========================================================================
     💇 ABA: GESTÃO DE CORPO TÉCNICO (Inicia Oculta)
     ========================================================================= -->
     <div id="aba_funcionarios" class="tab-content" style="display: none;">
    <div style="text-align: left; margin-bottom: 25px; border-bottom: 1px solid #334155; padding-bottom: 15px;">
        <h3 style="color: #eab308; text-transform: uppercase; font-size: 16px; letter-spacing: 1px;">💇 Gestão de Corpo Técnico</h3>
        <p style="color: #94a3b8; font-size: 12px;">Clique no profissional para consultar a pauta de disponibilidade em tempo real.</p>
    </div>

    <!-- Grade de Cards (Busca automática do Banco de Dados) -->
    <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(180px, 1fr)); gap: 20px;">
        <?php 
        // Reutilizando a lógica de busca que já configuramos para a Só Tranças (ID 242)
        try {
            $stmt_f = $pdo->prepare("SELECT nome, status, especialidade FROM funcionarios WHERE ativo = 1 ORDER BY nome ASC");
            $stmt_f->execute();
            $equipa = $stmt_f->fetchAll(PDO::FETCH_ASSOC);

            foreach($equipa as $f): 
                $status_clean = (strtolower($f['status']) == 'disponível' || $f['status'] == 'Disponivel') ? 'Disponível' : 'Ausente';
                $cor_foco = ($status_clean == 'Disponível') ? '#22c55e' : '#ef4444';
        ?>
            <div class="card-tecnico-premium" onclick="abrirAgendaFuncionario('<?= htmlspecialchars($f['nome']) ?>', '<?= $status_clean ?>', '<?= htmlspecialchars($f['especialidade']) ?>')" 
                 style="background: #1e293b; border: 1px solid #334155; padding: 25px 15px; border-radius: 16px; text-align: center; cursor: pointer; transition: 0.3s; position: relative;">
                
                <div style="position: absolute; top: 0; left: 0; width: 100%; height: 4px; background: <?= $cor_foco ?>; border-radius: 16px 16px 0 0;"></div>
                <div style="width: 60px; height: 60px; background: #0f172a; border-radius: 50%; margin: 0 auto 15px; display: flex; align-items: center; justify-content: center; font-size: 24px; border: 2px solid #334155;">👤</div>
                
                <strong style="display: block; font-size: 14px; color: #fff;"><?= htmlspecialchars($f['nome']) ?></strong>
                <small style="display: block; color: #64748b; margin-bottom: 10px; font-size: 11px;"><?= htmlspecialchars($f['especialidade']) ?></small>
                
                <span style="color: <?= $cor_foco ?>; font-size: 10px; font-weight: 800; text-transform: uppercase;">
                    ● <?= $status_clean ?>
                </span>
            </div>
        <?php endforeach; } catch (Exception $e) { echo "Erro ao carregar equipa."; } ?>
    </div>

    <!-- Área da Agenda Detalhada (Aparece ao clicar no Card) -->
    <div id="pauta_detalhada_func" style="display: none; margin-top: 30px;">
        <!-- (O JavaScript vai preencher aqui os horários como fizemos anteriormente) -->
    </div>
</div>
<script>
// 🟢 FUNÇÃO MESTRE: Fecha tudo no carregamento
window.addEventListener('DOMContentLoaded', () => {
    // Esconde todas as abas (Serviços, Agenda, Funcionários)
    const abas = document.querySelectorAll('.tab-content');
    abas.forEach(aba => {
        aba.style.display = 'none';
        aba.classList.remove('active');
    });

    // Remove destaque de botões do menu
    const links = document.querySelectorAll('.tab-link');
    links.forEach(link => link.classList.remove('active'));

    console.log("Sistema Só Tranças: Abas reiniciadas e fechadas.");
});

// 🟢 FUNÇÃO SWITCH: Abre apenas o que foi clicado
function switchTab(event, tabId) {
    if (event) {
        event.preventDefault();
    }

    // 1. Esconde tudo primeiro
    const contents = document.querySelectorAll('.tab-content');
    contents.forEach(c => c.style.display = 'none');

    // 2. Desativa os links
    const links = document.querySelectorAll('.tab-link');
    links.forEach(l => l.classList.remove('active'));

    // 3. Abre a aba desejada
    const alvo = document.getElementById(tabId);
    if (alvo) {
        alvo.style.display = 'block';
        if (event) event.currentTarget.classList.add('active');
        
        // Se abrir funcionários, garante que a agenda interna dele esteja fechada
        const pauta = document.getElementById('pauta_detalhada_func');
        if(pauta) pauta.style.display = 'none';
    }
}
</script>





<div id="pagamentos" class="tab-content">
    
    <!-- Seção de Estilos Fornecida (Isolada para esta Aba) -->
    <style>
        .tab-content * { box-sizing: border-box; font-family: 'Segoe UI', Arial, sans-serif; }
        .seccao-cadastro { position: relative; background: #1e293b; padding: 35px; width: 92%; max-width: 520px; margin: 40px auto; border-radius: 20px; text-align: left; border: 2px solid #38bdf8; box-shadow: 0 0 20px rgba(56, 189, 248, 0.4), inset 0 0 15px rgba(56, 189, 248, 0.1); animation: pulsarGlow 4s infinite alternate; color: #f8fafc; }
        @keyframes pulsarGlow { 0% { box-shadow: 0 0 12px rgba(56, 189, 248, 0.3); border-color: #0284c7; } 100% { box-shadow: 0 0 25px rgba(56, 189, 248, 0.7); border-color: #38bdf8; } }
        .btn-fechar-top { position: absolute; top: -15px; right: -15px; width: 36px; height: 36px; background: linear-gradient(135deg, #ef4444, #dc2626); color: white; border-radius: 50%; display: flex; align-items: center; justify-content: center; text-decoration: none; font-weight: bold; border: 2px solid #1e293b; }
        .seccao-cadastro h2 { margin-bottom: 20px; color: #38bdf8; font-size: 20px; border-bottom: 2px solid #334155; padding-bottom: 10px; text-transform: uppercase; font-weight: bold; }
        .campo-grupo { margin-bottom: 18px; display: flex; flex-direction: column; }
        .campo-grupo label { font-weight: bold; font-size: 12px; margin-bottom: 6px; color: #94a3b8; text-transform: uppercase; }
        .campo-grupo input { padding: 14px; border: 1px solid #475569; border-radius: 8px; font-size: 15px; background: #0f172a; color: white; outline: none; }
        .botoes-pagamento { display: flex; gap: 10px; margin-top: 15px; flex-wrap: wrap; }
        .btn-pagar { flex: 1; min-width: 120px; padding: 14px; border: none; border-radius: 8px; font-weight: bold; cursor: pointer; color: white; font-size: 12px; text-transform: uppercase; }
        .btn-unitel { background: linear-gradient(135deg, #ff6600, #cc5200); }
        .btn-express { background: linear-gradient(135deg, #003399, #002266); }
        .btn-saldo-interno { background: linear-gradient(135deg, #22c55e, #16a34a); color: #000; width: 100%; display: block; margin-top: 15px; font-weight: bold; }
        .fatura-box { background: #0b0f19; padding: 16px; border-radius: 12px; border: 1px solid #22314d; margin-bottom: 18px; font-size: 13px; }
        .linha-fatura { display: flex; justify-content: space-between; border-bottom: 1px dashed #334155; padding-bottom: 6px; margin-bottom: 6px; color: #f8fafc; }
        .linha-fatura.total-row { border-bottom: none; font-weight: bold; font-size: 16px; color: #22c55e; margin-top: 10px; }
        .badge-vip-alerta { background: rgba(234, 179, 8, 0.1); border: 1px solid #eab308; color: #eab308; padding: 12px; border-radius: 8px; font-size: 13px; font-weight: bold; margin-bottom: 18px; text-align: center; display: none; }
        .erro-fatura-msg { background: rgba(239, 68, 68, 0.1); border: 1px solid #ef4444; color: #f87171; padding: 12px; border-radius: 8px; font-weight: bold; margin-bottom: 15px; text-align: center; display: none; }
    </style>

<div class="seccao-cadastro">
<a href="Dashboard.php" class="btn-fechar-top">&times;</a>
<h2>Checkout de Caixa Sincronizado</h2>

<div id="msg_erro_fatura" class="erro-fatura-msg">⚠️ OPERAÇÃO REJEITADA: Este cliente não possui faturas pendentes!</div>
<div id="status_carteira_box" class="badge-vip-alerta"></div>

<div id="caixa_detalhe_pedido" style="background: #0f172a; padding: 14px; border-radius: 10px; margin-bottom: 18px; border-left: 4px solid #38bdf8; font-size: 13px; display: none;">
    <span>Fatura Pendente Localizada:</span>
    <strong style="display:block; color:#fff; font-size:15px; margin-top:3px;" id="txt_lbl_servico">---</strong>
</div>

<form id="form_unitel_real" method="POST" action="">
    <input type="hidden" name="executar_venda_final" value="1">
    <input type="hidden" name="id_pagamento_real" id="id_pagamento_real" value="0">
    <input type="hidden" name="metodo_gateway" id="txt_gateway_metodo" value="Unitel Money">

    <div class="campo-grupo">
        <label>Nome do Cliente:</label>
        <input type="text" name="nome_cliente" id="nome_input" placeholder="Insira o nome exato do agendamento" onkeyup="sincronizarFaturaPorNome(this.value)" required autocomplete="off">
    </div>

    <div class="campo-grupo">
        <label>Telefone / BI (Assinatura VIP):</label>
        <input type="tel" name="cliente_telefone" id="telefone_input" placeholder="Insira o número" required>
    </div>

    <div class="campo-grupo" id="wrapper_valor_pago">
        <label>Valor Entregue / Pago (AKZ):</label>
        <input type="number" step="0.01" name="valor_entregue" id="valor_entregue_input" value="0.00" min="0" required oninput="calcularTrocoMesa(this.value)">
    </div>

    <div class="fatura-box">
        <div class="linha-fatura"><span>Valor do Serviço:</span><span id="f_servico">0,00 AKZ</span></div>
        <div class="linha-fatura"><span>Subtotal Base:</span><span id="f_subtotal">0,00 AKZ</span></div>
        <div class="linha-fatura" id="linha_desconto_vip" style="display:none; color:#eab308;"><span>Desconto VIP (20%):</span><span id="txt_desc_vip">0,00 AKZ</span></div>
        <div class="linha-fatura" style="color:#64748b;"><span>Desconto Cortesia:</span><span id="f_taxa">-0,00 AKZ</span></div>
        <div class="linha-fatura total-row"><span>Total Líquido a Pagar:</span><span id="txt_total_liquido">0,00 AKZ</span></div>
    </div>

    <div class="botoes-pagamento" id="gateways_externos_bloco">
        <button type="submit" class="btn-pagar btn-unitel" onclick="setGateway('Unitel Money')">Unitel Money</button>
        <button type="submit" class="btn-pagar btn-express" onclick="setGateway('MCX Express')">MCX Express</button>
    </div>
</form>
</div>
</div>




           
    </div>
</div>

<!-- SCRIPT JAVASCRIPT PARA ALTERNAR AS ABAS -->
<script>
function switchTab(event, tabId) {
    event.preventDefault(); 

    const contents = document.querySelectorAll('.tab-content');
    contents.forEach(content => content.classList.remove('active'));

    const links = document.querySelectorAll('.tab-link');
    links.forEach(link => link.classList.remove('active'));

    document.getElementById(tabId).classList.add('active');
    event.currentTarget.classList.add('active');

    const navbar = document.getElementById('navbar-menu');
    if(navbar.classList.contains('show')) {
        navbar.classList.remove('show');
    }
}

function toggleMobileMenu() {
    const navbar = document.getElementById('navbar-menu');
    navbar.classList.toggle('show');
}
</script>


<?php
// 1. Configurações Iniciais
$id_empresa_ativa = 242; // ID SóTranças
$hoje = date('Y-m-d');
$expediente = ['08:00', '09:00', '10:00', '11:00', '12:00', '14:00', '15:00', '16:00', '17:00', '18:00'];

// 2. Busca todos os registos de HOJE
$stmt = $pdo->prepare("SELECT * FROM pagamentos WHERE id_parceiro = ? AND data_servico = ? ORDER BY hora_servico ASC");
$stmt->execute([$id_empresa_ativa, $hoje]);
$registos_hoje = $stmt->fetchAll(PDO::FETCH_ASSOC);

// 3. Cria lista de horas ocupadas para mudar a cor dos botões
$horas_ocupadas = array_column($registos_hoje, 'hora_servico');
// Limpa os segundos (08:00:00 -> 08:00) para comparar corretamente
$horas_ocupadas = array_map(function($h) { return substr($h, 0, 5); }, $horas_ocupadas);
?>

<div class="agenda-container" style="background: #111827; padding: 20px; border-radius: 12px; color: #fff;">
    <h3 style="color: #eab308;">📋 Serviços Marcados para Hoje (<?= date('d/m/Y') ?>)</h3>
    
    <table style="width: 100%; border-collapse: collapse; font-size: 13px; margin-bottom: 30px;">
        <thead>
            <tr style="border-bottom: 2px solid #334155; color: #94a3b8; text-align: left;">
                <th style="padding: 10px;">Ticket</th>
                <th>Técnico</th>
                <th>Serviço</th>
                <th>Hora</th>
                <th>Ação</th>
            </tr>
        </thead>
        <tbody>
            <?php if($registos_hoje): foreach($registos_hoje as $reg): ?>
           <!-- A tabela agora é apenas informativa para os clientes -->
<tr style="border-bottom: 1px solid #1e293b;">
<td style="padding: 12px 10px; color: #64748b;">#<?= $reg['id_pagamento'] ?></td>
<td><strong style="color: #38bdf8;"><?= htmlspecialchars($reg['funcionario']) ?></strong></td>
<td><?= htmlspecialchars($reg['servico']) ?></td>
<td style="color: #eab308; font-weight: bold;"><?= substr($reg['hora_servico'], 0, 5) ?></td>
</tr>
            <?php endforeach; else: ?>
            <tr><td colspan="5" style="padding: 20px; text-align: center; color: #64748b;">Nenhuma marcação para hoje.</td></tr>
            <?php endif; ?>
        </tbody>
    </table>

    <h3 style="color: #38bdf8;">⏳ Mapa de Disponibilidade</h3>
    <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(100px, 1fr)); gap: 10px;">
        <?php foreach($expediente as $hora): 
            $esta_ocupado = in_array($hora, $horas_ocupadas);
        ?>
            <div style="padding: 10px; border-radius: 8px; text-align: center; border: 1px solid <?= $esta_ocupado ? '#ef4444' : '#22c55e' ?>; background: <?= $esta_ocupado ? 'rgba(239, 68, 68, 0.1)' : 'rgba(34, 197, 94, 0.1)' ?>;">
                <strong style="display: block; color: #fff;"><?= $hora ?></strong>
                <span style="font-size: 10px; font-weight: bold; color: <?= $esta_ocupado ? '#f87171' : '#4ade80' ?>;">
                    <?= $esta_ocupado ? '🔴 OCUPADO' : '🟢 LIVRE' ?>
                </span>
            </div>
        <?php endforeach; ?>
    </div>
</div>












<?php
// 1. Configurações de Identificação e Tempo
$id_loja_sotranças = 242; 
$hoje_consulta = date('Y-m-d');
$grade_horaria = ['08:00', '09:00', '10:00', '11:00', '12:00', '14:00', '15:00', '16:00', '17:00', '18:00'];

try {
    // 2. BUSCA REAL: Lê a tabela 'funcionarios' conforme a sua estrutura do phpMyAdmin
    // Adicionei um filtro 'ativo = 1' para mostrar apenas quem não foi deletado logicamente
    $stmt_f = $pdo->prepare("SELECT id_funcionario, nome, status, especialidade FROM funcionarios WHERE ativo = 1 ORDER BY nome ASC");
    $stmt_f->execute();
    $corpo_tecnico = $stmt_f->fetchAll(PDO::FETCH_ASSOC);

    // 3. CRUZAMENTO DE DADOS: Busca marcações na tabela 'pagamentos' para ver quem está ocupado
    $stmt_m = $pdo->prepare("SELECT funcionario, hora_servico FROM pagamentos WHERE data_servico = ?");
    $stmt_m->execute([$hoje_consulta]);
    $marcacoes_db = $stmt_m->fetchAll(PDO::FETCH_ASSOC);

    $ocupacao_tecnica = [];
    foreach ($marcacoes_db as $m) {
        $ocupacao_tecnica[$m['funcionario']][] = substr($m['hora_servico'], 0, 5);
    }
} catch (Exception $e) { $corpo_tecnico = []; }
?>

<div class="modulo-equipa-sotranças" style="max-width: 900px; margin: 0 auto; color: #fff;">
    
    <!-- ECRÃ 1: LISTA DINÂMICA DE CARDS -->
    <div id="view_lista_profissionais">
        <div style="text-align: left; margin-bottom: 25px; border-bottom: 1px solid #334155; padding-bottom: 15px;">
            <h3 style="color: #eab308; text-transform: uppercase; font-size: 16px; letter-spacing: 1px;">💇 Gestão de Corpo Técnico</h3>
            <p style="color: #94a3b8; font-size: 12px;">Clique no profissional para consultar a pauta de disponibilidade em tempo real.</p>
        </div>

        <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(180px, 1fr)); gap: 20px;">
            <?php if(!empty($corpo_tecnico)): foreach($corpo_tecnico as $f): 
                $status_txt = (strtolower($f['status']) == 'disponível' || $f['status'] == 'Disponivel') ? 'Disponível' : 'Ausente';
                $cor_foco = ($status_txt == 'Disponível') ? '#22c55e' : '#ef4444';
            ?>
                <div class="card-tecnico-premium" onclick="abrirAgendaFuncionario('<?= htmlspecialchars($f['nome']) ?>', '<?= $status_txt ?>', '<?= htmlspecialchars($f['especialidade']) ?>')" 
                     style="background: #1e293b; border: 1px solid #334155; padding: 25px 15px; border-radius: 16px; text-align: center; cursor: pointer; transition: 0.3s; position: relative; overflow: hidden;">
                    
                    <div style="position: absolute; top: 0; left: 0; width: 100%; height: 4px; background: <?= $cor_foco ?>;"></div>
                    
                    <div style="width: 70px; height: 70px; background: #0f172a; border-radius: 50%; margin: 0 auto 15px; display: flex; align-items: center; justify-content: center; font-size: 30px; border: 2px solid #334155;">👤</div>
                    
                    <strong style="display: block; font-size: 15px; margin-bottom: 4px;"><?= htmlspecialchars($f['nome']) ?></strong>
                    <small style="display: block; color: #64748b; margin-bottom: 10px; font-style: italic;"><?= htmlspecialchars($f['especialidade'] ?? 'Técnico Geral') ?></small>
                    
                    <span style="background: <?= $cor_foco ?>22; color: <?= $cor_foco ?>; padding: 4px 10px; border-radius: 20px; font-size: 10px; font-weight: 800; text-transform: uppercase;">
                        ● <?= $status_txt ?>
                    </span>
                </div>
            <?php endforeach; else: ?>
                <div style="grid-column: span 3; padding: 40px; background: #0f172a; border-radius: 12px; text-align: center; color: #64748b;">
                    Nenhum funcionário localizado na tabela `funcionarios`.
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- ECRÃ 2: DETALHES DA AGENDA DO FUNCIONÁRIO (Inicia oculto) -->
    <div id="view_detalhe_agenda" style="display: none; animation: slideIn 0.4s ease-out;">
        <button onclick="voltarParaLista()" style="background: #334155; color: #fff; border: none; padding: 10px 20px; border-radius: 8px; cursor: pointer; margin-bottom: 25px; font-weight: bold; font-size: 12px;">← VOLTAR À EQUIPA</button>
        
        <div style="background: linear-gradient(145deg, #1e293b, #0f172a); border: 1px solid #eab308; padding: 30px; border-radius: 20px; box-shadow: 0 10px 30px rgba(0,0,0,0.5);">
            <div style="display: flex; align-items: center; gap: 25px; margin-bottom: 30px; border-bottom: 1px solid #334155; padding-bottom: 20px;">
                <div style="width: 90px; height: 90px; background: #111827; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 45px; border: 3px solid #eab308;">👤</div>
                <div>
                    <h2 id="txt_nome_foco" style="margin: 0; color: #fff; font-size: 24px;">---</h2>
                    <p id="txt_especialidade_foco" style="margin: 5px 0; color: #38bdf8; font-size: 14px;">---</p>
                    <span id="badge_status_foco" style="font-weight: bold; font-size: 11px; text-transform: uppercase;">---</span>
                </div>
            </div>

            <h4 style="color: #94a3b8; text-transform: uppercase; font-size: 11px; margin-bottom: 20px; font-weight: bold; letter-spacing: 1px;">⏳ Horários para Hoje (<?= date('d/m') ?>):</h4>
            <div id="grid_vagas_funcionario" style="display: grid; grid-template-columns: repeat(auto-fill, minmax(110px, 1fr)); gap: 12px;">
                <!-- Gerado dinamicamente pelo JavaScript -->
            </div>
        </div>
    </div>
</div>

<script>
// Transmissão de dados do PHP para o Motor Reativo JS
const mapaOcupacaoGlobal = <?= json_encode($ocupacao_tecnica) ?>;
const listaHorasPadrao = <?= json_encode($grade_horaria) ?>;

function abrirAgendaFuncionario(nome, status, especialidade) {
    document.getElementById('view_lista_profissionais').style.display = 'none';
    document.getElementById('view_detalhe_agenda').style.display = 'block';
    
    document.getElementById('txt_nome_foco').innerText = nome;
    document.getElementById('txt_especialidade_foco').innerText = especialidade || 'Técnico de Beleza';
    
    const badge = document.getElementById('badge_status_foco');
    badge.innerText = "● " + status;
    badge.style.color = (status === 'Disponível') ? '#22c55e' : '#ef4444';

    const contentorVagas = document.getElementById('grid_vagas_funcionario');
    contentorVagas.innerHTML = '';

    if (status !== 'Disponível') {
        contentorVagas.innerHTML = '<div style="grid-column: 1/-1; padding: 20px; background: rgba(239, 68, 68, 0.1); color: #f87171; border-radius: 8px; text-align: center;">Este profissional encontra-se ausente ou em folga hoje.</div>';
        return;
    }

    // Lógica de Construção da Pauta de Horas
    listaHorasPadrao.forEach(hora => {
        const isOcupado = mapaOcupacaoGlobal[nome] && mapaOcupacaoGlobal[nome].includes(hora);
        const div = document.createElement('div');
        
        div.style.cssText = `
            padding: 15px 10px; border-radius: 10px; text-align: center; font-size: 13px; 
            border: 1px solid ${isOcupado ? '#ef4444' : '#22c55e'}; 
            background: ${isOcupado ? 'rgba(239, 68, 68, 0.1)' : 'rgba(34, 197, 94, 0.1)'};
            transition: 0.3s;
        `;
        
        div.innerHTML = `
            <strong style="display:block; color:#fff;">${hora}</strong>
            <span style="font-size: 9px; font-weight:bold; color: ${isOcupado ? '#f87171' : '#4ade80'};">
                ${isOcupado ? '🔴 OCUPADO' : '🟢 LIVRE'}
            </span>
        `;
        contentorVagas.appendChild(div);
    });
}

function voltarParaLista() {
    document.getElementById('view_lista_profissionais').style.display = 'block';
    document.getElementById('view_detalhe_agenda').style.display = 'none';
}
</script>

<style>
@keyframes slideIn {
    from { opacity: 0; transform: translateY(20px); }
    to { opacity: 1; transform: translateY(0); }
}
.card-tecnico-premium:hover {
    transform: translateY(-5px);
    box-shadow: 0 10px 20px rgba(0,0,0,0.4);
    border-color: #eab308;
}
</style>



















<!-- =========================================================================
     🔮 SECCÃO VIVA CONTAINER: SISTEMA DE VERIFICAÇÃO OPERACIONAL COESORA
     ========================================================================= -->
     <?php
     include_once("Conexao.php");
     if (session_status() === PHP_SESSION_NONE) { session_start(); }
     
     // 🔑 CAPTURA UNIVERSAL DO PARCEIRO ATIVO
     $id_empresa_ativa = isset($_GET['id_parceiro']) ? intval($_GET['id_parceiro']) : (isset($_SESSION['codigo_loja']) ? intval($_SESSION['codigo_loja']) : 242);
     
     try {
         $stmt_empresa = $pdo->prepare("SELECT nome, endereco, nivel FROM `usuario` WHERE `codigo` = ? LIMIT 1");
         $stmt_empresa->execute([$id_empresa_ativa]);
         $dados_empresa_bd = $stmt_empresa->fetch(PDO::FETCH_ASSOC);
     
         if ($dados_empresa_bd) {
             $nome_salao_dinamico = $dados_empresa_bd['nome'];
             $endereco_salao_dinamico = $dados_empresa_bd['endereco'];
         } else {
             $nome_salao_dinamico = "BARBEARIA SÓ TRANÇAS";
             $endereco_salao_dinamico = "Huíla - Lubango";
         }
     } catch (PDOException $e) {
         $nome_salao_dinamico = "BARBEARIA SÓ TRANÇAS";
         $endereco_salao_dinamico = "Huíla - Lubango";
     }
     ?>

     

       


     
     <script>
    // 🟢 CONTROLADOR DE ABAS AURÉLIUS - VERSÃO DESTRAVADA
function switchTab(event, tabId) {
    // 1. Impede que o link recarregue a página ou cause saltos
    if (event) {
        event.preventDefault();
        event.stopPropagation();
    }

    console.log("A abrir aba: " + tabId); // Debug para ver se o clique funciona

    // 2. Esconde todas as abas de forma absoluta e limpa
    const contents = document.querySelectorAll('.tab-content');
    contents.forEach(content => {
        content.classList.remove('active');
        content.style.display = 'none'; // Garante a ocultação
    });

    // 3. Remove o estado ativo de todos os links do menu
    const links = document.querySelectorAll('.tab-link');
    links.forEach(link => link.classList.remove('active'));

    // 4. Mostra a aba selecionada e destaca o botão
    const targetTab = document.getElementById(tabId);
    if (targetTab) {
        targetTab.classList.add('active');
        targetTab.style.display = 'block'; // Força a exibição imediata
    }

    if (event && event.currentTarget) {
        event.currentTarget.classList.add('active');
    }

    // 5. Fecha o menu mobile (hambúrguer) se estiver aberto
    const navbar = document.getElementById('navbar-menu');
    if (navbar && navbar.classList.contains('show')) {
        navbar.classList.remove('show');
    }
}

// 🟢 INICIALIZAÇÃO SEGURA (Prevenir travamento ao atualizar)
window.addEventListener('load', () => {
    // Garante que tudo inicie fechado de forma limpa, mas sem quebrar o motor de cliques
    const contents = document.querySelectorAll('.tab-content');
    contents.forEach(content => {
        content.style.display = 'none';
        content.classList.remove('active');
    });

    const links = document.querySelectorAll('.tab-link');
    links.forEach(link => link.classList.remove('active'));

    // Limpa âncoras da URL (#pagamentos, etc) para evitar bugs de navegação do browser
    if (window.location.hash) {
        history.replaceState("", document.title, window.location.pathname + window.location.search);
    }
});









// Função para o menu hambúrguer no telemóvel
function toggleMobileMenu() {
    const navbar = document.getElementById('navbar-menu');
    if (navbar) {
        navbar.classList.toggle('show');
    }
}




function deletarAgendamento(id) {
    if (confirm("Deseja remover este atendimento da agenda? Esta ação é irreversível.")) {
        const formData = new URLSearchParams();
        formData.append('id_deletar', id);

        fetch('deletar_reserva.php', {
            method: 'POST',
            body: formData
        })
        .then(() => {
            alert("Sucesso: Horário libertado!");
            window.location.reload(); // Recarrega para atualizar o mapa de verde/vermelho
        })
        .catch(() => alert("Erro ao comunicar com o banco de dados."));
    }
}
</script>













<!-- =========================================================================
     🔮 MÓDULO DE SERVIÇOS E AGENDAMENTO INTEGRADO (PREMIUM DARK NEON)
     ========================================================================= -->
     <style>
     /* Estilos Visuais Obrigatórios para Evitar Travamento */
     .tab-content { display: none; }
     .tab-content.active { display: block !important; }
     .grid-servicos { display: grid; grid-template-columns: repeat(auto-fill, minmax(140px, 1fr)); gap: 15px; padding: 15px 0; }
     .card-item-neon { background: #1e293b; border: 1px solid #334155; color: #fff; padding: 15px 10px; border-radius: 12px; cursor: pointer; transition: 0.3s; text-align: center; border-bottom: 3px solid #334155; }
     .card-item-neon:hover { border-color: #38bdf8; background: #0f172a; box-shadow: 0 0 10px rgba(56, 189, 248, 0.4); transform: translateY(-2px); }
     .btn-voltar-neon { background: #1e293b; color: #38bdf8; border: 1px solid #38bdf8; padding: 8px 16px; border-radius: 6px; cursor: pointer; font-size: 12px; font-weight: bold; transition: 0.3s; margin-bottom: 15px; display: inline-block; }
     .btn-voltar-neon:hover { background: #38bdf8; color: #090514; box-shadow: 0 0 10px rgba(56, 189, 248, 0.5); }
     .campo-grupo { margin-bottom: 18px; display: flex; flex-direction: column; text-align: left; }
     .campo-grupo label { font-weight: bold; font-size: 11px; margin-bottom: 6px; color: #38bdf8; text-transform: uppercase; letter-spacing: 0.5px; }
     .campo-grupo input, .campo-grupo select { padding: 12px; border: 1px solid #334155; border-radius: 8px; font-size: 13.5px; background: #070b12; color: white; outline: none; }
 </style>
 
 <!-- 📋 ABA 1: SERVIÇOS 3 NÍVEIS (Inicia ativa por padrão) -->
 <div id="aba_servicos" class="tab-content active">
     
     <!-- PASSO 1: CATEGORIAS PRINCIPAIS -->
     <div id="sub_container_nivel1" style="display: block; text-align: left;">
         <span style="color:#94a3b8; font-size:13px; display:block; margin-bottom:10px;">PASSO 1: Selecione a Categoria Principal</span>
         <div class="grid-servicos">
             <div class="card-item-neon" onclick="mostrarNivel2('cortes')"> <img style="width:100%; border-radius:6px; margin-bottom:8px;" src="1776692284530.jpg"> <br>💇 Cortes de Cabelo</div>
             <div class="card-item-neon" onclick="mostrarNivel2('pinturas')"> <img style="width:100%; border-radius:6px; margin-bottom:8px;" src="1776692284530.jpg"> <br>🎨 Pinturas de Cabelo</div>
             <div class="card-item-neon" onclick="mostrarNivel2('sobrancelhas')"> <img style="width:100%; border-radius:6px; margin-bottom:8px;" src="1776692284530.jpg"> <br>✨ Design Sobrancelhas</div>
             <div class="card-item-neon" onclick="mostrarNivel2('maquilhagem')"> <img style="width:100%; border-radius:6px; margin-bottom:8px;" src="1776692284530.jpg"> <br>💄 Maquilhagens</div>
             <div class="card-item-neon" onclick="mostrarNivel2('tratamentos')"> <img style="width:100%; border-radius:6px; margin-bottom:8px;" src="1776692284530.jpg"> <br>🌿 Tratamentos Capilares</div>
         </div>
     </div>
 
     <!-- PASSO 2: SUBCATEGORIAS -->
     <div id="sub_container_nivel2" style="display: none; text-align: left;">
         <button type="button" class="btn-voltar-neon" onclick="voltarParaNivel1()">← Voltar às Categorias</button>
         <span style="color:#94a3b8; font-size:13px; display:block; margin-bottom:10px;">PASSO 2: Selecione a Subcategoria Específica</span>
         
         <div id="sub-cortes" class="grid-servicos sub-grupo" style="display:none;">
             <div class="card-item-neon" onclick="mostrarNivel3('cortes-masculinos')"> <img style="width:100%; border-radius:6px;" src="1776692284530.jpg"><br>🧔 Cortes Masculinos</div>
             <div class="card-item-neon" onclick="mostrarNivel3('cortes-femininos')"> <img style="width:100%; border-radius:6px;" src="1776692587785.jpg"><br>👩 Cortes Femininos</div>
         </div>
 
         <div id="sub-pinturas" class="grid-servicos sub-grupo" style="display:none;">
             <div class="card-item-neon" onclick="mostrarNivel3('tintura-global')"> <img style="width:100%; border-radius:6px;" src="1776692284530.jpg"><br>Tintura Geral</div>
             <div class="card-item-neon" onclick="mostrarNivel3('mechas')"> <img style="width:100%; border-radius:6px;" src="1776692284530.jpg"><br>Mechas / Luzes</div>
         </div>
 
         <div id="sub-sobrancelhas" class="grid-servicos sub-grupo" style="display:none;">
             <div class="card-item-neon" onclick="mostrarNivel3('design-simples')"> <img style="width:100%; border-radius:6px;" src="1776692284530.jpg"><br>Design Simples</div>
             <div class="card-item-neon" onclick="mostrarNivel3('henna')"> <img style="width:100%; border-radius:6px;" src="1776692284530.jpg"><br>Aplicação de Henna</div>
             <div class="card-item-neon" onclick="mostrarNivel3('tatuar-sobrancelhas')"><img style="width:100%; border-radius:6px;" src="1776692284530.jpg"><br>Sobrancelhas Normal</div>
         </div>
 
         <div id="sub-maquilhagem" class="grid-servicos sub-grupo" style="display:none;">
             <div class="card-item-neon" onclick="mostrarNivel3('make-social')"> <img style="width:100%; border-radius:6px;" src="1776692284530.jpg"><br>Maquilhagem Social</div>
             <div class="card-item-neon" onclick="mostrarNivel3('make-noiva')">💄 <img style="width:100%; border-radius:6px;" src="1776692284530.jpg"><br>Maquilhagem Noivas</div>
         </div>
 
         <div id="sub-tratamentos" class="grid-servicos sub-grupo" style="display:none;">
             <div class="card-item-neon" onclick="mostrarNivel3('hidratacao')"> <img style="width:100%; border-radius:6px;" src="1776692284530.jpg"><br>🌿 Hidratação Profunda</div>
             <div class="card-item-neon" onclick="mostrarNivel3('queratina')"> <img style="width:100%; border-radius:6px;" src="1776692284530.jpg"><br>💎 Banho de Queratina</div>
         </div>
     </div>
 
     <!-- PASSO 3: PRODUTOS ESPECÍFICOS E PREÇOS -->
     <div id="sub_container_nivel3" style="display: none; text-align: left;">
         <button type="button" class="btn-voltar-neon" onclick="voltarParaNivel2()">← Voltar</button>
         <h2 style="font-size: 14px; color: #eab308; text-transform: uppercase; margin: 15px 0; font-weight: bold;">PASSO 3: Tipos de Serviços Disponíveis</h2>
 
         <div id="opcoes-cortes-masculinos" class="grid-servicos opcao-grupo" style="display:none;">
             <div class="card-item-neon" onclick="selecionarServicoFinal('Francês Adulto', 2000)"> <img style="width:100%; border-radius:6px;" src="1776692284530.jpg"><br>Francês Adulto<br><b style="color:#22c55e;">2.000 Kz</b></div>
             <div class="card-item-neon" onclick="selecionarServicoFinal('Francês Criança', 1500)"> <img style="width:100%; border-radius:6px;" src="1776692284530.jpg"><br>Francês Criança<br><b style="color:#22c55e;">1.500 Kz</b></div>
             <div class="card-item-neon" onclick="selecionarServicoFinal('Covinha Adulto', 2000)"> <img style="width:100%; border-radius:6px;" src="1776692284530.jpg"><br>Covinha Adulto<br><b style="color:#22c55e;">2.000 Kz</b></div>
             <div class="card-item-neon" onclick="selecionarServicoFinal('Covinha Criança', 1500)"> <img style="width:100%; border-radius:6px;" src="1776692284530.jpg"><br>Covinha Criança<br><b style="color:#22c55e;">1.500 Kz</b></div>
             <div class="card-item-neon" onclick="selecionarServicoFinal('Barba Imperial', 1000)"> <img style="width:100%; border-radius:6px;" src="1776692284530.jpg"><br>Barba Imperial<br><b style="color:#22c55e;">1.000 Kz</b></div>
             <div class="card-item-neon" onclick="selecionarServicoFinal('Careca Completa', 800)"> <img style="width:100%; border-radius:6px;" src="1776692284530.jpg"><br>Careca Completa<br><b style="color:#22c55e;">800 Kz</b></div>
         </div>
 
         <div id="opcoes-cortes-femininos" class="grid-servicos opcao-grupo" style="display:none;">
             <div class="card-item-neon" onclick="selecionarServicoFinal('Corte Bob Premium', 2500)"> <img style="width:100%; border-radius:6px;" src="1776692284530.jpg"><br>Corte Bob Premium<br><b style="color:#22c55e;">2.500 Kz</b></div>
             <div class="card-item-neon" onclick="selecionarServicoFinal('Franja Estilizada', 1000)"> <img style="width:100%; border-radius:6px;" src="1776692284530.jpg"><br>Apenas Franja<br><b style="color:#22c55e;">1.000 Kz</b></div>
         </div>
 
         <div id="opcoes-tintura-global" class="grid-servicos opcao-grupo" style="display:none;">
             <div class="card-item-neon" onclick="selecionarServicoFinal('Coloração Total', 4500)"> <img style="width:100%; border-radius:6px;" src="1776692284530.jpg"><br>Coloração Completa<br><b style="color:#22c55e;">4.500 Kz</b></div>
             <div class="card-item-neon" onclick="selecionarServicoFinal('Retoque de Raiz', 2500)"> <img style="width:100%; border-radius:6px;" src="1776692284530.jpg"><br>Retoque de Raiz<br><b style="color:#22c55e;">2.500 Kz</b></div>
         </div>
 
         <div id="opcoes-mechas" class="grid-servicos opcao-grupo" style="display:none;">
             <div class="card-item-neon" onclick="selecionarServicoFinal('Luzes Platinadas', 7000)"> <img style="width:100%; border-radius:6px;" src="1776692284530.jpg"><br>Luzes Platinadas<br><b style="color:#22c55e;">7.000 Kz</b></div>
             <div class="card-item-neon" onclick="selecionarServicoFinal('Mechas Californianas', 6500)"> <img style="width:100%; border-radius:6px;" src="1776692284530.jpg"><br>Mechas Californianas<br><b style="color:#22c55e;">6.500 Kz</b></div>
         </div>
 
         <div id="opcoes-design-simples" class="grid-servicos opcao-grupo" style="display:none;">
 
         <div id="opcoes-design-simples" class="grid-servicos opcao-grupo" style="display:none;">
            <div class="card-item-neon" onclick="selecionarServicoFinal('Pinça Visagismo', 1200)"> <img style="width:100%; border-radius:6px;" src="1776692284530.jpg"><br>Limpeza com Pinça<br><b style="color:#22c55e;">1.200 Kz</b></div>
            <div class="card-item-neon" onclick="selecionarServicoFinal('Navalha Rápida', 800)"> <img style="width:100%; border-radius:6px;" src="1776692284530.jpg"><br>Alinhamento Navalha<br><b style="color:#22c55e;">800 Kz</b></div>
        </div>

        <!-- Serviços: Aplicação de Henna -->
        <div id="opcoes-henna" class="grid-servicos opcao-grupo" style="display:none;">
            <div class="card-item-neon" onclick="selecionarServicoFinal('Henna Efeito Natural', 2500)"> <img style="width:100%; border-radius:6px;" src="1776692284530.jpg"><br>Henna Natural<br><b style="color:#22c55e;">2.500 Kz</b></div>
            <div class="card-item-neon" onclick="selecionarServicoFinal('Henna Efeito Marcado', 2800)"> <img style="width:100%; border-radius:6px;" src="1776692284530.jpg"><br>Henna Ombré<br><b style="color:#22c55e;">2.800 Kz</b></div>
        </div>

        <!-- Serviços: Tatuar Sobrancelhas Normal -->
        <div id="opcoes-tatuar-sobrancelhas" class="grid-servicos opcao-grupo" style="display:none;">
            <div class="card-item-neon" onclick="selecionarServicoFinal('Sobrancelha Normal', 1500)"> <img style="width:100%; border-radius:6px;" src="1776692284530.jpg"><br>Design Tradicional<br><b style="color:#22c55e;">1.500 Kz</b></div>
        </div>

    </div> <!-- Fechamento da div id="sub_container_nivel3" -->
</div> <!-- Fechamento da div mestre da aba id="aba_servicos" -->


















<!-- =================================================================
     📋 BANNER DE CONSENTIMENTO E INTELIGÊNCIA DE NEGÓCIO (BI) REAL
     ================================================================= -->
     <div class="banner-consentimento" id="cookieBanner" style="position: fixed; bottom: 0; left: 0; right: 0; background-color: rgba(15, 23, 42, 0.98); border-top: 2px solid #ca8a04; padding: 15px 25px; display: none; justify-content: space-between; align-items: center; font-size: 12px; color: #94a3b8; z-index: 9999; box-shadow: 0 -5px 20px rgba(0,0,0,0.5); font-family: sans-serif;">
    <div style="padding-right: 20px; line-height: 1.5; text-align: justify;">
        <strong style="color: #ca8a04;">Controlo de Auditoria PWA:</strong> O Grupo Aurélius recolhe métricas estatísticas de navegação anonimizadas, escolhas de serviços estéticos e volumetria financeira para otimização da agenda diária, cálculo do ranking mensal de produtividade e monetização regionalizada no Huambo.
    </div>
    <div style="display: flex; gap: 10px; align-items: center;">
        <button class="btn-aceitar" onclick="processarConsentimentoRealBI()" style="background: linear-gradient(135deg, #22c55e, #16a34a); color: white; border: none; padding: 10px 20px; font-weight: bold; border-radius: 6px; cursor: pointer; white-space: nowrap; text-transform: uppercase; font-size: 11px; letter-spacing: 0.5px; box-shadow: 0 4px 6px rgba(0,0,0,0.2); transition: 0.2s;">
            Aceitar e Permitir Rastreamento
        </button>
    </div>
</div>

<script>

   // =========================================================================
    // 🎁 GATILHO REATIVO: EXIBE E APLICA O DESCONTO DO RANKING AUTOMATICAMENTE
    // =========================================================================
    // Resgata com segurança as variáveis que o PHP injetou na sessão lá no topo
    const corteVipPre = "<?php echo $_SESSION['servico_pre_selecionado'] ?? ''; ?>";
    const percentualVipPre = parseInt("<?php echo $_SESSION['desconto_cupao_ganho'] ?? 0; ?>");

    if (corteVipPre !== "" && percentualVipPre > 0) {
        console.log(`🎁 Cupão detetado: ${corteVipPre} com -${percentualVipPre}%`);

        // 1. Aciona a tua função blindada para preencher os textos e as caixas de preço
        if (typeof exibirPrecoFinal === "function") {
            // Passa o nome do corte e o texto sinalizando o cálculo do prémio
            exibirPrecoFinal(corteVipPre, "A aplicar prémio... kz");
            
            // Fixa os dados nas variáveis globais masters exigidas pelo teu botão de gravação
            window.nomeServicoGlobal = corteVipPre;
            
            // 2. Localiza o teu botão de agendamento e carimba o selo de desconto ganho
            const btnConfirmar = document.querySelector('.btn-confirmar') || document.querySelector('.btn-confirmar-sessao');
            if (btnConfirmar) {
                btnConfirmar.innerHTML = `📅 Confirmar Atendimento (Cupão -${percentualVipPre}% Ativo!)`;
                btnConfirmar.style.background = "linear-gradient(135deg, #22c55e, #16a34a)"; // Força a cor verde de sucesso
                btnConfirmar.style.color = "#ffffff";
                btnConfirmar.style.fontWeight = "bold";
            }
        }
    }


    document.addEventListener("DOMContentLoaded", function() {
        // Verifica se o cliente já aceitou o rastreamento nesta máquina anteriormente
        let consentimentoBI = localStorage.getItem("aurelius_consentimento_bi");
        
        // Se ainda não existir a chave gravada, o motor força a aparição do banner mudando para flex
        if (!consentimentoBI) {
            document.getElementById("cookieBanner").style.display = "flex";
        }
    });

    // Função executada no clique do botão verde para salvar a escolha e sumir com o bloco
    function processarConsentimentoRealBI() {
        localStorage.setItem("aurelius_consentimento_bi", "aceito_auditado");
        document.getElementById("cookieBanner").style.display = "none";
        console.log("✓ Auditoria BI autorizada com sucesso na rede local.");
    }


    // 1. PASSO 1 ➔ PASSO 2 (Escolha da Categoria para Subcategoria)
function mostrarNivel2(categoria) {
    document.getElementById('sub_container_nivel1').style.display = 'none';
    document.getElementById('sub_container_nivel2').style.display = 'block';
    
    document.querySelectorAll('.sub-grupo').forEach(div => div.style.display = 'none');
    const alvo = document.getElementById('sub-' + categoria);
    if (alvo) alvo.style.display = 'grid';
}

// 2. PASSO 2 ➔ PASSO 3 (Escolha da Subcategoria para os Preços Finais)
function mostrarNivel3(subcategoria) {
    document.getElementById('sub_container_nivel2').style.display = 'none';
    document.getElementById('sub_container_nivel3').style.display = 'block';
    
    document.querySelectorAll('.opcao-grupo').forEach(div => div.style.display = 'none');
    const alvo = document.getElementById('opcoes-' + subcategoria);
    if (alvo) alvo.style.display = 'grid';
}

// 3. PASSO 3 ➔ POLÍTICA DE FATURAÇÃO IMEDIATA (Clique no preço gera a Fatura)
function selecionarServicoFinal(nomeServico, precoNumerico) {
    // Guarda temporariamente os dados escolhidos para processar a fatura
    document.getElementById('txt_servico_nome').value = nomeServico;
    document.getElementById('txt_servico_preco').value = precoNumerico;
    
    // Captura o nome do cliente (se preenchido) ou gera anónimo para privacidade
    const nomeClienteInput = document.getElementById('inputNomeCliente');
    const nomeCliente = (nomeClienteInput && nomeClienteInput.value.trim() !== "") ? nomeClienteInput.value.trim() : "Cliente Reservado";
    const funcionarioSelecionado = document.getElementById('inputFuncionario').value || "Profissional a Alocar";
    const dataSelecionada = document.getElementById('inputDataServico').value || new Date().toISOString().split('T')[0];
    const horaSelecionada = document.getElementById('inputHorarioVaga').value || "00:00";

    // 🚀 DISPARADOR AJAX: Grava silenciosamente na tabela `pagamentos` do phpMyAdmin
    const dadosEnvio = new FormData();
    dadosEnvio.append('id_parceiro', '242'); // ID fixo SóTranças
    dadosEnvio.append('tipo_parceiro', 'barbearia');
    dadosEnvio.append('cliente', nomeCliente);
    dadosEnvio.append('funcionario', funcionarioSelecionado);
    dadosEnvio.append('data_servico', dataSelecionada);
    dadosEnvio.append('hora_servico', horaSelecionada);
    dadosEnvio.append('servico', nomeServico);
    dadosEnvio.append('valor', precoNumerico);
    dadosEnvio.append('status_atendimento', 'Confirmado');

    console.log("💾 A registar marcação na base de dados aurelius_salao...");

    fetch('gravar_reserva.php', {
        method: 'POST',
        body: dadosEnvio
    })
    .then(response => {
        // 4. MOSTRA A FATURA IMEDIATAMENTE NO ECRÃ
        renderizarFaturaNaTela({
            cliente: nomeCliente,
            profissional: funcionarioSelecionado,
            servico: nomeServico,
            preco: precoNumerico,
            data: dataSelecionada,
            hora: horaSelecionada
        });
    })
    .catch(error => {
        console.error("Erro ao gravar, gerando fatura em modo offline:", error);
        // Gera a fatura mesmo se o arquivo PHP falhar temporariamente
        renderizarFaturaNaTela({
            cliente: nomeCliente,
            profissional: funcionarioSelecionado,
            servico: nomeServico,
            preco: precoNumerico,
            data: dataSelecionada,
            hora: horaSelecionada
        });
    });
}

// 4. DESIGN VIVO DA FATURA (Substitui o ecrã atual de serviços)
function renderizarFaturaNaTela(d) {
    const container3 = document.getElementById('sub_container_nivel3');
    
    // Transforma o Passo 3 num recibo elegante estilo Aurélius Dark Gold
    container3.innerHTML = `
        <div style="text-align: left; color: #fff; background: #111827; padding: 25px; border-radius: 16px; border: 1px solid #1e293b; max-width: 500px; margin: 0 auto; box-shadow: 0 10px 25px rgba(0,0,0,0.5); animation: fadeIn 0.4s ease;">
            <div style="border-bottom: 2px solid #eab308; padding-bottom: 12px; margin-bottom: 20px; display: flex; justify-content: space-between; align-items: center;">
                <div>
                    <h2 style="color: #eab308; font-size: 16px; margin: 0; text-transform: uppercase;">🧾 FATURA EMITIDA COM SUCESSO</h2>
                    <p style="color: #64748b; font-size: 11px; margin: 0;">Módulo Automático — SÓ TRANÇAS</p>
                </div>
                <span style="font-size: 11px; color: #38bdf8; background: #0f172a; padding: 4px 8px; border-radius: 4px; font-weight: bold;">#ST-${Math.floor(Math.random() * 9000 + 1000)}</span>
            </div>
            
            <div style="display: flex; flex-direction: column; gap: 12px; font-size: 13px; background: #070b12; padding: 15px; border-radius: 10px; border: 1px solid #1e293b;">
                <p><strong>👤 Identificação:</strong> ${d.cliente}</p>
                <p><strong>💇 Profissional:</strong> <span style="color: #38bdf8;">${d.profissional}</span></p>
                <p><strong>✂️ Estilo Técnico:</strong> ${d.servico}</p>
                <p><strong>📅 Data/Hora:</strong> ${d.data.split('-').reverse().join('/')} às ${d.hora}</p>
                <hr style="border: 0; border-top: 1px dashed #334155; margin: 5px 0;">
                <div style="display: flex; justify-content: space-between; align-items: center;">
                    <span style="color: #94a3b8; font-weight: bold; font-size: 11px;">VALOR TOTAL:</span>
                    <strong style="color: #22c55e; font-size: 22px;">${parseFloat(d.preco).toLocaleString('pt-PT', {minimumFractionDigits: 2})} Kz</strong>
                </div>
            </div>

            <!-- Botão de Conclusão que Conduz Automaticamente para a Agenda -->
            <div style="margin-top: 20px;">
                <button type="button" onclick="conduzirParaAgendaReal('${d.servico}', '${d.preco}')" style="width: 100%; background: linear-gradient(135deg, #22c55e, #16a34a); color: #000; border: none; padding: 14px; border-radius: 8px; font-size: 14px; font-weight: bold; cursor: pointer; text-transform: uppercase; transition: 0.3s;">
                    🚀 AVANÇAR PARA A AGENDA PRINCIPAL
                </button>
            </div>
        </div>
    `;
}

// 5. ➔ CONDUZIR PARA A AGENDA AUTOMATICAMENTE
function conduzirParaAgendaReal(servicoNome, servicoPreco) {
    // Alimenta o display visual da aba Agenda com o valor do serviço faturado
    const lblExibicao = document.getElementById('lbl_valor_exibicao');
    if (lblExibicao) {
        lblExibicao.innerText = parseFloat(servicoPreco).toLocaleString('pt-PT', {minimumFractionDigits: 2}) + " Kz";
    }

    alert("⚡ Transação Sincronizada! A abrir a Agenda de Vagas...");
    
    // Muda a aba ativa do painel principal para a Agenda
    switchTab(null, 'agenda');
}




function gravarAgendamentoEFaturar(event) {
    event.preventDefault(); // Impede a página de recarregar

    // Coleta os dados dos campos
    const dados = {
        cliente: document.getElementById('inputNomeCliente').value,
        funcionario: document.getElementById('inputFuncionario').value,
        data_servico: document.getElementById('inputDataServico').value,
        hora_servico: document.getElementById('inputHorarioVaga').value,
        servico: document.getElementById('txt_servico_nome').value,
        valor: document.getElementById('txt_servico_preco').value
    };

    // Validação básica para não enviar vazio
    if (!dados.funcionario || !dados.servico || dados.valor === "0") {
        alert("🚨 Selecione o serviço e o profissional primeiro!");
        return;
    }

    // 🚀 ENVIAR PARA O BANCO DE DADOS (AJAX)
    fetch('gravar_reserva.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: new URLSearchParams(dados)
    })
    .then(response => response.json())
    .then(res => {
        if (resultado.status === 'success') {
            // MOSTRAR FATURA NA TELA
            const painel = document.getElementById('painel_bloco_reserva');
            painel.innerHTML = `
                <div style="background: #1e293b; padding: 20px; border-radius: 12px; border: 2px solid #eab308; color: #fff;">
                    <h3 style="color: #eab308;">🧾 FATURA REGISTADA</h3>
                    <p><strong>Serviço:</strong> ${dados.servico}</p>
                    <p><strong>Profissional:</strong> ${dados.funcionario}</p>
                    <p><strong>Data/Hora:</strong> ${dados.data_servico} às ${dados.hora_servico}</p>
                    <h2 style="color: #22c55e;">Total: ${dados.valor} Kz</h2>
                    <button onclick="window.location.reload()" style="width:100%; background:#eab308; padding:10px; border:none; border-radius:5px; font-weight:bold; cursor:pointer;">✅ CONCLUIR E VER AGENDA</button>
                </div>
            `;
        }
    })
    .catch(err => alert("Erro ao ligar ao servidor. Verifique o XAMPP."));
}


// 🟢 FUNÇÃO DO CLIQUE NA FOTO: Salva o preço e o nome do serviço
function atualizarPrecoNoFormulario(nomeServico, precoNumerico) {
    // 1. Atualiza o display visual de 0,00 Kz na parte inferior
    const lblExibicao = document.getElementById('lbl_valor_exibicao');
    if (lblExibicao) {
        lblExibicao.innerText = precoNumerico.toLocaleString('pt-PT', {minimumFractionDigits: 2}) + " Kz";
    }

    // 2. Preenche os inputs ocultos que a fatura precisa ler
    document.getElementById('txt_servico_nome').value = nomeServico;
    document.getElementById('txt_servico_preco').value = precoNumerico;

    console.log("Sincronizado: " + nomeServico + " | Preço: " + precoNumerico + " Kz");




    // 3. Rola a tela suavemente para o formulário
    const areaFormulario = document.getElementById('form_checkout_aurelius_saas');
    if (areaFormulario) {
        areaFormulario.scrollIntoView({ behavior: 'smooth' });
    }
}



function exibirReciboDigitalNoEcra(nome, func, s_nome, data, hora, s_preco) {
    const painel = document.getElementById('painel_bloco_reserva');
    const ticketID = "ST-" + Math.floor(Math.random() * 9000 + 1000);
    
    // Data da emissão (agora)
    const emissaoRelogio = new Date().toLocaleString('pt-PT');

    // Formata a Data da Marcação (de 2026-08-18 para 18/08/2026)
    const dataMarcacaoPT = data.split('-').reverse().join('/');

    painel.innerHTML = `
        <div style="background: #fff; color: #000; padding: 30px; border-radius: 4px; font-family: 'Courier New', Courier, monospace; box-shadow: 0 0 20px rgba(0,0,0,0.5); max-width: 400px; margin: 0 auto; border-top: 8px solid #eab308;">
            
            <div style="text-align: center; border-bottom: 2px dashed #000; padding-bottom: 15px; margin-bottom: 15px;">
                <h2 style="margin: 0; font-size: 20px;">SÓ TRANÇAS</h2>
                <p style="margin: 5px 0; font-size: 12px;">Huambo - Bairro São Luís</p>
            </div>

            <div style="font-size: 13px; line-height: 1.6;">
                <p><strong>TICKET:</strong> #${ticketID}</p>
                <p><strong>EMISSÃO:</strong> ${emissaoRelogio}</p>
                <hr style="border: 0; border-top: 1px solid #000;">
                
                <p><strong>CLIENTE:</strong> ${nome.toUpperCase()}</p>
                <p><strong>SERVIÇO:</strong> ${s_nome}</p>
                <p><strong>PROFISSIONAL:</strong> ${func}</p>
                
                <!-- 🟢 AQUI ESTÁ A CORREÇÃO: Mostra a escolha do cliente -->
                <div style="background: #000; color: #fff; padding: 10px; margin-top: 10px; text-align: center; border-radius: 4px;">
                    <span style="font-size: 10px; text-transform: uppercase;">Agendado para:</span><br>
                    <strong style="font-size: 16px;">${dataMarcacaoPT} às ${hora}H</strong>
                </div>
            </div>

            <div style="background: #f3f4f6; padding: 15px; margin-top: 20px; text-align: center;">
                <span style="font-size: 11px; color: #666;">Total Líquido a Pagar</span>
                <h2 style="margin: 5px 0; font-size: 26px;">${parseFloat(s_preco).toLocaleString('pt-PT')} AKZ</h2>
            </div>

            <div class="no-print" style="margin-top: 25px; display: grid; grid-template-columns: 1fr 1fr; gap: 10px;">
                <button onclick="window.print()" style="padding: 12px; background: #334155; color: #fff; border: none; cursor: pointer;">🖨️ IMPRIMIR</button>
                <button onclick="window.location.reload()" style="padding: 12px; background: #22c55e; color: #fff; border: none; cursor: pointer; font-weight: bold;">✅ CONCLUIR</button>
            </div>
        </div>
    `;
}




async function gravarAgendamentoEFaturar(event) {
    if (event) { event.preventDefault(); event.stopPropagation(); }

    // 🟢 CAPTURA FORÇADA: Lê os inputs no milissegundo do clique
    const inputData = document.getElementById('inputDataServico').value;
    const inputHora = document.getElementById('inputHorarioVaga').value;
    const inputNome = document.getElementById('inputNomeCliente').value.trim();
    const inputFunc = document.getElementById('inputFuncionario').value;
    
    const s_nome = document.getElementById('txt_servico_nome').value;
    const s_preco = document.getElementById('txt_servico_preco').value;

    // Bloqueio se o cliente não definiu data ou hora
    if (!inputData || !inputHora || inputHora === "--:--" || inputHora === "00:00") {
        alert("🚨 Atenção: Por favor, escolha a DATA e a HORA pretendida para o serviço!");
        return false;
    }

    // ... (Mantenha aqui o seu código de fetch para gravar no banco) ...

    // 🟢 ENVIAR PARA O RECIBO COM OS VALORES QUE O CLIENTE ESCOLHEU
    exibirReciboDigitalNoEcra(inputNome, inputFunc, s_nome, inputData, inputHora, s_preco);
}
</script>

</body>