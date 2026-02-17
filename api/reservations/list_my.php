<?php
require '../../config/db.php';

// Prevenção de erro: verifica se a variável se chama $conn ou $pdo no db.php
$db = isset($conn) ? $conn : $pdo;

// Receber o ID do utilizador pela URL
if (isset($_GET['user_id'])) {
    $user_id = $_GET['user_id'];

    // SQL PODEROSO (ATUALIZADO): 
    // 1. Vai à tabela reservations (r)
    // 2. Junta com a tabela rooms (rm) para sabermos o nome da sala
    // 3. Filtra pelo user E pelo tempo (end_time >= NOW())
    $sql = "SELECT r.*, rm.name as room_name 
            FROM reservations r
            JOIN rooms rm ON r.rooms_id = rm.id
            WHERE r.users_id = :uid
            AND r.end_time >= NOW()   -- <-- O TRUQUE ESTÁ AQUI: Esconde o passado!
            ORDER BY r.start_time ASC"; // Mudei para ASC (do mais cedo para o mais tarde)

    $stmt = $db->prepare($sql);
    $stmt->bindParam(':uid', $user_id);
    $stmt->execute();

    $reservations = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode($reservations);
} else {
    echo json_encode([]);
}
?>