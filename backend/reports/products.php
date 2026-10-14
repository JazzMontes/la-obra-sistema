<?php
require_once __DIR__ . '/common.php';
try {
    $rows = report_rows("SELECT p.id AS product_id, p.name AS name, SUM(oi.quantity) AS quantity, SUM(oi.subtotal) AS amount FROM order_items oi INNER JOIN products p ON p.id=oi.product_id INNER JOIN orders o ON o.id=oi.order_id INNER JOIN payments pay ON pay.order_id=o.id WHERE $reportWhere GROUP BY p.id,p.name ORDER BY quantity DESC,p.id", ["product_id", "quantity", "amount"]);
    report_output($rows);
} catch (Throwable $e) { report_error(500, 'No fue posible obtener el reporte'); }
