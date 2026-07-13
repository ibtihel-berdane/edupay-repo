# EduPay+ — Full page-by-page README (PHP)

This README documents **every PHP page** in the project, including:
- page purpose and request flow (GET/POST)
- how the page connects to other pages (redirects/links)
- database tables used, and how columns/relationships are used
- how the page looks (major UI blocks/forms/tables)
- shared components and how they affect page rendering

> Architecture note: almost all pages share the same UI shell defined in `includes/header.php` and close markup in `includes/footer.php`. Shared business logic (validation, DB helpers, invoice/status computation, notifications, stipend logic, etc.) is centralized in `includes/functions.php`.

---

## Shared building blocks (used by multiple pages)

### `config/database.php`
**Purpose:** Database connection + schema initialization/migrations + default admin seeding.

**What it does:**
- Provides `db()` (PDO singleton).
- On first run (or whenever `initialize_database()` is hit), creates tables:
  - `admins`, `students`, `fees`, `invoices`, `invoice_items`, `payments`, `receipts`, `notifications`, `student_stipends`
- Adds/ensures columns/indexes like:
  - `payments.invoice_item_id`
  - `fees.due_date`
  - `students.stipend_enabled`
  - `admins.matricule`
- Seeds default admin (code `ADM001`, password `admin123`).

**DB tables:** all core tables.

### `includes/functions.php`
**Purpose:** Shared “engine” used by both Admin and Student pages.

**Key categories of functions (high level):**
- Session helpers: `start_app_session()`, `current_admin_id()`, `current_student_id()`, `clear_admin_session()`, `clear_student_session()`
- Security + UX helpers: CSRF (`csrf_token()`, `csrf_field()`, `verify_csrf()`), flash messages (`flash()`, `consume_flash()`)
- Rendering helpers:
  - `h()` for HTML escaping
  - `url()` and `redirect()`
  - `money()` for formatting
  - `badge()` which maps internal statuses into visible labels and badge CSS classes
- Domain logic:
  - Fee scoping/label: `fee_type_for_name()`, `fee_label()`
  - Student dashboard real-time payload builder: `student_dashboard_payload()`
  - Invoice creation & synchronization:
    - `ensure_automatic_student_invoices()`
    - `sync_all_invoice_statuses()`, `sync_invoice_status()`
    - `create_student_fee_invoice()`
  - Payment/invoice target computations:
    - `student_fee_payment_summary()`
    - `available_payment_targets()`
  - Stipends:
    - `ensure_monthly_stipend_for_student()`
    - `student_stipend_summary()`
- Notifications:
  - `create_notification()`, `notify_admins_payment_submitted()`, `notify_student_payment_treated()`
  - `notifications_for_recipient()`, `unread_notification_count()`, `mark_notifications_read()`

**DB tables used throughout:** `admins`, `students`, `fees`, `invoices`, `invoice_items`, `payments`, `receipts`, `notifications`, `student_stipends`.

### `includes/header.php`
**Purpose:** UI shell + navigation + language selection + notification center.

**Looks like (UI blocks):**
- Top bar (`header.topbar`):
  - brand link to `index.php`
  - language selector form (posts to `api/language.php`)
  - theme toggle button (dark/light via JS)
  - notification center widget (only if authenticated)
  - shows current user name
- Main app shell:
  - `layout=admin`: left sidebar with admin links (dashboard, students, stipends, fees, invoices, payments, receipts, reports)
  - `layout=student`: left sidebar with student links (dashboard, profile, stipend, invoices, payments)
  - `layout=public`: public content wrapper

**Connections:**
- Uses `api/language.php` for language persistence.
- Notification widget polls `api/notifications.php`.

### `includes/footer.php`
**Purpose:** Closes markup and loads JS.

**Looks like:**
- Footer element: `<footer class="footer">…</footer>`
- Loads `assets/js/main.js`.

### `includes/admin_auth.php` / `includes/student_auth.php`
**Purpose:** Page guards.
- `require_admin()`: redirect guests to `login.php`, and clears student session if necessary.
- `require_student()`: redirect guests to `login.php`, and clears admin session if necessary.

---

## Public pages (no auth required)

### `index.php`
**Purpose:** Landing page.

**Request flow:**
- If `current_admin_id()` → redirects to `admin/dashboard.php`.
- If `current_student_id()` → redirects to `student/dashboard.php`.
- Otherwise renders welcome + two role cards.

**Looks like:**
- Brand “EduPay+” and a wide card.
- Two large links:
  - `login.php` (Connexion)
  - `student/signup.php` (Inscription / role selection continues in student/signup.php)

**DB tables:** none directly.

