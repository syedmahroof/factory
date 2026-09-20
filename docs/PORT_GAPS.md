# What is still missing — Livewire → Vue port

**Audited:** 2026-08-25, against `~/sites/ip` (the Livewire app) at the same date.
**Branch:** `vue-implimentation`

Every claim below was checked against the working tree — route lists, grep for callers,
class-coverage checks against the ported stylesheets. Where something was *not* verified
it says so. Counts are measured, not estimated.

---

## 0. How to read this

| Mark | Meaning |
|---|---|
| **Missing** | The legacy has it; the Vue app has nothing equivalent. |
| **Partial** | Ported, but a named piece of it is absent. |
| **Deviation** | Deliberately different from the legacy. Listed so it is not mistaken for a gap. |
| **Defect** | Works, but wrong — found while porting. |

---

## 1. Whole portals not started — the largest gap

Two of the three portals the migration plan commits to are empty stubs:

| Portal | Legacy | Vue | Status |
|---|---|---|---|
| Investor portal | 3 components (`Investor/`, `Investor/Merchant/`) | `resources/js/router/investor.js` → `export default []` | **Missing** |
| Public Funding site | 6 components (`Funding/`) | `resources/js/router/funding.js` → `export default []` | **Missing** |

Both are marked "populated in Phase 7" in their own files. All 44 Vue pages currently live
under `admin/` and `auth/`.

Legacy Livewire components: **73**. Vue pages: **44**. The difference is mostly these two
portals plus the items in §2.

---

## 2. Admin screens with no Vue equivalent

| Legacy | What it does | Status |
|---|---|---|
| `Admin/Upload/Payment` | Bulk payment upload from Excel | **Missing** — no page, no API route (`grep upload routes/api/v1/admin.php` → nothing) |
| `Admin/Excel/Report/ExcelDownload` | Queued Excel report download | **Missing** — not verified whether the reports module covers it another way |
| `Admin/Merchant/LenderPayment` | Standalone screen at `/Admin/Account/Merchant/LenderPayment` — records a lender-side payment | **Missing** — separate screen, not part of the merchant view |

`Admin/Report` (4 components) maps to one `ReportsIndex.vue` driving 12 reports. **Not
spot-checked** report by report — worth its own pass.

---

## 3. Merchant view — ~~remaining gaps~~ CLOSED (2026-08-25)

All five items in this section are done. Kept for the record.

