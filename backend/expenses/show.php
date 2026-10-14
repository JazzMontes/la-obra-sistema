<?php
require_once __DIR__.'/../config/service_helpers.php';
method('GET');
$u=user(false);
$id=positiveId($_GET['id']??null);$e=expense($id);expenseAccess($e,$u);if($u['role']==='employee'&&substr($e['created_at'],0,10)!==date('Y-m-d'))out(403,'El empleado solo consulta gastos de su jornada actual');if($u['role']==='admin')$e['history']=rows('SELECT * FROM expense_history WHERE expense_id=? ORDER BY changed_at DESC,id DESC',[$id]);out(200,'Gasto consultado',['expense'=>$e]);
