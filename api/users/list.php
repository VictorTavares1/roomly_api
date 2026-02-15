<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
require '../../config/db.php';

// MUDANÇA: Removemos o "WHERE is_active = 1"
// E adicionámos a coluna "is_active" na lista
$sql = "SELECT id, name, email, role, is_active FROM users ORDER BY is_active DESC, name ASC";

$stmt = $conn->prepare($sql);
$stmt->execute();

$users = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo json_encode($users);
?>