# LocalMarket: UI design brief

## Product

LocalMarket is a neighbourhood food shop in Barcelona with 14 staff and many regular customers. The platform gives the shop an online store, one panel for orders from the web, phone and WhatsApp, a counter till (POS), and stock control with batches and expiry dates. There is a single stock behind all of it, so nothing is ever sold twice.

**Problem statement:** Regular customers need to see what's really on the shelf and reserve it for pickup or delivery. Today orders arrive by WhatsApp and phone, and nobody can tell what is actually available.

**Status:** The app is already built and working: Laravel, Blade, Livewire and Tailwind 4, with 341 tests. This brief is for a **visual redesign**. The content, flows and features stay exactly as they are.

## Users and context

| User | Context |
|---|---|
| **Customer** (primary for the hero screen) | A local regular, often on a mobile phone, often in a hurry. They already know what they want: a search or a category tap, then add to cart. |
| Cashier | Desktop at the counter, using a barcode scanner. Speed matters most. |
| Warehouse manager | Tablet or desktop. Long tables: stock, batches, expiry dates. |
| Administrator | Desktop. Dashboard, reports, users, audit log. |

The UI language is **Spanish**.

## Navigation model

There is a top bar with the logo, a grouped menu for each role, notifications 🔔, the cart and the account. A customer sees: Catálogo, Tienda (Carrito, Mis pedidos) and avisos. Staff and the admin see their own groups: Panel, Ventas, Pedidos, Almacén, Catálogo and Administración.

## Screen inventory (journey order)

| Screen | Primary action | Type |
|---|---|---|
| **Catálogo** (hero) | Add a product to the cart | Key |
| Product page | Add to cart (with quantity or weight) | Supporting |
| Cart | Proceed to checkout | Key |
| Checkout | Confirm the order (pickup or delivery, time slot, cash or card on collection) | Key |
| Mis pedidos / order detail | Follow live status · Repeat order | Supporting |
| Login / register | Sign in | Supporting |
| Caja (POS) | Charge the sale | Key |
| Orders panel (staff) | Move the order to its next status | Key |
| Warehouse (9 tabs) | Depends on the tab | Supporting |
| Admin dashboard, reports | Read; export to CSV | Supporting |

## Hero screen: Catálogo

It's what the client sees first in the demo, and what customers use every week.

**Content, in priority order:**
1. Search (live, as you type)
2. Categories:
   - Fruta y verdura
   - Carnicería
   - Lácteos y huevos
   - Panadería
   - Despensa
   - Bebidas
3. Product grid. Each product shows:
   - name;
   - price, per unit or **per kg**;
   - category;
   - **availability**: *Disponible*, *Últimas unidades* or *Agotado*;
   - an add button with a quantity (whole units, or steps of 0.25 kg for weighed products).
4. Pagination

**Real content to use:**

| Product | Price | Availability |
|---|---|---|
| Tomate de ensalada | 2,40 €/kg | |
| Pechuga de pollo | 8,90 €/kg | Últimas unidades |
| Pan de payés | 3,20 € | Agotado |
| Huevos camperos (docena) | 3,40 € | |
| Aceite de oliva virgen extra 1 L | 8,95 € | |
| Arroz bomba 1 kg | 3,75 € | |
| Barra de pan | 0,95 € | |

There are no product photos yet: today the card shows the product's initial instead.

**States to design:**
- no results for a search («No hemos encontrado productos para "…"»), with a link to see everything;
- an empty category;
- loading while searching;
- a product that is sold out.

## UX principles

1. **Availability is truth.** Stock status is always visible and always honest.
2. **Two taps to the cart.** From the catalog, a product goes into the cart without opening its page.
3. **Quiet by default.** The interface steps back so products and prices stand out.
4. **One system for every role.** The customer storefront and the staff tools share one visual language. Staff screens are denser, not different.

## Brand and tone

- **Personality:** calm, efficient, premium, crafted. Closer to a good delicatessen than a discount supermarket.
- **Must never feel:**
  - loud or promotional, like a hypermarket flyer;
  - generic SaaS;
  - cluttered.
- **Visual identity:** open. There is no official brand. The current look (warm stone greys, emerald green, Instrument Sans) can be replaced.
- **Voice:** short, friendly and clear Spanish, using «tú».

## Platform and accessibility

- Responsive and mobile-first for the customer screens: 375, 768 and 1280 px.
- Staff screens are designed for desktop and tablet first.
- WCAG AA contrast, visible focus states, and tap targets of at least 44 px.
- Built with Tailwind 4 tokens, so the design system maps onto `@theme`.

## Scope

**Must keep:** every feature and flow that exists today.

**Won't have:**
- online payment (it's phase 3 in the proposal);
- loyalty points;
- product reviews;
- new features of any kind.
