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
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Inter', 'Segoe UI', system-ui, -apple-system, sans-serif;
        }

        body {
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            overflow-x: hidden;
            background-color: #060913;
            position: relative;
            padding: 20px;
            color: #f8fafc;
        }

        /* FUNDO COM ZOOM CINEMATOGRÁFICO */
        .bg-image {
            position: fixed;
            top: 0; left: 0; width: 100%; height: 100%;
            background-image: url('claro.png');
            background-size: cover;
            background-position: center;
            opacity: 0.28;
            z-index: -3;
            animation: zoomFundo 20s ease-in-out infinite alternate;
        }

        @keyframes zoomFundo {
            0% { transform: scale(1); }
            100% { transform: scale(1.1); }
        }

        /* ILUMINAÇÃO AMBIENTE DINÂMICA */
        .bg-animation {
            position: fixed;
            top: 0; left: 0; width: 100%; height: 100%;
            z-index: -2;
            background: 
                radial-gradient(circle at 22% 28%, rgba(0, 123, 255, 0.55) 0%, transparent 48%),
                radial-gradient(circle at 78% 72%, rgba(224, 40, 16, 0.5) 0%, transparent 48%),
                radial-gradient(circle at 50% 50%, rgba(14, 165, 233, 0.25) 0%, transparent 65%);
            filter: blur(55px) saturate(165%);
            animation: moveColors 14s ease-in-out infinite alternate;
        }
        
        @keyframes moveColors {
            0% { transform: translate(0, 0) scale(1); }
            50% { transform: translate(-5%, 5%) scale(1.12); }
            100% { transform: translate(5%, -5%) scale(1); }
        }

        /* MALHA DE PONTOS SUTIL (ESTÉTICA TECH / NOC) */
        .bg-grid {
            position: fixed;
            top: 0; left: 0; width: 100%; height: 100%;
            z-index: -1;
            background-image: radial-gradient(rgba(255, 255, 255, 0.08) 1px, transparent 1px);
            background-size: 28px 28px;
            pointer-events: none;
        }

        /* --- MINI PLAYER DE MÚSICA (DARK GLASS) --- */
        .music-pill {
            position: fixed;
            top: 24px;
            right: 24px;
            z-index: 100;
            display: flex;
            align-items: center;
            gap: 12px;
            background: rgba(15, 23, 42, 0.65);
            backdrop-filter: blur(22px);
            -webkit-backdrop-filter: blur(22px);
            padding: 8px 16px 8px 10px;
            border-radius: 50px;
            border: 1px solid rgba(255, 255, 255, 0.16);
            box-shadow: 0 12px 32px rgba(0, 0, 0, 0.4);
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }

        .music-pill:hover {
            transform: translateY(-2px);
            border-color: rgba(56, 189, 248, 0.45);
            box-shadow: 0 16px 36px rgba(0, 123, 255, 0.3);
        }

        .btn-audio-toggle {
            width: 34px;
            height: 34px;
            border-radius: 50%;
            border: none;
            background: linear-gradient(135deg, #007bff 0%, #e02810 100%);
            color: #ffffff;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            box-shadow: 0 4px 12px rgba(224, 40, 16, 0.4);
            transition: transform 0.2s ease;
        }

        .btn-audio-toggle:hover {
            transform: scale(1.08);
        }

        .music-meta {
            display: flex;
            flex-direction: column;
            min-width: 95px;
        }

        .music-label {
            font-size: 11.5px;
            font-weight: 700;
            color: #f8fafc;
            line-height: 1.2;
        }

        .music-sub {
            font-size: 10px;
            font-weight: 500;
            color: #94a3b8;
        }

        /* EQUALIZADOR ANIMADO */
        .eq-bars {
            display: flex;
            align-items: flex-end;
            gap: 2.5px;
            height: 14px;
            width: 16px;
        }

        .eq-bars span {
            width: 3px;
            height: 4px;
            background: #38bdf8;
            border-radius: 2px;
            transition: height 0.2s ease;
        }

        .eq-bars.active span {
            animation: eqPulse 0.7s ease-in-out infinite alternate;
        }
        .eq-bars.active span:nth-child(1) { animation-delay: 0.1s; background: #38bdf8; }
        .eq-bars.active span:nth-child(2) { animation-delay: 0.3s; background: #f87171; }
        .eq-bars.active span:nth-child(3) { animation-delay: 0.2s; background: #60a5fa; }

        @keyframes eqPulse {
            0% { height: 4px; }
            100% { height: 14px; }
        }

        .volume-range {
            -webkit-appearance: none;
            width: 52px;
            height: 4px;
            background: rgba(255, 255, 255, 0.2);
            border-radius: 5px;
            outline: none;
            cursor: pointer;
        }

        .volume-range::-webkit-slider-thumb {
            -webkit-appearance: none;
            width: 12px;
            height: 12px;
            border-radius: 50%;
            background: #ffffff;
            cursor: pointer;
            box-shadow: 0 0 8px rgba(56, 189, 248, 0.8);
        }

        /* --- CARD PRINCIPAL DARK GLASSMORPHISM --- */
        .card-redondo {
            position: relative;
            z-index: 1;
            width: 100%;
            max-width: 440px;
            padding: 38px 34px 32px;
            background: rgba(15, 23, 42, 0.62);
            backdrop-filter: blur(30px);
            -webkit-backdrop-filter: blur(30px);
            border-radius: 40px;
            border: 1px solid rgba(255, 255, 255, 0.16);
            box-shadow: 
                0 32px 70px rgba(0, 0, 0, 0.55),
                inset 0 1px 1px rgba(255, 255, 255, 0.22);
            animation: containerEntrance 0.75s cubic-bezier(0.16, 1, 0.3, 1);
        }

        @keyframes containerEntrance {
            from { opacity: 0; transform: translateY(25px) scale(0.97); }
            to { opacity: 1; transform: translateY(0) scale(1); }
        }

        /* CABEÇALHO DO SISTEMA (BRANDING) */
        .brand-header {
            text-align: center;
            margin-bottom: 24px;
        }

        .brand-badge {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 5px 12px;
            border-radius: 50px;
            background: rgba(16, 185, 129, 0.12);
            border: 1px solid rgba(52, 211, 153, 0.3);
            color: #34d399;
            font-size: 10.5px;
            font-weight: 700;
            letter-spacing: 1px;
            text-transform: uppercase;
            margin-bottom: 12px;
        }

        .pulse-dot {
            width: 6px;
            height: 6px;
            border-radius: 50%;
            background: #34d399;
            box-shadow: 0 0 8px #34d399;
            animation: pulseLive 1.8s infinite;
        }

        @keyframes pulseLive {
            0%, 100% { opacity: 1; transform: scale(1); }
            50% { opacity: 0.4; transform: scale(0.8); }
        }

        .brand-title {
            font-size: 21px;
            font-weight: 800;
            color: #ffffff;
            letter-spacing: -0.4px;
            margin-bottom: 4px;
        }

        .brand-subtitle {
            font-size: 12.5px;
            color: #94a3b8;
            font-weight: 400;
        }

        /* NAVEGAÇÃO POR ABAS PILL */
        .tab-switch {
            display: flex;
            background: rgba(0, 0, 0, 0.38);
            padding: 5px;
            border-radius: 50px;
            margin-bottom: 24px;
            border: 1px solid rgba(255, 255, 255, 0.08);
        }

        .tab-btn {
            flex: 1;
            border: none;
            background: transparent;
            padding: 10.5px 16px;
            border-radius: 50px;
            font-size: 13px;
            font-weight: 600;
            color: #94a3b8;
            cursor: pointer;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 6px;
        }

        .tab-btn:hover:not(.active) {
            color: #ffffff;
        }

        .tab-btn.active {
            background: linear-gradient(135deg, #007bff 0%, #0056b3 100%);
            color: #ffffff;
            box-shadow: 0 4px 15px rgba(0, 123, 255, 0.4);
        }

        .input-group {
            position: relative;
            margin-bottom: 16px;
        }

        label {
            color: #cbd5e1;
            font-size: 12px;
            margin-bottom: 6px;
            display: block;
            font-weight: 600;
            padding-left: 12px;
            letter-spacing: 0.2px;
        }

        .input-wrapper {
            position: relative;
            display: flex;
            align-items: center;
        }

        .input-icon {
            position: absolute;
            left: 17px;
            color: #64748b;
            width: 18px;
            height: 18px;
            pointer-events: none;
            transition: color 0.3s ease;
        }

        input[type="text"],
        input[type="password"],
        input[type="email"] {
            width: 100%;
            padding: 13.5px 18px 13.5px 46px;
            border: 1.5px solid rgba(255, 255, 255, 0.12);
            border-radius: 50px;
            background: rgba(255, 255, 255, 0.06);
            font-size: 14px;
            color: #ffffff;
            font-weight: 500;
            transition: all 0.3s ease;
        }

        input::placeholder { color: #64748b; }

        input:focus {
            outline: none;
            border-color: #38bdf8;
            background: rgba(255, 255, 255, 0.11);
            box-shadow: 0 0 0 4px rgba(56, 189, 248, 0.15);
            transform: translateY(-1px);
        }

        .input-wrapper:focus-within .input-icon {
            color: #38bdf8;
        }

        .toggle-password {
            position: absolute;
            right: 17px;
            background: none;
            border: none;
            color: #64748b;
            cursor: pointer;
            display: flex;
            align-items: center;
            transition: color 0.2s ease;
        }

        .toggle-password:hover { color: #ffffff; }

        /* BOTÃO PRINCIPAL COM EFEITO SHIMMER (BRILHO DESLIZANTE) */
        .btn-submit {
            position: relative;
            overflow: hidden;
            width: 100%;
            padding: 14.5px;
            background: linear-gradient(135deg, #007bff 0%, #0052cc 100%);
            color: white;
            border: none;
            border-radius: 50px;
            font-size: 15px;
            font-weight: 700;
            cursor: pointer;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            box-shadow: 0 8px 24px rgba(0, 123, 255, 0.38);
            margin-top: 14px;
            display: flex;
            justify-content: center;
            align-items: center;
            gap: 8px;
        }

        .btn-submit::after {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 60%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.28), transparent);
            transform: skewX(-25deg);
            transition: left 0.65s ease;
        }

        .btn-submit:hover {
            transform: translateY(-2px);
            box-shadow: 0 14px 30px rgba(0, 123, 255, 0.52);
        }

        .btn-submit:hover::after {
            left: 150%;
        }

        .btn-submit:active {
            transform: translateY(0);
        }

        .footer-links {
            display: flex;
            justify-content: center;
            margin-top: 22px;
            padding-top: 16px;
            border-top: 1px solid rgba(255, 255, 255, 0.1);
        }

        .footer-links a {
            font-size: 12.5px;
            color: #38bdf8;
            text-decoration: none;
            font-weight: 600;
            transition: color 0.2s ease;
        }

        .footer-links a:hover {
            color: #ffffff;
            text-decoration: underline;
        }

        #alerta-erro {
            color: #fca5a5;
            background: rgba(220, 38, 38, 0.22);
            border: 1px solid rgba(248, 113, 113, 0.45);
            padding: 11px 16px;
            border-radius: 50px;
            font-size: 12.5px;
            text-align: center;
            margin-top: 16px;
            display: none;
            font-weight: 600;
        }

        #alerta-erro.alerta-aviso {
            color: #fde047;
            background: rgba(234, 179, 8, 0.2);
            border: 1px solid rgba(250, 204, 21, 0.45);
        }

        .painel-conteudo { display: none; }
        .painel-conteudo.ativo { display: block; animation: fadeIn 0.4s ease; }

        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(6px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .system-footer {
            margin-top: 22px;
            font-size: 11px;
            color: rgba(255, 255, 255, 0.45);
            text-align: center;
            letter-spacing: 0.4px;
        }

        @media (max-width: 520px) {
            .music-pill {
                top: 14px;
                right: 50%;
                transform: translateX(50%);
                width: max-content;
            }
            .music-pill:hover {
                transform: translateX(50%) translateY(-2px);
            }
            .card-redondo {
                margin-top: 50px;
                padding: 32px 24px 26px;
            }
        }
    </style>
</head>

<body>
    <div class="bg-image"></div>
    <div class="bg-animation"></div>
    <div class="bg-grid"></div>

    <!-- ÁUDIO DE ENTRADA -->
    <audio id="musica-entrada" loop preload="auto">
        <source src="Keane - Somewhere Only We Know.mp3" type="audio/mpeg">
    </audio>

    <!-- PLAYER DE MÚSICA FLUTUANTE -->
    <div class="music-pill">
        <button type="button" class="btn-audio-toggle" id="btn-audio" onclick="alternarMusica()" title="Tocar / Pausar som">
            <i data-lucide="play" id="icone-audio" style="width: 15px; height: 15px; margin-left: 2px;"></i>
        </button>

        <div class="music-meta">
            <span class="music-label">Áudio Ambiente</span>
            <span class="music-sub" id="texto-status-audio">Ativar som</span>
        </div>

        <div class="eq-bars" id="barras-eq">
            <span></span><span></span><span></span>
        </div>

        <input type="range" class="volume-range" id="controle-volume" min="0" max="1" step="0.05" value="0.5" title="Volume">
    </div>

    <!-- CARD CENTRAL -->
    <div class="card-redondo">
        <div class="brand-header">
            <div class="brand-badge">
                <span class="pulse-dot"></span>
                <?php echo !empty($status_conexao) ? $status_conexao : 'Portal Operacional'; ?>
            </div>
            <h1 class="brand-title">Controle de Incidentes</h1>
            <p class="brand-subtitle">Histórico de Eventos & Monitoramento de Rede</p>
        </div>

        <!-- ABAS DE NAVEGAÇÃO -->
        <div class="tab-switch">
            <button type="button" class="tab-btn active" id="btn-tab-login" onclick="mudarAba('login')">
                <i data-lucide="log-in" style="width: 15px; height: 15px;"></i> Acessar Conta
            </button>
            <button type="button" class="tab-btn" id="btn-tab-cadastro" onclick="mudarAba('cadastro')">
                <i data-lucide="user-plus" style="width: 15px; height: 15px;"></i> Criar Conta
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
                    Entrar <i data-lucide="door-open" style="width: 19px; height: 19px;"></i>
                </button>
            </form>

            <div class="footer-links">
                <a href="forgot_password.php">Esqueceu a senha? Recuperar</a>
            </div>
        </div>

        <!-- ABA 2: CRIAR CONTA -->
        <div id="painel-cadastro" class="painel-conteudo">
            <form action="cadastro_usuario.php?acao=cadastrar" method="POST">
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

                <button type="submit" class="btn-submit" style="background: linear-gradient(135deg, #10b981 0%, #059669 100%); box-shadow: 0 8px 24px rgba(16, 185, 129, 0.35);">
                    Solicitar Cadastro <i data-lucide="check-circle" style="width: 18px; height: 18px;"></i>
                </button>
            </form>
        </div>

        <div id="alerta-erro"></div>
    </div>

    <div class="system-footer">
        Gestão de Incidentes &bull; Acesso Restrito e Monitorado
    </div>

    <script>
        lucide.createIcons();

        // --- PLAYER DE MÚSICA COM FADE-IN SUAVE ---
        const audio = document.getElementById('musica-entrada');
        const btnAudio = document.getElementById('btn-audio');
        const textoAudio = document.getElementById('texto-status-audio');
        const barrasEq = document.getElementById('barras-eq');
        const controleVolume = document.getElementById('controle-volume');
        let intervaloFade = null;

        function iniciarComFadeIn(volumeAlvo) {
            clearInterval(intervaloFade);
            audio.volume = 0;
            audio.play().then(() => {
                atualizarEstadoPlayer(true);
                intervaloFade = setInterval(() => {
                    if (audio.volume < volumeAlvo - 0.05) {
                        audio.volume = Math.min(volumeAlvo, audio.volume + 0.05);
                    } else {
                        audio.volume = volumeAlvo;
                        clearInterval(intervaloFade);
                    }
                }, 80);
            }).catch(() => {
                atualizarEstadoPlayer(false);
            });
        }

        function alternarMusica() {
            if (audio.paused) {
                iniciarComFadeIn(parseFloat(controleVolume.value));
            } else {
                clearInterval(intervaloFade);
                audio.pause();
                atualizarEstadoPlayer(false);
            }
        }

        function atualizarEstadoPlayer(tocando) {
            if (tocando) {
                btnAudio.innerHTML = '<i data-lucide="pause" style="width: 15px; height: 15px;"></i>';
                textoAudio.innerText = 'Tocando...';
                textoAudio.style.color = '#38bdf8';
                barrasEq.classList.add('active');
            } else {
                btnAudio.innerHTML = '<i data-lucide="play" style="width: 15px; height: 15px; margin-left: 2px;"></i>';
                textoAudio.innerText = 'Pausado';
                textoAudio.style.color = '#94a3b8';
                barrasEq.classList.remove('active');
            }
            lucide.createIcons();
        }

        controleVolume.addEventListener('input', (e) => {
            clearInterval(intervaloFade);
            audio.volume = parseFloat(e.target.value);
        });

        window.addEventListener('load', () => {
            iniciarComFadeIn(parseFloat(controleVolume.value));
        });

        // --- ALTERNÂNCIA DE ABAS ---
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

        // --- MOSTRAR / OCULTAR SENHA ---
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

        // --- ALERTAS DE ERRO E SUCESSO ---
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
            } else if (codigoErro === '7') mensagem = "🚫 Conta bloqueada por 3 tentativas incorretas. Contate o administrador.";
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
