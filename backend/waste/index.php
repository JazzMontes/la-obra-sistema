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

    // Preparar los filtros del listado.
    $where = [];
    $params = [];
    if (isset($_GET["date_from"]) && $_GET["date_from"] !== "") {
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
        $where[] = "w.waste_date >= ?";
        $params[] = $date_from;
    }
    if (isset($_GET["date_to"]) && $_GET["date_to"] !== "") {
        // Validar las fechas con formato YYYY-MM-DD.
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
        $where[] = "w.waste_date <= ?";
        $params[] = $date_to;
    }
    if (isset($date_from, $date_to) && $date_from > $date_to) {
        http_response_code(422);
        echo json_encode(["success" => false, "message" => "Rango de fechas inválido"]);
        exit;
    }
    if (isset($_GET["affected_type"]) && $_GET["affected_type"] !== "") {
        if (!in_array($_GET["affected_type"], ["input", "product", "preparation"], true)) {
            http_response_code(422);
            echo json_encode(["success" => false, "message" => "Tipo inválido"]);
            exit;
        }
        $where[] = "w.affected_type = ?";
        $params[] = $_GET["affected_type"];
    }
    if (isset($_GET["classification"]) && $_GET["classification"] !== "") {
        if (!is_string($_GET["classification"]) || mb_strlen($_GET["classification"]) > 100) {
            http_response_code(422);
            echo json_encode(["success" => false, "message" => "Clasificación inválida"]);
            exit;
        }
        $where[] = "w.classification = ?";
        $params[] = $_GET["classification"];
    }

    // El empleado solamente consulta sus registros del día.
    if ($user["role"] === "employee") {
        $where[] = "w.user_id = ?";
        $params[] = $user["id"];
        $where[] = "w.waste_date = CURDATE()";
    }
    $sql = "SELECT w.*, COALESCE(i.name, p.name) AS affected_name,
           CASE WHEN w.affected_type = 'input' THEN i.unit ELSE NULL END AS affected_unit,
           u.name AS user_name
    FROM waste_records w
    LEFT JOIN inputs i ON i.id = w.input_id
    LEFT JOIN products p ON p.id = w.product_id
    INNER JOIN users u ON u.id = w.user_id";
    $sql .= $where ? " WHERE " . implode(" AND ", $where) : "";
    $sql .= " ORDER BY w.waste_date DESC, w.id DESC";
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $wasteRecords = $stmt->fetchAll(PDO::FETCH_ASSOC);
    http_response_code(200);
    echo json_encode([
        "success" => true,
        "message" => "Mermas consultadas",
        "scope" => $user["role"] === "admin" ? "all" : "today_own",
        "waste_records" => $wasteRecords
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
