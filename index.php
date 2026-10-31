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
    <script src="https://unpkg.com/lucide@latest"></script>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Segoe UI', system-ui, -apple-system, sans-serif;
        }

        body {
            min-height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
            overflow-x: hidden;
            background-color: #0f172a;
            position: relative;
            padding: 20px;
        }

        .bg-image {
            position: fixed;
            top: 0; left: 0; width: 100%; height: 100%;
            background-image: url('claro.png');
            background-size: cover;
            background-position: center;
            opacity: 0.18;
            z-index: -3;
            animation: zoomFundo 15s ease-in-out infinite alternate;
        }

        @keyframes zoomFundo {
            0% { transform: scale(1); }
            100% { transform: scale(1.12); }
        }

        .bg-animation {
            position: fixed;
            top: 0; left: 0; width: 100%; height: 100%;
            z-index: -2;
            background: 
                radial-gradient(circle at 20% 30%, rgba(0, 123, 255, 0.45) 0%, transparent 50%),
                radial-gradient(circle at 80% 70%, rgba(220, 53, 69, 0.4) 0%, transparent 50%),
                radial-gradient(circle at 50% 50%, rgba(0, 183, 255, 0.3) 0%, transparent 60%);
            filter: blur(45px) saturate(160%);
            animation: moveColors 15s ease-in-out infinite alternate;
        }
        
        @keyframes moveColors {
            0% { transform: translate(0, 0) scale(1); }
            50% { transform: translate(-8%, 8%) scale(1.2); }
            100% { transform: translate(8%, -8%) scale(1); }
        }

        /* CARD ULTRA-REDONDO COM GLASSMORPHISM */
        .card-redondo {
            position: relative;
            z-index: 1;
            width: 100%;
            max-width: 440px;
            padding: 38px 34px;
            background: rgba(255, 255, 255, 0.78);
            backdrop-filter: blur(24px);
            -webkit-backdrop-filter: blur(24px);
            border-radius: 46px; /* Borda curva e suave */
            border: 1px solid rgba(255, 255, 255, 0.95);
            box-shadow: 
                0 25px 60px rgba(0, 0, 0, 0.22),
                inset 0 1px 2px rgba(255, 255, 255, 0.9);
            animation: containerEntrance 0.7s cubic-bezier(0.16, 1, 0.3, 1);
        }

        @keyframes containerEntrance {
            from { opacity: 0; transform: translateY(25px) scale(0.97); }
            to { opacity: 1; transform: translateY(0) scale(1); }
        }

        .status-conexao {
            font-size: 11px;
            color: #10b981;
            text-align: center;
            margin-bottom: 12px;
            letter-spacing: 1.5px;
            font-weight: 800;
            text-transform: uppercase;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 6px;
        }

        /* NAVEGAÇÃO POR ABAS PILL */
        .tab-switch {
            display: flex;
            background: rgba(226, 232, 240, 0.7);
            padding: 4px;
            border-radius: 50px;
            margin-bottom: 24px;
            border: 1px solid rgba(255, 255, 255, 0.8);
        }

        .tab-btn {
            flex: 1;
            border: none;
            background: transparent;
            padding: 10px 18px;
            border-radius: 50px;
            font-size: 14px;
            font-weight: 700;
            color: #64748b;
            cursor: pointer;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 6px;
        }

        .tab-btn.active {
            background: #ffffff;
            color: #007bff;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.08);
        }

        .input-group {
            position: relative;
            margin-bottom: 16px;
        }

        label {
            color: #334155;
            font-size: 12.5px;
            margin-bottom: 6px;
            display: block;
            font-weight: 700;
            padding-left: 6px;
        }

        .input-wrapper {
            position: relative;
            display: flex;
            align-items: center;
        }

        .input-icon {
            position: absolute;
            left: 16px;
            color: #94a3b8;
            width: 18px;
            height: 18px;
            pointer-events: none;
        }

        input {
            width: 100%;
            padding: 13px 16px 13px 44px;
            border: 1.5px solid rgba(203, 213, 225, 0.85);
            border-radius: 50px; /* Campos com acabamento arredondado */
            background: rgba(255, 255, 255, 0.95);
            font-size: 14px;
            color: #1e293b;
            font-weight: 500;
            transition: all 0.3s ease;
        }

        input::placeholder { color: #94a3b8; }

        input:focus {
            outline: none;
            border-color: #007bff;
            background: #ffffff;
            box-shadow: 0 0 0 4px rgba(0, 123, 255, 0.15);
        }

        .toggle-password {
            position: absolute;
            right: 16px;
            background: none;
            border: none;
            color: #94a3b8;
            cursor: pointer;
            display: flex;
            align-items: center;
        }

        .toggle-password:hover { color: #007bff; }

        .btn-submit {
            width: 100%;
            padding: 14px;
            background: linear-gradient(135deg, #007bff 0%, #0056b3 100%);
            color: white;
            border: none;
            border-radius: 50px;
            font-size: 15px;
            font-weight: 700;
            cursor: pointer;
            transition: all 0.3s ease;
            box-shadow: 0 6px 18px rgba(0, 123, 255, 0.32);
            margin-top: 10px;
            display: flex;
            justify-content: center;
            align-items: center;
            gap: 8px;
        }

        .btn-submit:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 24px rgba(0, 123, 255, 0.42);
        }

        .footer-links {
            display: flex;
            justify-content: center;
            margin-top: 20px;
            padding-top: 14px;
            border-top: 1px solid rgba(226, 232, 240, 0.8);
        }

        .footer-links a {
            font-size: 12.5px;
            color: #007bff;
            text-decoration: none;
            font-weight: 700;
        }

        .footer-links a:hover { text-decoration: underline; }

        #alerta-erro {
            color: #dc2626;
            background: rgba(254, 226, 226, 0.95);
            border: 1px solid rgba(252, 165, 165, 0.5);
            padding: 11px;
            border-radius: 50px;
            font-size: 12.5px;
            text-align: center;
            margin-top: 16px;
            display: none;
            font-weight: 600;
        }

        #alerta-erro.alerta-aviso {
            color: #92400e;
            background: rgba(254, 243, 199, 0.95);
            border: 1px solid rgba(252, 211, 77, 0.8);
        }

        .painel-conteudo { display: none; }
        .painel-conteudo.ativo { display: block; animation: fadeIn 0.4s ease; }

        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(6px); }
            to { opacity: 1; transform: translateY(0); }
        }
    </style>
