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
    $where = ["w.waste_date BETWEEN ? AND ?"];
    $params = [$date_from, $date_to];
    if (isset($_GET["affected_type"]) && $_GET["affected_type"] !== "") {
        if (!in_array($_GET["affected_type"], ["input", "product", "preparation"], true)) {
            http_response_code(422);
            echo json_encode(["success" => false, "message" => "Tipo inválido"]);
            exit;
        }
        $where[] = "w.affected_type = ?";
        $params[] = $_GET["affected_type"];
    }
    $sql = "SELECT w.affected_type, w.input_id, w.product_id, w.preparation_id,
        COALESCE(i.name, p.name) AS affected_name,
        CASE WHEN w.affected_type = 'input' THEN i.unit ELSE NULL END AS affected_unit,
        COUNT(*) AS records, SUM(w.quantity) AS total_quantity,
        COALESCE(SUM(CASE WHEN w.cost_status = 'calculated' THEN w.estimated_total_cost ELSE 0 END), 0) AS estimated_loss,
        SUM(CASE WHEN w.cost_status = 'unavailable' THEN 1 ELSE 0 END) AS without_cost
        FROM waste_records w
        LEFT JOIN inputs i ON i.id = w.input_id
        LEFT JOIN products p ON p.id = w.product_id
        WHERE " . implode(" AND ", $where) . "
        GROUP BY w.affected_type, w.input_id, w.product_id, w.preparation_id, i.name, p.name, i.unit
        ORDER BY estimated_loss DESC, records DESC";
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $ranking = $stmt->fetchAll(PDO::FETCH_ASSOC);
    http_response_code(200);
    echo json_encode([
        "success" => true,
        "message" => "Ranking consultado",
        "ranking" => $ranking,
        "note" => "Las cantidades solo se suman dentro de un mismo elemento; los costos no disponibles se reportan por separado"
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
