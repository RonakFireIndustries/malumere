# 90-Day SEO Roadmap — Ma Lumière, Mira Road East

**Baseline (supplied audit, 2026-11-23):** Overall SEO Health **56/100** · Local SEO **31/100**.
**Scope:** one clinic, Mira Road East, Maharashtra 401105 · phone **+91 99878 02355** · English only.
**Verified professional description:** "Aesthetic Physician" only · clinician named on site: **Dr. Rishita Ray**.
**Implementation status:** Phase 1–2 work listed below has been applied to the **local copy only** and verified (9/9 checks, JSON-LD parse checks). Production deployment remains an owner/developer action.
**Important:** This roadmap does **not** promise rankings, impressions, clicks or traffic growth. KPIs are measurement points to track once GSC/GA4/GBP data is connected; they are not forecasts.

---

## Phase 1 — Trust, NAP and indexation cleanup (Days 1–7)

**Owner dependencies:** confirm canonical address/PIN (401105 confirmed), grant theme/DB deploy access, audit GBP from the owner's account, supply no invented Place ID or reviews.

**Deliverables**
- Theme + DB backups retained (`ma-lumiere.in-audit/backups/20261123-142646/`).
- Patient-visible placeholders removed from published DB content and templates; results page converted to educational copy with no before/after images or outcome claims.
- One canonical NAP on site: full address with PIN 401105, `+91 99878 02355`, `tel:+919987802355`, address-based Google Maps directions link.
- Nine thin `/treatment-type/*` archives: `noindex,follow`, canonical to matching `/treatment/*` (miscellaneous archive → `/treatments/`), removed from sitemap.
- Unique title/meta overrides for homepage, About, Treatments, Book Consultation, Contact, FAQs, Blog and location page; self-canonicals for blog/archives.
- GBP URL customizer field added and intentionally left empty (share link is not a verified canonical place URL).

**Verification checks**
- `tools/verify-implementation.php` → `findings/implementation-verification.json`: **9/9 PASS**.
- Sitemap has 29 URLs and zero `/treatment-type/` entries; no placeholder strings in published posts.
- Render spot-check of address, `tel:` href and directions link on contact/location pages.

**KPIs once data is available (track, do not forecast)**
- Pages with exact canonical NAP: target 100% of audited templates.
- Patient-visible placeholders remaining: target 0.
- Thin archives indexed in GSC: target 0; canonicalised URLs reported correctly.
- Key pages with unique title and meta description: target 100%.

---

## Phase 2 — Schema and entity foundation (Days 8–21)

**Owner dependencies:** no new credentials needed for this phase; deploy schema code as a set. Do not activate an SEO plugin without re-validating (theme output is suppressed when one is detected).

**Deliverables**
- Separate JSON-LD blocks emitted from `inc/seo.php` and `inc/template-tags.php`: `MedicalClinic`+`LocalBusiness`, `WebSite`, `Person` (jobTitle "Aesthetic Physician"), `WebPage`, `Service`, `BreadcrumbList`; pre-existing `BlogPosting` retained.
- Shared `@id` strategy documented in `SCHEMA-IMPLEMENTATION.md`; no duplicate clinic nodes per page.
- Deliberately omitted: geo coordinates, structured opening hours, Place ID, reviews/ratings, `FAQPage`, `MedicalProcedure`, credentials and citations. **Do not add FAQPage for rich results — Google retired FAQ rich results in 2026.**

**Verification checks**
- `tools/verify-jsonld.php` → `findings/jsonld-verification.json`: 4/4 sampled URLs **VALID**, 0 JSON errors.
- Owner to run Google Rich Results Test and Schema.org validator on production URLs after deploy.
- Confirm `sameAs` contains no unverified share links and no ratings appear in markup.

**KPIs once data is available**
- Sampled URLs with parse-valid JSON-LD: target 100%.
- Unsupported/unverified schema fields present: target 0.
- Rich Results Test errors/warnings recorded per release (track trend).

---

## Phase 3 — Content depth and E-E-A-T (Days 15–45, overlaps Phase 2)

**Owner dependencies:** clinician-supplied degree/specialisation/registration/years in practice, named medical reviewer, dated authoritative citations, written consent for any results imagery. No credential may be inferred or invented.

**Deliverables**
- Four pillars on existing URLs: acne, Botox/injectables (already optimized), hair loss, pigmentation; plus laser hair reduction and pre-wedding skin preparation brought to 700–1,000 words each with unique mechanism, candidacy, contraindications, downtime, risk bounds and cost factors.
- Answer-first 40–60 word summaries under treatment H1s; review dates and sources where a reviewer is named.
- Credentials block and author/reviewer boxes once verified; `BlogPosting` author attribution corrected from `admin`.
- Descriptive internal anchors replacing generic "Learn more"; contextual links between treatments, FAQs, location and consultation.
- Consented, de-identified results images only if supplied; otherwise sections stay educational.

**Verification checks**
- Word count and uniqueness per priority URL; one H1 per page; no heading skips.
- Every credential/citation signed off against owner-supplied evidence.
- Zero `[IMAGE REQUIRED]` / consent-placeholder strings.

**KPIs once data is available**
- Priority pages with unique substantive sections: target 100%.
- GSC impressions and average position for the six priority URLs — record baseline at 30 days, then measure change; no growth is promised.
- Internal links from treatment pages to location, FAQs and booking: track coverage %.

---

## Phase 4 — Local authority, GBP and reviews (Days 30–60)

**Owner dependencies:** GBP dashboard access, canonical Maps URL and Place ID, permission to respond to reviews, directory logins, approval for local PR/outreach.

