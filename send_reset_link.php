<?php
// Arquivo: send_reset_link.php
require_once 'conexao.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
use PHPMailer\PHPMailer\SMTP;

// Ajuste de caminho para o PHPMailer no Render
$base_path = __DIR__ . '/PHPMailer/src/';
require $base_path . 'Exception.php';
require $base_path . 'PHPMailer.php';
require $base_path . 'SMTP.php';

$email_destino = filter_input(INPUT_POST, 'email', FILTER_SANITIZE_EMAIL);

if (empty($email_destino)) {
    header("Location: forgot_password.php");
    exit;
}

try {
    $stmt = $pdo->prepare("SELECT id, nome FROM usuario WHERE email = :email");
    $stmt->execute(['email' => $email_destino]);
    $usuario = $stmt->fetch();

    if ($usuario) {
        $token = bin2hex(random_bytes(32)); 
        $token_hash = hash('sha256', $token);
        $expires_at = date('Y-m-d H:i:s', strtotime('+1 hour'));

        $stmt = $pdo->prepare("UPDATE usuario SET reset_token = ?, token_expires_at = ? WHERE id = ?");
        $stmt->execute([$token_hash, $expires_at, $usuario['id']]);
        
        $protocol = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') ? "https://" : "http://";
        $link_reset = $protocol . $_SERVER['HTTP_HOST'] . "/reset_password.php?token=" . $token . "&email=" . urlencode($email_destino);

        $mail = new PHPMailer(true);

        // --- CONFIGURAÇÃO GMAIL ---
        $mail->isSMTP();
        $mail->Host       = 'smtp.gmail.com';
        $mail->SMTPAuth   = true;
        $mail->Username   = 'groshiro@gmail.com'; // Seu Gmail (sem o .br)
        $mail->Password   = getenv('SMTP_PASS') ?: '2735*lubichloe*'; 
        
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS; // SSL direto
        $mail->Port       = 465;                         // Porta SSL
        $mail->CharSet    = 'UTF-8';

        // Bypass de Certificado (Evita erro de Time Out no Render)
        $mail->SMTPOptions = array(
            'ssl' => array(
                'verify_peer' => false,
                'verify_peer_name' => false,
                'allow_self_signed' => true
            )
        );

        $mail->setFrom('groshiro@gmail.com', 'Sistema de Controle');
        $mail->addAddress($email_destino, $usuario['nome']); 
        
        $mail->isHTML(true);
        $mail->Subject = 'Redefinição de Senha';
        $mail->Body    = "Olá {$usuario['nome']},<br>Clique no link para resetar sua senha: <a href='{$link_reset}'>{$link_reset}</a>";
            
        $mail->send();
    }

    header("Location: forgot_password.php?status=sucesso");
    exit;

} catch (Exception $e) {
    // Se der erro, mostra na tela para diagnosticar
    echo "Erro técnico: " . $mail->ErrorInfo;
}
