# Modelo de datos

Diagrama entidad-relación **real** de LocalMarket: se genera directamente a partir de las migraciones aplicadas a la base de datos. Sustituye al «modelo conceptual» de la propuesta (figura 2).

```bash
php artisan docs:er-diagram --write   # regenera este diagrama
```

GitHub muestra el diagrama directamente (Mermaid). `PK` = clave primaria, `FK` = clave foránea, `UK` = valor único.

<!-- er:start -->
```mermaid
erDiagram
    audit_logs {
        bigint id PK
        bigint user_id FK
        varchar action
        tinyint is_sensitive
        varchar subject_type
        bigint subject_id
        varchar description
        text changes
        varchar ip_address
        varchar previous_hash
        varchar hash
        timestamp created_at
    }
    cart_items {
        bigint id PK
        bigint user_id FK
        bigint product_id FK
        decimal quantity
        timestamp created_at
        timestamp updated_at
    }
    categories {
        bigint id PK
        varchar name UK
        varchar slug UK
        varchar description
        bigint sort_order
        timestamp created_at
        timestamp updated_at
    }
    notifications {
        varchar id PK
        varchar type
        varchar notifiable_type
        bigint notifiable_id
        text data
        timestamp read_at
        timestamp created_at
        timestamp updated_at
    }
    order_items {
        bigint id PK
        bigint order_id FK
        bigint product_id FK
        varchar product_name
        varchar sku
        varchar unit_type
        decimal unit_price
        decimal quantity
        decimal delivered_quantity
        decimal line_total
        timestamp created_at
        timestamp updated_at
    }
    order_status_changes {
        bigint id PK
        bigint order_id FK
        bigint user_id FK
        varchar from_status
        varchar to_status
        varchar note
        timestamp created_at
    }
    orders {
        bigint id PK
        varchar code UK
        bigint user_id FK
        bigint created_by FK
        varchar channel
        varchar source_reference
        text source_message
        varchar status
        varchar fulfillment
        varchar payment_method
        varchar contact_name
        varchar contact_phone
        varchar delivery_address
        date slot_date
        varchar slot
        decimal subtotal
        decimal delivery_fee
        decimal total
        text notes
        timestamp created_at
        timestamp updated_at
    }
    payments {
        bigint id PK
        varchar payable_type
        bigint payable_id
        varchar method
        decimal amount
        decimal tendered
        decimal change
        varchar reference
        bigint user_id FK
        timestamp created_at
    }
    permission_role {
        bigint permission_id PK, FK
        bigint role_id PK, FK
    }
    permissions {
        bigint id PK
        varchar name UK
        varchar label
        varchar group
        timestamp created_at
        timestamp updated_at
    }
    product_stocks {
        bigint id PK
        bigint product_id FK
        decimal on_hand
        decimal store_qty
        decimal online_qty
        decimal reserved_qty
        decimal min_alert
        timestamp created_at
        timestamp updated_at
    }
    products {
        bigint id PK
        bigint category_id FK
        varchar name
        varchar slug UK
        varchar sku UK
        varchar barcode UK
        text description
        decimal price
        tinyint vat_rate
        varchar unit_type
        tinyint is_perishable
        varchar location
        varchar image_path
        tinyint is_active
        timestamp created_at
        timestamp updated_at
    }
    refund_items {
        bigint id PK
        bigint refund_id FK
        bigint sale_item_id FK
        decimal quantity
        decimal amount
        tinyint restocked
    }
    refunds {
        bigint id PK
        varchar code UK
        bigint sale_id FK
        bigint user_id FK
        varchar reason
        decimal amount
        timestamp created_at
        timestamp updated_at
    }
    roles {
        bigint id PK
        varchar name UK
        varchar label
        varchar description
        timestamp created_at
        timestamp updated_at
    }
    sale_items {
        bigint id PK
        bigint sale_id FK
        bigint product_id FK
        varchar product_name
        varchar sku
        varchar unit_type
        decimal unit_price
        tinyint vat_rate
        decimal quantity
        decimal line_total
    }
    sales {
        bigint id PK
        varchar code UK
        bigint user_id FK
        decimal total
        text vat_breakdown
        timestamp created_at
        timestamp updated_at
    }
    settings {
        varchar key PK
        varchar value
        timestamp created_at
        timestamp updated_at
    }
    stock_batches {
        bigint id PK
        bigint product_id FK
        varchar lot_code
        date expires_at
        decimal quantity_received
        decimal quantity_remaining
        varchar status
        date alerted_on
        bigint received_by FK
        varchar reference
        timestamp created_at
        timestamp updated_at
    }
    stock_movements {
        bigint id PK
        bigint product_id FK
        bigint user_id FK
        varchar type
        decimal store_delta
        decimal online_delta
        decimal reserved_delta
        decimal on_hand_after
        varchar reference
        varchar reason
        timestamp created_at
    }
    users {
        bigint id PK
        bigint role_id FK
        varchar name
        varchar email UK
        varchar phone
        varchar address
        timestamp email_verified_at
        varchar password
        tinyint is_active
        varchar remember_token
        timestamp created_at
        timestamp updated_at
    }
    write_offs {
        bigint id PK
        bigint product_id FK
        bigint stock_batch_id FK
        bigint user_id FK
        varchar type
        varchar channel
        decimal quantity
        decimal value
        varchar notes
        timestamp created_at
        timestamp updated_at
    }
    users |o--o{ audit_logs : "user_id"
    products ||--o{ cart_items : "product_id"
    users ||--o{ cart_items : "user_id"
    orders ||--o{ order_items : "order_id"
    products |o--o{ order_items : "product_id"
    orders ||--o{ order_status_changes : "order_id"
    users |o--o{ order_status_changes : "user_id"
    users |o--o{ orders : "created_by"
    users |o--o{ orders : "user_id"
    users |o--o{ payments : "user_id"
    permissions ||--o{ permission_role : "permission_id"
    roles ||--o{ permission_role : "role_id"
    products ||--o{ product_stocks : "product_id"
    categories ||--o{ products : "category_id"
    refunds ||--o{ refund_items : "refund_id"
    sale_items ||--o{ refund_items : "sale_item_id"
    sales ||--o{ refunds : "sale_id"
    users |o--o{ refunds : "user_id"
    products |o--o{ sale_items : "product_id"
    sales ||--o{ sale_items : "sale_id"
    users |o--o{ sales : "user_id"
    products ||--o{ stock_batches : "product_id"
    users |o--o{ stock_batches : "received_by"
    products ||--o{ stock_movements : "product_id"
    users |o--o{ stock_movements : "user_id"
    roles |o--o{ users : "role_id"
    products ||--o{ write_offs : "product_id"
    stock_batches |o--o{ write_offs : "stock_batch_id"
    users |o--o{ write_offs : "user_id"
```
<!-- er:end -->

