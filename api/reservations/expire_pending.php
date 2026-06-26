<?php
// Pode ser chamado por cron job ou incluído no início de qualquer request
// Cancela reservas pendentes cuja janela de check-in (15 min) já expirou

require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../config/logger.php';

$CHECKIN_WINDOW_MINUTES = 15;

try {
    // Buscar reservas expiradas antes de as cancelar (para o log)
    $stmt_fetch = $conn->prepare("
        SELECT r.id, r.users_id, r.start_time, rm.name AS room_name
        FROM reservations r
        JOIN rooms rm ON rm.id = r.rooms_id
        WHERE r.status = 'pendente'
          AND r.start_time < DATE_SUB(NOW(), INTERVAL :window MINUTE)
    ");
    $stmt_fetch->bindParam(':window', $CHECKIN_WINDOW_MINUTES, PDO::PARAM_INT);
    $stmt_fetch->execute();
    $expired = $stmt_fetch->fetchAll(PDO::FETCH_ASSOC);

    if (!empty($expired)) {
        // Cancelar em bulk
        $stmt_cancel = $conn->prepare("
            UPDATE reservations
            SET status = 'cancelada'
            WHERE status = 'pendente'
              AND start_time < DATE_SUB(NOW(), INTERVAL :window MINUTE)
        ");
        $stmt_cancel->bindParam(':window', $CHECKIN_WINDOW_MINUTES, PDO::PARAM_INT);
        $stmt_cancel->execute();

        // Log por cada reserva expirada
        foreach ($expired as $r) {
            $hora = substr($r['start_time'], 11, 5);
            logActivity($conn, $r['users_id'], 'cancelamento', "\"{$r['room_name']}\" — reserva das $hora cancelada por falta de check-in");
        }
    }

} catch (PDOException $e) {
    error_log("Erro ao expirar reservas pendentes: " . $e->getMessage());
}
?>
