<?php
require __DIR__ . '/../../config/db.php';
require __DIR__ . '/../../config/middleware.php';

$auth_user = authenticate($conn);

$data = get_json_body();
require_fields($data, ['id', 'rooms_id', 'start_time', 'end_time']);

$id = validate_positive_int($data['id'], 'id');

try {
    // Verificar que o utilizador é o dono da reserva (a não ser que seja admin)
    $stmt_owner = $conn->prepare("SELECT users_id FROM reservations WHERE id = :id");
    $stmt_owner->bindParam(':id', $id, PDO::PARAM_INT);
    $stmt_owner->execute();
    $reserva = $stmt_owner->fetch(PDO::FETCH_ASSOC);

    if (!$reserva) {
        json_error("Reserva não encontrada.", 404);
    }

    if ($reserva['users_id'] != $auth_user['id'] && $auth_user['role'] !== 'admin') {
        json_error("Sem permissão para editar esta reserva.", 403);
    }

    // Verificar se a reserva já começou
    $stmt_time = $conn->prepare("SELECT start_time FROM reservations WHERE id = :id");
    $stmt_time->bindParam(':id', $id, PDO::PARAM_INT);
    $stmt_time->execute();
    $reserva_time = $stmt_time->fetch(PDO::FETCH_ASSOC);

    if (strtotime($reserva_time['start_time']) <= time()) {
        json_error("Não é possível editar uma reserva que já começou.", 403);
    }

    // Verificar conflito de horário (excluir a própria reserva)
    $checkSql = "SELECT id FROM reservations 
                 WHERE rooms_id = :room 
                 AND id != :id  
                 AND (start_time < :end AND end_time > :start)";

    $stmt = $conn->prepare($checkSql);
    $stmt->bindParam(':room', $data['rooms_id'], PDO::PARAM_INT);
    $stmt->bindParam(':start', $data['start_time']);
    $stmt->bindParam(':end', $data['end_time']);
    $stmt->bindParam(':id', $id, PDO::PARAM_INT);
    $stmt->execute();

    if ($stmt->rowCount() > 0) {
        json_error("Esse horário já está ocupado por outra reserva!", 409);
    }

    // Atualizar
    $purpose = htmlspecialchars(strip_tags($data['purpose'] ?? ''));

    $sql = "UPDATE reservations SET rooms_id = :room, start_time = :start, end_time = :end, purpose = :purpose WHERE id = :id";
    $update = $conn->prepare($sql);
    $update->bindParam(':room', $data['rooms_id'], PDO::PARAM_INT);
    $update->bindParam(':start', $data['start_time']);
    $update->bindParam(':end', $data['end_time']);
    $update->bindParam(':purpose', $purpose);
    $update->bindParam(':id', $id, PDO::PARAM_INT);

    if ($update->execute()) {
        require_once __DIR__ . '/../../config/logger.php';
        $desc = "Reserva #" . $id . ($purpose ? " — " . mb_substr($purpose, 0, 40) : "");
        logActivity($conn, $auth_user['id'], 'alteracao', $desc);
        json_success("Reserva atualizada com sucesso!");
    } else {
        json_error("Erro ao atualizar.", 500);
    }

} catch (PDOException $e) {
    error_log("Erro ao atualizar reserva: " . $e->getMessage());
    json_error("Erro interno do servidor.", 500);
}
?>