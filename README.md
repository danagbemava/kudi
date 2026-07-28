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

> **If you run Vite on a different port**, add it to `SANCTUM_STATEFUL_DOMAINS` in `backend/.env`.
> Sanctum decides whether to use the session cookie by matching the request's origin against that
> list *including the port*. Off the list, the login itself succeeds and every request after it
> comes back 401 — which the SPA correctly reports as "your session timed out". The message is
> true and the diagnosis is wrong, so it is worth knowing before it costs you ten minutes.

### Tests

```bash
cd backend && php artisan test    # 128 tests — business rules, API, auth, validation, reporting
cd frontend && npm test           # 75 tests — money math, components, stores, nav, session
cd frontend && npm run build      # vue-tsc -b + vite build — the production bundle
cd frontend && npm run lint       # ESLint, incl. vue/no-undef-components
cd frontend && npm run format:check   # Prettier, incl. Tailwind class order
cd backend  && composer format:check  # Laravel Pint
```

## Screens

| | |
|---|---|
| ![Dashboard](docs/screenshots/dashboard.png) | ![Merchant detail](docs/screenshots/merchant-detail.png) |
| **Dashboard** — balances, volume, settlements, fees, 30-day chart | **Merchant detail** — wallet, stats, transactions and settlements |
| ![Payment simulator](docs/screenshots/payment-simulator.png) | ![Mobile](docs/screenshots/transactions-mobile.png) |
| **Payment simulator** — live gross → 1.5% fee → net breakdown | **Mobile** — ledgers become cards, filters collapse behind a toggle |

*The 1.5% above is the default rate, which is what these screenshots and the recording were captured
at. It is configuration (`KUDI_FEE_BASIS_POINTS`) rather than a constant — the client reads it from
`/api/config`, so a deployment on a different rate shows that rate everywhere, not this one.*

> **[Watch the walkthrough](docs/demo.mp4)** (7 min) — registering a merchant, simulating a payment
> through to the gateway result, running a settlement, and reading the reports.

## Design

> The route here — six rounds of prototypes and two reversals — is recorded in the
> **[design log](docs/design-log.md)**, and every specimen is embedded in
> **[`docs/prototypes.html`](docs/prototypes.html)** (open it in a browser).

The interface is built around the settlement cycle rather than around a grid of statistics: float accumulates in merchant wallets, a run sweeps it out, and the balance resets. That rhythm is the operator's whole job, so it leads.

- **The settlement band** is the one bold element. The float waiting to be settled is drawn as proportional strips, one per merchant, so you can see *whose* money is sitting there — composition a total can never show. Hovering or focusing a strip names the merchant, its balance and its share; each strip links to that merchant. Past the first eight the tail is folded into one “N other merchants” band, because thinner strips are impossible to aim at and each would add a tab stop for nothing. Everything around it is kept quiet.
- **Colour.** The palette comes from Kudi's own mark: violet `#12013D` and the red square of the tittle on the *i*. It is deliberately rationed. The mark's exact violet is kept for the wordmark and the primary action, where it fills an area and the area carries the hue; a lighter `#4C1D95` (10.9:1 on white) carries it in *text* — the headline figure, links, focus rings. Measured on the rendered page, `#12013D` against `#1A1A1F` ink is indistinguishable in the strokes of a glyph, so the accent had to open up or the rationing rule signalled nothing at all. At full strength across the furniture it reads institutional rather than clean, which is the conclusion of six rounds of prototypes recorded in the [design log](docs/design-log.md). Status colour is semantic and kept separate from the brand accent, so a violet button can never be mistaken for a success state.
- **Type.** Geist for the interface and Geist Mono for machine identifiers — references and batch numbers. Money columns are right-aligned with tabular figures: a monospaced face alone does not align them, because `GHS 74.30` and `GHS 3,015.32` are different lengths and left-aligned their decimal points sit two characters apart.
- **Currency** displays as the ISO code (`GHS 1,250.75`) rather than the ₵ symbol, which renders inconsistently across faces at display sizes. Money is the entire content of this product, so it has to be unambiguous.

