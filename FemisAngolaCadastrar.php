<html lang="pt">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cadastro - Barbearia Branca</title>
    <script type="module" src="LogarCadastro.js"></script>
</head>
<style>
    body, html { width: 100%; height: 100%; overflow: hidden; margin: 0; }
    body { 
        background: linear-gradient(rgb(54, 54, 241), rgba(255, 255, 0, 0.205), rgb(27, 97, 27)); 
        display: flex; justify-content: center; align-items: center; font-family: Arial, sans-serif; 
    }
    .page { background: rgba(0, 0, 0, 0.2); border: 3px double white; border-radius: 40px; padding: 40px; width: 350px; text-align: center; color: white; }
    .input-A { padding: 10px; }
    input, select { padding: 15px; width: 100%; border-radius: 25px; border: none; box-sizing: border-box; }
    button { padding: 15px; width: 100%; border-radius: 25px; border: none; font-weight: bold; cursor: pointer; background-color: #fff; color: #000; margin-top: 10px; }
    p a { color: yellow; font-weight: bold; cursor: pointer; text-decoration: underline; }
    .message { margin-top: 15px; font-weight: bold; color: #ffff00; min-height: 20px; }
</style>
<body>

    <div class="page">
        <form id="cadastroForm">
            <h1>Criar Conta</h1>
            <h2>Registo</h2>
            <div class="input-A">
                <input type="text" id="cadastroNome" placeholder="Nome Completo" required>
            </div>
            <div class="input-A">
                <input type="email" id="cadastroEmail" placeholder="Seu E-mail" required>
            </div>
            <div class="input-A">
                <input type="tel" id="cadastroTelefone" placeholder="Telefone" pattern="^9[0-9]{8}$" maxlength="9" title="Deve começar com 9 e ter 9 dígitos" required>
            </div>
            <div class="input-A">
                <select id="cadastroProvincia" required>
                    <option value="" disabled selected>Selecione a sua Província</option>
                    <option value="Bengo">Bengo</option>
                    <option value="Benguela">Benguela</option>
                    <option value="Bié">Bié</option>
                    <option value="Cabinda">Cabinda</option>
                    <option value="Cunene">Cunene</option>
                    <option value="Huambo">Huambo</option>
                    <option value="Huíla">Huíla</option>
                    <option value="Kuando-Kubango">Kuando-Kubango</option>
                    <option value="Kuanza-Norte">Kuanza-Norte</option>
                    <option value="Kuanza-Sul">Kuanza-Sul</option>
                    <option value="Luanda">Luanda</option>
                    <option value="Lunda-Norte">Lunda-Norte</option>
                    <option value="Lunda-Sul">Lunda-Sul</option>
                    <option value="Malanje">Malanje</option>
                    <option value="Moxico">Moxico</option>
                    <option value="Namibe">Namibe</option>
                    <option value="Uíge">Uíge</option>
                    <option value="Zaire">Zaire</option>
                </select>
            </div>
            <div class="input-A">
                <input type="password" id="cadastroPassword" placeholder="Palavra-passe" required>
            </div>
            <button type="submit">Cadastrar</button>
            <div>
                <p>Já tem uma conta? <a href="FemisAngolaCadastrar.php">Entrar</a></p>
                <p>   <a href="Principal.php">sair</a></p>
            </div>
        </form>
        <div id="mensagem" class="message"></div>
    </div>

</body>
</html>