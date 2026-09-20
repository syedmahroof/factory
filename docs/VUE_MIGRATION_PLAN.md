# Final Plan — Livewire → Vue 3 (full front-end conversion)

**Status:** final. Decisions below are settled, not proposed.
**Branch:** `vue-implimentation`
**Survey date:** 2026-08-24 (all counts measured from the working tree, not estimated)

---

## 0. Locked decisions

| # | Decision |
|---|---|
| 1 | **Plain REST API + Vue 3 SPA.** No Inertia, anywhere, at any phase. |
| 2 | **Token authentication** — Laravel Sanctum personal access tokens, `Authorization: Bearer`. |
| 3 | **Everything user-facing becomes Vue** — Admin, Investor **and** the public Funding site. No portal keeps a Blade page. |
| 4 | **Tailwind CSS** replaces Bootstrap in the SPA. |
| 5 | **Backend re-layered**: Controller (thin, `try/catch`) → FormRequest → Action (`ActionResult`) → JsonResource. |
| 6 | **`selfCreate/selfUpdate/selfDelete` deleted** from every model. |
| 7 | **Business logic removed from model `boot()`** — with the exceptions named in §9, which are invariants, not "unwanted". |
| 8 | **Laravel Boost** drives the dev workflow (already installed). |
| 9 | Vue lives in **`resources/js`**. |

**The only Blade that survives** is server-rendered output that was never front end and cannot
be Vue: the single SPA shell (`app.blade.php`), the **4 mail templates** (`resources/views/emails`,
rendered by Mailables) and the **3 PDF templates** (`resources/views/pdf`, rendered by dompdf).
Everything else — all 199 remaining blades — is deleted.

---

## 1. Starting point (measured)

| Area | Current state |
|---|---|
| Livewire components | **73** files · 12,462 LOC PHP + 15,358 LOC Blade |
| Blade views | **206** — `Admin` 85, `livewire` 71, `Investor` 13, `Funding` 12, `auth` 8, `emails` 4, `pdf` 3, misc 10 |
| Controllers | **24**, ~4,386 LOC (`ReportController` 1,555 · `MerchantController` 872) |
| Models | **35**; **20** carry `self*` methods → **58** methods, **74** call sites across 34 files |
| Model `boot()` with logic | **8** — `Merchant`, `MerchantInvestor`, `MerchantPayment`, `MerchantPaymentInvestor`, `MerchantInvestorFee`, `MerchantAchFeeItem`, `InvestorTransaction`, `MerchantBank::booted` |
| Actions | **19** (Account, Investor, Ach, Report) + `ActionResult` |
| FormRequests / Resources / Policies / Enums | **0 / 0 / 0 / 0** |
| Routes | 63 GET (pages) + 37 POST (DataTables/ajax); Funding alone exposes **11 public GET** routes |
| Auth | session `web` guard only; `routes/api.php` = one stub; Sanctum installed, unused |
| CSS/JS | Bootstrap 5 "Themesdesign" + jQuery, Yajra DataTables (9 controllers), select2, selectize, sweetalert2, air-datepicker, toastr, Pusher. **No Tailwind.** |
| Build | `package.json` runs **laravel-mix** while `vite.config.js` sits unused — split brain |
| Tests | effectively none |
| Already installed | `laravel/boost`, `laravel/sanctum`, `dedoc/scramble` (OpenAPI), Horizon, Pusher |

**Livewire components per module:** Admin/Merchant **30**, Admin/Investor 8, Funding 6,
Admin/Settings 6, Admin/Report 4, Investor 3, Auth 3, Admin/Actum 3, Account 1, misc 9.
Admin/Merchant is ~40 % of the work — it is deliberately not first.

---

## 2. Target architecture

```
Browser — Vue 3 SPA (Vite · Tailwind · Vue Router · Pinia · axios)
   │  Authorization: Bearer <sanctum token>
   ▼
routes/api/v1/*  →  Controller (thin, try/catch, report($e))
                       ├── FormRequest      validation + authorize()
                       ├── Policy           authorization
                       ├── Action           business logic → ActionResult
                       └── JsonResource     output shape
                              └── Models (dumb) · Jobs · Services
```

**Serving model:** one Blade shell with `<div id="app">` plus a catch-all web route. Vue Router
owns every URL. During Phases 2–7 Livewire keeps serving its old URLs; the two never share a
page, and each cutover flips a route group from Blade to the SPA shell.

**Consequences of no-Inertia, designed for up front:**
- Every screen needs a real endpoint — the Resource layer is load-bearing, not decorative.
- `$errors`, Blade flash messages and `redirect()->back()` disappear. Everything is the JSON
  envelope `{ success, message, data, errors? }` that `ActionResult::toResponse()` already emits.
- No server-rendered HTML for the public site — solved by build-time prerendering, §11.
- Payoff: the same API serves a future mobile/partner client, documented for free by Scramble.

---

## 3. Front-end structure (`resources/js`)

```
resources/js/
├── app.js                     # createApp · router · pinia · echo · global error handler
├── router/                    # index + admin.js / investor.js / funding.js route tables
├── stores/                    # Pinia: auth, ui (toasts/modals), lookups, tablePrefs
├── services/
│   ├── http.js                # axios: baseURL /api/v1, Bearer interceptor,
│   │                          # 401 → logout, 422 → field errors, 5xx → toast
│   └── api/                   # accounts · investors · merchants · payments · ach
│                              # · reports · settings · funding
├── layouts/                   # AdminLayout · InvestorLayout · FundingLayout · AuthLayout
├── components/
│   ├── ui/                    # Harbor system: HbPanel HbSheet HbStat HbButton
│   │                          # HbPill HbChip HbModal HbToast
│   ├── form/                  # HbInput HbSelect HbCombobox HbDatePicker HbMoney
│   │                          # HbFileUpload FormErrors
│   └── table/                 # DataTable.vue — server paging/sort/search/columns/
│                              # bulk-select/export. Replaces Yajra AND the Livewire tables
├── pages/
│   ├── admin/{accounts,investors,merchants,ach,reports,settings,uploads,actum}/
│   ├── investor/{dashboard,marketplace,merchants,reports}/
│   └── funding/{home,marketplace,details,about,how-it-works,videos,contact,
│                agreement,privacy,terms}/
├── composables/               # useTable useForm useCurrency useConfirm usePermissions
└── utils/                     # money/date formatters (port of helpers.php equivalents)
```

`resources/css/app.css` becomes the Tailwind entry. Delete `webpack.mix.js`, `resources/sass/`
and the laravel-mix scripts — Vite becomes the single build.

**TypeScript recommended.** 35 models' worth of shapes cross the wire; Scramble can emit the
OpenAPI schema and the client types can be generated from it rather than hand-written.

---

## 4. Design parity + Tailwind

The newest modules already ship a named design system — **"Harbor"**: navy stats panel, white
sheet, toolbar, pills/chips, round row actions. It currently covers ~6 pages (Account,
Investor, Merchant list/create, PendingInvestment). The other ~80 admin blades are raw
Themesdesign Bootstrap.

1. **Extract tokens** — Harbor palette, radii, shadows, spacing, type scale from
   `public/css/custom_style.css` into Tailwind v4 `@theme` tokens.
2. **Rebuild Harbor as Vue components** in `components/ui/`, screenshot-diffed against the
   live pages so parity is proven, not asserted.
3. **Keep the MDI icon font** — it is already loaded and used throughout the markup. Swapping
   icon sets is a separate optional task.
4. **Non-Harbor pages** get a mechanical Bootstrap→Tailwind translation with the same DOM
   structure. Visual redesign of those ~80 pages is out of scope unless explicitly added.
5. Tailwind v4 via `@tailwindcss/vite`. No Bootstrap in the SPA; legacy Blade layouts keep
   their own Bootstrap links until retired, so the two never load together.

---

## 5. Replacing the jQuery ecosystem

| Today | In Vue |
|---|---|
| Yajra DataTables + 9 POST `table` routes | `DataTable.vue` + `GET /api/v1/<resource>?page&per_page&sort&dir&search&filters[]` returning a paginated Resource collection |
| Livewire tables (Account/Investor/Merchant) | Same `DataTable.vue` — closest to done, port first |
| select2 / selectize | `HbSelect` / `HbCombobox`, async search against the existing dropdown endpoints |
| sweetalert2 | `useConfirm()` + `HbModal` |
| toastr + `brian2694/laravel-toastr` | Pinia `ui` store toasts; the API returns messages, never flash-session |
| air-datepicker | `HbDatePicker` |
| Pusher + inline Echo | `laravel-echo` + `pusher-js` in `app.js`; private channels authorised at `/broadcasting/auth` **with the Bearer token** |
| dompdf / Excel downloads (session-cookie today) | Signed short-lived download URLs, or axios blob download with the Bearer header — **breaks silently if overlooked**, see §16.3 |

---

## 6. Backend layering

### 6.1 API surface
- `routes/api/v1/{auth,admin,investor,funding}.php`, registered in `RouteServiceProvider`
  under prefix `api/v1`, middleware `['api','auth:sanctum']` + ability/role middleware.
  The Funding read endpoints are the exception: public, rate-limited, no auth.
- REST resource naming (`GET /merchants`, `POST /merchants`, `GET /merchants/{merchant}`),
  replacing verb-routes like `Account::Merchant::GetList`.
- One envelope everywhere: `{ success, message, data, errors? }`.
- `dedoc/scramble` publishes the OpenAPI contract the Vue side codes against.

### 6.2 Per-module deliverables — the repeating unit of work
**Requests → Resources → Actions → Controller → Policy → Vue pages → tests.**

- **FormRequest** per write endpoint (`app/Http/Requests/<Module>/<Verb><Model>Request.php`)
  with `rules()`, `authorize()`, `messages()`. Target **≈ 50**. The rule arrays currently in
  `Model::rules()` and Livewire `$rules` move here.
- **JsonResource** per exposed model (`app/Http/Resources/<Model>Resource.php`) plus thin list
  variants. Target **≈ 30**. Money formatting, roll-ups and `user_type_id` → label translation
  belong here — never in Vue.
- **Action** per use case returning `ActionResult`, following `app/Actions/SampleAction.php`.
  19 exist; expect **≈ 80–95**. Each Livewire public method maps roughly 1:1.
- **Controller**: thin. `try/catch` around the Action, `report($e)` on `Throwable`, Resource on
  success, `ActionResult::toResponse()` on failure. No queries, no logic, no DataTables config.
- **Policy** per model, invoked from `authorize()`. **This is new** — today every admin route
  sits behind a bare `middleware('auth')`, so an investor token could reach admin endpoints.
  Token abilities are the coarse gate; policies are the fine one.
- **Enums** for `user_type_id`, `status_id`, `payment_mode_id`, `ach_flag` — loose ints today,
  and about to be duplicated into JS if not fixed now.

---

## 7. Deleting `selfCreate` / `selfUpdate` / `selfDelete`

58 methods, 74 call sites. **Not** a single sweep — per module:

1. Write the Action that replaces the method (validation → FormRequest, persistence →
   `DB::transaction`, side-effect jobs dispatched from the Action).
2. Repoint that module's callers — the new controller **and** the still-live Livewire component.
3. Leave the model method as a `@deprecated` shim delegating to the Action (the pattern already
   proven on `User` in Module 1).
4. Delete the shim once its last caller is gone.

**Non-Livewire callers that must migrate too** — easy to miss, they are not on any screen:
`Jobs/Merchant/PaymentJob`, `Jobs/Merchant/MerchantFeeJob`, `Console/Commands/SyncMerchant`,
`Console/Commands/Transafer/InvestorportalDatas`, `Actions/Ach/RecordActumResultAction`.

**Exit criterion:** `grep -rn "self\(Create\|Update\|Delete\)" app` returns nothing.

---

## 8. Cleaning up model `boot()`

The 8 hooks are not equal — classify before touching. The rule: **derivations stay, actor-
dependent code and job dispatch move out.**

