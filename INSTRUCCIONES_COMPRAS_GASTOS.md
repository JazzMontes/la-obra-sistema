# Servicio de Compras y Gastos — La Obra

Se añadieron **únicamente** `backend/purchases/` (5 endpoints), `backend/expenses/` (6 endpoints) y `backend/config/service_helpers.php`. Se conservaron los demás archivos originales.

## Rutas

| Método | Ruta | Acceso |
|---|---|---|
| POST | purchases/create.php | admin |
| GET | purchases/index.php | admin |
| GET | purchases/show.php?id=1 | admin |
| PATCH | purchases/update.php | admin |
| GET | purchases/prices.php?input_id=1 | admin |
| POST | expenses/create.php | admin, employee |
| GET | expenses/index.php | admin (todos), employee (propios del día) |
| GET | expenses/show.php?id=1 | admin; empleado propio del día |
| PATCH | expenses/update.php | admin; empleado propio |
| PATCH | expenses/cancel.php | admin; empleado propio |
| GET | expenses/history.php?id=1 | admin |

Enviar `Authorization: Bearer TOKEN` en todos. En POST y PATCH, `Content-Type: application/json`.

Ejemplo `POST purchases/create.php`:
```json
{"purchase_date":"2026-10-13","supplier":"Proveedor ejemplo","payment_method_id":1,"items":[{"input_id":1,"quantity":2.5,"unit_cost":25.50}]}
```

Ejemplo `PATCH purchases/update.php` (permite modificar campos individuales, `items` reemplaza el detalle completo):
```json
{"id":1,"supplier":"Proveedor corregido","change_reason":"Corrección de proveedor"}
```

Ejemplo `POST expenses/create.php`:
```json
{"amount":85,"reason":"Compra urgente de queso durante el turno"}
```

Ejemplo `PATCH expenses/update.php`:
```json
{"id":1,"amount":80,"change_reason":"Corrección del monto capturado"}
```

Ejemplo `PATCH expenses/cancel.php`:
```json
{"id":1,"cancellation_reason":"Registro duplicado"}
```

## Limitaciones pendientes de integración

**Cortes de caja:** la tabla de cortes y la relación `cash_closing_id` aún no existen en la base de datos entregada. No se han inventado ni añadido. Por ello **NO está implementado el bloqueo de edición/cancelación después del cierre**, ni la vinculación automática de gastos a un corte abierto. Debe integrarse antes de considerar terminado el módulo de caja. El empleado ve sus gastos del día actual como solución provisional, no un turno real.

**Pruebas:** se comprobó la sintaxis PHP con `php -l`. No se han ejecutado pruebas de integración contra tu servidor MariaDB/XAMPP ni Postman; revisa con respaldo y entorno de pruebas.

**Historial de compras:** `purchase_history` se almacena en cada corrección; no se agregó endpoint para consultarlo porque no figuraba en los cinco endpoints aprobados. `prices.php` muestra el historial de precios según los detalles de compras vigentes.

El código usa las cinco tablas creadas previamente y el esquema original de `users`, `sessions`, `inputs` y `payment_methods`.
