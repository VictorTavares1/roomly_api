<?php
require __DIR__ . '/../../config/db.php';
require __DIR__ . '/../../config/middleware.php';

$auth_user = authenticate($conn);

// Expira automaticamente reservas pendentes sem check-in
require __DIR__ . '/expire_pending.php';

// Usa o ID do utilizador autenticado (ignora o query param por segurança)
$user_id = $auth_user['id'];

try {
    $sql = "SELECT r.*, rm.name as room_name, rm.type as room_type,
                   CASE WHEN r.end_time < NOW() THEN 1 ELSE 0 END AS is_past
            FROM reservations r
            JOIN rooms rm ON r.rooms_id = rm.id
            WHERE r.users_id = :uid
            ORDER BY r.start_time DESC";

    $stmt = $conn->prepare($sql);
    $stmt->bindParam(':uid', $user_id, PDO::PARAM_INT);
    $stmt->execute();

    $reservations = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo json_encode($reservations);

} catch (PDOException $e) {
    error_log("Erro ao listar minhas reservas: " . $e->getMessage());
    json_error("Erro interno do servidor.", 500);
}
?>