| Hook | Contents | Verdict |
|---|---|---|
| `Merchant::boot` saving | derives `rtr`, `payment_amount`; dispatches `StatusChangeJob` | Keep the derivation as `booted()`/Observer — it is an invariant every read depends on. **Move the job dispatch** into the Action: a job fired from a model hook also fires from seeders, imports and tinker |
| `MerchantInvestor::boot` saving | derives `rtr`, `share`, `company_id` | Keep derivation; move the `ResolveCompanyId` fallback into the Action |
| `MerchantInvestor::boot` deleting | `Bus::chain(InvestmentUpdateJob(..., auth()->user()->id, ...))` | **Must move.** `auth()->user()->id` inside a model hook is already a known bug source here (see the comment in `ActumRequest`) and it will 500 under queue, console and token contexts |
| `MerchantPayment`, `MerchantPaymentInvestor`, `MerchantInvestorFee`, `MerchantAchFeeItem`, `InvestorTransaction` | mixed derivation + dispatch | Same rule applied case by case |
| `MerchantBank::booted` | already modern | Leave |

**Hard gate:** the money-math characterization tests in §12 must exist and pass *before* any
hook is touched. The `rtr` / `share` / fee-cascade derivations are the product.

---

## 9. Token authentication

- **Sanctum personal access tokens** (not SPA cookie mode).
- `POST /api/v1/auth/login` → `{ token, user, abilities }`; `POST /auth/logout` revokes the
  current token; `GET /auth/me`; `POST /auth/forgot-password`, `/reset-password` — replacing the
  `Auth\*Controller` web flows and the `Auth/LoginPopUp`, `RegisterPopUp` Livewire components.
- **Abilities per portal**, issued at login from `user_type_id`: admin (1), investor (2),
  lender (4), company (6), merchant (7). Route middleware `ability:admin` etc.
- Token TTL via `sanctum.expiration` + refresh endpoint. Client keeps the token in memory +
  `localStorage`; the axios 401 interceptor clears state and routes to `/login`.
- Add `throttle:login` — there is no rate limiting today.
- Session guard stays alive only for `/horizon`, `/log-viewer` and Scramble docs.
- **Enumerate every session-dependent surface before cutover**: broadcasting auth, dompdf
  downloads, Excel exports. All three need an explicit token path.

---

## 10. Public Funding site — full Vue, SEO handled

The Funding site converts to Vue like everything else (6 Livewire components, 12 blades,
**11 public GET routes**: home, marketplace, details/{id}, about-us, how-it-works, videos,
contact-us, privacy-policy, terms-and-condition, login, index).

A client-only SPA is invisible to crawlers that do not run JS, so the conversion ships with:

1. **Build-time prerendering** (`vite-ssg` or `vite-plugin-prerender`) for the 9 static public
   routes — real HTML in `public/`, hydrated by Vue on load. Covers crawlers, social unfurls
   and first-paint.
2. **`@unhead/vue`** for per-route `<title>`, meta description, canonical and Open Graph tags —
   currently hardcoded in the Blade layout.
3. **`details/{id}` is dynamic** and cannot be prerendered at build. Serve it through a
   lightweight server-render or a meta-injection middleware that stamps title/description/OG
   into the shell HTML from the deal record before returning it.
4. **`sitemap.xml` + `robots.txt`** generated from the route table; JSON-LD structured data on
   the marketplace and detail pages.
5. Verify with Google Rich Results Test and a crawl (Screaming Frog or `curl` with JS off)
   **before** the DNS-level cutover, not after.

This is the piece of the plan with the least prior art in this codebase — budget accordingly.

---

## 11. Testing

- Add **Pest** (`pestphp/pest`, `pest-plugin-laravel`). The repo's `pest-testing` skill is the
  style guide.
- **Characterization tests first**, before any `boot()`/`self*` change: seed a funded advance +
  3 investors, run `PaymentJob → MerchantUpdateJob → InvestorUpdateJob`, snapshot
  `merchant_investors.paid_*`, `merchant_payments.investors_*`, `users.liquidity` and
  `liquidity_logs`. These numbers must not move across the entire migration.
- Feature test per endpoint: 401 unauthenticated · 403 wrong ability/policy · 422 payload ·
  200 happy path + Resource shape.
- Front end: **Vitest** for composables/stores, **Playwright** smoke over each migrated portal.
- CI gate: `pint --test` · `pest` · `vitest` · `vite build`.

---

## 12. Laravel Boost

`laravel/boost` is already vendored. Confirm it is registered and wired in:
run `php artisan boost:install` if config/guidelines are missing; use Boost's version-aware
docs search and tinker tooling during the refactor instead of guessing API shapes; add the
generated guidelines file to `CLAUDE.md`'s reading list so every module follows one convention.

---

## 13. Phases

### Phase 0 — Foundations (no user-visible change) · 2–3 wks
Vite-only build (delete laravel-mix) · Tailwind v4 + Harbor tokens · Vue 3 + Router + Pinia +
axios skeleton · SPA shell + catch-all route · `routes/api/v1/*` scaffolding · Sanctum tokens +
abilities + login/logout/me · Enums · base `DataTable.vue` + `ui/` and `form/` component sets ·
Pest installed **+ money-math characterization tests** · fix `env()` outside config
(`env('APP_NAME')` in the layouts breaks `config:cache`).

### Phase 1 — Auth + shell · 1 wk
Login/forgot/reset in Vue · Admin/Investor/Funding layouts (sidebar, topbar, notification
count) · global toasts, confirm modal, error handling. Old Livewire URLs still serve the rest.

### Phase 2 — Admin/Account pilot (1 component) · 1 wk
Already refactored to Actions + Harbor, so it validates the full stack end to end: Request,
Resource, Policy, DataTable, export, bulk delete. **Conventions freeze here** — everything
after is repetition.

### Phase 3 — Admin/Investor (8 components) · 2 wks
Investors CRUD · transactions · liquidity log · priority pass · advance table. Retires the
`Investor` and `InvestorTransaction` `self*` methods.

### Phase 4 — Admin/Merchant core (~14 of 30) · 4–5 wks
Create/edit/view · table · investors · assign/edit investor · payments · add payment · lender
payment · banks · companies · security · FAQ · story · status log. Largest and riskiest — the
money math lives here.

### Phase 5 — ACH + Actum (~8 components) · 2–3 wks
Terms · generate · ACH fees · payoff letter · Actum request/log/table · webhooks. `Terms` and
`Ach/Generate` hold scheduling and holiday math that belongs in Actions.

### Phase 6 — Reports + Uploads + Settings (~12 components) · 3–4 wks
Profitability · management fee · overpayment · liquidity · Excel export/upload · holidays ·
videos · general settings · job batch · failed jobs · API log.
**Fix the P1 raw SQL here** — string-interpolated `DB::raw` in `Report/Profitability`,
`Report/ManagementFee` and `Helpers/MerchantHelper` becomes bound parameters.

### Phase 7 — Investor portal (3) + Funding site (6, full Vue + SEO) · 3–4 wks
Investor marketplace, merchant table, merchant view. Then the complete Funding conversion with
prerendering, meta management, sitemap and structured data per §10, verified by a crawl before
cutover.

### Phase 8 — Decommission · 1 wk
Remove `livewire/livewire`, `yajra/*`, `laravel-collective/html`, `brian2694/laravel-toastr`,
`laravel/ui`, `laravel-mix`. Delete `app/Http/Livewire`, `resources/views/livewire`, all portal
Blade layouts and views, the POST `table` routes, the session guard. Final sweeps for `self*`,
`DB::raw("`, and `env(` outside config.

**Total: ~19–24 weeks** for one full-time dev. **~11–13 weeks** with two devs running modules
in parallel after Phase 2 freezes the conventions.

---

## 13a. Progress

### Phase 0 — done

**Done and verified**

- Toolchain: laravel-mix and `webpack.mix.js` removed, `resources/js/bootstrap.js` deleted,
  Vite 7 + `@vitejs/plugin-vue` + `@tailwindcss/vite` wired, `@` → `resources/js` alias.
  `npm run build` green, routes code-split per page.
- Tailwind v4 entry at `resources/css/app.css` with the **Harbor tokens lifted verbatim**
  from the blade `<style>` blocks (navy `#3051d3`, ink `#23304d`, sheet/panel radii and
  shadows), so the port is a pixel match rather than a reinterpretation.
- Vue skeleton: `app.js`, `App.vue`, router with auth/ability/title guards, Pinia `auth` and
  `ui` stores, axios client that unwraps the `ActionResult` envelope, `ToastHost`
  (replaces toastr), `ConfirmHost` (replaces sweetalert2), `AdminLayout`, `Login`, `NotFound`.
- Enums: `App\Enums\UserType` (all 17 types, with `portal()`, `abilities()`,
  `isPseudoInvestor()`), `Portal`, `UserStatus`.
- Auth vertical slice, the template every later module copies:
  `LoginRequest` → `LoginAction`/`LogoutAction` → `UserResource` → thin `AuthController`
  with `try/catch` + `report()`. Routes at `routes/api/v1/{auth,admin}.php`.
- Sanctum hardened: `'guard' => []` (see below), `expiration` 720 min via `SANCTUM_EXPIRATION`,
  `ability`/`abilities` middleware aliases registered, `throttle:login` limiter keyed on
  email+IP.
- Migration `2026_08_24_100000_add_expires_at_to_personal_access_tokens_table` — the table
  predated Sanctum 3 and had no `expires_at`, so every token mint was 500ing. Table was empty;
  nothing to backfill.
- SPA mounted at **`/app`**, not the site root — a root catch-all would swallow `/login`,
  `/Admin/*` and the Funding pages while Livewire is still serving them. Phase 8 moves it to `/`.

**Verified end to end** (dev server, real HTTP): login 200 + token + abilities · `me` 200 ·
empty body 422 with the field bag the client's `ValidationError` expects · logout 200 and the
revoked token then 401 · 6th bad login 429 · wrong password, unknown email, deactivated
account and pseudo-investor types all refused with distinct statuses. Test user created and
force-deleted; the 8.66 GB dev DB is unchanged, 0 tokens and 0 smoke users left behind.

**Two decisions worth flagging**

1. `config/sanctum.php` `'guard'` is now `[]`, not `['web']`. With `['web']` Sanctum falls back
   to the session guard, so a logged-in **Livewire** session would have authenticated API calls
   with no token and skipped the ability checks entirely — a real hole for as long as the two
   front ends coexist.
