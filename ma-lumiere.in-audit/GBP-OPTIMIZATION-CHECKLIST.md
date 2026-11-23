# Google Business Profile (GBP) Optimization Checklist

**Site:** https://ma-lumiere.in/
**Date:** 2026-11-23
**Business:** Ma Lumière — **one** physical medical skin & aesthetic care clinic, Mira Road East, Maharashtra 401105, India. Verified phone **+91 99878 02355**. Clinician named on site: **Dr. Rishita Ray**; only verified professional description: **"Aesthetic Physician."**
**Status:** Recommendations and owner tasks only. **No GBP dashboard, API, or Insights access was available.** No categories, rating, review count, hours, photos, posts, verification state, or Place ID were read. Nothing in this checklist is a measured finding — every GBP field below must be checked or entered by the owner.

---

## 0) Mandatory first step — canonical GBP URL (do this before any website integration)

> **Exact instruction:** The owner-supplied share URL `https://share.google/V7HQuDrctGMVpIoii` resolved during verification to a **Google share/search page, not a canonical Google Maps place URL**. The owner must open the listing in the **Google Business Profile dashboard**, use **Share → Copy link / "Share profile"** (or the canonical Maps place URL), and convert it into the **canonical GBP/Maps URL** before it is used anywhere on the website — specifically before adding it to the `sameAs` array in the clinic's JSON-LD.
>
> **Do not invent, guess, or copy a Place ID from a third party.** No Place ID was supplied in any audit artifact, and none should be fabricated. If a Place ID is later legitimately supplied by the owner from the GBP dashboard, it may be used to build a `https://www.google.com/maps/search/?api=1&query=...&query_place_id=...` link — until then, the site must keep its current **address-based** directions link. The share URL above must **not** be placed in `sameAs`, `hasMap`, or any canonical/structured-data field.

**Owner confirmation required:** canonical GBP URL obtained ☐ · verification status confirmed ☐ · exact Place ID (if owner supplies it) recorded ☐

---

## 1) Profile fields checklist

Legend: **[OWNER-VERIFY]** = must be read/confirmed in the GBP dashboard · **[SET]** = action to take in GBP · **[DEV]** = website/developer task · **[RECOMMENDATION]** = proposed value, subject to verification.

| Field | Proposed / required value | Status | Notes |
|---|---|---|---|
| **Primary category** | **Skin Clinic** *(proposed — subject to actual availability in the India category picker)* | [SET] [OWNER-VERIFY] | If "Skin Clinic" is not offered, use the closest accurate label: **Dermatologist** or **Medical Clinic**. **Never make "Beauty Salon" primary** — it dilutes medical intent. Primary category is the strongest local-pack factor; a wrong one is the strongest negative factor. |
| **Secondary categories** | Up to 4, only where genuinely offered: Dermatologist, Laser Hair Removal / Hair Removal Service, Medical Spa, Beauty Salon | [SET] [OWNER-VERIFY] | Add only services actually provided. Do not add categories to chase keywords. |
| **Business name** | One exact form, matching the site's displayed brand; must match GBP exactly | [OWNER-VERIFY] | The site now uses a single consistent name. Use that exact GBP spelling (including accent/case) in schema and citations; do not append keywords. |
| **Description** | See compliant draft in §2 | [SET] | 750 chars max; no unverifiable claims, no keyword stuffing, no URLs, no promotional jargon. |
| **Services** | See pillar-aligned list in §3 | [SET] | Use GBP "Services" items with accurate names/descriptions; link each to the matching on-site page. Botox stays on the injectables page. |
| **Hours** | Site currently displays: Tue–Sat 12:00–21:00; Sun 12:00–17:00; Mon closed | [OWNER-VERIFY] [SET] | **Owner must confirm these against GBP** — they were never verified here. GBP hours and website hours must match exactly, including holidays. Hours are **not** in the deployed schema yet (owner action). |
| **Address** | Shop No. 117, 1st Floor, Vasudev Sky High, Kanakia Road, Beverly Park, Mira Road East, Maharashtra 401105, India | [OWNER-VERIFY] | Confirm PIN **401105** matches GBP; the baseline flagged a 401105 vs 401107 conflict in third-party sources. Use one value everywhere; do not silently change it. Ensure the pin is dropped on the actual clinic, not the building centroid. |
| **Phone** | **+91 99878 02355** (site uses `+919987802355` in `tel:`/schema) | [OWNER-VERIFY] | Verified number. Must match GBP exactly. |
| **Website URL** | Decision needed: homepage `/` vs. local hub `/skin-clinic-mira-bhayander/` | [OWNER-VERIFY] [DEV] | A practitioner "Diversity Update" study suggests not pointing GBP at the strongest organic page (homepage); a dedicated local hub is a reasonable alternative. Not Google-confirmed — owner decides. **Do not** point it at the share URL. |
| **Appointment / booking link** | https://ma-lumiere.in/book-consultation/ (with UTMs — §7) | [SET] [DEV] | Use GBP's appointment/booking field if available, with tracked URL. Must be a real, working page. |
| **Photos** | Owner-supplied, genuine clinic/interior/team imagery only | [SET] | Never invent or stock-pass-off-as-real photos. See §4. |
| **Posts** | Owner-set cadence (e.g. 1/week, genuine updates/offers) | [SET] | See §4. Posts can generate Post Justifications; no confirmed direct ranking effect. |
| **Q&A** | Answers mirrored on website FAQ | [SET] [DEV] | GBP Q&A API discontinued 2025-11-03 and public Q&A reportedly being phased out — prioritise on-site `/faqs/` answers and the GBP description. Do not seed fake Q&A. |
| **Reviews** | Compliant acquisition + 100% owner responses | [SET] | See §5. Never gate, incentivise, or fabricate. |
| **Insights** | Calls, direction requests, website clicks, search terms (90 days) | [OWNER-VERIFY] | Export monthly; this is the data that turns the static local score into a measured one. |
| **UTM tracking** | Scheme in §7 | [DEV] [SET] | Apply to GBP website and appointment links only. |
| **Attributes / accessibility / payment** | Owner-verified facts only | [OWNER-VERIFY] | e.g. wheelchair access, appointment required, payment methods — only if true. |
| **Google Verified badge eligibility** | Owner confirms verification status | [OWNER-VERIFY] | Do not claim verified status in website copy until confirmed. |

