# AI Context Prompt — Investor Portal v3

Copy the block below into any AI/prompt tool (ChatGPT, Claude, Cursor rules, etc.) when you need it to understand this project. Claude Code does **not** need this file — it auto-loads `CLAUDE.md`; deeper detail lives in `docs/PROJECT_OVERVIEW.md`.

---

```text
You are working on Investor Portal v3, a Laravel application for a Merchant Cash
Advance (MCA) syndication business.

WHAT THE BUSINESS DOES
A funder advances cash to a small business (the "merchant") in exchange for a
fixed repayment total called RTR (right to receive) = funded × factor_rate.
Multiple investors syndicate ("participate") in each deal, each taking a
percentage share. The merchant repays in fixed installments (mostly daily/weekly
ACH debits). Every repayment must be split across the investors by share, minus
an agent fee and a management fee, and attributed to principal first, then profit.

WHAT THE APP IS FOR
It is the single system of record for that lifecycle:
1. MERCHANT CREATED AFTER FUNDING — a Merchant row is only created once a deal is
   funded (funded_date and funded amount are required; there is no underwriting
   pipeline). status_id defaults to ActiveAdvance and moves through Default,
   Collections, AdvanceCompleted, Settled, Cancelled, etc.
2. INVESTMENT SPLIT — a participation is a merchant_investors row with
   share = funded / merchant.funded × 100, rtr = funded × factor_rate, and
   invested = funded + investment fees. Participations come from admin
   assignment, auto-allocation (by liquidity or payment), or investor
   self-service on the marketplace (created as Pending, approved by admin).
3. MERCHANT PAYMENT SPLIT — each payment becomes a merchant_payments row plus
   one merchant_payment_investors row per investor:
     amount         = payment × share / 100   (capped at investor's remaining balance;
                      excess redistributes, leftover goes to an OverPayment pseudo-investor)
     agent_fee      = amount × payment.agent_fee_percentage / 100
     management_fee = amount × investor.management_fee_percentage / 100
     net_amount     = amount − agent_fee − management_fee
     net_amount repays invested first (principal), the remainder is profit.
4. ACH COLLECTION — merchants with ach_flag active get a payment schedule
   (merchant_payment_terms → merchant_payment_term_dates) generated
   daily/weekly/biweekly/monthly, skipping weekends and holidays. Debits are sent
   through the MMP/Sila HTTP gateway (m_m_p_requests, statuses T100 in process /
   T101 completed / T102 partial / T103 failed). A completed debit automatically
   marks the term date Paid and creates the real MerchantPayment, triggering the
   split. rcodes holds NACHA return codes; merchant_ach_fees bills incident fees
   (NSF, rejection, etc.). No NACHA files — API only.

CRITICAL SCHEMA FACTS
- users.id is the universal actor key. Merchants, investors, companies, and
  lenders are all users rows differentiated by user_type_id (Admin=1, Investor=2,
  Lender=4, Company=6, Merchant=7). Nearly every merchant_id / investor_id /
  company_id column references users.id, NOT merchants.id.
- All financial roll-up columns are owned by queued jobs, never written by hand:
  Bus::batch([PaymentJob, MerchantUpdateJob, InvestorUpdateJob]) after payments;
  InvestmentUpdateJob after investments; LiquidityUpdateJob for users.liquidity.
- users.liquidity (investor wallet) = sum of investor_transactions.amount +
  sum of (paid_net_amount − invested); every change is logged in liquidity_logs.
- Money columns are high-precision decimals (16,8; shares 16,12). The column
  merchant_payment_term_dates.recieved is intentionally misspelled.

STACK & CONVENTIONS
- Laravel 12 (legacy Kernel-based skeleton), PHP 8.4, Livewire 3, Blade + jQuery +
  Bootstrap 5, Yajra DataTables (being replaced per-module by Livewire tables),
  Horizon + Redis queues, Pusher broadcasts, maatwebsite/excel, dompdf,
  owen-it/laravel-auditing. Tests: PHPUnit (nearly none exist).
- Refactor in progress (branch refactor/best-practices, see REFACTOR_NOTES.md):
  new business logic goes in app/Actions/<Module>/<Name>Action.php returning
  ActionResult. Legacy model selfCreate()/selfUpdate()/selfDelete() methods that
  return ['result' => 'success'|error-string] are deprecated — do not add callers.
- Known gaps to respect and improve when touching code: missing validation
  (add FormRequests / allow-listed input), missing authorization (routes only use
  middleware('auth')), string-interpolated raw SQL in report helpers.
- Three route trees: routes/Admin/*, routes/Investor/*, routes/Funding/* (public
  marketplace). Merchants have no portal of their own.

KEY FILES
Payment split: app/Jobs/Merchant/PaymentJob.php
Roll-ups: app/Jobs/Merchant/{MerchantUpdateJob,InvestorUpdateJob,InvestmentUpdateJob}.php
Share/RTR math: app/Models/MerchantInvestor.php, app/Models/Merchant.php (boot methods)
ACH bridge: app/Models/MMPRequest.php (boot::saving), app/Helpers/MMPHelper.php
Schedule generation: app/Http/Livewire/Admin/Merchant/Terms.php
Marketplace query: app/Helpers/FundingHelper.php
Full documentation: docs/PROJECT_OVERVIEW.md

When making changes: preserve the job-owned write path (dispatch jobs instead of
writing roll-up columns), keep decimal precision, follow the Actions convention,
and run vendor/bin/pint before finishing.
```
