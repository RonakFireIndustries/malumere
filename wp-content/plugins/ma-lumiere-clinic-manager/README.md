# Ma Lumière Clinic Manager

Private clinic management system for **Ma Lumière Dermatology & Aesthetic Clinic**.

Phase 3 laid the **secure, scalable foundation** for the clinic system —
database schema, roles & capabilities, settings, security helpers, audit
logging, private file sandbox, email service backbone, REST/AJAX skeletons and
the admin navigation shell.

Phase 4 implements **patient management**: REST CRUD, the registration form,
the searchable/paginated patient list, the single patient record screen, the
`ML-PT-xxxxxx` UID allocator and per-field validation with audit on every write.

Phase 5 implements **appointments & public booking**: real appointment REST
endpoints (list/single/create/update/reschedule/cancel/check-in/complete/
no-show), public availability + booking endpoints, the Appointments admin
screen (list + Day/Week/Month calendars), a settings-driven weekly schedule
with per-day hours/breaks and booking horizon, and the public six-step
`[ml_booking]` widget (treatment → date → time → details → review →
confirmation) with honeypot, idempotency, rate limits and add-to-calendar.

Phase 6 implements **doctor visits, treatment sessions & prescriptions**:
independent visit records with a per-patient `ML-V-xxxx` number, a
recommend/confirm/complete session lifecycle, the full prescription workflow
(draft → finalize → issue, corrections that keep the original number, PDF
export via dompdf), matching REST endpoints, a nonce-gated admin PDF download,
and the Visits / Prescriptions / Follow-ups admin screens plus clinical panels
on the patient record.

The public website lives in the `ma-lumiere` theme. This plugin is explicitly
separated from the theme: disabling the theme leaves the clinic database intact,
and disabling the plugin leaves the website fully functional.

---

## 1. Purpose

Store and protect sensitive clinic data (patients, visits, prescriptions,
billing, medical photos) in dedicated, versioned database tables — **not** in
WordPress post meta — with strict authorization, IDOR protection, server-side
payment verification architecture and a full audit trail.

## 2. Installation

1. Copy the folder to `wp-content/plugins/ma-lumiere-clinic-manager/`.
2. Activate **Ma Lumière Clinic Manager** in wp-admin → Plugins.
3. On activation the plugin:
   - creates all `{prefix}ml_*` database tables (idempotent),
   - installs clinic roles and capabilities,
   - writes default settings,
   - prepares the private file sandbox.

No rewrite rules are flushed (the plugin registers no public rewrite-driven
content), and **no data is ever deleted on activation or deactivation**.

## 3. Database tables (version `1.4.0`)

All tables use `{$wpdb->prefix}ml_` (never a hard-coded `wp_` prefix) and are
managed by `ML_Database` using `dbDelta()`.

| Table                     | Purpose                                             |
|---------------------------|-----------------------------------------------------|
| `{prefix}ml_patients`     | Patient records; `patient_uid` unique (`ML-PT-xxxxxx`) |
| `{prefix}ml_visits`       | Independent visit records per patient (never overwritten) |
| `{prefix}ml_appointments` | Appointments (booking workflow)                     |
| `{prefix}ml_prescriptions`| Prescriptions (header)                              |
| `{prefix}ml_prescription_items` | Prescription line items (medicines)           |
| `{prefix}ml_treatment_sessions` | Per-patient treatment session schedule         |
| `{prefix}ml_followups`    | Follow-up scheduling                               |
| `{prefix}ml_patient_photos` | Medical photos metadata (protected storage)      |
| `{prefix}ml_treatments`   | Clinic treatment reference records                  |
| `{prefix}ml_medicines`    | Medicine catalogue; `sku` unique, never reused     |
| `{prefix}ml_medicine_batches` | Received lots: quantity on hand, expiry, cost  |
| `{prefix}ml_stock_movements` | Append-only stock ledger (every change)         |
| `{prefix}ml_invoices`     | Invoices                                            |
| `{prefix}ml_invoice_items`| Invoice line items                                  |
| `{prefix}ml_payments`     | Payments                                            |
| `{prefix}ml_audit_logs`   | Security/audit event log                            |

