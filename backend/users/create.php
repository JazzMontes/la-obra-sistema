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

// Verificar que venga el Bearer Token
if (!preg_match('/Bearer\s+(\S+)/', $authorization, $matches)) {
    http_response_code(401);

    echo json_encode([
        "success" => false,
        "message" => "Token de autenticación requerido"
    ]);

    exit;
}

// Extraer y convertir el token a hash
$token = $matches[1];
$tokenHash = hash("sha256", $token);

// Buscar al usuario dueño de la sesión
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

// Verificar que sea administrador
if ($currentUser["role"] !== "admin") {
    http_response_code(403);

    echo json_encode([
        "success" => false,
        "message" => "No tienes permisos para realizar esta acción"
    ]);

    exit;
}

// Recibir JSON
$data = json_decode(file_get_contents("php://input"), true);

$name = trim($data["name"] ?? "");
$username = trim($data["username"] ?? "");
$email = trim($data["email"] ?? "");
$role = trim($data["role"] ?? "");

// Validar campos
if (
    $name === "" ||
    $username === "" ||
    $email === "" ||
    $role === ""
) {
    http_response_code(422);

    echo json_encode([
        "success" => false,
        "message" => "Todos los campos son obligatorios"
    ]);

    exit;
}

// Validar correo
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    http_response_code(422);

    echo json_encode([
        "success" => false,
        "message" => "El correo electrónico no es válido"
    ]);

    exit;
}

// Validar rol
if (!in_array($role, ["admin", "employee"], true)) {
    http_response_code(422);

    echo json_encode([
        "success" => false,
        "message" => "El rol no es válido"
    ]);

    exit;
}

// Verificar username o email duplicados
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

if ($stmt->fetch(PDO::FETCH_ASSOC)) {
    http_response_code(409);

    echo json_encode([
        "success" => false,
        "message" => "El nombre de usuario o correo ya está registrado"
    ]);

    exit;
}

// Generar contraseña temporal
$temporaryPassword = bin2hex(random_bytes(5));

// Guardar únicamente su hash
$passwordHash = password_hash(
    $temporaryPassword,
    PASSWORD_DEFAULT
);

// Crear usuario
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
        'active',
        :role,
        TRUE
    )"
);

$stmt->execute([
    ":name" => $name,
    ":username" => $username,
    ":email" => $email,
    ":password_hash" => $passwordHash,
    ":role" => $role
]);

http_response_code(201);

echo json_encode([
    "success" => true,
    "message" => "Usuario creado correctamente",
    "user" => [
        "id" => (int) $pdo->lastInsertId(),
        "name" => $name,
        "username" => $username,
        "email" => $email,
        "role" => $role,
        "status" => "active",
        "must_change_password" => true
    ],
    "temporary_password" => $temporaryPassword
]);