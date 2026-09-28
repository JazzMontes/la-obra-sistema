<?php

header("Content-Type: application/json");

require_once __DIR__ . "/../config/database.php";

// Solo permitir PATCH
if ($_SERVER["REQUEST_METHOD"] !== "PATCH") {
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

// Convertir el token al mismo hash que guardamos en sessions
$tokenHash = hash("sha256", $token);

// Buscar la sesión y obtener al usuario
$stmt = $pdo->prepare(
    "SELECT
        u.id,
        u.password_hash,
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

// Verificar que la sesión exista
if (!$user) {
    http_response_code(401);

    echo json_encode([
        "success" => false,
        "message" => "Sesión inválida"
    ]);

    exit;
}

// Verificar que la sesión no haya expirado
if (strtotime($user["expires_at"]) <= time()) {
    http_response_code(401);

    echo json_encode([
        "success" => false,
        "message" => "La sesión ha expirado"
    ]);

    exit;
}

// Verificar que el usuario siga activo
if ($user["status"] !== "active") {
    http_response_code(403);

    echo json_encode([
        "success" => false,
        "message" => "La cuenta no tiene acceso al sistema"
    ]);

    exit;
}

// Recibir el JSON
$data = json_decode(file_get_contents("php://input"), true);

$currentPassword = $data["current_password"] ?? "";
$newPassword = $data["new_password"] ?? "";

// Validar campos
if ($currentPassword === "" || $newPassword === "") {
    http_response_code(422);

    echo json_encode([
        "success" => false,
        "message" => "La contraseña actual y la nueva contraseña son obligatorias"
    ]);

    exit;
}

// Validar longitud de la nueva contraseña
if (strlen($newPassword) < 8) {
    http_response_code(422);

    echo json_encode([
        "success" => false,
        "message" => "La nueva contraseña debe tener al menos 8 caracteres"
    ]);

    exit;
}

// Comprobar la contraseña actual
if (!password_verify($currentPassword, $user["password_hash"])) {
    http_response_code(401);

    echo json_encode([
        "success" => false,
        "message" => "La contraseña actual es incorrecta"
    ]);

    exit;
}

// Generar hash de la nueva contraseña
$newPasswordHash = password_hash($newPassword, PASSWORD_DEFAULT);

// Actualizar contraseña
$stmt = $pdo->prepare(
    "UPDATE users
     SET password_hash = :password_hash,
         must_change_password = FALSE
     WHERE id = :id"
);

$stmt->execute([
    ":password_hash" => $newPasswordHash,
    ":id" => $user["id"]
]);

http_response_code(200);

echo json_encode([
    "success" => true,
    "message" => "Contraseña actualizada correctamente"
]);