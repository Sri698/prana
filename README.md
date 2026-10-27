# Prana Homeo Care — World-Class Multi-Page Clinical Website

**Client:** Prana Homeo Care  
**Founder:** Dr. G. Srinivasan, M.D (Hom) — Consultant Homoeopathic Paediatrician  
**Website Crafted by:** **Camora Technologies**

---

## Architecture & Features Overview

### 1. Multi-Page Structure
- [`index.html`](index.html) — **Flagship Homepage**: Hero with Dr. G. Srinivasan credentials, quick features strip, Dr. Panda mascot spotlight, clinical conditions showcase, 10-verse Thirukkural Marunthu carousel, hybrid appointment booking, testimonials, FAQ, and footer.
- [`about.html`](about.html) — **About Doctor**: In-depth biography of Dr. G. Srinivasan, M.D(Hom), pediatric specialization, classical repertory philosophy, and clinic values.
- [`services.html`](services.html) — **Conditions & Treatments**: Comprehensive coverage of 8 clinical domains: Paediatric Care, Respiratory & ENT, Dermatology & Skin, Digestive Health, Joint & Spine Disorders, Kidney Care, Women's Health, and Chronic Vitality.
- [`panda-mascot.html`](panda-mascot.html) — **Meet Dr. Panda**: Dedicated page for the beloved mascot. Features the Triad (*Strong Body, Calm Mind, Healthy Future*) and an **Interactive Dr. Panda Remedy Cabinet** tool explaining gentle remedies for everyday childhood ailments.
- [`appointment.html`](appointment.html) — **Appointment Booking**:
  - **In-Clinic Visit (Offline)**: Kattupakkam, Chennai clinic.
  - **Online Video Consultation (Google Meet)**: PAN-India & global video consults.
  - **Google Meet & Google Calendar Integration**: Generates meeting links, calendar invites, and pre-formatted WhatsApp consultation confirmations.
- [`health-assessment.html`](health-assessment.html) — **Interactive Health Score Assessment**: 10-question clinical self-evaluation calculating an instant vitality score (0–100) with tailored recommendations.
- [`blog.html`](blog.html) — **Clinical Blog & Articles**: Authoritative articles on pediatric tonsillitis, Thirukkural health philosophy, Chennai humidity skin care, and remote video consultations.
- [`contact.html`](contact.html) — **Contact & Clinic Location**: Physical address, landmarks (Porur / Iyyappanthangal vicinity), interactive Google Maps embed, helpline numbers, and direct inquiry forms.

---

## Luxury Branding & 3D Asset Integration

All images and 3D icons are custom built and seamlessly integrated:
- `assets/prana_brand_logo.png` & `assets/prana_brand_logo_trans.png` — Bespoke standalone brand logo ("PRĀNĀ HOMEO CARE · DOOR OF HOPE") with sacred lotus meditation medallion and green medical cross.
- `assets/prana_brand_logo_white.png` — Dark-mode / footer brand emblem.
- `assets/icon_3d_clinic.jpg` & `assets/icon_3d_video.jpg` — 3D isometric icons for In-Clinic and Google Meet video appointments.
- `assets/icon_3d_pills.jpg`, `assets/icon_3d_shield.jpg`, `assets/icon_3d_respiratory.jpg`, `assets/icon_3d_digestion.jpg`, `assets/icon_3d_sleep.jpg`, `assets/icon_3d_relief.jpg` — 3D luxury wellness icons.
- `assets/panda_doctor_child.png` — 3D render of Dr. Panda doctor in lab coat checking child temperature.
- `assets/panda_mascot_poster.jpg` & extracted poses (`assets/panda_strong_body.jpg`, `assets/panda_calm_mind.jpg`, `assets/panda_healthy_future.jpg`).
- All 10 Thirukkural visual slides (`assets/kural-941.jpg` to `assets/kural-950.jpg`).

---

## SEO, GEO & AEO Optimization

- **SEO**: Semantic HTML5 tags, comprehensive meta descriptions, canonical URLs, and Open Graph tags.
- **GEO (Local SEO)**: Embedded Schema.org `MedicalClinic` and `Physician` JSON-LD schemas with exact geo-coordinates (`13.0432748, 80.1242513`), street address, opening hours, and postal code `600056`.
- **AEO (Answer Engine Optimization)**: Structured Q&A cards and `FAQPage` schemas formatted for Google SGE, Perplexity, and AI search engines.

---

## Tech Stack & Credits

- **Frontend**: Pure Vanilla HTML5, CSS3 with luxury clinical design tokens, and Vanilla JavaScript.
- **Typography**: AI & modern tech fonts `Outfit`, `Plus Jakarta Sans`, `Lora`, `Cinzel`, and `Noto Sans Tamil`.
- **Testing & Verification**: Automated Playwright test suite (`test_world_class.js`) validating Desktop (1440px) and Mobile (375px) viewports with **0 console errors** and **0 broken assets**.
- **Attribution**: "Website crafted with excellence by **Camora Technologies**" in header topbar, page footers, and metadata.

---

## Running & Testing

```bash
# Run local preview server and automated Playwright validation
npm test
```
Or open any `.html` file in your browser.
