import sqlite3, json, os

db_path = os.path.join(os.path.dirname(os.path.abspath(__file__)), 'prana_local.sqlite3')
conn = sqlite3.connect(db_path)
c = conn.cursor()

# Clear existing cases to give a fresh clean dummy dataset
c.execute("DELETE FROM cases")
c.execute("DELETE FROM followups")

dummy_cases = [
    {
        "patient_name": "Master K. Mithran",
        "phone": "+91 98842 23007",
        "diagnosis": "Hypertrophied Adenoids with Recurrent Tonsillitis",
        "language": "both",
        "created_at": "2026-10-14 10:30:00",
        "updated_at": "2026-10-30 18:20:00",
        "answers": {
            "patientName": "Master K. Mithran",
            "age": "6 years",
            "gender": "Male",
            "phone": "+91 98842 23007",
            "address": "Plot 42, Alwarthirunagar, Porur, Chennai 600087",
            "occupation": "School Student (Grade 1)",
            "maritalStatus": "Single",
            "chiefComplaint": "Mouth breathing during sleep, loud snoring, frequent throat infections catching cold after ice creams or cold drinks. Enlarged tonsils (Grade 3), swollen cervical glands, sluggish appetite.",
            "duration": "Since 1.5 years, worsening in past 4 months",
            "mind": "Stubborn, obstinate, throws temper tantrums when refused. Extremely affectionate at other times. Fears the dark, shadows, and dogs.",
            "thermal": "Hot child, kicks off blankets at night. Profuse perspiration on scalp/neck during sleep soaking the pillow with sour odor.",
            "thirst": "Moderate thirst, prefers cold water.",
            "appetite": "Poor appetite for regular meals. Strong cravings for boiled eggs, chocolates, sweets, and occasionally licks chalk/pencil.",
            "aversions": "Strong aversion to hot milk and green leafy vegetables.",
            "sleep": "Restless sleep, moans in sleep, sleeps in knee-chest position with mouth wide open.",
            "systemic": "Recurrent rhinitis, sticky thick white nasal discharge, enlarged jugulodigastric lymph nodes.",
            "pastHistory": "Recurrent bronchopneumonia at age 2. Vaccinated up to date.",
            "familyHistory": "Mother has chronic allergic rhinitis. Paternal grandfather had chronic asthma.",
            "doctorNotes": "Calcarea Carb constitutional type: Fair, flabby, perspiration on head, egg craving, tonsillar hypertrophy. Miasm: Psoro-Sycotic."
        },
        "followups": [
            {
                "visit_date": "2026-10-15",
                "notes": "First consultation. Detailed case record taken. Advised dietary modifications.",
                "prescription": "1. Calcarea Carb 200C — 4 pills once daily in morning on empty stomach (15 days)\n2. Baryta Carb 30C — 4 pills SOS if throat pain develops",
                "advice": "Strictly avoid ice cream, cold water, and curd at night. Steam inhalation once daily."
            },
            {
                "visit_date": "2026-10-30",
                "notes": "Snoring reduced by 60%. Child slept peacefully for past 10 nights with mouth closed. Appetite has improved significantly, asks for food on his own. No tonsil flare-up.",
                "prescription": "1. Calcarea Carb 1M — 1 single powder dose stat\n2. Sac Lac (Placebo) — 4 pills twice daily before food (21 days)",
                "advice": "Maintain nasal saline drops at bedtime. Continue avoiding chilled items."
            }
        ]
    },
    {
        "patient_name": "Mrs. S. Radhika",
        "phone": "+91 98843 46007",
        "diagnosis": "Atopic Flexural Eczema with Lichenification",
        "language": "both",
        "created_at": "2026-10-20 14:15:00",
        "updated_at": "2026-10-24 16:45:00",
        "answers": {
            "patientName": "Mrs. S. Radhika",
            "age": "32 years",
            "gender": "Female",
            "phone": "+91 98843 46007",
            "address": "Flat 3B, Ceebros Park, Kattupakkam, Chennai 600056",
            "occupation": "Bank Assistant Manager",
            "maritalStatus": "Married",
            "chiefComplaint": "Severe itching and dry eczema in elbow creases and behind knees. Honey-like thick sticky discharge upon scratching. Skin has thickened and darkened (lichenification). Burning pain after washing.",
            "duration": "Chronic since 3 years, aggravates during monsoon and winter",
            "mind": "Anxious about recovery, weeps easily during consultation, timid, lacks confidence, feels better when consoled.",
            "thermal": "Very chilly patient, hands and feet always cold. Cannot tolerate cold wind or AC.",
            "thirst": "Almost completely thirstless. Drinks only 1 to 1.5 litres of water a day.",
            "appetite": "Normal, cravings for sweets, cakes, and warm desserts. Aversion to fatty meat and curd.",
            "sleep": "Disturbed by night itching between 11 PM and 2 AM. Restless legs in bed.",
            "systemic": "Skin is unhealthy; small scratches suppurate. Delayed, scanty menstrual cycle.",
            "pastHistory": "Childhood infantile eczema treated with steroid ointments with temporary suppressions.",
            "familyHistory": "Mother has rheumatoid arthritis; sister has dust allergy.",
            "doctorNotes": "Graphites picture: Chilly, constipated tendencies, thick glutinous honey-like exudation, flexural eczema. Psoric-sycotic miasm."
        },
        "followups": [
            {
                "visit_date": "2026-10-24",
                "notes": "Sticky discharge has stopped. Eruptions are drying out. Daytime itching is 50% better. Night sleep improved.",
                "prescription": "1. Graphites 200C — 4 pills twice daily before food (15 days)\n2. Petroleum 30C — 4 pills at night (15 days)",
                "advice": "Apply pure virgin coconut oil. Avoid hot water bath and synthetic body soaps."
            }
        ]
    },
    {
        "patient_name": "Mr. Rajesh Kannan",
        "phone": "+91 98401 55678",
        "diagnosis": "Cervical Spondylosis with Cervicogenic Vertigo",
        "language": "both",
        "created_at": "2026-10-18 11:00:00",
        "updated_at": "2026-10-29 17:10:00",
        "answers": {
            "patientName": "Mr. Rajesh Kannan",
            "age": "46 years",
            "gender": "Male",
            "phone": "+91 98401 55678",
            "address": "Door 12, Senthil Nagar, Porur, Chennai 600116",
            "occupation": "Senior Software Architect",
            "maritalStatus": "Married",
            "chiefComplaint": "Constant neck pain radiating down right arm to fingertips with numbness. Episodes of vertigo on turning head quickly. MRI shows C5-C6 osteophytes with nerve root compression.",
            "duration": "8 months, exacerbated by long laptop screen hours",
            "mind": "Ambitious, short-tempered, impatient, workaholic. Irritable when interrupted or questioned.",
            "thermal": "Chilly, sensitive to AC air drafts falling on neck.",
            "thirst": "Thirsty for warm water and hot coffee.",
            "appetite": "Good appetite, craves highly spiced curries, stimulants (coffee 4-5 cups a day). Ineffective urging for stool with chronic constipation.",
            "sleep": "Sleeps 5-6 hours, wakes up unrefreshed with neck stiffness.",
            "systemic": "Gastro-oesophageal reflux disease, acidity, cervical muscle spasms.",
            "pastHistory": "Hypertension (controlled on Telmisartan 40mg).",
            "familyHistory": "Father had stroke; brother has lumbar disc bulge.",
            "doctorNotes": "Nux Vomica clearing state (sedentary, stimulants, irritability, constipation) followed by Rhus Tox for spondylotic stiffness."
        },
        "followups": [
            {
                "visit_date": "2026-10-18",
                "notes": "Initial consultation. Severe stiffness in cervical spine and right trapezius spasm.",
                "prescription": "1. Nux Vomica 200C — 4 pills bedtime (10 days)\n2. Hypericum 200C — 4 pills twice daily for nerve compression (10 days)",
                "advice": "Raise laptop screen to eye level. Reduce coffee to 1 cup. Hot fermentation on neck twice daily."
            },
            {
                "visit_date": "2026-10-29",
                "notes": "Digestive symptoms resolved. Bowels moving freely. Arm numbness and tingling completely cleared. Vertigo reduced by 80%.",
                "prescription": "1. Rhus Tox 200C — 4 pills twice daily (15 days)\n2. Magnesium Phos 6X — 4 tablets in warm water twice daily for muscle tension",
                "advice": "Continue neck isometric exercises. Avoid sudden jerky neck rotations."
            }
        ]
    },
    {
        "patient_name": "Ms. Deepa Narayanan",
        "phone": "+91 97910 88234",
        "diagnosis": "PCOS with Oligomenorrhea & Hormonal Acne",
        "language": "both",
        "created_at": "2026-10-22 16:30:00",
        "updated_at": "2026-10-28 15:40:00",
        "answers": {
            "patientName": "Ms. Deepa Narayanan",
            "age": "25 years",
            "gender": "Female",
            "phone": "+91 97910 88234",
            "address": "No 8, Balaji Avenue, Iyyappanthangal, Chennai 600056",
            "occupation": "Graphic Designer",
            "maritalStatus": "Single",
            "chiefComplaint": "Menstrual cycles delayed by 45 to 60 days, scanty dark flow for 2 days. Persistent cystic painful acne on jawline, chin, and back. Weight gain of 7 kg over past 10 months. Ultrasound pelvis reveals bilateral polycystic ovaries.",
            "duration": "2 years",
            "mind": "Gentle, mild, yielding disposition. Easily overwhelmed by emotional stress, weeps while explaining symptoms. Craves sympathy and reassurance.",
            "thermal": "Chilly, yet feels suffocated in closed warm rooms; craves cool open breeze.",
            "thirst": "Remarkably thirstless, goes through whole day with barely 2 glasses of water.",
            "appetite": "Variable appetite, cravings for desserts and ice creams. Complete aversion to fatty foods, butter, and pork.",
            "sleep": "Difficulty falling asleep, sleeps on abdomen or with arms raised above head.",
            "systemic": "Hormonal acne flare-ups before expected period date. Mild pelvic heaviness.",
            "pastHistory": "History of irregular periods since menarche at age 14.",
            "familyHistory": "Mother has Type 2 Diabetes; maternal aunt has hypothyroidism.",
            "doctorNotes": "Classic Pulsatilla Nigricans picture: Mild disposition, weeping tendency, thirstlessness, delayed scanty menses, better in open air."
        },
        "followups": [
            {
                "visit_date": "2026-10-28",
                "notes": "Menses established naturally after 36 days without hormonal tablets. Flow lasted 4 days with healthy color and no clotting. New acne breakouts stopped.",
                "prescription": "1. Pulsatilla 200C — 4 pills alternate mornings on empty stomach (21 days)\n2. Berberis Aquifolium Q — 10 drops in water twice daily after food for acne clearing",
                "advice": "30-40 minutes brisk walking daily. Eliminate processed sugars, maida, and dairy milk."
            }
        ]
    },
    {
        "patient_name": "Master R. Tharun",
        "phone": "+91 94440 12345",
        "diagnosis": "Extrinsic Bronchial Asthma with Midnight Dyspnoea",
        "language": "both",
        "created_at": "2026-10-25 09:45:00",
        "updated_at": "2026-10-26 12:15:00",
        "answers": {
            "patientName": "Master R. Tharun",
            "age": "11 years",
            "gender": "Male",
            "phone": "+91 94440 12345",
            "address": "15, Sri Krishna Nagar, Vanagaram, Chennai 600095",
            "occupation": "Student (Grade 6)",
            "maritalStatus": "Single",
            "chiefComplaint": "Episodes of severe wheezing, chest tightness, and dry hacking cough. Attacks triggered by dust exposure, rainy weather, and midnight drafts. Aggravation between 1 AM and 3 AM; child cannot lie down flat and must sit up bent forward.",
            "duration": "Since age 7 (4 years)",
            "mind": "Anxious during breathlessness, fears being left alone. Very tidy and fastidious boy, keeps his study table and books meticulously arranged.",
            "thermal": "Extremely chilly, wants to be covered with thick blankets even in warm weather. Drinking anything cold triggers an immediate coughing fit.",
            "thirst": "Intense thirst for small sips of warm water at frequent intervals.",
            "appetite": "Moderate appetite, desires warm soups and citrus fruits.",
            "sleep": "Disturbed by nocturnal coughing fits at 2 AM.",
            "systemic": "Bilateral expiratory wheezing on chest auscultation, prolonged expiration.",
            "pastHistory": "Infantile eczema in toddlerhood.",
            "familyHistory": "Father has allergic bronchitis; grandfather has chronic obstructive pulmonary disease.",
            "doctorNotes": "Arsenicum Album keynote symptoms: 1-2 AM aggravation, sitting up relieved, fastidiousness, small sips thirst, extreme chilliness."
        },
        "followups": [
            {
                "visit_date": "2026-10-26",
                "notes": "Initial consultation. Severe midnight coughing and audible wheeze.",
                "prescription": "1. Arsenicum Album 200C — 4 pills twice daily before food (15 days)\n2. Ipecacuanha 30C — 4 pills SOS if spasmodic suffocative cough begins",
                "advice": "Keep bedroom dust-free. Wash bed linen in warm water weekly. Drink lukewarm water throughout the day."
            }
        ]
    }
]

for case in dummy_cases:
    answers_json = json.dumps(case['answers'], ensure_ascii=False)
    c.execute('''
        INSERT INTO cases (patient_name, phone, diagnosis, language, answers_json, created_at, updated_at)
        VALUES (?, ?, ?, ?, ?, ?, ?)
    ''', (case['patient_name'], case['phone'], case['diagnosis'], case['language'], answers_json, case['created_at'], case['updated_at']))
    case_id = c.lastrowid
    
    for f in case.get('followups', []):
        c.execute('''
            INSERT INTO followups (case_id, visit_date, notes, prescription, advice, created_at)
            VALUES (?, ?, ?, ?, ?, ?)
        ''', (case_id, f['visit_date'], f['notes'], f['prescription'], f['advice'], case['created_at']))

conn.commit()
conn.close()
print(f"Successfully populated {len(dummy_cases)} rich clinical dummy cases into prana_local.sqlite3!")
