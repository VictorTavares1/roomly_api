<?php
require '../../config/db.php';

$data = json_decode(file_get_contents("php://input"), true);

if (isset($data['id']) && isset($data['role'])) {
    try {
        $sql = "UPDATE users SET role = :role WHERE id = :id";
        $stmt = $conn->prepare($sql);

        $stmt->bindParam(':role', $data['role']);
        $stmt->bindParam(':id', $data['id']);

        if ($stmt->execute()) {
            echo json_encode(["status" => "sucesso", "mensagem" => "Cargo atualizado com sucesso!"]);
        } else {
            echo json_encode(["status" => "erro", "mensagem" => "Erro ao atualizar cargo."]);
        }
    } catch (PDOException $e) {
        echo json_encode(["status" => "erro", "mensagem" => "Erro SQL: " . $e->getMessage()]);
    }
} else {
    echo json_encode(["status" => "erro", "mensagem" => "Dados em falta."]);
}
?>