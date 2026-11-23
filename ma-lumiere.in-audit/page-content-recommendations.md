# Ma Lumière — Page-by-Page Content Recommendations

**Site:** https://ma-lumiere.in/ · **Audit folder:** `ma-lumiere.in-audit/` · **Prepared:** 2026-11-23
**Language:** English (Indian English) throughout. **Scope:** baseline content recommendations; no site files were edited to produce this document. Implemented on-page changes are recorded in `CHANGELOG.md` and verified in `findings/`.

All keyword figures in the companion `keyword-research.csv` are labelled **ESTIMATE**. No GSC, GA4, GBP, CWV or backlink data was available, so this document makes no measured or ranking claims.

---

## Global rules — apply to every page

- **Verified facts only.** Use exactly: clinic name **Ma Lumière**; clinician **Dr. Rishita Ray**; professional description **Aesthetic Physician** (and no other title); **Mira Road East, Maharashtra 401105**; phone **+91 99878 02355**.
- **Never invent or imply:** degrees, registrations, years of experience, reviews, ratings, testimonials, patient images, Place ID, clinical results, or branches/centres elsewhere. There is **one physical clinic** — say "one clinic in Mira Road East" and describe other areas only as places patients travel from.
- **Botox lives on the existing URL** `/treatment/injectables-facial-contouring/`. Do not create a new Botox URL, and do not create one page per station.
- **Medically cautious, no guaranteed outcomes.** Use "may", "can", "typically", "often", "varies between individuals", "depends on assessment". Never use "cure", "permanent", "guaranteed", "100% safe" or fixed result promises.
- **FAQPage schema:** do **not** add it for Google rich-result benefit — Google retired FAQ rich results in 2026. Keep the existing on-page HTML FAQs; they are useful for patients and agents without markup.
- **Schema caution:** no `aggregateRating`/`review`, no `geo` coordinates, no `Place ID`, no `sameAs` URLs, no credentials and no `alumniOf` unless the owner supplies verified values. Add `reviewedBy` (Person) and a real `lastReviewed` date on medical pages.
- **CTAs:** primary "Book a consultation" → `/book-consultation/`; secondary "Call +91 99878 02355" → `tel:+919987802355`; tertiary "Contact us" → `/contact/`.
- **Western Railway corridor:** Tier 1 = Mira Road, Bhayander, Dahisar, Borivali, Kandivali. Tier 2 = Malad, Goregaon, Jogeshwari, Andheri, Vasai, Naigaon, Nalasopara, Virar. Mention as service areas, never as separate clinics.

---

## 1. Homepage — `/`

- **Primary keyword:** skin clinic mira road
- **Secondary keywords:** skin clinic mira bhayander, aesthetic clinic mira road, acne treatment mira road, hair loss treatment mira road, pigmentation treatment mira road, botox mira road
- **H1:** Skin, Hair and Aesthetic Care in Mira Road East
- **H2 structure:**
  1. Our four areas of care
  2. Acne and acne scars
  3. Injectables and facial contouring
  4. Hair loss and hair thinning
  5. Pigmentation and uneven skin tone
  6. One clinic, serving the Western Railway corridor
  7. Meet Dr. Rishita Ray, Aesthetic Physician
  8. How your first consultation works
  9. Common questions
  10. Book a consultation
