<?php
if (session_status() === PHP_SESSION_NONE) { 
    session_start(); 
}
date_default_timezone_set('Africa/Luanda');

require_once __DIR__ . "/config/Banco.php";

$mensagem_erro = "";

// 🟢 MOTOR DE GRAVAÇÃO SINCRONIZADO: Escuta o botão "finalizar_cadastro_loja" enviado pela Aba 4
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['finalizar_cadastro_loja'])) {
    
    // 1. Dados da Aba 1 (Identificação e Localização)
    $nome_loja = trim($_POST['nome_loja']);
    $provincia = trim($_POST['provincia']);
    $municipio = trim($_POST['municipio']);
    $endereco  = trim($_POST['endereco']);
    
    // Combina Município e Província para o formato do endereço do armazém nacional
    $endereco_armazem_completo = $endereco . " (" . $municipio . ", " . $provincia . ")";
    
    // Gera o slug automático para a URL amigável do Marketplace (Ex: Baratinhpo)
    $slug_loja = preg_replace('/[^A-Za-z0-9\-]/', '', str_replace(' ', '', $nome_loja));

    // 2. Dados da Aba 2 (Credenciais, Segurança e Conta Financeira)
    $email_mercantil = trim($_POST['email']);
    $telefone_corp   = trim($_POST['telefone']);
    $iban_bancario   = trim($_POST['iban_bancario']);
    $pin_acesso      = trim($_POST['pin_cadastro']); // Captura o PIN numérico criado
    
    // Criptografia md5 compatível com o padrão atual das tuas lojas do banco
    $senha_admin_md5 = md5($pin_acesso); 

    // 3. Dados da Aba 3 (Infraestrutura Logística Compactada em JSON)
    $escala_catalogo = trim($_POST['escala_catalogo'] ?? 'medio');
    $controlo_stock  = trim($_POST['controlo_stock_ecommerce'] ?? 'estrito');
    $metodos_entrega = isset($_POST['metodos_entrega']) ? $_POST['metodos_entrega'] : ['Levantamento Local'];
    
    // Converte as preferências de e-commerce numa string JSON para a coluna 'especificacoes_json'
    $especificacoes_json = json_encode([
        "escala_catalogo" => $escala_catalogo,
        "controlo_stock" => $controlo_stock,
        "metodos_entrega" => $metodos_entrega
    ], JSON_UNESCAPED_UNICODE);

    // Gerador de Código Público Único (Ex: AUR-8594)
    $id_publico = "AUR-" . rand(1000, 9999);

    // 4. Executa o INSERT real respeitando milimetricamente a estrutura do teu phpMyAdmin
    $stmt_nova_loja = $pdo->prepare("
    INSERT INTO `lojas` 
    (id_publico, pin_acesso, nome_loja, email_mercantil, telefone_corporativo, endereco_armazem, slug_loja, senha_administracao, transacao_status, visivel_no_site, especificacoes_json, data_cadastro, iban_bancario) 
    VALUES 
    (?, ?, ?, ?, ?, ?, ?, ?, 'Confirmado', 1, ?, NOW(), ?)
");

$executou = $stmt_nova_loja->execute([
    $id_publico,
    $pin_acesso,
    $nome_loja,
    $email_mercantil,
    $telefone_corp,
    $endereco_armazem_completo,
    $slug_loja,
    $senha_admin_md5,
    $especificacoes_json,
    $iban_bancario
]);
    if ($executou) {
        // Redireciona com sucesso para a vitrina pública, onde o Baratinhpo já vai saltar no topo!
        echo "<script>alert('✓ Espaço Comercial Ativado com sucesso! Seja bem-vindo ao Grupo Aurélius.'); window.location.href='Lojas.php';</script>";
        exit();
    } else {
        $mensagem_erro = "🚨 Falha crítica ao registar no MySQLi local. Detalhes: " . implode(" - ", $stmt_nova_loja->errorInfo());
    }
}
?>



<!DOCTYPE html>
<html lang="pt-PT">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Configuração de Nova Loja - Grupo Aurélius</title>
    <style>
        body { font-family: 'Segoe UI', Arial, sans-serif; background: #0f172a; color: #f8fafc; padding: 20px; display: flex; align-items: center; justify-content: center; min-height: 100vh; margin: 0; box-sizing: border-box; }
        .wrapper-card { max-width: 680px; width: 100%; background: #1e293b; padding: 35px; border-radius: 20px; box-shadow: 0 20px 40px rgba(0,0,0,0.5); border: 1px solid #334155; box-sizing: border-box; }
        .barra-passos { display: flex; justify-content: space-between; margin-bottom: 25px; background: #0f172a; padding: 12px 20px; border-radius: 30px; border: 1px solid #334155; }
        .passo-txt { font-size: 11px; font-weight: bold; color: #64748b; text-transform: uppercase; letter-spacing: 0.5px; }
        .passo-txt.active { color: #eab308; }
        .aba-painel { display: none; text-align: left; }
        .aba-painel.active { display: block; }
        .form-campo { margin-bottom: 18px; text-align: left; }
        label { display: block; font-size: 11px; color: #38bdf8; font-weight: bold; text-transform: uppercase; margin-bottom: 6px; letter-spacing: 0.5px; }
        input[type="text"], input[type="email"], input[type="password"], input[type="number"], select, textarea { width: 100%; padding: 12px; background: #0f172a; border: 1px solid #334155; border-radius: 8px; color: #f8fafc; font-size: 14px; box-sizing: border-box; transition: border-color 0.3s; }
        input:focus, select:focus, textarea:focus { border-color: #eab308; outline: none; }
        .grid-custom { display: grid; grid-template-columns: 1fr 1fr; gap: 16px; }
        .grid-boxes { display: grid; grid-template-columns: 1fr 1fr; gap: 10px; background: #0f172a; padding: 15px; border-radius: 10px; border: 1px dashed #475569; }
        .item-check { display: flex; align-items: center; gap: 10px; color: #e2e8f0; font-size: 13px; cursor: pointer; padding: 6px; border-radius: 6px; transition: background 0.2s; }
        .item-check:hover { background: #1e293b; }
        .item-check input { width: auto; cursor: pointer; accent-color: #eab308; }
        .bloco-subseccao { margin-top: 20px; background: #111827; padding: 18px; border-radius: 12px; border: 1px solid #1e293b; }
        .bloco-subseccao h4 { margin: 0 0 12px 0; font-size: 12px; color: #38bdf8; text-transform: uppercase; letter-spacing: 0.5px; }
        .btn-nav { padding: 12px 24px; border: none; border-radius: 6px; font-weight: bold; cursor: pointer; font-size: 13px; text-transform: uppercase; transition: all 0.3s; }
        .btn-ant { background: #475569; color: white; float: left; }
        .btn-ant:hover { background: #334155; }
        .btn-seg { background: #eab308; color: #000; float: right; }
        .btn-seg:hover { background: #ca8a04; }
        .btn-enviar { background: #22c55e; color: #000; float: right; font-weight: bold; font-size: 14px; box-shadow: 0 4px 14px rgba(34, 197, 94, 0.3); }
        .btn-enviar:hover { background: #16a34a; transform: translateY(-1px); }
        .alert-box { padding: 12px 16px; border-radius: 8px; font-size: 13px; margin-bottom: 20px; font-weight: 500; background: rgba(239, 68, 68, 0.1); border: 1px solid rgba(239, 68, 68, 0.2); color: #f87171; }
    </style>
</head>
<body>

<div class="wrapper-card" style="max-width: 600px; margin: 20px auto; padding: 20px 16px; background: #0f172a; border-radius: 16px; border: 1px solid #1e293b; box-shadow: 0 10px 30px rgba(0,0,0,0.5); font-family: system-ui, -apple-system, sans-serif; box-sizing: border-box; width: 100%;">
    <h2 style="color: #fff; font-size: 20px; font-weight: 600; margin-bottom: 20px; text-align: left;">Cria o teu espaço de vendas</h2>
    
    <!-- 🟢 FITA DE PASSOS RESPONSIVA -->
    <div class="barra-passos" style="display: flex; gap: 8px; overflow-x: auto; padding-bottom: 8px; margin-bottom: 25px; scrollbar-width: none; -webkit-overflow-scrolling: touch;">
        <div class="passo-txt active" id="ind-1" style="white-space: nowrap; padding: 6px 12px; background: #1e293b; color: #38bdf8; border-radius: 20px; font-size: 12px; font-weight: 600; border: 1px solid #38bdf8;">1. Sede</div>
        <div class="passo-txt" id="ind-2" style="white-space: nowrap; padding: 6px 12px; background: #070b12; color: #64748b; border-radius: 20px; font-size: 12px; font-weight: 600; border: 1px solid #1e293b;">2. Conta</div>
        <div class="passo-txt" id="ind-3" style="white-space: nowrap; padding: 6px 12px; background: #070b12; color: #64748b; border-radius: 20px; font-size: 12px; font-weight: 600; border: 1px solid #1e293b;">3. Logística</div>
        <div class="passo-txt" id="ind-4" style="white-space: nowrap; padding: 6px 12px; background: #070b12; color: #64748b; border-radius: 20px; font-size: 12px; font-weight: 600; border: 1px solid #1e293b;">4. Fim</div>
    </div>

    <?php if (!empty($mensagem_erro)): ?>
        <div class="alert-box" style="background: rgba(239, 68, 68, 0.1); border: 1px solid #ef4444; padding: 12px; border-radius: 8px; color: #f87171; margin-bottom: 20px; font-size: 13px; text-align: center;"><?php echo $mensagem_erro; ?></div>
    <?php endif; ?>

    <form method="POST" action="" onsubmit="return validarFormularioFinal()">
        
        <!-- =========================================================================
             🖼️ ABA 1: CONFIGURAÇÃO DE SEDE LOCAL E IDENTIFICAÇÃO MERCANTIL
             ========================================================================= -->
        <div id="aba-1" class="aba-painel active" style="display: block;">
            <h3 style="color: #eab308; font-size: 14px; text-transform: uppercase; margin-bottom: 20px; border-left: 3px solid #eab308; padding-left: 8px; font-weight: bold;">1. Dados de Localização da Loja</h3>
            
            <div class="form-campo" style="display: flex; flex-direction: column; gap: 6px; margin-bottom: 16px;">
                <label style="font-size: 13px; color: #94a3b8; font-weight: 600;">Nome Comercial da Loja Fornecedora:</label>
                <input type="text" name="nome_loja" id="nome_loja"  style="padding: 12px 14px; background: #070b12; border: 1px solid #374151; border-radius: 8px; color: #fff; font-size: 16px; outline: none; width: 100%;" required> 
            </div>
            
            <div class="grid-custom" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(140px, 1fr)); gap: 12px; margin-bottom: 16px;">
                <div class="form-campo" style="display: flex; flex-direction: column; gap: 6px;">
                    <label style="font-size: 13px; color: #94a3b8; font-weight: 600;">Província de Distribuição:</label>
                    <select name="provincia" id="provincia" style="padding: 12px 14px; background: #070b12; border: 1px solid #374151; border-radius: 8px; color: #fff; font-size: 16px; outline: none; width: 100%; color-scheme: dark;" required>
                        <option value="">Selecione...</option>
                        <option value="Bengo">Bengo</option>
                        <option value="Benguela">Benguela</option>
                        <option value="Bié">Bié</option>
                        <option value="Cabinda">Cabinda</option>
                        <option value="Cuanza Norte">Cuanza Norte</option>
                        <option value="Cuanza Sul">Cuanza Sul</option>
                        <option value="Cuando Cubango">Cuando Cubango</option>
                        <option value="Cunene">Cunene</option>
                        <option value="Huambo">Huambo</option>
                        <option value="Huíla">Huíla</option>
                        <option value="Luanda">Luanda</option>
                        <option value="Lunda Norte">Lunda Norte</option>
                        <option value="Lunda Sul">Lunda Sul</option>
                        <option value="Malanje">Malanje</option>
                        <option value="Moxico">Moxico</option>
                        <option value="Namibe">Namibe</option>
                        <option value="Uíge">Uíge</option>
                        <option value="Zaire">Zaire</option>
                    </select>
                </div>
                <div class="form-campo" style="display: flex; flex-direction: column; gap: 6px;">
                    <label style="font-size: 13px; color: #94a3b8; font-weight: 600;">Município:</label>
                    <input type="text" name="municipio" id="municipio" style="padding: 12px 14px; background: #070b12; border: 1px solid #374151; border-radius: 8px; color: #fff; font-size: 16px; outline: none; width: 100%;" required> 
                </div>
            </div>

            <div class="form-campo" style="display: flex; flex-direction: column; gap: 6px; margin-bottom: 24px;">
                <label style="font-size: 13px; color: #94a3b8; font-weight: 600;">Localização do Escritório:</label>
                <input type="text" name="endereco" id="endereco" style="padding: 12px 14px; background: #070b12; border: 1px solid #374151; border-radius: 8px; color: #fff; font-size: 16px; outline: none; width: 100%;" required placeholder="Rua, Bairro, e pontos de referência...">
            </div>

            <div style="margin-top: 20px; display: flex; gap: 10px; width: 100%;">
                <a href="Principal.php" style="flex: 1; display: block; background: #1e293b; color: white; padding: 14px; border-radius: 8px; text-decoration: none; font-weight: bold; font-size: 13px; text-transform: uppercase; text-align: center; border: 1px solid #334155;">← Voltar</a>
                <button type="button" onclick="irParaAba(2)" style="flex: 1.3; background: linear-gradient(135deg, #eab308, #ca8a04); color: #000; padding: 14px; border: none; border-radius: 8px; font-weight: bold; cursor: pointer; text-transform: uppercase; font-size: 12.5px; letter-spacing: 0.5px;">Seguinte →</button>
            </div>
        </div>


        <!-- =========================================================================
             🔐 ABA 2: SEGURANÇA E ACESSO MERCANTIL
             ========================================================================= -->
        <div id="aba-2" class="aba-painel" style="display: none;">
             <h3 style="color: #eab308; font-size: 14px; text-transform: uppercase; margin-bottom: 20px; border-left: 3px solid #eab308; padding-left: 8px; font-weight: bold;">2. Credenciais de Login Comercial</h3>
             
             <div class="grid-custom" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); gap: 12px; margin-bottom: 16px;">
                 <div class="form-campo" style="display: flex; flex-direction: column; gap: 6px;">
                     <label style="font-size: 13px; color: #94a3b8; font-weight: 600;">E-mail Mercantil:</label>
                     <input type="email" name="email" id="email" style="padding: 12px 14px; background: #070b12; border: 1px solid #374151; border-radius: 8px; color: #fff; font-size: 16px; outline: none; width: 100%;" required ">
                 </div>
                 <div class="form-campo" style="display: flex; flex-direction: column; gap: 6px;">
                     <label style="font-size: 13px; color: #94a3b8; font-weight: 600;"> Telefone:</label>
                     <input type="tel" name="telefone" id="telefone" style="padding: 12px 14px; background: #070b12; border: 1px solid #374151; border-radius: 8px; color: #fff; font-size: 16px; outline: none; width: 100%;" maxlength="9" oninput="this.value = this.value.replace(/[^0-9]/g, '');" required >
                 </div>
             </div>
 
             <div class="form-campo" style="display: flex; flex-direction: column; gap: 6px; margin-bottom: 16px;">
                 <label style="font-size: 13px; color: #94a3b8; font-weight: 600;">IBAN Bancário (Para Repasse de Vendas):</label>
                 <input type="text" name="iban_bancario" id="iban_bancario" style="padding: 12px 14px; background: #070b12; border: 1px solid #374151; border-radius: 8px; color: #fff; font-size: 16px; outline: none; width: 100%; font-family: monospace;" required placeholder="AO06.0000.0000.0000.0000.0">
             </div>

             <div class="grid-custom" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(140px, 1fr)); gap: 12px; margin-bottom: 24px;">
                 <div class="form-campo" style="display: flex; flex-direction: column; gap: 6px;">
                     <label style="font-size: 13px; color: #94a3b8; font-weight: 600;">PIN de Acesso (Numérico):</label>
                     <input type="password" name="pin_cadastro" id="pin_cadastro" style="padding: 12px 14px; background: #070b12; border: 1px solid #374151; border-radius: 8px; color: #fff; font-size: 16px; outline: none; width: 100%;" required maxlength="9" inputmode="numeric" oninput="this.value = this.value.replace(/[^0-9]/g, '');" placeholder="Crie um PIN">
                 </div>
<!-- CONTINUAÇÃO DA ABA 2: SEGURANÇA E ACESSO MERCANTIL -->
<div class="form-campo" style="display: flex; flex-direction: column; gap: 6px;">
                     <label style="font-size: 13px; color: #94a3b8; font-weight: 600;">Confirmar PIN de Acesso:</label>
                     <input type="password" 
                            id="pin_confirmacao" 
                            required 
                            maxlength="9" 
                            inputmode="numeric" 
                            oninput="this.value = this.value.replace(/[^0-9]/g, '');" 
                            placeholder="Repita o PIN"
                            style="padding: 12px 14px; background: #070b12; border: 1px solid #374151; border-radius: 8px; color: #fff; font-size: 16px; outline: none; width: 100%;">
                 </div>
             </div>
 
             <div style="margin-top: 30px; display: flex; gap: 10px; width: 100%;">
                 <button type="button" class="btn-ant" onclick="irParaAba(1)" style="flex: 1; background: #1e293b; color: white; padding: 14px; border-radius: 8px; font-weight: bold; border: 1px solid #334155; text-transform: uppercase; font-size: 13px; cursor: pointer;">← Voltar</button>
                 <button type="button" class="btn-seg" onclick="avancarParaAba3()" style="flex: 1.3; background: linear-gradient(135deg, #eab308, #ca8a04); color: #000; padding: 14px; border: none; border-radius: 8px; font-weight: bold; cursor: pointer; text-transform: uppercase; font-size: 12.5px; letter-spacing: 0.5px;">Seguinte (Logística) →</button>
             </div>
         </div>



        <!-- =========================================================================
             📦 ABA 3: ENGENHARIA DE LOGÍSTICA E E-COMMERCE ATACADISTA
             ========================================================================= -->
        <div id="aba-3" class="aba-painel" style="display: none;">
            <h3 style="color: #22c55e; font-size: 14px; text-transform: uppercase; margin-bottom: 20px; border-left: 3px solid #22c55e; padding-left: 8px; font-weight: bold;">3. Infraestrutura de E-Commerce & Logística</h3>
            
            <div class="grid-custom" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); gap: 12px; margin-bottom: 20px;">
                <div class="form-campo" style="display: flex; flex-direction: column; gap: 6px;">
                    <label style="font-size: 13px; color: #94a3b8; font-weight: 600;">Porte da Empresa:</label>
                    <select name="escala_catalogo" style="padding: 12px 14px; background: #070b12; border: 1px solid #374151; border-radius: 8px; color: #fff; font-size: 16px; width: 100%; color-scheme: dark;">
                        <option value="pequeno">Selecione</option>
                        <option value="pequeno">Pequeno Porte (Até 50 Produtos)</option>
                        <option value="medio" selected>Médio Porte (Até 500 Produtos - Distribuidor)</option>
                        <option value="atacadista">Grande Porte / Grossista (Ilimitados)</option>
                    </select>
                </div>
                <div class="form-campo" style="display: flex; flex-direction: column; gap: 6px;">
                    <label style="font-size: 13px; color: #94a3b8; font-weight: 600;">Políticas de Gestão de Stock:</label>
                    <select name="controlo_stock_ecommerce" style="padding: 12px 14px; background: #070b12; border: 1px solid #374151; border-radius: 8px; color: #fff; font-size: 16px; width: 100%; color-scheme: dark;">
                        <option value="estrito">Selecione</option>
                          <option value="estrito">Bloquear Vendas se o Stock Chegar a 0</option>
                        <option value="sob_encomenda">Permitir Venda sob Encomenda (Prazo Adicional)</option>
                    </select>
                </div>
            </div>

            <div class="bloco-subseccao" style="background: #070b12; padding: 15px; border-radius: 10px; border: 1px solid #1f2937; margin-bottom: 24px; text-align: left;">
                <h4 style="color: #38bdf8; font-size: 13px; font-weight: bold; margin-bottom: 12px; margin-top: 0; text-transform: uppercase;">Métodos de Entrega Disponíveis:</h4>
                <div style="display: flex; flex-direction: column; gap: 10px;">
                    <label style="display: flex; align-items: center; gap: 8px; font-size: 13.5px; color: #cbd5e1; cursor: pointer;"><input type="checkbox" name="metodos_entrega[]" value="Levantamento Local" checked style="accent-color: #22c55e; scale: 1.1;"> Levantamento Físico no Balcão</label>
                    <label style="display: flex; align-items: center; gap: 8px; font-size: 13.5px; color: #cbd5e1; cursor: pointer;"><input type="checkbox" name="metodos_entrega[]" value="Estafeta Rapido" checked style="accent-color: #22c55e; scale: 1.1;"> Entrega Local Expressa</label>
                    <label style="display: flex; align-items: center; gap: 8px; font-size: 13.5px; color: #cbd5e1; cursor: pointer;"><input type="checkbox" name="metodos_entrega[]" value="Frete Interprovincial" checked style="accent-color: #22c55e; scale: 1.1;"> Envio Interprovincial (Cargas)</label>
                </div>
            </div>

            <div style="margin-top: 20px; display: flex; gap: 10px; width: 100%;">
                <button type="button" onclick="irParaAba(2)" style="flex: 1; background: #1e293b; color: white; padding: 14px; border-radius: 8px; font-weight: bold; border: 1px solid #334155; text-transform: uppercase; font-size: 13px; cursor: pointer;">← Voltar</button>
                <button type="button" onclick="irParaAba(4)" style="flex: 1.3; background: linear-gradient(135deg, #22c55e, #16a34a); color: #000; padding: 14px; border: none; border-radius: 8px; font-weight: bold; cursor: pointer; text-transform: uppercase; font-size: 12.5px; letter-spacing: 0.5px;">Seguinte →</button>
            </div>
        </div>

        <!-- =========================================================================
             🎨 ABA 4: CONCLUSÃO E ATIVAÇÃO DO ESPAÇO DIGITAL
             ========================================================================= -->
        <div id="aba-4" class="aba-painel" style="display: none;">
            <h3 style="color: #38bdf8; font-size: 14px; text-transform: uppercase; margin-bottom: 20px; border-left: 3px solid #38bdf8; padding-left: 8px; font-weight: bold;">4. Conclusão do Espaço Digital</h3>
            
            <div style="background: #070b12; padding: 20px; border-radius: 12px; border: 1px solid #1f2937; text-align: center; margin-bottom: 24px;">
                <span style="font-size: 40px; display: block; margin-bottom: 12px;">🏪</span>
                <p style="color: #cbd5e1; font-size: 14px; line-height: 1.6; margin: 0;">Tudo pronto! Ao clicares no botão de ativação abaixo, o teu espaço mercantil será registado e sincronizado imediatamente com a vitrina do ecossistema do Grupo Aurélius.</p>
            </div>

            <div style="margin-top: 20px; display: flex; gap: 10px; width: 100%;">
                <button type="button" onclick="irParaAba(3)" style="flex: 1; background: #1e293b; color: white; padding: 14px; border-radius: 8px; font-weight: bold; border: 1px solid #334155; text-transform: uppercase; font-size: 13px; cursor: pointer;">← Voltar</button>
                <button type="submit" name="finalizar_cadastro_loja" style="flex: 1.3; background: linear-gradient(135deg, #38bdf8, #0284c7); color: #fff; padding: 14px; border: none; border-radius: 8px; font-weight: bold; cursor: pointer; text-transform: uppercase; font-size: 12.5px; letter-spacing: 0.5px; box-shadow: 0 4px 12px rgba(2, 132, 199, 0.3);">Ativar Meu Espaço </button>
            </div>
        </div>

    </form>
</div>







<!-- =========================================================================
     🧠 MOTOR JAVASCRIPT REATIVO DE NAVEGAÇÃO ENTRE ABAS E VALIDAÇÃO PWA
     ========================================================================= -->
     <script>
     // 🕹️ MOTOR DE NAVEGAÇÃO DE ABAS SAAS COMERCIAL (Otimizado para Mobile e PWA)
     function irParaAba(numeroAba) {
         // 1. Oculta todos os painéis e remove as classes ativas
         document.querySelectorAll('.aba-painel').forEach(painel => {
             painel.style.display = 'none';
             painel.classList.remove('active');
         });
         
         // 2. Exibe a aba alvo solicitada
         const abaAlvo = document.getElementById('aba-' + numeroAba);
         if (abaAlvo) {
             abaAlvo.style.display = 'block';
             abaAlvo.classList.add('active');
         }
     
         // 3. Atualiza graficamente os indicadores de passos no topo do visor
         document.querySelectorAll('.passo-txt').forEach((ind, index) => {
             const passoAtual = index + 1;
             ind.classList.remove('active');
             
             if (passoAtual === numeroAba) {
                 ind.classList.add('active');
                 ind.style.background = '#1e293b';
                 ind.style.color = '#38bdf8';
                 ind.style.borderColor = '#38bdf8';
             } else if (passoAtual < numeroAba) {
                 ind.style.background = '#0f172a';
                 ind.style.color = '#22c55e'; // Verde indica passo concluído com sucesso
                 ind.style.borderColor = '#22c55e';
             } else {
                 ind.style.background = '#070b12';
                 ind.style.color = '#64748b';
                 ind.style.borderColor = '#1e293b';
             }
         });
     
         // Desloca o scroll horizontal automático para manter o passo visível em telemóveis
         const wrapper = document.querySelector('.barra-passos');
         const itemAtivo = document.getElementById('ind-' + numeroAba);
         if (wrapper && itemAtivo) {
             wrapper.scrollTo({
                 left: itemAtivo.offsetLeft - 20,
                 behavior: 'smooth'
             });
         }
     }
     
     function avancarParaAba2() {
         const nomeLoja = document.getElementById('nome_loja');
         const municipio = document.getElementById('municipio');
         const provincia = document.getElementById('provincia');
         const endereco = document.getElementById('endereco');
     
         if (!nomeLoja || !nomeLoja.value.trim() || !municipio || !municipio.value.trim()) {
             alert("🚨 Dados em falta:\nIntroduza o Nome Comercial da Distribuidora e o Município Sede.");
             return;
         }
         if (provincia && !provincia.value) { alert('Por favor, selecione uma Província.'); return; }
         if (endereco && !endereco.value.trim()) { alert('Por favor, introduza o Endereço.'); return; }
         
         irParaAba(2);
     }
     
     function avancarParaAba3() {
         const email = document.getElementById('email').value.trim();
         const tel = document.getElementById('telefone').value.trim();
         const iban = document.getElementById('iban_bancario');
     
         // Captura os novos campos de PIN da tua Aba 2
         const pin1 = document.getElementById('pin_cadastro').value;
         const pin2 = document.getElementById('pin_confirmacao').value;
     
         if (email === "" || tel === "") {
             alert("🚨 Campos Obrigatórios:\nPreencha o E-mail Mercantil e o Terminal Telefónico.");
             return;
         }
         if (tel.length < 9) { alert('O Terminal Telefónico deve conter exatamente 9 dígitos.'); return; }
         if (iban && !iban.value.trim()) { alert('Por favor, insira o seu IBAN para repasses.'); return; }
     
         if (pin1 === "" || pin2 === "") {
             alert("🚨 Segurança em falta:\nPor favor, defina e confirme o seu PIN de acesso.");
             return;
         }
     
         // 🟢 CORREÇÃO: Validação matemática do tamanho da string do PIN
         if (pin1.length < 4) { 
             alert('O seu PIN de acesso deve conter pelo menos 4 caracteres.'); 
             return; 
         }
     
         if (pin1 !== pin2) {
             alert("❌ Incompatibilidade de PIN:\nOs códigos numéricos introduzidos não coincidem. Verifique-os.");
             return;
         }
     
         irParaAba(3);
     }
     
     function avancarParaAba4() {
         // Caso tenhas validações de checkbox na aba 3 antes de fechar o assistente
         const checkboxes = document.querySelectorAll("input[name='metodos_entrega[]']:checked");
         if (checkboxes.length === 0) {
             alert("🚨 Erro: Selecione pelo menos um método de entrega logística para a sua loja!");
             return;
         }
         irParaAba(4);
     }
     
     // 🕹️ RENDERIZAÇÃO GEOMÉTRICA DE FORMAS EM TEMPO REAL (Aparência da Loja)
     function atualizarPrevisualizacaoLojaForma() {
         const seletorForma = document.getElementById('formato_geometria');
         const demo = document.getElementById('demo_forma_loja');
         if (!demo || !seletorForma) return;
     
         var forma = seletorForma.value;
         demo.style.clipPath = "none";
         demo.style.borderRadius = "0px";
     
         if (forma === 'retangulo') demo.style.borderRadius = "12px";
         if (forma === 'bolla') demo.style.borderRadius = "50%";
         if (forma === 'hexagono') demo.style.clipPath = "polygon(25% 5%, 75% 5%, 100% 50%, 75% 95%, 25% 95%, 0% 50%)";
     }
     
     // Inicializa os ouvintes e gatilhos assim que o documento carregar
     document.addEventListener("DOMContentLoaded", function() {
         atualizarPrevisualizacaoLojaForma();
         
         // Vincula o ouvinte de alteração ao seletor de formas de forma automática
         const seletorForma = document.getElementById('formato_geometria');
         if (seletorForma) {
             seletorForma.addEventListener('change', atualizarPrevisualizacaoLojaForma);
         }
     });
     </script>
     </body>
     </html>