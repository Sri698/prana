import os, json, sqlite3, secrets
from datetime import datetime, timezone
from functools import wraps
from flask import Flask, request, jsonify, render_template, redirect, url_for, session, flash, abort

BASE_DIR = os.path.dirname(os.path.abspath(__file__))

# Auto-load .env if present
env_file = os.path.join(BASE_DIR, '.env')
if os.path.exists(env_file):
    try:
        with open(env_file, 'r', encoding='utf-8') as f:
            for line in f:
                line = line.strip()
                if line and not line.startswith('#') and '=' in line:
                    k, v = line.split('=', 1)
                    k, v = k.strip(), v.strip().strip('"').strip("'")
                    if k and k not in os.environ:
                        os.environ[k] = v
    except Exception:
        pass

DB_PATH = os.environ.get('PRANA_DB_PATH', os.path.join(BASE_DIR, 'prana_cases.sqlite3'))
app = Flask(__name__)
app.secret_key = os.environ.get('PRANA_SECRET_KEY') or secrets.token_hex(32)
app.config.update(SESSION_COOKIE_HTTPONLY=True, SESSION_COOKIE_SAMESITE='Lax')
ADMIN_USER = os.environ.get('PRANA_ADMIN_USER', 'doctor')
ADMIN_PASSWORD = os.environ.get('PRANA_ADMIN_PASSWORD', 'prana2026')


def db():
    conn = sqlite3.connect(DB_PATH)
    conn.row_factory = sqlite3.Row
    conn.execute('PRAGMA foreign_keys = ON')
    return conn


def init_db():
    with db() as c:
        c.execute('''CREATE TABLE IF NOT EXISTS cases (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            patient_name TEXT NOT NULL,
            phone TEXT DEFAULT '',
            diagnosis TEXT DEFAULT '',
            language TEXT DEFAULT 'en',
            answers_json TEXT NOT NULL,
            created_at TEXT NOT NULL,
            updated_at TEXT NOT NULL
        )''')
        c.execute('''CREATE TABLE IF NOT EXISTS followups (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            case_id INTEGER NOT NULL REFERENCES cases(id) ON DELETE CASCADE,
            visit_date TEXT NOT NULL,
            notes TEXT NOT NULL DEFAULT '',
            prescription TEXT NOT NULL DEFAULT '',
            advice TEXT NOT NULL DEFAULT '',
            created_at TEXT NOT NULL
        )''')


def now_iso():
    return datetime.now(timezone.utc).astimezone().isoformat(timespec='seconds')


def login_required(fn):
    @wraps(fn)
    def wrapper(*args, **kwargs):
        if not session.get('admin_logged_in'):
            return redirect(url_for('login', next=request.path))
        return fn(*args, **kwargs)
    return wrapper


@app.get('/')
def index():
    return render_template('index.html')


@app.after_request
def add_cors_and_security_headers(response):
    if request.path.startswith('/api/'):
        response.headers['Access-Control-Allow-Origin'] = '*'
        response.headers['Access-Control-Allow-Methods'] = 'GET, POST, OPTIONS'
        response.headers['Access-Control-Allow-Headers'] = 'Content-Type, Authorization'
    response.headers['X-Content-Type-Options'] = 'nosniff'
    response.headers['X-Frame-Options'] = 'SAMEORIGIN'
    return response


@app.route('/api/cases', methods=['POST', 'OPTIONS'])
def submit_case():
    if request.method == 'OPTIONS':
        return ('', 204)
    payload = request.get_json(silent=True)
    if not isinstance(payload, dict) or not isinstance(payload.get('answers'), dict):
        return jsonify(error='Invalid case record. Please review the form and submit again.'), 400
    answers = payload['answers']
    patient_name = str(answers.get('patientName') or answers.get('patient_name') or answers.get('Name /') or answers.get('s0f0') or '').strip()
    if not patient_name:
        return jsonify(error='Patient name is required.'), 400
    phone = str(answers.get('phone') or answers.get('Ph.No ./') or answers.get('phone_number') or answers.get('s0f8') or '').strip()
    diagnosis = str(answers.get('diagnosis') or answers.get('Diagnosis /') or '').strip()
    stamp = now_iso()
    with db() as c:
        cur = c.execute('INSERT INTO cases (patient_name, phone, diagnosis, language, answers_json, created_at, updated_at) VALUES (?,?,?,?,?,?,?)',
            (patient_name[:250], phone[:100], diagnosis[:500], str(payload.get('language','en'))[:5], json.dumps(answers, ensure_ascii=False), stamp, stamp))
        case_id = cur.lastrowid
    return jsonify(ok=True, id=case_id, message='Case submitted successfully.'), 201


