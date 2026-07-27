# Decision record

The judgment calls made building this app, why each went the way it did, and what the alternative
was. This is the *engineering* companion to two narrower documents: the [README](../README.md)
states the headline domain rules concisely, and the [design log](design-log.md) tells the story of
the visual direction. This one is broader — it covers the money model, the architecture, the
frontend, how correctness was verified, and the things deliberately left out — and it is honest
about the decisions that were reversed along the way.

Where a decision is enforced by a test or lives in a specific file, that is named, so a claim here
can be checked against the code rather than taken on trust.

---

## Money and the domain

The brief is a payments product, so the parts that move money were treated as the load-bearing
ones and given the most care.

**Money is integers end to end.** Every amount is stored and transported as integer minor units
(pesewas); only `frontend/src/utils/money.ts` turns them into strings for people. Floats were never
on the table — binary floating point cannot represent `0.10`, and a payments ledger that drifts by
a rounding error is worthless. The alternative, decimal columns, would work in the database but
still invites float arithmetic the moment a value reaches JavaScript. Integers are the one
representation that is exact in both languages.

**The fee is computed with integer math and rounded half-up**, `⌊(gross × 150 + 5000) / 10000⌋`,
in `App\Support\Money` on the server and mirrored in the frontend preview. Half-up is the
conventional commercial rounding. It is duplicated on the client deliberately — the payment
simulator shows gross → fee → net live as you type, before any request — with the server remaining
the single authority. Both implementations are pinned by tests, so they cannot drift apart
silently.

**The fee is stored, not derived.** A transaction keeps the fee it was actually charged rather than
recomputing it from a current rate. If the platform rate ever changes, historic transactions still
show what really happened. The transaction detail view surfaces the *effective* rate back out of
the stored figures, so an operator can confirm what was applied to a specific payment.

**Settlement decrements, it never zeroes.** The run locks wallet rows with `lockForUpdate` inside
one DB transaction, writes a settlement record per merchant, and subtracts the settled amount from
each balance. Setting the balance to zero would be simpler and would silently destroy any credit
that landed between the read and the write. Decrementing the exact amount cannot. Each run shares
one `BATCH-…` reference. Enforced in `App\Services\SettlementService`.

**Processing is race-safe.** The gateway callback re-checks the transaction status under a row lock
*inside* the transaction, not before it, so two callbacks arriving at once cannot both credit the
wallet. This is the kind of bug that never shows up in a demo and always shows up in production.

**The invariant is a test, not a comment.** `wallet.balance = Σ(successful net) − Σ(settled)` is
asserted in the suite, and the seeder earns the right to make that claim by replaying 30 days of
activity chronologically with real settlement runs at backdated timestamps, rather than writing
balances in directly.

**Pending payments never touch a wallet.** They credit only on successful processing; a settlement
run that happens between creation and processing simply leaves them for the next batch. This keeps
the invariant true at every instant rather than only at rest.

---

## Currency and locale

**Money displays as the ISO code (`GHS 1,250.75`), not the ₵ symbol.** The Cedi glyph renders
inconsistently across faces at display sizes, and money is the entire content of this product, so
it has to be unambiguous over pretty.

**Account numbers are validated against the institution, not a single pattern.** A mobile money
wallet is a Ghanaian mobile number (10 digits, `02x`/`05x` prefixes); a bank account is 8–20
digits. The field was originally `required|string|max:30`, which accepted `ACCT-90210` as a payout
destination — the one field on a merchant that decides where real money lands. `App\Support\
AccountNumber` now owns the rule, input is normalised before validating so `024 123 4567` is judged
and stored as `0241234567`, and the registration form states the rule (and switches its hint and
keypad) as you pick the institution. The honest limit, stated in the code and the README: this
checks *shape*, not existence. It catches typos; it cannot prove the account is real.

**A banks / mobile-money reference table replaces the brief's free-text "bank name."** A seeded list
of real Ghanaian institutions eliminates typos and mirrors real settlement rails. This is a
deliberate deviation from the brief's literal field, chosen because a free-text bank name on a
settlement product is a data-quality problem waiting to happen.

**One wallet per merchant, 1:1.** Documented as an assumption. Multi-currency or multi-account
wallets would be additive rather than a rewrite, so the simpler model was chosen for now.

