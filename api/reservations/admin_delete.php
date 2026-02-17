<?php
require '../../config/db.php';

$data = json_decode(file_get_contents("php://input"), true);

if (isset($data['id'])) {
    try {
        // DELETE direto para libertar o horário imediatamente
        $sql = "DELETE FROM reservations WHERE id = :id";

        $stmt = $conn->prepare($sql);
        $stmt->bindParam(':id', $data['id']);

        if ($stmt->execute()) {
            echo json_encode(["status" => "sucesso", "mensagem" => "Reserva cancelada!"]);
        } else {
            echo json_encode(["status" => "erro", "mensagem" => "Erro ao cancelar."]);
        }
    } catch (PDOException $e) {
        echo json_encode(["status" => "erro", "mensagem" => "Erro SQL: " . $e->getMessage()]);
    }
} else {
    echo json_encode(["status" => "erro", "mensagem" => "ID em falta."]);
}
?>