@app.get('/api/cases/<int:case_id>')
def get_case_api(case_id):
    with db() as c:
        row = c.execute('SELECT id, patient_name, phone, diagnosis, language, created_at FROM cases WHERE id=?', (case_id,)).fetchone()
        if not row:
            return jsonify(error='Case not found.'), 404
        return jsonify(ok=True, case=dict(row))


@app.get('/admin/login')
def login():
    if not ADMIN_PASSWORD:
        return render_template('setup.html'), 503
    return render_template('login.html', next=request.args.get('next', '/admin'))


@app.post('/admin/login')
def login_post():
    if not ADMIN_PASSWORD:
        abort(503)
    username = request.form.get('username','')
    password = request.form.get('password','')
    if secrets.compare_digest(username, ADMIN_USER) and secrets.compare_digest(password, ADMIN_PASSWORD):
        session.clear()
        session['admin_logged_in'] = True
        return redirect(request.form.get('next') or url_for('admin_dashboard'))
    flash('Invalid username or password.')
    return redirect(url_for('login'))


@app.post('/admin/logout')
@login_required
def logout():
    session.clear()
    return redirect(url_for('login'))


@app.get('/admin')
@login_required
def admin_dashboard():
    q = request.args.get('q','').strip()
    with db() as c:
        if q:
            rows = c.execute("SELECT * FROM cases WHERE patient_name LIKE ? OR phone LIKE ? OR diagnosis LIKE ? ORDER BY id DESC", (f'%{q}%', f'%{q}%', f'%{q}%')).fetchall()
        else:
            rows = c.execute('SELECT * FROM cases ORDER BY id DESC').fetchall()
    return render_template('admin.html', cases=rows, q=q)


def get_case(case_id):
    with db() as c:
        case = c.execute('SELECT * FROM cases WHERE id=?', (case_id,)).fetchone()
        if not case:
            abort(404)
        followups = c.execute('SELECT * FROM followups WHERE case_id=? ORDER BY visit_date DESC, id DESC', (case_id,)).fetchall()
    return case, followups


@app.get('/admin/case/<int:case_id>')
@login_required
def case_detail(case_id):
    case, followups = get_case(case_id)
    return render_template('case.html', case=case, answers=json.loads(case['answers_json']), followups=followups)


@app.post('/admin/case/<int:case_id>/edit')
@login_required
def edit_case(case_id):
    case, _ = get_case(case_id)
    try:
        answers = json.loads(case['answers_json'])
    except Exception:
        answers = {}
    for key, value in request.form.items():
        if key.startswith('answer__'):
            field = key[len('answer__'):]
            answers[field] = value
    patient_name = str(answers.get('patientName','')).strip()
    if not patient_name:
        flash('Patient name cannot be empty.')
        return redirect(url_for('case_detail', case_id=case_id))
    with db() as c:
        c.execute('UPDATE cases SET patient_name=?, phone=?, diagnosis=?, answers_json=?, updated_at=? WHERE id=?',
                  (patient_name, str(answers.get('phone','')), str(answers.get('diagnosis','')), json.dumps(answers, ensure_ascii=False), now_iso(), case_id))
    flash('Case record updated.')
    return redirect(url_for('case_detail', case_id=case_id))


@app.post('/admin/case/<int:case_id>/followup')
@login_required
def add_followup(case_id):
    get_case(case_id)
    visit_date = request.form.get('visit_date','').strip() or datetime.now().date().isoformat()
    notes = request.form.get('notes','').strip()
    prescription = request.form.get('prescription','').strip()
    advice = request.form.get('advice','').strip()
    if not any([notes, prescription, advice]):
        flash('Enter at least one follow-up detail.')
        return redirect(url_for('case_detail', case_id=case_id))
    with db() as c:
        c.execute('INSERT INTO followups (case_id, visit_date, notes, prescription, advice, created_at) VALUES (?,?,?,?,?,?)',
                  (case_id, visit_date, notes, prescription, advice, now_iso()))
    flash('Follow-up added.')
    return redirect(url_for('case_detail', case_id=case_id) + '#followups')


@app.post('/admin/case/<int:case_id>/delete')
@login_required
def delete_case(case_id):
    with db() as c:
        c.execute('DELETE FROM followups WHERE case_id=?', (case_id,))
        c.execute('DELETE FROM cases WHERE id=?', (case_id,))
    flash('Case record deleted successfully.')
    return redirect(url_for('admin_dashboard'))


@app.get('/health')
def health():
    return jsonify(status='ok')


init_db()
if __name__ == '__main__':
    # Development only. Use a production WSGI server and HTTPS when deployed.
    app.run(host='127.0.0.1', port=int(os.environ.get('PORT', '5000')), debug=False)
