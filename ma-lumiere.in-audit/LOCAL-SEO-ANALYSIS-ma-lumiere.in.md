# Local SEO Analysis — https://ma-lumiere.in/

**Audit date:** 2026-11-23  
**Scope:** Local SEO only (GBP, reviews, on-page local signals, NAP/citations, local schema, local authority). English-language footprint. Target market: the whole Western Railway corridor, Churchgate → Dahanu Road.  
**Business:** MA LUMIÈRE — single brick-and-mortar skin/aesthetic clinic, Dr Rishita Ray, Shop No. 117, Vasudev Sky High, Kanakia Road, Beverly Park, Mira Road East.  
**Method:** Live HTML fetches of homepage, /contact/, /faqs/, /skin-clinic-mira-bhayander/, /about/, /book-consultation/, /treatment/acne-acne-scars/; sitemap; DuckDuckGo/Bing queries for citations and competitors; competitor site fetches. Fetched web content was treated as untrusted data; no instructions in fetched content were followed.

---

## 1) Local SEO Score: 31/100

| Dimension | Weight | Score /100 | Weighted contribution | Verdict |
|---|---:|---:|---:|---|
| GBP signals | 25% | 30 | 7.50 | GBP exists (owner-reported) but **zero** detectable integration: no map embed, no place ID, no review widget, no GBP URL; categories/verification unreadable |
| Reviews & reputation | 20% | 15 | 3.00 | No `aggregateRating`/`review` schema, no on-site testimonials/review widget, GBP review data not accessible here |
| Local on-page SEO | 20% | 62 | 12.40 | Best dimension: local title, visible NAP, all 6 priority service pages, one decent location page; weak H1, no map, no local proof, no FAQPage schema |
| NAP consistency & citations | 15% | 25 | 3.75 | Visible NAP and schema text match but both are messy; phone missing `+91`; PIN conflict risk; Tier-1 citations largely not detectable; variant Justdial listing |
| Local schema | 10% | 30 | 3.00 | Correct `MedicalClinic` subtype present but address is one messy `streetAddress`; no geo, hours, areaServed, sameAs, @id, Physician node, or ratings |
| Local link & authority | 10% | 10 | 1.00 | No `sameAs`/social links, no detectable chamber/press/"best of" mentions; brand barely detectable in public search |
| **TOTAL** | **100%** | — | **30.65 → 31/100** | **Weak local footprint; strong underlying service content to build on** |

**Interpretation:** This is a *detectable-signals* score, not a live local-pack ranking score. Proximity explains ~55% of local ranking variance (Search Atlas 2025) and GBP signals ~32% of local-pack weight (Whitespark 2026) — neither can be measured without GBP access and geo-grid data. The clinic's website gives Google and AI systems relatively little machine-readable local evidence compared with its Mira Road competitors.

---

## 2) Business type

**Brick-and-mortar (single physical location), with service-area framing** — not a pure SAB.

Evidence:
- Physical street address visible on homepage contact section, /contact/, footer, and location page (`Shop no. 117 1st floor … Vasudev sky high … mira rd east 401105`).
- Click-to-call `tel:` links present in contact section and footer.
- Opening hours visible on /contact/ and in the global footer.
- Dedicated location page: `/skin-clinic-mira-bhayander/`.
- Service-area language also present: title "serving Mira Bhayander", FAQ "patients travel from Bhayander East/West and Kashimira", location page "patients travel … from Mira Road, Bhayander, Kashimira, Dahisar, Borivali and Thane".

**Impact on checks:** Full brick-and-mortar checks apply (physical NAP consistency, map/directions embed, hours). Service-area language is marketing reach only — it does **not** create ranking eligibility at distant stations; in GBP, rankings are based on the verification address, not the service-area setting (Sterling Sky, March 2025).

---

## 3) Vertical detected

**Healthcare — medical aesthetic / skin clinic** (secondary: beauty & personal care).

Signals: "Dr. Rishita Ray", "Founder & Aesthetic Physician", patients, consultations, treatment pages, `@type: MedicalClinic` in homepage JSON-LD; `HealthAndBeautyBusiness` also declared.