- **Body guidance:** 40–60 word answer-first paragraph under the H1 naming the clinic, the single Mira Road East location, the four pillars and the phone number. Give the four pillars **equal visual weight** (same card size, same word count, same link treatment) — no "most popular" ordering. Add a short verified NAP block and a plain-language corridor sentence. Keep the existing on-page FAQ block; do not add markup.
- **Internal links:** four pillar pages; `/skin-clinic-mira-bhayander/`; `/treatments/`; `/about/`; `/faqs/`; `/blog/`; `/book-consultation/`; `/contact/`. Replace generic "Learn more" anchors with descriptive ones such as "Acne scar treatment in Mira Road East".
- **Image alt guidance:** describe only what is genuinely shown, e.g. "Consultation room at Ma Lumière, Mira Road East". Do not use patient faces or skin close-ups unless documented consent exists; otherwise use neutral clinic/abstract images. Add `width`/`height` or `aspect-ratio`, and a real 1200×630 `og:image`.
- **Schema recommendation:** `@graph` with `MedicalClinic`/`HealthAndBeautyBusiness` (full `PostalAddress` incl. 401105, E.164 phone, verified `openingHoursSpecification` only), `WebSite`, `WebPage`, `BreadcrumbList`, `Person` for Dr. Rishita Ray with `jobTitle: "Aesthetic Physician"` and `worksFor`, plus `reviewedBy`/`lastReviewed`. Omit ratings, coordinates, Place ID and unverified `sameAs`.
- **CTA:** Book a consultation → `/book-consultation/`; Call +91 99878 02355 → `tel:`.

---

## 2. Treatments hub — `/treatments/`

- **Primary keyword:** skin treatments mira road
- **Secondary keywords:** aesthetic treatments mira bhayander, acne scar treatment mira road, botox treatment near me, hair thinning treatment mira road, pigmentation treatment mira bhayander
- **H1:** Treatments for Acne, Hair, Pigmentation and Facial Contouring
- **H2 structure:**
  1. Acne and acne scars
  2. Injectables and facial contouring
  3. Hair loss and hair thinning
  4. Pigmentation and uneven skin tone
  5. Other concerns we also assess
  6. Not sure which treatment you need?
  7. How consultations and plans work
- **Body guidance:** one balanced card per pillar with a 2–3 sentence answer-first summary (what it is, who it may suit, that assessment comes first). Add a short note that suitability and the plan are decided at consultation and that results vary. Avoid duplicate H1/title patterns copied from the pillar pages.
- **Internal links:** the four `/treatment/*` pages, `/faqs/`, `/skin-clinic-mira-bhayander/`, `/book-consultation/`.
- **Image alt guidance:** descriptive, non-patient imagery per card, e.g. "Illustration of a facial consultation for pigmentation concerns". No before/after placeholders.
- **Schema recommendation:** `CollectionPage` + `ItemList` pointing to the four service URLs + `BreadcrumbList` + `reviewedBy`/`lastReviewed`.
- **CTA:** Discuss your concern → `/book-consultation/`.

---

## 3. Acne pillar — `/treatment/acne-acne-scars/`

- **Primary keyword:** acne treatment mira road
- **Secondary keywords:** acne scar treatment mira road, pimples treatment bhayander, acne clinic near me, acne scar removal mira bhayander
- **H1:** Acne and Acne Scar Treatment in Mira Road East
- **H2 structure:**
  1. What acne and acne scars are
  2. How acne is assessed
  3. Treatment options we may discuss
  4. Why active acne is managed before scars
  5. What a staged plan can involve
  6. Preparation and aftercare
  7. Possible risks and limitations
  8. Cost factors
  9. Questions patients ask
  10. Book a consultation
- **Body guidance:** expand to 700–1,000 genuinely unique words. Cover mechanism, candidacy, common contraindications, typical downtime and a staged timeline; explain that improvement builds gradually and varies. Keep the existing medically sensible sequencing (active acne first). Add 2–3 authoritative dermatology citations and a visible `lastReviewed`/reviewed-by line. Remove any `[BEFORE/AFTER IMAGES REQUIRED]` placeholder. No clearance guarantees.
- **Internal links:** `/treatment/pigmentation-uneven-skin-tone/` (scarring vs pigmentation), `/skin-clinic-mira-bhayander/`, `/faqs/`, `/blog/`, the compliant results/expectations page, `/book-consultation/`.
- **Image alt guidance:** "Acne and acne scar consultation at Ma Lumière, Mira Road East". No patient skin close-ups without documented consent.
- **Schema recommendation:** `MedicalWebPage` + `MedicalProcedure` + `about` `MedicalCondition` (acne vulgaris/acne scarring), `preparation`, `followup`, `citation`, `reviewedBy`, `lastReviewed`. No FAQPage markup.
- **CTA:** Book an acne assessment → `/book-consultation/`.

