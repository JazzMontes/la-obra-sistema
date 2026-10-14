<?php
require_once __DIR__ . "/../config/cors.php";
require_once __DIR__ . "/../config/database.php";

header("Content-Type: application/json");

// Validar el método de la solicitud.
if ($_SERVER["REQUEST_METHOD"] !== "GET") {
    http_response_code(405);
    echo json_encode(["success" => false, "message" => "Método no permitido"]);
    exit;
}

// Obtener el token de autenticación.
$headers = array_change_key_case(getallheaders(), CASE_LOWER);
$authorization = $headers["authorization"] ?? ($_SERVER["HTTP_AUTHORIZATION"] ?? "");
if (!preg_match('/^Bearer\s+(\S+)$/i', trim($authorization), $matches)) {
    http_response_code(401);
    echo json_encode(["success" => false, "message" => "Token de autenticación requerido"]);
    exit;
}

$tokenHash = hash("sha256", $matches[1]);

try {
    // Consultar la sesión y al usuario.
    $stmt = $pdo->prepare(
        "SELECT u.id, u.role, u.status, u.must_change_password, s.expires_at
         FROM sessions s
         INNER JOIN users u ON s.user_id = u.id
         WHERE s.token_hash = :token_hash
         LIMIT 1"
    );
    $stmt->execute([":token_hash" => $tokenHash]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$user) {
        http_response_code(401);
        echo json_encode(["success" => false, "message" => "Sesión inválida"]);
        exit;
    }

    if (strtotime($user["expires_at"]) <= time()) {
        http_response_code(401);
        echo json_encode(["success" => false, "message" => "La sesión ha expirado"]);
        exit;
    }

    if ($user["status"] !== "active") {
        http_response_code(403);
        echo json_encode(["success" => false, "message" => "La cuenta no tiene acceso al sistema"]);
        exit;
    }

    if (!in_array($user["role"], ["admin", "employee"], true)) {
        http_response_code(403);
        echo json_encode(["success" => false, "message" => "No tienes acceso a este servicio"]);
        exit;
    }

    if ((bool) $user["must_change_password"]) {
        http_response_code(403);
        echo json_encode(["success" => false, "message" => "Debes cambiar tu contraseña antes de continuar"]);
        exit;
    }

    $id = $_GET["id"] ?? null;
    if ((!is_int($id) && !is_string($id)) || !preg_match('/^[1-9][0-9]*$/', (string) $id) || filter_var($id, FILTER_VALIDATE_INT) === false) {
        http_response_code(422);
        echo json_encode(["success" => false, "message" => "Identificador inválido"]);
        exit;
    }
    $id = (int) $id;
    $stmt = $pdo->prepare("SELECT w.*, COALESCE(i.name, p.name) AS affected_name,
           CASE WHEN w.affected_type = 'input' THEN i.unit ELSE NULL END AS affected_unit,
           u.name AS user_name
    FROM waste_records w
    LEFT JOIN inputs i ON i.id = w.input_id
    LEFT JOIN products p ON p.id = w.product_id
    INNER JOIN users u ON u.id = w.user_id WHERE w.id = ?");
    $stmt->execute([$id]);
    $waste = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$waste) {
        http_response_code(404);
        echo json_encode(["success" => false, "message" => "Merma no encontrada"]);
        exit;
    }
    if ($user["role"] === "employee" && ((int) $waste["user_id"] !== (int) $user["id"] || $waste["waste_date"] !== date("Y-m-d"))) {
        http_response_code(403);
        echo json_encode(["success" => false, "message" => "No tienes acceso a este registro"]);
        exit;
    }
    http_response_code(200);
    echo json_encode([
        "success" => true,
        "message" => "Detalle consultado",
        "waste" => $waste
    ]);

} catch (Throwable $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    error_log($e->getMessage());
    http_response_code(500);
    echo json_encode([
        "success" => false,
        "message" => "No se pudo procesar la solicitud"
    ]);
}
