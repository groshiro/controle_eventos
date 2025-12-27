<?php
// Arquivo: conexao.php

/**
 * 1. TENTA OBTER DADOS DAS VARIÁVEIS DE AMBIENTE (RECOMENDADO PARA DOCKER/RENDER)
 * Se você configurar DB_HOST, DB_NAME, etc., no painel do Render, o PHP pegará daqui.
 */
$host = getenv('DB_HOST') ?: 'dpg-d4rrpu24d50c73b2ia50-a.virginia-postgres.render.com';
$port = getenv('DB_PORT') ?: '5432';
$dbname = getenv('DB_NAME') ?: 'claro_7re0';
$user = getenv('DB_USER') ?: 'usuario';
$password = getenv('DB_PASSWORD') ?: 'InEB0P4jhfSXIbjEIOD7TJy8ZPcrBB5l';

$pdo = null; 
$status_conexao = "Não conectado"; 

try {
    /**
     * 2. MONTAGEM DO DSN
     * O 'sslmode=require' é obrigatório para o PostgreSQL no Render.
     */
    $dsn = "pgsql:host=$host;port=$port;dbname=$dbname;sslmode=require";
    
    // 3. CRIAÇÃO DA INSTÂNCIA PDO
    $pdo = new PDO($dsn, $user, $password, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        // Garante que a conexão use UTF-8
        PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8" 
    ]);

    $status_conexao = "✅ Conectado ao banco do Render com sucesso!";

} catch (PDOException $e) {
    $pdo = null;
    // Exibe uma mensagem amigável, mas registra o erro real no log do Docker
    $status_conexao = "❌ Erro ao conectar ao banco de dados.";
    error_log("Erro PDO: " . $e->getMessage()); 
}
