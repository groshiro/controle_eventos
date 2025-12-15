<?php
// Arquivo: conexao.php

// Parâmetros de conexão (use as suas credenciais)
define('HOST', 'localhost');
define('PORT', '5432');
define('DBNAME', 'claro');
define('USER', 'usuario'); // Usuário correto
define('PASSWORD', '4591'); // Senha correta

$pdo = null; // Inicializa a variável como null (necessário!)
$status_conexao = "Não conectado"; // Valor inicial

try {
    // Tenta conexão com SSL desabilitado
    $dsn_string = "pgsql:host=" . HOST . ";port=" . PORT . ";dbname=" . DBNAME . ";sslmode=disable";
    
    // Define a variável $pdo
    $pdo = new PDO($dsn_string, USER, PASSWORD); 
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $status_conexao = "✅ Conexão estabelecida!"; // Mensagem de sucesso

} catch (PDOException $e) {
    // ESSA LINHA É CRUCIAL: Captura a mensagem de erro real do PostgreSQL
    $status_conexao = "Falha: " . $e->getMessage();
    $pdo = null; // Garante que $pdo seja null em caso de falha
}

// O arquivo deve terminar aqui. O dashboard.php usará $pdo e $status_conexao.