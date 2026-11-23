# Deployment and Owner Actions — ma-lumiere.in

**Status:** Fixes are implemented and verified on the local XAMPP copy only. **Do not treat this as a production deployment record.**  
**Prepared:** 2026-11-23  
**Rollback backups:** `ma-lumiere.in-audit/backups/20261123-142646/theme-ma-lumiere.zip` and `database.sql`

---

## 1) What was changed locally

- Patient-visible placeholders removed from the database and active theme; the results URL is now an educational page with no patient photos, testimonials, Place ID or outcome claims.
- Canonical NAP applied: PIN `401105`, full verified address, `+91 99878 02355`, `tel:+919987802355`, and an address-based Google Maps directions URL.
- Unique titles/meta descriptions added to key pages and the four treatment pillars, including Botox on the existing `/treatment/injectables-facial-contouring/` URL.
- `/skin-clinic-mira-bhayander/` linked from the footer and treatment pages.
- Nine `/treatment-type/*` archives set to `noindex,follow`, canonicalised and removed from the sitemap (29 URLs).
- Verified-only JSON-LD implemented: `MedicalClinic`+`LocalBusiness`, `WebSite`, `Person` ("Aesthetic Physician"), `WebPage`, `Service`, `BreadcrumbList`; `BlogPosting` retained.
- `FAQPage`, geo coordinates, structured hours, Place ID, reviews/ratings and `MedicalProcedure` deliberately omitted.

Current static estimate: Overall **~65/100**, Local **~41/100**. These are not measured performance figures.

---

## 2) Pre-deployment verification (local)

Run from `C:\xampp\htdocs\malumere`:

```powershell
& "C:\xampp\php\php.exe" -f "ma-lumiere.in-audit\tools\verify-implementation.php" -- "--output=ma-lumiere.in-audit\findings\implementation-verification.json"
& "C:\xampp\php\php.exe" -f "ma-lumiere.in-audit\tools\verify-jsonld.php" -- "--output=ma-lumiere.in-audit\findings\jsonld-verification.json"
powershell -NoProfile -ExecutionPolicy Bypass -File "C:\xampp\htdocs\malumere\ma-lumiere.in-audit\tools\verify-pages.ps1"
```

The two PHP tools write their JSON artifacts directly with `--output=FILE`; this avoids the PowerShell `.php` pipeline issue encountered during the first close-out attempt. `verify-pages.ps1` emits UTF-8 so redirected output is readable.

PHP lint:

```powershell
$files = Get-ChildItem "wp-content\themes\ma-lumiere" -Recurse -Filter *.php
$bad = @()
foreach ($f in $files) {
  $o = & "C:\xampp\php\php.exe" -l $f.FullName 2>&1
  if ($LASTEXITCODE -ne 0) { $bad += "$($f.FullName): $o" }
}
if ($bad) { $bad } else { "PHP_LINT_OK $($files.Count) files" }
```

Placeholder grep:

```powershell
grep -R "\[[A-Z][^\]]*\(REQUIRED\|PENDING\|IMAGE\|NO TREATMENTS\)[^\]]*\]" wp-content\themes\ma-lumiere
```

Expected results:

- `findings/implementation-verification.json`: **9/9 PASS**
- `findings/jsonld-verification.json`: **4/4 sampled URLs VALID**, 0 errors
- Sampled rendered pages: **5/5 HTTP 200**, correct canonicals/robots, correct titles/metas, **0 placeholders**; retained as `findings/page-verification.txt`
- PHP lint: **PHP_LINT_OK 51 files**
- Sitemap: **29 URLs**, zero `/treatment-type/` entries
- `audit-data.json`: `JSON_VALID health=56 estimate_health=65 estimate_local=41 implementation=present`

---

## 3) Deployment sequence (owner/developer)

1. **Take a fresh production backup** of the live theme and database immediately before deployment. Do not rely only on the local backup.
2. **Stage the theme** by replacing `wp-content/themes/ma-lumiere` with the locally verified theme, preserving uploads and any environment-specific configuration.
3. **Apply the database changes** as one controlled batch: SEO title/meta fields, revised treatment/result/FAQ copy, and the term label change. Do not change permalinks.
4. **Flush caches** (object cache, page cache, CDN) if used.
5. **Re-run verification against production URLs** using the same checks above, substituting the production host.
6. **Validate schema** with Google Rich Results Test and Schema.org validator on the homepage, one treatment pillar and the location page.
7. **Check the sitemap** at `/sitemap.xml` for 29 URLs and no `/treatment-type/` entries.
8. **Take a post-deployment backup** and capture an SEO-drift baseline.
9. **Monitor** GSC indexation/coverage and GA4/GBP data once connected. Do not claim ranking or traffic improvement from the static score alone.

