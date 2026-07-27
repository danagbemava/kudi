# Kudi — holistic review

**Reviewed:** 27 July 2026 · worktree `.claude/worktrees/build` (branch `worktree-build`)
**Scope:** backend (Laravel 13.8 / SQLite / Sanctum), frontend (Vue 3.5 / TS / Tailwind 4.3 / Pinia), UI reviewed live in a browser at 1440×900 and 390×844.

---

## 0. Status — every finding below has been fixed

The review was written first and the fixes applied after, so the sections that
follow describe the code **as it was found**. Each one is now resolved; this
table is the index.

| § | Finding | Fix |
|---|---|---|
| 2.1 | Unvalidated dates → 500 | `DateRangeRequest` + per-endpoint index requests; `nullable\|date` on every `from`/`to` |
| 2.2 | Reversed range → backwards series | `after_or_equal:from` at the edge; the service also orders its own ends |
| 2.3 | No login rate limiting | 5 failures/min per email+IP → 429 with `Retry-After`; a success clears the tally |
| 2.4 | Seeder not idempotent | Admin user guarded; the activity replay declines to duplicate itself |
| 3.1 | Chart/totals period mismatch | Server returns `series_window`; the chart is labelled and says when it was trimmed. "All time" now charts from the first payment |
| 3.2 | Brand violet invisible at text sizes | New `--color-accent` `#4C1D95` (10.9:1) for figures, links and focus rings; `#12013D` kept for the wordmark and primary button |
| 3.3 | Money columns left-aligned | `align: 'right'` + `tabular-nums` on every money column in all three tables |
| 3.4 | Settlement history not grouped | `/api/settlements/batches` aggregates in SQL; history is one row per run, expanding to its merchants |
| 3.5 | Outcome bar linked only by colour | Bar now runs in legend order, so position identifies the segment |
| 3.6 | Frontend fee rate hardcoded | `/api/config` serves the rate; `feeFor` reads it, labels derive from it |
| 4.1 | Silent scale ceilings | Float returns `holder_count`/`remainder_*`; the band's strips still sum to 100% and the count is the real one |
| 4.2 | Unauthenticated non-JSON → 500 | `redirectGuestsTo(fn () => null)` |
| 4.3 | Login-while-authed → 302 | Answered in JSON by the controller |
| 4.4 | Wrong Vite port reads as "session timed out" | Documented in the README's local-setup section |
| 4.5 | Out-of-order responses | `createLatestGuard()` in every store |
| 4.5 | Inconsistent `ensureCsrf` | Moved into a request interceptor, so it cannot drift |
| 4.5 | `whereDate` forfeits indexes | Half-open ranges on the raw column |
| 4.5 | `toISOString()` for local dates | `toLocalDate()` helper |
| 4.5 | Unvalidated `redirect` param | `safeRedirect()`, with tests for arrays, `//host` and empty strings |
| 4.5 | Chart overshoot | `cubicInterpolationMode: 'monotone'` |
| 4.5 | No column sorting | Sortable headers with `aria-sort`, against a server-side whitelist |
| 4.5 | Dashboard 2xl breakpoint | Two-column from `xl`, ledger rows restacked so nothing truncates |
| 4.5 | Stale comment, run-together sidebar text | Corrected |
| 4.6 | Account numbers shown in full | Masked to the last four digits behind an explicit reveal |

**Verification after the fixes:** 128 backend tests (was 94), 75 frontend tests
(was 61), Pint / ESLint / Prettier / `vue-tsc` all clean, contrast re-audited at
0 failures, and every changed screen re-checked in the browser at 1440 and 390.

Not changed, deliberately: the prefilled demo credentials (correct for a
take-home) and the single-role authorization model (documented and sound).

---

## 1. Verdict

