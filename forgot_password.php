<?php
// Arquivo: forgot_password.php
require_once 'conexao.php'; 
$mensagem = $_GET['status'] ?? '';
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <title>Esqueci a Senha</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <style>
        /* Estilos Gerais */
        html, body {
            font-family: Arial, sans-serif;
            background-color: transparent;
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            margin: 0;
            height: 100%;
        }
        /* GARANTE QUE O FUNDO SE EXPANDA */
            body::before {
            content: "";
            /* Essencial para pseudo-elementos */
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;

            /* 2. Aplica a Imagem e a Suavização AQUI */
            background-image: url('claro.png');
            background-size: cover;
            background-position: center center;
            background-repeat: no-repeat;

            /* 3. Aplica a transparência SÓ NA IMAGEM, não no texto */
            opacity: 0.2;
            filter: grayscale(80%);

            /* 4. Coloca a imagem ATRÁS do conteúdo */
            z-index: -1;
        }

        /* Container Principal */
        .container {
            max-width: 400px;
            width: 90%;
            padding: 30px;
            background-color: #fff;
            border-radius: 8px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.15);
            text-align: center;
        }

        /* Título */
        h1 {
            color: #333;
            font-size: 1.8em;
            margin-bottom: 10px;
        }
        
        /* Descrição */
        .container > p:first-of-type {
            color: #666;
            margin-bottom: 25px;
            line-height: 1.4;
        }

        /* Estilos do Formulário */
        form {
            text-align: left;
        }

        label {
            display: block;
            margin-bottom: 5px;
            font-weight: bold;
            color: #555;
        }

        input[type="email"] {
            width: 100%;
            padding: 10px;
            margin-bottom: 20px;
            border: 1px solid #ccc;
            border-radius: 4px;
            box-sizing: border-box;
            font-size: 1em;
        }

        /* Botão de Envio */
        button[type="submit"] {
            width: 100%;
            padding: 12px;
            background-color: #f70b0bff; /* Laranja/Âmbar (Cor de alerta/ação) */
            color: white;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 1em;
            font-weight: bold;
            transition: background-color 0.3s ease, box-shadow 0.3s ease;
        }

        button[type="submit"]:hover {
            background-color: #50f305ff;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.2);
        }

        /* Link Voltar */
        .container p:last-child a {
            color: #007bff;
            text-decoration: none;
            font-weight: bold;
            display: inline-block;
            margin-top: 15px;
        }

        .container p:last-child a:hover {
            text-decoration: underline;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>Redefinir Senha</h1>
        <p>Insira seu endereço de e-mail. Enviaremos um link para redefinir sua senha.</p>
        
        <?php if ($mensagem === 'sucesso'): ?>
            <p style="color: green; font-weight: bold;">
                ✅ Se o e-mail estiver cadastrado, o link foi enviado! Verifique sua caixa de entrada.
            </p>
        <?php endif; ?>

        <form action="send_reset_link.php" method="POST">
            <label for="email">E-mail:</label>
            <input type="email" id="email" name="email" required>
            
            <button type="submit">Enviar Link de Redefinição</button>
        </form>
        
        <p><a href="index.php">Voltar ao Login</a></p>
    </div>
</body>
</html>