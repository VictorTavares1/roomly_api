<?php
require '../../config/db.php';

$data = json_decode(file_get_contents("php://input"), true);

if (isset($data['id']) && isset($data['is_active'])) {
    try {
        $sql = "UPDATE users SET is_active = :status WHERE id = :id";
        $stmt = $conn->prepare($sql);

        $stmt->bindParam(':status', $data['is_active']); // 1 ou 0
        $stmt->bindParam(':id', $data['id']);

        if ($stmt->execute()) {
            $msg = $data['is_active'] == 1 ? "Conta reativada!" : "Conta desativada!";
            echo json_encode(["status" => "sucesso", "mensagem" => $msg]);
        } else {
            echo json_encode(["status" => "erro", "mensagem" => "Erro ao atualizar estado."]);
        }
    } catch (PDOException $e) {
        echo json_encode(["status" => "erro", "mensagem" => "Erro SQL: " . $e->getMessage()]);
    }
}
?>