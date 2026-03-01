<?php
require __DIR__ . '/../../config/db.php';
require __DIR__ . '/../../config/middleware.php';

$auth_user = authenticate($conn);

$date = isset($_GET['date']) ? $_GET['date'] : date('Y-m-d');

// Validar formato de data
if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
    json_error("Formato de data inválido. Use YYYY-MM-DD.", 400);
}

try {
    $sql = "SELECT r.id, r.start_time, r.end_time, r.purpose, r.status,
                   u.name as user_name, rm.name as room_name
            FROM reservations r
            JOIN users u ON r.users_id = u.id
            JOIN rooms rm ON r.rooms_id = rm.id
            WHERE r.start_time >= :date_start 
            AND r.start_time < DATE_ADD(:date_end, INTERVAL 1 DAY)
            ORDER BY r.start_time ASC";

    $stmt = $conn->prepare($sql);
    $stmt->bindParam(':date_start', $date);
    $stmt->bindParam(':date_end', $date);
    $stmt->execute();

    $reservations = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo json_encode($reservations);

} catch (PDOException $e) {
    error_log("Erro ao listar reservas por data: " . $e->getMessage());
    json_error("Erro interno do servidor.", 500);
}
?>