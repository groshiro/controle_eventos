<?php
// Arquivo: forgot_password.php
require_once 'conexao.php'; 
$mensagem = $_GET['status'] ?? '';
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <title>Recuperar Senha | Histórico de Eventos</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <style>
        /* 1. RESET E FUNDO ANIMADO (Padronizado com o Login) */
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
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

        /* IMAGEM DE FUNDO FIXA */
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
            opacity: 0.25;
            z-index: -3;
        }

        /* ANIMAÇÃO DE CORES FLUTUANTES */
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

        /* 2. CONTAINER COM EFEITO DE VIDRO */
        .container {
            position: relative;
            z-index: 1;
            background: rgba(255, 255, 255, 0.65);
            backdrop-filter: blur(15px);
            -webkit-backdrop-filter: blur(15px);
            border-radius: 24px;
            border: 1px solid rgba(255, 255, 255, 0.8);
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.1);
            max-width: 400px;
            width: 90%;
            padding: 35px;
            text-align: center;
            animation: fadeIn 0.8s ease-out;
        }

        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }

        h1 {
            color: #333;
            font-size: 1.8rem;
            margin-bottom: 15px;
            font-weight: 700;
        }

        p.description {
            color: #666;
            font-size: 0.95rem;
            margin-bottom: 25px;
            line-height: 1.5;
        }

        /* 3. FORMULÁRIO */
        form {
            text-align: left;
        }

        label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            color: #555;
            font-size: 0.9rem;
        }

        input[type="email"] {
            width: 100%;
            padding: 12px;
            margin-bottom: 20px;
            border: 1px solid rgba(0,0,0,0.1);
            border-radius: 12px;
            background: rgba(255, 255, 255, 0.9);
            font-size: 1rem;
            transition: 0.3s;
            outline: none;
        }

        input:focus {
            border-color: #007bff;
            box-shadow: 0 0 0 4px rgba(0, 123, 255, 0.1);
        }

        /* Botão de Envio (Amarelo/Laranja conforme seu original) */
        button[type="submit"] {
            width: 100%;
            padding: 15px;
            background-color: #f70b0b; /* Ajustado para um vermelho vibrante institucional */
            color: white;
            border: none;
            border-radius: 12px;
            cursor: pointer;
            font-size: 1rem;
            font-weight: bold;
            transition: 0.3s;
            box-shadow: 0 4px 12px rgba(247, 11, 11, 0.2);
        }

        button[type="submit"]:hover {
            background-color: #d60a0a;
            transform: translateY(-2px);
            box-shadow: 0 6px 15px rgba(247, 11, 11, 0.3);
        }

        /* Link Voltar */
        .voltar-link {
            margin-top: 25px;
        }

        .voltar-link a {
            color: #007bff;
            text-decoration: none;
            font-weight: 700;
            font-size: 0.9rem;
            transition: 0.3s;
        }

        .voltar-link a:hover {
            text-decoration: underline;
        }

        .status-msg {
            padding: 12px;
            border-radius: 8px;
            margin-bottom: 20px;
            font-size: 0.9rem;
            background: rgba(40, 167, 69, 0.1);
            color: #155724;
            font-weight: 600;
        }
    </style>
</head>
<body>
    <div class="bg-image"></div>
    <div class="bg-animation"></div>

    <div class="container">
        <h1>Redefinir Senha</h1>
        <p class="description">Insira seu e-mail corporativo. Enviaremos um link seguro para você criar uma nova senha.</p>
        
        <?php if ($mensagem === 'sucesso'): ?>
            <div class="status-msg">
                ✅ Se o e-mail estiver cadastrado, o link foi enviado! Verifique sua caixa de entrada.
            </div>
        <?php endif; ?>

        <form action="send_reset_link.php" method="POST">
            <label for="email">E-mail Corporativo:</label>
            <input type="email" id="email" name="email" placeholder="exemplo@claro.com.br" required>
            
            <button type="submit">Enviar Link de Redefinição</button>
        </form>
        
        <div class="voltar-link">
            <a href="index.php">← Voltar ao Login</a>
        </div>
    </div>
</body>
</html>