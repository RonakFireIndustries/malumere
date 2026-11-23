# Local SEO — Condensed Findings

**Site:** https://ma-lumiere.in/
**Audit date:** 2026-11-23
**Business type:** Single brick-and-mortar skin/aesthetic clinic with service-area framing (Mira Road East, Thane district)
**Vertical:** Healthcare — medical aesthetic / skin clinic (Dr Rishita Ray)
**Target market:** Entire Western Railway corridor, Churchgate → Dahanu Road

## Local SEO Score: 31/100

| Dimension | Weight | Score /100 | Weighted | Verdict |
|---|---:|---:|---:|---|
| GBP signals | 25% | 30 | 7.50 | GBP exists (owner-reported) but no detectable integration: no map, place ID, review widget or GBP URL |
| Reviews & reputation | 20% | 15 | 3.00 | No rating/review schema, no on-site testimonials; GBP review data inaccessible |
| Local on-page SEO | 20% | 62 | 12.40 | Best area: local title, visible NAP, 6 service pages, one decent location page; weak H1, no map/local proof |
| NAP consistency & citations | 15% | 25 | 3.75 | Messy matching NAP, no `+91`, PIN conflict, variant directory name, Tier-1 citations mostly missing |
| Local schema | 10% | 30 | 3.00 | `MedicalClinic` present but one-line address; no geo, hours, areaServed, sameAs, @id or Physician node |
| Local link & authority | 10% | 10 | 1.00 | No social `sameAs`, no detectable press/chamber/"best of" mentions |
| **TOTAL** | **100%** | — | **30.65 → 31** | **Weak local footprint; solid underlying service content** |

Score reflects *detectable website and public-web signals*, not clinical quality or actual GBP performance.

## Top 5 Findings

1. **GBP exists but the website has zero local-pack integration (Critical).** No Google Maps embed, directions link, place ID, GBP URL or review widget on any checked page. The FAQ even claims the contact page has a Maps directions link — it does not. Categories, verification, hours, photos, posts and Insights are unverifiable without GBP access. Recommend primary category "Skin Clinic" (closest India label; fallback Dermatologist/Medical Clinic), never Beauty Salon.
2. **No review proof anywhere (High).** No `aggregateRating`/`review` schema, no on-site testimonials or review widget. Competitors (Newderma, Cosderma, Ariansh, UniDerm) all show testimonials/social proof. Need steady review velocity past 10 reviews, 4.5+ stars, no >2–3-week gaps, responses to 100% (never confirm a reviewer is a patient).
3. **NAP inconsistency and a PIN conflict (Critical).** Site uses "MA LUMIÈRE" / "Ma Lumière"; Justdial and Practo snippets show "Lumiere Clinic" (Vasudev Sky High, Shop No. 117 — likely same premises). Phone `9987802355` lacks `+91`. Site/owner use PIN **401105**, but multiple sources give **401107** for Beverly Park/Vasudev Sky High — verify, do not guess. Address is one unparsed string in both HTML and schema.
4. **Incomplete local schema (High).** Homepage JSON-LD is `["MedicalClinic","HealthAndBeautyBusiness"]` with a single messy `streetAddress`; missing structured address fields, exact geo (5+ decimals), opening hours, areaServed, sameAs, @id, `Physician` node for Dr Rishita Ray and FAQPage markup. A ready-to-use `@graph` replacement is in the full report (§8).
5. **Weak local authority and doorway risk if scaling by station (High).** No social `sameAs`, no detectable Bing/Apple/Facebook/Sulekha/Practo/Justdial ownership, no "best of" or press mentions. For corridor coverage, do **not** create one page per station: use one hub (`/skin-clinic-mira-bhayander/`), the 6 existing service spokes, and **at most 4–5** genuinely unique (>60%) corridor cluster pages that pass the swap test.

## Priority Actions (first 6)

1. Audit GBP: confirm primary/secondary categories, exact name/address/phone/PIN/hours, capture place ID and Insights.
2. Define one canonical NAP, resolve 401105 vs 401107, adopt `tel:+919987802355`, claim/correct the "Lumiere Clinic" Justdial and Practo entries.
3. Replace homepage schema with the full `MedicalClinic` + `Physician` `@graph`; add FAQPage schema.
4. Add a lazy-loaded map and real directions link on `/contact/` and the location page; fix the false FAQ claim.
5. Launch a compliant review cadence across Google, Justdial, Practo, Apple and Bing.
6. Claim Bing Places, Apple Business/Maps, Facebook, Sulekha and data aggregators; add real `sameAs` links.

## Limitations

No GBP API/MCP (categories, rating, reviews, photos, posts, verification and Insights unread); no geo-grid/live local-pack positions; no DA/backlink API; Justdial 403, Practo 404, Google Maps fetch unparseable; DuckDuckGo/Bing coverage noisy. PIN conflict and example coordinates in the full report require owner verification. Fetched content was treated as untrusted data; no site files were modified during the baseline analysis. Later local implementation is recorded in `../CHANGELOG.md`.

**Full report:** `../LOCAL-SEO-ANALYSIS-ma-lumiere.in.md`
