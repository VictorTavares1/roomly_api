<?php
require __DIR__ . '/../../config/db.php';
require __DIR__ . '/../../config/middleware.php';

$auth_user = authenticate($conn);
require_role($auth_user, ['admin']);

$data = get_json_body();
require_fields($data, ['id']);

$id = validate_positive_int($data['id'], 'id');

try {
    $sql = "DELETE FROM reservations WHERE id = :id";
    $stmt = $conn->prepare($sql);
    $stmt->bindParam(':id', $id, PDO::PARAM_INT);

    if ($stmt->execute()) {
        require_once __DIR__ . '/../../config/logger.php';
        logActivity($conn, $auth_user['id'], 'admin_delete', "Admin eliminou reserva ID $id");
        json_success("Reserva eliminada pelo administrador.");
    } else {
        json_error("Erro ao eliminar reserva.", 500);
    }

} catch (PDOException $e) {
    error_log("Erro ao eliminar reserva (admin): " . $e->getMessage());
    json_error("Erro interno do servidor.", 500);
}
?>