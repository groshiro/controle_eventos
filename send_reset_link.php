<?php
// Arquivo: send_reset_link.php
require_once 'conexao.php';

// =================================================================
// AUTOLOADING MANUAL DO PHPMailer (Substituindo o Composer)
// =================================================================
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
use PHPMailer\PHPMailer\SMTP;

spl_autoload_register(function ($class) {
    // Define o namespace e o caminho base para a pasta SRC
    $prefix = 'PHPMailer\\PHPMailer\\';
    // 🔑 AJUSTE O CAMINHO BASE ABAIXO conforme a sua estrutura de pastas!
    $base_dir = __DIR__ . '/vendor/phpmailer/phpmailer/src/'; 
    
    $len = strlen($prefix);
    if (strncmp($prefix, $class, $len) !== 0) {
        return;
    }
    
    $relative_class = substr($class, $len);
    $file = $base_dir . str_replace('\\', '/', $relative_class) . '.php';
    
    if (file_exists($file)) {
        require $file;
    }
});

// 1. Verifica se a conexão PDO está ativa
if (!$pdo) {
    header("Location: forgot_password.php?status=erro_bd");
    exit;
}

$email = $_POST['email'] ?? '';

if (empty($email)) {
    header("Location: forgot_password.php");
    exit;
}

try {
    // 2. Busca o ID e nome do usuário pelo e-mail
    $sql_usuario = "SELECT id, nome FROM usuario WHERE email = :email";
    $stmt = $pdo->prepare($sql_usuario);
    $stmt->execute(['email' => $email]);
    $usuario = $stmt->fetch();

    if ($usuario) {
        // 3. Geração do Token Seguro
        $token = bin2hex(random_bytes(32)); 
        $token_hash = hash('sha256', $token);
        
        // 4. Definir Expiração (1 hora)
        $expires_at = date('Y-m-d H:i:s', strtotime('+1 hour'));

        // 5. Salvar o Token e Expiração no BD
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
        
        // 6. CONSTRUÇÃO E ENVIO REAL DO E-MAIL
        $url_base = "http://" . $_SERVER['HTTP_HOST'] . "/controle/"; // Caminho Base Corrigido
        $link_reset = $url_base . "reset_password.php?token=" . $token . "&email=" . urlencode($email);

        $mail = new PHPMailer(true);

        // --- CONFIGURAÇÃO DO SERVIDOR SMTP ---
        $mail->isSMTP();
        $mail->Host       = 'smtps.uol.com.br'; // ⚠️ MUDAR: Servidor SMTP
        $mail->SMTPAuth   = true;
        $mail->Username   = 'gr.oshiro@uol.com.br'; // ⚠️ MUDAR: Seu e-mail
        $mail->Password   = '2735lubi'; // ⚠️ MUDAR: Senha do App
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS; // Habilita TLS
        $mail->Port       = 587; 
        $mail->CharSet    = 'UTF-8';

        // --- REMETENTE E DESTINATÁRIO ---
        $mail->setFrom('gr.oshiro@uol.com.br', 'Sistema de Controle');
        $mail->addAddress($email, $usuario['nome']); // E-mail do usuário para redefinição
        
        // --- CONTEÚDO DO E-MAIL ---
        $mail->isHTML(true);
        $mail->Subject = 'Redefinicao de Senha para o Sistema de Controle';
        
        $mail->Body    = "
            <html>
            <body>
                <h2>Ola, {$usuario['nome']}!</h2>
                <p>Voce solicitou a redefinicao de senha para a sua conta. O link expirara em 1 hora.</p>
                <p>Clique no link abaixo para criar uma nova senha:</p>
                <a href='{$link_reset}' style='background-color:#007bff; color:white; padding:10px 20px; text-decoration:none; border-radius:5px; display:inline-block;'>
                    Criar Nova Senha
                </a>
                <p style='margin-top: 20px; font-size: 0.8em; color: #666;'>Se voce nao solicitou essa redefinicao, ignore este email.</p>
            </body>
            </html>
        ";
        $mail->AltBody = "Ola, {$usuario['nome']}! Use o seguinte link para redefinir sua senha: {$link_reset}";

        $mail->send();
    }

} catch (PDOException $e) {
    error_log("Erro no envio de token (BD): " . $e->getMessage());
} catch (Exception $e) {
    error_log("Erro no envio do email (PHPMailer): {$mail->ErrorInfo}");
}

// 7. Redirecionar SEMPRE para uma mensagem genérica de sucesso
header("Location: forgot_password.php?status=sucesso");
exit;
?>
