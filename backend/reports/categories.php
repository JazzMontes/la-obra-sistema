<?php
require_once __DIR__ . '/common.php';
try {
    $rows = report_rows("SELECT DATE(pay.paid_at) AS date, c.id AS category_id, c.name AS category, SUM(oi.subtotal) AS amount FROM order_items oi INNER JOIN products p ON p.id=oi.product_id INNER JOIN categories c ON c.id=p.category_id INNER JOIN orders o ON o.id=oi.order_id INNER JOIN payments pay ON pay.order_id=o.id WHERE $reportWhere GROUP BY DATE(pay.paid_at),c.id,c.name ORDER BY date,c.id", ["category_id", "amount"]);
    report_output($rows);
} catch (Throwable $e) { report_error(500, 'No fue posible obtener el reporte'); }
