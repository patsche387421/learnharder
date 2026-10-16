"""Testzentrale im Browser: `scripts/dev.py web` → http://localhost:8090.

Gleiche Daten und Aktionen wie das Terminal-Dashboard (dev.py), dazu Grafiken, Mails im Volltext, der Testverlauf
und die Logs getrennt nach Quelle: App, Anfragen, Datenbank, Tests und Vite.
Nur Standardbibliothek. Die Seite ist dev_web.html, Farben und Schriften kommen aus dem Design-System der App.
Hört nur auf 127.0.0.1. Host und Origin müssen zu dieser Adresse passen, Aktionen brauchen den Header `X-LH-Dev: 1`:
So kann keine fremde Webseite im selben Browser die Datenbank neu aufsetzen.
"""

import json
import re
import threading
import time
import urllib.request
from collections import deque
from http.server import BaseHTTPRequestHandler, ThreadingHTTPServer
from pathlib import Path

import dev

PORT = 8090
PAGE = Path(__file__).with_name('dev_web.html')
STYLES = dev.ROOT / 'frontend' / 'src' / 'styles'
FONTS = dev.ROOT / 'frontend' / 'public' / 'assets' / 'fonts'
STATIC = {
    '/styles/tokens.css': STYLES / 'tokens.css',
    '/styles/fonts.css': STYLES / 'fonts.css',
    **{f'/assets/fonts/{name}': FONTS / name for name in
       ['AtkinsonHyperlegibleNext-Variable.woff2', 'SpaceMono-Regular.woff2', 'SpaceMono-Bold.woff2']},
}
TYPES = {'.html': 'text/html; charset=utf-8', '.css': 'text/css; charset=utf-8', '.woff2': 'font/woff2'}
HISTORY_SIZE = 300  # Messpunkte für die Grafiken, bei etwa 2,5 s Abstand gut 12 Minuten
MAIL_LIMIT = 25
LOG_SOURCES = ('app', 'requests', 'database', 'tests', 'vite')

ACTIONS = {
    'start': ('Starten', dev.action_start),
    'stop': ('Stoppen', dev.action_stop),
    'tests': ('Alle Tests', dev.action_tests),
    'tests-backend': ('Backend-Tests', lambda log: dev.run_tests(['backend'], log)),
    'tests-frontend': ('Frontend-Tests', lambda log: dev.run_tests(['frontend'], log)),
    'config-clear': ('config:clear', dev.action_config_clear),
    'fresh': ('Neu aufsetzen', dev.action_fresh),
    'clear-mails': ('Mails leeren', dev.action_clear_mails),
}


def allowed(method, host, origin, marker, port):
    """Nur Anfragen an diese Adresse (gegen DNS-Rebinding), von dieser Seite, Aktionen nur mit eigenem Header."""
    hosts = {f'localhost:{port}', f'127.0.0.1:{port}'}
    if host not in hosts:
        return False
    if origin is not None and origin not in {f'http://{host_}' for host_ in hosts}:
        return False
    return method != 'POST' or marker == '1'


def sample(state, now):
    """Messpunkt für die Grafiken: CPU in Prozent und RAM in MiB je Dienst, Antwortzeit von /up in ms."""
    cpu, mem = {}, {}
    for service, info in (state['services'] or {}).items():
        if info['name'] in state['stats']:
            cpu_text, mem_text = state['stats'][info['name']]
            cpu[service] = dev.parse_percent(cpu_text)
            mem[service] = round(dev.parse_mib(mem_text), 1)
    up = state['up']
    return {'t': round(now), 'cpu': cpu, 'mem': mem, 'up': round(up[1]) if up and up[1] is not None else None}


