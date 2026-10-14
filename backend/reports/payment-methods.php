<?php
require_once __DIR__ . '/common.php';
try {
    $rows = report_rows("SELECT pm.id AS method_id, pm.name AS name, SUM(pay.amount) AS amount, COUNT(*) AS orders FROM payments pay INNER JOIN orders o ON o.id=pay.order_id INNER JOIN payment_methods pm ON pm.id=pay.payment_method_id WHERE $reportWhere GROUP BY pm.id,pm.name ORDER BY amount DESC,pm.id", ["method_id", "amount", "orders"]);
    report_output($rows);
} catch (Throwable $e) { report_error(500, 'No fue posible obtener el reporte'); }
