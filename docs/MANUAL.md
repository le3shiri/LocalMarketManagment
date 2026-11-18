# Manual de usuario

Guía rápida de LocalMarket para cada perfil. Cada persona ve **solo** las opciones de su rol. El administrador puede cambiar qué hace cada rol en **Administración → Roles y permisos**.

| Rol | Cuenta de demostración | Qué ve en el menú |
|---|---|---|
| Cliente | `cliente@localmarket.test` | Catálogo, Tienda (carrito, mis pedidos), avisos |
| Usuario de caja | `caja@localmarket.test` | Caja, Pedidos, Almacén (consulta) |
| Encargado de almacén | `almacen@localmarket.test` | Almacén completo |
| Administrador general | `admin@localmarket.test` | Todo: panel, ventas, pedidos, almacén, catálogo, administración |

Contraseña de todas las cuentas de demostración: `password` (solo en local).

---

## 1. Cliente

**Comprar online**
1. **Catálogo** → busca por nombre o filtra por categoría. Cada producto indica si está *Disponible*, *Últimas unidades* o *Agotado*.
2. Elige la cantidad: unidades, o en pasos de 0,25 kg en los productos por peso. Pulsa **Añadir**.
3. **Tienda → Carrito**: ajusta las cantidades y revisa el total. Ahí verás también cuánto falta para el envío gratis.
4. **Tramitar pedido**:
   - elige **recogida en tienda** (gratis) o **envío a domicilio**;
   - elige el día y la franja horaria;
   - elige cómo pagarás al recoger o recibir el pedido: **efectivo** o **tarjeta**;
   - pulsa **Confirmar pedido**.
5. Recibes el número de pedido (`LM-…`) y un aviso. El stock queda reservado para ti.

**Seguir el pedido**
- En **Tienda → Mis pedidos** está el historial. El detalle de cada pedido muestra su estado en directo: *Pendiente → En preparación → Listo → Entregado*.
- Cuando el pedido está **listo** recibes un aviso por email y en la campana 🔔.
- Mientras esté *Pendiente* puedes **cancelarlo** tú mismo.

**Repetir un pedido**: en el pedido, pulsa **Repetir pedido**. Los productos vuelven al carrito con el **precio de hoy**, ajustados a lo que hay disponible.

**Mi cuenta**: pulsa tu nombre para cambiar tus datos o tu contraseña.

---

## 2. Usuario de caja

**Vender en el mostrador (Caja)**
1. **Caja**: escanea el código de barras, o escribe el nombre o el SKU y pulsa Enter.
2. Para los productos por peso, escribe el peso de la báscula en la línea (por ejemplo `1,235`).
3. A la derecha se ve el total y el desglose de IVA.
4. Cobro:
   - **Efectivo**: escribe lo entregado o usa los botones +5/+10/+20/+50. El sistema calcula el cambio.
   - **Tarjeta**: cobra en el datáfono y, si quieres, anota el código de autorización. Nunca se guardan datos de la tarjeta.
5. Pulsa **Cobrar**. Se genera el ticket `T-…` (factura simplificada), que puedes imprimir.

**Pedidos de teléfono y WhatsApp**
1. **Pedidos → + Pedido por teléfono / + Pedido por WhatsApp**.
2. Escribe el teléfono. Si el cliente tiene cuenta, sus datos se rellenan solos.
3. En WhatsApp, pega el **mensaje original**: queda guardado en el pedido.
4. Busca y añade los productos y elige la entrega, la franja y el método de pago. Pulsa **Registrar pedido**.
5. Si ese teléfono ya tiene un pedido en la misma franja, el sistema avisa de un **posible duplicado**.

**Preparar y entregar**
- **Pedidos**: la lista de pedidos abiertos, ordenada por franja. Un botón avanza cada pedido al siguiente estado.
- Al **entregar**, escribe el **peso real** de los productos por peso y cómo ha pagado el cliente. El importe se recalcula.
- **Cancelar** un pedido exige un motivo, y el stock reservado se libera.

---

