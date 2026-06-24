<?php
// SSE — Server-Sent Events
// Envia atualizações em tempo real para o frontend quando há mudanças

require_once __DIR__ . '/../config/db.php';

// Headers SSE (substituem o Content-Type do cors.php)
header("Content-Type: text/event-stream");
header("Cache-Control: no-cache");
header("X-Accel-Buffering: no");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: Authorization");

// Autenticar via query param (SSE não suporta headers custom facilmente)
$token = $_GET['token'] ?? '';
if (empty($token)) {
    echo "event: error\ndata: {\"mensagem\":\"Token não fornecido.\"}\n\n";
    exit;
}

try {
    $stmt = $conn->prepare("SELECT id, role FROM users WHERE token = :token AND is_active = 1 LIMIT 1");
    $stmt->bindParam(':token', $token);
    $stmt->execute();
    $auth_user = $stmt->fetch(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    echo "event: error\ndata: {\"mensagem\":\"Erro de autenticação.\"}\n\n";
    exit;
}

if (!$auth_user) {
    echo "event: error\ndata: {\"mensagem\":\"Sessão inválida.\"}\n\n";
    exit;
}

// Desativar output buffering para envio imediato
if (ob_get_level()) ob_end_clean();
set_time_limit(0);
ignore_user_abort(false);

// Estado inicial — guardar contagens para detetar mudanças
function getSnapshot($conn, $user_id, $role) {
    $snap = [];

    // Total de reservas ativas (não canceladas, não passadas)
    $stmt = $conn->query("SELECT COUNT(*) FROM reservations WHERE status != 'cancelada' AND end_time > NOW()");
    $snap['active_reservations'] = (int) $stmt->fetchColumn();

    // Última reserva criada/modificada
    $stmt = $conn->query("SELECT MAX(id) FROM reservations");
    $snap['last_reservation_id'] = (int) $stmt->fetchColumn();

    // Último report
    $stmt = $conn->query("SELECT MAX(id) FROM reports");
    $snap['last_report_id'] = (int) $stmt->fetchColumn();

    // Salas ativas
    $stmt = $conn->query("SELECT COUNT(*) FROM rooms WHERE is_active = 1");
    $snap['available_rooms'] = (int) $stmt->fetchColumn();

    return $snap;
}

function sendEvent($event, $data) {
    echo "event: {$event}\n";
    echo "data: " . json_encode($data) . "\n\n";
    flush();
}

$lastSnapshot = getSnapshot($conn, $auth_user['id'], $auth_user['role']);

// Enviar estado inicial
sendEvent('connected', ['status' => 'ok', 'user_id' => $auth_user['id']]);

$pollInterval = 5; // segundos entre cada verificação
$maxRuntime = 55;  // segundos máximos (antes do timeout do Apache/PHP)
$startTime = time();

while (true) {
    // Fechar se o cliente desligou ou se atingiu o tempo máximo
    if (connection_aborted() || (time() - $startTime) >= $maxRuntime) {
        break;
    }

    sleep($pollInterval);

    try {
        $newSnapshot = getSnapshot($conn, $auth_user['id'], $auth_user['role']);
    } catch (PDOException $e) {
        break;
    }

    // Detetar o que mudou e notificar
    $changes = [];

    if ($newSnapshot['active_reservations'] !== $lastSnapshot['active_reservations'] ||
        $newSnapshot['last_reservation_id'] !== $lastSnapshot['last_reservation_id']) {
        $changes[] = 'reservations';
    }

    if ($newSnapshot['last_report_id'] !== $lastSnapshot['last_report_id']) {
        $changes[] = 'reports';
    }

    if ($newSnapshot['available_rooms'] !== $lastSnapshot['available_rooms']) {
        $changes[] = 'rooms';
    }

    if (!empty($changes)) {
        sendEvent('update', ['changed' => $changes, 'ts' => time()]);
        $lastSnapshot = $newSnapshot;
    } else {
        // Heartbeat para manter a ligação viva
        echo ": heartbeat\n\n";
        flush();
    }
}
?>
