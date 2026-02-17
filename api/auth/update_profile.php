<?php
require '../../config/db.php';

$data = json_decode(file_get_contents("php://input"));

if (!isset($data->id)) {
    echo json_encode(["status" => "erro", "mensagem" => "O ID do utilizador é obrigatório."]);
    exit();
}

if (!$conn) {
    echo json_encode(["status" => "erro", "mensagem" => "Sem conexão à BD."]);
    exit();
}

try {
    $query = "UPDATE users SET name = :name, email = :email WHERE id = :id";
    $stmt = $conn->prepare($query);

    $stmt->bindParam(":name", $data->name);
    $stmt->bindParam(":email", $data->email);
    $stmt->bindParam(":id", $data->id);

    if ($stmt->execute()) {
        $stmtCheck = $conn->prepare("SELECT * FROM users WHERE id = :id");
        $stmtCheck->bindParam(":id", $data->id);
        $stmtCheck->execute();
        $updatedUser = $stmtCheck->fetch(PDO::FETCH_ASSOC);

        unset($updatedUser['password']);

        echo json_encode(["status" => "sucesso", "user" => $updatedUser]);
    } else {
        $erro = $stmt->errorInfo();
        error_log("Erro ao atualizar perfil: " . $erro[2]);
        echo json_encode(["status" => "erro", "mensagem" => "Erro ao atualizar perfil."]);
    }

} catch (PDOException $e) {
    error_log("Exceção ao atualizar perfil: " . $e->getMessage());
    echo json_encode(["status" => "erro", "mensagem" => "Erro interno do servidor."]);
}
?>