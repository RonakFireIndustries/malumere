# Local SEO Analysis — Update (ma-lumiere.in)

**Update date:** 2026-11-23
**Source baseline:** `LOCAL-SEO-ANALYSIS-ma-lumiere.in.md` (same date), Local SEO **31/100**
**Scope:** Local SEO only — GBP, reviews, on-page local signals, NAP/citations, local schema, local authority. English-only footprint.
**Business:** Ma Lumière — **one** physical medical skin & aesthetic care clinic, Shop No. 117, 1st Floor, Vasudev Sky High, Kanakia Road, Beverly Park, Mira Road East, Maharashtra 401105, India. Verified phone **+91 99878 02355**. Clinician named on site: **Dr. Rishita Ray**; sole verified professional description: **"Aesthetic Physician"** (no degrees, registrations, or years of experience are verified — do not add them).
**Target market:** Western Railway corridor, Churchgate → Dahanu Road (marketing reach only — see §2).
**Status of this document:** Updated analysis reflecting fixes implemented and verified on the **local WordPress copy**; not deployed to production. No GSC, GA4, GBP, Insights, geo-grid, or backlink data was available. Findings are split into **static-signal findings** (what is observable in the site/code) and **estimates** (the score). Nothing here is measured live local-pack performance.

---

## 1) Updated local score estimate: ~41/100 (baseline 31/100)

> **This is a static-signal estimate, not measured performance.** It is a deterministic re-score of the same six dimensions used in the baseline, after the implemented fixes listed in §3. No GBP dashboard, Search Console, Analytics, rank tracker, or geo-grid data was connected, so proximity, real local-pack positions, clicks, calls, and review performance are **unknown**, not scored. Treat the number as a directional estimate of machine-readable local evidence on the website and public web only.

| Dimension | Weight | Baseline /100 | Updated /100 | Baseline weighted | Updated weighted | Why it moved (or did not) |
|---|---:|---:|---:|---:|---:|---|
| GBP signals | 25% | 30 | 30 | 7.50 | 7.50 | **Unchanged.** Supplied `https://share.google/V7HQuDrctGMVpIoii` resolved to a Google share/search page, not a canonical Maps place URL; no Place ID supplied. No GBP URL, review widget, or place-based integration on site. The new directions link is **address-based**, not a GBP/Place ID integration. |
| Reviews & reputation | 20% | 15 | 15 | 3.00 | 3.00 | **Unchanged.** No reviews, ratings, or testimonials were added; no `aggregateRating`/`review` markup (correctly withheld). |
| Local on-page SEO | 20% | 62 | 74 | 12.40 | 14.80 | **Improved.** Real Google Maps *directions* link, standardised visible NAP, location page now linked from footer + contextual treatment-page link, key-page metas present, thin archives de-indexed and removed from sitemap. |
| NAP consistency & citations | 15% | 25 | 45 | 3.75 | 6.75 | **Partly improved.** On-page and schema NAP are now consistent and parsed into fields, with `+91` and PIN 401105. Directory/GBP citations remain unverified; variant "Lumiere Clinic" listings unresolved. |
| Local schema | 10% | 30 | 70 | 3.00 | 7.00 | **Improved.** Correct `MedicalClinic`+`LocalBusiness` subtype, `@id`, full `PostalAddress`, `areaServed`, `hasMap`, `Person` node, valid JSON-LD on sampled pages. Still **no verified geo coordinates, no opening hours and no real `sameAs`; `FAQPage` is deliberately omitted because Google retired FAQ rich results in 2026, and ratings are withheld pending genuine data**. |
| Local link & authority | 10% | 10 | 18 | 1.00 | 1.80 | **Small improvement.** Local hub is now internally reachable (footer + contextual), ending its orphan state. No real social `sameAs`, press, chamber, or "best of" mentions yet. |
| **TOTAL** | **100%** | — | — | **30.65 → 31** | **41.35 → ~41** | **+10 points from on-site fixes; the two heaviest dimensions (GBP 25% + Reviews 20% = 45% of weight) remain essentially capped pending owner action.** |

