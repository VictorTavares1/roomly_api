<?php
require __DIR__ . '/../../config/db.php';
require __DIR__ . '/../../config/middleware.php';

$auth_user = authenticate($conn);
require_role($auth_user, ['admin']);

$data = get_json_body();
require_fields($data, ['id', 'role']);

$id = validate_positive_int($data['id'], 'id');
validate_whitelist($data['role'], ['professor', 'funcionario', 'admin'], 'role');

// Admin não pode alterar o seu próprio role
if ($id == $auth_user['id']) {
    json_error("Não podes alterar o teu próprio cargo.", 400);
}

try {
    $sql = "UPDATE users SET role = :role WHERE id = :id";
    $stmt = $conn->prepare($sql);
    $stmt->bindParam(':role', $data['role']);
    $stmt->bindParam(':id', $id, PDO::PARAM_INT);

    if ($stmt->execute()) {
        json_success("Cargo atualizado com sucesso!");
    } else {
        json_error("Erro ao atualizar cargo.", 500);
    }

} catch (PDOException $e) {
    error_log("Erro ao atualizar role: " . $e->getMessage());
    json_error("Erro interno do servidor.", 500);
}
?>