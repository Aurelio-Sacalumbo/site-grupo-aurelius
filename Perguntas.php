<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title>Perguntas Frequentes - Grupo Aurelius</title>
    <style>
        /* ESTILOS DE BASE INTERNOS DO IFRAME */
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            padding: 20px;
            background-color: #0f172a;
            color: #f8fafc;
            margin: 0;
        }

        /* BOTÃO VOLTAR / SAIR */
        .sair { 
            text-decoration: none; 
            display: inline-block;
            font-weight: bold;
            font-size: 13px;
            border-radius: 6px; 
            border: 1px solid #334155; 
            padding: 10px 20px; 
            color: #94a3b8;
            background: #1e293b;
            transition: all 0.2s ease;
            text-transform: uppercase;
        }
        .sair:hover { 
            border-color: #38bdf8; 
            color: #0f172a; 
            background: #38bdf8; 
            box-shadow: 0 0 10px rgba(56, 189, 248, 0.3);
        }

        /* LINK GATILHO SUPERIOR */
        .gatilho-saber-mais {
            text-align: center;
            margin: 25px 0;
        }
        .gatilho-saber-mais a {
            text-decoration: none;
            color: #38bdf8;
            font-size: 24px;
            font-weight: bold;
            transition: color 0.2s ease;
        }
        .gatilho-saber-mais a:hover {
            color: #60a5fa;
        }

        /* BLOCO CENTRAL DAS PERGUNTAS (Começa com hidden) */
        .perguntas {
            background: #1e293b;
            border: 1px solid #334155;
            border-radius: 12px;
            padding: 30px;
            max-width: 850px;
            margin: 0 auto 40px auto;
            box-shadow: 0 4px 10px rgba(0,0,0,0.3);
        }

        .perguntas h1 {
            font-size: 22px;
            color: #38bdf8;
            text-align: center;
            margin-top: 0;
            margin-bottom: 30px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        /* SUBTÍTULOS DE CATEGORIAS (Clientes / Profissionais) */
        .h22 {
            font-size: 15px;
            font-weight: bold;
            color: #e2e8f0;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            border-left: 3px solid #38bdf8;
            padding-left: 10px;
            margin: 30px 0 15px 0;
        }

        /* CAIXAS ACORDION NATIVAS (details) */
        details {
            background-color: #0f172a;
            border: 1px solid #334155;
            border-radius: 6px;
            margin-bottom: 12px;
            padding: 14px;
            transition: all 0.2s ease;
        }
      
        summary {
            font-weight: 600;
            font-size: 14px;
            cursor: pointer;
            outline: none;
            color: #cbd5e1;
            list-style: none; /* Remove a seta padrão em alguns navegadores */
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        /* Indicador personalizado de mais/menos */
        summary::after {
            content: '＋';
            font-size: 12px;
            color: #64748b;
        }
      
        summary:hover {
            color: #38bdf8;
        }
      
        details[open] {
            border-color: #38bdf8;
            box-shadow: 0 0 8px rgba(56, 189, 248, 0.1);
        }

        details[open] summary {
            color: #38bdf8;
            border-bottom: 1px solid #334155;
            padding-bottom: 8px;
        }

        details[open] summary::after {
            content: '－';
            color: #38bdf8;
        }
      
        details p {
            margin-top: 10px;
            margin-bottom: 0;
            color: #94a3b8;
            font-size: 13px;
            line-height: 1.6;
        }

        /* BLOCO DE DEPOIMENTOS */
        .depor {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
            max-width: 850px;
            margin: 0 auto 40px auto;
        }

        .Depoimento {
            background: #1e293b;
            border: 1px solid #334155;
            border-radius: 8px;
            padding: 15px;
            text-align: center;
        }

        .Depoimento h2 {
            font-size: 13px;
            color: #94a3b8;
            margin: 0;
            text-transform: uppercase;
        }

        /* SEÇÃO DE RODAPÉ INSTITUCIONAL */
        footer {
            margin-top: 40px;
        }

        .section {
            background-color: #1e293b;
            border: 1px solid #334155;
            border-radius: 12px;
            padding: 30px 20px;
            margin: 0 auto;
            max-width: 850px;
            text-align: center;
        }

        .section h1 {
            color: #38bdf8;
            font-size: 20px;
            margin-bottom: 10px;
        }

        .section p {
            color: #94a3b8;
            font-size: 13px;
            margin: 5px 0 20px 0;
        }

        .section a {
            color: #cbd5e1;
            text-decoration: none;
            font-size: 13px;
            margin: 0 10px;
            display: inline-block;
        }

        .section a:hover {
            color: #38bdf8;
        }
    </style>
</head>
<body>

    <nav style="margin-bottom: 20px;">
        <a class="sair" href="Principalll.php">⬅️ Voltar</a>
    </nav>

    <div class="gatilho-saber-mais">
        <!-- O CLIQUE DISPARA A FUNÇÃO JAVASCRIPT QUE REMOVE OU ADICIONA O ATTRIBUTO HIDDEN -->
        <a href="javascript:void(0)" onclick="alternarVisibilidadeFAQ()">Saber Mais</a>
    </div>

    <!-- O ELEMENTO INICIA ESCONDIDO ATRAVÉS DO ATRIBUTO HIDDEN DO HTML5 -->
    <div id="blocoFaqAdmin" class="perguntas" hidden>
        <h1>Perguntas frequentes</h1>
        
        <h2 class="h22">Para Clientes</h2>
        
        <details>
            <summary>Como funciona o Grupo Aurelius?</summary>
            <p>O grupo Aurelius é uma plataforma que responde a vários serviços de Salão de Beleza e venda cosméticas online.</p>
            <p>Aqui tu fazes marcação, pedidos ao domicílio e compras on-lines de produtos cosméticos.</p>
        </details>

        <details>
            <summary>Posso Cancelar o serviço?</summary>
            <p>Sim. Caso já tenha adiantado os valores, basta entrar em contacto, mostrar a fatura, terás os valor de volta.</p>
        </details>

        <details>
            <summary>O Grupo Aurelius está em todo País?</summary>
            <p>De momento não, apesar de ser uma plataforma multisserviço, ainda temos escassez em algumas províncias.</p>
        </details>

        <details>
            <summary>E se o Profissional não aparecer ou fizer um trabalho ruim?</summary>
            <p>Nossa equipe de suporte está disponível para ajudar em qualquer problema. Você pode solicitar reembolso ou suporte para mitigar a situação.</p>
        </details>

        <details>
            <summary>Posso fazer o Pagamento depois do serviço?</summary>
            <p>Podes sim. Tens a opção de criar uma factura, na qual vais especificar o tipo de serviço e o preço.</p>
            <p>Vais guardar a fatura e depois do serviço, só assim podes fazer o pagamento.</p>
        </details>

        <h2 class="h22">Para Profissionais</h2>

        <details>
            <summary>Como faço para hospedar meus serviços na Plataforma?</summary>
            <p>Basta se cadastrar como profissional, preencher o formulário e aguardar a aprovação. Após isso, você começa a receber pedidos.</p>
        </details>

        <details>
            <summary>Sou Obrigado a aceitar todos os pedidos?</summary>
            <p>Não. Você decide quais pedidos aceitar, de acordo com sua disponibilidade, interesse e demanda.</p>
        </details>

        <details>
            <summary>Posso ajustar os preços?</summary>
            <p>Sim. Caso o serviço exija materiais extras ou ajustes, podes enviar para o cliente um orçamento detalhado.</p>
        </details>

        <details>
            <summary>Posso conversar com os meus clientes a solo?</summary>
            <p>Sim. A gerência de sua empresa é pessoal.</p>
        </details>
    </div>

    <!-- AREA DE DEPOIMENTOS -->
    <div class="depor">
        <div class="Depoimento"><h2>Depoimento 1</h2></div>
        <div class="Depoimento"><h2>Depoimento 2</h2></div>
        <div class="Depoimento"><h2>Depoimento 3</h2></div>
        <div class="Depoimento"><h2>Depoimento 4</h2></div>
    </div>

    <!-- INFORMAÇÕES DE RODAPÉ -->
    <footer>
        <section class="section">
            <h1><strong>🎌 Aurelius</strong></h1>
            <p>Plataforma líder em marcações de trabalhos ao domicílio e vendas de produtos cosméticos</p>
            <div style="display: flex; justify-content: center; flex-wrap: wrap; gap: 15px;">
                <a href="#">💧 Huambo, Angola</a>
                <a href="mailto:contacto@aureliius.ao">✉️ contacto@aureliius.ao</a>
                <a href="tel:+244925347372">📞 +244 925 347 372</a>
            </div>
        </section>
    </footer>

    <!-- INTERATIVIDADE JAVASCRIPT DO HIDDEN -->
    <script>
    function alternarVisibilidadeFAQ() {
        var faq = document.getElementById("blocoFaqAdmin");
        // Se contiver o atributo hidden (está oculto), remove-o para aparecer. Caso contrário, adiciona-o.
        if (faq.hasAttribute("hidden")) {
            faq.removeAttribute("hidden");
        } else {
            faq.setAttribute("hidden", "true");
        }
    }
    </script>

</body>
</html>