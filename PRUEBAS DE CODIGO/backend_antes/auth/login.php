<?php

require_once __DIR__ . "/../config/cors.php";
require_once __DIR__ . "/../config/database.php";

header("Content-Type: application/json");

// Solo permitir POST
if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    http_response_code(405);

    echo json_encode([
        "success" => false,
        "message" => "Método no permitido"
    ]);

    exit;
}

// Recibir JSON
$data = json_decode(file_get_contents("php://input"), true);

$username = trim($data["username"] ?? "");
$password = $data["password"] ?? "";

// Validar campos
if ($username === "" || $password === "") {
    http_response_code(422);

    echo json_encode([
        "success" => false,
        "message" => "Usuario y contraseña son obligatorios"
    ]);

    exit;
}

// Buscar usuario
$stmt = $pdo->prepare(
    "SELECT id, name, username, email, password_hash,
            status, role, must_change_password
     FROM users
     WHERE username = :username
     LIMIT 1"
);

$stmt->execute([
    ":username" => $username
]);

$user = $stmt->fetch(PDO::FETCH_ASSOC);

// Verificar usuario y contraseña
if (!$user || !password_verify($password, $user["password_hash"])) {
    http_response_code(401);

    echo json_encode([
        "success" => false,
        "message" => "Usuario o contraseña incorrectos"
    ]);

    exit;
}

// Verificar estado
if ($user["status"] !== "active") {
    http_response_code(403);

    echo json_encode([
        "success" => false,
        "message" => "La cuenta no tiene acceso al sistema"
    ]);

    exit;
}

// Generar token aleatorio
$token = bin2hex(random_bytes(32));

// Guardamos un hash del token
$tokenHash = hash("sha256", $token);

// La sesión durará 8 horas
$expiresAt = date("Y-m-d H:i:s", time() + (8 * 60 * 60));

// Guardar sesión
$stmt = $pdo->prepare(
    "INSERT INTO sessions (
        user_id,
        token_hash,
        expires_at
    )
    VALUES (
        :user_id,
        :token_hash,
        :expires_at
    )"
);

$stmt->execute([
    ":user_id" => $user["id"],
    ":token_hash" => $tokenHash,
    ":expires_at" => $expiresAt
]);

// Respuesta
http_response_code(200);

echo json_encode([
    "success" => true,
    "message" => "Inicio de sesión correcto",
    "token" => $token,
    "user" => [
        "id" => (int) $user["id"],
        "name" => $user["name"],
        "username" => $user["username"],
        "email" => $user["email"],
        "role" => $user["role"],
        "must_change_password" => (bool) $user["must_change_password"]
    ]
]);