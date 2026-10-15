<?php
// Consultar el reporte con los permisos y fechas correspondientes.
require_once __DIR__ . "/../config/cors.php";
require_once __DIR__ . "/../config/database.php";

header("Content-Type: application/json; charset=utf-8");
header("Cache-Control: no-store");

// Solo permitir solicitudes GET.
if ($_SERVER["REQUEST_METHOD"] !== "GET") {
    http_response_code(405);
    echo json_encode([
        "success" => false,
        "message" => "Método no permitido"
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

// Obtener el encabezado Authorization.
$headers = array_change_key_case(getallheaders(), CASE_LOWER);
$authorization = $headers["authorization"] ?? "";
if (!preg_match('/^Bearer\s+(\S+)$/i', $authorization, $matches)) {
    http_response_code(401);
    echo json_encode([
        "success" => false,
        "message" => "Token de autenticación requerido"
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

// Generar el mismo hash guardado durante el inicio de sesión.
$tokenHash = hash("sha256", $matches[1]);

try {
    // Buscar la sesión y los permisos del usuario.
    $stmt = $pdo->prepare(
        "SELECT u.status, u.role, s.expires_at
         FROM sessions s
         INNER JOIN users u ON s.user_id = u.id
         WHERE s.token_hash = :token_hash
         LIMIT 1"
    );
    $stmt->execute([":token_hash" => $tokenHash]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$user || strtotime($user["expires_at"]) <= time()) {
        http_response_code(401);
        echo json_encode([
            "success" => false,
            "message" => "La sesión es inválida o ha expirado"
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }
    if ($user["status"] !== "active" || $user["role"] !== "admin") {
        http_response_code(403);
        echo json_encode([
            "success" => false,
            "message" => "Los reportes están disponibles para el administrador"
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }
} catch (Throwable $e) {
    error_log($e->getMessage());
    http_response_code(500);
    echo json_encode([
        "success" => false,
        "message" => "No fue posible verificar la sesión"
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

// Recibir las fechas; por defecto se consulta el mes actual.
$from = $_GET["from"] ?? date("Y-m-01");
$to = $_GET["to"] ?? date("Y-m-d");
if (!is_string($from) || !is_string($to)) {
    http_response_code(422);
    echo json_encode([
        "success" => false,
        "message" => "Fecha inválida"
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

// Comprobar el formato y que las fechas existan.
$fromDate = DateTimeImmutable::createFromFormat("!Y-m-d", $from);
$toDate = DateTimeImmutable::createFromFormat("!Y-m-d", $to);
if (!$fromDate || !$toDate || $fromDate->format("Y-m-d") !== $from || $toDate->format("Y-m-d") !== $to) {
    http_response_code(422);
    echo json_encode([
        "success" => false,
        "message" => "Utiliza fechas válidas con formato AAAA-MM-DD"
    ], JSON_UNESCAPED_UNICODE);
    exit;
}
if ($fromDate > $toDate) {
    http_response_code(422);
    echo json_encode([
        "success" => false,
        "message" => "La fecha inicial no puede ser posterior a la final"
    ], JSON_UNESCAPED_UNICODE);
    exit;
}
if ($fromDate->diff($toDate)->days > 365) {
    http_response_code(422);
    echo json_encode([
        "success" => false,
        "message" => "Selecciona un rango de hasta 366 días"
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

// Incluir el último día completo usando el inicio del día siguiente.
$reportParams = [
    ":from" => $fromDate->format("Y-m-d 00:00:00"),
    ":until" => $toDate->modify("+1 day")->format("Y-m-d 00:00:00")
];

// Considerar solamente pedidos confirmados y pagados.
$reportWhere = "o.order_status = 'confirmed'
    AND o.payment_status = 'paid'
    AND pay.paid_at >= :from
    AND pay.paid_at < :until";

try {
    // Preparar y ejecutar la consulta del reporte.
    $stmt = $pdo->prepare(
        "SELECT DATE(pay.paid_at) AS date, c.id AS category_id, c.name AS category, SUM(oi.subtotal) AS amount
         FROM order_items oi
         INNER JOIN products p ON p.id=oi.product_id
         INNER JOIN categories c ON c.id=p.category_id
         INNER JOIN orders o ON o.id=oi.order_id
         INNER JOIN payments pay ON pay.order_id=o.id
         WHERE $reportWhere
         GROUP BY DATE(pay.paid_at),c.id,c.name
         ORDER BY date,c.id"
    );
    $stmt->execute($reportParams);
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Mantener los valores numéricos de la respuesta original.
    foreach ($rows as &$row) {
        $row["category_id"] = (float) $row["category_id"];
        $row["amount"] = (float) $row["amount"];
    }
    unset($row);

    // Enviar el reporte en JSON.
    http_response_code(200);
    echo json_encode([
        "success" => true,
        "from" => $fromDate->format("Y-m-d"),
        "to" => $toDate->format("Y-m-d"),
        "data" => $rows
    ], JSON_UNESCAPED_UNICODE);
} catch (Throwable $e) {
    error_log($e->getMessage());
    http_response_code(500);
    echo json_encode([
        "success" => false,
        "message" => "No fue posible obtener el reporte"
    ], JSON_UNESCAPED_UNICODE);
    exit;
}
