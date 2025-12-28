<?php
// Arquivo: send_reset_link.php
require_once 'conexao.php';

// 1. Configurações da API Resend
$apiKey = 're_8hatntgm_6dRKzQkjhD49ta2exp6vMKqE'; 

// 2. Verifica banco e entrada
if (!$pdo) {
    header("Location: forgot_password.php?status=erro_bd");
    exit;
}

$email_destino = filter_input(INPUT_POST, 'email', FILTER_SANITIZE_EMAIL);

if (empty($email_destino)) {
    header("Location: forgot_password.php");
    exit;
}

try {
    // 3. Busca o usuário
    $sql_usuario = "SELECT id, nome FROM usuario WHERE email = :email";
    $stmt = $pdo->prepare($sql_usuario);
    $stmt->execute(['email' => $email_destino]);
    $usuario = $stmt->fetch();

    if ($usuario) {
        // 4. Geração do Token
        $token = bin2hex(random_bytes(32)); 
        $token_hash = hash('sha256', $token);
        $expires_at = date('Y-m-d H:i:s', strtotime('+1 hour'));

        // 5. Salva no BD
        $sql_update = "UPDATE usuario SET reset_token = :token_hash, token_expires_at = :expires_at WHERE id = :id";
        $stmt = $pdo->prepare($sql_update);
        $stmt->execute([
            'token_hash' => $token_hash,
            'expires_at' => $expires_at,
            'id' => $usuario['id']
        ]);
        
        // 6. Construção do Link (Adaptado para HTTPS do Render)
        $protocol = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') ? "https://" : "http://";
        $link_reset = $protocol . $_SERVER['HTTP_HOST'] . "/reset_password.php?token=" . $token . "&email=" . urlencode($email_destino);

        // 7. PREPARAÇÃO DO JSON PARA A API
        $data = [
            "from" => "onboarding@resend.dev", // Alterar quando validar seu domínio
            "to" => [$email_destino],
            "subject" => "Redefinicao de Senha - Sistema de Controle",
            "html" => "
                <html>
                <body>
                    <h2>Ola, {$usuario['nome']}!</h2>
                    <p>Voce solicitou a redefinicao de senha. O link expirara em 1 hora.</p>
                    <a href='{$link_reset}' style='background-color:#007bff; color:white; padding:10px 20px; text-decoration:none; border-radius:5px; display:inline-block;'>
                        Criar Nova Senha
                    </a>
                </body>
                </html>"
        ];

        // 8. ENVIO VIA CURL (MÉTODO JSON)
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
            error_log("Erro Resend API (JSON): " . $response);
        }
    }

} catch (Exception $e) {
    error_log("Erro no processo: " . $e->getMessage());
}

// 9. Redirecionar Sucesso
header("Location: forgot_password.php?status=sucesso");
exit;

