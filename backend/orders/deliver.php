<?php
header("Content-Type: application/json");
require_once __DIR__ . "/../config/cors.php";
require_once __DIR__ . "/../config/database.php";

if ($_SERVER["REQUEST_METHOD"] !== "PATCH") {
    http_response_code(405);
    echo json_encode(["success" => false, "message" => "Método no permitido"]);
    exit;
}

$headers = getallheaders();
$authorization = $headers["Authorization"] ?? "";
if (!preg_match('/Bearer\s+(\S+)/', $authorization, $matches)) {
    http_response_code(401);
    echo json_encode(["success" => false, "message" => "Token de autenticación requerido"]);
    exit;
}
$tokenHash = hash("sha256", $matches[1]);
$stmt = $pdo->prepare("SELECT u.id, u.status, u.role, s.expires_at FROM sessions s INNER JOIN users u ON s.user_id=u.id WHERE s.token_hash=:token_hash LIMIT 1");
$stmt->execute([":token_hash" => $tokenHash]);
$currentUser = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$currentUser) {
    http_response_code(401);
    echo json_encode(["success" => false, "message" => "Sesión inválida"]);
    exit;
}
if (strtotime($currentUser["expires_at"]) <= time()) {
    http_response_code(401);
    echo json_encode(["success" => false, "message" => "La sesión ha expirado"]);
    exit;
}
if ($currentUser["status"] !== "active") {
    http_response_code(403);
    echo json_encode(["success" => false, "message" => "La cuenta no tiene acceso al sistema"]);
    exit;
}
if (!in_array($currentUser["role"], ["admin", "employee"], true)) {
    http_response_code(403);
    echo json_encode(["success" => false, "message" => "No tienes permisos para realizar esta acción"]);
    exit;
}

$data=json_decode(file_get_contents("php://input"),true);$id=$data["order_id"]??null;if(filter_var($id,FILTER_VALIDATE_INT)===false||(int)$id<=0){http_response_code(422);echo json_encode(["success"=>false,"message"=>"El id del pedido no es válido"]);exit;}$id=(int)$id;
$stmt=$pdo->prepare("SELECT order_status,delivery_status FROM orders WHERE id=:id");$stmt->execute([":id"=>$id]);$o=$stmt->fetch(PDO::FETCH_ASSOC);if(!$o){http_response_code(404);echo json_encode(["success"=>false,"message"=>"Pedido no encontrado"]);exit;}if($o["order_status"]!=="confirmed"){http_response_code(409);echo json_encode(["success"=>false,"message"=>"Solo se puede entregar un pedido confirmado"]);exit;}if($o["delivery_status"]==="delivered"){http_response_code(409);echo json_encode(["success"=>false,"message"=>"El pedido ya fue entregado"]);exit;}
$stmt=$pdo->prepare("UPDATE orders SET delivery_status='delivered',delivered_at=NOW() WHERE id=:id");$stmt->execute([":id"=>$id]);echo json_encode(["success"=>true,"message"=>"Pedido marcado como entregado"]);
