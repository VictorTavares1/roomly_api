<?php
require __DIR__ . '/../../config/db.php';
require __DIR__ . '/../../config/middleware.php';

$auth_user = authenticate($conn);
require_role($auth_user, ['admin', 'funcionario']);

$data = get_json_body();
require_fields($data, ['id', 'status']);

$id = validate_positive_int($data['id'], 'id');
validate_whitelist($data['status'], ['pendente', 'resolvido'], 'status');

try {
    $query = "UPDATE reports SET status = :status WHERE id = :id";
    $stmt = $conn->prepare($query);
    $stmt->bindParam(":status", $data['status']);
    $stmt->bindParam(":id", $id, PDO::PARAM_INT);

    if ($stmt->execute()) {
        require_once __DIR__ . '/../../config/logger.php';
        logActivity($conn, $auth_user['id'], 'alteracao_reporte', "Reporte #{$id} → {$data['status']}");
        json_success("Estado do reporte atualizado!");
    } else {
        json_error("Erro ao atualizar estado.", 500);
    }

} catch (PDOException $e) {
    error_log("Erro ao atualizar reporte: " . $e->getMessage());
    json_error("Erro interno do servidor.", 500);
}
?>