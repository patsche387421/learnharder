#!/usr/bin/env python3
"""Lokales Dashboard für learnharder (WSL2): Dienste starten und stoppen, Zustand live sehen.

Aufruf aus dem Repo: `scripts/dev.py` (Dashboard im Terminal), `scripts/dev.py web` (Testzentrale im Browser, dev_web.py),
`scripts/dev.py start`, `scripts/dev.py stop`.
Nur Standardbibliothek. Liest Docker, Mailpit (API) und das Laravel-Log, ändert keinen Code.
Vite läuft im Hintergrund weiter, wenn das Dashboard endet. PID, Logs und Testverlauf liegen in ~/.cache/learnharder-dev/.
"""

import argparse
import curses
import json
import os
import re
import shutil
import signal
import socket
import subprocess
import threading
import time
import urllib.error
import urllib.request
from collections import deque
from datetime import datetime, timezone
from pathlib import Path

ROOT = Path(__file__).resolve().parent.parent
BACKEND = ROOT / 'backend'
SAIL = BACKEND / 'vendor' / 'bin' / 'sail'
LARAVEL_LOG = BACKEND / 'storage' / 'logs' / 'laravel.log'
TESTING_LOG = BACKEND / 'storage' / 'logs' / 'testing.log'
STATE_DIR = Path.home() / '.cache' / 'learnharder-dev'
VITE_PID = STATE_DIR / 'vite.pid'
VITE_LOG = STATE_DIR / 'vite.log'
TEST_HISTORY = STATE_DIR / 'test-history.json'
TEST_HISTORY_LIMIT = 50
VITE_PORT = 5173

SERVICES = ['laravel.test', 'pgsql', 'redis', 'mailpit']
URLS = {'1': ('App', 'http://localhost:5173'), '2': ('CMS', 'http://localhost/admin'), '3': ('Mailpit', 'http://localhost:8025')}
MAILPIT_API = 'http://localhost:8025/api/v1'
LOG_LEVELS = ('WARNING', 'ERROR', 'CRITICAL', 'ALERT', 'EMERGENCY')
# Laravel und der Container schreiben UTC (config/app.php), die Oberfläche zeigt Ortszeit
LOG_ENTRY = re.compile(r'^\[(\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2})\] (\w+)\.(\w+): (.*)')
# Postgres mit log_line_prefix aus backend/compose.yaml: Zeit, Prozess, Benutzer@Datenbank (fehlt bei Serverprozessen)
POSTGRES_LINE = re.compile(r'^(\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2})\.\d+ \w+ \[\d+\] (?:\S+@(\S+) )?([A-Z]+):\s+(.*)')
POSTGRES_DETAIL = ('DETAIL', 'STATEMENT', 'HINT', 'CONTEXT', 'QUERY', 'LOCATION')
POSTGRES_PROBLEMS = ('WARNING', 'ERROR', 'FATAL', 'PANIC', 'SLOW')
# php artisan serve im Container: `  2026-10-16 18:40:20 /api/v1/me ....... ~ 0.12ms`. Die Dauer dort taugt nichts
# (gemessen 50–120 ms, im Log 0,1 ms oder 500 ms), deshalb nur Zeit und Pfad. Echte Antwortzeit: health() von außen.
REQUEST_LINE = re.compile(r'^\s*(\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}) (\S+) \.*\s*~ [\d.]+\s*m?s\s*$')
LOG_KEEP = 300
ANSI = re.compile(r'\x1b\[[0-9;?]*[A-Za-z]')
MIB_PER_UNIT = {'b': 1 / 1048576, 'kib': 1 / 1024, 'kb': 1 / 1024, 'mib': 1, 'mb': 1, 'gib': 1024, 'gb': 1024}
TEST_KINDS = ('passed', 'failed', 'skipped', 'todo', 'incomplete', 'risky')

_vite_process = None


# --- Auswerten (ohne Seiteneffekte, getestet in test_dev.py) ---

def parse_compose_ps(output):
    """`docker compose ps --format json`: je nach Version eine Liste oder eine Zeile pro Dienst."""
    output = output.strip()
    if not output:
        return {}
    rows = json.loads(output) if output.startswith('[') else [json.loads(line) for line in output.splitlines() if line.strip()]
    return {row['Service']: {'name': row['Name'], 'state': row['State']} for row in rows}


