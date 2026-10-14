<?php
require_once __DIR__ . "/../config/cors.php";
require_once __DIR__ . "/../config/database.php";

header("Content-Type: application/json");

// Validar el método de la solicitud.
if ($_SERVER["REQUEST_METHOD"] !== "POST") {
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

    // Recibir el JSON enviado.
    $data = json_decode(file_get_contents("php://input"), true);
    if (!is_array($data) || json_last_error() !== JSON_ERROR_NONE) {
        http_response_code(422);
        echo json_encode(["success" => false, "message" => "JSON inválido"]);
        exit;
    }
    $type = $data["affected_type"] ?? null;
    if (!in_array($type, ["input", "product", "preparation"], true)) {
        http_response_code(422);
        echo json_encode(["success" => false, "message" => "Tipo de elemento inválido"]);
        exit;
    }
    if ($type === "preparation") {
        http_response_code(422);
        echo json_encode(["success" => false, "message" => "El servicio de preparaciones todavía no está disponible"]);
        exit;
    }
    $id = $data["affected_id"] ?? null;
    if ((!is_int($id) && !is_string($id)) || !preg_match('/^[1-9][0-9]*$/', (string) $id) || filter_var($id, FILTER_VALIDATE_INT) === false) {
        http_response_code(422);
        echo json_encode(["success" => false, "message" => "Identificador inválido"]);
        exit;
    }
    $id = (int) $id;

    // Evitar identificadores contradictorios.
    foreach (["input_id", "product_id", "preparation_id"] as $field) {
        if (isset($data[$field])) {
            $relatedId = $data[$field];
            if ((!is_int($relatedId) && !is_string($relatedId)) || !preg_match('/^[1-9][0-9]*$/', (string) $relatedId) || filter_var($relatedId, FILTER_VALIDATE_INT) === false) {
                http_response_code(422);
                echo json_encode(["success" => false, "message" => "Identificador inválido"]);
                exit;
            }
            $relatedId = (int) $relatedId;
            if ($field !== ($type === "input" ? "input_id" : "product_id") || $relatedId !== $id) {
                http_response_code(422);
                echo json_encode(["success" => false, "message" => "Identificador del elemento contradictorio"]);
                exit;
            }
        }
    }
    $quantity = $data["quantity"] ?? null;
    if (!is_scalar($quantity) || !is_numeric($quantity) || !is_finite((float) $quantity) || (float) $quantity <= 0 || round((float) $quantity, 3) != (float) $quantity || (float) $quantity > 999999999.999) {
        http_response_code(422);
        echo json_encode(["success" => false, "message" => "Cantidad inválida"]);
        exit;
    }
    $quantity = number_format((float) $quantity, 3, ".", "");
    $approximate = $data["is_approximate"] ?? false;
    if (!is_bool($approximate)) {
        http_response_code(422);
        echo json_encode(["success" => false, "message" => "is_approximate debe ser booleano"]);
        exit;
    }
    // Validar las fechas con formato YYYY-MM-DD.
    $waste_date = $data["waste_date"] ?? null;
    if (!is_string($waste_date) || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $waste_date)) {
        http_response_code(422);
        echo json_encode(["success" => false, "message" => "waste_date: fecha inválida"]);
        exit;
    }
    $parts = explode("-", $waste_date);
    if (!checkdate((int) $parts[1], (int) $parts[2], (int) $parts[0])) {
        http_response_code(422);
        echo json_encode(["success" => false, "message" => "waste_date: fecha inválida"]);
        exit;
    }
    $reason = $data["reason"] ?? null;
    $classification = $data["classification"] ?? null;
    if (!is_string($reason) || trim($reason) === "" || mb_strlen($reason) > 500) {
        http_response_code(422);
        echo json_encode(["success" => false, "message" => "Motivo obligatorio, máximo 500 caracteres"]);
        exit;
    }
    if (!is_string($classification) || trim($classification) === "" || mb_strlen($classification) > 100) {
        http_response_code(422);
        echo json_encode(["success" => false, "message" => "Clasificación obligatoria, máximo 100 caracteres"]);
        exit;
    }
    // El nombre de tabla solo puede provenir de estos dos valores.
    $table = $type === "input" ? "inputs" : "products";
    $stmt = $pdo->prepare("SELECT id FROM $table WHERE id = ?");
    $stmt->execute([$id]);
    $element = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$element) {
        http_response_code(404);
        echo json_encode(["success" => false, "message" => "Elemento afectado no encontrado"]);
        exit;
    }

    $pdo->beginTransaction();

    // Obtener los insumos que forman el elemento afectado.
    if ($type === "input") {
        $parts = [["input_id" => $id, "quantity" => 1]];
    } else {
        $stmt = $pdo->prepare("SELECT input_id, quantity FROM recipes WHERE product_id = ?");
        $stmt->execute([$id]);
        $parts = $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // Estimar el costo a partir de la última compra de cada insumo.
    $unitCost = $parts ? 0 : null;
    foreach ($parts as $part) {
        $stmt = $pdo->prepare(
            "SELECT pi.unit_cost
             FROM purchase_items pi
             INNER JOIN purchases p ON p.id = pi.purchase_id
             WHERE pi.input_id = ?
             ORDER BY p.purchase_date DESC, p.id DESC, pi.id DESC
             LIMIT 1"
        );
        $stmt->execute([$part["input_id"]]);
        $purchaseItem = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$purchaseItem) {
            $unitCost = null;
            break;
        }
        $unitCost += (float) $part["quantity"] * round((float) $purchaseItem["unit_cost"], 4);
    }
    $unitCost = $unitCost === null ? null : round($unitCost, 4);
    $totalCost = $unitCost === null ? null : round($unitCost * (float) $quantity, 2);
    if ($totalCost !== null && $totalCost > 999999999999.99) {
        $unitCost = null;
        $totalCost = null;
    }

    // Registrar la merma y su costo estimado.
    $stmt = $pdo->prepare(
        "INSERT INTO waste_records (
            affected_type, input_id, product_id, preparation_id, quantity,
            is_approximate, waste_date, reason, classification,
            estimated_unit_cost, estimated_total_cost, cost_status, user_id
         ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)"
    );
    $stmt->execute([
        $type, $type === "input" ? $id : null, $type === "product" ? $id : null,
        null, $quantity, $approximate ? 1 : 0, $waste_date, trim($reason),
        trim($classification), $unitCost, $totalCost,
        $unitCost === null ? "unavailable" : "calculated", $user["id"]
    ]);
    $newId = (int) $pdo->lastInsertId();
    $stmt = $pdo->prepare("SELECT w.*, COALESCE(i.name, p.name) AS affected_name,
           CASE WHEN w.affected_type = 'input' THEN i.unit ELSE NULL END AS affected_unit,
           u.name AS user_name
    FROM waste_records w
    LEFT JOIN inputs i ON i.id = w.input_id
    LEFT JOIN products p ON p.id = w.product_id
    INNER JOIN users u ON u.id = w.user_id WHERE w.id = ?");
    $stmt->execute([$newId]);
    $waste = $stmt->fetch(PDO::FETCH_ASSOC);
    $pdo->commit();

    // Enviar la respuesta.
    http_response_code(201);
    echo json_encode([
        "success" => true,
        "message" => "Merma registrada correctamente",
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
