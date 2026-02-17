<?php
require '../../config/db.php';

$data = json_decode(file_get_contents("php://input"), true);

if (isset($data['id'])) {

    try {
        // NÃO APAGAMOS! Apenas desativamos (is_active = 0)
        // Assim não perdes o histórico das reservas dessa sala.
        $sql = "UPDATE rooms SET is_active = 0 WHERE id = :id";

        $stmt = $conn->prepare($sql);
        $stmt->bindParam(':id', $data['id']);

        if ($stmt->execute()) {
            echo json_encode(["status" => "sucesso", "mensagem" => "Sala desativada com sucesso!"]);
        } else {
            echo json_encode(["status" => "erro", "mensagem" => "Erro ao desativar sala."]);
        }
    } catch (PDOException $e) {
        echo json_encode(["status" => "erro", "mensagem" => "Erro SQL: " . $e->getMessage()]);
    }
} else {
    echo json_encode(["status" => "erro", "mensagem" => "ID da sala em falta."]);
}
?>