def parse_stats(output):
    """`docker stats --format '{{json .}}'` → {Container: (CPU, RAM)}."""
    stats = {}
    for line in output.splitlines():
        if line.strip():
            row = json.loads(line)
            stats[row['Name']] = (row['CPUPerc'], row['MemUsage'].split(' / ')[0])
    return stats


def extract_code(text):
    """Sechsstelliger Code aus einer Mail (E-025). Längere Zahlen wie Schulnummern zählen nicht."""
    match = re.search(r'(?<!\d)\d{6}(?!\d)', text or '')
    return match.group(0) if match else None


def log_problems(lines, limit=4):
    """Die letzten Warnungen und Fehler der App aus dem Laravel-Log, je nur die erste Zeile. Einträge aus Tests zählen nicht."""
    return [f'{entry["time"]} {entry["level"]}: {entry["text"]}' for entry in parse_laravel_log(lines)
            if entry['env'] != 'testing' and entry['level'] in LOG_LEVELS][-limit:]


def local_time(text):
    """UTC-Zeit `2026-10-16 18:40:20` → (ISO mit Zeitzone, `20:40:20`) in Ortszeit."""
    moment = datetime.strptime(text, '%Y-%m-%d %H:%M:%S').replace(tzinfo=timezone.utc).astimezone()
    return moment.isoformat(timespec='seconds'), moment.strftime('%H:%M:%S')


def parse_laravel_log(lines):
    """Einträge aus einem Laravel-Log. Folgezeilen (Stacktrace) landen in `detail`."""
    entries = []
    for line in lines:
        if match := LOG_ENTRY.match(line):
            stamp, time_ = local_time(match.group(1))
            entries.append({'stamp': stamp, 'time': time_, 'env': match.group(2), 'level': match.group(3),
                            'text': match.group(4), 'detail': ''})
        elif entries and line.strip() and len(entries[-1]['detail']) < 4000:
            entries[-1]['detail'] += line + '\n'
    return entries


def parse_postgres_log(lines):
    """Einträge aus dem Postgres-Log. DETAIL, STATEMENT und Co. gehören zur Zeile davor, langsame Abfragen heißen SLOW."""
    entries = []
    for line in lines:
        match = POSTGRES_LINE.match(line)
        if not match:
            continue
        stamp, time_ = local_time(match.group(1))
        database, level, text = match.group(2), match.group(3), match.group(4)
        if level in POSTGRES_DETAIL and entries:
            entries[-1]['detail'] += f'{level}: {text}\n'
            continue
        if level == 'LOG' and (slow := re.match(r'duration: ([\d.]+) ms\s+(?:statement|execute [^:]*): (.*)', text)):
            level, text = 'SLOW', f'{float(slow.group(1)):.0f} ms: {slow.group(2)}'
        entries.append({'stamp': stamp, 'time': time_, 'db': database, 'level': level, 'text': text, 'detail': ''})
    return entries


def parse_requests(lines):
    """Anfragen an Laravel (Zeit und Pfad), ohne /up: Das fragt die Testzentrale selbst ständig ab."""
    requests = []
    for line in lines:
        match = REQUEST_LINE.match(line)
        if match and match.group(2) != '/up':
            stamp, time_ = local_time(match.group(1))
            requests.append({'stamp': stamp, 'time': time_, 'path': match.group(2)})
    return requests


def classify_logs(laravel, testing, postgres, requests, vite):
    """Log-Zeilen nach Quelle: App, Anfragen, Datenbank (Entwicklung), Tests (Laravel und Datenbank testing), Vite."""
    laravel_entries = parse_laravel_log(laravel)
    postgres_entries = parse_postgres_log(postgres)
    tests = ([{**entry, 'origin': 'Laravel'} for entry in laravel_entries if entry['env'] == 'testing']
             + [{**entry, 'origin': 'Laravel'} for entry in parse_laravel_log(testing)]
             + [{**entry, 'origin': 'Datenbank'} for entry in postgres_entries if entry['db'] == 'testing'])
    sources = {
        'app': [entry for entry in laravel_entries if entry['env'] != 'testing'],
        'requests': parse_requests(requests),
        'database': [entry for entry in postgres_entries if entry['db'] != 'testing'],
        'tests': sorted(tests, key=lambda entry: entry['stamp']),
        'vite': [{'text': ANSI.sub('', line)} for line in vite if line.strip()],
    }
    return {name: entries[-LOG_KEEP:] for name, entries in sources.items()}


