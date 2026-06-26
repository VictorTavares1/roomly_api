<?php
date_default_timezone_set('Europe/Lisbon');
require __DIR__ . '/../../config/db.php';
require __DIR__ . '/../../config/middleware.php';

$auth_user = authenticate($conn);

// Carregar a API key do .env
$env_path = __DIR__ . '/../../.env';
$api_key = '';
if (file_exists($env_path)) {
    $lines = file($env_path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        if (strpos($line, 'GROQ_API_KEY=') === 0) {
            $api_key = trim(substr($line, strlen('GROQ_API_KEY=')));
        }
    }
}

if (empty($api_key)) {
    json_error("API key não configurada.", 500);
}

$data = get_json_body();
if (empty($data['message'])) {
    json_error("Mensagem vazia.", 400);
}

$user_message = $data['message'];
$history = array_slice($data['history'] ?? [], -10);
$today = date('Y-m-d');
$tomorrow = date('Y-m-d', strtotime('+1 day'));
$now = date('H:i');

// Buscar salas
$stmt = $conn->query("SELECT id, name, capacity, type, has_projector FROM rooms WHERE is_active = 1 ORDER BY name");
$rooms = $stmt->fetchAll(PDO::FETCH_ASSOC);

$rooms_list = "";
foreach ($rooms as $r) {
    $projector = $r['has_projector'] ? ", tem projetor" : ", sem projetor";
    $rooms_list .= "  - room_id={$r['id']} | nome=\"{$r['name']}\" | capacidade={$r['capacity']} | tipo={$r['type']}{$projector}\n";
}

