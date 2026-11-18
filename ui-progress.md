# LocalMarket: UI progress

- **Canvas:** https://claude.ai/artifact/B4HimDSdyjLrjLqad8mSfg
- **Design system:** https://claude.ai/artifact/JLFrP6bAab7hy3GRXveyLn (LocalMarket design system, installed on the canvas)
- **Brief:** [design-brief.md](design-brief.md)
- **Current round:** Round 4 done (system locked) and applied to the Blade views (PR #71, merged into develop). Next: Round 5 · Roll out, starting with the Cart (key screen, 3 variations).

## Round 4 · Design system

- 30 colour tokens (single light theme): grounds, ink, one action colour, success, stock states, and a tint/ink pair per section
- Manrope type scale (9 styles), 7 spacing steps, 5 radii, 2 shadows, 2 sizes (44 px controls, 48 px buttons)
- Components: Button, CategoryChip, StockStatus, WeightSelector, UnitStepper, ProductCard, Shelf, plus a cover
- Contrast fix while locking: `line-strong` #C7CBD1 → #80878F and `action-ring` #9DB0EE → #5B78D6 (both now ≥ 3:1); the canvas chip outline was updated to match

## Round 3 · Fixes applied to Estanterías

1. Weight chips ¼ · ½ · ¾ · 1 kg (0,75 kg from the demo script now possible)
2. One consistent primary button on every card: «+ Añadir»
3. New artboards: search results («pollo»), no results («salmón»), loading skeleton
4. Sold-out cards use real colours (no opacity); all controls 44 px; weight chips are real radio inputs
5. Every product shown on its shelf; on phones the shelf scrolls sideways
6. Sticky category bar with the current shelf highlighted
7. «✓ Añadido · ½ kg» confirmation and live cart count
8. Heading reduced to one line next to the category chips

## Picks

| Round | Options | Picked | Why |
|---|---|---|---|
| 1 · Directions | A · Pizarra, B · Vitrina, C · Al grano | **B · Vitrina** | User's choice: category-tinted tiles, premium storefront without photos |
| 2 · Variations | A · Foco (hierarchy), B · Estanterías (signature), C · Refinado (craft) | **B · Estanterías** | User's choice: categories as tinted shelves, weight chips on the card |