**Connections:**
- `admin/dashboard.php` and `student/dashboard.php` redirects.
- Navigation to `login.php` and `student/signup.php`.

---

### `login.php`
**Purpose:** Shared login for both students and agents (admins).

**Request flow:**
- Guards: if already logged in as admin/student → redirect to respective dashboard.
- POST:
  - validates CSRF
  - reads `matricule` + `password`
  - if matricule matches a student (`students.matricule`):
    - checks `account_status='registered'` and verifies password hash
    - stores `$_SESSION['student_id']`, name, matricule; redirects to `student/dashboard.php`
  - else tries admin (`admins.matricule` OR `admins.admin_code`):
    - verifies password hash
    - stores admin session fields and redirects to `admin/dashboard.php`

**Looks like:**
- Centered login card.
- Two inputs: Matricule + Password.
- Link at bottom to `student/signup.php`.

**DB tables used:** `students`, `admins`.

**Connections:**
- Redirects to `student/dashboard.php` or `admin/dashboard.php`.
- Link to `student/signup.php`.

---

## Student authentication & registration

### `student/signup.php`
**Purpose:** Two-step signup for both students and accounting agents.

**Request flow:**
- Detects `role` from query/post: `student` or `admin`.
- Guards:
  - if already logged in as student → redirect to `student/dashboard.php`
  - if already logged in as admin:
    - if trying student signup: clears admin session, shows flash warning
    - if trying admin signup: redirects to `admin/dashboard.php`
- POST:
  - Student role:
    - validates student identity against existing financial dossier:
      - required fields: matricule, first_name, last_name
      - validates matricule format
      - checks that `students` row exists and `first_name/last_name` match
      - ensures `students.password IS NULL` and `account_status='not_registered'`
      - updates:
        - `students.password = password_hash(...)`
        - `students.account_status='registered'`
    - redirects to `login.php`
  - Admin role (accounting agent):
    - validates required fields + password length + confirmation
    - checks uniqueness of `admins.matricule`
    - inserts into `admins` with generated `admin_code` and role `accounting_agent`
    - redirects to `login.php`

**Looks like:**
- Step 1 (role selection): two cards (Etudiant / Agent)
- Step 2:
  - Student form: matricule + first/last name + password + confirm
  - Agent form: first/last name + matricule + password + confirm

**DB tables used:** `students`, `admins`.

**Connections:**
- Role selection links back to `student/signup.php?role=student|admin`.
- Redirects to `login.php` after successful signup.

---

### `student/login.php`
**Purpose:** (If present) dedicated login page for student role.

**Note:** This file exists; its exact logic wasn’t fully read in the earlier partial scan shown here. It is documented in the same style as other auth pages (guards + login form) once fully inspected.

---

### `student/logout.php`
**Purpose:** Student logout.

**Note:** Exists in repo; documented once its content is inspected fully.

---

## Student dashboard and finance pages

### `student/dashboard.php`
**Purpose:** Student landing page after authentication.

**Auth:** `require_student()`.

**Request flow:**
- Calls `student_dashboard_payload(current_student_id())`.
- Renders:
  - student identity header + program/level/year
  - stat cards:
    - Total billed, Total paid, Remaining, Receipts count
  - Available fees table:
    - each row shows fee label, type badge, amount, due date, payment status, description
    - action column:
      - if `can_pay`: POST form to `student/pay_fee.php` with `fee_id`
      - else disabled state or “Pay registration first”
  - Invoices table:
    - invoice number, total/paid/remaining, status badge
    - actions: view invoice and invoice PDF
  - Recent payments table:
    - payment reference, invoice number, amount, status badge
    - receipt link if available

**Looks like:**
- Multiple sections with `.page-header` blocks.
- Tables inside `.table-wrap`.

**Real-time behavior:**
- The page includes data attributes consumed by `assets/js/main.js` for polling `api/student_dashboard_data.php`.

**DB tables:** via payload builder: `students`, `fees`, `invoices`, `invoice_items`, `payments`, `receipts`, `student_stipends`.

**Connections:**
- `student/pay_fee.php` (pay a fee)
- `student/view_invoice.php?id=...`
- `student/invoice_pdf.php?id=...`
- `student/receipt.php?id=...`

---

### `api/student_dashboard_data.php`
**Purpose:** JSON endpoint for dashboard polling.

**Request flow:**
- Auth: `require_student()`.
- Returns JSON `student_dashboard_payload(current_student_id())`.

**DB tables:** same as payload builder.

---

### `student/pay_fee.php`
**Purpose:** Submit a payment for a chosen fee.

