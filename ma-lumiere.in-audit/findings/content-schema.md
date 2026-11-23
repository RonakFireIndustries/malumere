# Ma Lumière — Content, On-Page & Schema Findings

**Audit target:** https://ma-lumiere.in/ (WordPress skin/aesthetic clinic, Mira Road East, Mumbai)
**Scope:** Content quality, on-page metadata, E-E-A-T/YMYL, keyword & local coverage, internal linking, structured data, AI-citability
**Baseline finding — no site files were edited to produce this analysis.** The shipped, verified schema is recorded in `../SCHEMA-IMPLEMENTATION.md` and `../CHANGELOG.md`; the example below remains a baseline proposal, not the deployed code.

## Content & Schema Score: 45/100

| Dimension | Score | Rationale |
|---|---:|---|
| On-page metadata | 9/20 | 5 sampled pages have no meta description; home title 83 chars; treatment vs treatment-type titles/H1s are exact duplicates; canonical missing on blog index + both archives. |
| Content depth & uniqueness | 11/25 | Home (1,114w) and location page (576w) are reasonable; 6 priority treatment pages are 364–397w and share an identical 11-section skeleton (~84 shared 5-word sequences); 9 archives are 41–64w; results page is a 39-word placeholder. |
| E-E-A-T / YMYL | 6/20 | Doctor is named and quoted, but no credentials, registration, years of experience, photo attribution, medical reviewer, citations or authored-by signals. Blog author is `admin` (Organization). |
| Keyword & local coverage | 9/15 | All 6 priority treatments exist as dedicated URLs; Mira Road/Bhayander covered well on one page. Treatment titles/metas carry zero local modifiers; Churchgate–Dahanu corridor terms are absent. |
| Internal linking | 5/10 | Consistent nav/footer, but 9 generic “Learn more” anchors on home, repeated 6-link footer block on every page, no contextual links from treatment pages to location/blog/results pages. |
| Schema & AI-citability | 5/10 | JSON-LD parses cleanly (0 invalid blocks) and breadcrumbs exist, but almost no entity depth; no FAQPage, Service/MedicalProcedure, Person, geo, openingHours or sameAs; Q&A is not marked up. |

## What works

- **One H1 per page** on all 18 sampled URLs; self-referencing canonicals on most pages; no heading-level skips on the 6 treatment pages; no mixed content.
- **Strong location asset:** `/skin-clinic-mira-bhayander/` has 576 words, 13 mentions of “Mira”, 10 of “Bhayander”, and clear sections for face treatments, hair, consultation, fees and booking.
- **Medically sensible sequencing:** pages explain active-acne-before-scars, patch testing before laser, staged pigmentation care, and realistic gradual results — good YMYL instincts.
- **Useful Q&A content:** `/faqs/` has 7 direct local/service answers; each treatment page has 2 condition-specific questions.
- **Clean technical baseline for schema:** 0 invalid JSON-LD blocks across all 18 sampled pages; `BreadcrumbList` present on inner pages; `BlogPosting` present on the post.
- **Complete visible contact details:** full address and opening hours are shown on `/contact/` (they are simply missing from schema — see gaps).

## Findings