Industry-specific implications:
- Correct schema family is `MedicalClinic` (Google-supported, rich-result eligible) plus a `Physician`/`Person` node for the doctor. Avoid generic `MedicalBusiness`.
- India-relevant citation set differs from US guidance: there is no NPI registry; use medical-council registration/qualifications if available, plus Practo, Justdial, Eka.care, Sulekha.
- Patient confidentiality: do **not** confirm or deny that a reviewer is a patient in review replies; generic, non-identifying responses only.
- No review gating (pre-screening satisfaction before asking for a review is prohibited by Google and the FTC, penalties up to $53,088/violation).

---

## 4) GBP optimisation checklist — detected vs missing

| Signal | Status | Evidence / action |
|---|---|---|
| GBP profile exists | Owner-reported, unverified here | No GBP API/MCP connected. Owner must supply listing URL/place ID. |
| Physical address on page | ✅ Detected | Homepage, /contact/, footer, location page. |
| Click-to-call `tel:` link | ⚠️ Detected but not internationalised | `tel:9987802355` (no `+91`). Change to `tel:+919987802355`. |
| Hours on page | ✅ Detected | Tue–Sat 12:00–21:00; Sun 12:00–17:00; Mon closed (contact + footer). Must exactly match GBP hours. |
| Google Maps embed / iframe | ❌ Missing | 0 `<iframe>` on every page checked. No map anywhere. |
| Google Maps directions link | ❌ Missing | FAQ says "The contact page includes a Google Maps link for directions" — **false**. No maps URL in /contact/ HTML. Fix content or add the link. |
| Place ID / GBP share URL on site | ❌ Missing | No `ChIJ…` or `maps.place`/`maps.app.goo.gl` reference on any checked page. |
| Review widget / rating display | ❌ Missing | No review widget, no rating stars, no review text on site. |
| GBP primary category alignment | ❌ Unverifiable | Owner must check in GBP. **Recommend primary: "Skin Clinic"** (use the closest exact label offered in the India category picker — fallbacks: "Dermatologist" or "Medical Clinic"). Do **not** make "Beauty Salon" primary: it dilutes medical intent. Wrong primary category is the #1 negative local-pack factor (Whitespark 2026). |
| Secondary categories | ❌ Unknown | Target up to 4: Dermatologist, Laser Hair Removal / Hair Removal Service, Medical Spa, Beauty Salon (only where services genuinely offered). |
| Posts / photos / video | ❌ Unknown | Owner must supply counts and cadence. Photos correlate with 45% more direction requests; posts can trigger Post Justifications but have no confirmed direct ranking effect. |
| Q&A | N/A / bonus | GBP Q&A API discontinued 2025-11-03 and public Q&A reportedly being phased out. Put answers in website FAQs (already partly done) and the GBP description. No `FAQPage` schema currently. |
| Google Verified badge eligibility | ❌ Unknown | Owner to confirm verification status in GBP. |
| GBP website URL strategy | ⚠️ Review | Sterling Sky's "Diversity Update" suggests not pointing GBP's website link at the strongest organic-ranking page (homepage). Consider a dedicated GBP landing page such as `/skin-clinic-mira-bhayander/` or `/contact/`. Practitioner study, not Google-confirmed. |

### Competitor snapshot (local signals)

| Clinic | Location | Detectable local signals |
|---|---|---|
| **Newderma Aesthetic Clinic** — newderma.in | Mira Road East (401107) | H1 "Skin, Hair & Aesthetic Clinic in Mira Road"; FAQ with full address; dedicated service pages; local-titled blog posts ("Acne Scar Treatment in Mira Road…"); patient testimonials; Facebook, Instagram, YouTube, Twitter, LinkedIn links; two `+91` phone numbers |
| **Cosderma Aesthetic Clinic** — cosdermaskinclinic.com | Mira Road East (401107) + Malad | Dozens of treatment URLs with Mira Road/Malad in path and title; location-tagged testimonials ("Sunita Ramesh — Mira Road"); `tel:` call button; "best skin clinic" style pages; two addresses |
| **Ariansh Aesthetic Clinic** — arianshaestheticclinic.com | Mira Road East (401107) | Address explicitly linked to `maps.app.goo.gl`; `+91` tel links; long FAQ using "best skin clinic in Mira Road"; Facebook/Instagram/YouTube links; multiple phones |
| **UniDerm Clinic** — unidermclinic.com | Mira Road | "Best Skin Clinic in Mira Road" copy; 12 treatment pages; hours in footer; `+91` tel links; Instagram and WhatsApp links; on-site 5-star testimonials |
| **Practo Mira Bhayandar directory** | Aggregator | Ranks for "Best Skin Clinics Near Me in Mira Bhayandar"; Ma Lumière not detected there (a different "Lumiere Clinic" Practo page exists — verify ownership) |

