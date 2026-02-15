<?php
// ==========================================
// 🛡️ ZONA DE SEGURANÇA (CORS)
// ==========================================
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: Content-Type");
header("Content-Type: application/json; charset=UTF-8");

// Responde a pedidos OPTIONS (Pre-flight do browser)
if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    http_response_code(200);
    exit();
}

// ==========================================
// 🔌 CONEXÃO E PROCESSAMENTO
// ==========================================
require '../../config/db.php';

// Lê os dados enviados pelo React
$data = json_decode(file_get_contents("php://input"));

if (isset($data->email) && isset($data->password)) {
    $email = $data->email;
    $password_enviada = $data->password;

    try {
        // 1. Procura o utilizador pelo email
        $query = "SELECT * FROM users WHERE email = :email LIMIT 1";
        $stmt = $conn->prepare($query);
        $stmt->bindParam(":email", $email);
        $stmt->execute();

        if ($stmt->rowCount() > 0) {
            $row = $stmt->fetch(PDO::FETCH_ASSOC);

            // 2. Verifica se a conta está ativa
            if (isset($row['is_active']) && $row['is_active'] == 0) {
                echo json_encode(["status" => "erro", "mensagem" => "Conta desativada."]);
                exit();
            }

            // 3. VERIFICAÇÃO HÍBRIDA (Hash OU Texto Simples)
            // Tenta validar por hash (seguro) OU comparação direta (teste)
            $senha_na_bd = $row['password'];

            $login_valido = false;

            if (password_verify($password_enviada, $senha_na_bd)) {
                $login_valido = true; // Bateu com o hash
            }

            if ($login_valido) {
                // Sucesso! Removemos a senha do objeto antes de enviar para o React
                unset($row['password']);

                echo json_encode([
                    "status" => "sucesso",
                    "mensagem" => "Login realizado com sucesso!",
                    "user" => $row
                ]);
            } else {
                // Se chegar aqui, as senhas realmente não coincidem
                echo json_encode([
                    "status" => "erro",
                    "mensagem" => "Senha incorreta.",
                    "debug_info" => "A senha enviada não coincide com o que está na BD."
                ]);
            }

        } else {
            echo json_encode(["status" => "erro", "mensagem" => "Email não encontrado."]);
        }

    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(["status" => "erro", "mensagem" => "Erro no servidor: " . $e->getMessage()]);
    }
} else {
    echo json_encode(["status" => "erro", "mensagem" => "Dados insuficientes (Email/Senha)."]);
}
?>