---

## 4. Botox / injectables pillar — `/treatment/injectables-facial-contouring/` (existing URL only)

- **Primary keyword:** botox treatment mira road
- **Secondary keywords:** botox clinic bhayander, facial contouring mira road, botox near me, injectables mira bhayander
- **H1:** Injectables and Facial Contouring in Mira Road East
- **H2 structure:**
  1. What injectables and facial contouring can address
  2. How the consultation works
  3. How the treatment is performed
  4. What to expect afterwards
  5. How long effects may last
  6. Possible risks and limitations
  7. Cost factors
  8. Questions patients ask
  9. Book a consultation
- **Body guidance:** 700–1,000 words, patient-focused and cautious. Describe assessment, suitability and aftercare; discuss duration as variable ("varies by person and product used") rather than a fixed promise. Do not name specific brands or devices unless the owner confirms they are used. No frozen-look or guaranteed-appearance claims.
- **Internal links:** the other three pillars, `/skin-clinic-mira-bhayander/`, `/faqs/`, `/blog/`, `/book-consultation/`. Do **not** link to a new Botox URL.
- **Image alt guidance:** "Facial contouring consultation at Ma Lumière, Mira Road East". No patient before/after images without documented consent.
- **Schema recommendation:** `MedicalWebPage` + `MedicalProcedure` + `preparation`/`followup`/`citation` + `reviewedBy`/`lastReviewed`. No FAQPage markup, no ratings.
- **CTA:** Ask about injectables → `/book-consultation/`.

---

## 5. Hair pillar — `/treatment/hair-loss-hair-thinning/`

- **Primary keyword:** hair loss treatment mira road
- **Secondary keywords:** hair thinning clinic bhayander, hair fall treatment near me, hair loss doctor mira bhayander
- **H1:** Hair Loss and Hair Thinning Assessment in Mira Road East
- **H2 structure:**
  1. Common causes of hair thinning
  2. How hair loss is assessed
  3. Treatment options we may discuss
  4. What the plan involves
  5. Timeline and realistic expectations
  6. Home care and aftercare
  7. Possible risks and limitations
  8. Cost factors
  9. Questions patients ask
  10. Book a consultation
- **Body guidance:** distinguish common patterns and note that medical causes may need evaluation; avoid promising regrowth or a fixed timeline. Include candidacy, contraindications and variability statements plus authoritative citations. Do not imply a transplant service unless the owner confirms it.
- **Internal links:** the other three pillars, `/skin-clinic-mira-bhayander/`, `/faqs/`, `/blog/`, `/book-consultation/`.
- **Image alt guidance:** "Hair and scalp assessment at Ma Lumière, Mira Road East". No patient scalp images without documented consent.
- **Schema recommendation:** `MedicalWebPage` + `MedicalProcedure` + `MedicalCondition`, `citation`, `reviewedBy`, `lastReviewed`. No FAQPage markup.
- **CTA:** Book a hair assessment → `/book-consultation/`.

---

## 6. Pigmentation pillar — `/treatment/pigmentation-uneven-skin-tone/`

- **Primary keyword:** pigmentation treatment mira road
- **Secondary keywords:** uneven skin tone clinic bhayander, dark spots treatment mira road, melasma treatment mira bhayander
- **H1:** Pigmentation and Uneven Skin Tone Treatment in Mira Road East
- **H2 structure:**
  1. What causes pigmentation
  2. How pigmentation is assessed
  3. Treatment options we may discuss
  4. Staged care and sun protection
  5. What to expect over time
  6. Preparation and aftercare
  7. Possible risks and limitations
  8. Cost factors
  9. Questions patients ask
  10. Book a consultation