| Severity | Issue | Evidence | Recommendation |
|---|---|---|---|
| **Critical** | Placeholder case study is live and exposed in metadata | `/results/acne-treatment-case-reference/` body: “[REAL, CONSENT-APPROVED CASE REQUIRED – before/after images must be uploaded here and must not reveal patient identity.]”; meta description is the same placeholder; 39 words total. | Remove from sitemap/index (`noindex`) until a real consented case exists. Replace with an educational “acne treatment: what results look like over time” page with explicit outcome variability, no identifiable images, and a named medical reviewer. |
| **High** | Structured data is extremely thin sitewide | Only homepage has `MedicalClinic`/`HealthAndBeautyBusiness`, with `address.streetAddress` only. The other 17 sampled pages have `BreadcrumbList` only; the blog post adds `BlogPosting`. No `FAQPage`, `Service`, `MedicalProcedure`, `Person`, `geo`, `openingHoursSpecification` or `sameAs` found. | Implement the `@graph` pattern below. Put clinic entity on homepage + contact; `FAQPage` only on `/faqs/`; `MedicalWebPage` + `MedicalProcedure` on treatment pages; `Person` for Dr Rishita Ray. |
| **High** | YMYL/E-E-A-T signals are missing | `/about/` and homepage say “Dr. Rishita Ray, Founder & Aesthetic Physician” but give no MBBS/MD/specialty, registration number, years in practice, training, photo attribution or external profiles. Blog post schema: `author` = `Organization` named `admin`. No citations, guidelines, review date or reviewer on any medical page. | Add a credential block (degree, specialisation, registration, experience), author box on articles, `lastReviewed`/`reviewedBy`, and 2–3 authoritative citations per treatment page (e.g. AAD/IADVL/peer-reviewed guidance). Link to real profiles via `sameAs`. |
| **High** | Duplicate and weak title/H1 patterns; missing meta descriptions | `/treatment/acne-acne-scars/` and `/treatment-type/acne-acne-scars/` share the exact title “Acne & Acne Scars · Ma Lumière” and H1 “Acne & Acne Scars”; same for pigmentation. Missing meta on `/about/`, `/treatments/`, `/faqs/`, `/contact/`, `/book-consultation/`. Home title 83 chars; blog post title 65. About/Contact/FAQs/Treatments titles are 17–23 chars. Canonical absent on `/blog/` and both sampled archives. | Rewrite to unique, localised patterns: `Acne & Acne Scar Treatment in Mira Road East | Ma Lumière`. Give archives a different H1/title or noindex them. Add 120–160 char metas and self-canonicals. |
| **High** | Thin taxonomy archives cannibalise treatment pages | `/treatment-type/acne-acne-scars/` = 64 words; `/treatment-type/pigmentation-uneven-skin-tone/` = 41 words. Content is title + copied treatment excerpt + “[IMAGE REQUIRED]” + 2 “Learn more” links. 9 such URLs are in the sitemap but only 2 blog posts exist to populate them. | If they remain indexable, give each a unique 250–400 word intro, curated links, FAQ and a self-canonical. Otherwise `noindex, follow` and remove from sitemap; keep links pointing to the real `/treatment/*` pages. |
| **Medium** | Local intent is concentrated on one page | Treatment title/metas contain no “Mira Road”, “Bhayander”, “Mumbai” or corridor terms. `/skin-clinic-mira-bhayander/` mentions Dahisar, Borivali, Kashimira, Thane and Bhayander but not Churchgate, Dahanu, Bandra, Dadar or Andheri. | Add locality modifiers to treatment titles/metas and a “Areas we serve /Western Railway corridor” section with genuinely useful local content, not keyword stuffing. Build a Churchgate→Dahanu hub only if real patient/journey information can be added. |
| **Medium** | Internal links are repetitive and anchor text is generic | Homepage has 9 “Learn more” anchors; archives have 2 and 1. The same 6 treatment links appear in the footer of every page. Treatment pages link to their thin `/treatment-type/*` archive but not to `/skin-clinic-mira-bhayander/`, `/faqs/`, the results page or each other contextually. Blog post links to only one other article. | Replace “Learn more” with descriptive anchors (“Acne scar treatment in Mira Road East”). Add contextual links inside body copy: acne→pigmentation, laser hair reduction→FAQ session guidance, all treatments→location page and consultation. Vary anchor text. |
| **Medium** | Templated treatment copy risks thin-content signals | All 6 priority treatment pages use the identical section order: How it works → Who it is for → Concerns it addresses → Treatment process → Expected results → Preparation & aftercare → Book → What happens next → Questions → Real results. 5-gram Jaccard between any two is ~0.12 (≈84 shared 5-word sequences). Words: 364–397. | Keep the navigation skeleton but expand condition-specific substance: mechanism, device/modality names, candidacy and contraindications, downtime timeline, risk bounds, cost factors and citations. Aim 700–1,000 words per YMYL money page. |
| **Medium** | AI-citability is under-exploited | FAQs and treatment Q&As are written as questions with answers in the visible copy, but there is no `FAQPage` markup, no answer-first summary block, no “last reviewed” date and no sourced statements. Schema claims “Evidence-Based Approach” on home with no supporting citation. | Mark up `/faqs/` with `FAQPage` (mirroring visible text exactly). Add 40–60 word answer-first paragraphs under each treatment H1, `lastReviewed`, `reviewedBy` and cited sources. Improve entity clarity with the Person/Clinic graph below. |
| **Low** | Missing Open Graph images and heading skips | `og:image` missing on all 10 deep-parsed pages. Heading jumps H1→H3 on `/faqs/`, `/blog/` and both archives. | Add a consistent `og:image` (1200×630) and insert H2s where needed. |
| **Low** | Claimed Google Maps link not present in fetched HTML | FAQ says “The contact page includes a Google Maps link for directions”, but no external anchors were found in the raw `/contact/` HTML. | Verify after JS rendering; if absent, add a real map/directions link and reflect it in `hasMap`/`sameAs` where appropriate. |

## Schema gap list