Indexes cover the primary query paths; `patient_uid` and `invoice_number` are
unique; visits enforce `UNIQUE(patient_id, visit_number)`. `prescription_number`
is a **non-unique** KEY so corrected revisions may legally share the issued
number (upgrade 2→3 demotes the old UNIQUE index explicitly).

`prescription_items` revision 2 adds `medicine_id`, `quantity` and
`dispensed_quantity`. A line only moves stock when it is linked to a catalogue
medicine **and** carries a quantity above zero; free-text lines remain printed
instructions. A correction copies the medicine link and quantity but resets
`dispensed_quantity`, because the stock was dispensed against the parent
prescription.

### Migrations
`ML_Database::maybe_upgrade()` compares the stored `ml_clinic_db_version`
option against `ML_CLINIC_DB_VERSION`. dbDelta adds missing columns/indexes/tables
only — it never drops data. Per-table revisions are stored in
`ml_clinic_table_versions`.

## 4. Roles

| Role               | Access summary                                                          |
|--------------------|------------------------------------------------------------------------|
| **Clinic Super Admin** | Full clinic access (all `ml_*` caps), audit, settings, users.      |
| **Clinic Doctor**  | Patients, medical records, visits, prescriptions, follow-ups, photos, clinical reports, medicine inventory, view billing. No settings/users/audit administration. |
| **Clinic Receptionist** | Register/search patients, manage appointments, medicine inventory, billing + payments, basic reports. No clinical/diagnosis/prescription/photo/settings/audit access. |
| **Clinic Patient** | Self-service portal capability set only (own records). Never receives administrator access. |

The built-in **Administrator** role receives SuperAdmin clinic capabilities so
existing admins can manage the clinic without a role swap.

## 5. Capabilities

Every sensitive operation is gated by a clinic capability (never just
`edit_posts`). Key capabilities:

`ml_manage_clinic`, `ml_view_patients`, `ml_create_patients`, `ml_edit_patients`,
`ml_view_medical_records`, `ml_view_visits`, `ml_manage_visits`,
`ml_view_prescriptions`, `ml_manage_prescriptions`, `ml_view_appointments`,
`ml_manage_appointments`, `ml_view_followups`, `ml_manage_followups`,
`ml_view_inventory`, `ml_manage_inventory`,
`ml_view_patient_photos`, `ml_manage_patient_photos`, `ml_view_billing`,
`ml_manage_billing`, `ml_view_payments`, `ml_record_payments`,
`ml_view_reports`, `ml_manage_reports`, `ml_manage_settings`,
`ml_view_audit_logs`, `ml_manage_users`, plus the patient self-service set
(`ml_view_own_*`).

The authoritative list lives in `ML_Capabilities::all()` and role mapping in
`ML_Capabilities::role_map()`.

`ml_manage_clinic` is reserved for the **Super Admin / Administrator** role
(clinic-wide administration, including patient deletion). Doctor and
Receptionist roles never receive it.

`ml_view_inventory` / `ml_manage_inventory` are the only two inventory
capabilities. Both Doctor and Receptionist hold them, so stock can be received
and dispensed at the front desk. Because `ML_Roles::maybe_sync()` fingerprints
the capability registry, adding these re-syncs existing users on the next
request — no role reassignment needed.

## 6. Admin menu

`Ma Lumière Clinic` top-level menu with capability-gated screens:

Dashboard · Patients · Appointments · Visits · Prescriptions · **Inventory** ·
Follow-ups · Treatments · Billing · Payments · Reports · Patient Photos ·
Audit Logs · Settings

