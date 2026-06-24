<?php
require __DIR__ . '/../../config/db.php';
require __DIR__ . '/../../config/middleware.php';

$auth_user = authenticate($conn);
require_role($auth_user, ['admin']);

$data = get_json_body();
require_fields($data, ['id']);

$id = validate_positive_int($data['id'], 'id');

try {
    $stmt_name = $conn->prepare("SELECT name FROM rooms WHERE id = :id");
    $stmt_name->bindParam(':id', $id, PDO::PARAM_INT);
    $stmt_name->execute();
    $room = $stmt_name->fetch(PDO::FETCH_ASSOC);

    // Soft delete (desativar, não apagar)
    $sql = "UPDATE rooms SET is_active = 0 WHERE id = :id";
    $stmt = $conn->prepare($sql);
    $stmt->bindParam(':id', $id, PDO::PARAM_INT);

    if ($stmt->execute()) {
        require_once __DIR__ . '/../../config/logger.php';
        $room_name = $room['name'] ?? "ID {$id}";
        logActivity($conn, $auth_user['id'], 'remocao_sala', "Sala \"{$room_name}\" desativada");
        json_success("Sala desativada com sucesso!");
    } else {
        json_error("Erro ao desativar sala.", 500);
    }

} catch (PDOException $e) {
    error_log("Erro ao desativar sala: " . $e->getMessage());
    json_error("Erro interno do servidor.", 500);
}
?>