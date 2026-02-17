<?php
require '../../config/db.php';

$data = json_decode(file_get_contents("php://input"), true);

if (isset($data['id']) && isset($data['rooms_id']) && isset($data['start_time']) && isset($data['end_time'])) {

    try {
        // 1. VERIFICAÇÃO DE CONFLITO (O TRUQUE ESTÁ AQUI)
        // Verificamos se existe ALGUMA OUTRA reserva (id != :id) que choque com este horário
        $checkSql = "SELECT id FROM reservations 
                     WHERE rooms_id = :room 
                     AND id != :id  
                     AND (
                        (start_time < :end AND end_time > :start)
                     )";

        $stmt = $conn->prepare($checkSql);
        $stmt->bindParam(':room', $data['rooms_id']);
        $stmt->bindParam(':start', $data['start_time']);
        $stmt->bindParam(':end', $data['end_time']);
        $stmt->bindParam(':id', $data['id']); // Ignora a própria reserva
        $stmt->execute();

        if ($stmt->rowCount() > 0) {
            echo json_encode(["status" => "erro", "mensagem" => "Esse horário já está ocupado por outra pessoa!"]);
            exit;
        }

        // 2. SE ESTIVER LIVRE, ATUALIZA
        $sql = "UPDATE reservations 
                SET rooms_id = :room, start_time = :start, end_time = :end, purpose = :purpose 
                WHERE id = :id";

        $update = $conn->prepare($sql);
        $update->bindParam(':room', $data['rooms_id']);
        $update->bindParam(':start', $data['start_time']);
        $update->bindParam(':end', $data['end_time']);
        $update->bindParam(':purpose', $data['purpose']);
        $update->bindParam(':id', $data['id']);

        if ($update->execute()) {
            echo json_encode(["status" => "sucesso", "mensagem" => "Reserva atualizada!"]);
        } else {
            echo json_encode(["status" => "erro", "mensagem" => "Erro ao atualizar."]);
        }

    } catch (PDOException $e) {
        echo json_encode(["status" => "erro", "mensagem" => "Erro SQL: " . $e->getMessage()]);
    }
} else {
    echo json_encode(["status" => "erro", "mensagem" => "Dados incompletos."]);
}
?>