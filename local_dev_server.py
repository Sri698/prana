#!/usr/bin/env python3
"""
PRANA HOMEO CARE — LOCAL FULL-STACK DEVELOPMENT & TESTING SERVER
Runs locally on http://localhost:8080 (or 127.0.0.1:8080).
Serves:
  - Doctor App: http://localhost:8080/case.html (or http://localhost:8080/case)
  - Patient Intake Form: http://localhost:8080/new_case/case.html
  - Redesign / New Prana App: http://localhost:8080/new_prana/case
  - Full API with SQLite: /api/cases & /api/cases.php (GET, POST, PUT, DELETE)
"""

import os, sys, json, sqlite3, mimetypes
from http.server import HTTPServer, BaseHTTPRequestHandler
from urllib.parse import urlparse, parse_qs
from datetime import datetime

PORT = 8080
BASE_DIR = os.path.dirname(os.path.abspath(__file__))
DB_PATH = os.path.join(BASE_DIR, 'prana_local.sqlite3')

def init_db():
    conn = sqlite3.connect(DB_PATH)
    c = conn.cursor()
    c.execute('''
        CREATE TABLE IF NOT EXISTS cases (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            patient_name TEXT NOT NULL,
            phone TEXT DEFAULT '',
            diagnosis TEXT DEFAULT '',
            language TEXT DEFAULT 'both',
            answers_json TEXT NOT NULL,
            created_at TEXT NOT NULL,
            updated_at TEXT NOT NULL
        )
    ''')
    c.execute('''
        CREATE TABLE IF NOT EXISTS followups (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            case_id INTEGER NOT NULL REFERENCES cases(id) ON DELETE CASCADE,
            visit_date TEXT NOT NULL,
            notes TEXT DEFAULT '',
            prescription TEXT DEFAULT '',
            advice TEXT DEFAULT '',
            created_at TEXT NOT NULL
        )
    ''')
    conn.commit()
    conn.close()

init_db()