**Takeaway:** Ma Lumière's treatment content is comparable, but it is the only clinic in this set with **no directions link, no social `sameAs`, no on-site testimonials, no `+91` phone formatting, and no local schema beyond one homepage block**. Competitors are winning the "local proof" layer.

---

## 5) Review health snapshot

| Check | Status |
|---|---|
| Google rating / count | **Unknown** — owner has GBP but no API here. |
| `aggregateRating` in schema | ❌ Absent on all checked pages. |
| `review` in schema | ❌ Absent on all checked pages. |
| On-site testimonials / review widget | ❌ None detected on homepage, location, contact, FAQ, about, treatment, or booking pages. |
| Third-party review presence | Not detectable for this brand; a likely same-premises Justdial entry exists under the variant name **"Lumiere Clinic"** (see §7). |
| Review recency / velocity | Not measurable. |
| Owner response rate | Not measurable. |
| Review gating | No evidence of gating; keep it that way. |

**What the owner must supply (GBP dashboard → "View performance"/"Reviews", or screenshots):**
1. Exact GBP listing URL and place ID.
2. Current star rating and total review count; rating/count from last 90 days.
3. Date of most recent review and count of new reviews in last 30 and 90 days.
4. Percentage of reviews with an owner response; sample responses.
5. GBP Insights: calls, direction requests, website clicks, search terms (last 90 days).
6. Photo/video count, post frequency, Q&A items, verification status, exact categories, business description, service-area setting, and current hours.
7. Confirmed ownership status of the Justdial "Lumiere Clinic" (Shop No. 117) and Practo "Lumiere Clinic" entries.

**Benchmarks to aim for:** 10+ reviews ("magic 10" threshold, Sterling Sky); 4.5+ stars (31% of consumers filter at 4.5+, 68% at 4+, BrightLocal 2026); no gap longer than ~2–3 weeks (velocity > volume); presence on ~6 platforms (BrightLocal 2026); 74% of consumers only care about reviews from the last 3 months.

---

## 6) NAP consistency audit

| Source | Name | Address | Phone |
|---|---|---|---|
| Visible HTML (homepage contact, /contact/, footer) | "MA LUMIÈRE" (brand text "Ma Lumière" elsewhere) | `Shop no. 117 1st floor Ma lumiere clinic , Vasudev sky high , Kanakia  Rd, Om Ram Sagar phase 1 . beverly park, mira rd east 401105` | `9987802355`, `tel:9987802355` |
| Homepage JSON-LD | `MA LUMIÈRE` | Same messy string, stuffed entirely into `streetAddress`; **no** `addressLocality`, `addressRegion`, `postalCode`, `addressCountry`, `geo` | `9987802355` (no `+91`) |
| GBP (owner-reported) | Unknown | Unknown | Unknown |
| Justdial result (detected via DuckDuckGo snippet) | **"Lumiere Clinic"** | "Vasudev Sky High Shop Number 117 Mira Road, Thane" | Not visible in snippet |
| Practo result (detected) | **"Lumiere Clinic"** — listed as General Physician, Mira Road | Mira Road | Not visible in snippet |