**Deliverables**
- GBP fully audited: exact name, address, PIN 401105, phone, categories, hours, photos, posts, Q&A, insights.
- Canonical GBP/Maps URL entered in the `ml_gbp_url` customizer field; only then does `sameAs` gain the entry.
- Compliant review cadence on Google/Justdial/Practo/Apple/Bing: no gating, no incentives, no "patient" claims, respond to 100%.
- NAP corrections on Justdial/Practo and other directories; real `sameAs` links only.
- Western Railway corridor architecture: expand the location hub; publish **at most 4–5** genuinely unique cluster pages (Bhayander, Vasai–Virar, Dahisar–Borivali, South Mumbai as candidates). Do **not** create a page per station or swap station names.
- Local digital PR: listings, chamber/community partnerships, real mentions.

**Verification checks**
- GBP fields match canonical NAP exactly; share link is absent from site markup.
- Station-name swap test and >60% unique content test for every corridor page.
- NAP cross-check log across top directories.

**KPIs once data is available**
- NAP consistency across audited directories: target 100%.
- GBP metric baseline: profile views, calls, direction requests, website clicks, photo views.
- Review count, velocity and owner response rate (target 100% response); rating tracked as reported, never manufactured.
- Number of corridor pages: cap 5.

---

## Phase 5 — Technical performance, images and AI readiness (Days 45–70)

**Owner dependencies:** developer for headers/caching, designer/owner for 1200×630 OG image and alt text, confirmation that search/citation crawlers may access the site.

**Deliverables**
- Width/height or CSS `aspect-ratio` on homepage treatment-card images; remove `loading="lazy"` from first Above-the-fold image; descriptive alts for informative images.
- 1200×630 `og:image` / `twitter:image`.
- Security headers (HSTS, `nosniff`, X-Frame-Options, Referrer-Policy, CSP as appropriate) and cache headers (immutable versioned assets, revalidating HTML); critical CSS inline / non-critical deferred.
- Optional short `llms.txt` (H1 + links to location, treatments, FAQs, contact) — not a ranking factor. Skip WebMCP/agent-card files unless real tools exist.
- robots.txt keeps search and citation crawlers allowed.

**Verification checks**
- Re-run page checks for image dimensions, alts and lazy-loading findings.
- PageSpeed Insights / CrUX field data captured per template; header check on production.
- robots.txt and `llms.txt` fetch successfully if added.

**KPIs once data is available**
- Informative images with descriptive alt text: target 100%.
- Mobile field CWV pass rate and LCP/INP/CLS values per template — record, then monitor; no threshold is promised.
- OG image coverage on key shareable URLs: target 100%.
- Crawl errors and 4xx/5xx counts tracked weekly.

---

## Phase 6 — Measurement and governance (Days 1–90; review at 30/60/90)

**Owner dependencies:** GSC and GA4 property access, GBP Insights access, optional DataForSEO/geo-grid tool budget, developer for event tagging.

**Deliverables**
- Connect Google Search Console, GA4, PageSpeed Insights/CrUX and GBP data; record a dated baseline before judging changes.
- Geo-grid rank baseline across Churchgate → Dahanu for six priority services + "skin clinic near me"; repeat monthly.
- Weekly review-velocity/response monitoring and monthly NAP recheck.
- SEO drift baseline captured after Phases 1–2; diff before each deploy. Re-run JSON-LD validation after any schema or SEO-plugin change.

**Verification checks**
- GSC property verified and sitemap submitted; GA4 events present for `tel:` clicks, directions clicks and consultation form starts/completions.
- Baseline snapshots stored in `ma-lumiere.in-audit/findings/` with dates.

**KPIs once data is available**
- GSC: indexed canonical URLs, indexing exceptions, impressions, clicks, CTR, average position (tracked, not targeted).
- GA4: consultation starts, completions, phone-click and direction-click events.
- GBP: views, calls, direction requests, review metrics.
- Geo-grid: visibility grid per service — record baseline and monthly deltas.
- Governance: # deploys with a drift diff, # schema revalidations, # crawl errors.

---

## Phase 7 — Review, reprioritise and hand over (Days 71–90)

**Owner dependencies:** decision-maker to approve next quarter, owner to close outstanding verification items (credentials, GBP canonical URL, hours, coordinates, review evidence).

**Deliverables**
- 90-day review pack: re-run all verification scripts, compare KPIs to the dated baselines, list what changed and what remains unverified.
- Prioritised next-90-day backlog with explicit owner dependencies; no unverified field added to satisfy a check.
- Runbook: deployment order, rollback via `backups/20261123-142646/`, schema rules (including the FAQPage warning), and re-validation commands.
- Re-measure the audit heuristic scores (baseline 56 Overall / 31 Local); report the new numbers without claiming Google endorsement.

**Verification checks**
- All Phase 1–6 checks re-run; 0 unsupported schema types; 0 fabricated credentials, reviews, ratings, coordinates or Place IDs.
- Every open item has a named owner and required evidence.

**KPIs once data is available**
- Change in audit scores vs 56/31 baseline (measured, not promised).
- Count of resolved vs open owner dependencies.
- Completion rate of scheduled verification runs.

---

## Guardrails for all phases

- Do not create station pages with swapped city names — doorway-page risk.
- Do not add `aggregateRating`/`review` schema, testimonials or "patient" claims without real, verifiable, consented reviews.
- Do not publish before/after images without documented consent and de-identification.
- Do not guess PIN, coordinates, hours, credentials, Place ID or GBP categories.
- Do not add `FAQPage` for rich results (retired in 2026); do not add `MedicalProcedure` without clinical evidence and sign-off.
- Do not block search/citation crawlers in robots.txt if AI citability is a goal.
- All ranking/traffic figures are observations to collect, not commitments.