def log_counts(logs):
    """Je Quelle: Anzahl und Probleme. Probleme zählen nur bei App und Datenbank, Fehler aus Tests sind gewollt."""
    problems = {
        'app': lambda entry: entry['level'] in LOG_LEVELS,
        'database': lambda entry: entry['level'] in POSTGRES_PROBLEMS,
    }
    return {name: {'total': len(entries), 'problems': sum(1 for entry in entries if name in problems and problems[name](entry))}
            for name, entries in logs.items()}


def test_summary(output, returncode):
    """Kurzfassung eines Testlaufs: Laravel (Text oder JSON) oder Vitest. Maßgeblich ist der Rückgabewert."""
    detail = ''
    for line in reversed(ANSI.sub('', output).splitlines()):
        line = line.strip()
        if line.startswith('{') and '"tests"' in line:
            try:
                data = json.loads(line)
                detail = f"{data.get('passed', 0)} von {data['tests']} bestanden"
                break
            except (ValueError, KeyError):
                continue
        if re.match(r'Tests:?\s', line):
            detail = re.sub(r'\s+', ' ', line)
            break
    return ('grün' if returncode == 0 else 'ROT') + (f' · {detail}' if detail else '')


def test_counts(output):
    """{'passed', 'failed', 'total'} aus Laravel (Text oder JSON) oder Vitest, None wenn nichts erkennbar."""
    for line in reversed(ANSI.sub('', output).splitlines()):
        line = line.strip()
        if line.startswith('{') and '"tests"' in line:
            try:
                data = json.loads(line)
                return {'passed': data.get('passed', 0), 'failed': data.get('failed', 0), 'total': data['tests']}
            except (ValueError, KeyError):
                continue
        if re.match(r'Tests:?\s', line):
            found = {kind: int(number) for number, kind in re.findall(r'(\d+) (\w+)', line) if kind in TEST_KINDS}
            return {'passed': found.get('passed', 0), 'failed': found.get('failed', 0), 'total': sum(found.values())}
    return None


def failed_tests(output, limit=15):
    """Namen fehlgeschlagener Tests: Laravel `FAILED  Klasse > Test`, Vitest `FAIL  Datei > Test`, JSON `failures`."""
    names = []
    for line in ANSI.sub('', output).splitlines():
        line = line.strip()
        if line.startswith('{') and '"failures"' in line:
            try:
                names += [failure['test'] for failure in json.loads(line)['failures']]
            except (ValueError, KeyError, TypeError):
                pass
        elif match := re.match(r'(?:FAILED|FAIL|×|⨯)\s+(.+)', line):
            names.append(re.sub(r'\s{2,}.*$', '', match.group(1)))
    return list(dict.fromkeys(names))[:limit]


def parse_percent(text):
    try:
        return float(text.rstrip('%'))
    except (ValueError, AttributeError):
        return 0.0


def parse_mib(text):
    """`219.5MiB`, `1.2GiB`, `512kB` → MiB."""
    match = re.match(r'([\d.]+)\s*([A-Za-z]+)', text or '')
    if not match or match.group(2).lower() not in MIB_PER_UNIT:
        return 0.0
    return float(match.group(1)) * MIB_PER_UNIT[match.group(2).lower()]


# --- Befehle und Dienste ---

def run(cmd, cwd=ROOT, timeout=None):
    """Befehl ausführen → (Rückgabewert, stdout, stderr). Fehlt das Programm, kommt 127."""
    try:
        result = subprocess.run(cmd, cwd=cwd, capture_output=True, text=True, timeout=timeout, stdin=subprocess.DEVNULL)
        return result.returncode, result.stdout, result.stderr
    except FileNotFoundError as error:
        return 127, '', str(error)
    except subprocess.TimeoutExpired:
        return 124, '', 'Zeitüberschreitung'


def last_line(text):
    lines = [line for line in ANSI.sub('', text).splitlines() if line.strip()]
    return lines[-1].strip() if lines else 'ohne Ausgabe'


def port_open(port):
    try:
        with socket.create_connection(('localhost', port), timeout=0.3):
            return True
    except OSError:
        return False


def vite_pid():
    """PID des Vite-Servers, den dieses Skript gestartet hat, auch aus einem früheren Aufruf."""
    if _vite_process is not None and _vite_process.poll() is not None:
        return None
    try:
        pid = int(VITE_PID.read_text())
        os.kill(pid, 0)
        return pid
    except (OSError, ValueError):
        return None