This is strong work. The domain modelling is correct where it matters most — money is integer minor units end to end, the settlement sweep is row-locked and decrements rather than zeroes, and the gateway callback re-checks status under a lock so it cannot double-credit. The invariant `wallet = Σ(successful net) − Σ(settled)` is asserted in tests *and* satisfied by a seeder that replays 30 days of history through the real settlement service. That is a level of rigour most take-homes don't reach.

The accessibility work is real, not claimed. I re-ran an independent canvas-composited contrast audit and reproduced the "zero failures" result. Focus management, `inert` on the drawer background, `aria-invalid`/`aria-describedby`/`role="alert"` wiring, and live regions all check out under inspection.

Everything the README says it verifies, verifies:

| Check | README | Actual |
|---|---|---|
| Backend tests | 71 | **94 pass** |
| Frontend tests | 52 | **61 pass** |
| ESLint · Prettier · `vue-tsc` · Pint | pass | **all clean** |
| Contrast audit | 0 failures | **0 failures** (independently reproduced) |

The README's test counts are stale, but in the honest direction — there is *more* coverage than advertised. Still worth correcting, since a reviewer may read the mismatch as carelessness rather than as growth.

What holds this back is a cluster of **unvalidated-input 500s**, **one missing security control**, and a **design rule that doesn't survive contact with the rendered page**. None are architectural; all are localised.

---

## 2. Must fix

### 2.1 Unvalidated date parameters return 500 — `ReportController.php:15`, `TransactionController::index`, `SettlementController::index`

`$request->date('from')` calls `Carbon::parse()`, which throws on unparseable input. Nothing validates these parameters anywhere.

```
GET /api/reports/summary?from=not-a-date  → 500
GET /api/transactions?from=garbage        → 500
```

Both verified against the running app. The SPA's `<input type="date">` can't produce this, but a URL, a bookmark, or any API consumer can. For a money product, an unhandled 500 on user-reachable input is the wrong failure mode.

**Fix:** a `FormRequest` (or inline `validate`) with `['nullable', 'date']` on `from`/`to`, plus `after_or_equal:from` on `to` — which also fixes 2.2.

### 2.2 A reversed date range produces a backwards time series — `ReportingService.php:69`

`$start->diffInDays($end)` is **signed** in Carbon 3. With `from` after `to`:

```
GET /api/reports/summary?from=2026-07-20&to=2026-07-10
→ 200, 11 points, running 2026-07-20 … 2026-07-10 (descending)
```

The chart renders with time flowing right-to-left, silently. Verified. Guard the ordering in validation, and clamp `$days` to `max(1, …)` as defence in depth.

### 2.3 No rate limiting on login — `routes/web.php:18`

```
12 consecutive failed logins → 422, 422, 422, 422, 422, 422, 422, 422, 422, 422, 422, 422
```

No throttle, no lockout, no backoff. Verified. The route carries only `guest`. This is the one genuine security gap: a single-account admin surface for a payments back office with unlimited password attempts.

**Fix:** `->middleware(['guest', 'throttle:5,1'])`, or Laravel's `RateLimiter` keyed on email+IP with `ThrottlesLogins` semantics.

### 2.4 The seeder is not idempotent — `DatabaseSeeder.php:30`

The README's own documented command fails on second run:

```
php artisan migrate --seed
→ UNIQUE constraint failed: users.email
```

`User::factory()->create([...])` should be `User::factory()->createOrFirst(['email' => 'admin@kudi.test'], [...])` (or `firstOrCreate`). A reviewer who runs the documented command twice hits a stack trace.

---

## 3. Should fix

### 3.1 Chart and headline figures can describe different periods, unlabelled

Two distinct paths produce this:

- **"All time"** sets `from`/`to` empty. The backend then computes totals over all time but defaults the *series* to `now()->subDays(29)` (`ReportingService.php:169`). The cards read all-time; the chart reads 30 days. **Visible in the running app right now** — "All time" selected, chart axis 28 Jun → 27 Jul.
- **Any range over 180 days** hits `MAX_SERIES_DAYS` (`ReportingService.php:25`) and the series is silently trimmed to the last 180 days while the totals still cover the whole range. Verified: `2025-01-01 → 2026-07-27` returns all-time fees of GHS 11,627.35 alongside a series starting 2026-01-29.

