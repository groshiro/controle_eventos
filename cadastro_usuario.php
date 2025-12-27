<?php
// Arquivo: cadastro_usuario.php
require_once 'conexao.php'; 

$mensagem = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nome  = $_POST['nome'] ?? '';
    $login = $_POST['login'] ?? '';
    $email = $_POST['email'] ?? '';
    $senha = $_POST['senha'] ?? '';
        
    if (!$pdo) {
        $mensagem = "❌ Erro: Falha na conexão com o BD.";
    } elseif (empty($nome) || empty($login) || empty($email) || empty($senha)) {
        $mensagem = "⚠️ Preencha todos os campos.";
    } else {
        try {
            $senha_hash = password_hash($senha, PASSWORD_DEFAULT); 
            
            $sql_insert = "INSERT INTO usuario (nome, login, email, senha_hash, nivel_permissao) 
                           VALUES (:nome_ph, :login_ph, :email_ph, :senha_ph, 'VIEW')";
            
            $stmt = $pdo->prepare($sql_insert);
            $stmt->execute([
                'nome_ph' => $nome,
                'login_ph' => $login,
                'email_ph' => $email,
                'senha_ph' => $senha_hash
            ]);
            
            $mensagem = "✅ Usuário cadastrado com sucesso! Faça login.";

        } catch (PDOException $e) {
            if (strpos($e->getMessage(), 'unique constraint') !== false || $e->getCode() == 23505) {
                 $mensagem = "❌ Erro: Login ou Email já cadastrado.";
            } else {
                 $mensagem = "❌ Erro ao cadastrar: " . $e->getMessage();
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cadastro | Histórico de Eventos</title>
    <style>
        /* 1. RESET E FUNDO ANIMADO (Igual ao Index) */
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

        /* 2. CARD DE CADASTRO COM EFEITO DE VIDRO */
        .card-cadastro {
            position: relative;
            z-index: 1;
            background: rgba(255, 255, 255, 0.65);
            backdrop-filter: blur(15px);
            -webkit-backdrop-filter: blur(15px);
            border-radius: 24px;
            border: 1px solid rgba(255, 255, 255, 0.8);
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.1);
            max-width: 450px;
            width: 90%;
            padding: 35px;
            animation: fadeIn 0.8s ease-out;
        }

        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }

        h1 {
            text-align: center;
            font-size: 1.8rem;
            color: #333;
            margin-bottom: 25px;
            font-weight: 700;
        }

        h1::after {
            content: '';
            display: block;
            width: 50px;
            height: 4px;
            background: #28a745; /* Verde para cadastro */
            margin: 10px auto;
            border-radius: 10px;
        }

        /* 3. ESTILOS DE FORMULÁRIO */
        label {
            display: block;
            margin-bottom: 5px;
            font-weight: 600;
            color: #555;
            font-size: 0.9rem;
        }
        
        input {
            width: 100%;
            padding: 12px;
            margin-bottom: 18px;
            border: 1px solid rgba(0,0,0,0.1);
            border-radius: 12px;
            background: rgba(255, 255, 255, 0.9);
            font-size: 1rem;
            transition: 0.3s;
            outline: none;
        }
        
        input:focus {
            border-color: #28a745;
            box-shadow: 0 0 0 4px rgba(40, 167, 69, 0.1);
        }
        
        button[type="submit"] {
            width: 100%; 
            background-color: #28a745;
            color: white;
            padding: 15px;
            border: none;
            border-radius: 12px;
            cursor: pointer;
            font-size: 1.1rem;
            font-weight: bold;
            transition: 0.3s;
            box-shadow: 0 4px 12px rgba(40, 167, 69, 0.2);
        }

        button[type="submit"]:hover {
            background-color: #218838;
            transform: translateY(-2px);
        }

        .mensagem {
            text-align: center;
            margin-bottom: 20px;
            font-size: 0.95rem;
        }

        .voltar-link {
            text-align: center;
            margin-top: 20px;
        }

        .voltar-link a {
            color: #007bff;
            text-decoration: none;
            font-weight: 700;
            font-size: 0.9rem;
        }

        .voltar-link a:hover {
            text-decoration: underline;
        }
    </style>
</head>
<body>
    <div class="bg-image"></div>
    <div class="bg-animation"></div>

    <div class="card-cadastro">
        <h1>Criar Conta</h1>
        
        <?php if (!empty($mensagem)): ?>
            <div class="mensagem" style="color: <?php echo strpos($mensagem, '✅') !== false ? '#155724' : '#721c24'; ?>;">
                <?php echo $mensagem; ?>
            </div>
        <?php endif; ?>

        <form action="cadastro_usuario.php" method="POST">
            <label for="nome">Nome Completo</label>
            <input type="text" name="nome" placeholder="Ex: João Silva" required>
            
            <label for="login">Nome de Usuário (Login)</label>
            <input type="text" name="login" placeholder="Ex: joao.silva" required>

            <label for="email">E-mail Corporativo</label>
            <input type="email" name="email" placeholder="email@claro.com.br" required>

            <label for="senha">Senha de Acesso</label>
            <input type="password" name="senha" placeholder="••••••••" required>
                        
            <button type="submit">Finalizar Cadastro</button>
        </form>

        <div class="voltar-link">
            <a href="index.php">← Já tenho conta. Voltar ao Login</a>
        </div>
    </div>
</body>
</html>