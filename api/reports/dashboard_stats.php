<?php
require __DIR__ . '/../../config/db.php';
require __DIR__ . '/../../config/middleware.php';

$auth_user = authenticate($conn);

// Usa o ID do utilizador autenticado
$user_id = $auth_user['id'];

try {
    // 1. Salas disponíveis hoje (query otimizada sem DATE())
    $query_rooms = "SELECT COUNT(r.id) as total 
                    FROM rooms r
                    LEFT JOIN reservations res ON r.id = res.rooms_id 
                      AND res.start_time >= CURDATE() 
                      AND res.start_time < CURDATE() + INTERVAL 1 DAY
                      AND res.status != 'cancelled' 
                    WHERE r.is_active = 1 AND res.id IS NULL";

    $stmt_rooms = $conn->prepare($query_rooms);
    $stmt_rooms->execute();
    $rooms = $stmt_rooms->fetch(PDO::FETCH_ASSOC)['total'];

    // 2. Minhas reservas hoje
    $query_res = "SELECT COUNT(*) as total FROM reservations 
                  WHERE users_id = :uid 
                  AND start_time >= CURDATE() 
                  AND start_time < CURDATE() + INTERVAL 1 DAY";
    $stmt_res = $conn->prepare($query_res);
    $stmt_res->bindParam(":uid", $user_id, PDO::PARAM_INT);
    $stmt_res->execute();
    $reservations_today = $stmt_res->fetch(PDO::FETCH_ASSOC)['total'];

    // 3. Total histórico de reservas
    $query_total = "SELECT COUNT(*) as total FROM reservations WHERE users_id = :uid";
    $stmt_total = $conn->prepare($query_total);
    $stmt_total->bindParam(":uid", $user_id, PDO::PARAM_INT);
    $stmt_total->execute();
    $total_reservations = $stmt_total->fetch(PDO::FETCH_ASSOC)['total'];

    // 4. Utilizadores ativos
    $query_users = "SELECT COUNT(*) as total FROM users WHERE is_active = 1";
    $stmt_users = $conn->prepare($query_users);
    $stmt_users->execute();
    $users = $stmt_users->fetch(PDO::FETCH_ASSOC)['total'];

    // 5. Atividades recentes (admin vê tudo, user vê só as suas)
    $activities = [];

    if ($auth_user['role'] === 'admin' || $auth_user['role'] === 'funcionario') {
        $query_logs = "SELECT l.action_type, l.description, l.created_at, u.name as user_name 
                       FROM activity_logs l
                       LEFT JOIN users u ON l.user_id = u.id
                       ORDER BY l.created_at DESC LIMIT 10";
        $stmt_logs = $conn->prepare($query_logs);
        $stmt_logs->execute();
    } else {
        $query_logs = "SELECT l.action_type, l.description, l.created_at, 'Eu' as user_name 
                       FROM activity_logs l
                       WHERE l.user_id = :uid
                       ORDER BY l.created_at DESC LIMIT 10";
        $stmt_logs = $conn->prepare($query_logs);
        $stmt_logs->bindParam(":uid", $user_id, PDO::PARAM_INT);
        $stmt_logs->execute();
    }

    while ($row = $stmt_logs->fetch(PDO::FETCH_ASSOC)) {
        $activities[] = [
            'user' => $row['user_name'] ?? 'Desconhecido',
            'action' => $row['action_type'],
            'target' => $row['description'],
            'time' => $row['created_at'],
            'type' => strtolower($row['action_type'])
        ];
    }

    // 6. Top 5 salas mais populares
    $query_chart = "SELECT r.name, COUNT(res.id) as reservas 
                    FROM rooms r
                    LEFT JOIN reservations res ON r.id = res.rooms_id 
                    WHERE r.is_active = 1
                    GROUP BY r.id 
                    ORDER BY reservas DESC LIMIT 5";
    $stmt_chart = $conn->prepare($query_chart);
    $stmt_chart->execute();
    $chart_data = $stmt_chart->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode([
        "rooms" => $rooms,
        "reservations_today" => $reservations_today,
        "total_reservations" => $total_reservations,
        "users" => $users,
        "recent_activities" => $activities,
        "chart_data" => $chart_data
    ]);

} catch (PDOException $e) {
    error_log("Erro no dashboard: " . $e->getMessage());
    json_error("Erro interno do servidor.", 500);
}
?>