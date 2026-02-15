<?php
// api/config/logger.php

function logActivity($conn, $userId, $actionType, $description)
{
    if (!$userId || !$actionType || !$description) {
        return;
    }

    try {
        $sql = "INSERT INTO activity_logs (user_id, action_type, description, created_at) VALUES (:uid, :action, :desc, NOW())";

        $stmt = $conn->prepare($sql);
        $stmt->bindParam(':uid', $userId);
        $stmt->bindParam(':action', $actionType);
        $stmt->bindParam(':desc', $description);

        $stmt->execute();
    } catch (PDOException $e) {
        // Regista o erro no log do servidor (apache/nginx error log) para não partir o JSON do frontend
        error_log("Erro ao registar log: " . $e->getMessage());
    }
}
?>