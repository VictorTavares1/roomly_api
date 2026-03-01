<?php
require __DIR__ . '/../../config/db.php';
require __DIR__ . '/../../config/middleware.php';

$auth_user = authenticate($conn);

$data = get_json_body();
require_fields($data, ['room_id', 'description']);

// Usa o ID do utilizador autenticado
$user_id = $auth_user['id'];
$description = htmlspecialchars(strip_tags($data['description']));
$room_id = validate_positive_int($data['room_id'], 'room_id');

try {
    $query = "INSERT INTO reports (users_id, rooms_id, description, status) VALUES (:uid, :rid, :desc, 'aberto')";
    $stmt = $conn->prepare($query);
    $stmt->bindParam(":uid", $user_id, PDO::PARAM_INT);
    $stmt->bindParam(":rid", $room_id, PDO::PARAM_INT);
    $stmt->bindParam(":desc", $description);

    if ($stmt->execute()) {
        require_once __DIR__ . '/../../config/logger.php';
        logActivity($conn, $user_id, 'reporte', "Reportou problema na Sala ID $room_id");

        json_success("Reporte criado com sucesso!", [], 201);
    } else {
        json_error("Erro ao guardar reporte.", 500);
    }

} catch (PDOException $e) {
    error_log("Erro ao criar reporte: " . $e->getMessage());
    json_error("Erro interno do servidor.", 500);
}
?>