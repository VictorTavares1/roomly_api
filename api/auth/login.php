<?php
require __DIR__ . '/../../config/db.php';
require __DIR__ . '/../../helpers/response.php';

$data = get_json_body();
require_fields($data, ['email', 'password']);

$email = $data['email'];
$password_enviada = $data['password'];

try {
    // Rate limiting: máximo 5 tentativas em 15 minutos
    $stmt_check = $conn->prepare(
        "SELECT COUNT(*) FROM login_attempts 
         WHERE email = :email AND attempted_at > DATE_SUB(NOW(), INTERVAL 15 MINUTE)"
    );
    $stmt_check->bindParam(":email", $email);
    $stmt_check->execute();

    if ($stmt_check->fetchColumn() >= 5) {
        json_error("Demasiadas tentativas. Tenta novamente em 15 minutos.", 429);
    }

    // Registar tentativa
    $stmt_attempt = $conn->prepare("INSERT INTO login_attempts (email) VALUES (:email)");
    $stmt_attempt->bindParam(":email", $email);
    $stmt_attempt->execute();

    // Buscar utilizador
    $query = "SELECT * FROM users WHERE email = :email LIMIT 1";
    $stmt = $conn->prepare($query);
    $stmt->bindParam(":email", $email);
    $stmt->execute();

    if ($stmt->rowCount() === 0) {
        json_error("Email ou palavra-passe incorretos.", 401);
    }

    $row = $stmt->fetch(PDO::FETCH_ASSOC);

    // Conta desativada
    if (isset($row['is_active']) && $row['is_active'] == 0) {
        json_error("Conta desativada. Contacte a administração.", 403);
    }

    // Verificar password (apenas hash, sem fallback de texto claro)
    if (!password_verify($password_enviada, $row['password'])) {
        json_error("Email ou palavra-passe incorretos.", 401);
    }

    // Gerar token de sessão e guardar na BD (expira em 24h)
    require_once __DIR__ . '/../../config/middleware.php';
    $token = generate_token();
    $expires_at = date('Y-m-d H:i:s', strtotime('+24 hours'));

    $stmt_token = $conn->prepare("UPDATE users SET token = :token, token_expires_at = :expires WHERE id = :id");
    $stmt_token->bindParam(':token', $token);
    $stmt_token->bindParam(':expires', $expires_at);
    $stmt_token->bindParam(':id', $row['id']);
    $stmt_token->execute();

    // Limpar tentativas de login com sucesso
    $stmt_clear = $conn->prepare("DELETE FROM login_attempts WHERE email = :email");
    $stmt_clear->bindParam(":email", $email);
    $stmt_clear->execute();

    // Remover campos sensíveis antes de enviar
    unset($row['password']);
    unset($row['token']);

    json_success("Login realizado com sucesso!", [
        "user" => $row,
        "token" => $token
    ]);

} catch (PDOException $e) {
    error_log("Erro no login: " . $e->getMessage());
    json_error("Erro interno do servidor.", 500);
}
?>