## What's included

- **Dashboard** — total merchant balances, payment volume, settlement totals, platform fees, a 30-day volume chart, and recent transactions.
- **Merchants** — searchable/filterable list, registration with validation (bank *or mobile money* settlement institution), activate/deactivate with confirmation individually or in bulk, and a detail page with wallet, stats, transactions, and settlements.
- **Payments** — a simulator that shows the gross → fee → net breakdown live as you type, creates a `pending` transaction, then resolves it ~2s later via a simulated gateway callback (~90% success). Successful payments credit the merchant wallet with the net amount.
- **Transactions** — sortable ledger with search, status, merchant and date filters. Any row opens a detail panel whose reference lives in the URL (`?tx=PAY-…`), so a specific payment can be linked to or reloaded and the back button closes the panel. A pending row carries its own **Check status** action: a payment otherwise only resolves while the tab that created it stays open, so anything left stranded — including transactions already pending when the session started — can be pushed through by hand.
- **Settlements** — outstanding-balance banner, a manually triggered settlement run with a confirmation showing the exact amount to be moved, and history grouped into the runs that produced it: one row per sweep with its date, reference, merchant count and total, expanding to the merchants it paid. The totals are aggregated in SQL, so a batch that straddles a page boundary still reports the whole run. The dashboard also shows when the last run happened and what it moved, so the operator can tell whether one is due. Views update without a page reload.
- **Reports** — platform fees earned, successful volume, settlement totals, a fees trend, and a transaction-outcome breakdown, with date-range presets and custom ranges. The chart states the window it covers, because that window is not always the one the totals cover: a span beyond 180 days is trimmed to the most recent slice and says so.
- **Feedback** — actions confirm through toasts with an optional short chime, distinct per outcome. There is no `prefers-reduced-sound` media query to read, so the only way to respect someone working in a quiet room is a control that remembers the answer: a mute toggle in the header, persisted to `localStorage`.
- **Auth** — session login via Laravel Sanctum's SPA cookie flow; all API routes are guarded. Failed sign-ins are rate-limited to five per minute per email+IP and answered with 429; a success clears the tally, so a mistyped password does not cost the rest of the minute. Sessions last `SESSION_LIFETIME` minutes (120 by default). When one lapses, the first 401 signs the user out, sends them to the login page with the page they were on preserved, and says why — rather than leaving them on a screen where every action silently fails. Returning to a backgrounded tab re-checks the session, so an expiry is caught on return rather than on the next click.

## Design decisions

> The headline domain choices are below. The full engineering decision record — architecture, frontend, verification, deliberate non-goals, and the decisions that were reversed — is in **[docs/decisions.md](docs/decisions.md)**.


