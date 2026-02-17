<?php
require __DIR__ . '/../../config/db.php';

$data = json_decode(file_get_contents("php://input"));

if (isset($data->email) && isset($data->password)) {
    $email = $data->email;
    $password_enviada = $data->password;

    try {
        $query = "SELECT * FROM users WHERE email = :email LIMIT 1";
        $stmt = $conn->prepare($query);
        $stmt->bindParam(":email", $email);
        $stmt->execute();

        if ($stmt->rowCount() > 0) {
            $row = $stmt->fetch(PDO::FETCH_ASSOC);

            if (isset($row['is_active']) && $row['is_active'] == 0) {
                echo json_encode(["status" => "erro", "mensagem" => "Conta desativada."]);
                exit();
            }

            $senha_na_bd = $row['password'];

            if (password_verify($password_enviada, $senha_na_bd)) {
                unset($row['password']);

                echo json_encode([
                    "status" => "sucesso",
                    "mensagem" => "Login realizado com sucesso!",
                    "user" => $row
                ]);
            } else {
                echo json_encode([
                    "status" => "erro",
                    "mensagem" => "Email ou palavra-passe incorretos."
                ]);
            }

        } else {
            echo json_encode(["status" => "erro", "mensagem" => "Email ou palavra-passe incorretos."]);
        }

    } catch (Exception $e) {
        http_response_code(500);
        error_log("Erro no login: " . $e->getMessage());
        echo json_encode(["status" => "erro", "mensagem" => "Erro interno do servidor."]);
    }
} else {
    echo json_encode(["status" => "erro", "mensagem" => "Dados insuficientes (Email/Senha)."]);
}
?>