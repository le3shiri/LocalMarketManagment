# Diseño de la interfaz

La interfaz sigue el **LocalMarket design system**, creado con el flujo de trabajo UX/UI:

1. **Brief:** [design-brief.md](../design-brief.md).
2. **Exploración en el lienzo de diseño:**
   - tres direcciones;
   - tres variaciones de la elegida;
   - crítica y corrección.

   El proceso está en [ui-progress.md](../ui-progress.md).
3. **Sistema de diseño:** se fijó a partir de la pantalla aprobada, la variante *Estanterías* del catálogo.

| Recurso | Enlace |
|---|---|
| Lienzo de diseño (todas las rondas) | https://claude.ai/artifact/B4HimDSdyjLrjLqad8mSfg |
| Sistema de diseño (tokens, componentes, guía) | https://claude.ai/artifact/JLFrP6bAab7hy3GRXveyLn |

## La idea

El catálogo se recorre como la tienda: **estantería a estantería**. Cada sección tiene un color suave: la estantería y su chip usan el tono claro, y el título usa el oscuro. Los productos son tarjetas blancas encima. Solo hay un color para las acciones (azul `action`), y cada producto se añade a la cesta en dos toques.

## Tokens en el código

Los tokens están en el bloque `@theme` de [`resources/css/app.css`](../resources/css/app.css). En las vistas se usan por su nombre, nunca con el valor hexadecimal:

| Grupo | Clases de Tailwind |
|---|---|
| Fondos | `bg-page`, `bg-card`, `bg-sunken`, `bg-soldout` |
| Texto | `text-ink`, `text-ink-soft`, `text-ink-muted`, `text-ink-disabled` |
| Líneas | `border-line`, `border-line-strong`, `border-line-dashed` |
| Acción | `bg-action`, `hover:bg-action-hover`, `text-on-action`. El anillo de foco (`action-ring`) es global |
| Confirmación | `bg-success`, `bg-success-soft`, `text-success-ink` |
| Stock | `text-stock-ok`, `text-stock-low`, `text-stock-out` |
| Secciones | `bg-<sección>-tint`, `text-<sección>-ink`, `bg-<sección>-ink`, con `fruta`, `carne`, `lacteos`, `panaderia`, `despensa`, `bebidas` y `otros`, a través de `App\Support\SectionColors::for($slug)` |
| Forma | `rounded-chip` (9), `rounded-control` (12), `rounded-card` (16), `rounded-shelf` (24); `shadow-card`, `shadow-selected` |

Tipografía: **Manrope**, de Google Fonts, con pesos de 400 a 800.

**Transición.** Las pantallas que todavía no se han rediseñado usan las paletas `stone` y `emerald` de Tailwind. En `@theme` esas paletas apuntan al sistema: `emerald` pasa a ser el azul de acción y `stone` los grises fríos. Así toda la aplicación ya se ve coherente. Cada vez que se rediseña una pantalla, se cambian sus clases por los tokens con nombre.

## Componentes Blade

| Componente | Archivo |
|---|---|
| Shelf (estantería) | `components/shelf.blade.php` |
| ProductCard | `components/product-card.blade.php` |
| WeightSelector y UnitStepper | `livewire/add-to-cart.blade.php` (modo `compact`) |
| StockStatus | `components/availability.blade.php` |
| Button, Input, Card, NavLink, NavGroup | `components/*.blade.php` |

## Reglas de accesibilidad

- El texto tiene un contraste mínimo de 4,5:1, y los bordes de los controles y el anillo de foco de 3:1.
- Todo lo que se puede pulsar mide al menos 44 px; el botón principal mide 48 px.
- El stock siempre se muestra con un punto y una palabra, nunca solo con color.
- La cantidad de los productos por peso se elige con botones de radio dentro de un `fieldset` con `legend`.
- Un producto agotado no se atenúa con opacidad: usa `bg-soldout` y `text-ink-disabled`.
- Hay un enlace «Saltar al contenido», y las animaciones se desactivan con `prefers-reduced-motion`.
