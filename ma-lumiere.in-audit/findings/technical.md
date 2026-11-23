# Technical SEO — ma-lumiere.in

**Technical SEO Score: 79/100** — Solid HTTPS/redirect fundamentals, valid robots.txt and sitemap, and clean self-canonicals on all 10 primary pages are offset by thin duplicate `/treatment-type/*` URLs in the sitemap with no canonical, missing security/cache headers, and unmeasurable Core Web Vitals (no PSI/CrUX/GSC credentials).

## Category breakdown

| Category | Status | Score | Basis |
|---|---|---|---|
| Crawlability | warn | 85/100 | robots `Allow: /` + declared/valid sitemap (35 URLs), no noindex, 10/10 pages HTTP 200, JS parity on homepage; 4 sampled URLs not linked from hub pages, sitemap declared path 301s to slash |
| Indexability | fail | 62/100 | Self-canonical on all 10 primary pages; 3/3 sampled `/treatment-type/*` pages have **no canonical**, are thin (32–64 words) and in the sitemap |
| Security | warn | 58/100 | Valid TLS 1.3 cert (host + www), 301 HTTP→HTTPS, www→non-www; HSTS/CSP/nosniff/X-Frame-Options/Referrer-Policy and cache headers missing |
| URL Structure | pass | 88/100 | Descriptive hyphenated slugs, no query params, max length 70 chars, consistent trailing slashes; parallel `/treatment/` vs `/treatment-type/` taxonomy adds ambiguity |
| Mobile | pass (partial) | 90/100 | Viewport meta on 10/10, responsive CSS (5 `@media`, 24 `max-width` rules), homepage raw vs rendered content parity; touch targets/overflow not measured |
| Core Web Vitals | not measured | — | No Google API key / PSI / CrUX / GSC configured — no LCP, INP or CLS field data |
| Structured Data | pass | 95/100 | JSON-LD on 10/10 pages, 0 invalid blocks (homepage: MedicalClinic + HealthAndBeautyBusiness), no JS-injected conflicts |
| JS Rendering | pass | 95/100 | Homepage `js_gap`: 0 words/links only in rendered output, no title/canonical/robots change, no console errors (1-page sample) |
| IndexNow | not measured | — | No key file/submit endpoint verified; cannot confirm support |

Weighted mean over the 7 measured categories = **79/100**.

## What works

- **robots.txt** at `https://ma-lumiere.in/robots.txt` returns 200 with `User-agent: * / Allow: /` and a valid `Sitemap:` declaration (`sitemap-discovery.json`).
- **Sitemap** is valid (`urlset`), 6,276 bytes, **35 unique URLs**, 0 invalid `<loc>`, 0 duplicates, 0 cross-host URLs; `lastmod` present on 26 URLs, all recent (2026-09-22 → 2026-11-17).
- **All 10 sampled URLs return HTTP 200** with **self-referencing canonicals**, `meta robots = max-image-preview:large` (no noindex), no `X-Robots-Tag`, and no mixed content (`http_on_https = 0`, `mixed_content = 0` in every `pagecheck-*.json`).
- **HTTPS enforcement**: `http://` → `https://` is a single 301 hop; `www.ma-lumiere.in` and apex resolve to the same final URL; certificate is valid, TLS 1.3, covers `*.ma-lumiere.in` + `ma-lumiere.in` + `www` (34 days to expiry as of 2026-11-23).
- **Lightweight HTML**: largest sampled page 63,581 bytes (homepage), DOM 531 nodes / depth 12 — far below Google's 2MB HTML fetch cap and within healthy DOM limits.
- **Server-rendered content**: homepage raw HTML and rendered DOM match exactly (1,208 words, 58 links, same H1/title/canonical/schema); no SPA/CSR dependency (`homepage-render.json`, `js_gap.significant = false`).
- **Structured data** present and parseable on all 10 pages (`jsonld_invalid = 0`).
- **Mobile basics**: viewport meta on 10/10, responsive stylesheet with media queries; no separate m-dot site.
- **No back-button hijacking** detected (`history.pushState`/`replaceState` absent in sampled homepage/treatments/blog/about HTML).

## Findings

