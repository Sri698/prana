/**
 * PRANA HOMEO CARE — CLINICAL PWA CONFIGURATION
 * Dedicated Database & Cloud Storage Settings
 */

window.PRANA_CONFIG = {
  clinicName: "PRANA HOMEO CARE",
  tagline: "Door of Hope",
  doctorName: "Dr. G. Srinivasan, M.D. (Hom)",
  doctorTitle: "Consultant Homoeopathic Paediatrician",
  phoneNumbers: ["+91 98842 23007", "+91 98843 46007"],
  address: "Plot no 121, 2/94A Jayalakshmi Nagar, Kattupakkam, Chennai 600056",
  email: "pranahomeocare@gmail.com",
  
  branches: [
    { id: "KATTUPAKKAM", name: "KATTUPAKKAM", sub: "PRIMARY CLINICAL BRANCH" },
    { id: "ONLINE_TELEHEALTH", name: "ONLINE / TELE-HEALTH", sub: "GLOBAL GOOGLE MEET" }
  ],

  encryptionKey: "Prana_HomeoCare_2026_Pro_v1",
  prescriptionStorageKey: "prana_prescriptions_v1",

  firebase: {
    apiKey: "AIzaSyBgaNGeyKLkZ3OX8TEOlkXjPAUwL9oKJWM",
    authDomain: "fir-clinical.firebaseapp.com",
    databaseURL: "https://fir-clinical-default-rtdb.firebaseio.com",
    projectId: "fir-clinical",
    storageBucket: "fir-clinical.firebasestorage.app",
    messagingSenderId: "432015586131",
    appId: "1:432015586131:web:cc91dc535e9a6d081b67a6"
  },

  supabase: {
    url: "https://your-prana-project.supabase.co",
    anonKey: "YOUR_PRANA_SUPABASE_ANON_KEY",
    bucket: "clinical-images"
  },

  apiBaseUrl: "https://pranahomeocare.in/api",
  bookingBaseUrl: "https://pranahomeocare.in"
};