## 3. Encargado de almacén

Todo está en **Almacén**, organizado en pestañas:

| Pestaña | Para qué |
|---|---|
| **Inventario** | Existencias reales, tienda, online, reservado y disponible. Pulsa un producto para ver sus **lotes**. El mínimo de aviso se edita aquí. |
| **Movimientos** | El historial de todo lo que ha entrado o salido, con su responsable. Se exporta a CSV. |
| **Reposición** | Productos bajo mínimo, ordenados por urgencia, con un **pedido sugerido**. |
| **Caducidades** | Lotes que caducan pronto (lo primero de la lista es lo que debe salir antes) y caducados pendientes de **retirar**. |
| **Entrada** | Recepción de mercancía: reparto entre tienda y online, albarán y, en los perecederos, **lote y caducidad obligatorios**. |
| **Salidas** | Devolución a proveedor, consumo interno o donación, siempre con motivo. |
| **Mermas** | Producto dañado, caducado o retirado. Se valora a precio de venta y tiene su propio informe. |
| **Recuento** | Escribe lo que has contado: el sistema registra la diferencia, con un **motivo obligatorio**. |
| **Canales** | Transferencias entre tienda y online, por ejemplo cuando lo online se agota a media mañana. |

**Cada mañana** te llega un aviso 🔔 con los lotes que caducan pronto. Los caducados se **bloquean solos** cada noche para que no se vendan. Cuando los quites de la estantería, pulsa **Confirmar retirada física**.

---

## 4. Administrador general

- **Panel de control** (la página de inicio al entrar): ventas de hoy frente a la semana pasada, pedidos por estado y canal, ventas de 14 días, los productos más vendidos y alertas del almacén.
- **Ventas**:
  - **Ventas y pagos**: tickets y cobros por método;
  - **Informes** por día, semana o mes, con el IVA;
  - **Más vendidos** con su tendencia.

  Todo se puede imprimir y **exportar a CSV** (se abre directamente en Excel).
- **Pedidos → Supervisión**: todos los pedidos de los tres canales, con filtros e importes por canal.
- **Catálogo**:
  - productos con su precio y el IVA incluido, venta por unidad o por peso, código de barras e imagen;
  - el interruptor **A la venta / Retirado**;
  - las categorías.
- **Canales** (en Almacén): porcentaje del stock destinado a online, uno general y otro para frescos, y aplicación del reparto.
- **Administración**:
  - **Usuarios**: altas, cambios de rol, activar o desactivar cuentas;
  - **Roles y permisos**: qué puede hacer cada rol;
  - **Historial**: todas las operaciones, con un filtro de **operaciones sensibles** y la comprobación de que el historial **no ha sido manipulado**;
  - **Copias de seguridad**: crear y descargar copias. La restauración está explicada en [BACKUP.md](BACKUP.md).

---

## Guion de demostración (10 minutos)

Prepara los datos con `php artisan migrate:fresh --seed`: genera 6 semanas de actividad realista.

1. **Admin (2 min)**: el panel con las ventas reales, la tendencia y los más vendidos. Abre **Informes** por semana y exporta a CSV.
2. **Cliente (2 min)**: busca «tomate», añade 0,75 kg y tramita el pedido con recogida y pago con tarjeta. Enseña el seguimiento en directo.
3. **Caja (3 min)**:
   - registra un pedido por WhatsApp pegando el mensaje;
   - en **Pedidos**, avanza el pedido del cliente hasta *Listo*. El cliente recibe el aviso.
   - entrégalo con el peso real;
   - haz una venta en **Caja** escaneando dos productos y cobra en efectivo con cambio.
4. **Almacén (2 min)**:
   - registra una entrada de pollo con lote y caducidad;
   - enseña **Caducidades** y **Reposición**;
   - haz un recuento con diferencia y búscalo en **Movimientos**.
5. **Seguridad (1 min)**: en **Historial**, enseña el sello «Historial íntegro» y el filtro de operaciones sensibles. Para terminar, ejecuta `php artisan audit:verify`.
