<?php

header("Content-Type: application/json");

require_once __DIR__ . "/../config/cors.php";
require_once __DIR__ . "/../config/database.php";

// Solo permitir GET
if ($_SERVER["REQUEST_METHOD"] !== "GET") {
    http_response_code(405);

    echo json_encode([
        "success" => false,
        "message" => "Método no permitido"
    ]);

    exit;
}

// Obtener Authorization
$headers = getallheaders();
$authorization = $headers["Authorization"] ?? "";

// Verificar Bearer Token
if (!preg_match('/Bearer\s+(\S+)/', $authorization, $matches)) {
    http_response_code(401);

    echo json_encode([
        "success" => false,
        "message" => "Token de autenticación requerido"
    ]);

    exit;
}

$token = $matches[1];
$tokenHash = hash("sha256", $token);

// Buscar al usuario autenticado
$stmt = $pdo->prepare(
    "SELECT
        u.id,
        u.status,
        u.role,
        s.expires_at
     FROM sessions s
     INNER JOIN users u ON s.user_id = u.id
     WHERE s.token_hash = :token_hash
     LIMIT 1"
);

$stmt->execute([
    ":token_hash" => $tokenHash
]);

$currentUser = $stmt->fetch(PDO::FETCH_ASSOC);

// Verificar sesión
if (!$currentUser) {
    http_response_code(401);

    echo json_encode([
        "success" => false,
        "message" => "Sesión inválida"
    ]);

    exit;
}

// Verificar expiración
if (strtotime($currentUser["expires_at"]) <= time()) {
    http_response_code(401);

    echo json_encode([
        "success" => false,
        "message" => "La sesión ha expirado"
    ]);

    exit;
}

// Verificar cuenta activa
if ($currentUser["status"] !== "active") {
    http_response_code(403);

    echo json_encode([
        "success" => false,
        "message" => "La cuenta no tiene acceso al sistema"
    ]);

    exit;
}

// Verificar permisos
if ($currentUser["role"] !== "admin") {
    http_response_code(403);

    echo json_encode([
        "success" => false,
        "message" => "No tienes permisos para consultar usuarios"
    ]);

    exit;
}

// Obtener el id enviado en la URL
$id = $_GET["id"] ?? "";

// Validar id
if (
    $id === "" ||
    filter_var($id, FILTER_VALIDATE_INT) === false ||
    (int) $id <= 0
) {
    http_response_code(422);

    echo json_encode([
        "success" => false,
        "message" => "El id del usuario no es válido"
    ]);

    exit;
}

$id = (int) $id;

// Buscar usuario
$stmt = $pdo->prepare(
    "SELECT
        id,
        name,
        username,
        email,
        status,
        role
     FROM users
     WHERE id = :id
     LIMIT 1"
);

$stmt->execute([
    ":id" => $id
]);

$user = $stmt->fetch(PDO::FETCH_ASSOC);

// Verificar existencia
if (!$user) {
    http_response_code(404);

    echo json_encode([
        "success" => false,
        "message" => "Usuario no encontrado"
    ]);

    exit;
}

$user["id"] = (int) $user["id"];

// Respuesta
http_response_code(200);

echo json_encode([
    "success" => true,
    "user" => $user
]);