<?php
// Layout_Topo.php - Cabeçalho e Identidade Visual Centralizada
if (session_status() === PHP_SESSION_NONE) { session_start(); }
?>
<!DOCTYPE html>
<html lang="pt-PT">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SaaS Master Engine - Grupo Aurélius</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; font-family: 'Segoe UI', Arial, sans-serif; }
        body { font-family: 'Segoe UI', Arial, sans-serif; background: #0b1424; color: #f8fafc; padding: 40px; margin: 0; min-height: 100vh; }
        nav { padding: 15px 30px; background: #0f172a; border-bottom: 2px solid #38bdf8; display: flex; justify-content: space-between; align-items: center; box-shadow: 0 4px 15px rgba(56, 189, 248, 0.15); margin-bottom: 30px; border-radius: 8px; }
        .logo h1 { font-size: 20px; font-weight: 800; color: #ef4444; text-transform: uppercase; margin: 0; }
        .logo h1 span { color: #f8fafc; }
        .container-hub-saas { max-width: 950px; margin: 0 auto; background: #0f172a; border-radius: 20px; border: 1px solid #1e293b; padding: 40px; box-shadow: 0 20px 40px rgba(0,0,0,0.5); }
        .bloco-camada { background: #1e293b; padding: 25px; border-radius: 12px; border: 1px solid #334155; margin-bottom: 25px; }
        .bloco-camada h3 { margin-top: 0; color: #38bdf8; font-size: 15px; border-bottom: 1px solid #334155; padding-bottom: 10px; text-transform: uppercase; letter-spacing: 0.5px; font-weight: bold; }
        .grid-duplo { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; }
        .campo-input-grupo { display: flex; flex-direction: column; margin-bottom: 15px; }
        .campo-input-grupo label { font-size: 11px; font-weight: bold; color: #94a3b8; text-transform: uppercase; margin-bottom: 6px; letter-spacing: 0.5px; }
        .campo-input-grupo input, .campo-input-grupo select, .campo-input-grupo textarea { padding: 12px; background: #0f172a; border: 1px solid #475569; border-radius: 6px; color: white; font-size: 14px; box-sizing: border-box; width: 100%; }
        .campo-input-grupo input:focus, .campo-input-grupo select:focus { border-color: #38bdf8; outline: none; box-shadow: 0 0 8px rgba(56, 189, 248, 0.2); }
        .scrolling-lista-servicos { display: grid; grid-template-columns: repeat(auto-fill, minmax(240px, 1fr)); gap: 10px; max-height: 230px; overflow-y: auto; background: #0b0f19; padding: 15px; border-radius: 8px; border: 1px solid #334155; }
        .box-checkbox-item { display: flex; align-items: center; gap: 10px; font-size: 13px; color: #cbd5e1; cursor: pointer; padding: 6px; border-radius: 4px; }
        .box-checkbox-item:hover { background: #1e293b; color: #fff; }
        .box-checkbox-item input { width: 16px; height: 16px; accent-color: #38bdf8; cursor: pointer; }
        .btn-acao-master { width: 100%; padding: 16px; background: #22c55e; color: #000; border: none; border-radius: 8px; font-weight: bold; font-size: 14px; text-transform: uppercase; cursor: pointer; box-shadow: 0 4px 14px rgba(34, 197, 94, 0.3); transition: background 0.2s; letter-spacing: 0.5px; }
        .btn-acao-master:hover { background: #16a34a; }
        .alerta-erro-caixa { background: rgba(239, 68, 68, 0.1); border: 1px solid #f87171; padding: 15px; border-radius: 8px; color: #f87171; margin-bottom: 25px; font-size: 14px; font-weight: bold; line-height: 1.5; }
    </style>
</head>
<body>
    <nav>
        <div class="logo">
            <h1>Aurélius <span>SaaS</span></h1>
        </div>
        <div style="font-size: 12px; color: #38bdf8; font-weight: bold; text-transform: uppercase;">
            🌐 Motor Transmunicipal de Instanciação Activo
        </div>
    </nav>
    <div class="container-hub-saas">

 <?php
// Inclui a central de inteligência de design modularizado
include_once("Layout_Topo.php");
include_once("Conexao.php");

// Tratamento reativo das mensagens de retorno enviadas pelo motor PHP
$erro_mensagem = "";
if (isset($_GET['erro'])) {
    if ($_GET['erro'] === 'bi') {
        $erro_mensagem = "❌ Validação de Documento Recusada:<br>O Bilhete de Identidade informado não pertence ao padrão nacional de Angola. Deve conter 9 dígitos, 2 letras maiúsculas e mais 3 números.";
    } elseif ($_GET['erro'] === 'senha') {
        $erro_mensagem = "❌ Falha de Segurança:<br>A senha introduzida é demasiado fraca. Deve conter no mínimo 6 caracteres.";
    } elseif ($_GET['erro'] === 'senha_match') {
        $erro_mensagem = "❌ Incompatibilidade de Dados:<br>A confirmação de palavra-passe não confere com a senha digitada no primeiro campo.";
    }
}
?>

    <div style="text-align: center; margin-bottom: 35px; border-bottom: 1px solid #1e293b; padding-bottom: 20px;">
        <h2 style="font-size: 24px; color: #fff; margin: 0;">🚀 Motor de Instanciação Automática SaaS</h2>
        <p style="color: #94a3b8; font-size: 13px; margin-top: 5px;">Crie e ative a sua barbearia ou salão autossustentável em 10 camadas de banco de dados imediatamente.</p>
    </div>

    <?php if (!empty($erro_mensagem)): ?>
        <div class="alerta-erro-caixa"><?= $erro_mensagem ?></div>
    <?php endif; ?>

    <!-- Formulário que submete os dados de forma limpa para o processador backend -->
    <form method="POST" action="processar_saas_completo.php" id="formInstanciacaoMaster">
        
        <!-- Bloco 1: Fachada da Franquia -->
        <div class="bloco-camada">
            <h3>1. Fachada & Identidade da Franquia</h3>
            <div class="grid-duplo">
                <div class="campo-input-grupo">
                    <label>Nome Comercial da Empresa / Barbearia:</label>
                    <input type="text" name="nome_empresa" placeholder="Ex: Barbearia LookNovo do Huambo" required>
                </div>
                <div class="campo-input-grupo">
                    <label>Plano de Assinatura Corporativa SaaS:</label>
                    <select name="plano_assinatura">
                        <option value="Professional">Plano Professional (Até 5 Mestres)</option>
                        <option value="Enterprise">🌟 Plano Enterprise Master (Mestres ilimitados + Split VIP)</option>
                    </select>
                </div>
            </div>
        </div>

        <!-- Bloco 2: Legal e Conta com validação de credenciais de login -->
        <div class="bloco-camada">
            <h3>2. Legal, Credenciais & Validação de Documento Nacional</h3>
            <div class="grid-duplo">
                <div class="campo-input-grupo">
                    <label>Nome Completo do Gestor / Diretor Geral:</label>
                    <input type="text" name="nome_gestor" placeholder="Insira o nome do responsável" required>
                </div>
                <div class="campo-input-grupo">
                    <label>Bilhete de Identidade (BI Angola - Auditoria):</label>
                    <input type="text" name="bi_gestor" id="bi_campo_verif" placeholder="Ex: 004732158LA042" maxlength="14" style="letter-spacing:1px; font-weight:600; color:#eab308;" required>
                </div>
            </div>
            <div class="grid-duplo" style="margin-top: 15px;">
                <div class="campo-input-grupo">
                    <label>E-mail Corporativo de Acesso:</label>
                    <input type="email" name="email_corporativo" placeholder="gestao@barbearia.com" required>
                </div>
                <div class="campo-input-grupo">
                    <label>Telefone Comercial (Rede de Angola):</label>
                    <input type="number" name="telefone_angola" placeholder="9XXXXXXXX" required>
                </div>
            </div>
            <div class="grid-duplo" style="margin-top: 15px;">
                <div class="campo-input-grupo">
                    <label>Palavra-Passe de Acesso ao Painel (Mínimo 6 dígitos):</label>
                    <input type="password" name="senha_login" id="pass_original" placeholder="••••••" required>
                </div>
                <div class="campo-input-grupo">
                    <label>Confirmar Palavra-Passe:</label>
                    <input type="password" name="senha_confirma" id="pass_match" placeholder="••••••" required>
                </div>
            </div>
        </div>

        <!-- Bloco 3: Engenharia e Localização Geográfica -->
        <div class="bloco-camada">
            <h3>3. Engenharia SaaS & Localização Geográfica</h3>
            <div class="grid-duplo">
                <div class="campo-input-grupo">
                    <label>Cidade / Província de Angola:</label>
                    <select name="cidade_sede">
                        <option value="Huambo">Huambo</option>
                        <option value="Luanda">Luanda</option>
                        <option value="Benguela">Benguela</option>
                        <option value="Namibe">Namibe</option>
                        <option value="Bié">Bié</option>
                    </select>
                </div>
                <div class="campo-input-grupo">
                    <label>Bairro / Rua da Sede Física:</label>
                    <input type="text" name="bairro_sede" placeholder="Ex: Bairro de São Luís / Cuca" required>
                </div>
            </div>
        </div>

        <!-- Bloco 4: Arquitetura Independente e Escolha Exaustiva de Serviços -->
        <div class="bloco-camada">
            <h3>4. Arquitetura Independente: Gama de Serviços da Barbearia Moderna</h3>
            <p style="color: #94a3b8; font-size: 12px; margin-bottom: 15px; line-height: 1.4;">Selecione com seriedade as ferramentas e tipos de serviços que a sua nova barbearia disponibilizará no catálogo. O sistema criará a infraestrutura baseada **estritamente** nas suas escolhas.</p>
            
            <div class="scrolling-lista-servicos">
                <label class="box-checkbox-item"><input type="checkbox" name="servicos_independentes[]" value="Corte de Adulto Clássico" checked> 💈 Corte de Adulto Clássico</label>
                <label class="box-checkbox-item"><input type="checkbox" name="servicos_independentes[]" value="Corte Careca Total" checked> 💈 Corte Careca Total</label>
                <label class="box-checkbox-item"><input type="checkbox" name="servicos_independentes[]" value="Corte Francês Cheio" checked> 💈 Corte Francês Cheio</label>
                <label class="box-checkbox-item"><input type="checkbox" name="servicos_independentes[]" value="Corte Francês Vazio"> 💈 Corte Francês Vazio</label>
                <label class="box-checkbox-item"><input type="checkbox" name="servicos_independentes[]" value="Design e Corte de Barba" checked> 💈 Design e Corte de Barba</label>
                <label class="box-checkbox-item"><input type="checkbox" name="servicos_independentes[]" value="Design de Barba Simples"> 💈 Design de Barba Simples</label>
                
                <label class="box-checkbox-item"><input type="checkbox" name="servicos_independentes[]" value="Tintura Geral de Cabelo" checked> 🎨 Tintura Geral de Cabelo</label>
                <label class="box-checkbox-item"><input type="checkbox" name="servicos_independentes[]" value="Mechas / Luzes Platinadas"> 🎨 Mechas / Luzes Platinadas</label>
                <label class="box-checkbox-item"><input type="checkbox" name="servicos_independentes[]" value="Hidratação Profunda / Alisamento"> 🧴 Hidratação Profunda</label>
                <label class="box-checkbox-item"><input type="checkbox" name="servicos_independentes[]" value="Queratina / Selagem Térmica"> 🧴 Queratina / Selagem Térmica</label>
                
                <label class="box-checkbox-item"><input type="checkbox" name="servicos_independentes[]" value="Manicure Simples" checked> 💅 Manicure Simples</label>
                <label class="box-checkbox-item"><input type="checkbox" name="servicos_independentes[]" value="Pedicure Estética Simples" checked> 💅 Pedicure Simples</label>
                <label class="box-checkbox-item"><input type="checkbox" name="servicos_independentes[]" value="Aplicação Gel / Acrigel" checked> 💅 Aplicação Gel / Acrigel</label>
                <label class="box-checkbox-item"><input type="checkbox" name="servicos_independentes[]" value="Spa Completo dos Pés"> 🦶 Spa Completo dos Pés</label>
                
                <label class="box-checkbox-item"><input type="checkbox" name="servicos_independentes[]" value="Maquilhagem Noiva Profissional"> 💄 Maquilhagem Noiva</label>
                <label class="box-checkbox-item"><input type="checkbox" name="servicos_independentes[]" value="Aplicação de Henna Sobrancelhas"> 💄 Aplicação de Henna</label>
                
                <div style="grid-column: 1 / -1; margin-top: 10px; border-top: 1px solid #334155; padding-top: 10px;"></div>
                <label style="font-size: 12px; color: #38bdf8; font-weight: bold; text-transform: uppercase;">Categorias Ativas Isoladas:</label>
                <label style="font-size: 12px; color: #fff;"><input type="checkbox" name="categorias_independentes[]" value="Cortes" checked disabled> Cortes</label>
                <label style="font-size: 12px; color: #fff;"><input type="checkbox" name="categorias_independentes[]" value="Estética" checked disabled> Estética</label>
                <label style="font-size: 12px; color: #fff;"><input type="checkbox" name="categorias_independentes[]" value="Química" checked disabled> Química</label>
            </div>
        </div>

        <button type="submit" name="disparar_instanciacao_saas" class="btn-acao-master">🚀 COMPILAR E INSTANCIAR 10 CAMADAS SAAS</button>
    </form>

<?php 
// Inclui o rodapé comum com os scripts de validação reativa
include_once("Layout_Fundo.php"); 
?>