- **Body guidance:** 700–1,000 unique words on assessment, staged care and daily sun protection; explain that change is usually gradual and recurrence can occur. Avoid "cure" and "permanent removal" phrasing in both copy and the answer to "can dark spots be removed permanently". Add citations and review metadata.
- **Internal links:** `/treatment/acne-acne-scars/`, the other two pillars, `/skin-clinic-mira-bhayander/`, `/faqs/`, `/blog/`, `/book-consultation/`.
- **Image alt guidance:** "Pigmentation and uneven skin tone consultation at Ma Lumière, Mira Road East". No patient face images without documented consent.
- **Schema recommendation:** `MedicalWebPage` + `MedicalProcedure` + `MedicalCondition`, `citation`, `reviewedBy`, `lastReviewed`. No FAQPage markup.
- **CTA:** Book a pigmentation assessment → `/book-consultation/`.

---

## 7. Location hub — `/skin-clinic-mira-bhayander/`

- **Primary keyword:** skin clinic mira bhayander
- **Secondary keywords:** skin clinic mira road, aesthetic clinic bhayander, skin doctor dahisar, skin clinic borivali
- **H1:** Skin and Aesthetic Clinic in Mira Road East, Serving Mira Bhayander
- **H2 structure:**
  1. Where we are
  2. Getting here from Mira Road and Bhayander
  3. Areas we serve along the Western Railway corridor
  4. Treatments patients travel to us for
  5. What happens at your first visit
  6. Fees and consultations
  7. Questions about visiting us
  8. Book a consultation
- **Body guidance:** full verified NAP, click-to-call and a genuine directions link. Add a real "Areas we serve" section listing Tier 1 then Tier 2, explicitly stating this is **one clinic** and these are travel areas. Keep travel-time statements only if the owner verifies them. Do not build per-station pages or claim a Place ID.
- **Internal links:** four pillars, `/contact/`, `/book-consultation/`, `/faqs/`, `/blog/`, `/treatments/`.
- **Image alt guidance:** use only a real photo, e.g. "Exterior of Ma Lumière clinic, Vasudev Sky High, Mira Road East"; otherwise a neutral map graphic with alt "Map showing Ma Lumière in Mira Road East".
- **Schema recommendation:** `MedicalClinic`/`LocalBusiness` with full `PostalAddress` (401105), E.164 phone, verified `openingHoursSpecification`, `areaServed` (Tier 1 + Tier 2 names), `BreadcrumbList`, `reviewedBy`/`lastReviewed`. Omit `geo`, Place ID and ratings unless verified.
- **CTA:** Book a consultation → `/book-consultation/`; Get directions → `/contact/`.

---

## 8. About — `/about/`

- **Primary keyword:** aesthetic physician mira road
- **Secondary keywords:** skin specialist mira road, dr rishita ray mira road, aesthetic clinic mira bhayander
- **H1:** About Ma Lumière and Dr. Rishita Ray
- **H2 structure:**
  1. Who we are
  2. Dr. Rishita Ray, Aesthetic Physician
  3. How we approach consultations
  4. Our four areas of care
  5. Visit our Mira Road East clinic
  6. Book a consultation
- **Body guidance:** identify the clinician only as **Dr. Rishita Ray, Aesthetic Physician**. Do not add degrees, registration numbers, years in practice, training claims or unverified profile links. Focus on consultation approach, staged plans, realistic expectations and the single location.
- **Internal links:** four pillars, `/skin-clinic-mira-bhayander/`, `/book-consultation/`, `/blog/`.
- **Image alt guidance:** use a portrait only if a real, attributed image exists; alt "Dr. Rishita Ray, Aesthetic Physician at Ma Lumière". Never substitute a stock image labelled as the clinician.
- **Schema recommendation:** `AboutPage` + `Person` (`name`, `jobTitle: "Aesthetic Physician"`, `worksFor` clinic) + `BreadcrumbList`. No `alumniOf`, credentials or `sameAs` unless verified.
- **CTA:** Meet us in person → `/book-consultation/`.

