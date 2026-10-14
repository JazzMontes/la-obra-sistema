<?php
require_once __DIR__.'/../config/service_helpers.php';
method('GET');
$u=user(true);
$id=positiveId($_GET['id']??null);expense($id);out(200,'Historial consultado',['history'=>rows('SELECT h.*,u.name AS changed_by_name FROM expense_history h JOIN users u ON u.id=h.changed_by WHERE h.expense_id=? ORDER BY h.changed_at DESC,h.id DESC',[$id])]);
