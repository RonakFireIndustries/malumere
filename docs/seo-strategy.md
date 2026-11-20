# Local SEO Strategy — Ma Lumière

Location: Mira Road East, serving Mira Bhayander, Maharashtra.
Primary conversion: a booked consultation.

This document records what the theme now does automatically, what still needs to
be done inside WordPress and Google, and the order to do it in. It is written for
the clinic owner and whoever maintains the site.

---

## 1. Objective and positioning

Rank for high-intent local searches around Mira Road, Bhayander and Kashimira,
then convert that traffic into consultations.

Positioning used across the site:

> A skin, hair and laser clinic in Mira Road East, serving Mira Bhayander —
> diagnosis first, procedures second.

Consistency rules:

- Use **Bhayander** in visible copy. **Bhayandar** is included in the area list
  and schema because both spellings are searched.
- The clinic locality is always **Mira Road East**, not just "Mira Road".
- Never publish prices, credentials, result counts or reviews that cannot be
  verified.
- Every page should give a next step: book a consultation or call the clinic.

---

## 2. Keyword map

| Intent | Primary keyword | Supporting searches | Target page |
| --- | --- | --- | --- |
| Local clinic | skin clinic in Mira Bhayander | skin clinic Mira Road, skin doctor Mira Road East, dermatologist near Bhayander | `/skin-clinic-mira-bhayander/` and `/` |
| Face treatments | face treatment in Mira Bhayander | face treatment Mira Road, facial treatment near Bhayander | `/skin-clinic-mira-bhayander/`, `/treatments/` |
| Treatment menu | skin treatments Mira Road | laser clinic Mira Road, skin clinic Bhayander | `/treatments/` |
| Acne | acne treatment in Mira Road | acne clinic Bhayander, acne scars treatment Mira Road | `/treatment/acne-acne-scars/` |
| Pigmentation | pigmentation treatment in Mira Road | melasma treatment Bhayander, uneven skin tone Mira Road | `/treatment/pigmentation-uneven-skin-tone/` |
| Laser hair | laser hair reduction in Mira Road | laser hair removal Bhayander, laser clinic Mira Road | `/treatment/laser-hair-reduction/` |
| Hair loss | hair loss treatment in Mira Road | hair fall clinic Bhayander, PRP near Mira Road | `/treatment/hair-loss-hair-thinning/` |
| Rejuvenation | facial rejuvenation in Mira Road | anti-ageing treatment Mira Bhayander | `/treatment/facial-rejuvenation/` |
| Booking | skin consultation Mira Road | book dermatologist Bhayander | `/book-consultation/` |
| Research | face treatments Mira Bhayander | how to choose a skin clinic | `/2026/11/17/face-treatments-mira-bhayander-guide/` |

Map one primary keyword to one page. Do not create a second page for a synonym
(a separate "skin clinic Bhayander" page would compete with the landing page).

---

## 3. What the theme now does automatically

### Titles and descriptions

- Home: `Skin & Face Treatment Clinic in Mira Bhayander · Ma Lumière`
- Treatments index: `Skin, Hair & Laser Treatments in Mira Road & Bhayander`
- Local landing page: `Skin Clinic in Mira Bhayander | Face & Skin Treatments`
- Treatment pages: `{Treatment} in Mira Road & Bhayander`
- About, Contact, FAQs and booking pages get location-led titles.
- Meta descriptions are generated per view and trimmed to a complete sentence
  where possible, not cut mid-word.
- Every page, treatment and post has an optional **Search appearance** box for a
  custom SEO title, meta description and focus keyword. Empty fields fall back to
  the local defaults.

### Structured data

| Page | Schema |
| --- | --- |
| Home | MedicalClinic + HealthAndBeautyBusiness, WebSite, FAQPage |
| Local landing page | MedicalClinic + HealthAndBeautyBusiness, FAQPage |
| Treatment | Service + MedicalProcedure, MedicalWebPage, FAQPage |
| About | Physician |
| Blog post | BlogPosting |

Clinic schema includes the real address, phone, email, opening hours, price
range, payment methods, area served (including both Bhayander and Bhayandar),
Google Maps link and social profiles. Geo coordinates are optional and should
only be added once copied from the verified Google Business Profile pin.

### Technical hygiene

- `robots.txt` is generated with the correct subdirectory prefix and a sitemap
  reference.
- Thin FAQ single pages are set to `noindex, follow`.
- FAQ entries are removed from the XML sitemap; the FAQ page stays indexed.
- Hero image is preloaded; treatment and doctor images have descriptive alt text.
- Favicon and default logo are served by the theme when no Site Icon is set.

### Editable local facts

Appearance → Customize → **SEO & Local Search** holds the single source of truth:

- Homepage SEO title and description
- Primary service area and locality
- PIN code and country
- Latitude / longitude (optional)
- Price range
- Google Maps link
- Areas served (one per line)
- Practo, Justdial, Lybrate, Facebook, Instagram, YouTube profile links

Change a value there and the titles, descriptions, visible area list and schema
all update together.

---

## 4. Google Business Profile actions

These have more impact on the local map pack than any on-page edit.

