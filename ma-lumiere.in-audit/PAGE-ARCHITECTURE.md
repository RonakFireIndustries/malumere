# Page Architecture & Internal Linking — ma-lumiere.in

**Date:** 2026-11-23
**Business:** Ma Lumière — **one** physical medical skin & aesthetic care clinic in Mira Road East, Maharashtra 401105, India. Verified phone +91 99878 02355. Clinician: Dr. Rishita Ray ("Aesthetic Physician" — no unverified credentials).
**Goal:** Local coverage of the Western Railway corridor (Churchgate → Dahanu Road) without doorway pages or branch claims.
**Status:** Recommendation document. No live crawl or GSC/GBP data was available; URL/status values below come from the audit artifacts and implementation verification on the local copy.

---

## 1) Core recommendations (read first)

1. **Keep current URLs.** No permalink changes, no migrations, no new Botox URL. Botox stays on the existing `/treatment/injectables-facial-contouring/` page.
2. **One local hub only:** `/skin-clinic-mira-bhayander/`. It is the single local landing page for the clinic; it is now linked from the footer and contextually from treatment pages.
3. **Four treatment pillars** (the money pages): acne, injectables (Botox), hair loss, pigmentation. All other `/treatment/*` pages are supporting spokes, not additional local hubs.
4. **At most 4–5 genuinely unique corridor cluster pages** — **only if real value exists**. These are conditional, not a quota. If a page cannot clear the >60%-unique + swap-test bar, **do not publish it**.
5. **No doorway pages.** No page per station (~30+ stops), no city×service matrix, no swapped station names on shared copy.
6. **No branch claims.** The clinic has one physical location. Never describe areas served as branches, centres, or additional clinics; never claim a GBP listing or pin for a station where no clinic exists.
7. **Breadcrumbs** on all inner pages; descriptive anchors, not "Learn more"; every important page ≤3 clicks from home.

---

## 2) Current URL inventory, roles and canonical actions

| Current URL | Role | Status (from artifacts) | Canonical action | Internal links |
|---|---|---|---|---|
| `/` | Homepage / brand + service overview | Live, self-canonical; valid clinic+website+person schema | Self-canonical `https://ma-lumiere.in/` | → local hub, 4 pillars, `/contact/`, `/faqs/`, `/book-consultation/`; replace generic "Learn more" anchors with descriptive ones |
| `/skin-clinic-mira-bhayander/` | **Single local hub** (Mira Road / Bhayander) | Live, self-canonical; now has footer link + contextual treatment-page link; hub content | Self-canonical `https://ma-lumiere.in/skin-clinic-mira-bhayander/` | → 4 pillars, `/contact/`, `/book-consultation/`, `/faqs/`; candidate GBP website target (owner decision) |
| `/treatment/acne-acne-scars/` | **Pillar** — acne & acne scars | Live, self-canonical; `Service` schema valid | Self-canonical; keep path | ↔ hub, ↔ pigmentation & hair-loss pillars, → `/book-consultation/`, → `/faqs/` |
| `/treatment/injectables-facial-contouring/` | **Pillar** — Botox & injectables | Live, self-canonical; Botox label updated, `Service` schema valid | Self-canonical; **Botox must remain here** | ↔ hub, → acne/pigmentation pillars (combination planning), → `/book-consultation/` |
| `/treatment/hair-loss-hair-thinning/` | **Pillar** — hair loss & thinning | Live, self-canonical | Self-canonical; keep path | ↔ hub, → `/treatment/laser-hair-reduction/` supporting page, → `/book-consultation/` |
| `/treatment/pigmentation-uneven-skin-tone/` | **Pillar** — pigmentation & uneven tone | Live, self-canonical | Self-canonical; keep path | ↔ hub, ↔ acne pillar, → `/book-consultation/` |
| `/treatment/laser-hair-reduction/` | Supporting treatment spoke | Live, self-canonical | Self-canonical | → hub, `/faqs/`, `/book-consultation/` |
| `/treatment/pre-wedding-skin-preparation/` | Supporting treatment spoke | Live | Self-canonical | → hub, related pillars, `/book-consultation/` |
| `/treatment/*` (other ~10: dull-tired-skin, enlarged-pores, facial-rejuvenation, healthy-aging-skin-rejuvenation, hyperhidrosis, laser-tattoo-removal, mole-and-skin-tag-removal, preventive-skin-care, uneven-skin-texture, etc.) | Supporting treatment spokes | Live | Self-canonical; do **not** turn into local landing pages | → nearest pillar, hub, `/treatments/`, `/book-consultation/` |
| `/treatment-type/*` (archives, e.g. `.../acne-acne-scars/`, `.../pigmentation-uneven-skin-tone/`) | Taxonomy archive | **Noindex,follow; removed from sitemap** (sitemap 29 URLs) | Canonicalise to the matching richer `/treatment/*` page; never self-canonical or index | Outbound links point to the corresponding `/treatment/*` pillar; no local intent |
| `/treatments/` | Treatment index | Live | Self-canonical | → 4 pillars + supporting spokes; no local modifiers |
| `/about/` | Clinician/practice story | Live | Self-canonical | → hub, `/faqs/`, `/book-consultation/`; no unverified credentials |
| `/contact/` | NAP, hours, directions | Live; standardised NAP, `+91` phone, address-based directions link | Self-canonical | → hub, `/book-consultation/`, `/faqs/`; directions link is address-based, **not** a GBP/Place ID link |
| `/faqs/` | Visible Q&A | Live; `FAQPage` deliberately omitted (rich results retired in 2026) | Self-canonical | → hub, pillars, `/contact/`, `/book-consultation/` |
| `/book-consultation/` | Appointment conversion | Live; key-page meta present | Self-canonical | → hub, pillars, `/contact/`; GBP appointment-link target with UTMs |
| `/blog/` | Editorial index | Live | Self-canonical | → relevant posts, hub, pillars |
| `/blog/face-treatments-mira-bhayander-guide/` | Existing local-adjacent blog post | Live | Self-canonical | → hub, pillars; do not duplicate the hub's role |
| `/results/acne-treatment-case-reference/` | Educational results/expectations page | Placeholders removed; educational copy published; no patient images or outcome claims; currently self-canonical and in sitemap | Owner decision: keep indexed as an educational resource, or `noindex` if it is not intended for public discovery. Do **not** add patient images/testimonials without documented consent | → acne pillar only; no local intent, no fabricated images |

