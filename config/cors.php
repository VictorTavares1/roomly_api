<?php
// cors.php — Headers CORS restritos a origens autorizadas

if (ob_get_level() == 0)
    ob_start();

header("Content-Type: application/json; charset=UTF-8");

// Preflight já tratado pelo .htaccess — mas por segurança:
if (isset($_SERVER['REQUEST_METHOD']) && $_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}
?>