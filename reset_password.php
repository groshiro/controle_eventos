<?php
// Arquivo: reset_password.php
require_once 'conexao.php';

$email = $_GET['email'] ?? '';
$token = $_GET['token'] ?? '';
$token_hash = hash('sha256', $token); // Recria o hash para a busca

if (empty($email) || empty($token)) {
    die("Erro de validação: Parâmetros inválidos.");
}

try {
    // 1. Busca o usuário pelo email E o token_hash
    $sql_check = "SELECT id FROM usuario 
                  WHERE email = :email 
                  AND reset_token = :token_hash
                  AND token_expires_at > NOW()"; // Verifica se não expirou
                  
    $stmt = $pdo->prepare($sql_check);
    $stmt->execute(['email' => $email, 'token_hash' => $token_hash]);
    $usuario = $stmt->fetch();

    if (!$usuario) {
        die("❌ Erro: Link de redefinição inválido ou expirado.");
    }
    
    $usuario_id = $usuario['id'];

} catch (PDOException $e) {
    die("Erro de banco de dados: " . $e->getMessage());
}

// Se o token for válido, exibe o formulário de nova senha
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <title>Nova Senha</title>
    
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #f4f7f6;
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            margin: 0;
        }

        .container {
            background-color: #ffffff;
            padding: 40px;
            border-radius: 10px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
            width: 100%;
            max-width: 400px;
            text-align: center;
        }

        h1 {
            color: #333;
            margin-bottom: 25px;
            font-size: 1.8em;
            border-bottom: 2px solid #007bff;
            padding-bottom: 10px;
        }

        form {
            display: flex;
            flex-direction: column;
            align-items: stretch;
        }

        label {
            text-align: left;
            margin-bottom: 8px;
            font-weight: bold;
            color: #555;
            font-size: 0.95em;
        }

        input[type="password"] {
            padding: 12px;
            margin-bottom: 20px;
            border: 1px solid #ccc;
            border-radius: 6px;
            box-sizing: border-box;
            font-size: 1em;
            transition: border-color 0.3s;
        }

        input[type="password"]:focus {
            border-color: #007bff;
            outline: none;
            box-shadow: 0 0 5px rgba(0, 123, 255, 0.3);
        }

        button[type="submit"] {
            background-color: #007bff;
            color: white;
            padding: 12px 20px;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            font-size: 1.1em;
            font-weight: bold;
            transition: background-color 0.3s, transform 0.1s;
        }

        button[type="submit"]:hover {
            background-color: #0056b3;
            transform: translateY(-1px);
        }
    </style>
    </head>
<body>
    <div class="container">
        <h1>Criar Nova Senha</h1>
        
        <form action="process_new_password.php" method="POST">
            
            <input type="hidden" name="id" value="<?php echo $usuario_id; ?>">
            <input type="hidden" name="token" value="<?php echo htmlspecialchars($token); ?>">
            
            <label for="nova_senha">Nova Senha:</label>
            <input type="password" id="nova_senha" name="nova_senha" required>
            
            <label for="confirma_senha">Confirmar Senha:</label>
            <input type="password" id="confirma_senha" name="confirma_senha" required>
            
            <button type="submit">Salvar Nova Senha</button>
        </form>
    </div>
</body>
</html>