---

## 9. Contact — `/contact/`

- **Primary keyword:** contact skin clinic mira road
- **Secondary keywords:** skin clinic phone mira road, skin clinic address mira bhayander, book skin consultation mira road
- **H1:** Contact Ma Lumière Skin Clinic in Mira Road East
- **H2 structure:**
  1. Our address
  2. Call or WhatsApp
  3. Opening hours
  4. Getting here
  5. Send an enquiry
  6. Book online
- **Body guidance:** verified address, `tel:+919987802355`, a real directions link and only owner-verified hours. Make the booking path obvious. Do not invent a map Place ID or embed a fabricated pin.
- **Internal links:** `/book-consultation/`, `/skin-clinic-mira-bhayander/`, `/faqs/`.
- **Image alt guidance:** "Map and directions to Ma Lumière, Mira Road East" only for a genuine map screenshot; otherwise omit.
- **Schema recommendation:** `ContactPage` + clinic `MedicalClinic`/`LocalBusiness` entity (full address, phone, verified hours) + `BreadcrumbList`. No ratings, coordinates or Place ID.
- **CTA:** Call +91 99878 02355 → `tel:`; Book a consultation → `/book-consultation/`.

---

## 10. FAQs — `/faqs/`

- **Primary keyword:** skin clinic faqs mira road
- **Secondary keywords:** acne treatment questions, botox safety questions, hair treatment questions, pigmentation questions
- **H1:** Frequently Asked Questions About Skin and Aesthetic Care
- **H2 structure:**
  1. About the clinic and visits
  2. Consultations and costs
  3. Acne and acne scars
  4. Injectables and facial contouring
  5. Hair loss and hair thinning
  6. Pigmentation and uneven skin tone
  7. Aftercare and safety
- **Body guidance:** keep the seven existing on-page answers and expand into the four equal pillars. Each answer: question as heading, 40–60 word answer-first response, cautious wording, no guaranteed outcome. Cover location/visits, first consultation, cost process, staged acne/scar care, injectable safety and duration, hair assessment, pigmentation expectations, sun protection and aftercare. Do **not** add `FAQPage` markup for rich-result benefit — Google retired FAQ rich results in 2026; the visible FAQs are sufficient.
- **Internal links:** link each answer cluster to its pillar page, plus `/contact/` and `/book-consultation/`.
- **Image alt guidance:** not image-led; omit decorative FAQ images.
- **Schema recommendation:** `WebPage` (or `CollectionPage`) + `BreadcrumbList` + `reviewedBy`/`lastReviewed`. **No FAQPage schema.**
- **CTA:** Still have a question? Book a consultation → `/book-consultation/`.

---

## 11. Blog — `/blog/`

- **Primary keyword:** skin care blog mira road
- **Secondary keywords:** acne tips blog, hair fall tips, pigmentation tips, botox guide
- **H1:** Skin and Hair Guides from Ma Lumière
- **H2 structure:**
  1. Acne and acne scars
  2. Injectables and facial contouring
  3. Hair loss and hair thinning
  4. Pigmentation and uneven skin tone
  5. Preparation and aftercare
  6. Clinic and local guides
- **Body guidance:** index posts under categories matching the four pillars plus preparation/aftercare and local guides; give each category a short 60–100 word intro. Every medical post needs a named reviewer (Dr. Rishita Ray, Aesthetic Physician), a real `lastReviewed` date and 2–3 authoritative citations. Fix heading jumps (H1→H3) and add a self-canonical.
- **Internal links:** four pillars, `/faqs/`, `/book-consultation/`, relevant category archives.
- **Image alt guidance:** descriptive feature-image alts; no patient imagery without documented consent; supply `og:image`.
- **Schema recommendation:** index = `CollectionPage` + `BreadcrumbList`. Posts = `BlogPosting` with `author` as `Person` (Dr. Rishita Ray, "Aesthetic Physician" — not `admin`/Organization), `reviewedBy`, `lastReviewed`, `citation`, `image`. No FAQPage markup.
- **CTA:** Read a guide, then book a consultation → `/book-consultation/`.

