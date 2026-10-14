<?php
// Consulta el listado de ventas.
header("Content-Type: application/json");
require_once __DIR__ . "/../config/cors.php";
require_once __DIR__ . "/../config/database.php";

if ($_SERVER["REQUEST_METHOD"] !== "GET") {
    http_response_code(405);

// Enviar respuesta
    echo json_encode(["success" => false, "message" => "Método no permitido"]);
    exit;
}

// Validar autenticación
$headers = getallheaders();
$authorization = $headers["Authorization"] ?? "";
if (!preg_match('/Bearer\s+(\S+)/', $authorization, $matches)) {
    http_response_code(401);
    echo json_encode(["success" => false, "message" => "Token de autenticación requerido"]);
    exit;
}
$tokenHash = hash("sha256", $matches[1]);

// Consultar o preparar datos
$stmt = $pdo->prepare("SELECT u.id, u.status, u.role, s.expires_at FROM sessions s INNER JOIN users u ON s.user_id=u.id WHERE s.token_hash=:token_hash LIMIT 1");
$stmt->execute([":token_hash" => $tokenHash]);
$currentUser = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$currentUser) { http_response_code(401); echo json_encode(["success"=>false,"message"=>"Sesión inválida"]); exit; }
if (strtotime($currentUser["expires_at"]) <= time()) { http_response_code(401); echo json_encode(["success"=>false,"message"=>"La sesión ha expirado"]); exit; }
if ($currentUser["status"] !== "active") { http_response_code(403); echo json_encode(["success"=>false,"message"=>"La cuenta no tiene acceso al sistema"]); exit; }
if (!in_array($currentUser["role"], ["admin","employee"], true)) { http_response_code(403); echo json_encode(["success"=>false,"message"=>"No tienes permisos para realizar esta acción"]); exit; }

$where=[]; $params=[];
$allowed=["payment_status"=>["pending","paid"],"delivery_status"=>["pending","delivered"],"order_status"=>["open","confirmed","cancelled"]];
foreach($allowed as $key=>$values){
    if(isset($_GET[$key]) && $_GET[$key]!==""){
        if(!in_array($_GET[$key],$values,true)){ http_response_code(422); echo json_encode(["success"=>false,"message"=>"Filtro $key inválido"]); exit; }
        $where[]="o.$key=:$key"; $params[":$key"]=$_GET[$key];
    }
}
if(isset($_GET["payment_method_id"]) && $_GET["payment_method_id"]!==""){
    if(filter_var($_GET["payment_method_id"],FILTER_VALIDATE_INT)===false || (int)$_GET["payment_method_id"]<=0){ http_response_code(422); echo json_encode(["success"=>false,"message"=>"Filtro payment_method_id inválido"]); exit; }
    $where[]="p.payment_method_id=:pm"; $params[":pm"]=(int)$_GET["payment_method_id"];
}

// El empleado consulta únicamente SU historial del día actual.
// El administrador conserva el historial completo y puede filtrar por fecha.
if ($currentUser["role"] === "employee") {
    $where[] = "o.user_id=:current_user_id";
    $params[":current_user_id"] = (int)$currentUser["id"];
    $where[] = "DATE(o.created_at)=CURDATE()";
} elseif (isset($_GET["date"]) && $_GET["date"] !== "") {
    $date = $_GET["date"];
    $d = DateTime::createFromFormat('Y-m-d', $date);
    if (!$d || $d->format('Y-m-d') !== $date) { http_response_code(422); echo json_encode(["success"=>false,"message"=>"Filtro date inválido"]); exit; }
    $where[] = "DATE(o.created_at)=:history_date";
    $params[":history_date"] = $date;
}

$sql="SELECT o.id,o.user_id,u.name AS user_name,o.created_at,o.confirmed_at,o.delivered_at,o.cancelled_at,o.total,o.order_status,o.payment_status,o.delivery_status,pm.id AS payment_method_id,pm.name AS payment_method,p.paid_at FROM orders o INNER JOIN users u ON u.id=o.user_id LEFT JOIN payments p ON p.order_id=o.id LEFT JOIN payment_methods pm ON pm.id=p.payment_method_id".($where?" WHERE ".implode(" AND ",$where):"")." ORDER BY o.created_at DESC";
$stmt=$pdo->prepare($sql); $stmt->execute($params); $rows=$stmt->fetchAll(PDO::FETCH_ASSOC);
$itemStmt=$pdo->prepare("SELECT oi.product_id,p.name,oi.quantity,oi.unit_price,oi.subtotal FROM order_items oi INNER JOIN products p ON p.id=oi.product_id WHERE oi.order_id=:id ORDER BY oi.id");
foreach($rows as &$r){
    $r["id"]=(int)$r["id"]; $r["user_id"]=(int)$r["user_id"]; $r["total"]=(float)$r["total"];
    $r["payment_method_id"]=$r["payment_method_id"]===null?null:(int)$r["payment_method_id"];
    $itemStmt->execute([":id"=>$r["id"]]); $r["items"]=$itemStmt->fetchAll(PDO::FETCH_ASSOC);
    foreach($r["items"] as &$i){ $i["product_id"]=(int)$i["product_id"]; $i["quantity"]=(int)$i["quantity"]; $i["unit_price"]=(float)$i["unit_price"]; $i["subtotal"]=(float)$i["subtotal"]; } unset($i);
}
unset($r);
echo json_encode(["success"=>true,"scope"=>$currentUser["role"]==="employee"?"today_own":"all","sales"=>$rows]);
