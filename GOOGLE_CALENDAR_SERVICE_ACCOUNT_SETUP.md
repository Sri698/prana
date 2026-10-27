# 📅 Google Calendar & Google Meet Service Account Integration Guide
### Prana Homeo Care — Automated Consultation Booking Engine

This guide walks you step-by-step through setting up a **Google Cloud Service Account** so that appointments booked through `book_appointment.php`, `appointment.html`, and `case.html` automatically sync to your Google Calendar and generate instant Google Meet video rooms.

---

## ⚡ What This Does
1. **Automated Google Calendar Sync**: Whenever a patient books an appointment, an event is added directly to Dr. Srinivasan's Google Calendar (`pranahomeocare@gmail.com`).
2. **Instant Google Meet Links**: For online consultations, an encrypted Google Meet video room is provisioned and included in both the patient and doctor confirmation emails.
3. **No Login Required for Patients**: Operates via server-to-server credentials (Service Account), meaning patients never have to log into Google or authorize permissions.

> **Note**: Even before you upload the Service Account key, Prana Homeo Care already works 100% out of the box! It uses Google Meet (`https://meet.google.com/new` or your permanent clinic room) and 1-click Google Calendar add links. Adding the Service Account enables direct calendar writing.

---

## 🛠️ Step-by-Step Google Cloud Console Setup

### Step 1: Open Google Cloud Console
1. Open your browser and navigate to: **[https://console.cloud.google.com/](https://console.cloud.google.com/)**
2. Sign in with the clinic Google account (e.g. `pranahomeocare@gmail.com`).

### Step 2: Create a Project
1. In the top navigation bar, click the project dropdown (next to the "Google Cloud" logo).
2. Click **NEW PROJECT** in the top right.
3. Project Name: `Prana Homeo Care`
4. Click **CREATE** and wait 5–10 seconds for the project to be created.
5. Select the newly created project from the notifications or project dropdown.

### Step 3: Enable the Google Calendar API
1. In the left sidebar menu (or top search bar), go to **APIs & Services** > **Library**.
2. Search for: `Google Calendar API`.
3. Click on **Google Calendar API** in the search results.
4. Click the blue **ENABLE** button.

### Step 4: Create a Service Account
1. From the left navigation menu, go to **IAM & Admin** > **Service Accounts**.
2. Click **+ CREATE SERVICE ACCOUNT** at the top.
3. Fill in:
   - **Service account name**: `prana-calendar-bot`
   - **Service account ID**: `prana-calendar-bot` (auto-filled)
   - **Description**: `Calendar and Google Meet automation for Prana Homeo Care`
4. Click **CREATE AND CONTINUE**.
5. Role (Optional): Select **Basic** > **Editor** (or leave blank).
6. Click **CONTINUE**, then click **DONE**.
7. Note down the **Email address** of your new service account (it will look like `prana-calendar-bot@prana-homeo-care-xxxxx.iam.gserviceaccount.com`).

### Step 5: Generate and Download the JSON Key
1. In the Service Accounts list, click on the service account you just created (`prana-calendar-bot@...`).
2. Go to the **KEYS** tab at the top.
3. Click **ADD KEY** > **Create new key**.
4. Choose **JSON** as the key type.
5. Click **CREATE**.
6. A `.json` file will automatically download to your computer.

### Step 6: Rename and Upload to cPanel
1. Rename the downloaded file to: `google_service_account.json`
2. Open your cPanel File Manager and navigate to `public_html/api/`.
3. Click **Upload** and upload `google_service_account.json` into:
   - Path: `public_html/api/google_service_account.json` (or root `public_html/google_service_account.json`)

### Step 7: Share Your Clinic Google Calendar with the Service Account
This is the **most crucial step** so Google gives permission to write to your calendar:
1. Open **[https://calendar.google.com/](https://calendar.google.com/)** in your browser.
2. Under "My calendars" on the left, hover over your main clinic calendar (`pranahomeocare@gmail.com` or Dr. G. Srinivasan) and click the **3 vertical dots** (⋮).
3. Click **Settings and sharing**.
4. Scroll down to the section titled **Share with specific people or groups**.
5. Click **+ Add people and groups**.
6. Paste your service account email from Step 4 (e.g. `prana-calendar-bot@prana-homeo-care-xxxxx.iam.gserviceaccount.com`).
7. Under **Permissions**, select: **"Make changes to events"**.
8. Click **Send**.

---

## 🎯 Verification & Testing

1. Go to your live appointment page:
   `https://pranahomeocare.in/appointment.html`
2. Select a date: the available slots will dynamically load from `book_appointment.php?action=slots&date=YYYY-MM-DD`.
3. Fill in patient test details and choose **Online Video (Google Meet)**.
4. Click **Confirm & Request Appointment**:
   - The booking will be saved in your MySQL database (`prana_appointments`).
   - An HTML email notification with patient details and WhatsApp link is sent to `pranahomeocare@gmail.com`.
   - A confirmation email with the Google Meet link is sent to the patient.
   - The appointment appears immediately in the Doctor Portal (`case.html` > Admin & Appointments).