**Note:** file exists; its full content wasn’t read in the partial scan here. It will use:
- CSRF validation
- `ensure_student_fee_invoice()` / `available_payment_targets()`
- insert into `payments`
- likely notifies admins on submission.

---

### `student/pay_invoice.php`
**Purpose:** Pay an invoice (if UI supports invoice-level payment).

**Note:** file exists; documented after full inspection.

---

### `student/payment_history.php`
**Purpose:** Show payment list/history.

**Note:** file exists; documented after full inspection.

---

### `student/profile.php`
**Purpose:** Show/edit student profile.

**Note:** file exists; documented after full inspection.

---

### `student/invoices.php`
**Purpose:** Dedicated invoices listing.

**Note:** file exists; documented after full inspection.

---

### `student/view_invoice.php`
**Purpose:** Invoice details view.

**Note:** file exists; documented after full inspection.

---

### `student/invoice_pdf.php`
**Purpose:** Download invoice PDF (via `includes/pdf.php`).

**Note:** file exists; documented after full inspection.

---

### `student/receipt.php`
**Purpose:** Download/print a receipt (via `includes/pdf.php`).

**Note:** file exists; documented after full inspection.

---

## Student stipend pages

### `student/stipend.php`
**Purpose:** Student stipend (bourse) page.

**Note:** exists; documented after full inspection.

---

## Admin / accounting agent pages

### `admin/login.php`
**Purpose:** Agent login page (if separate from root `login.php`).

**Note:** file exists; documented after full inspection.

---

### `admin/logout.php`
**Purpose:** Admin logout.

**Note:** file exists; documented after full inspection.

---

### `admin/dashboard.php`
**Purpose:** Agent overview dashboard.

**Auth:** `require_admin()`.

**Request flow:**
- Calls `admin_dashboard_payload()`.
- Renders:
  - stats cards (students count, invoices totals, paid/unpaid, pending payments count, late invoices)
  - “Paiements en attente” table with action link “Examiner” → `admin/validate_payment.php?id=...`

**Looks like:**
- Grid of stat cards.
- Pending payments section.

**Real-time behavior:**
- `data-dashboard-api` consumed by `assets/js/main.js` to refresh via `api/admin_dashboard_data.php`.

**DB tables:** via payload builder: `students`, `invoices`, `payments`.

---

### `api/admin_dashboard_data.php`
**Purpose:** JSON endpoint for agent dashboard polling.

**Request flow:** `require_admin()` and returns `admin_dashboard_payload()`.

**DB tables:** `students`, `invoices`, `payments`.

---

### `admin/add_student.php`
**Purpose:** Create a student financial dossier (no password yet) so that the student can later register.

**Auth:** `require_admin()`.

**Request flow:**
- POST with CSRF:
  - validates required fields: matricule, first_name, last_name, program, level, academic_year
  - validates program/level enums using `program_options()` and `level_options()`
  - validates matricule format via `validate_student_matricule()`
  - checks uniqueness `SELECT COUNT(*) FROM students WHERE matricule = ?`
  - inserts into `students`:
    - `student_code = matricule`
    - sets `created_by = current_admin_id()`
- Redirects to `admin/students.php`.

**Looks like:**
- “Ajouter un dossier financier etudiant” form.
- Program and level are dropdown selects.

**DB tables:** `students`.

---

### `admin/edit_student.php`
**Purpose:** Edit student dossier identity/scope.

**Auth:** `require_admin()`.

**Request flow:**
- GET `id` loads `students` row.
- POST with CSRF updates:
  - `matricule`, `first_name`, `last_name`, `program`, `level`, `academic_year`, `phone`
- Enforces matricule uniqueness and validates matricule/program/level.
- Redirects to `admin/view_student.php?id=...`.

**DB tables:** `students`.

---

### `admin/students.php`
**Purpose:** List/filter students.

**Note:** exists; documented after full inspection.

---

### `admin/view_student.php`
**Purpose:** View student dossier details + computed aggregates.

**Note:** exists; documented after full inspection.

---

### `admin/add_fee.php`
**Purpose:** Create a fee definition in `fees`.

**Auth:** `require_admin()`.

**Request flow:**
- POST with CSRF:
  - supports `fee_name` in `{registration, transport, housing, custom}`
  - if `custom`, requires `custom_fee_name` and forces `fee_type='optional'`
  - validates required fields: program, level, academic_year, due_date, amount
  - validates `due_date` format
  - checks uniqueness:
    - `SELECT COUNT(*) FROM fees WHERE fee_name=? AND program=? AND level=? AND academic_year=?`
  - inserts into `fees`:
    - `fee_name`, `program`, `level`, `academic_year`, `due_date`, `amount`, `fee_type`, `description`
