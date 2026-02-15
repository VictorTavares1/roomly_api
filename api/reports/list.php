<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
require '../../config/db.php';

// Aqui fazemos a ligação (JOIN) usando os nomes corretos da tua tabela
$query = "SELECT r.*, u.name as user_name, rm.name as room_name 
          FROM reports r
          JOIN users u ON r.users_id = u.id  
          JOIN rooms rm ON r.rooms_id = rm.id
          ORDER BY r.id DESC"; // Ordenar pelos mais recentes

$stmt = $conn->prepare($query);
$stmt->execute();

echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));
?>