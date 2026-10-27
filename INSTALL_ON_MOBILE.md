# Prana Homeo Care — Mobile Android APK Installation Guide

## 📱 App Overview
- **App Name:** Prana Clinic
- **Package ID:** `in.pranahomeocare.app`
- **Version:** `1.0.0` (Build 1)
- **APK Binary:** `PranaHomeoCare_Case_Portal.apk` (Size: 9.3 MB)
- **Target OS:** Android 7.0 (API 24) to Android 15 (API 35/36)

---

## 🚀 How to Install on Any Android Phone or Tablet

### Option 1: Direct Transfer via USB / WhatsApp / Google Drive
1. Copy or transfer `PranaHomeoCare_Case_Portal.apk` to your phone:
   - **Via USB Cable:** Plug phone into PC, drag & drop `PranaHomeoCare_Case_Portal.apk` into your phone's `Downloads` folder.
   - **Via WhatsApp / Telegram:** Send the `.apk` file to yourself or your testing group.
   - **Via Google Drive / Gmail:** Upload the `.apk` to Google Drive or attach it to an email and open it on your mobile device.
2. Tap on `PranaHomeoCare_Case_Portal.apk` on your phone.
3. If prompted: **"Install unknown apps"**, toggle **"Allow from this source"**.
4. Tap **Install**, then **Open**.

---

### Option 2: Direct Install via ADB (If Developer Mode is Enabled)
Connect your Android phone via USB and run:
```bash
adb install -r PranaHomeoCare_Case_Portal.apk
```

---

## 🛡️ Zero Blank Page Architecture & Mobile Optimizations
1. **100% Offline-Ready Asset Bundling:**
   - All critical vendor libraries (React 18 production runtime, ReactDOM, TailwindCSS, Font Awesome 6 icons and webfonts, CryptoJS, jsPDF, and Firebase/Supabase SDKs) are pre-bundled directly into the APK assets.
   - No external CDN dependencies (e.g., `unpkg.com` or `cdnjs`) are required to boot the application. The app opens immediately even in airplane mode.
2. **Instant Emerald Branded Splash Loader:**
   - A hardware-accelerated emerald splash screen displays immediately on boot with the Prana logo and heartbeat animation, transitioning smoothly once the clinical workspace is ready.
3. **Safe-Area Insets & Ergonomics:**
   - Designed specifically for modern mobile viewports (supports notches, punch-hole cameras, curved corners, and home indicator bars via `viewport-fit=cover` and CSS safe-area insets).
4. **Hardware Acceleration & Cleartext Permitted:**
   - High-performance WebView rendering with local DOM storage, offline caching, and cross-origin permissions configured.
5. **Universal Backend Sync:**
   - Seamlessly talks to `https://pranahomeocare.in/api` for ANSI SQL upsert syncing of patients, visits, consultations, pharmacy inventory, and telehealth Google Meet links.