class PranaLocalHandler(BaseHTTPRequestHandler):
    def send_cors_headers(self):
        self.send_header('Access-Control-Allow-Origin', '*')
        self.send_header('Access-Control-Allow-Methods', 'GET, POST, PUT, DELETE, OPTIONS')
        self.send_header('Access-Control-Allow-Headers', 'Content-Type, Authorization')

    def do_OPTIONS(self):
        self.send_response(204)
        self.send_cors_headers()
        self.end_headers()

    def send_json(self, data, status=200):
        body = json.dumps(data, ensure_ascii=False).encode('utf-8')
        self.send_response(status)
        self.send_header('Content-Type', 'application/json; charset=utf-8')
        self.send_cors_headers()
        self.send_header('Content-Length', str(len(body)))
        self.end_headers()
        self.wfile.write(body)

    def read_json_body(self):
        try:
            length = int(self.headers.get('Content-Length', 0))
            if length <= 0:
                return {}
            raw = self.rfile.read(length).decode('utf-8')
            return json.loads(raw)
        except Exception:
            return {}

    # -------------------------------------------------------------
    # ROUTING
    # -------------------------------------------------------------
    def do_GET(self):
        parsed = urlparse(self.path)
        path = parsed.path
        query = parse_qs(parsed.query)

        # 1. API: /api/cases or /api/cases.php
        if path in ['/api/cases', '/api/cases.php']:
            case_id = int(query.get('id', [0])[0])
            search = query.get('search', query.get('q', ['']))[0].strip()

            conn = sqlite3.connect(DB_PATH)
            conn.row_factory = sqlite3.Row
            c = conn.cursor()

            if case_id > 0:
                row = c.execute('SELECT * FROM cases WHERE id = ?', (case_id,)).fetchone()
                if not row:
                    conn.close()
                    return self.send_json({'status': 'error', 'message': 'Case not found'}, 404)
                
                case_dict = dict(row)
                try:
                    case_dict['answers'] = json.loads(case_dict['answers_json'])
                except Exception:
                    case_dict['answers'] = {}
                
                f_rows = c.execute('SELECT * FROM followups WHERE case_id = ? ORDER BY visit_date DESC, id DESC', (case_id,)).fetchall()
                case_dict['followups'] = [dict(f) for f in f_rows]
                conn.close()
                return self.send_json({'status': 'success', 'data': case_dict, 'case': case_dict, 'ok': True})
            else:
                sql = '''
                    SELECT c.id, c.patient_name, c.phone, c.diagnosis, c.language, c.created_at, c.updated_at,
                           COUNT(f.id) AS followup_count, MAX(f.visit_date) AS last_visit
                    FROM cases c
                    LEFT JOIN followups f ON c.id = f.case_id
                '''
                params = []
                if search:
                    sql += " WHERE c.patient_name LIKE ? OR c.phone LIKE ? OR c.diagnosis LIKE ?"
                    params = [f'%{search}%', f'%{search}%', f'%{search}%']
                sql += " GROUP BY c.id ORDER BY c.id DESC LIMIT 200"

                rows = c.execute(sql, params).fetchall()
                cases_list = [dict(r) for r in rows]
                conn.close()
                return self.send_json({'status': 'success', 'data': cases_list, 'cases': cases_list, 'ok': True, 'count': len(cases_list)})

        # 2. Static File Handling
        self.serve_static(path)

    def do_POST(self):
        parsed = urlparse(self.path)
        path = parsed.path
        query = parse_qs(parsed.query)

        if path in ['/api/cases', '/api/cases.php']:
            data = self.read_json_body()
            action = query.get('action', [data.get('action', '')])[0]

            # 2a. Add doctor followup & prescription
            if action == 'add_followup':
                case_id = int(data.get('case_id', 0))
                visit_date = str(data.get('visit_date', datetime.now().strftime('%Y-%m-%d'))).strip()
                notes = str(data.get('notes', '')).strip()
                prescription = str(data.get('prescription', '')).strip()
                advice = str(data.get('advice', '')).strip()

                if case_id <= 0 or (not notes and not prescription and not advice):
                    return self.send_json({'status': 'error', 'message': 'Case ID and notes/prescription required.'}, 400)

                stamp = datetime.now().strftime('%Y-%m-%d %H:%M:%S')
                conn = sqlite3.connect(DB_PATH)
                c = conn.cursor()
                c.execute('''
                    INSERT INTO followups (case_id, visit_date, notes, prescription, advice, created_at)
                    VALUES (?, ?, ?, ?, ?, ?)
                ''', (case_id, visit_date, notes, prescription, advice, stamp))
                fid = c.lastrowid
                conn.commit()
                conn.close()

                return self.send_json({'status': 'success', 'ok': True, 'id': fid, 'message': 'Follow-up saved.'}, 201)

            # 2b. Submit patient case record
            answers = data.get('answers')
            if not isinstance(answers, dict):
                return self.send_json({'status': 'error', 'message': 'Answers payload required.'}, 400)

            patient_name = str(answers.get('patientName') or answers.get('patient_name') or answers.get('Name /') or answers.get('s0f0') or '').strip()
            if not patient_name:
                return self.send_json({'status': 'error', 'message': 'Patient name is required.'}, 400)

            phone = str(answers.get('phone') or answers.get('Ph.No ./') or answers.get('phone_number') or answers.get('s0f8') or '').strip()
            diagnosis = str(answers.get('diagnosis') or answers.get('Diagnosis /') or '').strip()
            lang = str(data.get('language', 'both'))
            answers_json = json.dumps(answers, ensure_ascii=False)
            stamp = datetime.now().strftime('%Y-%m-%d %H:%M:%S')

            conn = sqlite3.connect(DB_PATH)
            c = conn.cursor()
            c.execute('''
                INSERT INTO cases (patient_name, phone, diagnosis, language, answers_json, created_at, updated_at)
                VALUES (?, ?, ?, ?, ?, ?, ?)
            ''', (patient_name, phone, diagnosis, lang, answers_json, stamp, stamp))
            cid = c.lastrowid
            conn.commit()
            conn.close()

            return self.send_json({'status': 'success', 'ok': True, 'id': cid, 'message': 'Case submitted successfully.'}, 201)

        self.send_json({'status': 'error', 'message': 'Not found'}, 404)

    def do_PUT(self):
        parsed = urlparse(self.path)
        path = parsed.path

        if path in ['/api/cases', '/api/cases.php']:
            data = self.read_json_body()
            case_id = int(data.get('id', 0))
            answers = data.get('answers')

            if case_id <= 0 or not isinstance(answers, dict):
                return self.send_json({'status': 'error', 'message': 'Invalid update payload'}, 400)

            patient_name = str(data.get('patient_name') or answers.get('patientName') or '').strip()
            phone = str(data.get('phone') or answers.get('phone') or '').strip()
            diagnosis = str(data.get('diagnosis') or answers.get('diagnosis') or '').strip()
            stamp = datetime.now().strftime('%Y-%m-%d %H:%M:%S')

            conn = sqlite3.connect(DB_PATH)
            c = conn.cursor()
            c.execute('''
                UPDATE cases
                SET patient_name = ?, phone = ?, diagnosis = ?, answers_json = ?, updated_at = ?
                WHERE id = ?
            ''', (patient_name, phone, diagnosis, json.dumps(answers, ensure_ascii=False), stamp, case_id))
            conn.commit()
            conn.close()

            return self.send_json({'status': 'success', 'ok': True, 'message': 'Case updated successfully.'})

        self.send_json({'status': 'error', 'message': 'Not found'}, 404)

    def do_DELETE(self):
        parsed = urlparse(self.path)
        path = parsed.path
        query = parse_qs(parsed.query)

        if path in ['/api/cases', '/api/cases.php']:
            action = query.get('action', [''])[0]
            conn = sqlite3.connect(DB_PATH)
            c = conn.cursor()

            if action == 'delete_followup':
                fid = int(query.get('followup_id', [0])[0])
                c.execute('DELETE FROM followups WHERE id = ?', (fid,))
                conn.commit()
                conn.close()
                return self.send_json({'status': 'success', 'ok': True, 'message': 'Followup deleted.'})
            else:
                cid = int(query.get('id', [0])[0])
                c.execute('DELETE FROM cases WHERE id = ?', (cid,))
                c.execute('DELETE FROM followups WHERE case_id = ?', (cid,))
                conn.commit()
                conn.close()
                return self.send_json({'status': 'success', 'ok': True, 'message': 'Case deleted.'})

        self.send_json({'status': 'error', 'message': 'Not found'}, 404)

    def serve_static(self, path):
        # Friendly rewrites
        if path in ['/', '/case', '/case/']:
            rel = 'case.html'
        elif path in ['/new_prana/case', '/new_prana/case/']:
            rel = 'prana_homeocare_responsive_redesign/case.html'
        elif path.startswith('/'):
            rel = path.lstrip('/')
        else:
            rel = path

        file_path = os.path.normpath(os.path.join(BASE_DIR, rel))
        # Prevent traversal
        if not file_path.startswith(BASE_DIR):
            self.send_response(403)
            self.end_headers()
            return

        if os.path.isdir(file_path):
            index_path = os.path.join(file_path, 'index.html')
            case_path = os.path.join(file_path, 'case.html')
            if os.path.isfile(case_path):
                file_path = case_path
            elif os.path.isfile(index_path):
                file_path = index_path
            else:
                self.send_response(404)
                self.end_headers()
                return

        if not os.path.isfile(file_path):
            self.send_response(404)
            self.end_headers()
            self.wfile.write(b'404 Not Found')
            return

        ctype, _ = mimetypes.guess_type(file_path)
        if not ctype:
            ctype = 'application/octet-stream'

        try:
            with open(file_path, 'rb') as f:
                content = f.read()
            self.send_response(200)
            self.send_header('Content-Type', ctype)
            self.send_cors_headers()
            self.send_header('Content-Length', str(len(content)))
            self.end_headers()
            self.wfile.write(content)
        except Exception as e:
            self.send_response(500)
            self.end_headers()
            self.wfile.write(str(e).encode('utf-8'))

    def log_message(self, format, *args):
        # Clean logging
        sys.stderr.write(f"[{datetime.now().strftime('%H:%M:%S')}] {args[0]} {args[1]} -> {args[2]}\n")

if __name__ == '__main__':
    server = HTTPServer(('127.0.0.1', PORT), PranaLocalHandler)
    print(f"===========================================================")
    print(f"PRANA CLINICAL LOCAL TEST SERVER RUNNING")
    print(f"Doctor App:          http://localhost:{PORT}/case.html")
    print(f"Patient Intake Form: http://localhost:{PORT}/new_case/case.html")
    print(f"API Endpoint:        http://localhost:{PORT}/api/cases.php")
    print(f"===========================================================")
    sys.stdout.flush()
    try:
        server.serve_forever()
    except KeyboardInterrupt:
        pass

