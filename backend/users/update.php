<?php
// Actualiza los datos de usuarios.

header("Content-Type: application/json");

require_once __DIR__ . "/../config/cors.php";
require_once __DIR__ . "/../config/database.php";

// Solo permitir PATCH
if ($_SERVER["REQUEST_METHOD"] !== "PATCH") {
    http_response_code(405);

// Enviar respuesta
    echo json_encode([
        "success" => false,
        "message" => "Método no permitido"
    ]);

    exit;
}

/*
|--------------------------------------------------------------------------
| AUTENTICACIÓN
|--------------------------------------------------------------------------
*/

// Obtener Authorization
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

$token = $matches[1];
$tokenHash = hash("sha256", $token);

// Buscar al usuario autenticado
$stmt = $pdo->prepare(
    "SELECT
        u.id,
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

$currentUser = $stmt->fetch(PDO::FETCH_ASSOC);

// Verificar que la sesión exista
if (!$currentUser) {
    http_response_code(401);

    echo json_encode([
        "success" => false,
        "message" => "Sesión inválida"
    ]);

    exit;
}

// Verificar que la sesión no haya expirado
if (strtotime($currentUser["expires_at"]) <= time()) {
    http_response_code(401);

    echo json_encode([
        "success" => false,
        "message" => "La sesión ha expirado"
    ]);

    exit;
}

// Verificar que la cuenta siga activa
if ($currentUser["status"] !== "active") {
    http_response_code(403);

    echo json_encode([
        "success" => false,
        "message" => "La cuenta no tiene acceso al sistema"
    ]);

    exit;
}

// Solo los administradores pueden modificar estado y rol
if ($currentUser["role"] !== "admin") {
    http_response_code(403);

    echo json_encode([
        "success" => false,
        "message" => "No tienes permisos para modificar usuarios"
    ]);

    exit;
}

/*
|--------------------------------------------------------------------------
| OBTENER USUARIO A MODIFICAR
|--------------------------------------------------------------------------
*/

$id = $_GET["id"] ?? "";

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

if (!$user) {
    http_response_code(404);

    echo json_encode([
        "success" => false,
        "message" => "Usuario no encontrado"
    ]);

    exit;
}

/*
|--------------------------------------------------------------------------
| RECIBIR DATOS
|--------------------------------------------------------------------------
*/

// Recibir datos de la solicitud
$data = json_decode(file_get_contents("php://input"), true);

if (!is_array($data)) {
    http_response_code(422);

    echo json_encode([
        "success" => false,
        "message" => "Los datos enviados no son válidos"
    ]);

    exit;
}

// Saber qué campos fueron enviados
$hasStatus = array_key_exists("status", $data);
$hasRole = array_key_exists("role", $data);

// Debe enviarse por lo menos uno
if (!$hasStatus && !$hasRole) {
    http_response_code(422);

    echo json_encode([
        "success" => false,
        "message" => "Debes proporcionar status, role o ambos"
    ]);

    exit;
}

/*
|--------------------------------------------------------------------------
| VALIDAR STATUS
|--------------------------------------------------------------------------
*/

$allowedStatuses = [
    "pending",
    "active",
    "rejected",
    "inactive"
];

$newStatus = $user["status"];

if ($hasStatus) {

    $newStatus = trim($data["status"] ?? "");

    if (!in_array($newStatus, $allowedStatuses, true)) {
        http_response_code(422);

        echo json_encode([
            "success" => false,
            "message" => "El estado no es válido"
        ]);

        exit;
    }
}

/*
|--------------------------------------------------------------------------
| VALIDAR ROLE
|--------------------------------------------------------------------------
*/

$allowedRoles = [
    "admin",
    "employee"
];

$newRole = $user["role"];

if ($hasRole) {

    $newRole = trim($data["role"] ?? "");

    if (!in_array($newRole, $allowedRoles, true)) {
        http_response_code(422);

        echo json_encode([
            "success" => false,
            "message" => "El rol no es válido"
        ]);

        exit;
    }
}

/*
|--------------------------------------------------------------------------
| REGLA DE ACTIVACIÓN
|--------------------------------------------------------------------------
|
| Una cuenta activa debe tener un rol.
| Si el usuario todavía no tiene rol, el administrador deberá enviarlo
| al momento de activarlo.
|
*/

if ($newStatus === "active" && empty($newRole)) {
    http_response_code(422);

    echo json_encode([
        "success" => false,
        "message" => "Para activar la cuenta debes asignar un rol"
    ]);

    exit;
}

/*
|--------------------------------------------------------------------------
| PROTEGER AL ÚLTIMO ADMINISTRADOR
|--------------------------------------------------------------------------
|
| Si el usuario que estamos modificando actualmente es admin,
| comprobamos que no sea el único antes de quitarle el rol admin
| o desactivar su cuenta.
|
*/

if ($user["role"] === "admin") {

    $willLoseAdminAccess =
        $newRole !== "admin" ||
        $newStatus !== "active";

    if ($willLoseAdminAccess) {

        $stmt = $pdo->prepare(
            "SELECT COUNT(*)
             FROM users
             WHERE role = 'admin'
               AND status = 'active'
               AND id != :id"
        );

        $stmt->execute([
            ":id" => $id
        ]);

        $otherAdmins = (int) $stmt->fetchColumn();

        if ($otherAdmins === 0) {
            http_response_code(422);

            echo json_encode([
                "success" => false,
                "message" => "No se puede realizar el cambio porque el sistema debe conservar al menos un administrador activo"
            ]);

            exit;
        }
    }
}

/*
|--------------------------------------------------------------------------
| ACTUALIZAR USUARIO
|--------------------------------------------------------------------------
*/

$stmt = $pdo->prepare(
    "UPDATE users
     SET
        status = :status,
        role = :role
     WHERE id = :id"
);

$stmt->execute([
    ":status" => $newStatus,
    ":role" => $newRole,
    ":id" => $id
]);

/*
|--------------------------------------------------------------------------
| RESPUESTA
|--------------------------------------------------------------------------
*/

http_response_code(200);

echo json_encode([
    "success" => true,
    "message" => "Usuario actualizado correctamente",
    "user" => [
        "id" => (int) $user["id"],
        "name" => $user["name"],
        "username" => $user["username"],
        "email" => $user["email"],
        "status" => $newStatus,
        "role" => $newRole
    ]
]);