def start_vite():
    global _vite_process
    if port_open(VITE_PORT):
        return 'Vite läuft schon.'
    STATE_DIR.mkdir(parents=True, exist_ok=True)
    with open(VITE_LOG, 'w') as log:
        _vite_process = subprocess.Popen(
            ['npm', '--prefix', 'frontend', 'run', 'dev'], cwd=ROOT,
            stdout=log, stderr=subprocess.STDOUT, stdin=subprocess.DEVNULL, start_new_session=True,
        )
    VITE_PID.write_text(str(_vite_process.pid))
    return f'Vite gestartet (Log: {VITE_LOG}).'


def stop_vite():
    pid = vite_pid()
    if pid is None:
        return 'Vite läuft in einem anderen Terminal, dort mit Strg+C beenden.' if port_open(VITE_PORT) else 'Vite läuft nicht.'
    os.killpg(pid, signal.SIGTERM)
    if _vite_process is not None:
        _vite_process.wait(timeout=10)
    VITE_PID.unlink(missing_ok=True)
    return 'Vite gestoppt.'


def sail(*args):
    return run([str(SAIL), *args], cwd=BACKEND)


def open_url(url):
    opener = shutil.which('explorer.exe') or shutil.which('xdg-open')
    if opener is None:
        return f'Kein Browser gefunden, bitte {url} selbst öffnen.'
    subprocess.Popen([opener, url], stdout=subprocess.DEVNULL, stderr=subprocess.DEVNULL)
    return f'Geöffnet: {url}'


# --- Aktionen (laufen im Hintergrund, melden über log) ---

def action_start(log):
    log('Docker-Dienste starten (sail up -d) …')
    code, out, err = sail('up', '-d')
    log('Docker-Dienste laufen.' if code == 0 else f'sail up fehlgeschlagen: {last_line(err or out)}')
    log(start_vite())


def action_stop(log):
    log(stop_vite())
    log('Docker-Dienste stoppen (sail stop) …')
    code, out, err = sail('stop')
    log('Docker-Dienste gestoppt, Daten bleiben.' if code == 0 else f'sail stop fehlgeschlagen: {last_line(err or out)}')


TEST_SUITES = {
    'backend': ('Backend', [str(SAIL), 'artisan', 'test'], BACKEND),
    'frontend': ('Frontend', ['npm', '--prefix', 'frontend', 'run', 'test'], ROOT),
}
_history_lock = threading.Lock()


def load_test_history():
    try:
        return json.loads(TEST_HISTORY.read_text())
    except (OSError, ValueError):
        return []


def save_test_run(entry, output):
    """Lauf mit Ausgabe speichern. Ältere Läufe über TEST_HISTORY_LIMIT fallen samt Ausgabe weg."""
    STATE_DIR.mkdir(parents=True, exist_ok=True)
    (STATE_DIR / f'{entry["id"]}.log').write_text(output)
    with _history_lock:
        history = load_test_history() + [entry]
        for old in history[:-TEST_HISTORY_LIMIT]:
            (STATE_DIR / f'{old["id"]}.log').unlink(missing_ok=True)
        TEST_HISTORY.write_text(json.dumps(history[-TEST_HISTORY_LIMIT:], ensure_ascii=False, indent=1))


def run_tests(suites, log):
    _, branch, _ = run(['git', 'branch', '--show-current'], timeout=5)
    _, commit, _ = run(['git', 'rev-parse', '--short', 'HEAD'], timeout=5)
    for suite in suites:
        name, cmd, cwd = TEST_SUITES[suite]
        log(f'{name}-Tests laufen …')
        started, stamp = time.monotonic(), datetime.now()
        code, out, err = run(cmd, cwd=cwd)
        save_test_run({
            'id': f'tests-{suite}-{stamp:%Y%m%d-%H%M%S}', 'suite': suite, 'time': stamp.isoformat(timespec='seconds'),
            'ok': code == 0, 'counts': test_counts(out + err), 'failures': failed_tests(out + err),
            'duration': round(time.monotonic() - started, 1), 'branch': branch.strip(), 'commit': commit.strip(),
        }, out + err)
        log(f'{name}: {test_summary(out + err, code)}')


def action_tests(log):
    run_tests(['backend', 'frontend'], log)


