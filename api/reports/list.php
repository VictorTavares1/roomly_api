<?php
require __DIR__ . '/../../config/db.php';
require __DIR__ . '/../../config/middleware.php';

$auth_user = authenticate($conn);

try {
    $query = "SELECT r.*, u.name as user_name, rm.name as room_name 
              FROM reports r
              JOIN users u ON r.users_id = u.id  
              JOIN rooms rm ON r.rooms_id = rm.id
              ORDER BY r.id DESC";

    $stmt = $conn->prepare($query);
    $stmt->execute();

    echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));

} catch (PDOException $e) {
    error_log("Erro ao listar reportes: " . $e->getMessage());
    json_error("Erro interno do servidor.", 500);
}
?>