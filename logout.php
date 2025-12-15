<?php
// Arquivo: logout.php

// 1. INICIA A SESSÃO: É necessário iniciar a sessão para poder destruí-la.
session_start();

// 2. DESTROI AS VARIÁVEIS DE SESSÃO: 
// Remove todas as variáveis de sessão (e.g., $_SESSION['usuario_logado']).
$_SESSION = array();

// 3. ENCERRA A SESSÃO: Destrói a sessão no servidor.
// Esta linha limpa o arquivo de sessão no disco.
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}

// Destrói a sessão.
session_destroy();

// 4. REDIRECIONA PARA A TELA DE LOGIN:
// Envia o usuário de volta para a página inicial (index.php).
header("Location: index.php");
exit;
?>