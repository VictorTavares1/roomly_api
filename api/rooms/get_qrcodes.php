<?php
require __DIR__ . '/../../config/db.php';
require __DIR__ . '/../../config/middleware.php';

$auth_user = authenticate($conn);
require_role($auth_user, 'admin');

try {
    $stmt = $conn->prepare("SELECT id, name, type, capacity, qr_token FROM rooms WHERE is_active = 1 ORDER BY name ASC");
    $stmt->execute();
    $rooms = $stmt->fetchAll(PDO::FETCH_ASSOC);

    json_success("OK", ['rooms' => $rooms]);

} catch (PDOException $e) {
    error_log("Erro ao obter QR codes: " . $e->getMessage());
    json_error("Erro interno do servidor.", 500);
}
?>
