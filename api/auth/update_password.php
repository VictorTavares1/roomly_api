<?php
require '../../config/db.php';

$json = file_get_contents("php://input");
$data = json_decode($json);

if (isset($data->user_id) && isset($data->current_password) && isset($data->new_password)) {

    $user_id = $data->user_id;
    $current_password = $data->current_password;
    $new_password = $data->new_password;

    try {
        $query = "SELECT `password` FROM users WHERE id = :id LIMIT 1";
        $stmt = $conn->prepare($query);
        $stmt->bindParam(":id", $user_id);
        $stmt->execute();

        if ($stmt->rowCount() > 0) {
            $user = $stmt->fetch(PDO::FETCH_ASSOC);

            // 1. Verifica a senha atual (ainda em modo híbrido para não dar erro agora)
            if (password_verify($current_password, $user['password']) || $current_password === $user['password']) {

                // 2. AQUI ESTÁ A MÁGICA: Encriptamos a NOVA senha antes de guardar
                $nova_senha_encriptada = password_hash($new_password, PASSWORD_DEFAULT);

                $query_update = "UPDATE users SET `password` = :new_pass WHERE id = :id";
                $stmt_update = $conn->prepare($query_update);

                // Guardamos o HASH e não o texto limpo
                $stmt_update->bindParam(":new_pass", $nova_senha_encriptada);
                $stmt_update->bindParam(":id", $user_id);

                if ($stmt_update->execute()) {
                    echo json_encode(["status" => "sucesso", "mensagem" => "Senha atualizada e encriptada!"]);
                }
            } else {
                echo json_encode(["status" => "erro", "mensagem" => "Senha atual incorreta."]);
            }
        }
    } catch (Exception $e) {
        echo json_encode(["status" => "erro", "mensagem" => "Erro: " . $e->getMessage()]);
    }
}
?>