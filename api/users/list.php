<?php
require __DIR__ . '/../../config/db.php';
require __DIR__ . '/../../config/middleware.php';

$auth_user = authenticate($conn);
require_role($auth_user, ['admin']);

try {
    $sql = "SELECT id, name, email, role, is_active FROM users ORDER BY is_active DESC, name ASC";
    $stmt = $conn->prepare($sql);
    $stmt->execute();
    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode($users);

} catch (PDOException $e) {
    error_log("Erro ao listar utilizadores: " . $e->getMessage());
    json_error("Erro interno do servidor.", 500);
}
?>