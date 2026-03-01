<?php
require __DIR__ . '/../../config/db.php';
require __DIR__ . '/../../config/middleware.php';

$auth_user = authenticate($conn);
require_role($auth_user, ['admin']);

$data = get_json_body();
require_fields($data, ['id', 'is_active']);

$id = validate_positive_int($data['id'], 'id');
$is_active = $data['is_active'] == 1 ? 1 : 0;

// Admin não pode desativar a sua própria conta
if ($id == $auth_user['id'] && $is_active == 0) {
    json_error("Não podes desativar a tua própria conta.", 400);
}

try {
    $sql = "UPDATE users SET is_active = :status WHERE id = :id";
    $stmt = $conn->prepare($sql);
    $stmt->bindParam(':status', $is_active, PDO::PARAM_INT);
    $stmt->bindParam(':id', $id, PDO::PARAM_INT);

    if ($stmt->execute()) {
        // Invalidar token se conta foi desativada
        if ($is_active == 0) {
            $stmt_token = $conn->prepare("UPDATE users SET token = NULL WHERE id = :id");
            $stmt_token->bindParam(':id', $id, PDO::PARAM_INT);
            $stmt_token->execute();
        }

        $msg = $is_active == 1 ? "Conta reativada!" : "Conta desativada!";
        json_success($msg);
    } else {
        json_error("Erro ao atualizar estado.", 500);
    }

} catch (PDOException $e) {
    error_log("Erro ao atualizar estado do utilizador: " . $e->getMessage());
    json_error("Erro interno do servidor.", 500);
}
?>