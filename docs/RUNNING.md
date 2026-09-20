# Running the app & checking migration progress

## 1. Start it

Two terminals. The app runs the **legacy Livewire UI and the new Vue SPA side by side** —
same database, same login, different URLs.

```bash
# terminal 1 — Laravel
php artisan serve --port=8080        # http://127.0.0.1:8080

# terminal 2 — pick ONE of these:
npm run dev                  # hot reload while editing Vue (recommended for development)
# or
npm run build                # compile once; no second terminal needed afterwards
```

### Gotcha 1: port 8000 is taken by another project

`php artisan serve` defaults to 8000, but **`~/sites/construction/construction-erp` is already
listening there**. Laravel does not fail loudly enough about this — you just get a 404 on
`/app/login`, because you are talking to the other application, which has no `/app` route.

```bash
lsof -nP -iTCP:8000 -sTCP:LISTEN     # see who holds it
php artisan serve --port=8080        # or just use a different port
```

Use whatever port you start on consistently — `http://127.0.0.1:8080/app/login`.

### Gotcha 2: `public/hot`

`npm run dev` writes `public/hot`. Laravel's `@vite` sees that file and points every
`<script>` at the Vite dev server instead of the compiled bundle.

**If the dev server is not running and `public/hot` still exists, the SPA is a blank page** —
the script tags point at `localhost:5173`, which nothing is listening on. Ctrl-C removes the
file cleanly; a killed terminal or a crash leaves it behind.

```bash
rm -f public/hot && npm run build     # fixes a blank /app page
```

That is worth checking first any time the SPA looks broken. (This exact thing was found while
verifying the app: a stale `hot` file from 16:07 with no dev server behind it.)

## 2. Log in

Open **http://127.0.0.1:8080/app/login** and sign in with an existing admin —
`iocod@iocod.com` or `rahees@iocod.com`, your usual password.

The SPA uses a **Sanctum bearer token**, not the session cookie, so this login is separate
from the Livewire one. Logging into `/app` does not log you into `/Admin/...` and vice versa;
during the migration you may be signed into both at once.

## 3. What is live in Vue today

| URL | Screen | Replaces |
|---|---|---|
| `/app/admin/accounts` | Accounts register | `Livewire\Admin\Account\Table` |
| `/app/admin/accounts/create` | Create / edit account | `Admin/Account/create.blade` |
| `/app/admin/investors` | Investors register | `Livewire\Admin\Investor\Table` |
| `/app/admin/investors/create` | Create / edit investor | `Livewire\Admin\Investor\Create` |
| `/app/admin/investors/{id}` | Investor ledger — **Advances / Transactions / Liquidity Log** tabs | `AdvanceTable`, `Transaction\Table`, `LiquidityLog` |
| `/app/admin/transactions` | All-investor transaction register | `Transaction\ListTable` |
| `/app/admin/priority-pass` | Priority Pass roster + sandbox | `Livewire\Admin\Investor\PriorityPass` |
| `/app/admin/merchants` | Advances register | `Livewire\Admin\Merchant\Table` |
| `/app/admin/merchants/create` | Create / edit advance | `Livewire\Admin\Merchant\Create` |
| `/app/admin/merchants/{id}` | Advance detail — payment ledger + **Add Payment with live split preview** | `Merchant\Payments`, `Merchant\AddPayment` |

`{id}` for investors is a **users.id**; for merchants it is a **merchants.id**.

**The sidebar is the progress tracker.** A link that navigates inside the SPA is migrated. A
link with a small ↗ icon leaves the SPA for the legacy Livewire page — that is everything
still to do.

## 4. Check progress from the terminal

```bash
# money-math safety net — must stay green
vendor/bin/pest tests/Feature/Money

# whole suite (1 known pre-existing failure: ExampleTest asserts GET / is 200,
# but HomeController has middleware('auth') so a guest gets 302)
vendor/bin/pest

# how many API endpoints exist
php artisan route:list --path=api/v1

# the two numbers that measure the backend refactor
grep -rn "public function self\(Create\|Update\|Delete\)" app/Models | wc -l   # definitions left
find app/Http/Livewire -name '*.php' | wc -l                                   # Livewire components left
```

## 5. Where things stand

- **10 Vue screens** live, covering 15 of the 73 Livewire components.
- **52 API endpoints** under `/api/v1`.
- **Livewire components: 73 of 73 still present.** Nothing has been deleted — both stacks
  serve their screens, which is the intended strangler state. Retiring a legacy screen is a
  separate, deliberate step per module.
- `self*` model methods: **55 definitions / 70 call sites**, down from 60 / 77.
- Tests: 62 passing, 198 assertions.
