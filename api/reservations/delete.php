<?php
require '../../config/db.php';
require '../../config/logger.php';

$data = json_decode(file_get_contents("php://input"), true);

if (isset($data['id'])) {
    $id = $data['id'];

    try {
        // -----------------------------------------------------------
        // PASSO 1: Buscar dados da reserva ANTES de apagar
        // (Senão não sabemos o que escrever no log)
        // -----------------------------------------------------------
        $query_info = "SELECT users_id, rooms_id, start_time FROM reservations WHERE id = :id";
        $stmt_info = $conn->prepare($query_info);
        $stmt_info->bindParam(':id', $id);
        $stmt_info->execute();
        $reserva = $stmt_info->fetch(PDO::FETCH_ASSOC);

        if ($reserva) {
            // -------------------------------------------------------
            // PASSO 2: Apagar a reserva
            // -------------------------------------------------------
            $sql = "DELETE FROM reservations WHERE id = :id";
            $stmt = $conn->prepare($sql);
            $stmt->bindParam(':id', $id);

            if ($stmt->execute()) {

                // ---------------------------------------------------
                // PASSO 3: Registar no Log (Se apagou com sucesso)
                // ---------------------------------------------------

                // Formata a data para ficar legível (Ex: 2026-02-14 10:00)
                $data_reserva = substr($reserva['start_time'], 0, 16);

                $descricao = "Cancelou reserva da Sala ID " . $reserva['rooms_id'] . " (" . $data_reserva . ")";

                // Usamos o ID do utilizador que estava na reserva
                logActivity($conn, $reserva['users_id'], 'cancelamento', $descricao);

                // ---------------------------------------------------

                echo json_encode(["status" => "sucesso"]);
            } else {
                echo json_encode(["status" => "erro", "mensagem" => "Não foi possível apagar."]);
            }
        } else {
            echo json_encode(["status" => "erro", "mensagem" => "Reserva não encontrada."]);
        }

    } catch (PDOException $e) {
        echo json_encode(["status" => "erro", "mensagem" => "Erro SQL: " . $e->getMessage()]);
    }
} else {
    echo json_encode(["status" => "erro", "mensagem" => "ID em falta."]);
}
?>