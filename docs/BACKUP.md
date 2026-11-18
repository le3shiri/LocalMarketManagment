# Copias de seguridad y restauración

Procedimiento de la historia **SUP-61 (US-53)**. Está pensado para la persona responsable técnica de LocalMarket.

## Qué se copia

Toda la base de datos: catálogo, stock y lotes, pedidos, ventas, pagos, usuarios e historial de auditoría.

| Base de datos | Herramienta | Fichero |
|---|---|---|
| MySQL / MariaDB (XAMPP, producción) | `mysqldump --single-transaction` (copia coherente sin parar la tienda) | `localmarket-AAAAMMDD-HHMMSS.sql.gz` |
| SQLite (desarrollo) | `VACUUM INTO` | `localmarket-AAAAMMDD-HHMMSS.sqlite.gz` |

Las copias se comprimen con gzip y se guardan en `storage/app/backups`, fuera de la carpeta pública. Se conservan las **14** más recientes (`BACKUP_KEEP`).

Las imágenes de producto (`storage/app/public/products`) son ficheros: inclúyelas en la copia del servidor o del disco.

## Configuración (`.env`)

```dotenv
BACKUP_KEEP=14
# XAMPP en Windows:
BACKUP_MYSQLDUMP="C:\xampp\mysql\bin\mysqldump.exe"
BACKUP_MYSQL="C:\xampp\mysql\bin\mysql.exe"
```

La contraseña de la base de datos se pasa a las herramientas por la variable de entorno `MYSQL_PWD`. Nunca aparece en la línea de comandos ni en los registros.

## Copia automática

El planificador de Laravel lanza `backup:run` **cada día a las 02:00**. En el servidor solo hace falta una entrada de cron:

```cron
* * * * * cd /ruta/a/localmarket && php artisan schedule:run >> /dev/null 2>&1
```

En Windows, crea una tarea programada que ejecute `php artisan schedule:run` cada minuto.

## Copia manual

- Desde la web: **Copias → Crear copia ahora** (solo con el permiso `backups.manage`).
- Desde la consola:

```bash
php artisan backup:run
```

**Regla 3-2-1:** descarga al menos una copia por semana desde la pantalla *Copias* y guárdala fuera del servidor (disco externo o nube de la tienda).

## Restaurar

Restaurar **reemplaza todos los datos actuales**, por eso solo se puede hacer desde la consola:

```bash
php artisan backup:restore                                  # elige de una lista
php artisan backup:restore localmarket-20261118-020000.sql.gz
```

1. Pon la tienda en mantenimiento: `php artisan down`.
2. Ejecuta `backup:restore` y confirma.
   - Antes de restaurar se crea **automáticamente** una copia de los datos actuales, para poder deshacer la operación.
   - El comando indica el nombre de esa copia.
3. Comprueba la integridad del historial: `php artisan audit:verify`.
4. Vuelve a abrir la tienda: `php artisan up`.

Cada copia y cada restauración quedan en el historial como **operación sensible**.

## Prueba de restauración (recomendado cada mes)

Restaura la última copia en un entorno de pruebas (otra base de datos o un XAMPP local) y comprueba que la aplicación arranca y que los últimos pedidos aparecen. Una copia que nunca se ha probado no es una copia fiable.
