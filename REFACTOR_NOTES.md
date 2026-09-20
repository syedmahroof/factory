# Best-Practices Refactor — Audit & Notes

Audited 2026-08-13 against `.claude/skills/laravel-best-practices` rules.
Convention: `app/Actions/SampleAction.php` + `ActionResult`. Branch: `refactor/best-practices`.

## P1 — Security / correctness

1. **No validation layer.** 0 FormRequests, 0 inline `->validate()` in controllers; only 7 of 64 Livewire components validate. Controllers feed `$request->all()` straight into models — 10 sites: `AccountController:119,184,213,248`, `MerchantController:207,387,562`, `InvestorController:95`, +2. (validation.md, security.md "Mass Assignment")
2. **String-interpolated raw SQL.** `DB::raw("... $var ...")` with PHP-built WHERE fragments: `Livewire/Admin/Report/Profitability.php:145-147`, `Report/ManagementFee.php:61`, `Helpers/MerchantHelper.php:309,318,319,363,364,524`. Each fragment must be audited/parameterized. (security.md "SQL Injection")
3. **No authorization layer.** 0 policies, 0 `authorize()`/`can()` calls. All Admin routes gated only by `middleware('auth')` — verify whether investors can reach Admin URLs. (security.md "Authorize Every Action")
4. **Exceptions swallowed & leaked.** 49 catch blocks, 64 `getMessage()` usages, **zero** `report()`/`Log::error` in app/Http. Failures lose stack traces; raw messages (incl. SQL errors) go to users. (error-handling.md, Action convention)

## P2 — Architecture

5. **Fat controllers.** `Admin/ReportController` 1555 lines, `Admin/MerchantController` 872 (DataTables config + inline JS strings + queries in one class). Total 4386 lines of controllers.
6. **Fat Livewire.** 6152 lines across 64 components; `Merchant/Terms` 403, `Merchant/Create` 377, `Merchant/AddPayment` 242 — payment scheduling / holiday math lives in components. Extract to Actions.
7. **God helper.** `Helpers/MerchantHelper.php` 821 lines building report SQL by string concat.
8. **Stored procedures in model accessors.** `Models/User.php:173-175` — 3 `CALL ...procedure` per accessor read; a table of users = 3N procedure calls.

## P3 — Hygiene

9. **`env()` outside config** (8 sites): `MMPHelper` (`MMP_URL`, `MMP_Access_Tocken` [sic], `MMP_Api_Key`), `APP_ENV` checks in `Livewire/Admin/{Investor,Merchant}/Create.php`. Breaks under `config:cache`. → move to `config/services.php`.
10. **Unguarded model:** `Models/LiquidityLog.php` (`$guarded = []`).
11. **Inline JS in PHP strings:** `MerchantController::list()` builds Swal/DataTables JS in heredocs.
12. **No tests.** Only the 3 example tests exist — refactor is behavior-preserving; verify by module smoke-testing.

## Deliberately NOT changed (behavior-affecting — needs sign-off)

- `User::getDropDownList()` `search_tag` filters on nonexistent `users.user_id` column → 500s whenever used. Probable fix: `name` LIKE. Left as-is.
- FormRequest migration for Account save deferred: current UX shows the first validation error via Toastr; FormRequests redirect with an `$errors` bag the blades don't render. Needs blade error display first.
- Unexpected DB errors on Account save/delete now show a generic message instead of the raw exception text (security fix; real error goes to the log via `report()`).
- `selfUpdate()` with an unknown id now returns `['result' => 'User not found.']` instead of the old `"Call to a member function update() on null"` string.
- Email uniqueness rule in `User::rules()` is commented out (pre-existing); left as-is.

## Changed routes (mapping only — URLs and names identical)

- `routes/Admin/AccountRoute.php` now points at renamed controller methods (`store`, `update`, `index`, `table`, `destroyMany`, `dropdown`).
- **Removed** dead route `GET Account/get/{id}` → `Account_get_ajax`: the method never existed (any hit 500'd) and nothing references `route('Account::get')`. Restore trivially if needed.

## Progress

- [x] Module 1: **Admin/Account** — `AccountController` 250→~260 lines but logic moved out:
  - `app/Actions/Account/{CreateAccountAction,UpdateAccountAction,DeleteAccountsAction}.php` own create/update/bulk-delete (validation, password hashing, transactions, `report()`).
  - `app/Exceptions/AccountException.php` = expected, user-safe failures.
  - `User::{selfCreate,selfUpdate,selfDelete,InvestorDelete,MerchantDelete}` are now thin `@deprecated` shims delegating to the Actions (6 other callers keep working; migrate them module-by-module, then delete the shims).
  - Controller input allow-listed via `only()` (was `$request->all()` ×4).
  - Verified: `php -l`, Pint passed, `route:list` resolves, tinker smoke test (validation msg + shim contract + delete-missing-id all match legacy).
- [x] Module 1b: **Account list page rebuilt without Yajra** (`/Admin/Account/list`):
  - New `App\Http\Livewire\Admin\Account\Table` component + view: server-side pagination, sortable headers (default name desc), debounced search (name/email/phone/company), user-type + company filters, page-length chooser, column show/hide, select-all/none, bulk delete via `DeleteAccountsAction` with confirm + Swal parity.
  - Perf: minimal `select`, eager-loaded `Company`/`UserType` → 5 queries / ~38ms per draw (was ~2 queries per row via lazy relations + `select *`). Indexes on `users.user_type_id` / `users.company_id` already existed.
  - `AccountController@index` now just returns the view; `@table` method deleted; `UserType::accountTypeIds()` is the shared home for the type list.
  - Yajra package untouched — every other module still uses it.
  - Behavior deltas (intentional): removed unused `POST Account/table` route; dropped the Action-menu "Transaction" link branch (its route `Account::Investor::Transaction::page` never existed and the branch was unreachable); replaced the header's boilerplate "Settings" dropdown (dead `#` links) with a Create Account button; company filter actually works now (old JS read `#company_ids`, an element this page never had); plain selects instead of selectize on this page (selectize hijacks the DOM, breaking `wire:model`).
- [x] Module 1c: **"Harbor" UI for Account list** (user-approved sample): navy stats panel (Total/Active/Deactivated/Companies/Lenders/Liquidity, cached 60s + busted on delete), white sheet with search + Show N + Columns + Export + Delete toolbar, User Type + Status filters, avatar/name+email cells, type chips, status pills, round view/edit row actions, compact density. New `status_id` filter + sort; `AccountsExport` (maatwebsite) drives Export of the filtered set. Per-page options now 10/25/50/100 (was 15/50/100/250/1000).
- [ ] Module 2: TBD (Admin/Investor or Security P1s)
