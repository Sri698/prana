# Prana Homeo Care — Responsive Case Record & Clinical Management Portal

A modern, fully responsive, bilingual (English & தமிழ்) clinical case-taking and patient management system designed for **Prana Homeo Care**.

- **Clinic Address:** Plot no 121, 2/94A Jayalakshmi Nagar, Kattupakkam, Chennai 600056
- **Helpline / WhatsApp:** +91 99623 03009
- **Compliance:** Built in compliance with National Commission for Homeopathy (NCH) clinical documentation guidelines.

---

## What This Project Does

### 1. Patient Case Intake (`/`)
- **Properly Responsive Across All Devices:**
  - **Mobile Phones (320px – 480px):** Clean compact sticky header showing current section counter, title snippet, percentage progress, an auto-scrolling horizontal pill strip (`1`, `2`, ..., `23`, `✓`), and an expandable **All Sections (24) ▾** drawer modal. Form inputs use 16px font to prevent mobile Safari auto-zoom, and all buttons feature $\ge 44\text{px}$ touch targets.
  - **Tablets (600px – 991px):** Adaptive single-column card with sticky navigation and comfortable touch spacing.
  - **Laptops & Desktops ($\ge 992\text{px}$):** Dynamic 2-column layout with a 290px sticky sidebar showing real-time completion status across all 24 sections.
- **Bilingual Support:** Instant toggle between English and தமிழ் at any step without losing entered data.
- **Auto-Save Drafts:** Automatically persists answers in local storage as the patient types, with manual "Save Draft" capability.
- **Seamless Connectivity & Post-Submission Confirmation:**
  - On submission, posts to `POST /api/cases` with CORS support.
  - Displays a dedicated confirmation card with a prominent **Case Reference ID** (e.g. `#1`).
  - **One-Click WhatsApp Notification:** Automatically launches a WhatsApp message to `+91 99623 03009` pre-filled with the patient's name and Reference ID.
  - **Print / PDF Record:** Clean print stylesheet allows patients and doctors to print or save the case record as a PDF.
  - **One-Click Copy Reference ID:** Instantly copies the reference number to the clipboard.

### 2. Doctor Workspace (`/admin`)
- **Doctor Sign In (`/admin/login`):**
  - Secured session authentication.
  - Default development credentials:
    - **Username:** `doctor`
    - **Password:** `prana2026`
- **Dashboard (`/admin`):**
  - Search submitted cases by patient name, phone number, or clinical diagnosis.
  - Responsive table on desktop/tablets, with automatic mobile cards view on smartphones.
  - Quick access stats: total submitted cases, authenticated role, and confidentiality status.
- **Case Detail & Follow-Up Manager (`/admin/case/<id>`):**
  - View full intake questionnaire responses.
  - Edit or append clinical notes and save changes.
  - Direct click-to-call (`tel:`) and click-to-WhatsApp links for patient communication.
  - Record longitudinal follow-up visits with visit date, patient progress, prescription/remedies/dosage, and dietary advice.
  - **Print / PDF Export (`window.print()`):** Dedicated `@media print` stylesheet that strips navigation and web chrome, producing a clean clinical report.

---

## Quick Start (Run Locally)

### 1. Requirements
- Python 3.10+ (Tested on Python 3.14)
- Flask 3.1+ & Waitress

### 2. Install Dependencies
```bash
pip install -r requirements.txt
```

### 3. Start Server
The project includes automatic `.env` configuration out-of-the-box:
```bash
python app.py
```
*The server will start on `http://127.0.0.1:5000`.*

### 4. Access URLs
- **Patient Intake Form:** [http://127.0.0.1:5000](http://127.0.0.1:5000)
- **Doctor Sign In:** [http://127.0.0.1:5000/admin/login](http://127.0.0.1:5000/admin/login)
  - Username: `doctor`
  - Password: `prana2026`
- **Health Check:** [http://127.0.0.1:5000/health](http://127.0.0.1:5000/health)

---

## API Endpoints

| Method | Endpoint | Description |
|---|---|---|
| `GET` | `/` | Renders the bilingual responsive patient intake form |
| `POST` | `/api/cases` | Submits patient answers JSON (CORS enabled) |
| `OPTIONS` | `/api/cases` | Preflight CORS handler |
| `GET` | `/api/cases/<id>` | Fetches case summary as JSON |
| `GET` | `/admin/login` | Doctor sign-in page |
| `POST` | `/admin/login` | Authenticates doctor credentials |
| `POST` | `/admin/logout` | Clears doctor session |
| `GET` | `/admin` | Doctor dashboard and case search |
| `GET` | `/admin/case/<id>` | Case detail and follow-up consultation file |
| `POST` | `/admin/case/<id>/edit` | Updates saved case fields |
| `POST` | `/admin/case/<id>/followup`| Adds a dated follow-up visit record |
| `GET` | `/health` | Service health status check |

---

## Production Deployment (Waitress)

To run in production behind a reverse proxy (e.g., Nginx, Caddy, or IIS) with HTTPS:

```bash
waitress-serve --host=127.0.0.1 --port=5000 app:app
```

Ensure environment variables are configured in `.env`:
```ini
PRANA_SECRET_KEY=your_strong_secret_key_here
PRANA_ADMIN_USER=doctor
PRANA_ADMIN_PASSWORD=your_strong_production_password
PORT=5000
```
