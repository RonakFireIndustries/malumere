# Full SEO Audit — ma-lumiere.in

**Audit date:** 2026-11-23
**Site:** https://ma-lumiere.in/ (WordPress, 35 sitemap URLs)
**Business:** Ma Lumière — single brick-and-mortar medical aesthetic / skin, hair and laser clinic, Mira Road East, Mumbai (Dr Rishita Ray)
**Vertical:** Healthcare / YMYL — Aesthetic Medicine
**Goal from owner:** Local SEO covering the whole Western Railway corridor (Churchgate → Dahanu Road), English-only
**Scope:** Full-site technical, content, on-page, schema, performance signals, AI readiness, images, local SEO. Baseline inspection plus subsequent fixes implemented and verified on a **local XAMPP copy only**; nothing was deployed to production.

---

## Executive Summary

| Score | Result |
|---|---|
| Score | Baseline (2026-11-23) | Current static estimate (local implementation) |
|---|---:|---:|
| **Overall SEO Health Score** | **56 / 100** | **~65 / 100** |
| **Local SEO Score (goal-specific)** | **31 / 100** | **~41 / 100** |

> Both current figures are **static-signal estimates of the local copy**, not measured rankings, traffic, conversions or local-pack performance. No GSC, GA4, GBP Insights, CrUX or geo-grid data was available. See `LOCAL-SEO-ANALYSIS.md` for the local re-score methodology.

The site has a **sound technical foundation** — HTTPS, clean redirects, valid robots.txt and sitemap, self-canonicals on primary pages, server-rendered content, parseable JSON-LD, and a genuinely useful doctor narrative and location page. The problems are concentrated in three areas that matter most for this business: **unfinished placeholder content, almost no local/review/entity proof, and thin duplicate taxonomy pages**. For a clinic whose growth depends on Western Railway corridor searches, the local score of 31 is the binding constraint, not the technical score.

**Business type detected:** Brick-and-mortar (single location), with service-area framing in copy.
**Industry vertical detected:** Healthcare — medical aesthetic / skin clinic (YMYL). Industry-specific checks applied: physician entity, credentials/registration, medical citations, consent-safe results imagery, no fabricated ratings.

### Implementation status (local copy, not deployed)

**Completed and verified:** patient-visible placeholders removed from the database and theme templates; results URL converted to an educational page with no patient images or outcome claims; canonical `401105` address and `+91 99878 02355` phone applied; address-based Google Maps directions link added; location page linked from the footer and treatment pages; unique titles/metas/canonicals added to key pages; nine thin `/treatment-type/*` archives set to `noindex,follow`, canonicalised and removed from the sitemap; Botox pillar optimized on the existing injectables URL; validated `MedicalClinic`+`LocalBusiness`, `WebSite`, `Person` ("Aesthetic Physician"), `WebPage`, `Service` and `BreadcrumbList` JSON-LD, with existing `BlogPosting` retained.

**Evidence:** `findings/implementation-verification.json` (9/9 PASS), `findings/jsonld-verification.json` (4/4 sampled URLs VALID), `tools/verify-pages.ps1`, and 51-file PHP lint. **Rollback:** `backups/20261123-142646/theme-ma-lumiere.zip` plus `backups/20261123-142646/database.sql`.

**Still owner-gated:** canonical GBP URL/Place ID, GBP category/hours/geo, genuine reviews and photos, directory NAP corrections, verified credentials/citations, real CWV data, and production deployment. **FAQPage markup was deliberately not added** because Google retired FAQ rich results in 2026; the visible FAQs remain.

### Baseline top 5 critical / high issues

1. **Critical — Live placeholder case study.** `/results/acne-treatment-case-reference/` contains `[REAL, CONSENT-APPROVED CASE REQUIRED …]` in the body and meta description (39 words). It is indexed in the sitemap. This is visible to patients and undermines trust.
2. **Critical — GBP exists but the website has zero local-pack integration.** No map embed, directions link, place ID, GBP URL or review widget on any checked page. The FAQ falsely claims the contact page has a Google Maps directions link.
3. **Critical — NAP inconsistency and PIN conflict.** Site uses "MA LUMIÈRE"/"Ma Lumière"; Justdial/Practo snippets show "Lumiere Clinic". Phone lacks `+91`. PIN is 401105 on site vs 401107 in several third-party sources — must be verified, not guessed.
4. **High — Thin duplicate `/treatment-type/*` pages** (32–64 words, no canonical, "[IMAGE REQUIRED]" text) are in the sitemap and compete with the richer `/treatment/*` pages.
5. **High — Almost no entity/E-E-A-T proof.** No doctor credentials, registration, reviewer attribution or clinical citations; no `Person`, `FAQPage`, `MedicalWebPage`, geo, hours or `areaServed` schema. No on-site reviews or ratings.

