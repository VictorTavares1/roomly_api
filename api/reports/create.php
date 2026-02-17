<?php
require '../../config/db.php';

$data = json_decode(file_get_contents("php://input"));

// O frontend envia 'user_id' e 'room_id', mas gravamos em 'users_id' e 'rooms_id'
if (isset($data->user_id) && isset($data->room_id) && isset($data->description)) {
    // ATENÇÃO AQUI: users_id e rooms_id
    $query = "INSERT INTO reports (users_id, rooms_id, description, status) VALUES (:uid, :rid, :desc, 'aberto')";
    $stmt = $conn->prepare($query);

    $stmt->bindParam(":uid", $data->user_id);
    $stmt->bindParam(":rid", $data->room_id);
    $stmt->bindParam(":desc", $data->description);

    if ($stmt->execute()) {
        echo json_encode(["status" => "sucesso"]);
    } else {
        echo json_encode(["status" => "erro", "mensagem" => "Erro ao guardar report."]);
    }
}
?>