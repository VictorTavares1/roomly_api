<?php
// config/middleware.php
// Middleware de autenticação e autorização por token

require_once __DIR__ . '/../helpers/response.php';

/**
 * Gera um token seguro (64 caracteres hex).
 */
function generate_token()
{
    return bin2hex(random_bytes(32));
}

/**
 * Autentica o utilizador pelo token no header Authorization.
 * Retorna os dados do utilizador se válido, ou responde 401 e termina.
 */
function authenticate($conn)
{
    $headers = getallheaders();
    $auth_header = $headers['Authorization'] ?? $headers['authorization'] ?? '';
    $token = str_replace('Bearer ', '', $auth_header);

    if (empty($token)) {
        json_error("Autenticação necessária. Token não fornecido.", 401);
    }

    try {
        $stmt = $conn->prepare("SELECT id, name, email, role, is_active, token_expires_at FROM users WHERE token = :token LIMIT 1");
        $stmt->bindParam(':token', $token);
        $stmt->execute();

        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$user) {
            json_error("Sessão inválida ou expirada.", 401);
        }

        if ($user['is_active'] == 0) {
            json_error("Conta desativada.", 403);
        }

        // Verificar expiração do token
        if (!empty($user['token_expires_at']) && strtotime($user['token_expires_at']) < time()) {
            json_error("Sessão expirada. Faça login novamente.", 401);
        }

        unset($user['token_expires_at']);
        return $user;
    } catch (PDOException $e) {
        error_log("Erro no middleware de autenticação: " . $e->getMessage());
        json_error("Erro interno de autenticação.", 500);
    }
}

/**
 * Verifica se o utilizador tem uma das roles permitidas.
 */
function require_role($user, $allowed_roles)
{
    $roles = is_array($allowed_roles) ? $allowed_roles : [$allowed_roles];
    if (!in_array($user['role'], $roles, true)) {
        json_error("Sem permissão para esta ação.", 403);
    }
}
?>