<?php
require __DIR__ . '/../../config/db.php';
require __DIR__ . '/../../config/middleware.php';

$auth_user = authenticate($conn);
$data = get_json_body();
require_fields($data, ['current_password', 'new_password']);

$user_id = $auth_user['id'];
$current_password = $data['current_password'];
$new_password = $data['new_password'];

// Validar tamanho mínimo da nova password
if (strlen($new_password) < 6) {
    json_error("A nova palavra-passe deve ter pelo menos 6 caracteres.", 400);
}

try {
    $query = "SELECT `password` FROM users WHERE id = :id LIMIT 1";
    $stmt = $conn->prepare($query);
    $stmt->bindParam(":id", $user_id);
    $stmt->execute();

    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$user) {
        json_error("Utilizador não encontrado.", 404);
    }

    // Verificar password atual (SEM fallback de texto claro)
    if (!password_verify($current_password, $user['password'])) {
        json_error("Senha atual incorreta.", 401);
    }

    // Encriptar nova password
    $nova_senha_encriptada = password_hash($new_password, PASSWORD_DEFAULT);

    $query_update = "UPDATE users SET `password` = :new_pass WHERE id = :id";
    $stmt_update = $conn->prepare($query_update);
    $stmt_update->bindParam(":new_pass", $nova_senha_encriptada);
    $stmt_update->bindParam(":id", $user_id);

    if ($stmt_update->execute()) {
        json_success("Senha atualizada com sucesso!");
    } else {
        json_error("Erro ao atualizar senha.", 500);
    }

} catch (PDOException $e) {
    error_log("Erro ao atualizar password: " . $e->getMessage());
    json_error("Erro interno do servidor.", 500);
}
?>