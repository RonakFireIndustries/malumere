# SEO Action Plan — ma-lumiere.in

**Audit date:** 2026-11-23  
**Baseline:** Overall SEO Health **56/100** · Local SEO **31/100**  
**Current static estimate after local implementation:** Overall **~65/100** · Local **~41/100**  
**Deployment status:** Implemented and verified on a local XAMPP copy only. **No production deployment has been performed.**

> Scores are static-signal estimates, not measured rankings, traffic, conversions or local-pack performance. No GSC, GA4, GBP Insights, CrUX or geo-grid data was available. The original ~70+ / ~55+ target is not achievable through website edits alone because GBP (25% of local score) and reviews (20%) require owner action.

**Legend:** **[DONE LOCAL]** verified on the local copy · **[OWNER]** requires owner/GBP/directory input · **[DEFER]** intentionally not implemented without evidence.

---

## Phase 1 — Critical fixes

| # | Action | Status | Evidence / remaining work |
|---|---|---|---|
| 1 | Remove patient-visible placeholder strings and convert the results URL to educational copy; publish no patient images or outcome claims | **[DONE LOCAL]** | DB post 15 and result/before-after templates updated; rendered spot-checks show 0 placeholders. Owner may decide whether to keep the educational page indexed or `noindex` it. |
| 2 | Audit GBP category, exact name, address, PIN, phone, hours, photos, posts, Place ID and Insights | **[OWNER]** | No GBP access; supplied `share.google` link is not a canonical Maps URL. Use `GBP-OPTIMIZATION-CHECKLIST.md`. |
| 3 | Define one canonical NAP and align directories | **[DONE LOCAL]** on site; **[OWNER]** for citations | Site now uses PIN `401105`, full verified address and `+91 99878 02355`. Justdial/Practo and other citations still need owner correction. |
| 4 | Add click-to-call and a real directions link | **[DONE LOCAL]** | `tel:+919987802355` on contact/footer/treatment/appointment templates; address-based Google Maps directions URL generated in `inc/helpers.php`. **No Place ID invented.** |
| 5 | Link the orphaned location page | **[DONE LOCAL]** | Footer link plus contextual treatment-page link added for `/skin-clinic-mira-bhayander/`; verify after deployment. |
| 6 | Consolidate nine thin `/treatment-type/*` archives | **[DONE LOCAL]** | `noindex,follow`, canonicals to matching treatment pages (miscellaneous archive → `/treatments/`), removed from sitemap; sitemap has 29 URLs. |
| 7 | Fix missing metas, duplicate titles and archive/blog canonicals | **[DONE LOCAL]** | Unique title/meta overrides on key pages and four pillars; canonical/robots output added in `inc/seo.php`. |

---

## Phase 2 — High-impact improvements

| # | Action | Status | Evidence / remaining work |
|---|---|---|---|
| 8 | Expand verified entity schema | **[DONE LOCAL]** for verified fields; **[OWNER]** for omitted fields | `MedicalClinic`+`LocalBusiness`, `WebSite`, `Person` ("Aesthetic Physician"), `WebPage`, `Service`, `BreadcrumbList`; existing `BlogPosting` retained. No geo, hours, Place ID, reviews, credentials or `FAQPage` added. See `SCHEMA-IMPLEMENTATION.md`. |
| 9 | Add verified credentials, reviewer attribution and clinical citations | **[OWNER]** | Nothing inferred or invented. Clinician remains "Aesthetic Physician" only. |
| 10 | Expand priority treatment content | **Partly [DONE LOCAL]** | Botox/injectables pillar expanded on the existing URL; acne, hair loss and pigmentation titles/H1s/metas revised. 700–1,000 word depth, contraindications, risk bounds and citations remain **[OWNER/clinical reviewer]**. |
| 11 | Launch a compliant review cadence | **[OWNER]** | No fake/incentivised/gated reviews; no `aggregateRating`/`review` markup until genuine data exists. |
| 12 | Handle patient results imagery | **[DONE LOCAL]** hide placeholders; **[OWNER]** decision | No patient images/testimonials published. Publish only with documented consent and de-identification, or leave sections educational. |
| 13 | Image hygiene and OG image | **Partly [DONE LOCAL]** | Placeholder image text removed and a logo/OG fallback added. Intrinsic dimensions, descriptive alts, lazy-above-the-fold fix and a real 1200×630 OG image remain **[OWNER/dev]**. |
| 14 | Security/cache headers and critical CSS | **[DEFER]** | Requires hosting/dev confirmation; not part of this local theme change set. |
| 15 | Descriptive internal anchors | **Partly [DONE LOCAL]** | Location hub and treatment cross-links added; generic "Learn more" anchors remain **[OWNER/content]**. |

