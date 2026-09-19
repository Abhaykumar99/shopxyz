# Design System: "the shop counter"

Phase 1 output. Every customer, delivery and print screen is built from these tokens and components.
Live preview (local only): **`/dev/ui`**. Phone-width frame: `/dev/ui/phone?src=/dev/ui&w=360`.

## Direction

The look takes its cues from what the shop handles every day: **shelf price tags, gift ribbon, parcel
labels and sweet-box colours**. One signature shape is used: the **price tag** (pointed left edge with a
punched hole). It appears on prices, discounts, the current step of the order tracker and the logo monogram.
Everything else stays quiet: borders instead of shadows, and no decorative motion.

**Theme: Rose Atelier** (ADR-017). Rose-wine for every action (lipstick, gift ribbon), rose gold for offers
(gift wrap, sweet-box foil), on a blush-white page with deep rose-brown text. DM Serif Display headings give a
boutique feel; Figtree keeps text clean and readable; soft corners and a gentle rose shadow lift cards.

Rules we hold to:
- Rose gold (`accent`) means **discount or waiting** and nothing else.
- Status colours always mean the same thing everywhere (see below).
- Sentence case everywhere. No all-caps labels, no gradients, and motion only to confirm an action.
- Mobile first: design at 360 px, then widen.

## Tokens (`resources/css/app.css`)

Tailwind's default palette is removed. To rebrand, only change the values in `@theme`.

| Token | Hex | Use | Contrast |
|---|---|---|---|
| `ink` | `#2A1A20` | Text | 16.0:1 on paper |
| `ink-soft` | `#6E5B62` | Secondary text, placeholders | 6.1:1 on paper, 5.5:1 on mist |
| `paper` | `#FFFAFA` | Page background (blush white) | — |
| `surface` | `#FFFFFF` | Cards, fields | — |
| `mist` | `#F8EEF0` | Quiet fills, hover | — |
| `line` | `#F0E1E5` | Decorative dividers | — |
| `line-strong` | `#8F7A82` | Form field borders | 4.0:1 on white (≥3:1 required) |
| `brand` / `brand-dark` | `#9B2C55` / `#7C2244` | Actions, links, focus ring (rose-wine) | white on brand 7.3:1, brand on paper 7.0:1 |
| `brand-tint` | `#FBE9EF` | Selected state, price tag | brand 6.2:1 |
| `accent` / `accent-ink` / `accent-tint` | `#E8B4A0` / `#7A3E2C` / `#FBEDE6` | Offers and "waiting" states only (rose gold) | ink on accent 9.1:1, accent-ink on tint 7.2:1 |
| `pistachio` / `pistachio-tint` | `#2F6B4E` / `#E2F0E7` | Paid, delivered, success | 5.4:1 |
| `info` / `info-tint` | `#2A5B87` / `#E5EDF5` | New, confirmed | 6.0:1 |
| `danger` / `danger-tint` | `#B3261E` / `#FBEAE8` | Errors, cancelled, failed | 5.6:1 |

**Status tones** (`<x-ui.status-pill tone>`): `info` = placed or confirmed, `offer` = waiting on someone
(payment check, packing), `brand` = on the move (assigned, out for delivery), `success` = delivered or paid,
`danger` = cancelled or failed. `OrderStatus::tone()` and `PaymentStatus::tone()` return the matching value.

**Type:** DM Serif Display (`font-display`: headings, prices, order numbers; one weight, no faux bold) and Figtree
(`font-sans`: body), with Mukta as the Devanagari fallback for Hindi. Scale: 13 / 14 / 16 / 18 / 22 / 28 / 36 px (`text-xs` … `text-3xl`). Body text is
16 px so phone browsers don't zoom into inputs. Use the `figures` utility for tabular numbers.

**Radius by importance:** `rounded-field` (12 px) < `rounded-card` (20 px) < `rounded-sheet` (28 px).
**Shadow:** `shadow-overlay` for dialogs, dropdowns and toasts; cards use `shadow-[var(--card-shadow)]`.
**Utilities:** `tag-shape`, `figures`, `pb-safe`, `pb-safe-nav`, `no-scrollbar`.

## Components

Generic: `resources/views/components/ui/`

