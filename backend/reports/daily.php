<?php
require_once __DIR__ . '/common.php';
try {
    $rows = report_rows("SELECT DATE(pay.paid_at) AS date, SUM(pay.amount) AS amount, COUNT(*) AS orders FROM payments pay INNER JOIN orders o ON o.id=pay.order_id WHERE $reportWhere GROUP BY DATE(pay.paid_at) ORDER BY date", ["amount", "orders"]);
    report_output($rows);
} catch (Throwable $e) { report_error(500, 'No fue posible obtener el reporte'); }
