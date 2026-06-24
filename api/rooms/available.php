<?php
require __DIR__ . '/../../config/db.php';
require __DIR__ . '/../../config/middleware.php';

$auth_user = authenticate($conn);

$date       = isset($_GET['date'])       ? $_GET['date']       : '';
$start_time = isset($_GET['start_time']) ? $_GET['start_time'] : '';
$end_time   = isset($_GET['end_time'])   ? $_GET['end_time']   : '';

if (!$date || !$start_time || !$end_time) {
    json_error("Parâmetros obrigatórios: date, start_time, end_time.", 400);
}

$start_full = $date . ' ' . $start_time . ':00';
$end_full   = $date . ' ' . $end_time   . ':00';

try {
    $sql = "SELECT
                r.id,
                r.name,
                r.capacity,
                r.has_projector,
                r.is_active,
                r.type,
                'disponivel' AS status
            FROM rooms r
            WHERE r.is_active = 1
              AND NOT EXISTS (
                  SELECT 1 FROM reports rp
                  WHERE rp.rooms_id = r.id
                    AND rp.status IN ('aberto', 'em_progresso')
              )
              AND NOT EXISTS (
                  SELECT 1 FROM reservations res
                  WHERE res.rooms_id = r.id
                    AND res.status != 'cancelada'
                    AND res.start_time < :end_full
                    AND res.end_time   > :start_full
              )
            ORDER BY r.name ASC";

    $stmt = $conn->prepare($sql);
    $stmt->bindParam(':start_full', $start_full);
    $stmt->bindParam(':end_full',   $end_full);
    $stmt->execute();
    $rooms = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode($rooms);

} catch (PDOException $e) {
    error_log("Erro ao listar salas disponíveis: " . $e->getMessage());
    json_error("Erro interno do servidor.", 500);
}
?>
