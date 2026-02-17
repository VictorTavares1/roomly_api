<?php
require '../../config/db.php';

$data = json_decode(file_get_contents("php://input"), true);

if (isset($data['name']) && isset($data['email']) && isset($data['password'])) {

    $name = $data['name'];
    $email = $data['email'];

    // 🔥 AQUI ESTÁ A MUDANÇA: Encriptamos logo ao receber!
    $password_raw = $data['password'];
    $password_hash = password_hash($password_raw, PASSWORD_DEFAULT);

    $role = isset($data['role']) ? $data['role'] : 'professor';

    // 1. Verificar se o email já existe
    $check = $conn->prepare("SELECT id FROM users WHERE email = :email");
    $check->bindParam(':email', $email);
    $check->execute();

    if ($check->rowCount() > 0) {
        echo json_encode(["status" => "erro", "mensagem" => "Esse email já existe!"]);
        exit;
    }

    // 2. Criar o utilizador
    // Nota: Agora guardamos :pass (que será o hash)
    $sql = "INSERT INTO users (name, email, password, role) VALUES (:name, :email, :pass, :role)";
    $stmt = $conn->prepare($sql);

    $stmt->bindParam(':name', $name);
    $stmt->bindParam(':email', $email);
    $stmt->bindParam(':pass', $password_hash); // ✅ Usamos a versão encriptada
    $stmt->bindParam(':role', $role);

    if ($stmt->execute()) {
        echo json_encode(["status" => "sucesso", "mensagem" => "Criado com sucesso!"]);
    } else {
        echo json_encode(["status" => "erro", "mensagem" => "Erro ao criar."]);
    }
} else {
    echo json_encode(["status" => "erro", "mensagem" => "Dados incompletos."]);
}
?>