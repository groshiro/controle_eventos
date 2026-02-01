<?php
// Arquivo: send_reset_link.php
require_once 'conexao.php';

// =================================================================
// AUTOLOADING MANUAL DO PHPMailer
// =================================================================
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
use PHPMailer\PHPMailer\SMTP;

spl_autoload_register(function ($class) {
    $prefix = 'PHPMailer\\PHPMailer\\';
    $base_dir = __DIR__ . '/vendor/phpmailer/phpmailer/src/'; 
    
    $len = strlen($prefix);
    if (strncmp($prefix, $class, $len) !== 0) return;
    
    $relative_class = substr($class, $len);
    $file = $base_dir . str_replace('\\', '/', $relative_class) . '.php';
    
    if (file_exists($file)) require $file;
});

// 1. Verificação de Segurança
if (!$pdo) {
    header("Location: forgot_password.php?status=erro_bd");
    exit;
}

$email = filter_input(INPUT_POST, 'email', FILTER_SANITIZE_EMAIL);

if (empty($email)) {
    header("Location: forgot_password.php");
    exit;
}

try {
    // 2. Busca Usuário
    $sql_usuario = "SELECT id, nome FROM usuario WHERE email = :email";
    $stmt = $pdo->prepare($sql_usuario);
    $stmt->execute(['email' => $email]);
    $usuario = $stmt->fetch();

    if ($usuario) {
        // 3. Token e Expiração
        $token = bin2hex(random_bytes(32)); 
        $token_hash = hash('sha256', $token);
        $expires_at = date('Y-m-d H:i:s', strtotime('+1 hour'));

        // 4. Update no Banco
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
        
        // 5. Construção da URL (HTTPS para Render)
        $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? "https://" : "http://";
        $url_base = $protocol . $_SERVER['HTTP_HOST'] . "/"; 
        $link_reset = $url_base . "reset_password.php?token=" . $token . "&email=" . urlencode($email);

        // 6. Configuração PHPMailer
        $mail = new PHPMailer(true);

        // --- CONFIGURAÇÃO SMTP ---
        $mail->isSMTP();
        $mail->Host       = 'smtps.uol.com.br'; 
        $mail->SMTPAuth   = true;
        $mail->Username   = 'gr.oshiro@uol.com.br'; 
        $mail->Password   = '2735lubi'; 
        
        // Ajustado para SSL na porta 465 (Melhor para ambiente de nuvem)
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS; 
        $mail->Port       = 465; 
        $mail->CharSet    = 'UTF-8';

        // Opções de SSL para evitar falhas de handshake no Render
        $mail->SMTPOptions = array(
            'ssl' => array(
                'verify_peer' => false,
                'verify_peer_name' => false,
                'allow_self_signed' => true
            )
        );

        // --- ENVELOPE ---
        $mail->setFrom('gr.oshiro@uol.com.br', 'Sistema de Controle');
        $mail->addAddress($email, $usuario['nome']); 
        
        // --- CONTEÚDO ---
        $mail->isHTML(true);
        $mail->Subject = 'Redefinição de Senha - Sistema de Controle';
        
        $mail->Body    = "
            <html>
            <body style='font-family: Arial, sans-serif; line-height: 1.6;'>
                <h2>Olá, {$usuario['nome']}!</h2>
                <p>Você solicitou a redefinição de senha para a sua conta. O link expirará em 1 hora.</p>
                <p>Clique no botão abaixo para criar uma nova senha:</p>
                <div style='text-align: center; margin: 30px 0;'>
                    <a href='{$link_reset}' style='background-color:#007bff; color:white; padding:12px 25px; text-decoration:none; border-radius:5px; font-weight:bold; display:inline-block;'>
                        Criar Nova Senha
                    </a>
                </div>
                <p style='font-size: 0.8em; color: #666;'>Se o botão não funcionar, copie e cole o link no navegador:<br>{$link_reset}</p>
                <hr style='border: 0; border-top: 1px solid #eee;'>
                <p style='font-size: 0.8em; color: #999;'>Se você não solicitou essa redefinição, ignore este e-mail.</p>
            </body>
            </html>
        ";
        $mail->AltBody = "Olá, {$usuario['nome']}! Use o seguinte link para redefinir sua senha: {$link_reset}";

        $mail->send();
    }

} catch (PDOException $e) {
    error_log("Erro no BD: " . $e->getMessage());
} catch (Exception $e) {
    error_log("Erro PHPMailer: {$mail->ErrorInfo}");
}

// 7. Redirecionamento Final
header("Location: forgot_password.php?status=sucesso");
exit;