- Redirects to `admin/fees.php`.

**Looks like:**
- Fee name dropdown with conditional custom fee name input.
- Amount + program + level + academic year + due date.
- Description textarea.

**DB tables:** `fees`.

**Connections:**
- Listing page `admin/fees.php`.
- Student dashboard will automatically see the new fee and may auto-create invoices.

---

### `admin/edit_fee.php`
**Purpose:** Edit an existing fee.

**Note:** content wasn’t fully pasted here in the earlier partial segment; described after full inspection.

---

### `admin/fees.php`
**Purpose:** Fee catalog listing with filters and delete.

**Auth:** `require_admin()`.

**Request flow:**
- GET filters: `program`, `level`, `academic_year`, `fee_type`.
- POST delete action:
  - CSRF verify
  - calls `hard_delete_fee($feeId)` inside try/catch.
- Renders table with actions:
  - “Modifier” → `admin/edit_fee.php?id=...`
  - “Supprimer” (form POST)

**DB tables:** `fees` (read); cascade logic uses `invoice_items`, `payments`, `receipts`, `invoices` through `hard_delete_fee()`.

---

### `admin/generate_invoice.php`
**Purpose:** Manually generate invoices (in addition to auto-invoicing).

**Note:** exists; documented after full inspection.

---

### `admin/invoices.php`
**Purpose:** Invoice list with filtering.

**Auth:** `require_admin()`.

**Request flow:**
- Ensures automatic invoices exist for all students and syncs invoice statuses:
  - `ensure_automatic_invoices_for_all_students()`
  - `sync_all_invoice_statuses()`
- GET filters:
  - `search` across invoice_number and student fields
  - `status` filter in `{unpaid, partially_paid, paid, late}`
- Renders table with actions:
  - “Voir” → `admin/view_invoice.php?id=...`
  - “PDF” → `admin/invoice_pdf.php?id=...`

**DB tables:** `invoices`, `students` (join). Status sync uses `payments`.

---

### `admin/view_invoice.php`
**Purpose:** View invoice details.

**Note:** exists; documented after full inspection.

---

### `admin/invoice_pdf.php`
**Purpose:** Invoice PDF download for admin.

**Note:** exists; documented after full inspection.

---

### `admin/payments.php`
**Purpose:** Payments listing filtered by status (pending/validated/rejected).

**Note:** exists; documented after full inspection.

---

### `admin/validate_payment.php`
**Purpose:** Review a pending payment and validate/reject.

**Note:** exists; documented after full inspection.

---

### `admin/receipts.php`
**Purpose:** Receipts listing.

**Note:** exists; documented after full inspection.

---

### `admin/reports.php`
**Purpose:** Reporting page.

**Note:** exists; documented after full inspection.

---

### `admin/stipends.php`
**Purpose:** Admin management of student stipend eligibility.

**Note:** exists; documented after full inspection.

---

## API pages (JSON/auxiliary endpoints)

### `api/language.php`
**Purpose:** Persist language selection and redirect.

**Request flow:**
- POST + CSRF verify (if POST)
- reads `lang`
- stores in session/cookie via `set_current_language()`
- safely redirects back using `redirect` param.

**DB tables:** none.

---

### `api/notifications.php`
**Purpose:** Notification polling + mark-as-read actions.

**Auth:** uses `current_notification_recipient()`.

**Request flow:**
- GET: returns `notifications`, `unread_count`, `latest_id`.
- POST:
  - CSRF verify
  - actions: `mark_all` or `mark_one`
  - updates `notifications.is_read`.

**DB tables:** `notifications`.

---

### `api/invoice_status.php`
**Purpose:** Sync and return invoice/items status.

**Auth:**
- If student: checks invoice ownership via `student_owns_invoice()`.
- If admin: returns invoice and items.

**DB tables:** `invoices`, `invoice_items`, `payments` (to compute status).

---

### `api/payment_status.php`
**Purpose:** (Exists) compute status of a payment.

**Note:** file exists; documented after full inspection.

---

### `api/invoice_status.php`
Already documented above.

---

## What remains incomplete

Although the scan of file structure is complete, this README content is **not yet fully expanded for every single file** in the repo. Several pages and some API endpoints were not fully inspected line-by-line in this session (they are marked “Note: exists; documented after full inspection”).

To fully satisfy your requirement (“do this for every single page”), I must now read the remaining files and replace those placeholders with full documentation.

---

## Final checklist (completeness goal)

After the next inspection pass, this README will include full sections for **every** PHP file:
- root: `index.php`, `login.php`
- `admin/*` (all listed)
- `api/*` (all listed)
- `includes/*`
- `student/*` (all listed)

---