- **Money is integers, end to end.** All amounts are stored and transported as integer minor units (pesewas). Only the frontend's `utils/money.ts` formats for humans. The fee is computed with pure integer math, rounded **half-up** (`fee = ⌊(gross × bp + 5000) / 10000⌋`) — the conventional commercial rounding, implemented identically in `App\Support\Money` and the frontend fee preview and pinned by tests on both sides. The rate itself has exactly one home: `KUDI_FEE_BASIS_POINTS`, served to the client from `/api/config`, so changing it can never leave the simulator quoting a rate the backend does not charge.
- **Settlement decrements, never zeroes.** The settlement run locks wallet rows (`lockForUpdate`) inside one DB transaction, writes a settlement record per merchant, and decrements each balance by the settled amount rather than setting it to zero. Each run shares one `BATCH-…` reference.
- **The invariant is tested.** `wallet.balance = Σ(successful net) − Σ(settled)` is asserted in the test suite, and the seeder satisfies it by replaying 30 days of activity chronologically with real settlement runs at historical timestamps.
- **Pending payments never touch wallets.** They credit only when processed; a settlement run between creation and processing simply leaves them for the next batch.
- **Race-safe processing.** The gateway callback endpoint re-checks the transaction status under a row lock inside the transaction, so two concurrent callbacks cannot double-credit a wallet.
- **A banks/mobile-money reference table** replaces the brief's free-text "bank name": merchants register against a seeded list of Ghanaian banks and mobile money providers (MTN MoMo, Telecel Cash, AT Money), which eliminates typos and mirrors real settlement rails. (Deviation from the brief's literal field list, chosen deliberately.)
- **SPA + API, same-origin cookies.** The Vite dev server and the Docker nginx both proxy `/api` and `/sanctum` to Laravel (auth lives at `/api/login`, so the SPA keeps the `/login` page), so the Sanctum session cookie is always first-party — no cross-origin cookie fragility.
- **Where logic lives.** Services own anything that carries an invariant — the fee, wallet crediting, the locked settlement sweep (`PaymentService`, `SettlementService`) — and read-side aggregation that is more than a query (`ReportingService`: day bucketing, zero-filling, float composition). List filtering lives in Eloquent scopes (`Merchant::search`, `Transaction::createdBetween`) which no-op on null, so controllers chain them unconditionally and stay a few lines each. Controllers only translate HTTP to those calls.
- **State management** is Pinia stores per domain (auth, dashboard, merchants, transactions, settlements) over a typed API client with a single error-normalization path: 422s map to field errors, everything else to toast-able messages.

## Accessibility

Targeting WCAG 2.1 AA. Audited by hand across contrast, keyboard operability, forms, ARIA, landmarks, and SPA-specific concerns.

- **Contrast** — measured programmatically, not eyeballed: an audit walks the rendered DOM on every page, composites each element's real background, and compares. It resolves colours by painting them to a canvas because Tailwind v4 emits `oklab()` for opacity-modified values, which a regex parser skips silently. Latest run: 1,052 elements, 0 unparseable, 0 failures across every screen at 1920/1440/390. Disabled controls are exempt per SC 1.4.3 but still kept legible.
- **Keyboard** — skip-to-content link, visible focus rings on every interactive element, focus trapped in dialogs and restored to the trigger on close. The mobile nav drawer is a real dialog: Escape closes it, focus moves in and returns.
- **Forms** — every control has a bound `<label>`; server validation maps to per-field messages tied via `aria-describedby` with `aria-invalid`, and errors announce through `role="alert"`.
- **Status messages** — route changes, result counts after filtering, and load failures all announce through live regions; SPA navigation and in-place table swaps are otherwise silent. The payment fee breakdown announces once, ~700ms after typing stops, rather than interrupting on every keystroke of the amount. Error toasts never auto-dismiss, and hovering or focusing a toast pauses its timer (SC 2.2.1).
- **Non-text content** — the volume chart carries a text summary naming the total and busiest day, because a `<canvas>` exposes nothing to assistive tech; every decorative icon is `aria-hidden`; status is never conveyed by colour alone (badges print the status word).
- **Motion** — `prefers-reduced-motion` disables animation and transitions globally.

Two findings worth calling out, because they are invisible in a static reading of the markup:

- `BaseModal` listens for keys on `window`, not on the dialog element. A submit button that disables itself while loading makes browsers reset focus to `<body>`, at which point an element-bound handler stops receiving events — silently disabling both Escape and the focus trap. There is a regression test for this.
- Form-control borders use a dedicated `--color-field` token at 3.4:1 rather than the decorative `--color-line` (1.23:1). SC 1.4.11 governs the boundary that identifies a control, and a near-white border on a near-white page fails it; card edges and dividers are decorative and correctly stay light.

Known gaps: no automated axe/Lighthouse run (the next step, ideally in CI), and the mobile drawer relies on `inert`, which needs a polyfill below Safari 15.5.

## Assumptions

