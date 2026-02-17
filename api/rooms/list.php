<?php
require '../../config/db.php';

try {
    $sql = "SELECT * FROM rooms WHERE is_active = 1";

    $stmt = $conn->prepare($sql);
    $stmt->execute();

    $rooms = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode($rooms);

} catch (PDOException $e) {
    echo json_encode(["status" => "erro", "mensagem" => $e->getMessage()]);
}
?>