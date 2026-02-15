<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: POST");
require '../../config/db.php';

$data = json_decode(file_get_contents("php://input"));

if (isset($data->id) && isset($data->status)) {
    $query = "UPDATE reports SET status = :status WHERE id = :id";
    $stmt = $conn->prepare($query);
    $stmt->bindParam(":status", $data->status);
    $stmt->bindParam(":id", $data->id);

    if ($stmt->execute()) {
        echo json_encode(["status" => "sucesso"]);
    } else {
        echo json_encode(
            ["status" => "erro"]
        );
    }
}
?>