---

## Phase 3 — Corridor authority and content

| # | Action | Status | Evidence / remaining work |
|---|---|---|---|
| 16 | Claim/align Bing Places, Apple Business/Maps, Facebook, Sulekha, Justdial/Practo, aggregators | **[OWNER]** | Only real `sameAs` URLs may be added; the unverified share link is not used. |
| 17 | Build the corridor architecture | **Foundation [DONE LOCAL]**; page build **[OWNER]** | One local hub and four pillars retained. At most 4–5 genuinely unique cluster pages, only if they pass the >60% unique-content and station-name swap test. No page per station, no doorway pages, no branch claims. See `PAGE-ARCHITECTURE.md`. |
| 18 | Local digital PR / real mentions | **[OWNER]** | No fabricated listings or citations. |
| 19 | Reviewed blog cadence | **[OWNER]** | Use `90-day-content-calendar.md` and `keyword-research.csv`; all keyword volumes/difficulties are labelled ESTIMATE. |

---

## Phase 4 — Measurement and governance

| # | Action | Status | Evidence / remaining work |
|---|---|---|---|
| 20 | Connect GSC, GA4, PSI/CrUX and GBP Insights | **[OWNER]** | Required to replace static estimates with measured performance. |
| 21 | Geo-grid rank tracking across the corridor | **[OWNER]** | Track six priority services plus "skin clinic near me"; do not forecast results. |
| 22 | Review velocity, response rate and NAP rechecks | **[OWNER]** | Weekly/monthly process; no data available here. |
| 23 | SEO drift baseline and schema revalidation | **Partly [DONE LOCAL]** | Verification artifacts retained; add a production baseline after deployment and revalidate after each release. |
| 24 | Optional `llms.txt` discovery aid | **[DEFER]** | Low-cost community proposal, not a ranking factor; skip WebMCP/agent cards unless real tools exist. |

---

## What NOT to do

- Do not create 30+ station pages with swapped city names — doorway-page risk.
- Do not claim branches, extra clinics or GBP pins where no physical clinic exists.
- Do not add `aggregateRating`/`review` schema, fake testimonials, patient photos, Place IDs, ratings or clinical results.
- Do not guess PINs, geo coordinates, credentials, registration numbers or GBP categories. PIN `401105` is adopted per owner instruction; correct third-party `401107` listings instead.
- Do not add `FAQPage` markup for rich-result benefit — Google retired FAQ rich results in 2026. Visible FAQs remain.
- Do not deploy directly to production without owner approval and a verified backup/rollback path.

---

## Acceptance checks before deployment

1. Re-run `tools/verify-implementation.php` → expect **9/9 PASS**.
2. Re-run `tools/verify-jsonld.php` → expect **4/4 sampled URLs VALID**, 0 errors.
3. Re-run `tools/verify-pages.ps1` and confirm **5/5 200 responses**, canonicals/robots, correct titles/metas and **0 placeholders**; retain output as `findings/page-verification.txt`.
4. Run PHP lint across `wp-content/themes/ma-lumiere` → expect `PHP_LINT_OK 51 files`.
5. Confirm sitemap has 29 URLs and zero `/treatment-type/` entries.
6. Confirm no verified-only field was populated with a guess: GBP URL, Place ID, geo, hours, reviews, credentials, patient assets.
7. Deploy theme files and database changes as one controlled batch; verify production URLs, then take a fresh production backup and SEO-drift baseline.