| # | Item | Resolution |
|---|---|---|
| 3.1 | Audit tab had only the timeline, not the pivoted **Audit report** | **Done.** New `GET {merchant}/audit/report` endpoint ports the pivot (columns decided by the result set, ordered by the models' fillable order, 2,000-row scan cap with a truncation notice, in-PHP search over printed values). `AuditTab.vue` is now a sub-tab shell over `AuditTrail.vue` + `AuditReport.vue` |
| 3.2 | Auto-allocate "by payments received" was not gated | **Done.** `options` now returns `allows_payment_allocation` (`label_id === Label::Insurance`); `AllocateTab` filters the method out and refuses to open on it. Verified both ways: deal 27 (insurance) `true`, deals 24/44 `false` |
| 3.3 | `CompaniesTab.vue` orphaned | **Done.** Deleted — the company strip above the tabs covers it |
| 3.4 | Ledger had no busy indicator | **Done.** `HbLedger` renders `.hbl-busy` while `table.loading`, so the rule is no longer orphaned |
| 3.5 | `hbst-move-form` had no rule anywhere | **Done.** It was a typo for `.hbst-move`, which the stylesheet does define |

### Found while fixing 3.1 — **Defect, fixed**

`MerchantAuditController` type-hinted `OwenIt\Auditing\Models\Audit`, but the trail is
`App\Models\Audit` (a plain Eloquent model that does **not** extend the package's). Every
call would `TypeError` → 500. **The change trail was broken for any merchant that actually
had audit rows** — my earlier smoke test had passed only because it hit a merchant with an
empty trail. Both methods now hint `App\Models\Audit`; verified against merchant 24
(5 audits): timeline 5 rows, report 5 rows across 7 pivoted columns.

## 4. Deliberate deviations — not gaps

Listed so nobody "fixes" them back.

| Area | Legacy | Vue | Why |
|---|---|---|---|
| Story / FAQ / Security | **Three separate tabs**, and the tab links are hard-disabled (`@if ($Merchant->marketplace == 'Yes' && false)`) | One "Securities, FAQs & Story" tab, always visible | Legacy tabs are unreachable; merged rather than replicate three dead tabs |
| Liquidity Log tab | `@if (env('APP_ENV') == 'local')` — dev-only | Always visible | Deliberate, but **confirm this is wanted in production** |
| Auto-allocate, payoff letters | Modals from the investors toolbar / More menu | Same, plus kept as dialogs | Matches |
| `merchant-ledger.css` | Verbatim copy of `LedgerStyle.blade.php` | Same values, DataTables hooks renamed to `hbl-` | Yajra removed; the file can no longer be regenerated from the Blade — noted in its header |
| Phone numbers | Stored bare (`RemovePhoneNoStrings`) | Stored formatted | Pre-existing in `InvestorForm`/`AccountForm`; matched rather than changed |
| Chain tag wrap | `.ctag` is a fixed `66px`, so "INVESTMENT" wraps | Identical | Faithful to the Blade. Fix in the SPA guard if the wrap is unwanted |

---

## 5. Defects found while porting

| # | Defect | Status |
|---|---|---|
| 1 | `SendDueDebitsAction` rolled back the `actum_requests` row for a gateway call it had just made — destroying the Indeterminate record that prevents a double-debit, and freezing the idempotence key at `-0` | **Fixed**, pinned by `tests/Feature/Ach/SendDueDebitsTest.php` |
| 2 | `merchants.marketplace` is `NOT NULL`; a blank form field arrived as `null` and broke every advance save | **Fixed** in `SaveMerchantRequest::merchantData()` |
| 3 | Tailwind generates a utility for any token it scans — `class="feecell fixed"` became `position: fixed` and stacked fee cells on top of each other | **Fixed** via the SPA guard in `merchant-view.css` |
| 4 | `merchant-view.css` was a generation behind the Blade — 87 selectors missing, leaving the fee bands and cost/return chains unstyled | **Fixed**; re-extract with `docs/extract-harbor-style.py` |
| 5 | Bank toggles rendered `<span class="hbl-sw"><i/></span>` — no input, no `.track`, so no switch was drawn | **Fixed** |
| 6 | Merchant form had no phone mask; edit mode showed ten bare digits | **Fixed** |
| 7 | ACH schedule builder opened pre-filled (`30`, `0`), so typing `12` into the count produced `3012` | **Fixed** — re-verified live 2026-08-25: opens blank, typing `12` gives `12` |
| 8 | **`MerchantFactory` writes a `source_id` column `merchants` no longer has** — any `Merchant::factory()->create()` throws | **Open** — worked around by hand-building fixtures in the new test |
| 9 | The US phone mask now exists **three times**: `utils/phone.js`, `InvestorForm.vue`, `AccountForm.vue` | **Open** — point the latter two at the util |
| 10 | Legacy `default_debit` is locked on the merchant bank form; the Vue panel lets you toggle it | **Open** — behavioural, deliberately not changed |
| 11 | **The `max_assign_percentage` setting was ignored.** The allocate dialog hardcoded a 100% cap on both client and API, so allocations were proposed against the whole wallet on a portal configured for 20% | **Fixed** — `allocate/investors` now returns the configured value and the preview falls back to it |
| 12 | Priority Pass opened a new pass on an invented `max_percentage: 5`; the legacy resets to `0` | **Fixed** |

---

## 6. Verification gaps

- **119 tests, 837 assertions** pass. The audit report endpoint added in §3.1 has none. Coverage is thin relative to what was added this pass:
  the merchant investor/payment/term/ACH-fee registers, the table-preference endpoint, the
  exports and the bulk deletes have **no tests**. Only the ACH send path is pinned.
- No visual regression harness. The ledger restyle was verified by rendering before/after
  and comparing PNG hashes by hand — worth making repeatable.
- The **pre-fill sweep is now done** for typed numeric fields across the admin forms
  (§5.11, §5.12 came out of it). `ach_flag` is a radio and `max_share_percentage` a
  meaningful cap, so neither needed blanking. Selects and date fields were not in scope.

---

## 7. Suggested order

1. **Investor portal + Funding site** (§1) — the only items that are whole products.
2. `MerchantFactory` (§5.8) — it blocks writing tests for everything else.
3. Payment Excel upload (§2).
4. Phone-mask dedupe (§5.9), `default_debit` lock (§5.10).

~~Audit report sub-tab~~ and the merchant-view small items are done — see §3.
