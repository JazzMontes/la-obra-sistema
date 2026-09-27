<?php

header("Content-Type: application/json");

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

// Generar el mismo hash que guardamos durante el login
$tokenHash = hash("sha256", $token);

// Buscar la sesión y al usuario correspondiente
$stmt = $pdo->prepare(
    "SELECT
        u.id,
        u.name,
        u.username,
        u.email,
        u.role,
        u.status,
        s.expires_at
     FROM sessions s
     INNER JOIN users u ON s.user_id = u.id
     WHERE s.token_hash = :token_hash
     LIMIT 1"
);

$stmt->execute([
    ":token_hash" => $tokenHash
]);

$user = $stmt->fetch(PDO::FETCH_ASSOC);

// Si el token no corresponde a ninguna sesión
if (!$user) {
    http_response_code(401);

    echo json_encode([
        "success" => false,
        "message" => "Sesión inválida"
    ]);

    exit;
}

// Comprobar que la sesión no haya expirado
if (strtotime($user["expires_at"]) <= time()) {
    http_response_code(401);

    echo json_encode([
        "success" => false,
        "message" => "La sesión ha expirado"
    ]);

    exit;
}

// Comprobar que la cuenta siga activa
if ($user["status"] !== "active") {
    http_response_code(403);

    echo json_encode([
        "success" => false,
        "message" => "La cuenta no tiene acceso al sistema"
    ]);

    exit;
}

// Respuesta exitosa
http_response_code(200);

echo json_encode([
    "success" => true,
    "user" => [
        "id" => (int) $user["id"],
        "name" => $user["name"],
        "username" => $user["username"],
        "email" => $user["email"],
        "role" => $user["role"],
        "status" => $user["status"]
    ]
]);