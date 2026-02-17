<?php
require '../../config/db.php';

$user_id = isset($_GET['user_id']) ? $_GET['user_id'] : null;

if (!$user_id) {
    echo json_encode(["error" => "ID de utilizador necessário"]);
    exit();
}

try {
    // ---------------------------------------------------------
    // 1. Contar Salas DISPONÍVEIS Hoje
    // ---------------------------------------------------------
    $query_rooms = "SELECT COUNT(r.id) as total 
                    FROM rooms r
                    LEFT JOIN reservations res 
                    ON r.id = res.rooms_id 
                    AND DATE(res.start_time) = CURDATE()
                    AND res.status != 'cancelled' 
                    WHERE res.id IS NULL";

    $stmt_rooms = $conn->prepare($query_rooms);
    $stmt_rooms->execute();
    $rooms = $stmt_rooms->fetch(PDO::FETCH_ASSOC)['total'];

    // ---------------------------------------------------------
    // 2. Contar Minhas Reservas (Hoje)
    // ---------------------------------------------------------
    $query_res = "SELECT COUNT(*) as total FROM reservations 
                  WHERE users_id = :uid AND DATE(start_time) = CURDATE()";
    $stmt_res = $conn->prepare($query_res);
    $stmt_res->bindParam(":uid", $user_id);
    $stmt_res->execute();
    $reservations_today = $stmt_res->fetch(PDO::FETCH_ASSOC)['total'];

    // ---------------------------------------------------------
    // 3. Contar TOTAL Histórico de Reservas (KPI do Utilizador)
    // ---------------------------------------------------------
    $query_total = "SELECT COUNT(*) as total FROM reservations WHERE users_id = :uid";
    $stmt_total = $conn->prepare($query_total);
    $stmt_total->bindParam(":uid", $user_id);
    $stmt_total->execute();
    $total_reservations = $stmt_total->fetch(PDO::FETCH_ASSOC)['total'];

    // ---------------------------------------------------------
    // 4. Contar Utilizadores Ativos (KPI Global)
    // ---------------------------------------------------------
    $query_users = "SELECT COUNT(*) as total FROM users WHERE is_active = 1";
    $stmt_users = $conn->prepare($query_users);
    $stmt_users->execute();
    $users = $stmt_users->fetch(PDO::FETCH_ASSOC)['total'];

    // ---------------------------------------------------------
    // 5. Buscar Atividades Recentes (LÓGICA HÍBRIDA ADMIN/USER)
    // ---------------------------------------------------------

    // Passo A: Descobrir quem é este utilizador (Admin ou Normal?)
    $stmt_role = $conn->prepare("SELECT role FROM users WHERE id = :uid");
    $stmt_role->bindParam(":uid", $user_id);
    $stmt_role->execute();
    $current_role = $stmt_role->fetchColumn(); // ex: 'admin', 'professor'

    $activities = [];

    // Passo B: Executar a query correta baseada no cargo
    if ($current_role === 'admin' || $current_role === 'funcionario') {
        // === MODO ADMIN: Vê tudo de todos ===
        $query_logs = "SELECT l.action_type, l.description, l.created_at, u.name as user_name 
                       FROM activity_logs l
                       LEFT JOIN users u ON l.user_id = u.id
                       ORDER BY l.created_at DESC 
                       LIMIT 10";
        $stmt_logs = $conn->prepare($query_logs);
        // Não filtramos por ID aqui, queremos ver geral
        $stmt_logs->execute();
    } else {
        // === MODO UTILIZADOR: Vê apenas o seu ===
        $query_logs = "SELECT l.action_type, l.description, l.created_at, 'Eu' as user_name 
                       FROM activity_logs l
                       WHERE l.user_id = :uid
                       ORDER BY l.created_at DESC 
                       LIMIT 10";
        $stmt_logs = $conn->prepare($query_logs);
        $stmt_logs->bindParam(":uid", $user_id);
        $stmt_logs->execute();
    }

    // Passo C: Formatar dados
    while ($row = $stmt_logs->fetch(PDO::FETCH_ASSOC)) {
        $activities[] = [
            'user' => $row['user_name'] ?? 'Desconhecido', // Nome do user ou 'Eu'
            'action' => $row['action_type'],
            'target' => $row['description'],
            'time' => $row['created_at'],
            'type' => strtolower($row['action_type']) // para cores no CSS
        ];
    }

    // ---------------------------------------------------------
    // 6. Dados para o Gráfico (Top 5 Salas)
    // ---------------------------------------------------------
    $query_chart = "SELECT r.name, COUNT(res.id) as reservas 
                    FROM rooms r
                    LEFT JOIN reservations res ON r.id = res.rooms_id 
                    GROUP BY r.id 
                    ORDER BY reservas DESC 
                    LIMIT 5";
    $stmt_chart = $conn->prepare($query_chart);
    $stmt_chart->execute();
    $chart_data = $stmt_chart->fetchAll(PDO::FETCH_ASSOC);

    // ---------------------------------------------------------
    // RETORNO FINAL
    // ---------------------------------------------------------
    echo json_encode([
        "rooms" => $rooms,
        "reservations_today" => $reservations_today,
        "total_reservations" => $total_reservations,
        "users" => $users,
        "recent_activities" => $activities,
        "chart_data" => $chart_data
    ]);

} catch (PDOException $e) {
    echo json_encode(["error" => "Erro SQL: " . $e->getMessage()]);
}
?>