1. **Clinic entity (`MedicalClinic` / `HealthAndBeautyBusiness`)** — homepage only; `address` has `streetAddress` but no `addressLocality`, `addressRegion`, `postalCode`, `addressCountry`, no `geo`, no `openingHoursSpecification`, no E.164 phone, no secondary email, no `sameAs`, no `areaServed`, no `hasOfferCatalog`.
2. **LocalBusiness on `/contact/` and `/skin-clinic-mira-bhayander/`** — neither page declares the clinic entity; only breadcrumbs.
3. **Physician (`Person`)** — no entity for Dr Rishita Ray anywhere; no `knowsAbout`, `worksFor`, `alumniOf`, `image`, `sameAs`, credentials.
4. **`FAQPage`** — absent despite 7 Q&As on `/faqs/` and 6 on the homepage.
5. **Service / `MedicalProcedure` / `MedicalWebPage`** — absent on all 16 treatment pages; no `procedureType`, `bodyLocation`, `preparation`, `followup`, `lastReviewed`, `reviewedBy`, `citation`.
6. **`BlogPosting`** — present but `author` is `Organization` named `admin`, no `image`, `articleSection` = `Uncategorized`, no `wordCount`, no author `Person`.
7. **`CollectionPage` / canonical** — archive pages have neither; they inherit a treatment-like title and duplicate H1.
8. **Do not add** `aggregateRating` or `review` until genuine, verifiable reviews exist. Never fabricate ratings, coordinates or credentials.

### Ready-to-use improved JSON-LD pattern (homepage / clinic — replace placeholders)

```html
<script type="application/ld+json">
{
  "@context": "https://schema.org",
  "@graph": [
    {
      "@type": ["MedicalClinic", "HealthAndBeautyBusiness"],
      "@id": "https://ma-lumiere.in/#clinic",
      "name": "Ma Lumière",
      "url": "https://ma-lumiere.in/",
      "logo": {
        "@type": "ImageObject",
        "url": "https://ma-lumiere.in/wp-content/themes/ma-lumiere/assets/images/logo-mark.png"
      },
      "telephone": "+91-9987802355",
      "email": ["info@ma-lumiere.in", "malumiere2026@gmail.com"],
      "address": {
        "@type": "PostalAddress",
        "streetAddress": "Shop No. 117, 1st Floor, Vasudev Sky High, Kanakia Road, Beverly Park, Mira Road East",
        "addressLocality": "Mira Road East",
        "addressRegion": "Maharashtra",
        "postalCode": "401105",
        "addressCountry": "IN"
      },
      "geo": {
        "@type": "GeoCoordinates",
        "latitude": "REPLACE_WITH_VERIFIED_LATITUDE",
        "longitude": "REPLACE_WITH_VERIFIED_LONGITUDE"
      },
      "openingHoursSpecification": [
        {
          "@type": "OpeningHoursSpecification",
          "dayOfWeek": ["Tuesday", "Wednesday", "Thursday", "Friday", "Saturday"],
          "opens": "12:00", "closes": "21:00"
        },
        {
          "@type": "OpeningHoursSpecification",
          "dayOfWeek": "Sunday",
          "opens": "12:00", "closes": "17:00"
        }
      ],
      "areaServed": [
        "Mira Road", "Mira Bhayander", "Bhayander East", "Bhayander West",
        "Kashimira", "Dahisar", "Borivali", "Mumbai"
      ],
      "medicalSpecialty": "Aesthetic Medicine",
      "physician": { "@id": "https://ma-lumiere.in/about/#physician" },
      "sameAs": ["REPLACE_WITH_REAL_GOOGLE_BUSINESS_PROFILE_URL", "REPLACE_WITH_REAL_SOCIAL_PROFILES"],
      "hasOfferCatalog": {
        "@type": "OfferCatalog",
        "name": "Skin, hair and laser treatments",
        "itemListElement": [
          { "@type": "Offer", "itemOffered": { "@type": "MedicalProcedure", "name": "Acne and acne scar treatment", "url": "https://ma-lumiere.in/treatment/acne-acne-scars/" } },
          { "@type": "Offer", "itemOffered": { "@type": "MedicalProcedure", "name": "Pigmentation and uneven skin tone treatment", "url": "https://ma-lumiere.in/treatment/pigmentation-uneven-skin-tone/" } },
          { "@type": "Offer", "itemOffered": { "@type": "MedicalProcedure", "name": "Hair loss and hair thinning assessment", "url": "https://ma-lumiere.in/treatment/hair-loss-hair-thinning/" } },
          { "@type": "Offer", "itemOffered": { "@type": "MedicalProcedure", "name": "Pre-wedding skin preparation", "url": "https://ma-lumiere.in/treatment/pre-wedding-skin-preparation/" } },
          { "@type": "Offer", "itemOffered": { "@type": "MedicalProcedure", "name": "Injectables and facial contouring", "url": "https://ma-lumiere.in/treatment/injectables-facial-contouring/" } },
          { "@type": "Offer", "itemOffered": { "@type": "MedicalProcedure", "name": "Laser hair reduction", "url": "https://ma-lumiere.in/treatment/laser-hair-reduction/" } }
        ]
      }
    },
    {
      "@type": "Person",
      "@id": "https://ma-lumiere.in/about/#physician",
      "name": "Dr Rishita Ray",
      "jobTitle": "Founder & Aesthetic Physician",
      "worksFor": { "@id": "https://ma-lumiere.in/#clinic" },
      "knowsAbout": ["Acne", "Acne scarring", "Pigmentation", "Aesthetic medicine", "Skin rejuvenation"],
      "description": "REPLACE_WITH_VERIFIED_QUALIFICATIONS_AND_REGISTRATION",
      "image": "REPLACE_WITH_REAL_PORTRAIT_URL",
      "sameAs": ["REPLACE_WITH_REAL_PROFILE_URLS"]
    },
    {
      "@type": "WebPage",
      "@id": "https://ma-lumiere.in/#webpage",
      "url": "https://ma-lumiere.in/",
      "name": "Skin, hair and laser clinic in Mira Road East, Mumbai",
      "isPartOf": { "@id": "https://ma-lumiere.in/#clinic" },
      "reviewedBy": { "@id": "https://ma-lumiere.in/about/#physician" },
      "lastReviewed": "2026-11-23"
    }
  ]
}
</script>
```

