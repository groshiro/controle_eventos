<?php
// Arquivo: conexao.php

/**
 * 1. OBTENÇÃO DOS DADOS
 * Prioriza variáveis de ambiente (Render/Docker) e usa os seus dados como fallback.
 */
$host = getenv('DB_HOST') ?: 'dpg-d4rrpu24d50c73b2ia50-a';
$port = getenv('DB_PORT') ?: '5432';
$dbname = getenv('DB_NAME') ?: 'claro_7re0';
$user = getenv('DB_USER') ?: 'usuario';
$password = getenv('DB_PASSWORD') ?: 'InEB0P4jhfSXIbjEIOD7TJy8ZPcrBB5l';

$pdo = null; 
$status_conexao = "Não conectado"; 

try {
    /**
     * 2. MONTAGEM DO DSN
     * Removido o comando de MySQL que causava o Fatal Error.
     */
    $dsn = "pgsql:host=$host;port=$port;dbname=$dbname;sslmode=require";
    
    // 3. CRIAÇÃO DA INSTÂNCIA PDO
    $pdo = new PDO($dsn, $user, $password, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
    ]);

    $status_conexao = "✅ Conectado ao banco do Render com sucesso!";

} catch (PDOException $e) {
    $pdo = null;
    // Registra o erro detalhado nos logs do Render/Docker para você depurar
    error_log("Erro na conexão PostgreSQL: " . $e->getMessage());
    
    // Mensagem simplificada para o usuário final
    $status_conexao = "❌ Erro técnico ao conectar ao banco de dados.";
}