Each submenu exists only for users who hold the matching capability. All screens
are implemented (billing, photos, portal and reports landed across phases 7–10).

### Inventory screens

`ml-clinic-inventory` (`ML_Inventory_Controller`) with five tabs plus
context screens, all nonce- and capability-gated:

- **Medicines** — searchable catalogue, `out`/`low` stock filters, on-hand
  totals, next expiry, CRUD. A medicine is only deletable while it has no
  batches and no ledger entries; otherwise it is made inactive so history
  survives.
- **Batches** — every lot with received/on-hand quantities, expiry state,
  status, supplier, cost and storage location. Batch status can be set to
  active/quarantined/expired/disposed; `depleted` is set automatically.
- **Movements** — the append-only ledger, filterable by medicine, batch, type
  and date range, with the running balance per movement.
- **Alerts** — out of stock, at/below reorder level, expiring within 90 days,
  and expired stock still held, with a bulk expired write-off.
- **Stock card** (`action=stockcard&id=`) — per-medicine view combining the
  derived total, the FEFO order used for dispensing, the batch table and the
  medicine's own movements.

Context screens: `action=new|edit` (medicine form), `action=receive` (receive
stock into a new lot) and `action=edit_batch` (edit an existing lot).

### Inventory data rules

- A medicine's on-hand figure is the sum of its **dispensable** lots: active
  status and not past expiry. Expired stock is surfaced separately rather than
  inflating the on-hand total, so the number shown always matches what can
  actually be handed out.
- Dispensing is **FEFO** (first expiry, first out) across eligible lots.
- `ML_Medicine_Batch_Repository::apply_delta()` is optimistic: every write
  passes the quantity the caller believed was on hand, so two people dispensing
  at once cannot oversell a lot. A mismatch returns a conflict rather than
  silently taking stock.
- The ledger is **append-only**. Failed work is unwound with compensating
  `return` movements rather than by deleting rows, so stock totals stay
  auditable. `return_from_prescription()` is idempotent: it skips dispenses
  that already have a reversal, and only clears `dispensed_quantity` once every
  pending movement has been reversed.
- Prescribing and dispensing are separate steps. Finalizing a prescription does
  **not** consume stock; staff dispense the outstanding units explicitly, which
  keeps issuing a prescription from failing because a shelf is empty.
- Every stock action writes an audit entry.

### Patient screens (Phase 4)

- **Patients list** — `ml-clinic-patients`: search (name/UID/phone/email),
  status filter, pagination, row actions. Empty state with a clear
  registration call-to-action; real figures only, never sample data.
- **Patient record** — `?page=ml-clinic-patients&view={id}`: demographics,
  contact + emergency contact, and a capability-filtered record summary
  (appointments, visits, prescriptions, follow-ups, photos, billing) plus the
  patient's recent audit trail for audit-capable roles.
- **Register / Edit** — `action=new` / `action=edit`: single post-back form
  with `ML_Security` nonce (`ml_clinic_nonce`), field-level validation,
  `novalidate` for server-side enforcement, and required-field marking.
- **Delete** — SuperAdmin-only (`ml_manage_clinic`), nonce-protected, with a
  confirmation prompt. Deletion is a hard row delete that intentionally does
  **not** cascade to clinical sub-records, so history is never silently
  destroyed.

### Appointment screens (Phase 5)

- **Appointments list** — `ml-clinic-appointments`: search, date range, status
  and doctor filters, pagination, status badges and row actions
  (View / Check in / Complete / No-show / Reschedule / Cancel).
- **Calendar views** — Day / Week / Month grids driven by the same REST data;
  `ml_date` + `ml_off` navigate across days.
- **New / Reschedule / Cancel modals** — REST-backed, with patient autocomplete
  and live time-slot loading from the availability endpoint when a treatment
  and day are chosen.

### Clinical screens (Phase 6)

