<?php
require __DIR__ . '/../../config/db.php';
require __DIR__ . '/../../config/middleware.php';

$auth_user = authenticate($conn);

$data = get_json_body();
require_fields($data, ['rooms_id', 'start_time', 'end_time', 'purpose']);

// Usa o ID do utilizador autenticado (não confia no body)
$user_id = $auth_user['id'];

try {
    // 1. Verificar se a sala já está ocupada neste horário
    $checkSql = "SELECT id FROM reservations 
                 WHERE rooms_id = :room 
                 AND (start_time < :end AND end_time > :start)";

    $stmt = $conn->prepare($checkSql);
    $stmt->bindParam(':room', $data['rooms_id'], PDO::PARAM_INT);
    $stmt->bindParam(':start', $data['start_time']);
    $stmt->bindParam(':end', $data['end_time']);
    $stmt->execute();

    if ($stmt->rowCount() > 0) {
        json_error("Sala ocupada neste horário!", 409);
    }

    // 2. Criar a Reserva
    $purpose = htmlspecialchars(strip_tags($data['purpose']));

    $sql = "INSERT INTO reservations (users_id, rooms_id, start_time, end_time, purpose, status) 
            VALUES (:user, :room, :start, :end, :purpose, 'confirmada')";

    $insert = $conn->prepare($sql);
    $insert->bindParam(':user', $user_id, PDO::PARAM_INT);
    $insert->bindParam(':room', $data['rooms_id'], PDO::PARAM_INT);
    $insert->bindParam(':start', $data['start_time']);
    $insert->bindParam(':end', $data['end_time']);
    $insert->bindParam(':purpose', $purpose);

    if ($insert->execute()) {
        // Registar no log de atividades
        require_once __DIR__ . '/../../config/logger.php';
        $desc = "Sala ID " . $data['rooms_id'] . " (" . substr($data['start_time'], 11, 5) . " às " . substr($data['end_time'], 11, 5) . ")";
        logActivity($conn, $user_id, 'reserva', $desc);

        json_success("Reserva criada com sucesso!", [], 201);
    } else {
        json_error("Erro ao criar reserva.", 500);
    }

} catch (PDOException $e) {
    error_log("Erro ao criar reserva: " . $e->getMessage());
    json_error("Erro interno do servidor.", 500);
}
?>