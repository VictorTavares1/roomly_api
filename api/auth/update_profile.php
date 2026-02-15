<?php
// ==========================================
// 🕵️‍♂️ UPDATE PROFILE - MODO DEBUG SUPREMO
// ==========================================
// 1. Permitir tudo (CORS) para não haver bloqueios
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: POST");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

// 2. Tentar ligar à Base de Dados e apanhar erros logo aqui
try {
    require '../../config/db.php';
} catch (Exception $e) {
    echo json_encode(["status" => "erro", "mensagem" => "Erro ao incluir db.php: " . $e->getMessage()]);
    exit();
}

// 3. Receber o que o React enviou
$raw_input = file_get_contents("php://input");
$data = json_decode($raw_input);

// === 📝 O DIÁRIO DE BORDO (LOG) ===
// Vamos gravar tudo num ficheiro de texto para tu leres
$log = "--- NOVO PEDIDO: " . date("Y-m-d H:i:s") . " ---\n";
$log .= "Recebido do React: " . $raw_input . "\n";
$log .= "Decodificado: " . print_r($data, true) . "\n";

// Verificar ID
if (!isset($data->id)) {
    $log .= "❌ ERRO: O ID veio vazio ou nulo!\n";
    file_put_contents("debug_log.txt", $log, FILE_APPEND);
    echo json_encode(["status" => "erro", "mensagem" => "O ID do utilizador não chegou ao servidor."]);
    exit();
}

$log .= "✅ ID recebido: " . $data->id . "\n";

// Verificar Ligação DB
if (!$conn) {
    $log .= "❌ ERRO: A variável \$conn não existe (falha no db.php)!\n";
    file_put_contents("debug_log.txt", $log, FILE_APPEND);
    echo json_encode(["status" => "erro", "mensagem" => "Sem conexão à BD."]);
    exit();
}

try {
    // Tentar Atualizar
    $query = "UPDATE users SET name = :name, email = :email WHERE id = :id";
    $stmt = $conn->prepare($query);

    $stmt->bindParam(":name", $data->name);
    $stmt->bindParam(":email", $data->email);
    $stmt->bindParam(":id", $data->id);

    if ($stmt->execute()) {
        $log .= "✅ SUCESSO: SQL executado. Linhas afetadas: " . $stmt->rowCount() . "\n";

        // Buscar user atualizado
        $stmtCheck = $conn->prepare("SELECT * FROM users WHERE id = :id");
        $stmtCheck->bindParam(":id", $data->id);
        $stmtCheck->execute();
        $updatedUser = $stmtCheck->fetch(PDO::FETCH_ASSOC);

        // Se não encontrar o user (mesmo depois de atualizar)
        if (!$updatedUser) {
            $log .= "⚠️ ALERTA: Atualizou mas não conseguiu ler de volta (ID incorreto?).\n";
        }

        file_put_contents("debug_log.txt", $log, FILE_APPEND);

        // Remover senha
        unset($updatedUser['password']);

        echo json_encode(["status" => "sucesso", "user" => $updatedUser]);

    } else {
        $erro = $stmt->errorInfo();
        $log .= "❌ ERRO SQL: " . print_r($erro, true) . "\n";
        file_put_contents("debug_log.txt", $log, FILE_APPEND);
        echo json_encode(["status" => "erro", "mensagem" => "Erro SQL: " . $erro[2]]);
    }

} catch (PDOException $e) {
    $log .= "💀 EXCEÇÃO FATAL: " . $e->getMessage() . "\n";
    file_put_contents("debug_log.txt", $log, FILE_APPEND);
    echo json_encode(["status" => "erro", "mensagem" => "Erro Fatal: " . $e->getMessage()]);
}
?>