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

    if ($user["role"] !== "admin") {
        http_response_code(403);
        echo json_encode(["success" => false, "message" => "Solo el administrador puede consultar esta información"]);
        exit;
    }

    // Validar las fechas con formato YYYY-MM-DD.
    $period1_start = $_GET["period1_start"] ?? null;
    if (!is_string($period1_start) || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $period1_start)) {
        http_response_code(422);
        echo json_encode(["success" => false, "message" => "period1_start: fecha inválida"]);
        exit;
    }
    $parts = explode("-", $period1_start);
    if (!checkdate((int) $parts[1], (int) $parts[2], (int) $parts[0])) {
        http_response_code(422);
        echo json_encode(["success" => false, "message" => "period1_start: fecha inválida"]);
        exit;
    }
    $period1_end = $_GET["period1_end"] ?? null;
    if (!is_string($period1_end) || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $period1_end)) {
        http_response_code(422);
        echo json_encode(["success" => false, "message" => "period1_end: fecha inválida"]);
        exit;
    }
    $parts = explode("-", $period1_end);
    if (!checkdate((int) $parts[1], (int) $parts[2], (int) $parts[0])) {
        http_response_code(422);
        echo json_encode(["success" => false, "message" => "period1_end: fecha inválida"]);
        exit;
    }
    $period2_start = $_GET["period2_start"] ?? null;
    if (!is_string($period2_start) || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $period2_start)) {
        http_response_code(422);
        echo json_encode(["success" => false, "message" => "period2_start: fecha inválida"]);
        exit;
    }
    $parts = explode("-", $period2_start);
    if (!checkdate((int) $parts[1], (int) $parts[2], (int) $parts[0])) {
        http_response_code(422);
        echo json_encode(["success" => false, "message" => "period2_start: fecha inválida"]);
        exit;
    }
    $period2_end = $_GET["period2_end"] ?? null;
    if (!is_string($period2_end) || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $period2_end)) {
        http_response_code(422);
        echo json_encode(["success" => false, "message" => "period2_end: fecha inválida"]);
        exit;
    }
    $parts = explode("-", $period2_end);
    if (!checkdate((int) $parts[1], (int) $parts[2], (int) $parts[0])) {
        http_response_code(422);
        echo json_encode(["success" => false, "message" => "period2_end: fecha inválida"]);
        exit;
    }
    if ($period1_start > $period1_end) {
        http_response_code(422);
        echo json_encode(["success" => false, "message" => "El inicio no puede superar el final"]);
        exit;
    }
    if ($period2_start > $period2_end) {
        http_response_code(422);
        echo json_encode(["success" => false, "message" => "El inicio no puede superar el final"]);
        exit;
    }
    $stmt = $pdo->prepare("SELECT COUNT(*) AS records,
           COALESCE(SUM(CASE WHEN cost_status = 'calculated' THEN estimated_total_cost ELSE 0 END), 0) AS estimated_loss,
           SUM(CASE WHEN cost_status = 'unavailable' THEN 1 ELSE 0 END) AS without_cost
    FROM waste_records WHERE waste_date BETWEEN ? AND ?");
    $stmt->execute([$period1_start, $period1_end]);
    $period1 = $stmt->fetch(PDO::FETCH_ASSOC);
    $stmt = $pdo->prepare("SELECT COUNT(*) AS records,
           COALESCE(SUM(CASE WHEN cost_status = 'calculated' THEN estimated_total_cost ELSE 0 END), 0) AS estimated_loss,
           SUM(CASE WHEN cost_status = 'unavailable' THEN 1 ELSE 0 END) AS without_cost
    FROM waste_records WHERE waste_date BETWEEN ? AND ?");
    $stmt->execute([$period2_start, $period2_end]);
    $period2 = $stmt->fetch(PDO::FETCH_ASSOC);
    $difference = round((float) $period2["estimated_loss"] - (float) $period1["estimated_loss"], 2);
    $percentage = (float) $period1["estimated_loss"] > 0
        ? round($difference / (float) $period1["estimated_loss"] * 100, 2)
        : null;
    http_response_code(200);
    echo json_encode([
        "success" => true,
        "message" => "Comparación consultada",
        "period1" => $period1,
        "period2" => $period2,
        "difference" => $difference,
        "percentage_change" => $percentage,
        "note" => "Los valores económicos excluyen pérdidas sin costo estimable"
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
