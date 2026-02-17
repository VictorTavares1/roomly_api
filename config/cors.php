<?php
// cors.php
// Centraliza os headers de segurança e permissão de acesso (CORS)

// Evita output antes dos headers
if (ob_get_level() == 0)
    ob_start();

header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With");
header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");
header("Content-Type: application/json; charset=UTF-8");

// Se for um pedido OPTIONS (pre-flight do browser), responde OK e para por aqui
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}
?>