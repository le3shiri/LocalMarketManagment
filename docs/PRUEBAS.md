# Informe de pruebas

## Resumen

| | |
|---|---|
| Tests automáticos | **341** tests de feature en 58 ficheros (1055 aserciones) |
| Bases de datos | Todo se ejecuta en **SQLite** (en memoria, rápido) y en **MariaDB 10.4** (la misma versión que XAMPP) |
| Integración continua | GitHub Actions en cada push y pull request (`.github/workflows/tests.yml`) |
| Resultado | 341 / 341 en verde en las dos bases de datos |

```bash
php artisan test                    # SQLite en memoria (por defecto)
DB_CONNECTION=mysql DB_DATABASE=localmarket_test php artisan test   # MariaDB / MySQL
```

## Estrategia

- **Tests de feature** que recorren la aplicación como un usuario: peticiones HTTP, componentes Livewire, comandos de Artisan y servicios. Las reglas de negocio están en los servicios, así que se prueban directamente y también a través de las pantallas.
- Cada historia del Product Backlog tiene sus tests en el mismo commit que la implementa (`feat(…): … (SUP-XX)`).
- Cada **bug** encontrado incluye un **test de regresión** en su rama `fix/…`.
- Las pruebas no dependen del orden de ejecución ni de los ids autoincrementales: leen los códigos `LM-`, `T-` y `D-` de los propios registros.
- Las fechas son deterministas (`travelTo`) en todo lo que depende del día: franjas, caducidades e informes.

## Tests por módulo

| Módulo | Ficheros | Tests | Qué cubre |
|---|---:|---:|---|
| Autenticación (`Auth`) | 3 | 12 | Login con bloqueo, registro y logout |
| Administración (`Admin`) | 15 | 87 | Usuarios, roles, catálogo, auditoría con hash, panel, informes, más vendidos, exportaciones y copias |
| Carrito (`Cart`) | 4 | 25 | Añadir y editar líneas, validación de stock online, totales |
| Checkout | 2 | 15 | Franjas, códigos correlativos y **reserva indivisible** |
| Pedidos (`Orders`) | 10 | 66 | Estados, entrega con peso real, cancelación, teléfono, WhatsApp, avisos, repetir pedido |
| Caja (`Pos`) | 5 | 37 | Escáner, IVA, efectivo y tarjeta, ticket, devoluciones |
| Almacén (`Warehouse`) | 12 | 66 | Inventario, entradas, salidas, recuentos, canales, lotes FEFO, caducidades, alertas, mermas |
| General | 7 | 33 | Catálogo, búsqueda, disponibilidad, perfil, navegación, datos de demo, diagrama ER |
| **Total** | **58** | **341** | |

## Pruebas imprescindibles de la propuesta (sección 5.4)

| Prueba de la propuesta | Test |
|---|---|
| Dos pedidos simultáneos de la última unidad: solo uno se confirma | `Checkout/StockReservationTest::test_two_customers_ordering_the_last_unit_only_one_gets_it` |
| Un pedido cancelado libera exactamente lo que reservó | `Orders/CancelOrderTest::test_staff_cancel_releases_exactly_the_reserved_stock` |
| Un producto por peso entregado con otro peso deja el stock cuadrado | `Orders/OrderStockLifecycleTest::test_weighted_product_delivered_with_a_different_weight_keeps_stock_consistent` |
| Una transferencia mayor que el stock disponible se rechaza | `Warehouse/ChannelAllocationTest::test_a_transfer_bigger_than_the_available_stock_is_rejected` |
| Un ajuste hecho por un usuario sin permiso se rechaza | `Warehouse/StockCountAdjustmentTest::test_an_adjustment_by_a_user_without_permission_is_rejected` |
| Tras un recuento con diferencias, el libro conserva motivo y responsable | `Warehouse/StockCountAdjustmentTest::test_after_a_count_with_differences_the_ledger_keeps_reason_and_responsible` |

Además:

- **Reserva todo o nada**: si falta una línea, no se reserva ninguna (`StockReservationTest::test_reservation_is_all_or_nothing_across_lines`).
- **Nunca por debajo de cero**: entradas, salidas, ventas, entregas, transferencias y recuentos lo comprueban cada uno en su test.
- **Datos de demostración coherentes**: tras generar 6 semanas de actividad, lo reservado coincide exactamente con lo que tienen los pedidos abiertos, todos los tickets y pedidos entregados están cobrados y la cadena de auditoría es válida (`DemoActivitySeederTest`).

## Integración continua

Cada push y cada pull request ejecuta dos trabajos:

1. **SQLite**: compila los assets, ejecuta la suite completa y comprueba que **todas las migraciones son reversibles** (migrar → revertir todo → migrar).
2. **MariaDB 10.4**: la suite completa (incluidas las copias con `mysqldump`) y la reversibilidad de las migraciones en MySQL.

## Pruebas manuales en el navegador

Cada Sprint se ha probado a mano antes de la entrega. Esas pruebas encontraron cuatro errores, que se corrigieron con su test de regresión:

| Error | Dónde se encontró | Corrección |
|---|---|---|
| Dos lecturas rápidas del escáner se perdían o se juntaban | Caja, Sprint 4 | El código se pasa directamente a `scan()` y el campo se vacía en el cliente |
| Los contadores de pedidos mostraban «2.00» | Panel, Sprint 6 | El alias `total` chocaba con el cast decimal de `orders.total` |
| Una migración no era reversible en SQLite | Copias, Sprint 6 | Se borra el índice antes que la columna; se añadió el paso de CI de migraciones reversibles |
| El menú de administración se salía por la derecha | Menú agrupado | Los menús cercanos al borde se abren hacia la izquierda |

## Validación en MySQL (XAMPP)

En XAMPP (MariaDB 10.4.32):

- se ha ejecutado la suite completa;
- se ha generado el conjunto de demostración (426 tickets y 117 pedidos);
- se ha hecho una copia con `mysqldump`, después se han **borrado todas las ventas** y se han **restaurado**: han vuelto las 426;
- se ha verificado la cadena de auditoría.
