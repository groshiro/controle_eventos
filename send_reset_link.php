<?php
// Arquivo: send_reset_link.php
require_once 'conexao.php';

// 1. Configurações
$apiKey = 're_8hatntgm_6dRKzQkjhD49ta2exp6vMKqE'; // Começa com re_...
$email_destino = filter_input(INPUT_POST, 'email', FILTER_SANITIZE_EMAIL);

if (empty($email_destino)) {
    header("Location: forgot_password.php");
    exit;
}

try {
    // 2. Busca usuário no banco
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

        // 3. ENVIO VIA API DO RESEND (Nunca bloqueia no Render)
        $data = [
            "from" => "onboarding@resend.dev", // Use este remetente padrão para testes
            "to" => [$email_destino],
            "subject" => "Redefinição de Senha",
            "html" => "<h2>Olá {$usuario['nome']}!</h2><p>Clique no link para resetar sua senha:</p><a href='{$link_reset}'>{$link_reset}</a>"
        ];

        $ch = curl_init('https://api.resend.com/emails');
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Authorization: Bearer ' . $apiKey,
            'Content-Type: application/json'
        ]);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($httpCode !== 200 && $httpCode !== 201) {
             error_log("Erro Resend API: " . $response);
        }
    }

    header("Location: forgot_password.php?status=sucesso");
    exit;

} catch (Exception $e) {
    die("Erro interno: " . $e->getMessage());
}
