<?php
require __DIR__ . '/../../config/db.php';
require __DIR__ . '/../../config/middleware.php';

$auth_user = authenticate($conn);
require_role($auth_user, ['admin']);

$data = get_json_body();
require_fields($data, ['name', 'capacity']);

$name = htmlspecialchars(strip_tags($data['name']));
$capacity = validate_positive_int($data['capacity'], 'capacity');
$hasProjector = isset($data['has_projector']) && $data['has_projector'] ? 1 : 0;

$allowedTypes = ['AULA', 'LABORATÓRIO', 'REUNIÃO', 'AUDITÓRIO'];
$type = isset($data['type']) && in_array($data['type'], $allowedTypes) ? $data['type'] : 'AULA';

try {
    $sql = "INSERT INTO rooms (name, capacity, has_projector, is_active, type) VALUES (:name, :capacity, :has_projector, 1, :type)";
    $stmt = $conn->prepare($sql);
    $stmt->bindParam(':name', $name);
    $stmt->bindParam(':capacity', $capacity, PDO::PARAM_INT);
    $stmt->bindParam(':has_projector', $hasProjector, PDO::PARAM_INT);
    $stmt->bindParam(':type', $type);

    if ($stmt->execute()) {
        require_once __DIR__ . '/../../config/logger.php';
        logActivity($conn, $auth_user['id'], 'nova_sala', "Sala \"{$name}\" criada (cap. {$capacity})");
        json_success("Sala criada com sucesso!", [], 201);
    } else {
        json_error("Não foi possível criar a sala.", 500);
    }

} catch (PDOException $e) {
    error_log("Erro ao criar sala: " . $e->getMessage());
    json_error("Erro interno do servidor.", 500);
}
?>