| Severity | Issue | Evidence (specific URL/data) | Recommendation |
|---|---|---|---|
| **High** | Thin duplicate `/treatment-type/*` pages in the sitemap with **no canonical**, competing with richer `/treatment/*` pages | `https://ma-lumiere.in/treatment-type/acne-acne-scars/` (64 words, `canonical: null`, title "Acne & Acne Scars — Ma Lumière") vs `/treatment/acne-acne-scars/` (397 words, self-canonical); same pattern at `/treatment-type/laser-hair-reduction/` (32 words, no canonical) and `/treatment-type/pre-wedding-skin-preparation/` (34 words, no canonical) vs `/treatment/pre-wedding-skin-preparation/` (366 words). 8 `/treatment-type/*` URLs are in `sitemap.xml/`. (`pagecheck-treatment-type-*.json`, `technical-collect3.json`) | Add a canonical from each `/treatment-type/*` URL to its `/treatment/*` equivalent (or noindex + remove from sitemap if intentionally thin). Consolidate any unique copy into the target page. Re-check after ~2 weeks. |
| **High** | Orphaned local-SEO money page (and blog/results hubs) not linked from sampled navigation hubs | `https://ma-lumiere.in/skin-clinic-mira-bhayander/` (576 words, self-canonical, in sitemap) is not linked from homepage, `/treatments/`, `/blog/` or `/about/`; `/blog/` and `https://ma-lumiere.in/results/acne-treatment-case-reference/` are also unlinked from those hubs (`technical-collect.json → orphan_candidates`) | Add crawlable internal links from the primary nav and/or relevant treatment pages to the Mira Bhayander location page; link the blog hub from the header/footer and the case-reference page from related treatments. |
| **Medium** | HSTS and other core security headers absent | `transport-check.json`: `hsts.present = false`, `csp.present = false`, `x_content_type_options.present = false`, `framing.ok = false`, `referrer_policy.present = false`, `permissions_policy.present = false` on `https://ma-lumiere.in/` | Add `Strict-Transport-Security: max-age=31536000; includeSubDomains` once all subdomains are confirmed HTTPS, plus `X-Content-Type-Options: nosniff`, `X-Frame-Options: SAMEORIGIN`, `Referrer-Policy: strict-origin-when-cross-origin`, and a CSP. (Security/trust, not a confirmed ranking factor.) |
| **Medium** | No cache headers on HTML or versioned static assets | `technical-collect2.json`: `Cache-Control`, `Expires`, `ETag`, `Age` all null on `/`, `/treatments/`, `/treatment/acne-acne-scars/`, `site.css?ver=1795414769` and `main.js?ver=1795414761`; only `Last-Modified` on CSS/JS | Set long-lived `Cache-Control: public, max-age=31536000, immutable` on versioned CSS/JS and a short `max-age` with revalidation on HTML. Improves repeat-visit speed without changing content. |
| **Medium** | Image dimensions missing on the highest-traffic pages (CLS risk) | `pagecheck-home.json`: 8/12 images without width/height; `pagecheck-treatments.json`: 11/19; `pagecheck-acne-acne-scars.json`: 4/5; `pagecheck-laser-hair-reduction.json`: 4/5 | Add `width`/`height` attributes or CSS `aspect-ratio` to all `<img>` elements, especially hero/above-the-fold images. |
| **Medium** | Above-the-fold image lazy-loaded on `/about/` (LCP risk) | `pagecheck-about.json` finding `first-image-lazy-loaded` (also seen on sampled `/treatment-type/*` pages) | Remove `loading="lazy"` from the first/hero image; keep lazy loading for below-the-fold images only. |
| **Medium** | Missing meta descriptions on key pages | `pagecheck-about.json`, `pagecheck-treatments.json`, `pagecheck-faqs.json`, `pagecheck-contact.json`, `pagecheck-book-consultation.json` all report `missing-meta-description` | Add unique ~120–160 character descriptions per page; homepage description (146 chars) is a good template. |
| **Medium** | Render-blocking CSS/font chain on every page | `technical-collect.json`: each sampled page loads 3 external stylesheets (Google Fonts `Cormorant Garamond`+`Manrope`, `style.css`, `site.css` 57.6 KB) plus 5 inline `<style>` blocks (~14.7 KB); no CSS `preload`. `display=swap` and `preconnect` to `fonts.gstatic.com` are already present. | Inline critical CSS, defer non-critical CSS, and consider self-hosting the two font families with `font-display: swap` to cut render-blocking time. |
| **Low** | `og:image` missing on 8 of 10 sampled pages | Only `/treatment/acne-acne-scars/` (`wix_acne2-2.jpg`) and `/treatment/laser-hair-reduction/` (`wix_laserhair-2.jpg`) have `og:image`; homepage, `/about/`, `/treatments/`, `/contact/`, `/faqs/`, `/book-consultation/`, `/skin-clinic-mira-bhayander/`, blog guide do not | Add a consistent 1200×630 `og:image` (and `twitter:image`) per page — prefer a clinic/physician photo on local pages. |
| **Low** | Title lengths outside the 30–60 character heuristic | Homepage 83 chars; blog guide 65 chars (too long); `/about/` 18, `/faqs/` 17, `/contact/` 20, `/treatments/` 23 chars (too short) | Rewrite to ~50–60 chars with primary keyword + locality. This is a display heuristic, not a search-engine limit. |
| **Low** | `lang="en-US"` and no hreflang on an India-targeted site | All 10 pages: `lang: en-US`, `hreflang_count: 0`; business address is Mira Road East, Mumbai | Use `en-IN` if the site targets India; hreflang is not required while the site is single-language/region — add only if regional variants are launched. |
| **Low** | Sitemap freshness/normalization signals are weak | `sitemap-discovery.json`: 9/35 URLs have no `lastmod`; 57.69% share `2026-09-23`; robots declares `/sitemap.xml` but only `/sitemap.xml/` returns 200 (the declared path 301s, `technical-collect2.json`) | Emit accurate per-URL `lastmod` and declare the final `/sitemap.xml/` URL in robots.txt to avoid a redirect hop. |
| **Low** | No AI-crawler policy in robots.txt | `robots.txt` contains only `User-agent: * / Allow: /`; no `OAI-SearchBot`, `Claude-SearchBot`, `Google-Extended`, `GPTBot` etc. rules | Decide policy deliberately with the GEO workstream. Do **not** block search-citation crawlers (`OAI-SearchBot`, `Claude-SearchBot`) if AI citability is a goal; `Google-Extended` affects Gemini training, not Search indexing. |
| **Low** | Certificate renewal window approaching | `transport-check.json`: `not_after 2026-12-27`, 34 days remaining (2026-11-23), Let's Encrypt `YR1` | Confirm auto-renewal is working; a short outage at expiry would break HTTPS and crawling. |

