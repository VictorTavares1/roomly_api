<?php
require __DIR__ . '/../../config/db.php';
require __DIR__ . '/../../config/middleware.php';

$auth_user = authenticate($conn);

try {
    $sql = "SELECT id, name, capacity, has_projector, is_active FROM rooms WHERE is_active = 1";
    $stmt = $conn->prepare($sql);
    $stmt->execute();
    $rooms = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode($rooms);

} catch (PDOException $e) {
    error_log("Erro ao listar salas: " . $e->getMessage());
    json_error("Erro interno do servidor.", 500);
}
?>