<?php
require __DIR__ . '/../../config/db.php';
require __DIR__ . '/../../config/middleware.php';

$auth_user = authenticate($conn);

$room_id = isset($_GET['room_id']) ? (int)$_GET['room_id'] : 0;
if ($room_id <= 0) {
    json_error("room_id inválido.", 400);
}

try {
    $sql = "SELECT
                res.id,
                res.start_time,
                res.end_time,
                res.purpose,
                res.status,
                u.name AS user_name
            FROM reservations res
            JOIN users u ON res.users_id = u.id
            WHERE res.rooms_id = :room_id
              AND res.status != 'cancelada'
              AND res.end_time >= NOW()
            ORDER BY res.start_time ASC
            LIMIT 5";

    $stmt = $conn->prepare($sql);
    $stmt->bindParam(':room_id', $room_id, PDO::PARAM_INT);
    $stmt->execute();
    $reservations = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode($reservations);

} catch (PDOException $e) {
    error_log("Erro ao listar reservas da sala: " . $e->getMessage());
    json_error("Erro interno do servidor.", 500);
}
?>
