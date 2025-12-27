<?php
// Arquivo: index.php
require_once 'conexao.php'; 
?>
<!DOCTYPE html>
<html lang="pt-br">

<head>
    <meta charset="UTF-8">
    <title>Acesso ao Sistema | Histórico de Eventos</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <style>
        /* 1. RESET E BASE */
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Segoe UI', sans-serif;
        }

        body {
            height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
            overflow: hidden;
            background-color: #ffffff;
            position: relative;
        }

        /* 2. IMAGEM DE FUNDO FIXA (MARCA D'ÁGUA) */
        .bg-image {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-image: url('claro.png');
            background-size: cover;
            background-position: center;
            background-repeat: no-repeat;
            opacity: 0.25; /* Imagem suave ao fundo */
            z-index: -3;
        }

        /* 3. ANIMAÇÃO DE CORES FLUTUANTES (MESH GRADIENT) */
        .bg-animation {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            z-index: -2;
            background: 
                radial-gradient(circle at 20% 30%, rgba(0, 123, 255, 0.15) 0%, transparent 40%),
                radial-gradient(circle at 80% 70%, rgba(220, 53, 69, 0.1) 0%, transparent 40%),
                radial-gradient(circle at 50% 50%, rgba(0, 123, 255, 0.05) 0%, transparent 60%);
            filter: blur(60px);
            animation: moveColors 20s ease-in-out infinite alternate;
        }

        @keyframes moveColors {
            0% { transform: translate(0, 0) scale(1); }
            50% { transform: translate(-5%, 5%) scale(1.1); }
            100% { transform: translate(5%, -5%) scale(1); }
        }

        /* 4. CONTAINER GLASSMORPHISM (O VIDRO) */
        .login-container {
            position: relative;
            z-index: 1;
            width: 90%;
            max-width: 400px;
            padding: 40px;
            /* Branco com alta transparência para o efeito de vidro */
            background: rgba(255, 255, 255, 0.6);
            backdrop-filter: blur(15px);
            -webkit-backdrop-filter: blur(15px);
            border-radius: 24px;
            border: 1px solid rgba(255, 255, 255, 0.8);
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.05);
            animation: fadeIn 1s ease-out;
        }

        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }

        /* 5. ELEMENTOS DO FORMULÁRIO */
        .status-conexao {
            font-size: 11px;
            color: #888;
            text-align: center;
            margin-bottom: 15px;
            letter-spacing: 1.5px;
            font-weight: bold;
        }

        h1 {
            color: #333;
            text-align: center;
            font-size: 26px;
            margin-bottom: 30px;
            font-weight: 700;
        }

        label {
            color: #555;
            font-size: 14px;
            margin-bottom: 8px;
            display: block;
            font-weight: 600;
        }

        input {
            width: 100%;
            padding: 14px;
            margin-bottom: 20px;
            border: 1px solid rgba(0,0,0,0.08);
            border-radius: 12px;
            background: rgba(255, 255, 255, 0.9);
            font-size: 16px;
            transition: all 0.3s ease;
        }

        input:focus {
            outline: none;
            border-color: #007bff;
            background: #fff;
            box-shadow: 0 0 0 4px rgba(0, 123, 255, 0.1);
        }

        button {
            width: 100%;
            padding: 16px;
            background: #007bff;
            color: white;
            border: none;
            border-radius: 12px;
            font-size: 16px;
            font-weight: bold;
            cursor: pointer;
            transition: 0.3s;
            box-shadow: 0 4px 12px rgba(0, 123, 255, 0.2);
        }

        button:hover {
            background: #0056b3;
            transform: translateY(-2px);
            box-shadow: 0 6px 15px rgba(0, 123, 255, 0.3);
        }

        /* 6. LINKS INFERIORES LADO A LADO */
        .footer-links {
            display: flex;
            justify-content: space-between;
            margin-top: 25px;
            gap: 10px;
        }

        .link-item {
            flex: 1;
            text-align: center;
        }

        .link-item span {
            display: block;
            font-size: 10px;
            color: #999;
            margin-bottom: 4px;
        }

        .link-item a {
            font-size: 13px;
            color: #007bff;
            text-decoration: none;
            font-weight: 700;
            transition: 0.2s;
        }

        .link-item a:hover {
            color: #004494;
            text-decoration: underline;
        }

        #alerta-erro {
            color: #d93025;
            background: rgba(217, 48, 37, 0.05);
            padding: 10px;
            border-radius: 8px;
            font-size: 13px;
            text-align: center;
            margin-top: 15px;
            display: none;
        }
    </style>
</head>

<body>
    <div class="bg-image"></div>
    
    <div class="bg-animation"></div>

    <div class="login-container">
        <div class="status-conexao">
            <?php echo $status_conexao; ?>
        </div>
        
        <h1>Acessar conta</h1>

        <form action="validar_login.php" method="POST">
            <label for="usuario">Usuário</label>
            <input type="text" id="usuario" name="login" required placeholder="Digite seu login">

            <label for="pswd">Senha</label>
            <input type="password" id="pswd" name="pswd" required placeholder="••••••••">

            <button type="submit">Entrar</button>
            
            <div class="footer-links">
                <div class="link-item">
                    <span>Novo acesso?</span>
                    <a href="cadastro_usuario.php">Criar Conta</a>
                </div>
                <div class="link-item">
                    <span>Esqueceu a senha?</span>
                    <a href="forgot_password.php">Recuperar Senha</a>
                </div>
            </div>
        </form>

        <div id="alerta-erro"></div>
    </div>

    <script>
        const params = new URLSearchParams(window.location.search);
        if (params.has('erro')) {
            const codigoErro = params.get('erro');
            let mensagem = "";
            const alertaDiv = document.getElementById('alerta-erro');

            if (codigoErro === '1') mensagem = "Login ou senha incorretos.";
            else if (codigoErro === '2') mensagem = "Preencha todos os campos.";
            else if (codigoErro === '3') mensagem = "Erro na conexão com o banco.";

            if(mensagem) {
                alertaDiv.style.display = 'block';
                alertaDiv.innerHTML = mensagem;
            }
        }
    </script>
</body>
</html>