def action_config_clear(log):
    code, out, err = sail('artisan', 'config:clear')
    log('Konfiguration neu geladen.' if code == 0 else f'config:clear fehlgeschlagen: {last_line(err or out)}')


def action_fresh(log):
    """Wie in backend/CLAUDE.md: Migrationen mit Seeder, Import aus V1, dann die Demo-Klasse."""
    for args in [('migrate:fresh', '--seed'), ('learnharder:import-legacy-content',), ('db:seed', '--class=DemoSchoolSeeder')]:
        log(f'artisan {" ".join(args)} …')
        code, out, err = sail('artisan', *args)
        if code != 0:
            log(f'Abgebrochen: {last_line(err or out)}')
            return
    log('Datenbank neu aufgesetzt. Alle sind abgemeldet.')


def action_clear_mails(log):
    try:
        urllib.request.urlopen(urllib.request.Request(f'{MAILPIT_API}/messages', method='DELETE'), timeout=3).close()
        log('Mailpit geleert.')
    except OSError as error:
        log(f'Mailpit nicht erreichbar: {error}')


ACTIONS = {
    's': ('Starten', action_start, None),
    'x': ('Stoppen', action_stop, None),
    't': ('Tests', action_tests, None),
    'c': ('config:clear', action_config_clear, None),
    'f': ('Neu aufsetzen', action_fresh, 'Datenbank leeren und neu befüllen (migrate:fresh, Import, Demo-Klasse)?'),
    'd': ('Mails leeren', action_clear_mails, None),
}


# --- Live-Zustand ---

def tail(path, max_bytes=65536):
    try:
        with open(path, 'rb') as file:
            size = file.seek(0, os.SEEK_END)
            file.seek(max(0, size - max_bytes))
            lines = file.read().decode('utf-8', 'replace').splitlines()
            return lines[1:] if size > max_bytes else lines
    except OSError:
        return []


def mails(limit=3):
    """(Anzahl, [Mail]) der neuesten Mails mit Uhrzeit, Empfänger, Betreff und Code. None ohne Mailpit."""
    try:
        with urllib.request.urlopen(f'{MAILPIT_API}/messages?limit={limit}', timeout=1.5) as response:
            data = json.load(response)
    except (OSError, ValueError):
        return None
    rows = []
    for message in data.get('messages') or []:
        created = datetime.fromisoformat(message['Created'].replace('Z', '+00:00')).astimezone()
        rows.append({
            'id': message['ID'], 'time': created.strftime('%H:%M:%S'), 'to': message['To'][0]['Address'] if message.get('To') else '?',
            'subject': message.get('Subject', ''), 'code': extract_code(message.get('Snippet')), 'snippet': message.get('Snippet', ''),
        })
    return data.get('messages_count', data.get('total', 0)), rows


def health():
    """(HTTP-Status, Millisekunden) von Laravels /up, None ohne Antwort."""
    start = time.monotonic()
    try:
        with urllib.request.urlopen('http://localhost/up', timeout=2) as response:
            return response.status, (time.monotonic() - start) * 1000
    except urllib.error.HTTPError as error:
        return error.code, None
    except OSError:
        return None


def compose_logs(service, lines=400):
    """Container-Log eines Dienstes. Nur stdout, auf stderr warnt docker compose über fehlende Variablen."""
    code, out, _ = run(['docker', 'compose', 'logs', '--no-log-prefix', '--no-color', '--tail', str(lines), service], cwd=BACKEND, timeout=10)
    return out.splitlines() if code == 0 else []


def read_logs():
    big = 262144  # Stacktraces sind lang
    return classify_logs(tail(LARAVEL_LOG, big), tail(TESTING_LOG, big), compose_logs('pgsql'), compose_logs('laravel.test'), tail(VITE_LOG))


def snapshot(mail_limit=3):
    code, out, _ = run(['docker', 'compose', 'ps', '--all', '--format', 'json'], cwd=BACKEND, timeout=10)
    try:
        services = parse_compose_ps(out) if code == 0 else None
    except (ValueError, KeyError):
        services = None
    code, out, _ = run(['docker', 'stats', '--no-stream', '--format', '{{json .}}'], timeout=15)
    try:
        stats = parse_stats(out) if code == 0 else {}
    except (ValueError, KeyError):
        stats = {}
    _, branch, _ = run(['git', 'branch', '--show-current'], timeout=5)
    _, changes, _ = run(['git', 'status', '--porcelain'], timeout=5)
    _, commit, _ = run(['git', 'rev-parse', '--short', 'HEAD'], timeout=5)
    return {
        'services': services, 'stats': stats,
        'vite': port_open(VITE_PORT), 'vite_own': vite_pid() is not None,
        'up': health(), 'mails': mails(mail_limit), 'log': tail(LARAVEL_LOG),
        'branch': branch.strip() or '?', 'commit': commit.strip(), 'changes': len(changes.splitlines()),
    }