---

## 2) Compliant business description (draft — owner to finalise)

> **Draft (no unverifiable claims):**
> "Ma Lumière is a medical skin and aesthetic care clinic in Mira Road East, Maharashtra, led by Dr. Rishita Ray, an Aesthetic Physician. We offer consultation-led care for acne and acne scars, pigmentation and uneven skin tone, hair loss and thinning, and injectable and contouring treatments. Every plan begins with an assessment of your skin and a discussion of what is realistically achievable. Consultations are by appointment. Call +91 99878 02355 or book online."

**Rules:** do **not** add degrees, registration numbers, years of experience, board certifications, "best"/"No. 1" claims, or patient counts — none are verified. Keep it factual, locality-specific, and consistent with the website. Avoid URLs and keyword stuffing.

---

## 3) Services to list (aligned with site pillars)

Use exact, truthful service names; link each to its existing page. **Botox remains on the existing injectables URL — do not create a separate Botox page or GBP service pointing at a new URL.**

| GBP service name (proposed) | Website page |
|---|---|
| Acne & Acne Scar Treatment | `/treatment/acne-acne-scars/` |
| Botox & Injectable Aesthetic Treatments | `/treatment/injectables-facial-contouring/` |
| Hair Loss & Hair Thinning Assessment | `/treatment/hair-loss-hair-thinning/` |
| Pigmentation & Uneven Skin Tone Treatment | `/treatment/pigmentation-uneven-skin-tone/` |
| Laser Hair Reduction | `/treatment/laser-hair-reduction/` |
| Pre-Wedding Skin Preparation | `/treatment/pre-wedding-skin-preparation/` |
| Skin Consultation | `/book-consultation/` |

Add other on-site treatments only if genuinely offered and accurately described. Descriptions should mention candidacy and that a consultation comes first — no outcome guarantees.

---

## 4) Photos, posts and proof

- **Photos:** owner supplies genuine exterior (for finding the clinic), interior/reception, treatment rooms, and team images; descriptive filenames and alt text on site. Count and photo types must be recorded from the dashboard. Never fabricate or present stock imagery as the clinic.
- **Posts:** owner sets a realistic cadence (e.g. weekly genuine update, seasonal skin note, announcement). Use only verified claims; no before/after images without documented patient consent and de-identification.
- **Q&A:** migrate the useful on-site FAQ answers into the GBP description where relevant; keep `/faqs/` as the canonical answer set with visible-text parity. Do not post fabricated questions or answers.
- **Nothing here is measurable in this audit** — counts, cadence and engagement must come from the owner.

---

## 5) Compliant review acquisition strategy

