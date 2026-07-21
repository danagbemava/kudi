# Kudi — Merchant Wallet & Settlement Dashboard

A merchant wallet and settlement dashboard for a fintech platform: register merchants, simulate customer payments with a 1.5% processing fee, track wallet balances, and run daily settlements — built as a take-home assessment for Kudi Systems.

**Stack:** Vue 3 + TypeScript + Tailwind CSS v4 + Pinia (frontend) · Laravel 13 + SQLite + Sanctum (backend) · Pest + Vitest (tests) · Docker (one-command run)

## Running the application

### Option A — Docker (recommended for review)

```bash
docker compose up -d --build
```

Open **http://localhost:8080** and sign in with `admin@kudi.test` / `password`.
The first boot migrates and seeds ~30 days of realistic data automatically.

### Option B — Local (Laravel Herd / any PHP 8.3+, Node 20+)

```bash
# Backend
cd backend
composer install
cp .env.example .env && php artisan key:generate
touch database/database.sqlite
php artisan migrate --seed
php artisan serve            # http://localhost:8000

# Frontend (second terminal)
cd frontend
npm install
npm run dev                  # http://localhost:5173
```

Open **http://localhost:5173** — the Vite dev server proxies API calls to Laravel so session cookies stay first-party.

### Tests

```bash
cd backend && php artisan test    # 43 tests — business rules, API, auth
cd frontend && npm test           # 15 tests — money math, components, stores
cd frontend && npm run lint       # ESLint, incl. vue/no-undef-components
```

## What's included

- **Dashboard** — total merchant balances, payment volume, settlement totals, platform fees, a 30-day volume chart, and recent transactions.
- **Merchants** — searchable/filterable list, registration with validation (bank *or mobile money* settlement institution), activate/deactivate with confirmation, and a detail page with wallet, stats, transactions, and settlements.
- **Payments** — a simulator that shows the gross → 1.5% fee → net breakdown live as you type, creates a `pending` transaction, then resolves it ~2s later via a simulated gateway callback (~90% success). Successful payments credit the merchant wallet with the net amount.
- **Settlements** — outstanding-balance banner, a manually triggered settlement run with a confirmation showing the exact amount to be moved, and batch history. Views update without a page reload.
- **Reports** — platform fees earned, successful volume, settlement totals, and a transaction-outcome breakdown, with date-range presets and custom ranges.
- **Auth** — session login via Laravel Sanctum's SPA cookie flow; all API routes are guarded.

## Design decisions

- **Money is integers, end to end.** All amounts are stored and transported as integer minor units (pesewas). Only the frontend's `utils/money.ts` formats for humans. The 1.5% fee is computed with pure integer math, rounded **half-up** (`fee = ⌊(gross × 150 + 5000) / 10000⌋`) — the conventional commercial rounding, mirrored exactly in backend (`App\Support\Money`) and frontend fee preview, and pinned by tests on both sides.
- **Settlement decrements, never zeroes.** The settlement run locks wallet rows (`lockForUpdate`) inside one DB transaction, writes a settlement record per merchant, and decrements each balance by the settled amount rather than setting it to zero — a concurrent credit can never be silently destroyed. Each run shares one `BATCH-…` reference.
- **The invariant is tested.** `wallet.balance = Σ(successful net) − Σ(settled)` is asserted in the test suite, and the seeder satisfies it by replaying 30 days of activity chronologically with real settlement runs at historical timestamps.
- **Pending payments never touch wallets.** They credit only when processed; a settlement run between creation and processing simply leaves them for the next batch.
- **Race-safe processing.** The gateway callback endpoint re-checks the transaction status under a row lock inside the transaction, so two concurrent callbacks cannot double-credit a wallet.
- **A banks/mobile-money reference table** replaces the brief's free-text "bank name": merchants register against a seeded list of Ghanaian banks and mobile money providers (MTN MoMo, Telecel Cash, AT Money), which eliminates typos and mirrors real settlement rails. (Deviation from the brief's literal field list, chosen deliberately.)
- **SPA + API, same-origin cookies.** The Vite dev server and the Docker nginx both proxy `/api`, `/login`, `/logout`, and `/sanctum` to Laravel, so the Sanctum session cookie is always first-party — no cross-origin cookie fragility.
- **State management** is Pinia stores per domain (auth, dashboard, merchants, transactions, settlements) over a typed API client with a single error-normalization path: 422s map to field errors, everything else to toast-able messages.

## Assumptions

- **One wallet per merchant** (single currency, GHS). The brief consistently says "the merchant wallet"; wallets sit in their own table so multi-currency would be an additive change.
- **Inactive merchants still settle.** Deactivation blocks *new payments* only — money already owed is still paid out. The UI says this at deactivation time.
- **Currency is Ghanaian cedi**, stored as pesewas, formatted with `Intl.NumberFormat('en-GH')`. The currency lives in one config value (backend) and one util (frontend).
- **Single operator role.** The dashboard is an internal admin tool; there is no per-merchant login or row-level authorization. In production, account numbers would be masked in list views and access audited.
- **The payment gateway is simulated** — a weighted lottery (~90% success) resolves pending transactions when the frontend triggers the "callback" endpoint ~2s after creation.

## API summary

All routes are JSON, under `auth:sanctum` unless noted.

| Method | Path | Purpose |
|---|---|---|
| POST | `/login`, `/logout` | Session auth (guest / auth) |
| GET | `/api/user` | Current user |
| GET | `/api/dashboard` | Totals, 30-day volume series, recent transactions |
| GET | `/api/institutions` | Banks + mobile money providers for registration |
| GET/POST | `/api/merchants` | List (search, status, pagination) / register |
| GET | `/api/merchants/{id}` | Profile, wallet, stats, recent transactions and settlements |
| PATCH | `/api/merchants/{id}/status` | Activate / deactivate |
| GET | `/api/transactions` | List (search, status, merchant, date range, pagination) |
| POST | `/api/payments` | Create pending payment (fee precomputed) |
| POST | `/api/transactions/{id}/process` | Simulated gateway callback: resolve pending |
| GET | `/api/settlements` | List (date range, pagination) |
| POST | `/api/settlements/run` | Settle all positive balances into one batch |
| GET | `/api/reports/summary` | Fees, volume, settled totals, outcome counts by period |

## Tools used

Built with AI-assisted development (Claude Code), per the brief's recommendation. All business rules, architecture decisions, and code were reviewed and are explainable by the author.

## Known limitations

- `php artisan serve` (single PHP worker) backs the Docker image — fine for review traffic, not production; a real deployment would use FPM behind nginx.
- No per-merchant authentication, audit log, idempotency keys, or webhooks — out of scope per the brief.
- The transaction-outcome bar on Reports is proportional but not a full time-series chart; volume trends live on the Dashboard.
