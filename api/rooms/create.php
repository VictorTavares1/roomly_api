<?php
require '../../config/db.php';

$data = json_decode(file_get_contents("php://input"), true);

if (isset($data['name']) && isset($data['capacity'])) {

    try {
        // MUDANÇA AQUI: Usamos 'has_projector' em vez de 'description'
        $sql = "INSERT INTO rooms (name, capacity, has_projector, is_active) 
                VALUES (:name, :capacity, :has_projector, 1)";

        $stmt = $conn->prepare($sql);

        $name = htmlspecialchars(strip_tags($data['name']));

        // Converte o true/false do Javascript para 1 ou 0 do MySQL
        $hasProjector = isset($data['has_projector']) && $data['has_projector'] ? 1 : 0;

        $stmt->bindParam(':name', $name);
        $stmt->bindParam(':capacity', $data['capacity']);
        $stmt->bindParam(':has_projector', $hasProjector); // Bind do projetor

        if ($stmt->execute()) {
            echo json_encode(["status" => "sucesso", "mensagem" => "Sala criada com sucesso!"]);
        } else {
            echo json_encode(["status" => "erro", "mensagem" => "Não foi possível criar a sala."]);
        }
    } catch (PDOException $e) {
        echo json_encode(["status" => "erro", "mensagem" => "Erro SQL: " . $e->getMessage()]);
    }
} else {
    echo json_encode(["status" => "erro", "mensagem" => "Nome e Capacidade são obrigatórios."]);
}
?>