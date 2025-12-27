<?php
// Arquivo: send_reset_link.php
require_once 'conexao.php';

// Importação dos Namespaces do PHPMailer
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
use PHPMailer\PHPMailer\SMTP;

/**
 * 1. AJUSTE DE AUTOLOAD MANUAL
 * No Render (Linux), os caminhos diferenciam maiúsculas de minúsculas.
* No Render/Docker, seus arquivos ficam em /var/www/html/
 */
$base_path = '/var/www/html/PHPMailer/src/';

if (!file_exists($base_path . 'Exception.php')) {
    die("❌ Erro: A pasta PHPMailer não foi encontrada na raiz do GitHub.");
}

require $base_path . 'Exception.php';
require $base_path . 'PHPMailer.php';
require $base_path . 'SMTP.php';

// Verifica se a conexão PDO está ativa
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
    // 2. Busca o usuário
    $sql_usuario = "SELECT id, nome FROM usuario WHERE email = :email";
    $stmt = $pdo->prepare($sql_usuario);
    $stmt->execute(['email' => $email]);
    $usuario = $stmt->fetch();

    if ($usuario) {
        // 3. Geração do Token
        $token = bin2hex(random_bytes(32)); 
        $token_hash = hash('sha256', $token);
        
        // 4. Expiração (Compatível com PostgreSQL do Render)
        $expires_at = date('Y-m-d H:i:s', strtotime('+1 hour'));

        // 5. Salvar no Banco
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
        
        // 6. CONSTRUÇÃO DA URL PARA O RENDER
        // No Render, o site sempre roda em HTTPS. O $_SERVER['HTTP_HOST'] pegará 'controle-claro.onrender.com'
        $protocol = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') ? "https://" : "http://";
        $url_base = $protocol . $_SERVER['HTTP_HOST'] . "/"; 
        $link_reset = $url_base . "reset_password.php?token=" . $token . "&email=" . urlencode($email);

        $mail = new PHPMailer(true);

        // --- CONFIGURAÇÃO DO SERVIDOR SMTP ---
        $mail->isSMTP();
        $mail->Host       = 'smtps.uol.com.br'; 
        $mail->SMTPAuth   = true;
        $mail->Username   = 'gr.oshiro@uol.com.br'; 
        
        /**
         * 🔑 SEGURANÇA: No Render, vá em Environment e crie 'SMTP_PASS'.
         * Se não estiver lá, ele usará a sua senha padrão.
         */
        $mail->Password   = getenv('SMTP_PASS') ?: '2735lubi'; 
        
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS; 
        $mail->Port       = 587; 
        $mail->CharSet    = 'UTF-8';

        // --- REMETENTE E DESTINATÁRIO ---
        $mail->setFrom('gr.oshiro@uol.com.br', 'Sistema de Controle');
        $mail->addAddress($email, $usuario['nome']); 
        
        // --- CONTEÚDO DO E-MAIL ---
        $mail->isHTML(true);
        $mail->Subject = 'Redefinição de Senha - Sistema de Controle';
        
        $mail->Body    = "
            <html>
            <body style='font-family: sans-serif;'>
                <h2>Olá, {$usuario['nome']}!</h2>
                <p>Você solicitou a redefinição de senha. O link abaixo expirará em 1 hora.</p>
                <div style='margin: 30px 0;'>
                    <a href='{$link_reset}' style='background-color:#f70b0b; color:white; padding:12px 25px; text-decoration:none; border-radius:8px; font-weight:bold;'>
                        Criar Nova Senha
                    </a>
                </div>
                <p style='font-size: 12px; color: #666;'>Se você não solicitou, ignore este e-mail.</p>
            </body>
            </html>";
            
        $mail->send();
    }

} catch (PDOException $e) {
    error_log("Erro BD: " . $e->getMessage());
} catch (Exception $e) {
    error_log("Erro PHPMailer: {$mail->ErrorInfo}");
}

header("Location: forgot_password.php?status=sucesso");
exit;
?>







