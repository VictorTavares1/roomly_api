<?php
require __DIR__ . '/../../config/db.php';
require __DIR__ . '/../../config/middleware.php';

$auth_user = authenticate($conn);
$data = get_json_body();
require_fields($data, ['name', 'email']);

// O utilizador só pode atualizar o seu próprio perfil
$user_id = $auth_user['id'];

try {
    // Verificar se o email já pertence a outro utilizador
    $check = $conn->prepare("SELECT id FROM users WHERE email = :email AND id != :id");
    $check->bindParam(":email", $data['email']);
    $check->bindParam(":id", $user_id);
    $check->execute();

    if ($check->rowCount() > 0) {
        json_error("Este email já está em uso por outra conta.", 409);
    }

    $name = htmlspecialchars(strip_tags($data['name']));
    $email = filter_var($data['email'], FILTER_VALIDATE_EMAIL);

    if (!$email) {
        json_error("Formato de email inválido.", 400);
    }

    $query = "UPDATE users SET name = :name, email = :email WHERE id = :id";
    $stmt = $conn->prepare($query);
    $stmt->bindParam(":name", $name);
    $stmt->bindParam(":email", $email);
    $stmt->bindParam(":id", $user_id);

    if ($stmt->execute()) {
        $stmtCheck = $conn->prepare("SELECT id, name, email, role, is_active FROM users WHERE id = :id");
        $stmtCheck->bindParam(":id", $user_id);
        $stmtCheck->execute();
        $updatedUser = $stmtCheck->fetch(PDO::FETCH_ASSOC);

        json_success("Perfil atualizado com sucesso!", ["user" => $updatedUser]);
    } else {
        json_error("Erro ao atualizar perfil.", 500);
    }

} catch (PDOException $e) {
    error_log("Erro ao atualizar perfil: " . $e->getMessage());
    json_error("Erro interno do servidor.", 500);
}
?>