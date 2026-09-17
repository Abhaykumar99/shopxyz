# Design System: "the shop counter"

Phase 1 output. Every customer, delivery and print screen is built from these tokens and components.
Live preview (local only): **`/dev/ui`**. Phone-width frame: `/dev/ui/phone?src=/dev/ui&w=360`.

## Direction

The look takes its cues from what the shop handles every day: **shelf price tags, gift ribbon, parcel
labels and sweet-box colours**. One signature shape is used: the **price tag** (pointed left edge with a
punched hole). It appears on prices, discounts, the current step of the order tracker and the logo monogram.
Everything else stays quiet: borders instead of shadows, and no decorative motion.

Rules we hold to:
- Marigold means **discount** and nothing else.
- Status colours always mean the same thing everywhere (see below).
- Sentence case everywhere. No all-caps labels, no gradients, and motion only to confirm an action.
- Mobile first: design at 360 px, then widen.

## Tokens (`resources/css/app.css`)

Tailwind's default palette is removed. To rebrand, only change the values in `@theme`.

| Token | Hex | Use | Contrast |
|---|---|---|---|
| `ink` | `#2B1631` | Text | 15.7:1 on paper |
| `ink-soft` | `#6B5870` | Secondary text, placeholders | 6.1:1 on paper |
| `paper` | `#FBF7FA` | Page background | — |
| `surface` | `#FFFFFF` | Cards, fields | — |
| `mist` | `#F3ECF2` | Quiet fills, hover | ink 14.3:1 |
| `line` | `#E6DAE5` | Decorative dividers | — |
| `line-strong` | `#8C7A90` | Form field borders | 4.0:1 on white (≥3:1 required) |
| `berry` / `berry-dark` | `#A3214F` / `#7E1A3E` | Actions, links, focus ring | white on berry 7.3:1 |
| `berry-tint` | `#F7E6EE` | Selected state, price tag | berry 6.1:1 |
| `marigold` / `marigold-ink` / `marigold-tint` | `#F3A712` / `#7A4B00` / `#FDF0D2` | Offers and "waiting" states only | ink on marigold 8.2:1 |
| `pistachio` / `pistachio-tint` | `#2F6B45` / `#E3F1E1` | Paid, delivered, success | 5.4:1 |
| `info` / `info-tint` | `#1D5B8F` / `#E4EEF7` | New, confirmed | 6.1:1 |
| `danger` / `danger-tint` | `#B42318` / `#FDECEA` | Errors, cancelled, failed | 5.8:1 |

**Status tones** (`<x-ui.status-pill tone>`): `info` = placed or confirmed, `offer` = waiting on someone
(payment check, packing), `berry` = on the move (assigned, out for delivery), `success` = delivered or paid,
`danger` = cancelled or failed. The Phase 6 status enums will expose the matching `tone()`.

**Type:** Bricolage Grotesque (`font-display`: headings, prices, order numbers) and Mukta (`font-sans`: body,
with Devanagari for future Hindi). Scale: 13 / 14 / 16 / 18 / 22 / 28 / 36 px (`text-xs` … `text-3xl`). Body text is
16 px so phone browsers don't zoom into inputs. Use the `figures` utility for tabular numbers.

**Radius by importance:** `rounded-field` (8 px) < `rounded-card` (14 px) < `rounded-sheet` (20 px).
**Shadow:** only `shadow-overlay` for dialogs, dropdowns and toasts.
**Utilities:** `tag-shape`, `figures`, `pb-safe`, `pb-safe-nav`.

## Components

Generic: `resources/views/components/ui/`

| Component | Key props |
|---|---|
| `x-ui.button` | `variant` primary/secondary/ghost/danger, `size` sm/md/lg, `href`, `type`, `icon`, `icon-end`, `loading` (Livewire action name), `block` |
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
| `x-ui.empty-state` | `icon`, `title`, `action` slot |
| `x-ui.skeleton` | Size with classes |
| `x-ui.modal` | `name`, `title`, `sheet` (bottom sheet on phones), `max-width`, `footer` slot. Open/close with `$dispatch('open-modal', 'name')` / `close-modal` |
| `x-ui.dropdown` + `x-ui.dropdown-item` | `trigger` slot (must contain a button or link), `align`, `width`; item: `href`, `icon`, `tone` |
| `x-ui.tabs` + `x-ui.tab` | Link tabs: `label`; tab: `href`, `active`, `icon` |
| `x-ui.breadcrumb` | `items` = `[label => url]`, last item is the current page |
| `x-ui.pagination` | `paginator` (any Laravel paginator) |

