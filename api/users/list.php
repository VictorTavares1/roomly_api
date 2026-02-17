<?php
require '../../config/db.php';

$sql = "SELECT id, name, email, role, is_active FROM users ORDER BY is_active DESC, name ASC";

$stmt = $conn->prepare($sql);
$stmt->execute();

$users = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo json_encode($users);
?>