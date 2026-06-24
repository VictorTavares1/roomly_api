<?php
require __DIR__ . '/../../config/db.php';
require __DIR__ . '/../../config/middleware.php';

$auth_user = authenticate($conn);

$user_id     = $auth_user['id'];
$room_id     = isset($_POST['room_id']) ? validate_positive_int($_POST['room_id'], 'room_id') : null;
$description = isset($_POST['description']) ? htmlspecialchars(strip_tags($_POST['description'])) : '';

if (!$room_id || !$description) {
    json_error("Campos obrigatórios em falta: room_id, description.", 400);
}

$image_path = null;

if (!empty($_FILES['image']['name'])) {
    $file     = $_FILES['image'];
    $max_size = 5 * 1024 * 1024; // 5 MB

    $allowed_mime = ['image/jpeg', 'image/png', 'image/webp', 'image/gif'];
    $finfo        = new finfo(FILEINFO_MIME_TYPE);
    $mime         = $finfo->file($file['tmp_name']);

    if (!in_array($mime, $allowed_mime, true)) {
        json_error("Tipo de ficheiro não permitido. Usa JPG, PNG ou WebP.", 400);
    }

    if ($file['size'] > $max_size) {
        json_error("A imagem não pode ultrapassar 5 MB.", 400);
    }

    $ext      = pathinfo($file['name'], PATHINFO_EXTENSION);
    $filename = 'report_' . $user_id . '_' . time() . '_' . bin2hex(random_bytes(4)) . '.' . strtolower($ext);
    $upload_dir = __DIR__ . '/../../uploads/reports/';

    if (!is_dir($upload_dir)) {
        mkdir($upload_dir, 0755, true);
    }

    if (!move_uploaded_file($file['tmp_name'], $upload_dir . $filename)) {
        json_error("Erro ao guardar a imagem.", 500);
    }

    $image_path = 'uploads/reports/' . $filename;
}

try {
    $query = "INSERT INTO reports (users_id, rooms_id, description, image_path, status) VALUES (:uid, :rid, :desc, :img, 'aberto')";
    $stmt  = $conn->prepare($query);
    $stmt->bindParam(':uid',  $user_id,    PDO::PARAM_INT);
    $stmt->bindParam(':rid',  $room_id,    PDO::PARAM_INT);
    $stmt->bindParam(':desc', $description);
    $stmt->bindParam(':img',  $image_path);

    if ($stmt->execute()) {
        require_once __DIR__ . '/../../config/logger.php';
        $stmt_room = $conn->prepare("SELECT name FROM rooms WHERE id = :rid");
        $stmt_room->bindParam(':rid', $room_id, PDO::PARAM_INT);
        $stmt_room->execute();
        $room_name = $stmt_room->fetchColumn() ?: "Sala #$room_id";
        logActivity($conn, $user_id, 'reporte', "Avaria reportada em \"$room_name\"");

        json_success("Reporte criado com sucesso!", [], 201);
    } else {
        json_error("Erro ao guardar reporte.", 500);
    }

} catch (PDOException $e) {
    error_log("Erro ao criar reporte: " . $e->getMessage());
    json_error("Erro interno do servidor.", 500);
}
?>