The section heading is just "Platform fees earned" with no period. Either label the charted window explicitly, or return the applied window in the payload and render it. The truncation in particular should not be silent.

### 3.2 The brand violet is indistinguishable from ink at text sizes

The stated rule is that violet appears exactly three times per screen, one of them being the single headline figure. Measured from the rendered page:

| Token | Value |
|---|---|
| `text-violet` | `rgb(18, 1, 61)` — #12013D |
| `text-ink` | `rgb(26, 26, 31)` — #1A1A1F |

At the dashboard's 48px "Waiting to settle" figure the violet reads as violet. At the Settlements page's 30px "Outstanding wallet balances" figure — same `text-violet` class — it reads as **black**. The accent is carrying no signal on four of five screens, which means the rationing rule is enforced in the code but invisible in the product.

This is worth resolving one way or the other: either lift the accent's chroma/lightness at text sizes (a tint of the brand violet rather than the mark's exact value), or drop the "one violet figure" rule and let the accent live only on the wordmark and primary action, where it does read.

### 3.3 Money columns are left-aligned in the three main tables

The README argues that mono figures "align by construction". They don't, because the columns are left-aligned and the strings differ in length:

```
GHS 3,015.32
GHS 74.30          ← decimal point two columns left
GHS 1,536.05
```

Measured `text-align` across the app:

| Surface | Alignment |
|---|---|
| Merchant detail — transactions, settlements | `right` ✅ (10 cells) |
| Transactions / Merchants / Settlements tables | `start` ❌ |

So the app already does it correctly in one place. Right-aligning the money columns in the three list views is a one-line-per-column change and makes magnitude scannable — which is the entire job of a ledger.

### 3.4 Settlement history doesn't group by batch — `SettlementsPage.vue`

The README's central design argument is that the settlement *run* is the unit of the operator's work. The history screen contradicts that: it's a flat per-merchant list with `BATCH-20260719-DVUC` repeated verbatim on twelve consecutive rows, and the run's total appears nowhere. Grouping by batch (header row with date, reference, merchant count, total; merchants nested or collapsible) would match the model the rest of the app argues for.

### 3.5 The outcome bar makes colour the only link to its legend — `ReportsPage.vue:84-91`

Two documented decisions undercut each other. The bar is sorted by magnitude while the legend keeps fixed status order, "linked by colour, not position". But the adjacent comment states the three status tones sit at roughly **1.2:1 against each other** — chosen for text legibility on white, not for mutual discrimination.

So the reader is asked to match segments to legend entries by colour, using three colours the code itself documents as nearly indistinguishable side by side. Either sort the bar in legend order (position becomes the link), or label the segments inline.

### 3.6 Front-end fee rate is hardcoded while the backend reads config

```ts
// frontend/src/utils/money.ts:44
return Math.floor((minorUnits * 150 + 5000) / 10000)
```

```php
// backend/app/Support/Money.php
$basisPoints = (int) config('kudi.fee_basis_points', 150);
```

`KUDI_FEE_BASIS_POINTS` exists as an env var. Set it to anything but 150 and the simulator's live preview silently disagrees with what the backend actually charges — the README's "mirrored exactly" holds only at the default. Expose the rate on an endpoint (or in the bootstrapped payload) and derive the preview from it.

---

## 4. Worth knowing

### 4.1 Scale ceilings that fail silently

| Location | Cap | Consequence past the cap |
|---|---|---|
| `ReportingService::unsettledFloat()` — `:101` | 40 merchants | The settlement band's strips are shares of `wallet_balance` (**all** wallets), so past 40 float-holders the strips no longer sum to 100% and the band under-fills. "held across N of M merchants" also caps N at 40 and understates it. |
| `PaymentSimulatorModal.loadMerchants()` | `per_page: 100` (backend max) | The 101st active merchant cannot be paid, with no indication why. |

