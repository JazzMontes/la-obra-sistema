<?php

header("Content-Type: application/json");

require_once __DIR__ . "/../config/database.php";

// Solo permitir POST
if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    http_response_code(405);

    echo json_encode([
        "success" => false,
        "message" => "Método no permitido"
    ]);

    exit;
}

// Obtener el encabezado Authorization
$headers = getallheaders();

$authorization = $headers["Authorization"] ?? "";

// Comprobar que venga como Bearer <token>
if (!preg_match('/Bearer\s+(\S+)/', $authorization, $matches)) {
    http_response_code(401);

    echo json_encode([
        "success" => false,
        "message" => "Token de autenticación requerido"
    ]);

    exit;
}

// Extraer el token
$token = $matches[1];

// Obtener el mismo hash que guardamos en sessions
$tokenHash = hash("sha256", $token);

// Buscar la sesión
$stmt = $pdo->prepare(
    "SELECT id
     FROM sessions
     WHERE token_hash = :token_hash
     LIMIT 1"
);

$stmt->execute([
    ":token_hash" => $tokenHash
]);

$session = $stmt->fetch(PDO::FETCH_ASSOC);

// Si no existe esa sesión
if (!$session) {
    http_response_code(401);

    echo json_encode([
        "success" => false,
        "message" => "Sesión inválida"
    ]);

    exit;
}

// Eliminar la sesión
$stmt = $pdo->prepare(
    "DELETE FROM sessions
     WHERE id = :id"
);

$stmt->execute([
    ":id" => $session["id"]
]);

// Respuesta exitosa
http_response_code(200);

echo json_encode([
    "success" => true,
    "message" => "Sesión cerrada correctamente"
]);