| Component | Key props |
|---|---|
| `x-ui.button` | `variant` primary/secondary/ghost/danger/inverse/inverse-outline (the last two for dark banners), `size` sm/md/lg, `href`, `type`, `icon`, `icon-end`, `loading` (Livewire action name), `block` |
| `x-ui.icon-button` | `icon`, `label` (required), `href`, `variant`, `count` |
| `x-ui.icon` | `name` (file in `resources/icons`), `size`, `label` (only for meaningful icons) |
| `x-ui.link` | `href` |
| `x-ui.input` / `textarea` / `select` | `label`, `name`, `hint`, `error`, `required` (others show "(optional)"), `prefix`, `icon`, `input-class`; select: `options`, `selected`, `placeholder`. Errors come from `$errors` automatically |
| `x-ui.field` | Wrapper used by the controls above |
| `x-ui.checkbox`, `x-ui.radio-card` | `label` / `title`, `description`, `icon` |
| `x-ui.badge`, `x-ui.status-pill` | `tone` |
| `x-ui.card` | `as`, `padding` none/sm/md/lg, `header` slot |
| `x-ui.alert` | `tone` info/success/warning/danger, `title` |
| `x-ui.toaster` | Included in the base layout. Trigger with `$this->dispatch('toast', message: '…', tone: 'success')` or `session()->flash('toast', …)` |
| `x-ui.empty-state` | `icon`, `title`, `level` (heading level, default 2), `action` slot |
| `x-ui.skeleton` | Size with classes |
| `x-ui.modal` | `name`, `title`, `sheet` (bottom sheet on phones), `max-width`, `footer` slot. Open/close with `$dispatch('open-modal', 'name')` / `close-modal` |
| `x-ui.dropdown` + `x-ui.dropdown-item` | `trigger` slot (must contain a button or link), `align`, `width`; item: `href`, `icon`, `tone` |
| `x-ui.tabs` + `x-ui.tab` | Link tabs: `label`; tab: `href`, `active`, `icon` |
| `x-ui.breadcrumb` | `items` = `[label => url]`, last item is the current page |
| `x-ui.pagination` | `paginator` (any Laravel paginator), `livewire` (use `previousPage()`/`nextPage()`) |

Shop: `resources/views/components/shop/`

| Component | Key props |
|---|---|
| `x-shop.price` | `paise`, `mrp`, `size` |
| `x-shop.price-tag` | `paise`, `mrp`, `offer` (accent "% off" tag), `size` |
| `x-shop.product-image` | `src`, `alt`, `category` (placeholder colour and icon) |
| `x-shop.product-card` | `name`, `url`, `image`, `category`, `brand`, `variant`, `paise`, `mrp`, `in-stock`, `action` slot |
| `x-shop.category-tile` | `name`, `url`, `slug`, `count` |
| `x-shop.quantity-stepper` | `value`, `min`, `max`, `name`, `label`. Works with `wire:model` |
| `x-shop.cart-line` | `name`, `url`, `image`, `category`, `variant`, `paise`, `mrp`, `quantity`; in Livewire: `sku` (binds `quantities.{sku}`, calls `remove()`), `max`, `available`, `stock` |
| `x-shop.address-card` | `label`, `name`, `phone`, `lines`, `pincode`, `is-default` |
| `x-shop.payment-option` | `method` cod/upi, `name` |
| `x-shop.upi-qr-panel` | `paise`, `vpa`, `payee`, `order-number`, `qr` (SVG from Phase 6), default slot for the proof form |
| `x-shop.file-drop` | `label`, `name`, `hint`, `accept` |
| `x-shop.order-tracker` | `steps` = `[label, time, state done/current/upcoming, icon, note]`, `failed` |
| `x-shop.delivery-order-card` | `order-number`, `url`, `customer`, `area`, `pincode`, `items`, `cod-paise`, `collected`, `status`, `tone` |
| `x-shop.google-button` | `href`. Follows Google's sign-in branding |
| `x-shop.logo` | `href`, `compact`. Admin logo or monogram plus `$shop->name` |
| `x-shop.nav-item` | Phone bottom-navigation link: `href`, `icon`, `active`, `count` |
| `x-shop.section-heading` | `title`, `href`, `link-text`, `id`; slot = short description |
| `x-shop.product-grid` | `products`, `rail` (swipeable row on phones), `columns`. Calls the page's `addToCart()` |
| `x-shop.catalog-filters` / `x-shop.catalog-results` | Filters, sort, chips, grid, pagination and filter sheet for pages using the `FiltersCatalog` trait |
| `x-shop.address-fields` | Address form fields bound to an `AddressForm` (`model`, `labels`, `states`) |
| `x-shop.info-page` | Information page wrapper with side menu and placeholder notice |
| `x-shop.hero-carousel` | Desktop banner carousel over the admin's banner rows: `banners`, `interval` (ADR-018, ADR-024) |
| `x-shop.banner-panel` | One admin-managed banner: `banner`, `layout` (hero/promo), `heading`, `showcase` slot (ADR-024) |
| `x-shop.hero-showcase` | Three products arranged as a shop window inside a banner (`products`) |
| `x-shop.wholesale-slabs` | A wholesale product's price slabs as a card (`item`), for banners |
| `x-shop.slab-table` | Quantity price slabs (`item`, `layout` grid/rows, `quantity` to highlight) (ADR-019) |
| `x-shop.cart-line` | One bag line; `wholesale`, `slab`, `next-slab-units`, `next-slab-paise`, `made-to-order` show slab pricing |
| `x-shop.quantity-stepper` | `value`, `min`, `max`, `step`, `editable` (a number field for bulk quantities) |

