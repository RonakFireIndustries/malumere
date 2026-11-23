# Schema Implementation Record — ma-lumiere.in

**Status:** implemented on the local copy only (not deployed to production).
**Baseline:** Overall SEO Health 56/100 · Local SEO 31/100.
**Audit date:** 2026-11-23.
**Reference artifacts:** `findings/jsonld-verification.json`, `findings/implementation-verification.json`, `tools/verify-jsonld.php`, `tools/verify-implementation.php`.
**Code locations:** `wp-content/themes/ma-lumiere/inc/seo.php`, `wp-content/themes/ma-lumiere/inc/template-tags.php`.

The audit pattern in `findings/content-schema.md` proposed a single `@graph`. The implemented version uses **separate `<script type="application/ld+json">` blocks** with shared `@id` references instead. That is equivalent for entity linking and avoids duplicating the clinic node on every page. No SEO plugin is active; if one is installed, `ml_has_seo_plugin()` suppresses the theme output — re-validate to avoid duplicate or conflicting markup.

---

## 1. Exact schema types implemented

| Type | Where emitted | Pages | Notes |
|---|---|---|---|
| `MedicalClinic` + `LocalBusiness` (single node, `@type` array) | `inc/seo.php` → `ml_organization_schema()`, `wp_head` priority 2 | Front page only | Replaces the old thin clinic block. `HealthAndBeautyBusiness` was proposed in the audit but **not** implemented. |
| `WebSite` | `inc/seo.php` → `ml_organization_schema()`, `wp_head` priority 2 | Front page only | `publisher` references the clinic `@id`. |
| `Person` | `inc/seo.php` → `ml_organization_schema()`, `wp_head` priority 2 | Front page only, when a doctor name is configured | Name **Dr. Rishita Ray**; `jobTitle` **Aesthetic Physician** only. |
| `WebPage` | `inc/seo.php` → `ml_webpage_schema()`, `wp_head` priority 5 | Every singular view (pages, treatments, posts, results) | `isPartOf` → `#website`; `about` → `#clinic`. |
| `Service` | `inc/seo.php` → `ml_webpage_schema()`, `wp_head` priority 5 | `ml_treatment` singular only | Provider → `#clinic`. No `MedicalProcedure` or `offers`/price markup. |
| `BreadcrumbList` | `inc/template-tags.php` → `ml_breadcrumbs()`, called from page/single/archive/taxonomy templates | All non-front pages where the breadcrumb nav renders | Emitted inline with the visible breadcrumbs, not in `wp_head`. |
| `BlogPosting` | `inc/seo.php` → `ml_article_schema()`, `wp_head` priority 4 | Single blog posts | **Pre-existing, retained unchanged**; documented here for completeness. Author/publisher are still `Organization`, not a reviewed `Person`. |

No `FAQPage`, `MedicalWebPage`, `MedicalProcedure`, `aggregateRating` or `review` markup exists in the shipped theme.

---

## 2. Emission map (hook order in `inc/seo.php`)

| `wp_head` priority | Function | Output |
|---:|---|---|
| 1 | `ml_seo_head()` | Open Graph / Twitter tags (not JSON-LD). |
| 2 | `ml_organization_schema()` | `MedicalClinic`+`LocalBusiness`, `WebSite`, `Person`. |
| 3 | `ml_meta_description()` | Meta description + OG/Twitter descriptions. |
| 4 | `ml_article_schema()` | `BlogPosting` (posts only). |
| 5 | `ml_webpage_schema()` | `WebPage`, plus `Service` for treatments. |
| 6 | `ml_canonical_and_robots()` | Canonical and `noindex,follow` (9 thin archives, search, 404). |

`ml_breadcrumbs()` in `inc/template-tags.php` builds the visible `<nav class="breadcrumbs">` and the matching `BreadcrumbList`. It is called by `page.php`, `single.php`, `single-ml_treatment.php`, `single-ml_before_after.php`, `archive.php`, `archive-ml_treatment.php`, `archive-ml_before_after.php`, `taxonomy-treatment_category.php`, `index.php`, `search.php` and the page templates for About, Treatments, FAQs, Contact and Book Consultation. It returns early on the front page.

