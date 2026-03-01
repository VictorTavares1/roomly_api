<?php
require __DIR__ . '/../../config/db.php';
require __DIR__ . '/../../config/middleware.php';

$auth_user = authenticate($conn);

try {
    $query = "SELECT r.id, r.start_time, r.end_time, rm.name as room_name, u.name as user_name 
              FROM reservations r 
              JOIN rooms rm ON r.rooms_id = rm.id
              JOIN users u ON r.users_id = u.id
              WHERE r.status != 'cancelled'
              ORDER BY r.start_time ASC";

    $stmt = $conn->prepare($query);
    $stmt->execute();
    $events = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode($events);

} catch (PDOException $e) {
    error_log("Erro ao buscar eventos do calendário: " . $e->getMessage());
    json_error("Erro interno do servidor.", 500);
}
?>