### Baseline quick wins — implementation status

| Baseline quick win | Status on local copy | Remaining owner action |
|---|---|---|
| Remove live placeholder strings / block the results page | **Done** — placeholders removed; page is now educational and indexable | Decide whether to keep it indexed or `noindex`; publish images only with documented consent |
| One canonical NAP + directions link | **Done on site** — `401105`, `+91 99878 02355`, `tel:+919987802355`, address-based directions | Confirm GBP/directories match; obtain canonical GBP URL |
| Link the orphaned location page | **Done** — footer + contextual treatment-page links | Verify links after deployment; consider primary nav if desired |
| Expand entity schema | **Done for verified fields** — separate linked JSON-LD nodes; no fabricated facts | Supply geo, hours, verified credentials/citations and real social/GBP URLs |
| Fix metas, duplicate titles and archive canonicals | **Done** — key pages have unique titles/metas, canonicals and archive consolidation | Review wording; expand treatment-page substance |

---

## Score breakdown

| Category | Weight | Score | Weighted | Basis |
|---|---:|---:|---:|---|
| Technical SEO | 22% | 79 | 17.38 | Valid robots/sitemap, 200s, self-canonicals, HTTPS, SSR; duplicate thin archives and missing headers |
| Content Quality | 23% | 45 | 10.35 | Good doctor/location narrative; thin templated treatment copy, no E-E-A-T credentials, placeholder case page |
| On-Page SEO | 20% | 47 | 9.40 | One H1/page, but 9 generic anchors, missing metas, duplicate titles, weak internal linking |
| Schema / Structured Data | 10% | 42 | 4.20 | 0 invalid JSON-LD blocks; but entity depth almost absent sitewide |
| Performance (CWV) | 10% | 55* | 5.50 | Static-signal estimate only: image dimensions, lazy above-the-fold image, render-blocking CSS, no cache headers |
| AI Search Readiness | 10% | 64 | 6.40 | Permissive robots, clean a11y tree, direct answers; no llms.txt, thin entity data, no AI visibility measurement |
| Images | 5% | 60 | 3.00 | Alt coverage mostly fine and small file sizes; 8/12 home images lack dimensions, empty alts on informative images, no og:image |
| **Total** | **100%** | — | **56.23 → 56** | **Overall SEO Health Score** |

\* Performance cannot be field-measured in this environment (no PageSpeed Insights / CrUX / GSC credentials, PSI returned 429). The 55 is an estimate from static HTML/header signals, not a measured LCP/INP/CLS score.

**Post-implementation static estimate (local copy, not measured):**

| Category | Weight | Baseline | Estimate after fixes | Change driver |
|---|---:|---:|---:|---|
| Technical SEO | 22% | 79 | 84 | Archive consolidation, sitemap reduced to 29 URLs, location link, canonicals; headers/cache and dimensions still open |
| Content Quality | 23% | 45 | 50 | Placeholders removed, educational results copy, Botox pillar expanded; credentials/citations and depth still open |
| On-Page SEO | 20% | 47 | 66 | Unique key-page titles/metas, canonicals, location link; generic anchors remain |
| Schema / Structured Data | 10% | 42 | 68 | Clinic/WebSite/Person/WebPage/Service graph with `@id`; no geo/hours/reviews/FAQPage added |
| Performance (estimated) | 10% | 55 | 55 | No field data and no performance-specific code changes |
| AI Search Readiness | 10% | 64 | 68 | Stronger entity data and internal links; no `llms.txt` or measured agent data |
| Images | 5% | 60 | 64 | Patient-visible placeholder sections removed; og:image fallback added; dimensions/descriptive alts still open |
| **Weighted total** | **100%** | **56.23 → 56** | **65.48 → ~65** | **Static estimate only** |

**Separate goal score:** Local SEO **31/100 baseline → ~41/100 static estimate** after the implemented on-site fixes. GBP and reviews dominate the remaining gap. See `LOCAL-SEO-ANALYSIS.md` (updated) and `LOCAL-SEO-ANALYSIS-ma-lumiere.in.md` (baseline).