---

## 3. `@id` strategy

Base is the site home URL from WordPress (`home_url( '/')`, i.e. `https://ma-lumiere.in/` once deployed). Fragments identify entities; nodes are referenced with `{"@id": "..."}` rather than repeated objects.

| `@id` | Entity | Scope |
|---|---|---|
| `https://ma-lumiere.in/#clinic` | `MedicalClinic`+`LocalBusiness` | Defined once on the front page. |
| `https://ma-lumiere.in/#website` | `WebSite` | Defined once on the front page. |
| `https://ma-lumiere.in/#aesthetic-physician` | `Person` | Defined once on the front page when a name is set. |
| `https://<page-url>#webpage` | `WebPage` | Defined on each singular URL. |
| `https://<treatment-url>#service` | `Service` | Defined on each treatment URL. |
| `https://<post-url>#article` | `BlogPosting` | Pre-existing; on posts. |
| *(none)* | `BreadcrumbList` | Local node with `itemListElement` positions; no `@id` assigned. |

Reference edges:
- `WebSite.publisher` → `#clinic`
- `WebPage.isPartOf` → `#website`
- `WebPage.about` → `#clinic`
- `Service.provider` → `#clinic`
- `Person.worksFor` → `#clinic`

---

## 4. Fields included

**`MedicalClinic` + `LocalBusiness`**
`@context`, `@type`, `@id`, `name`, `url`, `description`, `telephone` (from `+91 99878 02355`, normalised to `+919987802355`), `email` (configured clinic email), `address` as `PostalAddress` with `streetAddress` (full verified address at Shop No. 117, 1st Floor, Vasudev Sky High, Kanakia Road, Beverly Park, Mira Road East), `addressLocality` Mira Road East, `addressRegion` Maharashtra, `postalCode` **401105**, `addressCountry` IN; `areaServed` as three `Place` objects (Mira Road East, Bhayander, Western Railway corridor / Mumbai Metropolitan Region); `hasMap` (address-based Google Maps directions URL); `logo` and `image` (site custom logo or bundled default); `sameAs` only when real social URLs or a verified GBP URL are configured.

**`WebSite`**
`@context`, `@type`, `@id`, `url`, `name`, `publisher` reference, `inLanguage` `en-IN`.

**`Person`**
`@context`, `@type`, `@id`, `name` (Dr. Rishita Ray), `jobTitle` (**Aesthetic Physician** — the only verified professional description), `worksFor` reference. No degree, registration, specialisation, photo or external profile claims.

**`WebPage`**
`@context`, `@type`, `@id`, `url`, `name`, `description` (from the saved SEO/excerpt content), `isPartOf`, `about`, `inLanguage` `en-IN`, plus `primaryImageOfPage` (`ImageObject`) when a featured image exists.

**`Service`**
`@context`, `@type`, `@id`, `name`, `description`, `url`, `provider` reference, `areaServed` (`Place` objects for Mira Road East and Mira-Bhayander).

**`BreadcrumbList`**
`@context`, `@type`, `itemListElement` with `ListItem`, `position`, `name`, `item`. The final item points to the current page permalink.

**`BlogPosting` (pre-existing)**
`headline`, `description`, `datePublished`, `dateModified`, `inLanguage`, `author` (`Organization`), `publisher` (`Organization` with logo `ImageObject`), `mainEntityOfPage`, optional `image`, `articleSection`, `keywords`.

---

## 5. Fields intentionally omitted (and why)

