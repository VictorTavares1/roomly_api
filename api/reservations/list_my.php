<?php
require __DIR__ . '/../../config/db.php';
require __DIR__ . '/../../config/middleware.php';

$auth_user = authenticate($conn);

// Usa o ID do utilizador autenticado (ignora o query param por segurança)
$user_id = $auth_user['id'];

try {
    $sql = "SELECT r.*, rm.name as room_name 
            FROM reservations r
            JOIN rooms rm ON r.rooms_id = rm.id
            WHERE r.users_id = :uid
            AND r.end_time >= NOW()
            ORDER BY r.start_time ASC";

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