# --- Oberfläche ---

GREEN, RED, YELLOW, CYAN = 1, 2, 3, 4
SPINNER = '|/-\\'


class Runner:
    """Aktivität und genau eine laufende Aktion. Teilen sich Terminal-Dashboard und Testzentrale (dev_web.py)."""

    def __init__(self, activity_size):
        self.lock = threading.Lock()
        self.activity = deque(maxlen=activity_size)
        self.busy = None

    def log(self, message):
        with self.lock:
            self.activity.append(f'{datetime.now():%H:%M:%S}  {message}')

    def run_action(self, name, action):
        if self.busy:
            self.log(f'Warte, bis „{self.busy}“ fertig ist.')
            return

        def work():
            try:
                action(self.log)
            except Exception as error:  # Dashboard soll weiterlaufen, Fehler erscheinen in der Aktivität
                self.log(f'{name}: Fehler {error}')
            finally:
                self.busy = None

        self.busy = name
        threading.Thread(target=work, daemon=True).start()


class Dashboard(Runner):
    def __init__(self):
        super().__init__(activity_size=6)
        self.state = None
        self.confirm = None
        self.show_log = False

    def collect(self):
        while True:
            state = snapshot()
            with self.lock:
                self.state = state
            time.sleep(2)

    def handle(self, key):
        """Taste verarbeiten. False beendet das Dashboard."""
        char = chr(key).lower() if 0 <= key < 256 else ''
        if self.confirm:
            _, name, action = self.confirm
            self.confirm = None
            if char not in ('j', 'y'):
                self.log('Abgebrochen.')
            elif action is None:
                return False
            else:
                self.run_action(name, action)
        elif char == 'q':
            if not self.busy:
                return False
            self.confirm = (f'„{self.busy}“ läuft noch. Trotzdem beenden?', 'Beenden', None)
        elif char == 'l':
            self.show_log = not self.show_log
        elif char in URLS:
            self.log(open_url(URLS[char][1]))
        elif char in ACTIONS:
            name, action, question = ACTIONS[char]
            if question:
                self.confirm = (question, name, action)
            else:
                self.run_action(name, action)
        return True

    def draw(self, screen, tick):
        screen.erase()
        height, width = screen.getmaxyx()
        with self.lock:
            state, activity = self.state, list(self.activity)
        row = 0

        def put(text, attr=0, x=0, newline=True):
            nonlocal row
            if row < height - 2 and x < width - 1:
                try:
                    screen.addnstr(row, x, text, width - 1 - x, attr)
                except curses.error:
                    pass
            if newline:
                row += 1

        def section(title):
            put(f'── {title} ' + '─' * max(0, width - len(title) - 5), curses.color_pair(CYAN))

        if state is None:
            put('learnharder · lokal', curses.A_BOLD)
            put('Lese Zustand …')
        else:
            put('learnharder · lokal', curses.A_BOLD, newline=False)
            git = f'{state["branch"]} · ' + (f'{state["changes"]} Änderungen' if state['changes'] else 'sauber')
            put(git, curses.A_DIM, max(0, width - len(git) - 2))

            section('DIENSTE')
            if state['services'] is None:
                put('Docker nicht erreichbar. Läuft Docker Desktop?', curses.color_pair(RED))
            else:
                for service in SERVICES + sorted(set(state['services']) - set(SERVICES)):
                    info = state['services'].get(service)
                    running = info is not None and info['state'] == 'running'
                    label = '● läuft ' if running else f'○ {info["state"] if info else "fehlt"}'
                    cpu, mem = state['stats'].get(info['name'], ('', '')) if info else ('', '')
                    put(f'{service:<14}', newline=False)
                    put(f'{label:<12}', curses.color_pair(GREEN if running else RED), 15, newline=False)
                    put(f'{cpu:>7}  {mem:>10}', curses.A_DIM, 28)
            vite = '● läuft ' if state['vite'] else '○ aus'
            put(f'{"vite (App)":<14}', newline=False)
            put(f'{vite:<12}', curses.color_pair(GREEN if state['vite'] else RED), 15, newline=False)
            put('localhost:5173' + ('' if state['vite_own'] or not state['vite'] else ' (anderes Terminal)'), curses.A_DIM, 28)
            up = state['up']
            put(f'{"/up":<14}', newline=False)
            if up is None:
                put('keine Antwort', curses.color_pair(RED), 15)
            else:
                put(f'{up[0]}' + (f' in {up[1]:.0f} ms' if up[1] is not None else ''), curses.color_pair(GREEN if up[0] == 200 else YELLOW), 15)

            mail = state['mails']
            section('MAILPIT' + (f' ({mail[0]} Mails)' if mail else ''))
            if mail is None:
                put('Mailpit nicht erreichbar.', curses.color_pair(RED))
            elif not mail[1]:
                put('Keine Mails.', curses.A_DIM)
            for message in (mail[1] if mail else []):
                put(f'{message["time"][:5]}  {message["to"][:24]:<24}  {message["subject"]}', newline=False)
                if message['code']:
                    put(f' Code {message["code"]} ', curses.color_pair(YELLOW) | curses.A_BOLD | curses.A_REVERSE, max(0, width - 14))
                else:
                    put('')

            section('LARAVEL-LOG' + (' (alles, [l] zurück)' if self.show_log else ' (Warnungen und Fehler)'))
            if self.show_log:
                room = max(1, height - row - 2 - min(len(activity), 4) - 1)
                for line in state['log'][-room:]:
                    put(line, curses.A_DIM)
            else:
                problems = log_problems(state['log'])
                if not problems:
                    put('Keine Warnungen oder Fehler.', curses.color_pair(GREEN))
                for problem in problems:
                    put(problem, curses.color_pair(RED if 'ERROR' in problem or 'CRITICAL' in problem else YELLOW))

        section('AKTIVITÄT')
        if self.busy:
            put(f'{SPINNER[tick % len(SPINNER)]} {self.busy} …', curses.color_pair(YELLOW))
        for line in activity[-4:]:
            put(line, curses.color_pair(RED) if 'ROT' in line or 'fehlgeschlagen' in line or 'Fehler' in line else 0)

        footer = curses.A_REVERSE
        if self.confirm:
            lines = [f' {self.confirm[0]} [j/n] ', '']
            footer |= curses.color_pair(YELLOW)
        else:
            lines = [' [s] Start  [x] Stop  [t] Tests  [c] config:clear  [f] Neu aufsetzen  [d] Mails leeren',
                     ' [1] App  [2] CMS  [3] Mailpit  [l] Log  [q] Beenden (Dienste laufen weiter)']
        for offset, text in enumerate(lines):
            try:
                screen.addnstr(height - 2 + offset, 0, text.ljust(width - 1), width - 1, footer if text else 0)
            except curses.error:
                pass
        screen.refresh()


