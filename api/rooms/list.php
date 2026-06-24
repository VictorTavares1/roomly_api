<?php
require __DIR__ . '/../../config/db.php';
require __DIR__ . '/../../config/middleware.php';

$auth_user = authenticate($conn);

try {
    $sql = "SELECT
                r.id,
                r.name,
                r.capacity,
                r.has_projector,
                r.is_active,
                r.type,
                CASE
                    WHEN EXISTS (
                        SELECT 1 FROM reservations res
                        WHERE res.rooms_id = r.id
                          AND res.status != 'cancelada'
                          AND NOW() BETWEEN res.start_time AND res.end_time
                    ) THEN 'em_curso'
                    ELSE 'disponivel'
                END AS status
            FROM rooms r
            WHERE r.is_active = 1
            ORDER BY r.name ASC";

    $stmt = $conn->prepare($sql);
    $stmt->execute();
    $rooms = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode($rooms);

} catch (PDOException $e) {
    error_log("Erro ao listar salas: " . $e->getMessage());
    json_error("Erro interno do servidor.", 500);
}
?>