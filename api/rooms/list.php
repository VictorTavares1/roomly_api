<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: Content-Type");
header("Content-Type: application/json; charset=UTF-8");

require '../../config/db.php';

try {
    // ALTERAÇÃO AQUI:
    // Adicionámos "WHERE is_active = 1" para ignorar as salas que foram "soft deleted"
    $sql = "SELECT * FROM rooms WHERE is_active = 1";

    $stmt = $conn->prepare($sql);
    $stmt->execute();

    $rooms = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode($rooms);

} catch (PDOException $e) {
    echo json_encode(["status" => "erro", "mensagem" => $e->getMessage()]);
}
?>