**Rule:** the sitemap should contain only indexable, self-canonical pages. Noindexed `/treatment-type/*` archives stay out (29 URLs after the fix). The results URL remains in the sitemap because it is now an indexable educational page; the owner may choose to noindex and remove it only if it is not intended as a public resource.

---

## 3) Desired hub-and-spoke model

```
Homepage
├── Local hub: /skin-clinic-mira-bhayander/          (ONE local hub)
│   ├── Pillar: /treatment/acne-acne-scars/
│   ├── Pillar: /treatment/injectables-facial-contouring/   (Botox stays here)
│   ├── Pillar: /treatment/hair-loss-hair-thinning/
│   ├── Pillar: /treatment/pigmentation-uneven-skin-tone/
│   ├── (Optional, conditional) corridor clusters — max 4–5, see §4
│   ├── /contact/  ·  /faqs/  ·  /book-consultation/
│   └── Supporting /treatment/* spokes
└── /about/ · /treatments/ · /blog/ · /book-consultation/
```

**Link rules:**
- Homepage → hub + 4 pillars + contact/booking.
- Hub → 4 pillars + contact + booking + FAQs; optional clusters only if published.
- Each pillar → hub + 2 related pillars/spokes + booking + FAQ.
- Every supporting treatment → nearest pillar + hub + booking.
- Vary anchors by intent ("acne scar treatment in Mira Road East", "book a skin consultation"), not generic "Learn more".
- No links from any page to noindexed archives as ranking targets; archives only point up to the real treatment page.

---

## 4) Corridor cluster pages — conditional, max 4–5

**None are required to launch.** Create only where a real patient-journey need exists, with >60% unique content and a documented swap-test pass. Candidate groups from the existing audit reports (Churchgate → Dahanu Road):

| Candidate cluster (proposed, not created) | Real value test — must be satisfied before publishing |
|---|---|
| Bhayander / Kashimira | Genuine travel detail from Bhayander East/West and Kashimira to Mira Road; parking/landmark; local FAQ; consented local proof. |
| Borivali – Dahisar | Actual commute guidance and why patients travel to Mira Road; distinct from hub copy. |
| Vasai – Virar | Station-specific directions/travel time; distinct FAQs; not a renamed hub. |
| Palghar – Dahanu | Long-distance journey planning, appointment staging; only if real demand shown (GSC/GBP Insights). |
| Churchgate – Bandra / South Mumbai (optional fifth) | Only if genuine demand/evidence exists; otherwise skip — do not fill a quota. |

**Every cluster must:** be >60% unique, include directions/travel time from the **actual** station, landmark/parking detail, locally relevant FAQ, consented/de-identified proof, internal links to the relevant pillar(s) and hub, and a **documented swap test** (if the station name can be swapped and the copy still reads correctly → **do not publish**).

**Never:** one page per station; city×service matrices; swapped copy; claims of a clinic/branch at that station; "best skin clinic in [station]" thin pages.

---

## 5) Breadcrumbs

- Homepage: none (or just Home).
- Pillars/supporting treatments: `Home > Treatments > [Pillar/Treatment]`
- Local hub: `Home > Skin Clinic Mira Bhayander`
- Conditional corridor cluster: `Home > Skin Clinic Mira Bhayander > [Area Guide]`
- Utility pages: `Home > [Contact / FAQs / Book Consultation / About / Blog]`
- Keep `BreadcrumbList` JSON-LD aligned with visible breadcrumbs; one breadcrumb trail per page.

---

## 6) Guardrails

- **No doorway pages:** per the baseline audit, a station-per-page pattern (~30+ pages with swapped names) is a textbook doorway risk; seo-local guidance flags WARNING at 30+ and HARD STOP at 50+ without justification. Four to five genuinely unique clusters are the ceiling.
- **No branch claims:** one clinic, one pin, one NAP. Service-area copy = where patients come from, not where clinics are.
- **No permalink changes** in this phase; keep the implemented NAP/schema/directions/noindex state intact.
- **No fabricated data:** no Place IDs, geo coordinates, reviews, photos, hours, credentials, or ratings.

---

## 7) Limitations

URL roles/statuses derive from the baseline audit artifacts and local implementation verification, not a fresh production crawl. No GSC/GA4/GBP data was available to confirm real demand for corridor clusters; candidate pages are proposals gated on owner evidence. The guardrails and link rules in this document informed the local implementation, which is recorded in `CHANGELOG.md`; no production files were changed. See `LOCAL-SEO-ANALYSIS.md` (score and owner actions) and `GBP-OPTIMIZATION-CHECKLIST.md` (GBP fields and review policy).
