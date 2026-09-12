<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Registo de Clientes e Produtos</title>
    <style>
        /* Estilos Base e Design Moderno */
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: #05357c;
            margin: 0;
            padding: 20px;
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
        }
        a{ text-decoration: none; color: aliceblue; background: blue; padding: 20px; border-radius: 30px;}
        .form-container {
            background: white;
            padding: 30px;
            border-radius: 12px;
            box-shadow: 0 8px 24px rgba(0,0,0,0.1);
            width: 100%;
            max-width: 500px;
        }
        h2 {
            text-align: center;
            color: #0b3c95;
            margin-bottom: 25px;
        }
        .form-group {
            margin-bottom: 18px;
        }
        label {
            display: block;
            margin-bottom: 6px;
            font-weight: 600;
            color: #333;
            font-size: 14px;
        }
        input, select {
            width: 100%;
            padding: 10px;
            border: 1px solid #ccc;
            border-radius: 6px;
            box-sizing: border-box;
            font-size: 15px;
            transition: border-color 0.3s;
        }
        input:focus, select:focus {
            border-color: #0b3c95;
            outline: none;
        }
        .btn-enviar {
            background-color: #0b3c95;
            color: white;
            border: none;
            padding: 12px;
            width: 100%;
            border-radius: 6px;
            font-size: 16px;
            font-weight: bold;
            cursor: pointer;
            transition: background 0.3s;
            margin-top: 10px;
        }
        .btn-enviar:hover {
            background-color: #072663;
        }
        .erro-msg {
            color: #dc3545;
            font-size: 12px;
            margin-top: 4px;
            display: none;
        }
    </style>
</head>
<body>

    <div class="form-container"> <nav> <a href="Dashboard.php"> Voltar</a></nav>
        <h2>Registo de Dados</h2>
        <form id="cadastroForm">
            
            <!-- Nome do Profissional (Select) -->
            <div class="form-group">
                <label for="profissional">Nome do Profissional:</label>
                <select id="profissional" required>
                    <option value="">Selecione um profissional...</option>
                    <option value="Analtino">Handanga (Barbeiro)</option>
                    <option value="Afonso">Albino (Esteticista /Barbeiro/ Manicuri)</option>
                    <option value="Carlos">Carlos (Manicuri)
                        <option value="Analtino">Analtino (Barbeiro)</option>
                    <option value="Afonso">Aurélio (Cabeleireiro)</option>
                    <option value="Carlos">Raimundo(Pedicuri)
                            <option value="Analtino">Analtino (Barbeiro)</option>
                            <option value="Afonso">Afonso (Cabeleireiro)</option>
                            <option value="Carlos">Carlos (Cabeleriro)
                                    </option>
                </select>
            </div>

            <!-- Cliente -->
            <div class="form-group">
                <label for="cliente">Nome do Cliente:</label>
                <input type="text" id="cliente" placeholder="Nome completo do cliente" required>
            </div>

            <!-- Tipo de Produto -->
            <div class="form-group">
                <label for="produto">Tipo de Produto/Serviço:</label>
                <select id="produto" required>
                    <option value="">Selecione o produto...</option>
                    <option value="Corte Jelinho">Corte Francês com barba</option>
                    <option value="Barba Simples">Barba Simples</option>
                    <option value="Tratamento Capilar">Corte Francês sem barba</option>
                    <option value="Careca">Careca</option>
                    <option value="Corte Jelinho">Corte covinha cheia</option>
                    <option value="Barba Simples">Corte covinha vazio</option>
                    <option value="Tratamento Capilar">Tratamento Capilar</option>
                    <option value="Jelinho">Jelinho</option>
                    <option value="Corte Jelinho">Gel normal </option>
                    <option value="Barba Simples">Gel completo</option>
                    <option value="Tratamento Capilar">Tratamento Capilar</option>
                    <option value=" frestyle">Freestyle</option>
                    <option value="Punk">Punk</option>
                    <option value="pinturas Simples"> Pinturas simples</option>
                    <option value="Pinturas completa">Pinturas completa</option>
                    <option value=" pint. malhadas">pinturas malhadas</option>
                    <option value="Pedicuri normal">Pedicuri normal</option>
                    <option value="Pedicuri Completo">Pedicuri Completo</option>
                    <option value="Tranças de cabelo natural">Tranças de cabelo Natural</option>
                    <option value="">Tranças de Postiços</option>
                    <option value="Puchinhos">Puchinho de  Noivas</option>
                    <option value="Cortes Femeninos">Cortes Femeninos</option>
                    <option value="Tatuagens">Tatuagens</option>

                </select>
            </div>

            <!-- Idade -->
            <div class="form-group">
                <label for="idade">Idade:</label>
                <input type="number" id="idade" min="1" max="1200000" placeholder="Ex: 25" required>
            </div>

            <!-- Período -->
            <div class="form-group">
                <label for="periodo">Período:</label>
                <select id="periodo" required>
                    <option value="">Selecione o período...</option>
                    <option value="Manhã">Manhã (08h - 12h)</option>
                    <option value="Tarde">Tarde (12h - 18h)</option>
                    <option value="Noite">Noite (18h - 22h)</option>
                </select>
            </div>

            <!-- Telefone-->
            <div class="form-group">
                <label for="telefone">Telefone do Cliente:</label>
                <input type="text" id="telefone" maxlength="9" placeholder="Apenas números" required>
                <span id="erroTelefone" class="erro-msg">O número deve começar com 9 e conter 9 algarismos.</span>
            </div>

           
            <!-- Email -->
            <div class="form-group">
                <label for="email">E-mail do Cliente:</label>
                <input type="email" id="email" placeholder="seu email" required>
            </div>

            <!-- Botão de Cadastro -->
            <button type="submit" class="btn-enviar">Gravar e Avançar</button>
        </form>
    </div>

    <script>
        document.getElementById('cadastroForm').addEventListener('submit', function(event) {
            event.preventDefault(); 

            const profissional = document.getElementById('profissional').value;
            const cliente = document.getElementById('cliente').value;
            const produto = document.getElementById('produto').value;
            const preço = document.getElementById('idade').value;
            const periodo = document.getElementById('periodo').value;
            const telefone = document.getElementById('telefone').value;
            const email = document.getElementById('email').value;

            // Validação
            const telefoneRegex = /^9[0-9]{8}$/;
            if (!telefoneRegex.test(telefone)) {
                document.getElementById('erroTelefone').style.display = 'block';
                return; 
            } else {
                document.getElementById('erroTelefone').style.display = 'none';
            }
            const novoRegisto = {
                id: Date.now(),
                profissional,
                cliente,
                produto,
                idade,
                periodo,
                telefone,
                email,
                dataRegisto: new Date().toLocaleString('pt-PT')
            };
            let bancoDados = JSON.parse(localStorage.getItem('bancoClientes')) || [];
            
            bancoDados.push(novoRegisto);
            
            // Salvar
            localStorage.setItem('bancoClientes', JSON.stringify(bancoDados));

            alert("Cadastro realizado com sucesso na base de dados local!");

            window.location.href = "Destino.php";
        });

        document.getElementById('telefone').addEventListener('input', function(e) {
            this.value = this.value.replace(/[^0-9]/g, '');
        });
    </script>
</body>
</html>
