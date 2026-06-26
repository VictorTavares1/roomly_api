<?php
require __DIR__ . '/../../config/db.php';
require __DIR__ . '/../../config/middleware.php';

$auth_user = authenticate($conn);
require_role($auth_user, ['admin']);

$data = get_json_body();
require_fields($data, ['id', 'name', 'capacity']);

$id = validate_positive_int($data['id'], 'id');
$name = trim(htmlspecialchars(strip_tags($data['name'])));
if (strlen($name) < 2) json_error("O nome da sala deve ter pelo menos 2 caracteres.", 400);
if (strlen($name) > 100) json_error("O nome da sala não pode exceder 100 caracteres.", 400);
$capacity = validate_positive_int($data['capacity'], 'capacity');
$hasProjector = (isset($data['has_projector']) && $data['has_projector']) ? 1 : 0;

$allowedTypes = ['AULA', 'LABORATÓRIO', 'REUNIÃO', 'AUDITÓRIO'];
$type = isset($data['type']) && in_array($data['type'], $allowedTypes) ? $data['type'] : 'AULA';

try {
    $sql = "UPDATE rooms SET name = :name, capacity = :capacity, has_projector = :has_projector, type = :type WHERE id = :id";
    $stmt = $conn->prepare($sql);
    $stmt->bindParam(':name', $name);
    $stmt->bindParam(':capacity', $capacity, PDO::PARAM_INT);
    $stmt->bindParam(':has_projector', $hasProjector, PDO::PARAM_INT);
    $stmt->bindParam(':type', $type);
    $stmt->bindParam(':id', $id, PDO::PARAM_INT);

    if ($stmt->execute()) {
        require_once __DIR__ . '/../../config/logger.php';
        logActivity($conn, $auth_user['id'], 'alteracao_sala', "Sala \"{$name}\" atualizada");
        json_success("Sala atualizada com sucesso!");
    } else {
        json_error("Não foi possível atualizar.", 500);
    }

} catch (PDOException $e) {
    error_log("Erro ao atualizar sala: " . $e->getMessage());
    json_error("Erro interno do servidor.", 500);
}
?>