**Methodology.** Same weighted rubric and weights as the baseline, re-scored only against evidence observable in the local theme/database and the audit artifacts (`findings/implementation-verification.json`, `findings/jsonld-verification.json`, `findings/sitemap-discovery.json`). Each dimension was re-rated on: presence of the signal, correctness/consistency, machine-readability, and whether it is verified vs. placeholder. No new data source was introduced; no measurement was inferred from the score. **The estimate cannot confirm any ranking, traffic, or conversion outcome.**

**Implication for the ~55 target in `ACTION-PLAN.md`:** reaching 55+ is not achievable through website edits alone. It requires owner-supplied GBP verification (unlocks the 25% block) and a genuine review history (unlocks the 20% block). Until then,~41 is the realistic ceiling for static on-site evidence.

---

## 2) Business type and vertical (unchanged)

- **Brick-and-mortar, single physical location**, with service-area *framing* in copy — not a pure service-area business. Full brick-and-mortar checks apply (physical NAP, directions, hours).
- **Vertical:** healthcare — medical aesthetic / skin clinic (YMYL). Correct schema family is `MedicalClinic` plus a `Person` node; never fabricate credentials, registrations, ratings, coordinates, or reviews.
- **One clinic only.** service-area language (Bhayander, Kashimira, Dahisar, Borivali, Thane, corridor stations) describes where patients travel *from*. It does **not** create ranking eligibility at distant stations, and must not be presented as branches or multiple locations.

---

## 3) Implemented fixes now reflected in this analysis

Verified in `findings/implementation-verification.json` (9/9 PASS) and `findings/jsonld-verification.json` (sampled pages VALID):

| Fix | Implemented state | Local-SEO effect |
|---|---|---|
| Canonical NAP | `Shop No. 117, 1st Floor, Ma Lumière Clinic, Vasudev Sky High, Kanakia Road, Beverly Park, Mira Road East, Maharashtra 401105, India` | Consistent, parsable address on site and in schema. GBP must be checked to match exactly. |
| Phone | Display `+91 99878 02355`; `tel:`/schema `+919987802355` | E.164-format click-to-call and structured telephone. |
| Directions | Address-based Google Maps directions URL present (no Place ID) | Fixes the previously false FAQ claim; genuine local conversion signal. **Not** a GBP integration. |
| Schema | `MedicalClinic`+`LocalBusiness` with `@id`, full `PostalAddress`, `areaServed`, `hasMap`, `WebSite`, `Person` ("Aesthetic Physician"), `WebPage`; `Service` on treatment pillars; JSON-LD valid on sampled pages | Strong machine-readable local entity; no fabricated data. |
| Thin `/treatment-type/*` archives | `noindex,follow` for `treatment_category` taxonomies, removed from sitemap (sitemap now 29 URLs), canonicalised to richer `/treatment/*` pages | Removes thin duplicate competition; consolidates signals. |
| Location page | `/skin-clinic-mira-bhayander/` now has a footer link and a contextual link from treatment pages | Ends orphan status; strengthens the single local hub. |
| Content placeholders | Patient-visible placeholders removed; "Botox" relabelled to "Botox & Injectables" with the pillar updated | Trust/YMYL hygiene; Botox remains on the existing injectables URL. |

**Owner action still required for each of these to be safe:** confirm the GBP listing's exact name, address, PIN, and phone match the canonical NAP above; the site now uses one consistent form, but GBP has not been read.

---

## 4) GBP status — unresolved (owner action)

**No GBP data was accessible.** There is no GBP API/MCP connection, no Place ID, and no canonical GBP URL.

- The owner-supplied `https://share.google/V7HQuDrctGMVpIoii` **must be treated as unverified**: verification resolved it to a Google share/search page, not a canonical Google Maps place URL. It must **not** be used as the website `sameAs` value, and **no Place ID has been supplied or invented**.
- The address-based directions link currently deployed is intentionally **not** a place-based link; adding a Place ID or `maps.place`/`cid` URL requires the owner to supply the canonical URL from the GBP dashboard.
- Categories, verification status, hours, photos, posts, Q&A, review data, service-area setting, and Insights remain **unknown**. See `GBP-OPTIMIZATION-CHECKLIST.md` for the full owner checklist.

