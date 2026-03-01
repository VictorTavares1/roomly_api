<?php
require __DIR__ . '/../../config/db.php';
require __DIR__ . '/../../config/middleware.php';

$auth_user = authenticate($conn);
require_role($auth_user, ['admin']);

$data = get_json_body();
require_fields($data, ['name', 'email', 'password']);

$name = htmlspecialchars(strip_tags($data['name']));
$email = filter_var($data['email'], FILTER_VALIDATE_EMAIL);

if (!$email) {
    json_error("Formato de email inválido.", 400);
}

$password_hash = password_hash($data['password'], PASSWORD_DEFAULT);

$role = isset($data['role']) ? $data['role'] : 'professor';
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
        json_success("Utilizador criado com sucesso!", [], 201);
    } else {
        json_error("Erro ao criar utilizador.", 500);
    }

} catch (PDOException $e) {
    error_log("Erro ao criar utilizador: " . $e->getMessage());
    json_error("Erro interno do servidor.", 500);
}
?>