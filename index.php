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
    <!-- Lucide Icons (Ícones modernos e leves) -->
    <script src="https://unpkg.com/lucide@latest"></script>
    <style>
        /* 1. RESET E BASE */
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Segoe UI', system-ui, -apple-system, sans-serif;
        }

        body {
            height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
            overflow: hidden;
            background-color: #0f172a;
            position: relative;
        }

        /* 2. IMAGEM DE FUNDO FIXA */
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
            opacity: 0.18;
            z-index: -3;
            /* Adiciona a animação de Zoom */
            animation: zoomFundo 15s ease-in-out infinite alternate;
        }

        @keyframes zoomFundo {
        0% {
            transform: scale(1);
        }
        100% {
            transform: scale(1.12); /* Expande suavemente a imagem em 12% */
        }
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
                radial-gradient(circle at 20% 30%, rgba(0, 123, 255, 0.45) 0%, transparent 50%),
                radial-gradient(circle at 80% 70%, rgba(220, 53, 69, 0.4) 0%, transparent 50%),
                radial-gradient(circle at 50% 50%, rgba(0, 183, 255, 0.3) 0%, transparent 60%);
            /* Desfocado suave + Saturação elevada para dar vivacidade */
            filter: blur(45px) saturate(160%);
            animation: moveColors 15s ease-in-out infinite alternate;
        }
        
        @keyframes moveColors {
            0% { transform: translate(0, 0) scale(1); }
            50% { transform: translate(-8%, 8%) scale(1.2); }
            100% { transform: translate(8%, -8%) scale(1); }
        }
        /* 4. CONTAINER GLASSMORPHISM (EFEITO VIDRO REFINADO) */
        .login-container {
            position: relative;
            z-index: 1;
            width: 90%;
            max-width: 420px;
            padding: 45px 35px;
            background: rgba(255, 255, 255, 0.75);
            backdrop-filter: blur(20px);
            -webkit-backdrop-filter: blur(20px);
            border-radius: 28px;
            border: 1px solid rgba(255, 255, 255, 0.9);
            box-shadow: 
                0 20px 50px rgba(0, 0, 0, 0.12),
                inset 0 1px 1px rgba(255, 255, 255, 0.9);
            animation: containerEntrance 0.8s cubic-bezier(0.16, 1, 0.3, 1);
        }

        @keyframes containerEntrance {
            from {
                opacity: 0;
                transform: translateY(30px) scale(0.96);
            }
            to {
                opacity: 1;
                transform: translateY(0) scale(1);
            }
        }

        /* 5. ELEMENTOS DO FORMULÁRIO */
        .status-conexao {
            font-size: 11px;
            color: #64748b;
            text-align: center;
            margin-bottom: 12px;
            letter-spacing: 1.8px;
            font-weight: 700;
            text-transform: uppercase;
        }

        h1 {
            color: #0f172a;
            text-align: center;
            font-size: 28px;
            margin-bottom: 28px;
            font-weight: 800;
            letter-spacing: -0.5px;
        }

        .input-group {
            position: relative;
            margin-bottom: 20px;
        }

        label {
            color: #334155;
            font-size: 13px;
            margin-bottom: 8px;
            display: block;
            font-weight: 700;
            letter-spacing: 0.3px;
        }

        .input-wrapper {
            position: relative;
            display: flex;
            align-items: center;
        }

        .input-icon {
            position: absolute;
            left: 14px;
            color: #94a3b8;
            width: 20px;
            height: 20px;
            transition: color 0.3s ease;
        }

        input {
            width: 100%;
            padding: 14px 14px 14px 44px;
            border: 1.5px solid rgba(203, 213, 225, 0.8);
            border-radius: 14px;
            background: rgba(255, 255, 255, 0.95);
            font-size: 15px;
            color: #1e293b;
            font-weight: 500;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }

        input::placeholder {
            color: #94a3b8;
        }

        input:focus {
            outline: none;
            border-color: #007bff;
            background: #ffffff;
            box-shadow: 0 0 0 4px rgba(0, 123, 255, 0.15);
        }

        input:focus + .input-icon,
        .input-wrapper:focus-within .input-icon {
            color: #007bff;
        }

        /* Toggle Senha (Olho) */
        .toggle-password {
            position: absolute;
            right: 14px;
            background: none;
            border: none;
            padding: 0;
            width: auto;
            color: #94a3b8;
            cursor: pointer;
            box-shadow: none;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .toggle-password:hover {
            color: #007bff;
            background: none;
            transform: none;
            box-shadow: none;
        }

        button[type="submit"] {
            width: 100%;
            padding: 16px;
            background: linear-gradient(135deg, #007bff 0%, #0056b3 100%);
            color: white;
            border: none;
            border-radius: 14px;
            font-size: 16px;
            font-weight: 700;
            cursor: pointer;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            box-shadow: 0 6px 20px rgba(0, 123, 255, 0.28);
            margin-top: 10px;
            display: flex;
            justify-content: center;
            align-items: center;
            gap: 8px;
        }

        button[type="submit"]:hover {
            background: linear-gradient(135deg, #0069d9 0%, #004494 100%);
            transform: translateY(-2px);
            box-shadow: 0 10px 25px rgba(0, 123, 255, 0.38);
        }

        button[type="submit"]:active {
            transform: translateY(0);
        }

        /* 6. LINKS INFERIORES */
        .footer-links {
            display: flex;
            justify-content: space-between;
            margin-top: 28px;
            gap: 12px;
            padding-top: 20px;
            border-top: 1px solid rgba(226, 232, 240, 0.8);
        }

        .link-item {
            flex: 1;
            text-align: center;
        }

        .link-item span {
            display: block;
            font-size: 11px;
            color: #64748b;
            margin-bottom: 4px;
            font-weight: 600;
        }

        .link-item a {
            font-size: 13px;
            color: #007bff;
            text-decoration: none;
            font-weight: 700;
            transition: color 0.2s ease;
        }

        .link-item a:hover {
            color: #004494;
            text-decoration: underline;
        }

        /* ALERTA DE ERRO ANIMADO */
        #alerta-erro {
            color: #dc2626;
            background: rgba(254, 226, 226, 0.9);
            border: 1px solid rgba(252, 165, 165, 0.5);
            padding: 12px;
            border-radius: 12px;
            font-size: 13px;
            text-align: center;
            margin-top: 20px;
            display: none;
            font-weight: 600;
            animation: shake 0.4s cubic-bezier(0.36, 0.07, 0.19, 0.97) both;
        }

        @keyframes shake {
            10%, 90% { transform: translate3d(-1px, 0, 0); }
            20%, 80% { transform: translate3d(2px, 0, 0); }
            30%, 50%, 70% { transform: translate3d(-4px, 0, 0); }
            40%, 60% { transform: translate3d(4px, 0, 0); }
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
            <div class="input-group">
                <label for="usuario">Usuário</label>
                <div class="input-wrapper">
                    <i data-lucide="user" class="input-icon"></i>
                    <input type="text" id="usuario" name="login" required placeholder="Digite seu login" autocomplete="username">
                </div>
            </div>

            <div class="input-group">
                <label for="pswd">Senha</label>
                <div class="input-wrapper">
                    <i data-lucide="lock" class="input-icon"></i>
                    <input type="password" id="pswd" name="pswd" required placeholder="••••••••" autocomplete="current-password">
                    <button type="button" class="toggle-password" onclick="togglePasswordVisibility()" aria-label="Mostrar ou ocultar senha">
                        <i data-lucide="eye" id="eye-icon" style="width: 20px; height: 20px;"></i>
                    </button>
                </div>
            </div>

            <button type="submit">
                Entrar <i data-lucide="arrow-right" style="width: 18px; height: 18px;"></i>
            </button>
            
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
        // Inicializa os ícones Lucide
        lucide.createIcons();

        // Função para mostrar/ocultar senha
        function togglePasswordVisibility() {
            const passwordInput = document.getElementById('pswd');
            const eyeIcon = document.getElementById('eye-icon');
            
            if (passwordInput.type === 'password') {
                passwordInput.type = 'text';
                eyeIcon.setAttribute('data-lucide', 'eye-off');
            } else {
                passwordInput.type = 'password';
                eyeIcon.setAttribute('data-lucide', 'eye');
            }
            lucide.createIcons();
        }

        // Lógica de feedback de erro
        const params = new URLSearchParams(window.location.search);
        if (params.has('erro')) {
            const codigoErro = params.get('erro');
            let mensagem = "";
            const alertaDiv = document.getElementById('alerta-erro');

            if (codigoErro === '1') mensagem = "⚠️ Login ou senha incorretos.";
            else if (codigoErro === '2') mensagem = "⚠️ Preencha todos os campos.";
            else if (codigoErro === '3') mensagem = "⚠️ Erro na conexão com o banco.";

            if(mensagem) {
                alertaDiv.style.display = 'block';
                alertaDiv.innerHTML = mensagem;
            }
        }
    </script>
</body>
</html>
