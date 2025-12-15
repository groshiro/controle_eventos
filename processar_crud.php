<?php
// Arquivo: processar_crud.php
session_start();
require_once 'conexao.php';

if (!$pdo) {
    die("Falha na conexão.");
}

// ----------------------------------------------------
// 🛑 PASSO 1: VERIFICAÇÃO DE AUTENTICAÇÃO
// ----------------------------------------------------
if (!isset($_SESSION['usuario_id']) || empty($_SESSION['nivel_permissao'])) {
    header("Location: index.php?erro=sessao_expirada"); 
    die("Acesso negado. Por favor, faça login."); // Adiciona die() por segurança
}

$acao = $_GET['acao'] ?? $_POST['acao'] ?? ''; 
$id = $_GET['id'] ?? $_POST['id'] ?? null; 

$nivel_permissao = trim($_SESSION['nivel_permissao']); 
$PERMISSAO_ADMIN = 'ADMIN'; 
$PERMISSAO_EDICAO = 'EDITOR'; 

// ----------------------------------------------------
// 🛑 PASSO 2: VERIFICAÇÃO DE AUTORIZAÇÃO (O USUÁRIO TEM PERMISSÃO?)
// ----------------------------------------------------

$acoes_admin = ['excluir', 'alterar_usuario']; // Exige ADMIN
$acoes_edicao_restrita = ['alterar'];          // Exige EDITOR ou ADMIN

if (in_array($acao, $acoes_admin)) {
    // BLOCAGEM PARA EXCLUIR E ALTERAR USUÁRIO (Requer ADMIN)
    if ($nivel_permissao != $PERMISSAO_ADMIN) {
        $msg_erro = ($acao == 'excluir') 
            ? "Acesso Negado: A exclusão de registros requer a permissão 'ADMIN'. Permissão atual: {$nivel_permissao}."
            : "Acesso Negado: A alteração de permissões de usuário requer a permissão 'ADMIN'.";

        $_SESSION['alerta_erro'] = $msg_erro; // Salva o erro na sessão
        header("Location: dashboard.php");
        exit;
    }
} elseif (in_array($acao, $acoes_edicao_restrita)) {
    // BLOCAGEM PARA ALTERAR INCIDENTE (Requer EDITOR ou ADMIN, nega VIEW)
    if ($nivel_permissao == 'VIEW') {
        $msg_erro = "Acesso Negado: Usuários 'VIEW' não têm permissão para editar ou alterar dados.";
        
        $_SESSION['alerta_erro'] = $msg_erro; // Salva o erro na sessão
        header("Location: dashboard.php");
        exit;
    }
}

// ----------------------------------------------------
// FIM DA VERIFICAÇÃO DE SEGURANÇA.
// ----------------------------------------------------

if (!$id || empty($acao)) {
    header("Location: dashboard.php");
    exit;
}

try {
    if ($acao == 'excluir') {
        // ... (Bloco de EXCLUSÃO) ...
        $sql_delete = "DELETE FROM usuario WHERE id = :id"; 
        $stmt = $pdo->prepare($sql_delete);
        $stmt->execute(['id' => $id]);
        // Se a exclusão fosse de incidente, use 'dashboard.php?status=excluido'
        header("Location: dashboard.php?status=excluido"); 
        exit;

    } elseif ($acao == 'alterar_usuario' && $_SERVER['REQUEST_METHOD'] === 'POST') { 
        // ... (Bloco de ALTERAR USUÁRIO) ...
        $nome = $_POST['nome'] ?? ''; 
        $login = $_POST['login'] ?? '';
        $email = $_POST['email'] ?? '';
        $nivel_permissao = $_POST['nivel_permissao'] ?? ''; 

        $sql_update = "UPDATE usuario SET nome = :nome, login = :login, email = :email, nivel_permissao = :nivel_permissao WHERE id = :id";
        $stmt = $pdo->prepare($sql_update);
        $stmt->execute(['nome' => $nome, 'login' => $login, 'email' => $email, 'nivel_permissao' => $nivel_permissao, 'id' => $id]);

        header("Location: dashboard.php?status=usuario_alterado");
        exit;
        
    } elseif ($acao == 'alterar' && $_SERVER['REQUEST_METHOD'] === 'POST') {
        // ... (Bloco de ALTERAR INCIDENTE) ...
        $incidente = $_POST['incidente'] ?? '';
        // ... (restante dos campos)
        
        $sql_update = "UPDATE controle SET incidente = :incidente /* ... */ WHERE id = :id";
        // ... (preparação e execução) ...

        header("Location: dashboard.php?status=alterado");
        exit;
    }
    
} catch (PDOException $e) {
    // Captura erros de banco de dados
    header("Location: dashboard.php?status=erro&msg=" . urlencode($e->getMessage()));
    exit;
}

// Redirecionamento padrão para qualquer ação não reconhecida
header("Location: dashboard.php");
exit;

?>