// Buscar reservas futuras do utilizador
$stmt = $conn->prepare("
    SELECT res.id, r.name as room_name, res.start_time, res.end_time, res.purpose
    FROM reservations res
    JOIN rooms r ON res.rooms_id = r.id
    WHERE res.users_id = :uid AND res.status != 'cancelada' AND res.end_time >= NOW()
    ORDER BY res.start_time ASC LIMIT 10
");
$stmt->bindParam(':uid', $auth_user['id'], PDO::PARAM_INT);
$stmt->execute();
$my_reservations = $stmt->fetchAll(PDO::FETCH_ASSOC);

$reservations_list = "";
if (empty($my_reservations)) {
    $reservations_list = "  Nenhuma reserva futura.";
} else {
    foreach ($my_reservations as $res) {
        $reservations_list .= "  - reservation_id={$res['id']} | sala=\"{$res['room_name']}\" | início={$res['start_time']} | fim={$res['end_time']} | motivo=\"{$res['purpose']}\"\n";
    }
}

$system_prompt = <<<PROMPT
És o assistente de reservas do sistema Roomly. Respondes SEMPRE em português de Portugal.
Utilizador: {$auth_user['name']}
Data de hoje: {$today} | Amanhã: {$tomorrow} | Hora atual: {$now}

=== SALAS DISPONÍVEIS (os room_id são apenas para uso interno — NUNCA os mostres ao utilizador) ===
{$rooms_list}
=== RESERVAS FUTURAS DO UTILIZADOR (os reservation_id são apenas para uso interno — NUNCA os mostres) ===
{$reservations_list}

=== REGRAS DE PRIVACIDADE — ABSOLUTAMENTE OBRIGATÓRIAS ===
- NUNCA mostres room_id, reservation_id, user_id, ou qualquer número de ID nas respostas de texto
- NUNCA uses o formato "room_id=X", "nome=X", "capacidade=X" nas respostas — isso é formato interno
- Quando falares de salas, usa APENAS o nome (ex: "Sala 1"), capacidade e tipo
- Quando falares de reservas, usa APENAS o nome da sala, data e hora
- Os IDs existem só para o JSON de ação — em texto nunca aparecem

=== O TEU ÂMBITO É ESTRITAMENTE O SEGUINTE ===
Só podes ajudar com:
- Criar reservas de salas
- Consultar salas disponíveis (tipo, capacidade, projetor)
- Informar sobre as reservas futuras do utilizador
- Orientar sobre como cancelar/editar reservas manualmente

Se o utilizador perguntar QUALQUER coisa fora deste âmbito (história, geografia, ciência, entretenimento, programação, ou qualquer outro tema), responde APENAS com:
"Só posso ajudar com reservas e informações sobre as salas do Roomly."
Não respondas, não expliques, não te desculpes em detalhe — apenas essa frase.

=== A TUA ÚNICA AÇÃO POSSÍVEL ===
Só podes CRIAR reservas. Responde APENAS com este JSON puro (sem texto antes ou depois):
{"action":"create_reservation","room_id":NUMERO_EXATO_DA_LISTA_ACIMA,"date":"YYYY-MM-DD","start_time":"HH:MM","end_time":"HH:MM","purpose":"motivo"}

Para perguntas dentro do âmbito (disponibilidade, informações de salas, etc.), responde normalmente em texto.

=== REGRAS ABSOLUTAS ===
- NUNCA cancelas, editas ou apagues reservas — essa responsabilidade é do utilizador manualmente
- Se o utilizador pedir para cancelar uma reserva, informa educadamente que deve fazê-lo manualmente em "Minhas Reservas"
- USA SEMPRE o room_id numérico da lista acima no JSON, NUNCA o nome da sala como ID
- Se o utilizador disser "sala 1", procura na lista qual sala tem "1" no nome e usa o seu room_id no JSON
- Se faltarem dados (sala, data ou hora), OBRIGATORIAMENTE pergunta antes de agir — NUNCA inventes uma hora
- Não inventes salas que não existam na lista
- Responde diretamente com o JSON, sem confirmar primeiro
- A reserva criada fica com estado PENDENTE — o utilizador tem obrigatoriamente de se dirigir à sala e fazer scan do QR Code para confirmar a presença. Sem o scan, a reserva expira automaticamente.

PROMPT;

// Construir histórico de mensagens
$messages = [["role" => "system", "content" => $system_prompt]];
foreach ($history as $h) {
    if (!empty($h['role']) && !empty($h['text'])) {
        $role = $h['role'] === 'user' ? 'user' : 'assistant';
        $messages[] = ["role" => $role, "content" => $h['text']];
    }
}
$messages[] = ["role" => "user", "content" => $user_message];

$payload = json_encode([
    "model" => "llama-3.3-70b-versatile",
    "messages" => $messages,
    "max_tokens" => 400,
    "temperature" => 0.1
]);

$ch = curl_init("https://api.groq.com/openai/v1/chat/completions");
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    "Content-Type: application/json",
    "Authorization: Bearer {$api_key}"
]);
curl_setopt($ch, CURLOPT_TIMEOUT, 15);
curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 5);

$response = curl_exec($ch);
$http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if (!$response) {
    json_error("Erro ao contactar o assistente.", 500);
}

$result = json_decode($response, true);

if ($http_code !== 200 || empty($result['choices'][0]['message']['content'])) {
    error_log("Groq API error: " . $response);
    json_error("Erro na resposta do assistente.", 500);
}

$assistant_reply = trim($result['choices'][0]['message']['content']);

// Tentar extrair JSON de ação mesmo que venha com texto à volta
if (preg_match('/\{[^{}]*"action"\s*:\s*"create_reservation"[^{}]*\}/s', $assistant_reply, $matches)) {
    $decoded = json_decode($matches[0], true);
    if (json_last_error() === JSON_ERROR_NONE && isset($decoded['action'])) {
        echo json_encode(["type" => "action", "action" => $decoded]);
        exit;
    }
}

// Limpar markdown code blocks e tentar parse normal
$assistant_reply = preg_replace('/^```json\s*/i', '', $assistant_reply);
$assistant_reply = preg_replace('/^```\s*/i', '', $assistant_reply);
$assistant_reply = preg_replace('/\s*```$/i', '', $assistant_reply);
$assistant_reply = trim($assistant_reply);

$decoded = json_decode($assistant_reply, true);
if (json_last_error() === JSON_ERROR_NONE && isset($decoded['action']) && $decoded['action'] === 'create_reservation') {
    echo json_encode(["type" => "action", "action" => $decoded]);
} else {
    echo json_encode(["type" => "message", "message" => $assistant_reply]);
}
?>