**Add-on for `/faqs/` (mirror the visible Q&A text exactly; use this instead of also marking up the duplicate FAQ block on the homepage):**

```html
<script type="application/ld+json">
{
  "@context": "https://schema.org",
  "@type": "FAQPage",
  "@id": "https://ma-lumiere.in/faqs/#faq",
  "mainEntity": [
    {
      "@type": "Question",
      "name": "Where is Ma Lumière skin clinic located?",
      "acceptedAnswer": {
        "@type": "Answer",
        "text": "Ma Lumière is on the first floor of Vasudev Sky High, Kanakia Road, Beverly Park, Mira Road East, Mumbai 401105."
      }
    },
    {
      "@type": "Question",
      "name": "Can you treat acne scars and pigmentation?",
      "acceptedAnswer": {
        "@type": "Answer",
        "text": "Yes, but they are usually managed in stages. Active acne is brought under control first, then scars or pigmentation are treated with an appropriate plan. Results build gradually over sessions."
      }
    }
  ]
}
</script>
```

**Add-on for each treatment page (example: acne):**

```html
<script type="application/ld+json">
{
  "@context": "https://schema.org",
  "@type": "MedicalWebPage",
  "@id": "https://ma-lumiere.in/treatment/acne-acne-scars/#webpage",
  "url": "https://ma-lumiere.in/treatment/acne-acne-scars/",
  "headline": "Acne & Acne Scar Treatment in Mira Road East",
  "isPartOf": { "@id": "https://ma-lumiere.in/#clinic" },
  "reviewedBy": { "@id": "https://ma-lumiere.in/about/#physician" },
  "lastReviewed": "2026-11-23",
  "about": { "@type": "MedicalCondition", "name": "Acne vulgaris and acne scarring" },
  "mainEntity": {
    "@type": "MedicalProcedure",
    "name": "Acne and acne scar management",
    "procedureType": "https://schema.org/TherapeuticProcedure",
    "bodyLocation": "Face and body",
    "preparation": "Consultation, skin assessment and review of previous treatments.",
    "followup": "Review of progress and staged scar/texture planning once active acne is stable."
  }
}
</script>
```

## Limitations

- Analysis used raw HTML snapshots from `fetch_page.py` plus deterministic `page_checks.py`; JavaScript-rendered content was not evaluated. The claimed Google Maps link may exist only after rendering.
- 18 of 35 sitemap URLs were checked in depth (10 full HTML parses + 6 treatment-page similarity comparisons). Remaining treatment/archives are assumed to follow the same templates but were not individually verified.
- No GSC, GA4, backlink, GBP or social-profile data was available, so `sameAs`, citations and `areaServed` must be populated from verified sources.
- Schema was checked for parse validity (0 invalid blocks), not against a full rich-result validator or Google's eligibility requirements.
- Fetched page text was treated strictly as untrusted data; no instructions found in page content were acted upon.
- Placeholder values in the JSON-LD above (coordinates, credentials, portrait URL, social/GBP URLs) must be replaced with verified facts before any future use. This example was not deployed; the implemented schema omits those unverified fields.

## Artifacts produced

- `findings/content-<slug>.json` — 18 `page_checks.py` outputs (home, about, treatments, FAQs, contact, booking, blog index, location, 6 priority treatments, 2 treatment-type archives, results page, blog post).
- `findings/html-cache/*.html` — 16 raw HTML snapshots used for schema/heading/link analysis.
- `findings/content-deep-analysis.txt` — extracted JSON-LD, headings, body text, internal links and keyword signals for 10 key pages.
