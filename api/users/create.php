<?php
require __DIR__ . '/../../config/db.php';
require __DIR__ . '/../../config/middleware.php';

$auth_user = authenticate($conn);
require_role($auth_user, ['admin']);

$data = get_json_body();
require_fields($data, ['name', 'email', 'password']);

$name = trim(htmlspecialchars(strip_tags($data['name'])));
$email = filter_var(trim($data['email']), FILTER_VALIDATE_EMAIL);
$password = $data['password'];
$role = isset($data['role']) ? $data['role'] : 'professor';

// Validar nome
if (strlen($name) < 3) {
    json_error("O nome deve ter pelo menos 3 caracteres.", 400);
}
if (strlen($name) > 100) {
    json_error("O nome não pode ultrapassar 100 caracteres.", 400);
}

// Validar email
if (!$email) {
    json_error("Formato de email inválido.", 400);
}

// Validar password
if (strlen($password) < 6) {
    json_error("A palavra-passe deve ter pelo menos 6 caracteres.", 400);
}
if (strlen($password) > 72) {
    json_error("A palavra-passe não pode ultrapassar 72 caracteres.", 400);
}
if (!preg_match('/[A-Za-z]/', $password)) {
    json_error("A palavra-passe deve conter pelo menos uma letra.", 400);
}
if (!preg_match('/[0-9]/', $password)) {
    json_error("A palavra-passe deve conter pelo menos um número.", 400);
}

$password_hash = password_hash($password, PASSWORD_DEFAULT);

validate_whitelist($role, ['professor', 'funcionario', 'admin'], 'role');

try {
    // Verificar se o email já existe
    $check = $conn->prepare("SELECT id FROM users WHERE email = :email");
    $check->bindParam(':email', $email);
    $check->execute();

    if ($check->rowCount() > 0) {
        json_error("Esse email já existe!", 409);
    }

    // Criar o utilizador
    $sql = "INSERT INTO users (name, email, password, role) VALUES (:name, :email, :pass, :role)";
    $stmt = $conn->prepare($sql);
    $stmt->bindParam(':name', $name);
    $stmt->bindParam(':email', $email);
    $stmt->bindParam(':pass', $password_hash);
    $stmt->bindParam(':role', $role);

    if ($stmt->execute()) {
        require_once __DIR__ . '/../../config/logger.php';
        logActivity($conn, $auth_user['id'], 'novo_utilizador', "Utilizador \"{$name}\" criado ({$role})");
        json_success("Utilizador criado com sucesso!", [], 201);
    } else {
        json_error("Erro ao criar utilizador.", 500);
    }

} catch (PDOException $e) {
    error_log("Erro ao criar utilizador: " . $e->getMessage());
    json_error("Erro interno do servidor.", 500);
}
?>