---

## Architecture

**Separate SPA and API in one repo.** A Vue 3 + Vite frontend and an API-only Laravel backend,
rather than Blade views or Inertia. The brief scores frontend architecture, and a real SPA with its
own router, stores, and typed client demonstrates more of that than server-rendered pages would.

**Same-origin cookies via a proxy.** Both the Vite dev server and the Docker nginx proxy `/api` and
`/sanctum` to Laravel, so Sanctum's session cookie is always first-party and there is no CORS or
cross-origin-cookie fragility. Auth lives under `/api/login` specifically so the SPA can keep its
own `/login` page — an early version put auth at the top level and the two `/login` routes
collided.

**Services own invariants; scopes own filtering; controllers stay thin.** Anything that carries a
correctness guarantee — the fee, wallet crediting, the locked settlement sweep — lives in a service
(`PaymentService`, `SettlementService`). Read-side aggregation that is more than a query — day
bucketing, zero-filling, float composition — lives in `ReportingService`. List filtering lives in
Eloquent scopes (`Merchant::search`, `Transaction::createdBetween`) that no-op on null, so
controllers chain them unconditionally and stay a few lines each. This line was drawn mid-build:
query logic that had started in the controllers was moved into the service and scope layers once it
was clear the controllers were accreting responsibility.

**State is Pinia stores per domain over a typed client.** One store each for auth, dashboard,
merchants, transactions, settlements, over an API client with a single error-normalisation path:
422s become field errors, everything else becomes a toast-able message. A domain store is the unit
that owns "the current list, its meta, and how to refetch it," which is exactly what each page
needs.

**Shared types are extracted once they are written twice.** The Laravel paginator meta shape was
being redeclared in three stores, in the `PaginationBar` props, and inside `Paginated<T>`; it is now
one `PageMeta` interface. Extracting it also surfaced a latent bug — the stores *typed* three fields
while *assigning* all six, so half the object was invisible to the compiler.

---

## Frontend behaviour and UX

**Register merchant is a dialog, not an inline form.** An inline form below the list pushes the
list around and buries the primary content; a dialog keeps the list stable and focuses the task.

**Navigation highlight is computed from the path, not from RouterLink's matched records.** A merchant
detail route (`/merchants/:id`) is a sibling of the list route, not a child, so RouterLink's own
active-class does not treat the section as active on a detail page. Deriving it from the path keeps
the correct nav item lit. This is asserted in a test that fails if the prefix matching is removed.

**A lapsed session logs the user out rather than letting the page fail silently.** The first 401
signs them out, sends them to login with the page they were on preserved and a reason shown, and
returning to a backgrounded tab re-checks the session — so an expiry is caught on return rather than
on the next click that mysteriously does nothing.

**The open transaction lives in the URL.** `?tx=PAY-…` makes a specific payment linkable and
reloadable, and lets the browser back button close the panel. Resolving it goes *through the store*,
which checks the loaded page first and falls back to fetching by reference — because the list holds
only one page, and matching against it alone silently opened nothing for any transaction past page
one. A stale or mistyped reference explains itself and tidies the URL rather than looking like the
click failed.

**Stranded pending payments are recoverable.** A payment resolves via a follow-up call from the tab
that created it; close that tab and the record is stuck pending forever, as is any transaction
seeded pending. A *Check status* action on pending rows re-asks the gateway through the existing
endpoint. The real fix — server-side reconciliation on a schedule — is named as a limitation rather
than pretended away.

**Pagination returns the viewport to the top.** Without it, paging keeps the scroll position:
mid-table on a full page, but snapped to the top on a short final page, so the same click lands in
two different places. It jumps rather than smooth-scrolls, because the rows underneath are being
replaced at the same time.

**Toasts auto-dismiss, errors last longer, and they sit where the action was.** Everything clears
itself — success and info at 5s, errors at 10s — with the timer paused while a toast is hovered or
focused. Errors were originally persistent on a WCAG reading, but a panel the user has to tidy up
after is worse in practice. They live top-right, near the actions that raise them, rather than
bottom-centre. A short synthesised Web Audio chime plays on each, behind a header mute toggle that
persists, because there is no `prefers-reduced-sound` to read.

