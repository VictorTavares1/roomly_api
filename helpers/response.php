<?php
// helpers/response.php
// Funções centralizadas para respostas JSON e validação

/**
 * Responde com sucesso e termina o script.
 */
function json_success($msg = "Sucesso", $extra = [], $code = 200)
{
    http_response_code($code);
    echo json_encode(array_merge(["status" => "sucesso", "mensagem" => $msg], $extra));
    exit;
}

/**
 * Responde com erro e termina o script.
 */
function json_error($msg, $code = 400)
{
    http_response_code($code);
    echo json_encode(["status" => "erro", "mensagem" => $msg]);
    exit;
}

/**
 * Lê o body JSON do request e retorna como array associativo.
 */
function get_json_body()
{
    $raw = file_get_contents("php://input");
    $data = json_decode($raw, true);

    if ($data === null && json_last_error() !== JSON_ERROR_NONE) {
        json_error("Body JSON inválido.", 400);
    }

    return $data ?? [];
}

/**
 * Verifica se todos os campos obrigatórios existem.
 */
function require_fields($data, $fields)
{
    $missing = [];
    foreach ($fields as $field) {
        if (!isset($data[$field]) || (is_string($data[$field]) && trim($data[$field]) === '')) {
            $missing[] = $field;
        }
    }
    if (!empty($missing)) {
        json_error("Campos obrigatórios em falta: " . implode(", ", $missing), 400);
    }
}

/**
 * Valida se um valor pertence a uma whitelist.
 */
function validate_whitelist($value, $allowed, $field)
{
    if (!in_array($value, $allowed, true)) {
        json_error("Valor inválido para '$field'. Permitidos: " . implode(", ", $allowed), 400);
    }
}

/**
 * Valida se um valor é um inteiro positivo.
 */
function validate_positive_int($value, $field)
{
    $int = filter_var($value, FILTER_VALIDATE_INT, ["options" => ["min_range" => 1]]);
    if ($int === false) {
        json_error("'$field' deve ser um número inteiro positivo.", 400);
    }
    return $int;
}
?>