Both are invisible at the seeded 12 merchants. The band is the more interesting one because the *denominator* comes from a different query than the *numerator*.

### 4.2 Unauthenticated non-JSON hit on `/api/*` → 500

```
curl -H "Accept: text/html" /api/dashboard
→ 500  RouteNotFoundException: Route [login] not defined
```

`shouldRenderJsonWhen` covers exception rendering, but the auth middleware's redirect target is resolved first. Harmless for the SPA (always sends `Accept: application/json`), but anyone poking the API in a browser gets a stack trace instead of a 401. Register a named `login` route or set `$middleware->redirectGuestsTo(fn () => null)`.

### 4.3 `POST /api/login` while already authenticated returns a 302, not JSON

The `guest` middleware redirects to `/` rather than answering in JSON. In the SPA this surfaces as an opaque CORS/network failure rather than "you're already signed in" — I hit exactly this during review. Low frequency, but the response shape breaks the API contract every other endpoint honours.

### 4.4 A misconfigured port reports itself as "Your session timed out"

`SANCTUM_STATEFUL_DOMAINS` is port-pinned. Run Vite on any port not in that list and login *succeeds*, then the next request 401s, the interceptor fires, and the user is bounced to the login page reading "Your session timed out." That is a configuration error wearing a session error's clothes. It cost me several minutes and would cost a reviewer the same. Worth either a startup assertion or a note in the README's local-setup section.

### 4.5 Smaller items

- **No request sequencing in the stores.** `transactions.fetch()` has no cancellation or sequence guard; rapid filter changes can land out of order and paint a stale page. Search is debounced 300ms, but status/from/to fire immediately.
- **`ensureCsrf()` is called inconsistently** — present on `login`, `createMerchant`, `setMerchantStatuses`, `createPayment`, `runSettlement`; absent on `setMerchantStatus`, `processTransaction`, `logout` (`api/index.ts:52,108,138`). It works today because the `XSRF-TOKEN` cookie outlives the module-level `csrfReady` flag, but the asymmetry is a latent 419.
- **`whereDate()` on `created_at` / `settled_at`** wraps the column in a function and forgoes the indexes the migrations create. Irrelevant at 194 rows; the first thing to bite at scale.
- **`setRange()` uses `toISOString()`** (`ReportsPage.vue:45-46`) — UTC dates derived from local-time arithmetic. Ghana is UTC+0 so the target market is unaffected, but it's an off-by-one day for anyone west of it.
- **`router.push(route.query.redirect as string)`** (`LoginPage.vue:53`) — `??` doesn't catch an empty string, and a repeated query param yields an array. Vue Router resolves these relatively so it isn't an open redirect, but it should be validated to a leading single `/`.
- **Chart smoothing (`tension: 0.35`)** interpolates curves between daily points, implying intra-day values that don't exist and overshooting local minima. `cubicInterpolationMode: 'monotone'` keeps the smoothing without inventing extrema.
- **No column sorting anywhere.** A 194-row ledger that can't be sorted by amount is a functional gap, not a style one.
- **Dashboard splits at `2xl` (1536px).** At 1440 and 1366 — the two most common laptop widths — the chart takes the full width and pushes "Recent transactions" entirely below the fold. The documented reasoning (a 460px ledger column at 1280 truncates references) is sound, but the chosen breakpoint means most desktop users never see the two-column layout it was designed for.
- **Stale comment:** `money.ts` explains the ISO-code choice in terms of how "Archivo" draws U+20B5; the type stack is Geist.
- **Sidebar accessible text runs together:** "Merchant Wallet & SettlementDemo environment".

### 4.6 Security posture

