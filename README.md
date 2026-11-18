# LocalMarket

Plataforma de tienda online, gestión de pedidos y control de stock para **LocalMarket**, un comercio de alimentación de barrio de Barcelona.

Proyecto de la asignatura *Entornos de Desarrollo* (1.º DAM / DAW). El Product Backlog y la planificación de Sprints se gestionan en la plataforma Agile (proyecto **SUP – Supermercado Omnicanal**); cada historia tiene su issue en GitHub con la referencia `SUP-XX`.

## Stack

| Pieza | Tecnología |
|---|---|
| Backend | Laravel 12 (PHP 8.2) |
| Interfaz | Blade + Livewire 3 + Tailwind CSS 4 |
| Base de datos | MySQL (XAMPP) · SQLite en memoria para los tests |
| CI | GitHub Actions (`.github/workflows/tests.yml`) |

## Módulos

- **Usuarios y roles**: inicio y cierre de sesión, registro de clientes, gestión de cuentas, roles y permisos (mínimo privilegio).
- **Catálogo**: productos por unidad o por peso aproximado, perecederos, categorías, activación de la venta.
- **Tienda online**: búsqueda, disponibilidad en tiempo real, carrito, checkout, «Mis pedidos», **repetir un pedido anterior** con los precios de hoy y seguimiento del estado en directo.
- **Gestión de pedidos** (`/gestion/pedidos`): pedidos de web, teléfono y WhatsApp en un único panel; estados Pendiente → En preparación → Listo → Entregado, cancelación con motivo y detección de duplicados.
- **Almacén** (`/almacen`): dos stocks coordinados (tienda física / online) y libro de movimientos con exportación CSV. Incluye:
  - entradas con lote y caducidad (obligatorios en perecederos); las salidas consumen primero el lote que caduca antes (FEFO);
  - salidas con motivo, mermas valoradas, ajustes tras recuento físico y transferencias entre canales;
  - reparto online configurable (25 % general y 20 % en frescos, por defecto);
  - mínimos con lista de reposición y lotes próximos a caducar;
  - los lotes caducados se bloquean para que no se vendan.

  La entrega de un pedido descuenta lo reservado (con el peso real en productos por peso) y la cancelación lo libera.
- **Caja / TPV** (`/caja`): lectura de códigos de barras EAN-13, cantidades y pesos, desglose de IVA (4/10/21 %), cobro en efectivo (con cambio) o con tarjeta (datáfono), ticket imprimible (factura simplificada) y devoluciones con sus reglas (15 días, perecederos que no vuelven a la venta).
- **Pagos de pedidos**: el cliente elige efectivo o tarjeta al recoger o recibir el pedido, y el cobro se registra con el importe final.
- **Ventas** (`/admin/ventas`): ventas, ticket medio, cobros por método (caja + pedidos) y devoluciones.
- **Supervisión** (`/admin/pedidos`): pedidos e importes por canal, filtros y pedidos por día y estado.
- **Avisos**: email y notificación en la web al confirmar, cuando el pedido está listo y si se cancela.
- **Panel** (`/admin`): ventas de hoy frente a la semana anterior, pedidos por estado y canal, ventas de 14 días y alertas de almacén.
- **Informes** (`/admin/informes`): ventas por día, semana o mes (caja, pedidos, devoluciones, neto e IVA) y **productos más vendidos** con tendencia. Imprimibles.
- **Exportaciones CSV** (compatibles con Excel): informes, tickets, pedidos, más vendidos, mermas y movimientos, siempre con los filtros de la pantalla y registradas en el historial.
- **Historial** (`/admin/historial`): registro de operaciones con marca de **operaciones sensibles**. Las entradas están encadenadas con hash SHA-256, así que cualquier modificación o borrado se detecta (`php artisan audit:verify`).
- **Copias de seguridad** (`/admin/copias`): copia diaria comprimida con rotación, descarga y restauración por consola. Ver [docs/BACKUP.md](docs/BACKUP.md).

## Documentación

| Documento | Contenido |
|---|---|
| [Manual de usuario](docs/MANUAL.md) | Tareas por rol y guion de demostración de 10 minutos |
| [Arquitectura](docs/ARQUITECTURA.md) | Módulos, servicios, flujo de stock, ciclo del pedido, seguridad y decisiones técnicas |
| [Modelo de datos](docs/MODELO_DE_DATOS.md) | Diagrama entidad-relación generado del esquema real (`php artisan docs:er-diagram --write`) |
| [Informe de pruebas](docs/PRUEBAS.md) | 341 tests, pruebas imprescindibles de la propuesta, CI en SQLite y MariaDB |
| [Copias de seguridad](docs/BACKUP.md) | Copia, restauración y prueba de restauración |

## Puesta en marcha (XAMPP)

```bash
git clone https://github.com/le3shiri/LocalMarketManagment.git
cd LocalMarketManagment
composer install
npm install && npm run build
cp .env.example .env
php artisan key:generate
```

1. Arranca **MySQL** desde el panel de XAMPP y crea la base de datos `localmarket` (phpMyAdmin o `CREATE DATABASE localmarket;`).
2. Ejecuta las migraciones con datos de demostración:

```bash
php artisan migrate --seed
php artisan storage:link
php artisan serve
```

La aplicación queda en <http://localhost:8000>.

En entorno local, el seed genera también **6 semanas de actividad realista** (tickets, pedidos de los tres canales, devoluciones, mermas y caducidades), así que el panel y los informes tienen datos desde el primer momento. Para una tienda vacía usa `SEED_DEMO_ACTIVITY=false`.

En Windows/XAMPP, añade al `.env` las rutas de las herramientas de copia:

```dotenv
BACKUP_MYSQLDUMP="C:/xampp/mysql/bin/mysqldump.exe"
BACKUP_MYSQL="C:/xampp/mysql/bin/mysql.exe"
```

### Cuentas de demostración

Todas usan la contraseña `password` (solo para entorno local).

| Rol | Email |
|---|---|
| Administrador general | `admin@localmarket.test` |
| Usuario de caja | `caja@localmarket.test` |
| Encargado de almacén | `almacen@localmarket.test` |
| Cliente | `cliente@localmarket.test` |

## Tareas programadas

```bash
php artisan stock:block-expired   # 00:05 · bloquea los lotes caducados
php artisan stock:expiry-alerts   # 07:30 · avisa al almacén de lo que caduca pronto
php artisan backup:run            # 02:00 · copia de seguridad de la base de datos
```

En producción basta con una entrada de cron: `* * * * * php artisan schedule:run`.

## Tests

```bash
php artisan test
```

## Flujo de trabajo Git

- `main`: versión entregada al final de cada Sprint (etiquetas `v0.1.0`, `v0.2.0`, …).
- `develop`: integración continua del Sprint en curso.
- `feature/SUP-XX-descripcion`: una rama por historia o tarea, fusionada en `develop` con `--no-ff`.
- Mensajes de commit con [Conventional Commits](https://www.conventionalcommits.org/es/) y la referencia de la historia: `feat(cart): validar stock online al añadir (SUP-64)`.
- Al cerrar el Sprint se abre un Pull Request `develop → main`.

## Equipo

Mohamed El Achiri · Victor Escobar · Rubi Fernández · Mohamed 2