- **Visits** — `ml-clinic-visits`: search + status filter, lock/unlock on each
  row (nonce + `ml_manage_visits`), `<details>` expansion for diagnosis, notes
  and plan. Live data only, paginated.
- **Prescriptions** — `ml-clinic-prescriptions`: drafts show a **Finalize**
  action; issued rows show **PDF** (nonce-gated `admin-ajax.php?action=ml_prescription_pdf`)
  and **Correct**; item lists expand inline.
- **Follow-ups** — `ml-clinic-followups`: complete/cancel actions with status
  badges, searchable.
- **Patient record** — the patient view now embeds cap-filtered clinical
  panels: visits, prescriptions, treatment sessions and follow-ups, each with
  an inline add form (record visit, draft prescription with up to 5 items,
  recommend a session, schedule a follow-up) and one-click status actions.
- **Dashboard** — cards for open visits and draft/finalized prescriptions plus
  quick links to the new screens; AJAX/REST stat endpoints extended to match.
- **Reports** — `ml-clinic-reports`: date-range filter (defaults to the current
  month), summary cards (new patients, appointments, visits, finalized
  prescriptions, sessions, follow-ups, revenue, refunds, outstanding), a daily
  breakdown table, and a **per-clinician breakdown** (appointments, visits,
  finalized prescriptions, sessions, follow-ups plus revenue/refunds attributed
  via the clinician's invoices) with a clinician filter on both the screen and
  the exports; CSV/PDF export backed by `ML_Reports`/`ML_Report_Pdf` (dompdf),
  gated by `ml_view_reports` + a `ml_export_report` nonce; every export is
  audit-logged (`report_exported`).

## 7. REST namespace

`ml-clinic/v1` — patients, appointments, visits, prescriptions, treatment
sessions, follow-ups, billing/invoices and reports are all implemented against
real data:

```
GET   /patients                 list (page/per_page/search/status/orderby/order)
POST  /patients                 create (ml_create_patients)
GET   /patients/{id}            single patient (IDOR-guarded)
PUT   /patients/{id}            update (ml_edit_patients)
DELETE /patients/{id}           delete (ml_manage_clinic, SuperAdmin only)
GET   /patients/{id}/records    medical record summary (ml_view_medical_records)
GET   /appointments             list (page/per_page/from/to/status/doctor/search)
POST  /appointments             create (ml_manage_appointments, 201)
GET   /appointments/{id}        single (own-doctor aware)
PUT   /appointments/{id}        update (non-terminal only, slot re-validated)
POST  /appointments/{id}/reschedule|cancel|checkin|complete|no-show
GET   /appointments/availability  PUBLIC — open days, or slots for a date
POST  /appointments/book          PUBLIC — six-step booking (nonce-guarded)
GET   /visits                   list (page/per_page/status/search)
POST  /visits                   create visit (ml_manage_visits)
GET   /visits/{id}              single visit (IDOR-guarded)
PUT   /visits/{id}              update (locked visits frozen)
POST  /visits/{id}/lock         lock/unlock
GET   /prescriptions            list (drafts + finals; status/patient/search)
POST  /prescriptions            create draft (ml_manage_prescriptions)
GET   /prescriptions/{id}       single + line items (IDOR-guarded)
PUT   /prescriptions/{id}       update draft
POST  /prescriptions/{id}/finalize  issue (number allocation + follow-up)
POST  /prescriptions/{id}/correct   open correction draft (keeps number)
GET   /prescriptions/{id}/revisions  correction chain
GET   /medicines              list (page/per_page/status/form/category/stock/search)
POST  /medicines              create (ml_manage_inventory)
GET   /medicines/{id}         single + stock state
PUT   /medicines/{id}         update
GET   /medicines/{id}/batches lots for a medicine (status/per_page)
GET   /medicines/{id}/stockcard  medicine + lots + movements + next expiry
POST  /batches                receive stock into a new lot (ml_manage_inventory)
PUT   /batches/{batch_id}     edit lot metadata (medicine_id carried over)
POST  /batches/{batch_id}/adjust  signed correction/wastage with a reason
GET   /inventory/movements    ledger (medicine_id/batch_id/movement_type/from/to/search)
GET   /inventory/summary      on-hand, value, low/out, expiry and movement totals
GET   /inventory/alerts       out/low/expiring/expired
POST  /inventory/expired/write-off  write off every expired unit held
POST  /prescriptions/{id}/dispense      draw outstanding units (FEFO)
POST  /prescriptions/{id}/return-stock   return dispensed units to their lots
GET   /sessions                 list (requires patient_id, ml_view_visits)
POST  /sessions                 recommend a session
POST  /patients/{id}/sessions/plan   recommend N sessions in one call
PUT   /sessions/{id}            update status (locked sessions frozen)
GET   /followups                list (status/search)
POST  /followups                create (ml_manage_followups)
GET   /patients/{id}/followups  a patient's follow-ups
POST  /followups/{id}/status    complete/cancel
GET   /invoices/{id}            single invoice (IDOR-guarded)
GET   /reports/summary          date-range summary (from/to, ml_view_reports)
```

Patient write routes are cookie-authenticated WordPress REST endpoints, so
clients must send the standard `X-WP-Nonce` header for CSRF protection; all
routes still enforce capability + IDOR checks server-side. No patient data is
exposed to unauthenticated users.

Public booking specifics: `GET /appointments/availability` is rate-limited per
IP and never returns patient data or slot counts; `POST /appointments/book`
requires a `ml_public_booking` nonce (sent in the `_nonce` body field by the
widget, falling back to the header for legacy clients), rejects a filled
honeypot (`ml_website`), de-duplicates via `idempotency_key`, and returns
429 on rate limits (IP 5/15 min, email 3/hour).

## 8. Security architecture

- **Nonces** — every admin/AJAX interaction; `ML_Security::gate()`.
- **Authentication** — `is_user_logged_in()` required by all AJAX/REST entry points.
- **Authorization** — capability checks on every sensitive operation; menus
  are capability-gated.
- **IDOR protection** — `ML_Security::can_access_patient()` implements the
  check chain: *user → capability → ownership → record*. Portal users can only
  ever reach the patient row linked to their own `wp_user_id`.
- **Sanitization** — `sanitize_text_field`, `sanitize_email`, `sanitize_key`,
  `absint`, `sanitize_textarea_field`; a single `ml_clean()` helper centralizes
  common casts.
- **Escaping** — `esc_html`, `esc_attr`, `esc_url`, `wp_kses_post` throughout views.
- **SQL** — `$wpdb->prepare()` for every dynamic query; table names come from
  the namespace helper. No string-concatenated user input in SQL.
- **Payment integrity** — payment records are server-side only; gateway success
  reported by frontend JS is never trusted. Razorpay integration is deferred.
- **Private files** — see below.
- **Audit log** — every sensitive action is recorded; passwords/secrets/clinical
  content are never written to logs.

## 9. File privacy strategy

Medical photos/documents are never placed in the public uploads tree nor
registered as public media.

- `ML_Private_File_Manager` manages a private directory (default
  `wp-content/uploads/ml-private/`, stored in option `ml_clinic_private_dir`
  so production can relocate it outside the web root).
- The directory is blocked from direct web access (`.htaccess` deny + silent
  `index.php`), directory listing is off, and **no public serving handler
  exists yet**.
- Upload validation: whitelisted MIME + extension (`jpg/jpeg/png/webp/pdf`),
  magic-byte detection via `finfo`/header sniffing, size limit, and an explicit
  forbidden list (`php`, `phtml`, `php5`, `js`, `html`, `svg`, …).
- A token-based, authorized download/serve endpoint is planned for the photo
  management phase.

## 10. Email service

`ML_Email_Service` provides the transport + template registry for appointment
confirmation/reminder/cancellation, follow-up, invoice and prescription emails.
Phase 6 ships the `prescription_notification` template (driven by
`ML_Prescription_Service::send_email` on finalize). Sending is disabled by
default — the `ml_clinic_send_email` filter must explicitly allow it, so no
emails are silently sent during development.

## 11. Uninstall behavior

- **Deactivation** preserves everything: tables, roles, data, options.
- **Uninstall** only removes plugin data when **Settings → Privacy →
  “Delete clinic data on uninstall”** is explicitly enabled (default OFF).
  Roles/capabilities are always cleaned up; non-data options are removed.
  The schema is otherwise left intact so a reinstall resumes cleanly.

## 12. Privacy & exposure

Patient data is never surfaced through search, sitemaps, public REST, archives,
page source, frontend JS or local storage. Clinic pages are wp-admin pages
(noindexed by WordPress). Patient record creation flows, portal templates and
photo serving (all future) must preserve this privacy boundary.

## 13. Patient management (Phase 4)

Implemented end-to-end through the shared `ML_Patient_Repository`:

- **UID allocation** — `ML-PT-%06d`, derived from the maximum existing suffix
  (no backfill of internal gaps while a record with a higher UID exists); the
  UNIQUE `patient_uid` key backs a retry-on-race loop.
- **Validation** — required first/last name, `is_email()`, strict `Y-m-d`
  dates, no future birth dates, gender/status whitelists, phone/pincode
  scrubbing, max lengths per column.
- **Sanitization** — every field cleaned in `sanitize()`, unknown keys dropped,
  `%d`/`%s` prepared everywhere; outputs escaped (`esc_html`/`esc_attr`) in views.
- **Audit** — `patient_created`, `patient_updated`, `patient_deleted` are
  recorded with a short safe description (never clinical content).
- **No sample data** — list, record and dashboard always show real counts; an
  empty registry shows empty states with a registration prompt.

## 14. Appointments & public booking (Phase 5)

Implemented end-to-end through `ML_Appointment_Repository`,
`ML_Appointment_Service` and `ML_Availability_Service`:

- **Catalog** — bookable treatments come from the theme's `ml_treatment` CPT
  (lazy sync, per-treatment duration from `_ml_treatment_duration`); nothing is
  fabricated. The default consulting doctor comes from Settings →
  Appointments.
- **Schedule** — per-day Open/Close switches, a daily break (start/end), and a
  booking horizon (days) in Settings; closed days never yield slots, past times
  are excluded, and booked slots are removed at write time with optimistic
  conflict re-check.
- **Workflow & statuses** — pending → confirmed → checked_in → completed;
  reschedule closes the original as `rescheduled` and creates a linked
  successor; no-show/cancel terminal states; every transition recorded in the
  audit log (`appointment_created`, `appointment_updated`, `appointment_*`).
- **Admin UI** — list + Day/Week/Month calendars with REST-backed quick
  actions and modals, fed by `admin/js/admin.js`.
- **Public widget** — `[ml_booking]` six-step flow (`public/js/booking.js`,
  `public/css/booking.css`) on the theme's Appointment template; `?treatment=`
  preselects; confirm screen offers Google Calendar + `.ics` add-to-calendar.
  Treatment artwork uses the post's featured image or a curated stock hotlink.
- **Abuse protection** — `ml_website` honeypot, per-IP/per-email rate limits,
  `idempotency_key` replay protection and a `ml_public_booking` nonce on the
  public booking endpoint.

## 15. Doctor visits, prescriptions & PDF (Phase 6)

Implemented through `ML_Visit_Repository`, `ML_Prescription_Repository`,
`ML_Prescription_Service`, `ML_Prescription_Pdf`, `ML_Treatment_Session_Repository`
and `ML_Followup_Repository`:

- **Visits** — independent consultation rows with a per-patient `ML-V-0001`
  number (`MAX()` + per-patient uniqueness), dermatology-specific skin/Hair
  fields, a written update-history trail, `open`/`completed` statuses and a
  hard lock that freezes edits (unlock requires `ml_manage_visits`).
- **Prescription workflow** — create a draft (date, diagnosis, advice,
  follow-up date, up to N line items), `finalize` to issue — the number
  (`ML-RX-000001`, allocated server-side with a duplicate re-check + retry
  loop) is assigned only at issue time, and a follow-up is auto-derived from
  `follow_up_date`. Corrections open a new **draft revision** that reuses the
  original number with an incremented `version` (`revision_of` chain);
  `prescription_number` is therefore a plain KEY, not UNIQUE.
- **PDF export** — `ML_Prescription_Pdf` renders an approved, issued document
  through **dompdf** (Composer vendor, lazily loaded). The doctor signature is
  a JPEG data-URI (`DCTDecode`; non-JPEG attachments are rejected). Filename
  pattern `prescription-{lowercased-number}-v{N}.pdf`.
- **Admin download** — `wp_ajax_ml_prescription_pdf` streams the PDF only for
  `final` prescriptions, gated by nonce → capability → IDOR; every download is
  audit-logged (`prescription_pdf_downloaded`).
- **Treatment sessions** — per-patient `treatment_sessions` rows
  (`rec`/`confirmed`/`completed`/`cancelled`), created individually or as an
  N-session plan; locked rows are frozen; recommended sessions show as
  "Recommended". Scoped under the visits capabilities.
- **Follow-ups** — scheduled per patient, auto-derived from prescription issue
  dates; `complete`/`cancel` recorded in the audit log.
- **Email** — the patient prescription notice (`ML_Email_Service`, type
  `prescription_notification`) stays disabled unless the `ml_clinic_send_email`
  filter opts in; the finalize method never blocks on transport.
- **Admin UI** — Visits / Prescriptions / Follow-ups list screens plus
  clinical panels on the patient record (all nonce + capability gated, no
  sample data, no server-side non-admin footguns — IDOR checks resolve each
  record's real `patient_id`).

## 16. Phase history

All planned areas are now implemented across the incremental phases:

1. Patient photo upload + before/after management (token-based private serving) — delivered.
2. Billing UI, invoice PDF, Razorpay payment gateway + server-side verification — delivered.
3. Patient portal + login flow (self-service, own-record only) — delivered.
4. Reports (summary page, CSV/PDF exports) + dashboards — delivered.
5. Medicine inventory (catalogue, batch/expiry tracking, stock ledger, dispensing,
   returns, adjustments, alerts) — delivered in 1.4.0.

Remaining ideas are hardening and polish: pagination/export limits for very
large ranges, per-clinician report breakdowns, scheduled PDF delivery, and
scheduled (cron) expiry alerts instead of on-demand calculation only.

## 17. Assumptions

- `patient_uid` format `ML-PT-000001` (progressive, zero-padded on generation).
- `ml_patient_photos.file_path` stores a path inside the private directory;
  `attachment_id` is reserved for an optional media reference (kept private).
- `ml_treatments.wp_post_id` links to the theme's `ml_treatment` CPT post when
  one exists; a treatment may also exist without a public post.
- Internal clinic days are stored as ISO day codes (`mon`…`sun`).
- Money is stored as `DECIMAL(10,2)`; amounts are formatted by `ml_money()`.
- Inventory quantities are whole units (no fractional dispensing); a partial
  pack is received as a separate lot.
- A lot with no expiry date is treated as non-expiring and never appears in the
  expiring/expired alerts.
- Stock is dispensed only for a **finalized** prescription, one explicit action
  at a time, so a prescription can be issued even when a shelf is empty.
- Email sending stays disabled until a production transport is configured.

---

**Version 1.4.0** — Ma Lumière Dermatology & Aesthetic Clinic.