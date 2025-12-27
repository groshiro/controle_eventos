<?php
// Arquivo: conexao.php

// Parâmetros de conexão (use as suas credenciais)
define('HOST', 'dpg-d4rrpu24d50c73b2ia50-a.virginia-postgres.render.com');
define('PORT', '5432');
define('DBNAME', 'claro_7re0');
define('USER', 'usuario'); // Usuário correto
define('PASSWORD', 'InEB0P4jhfSXIbjEIOD7TJy8ZPcrBB5l'); // Senha correta

$pdo = null; // Inicializa a variável como null (necessário!)
$status_conexao = "Não conectado"; // Valor inicial

try {
    // DSN (Data Source Name) para PostgreSQL
    // Importante: O Render exige SSL, por isso adicionamos o sslmode=require
    $dsn = "pgsql:host=" . HOST . ";port=" . PORT . ";dbname=" . DBNAME . ";sslmode=require";
    
    // Criando a instância PDO
    $pdo = new PDO($dsn, USER, PASSWORD, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, // Lança exceções em caso de erro
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC, // Retorna os dados como array associativo
    ]);

    $status_conexao = "✅ Conectado ao banco do Render com sucesso!";

} catch (PDOException $e) {
    // Em caso de erro, limpamos a variável $pdo e exibimos a mensagem
    $pdo = null;
    $status_conexao = "❌ Erro ao conectar ao banco do Render: " . $e->getMessage();
}
// O arquivo deve terminar aqui. O dashboard.php usará $pdo e $status_conexao.