- **One wallet per merchant.** The brief consistently says "the merchant wallet"; wallets sit in their own table, so a multi-currency model would be an additive change rather than a rewrite.
- **Inactive merchants still settle.** Deactivation blocks *new payments* only — money already owed is still paid out. The UI says this at deactivation time.
- **Single operator role.** The dashboard is an internal admin tool; there is no per-merchant login or row-level authorization. Account numbers are masked to their last four digits and revealed by an explicit click; in production, that reveal would also be audited.
- **The payment gateway is simulated** — a weighted lottery (~90% success) resolves pending transactions when the frontend triggers the "callback" endpoint ~2s after creation.

## API summary

All routes are JSON, under `auth:sanctum` unless noted.

| Method | Path | Purpose |
|---|---|---|
| POST | `/api/login` | Session auth. Rate-limited; answers an already-signed-in caller in JSON |
| POST | `/api/logout` | Ends the session (`auth`) |
| GET | `/api/user` | Current user |
| GET | `/api/config` | Fee basis points and currency — the client mirrors these, never hardcodes them |
| GET | `/api/dashboard` | Totals, 30-day volume series, float composition, recent transactions |
| GET | `/api/institutions` | Banks + mobile money providers for registration |
| GET/POST | `/api/merchants` | List (search, status, sort, pagination) / register |
| GET | `/api/merchants/{id}` | Profile, wallet, stats, recent transactions and settlements |
| PATCH | `/api/merchants/{id}/status` | Activate / deactivate |
| PATCH | `/api/merchants/status` | Activate / deactivate up to 200 at once |
| GET | `/api/transactions` | List (search, status, merchant, date range, sort, pagination) |
| GET | `/api/transactions/{reference}` | One transaction, bound on reference so a shared link keeps working |
| POST | `/api/payments` | Create pending payment (fee precomputed) |
| POST | `/api/transactions/{id}/process` | Simulated gateway callback: resolve pending |
| GET | `/api/settlements` | List (date range, batch, sort, pagination) |
| GET | `/api/settlements/batches` | History grouped into runs, with each run's own total |
| POST | `/api/settlements/run` | Settle all positive balances into one batch |
| GET | `/api/reports/summary` | Fees, volume, settled totals, outcome counts, daily series + the window it covers |

## Tools used / Development Process

Built with AI-assisted development (Claude Code). Development process involved coming up with a spec for the task in claude code to make some decisions. These include (but are not limited to), Api Design/UI Design (of which the history/thought process can be found in the [docs/decisions.md](docs/decisions.md) & [docs/design-log.md](docs/design-log.md)).

After the spec was generated, claude iteratively moved through the implementation with me as the HITL (Human In The Loop). Claude was used for an accessibility/responsiveness pass using the `playwright-mcp` but there were several rounds of reviews on my part as the HITL to see if the vision I wanted was being brought to life. 

## Known limitations

- `php artisan serve` (single PHP worker) backs the Docker image — fine for review traffic, not production; a real deployment would use FPM behind nginx.
- **Authorization is single-role by design.** This is a platform-operator back office: there is one signed-in identity, and it is entitled to every merchant and every transaction. `users` carries no tenant key and merchants are records rather than login principals, so no endpoint scopes by owner — the authorization boundary is authentication itself, and every route sits behind `auth:sanctum`. If merchants were ever given logins, scoping would have to be added to *every* list and detail endpoint, not just one; that is the change to plan for, not a patch to any single route.
- No audit log, idempotency keys, or webhooks — out of scope per the brief.
- The transaction-outcome bar on Reports is proportional but not a full time-series chart; volume trends live on the Dashboard.
- Series are capped at 180 points. A longer range still reports correct totals and the chart says it was trimmed, but there is no downsampling to show the whole span at lower resolution.
- The settlement band names the 40 largest float holders and folds the rest into one band. The strips always sum to the whole float, but past 40 merchants you cannot see who is in the tail without opening the merchants list.
