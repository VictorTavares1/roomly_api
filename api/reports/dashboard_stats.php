<?php
require __DIR__ . '/../../config/db.php';
require __DIR__ . '/../../config/middleware.php';

$auth_user = authenticate($conn);

// Usa o ID do utilizador autenticado
$user_id = $auth_user['id'];

try {
    // 1. Salas disponíveis (ativas e não em manutenção)
    $query_rooms = "SELECT COUNT(id) as total FROM rooms WHERE is_active = 1";

    $stmt_rooms = $conn->prepare($query_rooms);
    $stmt_rooms->execute();
    $rooms = $stmt_rooms->fetch(PDO::FETCH_ASSOC)['total'];

    // 2. Minhas reservas hoje (apenas futuras/em curso)
    $query_res = "SELECT COUNT(*) as total FROM reservations
                  WHERE users_id = :uid
                  AND start_time >= CURDATE()
                  AND start_time < CURDATE() + INTERVAL 1 DAY
                  AND end_time >= NOW()";
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

    // 4b. Problemas reportados (não resolvidos)
    $query_reports = "SELECT COUNT(*) as total FROM reports WHERE status IN ('aberto', 'em_progresso')";
    $stmt_reports = $conn->prepare($query_reports);
    $stmt_reports->execute();
    $reported_problems = $stmt_reports->fetch(PDO::FETCH_ASSOC)['total'];

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

    $action_labels = [
        'reserva'           => 'Reserva confirmada',
        'cancelamento'      => 'Reserva cancelada',
        'alteracao'         => 'Reserva alterada',
        'reporte'           => 'Problema reportado',
        'alteracao_reporte' => 'Reporte atualizado',
        'admin_delete'      => 'Reserva eliminada (admin)',
        'nova_sala'         => 'Sala criada',
        'alteracao_sala'    => 'Sala atualizada',
        'remocao_sala'      => 'Sala desativada',
        'novo_utilizador'   => 'Utilizador criado',
        'alteracao_status'  => 'Estado de conta alterado',
        'alteracao_cargo'   => 'Cargo atualizado',
    ];

    while ($row = $stmt_logs->fetch(PDO::FETCH_ASSOC)) {
        $type = strtolower($row['action_type']);
        $activities[] = [
            'user'         => $row['user_name'] ?? 'Desconhecido',
            'action'       => $row['action_type'],
            'action_label' => $action_labels[$type] ?? $row['action_type'],
            'target'       => $row['description'],
            'time'         => $row['created_at'],
            'type'         => $type,
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
        "reported_problems" => $reported_problems,
        "recent_activities" => $activities,
        "chart_data" => $chart_data
    ]);

} catch (PDOException $e) {
    error_log("Erro no dashboard: " . $e->getMessage());
    json_error("Erro interno do servidor.", 500);
}
?>