def dashboard(screen):
    curses.curs_set(0)
    curses.use_default_colors()
    for pair, color in [(GREEN, curses.COLOR_GREEN), (RED, curses.COLOR_RED), (YELLOW, curses.COLOR_YELLOW), (CYAN, curses.COLOR_CYAN)]:
        curses.init_pair(pair, color, -1)
    screen.timeout(250)
    app = Dashboard()
    threading.Thread(target=app.collect, daemon=True).start()
    app.log('Bereit. [s] startet alles.')
    tick = 0
    while True:
        app.draw(screen, tick)
        tick += 1
        key = screen.getch()
        if key not in (-1, curses.KEY_RESIZE) and not app.handle(key):
            break


def main():
    parser = argparse.ArgumentParser(description='Lokales Dashboard für learnharder. Ohne Befehl startet das Dashboard.')
    parser.add_argument('command', nargs='?', choices=['start', 'stop', 'web'], help='alles starten oder stoppen, oder die Testzentrale im Browser')
    args = parser.parse_args()
    if not SAIL.exists():
        raise SystemExit(f'{SAIL} fehlt. Einmalig Composer installieren, siehe backend/CLAUDE.md.')
    if args.command == 'web':
        import dev_web
        dev_web.serve()
        return
    if args.command:
        (action_start if args.command == 'start' else action_stop)(print)
        return
    curses.wrapper(dashboard)
    print('Dienste laufen weiter. Stoppen: scripts/dev.py stop')


if __name__ == '__main__':
    main()