| Omitted field / type | Reason | Owner action required before adding |
|---|---|---|
| `geo` / `GeoCoordinates` | No verified latitude/longitude supplied; guessing is prohibited. | Provide coordinates from the verified GBP listing or a surveyed location, to 5+ decimals, with source and date. |
| `openingHoursSpecification` | Visible hours exist but no structured, verified schedule (including Sunday/holiday rules) was supplied. | Provide confirmed hours per weekday; keep the visible contact information and schema identical. |
| Google Business Profile `place_id` / canonical Maps URL | The supplied share link resolves to a Google share/search page, not a verified canonical place URL. A customizer field `ml_gbp_url` was added in `inc/customizer.php` and left **empty**; `ml_clinic_gbp_url()` only adds a `sameAs` entry when set. | Confirm the canonical Maps/GBP URL and Place ID in the GBP dashboard, then fill the field. Do not paste the share link. |
| `review`, `aggregateRating`, ratings or testimonial markup | No genuine, verifiable on-site reviews; fabricated ratings are prohibited. | Run a compliant review programme (no gating/incentives), collect consent and real review URLs, then add only reviews that actually exist. |
| **`FAQPage`** | **Google retired FAQ rich results in 2026. Do not add FAQPage for rich-result eligibility — there is no rich result to earn.** Visible Q&A copy remains for users and agents. | Only reconsider if a future, documented surface explicitly requires it; mirror visible text exactly and obtain clinical sign-off. |
| `MedicalProcedure`, `MedicalWebPage`, `hasOfferCatalog`, `medicalSpecialty`, `physician` property | No supplied, verifiable procedure/credential data; unsupported/unverified medical markup is a YMYL risk. | Supply procedure-specific clinical documentation, named reviewer, review dates and authoritative citations; obtain clinical/legal sign-off. |
| Person extras: `alumniOf`, `knowsAbout`, `image`, degrees, registration number, years in practice, real profile URLs | Not verified. Only "Aesthetic Physician" is an approved description. | Supply documentary evidence and real profile links; keep the title accurate. |
| `lastReviewed`, `reviewedBy`, `citation` on medical pages | Requires a named reviewer and dated, authoritative sources. | Nominate a reviewer and supply sources (e.g. IADVL/AAD/peer-reviewed guidance); record review dates. |
| `sameAs` social links | Empty until real profiles are confirmed. | Provide verified profile URLs; then the theme emits them automatically. |

---

## 6. Validation performed

1. **Implementation checks** — `tools/verify-implementation.php` → `findings/implementation-verification.json`: **9/9 PASS, 0 FAIL** (NAP with PIN 401105, `+91` phone, `tel:` href, directions URL, no patient-visible placeholders in published DB content, Botox term label, Botox pillar, key-page meta present, sitemap has no `/treatment-type/` URLs; sitemap 29 URLs).
2. **JSON-LD parse checks** — `tools/verify-jsonld.php` fetches sampled rendered URLs, extracts every `application/ld+json` block with a regex, `json_decode`s each block, records `@type` values and JSON errors → `findings/jsonld-verification.json`.
   - `http://localhost/malumere/` — **VALID**, 4 blocks: `MedicalClinic+LocalBusiness`, `WebSite`, `Person`, `WebPage`, 0 errors.
   - `/treatment/acne-acne-scars/` — **VALID**, 3 blocks: `WebPage`, `Service`, `BreadcrumbList`, 0 errors.
   - `/treatment/injectables-facial-contouring/` — **VALID**, 3 blocks: `WebPage`, `Service`, `BreadcrumbList`, 0 errors.
   - `/skin-clinic-mira-bhayander/` — **VALID**, 2 blocks: `WebPage`, `BreadcrumbList`, 0 errors.
3. **Scope of this validation:** local copy, parse-level only (syntactically valid JSON, expected types present). It is **not** Google Rich Results Test or Schema.org validator output, and it does not verify data accuracy. Owner should re-run the two scripts plus Google Rich Results Test and https://validator.schema.org/ on production after deployment, and after any SEO-plugin change.

---

## 7. Deployment and rollback notes

- Deploy `inc/seo.php`, `inc/template-tags.php`, `inc/customizer.php`, `inc/helpers.php` and the related template changes together; partial deployment can leave canonical/robots directives and schema out of sync.
- Rollback: restore `ma-lumiere.in-audit/backups/20261123-142646/theme-ma-lumiere.zip` over `wp-content/themes/ma-lumiere` and the matching `database.sql`.
- Keep GBP canonical URL, coordinates, hours, credentials and reviews out of the markup until the owner supplies verified values. Do not add `FAQPage` for rich results.
