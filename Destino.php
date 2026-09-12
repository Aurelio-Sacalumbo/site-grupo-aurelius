<html lang="pt">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Registos</title>
    <style>
        body { font-family: Arial; padding: 20px; background: #f4f6f9; }
        .container { background: white; padding: 20px; border-radius: 8px; max-width: 1000px; margin: 0 auto; }
        table { width: 100%; border-collapse: collapse; margin-top: 15px; }
        th, td { border: 1px solid #ddd; padding: 10px; text-align: left; }
        th { background: #0b3c95; color: white; }
        .btn { padding: 8px 12px; border-radius: 4px; border: none; font-weight: bold; cursor: pointer; color: white; text-decoration: none; }
        @media print { .no-print { display: none; } }
    </style>
</head>
<body>
    <div class="container">    <nav>
            <h1 class="logo" onclick="irParaSecao('home')"><strong><u>🎌AURE<span>LIUS</span></u> <h6>Salão de Beleza e Barbearia</h6> </strong></h1></nav>
            
        <h2 style="color:#0b3c95; text-align:center;">Registos na Base de Dados</h2>
        <div class="no-print" style="margin-bottom: 15px;">
            <a href="bnasa.php" class="btn" style="background:#333;">← Novo</a>
            <button class="btn" style="background:#28a745;" onclick="window.print()">🖨️ Imprimir</button>
            <button class="btn" style="background:#dc3545;" onclick="limpar()">🗑️ Limpar</button>
        </div>
        <table>
            <thead>
                <tr><th>Profissional</th><th>Cliente</th><th>Produto</th><th>Preço</th><th>Período</th><th>Telefone</th><th>Boletim</th><th>E-mail</th></tr>
            </thead>
            <tbody id="tabelaCorpo"></tbody>
        </table>
    </div>
    <script>
        function carregar() {
            const dados = JSON.parse(localStorage.getItem('bancoClientes')) || [];
            const corpo = document.getElementById('tabelaCorpo');
            corpo.innerHTML = dados.length ? dados.map(r => `<tr>
                <td>${r.profissional}</td><td>${r.cliente}</td><td>${r.produto}</td><td>${r.preco} kz</td>
                <td>${r.periodo}</td><td>${r.telefone}</td><td>${r.boletim}</td><td>${r.email}</td>
            </tr>`).join('') : `<tr><td colspan="8" style="text-align:center;">Nenhum registo encontrado.</td></tr>`;
        }
        function limpar() {
            if (confirm("Apagar todos os dados?")) { localStorage.removeItem('bancoClientes'); carregar(); }
        }
        carregar();
    </script>
</body>
</html>