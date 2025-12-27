<?php
// Ativa o buffer de saída para evitar erros de "Headers already sent"
ob_start();
session_start();

// 1. Inclui a conexão (que já possui as credenciais do Render e sslmode=require)
require_once 'conexao.php'; 

// 2. Recebe e limpa os dados do formulário
$login_usuario = trim($_POST['login'] ?? ''); 
$senha_usuario = $_POST['pswd'] ?? ''; 

// 3. Verifica se a conexão PDO existe (definida no conexao.php)
if (!isset($pdo) || $pdo === null) {
    ob_end_clean();
    header("Location: index.php?erro=3"); 
    exit;
}

// 4. Verifica se os campos foram preenchidos
if (empty($login_usuario) || empty($senha_usuario)) {
    ob_end_clean();
    header("Location: index.php?erro=2");
    exit;
}

try {
    // 5. Consulta Segura
    // ✅ No Render/PostgreSQL, nomes de tabelas e colunas são case-sensitive se criados com aspas. 
    // Certifique-se que a tabela é 'usuario' em minúsculo no pgAdmin.
    $instrucaoSQL = 'SELECT id, senha_hash, nome, nivel_permissao FROM usuario WHERE login = :login'; 
    $stmt = $pdo->prepare($instrucaoSQL);
    $stmt->execute(['login' => $login_usuario]);
    $controle = $stmt->fetch(PDO::FETCH_ASSOC);

    // 6. Verificação de Senha
    if ($controle && password_verify($senha_usuario, $controle['senha_hash'])) {
        // SUCESSO: Gera um novo ID de sessão para evitar fixação de sessão
        session_regenerate_id(true); 
        
        $_SESSION['usuario_id'] = $controle['id']; 
        $_SESSION['usuario_logado'] = $login_usuario;
        $_SESSION['nome_completo'] = $controle['nome']; 
        $_SESSION['nivel_permissao'] = $controle['nivel_permissao'];
        
        ob_end_clean();
        header("Location: dashboard.php");
        exit;
    } else {
        // FALHA: Usuário ou senha incorretos
        ob_end_clean();
        header("Location: index.php?erro=1");
        exit;
    }
} catch (PDOException $e) {
    // Caso ocorra um erro de SQL (ex: tabela não encontrada após o Restore)
    error_log("Erro no Render: " . $e->getMessage());
    ob_end_clean();
    header("Location: index.php?erro=3");
    exit;
}
?>