---

## 4) Rollback

If any production check fails:

1. Restore the pre-deployment production theme backup.
2. Restore the pre-deployment production database backup.
3. Flush caches.
4. Re-run verification and confirm the site returns to its prior state.

Local-copy rollback:

1. Restore `ma-lumiere.in-audit/backups/20261123-142646/theme-ma-lumiere.zip` over `wp-content/themes/ma-lumiere`.
2. Restore `ma-lumiere.in-audit/backups/20261123-142646/database.sql`.
3. Re-run the verification commands in §2.

---

## 5) Owner action register

### Blocking local SEO

| # | Owner action | Why it matters |
|---|---|---|
| 1 | Open GBP dashboard → **Share → Copy link** or copy the canonical Maps URL. Supply the exact URL, and a Place ID only if genuinely available. | Website cannot safely add `sameAs` or place-based links while the supplied `share.google` link remains unverified. |
| 2 | Verify GBP primary category, secondary categories, exact business name, address, PIN `401105`, phone, hours, verification status and service area. | Largest remaining local-score block; website and GBP must match exactly. |
| 3 | Supply GBP Insights (90 days: calls, direction requests, website clicks, search terms, profile views) and connect GSC/GA4. | Converts static estimates into measured performance. |
| 4 | Launch compliant review acquisition: ask all patients uniformly, no incentives, no gating, no fake reviews, respond professionally without confirming reviewer identity. | Reviews are 20% of the local score and must be genuine. |
| 5 | Supply genuine clinic/interior/team photos and an approved post cadence. | Local proof and engagement; nothing may be fabricated. |
| 6 | Correct Justdial/Practo "Lumiere Clinic" variants and claim Bing Places, Apple Business/Maps, Facebook and Sulekha. | NAP/citation consistency. Add only real `sameAs` URLs. |
| 7 | Supply exact GBP pin coordinates (5+ decimals) if place-based schema is wanted. | No geo coordinates may be guessed. |

### Content and trust

| # | Owner action | Why it matters |
|---|---|---|
| 8 | Supply verified clinician degree/specialisation/registration/years in practice only if the owner wants them published. Reviewer attribution must be real. | YMYL/E-E-A-T. Until then, use only "Aesthetic Physician". |
| 9 | Provide dated authoritative citations for each condition page and a named clinical reviewer. | Needed for credible medical content. |
| 10 | Expand the four pillars to genuinely unique 700–1,000 word resources with candidacy, contraindications, downtime, risk bounds and cost factors. | Current local fixes improve metadata/schema, not depth. |
| 11 | Decide whether `/results/acne-treatment-case-reference/` stays indexed as an educational page or is `noindex`ed. | It currently contains educational copy only; no patient assets are published. |
| 12 | Supply consent-approved, de-identified patient assets only if desired. Otherwise keep educational fallbacks. | No patient photos, testimonials or results may be invented. |

### Technical

| # | Owner action | Why it matters |
|---|---|---|
| 13 | Add security headers and cache headers at host/CDN level after confirming subdomain posture. | Not changed locally. |
| 14 | Add intrinsic image dimensions, fix above-the-fold lazy loading, improve informative alts and supply a real 1200×630 OG image. | Remaining image/CWV risk. |
| 15 | Replace generic "Learn more" anchors with descriptive intent anchors. | Internal-link quality. |
| 16 | Keep robots.txt permissive to search/citation crawlers. Optionally add a short `llms.txt`; it is not a ranking factor. | AI discovery hygiene. |

---

## 6) Explicit non-actions

Nothing in this deployment batch should:

- change URLs or permalinks;
- create a new Botox URL or separate clinic location;
- add a page per Western Railway station;
- claim branches, ratings, reviews, Place IDs, coordinates, credentials or patient results that were not supplied and verified;
- add `FAQPage` markup for rich-result eligibility (Google retired FAQ rich results in 2026);
- deploy to production without owner approval.
