# Ma Lumière — AI search readiness, agentic access, SXO & images

**Site:** https://ma-lumiere.in/ (WordPress, skin/hair/laser clinic, Mira Road East, Mumbai)
**Audit date:** 2026-11-23
**Area owner:** AI Search / Agentic Access / SXO / Images subagent
**Scope:** homepage plus `/skin-clinic-mira-bhayander/`, `/treatment/acne-acne-scars/`, `/faqs/`, `/results/acne-treatment-case-reference/`, `/about/`, `/treatments/`, `/contact/`

## Overall score for this area: 64/100

Component view (judgement, not a vendor score): agentic access 80/100; AI citability / entity clarity 55/100; SXO & intent match 60/100; images & results credibility 55/100.

The site is technically friendly to agents — server-rendered, reachable robots.txt, real 404s, and a clean accessibility tree with no unnamed interactive elements — but it is held back by unfinished before/after sections, thin entity/author structured data, no Markdown or `llms.txt` discovery layer, a location page that is absent from site navigation, and generic image/link semantics.

## What works

- **Lighthouse Agentic Browsing (measured? no):** PSI returned HTTP 429 (shared anonymous quota) for both mobile and desktop. No Google API key or local Chrome/Lighthouse CLI was available, so **no `X/N` fraction can be reported**. This is a tooling limitation, not a site finding.
- **Local Agent-UX heuristic: 100/100** on the homepage and on `/treatment/acne-acne-scars/` (`agent_ux_check.py`). Both had a present accessibility tree, 0 unnamed interactive nodes, 0 `div onclick` widgets, semantic landmarks, and labelled inputs. This is a local heuristic, separate from Lighthouse.
- **P0 agent basics pass:** `server-rendered` (1,239 words without JS, no SPA shell) and `robots-reachable` (HTTP 200, one group) both pass. The unknown-URL probe returns a real 404 (no catch-all 200).
- **robots.txt is permissive and simple:** `User-agent: * / Allow: /` plus a sitemap line. AI training, search and user-fetch crawlers all fall through to `*` and are allowed at root; no named group blocks GPTBot, ClaudeBot, PerplexityBot, Google-Extended, CCBot, OAI-SearchBot, Claude-SearchBot, ChatGPT-User, Claude-User, Perplexity-User or Google-Agent.
- **Accessibility tree quality:** homepage tree has 56 accessible links, 7 buttons, 2 named images, landmarks `banner`, `main`, `contentinfo`, two `navigation` regions, and 0 empty-named links/buttons/images. FAQ accordions expose each question as both a heading and a named button.
- **Direct answers exist:** homepage and `/faqs/` answer booking, location, Bhayander travel, first consultation, and cost in plain quotable sentences. Treatment pages carry concrete facts (e.g. acne scars "4–6" sessions, downtime "None–48 hours").
- **Doctor attribution is present in copy:** "Dr. Rishita Ray, Founder & Aesthetic Physician" with a first-person philosophy section on homepage and `/about/`. Results page includes "Shared with consent. Individual outcomes vary."
- **GBP deprecation lint is clean:** `gbp_deprecation_lint.py` on the homepage returned `ok: true`, 0 findings (no deprecated chat/business.site markup).
- **Image bytes are modest and the hero is handled correctly:** HEAD checks show `hero.jpg` ~64 KB, `hero-bg.jpg` ~102 KB, `doctor-portrait.jpg` ~147 KB, treatment card ~40 KB, Pexels image ~87 KB, logo PNG ~31 KB. The first (hero) image is not lazy but carries `fetchpriority="high"` and `decoding="async"`, which is correct for an LCP candidate — the page-check "first image not lazy" flag is not a defect here.
- **Homepage alt coverage:** 12 images, 0 missing `alt`; the one empty alt is the decorative logo mark inside a link that has a real accessible name ("MA LUMIÈRE SKIN | HAIR | LASER").
- **Page types mostly match local YMYL intent:** dedicated location page, 15 treatment pages, FAQs, booking and contact pages are all in the sitemap and reachable.

## Findings table