---

## 12. Results / expectations page — `/results/acne-treatment-case-reference/` (existing placeholder URL)

- **Primary keyword:** acne treatment results mira road
- **Secondary keywords:** acne scar improvement timeline, what to expect acne treatment, skin treatment expectations
- **H1:** What to Expect Over Time from Acne Treatment
- **H2 structure:**
  1. Why we do not show unverified before-and-after images
  2. How improvement is usually described
  3. Factors that affect the timeline
  4. Why plans are staged
  5. How progress is reviewed
  6. Questions to ask at your consultation
  7. Book a consultation
- **Body guidance:** **education only.** Remove every `[REAL, CONSENT-APPROVED CASE REQUIRED…]` and `[BEFORE/AFTER IMAGES REQUIRED]` placeholder from this page and from metadata. Publish no patient images, case studies, clinical results, ratings or testimonials. Include an explicit variability statement ("changes differ between individuals; no outcome can be guaranteed"). Keep `noindex` until fully compliant, per the existing action plan.
- **Internal links:** acne pillar, `/faqs/`, `/blog/`, `/book-consultation/`.
- **Image alt guidance:** no patient images; use neutral educational graphics with alts such as "Illustration of the stages of a staged acne care plan".
- **Schema recommendation:** `MedicalWebPage` + `reviewedBy`/`lastReviewed`. No `aggregateRating`, `review` or patient-image markup.
- **CTA:** Discuss realistic expectations → `/book-consultation/`.

---

## 13. Treatment-type archives — `/treatment-type/*` (e.g. `/treatment-type/acne-acne-scars/`, `/treatment-type/pigmentation-uneven-skin-tone/`)

- **Primary keyword (per archive):** e.g. "acne and acne scar treatment options" / "pigmentation treatment options"
- **Secondary keywords:** acne treatments category, hair loss treatment options, injectables guides, pigmentation guides
- **H1:** Acne and Acne Scar Treatment Guides *(archive-specific and clearly different from the service-page H1)*
- **H2 structure:**
  1. About this treatment area
  2. Treatment pages and guides
  3. Questions patients ask
  4. Book a consultation
- **Body guidance:** 250–400 **unique** words per archive. Do not copy the service-page excerpt. Curate links to the matching real `/treatment/*` page and relevant blog posts. Give each archive a self-canonical if it stays indexable; otherwise `noindex, follow` and remove from the sitemap. Do **not** create new archives purely to target keywords, and never let archives outrank or duplicate the four pillar pages.
- **Internal links:** matching `/treatment/*` page, sibling archives, `/blog/`, `/book-consultation/`.
- **Image alt guidance:** remove `[IMAGE REQUIRED]` placeholders; use one real descriptive image or none.
- **Schema recommendation:** if indexable, `CollectionPage` + `BreadcrumbList` + `reviewedBy`/`lastReviewed`. Do **not** put `MedicalProcedure` on archives; reserve it for the pillar pages.
- **CTA:** Speak to the clinic → `/book-consultation/`.

---

## Cross-page linking map (one clinic, four equal pillars)

| From | Link to |
|---|---|
| Homepage / Treatments hub | all four pillars, location hub, FAQs, blog, booking, contact |
| Each pillar | the other three pillars (contextually), location hub, FAQs, relevant blog guides, booking |
| Location hub | four pillars, contact, booking, FAQs, blog |
| About | pillars, location hub, blog, booking |
| FAQs / Blog | relevant pillar per answer/category, booking |
| Results/expectations | acne pillar, FAQs, blog, booking |
| Archives | matching pillar, blog, booking |

No new Botox URL. No per-station location pages. No fabricated credentials, results, images or ratings.