**One panel primitive, one dropdown treatment.** Every content block — table, chart, stat — sits
inside the same `.panel` (1px edge, 10px radius, unfilled); every `<select>` uses one `.select-field`
chevron at a consistent inset. Mixing framed and unframed blocks, or native and custom dropdown
arrows, is what makes a UI look unresolved. The tone of a toast, similarly, is a visual register and
not a claim about the request — a declined payment shows an error toast even though the API call
that reported it succeeded.

The visual language itself — a white ground with brand violet rationed to three touches per screen —
is the subject of the [design log](design-log.md), including the several rounds it took to get there.

---

## How correctness was verified

**Contrast is measured programmatically, not eyeballed.** An audit walks the rendered DOM on every
page, composites each element's real background, and compares. Crucially it resolves colours by
painting them to a canvas rather than parsing strings, because Tailwind v4 emits `oklab()` for any
opacity-modified colour, which a regex parser skips *silently* — the first audit reported clean and
was wrong. The rewritten audit reports how many elements it could not parse, so it cannot lie that
way again. Latest run: 1,268 elements, 0 unparseable, 0 failures across 1920 / 1440 / 390.

**Business rules were built test-first.** The fee, the settlement sweep, the wallet invariant, and
account-number validation were written as failing tests first, then implemented — so each test is
known to fail without its code, rather than being written afterward to confirm what was already
built.

**Behaviour was verified in a real browser, not just asserted in unit tests.** Flows like the
settlement run, the deep-linked transaction, the declined-payment toast colour, and the sorted
outcome bar were driven and measured in an actual browser — often by forcing the specific case
(intercepting a response to force a decline, or a count distribution) rather than waiting on a
simulated probability. This is how several of the bugs above were found in the first place.

---

## Deliberate non-goals

These are choices too, and calling them out is more honest than leaving them to be discovered.

- **Authorization is single-role by design.** This is a platform-operator back office: one signed-in
  identity, entitled to every merchant and transaction. The `users` table has no tenant key and
  merchants are records, not login principals — so no endpoint scopes by owner, and the boundary is
  authentication itself, with every route behind `auth:sanctum`. If merchants were ever given logins,
  scoping would have to be added to *every* list and detail endpoint, not patched onto one.
- **No dark mode.** Deliberately deferred. The token layer is now clean enough that it is mostly a
  second set of values rather than a rework.
- **Offset pagination, not cursor.** Simpler, and correct enough for this data size. Cursor
  pagination is the right answer for an append-only ledger that grows without bound, and would be the
  change to make first at scale.
- **The gateway is simulated and not reconciled server-side.** Resolution depends on a client call;
  a scheduled reconciliation job is the production answer and is out of scope here.
- **GenUI was evaluated and declined for this deliverable.** It fits nowhere near money movement, and
  the one place it could help — natural-language ledger filtering — needs an API key that collides
  with the brief's "do not commit secrets" and adds reviewer setup, for a feature the brief does not
  score. Better spent elsewhere.
- **`php artisan serve` backs the Docker image.** Fine for review traffic, not production, where it
  would be PHP-FPM behind nginx.

---

## Decisions we reversed

The straight-line version of a build is a fiction. The reversals are where most of the judgment
actually happened.

- **Invented brand → the real one → rationed.** The first build shipped in a palette invented from
  nothing while Kudi's logo sat on page one of the brief. Corrected to the real values, then
  *over*-corrected into an institutional violet slab, and only resolved once the brand colour was
  given a budget rather than a better hue. The full arc is in the [design log](design-log.md).
- **Per-route container width → one width everywhere.** A full-screen pass showed 640px of unused
  window, so the container was widened — but making the width depend on the route meant content
  shifted position when navigating between Dashboard and Merchants. That is a worse problem than a
  slightly wide summary page, so it was reverted to one container width for the whole shell, with the
  dashboard laid out in columns internally instead.
- **Persistent error toasts → auto-dismiss.** Errors were initially left on screen indefinitely on a
  WCAG "no time limits on important content" reading. In practice that makes a panel the user has to
  clean up. They now auto-dismiss on a longer timer, paused on hover/focus.

Each of these was a real mistake caught and fixed, and each correction came from review rather than
from the initial implementation — which is itself the argument for having the review.
