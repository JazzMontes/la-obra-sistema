<?php
// Consulta el listado de gastos y salidas.
require_once __DIR__.'/../config/service_helpers.php';
method('GET');
$u=user(false);
$where=[];$params=[];if($u['role']==='employee'){$where[]='e.user_id=?';$params[]=$u['id'];$where[]='DATE(e.created_at)=CURDATE()';}else{if(isset($_GET['user_id'])&&$_GET['user_id']!==''){$where[]='e.user_id=?';$params[]=positiveId($_GET['user_id']);}foreach(['date_from'=>'>=','date_to'=>'<='] as $k=>$op)if(isset($_GET[$k])&&$_GET[$k]!==''){$where[]='DATE(e.created_at) '.$op.' ?';$params[]=dateval($_GET[$k]);}}if(isset($_GET['status'])&&$_GET['status']!==''){if(!in_array($_GET['status'],['active','cancelled'],true))out(422,'Estado inválido');$where[]='e.status=?';$params[]=$_GET['status'];}$sql='SELECT e.*,u.name AS user_name FROM expenses e JOIN users u ON u.id=e.user_id'.($where?' WHERE '.implode(' AND ',$where):'').' ORDER BY e.created_at DESC,e.id DESC';out(200,'Gastos consultados',['scope'=>$u['role']==='admin'?'all':'today_own','expenses'=>rows($sql,$params)]);
