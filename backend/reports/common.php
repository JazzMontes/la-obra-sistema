<?php
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');
require_once __DIR__ . '/../config/cors.php';
require_once __DIR__ . '/../config/database.php';
function report_error($status, $message) {
    http_response_code($status);
    echo json_encode(['success' => false, 'message' => $message], JSON_UNESCAPED_UNICODE);
    exit;
}
if ($_SERVER['REQUEST_METHOD'] !== 'GET') report_error(405, 'Método no permitido');
try {
    $headers = array_change_key_case(getallheaders(), CASE_LOWER);
    $authorization = $headers['authorization'] ?? '';
    if (!preg_match('/^Bearer\s+(\S+)$/i', $authorization, $matches)) report_error(401, 'Token de autenticación requerido');
    $stmt = $pdo->prepare('SELECT u.status, u.role, s.expires_at FROM sessions s INNER JOIN users u ON s.user_id=u.id WHERE s.token_hash=:token_hash LIMIT 1');
    $stmt->execute([':token_hash' => hash('sha256', $matches[1])]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$user || strtotime($user['expires_at']) <= time()) report_error(401, 'La sesión es inválida o ha expirado');
    if ($user['status'] !== 'active' || $user['role'] !== 'admin') report_error(403, 'Los reportes están disponibles para el administrador');
} catch (Throwable $e) { report_error(500, 'No fue posible verificar la sesión'); }
function report_date($key, $default) {
    $value = $_GET[$key] ?? $default;
    if (!is_string($value)) report_error(422, 'Fecha inválida');
    $date = DateTimeImmutable::createFromFormat('!Y-m-d', $value);
    if (!$date || $date->format('Y-m-d') !== $value) report_error(422, 'Utiliza fechas válidas con formato AAAA-MM-DD');
    return $date;
}
$fromDate = report_date('from', date('Y-m-01'));
$toDate = report_date('to', date('Y-m-d'));
if ($fromDate > $toDate) report_error(422, 'La fecha inicial no puede ser posterior a la final');
if ($fromDate->diff($toDate)->days > 365) report_error(422, 'Selecciona un rango de hasta 366 días');
$reportParams = [':from' => $fromDate->format('Y-m-d 00:00:00'), ':until' => $toDate->modify('+1 day')->format('Y-m-d 00:00:00')];
// Intervalo semiabierto: incluye todo el último día sin perder fracciones de segundo.
$reportWhere = "o.order_status='confirmed' AND o.payment_status='paid' AND pay.paid_at >= :from AND pay.paid_at < :until";
function report_rows($sql, $numericColumns) {
    global $pdo, $reportParams;
    $stmt = $pdo->prepare($sql);
    $stmt->execute($reportParams);
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
    foreach ($rows as &$row) foreach ($numericColumns as $column) $row[$column] = (float)$row[$column];
    unset($row);
    return $rows;
}
function report_output($rows) {
    global $fromDate, $toDate;
    echo json_encode(['success' => true, 'from' => $fromDate->format('Y-m-d'), 'to' => $toDate->format('Y-m-d'), 'data' => $rows], JSON_UNESCAPED_UNICODE);
}