**Non-negotiable rules (Google + FTC-aligned):**
- **No incentives** — no discounts, freebies, gifts, or "leave a review to enter a draw."
- **No fake reviews** — no purchased reviews, no review farms, no writing reviews on behalf of patients, no employee/family reviews pretending to be patients.
- **No review gating** — do not pre-screen for satisfaction; do not ask only happy patients or suppress unhappy ones. Ask **all** patients uniformly. Review gating is prohibited (FTC penalties can exceed $50,000 per violation).
- **No patient confirmation in replies** — never state or imply that a reviewer is a patient (privacy/YMYL caution). Use generic, non-identifying responses.
- **No review swaps** with other clinics and no filtering by sentiment before inviting.

**Recommended cadence:** ask every patient at a consistent point (e.g. after the consultation/conclusion of care), via SMS/email with a direct review link; aim past 10 reviews, 4.5+ stars, no gap over ~2–3 weeks (velocity over volume); respond to 100% of reviews; collect on Google first, then Justdial/Practo/Apple/Bing where profiles are claimed. Add `aggregateRating`/`review` schema **only** from genuine, verifiable data — never to "catch up" to competitors.

**Neutral request template (no sentiment pre-screen, no incentive):**
> "Thank you for visiting Ma Lumière. If you'd like to share your experience, an honest Google review helps other people find us: [canonical GBP review link]. If there is anything we could do better, please tell us directly at +91 99878 02355."

The review link must be built from the **owner-supplied canonical GBP URL** obtained in §0 — never from the share URL, and never with an invented Place ID.

**Owner-supplied data still needed:** current rating and count, last-90-day new reviews, most recent review date, response rate and sample replies. None of this was available here.

---

## 6) Owner-verification register (must be confirmed, not assumed)

1. Canonical GBP/Maps URL from the dashboard (§0) — **share URL is not acceptable**.
2. Verification status and whether a Google Verified badge applies.
3. Primary and secondary categories actually available/selected.
4. Exact business name, address, PIN (401105), and phone as shown in GBP.
5. Opening hours, including Sunday and Monday closures and holiday hours.
6. Exact geo pin coordinates (5+ decimals) for website schema.
7. Genuine photos count, post cadence, Q&A state.
8. Rating, review count, recency, velocity, owner response rate.
9. GBP Insights: calls, direction requests, website clicks, search terms (90 days).
10. Website vs. appointment-link destination decision; UTMs applied.
11. Ownership of the variant "Lumiere Clinic" Justdial/Practo entries at the same premises.
12. Attributes, accessibility and payment details — only if true.

---

## 7) UTM tracking

Apply UTMs to the **GBP website link** and **appointment/booking link** only (direction requests and calls are measured in Insights, not UTMs).

| Link | Proposed URL |
|---|---|
| Website | `https://ma-lumiere.in/?utm_source=google&utm_medium=organic&utm_campaign=gbp&utm_content=website` |
| Appointment link | `https://ma-lumiere.in/book-consultation/?utm_source=google&utm_medium=organic&utm_campaign=gbp&utm_content=booking` |
| (Optional) local-hub website variant | `https://ma-lumiere.in/skin-clinic-mira-bhayander/?utm_source=google&utm_medium=organic&utm_campaign=gbp&utm_content=local-hub` |

Rules: keep parameters lowercase and consistent; never change the canonical tag — UTMs are campaign parameters, the pages remain self-canonical; do not use UTMs on the share URL; record the final choice in GA4 (once connected). UTM data cannot be validated here because GA4 was not available — this is a proposal.

---

## 8) Sequence

1. **§0** — owner obtains canonical GBP URL; no website `sameAs` change until then.
2. **§1/§6** — owner verifies categories, NAP, hours, pin, verification status.
3. **§2/§3/§4** — set description, services, photos, posts, Q&A, appointment link with UTMs.
4. **§5** — launch compliant review acquisition; add schema markup only from real data.
5. **§6** — export Insights; connect GA4/GSC; review monthly.
6. Developer adds verified geo/hours and real `sameAs` to schema only. **Do not add `FAQPage` for rich-result eligibility** — Google retired FAQ rich results in 2026; keep the visible on-page FAQs.

---

## 9) Limitations

No GBP dashboard, API, or Insights access; no Place ID supplied; no reviews, ratings, photos, hours, categories, or verification state were measured. The share URL was resolved only to a Google share/search page. Category labels are proposals subject to actual GBP availability. All proposed values are recommendations, not findings. The site's implemented NAP, directions link, schema and noindex fixes are documented in `LOCAL-SEO-ANALYSIS.md`; URL/linking rules are in `PAGE-ARCHITECTURE.md`.