---

## Technical SEO — 79/100

**What works:** `robots.txt` returns 200 with `Allow: /` and a declared sitemap; sitemap is a valid 35-URL `urlset` with 0 invalid/duplicate/cross-host URLs; all 10 sampled pages are HTTP 200 with self-canonicals and `max-image-preview:large`; HTTP→HTTPS and www→apex are single 301 hops; valid TLS 1.3 cert; server-rendered content with raw/rendered parity; lightweight HTML (63.6 KB homepage, 531 DOM nodes); viewport meta on all pages; JSON-LD parseable on all 10 pages.

| Severity | Finding | Evidence | Fix |
|---|---|---|---|
| High | Thin duplicate `/treatment-type/*` pages in sitemap, no canonical | e.g. acne 64 words vs `/treatment/acne-acne-scars/` 397 words; laser hair reduction 32 words; pre-wedding 34 words | Canonical to `/treatment/*` equivalent, or noindex + remove from sitemap |
| High | Orphaned money page and hubs | `/skin-clinic-mira-bhayander/`, `/blog/`, results page not linked from sampled nav/hubs | Add crawlable nav/footer/contextual links |
| Medium | Missing HSTS/CSP/nosniff/X-Frame-Options/Referrer-Policy | `transport-check.json`: all absent | Add after confirming subdomain HTTPS posture (trust, not a confirmed ranking factor) |
| Medium | No cache headers on HTML or versioned assets | Cache-Control/ETag/Expires null on tested URLs | Long immutable cache on versioned CSS/JS; short revalidating cache on HTML |
| Medium | Missing image dimensions; lazy above-the-fold image on `/about/` | 8/12 home, 11/19 treatments, 4/5 acne page; `first-image-lazy-loaded` on about | Add width/height or `aspect-ratio`; remove lazy from hero |
| Medium | Missing meta descriptions on 5 key pages | about, treatments, FAQs, contact, booking | Unique 120–160 char descriptions |
| Medium | Render-blocking CSS/font chain | 3 external stylesheets + 5 inline style blocks per page | Inline critical CSS, defer non-critical, consider self-hosting fonts |
| Low | og:image missing on 8/10 pages; short/long titles; `en-US` locale; sitemap `lastmod` gaps; declared sitemap path 301s to slash | See `findings/technical.md` | Fix incrementally |

**Implemented locally:** all nine `treatment_category` archives are `noindex,follow`, canonicalised to the matching treatment page (miscellaneous archive → `/treatments/`) and removed from the sitemap; sitemap now has 29 URLs. Canonical and robots output was added for singular views, blog index and archives; `/skin-clinic-mira-bhayander/` is linked from the footer and treatment pages. **Still open:** security/cache headers, image dimensions and render-blocking CSS.

Full detail: `findings/technical.md`.

## Content Quality — 45/100

| Dimension | Score |
|---|---:|
| Content depth & uniqueness | 11/25 |
| E-E-A-T / YMYL | 6/20 |
| Keyword & local coverage | 9/15 |

Homepage (1,114 words) and the location page (576 words) are reasonable. All 6 priority treatments exist as dedicated URLs with medically sensible sequencing (active acne before scars, patch test before laser, staged pigmentation). However, the six priority treatment pages are 364–397 words and share an identical 11-section skeleton (~84 shared 5-word sequences); the 9 `/treatment-type/*` archives are 41–64 words; the results page is a 39-word placeholder. Dr Rishita Ray is named but has no degree, registration, years of experience, reviewer attribution or citations. Blog author is `admin` (Organization). Corridor terms beyond Mira/Bhayander/Dahisar/Borivali/Thane are absent.

**Key fixes:** publish verified credentials and `lastReviewed`/`reviewedBy`; add 1–2 authoritative references per condition page; expand money pages to 700–1,000 words with condition-specific mechanism, candidacy, contraindications, downtime, risks and cost factors; replace the results placeholder.

**Implemented locally:** the results URL now contains educational acne-planning copy with no patient photos, testimonials or outcome promises; all patient-visible placeholder strings were removed. The Botox/injectables pillar was expanded on its existing URL with cautious assessment, suitability and aftercare copy. **Still open:** verified credentials, clinical citations, reviewer attribution and 700–1,000 word depth on the four pillars.