Every component reads shop details from `$shop` (ADR-013), never from literals.

## Layouts (`resources/views/layouts/`, used as `<x-layouts::name>`)

| Layout | Use |
|---|---|
| `app` | Base HTML document: title "Page \| Shop name", fonts, Vite, Livewire, skip link, toaster |
| `shop` | Customer pages: sticky header, search, bag count, desktop category bar, phone bottom navigation (`active`), footer with shop contacts |
| `account` | Profile / Orders / Addresses tabs inside `shop` |
| `auth` | Centered sign-in card |
| `delivery` | Delivery panel: back button, menu, pinned `action` slot for the one main action |
| `print` | Invoice and label sheets at the selected `PrintFormat` with a screen toolbar (paper choice + Print) |
| `error` | Minimal page for 404, 419, 429, 500 and 503. No Livewire or session data, so it always renders |

## Printing (ADR-014)

`resources/views/pdf/label.blade.php` and `pdf/invoice.blade.php` take the order shape from
`App\Support\Demo\DemoData::order()` until Phase 8. Sizes use `em`, so each document scales with the paper.

Checked with Chrome's print engine (CSS page size honoured): label on 4×6" = 1 page at 100×150 mm, A5 = 1 page
at 148×210 mm, A4 = 1 page at 210×297 mm; invoice on A4 and A5 = 1 page each. In the browser print dialog, keep
"Margins: default/none" and scale 100%.

## Accessibility baseline

- WCAG 2.2 AA colour contrast for all text pairs (table above). Field borders are at least 3:1.
- Visible focus ring (`outline-brand`) on everything, a skip link, and scroll padding so focused items stay clear of
  the sticky header and bottom bar.
- Every field has a visible label. Hints and errors are linked with `aria-describedby`, and errors set `aria-invalid`.
- Touch targets are 44 px (buttons, icon buttons, steppers, bottom navigation).
- Dialogs use native `<dialog>` (focus handling and Escape). The dropdown sets `aria-expanded` and `aria-controls` on its trigger.
- Motion is disabled under `prefers-reduced-motion`.
- Phase 1 audit: axe-core 4.11 (WCAG 2.0/2.1/2.2 A+AA and best practice) found **0 violations** on the style guide,
  delivery, sign-in and print previews at 360 px, including with the dialog, sheet and dropdown open. Keyboard tab
  order was checked by hand. Automated checks don't cover everything: screen-reader testing on a real phone is part of Phase 10.

## Customer pages (Phase 2)

Delivery panel (sign-in, round, delivery, cash, history, profile) on the `delivery` layout (ADR-020).
Admin panel at `/admin` on Filament, themed to Rose Atelier in `resources/css/filament/admin/theme.css`
(ADR-023): the colour ramp, greys dark enough for AA, DM Serif headings and tabular figures.
Home (desktop banner carousel), all categories, category, search, product, **wholesale**, **wholesale quote**, bag, sign-in, checkout,
UPI payment, order placed, profile, orders, order detail, addresses, six information pages and error pages. Each is a class-based Livewire component in
`app/Livewire/{Shop,Cart,Checkout,Account,Wholesale}` on the `shop` or `account` layout (see `routes/web.php`).
Page-level patterns:
- **Sticky action bar on phones:** product "Add to bag" and bag "Checkout" sit above the bottom navigation.
- **Filters in the URL:** category and search filters and sort are kept in the query string.
- **Numbered steps only for real sequences:** checkout steps, UPI payment steps, "What happens next".
- **Axe audit, Phase 2:** 0 violations on all 18 page states at 360 px and 1280 px, including the filter sheet, address
  sheet and cancel dialog. The checkout tab order was checked by hand.

## Adding to the system

1. Look for an existing component first. Extend it with a prop rather than copying its classes.
2. New generic pieces go in `components/ui`, shop-specific ones in `components/shop`.
3. Add the component to `/dev/ui` with realistic content and a test in `tests/Feature/View`.
4. New icons: copy the Lucide SVG into `resources/icons/` (ISC licence, see `resources/icons/LICENSE.txt`).
