<?php

header("Content-Type: application/json");

require_once __DIR__ . "/../config/cors.php";
// Conexión a la base de datos
require_once __DIR__ . "/../config/database.php";

// Solo permitir peticiones POST
if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    http_response_code(405);

    echo json_encode([
        "success" => false,
        "message" => "Método no permitido"
    ]);

    exit;
}

// Recibir el JSON enviado
$data = json_decode(file_get_contents("php://input"), true);

// Obtener y limpiar los datos
$name = trim($data["name"] ?? "");
$username = trim($data["username"] ?? "");
$email = trim($data["email"] ?? "");
$password = $data["password"] ?? "";

// Validar que todos los campos estén completos
if (
    $name === "" ||
    $username === "" ||
    $email === "" ||
    $password === ""
) {
    http_response_code(422);

    echo json_encode([
        "success" => false,
        "message" => "Todos los campos son obligatorios"
    ]);

    exit;
}

// Validar formato del correo
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    http_response_code(422);

    echo json_encode([
        "success" => false,
        "message" => "El correo electrónico no es válido"
    ]);

    exit;
}

// Validar longitud mínima de contraseña
if (strlen($password) < 8) {
    http_response_code(422);

    echo json_encode([
        "success" => false,
        "message" => "La contraseña debe tener al menos 8 caracteres"
    ]);

    exit;
}

// Verificar que username o email no estén registrados
$stmt = $pdo->prepare(
    "SELECT id
     FROM users
     WHERE username = :username
        OR email = :email
     LIMIT 1"
);

$stmt->execute([
    ":username" => $username,
    ":email" => $email
]);

$existingUser = $stmt->fetch(PDO::FETCH_ASSOC);

if ($existingUser) {
    http_response_code(409);

    echo json_encode([
        "success" => false,
        "message" => "El nombre de usuario o correo ya está registrado"
    ]);

    exit;
}

// Convertir la contraseña en un hash seguro
$passwordHash = password_hash($password, PASSWORD_DEFAULT);

// Registrar al usuario
$stmt = $pdo->prepare(
    "INSERT INTO users (
        name,
        username,
        email,
        password_hash,
        status,
        role,
        must_change_password
    )
    VALUES (
        :name,
        :username,
        :email,
        :password_hash,
        'pending',
        NULL,
        FALSE
    )"
);

$stmt->execute([
    ":name" => $name,
    ":username" => $username,
    ":email" => $email,
    ":password_hash" => $passwordHash
]);

// Respuesta exitosa
http_response_code(201);

echo json_encode([
    "success" => true,
    "message" => "Registro realizado correctamente. Tu cuenta está pendiente de aprobación."
]);