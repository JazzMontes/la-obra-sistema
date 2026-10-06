<?php

header("Content-Type: application/json");
require_once __DIR__ . "/../config/cors.php";
require_once __DIR__ . "/../config/database.php";

// Obtener el método de la petición
$method = $_SERVER["REQUEST_METHOD"];

// Solo permitir GET y PUT
if ($method !== "GET" && $method !== "PUT") {
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
        u.must_change_password,
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

/*
|--------------------------------------------------------------------------
| GET /auth/me
|--------------------------------------------------------------------------
| Consultar los datos del usuario autenticado
*/

if ($method === "GET") {

    http_response_code(200);

    echo json_encode([
        "success" => true,
        "user" => [
            "id" => (int) $user["id"],
            "name" => $user["name"],
            "username" => $user["username"],
            "email" => $user["email"],
            "role" => $user["role"],
            "status" => $user["status"],
            "must_change_password" => (bool) $user["must_change_password"]
        ]
    ]);

    exit;
}

/*
|--------------------------------------------------------------------------
| PUT /auth/me
|--------------------------------------------------------------------------
| Modificar los datos del propio usuario
*/

if ($method === "PUT") {

    // Recibir JSON
    $data = json_decode(file_get_contents("php://input"), true);

    $name = trim($data["name"] ?? "");
    $username = trim($data["username"] ?? "");
    $email = trim($data["email"] ?? "");

    // Validar campos obligatorios
    if (
        $name === "" ||
        $username === "" ||
        $email === ""
    ) {
        http_response_code(422);

        echo json_encode([
            "success" => false,
            "message" => "Nombre, usuario y correo son obligatorios"
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

    // Verificar que username o email no pertenezcan a otro usuario
    $stmt = $pdo->prepare(
        "SELECT id
         FROM users
         WHERE (username = :username OR email = :email)
           AND id != :id
         LIMIT 1"
    );

    $stmt->execute([
        ":username" => $username,
        ":email" => $email,
        ":id" => $user["id"]
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

    // Actualizar los datos del usuario autenticado
    $stmt = $pdo->prepare(
        "UPDATE users
         SET
            name = :name,
            username = :username,
            email = :email
         WHERE id = :id"
    );

    $stmt->execute([
        ":name" => $name,
        ":username" => $username,
        ":email" => $email,
        ":id" => $user["id"]
    ]);

    http_response_code(200);

    echo json_encode([
        "success" => true,
        "message" => "Perfil actualizado correctamente",
        "user" => [
            "id" => (int) $user["id"],
            "name" => $name,
            "username" => $username,
            "email" => $email,
            "role" => $user["role"],
            "status" => $user["status"]
        ]
    ]);

    exit;
}