---

## 5) Reviews & reputation — unresolved (owner action)

| Check | Status (static finding) |
|---|---|
| `aggregateRating` / `review` schema | ❌ Absent — and correctly so; no genuine data to mark up. |
| On-site testimonials / review widget | ❌ None detected. |
| Google rating / count / recency / owner responses | **Unknown** — GBP data not available. |
| Variant third-party listings | "Lumiere Clinic" Justdial/Practo entries at the same premises were flagged in the baseline; ownership still unverified. |

**Owner actions:** supply rating and count, last-90-day review data, most recent review date, response rate, and Insights; launch the compliant acquisition cadence in the GBP checklist. Benchmarks remain: 10+ reviews, 4.5+ stars, no gap over ~2–3 weeks, 100% owner responses (never confirm a reviewer is a patient), no gating, no incentives, no fake reviews.

---

## 6) NAP consistency & citations — partial fix, owner verification outstanding

**Canonical NAP now in use on site + schema:** name form as displayed on site (must be matched to GBP exactly); address as in §3; phone `+91 99878 02355` / `+919987802355`; PIN **401105**.

| Source | Status | Owner action |
|---|---|---|
| Site HTML, schema, footer, contact, hub | ✅ Consistent and parsable (static finding) | Keep this exact block everywhere; do not reintroduce abbreviations. |
| Google Business Profile | ⚠️ Unread | Confirm exact name/address/PIN/phone/hours match; supply canonical URL. |
| Bing Places, Apple Business/Maps, Facebook, Sulekha | ⚠️ Not detectable in baseline | Claim and align; Bing feeds ChatGPT/Copilot. |
| Justdial / Practo "Lumiere Clinic" | ⚠️ Unverified variant | Confirm ownership; claim/correct name, specialty, phone, PIN, hours. |
| Data aggregators / local press / "best of" lists | ⚠️ Not detectable | Pursue; brand mentions precede AI visibility. |

"Not detectable" means not found in the search corpus used for the baseline — not proof of absence. **Hours** are a separate owner action: the site displays Tue–Sat 12:00–21:00, Sun 12:00–17:00, Mon closed, but these were **not** verified against GBP and are **not** in schema.

---

## 7) Local schema — implemented vs still missing

**Implemented:** `MedicalClinic` + `LocalBusiness`, `@id`, structured `PostalAddress` (locality, region, `postalCode`, country), `areaServed`, `hasMap` (address URL), `WebSite`, `Person` node, `WebPage`, `Service` on pillars; valid JSON-LD on sampled pages.

**Still missing / owner-dependent — do not guess:**
1. **Geo coordinates** — no `geo`/`GeoCoordinates` in the deployed schema. Must be the **exact GBP pin** to 5+ decimals; area-level examples are not acceptable. Owner action.
2. **Opening hours** — `openingHoursSpecification` not present; must match GBP exactly. Owner action.
3. **`sameAs`** — only emitted when real social/GBP URLs are configured. The uncanonical share URL must **not** be used. Owner action.
4. **`FAQPage`** — deliberately **not** added. Google retired FAQ rich results in 2026, so there is no rich-result benefit; the visible Q&A remains useful for patients and agents. Reconsider only for a documented future surface, with visible-text parity and clinical sign-off.
5. **Ratings/reviews** — never add without genuine, verifiable data.
6. **Clinician detail** — `Person` correctly says only "Aesthetic Physician"; no degrees, registration numbers, or experience years may be added until the owner verifies them.

---

## 8) Location page, corridors and doorway risk