| Severity | Issue | Evidence | Recommendation |
|---|---|---|---|
| **High** | Unfinished before/after placeholders are live on patient-facing pages; no real results images exist anywhere in the sampled site. Consent language exists, but the placeholder undercuts it. | `/treatment/acne-acne-scars/` rendered text contains `[BEFORE/AFTER IMAGES REQUIRED]` in a `div.ph` inside the "Real results" section; `/results/acne-treatment-case-reference/` contains `[REAL, CONSENT-APPROVED CASE REQUIRED — before/after images must be uploaded here and must not reveal patient identity.]` preceded by "Shared with consent. Individual outcomes vary." Neither page contains a results image (only the logo). | Publish de-identified, explicitly consented case images with descriptive alts and a visible consent/disclaimer line, or remove both sections until real assets exist. Link the case from the treatment page's "Real results" block. |
| **High** | Entity and author structured data are too thin for a YMYL health clinic; no machine-readable doctor/author or FAQ coverage. | Homepage has **1** JSON-LD block (~500 bytes): `MedicalClinic` + `HealthAndBeautyBusiness` with only `name`, `url`, `logo`, `telephone`, `email`, `address` (one free-text `PostalAddress`, no `postalCode`/`geo`). No `@id`, `sameAs`, `openingHours`, `priceRange`, `areaServed`, `founder`, `physician`, `image`. No `Person`/`Physician`, `FAQPage` or `MedicalWebPage` (0 occurrences in homepage HTML); `/faqs/`, location, treatment and results pages have only `BreadcrumbList`. | Expand `MedicalClinic` with `@id`, postal code, geo, opening hours, `areaServed`, `image`, `sameAs` (GBP/maps/social). Add `Person` for Dr. Rishita Ray with credentials and `worksFor`, plus `reviewedBy`/`author` on medical copy. Add `FAQPage` only where the Q&A is visibly rendered. |
| **Medium** | No LLM discovery layer: `llms.txt`, Markdown delivery and `/.well-known` documents all absent. | `agentic_check.py`: `/llms.txt` 404 (HTML, 28 KB; `/llms-full.txt` also 404); `Accept: text/markdown` returns HTML 200 with `Vary: Accept-Encoding` (no `Accept` variation), no alternates, `/index.md` 404. `/.well-known/api-catalog`, `/oauth-*`, `/agent-card.json`, `/ucp` and `/.well-known/ai-catalog.json` all 404. | Optional P1/P3, not a Google Search factor. Add a small `llms.txt` (H1 + Markdown links, per the community proposal) and/or tested `Accept: text/markdown` negotiation with `Vary: Accept`. Skip API/OAuth/UCP/agent-card files unless those services exist. |
| **Medium** | Location page is effectively hidden from navigation. | Homepage raw HTML has **0** occurrences of `skin-clinic-mira-bhayander`; primary nav and footer list Home/About/Treatments/FAQs/Contact, treatment links and Privacy only. `/contact/` markdown likewise has no link to it. The page exists only via sitemap. | Add a visible "Skin clinic – Mira Road / Bhayander" link in primary nav and footer, and contextual links from homepage (FAQ answer "Where is Ma Lumière…?"), contact page and relevant treatment pages. |
| **Medium** | 8 of 12 homepage images lack intrinsic dimensions; lab CLS not measured. | `homepage-pagechecks.json`: `missing_dimensions: 8`. Raw HTML shows the 8 treatment-card images (`hair-loss`, `pre-wedding`, `acne`, `laser-hair-reduction`, `pigmentation`, `laser-tattoo-removal`, `healthy-aging`, `injectables`) have no `width`/`height`; hero and doctor portrait do. | Add `width`/`height` to those 8 `<img>` tags or enforce a CSS `aspect-ratio` on the card media box. Re-check CLS when Lighthouse/PSI is available; target ≤ 0.1. |
| **Medium** | Empty alts on informative treatment-page images. | `/treatment/acne-acne-scars/` has 5 images, 4 with `alt=""`: decorative logo (acceptable), two Pexels photos in `<figure class="td-row__media">` that illustrate "How it works" / "Who it is for", and a repeated `acne-acne-scars.jpg` in a lower section where the hero copy uses `alt="Acne & Acne Scars"`. | Give the Pexels/editorial images concise descriptive alts, or explicitly mark purely decorative figures as presentation. Keep the logo empty only because the parent link is named. |
| **Medium** | YMYL citability gap: named doctor but no credentials, no reviewed-by, no cited clinical sources, vague pricing. | Homepage/`/about/` say "Aesthetic Physician" with no degree, registration number or speciality body; treatment copy contains claims such as "scar treatment is planned only after skin is stable" with no reference; cost answer is "Cost depends on the treatment… A quotation is given after your consultation." No statistics with sources in sampled content. | Add verifiable qualifications/registration, a "medically reviewed by Dr. Rishita Ray" line with date, 1–2 authoritative references per condition page, and a starting-price band or clear "from ₹X, quoted after assessment" if commercially possible. Never fabricate credentials or stats. |
| **Low** | robots.txt declares no deliberate AI purpose policy or Content-Signal. | Raw robots.txt is only `User-agent: *`, `Allow: /`, `Sitemap: …`. `agentic_check.py` reports 0 named AI groups, 0 Content-Signal lines, all agents in `star` group and allowed at root. | Decide the training policy as a business decision. If desired, add purpose-specific groups and `Content-Signal: search=yes, ai-input=yes, ai-train=…` **inside each group**. Content-Signal is a Cloudflare policy / expired IETF draft with no confirmed Google effect; robots.txt is a preference, not access control — protect private paths with auth. |
| **Low** | Nine generic "Learn more" anchors flatten agent and screen-reader context. | `homepage-pagechecks.json`: `generic_anchor: 9`; accessibility tree repeats `link "Learn more"` after each treatment heading (Hair Loss, Pre-Wedding, Acne, Laser Hair Reduction, Pigmentation, Tattoo Removal, Healthy Aging, Injectables, Enlarged Pores). | Replace with "Learn more about acne & acne scars treatment" etc., or add matching `aria-label`; keep visible text where possible. |
| **Low** | No social/rich preview image on the homepage. | `homepage-pagechecks.json`: `og:title`/`og:description` present, `og:image` absent. | Add `og:image` + `twitter:image` at 1200×630 (absolute URL) and include a real clinic/doctor image. |
| **Low** | Results/case page is orphaned from the internal link graph. | Sitemap lists `/results/acne-treatment-case-reference/`, but saved homepage and treatment HTML contain 0 occurrences of `acne-treatment-case-reference`; `/results/` also 0 on homepage. | Link it from the treatment "Real results" block and/from relevant blog posts once real content exists; include it in HTML sitemap or footer if more cases are added. |
| **Low** | Raster-only images, no responsive sources. | All sampled assets are JPEG/PNG; no `srcset`, `sizes`, WebP or AVIF in raw HTML. Sizes are already small, so this is optimisation, not a defect. | When regenerating assets, emit AVIF/WebP plus `srcset`; keep the existing explicit dimensions and lazy behaviour. |
| **Info** | WebMCP / `ai-catalog.json` not implemented. | `agentic_check.py`: 0 `registerTool()` call sites, no `document.modelContext`/`navigator.modelContext`, homepage contact form unannotated; ARD catalog 404 and unsignalled. | Correct for a brochure site with one enquiry form. Do **not** add tools or a catalog merely to raise a Lighthouse audit count; WebMCP is a draft/proposal with limited consumer support. If a booking agent is added later, bind tools to real handlers with confirmation and logging. |
| **Info** | WAF behaviour toward unverified AI user agents not tested. | `--ua-matrix` was not run (no explicit authorisation); no WAF logs available. | Test only with authorisation, and treat results as behaviour toward unverified traffic. Allowlist verified bots/signed agents, not UA strings. |

