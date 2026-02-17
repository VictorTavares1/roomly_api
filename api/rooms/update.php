<?php
require '../../config/db.php';

$data = json_decode(file_get_contents("php://input"), true);

// Precisamos do ID para saber qual atualizar, e dos dados novos
if (isset($data['id']) && isset($data['name']) && isset($data['capacity'])) {

    try {
        $sql = "UPDATE rooms 
                SET name = :name, 
                    capacity = :capacity, 
                    has_projector = :has_projector 
                WHERE id = :id";

        $stmt = $conn->prepare($sql);

        $name = htmlspecialchars(strip_tags($data['name']));
        // Converte true/false para 1 ou 0
        $hasProjector = (isset($data['has_projector']) && $data['has_projector']) ? 1 : 0;

        $stmt->bindParam(':name', $name);
        $stmt->bindParam(':capacity', $data['capacity']);
        $stmt->bindParam(':has_projector', $hasProjector);
        $stmt->bindParam(':id', $data['id']);

        if ($stmt->execute()) {
            echo json_encode(["status" => "sucesso", "mensagem" => "Sala atualizada!"]);
        } else {
            echo json_encode(["status" => "erro", "mensagem" => "Não foi possível atualizar."]);
        }
    } catch (PDOException $e) {
        echo json_encode(["status" => "erro", "mensagem" => "Erro SQL: " . $e->getMessage()]);
    }
} else {
    echo json_encode(["status" => "erro", "mensagem" => "Dados incompletos."]);
}
?>