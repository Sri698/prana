# PRANA HOMEO CARE — cPanel Deployment & Database Setup Guide

Welcome to the production release of the **Prana Homeo Care** Web Platform & Clinical Management PWA.

This package is completely self-hosted using **PHP + MySQL** on your cPanel server. It eliminates dependency on third-party cloud services like Firebase or Supabase, keeping 100% of your patient case histories, prescriptions, pharmacy stock, and clinical photos privately on your own hosting server.

---

## 📦 What is Included in this Package

1. **Complete Multi-Page Clinical Website (20 Pages)**:
   - `index.html` (Homepage with Dr. Srinivasan credentials, Dr. Panda mascot, Thirukkural wellness carousel)
   - `about.html`, `appointment.html`, `services.html`, `contact.html`, `blog.html`, `health-assessment.html`, `panda-mascot.html`, `construct.html`
   - Clinical Condition Hubs: `treatment-respiratory.html`, `treatment-paediatrics.html`, `treatment-dermatology.html`, `treatment-womens-health.html`, `treatment-digestive.html`, `treatment-joints.html`
   - Clinical Articles: `article-allergic-rhinitis.html`, `article-adenoids.html`, `article-eczema-skin.html`, `article-pcos-hormones.html`, `article-thirukkural-medicine.html`
2. **Clinical EHR / Case Taking PWA (`case.html`)**:
   - Modern consultation sheet, rubrics assessment, prescription builder, pharmacy stock tracker, and patient timeline.
   - Built-in zero-data-loss system: Offline-ready with AES encrypted local cache and instant MySQL server sync.
3. **PHP REST API Suite (`api/`)**:
   - `api/config.php` — Database credentials & file upload settings
   - `api/db.php` — PDO connection handler with UTF-8 & CORS
   - `api/patients.php` — Patient registration, search, sorting, and duplicate ID prevention
   - `api/visits.php` — Consultation visits, complaints, mind rubrics, prescription records
   - `api/pharmacy.php` — Medicine inventory, potency, batch numbers, and reorder levels
   - `api/upload.php` — Clinical photo/test report file upload to local server
   - `api/health.php` — Server diagnostic health checker
   - `api/install.php` & root `install.php` — One-click automated database installer
4. **MySQL Database Schema (`database.sql`)**:
   - Clean, indexed table definitions for patients, visits, pharmacy, users, and attachments.
5. **Secure Storage (`uploads/`)**:
   - Protected with `.htaccess` to prevent script execution while serving patient photos.

---

## 🚀 3-Step cPanel Deployment

### Step 1: Upload and Extract ZIP in cPanel
1. Log into your **cPanel**.
2. Open **File Manager** and navigate into `public_html` (or the folder of your subdomain/addon domain).
3. Click **Upload** and upload `prana_cpanel_full_package.zip`.
4. Right-click the uploaded zip file and click **Extract**.

---

### Step 2: Create MySQL Database in cPanel
1. In cPanel, go to **MySQL Databases** (or **MySQL Database Wizard**).
2. Create a new database (e.g. `yourcpaneluser_pranadb`).
3. Create a new MySQL user (e.g. `yourcpaneluser_pranauser`) with a strong password.
4. Add the user to the database and check **ALL PRIVILEGES**.
5. In cPanel File Manager, edit `api/config.php` and set your credentials:
   ```php
   define('DB_HOST', 'localhost');
   define('DB_NAME', 'yourcpaneluser_pranadb');
   define('DB_USER', 'yourcpaneluser_pranauser');
   define('DB_PASS', 'YourStrongPasswordHere');
   ```

---

### Step 3: Run Database Setup (No Query Writing Required!)

You have **two easy choices** — you do NOT have to write SQL queries manually:

#### Option A: 1-Click Automated Web Installer (Recommended)
1. Open your browser and navigate to:
   ```
   https://yourdomain.com/install.php
   ```
2. Click the button: **"Run 1-Click Installation Now"**.
3. It will automatically create all tables (`prana_patients`, `prana_visits`, `prana_pharmacy`, `prana_users`, `prana_attachments`), configure the default doctor account, and test your uploads folder.

#### Option B: phpMyAdmin Import
If you prefer using phpMyAdmin:
1. In cPanel, click **phpMyAdmin**.
2. Select your newly created database on the left.
3. Click the **Import** tab at the top.
4. Click **Choose File** and select `database.sql` from your computer (or server).
5. Scroll down and click **Go** (or **Import**). All tables will be generated instantly.

---

## 🔒 Default Doctor Login Credentials
- **Email**: `clinic@email.com`
- **Password**: `password123`

You can also use the **Quick Access / Offline Clinic Mode** button on `case.html` to enter the clinic interface immediately.

---

## 🛡️ Zero Data Loss Guarantee
- If the server or internet connection is temporarily interrupted, `case.html` automatically saves all new patients, consultation records, and prescriptions into AES-encrypted local storage.
- As soon as connectivity or the database is restored, the application syncs with MySQL.
- Photos and reports uploaded in consultations are stored safely in the server's `uploads/` folder with local fallback.

---

*Crafted with excellence by Camora Technologies.*