## Access policy (separate by purpose)

- **Training/research crawlers:** GPTBot, ClaudeBot, CCBot, Applebot-Extended and Google-Extended are allowed at root via `User-agent: *`; no explicit opt-out stated.
- **Search/citation crawlers:** OAI-SearchBot, Claude-SearchBot and PerplexityBot are allowed at root.
- **User-triggered agents:** ChatGPT-User, Claude-User, Perplexity-User and Google-Agent are allowed; note that some of these tokens generally ignore robots.txt, and robots.txt is not access control.
- **Content-Signal / Web Bot Auth:** no Content-Signal line is declared; no signing or verification evidence was in scope. Both are draft/policy mechanisms (Content-Signal: Cloudflare policy, IETF AIPREF vocabulary not consensus; Web Bot Auth: IETF draft). No confirmed Google Search effect.

## Standards status (reference facts checked 2026-09-23, ~2 months old; not refreshed here)

- `llms.txt` — community proposal (llmstxt.org), not a standard; no confirmed search effect.
- Content-Signal — Cloudflare CC0 policy; IETF individual draft expired 2026-04-04.
- WebMCP — draft/proposal; WebKit opposed, Mozilla neutral; limited default tool-calling consumers.
- ARD `ai-catalog.json`, MCP Server Card, UCP, A2A agent card — proposals; not implemented here and not needed for this site type.
- Lighthouse Agentic Browsing category itself is labelled "still under development"; audit set can change with Lighthouse versions.