**Discrepancies found:**
1. **Brand-name variant:** "MA LUMIÈRE" / "Ma Lumière" on site vs **"Lumiere Clinic"** on detected directories. If those listings are the clinic, claim and align them; if not, document as a different business to avoid wrong-entity association.
2. **Phone format:** no `+91` and no separator anywhere; schema must match GBP exactly.
3. **Address format:** one unparsed string with inconsistent casing, double spaces, stray punctuation and abbreviations (`mira rd east`), so machines cannot reliably extract locality/postal code.
4. **PIN conflict (verify, don't guess):** the site and owner brief use **401105**, but multiple independent sources (NoBroker, HDFC Bank branch locator, onefivenine, and nearby clinic listings including Justdial/Eka entries at Vasudev Sky High) give **401107** for Beverly Park / Mira Road East / Vasudev Sky High. Confirm with India Post/GBP and then use the same value everywhere. **Do not silently change it.**
5. **Name-vs-schema accent:** GBP name must match schema `name` exactly; decide one form (recommend `MA LUMIÈRE` if that is what GBP shows) and use it consistently.

**Recommended canonical NAP (after PIN verification):**

- **Name:** MA LUMIÈRE *(must match GBP exactly)*
- **Phone (display):** +91 99878 02355
- **Phone (`tel:` and schema):** `tel:+919987802355`, `"telephone": "+919987802355"`
- **Address (single line):** Shop No. 117, 1st Floor, Vasudev Sky High, Kanakia Road, Om Ram Sagar Phase 1, Beverly Park, Mira Road East, Thane, Maharashtra 401105 *(replace PIN with verified value)*
- **Structured:** `streetAddress`: "Shop No. 117, 1st Floor, Vasudev Sky High, Kanakia Road, Om Ram Sagar Phase 1, Beverly Park"; `addressLocality`: "Mira Road East"; `addressRegion`: "Maharashtra"; `postalCode`: "401105"; `addressCountry`: "IN"

Put this exact block in the footer, /contact/, the location page, JSON-LD, GBP, and every directory listing.

---

## 7) Citation presence check

| Source | Status | Notes |
|---|---|---|
| Google Business Profile | Owner-reported exists; not verifiable here | Primary local signal source. Provide place ID/URL. |
| Google Maps listing | Unverified | No place ID/map link on site. |
| Bing Places | **Not detectable** | Claim urgently — powers ChatGPT, Copilot, Alexa; Bing overhauled Places in Oct 2025. |
| Apple Maps / Apple Business | **Not detectable** | Claim/verify. Apple Maps used by 27% of consumers (BrightLocal 2026, up from 14%). Verify any "new all-in-one Apple Business platform" claim against Apple primary sources before acting on secondary reports. |
| Facebook Business page | **Not detectable** (site: search returned nothing; no FB link on site) | Claim and link with `sameAs`. |
| Justdial | **Probable listing under variant name** — "Lumiere Clinic", Vasudev Sky High Shop No. 117, Mira Road (1 photo per snippet). Direct fetch 403. | Claim/verify; align name, phone, PIN, hours, categories. Same building also has a Justdial listing for "The Skin and Lungs Clinic" — avoid confusion. |
| Practo | "Lumiere Clinic, Mira Road" detected (listed as General Physician); direct brand search 404. | Verify whether it is the same entity; claim and correct specialty to skin/aesthetic. Also consider a Mira Bhayandar skin-clinic category listing. |
| Sulekha | **Not detectable** (`site:sulekha.com "ma lumiere"` → no results) | Claim/verify. |
| Eka.care / ClinicSpots / ThreeBestRated-style "best of" lists | Not detectable for this brand; competitors and same-building clinics appear | Pursue — "best of" placements are the #1 AI visibility citation factor (Whitespark 2026). |
| Data aggregators (Data Axle, Foursquare, Neustar/TransUnion) | Unknown | Submit for downstream distribution; Foursquare powers Uber/Nextdoor/Yahoo/ChatGPT. |
| Chamber of commerce / local press | Not detectable | Authority opportunity. |

"Not detectable" means *not found in the search corpus used here*, not proof of absence. Direct Justdial access was blocked (403), Practo search 404, and Google Maps fetch returned no parseable content. Bing's generic result set was noisy, so the negative Bing findings should be re-checked manually by the owner.

### AI-search local sources

- ChatGPT does **not** read GBP directly; it draws from the Bing index, Yelp, TripAdvisor, BBB, Reddit and similar third-party sources. In an Indian local context, Practo/Justdial/Sulekha are likely material for AI answers.
- 3 of the top 5 AI-visibility factors are citation-related (Whitespark 2026). Brand mentions correlate ~3× more strongly with AI visibility than backlinks (Ahrefs: 0.664 vs 0.218).
- Action: claim Bing Places first, then Apple/Justdial/Practo/Facebook, and seed consistent, crawlable brand mentions. Run `/seo geo https://ma-lumiere.in/` for a dedicated GEO/citability audit (not duplicated here).

---

## 8) Local schema status + ready-to-use fix

**Current state (homepage only):**

```json
{"@context":"https://schema.org","@type":["MedicalClinic","HealthAndBeautyBusiness"],"name":"MA LUMIÈRE","url":"https://ma-lumiere.in/","logo":"…","telephone":"9987802355","email":"malumiere2026@gmail.com","address":{"@type":"PostalAddress","streetAddress":"Shop no. 117 1st floor Ma lumiere clinic , Vasudev sky high , Kanakia  Rd, Om Ram Sagar phase 1 . beverly park, mira rd east 401105"}}
```

Issues: no `@id`; mixed types dilute the specific healthcare subtype; address has no structured locality/region/postal code; phone lacks `+91`; no `geo` (5+ decimals), `openingHoursSpecification`, `areaServed`, `sameAs`, `image`, `priceRange`, `hasMap`, `aggregateRating`, or Physician entity. Other pages have only `BreadcrumbList`. Schema is not a direct ranking factor (Mueller), but it drives rich results and machine/AI comprehension.

**Ready-to-use replacement (put in `<head>` of the homepage; reference the same `@id` from /contact/ and the location page — do not duplicate conflicting copies):**

> ⚠️ Before publishing: (a) replace the example `geo` values with the **exact** GBP pin coordinates (right-click the pin in Google Maps → copy lat/long, or GBP → Share → coordinates); the values below are area-level examples only. (b) Confirm the PIN (401105 vs 401107). (c) Replace placeholder `sameAs`/`hasMap` URLs with real ones or remove them. (d) Use the GBP-exact `name`.

```html
<script type="application/ld+json">
{
  "@context": "https://schema.org",
  "@graph": [
    {
      "@type": "MedicalClinic",
      "@id": "https://ma-lumiere.in/#clinic",
      "name": "MA LUMIÈRE",
      "url": "https://ma-lumiere.in/",
      "telephone": "+919987802355",
      "email": "info@ma-lumiere.in",
      "image": "https://ma-lumiere.in/wp-content/themes/ma-lumiere/assets/images/hero.jpg",
      "priceRange": "₹₹",
      "address": {
        "@type": "PostalAddress",
        "streetAddress": "Shop No. 117, 1st Floor, Vasudev Sky High, Kanakia Road, Om Ram Sagar Phase 1, Beverly Park",
        "addressLocality": "Mira Road East",
        "addressRegion": "Maharashtra",
        "postalCode": "401105",
        "addressCountry": "IN"
      },
      "geo": {
        "@type": "GeoCoordinates",
        "latitude": 19.294890,
        "longitude": 72.867840
      },
      "openingHoursSpecification": [
        {
          "@type": "OpeningHoursSpecification",
          "dayOfWeek": ["Tuesday", "Wednesday", "Thursday", "Friday", "Saturday"],
          "opens": "12:00",
          "closes": "21:00"
        },
        {
          "@type": "OpeningHoursSpecification",
          "dayOfWeek": "Sunday",
          "opens": "12:00",
          "closes": "17:00"
        },
        {
          "@type": "OpeningHoursSpecification",
          "dayOfWeek": "Monday",
          "opens": "00:00",
          "closes": "00:00"
        }
      ],
      "areaServed": [
        {"@type": "Place", "name": "Mira Road East"},
        {"@type": "Place", "name": "Bhayander"},
        {"@type": "Place", "name": "Dahisar"},
        {"@type": "Place", "name": "Borivali"},
        {"@type": "Place", "name": "Vasai"},
        {"@type": "Place", "name": "Virar"},
        {"@type": "Place", "name": "Palghar"},
        {"@type": "Place", "name": "Dahanu"},
        {"@type": "Place", "name": "Thane"},
        {"@type": "Place", "name": "Mumbai"}
      ],
      "hasMap": "https://www.google.com/maps/search/?api=1&query=PLACE_ID_OR_ENCODED_ADDRESS",
      "sameAs": [
        "https://www.facebook.com/REPLACE_WITH_REAL_PAGE",
        "https://www.instagram.com/REPLACE_WITH_REAL_PROFILE",
        "https://www.justdial.com/REPLACE_WITH_CLAIMED_LISTING",
        "https://www.practo.com/REPLACE_WITH_CLAIMED_LISTING"
      ]
    },
    {
      "@type": "Physician",
      "@id": "https://ma-lumiere.in/#dr-rishita-ray",
      "name": "Dr. Rishita Ray",
      "medicalSpecialty": "Dermatology",
      "worksFor": {"@id": "https://ma-lumiere.in/#clinic"},
      "url": "https://ma-lumiere.in/about/"
    }
  ]
}
</script>
```

**Optional — only when genuine and policy-compliant** (never fabricate ratings; they must be real, verifiable and, for review snippets, aligned with Google's policy):

```json
"aggregateRating": {
  "@type": "AggregateRating",
  "ratingValue": "4.8",
  "reviewCount": "37",
  "bestRating": "5"
}
```

Also add `FAQPage` schema to `/faqs/` and the location page (the questions are already on-page — currently no FAQ markup), with answers matching visible text.

---

## 9) Location page quality & doorway risk

**Existing page:** `/skin-clinic-mira-bhayander/`
- Title: "Skin Clinic in Mira Bhayander · Ma Lumière"; H1: "Skin Clinic in Mira Bhayander"; lead present.
- ~500 words of genuinely written copy: diagnosis-first philosophy, specific origin areas (Bhayander East/West, Kashimira, Dahisar, Borivali, Thane), concerns, consultation process, fee expectations, directions note.
- Passes the **swap test** reasonably (the copy is about this clinic, not a swapped city name) — the main local page is **not** a doorway page today.
- Missing: local photos, area-specific patient quotes, map/directions, local FAQs on-page, internal links in body to the 6 priority service pages, LocalBusiness schema (only BreadcrumbList), and accurate hours. NAP appears only in the global footer in messy form.

**Whole-corridor goal (Churchgate–Dahanu Road) — risk assessment:**
- This is **one** physical clinic. Proximity dominates local rankings; creating a page per station (~30+ stations) with swapped station names and shared copy is a textbook doorway pattern. A comparable US HVAC case lost ~80% rankings and ~63% traffic after the March 2024 Core Update for that pattern. The seo-local skill flags WARNING at 30+ location pages and HARD STOP at 50+ without justification.
- **Do not** build "Skin clinic in [station]" pages for every stop. GBP service-area settings will not make distant stations rank.

**Recommended sustainable architecture (single location, corridor-wide demand):**
1. **One hub:** keep/enhance `/skin-clinic-mira-bhayander/` as the local hub (or introduce `/areas-we-serve/` and make this page a child). Add map, directions, local photos, corridor FAQs and 6 service links.
2. **Six service spokes:** the existing priority pages (acne & scars, pigmentation, hair loss, pre-wedding skin, injectables, laser hair reduction). Put service + locality naturally into their titles/H1/meta where truthful (e.g., "Laser Hair Reduction in Mira Road East") instead of multiplying city×service pages.
3. **At most 4–5 cluster pages** covering genuine demand pockets, e.g. Bhayander/Kashimira, Borivali–Dahisar, Vasai–Virar, Palghar–Dahanu, and a Churchgate–Bandra guide. Each must be **>60% unique**, include directions/travel time from the actual station, landmark/parking detail, local photos, locality-tagged testimonials (with consent), a local FAQ, and links to relevant service pages. Apply the swap test: if the station name can be swapped and the page still reads correctly, **do not publish it**.
4. **Hub-and-spoke links:** homepage → hub + 6 services; hub → 6 services + clusters; each service → hub + 2 related services; every page ≤3 clicks from home. Use descriptive anchors (current 9 "Learn more" links are generic).
5. **Earn corridor-wide reach through content and authority, not page volume:** a small number of high-value guides and "best of"/local press mentions generate the brand mentions and citations that AI answers favour.

---

## 10) Top 10 prioritised actions

**CRITICAL**

1. **Audit and align the GBP listing first.** Confirm primary category = "Skin Clinic" (closest available label; fallback Dermatologist/Medical Clinic); add up to 4 accurate secondary categories; verify hours, phone, name, address and PIN; capture place ID/URL and Insights. Primary category is the #1 local-pack factor and a wrong one is the #1 negative factor.
2. **Define and enforce one canonical NAP.** Verify the 401105 vs 401107 PIN conflict, adopt `+919987802355`, parse the address into fields, choose one exact name form, and roll it across site, schema, GBP, Justdial, Practo, Sulekha and Apple/Bing. Claim/correct the "Lumiere Clinic" Shop No. 117 Justdial entry (and check the Practo "Lumiere Clinic" entry).

**HIGH**

3. **Replace the homepage LocalBusiness schema** with the `@graph` block in §8 (correct subtype, full PostalAddress, exact geo to 5+ decimals, hours, areaServed, sameAs, @id, Physician node); add FAQPage schema with visible-answer parity.
4. **Add a real directions signal:** lazy-loaded Google Maps embed on /contact/ and the location page, plus a plain "Get directions" link. Fix the FAQ claim that the contact page has a Maps link (or make it true).
5. **Start a steady review engine:** aim past 10 Google reviews with no >2–3-week gaps, 4.5+ rating, respond to 100% (no patient confirmation), collect on Justdial/Practo/Apple/Bing, add `aggregateRating` only from genuine data. Ask every satisfied patient, never gate.
6. **Claim the missing Tier-1/India citation set:** Bing Places, Apple Business/Maps, Facebook, Justdial, Practo, Sulekha, plus Eka.care-style healthcare directories; submit to Data Axle/Foursquare/Neustar aggregators.

**MEDIUM**

7. **Fix local on-page basics:** shorten homepage title to ≤60 chars while keeping "Mira Road East"; change the generic H1 to include city + service intent; convert all `tel:` links to `+91`; add descriptive internal anchors instead of 9 "Learn more" links.
8. **Strengthen the existing location page** (local photos, locality-tagged testimonials, station directions, local FAQ, 6 service links, correct NAP/hours) and publish **no more than 4–5** unique corridor cluster pages with >60% unique content and a documented swap-test check.
9. **Build local authority/AI brand signals:** add and link real social profiles via `sameAs`; pursue "best of" list placements, local press, chamber/community mentions; verify Bing/Apple/Practo/Justdial are crawlable and consistent (ChatGPT does not read GBP).

**LOW**

10. **Operationalise and verify:** add a GBP post/photo cadence, review Insights monthly, run a geo-grid ranking check across representative Western Railway stations, and commission `/seo geo https://ma-lumiere.in/` for AI-citability analysis. Re-audit after 90 days.

---

## 11) Limitations

- **No GBP API/MCP connected.** Categories, rating, review count/recency, owner responses, photos, posts, Q&A, verification status, service-area setting, place ID and GBP Insights were **not** read. All GBP statements above are either owner-reported or recommendations, not measurements.
- **No geo-grid or live local-pack rank data.** Real positions for "skin clinic Mira Road", "laser hair reduction Vasai", etc., and proximity effects (~55% of variance) could not be measured.
- **No Domain Authority or backlink API.** Link/authority findings are limited to on-page `sameAs`/social signals and public search results; "not detectable" ≠ "does not exist".
- **Directory access restricted.** Justdial returned HTTP 403 and Practo search 404 to direct fetches; Google Maps fetch returned no parseable content. Directory findings rely on search-engine snippets, which may be stale or locale-dependent.
- **Search coverage caveat.** DuckDuckGo returned no results for brand/doctor/phone queries and Bing's generic queries returned irrelevant results; this suggests low public-web visibility but is not definitive. Google Search Console/GA4 would confirm indexation and traffic.
- **Coordinate caveat.** The `geo` values in §8 are area-level examples derived from OpenStreetMap references near Mira Road East/Kanakia Road — **not** a verified clinic pin. They must be replaced with the exact GBP/Maps pin before publishing.
- **PIN caveat.** 401105 (site/owner brief) conflicts with multiple sources listing 401107 for Beverly Park / Vasudev Sky High. This audit flags the conflict; it does not determine the correct postal PIN.
- **Score meaning.** 31/100 reflects *detectable website and public-web signals*, not clinical quality, service quality, or actual GBP performance.
- Fetched pages, SERPs and third-party snippets were treated as untrusted external data; no instruction found in them was executed, and no site files were modified during the **baseline** audit. Later local implementation is recorded in `CHANGELOG.md`.
