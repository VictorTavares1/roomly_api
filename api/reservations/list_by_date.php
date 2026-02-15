<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");

require '../../config/db.php';

$date = isset($_GET['date']) ? $_GET['date'] : date('Y-m-d');

try {
    // Seleciona reservas onde o start_time (parte da data) corresponde à data pedida
    // Assumindo que start_time é DATETIME (ex: '2023-10-25 14:00:00')
    $sql = "SELECT 
                r.id, 
                r.start_time, 
                r.end_time, 
                r.purpose, 
                r.status,
                u.name as user_name, 
                rm.name as room_name
            FROM reservations r
            JOIN users u ON r.users_id = u.id
            JOIN rooms rm ON r.rooms_id = rm.id
            WHERE DATE(r.start_time) = :date
            ORDER BY r.start_time ASC";

    $stmt = $conn->prepare($sql);
    $stmt->bindParam(':date', $date);
    $stmt->execute();

    $reservations = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode($reservations);

} catch (PDOException $e) {
    echo json_encode(["status" => "erro", "mensagem" => $e->getMessage()]);
}
?>