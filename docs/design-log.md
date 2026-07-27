# Design log

How this interface arrived at its current look, in the order it happened. Six rounds of
prototypes, two reversals, and a brand that had to be discovered, taken literally, and then
turned down.

Each round was built as a working HTML specimen with real figures rather than a static mockup,
so it could be judged at full size before anything was committed to the app.

Four of the rounds are embedded in **[`prototypes.html`](prototypes.html)** — open that file in a
browser to page through the actual specimens. (GitHub shows HTML as source, so clone the repo or
use a raw-HTML viewer.) The typographic round is described below but not embedded: it inlined eight
typefaces, which is half a megabyte to make a point this log makes in a sentence.

---

## 1. Where it started — a brand I invented

The first working version shipped in a palette with no source: kente maize, forest green and clay
red, set in Archivo against IBM Plex. It was internally consistent, it measured well for contrast,
and it had nothing to do with Kudi.

The company's logo was on page one of the brief the whole time. I had not looked at it.

> **The mistake.** Inventing an identity for a client who already has one is not a style choice.
> It is not having read the brief.

---

## 2. Round one — four directions, and the correction

Four complete directions, presented for comparison. The useful outcome was not any of them; it was
the response: *use Kudi's own design language.*

Sampling the logo out of the rendered PDF gave the real values:

| Token | Value | Where it came from |
| --- | --- | --- |
| Violet | `#12013D` | The wordmark |
| Red | `#D73E33` | The square tittle on the *i* |
| Squared tittle | — | The one distinguishing detail of the mark |

Applied literally, those values produced a saturated violet slab behind the headline figure, a
five-hue settlement band, 3px corners, and heavy wide type.

> **What was wrong.** Faithful to the logo, and institutional to use. Correct values, wrong weight.

🎨 [The four directions](prototypes.html#round-02)

---

## 3. Round two — five typographic treatments

Same brand tokens, five different typographic temperaments, to isolate whether the heaviness was
coming from the type or from the colour.

It was coming from both, but mostly the colour — which is what the next round acted on.

> **The verdict.** *"doesn't feel like a clean modern dashboard"* — still true, three rounds in.


---

## 4. Round three — give the brand a budget

The diagnosis that unlocked it: violet was competing with the data rather than framing it. So it
was rationed to three appearances per screen — the wordmark, the primary action, and the single
headline figure — with everything else neutral.

Seven treatments, varying ground, navigation structure, density and how much colour was permitted
at all. Two findings worth keeping:

- **Airy and Night are one design at two luminances**, not two options — same pairing, same
  structure, same spacing, different ground.
- The mark's own personality (heavy, wide, squared) barely survives any genuinely clean treatment.
  That trade was made deliberately, not by accident.

📐 [All seven clean treatments](prototypes.html#round-04)

---

## 5. Round four — islands

Panels floating on a ground rather than sections divided by rules. The interesting variant was the
inverse of the obvious one: instead of scattering white cards, make the **shell** the brand and let
the content sit inside it as a single inset page. Violet on every screen, never touching a number.

> **Why it nearly won.** It resolved the original tension instead of trading it off — the brand is
> unmistakable and the data is completely undisturbed.

🧩 [The four island treatments](prototypes.html#round-05)

---

## 6. Round five — Inset × Deep

Fusing the inset structure with a dark ground. It works because `#12013D` is already a near-black:
read as dark-mode chrome rather than as a coloured panel, it stops being a slab.

Building it surfaced something the eye cannot check. The first attempt put the content page at
**1.07:1** against the shell — mathematically lifted, visually invisible. Two near-blacks never
separate by luminance, and spreading them far enough apart to try drops the lighter one below 3:1
against the page.

> **What it taught.** Dark-on-dark boundaries are carried by hue and border, not brightness. Every
> surface was re-tuned and re-measured rather than eyeballed.

🌒 [The three fusions](prototypes.html#round-06)

---

## 7. The decision — Airy only

> *"we don't necessary need to go full on with the branding, it will make it too busy"*

The right call, and it ended the search. A full violet shell puts brand colour on every screen at
all times — the same weight problem as round one, relocated from the content to the furniture. It
photographs well and wears badly over an eight-hour shift.

What shipped:

- **White throughout.** Structure comes from hairline rules and space. `canvas` is only ever a hover
  or zebra tint, never a ground.
- **One panel primitive.** Every content block — a table, a chart, a stat — sits inside the same
  1px edge and 10px radius. Mixing framed and unframed blocks on one screen is what makes a layout
  look unresolved.
- **Violet rationed to three touches per screen**: the wordmark, the primary action, the headline
  figure. Verified by walking the rendered DOM for computed violet, not by inspection.
- **Status colour is semantic and separate** from the brand accent, so a violet button can never be
  mistaken for a success state.

---

## What the detour was actually for

Rounds one to three were increasingly faithful to the mark and increasingly unpleasant to use. The
deadlock broke by giving violet a *budget*, not by finding a better violet.

Both corrections that mattered came from review, not from me: that Kudi already had a design
language, and that the full-brand shell was too busy before it shipped.

What survives from the discarded rounds is not visual — it is the measurements:

- Two near-blacks never separate by luminance; the boundary needs hue and a border.
- Three status colours cannot be *mutually* distinguishable and *all* readable on white — the
  luminance range does not stretch that far, so the segments of a stacked bar have to be separated
  structurally instead.
- A violet chosen for text contrast is the wrong violet for a chart segment on a dark ground.
- Tailwind v4 emits `oklab()` for opacity-modified colours, which silently defeats regex-based
  contrast auditing. Colours have to be resolved by painting them to a canvas.

📖 The specimens themselves are in **[`prototypes.html`](prototypes.html)**.