## Cómo leerlo por módulos

| Módulo | Tablas | Notas |
|---|---|---|
| Usuarios y roles | `users`, `roles`, `permissions`, `permission_role` | Cada usuario tiene un rol. Los permisos son *abilities* del Gate (`can:stock.adjust`). |
| Catálogo | `categories`, `products` | `unit_type` (unidad / peso), `vat_rate` (4/10/21 %), `barcode` EAN-13, `is_perishable`. |
| Stock | `product_stocks`, `stock_batches`, `stock_movements`, `write_offs` | Dos stocks coordinados en `product_stocks` (tienda / online / reservado). `stock_movements` es el libro de movimientos: solo se añaden filas, nunca se modifican. |
| Tienda online | `cart_items`, `orders`, `order_items`, `order_status_changes` | Las líneas guardan una copia del nombre, el precio y el IVA del momento. `order_status_changes` es la línea de tiempo del pedido. |
| Caja | `sales`, `sale_items`, `refunds`, `refund_items` | Ticket `T-…` (factura simplificada) y devolución `D-…`. |
| Pagos | `payments` | **Polimórfica** (`payable_type` / `payable_id`): tickets, pedidos cobrados y devoluciones (importe negativo). |
| Seguridad | `audit_logs`, `settings`, `notifications` | `audit_logs` encadenado con SHA-256 (`previous_hash` → `hash`). La relación con el sujeto (`subject_type` / `subject_id`) es polimórfica. |

Las relaciones polimórficas (`payments.payable`, `audit_logs.subject`, `notifications.notifiable`) no aparecen como líneas en el diagrama porque no son claves foráneas de la base de datos: las resuelve Eloquent.

## Reglas que garantiza el esquema

- `disponible online = online_qty − reserved_qty`, nunca negativo. Todas las escrituras pasan por `StockService`, con bloqueo de fila (`lockForUpdate`).
- Se puede eliminar un producto sin perder la historia: las líneas de pedido, ticket y devolución guardan su copia y la clave foránea pasa a `NULL` (`nullOnDelete`).
- Las categorías con productos no se pueden borrar (`restrictOnDelete`).
- Los códigos públicos (`LM-000001`, `T-000001`, `D-000001`) salen del id autoincremental: son correlativos y no hay condiciones de carrera.
