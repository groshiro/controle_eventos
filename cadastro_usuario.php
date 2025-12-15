<?php
// Arquivo: cadastro_usuario.php
require_once 'conexao.php'; 

$mensagem = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // 1. Receber e sanitizar dados
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
            // 2. Criptografar a Senha
            $senha_hash = password_hash($senha, PASSWORD_DEFAULT); 
            
            // 3. INSERÇÃO SEGURA na tabela USUARIO
            $sql_insert = "INSERT INTO usuario (nome, login, email, senha_hash, nivel_permissao) 
                           VALUES (:nome_ph, :login_ph, :email_ph, :senha_ph, 'VIEW')"; // Define VIEW como padrão
            
            $stmt = $pdo->prepare($sql_insert);

            // 4. Executa a instrução
            $stmt->execute([
                'nome_ph' => $nome,
                'login_ph' => $login,
                'email_ph' => $email,
                'senha_ph' => $senha_hash
            ]);
            
            $mensagem = "✅ Usuário cadastrado com sucesso! Faça login.";

        } catch (PDOException $e) {
            // Verifica se o erro é de violação de unicidade (login/email já existem)
            if (strpos($e->getMessage(), 'unique constraint') !== false) {
                 $mensagem = "❌ Erro: Login ou Email já cadastrado.";
            } else {
                 $mensagem = "❌ Erro grave ao cadastrar usuário: " . $e->getMessage();
            }
        }
    }
}
?>
<!DOCTYPE html>
<html LANG="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cadastro de Usuário</title>
    <style>
        /* ------------------------------------------- */
        /* ESTILOS GERAIS E ESTRUTURA (CENTRALIZAÇÃO) */
        /* ------------------------------------------- */
        body {
            font-family: Arial, sans-serif;
            background-color: #f4f4f4;
            margin: 0;
            display: flex; 
            justify-content: center; /* Centraliza horizontalmente */
            align-items: center; /* Centraliza verticalmente */
            min-height: 100vh; /* Garante que a altura da viewport seja coberta */
        }
        
        /* Contêiner Principal (Card) */
        .card-cadastro {
            background: white;
            border-radius: 8px;
            box-shadow: 0 0 15px rgba(0,0,0,0.1);
            max-width: 400px;
            width: 90%; /* Responsivo */
            padding: 30px;
            border: 1px solid #ddd;
        }

        /* Título e Separador */
        h1 {
            text-align: center;
            font-size: 1.8em;
            color: #333;
            margin-bottom: 20px;
            position: relative;
        }
        h1::after {
            content: '';
            display: block;
            width: 60%; /* Linha decorativa */
            height: 3px;
            background-color: #007bff;
            margin: 8px auto 0;
            border-radius: 2px;
        }

        /* ------------------------------------------- */
        /* ESTILOS DE FORMULÁRIO */
        /* ------------------------------------------- */
        label {
            display: block;
            margin-top: 10px;
            margin-bottom: 5px;
            font-weight: bold;
            color: #555;
        }
        
        input[type="text"], 
        input[type="email"], 
        input[type="password"] {
            width: 100%;
            padding: 10px;
            margin-bottom: 15px;
            border: 1px solid #ccc;
            border-radius: 4px;
            box-sizing: border-box; 
            font-size: 1em;
        }
        
        /* Botão de Registrar */
        button[type="submit"] {
            width: 100%; 
            background-color: #28a745; /* Verde de sucesso */
            color: white;
            padding: 12px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 1.1em;
            font-weight: bold;
            transition: background-color 0.2s;
        }
        button[type="submit"]:hover {
            background-color: #218838;
        }

        /* Link de Voltar */
        .card-cadastro p a {
            color: #007bff;
            text-decoration: none;
            font-weight: bold;
        }
        .card-cadastro p a:hover {
            text-decoration: underline;
        }
    </style>
</head>
<body>
    <div class="card-cadastro">
        <h1>Cadastrar Novo Usuário</h1>
        
        <?php if (!empty($mensagem)): ?>
            <p style="color: green; font-weight: bold; text-align: center;"><?php echo $mensagem; ?></p>
        <?php endif; ?>

        <form action="cadastro_usuario.php" method="POST">
            <label for="nome">Nome Completo:</label>
            <input type="text" name="nome" required><br>
            
            <label for="login">Login:</label>
            <input type="text" name="login" required><br>

            <label for="email">Email:</label>
            <input type="email" name="email" required><br>

            <label for="senha">Senha:</label>
            <input type="password" name="senha" required><br>
                        
            <button type="submit">Registrar Usuário</button>
        </form>
        <p style="text-align: center; margin-top: 20px;"><a href="index.php">Voltar ao Login</a></p>
    </div>
</body>
</html>