<?php
require __DIR__ . '/../../config/db.php';
require __DIR__ . '/../../config/middleware.php';

$auth_user = authenticate($conn);
require_role($auth_user, 'admin');

$data = get_json_body();
require_fields($data, ['id']);

$id = validate_positive_int($data['id'], 'id');

try {
    $stmt_check = $conn->prepare("
        SELECT r.id, r.users_id, r.start_time, r.status, rm.name AS room_name
        FROM reservations r
        JOIN rooms rm ON rm.id = r.rooms_id
        WHERE r.id = :id
    ");
    $stmt_check->bindParam(':id', $id, PDO::PARAM_INT);
    $stmt_check->execute();
    $reserva = $stmt_check->fetch(PDO::FETCH_ASSOC);

    if (!$reserva) {
        json_error("Reserva não encontrada.", 404);
    }

    if ($reserva['status'] === 'confirmada') {
        json_error("Esta reserva já está confirmada.", 400);
    }

    if ($reserva['status'] === 'cancelada') {
        json_error("Não é possível confirmar uma reserva cancelada.", 400);
    }

    $stmt_confirm = $conn->prepare("
        UPDATE reservations SET status = 'confirmada', confirmed_at = NOW() WHERE id = :id
    ");
    $stmt_confirm->bindParam(':id', $id, PDO::PARAM_INT);
    $stmt_confirm->execute();

    require_once __DIR__ . '/../../config/logger.php';
    $hora = substr($reserva['start_time'], 11, 5);
    logActivity($conn, $auth_user['id'], 'checkin', "Confirmação manual de \"{$reserva['room_name']}\" — reserva das $hora (por admin)");

    json_success("Reserva confirmada manualmente.");

} catch (PDOException $e) {
    error_log("Erro na confirmação manual: " . $e->getMessage());
    json_error("Erro interno do servidor.", 500);
}
?>
