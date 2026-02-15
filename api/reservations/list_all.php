<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");

require '../../config/db.php';

try {
    // JOIN PODEROSO: Junta Reservas + Utilizadores + Salas
    // Assim sabemos que o "ID 5" é o "Victor" e a "Sala 2" é o "Auditório"
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
            ORDER BY r.start_time DESC"; // As mais recentes primeiro

    $stmt = $conn->prepare($sql);
    $stmt->execute();

    $reservations = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode($reservations);

} catch (PDOException $e) {
    echo json_encode(["status" => "erro", "mensagem" => $e->getMessage()]);
}
?>