<?php
require __DIR__ . '/../../config/db.php';
require __DIR__ . '/../../config/middleware.php';

$auth_user = authenticate($conn);

$data = get_json_body();
require_fields($data, ['id']);

$id = validate_positive_int($data['id'], 'id');

try {
    // 1. Buscar dados da reserva antes de apagar
    $query_info = "SELECT users_id, rooms_id, start_time FROM reservations WHERE id = :id";
    $stmt_info = $conn->prepare($query_info);
    $stmt_info->bindParam(':id', $id, PDO::PARAM_INT);
    $stmt_info->execute();
    $reserva = $stmt_info->fetch(PDO::FETCH_ASSOC);

    if (!$reserva) {
        json_error("Reserva não encontrada.", 404);
    }

    // Verificar que o utilizador é o dono da reserva (a não ser que seja admin)
    if ($reserva['users_id'] != $auth_user['id'] && $auth_user['role'] !== 'admin') {
        json_error("Sem permissão para cancelar esta reserva.", 403);
    }

    // 2. Apagar a reserva
    $sql = "DELETE FROM reservations WHERE id = :id";
    $stmt = $conn->prepare($sql);
    $stmt->bindParam(':id', $id, PDO::PARAM_INT);

    if ($stmt->execute()) {
        require_once __DIR__ . '/../../config/logger.php';
        $data_reserva = substr($reserva['start_time'], 0, 16);
        $descricao = "Cancelou reserva da Sala ID " . $reserva['rooms_id'] . " (" . $data_reserva . ")";
        logActivity($conn, $auth_user['id'], 'cancelamento', $descricao);

        json_success("Reserva cancelada com sucesso!");
    } else {
        json_error("Não foi possível cancelar.", 500);
    }

} catch (PDOException $e) {
    error_log("Erro ao apagar reserva: " . $e->getMessage());
    json_error("Erro interno do servidor.", 500);
}
?>