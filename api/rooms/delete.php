<?php
require __DIR__ . '/../../config/db.php';
require __DIR__ . '/../../config/middleware.php';

$auth_user = authenticate($conn);
require_role($auth_user, ['admin']);

$data = get_json_body();
require_fields($data, ['id']);

$id = validate_positive_int($data['id'], 'id');

try {
    // Soft delete (desativar, não apagar)
    $sql = "UPDATE rooms SET is_active = 0 WHERE id = :id";
    $stmt = $conn->prepare($sql);
    $stmt->bindParam(':id', $id, PDO::PARAM_INT);

    if ($stmt->execute()) {
        json_success("Sala desativada com sucesso!");
    } else {
        json_error("Erro ao desativar sala.", 500);
    }

} catch (PDOException $e) {
    error_log("Erro ao desativar sala: " . $e->getMessage());
    json_error("Erro interno do servidor.", 500);
}
?>