- **One local hub:** `/skin-clinic-mira-bhayander/` is the only local hub. It is now linked (footer + contextual treatment link) and remains self-canonical. It is the correct target for a future GBP "website" link decision.
- **Four treatment pillars:** `/treatment/acne-acne-scars/`, `/treatment/injectables-facial-contouring/` (Botox stays here), `/treatment/hair-loss-hair-thinning/`, `/treatment/pigmentation-uneven-skin-tone/`.
- **Corridor expansion must be restrained:** at most **4–5 genuinely unique** cluster pages (e.g. Bhayander/Kashimira, Borivali–Dahisar, Vasai–Virar, Palghar–Dahanu, Churchgate–Bandra/South Mumbai) **only if** real patient-journey value exists — >60% unique content, real travel directions from the actual station, landmark/parking detail, consented local proof, local FAQ, and a documented swap-test pass. **No page per station** (~30+ stations) and **no branch claims**. Full rules in `PAGE-ARCHITECTURE.md`.

---

## 9) Owner action register (unresolved — the binding constraints)

| # | Owner action | Why it blocks the score |
|---|---|---|
| 1 | Convert `https://share.google/V7HQuDrctGMVpIoii` into a **canonical GBP/Maps URL** in the dashboard; supply the exact URL. Do **not** invent a Place ID. | Unlocks GBP signals (25%) and `sameAs`; required before website integration. |
| 2 | Verify GBP primary category (proposed **Skin Clinic**, subject to actual availability; fallbacks Dermatologist / Medical Clinic — never Beauty Salon), secondary categories, and verification status. | Primary category is the strongest local-pack factor. |
| 3 | Verify and align GBP name, address, PIN 401105, phone, and **hours** with the canonical NAP. | Consistency and eligibility; hours also feed schema. |
| 4 | Supply exact GBP **geo pin coordinates** (5+ decimals) for schema. | Required for correct `geo`; no coordinates may be guessed. |
| 5 | Supply genuine **review** data and launch a compliant acquisition cadence; add markup only from real data. | Unlocks Reviews & reputation (20%). |
| 6 | Supply and upload genuine **photos** (and a post cadence); nothing fabricated. | Local proof and engagement; not measurable here. |
| 7 | Claim/align **citations**: Bing Places, Apple Business/Maps, Facebook, Sulekha, Justdial/Practo "Lumiere Clinic", aggregators. | NAP/citations (15%) and AI-search visibility. |
| 8 | Supply **GBP Insights** (calls, direction requests, website clicks, search terms, last 90 days) and connect GSC/GA4. | Converts this static estimate into measured performance. |

---

## 10) Prioritised next steps

1. **Owner:** complete action register items 1–4 (GBP URL/category/NAP/hours/geo). Everything local depends on them.
2. **Owner + developer:** add verified geo and hours to schema; add `sameAs` only from real URLs. **Do not add `FAQPage` for rich results** (retired in 2026).
3. **Owner:** start the compliant review engine and citation claims (§9 items 5, 7); photos/posts (item 6).
4. **Developer:** publish at most 4–5 unique corridor clusters only after swap-test validation; keep Botox on the injectables URL; keep the one-hub/four-pillar architecture.
5. **Measurement:** connect GSC/GA4 and GBP Insights, run a geo-grid check, then **re-score with real data** and retire this static estimate.

---

## 11) Limitations

- No GSC, GA4, GBP/Insights, geo-grid, rank-tracking, or backlink data. All GBP statements are owner-reported or recommendations, never measurements.
- **~41/100 is a static-signal estimate of on-site and public-web evidence, not measured local performance.**
- Directory access was restricted in the baseline (Justdial 403, Practo 404, Maps fetch unparseable); "not detectable" is not absence.
- Implemented fixes were verified on the **local** WordPress copy via the cited verification artifacts; live production propagation was not re-fetched in this update.
- The share URL was resolved only to a share/search page; no Place ID exists in any artifact.
- Fetched web content was treated as untrusted data; no instructions in it were followed. No site files were modified while producing this document; the implementation was verified separately on the local copy.

**Companion documents:** `GBP-OPTIMIZATION-CHECKLIST.md` (owner/GBP actions) · `PAGE-ARCHITECTURE.md` (URL and internal-linking rules).