</head>

<body>
    <div class="bg-image"></div>
    <div class="bg-animation"></div>

    <div class="card-redondo">
        <div class="status-conexao">
            <i data-lucide="shield-check" style="width: 14px; height: 14px;"></i>
            <?php echo !empty($status_conexao) ? $status_conexao : 'SISTEMA SEGURO'; ?>
        </div>

        <!-- ABAS DE NAVEGAÇÃO -->
        <div class="tab-switch">
            <button type="button" class="tab-btn active" id="btn-tab-login" onclick="mudarAba('login')">
                <i data-lucide="log-in" style="width: 16px; height: 16px;"></i> Acessar Conta
            </button>
            <button type="button" class="tab-btn" id="btn-tab-cadastro" onclick="mudarAba('cadastro')">
                <i data-lucide="user-plus" style="width: 16px; height: 16px;"></i> Criar Conta
            </button>
        </div>

        <!-- ABA 1: LOGIN -->
        <div id="painel-login" class="painel-conteudo ativo">
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
                        <button type="button" class="toggle-password" onclick="togglePasswordVisibility('pswd', 'eye-icon-login')">
                            <i data-lucide="eye" id="eye-icon-login" style="width: 18px; height: 18px;"></i>
                        </button>
                    </div>
                </div>

                <button type="submit" class="btn-submit">
                    Entrar <i data-lucide="arrow-right" style="width: 18px; height: 18px;"></i>
                </button>
            </form>

            <div class="footer-links">
                <a href="forgot_password.php">Esqueceu a senha? Recuperar</a>
            </div>
        </div>

        <!-- ABA 2: CRIAR CONTA -->
        <div id="painel-cadastro" class="painel-conteudo">
            <form action="processar_usuario.php?acao=cadastrar" method="POST">
                <div class="input-group">
                    <label for="cad_nome">Nome Completo</label>
                    <div class="input-wrapper">
                        <i data-lucide="user-check" class="input-icon"></i>
                        <input type="text" id="cad_nome" name="nome" required placeholder="Seu nome completo">
                    </div>
                </div>

                <div class="input-group">
                    <label for="cad_email">E-mail Corporativo</label>
                    <div class="input-wrapper">
                        <i data-lucide="mail" class="input-icon"></i>
                        <input type="email" id="cad_email" name="email" required placeholder="seu.email@claro.com.br">
                    </div>
                </div>

                <div class="input-group">
                    <label for="cad_login">Usuário / Login</label>
                    <div class="input-wrapper">
                        <i data-lucide="at-sign" class="input-icon"></i>
                        <input type="text" id="cad_login" name="login" required placeholder="Nome de usuário desejado">
                    </div>
                </div>

                <div class="input-group">
                    <label for="cad_senha">Senha de Acesso</label>
                    <div class="input-wrapper">
                        <i data-lucide="lock" class="input-icon"></i>
                        <input type="password" id="cad_senha" name="senha" required placeholder="••••••••">
                        <button type="button" class="toggle-password" onclick="togglePasswordVisibility('cad_senha', 'eye-icon-cad')">
                            <i data-lucide="eye" id="eye-icon-cad" style="width: 18px; height: 18px;"></i>
                        </button>
                    </div>
                </div>

                <button type="submit" class="btn-submit" style="background: linear-gradient(135deg, #16a34a 0%, #15803d 100%); box-shadow: 0 6px 18px rgba(22, 163, 74, 0.32);">
                    Solicitar Cadastro <i data-lucide="check-circle" style="width: 18px; height: 18px;"></i>
                </button>
            </form>
        </div>

        <div id="alerta-erro"></div>
    </div>

    <script>
        lucide.createIcons();

        function mudarAba(aba) {
            const painelLogin = document.getElementById('painel-login');
            const painelCadastro = document.getElementById('painel-cadastro');
            const btnLogin = document.getElementById('btn-tab-login');
            const btnCadastro = document.getElementById('btn-tab-cadastro');
            const alerta = document.getElementById('alerta-erro');

            alerta.style.display = 'none';

            if (aba === 'login') {
                painelLogin.classList.add('ativo');
                painelCadastro.classList.remove('ativo');
                btnLogin.classList.add('active');
                btnCadastro.classList.remove('active');
            } else {
                painelLogin.classList.remove('ativo');
                painelCadastro.classList.add('ativo');
                btnLogin.classList.remove('active');
                btnCadastro.classList.add('active');
            }
            lucide.createIcons();
        }

        function togglePasswordVisibility(inputId, iconId) {
            const input = document.getElementById(inputId);
            const icon = document.getElementById(iconId);
            
            if (input.type === 'password') {
                input.type = 'text';
                icon.setAttribute('data-lucide', 'eye-off');
            } else {
                input.type = 'password';
                icon.setAttribute('data-lucide', 'eye');
            }
            lucide.createIcons();
        }

        const params = new URLSearchParams(window.location.search);
        if (params.has('erro')) {
            const codigoErro = params.get('erro');
            let mensagem = "";
            const alertaDiv = document.getElementById('alerta-erro');
            alertaDiv.classList.remove('alerta-aviso');

            if (codigoErro === '1') mensagem = "⚠️ Login ou senha incorretos.";
            else if (codigoErro === '2') mensagem = "⚠️ Preencha todos os campos.";
            else if (codigoErro === '3') mensagem = "⚠️ Erro na conexão com o banco.";
            else if (codigoErro === '4') {
                mensagem = "⏳ Sua conta ainda aguarda aprovação de um administrador.";
                alertaDiv.classList.add('alerta-aviso');
            } else if (codigoErro === '5') mensagem = "❌ Sua conta está inativa ou foi bloqueada.";
            else if (codigoErro === '6') {
                mensagem = "⚠️ 2ª tentativa incorreta. Mais uma e seu acesso será bloqueado!";
                alertaDiv.classList.add('alerta-aviso');
            } else if (codigoErro === '7') mensagem = "🚫 Conta bloqueada por 3 tentativas incorretas.";
            else if (codigoErro === 'cadastro_sucesso') {
                mensagem = "✅ Solicitação enviada! Aguarde a aprovação do administrador.";
                alertaDiv.classList.add('alerta-aviso');
            }

            if (mensagem) {
                alertaDiv.style.display = 'block';
                alertaDiv.innerText = mensagem;
            }
        }
    </script>
</body>
</html>
