<?php
require __DIR__ . '/../../config/db.php';
require __DIR__ . '/../../config/middleware.php';

$auth_user = authenticate($conn);

$data = get_json_body();
require_fields($data, ['qr_token']);

$qr_token = trim($data['qr_token']);
$CHECKIN_WINDOW_MINUTES = 15;

try {
    // 1. Encontrar a sala pelo qr_token
    $stmt_room = $conn->prepare("SELECT id, name FROM rooms WHERE qr_token = :token AND is_active = 1 LIMIT 1");
    $stmt_room->bindParam(':token', $qr_token);
    $stmt_room->execute();
    $room = $stmt_room->fetch(PDO::FETCH_ASSOC);

    if (!$room) {
        json_error("QR Code inválido.", 404);
    }

    // 2. Encontrar reserva pendente do utilizador para esta sala que esteja dentro da janela de check-in
    // Aceita scan desde 5 min antes do início até 15 min depois
    $stmt_res = $conn->prepare("
        SELECT id, start_time, end_time, purpose
        FROM reservations
        WHERE users_id = :uid
          AND rooms_id  = :rid
          AND status    = 'pendente'
          AND start_time BETWEEN DATE_SUB(NOW(), INTERVAL :window MINUTE) AND DATE_ADD(NOW(), INTERVAL 5 MINUTE)
        ORDER BY start_time ASC
        LIMIT 1
    ");
    $stmt_res->bindParam(':uid',    $auth_user['id'], PDO::PARAM_INT);
    $stmt_res->bindParam(':rid',    $room['id'],      PDO::PARAM_INT);
    $stmt_res->bindParam(':window', $CHECKIN_WINDOW_MINUTES, PDO::PARAM_INT);
    $stmt_res->execute();
    $reserva = $stmt_res->fetch(PDO::FETCH_ASSOC);

    if (!$reserva) {
        json_error("Não tens nenhuma reserva pendente para esta sala neste horário.", 404);
    }

    // 3. Confirmar a reserva
    $stmt_confirm = $conn->prepare("
        UPDATE reservations
        SET status = 'confirmada', confirmed_at = NOW()
        WHERE id = :id
    ");
    $stmt_confirm->bindParam(':id', $reserva['id'], PDO::PARAM_INT);
    $stmt_confirm->execute();

    // 4. Registar log
    require_once __DIR__ . '/../../config/logger.php';
    $hora = substr($reserva['start_time'], 11, 5);
    logActivity($conn, $auth_user['id'], 'reserva', "Check-in em \"{$room['name']}\" — reserva das $hora confirmada");

    json_success("Check-in confirmado! Boa utilização da {$room['name']}.", [
        'reservation_id' => $reserva['id'],
        'room_name'      => $room['name'],
        'start_time'     => $reserva['start_time'],
        'end_time'       => $reserva['end_time'],
    ]);

} catch (PDOException $e) {
    error_log("Erro no check-in: " . $e->getMessage());
    json_error("Erro interno do servidor.", 500);
}
?>
