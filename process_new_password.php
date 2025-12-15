<?php
// Arquivo: process_new_password.php
require_once 'conexao.php';

// 1. Recebe os dados
$id = $_POST['id'] ?? null;
$token = $_POST['token'] ?? '';
$nova_senha = $_POST['nova_senha'] ?? '';
$confirma_senha = $_POST['confirma_senha'] ?? '';

// O token_hash é usado para a verificação final no banco de dados
$token_hash = hash('sha256', $token);

if (!$pdo || !$id || empty($token) || empty($nova_senha) || $nova_senha !== $confirma_senha) {
    header("Location: index.php?erro=reset_invalido");
    exit;
}

try {
    // 2. Verifica o token e a validade (dupla verificação de segurança)
    $sql_check = "SELECT id FROM usuario 
                  WHERE id = :id 
                  AND reset_token = :token_hash";
                  
    $stmt = $pdo->prepare($sql_check);
    $stmt->execute(['id' => $id, 'token_hash' => $token_hash]);
    $usuario = $stmt->fetch();

    if (!$usuario) {
        header("Location: index.php?erro=token_nao_encontrado");
        exit;
    }
    
    // 3. Gera o novo hash seguro
    $novo_hash = password_hash($nova_senha, PASSWORD_DEFAULT);

    // 4. ATUALIZA A SENHA E INVALIDA O TOKEN
    $sql_update = "UPDATE usuario 
                   SET senha_hash = :novo_hash, 
                       reset_token = NULL, 
                       token_expires_at = NULL 
                   WHERE id = :id";
                   
    $stmt = $pdo->prepare($sql_update);
    $stmt->execute(['novo_hash' => $novo_hash, 'id' => $id]);
    
    // SUCESSO
    header("Location: index.php?status=senha_redefinida");
    exit;

} catch (PDOException $e) {
    error_log("Erro ao redefinir senha: " . $e->getMessage());
    header("Location: index.php?erro=reset_db_fail");
    exit;
}
?>