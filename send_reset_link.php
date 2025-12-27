<?php
// Arquivo: send_reset_link.php
require_once 'conexao.php';

// 1. Logs de Erro
ini_set('display_errors', 1);
error_reporting(E_ALL);

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
use PHPMailer\PHPMailer\SMTP;

// 2. Carregamento Manual (Caminho absoluto do Render)
$base_path = __DIR__ . '/PHPMailer/src/';
require $base_path . 'Exception.php';
require $base_path . 'PHPMailer.php';
require $base_path . 'SMTP.php';

$email = filter_input(INPUT_POST, 'email', FILTER_SANITIZE_EMAIL);

if (empty($email)) {
    header("Location: forgot_password.php");
    exit;
}

try {
    // 3. Busca usuário
    $stmt = $pdo->prepare("SELECT id, nome FROM usuario WHERE email = :email");
    $stmt->execute(['email' => $email]);
    $usuario = $stmt->fetch();

    if ($usuario) {
        $token = bin2hex(random_bytes(32)); 
        $token_hash = hash('sha256', $token);
        $expires_at = date('Y-m-d H:i:s', strtotime('+1 hour'));

        $stmt = $pdo->prepare("UPDATE usuario SET reset_token = ?, token_expires_at = ? WHERE id = ?");
        $stmt->execute([$token_hash, $expires_at, $usuario['id']]);
        
        $protocol = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') ? "https://" : "http://";
        $link_reset = $protocol . $_SERVER['HTTP_HOST'] . "/reset_password.php?token=" . $token . "&email=" . urlencode($email);

        $mail = new PHPMailer(true);

        // 4. CONFIGURAÇÃO DE CONEXÃO (Ajustada para o Render)
        $mail->isSMTP();
        $mail->Host       = 'smtp.gmail.com';  
        $mail->SMTPAuth   = true;
        $mail->Username   = 'groshiro@gmail.com'; 
        $mail->Password   = getenv('SMTP_PASS') ?: '2735*lubichloe*'; 
        
        // MUDANÇA PARA PORTA 465 (Geralmente aberta no Render)
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS; 
        $mail->Port       = 587; 
        $mail->Timeout    = 20; // Aumenta o tempo de espera
        $mail->CharSet    = 'UTF-8';

        // 5. BYPASS DE CERTIFICADO SSL (Evita o erro de Time Out no Docker)
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
        $mail->Body    = "Olá {$usuario['nome']}, seu link: <a href='{$link_reset}'>{$link_reset}</a>";
            
        $mail->send();
    }

    header("Location: forgot_password.php?status=sucesso");
    exit;

} catch (Exception $e) {
    // Exibe o erro técnico para sabermos se o Time Out sumiu
    echo "<h1>Diagnóstico de Conexão:</h1>";
    echo "Mensagem: " . $e->getMessage() . "<br>";
    echo "Erro PHPMailer: " . $mail->ErrorInfo;
    exit; 
}


