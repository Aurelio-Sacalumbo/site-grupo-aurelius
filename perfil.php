<html lang="pt">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ficha Técnica do Funcionário</title>
    <style>
        body { font-family: 'Segoe UI', sans-serif; background: #1a252f; padding: 20px; color: white; }
        .perfil-container { max-width: 600px; background: #2c3e50; padding: 30px; border-radius: 12px; margin: 30px auto; box-shadow: 0 4px 15px rgba(0,0,0,0.3); }
        h2 { border-bottom: 3px solid #3498db; padding-bottom: 10px; color: #3498db; margin-top: 0; }
        .info-group { margin-bottom: 15px; font-size: 16px; display: flex; border-bottom: 1px solid rgba(255,255,255,0.05); padding-bottom: 8px; }
        .info-group strong { color: #bdc3c7; width: 180px; display: inline-block; flex-shrink: 0; }
        .btn-voltar { display: inline-block; margin-top: 20px; background: #3498db; color: white; padding: 10px 15px; text-decoration: none; border-radius: 6px; font-weight: bold; }
        .btn-voltar:hover { background: #2980b9; }
    </style>
</head>
<body>
    <div id="lista-gestao-admin"></div>
<div class="perfil-container">
    <h2>📄 Ficha Cadastral e Profissional</h2>
    <div id="dados-funcionario">Carregando dados do profissional...</div>
    <a href="Dashboard.html" class="btn-voltar">← Voltar ao Painel</a>
</div>
<script src="https://gstatic.com"></script>
<script src="https://gstatic.com"></script>

<script>
    // 2. CONFIGURAÇÃO DA SUA BASE DE DADOS EXISTENTE (basefistore)
    const firebaseConfig = {
        apiKey: "AIzaSyBfJPuo7UOWYSxEVb-1wzJ0_m9IxQmxE50",
        authDomain: "://firebaseapp.com",
        projectId: "basefistore",
        storageBucket: "basefistore.firebasestorage.app",
        messagingSenderId: "420094149013",
        appId: "1:420094149013:web:dd04ef523c082937e7b69c"
    };

    // 3. INICIALIZAÇÃO DA VARIÁVEL CRÍTICA DB
    var db = null;
    try {
        if (typeof firebase !== 'undefined') {
            firebase.initializeApp(firebaseConfig);
            db = firebase.firestore();
            console.log("🔥 Ligado com sucesso ao Firestore no perfil.html!");
        }
    } catch(e) {
        console.error("Erro na ligação inicial:", e.message);
    }

    // 4. A SUA FUNÇÃO QUE DAVA ERRO (Agora já encontra a variável 'db')
    function lerFichaDoFirebase() {
        const params = new URLSearchParams(window.location.search);
        const idDoc = params.get('id');

        if (!db) {
            console.error("Aguardando inicialização do banco...");
            return;
        }
        if (!idDoc) {
            document.getElementById('conteudo-perfil').innerHTML = "Erro nos parâmetros de busca.";
            return;
        }

        db.collection("funcionarios").doc(idDoc).get().then((doc) => {
            if (!doc.exists) {
                document.getElementById('conteudo-perfil').innerHTML = "Profissional não localizado no banco.";
                return;
            }

            const func = doc.data();
            document.getElementById('conteudo-perfil').innerHTML = `
                <div><strong>Nome Completo:</strong> ${func.nome}</div>
                <div><strong>Idade:</strong> ${func.idade || 'Não informada'} anos</div>
                <div><strong>Sexo:</strong> ${func.sexo || 'Não informado'}</div>
                <div><strong>Estado Civil:</strong> ${func.estado_civil || 'Não informado'}</div>
                <hr style="border:0; border-top:1px dashed #1a252f; margin:15px 0;">
                <div><strong>Formação Académica:</strong> ${func.formacao_academica || 'Nenhuma'}</div>
                <div><strong>Formação Profissional:</strong> ${func.formacao_profissional || 'Nenhuma'}</div>
                <div><strong>Experiência Laboral:</strong> ${func.experiencia || 'Nenhuma'}</div>
                <div><strong>Cursos Extra:</strong> ${func.cursos || 'Nenhum'}</div>
            `;
        }).catch(erro => {
            console.error("Erro ao ler perfil:", erro);
        });
    }

    // Executa a função imediatamente
    lerFichaDoFirebase();
</script>
</body>
</html>