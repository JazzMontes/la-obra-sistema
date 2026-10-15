<?php
// Consulta el listado de usuarios.

header("Content-Type: application/json");

require_once __DIR__ . "/../config/cors.php";
require_once __DIR__ . "/../config/database.php";

// Solo permitir GET
if ($_SERVER["REQUEST_METHOD"] !== "GET") {
    http_response_code(405);

// Enviar respuesta
    echo json_encode([
        "success" => false,
        "message" => "Método no permitido"
    ]);

    exit;
}

// Obtener el encabezado Authorization
$headers = getallheaders();

// Validar autenticación
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

// Extraer token y convertirlo a hash
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

// Verificar que la cuenta esté activa
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

// Obtener filtro opcional
$status = trim($_GET["status"] ?? "");

// Si se envió un status, comprobar que sea válido
if ($status !== "") {

    $allowedStatuses = [
        "pending",
        "active",
        "rejected",
        "inactive"
    ];

    if (!in_array($status, $allowedStatuses, true)) {
        http_response_code(422);

        echo json_encode([
            "success" => false,
            "message" => "El estado no es válido"
        ]);

        exit;
    }

    // Consultar usuarios filtrados por estado
    $stmt = $pdo->prepare(
        "SELECT
            id,
            name,
            username,
            email,
            status,
            role
         FROM users
         WHERE status = :status
         ORDER BY id ASC"
    );

    $stmt->execute([
        ":status" => $status
    ]);

} else {

    // Consultar todos los usuarios
    $stmt = $pdo->prepare(
        "SELECT
            id,
            name,
            username,
            email,
            status,
            role
         FROM users
         ORDER BY id ASC"
    );

    $stmt->execute();
}

// Obtener todos los resultados
$users = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Convertir id a número
foreach ($users as &$user) {
    $user["id"] = (int) $user["id"];
}

http_response_code(200);

echo json_encode([
    "success" => true,
    "users" => $users
]);