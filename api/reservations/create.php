<?php
require '../../config/db.php';
require '../../config/logger.php';

$data = json_decode(file_get_contents("php://input"), true);

if (
    isset($data['user_id']) &&
    isset($data['rooms_id']) &&
    isset($data['start_time']) &&
    isset($data['end_time']) &&
    isset($data['purpose'])
) {
    try {
        // 1. Verificar se a sala já está ocupada
        $checkSql = "SELECT id FROM reservations 
                     WHERE rooms_id = :room 
                     AND (
                        (start_time < :end AND end_time > :start)
                     )";

        $stmt = $conn->prepare($checkSql);
        $stmt->bindParam(':room', $data['rooms_id']);
        $stmt->bindParam(':start', $data['start_time']);
        $stmt->bindParam(':end', $data['end_time']);
        $stmt->execute();

        if ($stmt->rowCount() > 0) {
            echo json_encode(["status" => "erro", "mensagem" => "Sala ocupada neste horário!"]);
            exit;
        }

        // 2. Criar a Reserva
        $sql = "INSERT INTO reservations (users_id, rooms_id, start_time, end_time, purpose, status) 
                VALUES (:user, :room, :start, :end, :purpose, 'confirmada')";

        $insert = $conn->prepare($sql);

        $insert->bindParam(':user', $data['user_id']);
        $insert->bindParam(':room', $data['rooms_id']);
        $insert->bindParam(':start', $data['start_time']);
        $insert->bindParam(':end', $data['end_time']);
        $insert->bindParam(':purpose', $data['purpose']);

        if ($insert->execute()) {

            // --- 2. GATILHO DO LOGGER AQUI ---
            // Formata uma descrição simples. Ex: "Sala 4 - 10:00 as 12:00"
            $desc = "Sala ID " . $data['rooms_id'] . " (" . substr($data['start_time'], 11, 5) . " às " . substr($data['end_time'], 11, 5) . ")";

            // Grava na base de dados
            logActivity($conn, $data['user_id'], 'reserva', $desc);
            // ---------------------------------

            echo json_encode(["status" => "sucesso", "mensagem" => "Reserva criada com sucesso!"]);
        } else {
            $errorInfo = $insert->errorInfo();
            echo json_encode(["status" => "erro", "mensagem" => "Erro SQL: " . $errorInfo[2]]);
        }

    } catch (PDOException $e) {
        echo json_encode(["status" => "erro", "mensagem" => "Erro de Conexão: " . $e->getMessage()]);
    }
} else {
    echo json_encode(["status" => "erro", "mensagem" => "Dados incompletos!"]);
}
?>