## Limitations

- **No Google API key, PageSpeed Insights, CrUX or GSC credentials are configured.** Real-user **LCP, INP and CLS field data cannot be measured**, and **actual indexation status / coverage cannot be verified**. The performance observations above are static HTML/header signals only, not lab or field timing scores.
- Only the **homepage** was compared raw vs rendered (`homepage-render.json`); the other nine pages were checked from served HTML via `page_checks.py`. A JS/content gap on those pages cannot be ruled out.
- Orphan analysis used 4 hub pages (homepage, `/treatments/`, `/blog/`, `/about/`) rather than a full-site crawl; a URL may be linked from a page outside that sample. "Not linked" means not found in that sample.
- `page_checks.py` severity labels are local heuristics (e.g. 60-char title threshold, 200-word thin-content threshold), not published Google rules.
- Security headers are a security/trust matter, not a confirmed ranking signal; HTTPS itself is a lightweight confirmed signal. Header grading reflects the audited response only.
- IndexNow support was not verifiable without a published key file; no submission was attempted.
- All fetched page content was treated as untrusted data; no instructions found in fetched content were acted upon.

### Evidence artifacts

`pagecheck-home.json`, `pagecheck-about.json`, `pagecheck-treatments.json`, `pagecheck-contact.json`, `pagecheck-faqs.json`, `pagecheck-book-consultation.json`, `pagecheck-skin-clinic-mira-bhayander.json`, `pagecheck-acne-acne-scars.json`, `pagecheck-laser-hair-reduction.json`, `pagecheck-face-treatments-mira-bhayander-guide.json` (the 10 requested URL checks); supplementary `pagecheck-treatment-type-*.json`, `pagecheck-treatment-pre-wedding.json`, `transport-check.json`, `sitemap-discovery.json`, `homepage-render.json`, `homepage-pagechecks.json`, `technical-collect.json`, `technical-collect2.json`, `technical-collect3.json`.