Shop: `resources/views/components/shop/`

| Component | Key props |
|---|---|
| `x-shop.price` | `paise`, `mrp`, `size` |
| `x-shop.price-tag` | `paise`, `mrp`, `offer` (marigold "% off" tag), `size` |
| `x-shop.product-image` | `src`, `alt`, `category` (placeholder colour and icon) |
| `x-shop.product-card` | `name`, `url`, `image`, `category`, `brand`, `variant`, `paise`, `mrp`, `in-stock`, `action` slot |
| `x-shop.category-tile` | `name`, `url`, `slug`, `count` |
| `x-shop.quantity-stepper` | `value`, `min`, `max`, `name`, `label`. Works with `wire:model` |
| `x-shop.cart-line` | `name`, `url`, `image`, `category`, `variant`, `paise`, `quantity` |
| `x-shop.address-card` | `label`, `name`, `phone`, `lines`, `pincode`, `is-default` |
| `x-shop.payment-option` | `method` cod/upi, `name` |
| `x-shop.upi-qr-panel` | `paise`, `vpa`, `payee`, `order-number`, `qr` (SVG from Phase 6), default slot for the proof form |
| `x-shop.file-drop` | `label`, `name`, `hint`, `accept` |
| `x-shop.order-tracker` | `steps` = `[label, time, state done/current/upcoming, icon, note]`, `failed` |
| `x-shop.delivery-order-card` | `order-number`, `url`, `customer`, `area`, `pincode`, `items`, `cod-paise`, `collected`, `status`, `tone` |
| `x-shop.google-button` | `href`. Follows Google's sign-in branding |
| `x-shop.logo` | `href`, `compact`. Admin logo or monogram plus `$shop->name` |

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

## Printing (ADR-014)

`resources/views/pdf/label.blade.php` and `pdf/invoice.blade.php` take the order shape from
`App\Support\Demo\DemoData::order()` until Phase 8. Sizes use `em`, so each document scales with the paper.

Checked with Chrome's print engine (CSS page size honoured): label on 4×6" = 1 page at 100×150 mm, A5 = 1 page
at 148×210 mm, A4 = 1 page at 210×297 mm; invoice on A4 and A5 = 1 page each. In the browser print dialog, keep
"Margins: default/none" and scale 100%.

## Accessibility baseline

- WCAG 2.2 AA colour contrast for all text pairs (table above). Field borders are at least 3:1.
- Visible focus ring (`outline-berry`) on everything, a skip link, and scroll padding so focused items stay clear of
  the sticky header and bottom bar.
- Every field has a visible label. Hints and errors are linked with `aria-describedby`, and errors set `aria-invalid`.
- Touch targets are 44 px (buttons, icon buttons, steppers, bottom navigation).
- Dialogs use native `<dialog>` (focus handling and Escape). The dropdown sets `aria-expanded` and `aria-controls` on its trigger.
- Motion is disabled under `prefers-reduced-motion`.
- Phase 1 audit: axe-core 4.11 (WCAG 2.0/2.1/2.2 A+AA and best practice) found **0 violations** on the style guide,
  delivery, sign-in and print previews at 360 px, including with the dialog, sheet and dropdown open. Keyboard tab
  order was checked by hand. Automated checks don't cover everything: screen-reader testing on a real phone is part of Phase 10.

## Adding to the system

1. Look for an existing component first. Extend it with a prop rather than copying its classes.
2. New generic pieces go in `components/ui`, shop-specific ones in `components/shop`.
3. Add the component to `/dev/ui` with realistic content and a test in `tests/Feature/View`.
4. New icons: copy the Lucide SVG into `resources/icons/` (ISC licence, see `resources/icons/LICENSE.txt`).