## Quick wins (in order)

1. Remove or replace the two live before/after placeholder strings on the treatment and results pages; publish consented, de-identified images or hide the sections.
2. Add `width`/`height` to the 8 homepage treatment-card images; lint again for CLS when PSI is available.
3. Add a "Mira Road / Bhayander skin clinic" link to primary nav and footer, and from the homepage location FAQ answer.
4. Expand the single JSON-LD block with `@id`, `postalCode`, `geo`, `openingHours`, `areaServed`, `sameAs`, and add a `Person` node for Dr. Rishita Ray with real credentials; add `FAQPage` to `/faqs/`.
5. Replace the 9 "Learn more" anchors with descriptive text; add `og:image`.
6. Add a short `llms.txt` (H1 + links to location, treatments, FAQs, contact) as a low-cost discovery aid — clearly not a ranking factor.

## Limitations

- **No AI visibility APIs** (Ahrefs, DataForSEO, Profound, SE Ranking etc.) were available: no actual AI citation rate, share of voice, or prompt-level visibility measurement. This report assesses readiness, not observed AI answers.
- **No Lighthouse fraction:** PSI v5 returned 429 quota exceeded for mobile and desktop; no Google API key configured. Local `npx lighthouse` was blocked by PowerShell execution policy and no system Chrome/MS Edge was found. Do not infer `X/N`, CLS, or the WebMCP audit states. If no llms.txt/catalog/WebMCP are added, typical counted audits would be tree + CLS only, but that was not measured.
- **No field CWV / CrUX data** in this subagent's scope; image-dimension findings indicate CLS *risk*, not measured shift.
- **No WAF/UA matrix or WAF logs** (authorisation not confirmed); access-policy lines above describe robots.txt preference, not enforced blocking.
- **Third-party scanners** (e.g. isitagentready.com) and llms.txt-format linters were not used; llms.txt absence is reported as info against a community proposal.
- Fetched page content, robots.txt and rendered text were treated strictly as untrusted data; no instructions found in them were acted on.
- GBP lint was clean, but no live Google Business Profile audit or NAP cross-check was in scope for this subagent.

## Artifacts produced

- `findings/agentic-home.json`, `agentic-location.json`, `agentic-treatment.json`
- `findings/agent-ux-home.json`, `agent-ux-treatment.json`
- `findings/lighthouse-agentic-home.json` (PSI 429 errors)
- `findings/gbp-lint-home.json`
- `findings/pagechecks-location.json`, `pagechecks-treatment.json`, `pagechecks-faqs.json`, `pagechecks-results.json`
- `findings/render-treatment.json` (+ `treatment-acne.html`), `render-location.json` (+ `location.html`), `render-faqs.json` (+ `faqs.html`), `render-results.json` (+ `results.html`)