2. Frontend is **plain JavaScript**, not TypeScript (owner's call). Open question 6 is closed.

### Phase 1 — done

- Full `AdminLayout`: sidebar tree ported from `Admin/layouts/sidebar.blade.php` (7 sections,
  27 links) and topbar from `topbar.blade.php` — brand, settings, user menu, logout.
- **The sidebar doubles as the migration tracker.** Each leaf is either `to` (a Vue route, module
  migrated) or `href` (the legacy Livewire URL, full page load out of the SPA), and migrated
  links are visually distinct from the ones that leave. When a module lands in Vue its `href`
  becomes a `to` and nothing else changes.
- Password reset end to end: `ForgotPasswordRequest`/`ResetPasswordRequest` →
  `SendPasswordResetLinkAction`/`ResetPasswordAction` → two Vue pages.
  `ResetPassword::createUrlUsing()` points the emailed link at the SPA, and a successful reset
  revokes every existing token.
- Forgot-password never reveals whether an address is registered — it would otherwise be a free
  account-enumeration oracle.

### Phase 2 — done (Accounts pilot)

The whole stack, proven against the real database. **Conventions are now frozen; every later
module is repetition of this shape.**

- Requests: `IndexAccountsRequest` (sort/per-page **allow-lists**), `StoreAccountRequest`,
  `UpdateAccountRequest`, `DeleteAccountsRequest`.
- `AccountResource` — initials, type label, status pill, money precision and the type-specific
  view URL are all computed server-side; Vue stays a renderer.
- Actions: `ListAccountsAction` (query shared by table **and** export, so the sheet is always
  exactly the filtered set), `AccountStatsAction` (60s cache, busted on write).
- `AccountController` — 8 endpoints, thin, `try/catch` + `report()`.
- **`UserPolicy` — the first policy on the platform.** `before()` passes Admin, back-office types
  get read/write, delete is admin-only.
- Vue: `useTable` composable (debounced search, stale-response guard, gapped pagination),
  `AccountsIndex` (full Harbor port — navy stats strip, toolbar, column toggles, filters,
  sortable grid, bulk select, export, pagination) and `AccountForm` (3 sections, live preview
  aside, US phone mask).

**Verified end to end:** paging/search/filter/sort against live data · sort allow-list rejects
`?sort=name;DROP TABLE users` with 422 · create, show, update, bulk delete · blank password on
update preserves the existing one · xlsx export downloads (6.3 KB) · stats cache busts on delete ·
**an investor token gets 403 on all six admin endpoints**. All test users force-deleted.

**Two bugs found and fixed while testing**

1. `$request->validated()` drops `password_confirmation` (not a rule key), so the Action's own
   `confirmed` re-validation failed on every create. Both requests now declare the field.
2. `authorize()` inside a `try/catch (Throwable)` turned every 403 into a 500. All four checks
   hoisted above their try blocks.

### Phase 3 — done (8 of 8 components)

**Done: Investors register + Investor create/edit form.**

- `IndexInvestorsRequest` (sort/per-page allow-lists) · `SaveInvestorRequest` (create **and**
  update; `attributes()` so nested rules stop rendering as "The profile.file type id field…").
- `ListInvestorsAction` (query shared by table and export, investor_type sub-select preserved) ·
  `InvestorStatsAction` · **`SaveInvestorAction`**.
- `InvestorResource` (list row) · `InvestorProfileResource` (form payload: users + investors rows).
- `InvestorController` — 8 endpoints, thin, `try/catch` + `report()`.
- Vue: `InvestorsIndex`, `InvestorForm` (4 sections, notification-email chip editor).

**Shared table shell extracted first.** Six more registers are coming, so `HbHero`, `HbTable`
(slot-per-column) and `HbColumnPicker` were pulled out before writing the second one, and
**Accounts was refactored onto them** so the two do not diverge. Result: AccountsIndex
14.0 → 6.9 kB, InvestorsIndex 15.9 → 8.8 kB, plus one 8.7 kB shared chunk.

**`SaveInvestorAction` is a rewrite, not a port.** The existing `StoreInvestorAction` and
`BuildInvestorFormAction` take the Livewire component itself, mutate its public properties, call
`$component->validate()` and return a `redirect()` — logic moved out of the component but still
welded to it, and unusable from an API. The replacement takes arrays, returns an `ActionResult`,
and can be called by a controller, a command or a job. It also:

- drops `User::selfCreate/selfUpdate` and `Investor::selfSave` from this path (**plan §7**), and
- **takes the actor as an argument** instead of reaching for `auth()->user()->id` inside the
  model, which is null in queue and console contexts (**plan §15.2**).

**Verified end to end** against live data (16 investors, $17.6M liquidity): paging, search, four
filters, sort · OverPayment correctly flagged `is_pseudo` and its edit action hidden · create ·
show · update (confirmed **one** users row and **one** investors row — no duplicate profile — with
`created_by`/`updated_by` set from the passed actor) · xlsx export · `?sort=name;DROP TABLE`
rejected 422 · investor token gets 403 on all four admin endpoints · Accounts regression clean
after the refactor. All test users force-deleted.

**The remaining 6 components are now done too.**

- **`ListTransactionsAction` collapses two Livewire components into one.** `Transaction\Table`
  (one investor) and `Transaction\ListTable` (every investor) were the same query — a `user_id`
  scope was the only difference — so both screens share one Action and one endpoint. The
  category-label sort (`FIELD()`, because ordering by `category_id` orders by constant) and the
  "1,250 finds 1250" amount search are carried over intact.
- `ListLiquidityLogAction` (read-only; written by the payment jobs) with filter options drawn from
  what the investor's own log actually contains.
- `ListInvestorAdvancesAction` (joined so merchant name and funded date stay sortable).
- `ListPriorityPassAction` (left joins so a row whose investor was deleted still lists and can
  still be taken off the program; zero-ceiling rows sort to the loose end).
- Resources: `InvestorTransactionResource`, `LiquidityLogResource`, `InvestorAdvanceResource`,
  `PriorityPassResource`. Controllers: `InvestorTransactionController`, `InvestorLedgerController`,
  `PriorityPassController`. **38 endpoints** live under `/api/v1`.
- Vue: `InvestorShow` with **Advances / Transactions / Liquidity Log tabs** — the three registers
  all answer questions about the same wallet, so they became one screen — plus a standalone
  `TransactionsRegister` and `PriorityPassIndex` (with the sample-advance sandbox ported).

**Verified end to end** on live data: 294 transactions · 981 liquidity-log lines · 35 advances.
Category→direction guard refuses an impossible pair (422) · a Debit stores negative and the
wallet recomputes (`LiquidityUpdateJob` runs inline on `QUEUE_CONNECTION=sync`) · deleting walks
the wallet back to 0 · a transaction id from another wallet is refused when a `user_id` scopes the
delete · Priority Pass re-save updates rather than duplicating, `max_percentage: 0` rejected,
removal works · every sort allow-list rejects `;DROP` with 422 · **an investor token gets 403 on
all ten new admin endpoints**.

Cross-check worth noting: the newest liquidity-log line's `net_liquidity` (1,970,960.726) matched
the independently computed wallet total exactly.

All test data removed — including the `liquidity_logs` rows the test movements produced, which
hold an FK to the actor and blocked the first cleanup attempt.

**Note on the dev database:** it was **reseeded by something outside this work** partway through —
all 121 `users` rows now carry today's `created_at` and `AUTO_INCREMENT` fell from ~230 to 125.
Phase 2 numbers (9 accounts / 6 lenders) and Phase 3 numbers (5 accounts / 3 lenders) are
therefore not comparable. No row was deleted by this migration work; every test account was
created and force-deleted by name.

### Phase 0 gate — money-math characterization tests · CLOSED

The §8 gate is now real. **Pest installed; `tests/Feature/Money/PaymentSplitTest.php`:
7 tests, 37 assertions, all green.** They do not assert what the cascade *should* do — they
pin what it *currently* does, to the cent, so boot() hooks can be moved and `self*` retired
without silently changing a figure.

Covered: `rtr = factor_rate x funded` and `payment_amount` derivation · per-investor
`share`/`rtr` derivation · the full fee cascade on a 3-investor 50/30/20 split (agent fee,
management fee, net, and that the three amounts sum to the whole payment) · net beyond
`invested` becoming profit rather than principal · an investor capped at their balance with
the excess redistributed · merchant and investor-link roll-ups · `users.liquidity` identity.

Setup notes for whoever runs these next:
- `testing` schema is migrated out of band (`DB_DATABASE=testing php artisan migrate`);
  `tests/Pest.php` uses **DatabaseTransactions**, not RefreshDatabase — 70 MySQL-specific
  migrations per test is not worth it.
- The tests seed their own `user_types` rows, because three pre-existing suites use
  `RefreshDatabase` and `migrate:fresh` drops anything seeded by hand.

**Two schema guards the tests surfaced** (both working as designed, both cost a first run):
1. `paid_net_amount` / `paid_principal` / `paid_profit` are **deliberately absent** from
   `MerchantInvestor::$fillable` — job-owned roll-ups. A plain `update()` on them is silently
   dropped; the fixture has to `forceFill()` past the guard.
2. `merchants.lender_id` is NOT NULL with an FK to `users`, so it defaults to 0 and trips the
   constraint unless a real lender is named.

### Phase 4 — started (advances register)

- `IndexMerchantsRequest` (sort **and** `based_on` date-column allow-lists — `based_on` is
  interpolated into `whereDate()`, so it must never come raw off the wire).
- `ListMerchantsAction` — the register plus a single-pass aggregate for the hero strip.
  Deliberately **not** cached: the totals describe the filtered set, so they must agree with
  the rows underneath them.
- `MerchantResource`, `MerchantController` (6 endpoints incl. an investor type-ahead, because
  a plain `<select>` of every investor is unusable).
- `MerchantsIndex.vue` — 13 columns, multi-select status chips, label/lender/date-basis
  filters, investor type-ahead with chips, completion bars.

**Verified on live data**: 100 advances · $24.4M funded · $33.1M RTR · $14.5M balance ·
62 active / 20 at risk. `rtr` on the first row is 1.22 x 350,000 = 427,000 — the derivation
checking itself against real records. Filters, sort, search, export (13.7 KB xlsx) all work;
`?sort=funded;DROP` and `?based_on=deleted_at` both 422; investor token 403s on all five
endpoints. Test users removed.

**44 endpoints** now live under `/api/v1`.

**Known pre-existing failure:** `tests/Feature/ExampleTest.php` asserts `GET /` returns 200,
but `HomeController` carries `middleware('auth')` so a guest gets 302. It is from the first
commit, untouched by this work, and has never been right for this app.

### Phase 4b — advance create / edit

The biggest single component in the codebase (`Merchant\Create`, 638 LOC) and the one that
touches the money core.

- `SaveMerchantRequest` — user + merchant + company-split in one shape, `attributes()` so
  nested rules read properly, and the legacy `factor_rate max:2` cap kept (a rate above that
  is a typo, not a deal).
- **`SaveMerchantAction`** replaces `Merchant::selfCreate/selfUpdate` **and**
  `User::selfCreate/selfUpdate` on this path. Both read `auth()->user()->id` for
  created_by/updated_by; the actor is now passed in (**plan §15.2**).
- `MerchantProfileResource`, plus `show`/`store`/`update` on `MerchantController`.
- `MerchantForm.vue` — 4 sections, a company-split grid where percentage and amount drive
  each other, "split equally" with the rounding drift absorbed by the first participant, and
  a sticky panel previewing the derived RTR and instalment.

**Three deliberate decisions**

1. **`rtr` and `payment_amount` stay derived by `Merchant::boot()`.** They are invariants every
   read depends on (plan §8). `SaveMerchantRequest::merchantData()` *strips* them from the
   payload, so a client cannot contradict the derivation — verified by sending
   `rtr: 999999` and getting 250,000 stored.
2. **The split must be exhaustive before anything is written** — 100% and equal to the funded
   amount. A deal whose companies do not add up would misreport every downstream share.
3. **`MerchantUpdateJob` dispatches after commit, not inside the transaction**, so the roll-up
   job never reads rows the transaction has not published.

**Verified end to end:** create (200,000 x 1.25 -> rtr 250,000, /100 -> 2,500) · update
re-derives (300,000 x 1.40 -> 420,000, /120 -> 3,500) · rtr/payment_amount unforgeable from the
wire · split guards refuse both a non-100% percentage and a mismatched amount · `factor_rate: 5`
rejected · re-save produces no duplicate `merchant_companies` row · one `users` row ·
`created_by`/`updated_by` carry the actor · **`Merchant::boot()`'s StatusChangeJob still fires
through the new Action** — a 1 -> 4 change logged `1 -> 4 by 127`, so the audit trail survives
passing the actor explicitly rather than reading `auth()` inside the model.

**47 endpoints** under `/api/v1`. Suite: 60 passed, 1 pre-existing failure.

**`self*` call sites: 77.** Up from 74 at the start — the Merchant and Investor paths are off
them, but Phase 4's remaining components (payments, banks, fees, terms) still call them, and
`MerchantPayment::selfCreate` is reached from `PaymentJob` itself. That number only falls once
the payment module lands.

### Phase 4c — payments and Add Payment

The money-critical screen. `PaymentJob` and the fee cascade are reached directly here.

- **`PreviewPaymentSplitAction`** — what a payment *would* do, ported from
  `AddPayment::shareCheck()`.
- `RecordPaymentAction` — validates the entry and dispatches the canonical batch
  `[PaymentJob, MerchantUpdateJob, InvestorUpdateJob]` (or `MerchantFeeJob` for
  `payment_mode_id = 4`). Does **no** money math of its own; the actor is passed in rather
  than read from `auth()`, so it is callable from a console command.
- `ListMerchantPaymentsAction` — the ledger, built from `merchant_payment_investors` grouped
  back up to the payment, because the fee columns only exist per investor. Totals run over
  the grouped set as a subquery; summing the joined rows directly would count each payment
  once per investor.
- `StorePaymentRequest`, `MerchantPaymentResource`, `MerchantPaymentController` (5 endpoints).
- Vue: `MerchantShow` + `PaymentsTab` + `AddPaymentPanel` with a live split preview,
  capped/redistributed badges and an overpayment warning.

**The duplicate-cascade problem, and what was done about it**

The Add Payment screen computes the split so the operator can see it; `PaymentJob` then
computes it **again** when the entry is recorded. Two implementations of one fee cascade, with
nothing forcing them to agree. Consolidating them means editing `PaymentJob`, which is why it
was not done here.

Instead, `tests/Feature/Money/PaymentPreviewTest.php` **pins them together**: it previews a
payment, records the same payment, and asserts every figure matches to the cent — on an
ordinary split *and* on the capped/redistribution branch, which is where drift would most
likely hide. If either implementation changes, that test says so.

The preview is also computed **server-side**, not in JS — a third implementation in the
browser would be one more thing to keep in step.

**Verified against real data** (advance with 63 payments, $506,614.52 collected): both money
identities hold on a live row — `investors_amount − agent_fee − management_fee = net_amount`,
and `principal + profit = net_amount`; `rtr − collected = balance` at the deal level.

**Verified on a throwaway advance**: preview showed A 650/32.50/61.75/555.75, B 390/19.50/0/370.50,
C 260/13/12.35/234.65 — and the recorded payment wrote **exactly** those figures, with roll-ups
following (balance 128,700, 1% complete, paid_count 1). A debit preview caps each investor at
what they were paid. A 200,000 payment against a 128,700 balance previews 128,700 placed +
71,300 overpayment, summing exactly. Entry guards (no amount and no return code; fee mode with
no fee account) both 422. Investor token 403s on all five endpoints.

**52 endpoints.** Suite: 62 passed (198 assertions), 1 pre-existing failure.

**Two data observations (neither caused by this work, neither touched)**

1. **A merchant was created through the legacy Livewire form during this session** —
   merchants.id 102, `cv@iocod.com`, funded 120,000, factor 1.5, created_by user 2
   (`iocod@iocod.com`). That is the `APP_ENV=local` pre-fill in `Merchant\Create`, submitted
   from a browser. It is real user activity, not test data, so it was left in place. It also
   confirms both stacks are genuinely serving traffic at once.
2. **Four merchants are orphaned** — ids 25, 32, 34, 96 have a `user_id` with no `users` row
   (all seeded with 2025 dates). `MerchantResource` degrades to an empty name rather than
   erroring, but the rows are worth a look.

### Phase 4d — PaymentJob off `selfCreate` (plan §7 reaches the money core)

The characterization tests existed for exactly this. `PaymentJob` and `MerchantFeeJob` both
created their rows through `Model::selfCreate()`, whose contract was an
`['result' => 'success'|<error string>]` array checked by string comparison.

- New trait `App\Jobs\Merchant\Concerns\CreatesValidatedModels` — validates against the
  model's own `rules()` and creates it, throwing on failure. Same rules, same abort-and-roll-back
  behaviour; callers now get a real model instead of an array.
- **6 call sites converted** across the two jobs (4 in `PaymentJob`, 2 in `MerchantFeeJob`).
- **5 model methods deleted outright** once they had no callers left:
  `MerchantPayment::{selfCreate, selfUpdate}` and
  `MerchantPaymentInvestor::{selfCreate, selfUpdate, selfDelete}`.

**Deliberately left alone**

- `MerchantPayment::selfDelete` — `Livewire\Admin\Merchant\Payments` still calls it.
- `MerchantInvestor::selfCreate` — **not** a thin wrapper. It carries a duplicate-investment
  guard and cascades investor-fee creation, so it is a real Action's worth of logic and belongs
  with the assign-investor screen, not with this refactor.
- One ordering change in `MerchantFeeJob`: the create now throws before the investor roll-up
  rather than after it. Both sit in the same transaction, so the committed state is identical.

**Proof it changed nothing**: all 9 money tests green before and after, and a live end-to-end
run through the refactored job produced byte-identical figures — A 650/32.50/61.75/555.75,
B 390/19.50/0/370.50, C 260/13/12.35/234.65, balance 128,700, 1% complete, paid_count 1.

**`self*` progress: call sites 77 → 70, method definitions 60 → 55.** This is the first time
the count has gone down; every earlier phase added Actions beside the old methods without being
able to remove them.

### Phase 4e — the syndicate (investors on an advance)

The domain core: who is in a deal, for how much, and what each has been repaid.

- **`AssignInvestorsAction`** replaces `MerchantInvestor::selfCreate()` — which was **not** a
  thin wrapper. It carried a duplicate-investment guard and cascaded `MerchantInvestorFee`
  rows off the advance's own fee schedule. Both preserved, now with arrays and an explicit
  actor. The whole assignment is one transaction: a duplicate on the third investor rolls the
  first two back, because a half-syndicated advance misreports every share.
- `ListMerchantInvestorsAction` (replaces the Yajra table behind `Livewire\Merchant\Investor`),
  `RemoveInvestorAction`, `MerchantInvestorResource`, `AssignInvestorsRequest`,
  `MerchantInvestorController` (5 endpoints).
- Vue: **`InvestorsTab`** on the advance detail — staged assignment list with live share
  preview and "use remaining", an *unplaced* tile that goes amber when the deal is not fully
  syndicated, and a lock icon on investors who cannot be removed.

**5 new characterization tests** (`AssignInvestorsTest`) pin the behaviour that was inside
`selfCreate`: share/rtr derivation, `company_id` resolution, the duplicate guard, whole-batch
rollback on a mid-list duplicate, the fee cascade off the advance schedule, and an explicit
per-investor schedule overriding it.

**Two guards added that the legacy screen did not have**

1. A link that has **already been paid on cannot be removed** — deleting it would strand money
   already split to that investor. The UI shows a lock instead of a bin.
2. A link id from **another advance** cannot be removed by posting it to this one.

**Verified on live data**: advance 100 reads 6 investors, 91,358.10 placed of 124,500 funded —
a genuinely part-syndicated deal, so *unplaced* correctly shows 33,141.90 and every investor is
non-removable because they have been paid. On a throwaway advance: 60k/40k assigned → shares
60.0000%/40.0000%, rtr 78,000/52,000, fee cascade 2% → 1,200 and 800; duplicate refused by name
("Aaron Katz has Already Invested"); removal recalculated placed → 60,000 / unplaced → 40,000;
cross-advance and already-paid removals both 422.

**Known landmine left standing, on purpose.** `MerchantInvestor::boot()`'s `deleting` hook
dispatches `InvestmentUpdateJob` using `auth()->user()->id` (plan §15.2). It cannot be removed
yet: `Account\DeleteAccountsAction` also deletes these rows, and without the hook that path
would stop recalculating roll-ups entirely. Removing it needs `DeleteAccountsAction` to take an
actor first, which touches the Accounts, Investors **and** Merchants delete paths at once.
`RemoveInvestorAction` is the sanctioned route meanwhile, and refuses to run outside an
authenticated request rather than letting the hook fatal deeper in.

**57 endpoints.** Suite: 67 passed (217 assertions), 1 pre-existing failure.
Merchant components with Vue equivalents: **7 of 30**.

### ⚠️ Schema finding — `ON DELETE CASCADE` from `users` into financial tables

Found the hard way while testing Phase 4f: **hard-deleting a user destroys every advance and
payment that references them**, silently.

`merchants.created_by`, `merchants.updated_by`, `merchants.user_id`, `merchants.lender_id` and
`merchant_payments.creator_id` are all `ON DELETE CASCADE` to `users` — 30 such constraints
point at `users` in total.

Blast radius on the current database:

| Delete this user | Destroys |
|---|---|
| `rahees@iocod.com` (id 1) | **100 advances** and **2,783 payments** |
| `iocod@iocod.com` (id 2) | 1 advance |

**Why production is not currently exposed** — and this matters: `User` uses `SoftDeletes`,
`DeleteAccountsAction` calls `$user->delete()` (soft), and there is **no `forceDelete` anywhere
in `app/`**. The cascade only fires on a hard delete. It is a latent landmine, not an active
bug.

**It is still worth fixing**, because anything that hard-deletes a user — a GDPR purge, a
cleanup script, `model:prune`, or a DBA running SQL — would take the ledger with it and leave
no error. The audit-trail columns (`created_by` / `updated_by`) especially have no business
cascading; they should be `ON DELETE RESTRICT` or `SET NULL`.

**What happened here:** the story test set `updated_by` to a temporary admin, and force-deleting
that admin in cleanup cascaded `merchants.id = 100` out of existence — while leaving its 6
investors, 60 payments, 360 payment-investor rows and 366 liquidity logs orphaned. The row was
rebuilt from `owen-it/laravel-auditing` (which had captured the story) plus values derived from
the surviving children: `funded x factor_rate = 178,035` matches `sum(payments)` exactly, which
independently confirms both. **`lender_id` was unrecoverable and is a placeholder (20) that
needs correcting** — advance 100 only. My mistake was running a write test against a real
seeded advance instead of a throwaway one.

### Phase 4f — securities, FAQs and the marketplace story

- `MerchantContentController` — 10 endpoints across three small per-advance resources that were
  four Livewire components (`Security\Create`, `Security\Table`, `Faq\Create`, `Faq\Table`,
  `Story`).
- `SaveMerchantStoryAction` replaces `Merchant::selfUpdate()` on the story path and keeps the
  legacy image location (`Upload/Merchant/{id}/Story/{time}-{name}` on the `public` disk) so
  existing `story_image` values stay resolvable.
- Requests: `SaveSecurityRequest`, `SaveFaqRequest`, `SaveStoryRequest` (image, max 5 MB).
- Vue: one **`ContentTab`** on the advance detail with three sections, inline edit for
  securities and FAQs, and an image preview for the story.
- Every write is scoped: a security or FAQ id belonging to another advance is refused with 422.

**Environment fix applied:** `public/storage` was never linked, so story images 404'd — in the
**legacy** Livewire screen too, since its blade builds `storage/...` URLs. Ran
`php artisan storage:link`; images now serve.

**67 endpoints.** Merchant components with Vue equivalents: **11 of 30**.

### Phase 4g — banks, ACH terms, status log, pending investments, payoff letters

Five screens, nine Livewire components.

**Banks** — `SaveBankAccountAction`, `MerchantBankResource`, `MerchantBankController`,
`BanksTab`.
- **The numbers never leave the API.** They are `encrypted` casts listed in
  `MerchantBank::$hidden`; the Resource exposes only the stored last-4 columns. Verified: a
  raw list response contains no `account_number` key at all, the ciphertext is real
  (`eyJpdiI6…`), the blind index re-hashes to the stored value, and `toArray()` still hides
  them. Editing therefore cannot pre-fill the numbers — the operator re-enters them, which is
  also the only way the blind index and last-4 can be rebuilt.
- Duplicates are matched on `account_number_hash`, never the column: the encrypted value
  differs on every write, so equality on it can never hit.
- **Behaviour change, deliberate:** default flags are now exclusive. The legacy screen only
  *warned* that another account held the default and wrote it anyway, so two accounts could
  both be `default_debit` — and `MerchantBank::primaryFor()` resolves that with
  `orderByDesc(default)->orderByDesc(id)`, i.e. whichever was added last. An ACH debit could
  originate against an account nobody chose. No merchant currently has a conflicting pair, so
  no existing data changes.

**ACH terms** — `GeneratePaymentDatesAction`, `SavePaymentTermAction`, `MerchantTermController`,
`TermsTab` with a live date preview.
- The generator was **diffed against a verbatim copy of the original** across 360 schedules
  (4 cadences × 5 start dates × 6 lengths × 3 holiday sets). The first port differed on 6 of
  them, all daily-with-holiday: the legacy `goto` **resets its day counter to 0 on every
  restart while the weekend/holiday drift counters keep climbing**, so days skipped twice are
  counted twice and a daily schedule drifts further than the calendar explains.
- That is wrong arithmetic — and it is the arithmetic every live ACH schedule was built with.
  Rewriting it would move real debits onto different days, so the quirk is reproduced exactly.
  **0 mismatches** after the fix, and `PaymentDatesTest` pins it (9 tests, 304 assertions),
  including one case whose only purpose is to stop someone "fixing" the double-count.
- Dates are regenerated server-side on save rather than trusting the previewed list.

**Status log** — `ListStatusChangesAction`, `MerchantStatusController`, `StatusLogTab`: a
timeline that classifies each move as risk/warn/good, marks escalations and recoveries, counts
days spent in trouble across every spell, and folds long histories.

**Pending investments** — `ListPendingInvestmentsAction`, `ChangeInvestmentStatusAction`,
`PendingInvestmentController`, a full page. Replaces `MerchantInvestor::selfUpdate()` on this
path, which round-tripped every roll-up column through mass assignment; the Action touches only
`active_status`. Live: 64 pending across 50 advances, $2.4M.

**Payoff letters** — `SaveAchFeeAction`, `MerchantAchFeeController`, `PayoffTab`. The header
total is **recomputed from the lines**, never accepted from the client (verified: 2×35 + 100
→ 170, then +3×35 → 275, then reduced to 1×35 → 35). Lines are rewritten wholesale so a
removed line actually disappears. Prices are stored per line, so a historical letter keeps the
price it was written at.

**Routing bug found and fixed.** `/merchants/term-options` and `/merchants/ach-fee-options`
were registered *after* `Route::get('{merchant}')`, so the wildcard matched them as merchant
ids and both 404'd — `TermsTab`'s cadence list would have come up empty in the browser. Static
segments now precede the wildcard, with a comment saying why.

**89 endpoints.** Merchant components with Vue equivalents: **20 of 30**.

### Phase 4h — the dashboard

The landing page. `PeriodActivityAction` was already array-based and returned an `ActionResult`,
so it needed no rewrite — the only thing missing was the range resolution, which lived inside
the Livewire component.

- **`ResolveReportPeriodAction`** — extracted from `PortfolioActivity::range()`. On its own
  because the dashboard, the report screens and any scheduled digest all need the same answer
  for "last 30 days", and three copies would drift. Keeps the behaviour that a **reversed
  custom range is swapped rather than producing an empty report**.
- `DashboardController` (2 endpoints), `dashboard.js`, `DashboardIndex.vue`.
- The page: hero totals, four vs-prior-period cards, a collections bar chart, the book split by
  tone (live / watch / trouble / closed), ACH paid-rate, top payers, investor flow, one-time
  fees and status moves.
- **The dashboard is now the landing route.** `/app/admin` renders it, and login, the 404 page,
  the sidebar and the layout brand all point there instead of at Accounts.

**Verified on live data:** 30d window reads 1,236,854.07 over 129 payments against a prior
908,512.01 over 109; ACH 167/181 paid; liquidity 22,112,780.03. Every preset returns a distinct
range, and `auto` granularity buckets by **day** for short windows and switches to **month** for
`this_year` on its own. A reversed custom range (30 Jun → 1 Jun) returns exactly the same
figures as the right-way-round one. Bad period and bad granularity both 422; no token 401;
investor token 403.

**91 endpoints.** Suite: 76 passed (521 assertions), 1 pre-existing failure.

### Phase 4i — the daily ACH send screen and the merchant liquidity log

**Send Merchant ACH** — `ListDueDebitsAction`, `SaveDayAchFeesAction`,
**`SendDueDebitsAction`**, `AchGenerateController` (3 endpoints), `AchGenerate.vue`.

This screen originates **real ACH debits**, so three properties are preserved deliberately:

1. **Each row is its own transaction.** One merchant failing must not stop or roll back the
   others — a batch that half-sent and then reverted would leave live entries at the bank with
   no record of them here. Failures come back per row and stay on screen; what went out becomes
   InProgress and drops off.
2. **A fee that was never saved is never sent.** If a row carries fees but no `MerchantAchFee`
   exists for that merchant and date, the row is refused rather than debited for the instalment
   alone. The UI also blocks sending while the fee grid is dirty.
3. **Nothing sends without confirmation.** The endpoint requires `confirm: accepted`, and the
   rows are **re-read from the database** rather than taken from the request — a client cannot
   name an amount, and a term date that is not `NotPaid` cannot be sent twice.

Verified without originating anything: missing confirm 422, `confirm:false` 422, empty list 422,
already-sent id 422, investor token 403. Read and fee-save paths tested live and undone.

**Bug found and fixed (mine).** `ListDueDebitsAction` eager-loaded
`MerchantPaymentTermDate::MerchantAchFeeItems`, but that relation constrains itself with
`->where('merchant_id', $this->merchant_id)` — during eager loading `$this` is an empty
instance, so the constraint becomes `merchant_id = null` and **every saved fee quantity came
back as 0**. The legacy screen read it lazily and was correct but N+1. Replaced with one
grouped query: correct *and* one query.

**Merchant liquidity log** — `ListMerchantLiquidityAction`, `MerchantLiquidityController`
(3 endpoints), `LiquidityTab`. Rows are grouped into the batch a job wrote them in, because a
batch is one decision and its total is the figure that means something.

**Second bug found and fixed (also mine).** The grouping key is
`(batch_no, description, creator_id)`, but the drill-down filtered on `batch_no` alone. Real
data has batch 15 carrying **both** an `Investment` of 1 line and a `Payment` of 62 — so
expanding a row returned 63 lines totalling 6,624.60 against a row showing 62 lines and
46,093.57. All three columns are now part of the key; every row reconciles with its expansion.

**97 endpoints.** Merchant components with Vue equivalents: **22 of 30**.

### Design fidelity pass — the chrome was approximated, not reproduced

Feedback: the icons, font and topbar did not match. Correct — the layout had been built to
*look like* Harbor rather than measured against the template. Fixed by reading
`assets/css/app.min.css` instead of choosing values.

| | Was | Now (measured) |
|---|---|---|
| Typeface | Tailwind default sans | **Nunito** — `app.min.css` imports it from Google Fonts; the SPA never loaded that stylesheet, so it silently fell back |
| Topbar height | 56px | **70px** (`#page-topbar`) |
| Topbar colour | `#2a3fb0` | **`#3051d3`** |
| Sidebar width | 240px | **250px** (`.vertical-menu`) |
| Sidebar shadow | none | **`0 2px 4px rgba(0,0,0,.08)`** |
| Menu link | Tailwind defaults | **`.7rem 1.5rem`, 15px, `#7c8a96`, hover `#383c40`** |
| Sub-menu link | indent guess | **`.4rem 1.5rem .4rem 3.7rem`, 13.5px** |
| Header items | ad-hoc | **70px tall, `#e9ecef`, 22px glyphs** |
| Brand | text "Investor Portal" | **`/image/logo.svg`** in a 250px centred brand box |
| Sidebar icons | generic `mdi-*` | **the original inline SVGs, verbatim** |

The measurements are now Tailwind theme tokens (`--spacing-topbar`, `--spacing-sidebar`,
`--color-nav-link`, `--shadow-chrome`, …) so the numbers live in one place and the layout reads
against them rather than hard-coding pixels.

**Sidebar icons.** Four of the seven are bespoke inline SVGs drawn for this portal — a cash
briefcase for Merchant, two dollar plants for Investors. Substituting an icon set for them
changes what the navigation *is*, so `resources/js/config/navIcons.js` carries the original
markup across unchanged, extracted from the blade.

**Worth knowing:** the other three (Dashboard, Log, ACH) use `uim uim-airplay` /
`uim uim-layers-alt` — Unicons classes that are **not defined in any shipped stylesheet**. They
render blank in the *legacy* sidebar too. The original classes are carried across so they will
work if Unicons is ever added, but today those three sections have no icon in either UI. Say
the word and they can be given MDI equivalents.

### Fixes from review — session bridge, icons, missing form fields

**1. Menu items bounced to /login.** The real one. Legacy routes sit behind
`middleware('auth')` — the SESSION guard — while the SPA authenticates with a Sanctum bearer
token and, by design (`sanctum.guard => []`), never touches the session. So every un-migrated
sidebar link 302'd even though the operator was signed in.

Fixed with `SessionBridgeController`: `POST /app/session` is authenticated by the bearer token
but runs inside the `web` middleware group, so it writes the session cookie the Livewire pages
need. The SPA calls it on login and on boot; `POST /app/session/destroy` clears it on logout so
a shared machine is not left signed into the legacy side.

**The bridge only goes one way.** `sanctum.guard` is still `[]`, so a session can never
authenticate an API call. Verified: legacy pages 302 → **200** after bridging; the API returns
**401 with the session cookie alone** and 200 only with the token; after logout the legacy page
302s again. Delete this controller with the last Livewire screen.

**2. Dashboard/Log/ACH icons missing.** They asked for `uim uim-airplay` / `uim uim-layers-alt`
— Unicons classes not defined in any stylesheet this project ships, so they were blank in the
legacy sidebar too. Given MDI equivalents; the original class is kept in `original_class`.

**3. Merchant form was missing fields.** Audited the legacy blade's `wire:model` bindings
against the Vue form and found **11 missing**: `first_name`, `last_name`, `business_address`,
`credit_score`, `experian_intelliscore`, `experian_financial_score`, `debit_ratio`,
`monthly_revenue`, `centrex_advance_id`, an `ach_flag` control, and — most importantly — the
whole **commission-rates section**.

Those commissions are three fixed `MerchantFee` lines (Commission on Funded, Up Sell Commission
on RTR, Syndication Fee on Funded, each capped at 20%). They are not cosmetic: `AssignInvestors-
Action` cascades the advance's fee schedule onto **every investor who syndicates**, so a missing
commission silently changes what each investor is charged.

A blank rate now deletes the line rather than storing 0% — a fee that is not on the deal reads
differently from a fee set to zero. Verified end to end: all 11 fields round-trip, the two
entered commissions become real `MerchantFee` rows, the blank one is absent, and 25% is refused.

### Field audit — every ported form vs its legacy blade

Rather than wait for gaps to be reported one at a time, `scratchpad/audit.py` diffs each legacy
blade's form bindings against the Vue file that replaced it. Twelve screens, 102 legacy fields.

The first pass reported 0 fields for three blades — the regex only matched
`wire:model="field"`, and `terms`, `pay-off-letter` and `story` use laravel-collective
`Form::` helpers with `'wire:model' => 'field'` **single-quoted inside a PHP array**. Worth
knowing before trusting any audit of this codebase.

Result after fixing that: **11 candidates, 10 of them renames**, each verified individually —
`debit_type` → `debit`, `pay_off_fee_id` → `draft.fee_id`, the four Priority Pass
`filter_*` → `table.filters.*`, `selected_companies` → the split rows' `selected`, and
`user_type_id`, which is deliberately forced server-side rather than accepted from the wire.

| Screen | Legacy fields | Real gaps |
|---|---|---|
| Account, Bank, ACH terms, Investor txn, Story, ACH generate | 6 / 10 / 8 / 3 / 5 / 2 | none |
| Investor form, Merchant form, Add payment, Payoff letter, Priority Pass | 17 / 30 / 6 / 5 / 10 | none (renames) |
| **Assign investor** | 6 | **1 — real** |

**The one real gap.** `AssignNewInvestor` scopes its investor picker with
`whereIn('company_id', $share_company_ids)` — only investors belonging to a company that is
actually **funding this advance** may syndicate it. The Vue tab offered every investor.

On the current database both return 10, because there is one company and every investor belongs
to it — which is exactly why this was easy to lose. The constraint only bites once a second
company exists, and by then an investor could already sit on a deal their company has no share
of. Now scoped, with the investor's liquidity in the picker label as the legacy dropdown had it,
and an explicit message when no investor is eligible.

### Phase 4j — the three investment-allocation screens

Lender payment was dropped from scope on request. The three auto-allocators are ported as one
**Auto-allocate** tab with a strategy switch, plus `MerchantAllocationController` (2 endpoints).

- **`AllocateByLiquidityAction`** — split in proportion to each investor's available wallet
  (`liquidity x max_share_percentage`). Anyone whose slice falls under the minimum is dropped
  and the whole split recomputed without them.
- **`AllocateByPriorityPassAction`** — equal split bound by each investor's own % and $ caps,
  with what a capped investor cannot take offered again to those with headroom.
- **`AllocateByPaymentAction`** — roll what investors collected over a window back into the
  advance, less the advance's commission lines. Replaces a per-investor query inside a loop
  with one grouped query.

All three **propose only**. Committing goes through `AssignInvestorsAction` like any other
syndication, so the duplicate guard, the fee cascade and `InvestmentUpdateJob` still run — the
allocator never becomes a second way to write an investment.

**Diffed against the original, 1,080 allocations, 0 mismatches.** The liquidity split uses a
`goto` to drop-and-recompute; after the ACH date generator turned out to differ on 6 of 360
schedules, this one was diffed the same way before being trusted — 5 pool seeds x 6 sizes x
4 requirements x 3 percentages x 3 minimums.

**Bug found and fixed (mine).** The payment strategy validated its date range in a *second*
`$request->validate()` inside the try block, so a missing date came back as **500** instead of
422 — the same shape as the `authorize()`-inside-try bug from Phase 2. The rule is now
`required_if:strategy,payment` in the single validate() call above the try, with
`after_or_equal` catching a reversed range too.

**Verified on live data:** advance 2 (363,500 funded, 110,887.12 placed) proposes 9 investors
summing to **exactly** the 252,612.88 still open, with 3 dropped under the 1,000 minimum;
Priority Pass shows its cap and which of the two caps binds; the payment strategy rolls
8,926,608.68 collected into 8,156,242.35 after 8.63% commission.

**99 endpoints.** Merchant components with Vue equivalents: **25 of 30** (lender payment out of
scope). Left: edit-investor, audit report, ACH fee register, companies.

### Phase 4k — the last four merchant screens

- **Edit participation** — `UpdateInvestorParticipationAction` + `show`/`update` on
  `MerchantInvestorController`. Replaces `MerchantInvestor::selfUpdate()` and keeps three
  behaviours: a participation that has taken a payment **cannot** be edited (its share has
  already split real money); a change to `invested` writes an `Investment Edit` **liquidity
  log line** for the difference, because the wallet is
  `SUM(transactions) + SUM(paid_net_amount − invested)` and without the line the balance moves
  with nothing explaining it; and `InvestmentUpdateJob` recomputes the roll-ups afterwards.
- **Companies** — `ListMerchantCompaniesAction`: what each company committed against what its
  own investors actually placed, with the gap surfaced as *unplaced*.
- **Audit** — `MerchantAuditController` over the existing `AuditPresenter`, which was already a
  plain service class, so the trail, field labels and edit/system classification are unchanged.
- **ACH fee register** — the same `merchant_ach_fees` data the payoff screen already served;
  it needed the legacy status/date filters rather than a new screen.

**Two things caught while verifying**

1. **Company totals were grouped on the wrong column.** I grouped on
   `merchant_investors.company_id`; the legacy screen joins `users` on `investor_id` and groups
   on **`users.company_id`** — the investor's own company. The link's column is a resolved
   fallback (it fills in the advance's company for the pseudo-investors), so the two disagree
   exactly when it matters. Identical on today's single-company data, which is why it needed
   checking rather than eyeballing.
2. **The audit cell rendered `None → None`.** `AuditPresenter::changes()` returns
   `{ label, from, to, delta, system, minor }` where `from`/`to` are objects carrying `text`
   and a full-precision `title` — my template read `c.old`/`c.new`. The API had been correct
   throughout; only the display was wrong. Now shows values, deltas, full precision on hover,
   and folds away `minor` changes (updates whose two sides print identically).

**103 endpoints.** Merchant components with Vue equivalents: **29 of 30** — lender payment
remains out of scope by request.

**Not started**

- Pest + the money-math characterization tests. These gate §8 (`boot()` changes) and nothing
  has touched a model hook yet, so the ordering still holds — but this is the next task.
- `DataTable.vue` and the `ui/` + `form/` component sets beyond the toast/confirm hosts.
- The `env()`-outside-config fix in the legacy layouts.

### Phase 6a — the reports module

Twelve legacy report screens (`ReportController`, 1,555 lines of Yajra DataTable
callbacks) become **one Vue page driven by a server-side description of each report**.
The server says what filters a report takes, which columns it can be sorted on, and
whether it drills down; the page renders that. Columns are still derived from the rows
themselves — twelve queries select twelve different shapes, and a hard-coded column list
per report would drift from the SQL the moment a column is added.

**Shipped**

- `RunReportAction` — the ten paged reports, each entry naming its source query, sortable
  columns, count columns, select and default order. `RunReportDetailAction` — the five
  drill-downs, keyed by report so the parent key is validated and the child query cannot
  be chosen freely. `ReportQueries` — the four queries that only ever existed inline in a
  DataTable callback (merchant status log, both liquidity logs), now in one place.
- `ReportFilterRequest` covering the full legacy filter set, including the five
  payment-details filters that had been missed (payment mode, return code, amount range,
  remarks) and the liquidity-log movement labels.
- `ReportController` — `options`, `run`, `detail`, `managementFee`, `profitability`.
- `ReportsIndex.vue` + `HbMultiSelect.vue` (the selectize replacement): filter set per
  report, sortable headers, paging, footer totals, inline drill-down rows.

**Four bugs found, all of them in the port rather than the legacy code**

1. **`paginate()` could not count two of the reports.** `payment` and `investment` hang a
   `having()` on an alias produced by a joined sub-select. Laravel counts such a query by
   wrapping it as a derived table with the SELECT replaced by `<from>.*`, which drops the
   alias — MySQL then rejects the HAVING. Wrapping it with the full select instead fails
   differently: `merchants.*` and `merchant_investors.*` both carry `rtr` and `funded`, and
   a derived table needs unique column names. Both now count over the narrowest unique set
   that still satisfies the HAVING — the row key plus the alias it filters on.
2. **None of the six helper queries orders itself.** The legacy DataTable always sent an
   `ORDER BY`, so it never showed; paged by `LIMIT/OFFSET` with no order, MySQL may repeat
   a row on one page and drop it from the next. Each report now has a default order on its
   own row key. Pinned by a test that walks three pages and counts duplicates.
3. **`based_on` defaulted to a value no helper recognises** (`date`, against helpers that
   switch on `payment_date`/`funded_date`/`changed_date`). It happened to land in the right
   `default:` branch; it is now left null so each helper applies its own default, and the
   allowed set is the union of what the screens offer.
4. **`max_amount` on its own was a 422** — the `gte:min_amount` rule fired against an
   absent floor. A ceiling with no floor is a perfectly good filter.

**One deliberate deviation from legacy.** The merchant liquidity log grouped on `batch_no`
alone. A batch number is not unique — an Investment and a Payment posted in the same batch
share it — so the report summed two unrelated postings into one row and labelled it with
whichever description MySQL picked. It now groups on `(batch_no, description, creator_id)`,
the key the merchant screen already uses. And a batch is not one merchant: **every** batch
in the book spans dozens of them, and legacy selected `merchants.name` anyway, naming an
arbitrary advance out of forty-odd. The name is now given only when the posting really does
concern a single merchant, with the count alongside. Column totals are unchanged throughout,
and each grouped row reconciles exactly with its drill-down (30/30, pinned by a test).

**Verified against the unported queries**, not just for absence of errors: every report's
paginator total equals the row count of the same query run unpaged (10/10), and the
liquidity report's seven column totals match the legacy DataTable footer to the cent.

**Injection.** Fifteen sites where a request value was concatenated into an `IN (...)`
fragment now go through `sqlIdList()`, with integer array rules in the FormRequest as the
second line of defence. `investor_ids[]=2) OR 1=1 -- ` is a 422 at the edge and `'1,2'`
after `sqlIdList()`.

`tests/Feature/Reports/ReportEndpointTest.php` — 14 tests, 199 assertions.

**Not carried over:** the legacy per-column search boxes (Yajra `filterColumn`), which
searched formatted currency strings with `LIKE '%1,234%'`.

### Phase 6b — the two log screens, and the reports module reaches the menu

**The reports module was finished but unreachable.** Twelve sidebar entries still
pointed at their Blade URLs. They now all resolve to the one Vue screen with the
report in the query (`/admin/reports?report=payment_details`), so the module keeps
twelve addresses rather than collapsing into one. Three things had to follow:

- `ReportsIndex` reads the report from the URL and writes it back on change, so a
  link to one report can be shared.
- `isActive()` in the sidebar compares the query as well as the route name —
  otherwise one shared route name lit all twelve entries at once.
- The title band can be set by the screen (`ui.setPageTitle`), because one route is
  now twelve screens and the band should name the one on show, as each legacy page
  did. It is cleared on every navigation so it cannot leak onto the next screen.

**API log** (`livewire/admin/api-log/table.blade.php`) and **Actum request log**
(`livewire/admin/actum/log.blade.php`) are ported. Both share the `.hbq-*` palette,
copied verbatim to `resources/css/actum.css` and verified character-identical to the
Blade partial (13,027 non-whitespace chars either side). Each is Controller →
FormRequest → Action, read-only throughout, with the query, its totals and the
filter lists lifted out of the components' `render()`.

Two things had to move server-side rather than be duplicated in JavaScript: the
Actum decline wording (`ActumDeclineCode::describe()`, now travelling on each call
as `decline_label`) and the enum-backed filter lists. The API log's caller filter is
still built from the log itself, so it can never offer a choice that returns nothing.

**Payloads are decoded in the resource**, not shipped as JSON strings — the screens
pretty-print them, and encoding on the way out then parsing on the way in is how the
two drift.

`tests/Feature/Logs/` — 15 tests. The Actum ones exist because that screen filters a
JSON column: `whereJsonContains('call_log', ['outcome' => ...])` matches a
transaction when ANY call in its conversation carries the value, so a transaction
whose first call succeeded and whose third was declined belongs under "declined".
A wrong predicate there returns rows rather than an error, which is exactly the kind
of bug that survives a manual check.

### Phase 6c — the ACH register

`Admin/Actum` — every transaction sent to Actum, one row each, opening into the four
gateway payloads. Reads plus one write: the status check, which is read-only on
Actum's side (`action_code=A`) and never resubmits, which is why it is a refresh
button and not a "resend" one. `SyncActumStatusAction` already existed and is reused
unchanged.

The screen updates the checked row **in place** rather than re-running the query, so
a status check does not cost the reader their scroll position or the row they had
open. Payloads are decoded in the resource, and one that will not parse is handed
back as stored — an operator reading a malformed reply still needs to see it.

`tests/Feature/Ach/ActumRegisterTest.php` — 8 tests, aimed at the stats aggregate:
one CASE-heavy query with a dozen ordered bindings, where collected counts settled
DEBITS, paid out counts settled CREDITS, and in-flight counts the three statuses
whose outcome nobody knows yet. A binding out of order still returns numbers, just
the wrong ones.

**The sidebar is now 25 migrated, 2 legacy**, and both remaining entries are
deliberate: Lender Payment (out of scope by request) and the Laravel log, which is
`log-viewer`'s own UI rather than a screen of ours.

**Still Livewire:** the three Settings screens (reached from the topbar wrench, not
the sidebar), the Excel payment upload, and the company view.

### Phase 6d — the design audit, and what it found

Asked to check every screen for design and field parity, so I built the check
rather than eyeballing thirty screens. Three scripts, all committed:

- **`docs/screen-audit.py`** — every `wire:model`, `Form::` helper and `name=`
  binding in each legacy Blade, checked for a mention in the Vue that replaced it.
- **`docs/extract-styles.py`** — lifts each Blade's `<style>` block into
  `resources/css/legacy/`, verifying each copy character-for-character.
- **`docs/design-audit.py`** — what share of a legacy stylesheet's vocabulary the
  Vue screen actually uses. 100% means the markup was ported; less means it was
  reinterpreted.

**Fields: one real gap out of 36 candidates.** The rest were renames the audit
cannot see through (`fromDate` → `from_date`, `debit_type` → `debit`), DOM ids
mistaken for bindings (`pay_off_fee_id` is the element id; the binding is `fee_id`),
or hidden constants the API sets server-side (`user_type_id` on the investor form).

The real one was the **allocation screen**. Legacy offers a `user_ids` multi-select
that narrows which investors an allocation may consider, and it narrows the pool
*before* the split runs. The Vue port had no equivalent — only an "exclude" button
that dropped rows from a finished result. Those are not the same thing: every
strategy divides what is open among the investors it was given, so removing someone
afterwards leaves the advance short instead of redistributing their slice. Fixed
with `investor_ids` on the preview request and a `GET .../allocate/investors`
endpoint for the eligible pool, so the choice is offered before the first preview as
it is in the Blade. Verified: 11 eligible investors spread $12,036.18 across 10 rows;
narrowed to one investor, the same $12,036.18 is placed on that one row.

**Design: 3 of 23 screens ported, 20 reinterpreted.** This is the real finding. The
admin portal carries ~395KB of bespoke CSS across 21 stylesheets — Harbor for the
merchant view (189 classes), the investor portfolio (94), the ledger (82), and so
on. The dashboard and the Actum/API screens were ported verbatim; everything else
was rebuilt with Tailwind utilities, which reads as the same information in a
different design. Coverage now:

| ported | reinterpreted |
| --- | --- |
| `account-form` 100%, `actum` 95%, `portfolio-activity` 83% | the other 20, at 2–17% |

(The two verbatim ports fall short of 100% only because of dead rules in the
originals — `.pa-accfoot` and `.pa-xaxis` are defined and never used in the Blade
either.)

The pattern for closing this is set by `account-form`: extract the stylesheet
verbatim, then rewrite the Vue markup to the Blade's own structure and class names.
Done for the account form — the numbered sections, the leading input icons, the
password strength meter scored exactly as the Blade's `strength()` does, and the
sticky live-preview aside, none of which the Tailwind version had. Twenty screens
remain, `merchant-view` (189 classes) being the largest.

### Phase 6e — the registers get their design back

Six screens now at 93–100% of their legacy stylesheet, up from two.

**The three registers were one conversion, not three.** Accounts, investors and
merchants all render through `HbTable` / `HbHero` / `HbColumnPicker`, so converting
those components to the Blade's own markup — `.hb-sheet`, `.hb-searchrow`,
`.hb-grid`, `.hb-foot`, `.hb-panel`, `.hb-stats` — carried all three at once. Each
page then supplies its own cells (`.hb-idcell`, `.hb-av`, `.hb-chip`, `.hb-pill`,
`.hb-bar`) and imports its own sheet.

**Three near-identical sheets needed scoping.** The registers share 69 of 72 common
selectors byte-for-byte and differ in three (`.hb-grid` min-width, `.hb-stats`
columns, `.hb-pill` wrapping). Vite injects a chunk's CSS on load and never removes
it, so after visiting two registers whichever loaded last would win. `extract-styles.py`
now pins each to a screen class — `.harbor.hb-s-accounts` and friends — mechanically,
so the declarations stay verbatim.

**Two features the Vue had quietly dropped**, both restored into the shared
components so every register gets them:

- the *Filtered view · Clear all* strip, which is the only cue that a register is not
  showing everything — a filtered table and an empty one otherwise look alike;
- the sub-line under a statistic ("wallet balance across investors").

**And two the merchant register lacked**: the `.hb-ms` multi-select popover, now
`HbMsFilter` since the Blade uses two of them side by side, and the four-state status
tone. That last one was a data problem, not a markup one — the API sent `is_at_risk`,
a boolean, where the Blade distinguishes *paying / at risk / completed / other*.
`MerchantResource` now resolves `status_tone` server-side, which the pill and the
progress bar both colour by.

The account form was converted the same way: numbered sections, leading input icons,
the password meter scored exactly as the Blade's `strength()`, and the sticky
live-preview aside — none of which the Tailwind version had.

| at 100% | close | untouched |
| --- | --- | --- |
| account-form, account-list, investor-list, actum | merchant-list 99%, portfolio-activity 93% | 17 screens, 6–33% |

The two at 93–99% fall short only on classes the audit cannot see: dead rules in the
original, and tone values that arrive from the API rather than appearing as literals
in the template.

Next by size: `merchant-view` (189 classes), `investor-transaction` (102),
`investor-portfolio` (94), `merchant-form` (90).

### Phase 6f — two reported design faults, and a bug in the extractor

**The merchant register overflowed the page.** The ported markup carries Bootstrap
class names because it was copied verbatim, and the legacy pages loaded the whole
framework — the SPA does not. `.hb-grid` sets `min-width: 1240px` and leans on
`.table` for its width and `.table-responsive` for the scroll box, so without them
the table ran off the page instead of scrolling inside its container.
`resources/css/legacy/bootstrap-compat.css` now carries the six primitives the
ported markup actually uses, copied from the vendored `bootstrap.css`. It is a shim,
not a framework.

**The transactions register was wearing the wrong design entirely.** It is not a
`harbor` register — the Blade builds a command bar, a collapsible filter drawer,
applied-filter chips that lift one at a time, a segmented direction control, and an
inline delete confirmation *in the page* rather than a dialog, because walking a
wallet balance back should not be clickable-through. Rewritten to `hbt-*`, importing
both sheets the legacy page includes.

Three things had to follow it:

- The Blade filters by sets; the API only took single values. `company_ids`,
  `investor_ids` and `category_ids` added — the singular keys stay for the
  per-investor tab, which scopes to one wallet.
- `company` and `is_own` added to the resource. The register shows the owner's
  company under their name and refuses a delete tick on the operator's own wallet.
- That exposed a latent fault: the eager load selected `id,name,email`, so
  `User->Company` would have resolved to null and, once fixed, queried per row.
  Now `User:id,name,email,company_id` with `User.Company`.

**And a bug in `extract-styles.py` itself.** Several of these views explain, in
prose, why a `<style>` inside a Livewire component is a problem — and that prose
contains the literal string `<style>`, which the tag match started from. The
`investor-transaction` sheet therefore began with a sentence rather than a rule and
failed the build. Fixed: Blade comments are stripped first, `{{ url('...') }}` asset
expressions resolve to the path they were always going to produce, and each sheet is
now checked for balanced braces and Blade leftovers as well as for being identical to
its source — an extraction that captures stray text still matches itself, so identity
alone proved nothing. Scoping moved into the extractor too, so a re-run reproduces
the scoped sheets rather than needing a manual pass. 21 of 21 clean.

The design audit now discounts classes belonging to jQuery widgets the SPA does not
load — selectize is replaced by `HbMsFilter`, DataTables by `useTable()` — since
their styling hooks can never appear in a Vue file and counting them made every
screen look unfinished.

**7 of 23 screens ported.** Lender Payment is gone from the sidebar, leaving the
Laravel log as the only legacy entry.

### Phase 6g — the merchant view's ledgers and syndicate panel

The merchant view is not one screen with one stylesheet. `HarborStyle` (209 classes,
`hbi-`/`hbf-`/`hbp-`) dresses its tab *content*, and `LedgerStyle` (94 classes,
`hbl-`) dresses the two grids on it. Neither belongs to `MerchantShow.vue`, which is
only the shell — measuring them against it was why the screen read 11%.

**`HbLedger.vue`** is the ledger chrome: toolbar, totals strip, grid, and a footer
carrying the count, the page length and the pagination. Both the syndicate and the
payments tab render through it, the same way the three registers share `HbTable`.

One detail worth stating: its table carries `class="table dataTable"`. A third of
LedgerStyle is written against `table.dataTable` because DataTables generated that
class, and the plugin is not loaded — only the class name is kept, so the rules that
dress the rows still find them. `.dataTables_filter` and friends are vendor chrome
the SPA replaces outright, and the audit discounts them.

**The syndicate's assign panel** was a four-field strip; the Blade builds a band with
deal chips, an editing column of cards, a staged-participation table and a sticky
rail carrying the syndication bar, the over-allocation guard and the actions. Rebuilt
to `hbi-*`.

Two faults found while converting, both of the kind a build will not catch:

- `PaymentsTab` used `computed()` without importing it. Vue compiles the template
  lazily, so the build passed and the page would have thrown on mount. Every
  converted file is now scanned for Composition API calls missing from its `vue`
  import.
- `MerchantsIndex` kept a `selectedInvestors.value = []` in `resetFilters()` after
  the ref behind it was replaced — the same class of fault, caught the same way.

`merchant-view` 13% → 34%, `merchant-ledger` 19% → 57%, overall 38% → 44%.

### Phase 6h — the rest of the merchant view's tabs

Payoff letter, payment entry, ACH terms and the status trail.

**Payoff** (`hbf-`) is a charge builder, not a form: a fee/qty/amount strip, the
charge as rows rather than a four-column table — "four columns of figures do not
survive a phone, and a fee is really a name and an amount" — and three endings held
at the foot in the order they escalate: print it, keep it, collect it.

**Payment entry** (`hbp-`) gained the band, the Payment/Fee choice, the direction
radios, the live split preview as `hbi-ledger hbp-split` including the overpayment
row, and the sticky impact rail — where the advance lands once the entry is recorded.

**ACH terms** are schedule cards (`hbs-`), each with its own meter, its "n of m paid"
line and the count pills for whatever became of its debits. Its date pills now use
LedgerStyle's tones; Not Paid takes the base pill, since the Blade defines no `idle`
tone for a pill — only for a stat tile.

**The status trail** needed the API to catch up. The Blade derives a great deal in
PHP — the tone of each status, whether a move escalated or recovered, how long each
stretch was held, the actor's initials — and the endpoint returned none of it.
`BuildStatusTrailAction` ports that derivation whole: the advance's life as a chain
of stretches, newest first, with the funding of the deal closing the list.
`ListStatusChangesAction`, which it supersedes, is deleted.

One thing that fell out of it: the trail keys on `users.id`. `MerchantStatusLog.merchant_id`
is a FK to `users`, not to `merchants` — 38 of 38 distinct ids match `merchants.user_id`
— so the action reads `$merchant->user_id`, not the route key. Checked rather than
assumed, because 29 of those ids also happen to be valid `merchants.id` values and a
wrong key would have returned somebody else's history rather than an error.

While converting, the change-status form was briefly lost to an empty slot and
restored inline — in the panel rather than a dialog, because the trail above it is
the context for the decision and a modal would cover it.

`merchant-view` 43% → 55%, `merchant-status` 11% → 95%, `merchant-ledger` 57% → 71%.
Overall 45% → 49%, 8 of 23 screens ported.

### Phase 6i — the ACH run, pending investments, and the advance's detail cards

**The ACH run** (`hba-`, 9% → 91%) is a day being walked, not a table. It has its own
hero — the day in words, the four figures, the run's status — a stepper rather than a
bare date box, because "a day is missed by not noticing the date rather than by
mistyping it", and a send block that asks **in place**. That last one matters: the
Vue port had replaced the in-place confirmation with a modal, and the Blade is
explicit about why it is not one — "Send moves money out of the merchants' accounts
and cannot be taken back, so it asks once, in place, naming what it is about to
move." A dialog can be clicked through; the sentence naming the amount cannot.

**Pending investments** (70% → 84%) picked up the register vocabulary its shared
components already spoke. Its stylesheet is now scoped like the other three
`harbor` registers, since it is a fourth one. The remaining classes are the legacy's
bulk status-change modal, which this port replaces with an inline control.

**The advance's detail page was missing its main content.** The Blade opens with four
cards — twenty-two figures across funding, terms, collection and the syndicate — and
the Vue showed a hero and a tab bar with nothing between them. The endpoint could not
have fed them either: `merchants/{id}` returned the stored columns and none of the
roll-ups. `MerchantDetailFiguresAction` gathers them, and `MerchantShow` renders the
cards.

Two notes on that:

- `additional()` on the resource was silently dropped by the `ok()` envelope, which
  json-serialises the resource and loses anything hung off it. The figures are merged
  into the payload instead.
- Both one-sided figures — what the syndicate is still owed, and what it has been
  paid beyond that — are clamped at zero, as the Blade clamps them. A participation
  cannot be owed a negative amount, and the two would otherwise be each other's
  negative.

The cards need Bootstrap's `card`/`list-group`/`media`, which the SPA does not load,
so `cards-compat.css` carries them — with the template's own `.card` override, since
the shadow and margin come from app.min.css rather than Bootstrap. The four-column
row is a grid: importing Bootstrap's twelve-column scale for one layout would have
been a lot of stylesheet for one `row`.

Overall 49% → 53%, 9 of 23 screens.

### Phase 6j — the investor portfolio

11% → 86%. The screen was a generic hero over three register tabs; the Blade is a
portfolio.

**Its figures were not in the API at all.** `investors/{id}` returned the profile and
the user, and nothing else — no projected value, no wallet liquidity, no fee cascade.
Both already exist in `InvestorHelper` (`portofolioValues()` and
`portfolioAnalytics()`), and `ShowInvestorAction` was already calling the second for
the Blade, so the endpoint now returns both rather than a second definition of the
same arithmetic being written in JavaScript.

**The Overview tab did not exist.** It is what the page is opened for: the fee
cascade drawn as one collected dollar cut into the four parts it splits into — agent
fee, management fee, principal returned, profit — with a brace under the bar
bracketing the two fees against the net, since net is not a fifth slice but the last
two added together. Verified against live data: the four parts sum to the gross to
the cent.

The fee rates on that card are worked back **out of the money**, not read off the
investor record, and the note under each says so. The configured percentage is only
the default a new syndication starts from; a deal can be written at its own rate, so
across thirty-five advances the rate really applied is the blend. Management fee comes
off what is left after the agent fee, so that is its base.

The account file is on the page now too. Those terms — management fee, syndication
basis, payout frequency, agreement date — were reachable only behind the Edit pencil,
so the figures were shown without what produced them.

**The advances register** became the Blade's `hbp-` panel: command bar, filter drawer,
applied-filter chips, sortable headers, and an Outstanding column my version did not
have. Its statuses are shown as toggles rather than folded into a dropdown — there are
few enough to read at a glance, and which one is in force is the question being asked.

Two labels moved server-side so the screen does not hold a second copy of the option
lists: the investor type and payout frequency names, and the account's status.

Overall 53% → 58%.

### Phase 6k — the merchant form

25% → 52%. The same Harbor form vocabulary as the account form, at four times the
size: eight numbered sections, a sticky deal-summary rail, and a company-split table
that has to come to 100%.

Converted by transforming the repeated patterns rather than by hand — the label, the
`hb-in` wrapper, the message line, the section head and the field row are the same
five shapes twenty-six times over. Each field's **leading icon was read out of the
Blade**, not chosen: a store for the business name, a factory for the industry, a
padlock for the passwords, and no icon at all on the fields the Blade leaves bare.

The rail now states the deal as it will be written — RTR, instalment, term — plus the
fee cascade on one instalment, applied in the order `PaymentJob` applies it: the agent
fee off the payment, the management fee off what is left, the remainder to investors.
It is a preview; the real split is written per investor by the job.

Two things the conversion itself got wrong and the build caught:

- The error state was left on the control while the stylesheet expects it on the
  `hb-in` wrapper. Moved, with `class` keeping the field's own chrome and `:class`
  carrying only the error — Vue merges the two, so putting `hb-in` in both duplicated it.
- A replacement wrote `\'err\'` into eleven templates. Vue's compiler rejected it
  outright, which is the good case; a silently wrong class would not have been.

Still Tailwind on this screen: the money grid, the allocation bar, the commission
table and the choice toggles — the blocks that are not one of the five repeated
shapes.

### Phase 6l — the merchant form, matched section for section

52% → 96%. The earlier pass converted the repeated shapes; this one rebuilt the
template against the Blade, section by section, in its order and with its wording:
Business, Advance terms, Collection, Address, Underwriting, Security, Company split,
Commissions.

**Five fields were missing from the port entirely** — industry, state, source,
advance type and marketplace. Two of them (`industry_id`, `state_id`) were already
validated by the API, so the request would have accepted them; there was simply
nothing on the screen to send them. The other three needed rules adding. None of the
five had an option list on `merchants/options`, which is why they were dropped in the
first place: there was nothing to populate a select with. Industries (209), states
(51), sources, advance types and the yes/no picker now travel with the options.
Verified by round-tripping all five through a create and reading them back off the
row.

Also restored from the Blade: the read-only RTR field inside Advance terms (the
aside showed it, the field did not), the password strength meter, the percent suffix
on the commission rates, the ACH state pill in the rail, and the split's status line
— over, exactly right, or short, in the Blade's own words.

The status tone is a literal class per state rather than a template literal. A
`hb-tone-${key}` is invisible to a grep for where a class is used, which is the same
reason the audit could not see it.

Left as-is: the two extra commission row kinds (`hb-other` for fees an import put on
the deal under a name this form does not own, `hb-fee-err` for a row sitting on the
other basis) and the selectize widget classes the SPA replaces outright.

### Phase 6m — the investor form and the bank accounts tab

**Investor form: 30% → 100%.** Five sections in the Blade's order — Identity,
Contact, Syndication Terms, Payout, Security — with the payout frequency and
statement file type as tile pickers rather than dropdowns, the fee terms in a
three-column row, and the live preview aside carrying the two fee rates and the
payout: the terms every figure on that investor's screens is worked out from.

**Banks: 6%/11% → 94%/85%**, and it needed an endpoint. The list is a grid of
account cards — the two numbers laid out as they read off a voided check, the
debit/credit roles with a star on whichever is the default, and the ACH keys Actum
holds. The editor is two columns: the fields, and the account as it will be stored,
drawn as a check.

**The account number needed a reveal.** The Blade masks it on the card and shows it
only when an operator asks, "because this tab is open on a shared screen more often
than anyone edits it". The API had no way to ask — only last-4 came back — so
`GET .../banks/{bank}/reveal` was added: its own request, reading through the model's
decrypting cast, and refusing a bank id that belongs to another advance (verified:
404). The API service's own comment said the full number is never returned; that is
now stated as the rule with reveal named as the one exception, rather than left
contradicting the code beneath it.

Twelve of 23 screens, overall 70%.

### Phase 6n — the last Livewire dependency, and three id bugs

**Settings is ported, so nothing in the SPA reaches into Livewire any more.** It was
three components behind the topbar's wrench — general values, the ACH holiday
calendar, and the marketplace videos. Controller → FormRequest → Action, with
`SaveGeneralSettingsAction` writing the whole set in one transaction: `settings` is a
key/value table, and a maximum assignment saved without its matching minimum would
let the allocation screens propose something the other rule forbids.

The percentages are bounded 0–100 in the request, which they were not before: a share
over 100 would let one investor be assigned more of an advance than exists, and the
split has no way to refuse it afterwards. Verified — 150 is a 422.

These three Blades have no stylesheet of their own, so the screen reproduces their
plain Bootstrap cards rather than dressing them in Harbor, which they never wore.

**Three links pointed at the wrong record**, all the same fault: the Vue route binds
on `merchants.id`, but every `merchant_id` in this schema is a FK to `users.id`.

- The merchant name on the merchants list left the SPA for the legacy Blade page —
  `MerchantResource` still carried a `view_url` commented "still legacy Blade for
  now", which had stopped being true several phases earlier.
- The investor's advances register and the pending-investments list had the same
  link; pending's went to the merchants *list*, so every row led to one place.
- The payoff-letter PDF was handed the advance's key when its controller looks the
  merchant up by `user_id` — it would have printed a different merchant's letter,
  silently.

Both list queries now select the advance's own key alongside the user id, and
`view_url`/`merchant_url` are gone from the resources so this cannot drift back.
Verified: three different route keys each resolve to the advance their row names.

**Accounts now list newest first.** A deliberate departure from the Livewire default
of name-descending, which put Z first — the list is read to find what was just added.

---

## 14. Definition of done

- [ ] `app/Http/Livewire` and `resources/views/livewire` deleted; `livewire/livewire` removed from `composer.json`
- [ ] `resources/views` contains **only** `app.blade.php`, `emails/*` (4) and `pdf/*` (3)
- [ ] `grep -rn "self\(Create\|Update\|Delete\)" app` → no results
- [ ] No job dispatch and no `auth()` call inside any model hook
- [ ] Every write endpoint has a FormRequest; every response goes through a JsonResource
- [ ] Every model has a Policy and every controller action calls `authorize()`
- [ ] No `DB::raw` with interpolated variables anywhere in `app/`
- [ ] Bootstrap, jQuery, DataTables, select2, selectize, sweetalert2, air-datepicker, toastr all gone from the front end
- [ ] Money-math characterization tests green and unchanged since Phase 0
- [ ] Funding site passes a JS-disabled crawl with correct titles and meta

---

## 15. Risks

1. **Money-math regressions** — `PaymentJob` fee cascade, `rtr`/`share` derivations, liquidity.
   Mitigated by the Phase 0 characterization tests and the hard gate in §8.
2. **`auth()->user()` inside model hooks and jobs.** Token auth changes these contexts. Audit
   every `auth()` call in `app/Models`, `app/Jobs`, `app/Observers` before Phase 4.
3. **File downloads and Pusher auth silently depend on the session cookie.** Both need an
   explicit token path; neither fails loudly.
4. **Authorization is being introduced, not ported.** Adding policies where there were none will
   generate "this used to work" reports. Roll out in permissive-logging mode first.
5. **`ReportController` (1,555 lines) + `MerchantHelper` (821 lines)** are string-built SQL —
   the least-tested, most business-visible surface. Budget extra.
6. **Double maintenance window.** Livewire and Vue coexist across Phases 2–7; every bug fix must
   go to whichever side owns that page. Keep a cutover table.
7. **Scope expectation.** Harbor covers ~6 pages; the other ~80 admin pages get mechanical
   translation, not a redesign. Confirm that is understood before Phase 4.
8. **Funding SEO** is the least-charted work in this repo. Treat the crawl verification in §10
   as a release gate, not a checklist item.
9. **MongoDB (`mongodb/laravel-mongodb`) and the CRM integration** are outside this plan — verify
   neither depends on session state.

---

## 16. Remaining open questions

1. ~~Inertia vs token API~~ — **settled: token API + Vue, no Inertia.**
2. ~~Funding site: SPA or stay Blade?~~ — **settled: full Vue, SEO via prerendering (§10).**
3. Full Harbor redesign for all ~80 admin pages, or mechanical parity for non-Harbor pages?
4. Policy granularity — is `user_type_id` enough, or is per-company/multi-tenant scoping needed?
5. Cutover style — whole portal at once, or page-by-page behind a feature flag?
6. TypeScript or plain JS? (TS recommended, and Scramble can generate the client types.)