class Center(dev.Runner):
    def __init__(self):
        super().__init__(activity_size=100)
        self.state = None
        self.logs = {source: [] for source in LOG_SOURCES}
        self.history = deque(maxlen=HISTORY_SIZE)

    def collect(self):
        while True:
            state = dev.snapshot(mail_limit=MAIL_LIMIT)
            state['problems'] = dev.log_problems(state.pop('log'), limit=8)
            logs = dev.read_logs()
            state['logs'] = dev.log_counts(logs)
            with self.lock:
                self.state = state
                self.logs = logs
                self.history.append(sample(state, time.time()))
            time.sleep(1)

    def payload(self):
        with self.lock:
            return {
                'state': self.state, 'history': list(self.history), 'activity': list(self.activity)[::-1],
                'busy': self.busy, 'tests': dev.load_test_history()[::-1],
            }


def mail_text(message_id):
    with urllib.request.urlopen(f'{dev.MAILPIT_API}/message/{message_id}', timeout=3) as response:
        data = json.load(response)
    return {
        'subject': data.get('Subject', ''), 'from': (data.get('From') or {}).get('Address', ''),
        'to': ', '.join(to['Address'] for to in data.get('To') or []), 'text': data.get('Text', ''),
    }


def make_handler(center):
    class Handler(BaseHTTPRequestHandler):
        server_version = 'learnharder-dev'

        def log_message(self, *args):
            """Keine Zugriffszeilen im Terminal, die Seite fragt jede Sekunde."""

        def respond(self, status, body, content_type='application/json; charset=utf-8'):
            data = body if isinstance(body, bytes) else json.dumps(body, ensure_ascii=False).encode()
            self.send_response(status)
            self.send_header('Content-Type', content_type)
            self.send_header('Content-Length', str(len(data)))
            self.send_header('Cache-Control', 'no-store')
            self.send_header('X-Content-Type-Options', 'nosniff')
            self.end_headers()
            self.wfile.write(data)

        def guard(self):
            if allowed(self.command, self.headers.get('Host'), self.headers.get('Origin'),
                       self.headers.get('X-LH-Dev'), self.server.server_port):
                return True
            self.respond(403, {'error': 'Nicht erlaubt.'})
            return False

        def do_GET(self):
            if not self.guard():
                return
            path = self.path.split('?', 1)[0]
            if path == '/':
                return self.respond(200, PAGE.read_bytes(), TYPES['.html'])
            if path in STATIC:
                return self.respond(200, STATIC[path].read_bytes(), TYPES[STATIC[path].suffix])
            if path == '/api/state':
                return self.respond(200, center.payload())
            if (match := re.fullmatch(r'/api/logs/([a-z]+)', path)) and match.group(1) in LOG_SOURCES:
                with center.lock:
                    return self.respond(200, {'entries': center.logs[match.group(1)][::-1]})
            if match := re.fullmatch(r'/api/mails/([A-Za-z0-9]+)', path):
                try:
                    return self.respond(200, mail_text(match.group(1)))
                except (OSError, ValueError):
                    return self.respond(502, {'error': 'Mailpit nicht erreichbar.'})
            if match := re.fullmatch(r'/api/tests/([a-z0-9-]+)', path):
                if any(run['id'] == match.group(1) for run in dev.load_test_history()):
                    report = dev.STATE_DIR / f'{match.group(1)}.log'
                    return self.respond(200, {'output': dev.ANSI.sub('', report.read_text()) if report.exists() else ''})
            self.respond(404, {'error': 'Nicht gefunden.'})

        def do_POST(self):
            if not self.guard():
                return
            match = re.fullmatch(r'/api/actions/([a-z-]+)', self.path)
            if not match or match.group(1) not in ACTIONS:
                return self.respond(404, {'error': 'Unbekannte Aktion.'})
            if center.busy:
                return self.respond(409, {'error': f'„{center.busy}“ läuft noch.'})
            center.run_action(*ACTIONS[match.group(1)])
            self.respond(202, {'ok': True})

    return Handler


def serve(port=PORT):
    center = Center()
    threading.Thread(target=center.collect, daemon=True).start()
    server = ThreadingHTTPServer(('127.0.0.1', port), make_handler(center))
    url = f'http://localhost:{port}'
    print(f'Testzentrale: {url}  (Strg+C beendet sie, die Dienste laufen weiter)')
    dev.open_url(url)
    try:
        server.serve_forever()
    except KeyboardInterrupt:
        print()
    finally:
        server.server_close()
