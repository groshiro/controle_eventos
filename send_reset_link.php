<?php
// Arquivo: send_reset_link.php
require_once 'conexao.php';

// 1. Configurações de Erro para o Render
ini_set('display_errors', 1);
ini_set('log_errors', 1);
error_reporting(E_ALL);

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
use PHPMailer\PHPMailer\SMTP;

// 2. Ajuste de Caminho PHPMailer
$base_path = __DIR__ . '/PHPMailer/src/';
require $base_path . 'Exception.php';
require $base_path . 'PHPMailer.php';
require $base_path . 'SMTP.php';

if (!$pdo) {
    die("❌ Erro: Falha na conexão com o banco de dados.");
}

$email = filter_input(INPUT_POST, 'email', FILTER_SANITIZE_EMAIL);

if (empty($email)) {
    header("Location: forgot_password.php");
    exit;
}

try {
    // 3. Busca o usuário
    $sql_usuario = "SELECT id, nome FROM usuario WHERE email = :email";
    $stmt = $pdo->prepare($sql_usuario);
    $stmt->execute(['email' => $email]);
    $usuario = $stmt->fetch();

    if ($usuario) {
        $token = bin2hex(random_bytes(32)); 
        $token_hash = hash('sha256', $token);
        $expires_at = date('Y-m-d H:i:s', strtotime('+1 hour'));

        // 4. Salva o Token
        $sql_update = "UPDATE usuario 
                       SET reset_token = :token_hash, 
                           token_expires_at = :expires_at 
                       WHERE id = :id";
        $stmt = $pdo->prepare($sql_update);
        $stmt->execute([
            'token_hash' => $token_hash,
            'expires_at' => $expires_at,
            'id' => $usuario['id']
        ]);
        
        // 5. URL
        $protocol = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') ? "https://" : "http://";
        $url_base = $protocol . $_SERVER['HTTP_HOST'] . "/"; 
        $link_reset = $url_base . "reset_password.php?token=" . $token . "&email=" . urlencode($email);

        $mail = new PHPMailer(true);

        // 6. CONFIGURAÇÃO SMTP CORRIGIDA PARA O RENDER
        $mail->isSMTP();
        $mail->Host       = 'smtps.uol.com.br'; // Tente 'smtp.uol.com.br' se este falhar
        $mail->SMTPAuth   = true;
        $mail->Username   = 'gr.oshiro@uol.com.br'; 
        $mail->Password   = getenv('SMTP_PASS') ?: '2735lubi'; 
        
        // MUDANÇA CRUCIAL: PORTA 465 COM ENCRYPTION_SMTPS (SSL)
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS; 
        $mail->Port       = 465; 
        $mail->CharSet    = 'UTF-8';

        // 7. BYPASS DE CERTIFICADO (Evita o Time Out no Render)
        $mail->SMTPOptions = array(
            'ssl' => array(
                'verify_peer' => false,
                'verify_peer_name' => false,
                'allow_self_signed' => true
            )
        );

        $mail->setFrom('gr.oshiro@uol.com.br', 'Sistema de Controle');
        $mail->addAddress($email, $usuario['nome']); 
        
        $mail->isHTML(true);
        $mail->Subject = 'Redefinição de Senha';
        $mail->Body    = "Olá {$usuario['nome']}, clique no link para resetar sua senha: <br><a href='{$link_reset}'>{$link_reset}</a>";
            
        $mail->send();
    }

    // Se chegar aqui, deu certo!
    header("Location: forgot_password.php?status=sucesso");
    exit;

} catch (Exception $e) {
    // 8. EXIBIÇÃO DO ERRO TÉCNICO (Trecho de Diagnóstico)
    echo "<h1>Erro Técnico Detalhado:</h1>";
    echo "Mensagem: " . $e->getMessage() . "<br>";
    echo "Erro do PHPMailer: " . $mail->ErrorInfo;
    exit; 
}








