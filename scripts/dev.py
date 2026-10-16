#!/usr/bin/env python3
"""Lokales Dashboard für learnharder (WSL2): Dienste starten und stoppen, Zustand live sehen.

Aufruf aus dem Repo: `scripts/dev.py` (Dashboard), `scripts/dev.py start`, `scripts/dev.py stop`.
Nur Standardbibliothek. Liest Docker, Mailpit (API) und das Laravel-Log, ändert keinen Code.
Vite läuft im Hintergrund weiter, wenn das Dashboard endet. PID und Log liegen in ~/.cache/learnharder-dev/.
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
from datetime import datetime
from pathlib import Path

ROOT = Path(__file__).resolve().parent.parent
BACKEND = ROOT / 'backend'
SAIL = BACKEND / 'vendor' / 'bin' / 'sail'
LARAVEL_LOG = BACKEND / 'storage' / 'logs' / 'laravel.log'
STATE_DIR = Path.home() / '.cache' / 'learnharder-dev'
VITE_PID = STATE_DIR / 'vite.pid'
VITE_LOG = STATE_DIR / 'vite.log'
VITE_PORT = 5173

SERVICES = ['laravel.test', 'pgsql', 'redis', 'mailpit']
URLS = {'1': ('App', 'http://localhost:5173'), '2': ('CMS', 'http://localhost/admin'), '3': ('Mailpit', 'http://localhost:8025')}
MAILPIT_API = 'http://localhost:8025/api/v1'
LOG_LEVELS = ('WARNING', 'ERROR', 'CRITICAL', 'ALERT', 'EMERGENCY')
LOG_ENTRY = re.compile(r'^\[\d{4}-\d{2}-\d{2} (\d{2}:\d{2}:\d{2})\] \w+\.(\w+): (.*)')
ANSI = re.compile(r'\x1b\[[0-9;?]*[A-Za-z]')

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
    """Die letzten Warnungen und Fehler aus dem Laravel-Log, je nur die erste Zeile."""
    problems = []
    for line in lines:
        match = LOG_ENTRY.match(line)
        if match and match.group(2) in LOG_LEVELS:
            problems.append(f'{match.group(1)} {match.group(2)}: {match.group(3)}')
    return problems[-limit:]


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


def action_tests(log):
    STATE_DIR.mkdir(parents=True, exist_ok=True)
    for name, cmd, cwd in [
        ('Backend', [str(SAIL), 'artisan', 'test'], BACKEND),
        ('Frontend', ['npm', '--prefix', 'frontend', 'run', 'test'], ROOT),
    ]:
        log(f'{name}-Tests laufen …')
        code, out, err = run(cmd, cwd=cwd)
        report = STATE_DIR / f'tests-{name.lower()}.log'
        report.write_text(out + err)
        log(f'{name}: {test_summary(out + err, code)}' + ('' if code == 0 else f' (Ausgabe: {report})'))


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


def mails():
    """(Anzahl, [(Uhrzeit, An, Betreff, Code)]) der letzten drei Mails, None ohne Mailpit."""
    try:
        with urllib.request.urlopen(f'{MAILPIT_API}/messages?limit=3', timeout=1.5) as response:
            data = json.load(response)
    except (OSError, ValueError):
        return None
    rows = []
    for message in data.get('messages') or []:
        created = datetime.fromisoformat(message['Created'].replace('Z', '+00:00')).astimezone()
        to = message['To'][0]['Address'] if message.get('To') else '?'
        rows.append((created.strftime('%H:%M'), to, message.get('Subject', ''), extract_code(message.get('Snippet'))))
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


def snapshot():
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
    return {
        'services': services, 'stats': stats,
        'vite': port_open(VITE_PORT), 'vite_own': vite_pid() is not None,
        'up': health(), 'mails': mails(), 'log': tail(LARAVEL_LOG),
        'branch': branch.strip() or '?', 'changes': len(changes.splitlines()),
    }


# --- Oberfläche ---

GREEN, RED, YELLOW, CYAN = 1, 2, 3, 4
SPINNER = '|/-\\'


class Dashboard:
    def __init__(self):
        self.lock = threading.Lock()
        self.state = None
        self.activity = deque(maxlen=6)
        self.busy = None
        self.confirm = None
        self.show_log = False

    def log(self, message):
        with self.lock:
            self.activity.append(f'{datetime.now():%H:%M:%S}  {message}')

    def collect(self):
        while True:
            state = snapshot()
            with self.lock:
                self.state = state
            time.sleep(2)

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
            for time_, to, subject, code in (mail[1] if mail else []):
                put(f'{time_}  {to[:24]:<24}  {subject}', newline=False)
                if code:
                    put(f' Code {code} ', curses.color_pair(YELLOW) | curses.A_BOLD | curses.A_REVERSE, max(0, width - 14))
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
    parser.add_argument('command', nargs='?', choices=['start', 'stop'], help='alles starten oder stoppen, ohne Dashboard')
    args = parser.parse_args()
    if not SAIL.exists():
        raise SystemExit(f'{SAIL} fehlt. Einmalig Composer installieren, siehe backend/CLAUDE.md.')
    if args.command:
        (action_start if args.command == 'start' else action_stop)(print)
        return
    curses.wrapper(dashboard)
    print('Dienste laufen weiter. Stoppen: scripts/dev.py stop')


if __name__ == '__main__':
    main()
