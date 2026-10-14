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
    $date_from = $_GET["date_from"] ?? null;
    if (!is_string($date_from) || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $date_from)) {
        http_response_code(422);
        echo json_encode(["success" => false, "message" => "date_from: fecha inválida"]);
        exit;
    }
    $parts = explode("-", $date_from);
    if (!checkdate((int) $parts[1], (int) $parts[2], (int) $parts[0])) {
        http_response_code(422);
        echo json_encode(["success" => false, "message" => "date_from: fecha inválida"]);
        exit;
    }
    $date_to = $_GET["date_to"] ?? null;
    if (!is_string($date_to) || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $date_to)) {
        http_response_code(422);
        echo json_encode(["success" => false, "message" => "date_to: fecha inválida"]);
        exit;
    }
    $parts = explode("-", $date_to);
    if (!checkdate((int) $parts[1], (int) $parts[2], (int) $parts[0])) {
        http_response_code(422);
        echo json_encode(["success" => false, "message" => "date_to: fecha inválida"]);
        exit;
    }
    if ($date_from > $date_to) {
        http_response_code(422);
        echo json_encode(["success" => false, "message" => "El inicio no puede superar el final"]);
        exit;
    }
    $stmt = $pdo->prepare("SELECT COUNT(*) AS records,
           COALESCE(SUM(CASE WHEN cost_status = 'calculated' THEN estimated_total_cost ELSE 0 END), 0) AS estimated_loss,
           SUM(CASE WHEN cost_status = 'unavailable' THEN 1 ELSE 0 END) AS without_cost
    FROM waste_records WHERE waste_date BETWEEN ? AND ?");
    $stmt->execute([$date_from, $date_to]);
    $waste = $stmt->fetch(PDO::FETCH_ASSOC);
    $stmt = $pdo->prepare("SELECT COALESCE(SUM(o.total), 0) AS total FROM orders o WHERE o.order_status = 'confirmed' AND o.payment_status = 'paid' AND EXISTS (SELECT 1 FROM payments pay WHERE pay.order_id = o.id AND pay.paid_at >= ? AND pay.paid_at < DATE_ADD(?, INTERVAL 1 DAY))");
    $stmt->execute([$date_from, $date_to]);
    $sales = $stmt->fetch(PDO::FETCH_ASSOC);
    $stmt = $pdo->prepare("SELECT COALESCE(SUM(total), 0) AS total FROM purchases WHERE purchase_date BETWEEN ? AND ?");
    $stmt->execute([$date_from, $date_to]);
    $purchases = $stmt->fetch(PDO::FETCH_ASSOC);
    $loss = (float) $waste["estimated_loss"];
    $revenue = (float) $sales["total"];
    $purchased = (float) $purchases["total"];
    http_response_code(200);
    echo json_encode([
        "success" => true,
        "message" => "Indicadores consultados",
        "waste" => $waste,
        "sales_total" => $revenue,
        "purchases_total" => $purchased,
        "loss_as_percent_of_sales" => $revenue > 0 ? round($loss / $revenue * 100, 2) : null,
        "loss_as_percent_of_purchases" => $purchased > 0 ? round($loss / $purchased * 100, 2) : null,
        "cost_basis" => "purchases_total es gasto en compras del periodo, no costo de ventas ni consumo real",
        "note" => "Los porcentajes se basan en pérdidas con costo calculable; los demás registros se cuentan por separado"
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
