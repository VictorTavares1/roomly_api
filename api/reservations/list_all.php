<?php
require __DIR__ . '/../../config/db.php';
require __DIR__ . '/../../config/middleware.php';

$auth_user = authenticate($conn);
require_role($auth_user, ['admin', 'funcionario']);

// Expira automaticamente reservas pendentes sem check-in
require __DIR__ . '/expire_pending.php';

try {
    $sql = "SELECT r.id, r.start_time, r.end_time, r.purpose, r.status,
                   u.name as user_name, rm.name as room_name
            FROM reservations r
            JOIN users u ON r.users_id = u.id
            JOIN rooms rm ON r.rooms_id = rm.id
            ORDER BY r.start_time DESC";

    $stmt = $conn->prepare($sql);
    $stmt->execute();
    $reservations = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode($reservations);

} catch (PDOException $e) {
    error_log("Erro ao listar reservas: " . $e->getMessage());
    json_error("Erro interno do servidor.", 500);
}
?>