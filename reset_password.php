<?php
// Arquivo: reset_password.php
require_once 'conexao.php';

$email = filter_input(INPUT_GET, 'email', FILTER_SANITIZE_EMAIL);
$token = $_GET['token'] ?? '';

// Recria o hash para comparar com o que está salvo no banco
$token_hash = hash('sha256', $token); 

if (empty($email) || empty($token)) {
    die("❌ Erro de validação: Link incompleto ou inválido.");
}

try {
    /**
     * 1. BUSCA O USUÁRIO E VALIDA O TOKEN
     * No PostgreSQL, usamos CURRENT_TIMESTAMP em vez de NOW() para maior precisão
     * e compatibilidade com o fuso horário do servidor do Render.
     */
    $sql_check = "SELECT id FROM usuario 
                  WHERE email = :email 
                  AND reset_token = :token_hash
                  AND token_expires_at > CURRENT_TIMESTAMP"; 
                  
    $stmt = $pdo->prepare($sql_check);
    $stmt->execute([
        'email'      => $email, 
        'token_hash' => $token_hash
    ]);
    
    $usuario = $stmt->fetch();

    if (!$usuario) {
        // Se cair aqui, o link expirou (passou de 1h) ou o token já foi usado
        die("❌ Erro: Este link de redefinição é inválido ou já expirou.");
    }
    
    $usuario_id = $usuario['id'];

} catch (PDOException $e) {
    error_log("Erro de banco de dados: " . $e->getMessage());
    die("❌ Erro interno no servidor. Tente novamente mais tarde.");
}

// Se o token for válido, o formulário HTML abaixo será exibido
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <title>Nova Senha | Sistema de Controle</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <style>
        body {
            font-family: 'Segoe UI', Arial, sans-serif;
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
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
            width: 90%;
            max-width: 400px;
            text-align: center;
        }

        h1 {
            color: #333;
            font-size: 1.5rem;
            margin-bottom: 25px;
        }

        form {
            display: flex;
            flex-direction: column;
            text-align: left;
        }

        label {
            margin-bottom: 8px;
            font-weight: 600;
            color: #555;
        }

        input[type="password"] {
            padding: 12px;
            margin-bottom: 20px;
            border: 1px solid #ddd;
            border-radius: 8px;
            font-size: 1rem;
            outline: none;
            transition: 0.3s;
        }

        input[type="password"]:focus {
            border-color: #f70b0b; /* Vermelho institucional */
            box-shadow: 0 0 0 3px rgba(247, 11, 11, 0.1);
        }

        button {
            background-color: #f70b0b;
            color: white;
            padding: 14px;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-size: 1rem;
            font-weight: bold;
            transition: 0.3s;
        }

        button:hover {
            background-color: #d60a0a;
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
            <input type="password" id="nova_senha" name="nova_senha" required minlength="6">
            
            <label for="confirma_senha">Confirmar Senha:</label>
            <input type="password" id="confirma_senha" name="confirma_senha" required minlength="6">
            
            <button type="submit">Salvar Nova Senha</button>
        </form>
    </div>
</body>
</html>
</html>