1. **Claim and verify** the profile if it is not already verified.
2. Set the primary category to **Skin care clinic**. Add secondary categories
   such as *Dermatologist*, *Medical clinic* and *Laser hair removal service* —
   only categories that genuinely describe the clinic.
3. Match the name, address, phone and hours **exactly** to the website and the
   Customizer values. Do not add keywords to the business name.
4. Add the website link to `https://ma-lumiere.in/skin-clinic-mira-bhayander/`
   (or the homepage if the landing page is not live yet).
5. Add the appointment link to `/book-consultation/`.
6. Upload real photos monthly: exterior, reception, treatment room, doctor,
   equipment. Avoid stock imagery and before/after photos that imply guarantees.
7. Publish a Google post every two weeks: a treatment explainer, a seasonal skin
   tip, or a clinic update.
8. List every service from the treatment menu with a short description.
9. Enable messaging only if someone can reply during clinic hours.
10. Add the Google Maps link to the site's Contact page and footer (already
    linked through the Customizer value).

---

## 5. Citations and consistency

Build the same NAP (name, address, phone) on these profiles, in this order:

1. Google Business Profile
2. Practo
3. Justdial
4. Lybrate
5. Clinic's Instagram and Facebook pages
6. Apple Maps / Bing Places
7. Local directories relevant to Mira Bhayander (housing society guides,
   neighbourhood associations, local business lists)

Rules:

- Use the same phone number everywhere. If a second number is needed, keep the
  primary number identical across all listings.
- Link each profile to the local landing page where possible.
- Add those profile URLs to **SEO & Local Search → Other profile links** so they
  appear in `sameAs` schema.

---

## 6. Reviews

- Ask every patient at the end of a consultation or treatment, in person or by
  follow-up message. Never offer a discount in exchange for a review.
- Send a direct review link, not the general Google Maps page.
- Reply to every review within a week, briefly and without confirming clinical
  details.
- Do not publish patient reviews on the website without written permission.
- Target a steady trickle (2–4 a month) rather than a burst.

---

## 7. Content plan

The library already covers laser hair reduction, first consultations and
post-treatment recovery. Add one useful page every month, in this order:

1. **Acne treatment in Mira Road: what a first visit looks like**
   Links to `/treatment/acne-acne-scars/` and the booking page.
2. **Pigmentation and melasma in Indian skin: why sun protection comes first**
   Links to `/treatment/pigmentation-uneven-skin-tone/`.
3. **Laser hair reduction for men in Mira Bhayander: areas, sessions, downtime**
   Links to `/treatment/laser-hair-reduction/`.
4. **How to choose a skin clinic in Mira Bhayander** — consultation questions,
   red flags, what a proper assessment includes. Links to the landing page.
5. **Pre-wedding skin planning in Mira Road: a 12-week timeline** — no
   guarantees, no countdown pressure.

Each article should:

- Answer the question in the first paragraph.
- Name the concern and the area naturally (Mira Road, Bhayander).
- Link to one treatment page and one booking page.
- End with a single call to action.

---

## 8. Measurement

Track monthly in Google Search Console and Google Business Profile:

- Impressions and clicks for queries containing *Mira Road*, *Bhayander*,
  *Bhayandar* and *skin clinic*.
- Average position for the ten target keywords in section 2.
- Calls, direction requests and website clicks from the Business Profile.
- Bookings attributed to organic traffic (ask "how did you find us?").
- Pages with impressions but no clicks — rewrite the title or description.

Baseline the numbers before making further changes.

---

## 9. 90-day plan

### Weeks 1–2 — foundation

- Deploy the theme changes.
- Run the content seed once on the target site:
  `php wp-content/themes/ma-lumiere/tools/seed-seo-content.php`
- Confirm `blogname` is **Ma Lumière** and the tagline is the local one.
- Set the homepage SEO title and description in the Customizer.
- Submit `https://ma-lumiere.in/wp-sitemap.xml` in Search Console.
- Fix the production HTTP 500 before requesting indexing.
- Verify the landing page, treatment pages and FAQ schema in the Rich Results
  Test.

### Weeks 3–4 — Google Business Profile

- Complete every profile field, service and photo.
- Add the landing page and booking links.
- Add the profile URL to the Customizer's profile links field.
- Start asking for reviews.

### Weeks 5–8 — content and citations

- Publish the first two articles from section 7.
- Build the Practo, Justdial and Lybrate listings.
- Add internal links from the new articles to the landing page and treatments.
- Review Search Console queries and adjust titles/descriptions.

### Weeks 9–12 — consolidation

- Publish the next two articles.
- Refresh the local landing page with any new services or FAQs.
- Add geo coordinates once confirmed from the Business Profile pin.
- Review map-pack rankings and bookings; decide whether a second local page
  (for example Bhayander-specific) is justified by real demand.

---

## 10. Guardrails

- No keyword stuffing in titles, alt text or schema.
- No fake reviews, ratings, awards or credentials.
- No doorway pages for every locality. One strong local page beats ten thin ones.
- Keep the visible address, opening hours and phone identical to Google.
- If Rank Math, Yoast or AIOSEO is installed later, the theme SEO output steps
  aside automatically so there is only one source of truth.
