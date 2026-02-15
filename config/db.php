<?php
require_once __DIR__ . '/cors.php';

// Dados do XAMPP
$host = 'localhost';
$db_name = 'roomly';
$username = 'root';
$password = '';

try {
    $conn = new PDO("mysql:host=$host;dbname=$db_name;charset=utf8", $username, $password);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

} catch (PDOException $e) {

    echo json_encode([
        "status" => "erro",
        "mensagem" => "Erro fatal na BD: " . $e->getMessage()
    ]);
    exit;
}
?>