Full detail: `findings/content-schema.md`.

## On-Page SEO — 47/100

| Dimension | Score |
|---|---:|
| On-page metadata | 9/20 |
| Internal linking | 5/10 |

One H1 per page on all 18 sampled URLs, no heading skips on treatment pages. But home title is 83 chars, About/FAQs/Contact/Treatments titles are 17–23 chars, 5 pages have no meta description, `/treatment/*` and `/treatment-type/*` share identical titles and H1s, and canonicals are missing on blog index and archives. Homepage has 9 "Learn more" anchors; treatment pages link to thin archives but not to the location page, FAQs or results page.

**Implemented locally:** unique localised title/meta overrides on homepage, About, Treatments, Booking, Contact, FAQs, Blog and the location page; the four pillars have revised titles, H1s and descriptions; canonicals are emitted for singular views, archives and the blog index; treatment pages now link contextually to the location hub. **Still open:** replacing generic "Learn more" anchors and adding broader FAQ/location cross-links.

## Schema / Structured Data — 42/100

JSON-LD parses cleanly (0 invalid blocks across 18 pages) and `BreadcrumbList` exists on inner pages. But only the homepage declares a clinic entity, with `name`, `url`, `logo`, `telephone`, `email` and a single free-text `streetAddress` — no `addressLocality`, `postalCode`, `geo`, `openingHoursSpecification`, `areaServed`, `@id`, `sameAs`, `Physician`/`Person`, `FAQPage`, `MedicalProcedure` or `MedicalWebPage`. Blog `author` is Organization `admin`.

**Implemented locally:** separate, linked JSON-LD blocks now emit `MedicalClinic`+`LocalBusiness` with `@id`, structured `PostalAddress` (PIN `401105`), `areaServed` and address-based `hasMap`; `WebSite`; `Person` with only `jobTitle: Aesthetic Physician`; `WebPage`; `Service` on treatment pages; existing `BreadcrumbList` and `BlogPosting` retained. Four sampled URLs parse as VALID with 0 JSON errors (`findings/jsonld-verification.json`).

**Deliberately omitted:** geo coordinates, structured hours, Place ID, reviews/ratings, `FAQPage`, `MedicalProcedure` and unverified credentials. **Do not add** `aggregateRating`/`review` without genuine data. **Do not add `FAQPage` for rich results** — Google retired FAQ rich results in 2026. The older `@graph` example in `findings/content-schema.md` is a baseline proposal, not the shipped implementation; see `SCHEMA-IMPLEMENTATION.md`.

## Performance (estimated) — 55/100

No field data available: PSI/CrUX/GSC unconfigured and PSI returned 429. Static signals: small image bytes (hero ~64 KB, portrait ~147 KB), correct hero handling (`fetchpriority=high`, not lazy), but 8/12 homepage images lack intrinsic dimensions, `/about/` lazy-loads its first image, 3 external stylesheets + 5 inline style blocks block render, and there are no cache headers. Treat 55 as a **risk estimate**, not a measurement.

## AI Search Readiness — 64/100

Agentic access 80, AI citability/entity clarity 55, SXO/intent 60. Positives: server-rendered, real 404s, permissive robots.txt (search/citation crawlers not blocked), clean accessibility tree (0 unnamed interactive nodes, 56 links, 7 buttons, semantic landmarks), direct quotable answers in FAQs, GBP deprecation lint clean. Gaps: no `llms.txt`, no Markdown negotiation, no `/.well-known` discovery documents, thin entity data, no `lastReviewed`/citations, and the location page is hidden from navigation. `llms.txt` and WebMCP are community proposals/drafts with no confirmed Google Search effect — add `llms.txt` as a low-cost discovery aid, not a ranking play.

Full detail: `findings/ai-agentic-images.md`.

## Images — 60/100

12 homepage images with 0 missing alt (the 1 empty alt is a decorative logo mark inside a named link); file sizes modest. Issues: 8/12 homepage images lack width/height (CLS risk); informative treatment-page images have `alt=""`; no `og:image` on 8/10 pages; raster-only with no `srcset`/WebP/AVIF. The **live before/after placeholder text was removed locally**; no patient images, testimonials or results are published. Informative treatment-image alts, intrinsic dimensions and a real 1200×630 OG image remain open.

## Local SEO — 31/100 (goal-specific)

