<?php
// Arquivo: send_reset_link.php
require_once 'conexao.php';

// 1. Configurações de Log (Ver erros na aba Logs do Render)
ini_set('display_errors', 0);
ini_set('log_errors', 1);
error_reporting(E_ALL);

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
use PHPMailer\PHPMailer\SMTP;

// 2. Ajuste de Caminho para PHPMailer (Compatível com Linux/Render)
$base_path = __DIR__ . '/PHPMailer/src/';

if (!file_exists($base_path . 'Exception.php')) {
    error_log("❌ Erro Crítico: Pasta PHPMailer não encontrada em: " . $base_path);
    die("Erro de configuração interna. Verifique os logs do servidor.");
}

require $base_path . 'Exception.php';
require $base_path . 'PHPMailer.php';
require $base_path . 'SMTP.php';

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
    // 3. Busca o usuário pelo e-mail
    $sql_usuario = "SELECT id, nome FROM usuario WHERE email = :email";
    $stmt = $pdo->prepare($sql_usuario);
    $stmt->execute(['email' => $email]);
    $usuario = $stmt->fetch();

    if ($usuario) {
        // 4. Geração do Token Seguro
        $token = bin2hex(random_bytes(32)); 
        $token_hash = hash('sha256', $token);
        $expires_at = date('Y-m-d H:i:s', strtotime('+1 hour'));

        // 5. Salva o Token no Banco (PostgreSQL do Render)
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
        
        // 6. Construção da URL (HTTPS automático no Render)
        $protocol = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') ? "https://" : "http://";
        $url_base = $protocol . $_SERVER['HTTP_HOST'] . "/"; 
        $link_reset = $url_base . "reset_password.php?token=" . $token . "&email=" . urlencode($email);

        // 7. Configuração do PHPMailer
        $mail = new PHPMailer(true);
        $mail->isSMTP();
        $mail->Host       = 'smtps.uol.com.br'; 
        $mail->SMTPAuth   = true;
        $mail->Username   = 'gr.oshiro@uol.com.br'; 
        
        // Pega a senha configurada no painel do Render (Environment Variables)
        $mail->Password   = getenv('SMTP_PASS') ?: '2735lubi'; 
        
        // Configuração de Segurança para Porta 465 (Recomendado para UOL no Render)
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS; 
        $mail->Port       = 465; 
        $mail->CharSet    = 'UTF-8';

        // Bypass de verificação de SSL (Evita erros de certificado no Render)
        $mail->SMTPOptions = array(
            'ssl' => array(
                'verify_peer' => false,
                'verify_peer_name' => false,
                'allow_self_signed' => true
            )
        );

        // --- REMETENTE E DESTINATÁRIO ---
        $mail->setFrom('gr.oshiro@uol.com.br', 'Sistema de Controle');
        $mail->addAddress($email, $usuario['nome']); 
        
        // --- CONTEÚDO DO E-MAIL ---
        $mail->isHTML(true);
        $mail->Subject = 'Redefinição de Senha - Sistema de Controle';
        $mail->Body    = "
            <html>
            <body style='font-family: Arial, sans-serif; line-height: 1.6;'>
                <h2 style='color: #333;'>Olá, {$usuario['nome']}!</h2>
                <p>Recebemos uma solicitação para redefinir a sua senha. Este link expirará em 1 hora.</p>
                <div style='margin: 30px 0;'>
                    <a href='{$link_reset}' style='background-color:#f70b0b; color:white; padding:12px 25px; text-decoration:none; border-radius:8px; font-weight:bold;'>
                        Criar Nova Senha
                    </a>
                </div>
                <p style='font-size: 12px; color: #666;'>Se você não solicitou esta alteração, ignore este e-mail.</p>
            </body>
            </html>";
            
        $mail->send();
    }

    // Redireciona para sucesso mesmo se o usuário não existir (evita descoberta de e-mails)
    header("Location: forgot_password.php?status=sucesso");
    exit;

} catch (PDOException $e) {
    error_log("❌ Erro de Banco de Dados: " . $e->getMessage());
    header("Location: forgot_password.php?status=erro_bd");
    exit;
} catch (Exception $e) {
    // Se o PHPMailer falhar, o motivo real aparecerá na aba Logs do Render
    error_log("❌ Erro PHPMailer: " . $mail->ErrorInfo);
    header("Location: forgot_password.php?status=erro_email");
    exit;
}










