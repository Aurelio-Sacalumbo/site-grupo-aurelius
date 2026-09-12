<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Msica</title>
    <style>
        body { font-family: Arial, sans-serif; background: #f0f2f5; margin: 0; padding: 0px; text-align: center; }

        #header{ 
    background-color: rgb(1, 18, 59); }
#h1-header{ 
    color: #fff; font-family:serif; padding-left:5px; padding-top:1px; padding-bottom:5px; text-align: center;  }
.h3-header{
     color: #fff; font-family:serif; padding-top: 1px; padding-bottom:10px; text-align: center; }
     .Nav0{ text-align:right; padding-bottom: 15px; padding-right: 15px;  }
.Nav1{ text-decoration:none; color:rgb(4, 44, 138); padding: 10px;  background-color:#fff; border-radius: 5px;  }
.Main1{ display:flex; font-weight: bold;}
.Nav1 .Voltar{float: left; margin-left: 5px; margin-bottom: 10px; justify-content: 0 auto; max-width: 20px; }
.Section{ 
    background:rgb(67, 72, 78); margin: 30px; text-align: center; color: #fff; width: 90%; height: 100px; border-radius: 12px;
 }  
 .aba { padding: 30px; margin: 20px auto; max-width: 400px; border-radius: 8px; box-shadow: 0 4px 6px rgba(0,0,0,0.1); }
 #aba1 { background: #e0f7fa; width: 50%; }
 #aba2 {  display: none; }
 #btn-avancar { padding: 10px 20px; font-size: 16px; border: none; border-radius: 5px; background: #333; color: #fff; cursor: pointer; }
 #btn-avancar:hover { background: #555; }

.container {  margin: 5px;  background: rgb(255, 255, 255); padding: 30px;gap: 30px; border-radius: 8px; box-shadow: 0 4px 8px rgba(0,0,0,0.1); }
        .grids{    display: grid; gap: 5px; margin:0 auto; padding: 10px;}
        a img{  }
        #rodapé { 
    background: rgb(19, 78, 218);
}

#h2-rodapé{ 
    text-align: center; color: #fff; font-size: 25px; font-family: Arial; padding-top: 5px;
}
.p1-footer{ 
     color: #fff; font-family: Arial; padding-left: 5px; padding-bottom: 5px; font-size:18px;
}
      
        
    
    </style>
</head>
<body>
    <header id="header">
        <h3 class="h3-header"> As melhores músicas do país</h3>
        <nav class="Nav0">
            <a class="Voltar" href="Parte3.html">
                <img style="float:left;margin-left:20px; " width="40px" src="images (18).png" alt=""> </a> <br>
            <a class="Nav1" href="#">Dj</a>
            <a class="Nav1" href="#">Estilo</a>
            <a class="Nav1" href="#">Stúdio</a>
            <a class="Nav1" href="Video.html">Vídeos</a>
            <a class="Nav1" href="#">Letras</a>  
        </nav>
    </header>
   
    <section class="Section">


    </section> <br>
    

 <!-- Aba 1 -->
 <div id="aba1" class="aba">
     <h2> Formas de pagamentos: <br>
  
      
            <form id="Form" class="formd">
                <input type="text" id="nomeUsuario" name="nomeUsuario" placeholder="Nome do Usuário" required>
                <input type="text" id="produto" name="produto" placeholder="Tipo de produto">
                <input type="text" id="telefone" name="telefone" placeholder="Telefone">
                <input type="number" step="0.01" id="preco" name="preco" placeholder="Preço (Kz)">
                <input type="datetime-local" id="dataHora" name="dataHora" required>
            
                <select id="formaPagamento" name="formaPagamento" required>
                    <option value="">Escolha a Forma de Pagamento</option>
                    <option value="Visa">Visa</option>
                    <option value="Kz">Kz (Dinheiro)</option>
                    <option value="UnitelMoney">UnitelMoney</option>
                    <option value="Express">Express</option>
                    <option value="Outro">Outro</option>
                </select> <br> <br>
                <button id="btn-avancar" onclick="cliqueManual()"> Enviar</button>
     </h2>
    </div>

    <!-- Aba 2 -->
    <div id="aba2" class="aba">
            <div class="container">
                    <a href="https://www.instagram.com/gerilson_insrael/"> <img  width="120" src="download.webp" alt=""></a>
                    </div>
                    <div class="container">
                    <a href="https://instagram.com"><img width="120" src="download (1).jpg" alt=""></a>
                    
                    </div>
                    <div class="container">
                    <a href="https://youtube.com"><img width="120" src="images.webp" alt=""></a>
                    </div>
                    <div class="container">
                    <a href="https://instagram.com"><img width="120" src="downlod.jpg" alt=""></a>
                    </div>
                    <div class="container">
                    <a href="https://wikipedia.org"><img width="120" src="download.jpg" alt=""></a></div>
                    <div class="container">
                    <a href="https://platinaline.com"><img width="120" src="images (10).jpg" alt=""></a>
                    </div>
                    </div>
                 </div>
                 <footer id="rodapé">
                    <h2 id="h2-rodapé"> Contactos do Salão de Beleza</h2>
                    <p class="p1-footer"> whatsapp: (+244) 925347372 </p>
                    <p class="p1-footer"> Email: aureliusjbs@gmail.com </p>
                    <p class="p1-footer"> Endereço: Bairro de São Luis Catimba, casa nº261 </p>
                    <p class="p1-footer"> Desenvolvido pelo Eng. Aurélio Jamba Sacalumbo </p>
                    <p class="p1-footer"> Especialista em Programação Web </p>
            
                </footer>
            

  

    <script>
        let temporizador = setInterval(alternarAbas, 99000);

        function alternarAbas() {
            const aba1 = document.getElementById('aba1');
            const aba2 = document.getElementById('aba2');
            const botao = document.getElementById('btn-avancar');

            // Verifica se 1 está visível
            if (aba1.style.display !== 'none') {
                aba1.style.display = 'none';
                aba2.style.display = 'block';
                botao.style.display = 'none';        
                
            } else {
                aba1.style.display = 'block';
                aba2.style.display = 'none';
                botao.style.display = 'inline-block'; 
            }
        }

        function cliqueManual() {
            clearInterval(temporizador);       
            alternarAbas();                
            temporizador = setInterval(alternarAbas, 99000);
        }
    </script>
    <script src="cordova.js"></script>
    <script src="js/index.js"></script>
</body>
</html>