| Dimension | Weight | Score |
|---|---:|---:|
| GBP signals | 25% | 30 |
| Reviews & reputation | 20% | 15 |
| Local on-page SEO | 20% | 62 |
| NAP consistency & citations | 15% | 25 |
| Local schema | 10% | 30 |
| Local link & authority | 10% | 10 |

**Corridor strategy (Churchgate → Dahanu Road):** do **not** create one thin page per station. That pattern fails the swap test and risks doorway-page penalties. Use: **one local hub** (the existing `/skin-clinic-mira-bhayander/`, expanded with a genuine "Areas we serve / Western Railway corridor" section), **six existing service spokes** with locality modifiers in titles/metas, and **at most 4–5 genuinely unique cluster pages** (>60% unique content) built around real patient-journey information — e.g. travel from Bhayander/Vasai-Virar to Mira Road, station-adjacent consultation planning, area-specific FAQs and photos. Every cluster page must survive the swap test: if swapping the station name leaves the copy valid, it is a doorway page.

GBP recommendations: primary category "Skin Clinic" (fallback Dermatologist / Medical Clinic — verify India labels in the dashboard), never Beauty Salon; add secondary categories; keep a steady review cadence with no gaps beyond ~2–3 weeks, 4.5+ stars and 100% owner responses (never state that a reviewer is a patient — HIPAA-style caution and Google policy). Claim Bing Places (feeds ChatGPT/Copilot), Apple Business/Maps, Facebook, Sulekha, and correct the Justdial/Practo "Lumiere Clinic" entries.

**Updated locally:** canonical on-page NAP with PIN `401105`, `+91` phone/`tel:` link and address-based directions link; structured local schema; location page linked; thin archives de-indexed. Static local estimate is **~41/100**; the GBP (25%) and reviews (20%) blocks remain owner-gated. Full detail: `LOCAL-SEO-ANALYSIS.md` (updated), `LOCAL-SEO-ANALYSIS-ma-lumiere.in.md` (baseline) and `findings/local.md`.

---

## Limitations

- **No Google API credentials, DataForSEO/MCP, or GBP API** are configured here. Real GSC impressions/clicks/indexation, GA4 organic traffic, GBP Insights/categories/reviews/photos, geo-grid local-pack positions, Domain Authority and full backlink data could not be measured. Connect credentials or export the data to close these gaps.
- PageSpeed Insights returned 429 (anonymous quota); no local Chrome/Lighthouse was available, so no LCP/INP/CLS field or lab numbers and no Lighthouse Agentic fraction.
- 18 of 35 sitemap URLs were checked in depth; the other treatment/archive pages are assumed to follow the same templates but were not individually verified.
- Orphan analysis sampled 4 hub pages, not a full crawl.
- `page_checks.py` severities (e.g. 60-char title, 200-word thin content) are heuristics, not published Google rules.
- PIN **401105** was adopted as the canonical value per owner instruction; third-party sources showing `401107` still need directory correction. No coordinates, credentials, ratings, reviews, Place ID or patient images have been invented or published.
- All fetched web content was treated as untrusted data; no instructions found in it were acted upon.

## Artifacts

- `FULL-AUDIT-REPORT.md` — this report (baseline + implemented-state update)
- `ACTION-PLAN.md` — phased plan with implementation status
- `audit-data.json` — structured envelope for report generation
- `CHANGELOG.md` — implementation log and rollback record
- `DEPLOYMENT-AND-OWNER-ACTIONS.md` — deployment runbook and owner task list
- `LOCAL-SEO-ANALYSIS.md` — updated local re-score (~41/100)
- `LOCAL-SEO-ANALYSIS-ma-lumiere.in.md` — original local baseline (31/100)
- `SCHEMA-IMPLEMENTATION.md`, `PAGE-ARCHITECTURE.md`, `GBP-OPTIMIZATION-CHECKLIST.md`
- `keyword-research.csv`, `page-content-recommendations.md`, `90-day-content-calendar.md`, `90-DAY-ROADMAP.md`, `seo-titles-meta.csv`
- `findings/implementation-verification.json`, `findings/jsonld-verification.json`
- `findings/technical.md`, `findings/content-schema.md`, `findings/local.md`, `findings/ai-agentic-images.md`
- `findings/*.json` — raw page checks, render/accessibility data, agentic checks, plus `html-cache/` and extracted analysis files
