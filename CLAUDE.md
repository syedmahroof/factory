# Factory ERP — Project Context

A manufacturing **ERP**: organization and master data, engineering (items, BOMs,
routings, work centers), procurement, inventory, production, planning, sales,
quality, maintenance, workforce and finance.

> **This file was rewritten on 2026-09-03.** It previously described a Merchant
> Cash Advance syndication platform — that application was copied into this repo,
> never wired up, and has since been deleted. See `DESIGN_PORT_GAPS.md`. If you
> find a doc under `docs/` describing merchants, investors, ACH or the MMP/Sila
> gateway, it is stale and does not apply here.

## Stack

Laravel 12 + PHP 8.4 · Sanctum tokens · Vue 3 SPA (Composition API, `<script setup>`)
· Vue Router · Pinia · Tailwind CSS **v4** (CSS-first `@theme`, no JS config) · Vite 7
· floating-vue for tooltips. No Blade UI, no jQuery, no Bootstrap.

`routes/web.php` serves the SPA shell for every path; all UI routing is client-side.

## Layout

```
resources/js/
  app.js                 entry — mounts layouts/App.vue, registers 9 global components
  layouts/App.vue        the whole chrome: topbar, sidebar, title band, page slot
  router/index.js        flat route table + auth guard + /:pathMatch catch-all
  pages/<module>/        one folder per ERP module
  components/            shared UI (DataTable, PageHeader, LineEditor, BrandMark…)
  composables/           useApi (get/post/put/del), useToast
  api/client.js          axios on /api/v1, Bearer from localStorage, 401 → /login
  stores/auth.js         Pinia: user, token, login, logout, fetchUser
app/
  Actions/               148 action classes — where business logic goes
  Policies/              92 policies
  Http/Controllers/Api/V1/Factory/   46 controllers
  Models/                99 models
```

## Conventions

- **Business logic lives in `app/Actions/<Module>/<Verb><Thing>.php`** with a single
  `execute()`. Simple actions return the model; ones with failure modes return
  `ActionResult` (`app/Actions/ActionResult.php`) — `success`, `message`, `data`,
  `status` always present, with `->toResponse()` for controllers. Follow whichever
  the neighbouring actions in that module use.
- Mutations log through `AuditService::log(...)`.
- API is `/api/v1`, `apiResource` per module, everything behind `auth:sanctum`
  except `POST /login`.
- Style: `vendor/bin/pint`.

## Design system — read before touching any UI

The look is ported from the `ip_new` project and the values are **measured, not
chosen**. Do not invent colours, radii or sizes.

- `resources/css/app.css` — the tokens, in a Tailwind v4 `@theme` block. Use them as
  utilities: `text-hb-ink`, `text-hb-mut`, `border-hb-line`, `bg-hb-hover`,
  `bg-hb-brand`, `text-hb-red`, `text-hb-green`, `rounded-hb-ctl`,
  `shadow-hb-sheet`, `text-hb-body` (13.5px), `text-hb-sm` (12px),
  `text-hb-lbl` (10.5px uppercase).
- `resources/css/erp-surface.css` — the class vocabulary the pages use:
  `.card`, `.btn` + `.btn-primary/secondary/success/danger/outline/sm`,
  `.btn-icon` (+ `.danger` / `.ok`) for row actions, `.data-table`, `.hb-pill`
  (+ `.ok/.off/.done/.idle/.neutral`), `.hb-lbl`, `.hb-input-sm`, `.page-toolbar`.
- **Never** reach for raw Tailwind palette classes — no `gray-*`, `indigo-*`,
  `blue-*`, `rounded-md`, `text-sm`. There are currently zero in the codebase and
  it should stay that way.
- Bare `<input>`, `<select>`, `<textarea>` are already styled globally. Don't
  re-dress them.
- `lg:` is **992px**, not Tailwind's 1024 — the chrome is built on it.
- **Icons**: `app.blade.php` loads `public/assets/css/icons.min.css`, an older
  Material Design Icons build that has no `-outline` variant for many names
  (`mdi-cog-outline`, `mdi-office-building-outline`,
  `mdi-book-open-page-variant-outline` are all absent). A missing name renders as
  nothing at all, silently. Check before using one:
  `grep -c 'mdi-<name>:' public/assets/css/icons.min.css`.
  That sheet also carries **Font Awesome**, which owns `.fa` and every `.fa-*`
  class — `.fa` alone sets `font-family: 'Font Awesome 5 Free'; font-weight: 900;
  line-height: 1; display: inline-block`. Never use `fa`/`fa-*` as your own class
  names; the dashboard's stylesheet uses `dash-*` for exactly this reason.

### Page anatomy

`layouts/App.vue` draws the brand-red title band and takes the heading from
`route.meta.title`, with a `Section / Page` breadcrumb. So a page must **not**
render its own `<h1>` — `<page-header>` is the toolbar, and its default slot is
accepted but ignored; put the buttons in `<template #actions>`, where the primary
one renders as a white pill on the band. A screen that draws its own brand-filled panel
over the band sets `meta.ownsHeading: true` instead.

## Commands

`php artisan serve` · `npm run dev` · `npm run build` · `php artisan test` · `vendor/bin/pint`

Local URL `http://inv_portal.test`. Seeded login `admin@factory.com` / `password`
(`database/seeders/DatabaseSeeder.php`).

## Known gaps — read before trusting the test suite

**`php artisan test` is currently 109 failed / 51 passed.** This predates any recent
work. The cause: a large MCA (merchant cash advance) backend is still present —
`app/Actions/{Merchant,Investor,Ach}` (60 files), `app/Jobs/Merchant`,
`app/Services/Merchant`, `database/seeders/Demo`, `tests/Feature/Money` — but the
~15 models it depends on (`Merchant`, `MerchantInvestor`, `MerchantPayment`,
`UserType`, `LiquidityLog`, `Holiday`, `Bank`…) do not exist in `app/Models`.

Treat this repo as **half-migrated**, not clean. Do not delete that code casually:
it is the payment-split and fee-cascade logic. Do not add to it either until
someone decides whether the MCA side is coming back.

Also open: no password-reset endpoints, and `docs/` describes the MCA app
throughout. Full list in `DESIGN_PORT_GAPS.md`.

**This repo is not under git** — there is no undo.
