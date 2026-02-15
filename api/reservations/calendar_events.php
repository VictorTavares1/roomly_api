<?php
// Devolve todas as reservas ativas para o calendário pintar
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");

require '../../config/db.php';

try {
    // Trazemos o nome da sala para aparecer no título do evento (Ex: "Sala TIC - Prof. João")
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
    echo json_encode(["error" => $e->getMessage()]);
}
?>