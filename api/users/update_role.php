<?php
require __DIR__ . '/../../config/db.php';
require __DIR__ . '/../../config/middleware.php';

$auth_user = authenticate($conn);
require_role($auth_user, ['admin']);

$data = get_json_body();
require_fields($data, ['id', 'role']);

$id = validate_positive_int($data['id'], 'id');
validate_whitelist($data['role'], ['professor', 'funcionario', 'admin'], 'role');

// Admin não pode alterar o seu próprio role
if ($id == $auth_user['id']) {
    json_error("Não podes alterar o teu próprio cargo.", 400);
}

try {
    $stmt_uname = $conn->prepare("SELECT name FROM users WHERE id = :id");
    $stmt_uname->bindParam(':id', $id, PDO::PARAM_INT);
    $stmt_uname->execute();
    $target_user = $stmt_uname->fetch(PDO::FETCH_ASSOC);

    $sql = "UPDATE users SET role = :role WHERE id = :id";
    $stmt = $conn->prepare($sql);
    $stmt->bindParam(':role', $data['role']);
    $stmt->bindParam(':id', $id, PDO::PARAM_INT);

    if ($stmt->execute()) {
        require_once __DIR__ . '/../../config/logger.php';
        $uname = $target_user['name'] ?? "ID {$id}";
        logActivity($conn, $auth_user['id'], 'alteracao_cargo', "Cargo de \"{$uname}\" → {$data['role']}");
        json_success("Cargo atualizado com sucesso!");
    } else {
        json_error("Erro ao atualizar cargo.", 500);
    }

} catch (PDOException $e) {
    error_log("Erro ao atualizar role: " . $e->getMessage());
    json_error("Erro interno do servidor.", 500);
}
?>