- **Account numbers render in full** on the merchant detail page (`MerchantDetailPage.vue:102`), e.g. `4922014395`. The README commits to masking "in list views" — but list views don't show the field at all, so the stated mitigation targets the one surface that doesn't need it. The detail page is where the exposure actually is.
- **Demo credentials are hardcoded and prefilled** in `LoginPage.vue`. Correct for a take-home; must not survive into anything real.
- **Single-role authorization** is documented, reasoned, and appropriate for a platform-operator back office. The README's framing — that adding merchant logins would require scoping *every* endpoint, not patching one — is the right way to think about it.

---

## 5. What's genuinely good

Worth stating explicitly, because it's the bulk of the work:

- **Integer money end to end**, half-up rounding implemented without floats, mirrored in both languages and pinned by tests on both sides. Verified live: GHS 1,234.56 → fee GHS 18.52 → net GHS 1,216.04.
- **Settlement decrements under `lockForUpdate` inside one transaction**, never overwrites with zero. A concurrent credit survives.
- **The gateway callback re-reads and re-checks status under a row lock**, so two concurrent callbacks can't double-credit.
- **The seeder replays history through the real `SettlementService`** at backdated timestamps, so the seeded data satisfies the same invariant the tests assert rather than approximating it.
- **The settlement band is good information design.** Proportional strips per merchant, one interpolated ramp rather than categorical hues, tail folded past eight, each strip a link, hover *and* focus reveal, plus a text fallback for touch. The reasoning for each of those is in the code.
- **The settlement confirmation names the exact amount, the wallet count, and the pending-payments caveat** before it moves GHS 182,557.91. That's how a destructive confirmation should read.
- **Validation adapts to context**: choosing MTN Mobile Money relabels the field "Mobile money number" and errors with "Enter the 10-digit mobile money number, starting with 0. For example 024 123 4567." Error text measures 7.0:1.
- **The `BaseModal` window-vs-element keydown finding** (a self-disabling submit button drops focus to `<body>`, silently killing an element-bound Escape handler and focus trap) is a subtle real bug, correctly diagnosed, fixed, and regression-tested.
- **Clean layering**: services own anything carrying an invariant, scopes own filtering and no-op on null so controllers chain unconditionally, controllers stay a few lines. Read-side aggregation is separated because it's genuine logic, not query mapping.
- **The comments explain *why*, consistently**, including the decisions that were reversed. `docs/decisions.md` and `docs/design-log.md` are unusually candid.

---

## Appendix A — environment notes

- The worktree's changes are **uncommitted** — both the work that was already there and the fixes from §0. Nothing was committed or pushed.
- `backend/database/database.sqlite` (untracked, gitignored) was re-seeded with `migrate:fresh --seed`. Its wallets were already all-zero on arrival — a prior settlement run had swept them — which would have hidden the settlement band entirely. It now holds the intended state: 12 merchants, ~195 transactions, 12 positive wallets.
- I ran a Laravel server on `:8123` and Vite on `:5175` for the review. Both are still running; kill them with `pkill -f "artisan serve --port=8123"` and `pkill -f "vite --port 5175"`. Your own servers on `:8000`, `:5173` and `:5174` were left untouched.
- `.playwright-mcp/` in the frontend directory is browser-tool scratch output, already untracked.

## Appendix B — how findings were verified

| Finding | Method |
|---|---|
| 500s on bad dates, reversed series, 180-day truncation | Pest feature probes and `tinker` against `ReportingService`, then deleted |
| No login throttle | 12 sequential `postJson('/api/login')` calls |
| Contrast (0 failures) | Independent canvas-composited audit re-run in-page on `/` and `/merchants`; alpha un-premultiplied before compositing, ancestor backgrounds walked to the first opaque layer |
| Colour tokens, text alignment | `getComputedStyle` over rendered pages |
| `inert` / focus on drawer, modal ARIA, error wiring | DOM inspection while the UI was open |
| Fee math, payment lifecycle | End-to-end run through the simulator against the live API |
| Test suites, lint, format, types | `php artisan test`, `npm test`, `eslint`, `prettier --check`, `vue-tsc --noEmit`, `pint --test` |
