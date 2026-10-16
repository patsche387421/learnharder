"""Tests für die Auswertung im Dashboard: `python3 -m unittest discover -s scripts` (aus dem Repo)."""

import json
import tempfile
import unittest
from pathlib import Path
from unittest import mock

import dev


class ParseTest(unittest.TestCase):
    def test_compose_ps_als_zeilen_und_als_liste(self):
        rows = [
            {'Service': 'laravel.test', 'Name': 'backend-laravel.test-1', 'State': 'running'},
            {'Service': 'mailpit', 'Name': 'backend-mailpit-1', 'State': 'exited'},
        ]
        expected = {
            'laravel.test': {'name': 'backend-laravel.test-1', 'state': 'running'},
            'mailpit': {'name': 'backend-mailpit-1', 'state': 'exited'},
        }
        self.assertEqual(expected, dev.parse_compose_ps('\n'.join(json.dumps(row) for row in rows)))
        self.assertEqual(expected, dev.parse_compose_ps(json.dumps(rows)))
        self.assertEqual({}, dev.parse_compose_ps(''))

    def test_stats_nur_cpu_und_belegter_speicher(self):
        line = json.dumps({'Name': 'backend-pgsql-1', 'CPUPerc': '0.30%', 'MemUsage': '48.1MiB / 4.804GiB'})
        self.assertEqual({'backend-pgsql-1': ('0.30%', '48.1MiB')}, dev.parse_stats(line + '\n'))

    def test_code_aus_der_mail_aber_keine_schulnummer(self):
        mail = 'Hallo! Dein Code für die Registrierung bei learnharder: 981503 Er gilt 10 Minuten.'
        self.assertEqual('981503', dev.extract_code(mail))
        self.assertIsNone(dev.extract_code('Konto für 20269999@htl-md.schule'))
        self.assertIsNone(dev.extract_code(None))

    def test_log_nur_warnungen_und_fehler_der_app_mit_erster_zeile(self):
        lines = [
            '[2026-10-16 16:40:01] local.INFO: Mail verschickt',
            '[2026-10-16 16:41:02] local.ERROR: SQLSTATE[08006] Verbindung fehlgeschlagen {"exception":"[object]"}',
            '#0 /var/www/html/vendor/laravel/framework/src/Database/Connection.php(1)',
            '[2026-10-16 16:41:30] testing.ERROR: gewollter Fehler aus einem Test',
            '[2026-10-16 16:42:03] local.WARNING: Langsame Anfrage',
        ]
        with mock.patch.object(dev, 'local_time', lambda text: (text, text[11:])):
            self.assertEqual([
                '16:41:02 ERROR: SQLSTATE[08006] Verbindung fehlgeschlagen {"exception":"[object]"}',
                '16:42:03 WARNING: Langsame Anfrage',
            ], dev.log_problems(lines))

    def test_testergebnis_aus_laravel_vitest_und_json(self):
        laravel = '  PASS  Tests\\Feature\\LegalTest\n\n  Tests:    262 passed (2075 assertions)\n  Duration: 61.42s\n'
        self.assertEqual('grün · Tests: 262 passed (2075 assertions)', dev.test_summary(laravel, 0))

        vitest = ' Test Files  28 passed (28)\n      Tests  \x1b[32m124 passed\x1b[39m (124)\n   Start at  16:40:00\n'
        self.assertEqual('grün · Tests 124 passed (124)', dev.test_summary(vitest, 0))

        agent = '{"tool":"phpunit","result":"failed","tests":262,"passed":261,"failed":1}'
        self.assertEqual('ROT · 261 von 262 bestanden', dev.test_summary(agent, 1))

        self.assertEqual('ROT', dev.test_summary('Fatal error', 255))

    def test_testzahlen_aus_laravel_vitest_und_json(self):
        laravel = '  Tests:    1 failed, 2 skipped, 259 passed (2075 assertions)\n  Duration: 61.42s\n'
        self.assertEqual({'passed': 259, 'failed': 1, 'total': 262}, dev.test_counts(laravel))
        vitest = '      Tests  \x1b[31m1 failed\x1b[39m | \x1b[32m123 passed\x1b[39m (124)\n'
        self.assertEqual({'passed': 123, 'failed': 1, 'total': 124}, dev.test_counts(vitest))
        agent = '{"tool":"phpunit","result":"passed","tests":262,"passed":262}'
        self.assertEqual({'passed': 262, 'failed': 0, 'total': 262}, dev.test_counts(agent))
        self.assertIsNone(dev.test_counts('Fatal error'))

    def test_namen_fehlgeschlagener_tests(self):
        output = (
            '  FAILED  Tests\\Feature\\Api\\LegalTest > alle texte ohne login   ErrorException\n'
            ' FAIL  src/views/LoginView.test.js > LoginView > zeigt den Hinweis\n'
            ' FAIL  src/views/LoginView.test.js > LoginView > zeigt den Hinweis\n'
            '{"failures":[{"test":"Tests\\\\Feature\\\\X::test_y"}]}\n'
        )
        self.assertEqual([
            'Tests\\Feature\\Api\\LegalTest > alle texte ohne login',
            'src/views/LoginView.test.js > LoginView > zeigt den Hinweis',
            'Tests\\Feature\\X::test_y',
        ], dev.failed_tests(output))

    def test_messwerte_in_prozent_und_mib(self):
        self.assertEqual(1.25, dev.parse_percent('1.25%'))
        self.assertEqual(0.0, dev.parse_percent('--'))
        self.assertEqual(219.5, dev.parse_mib('219.5MiB'))
        self.assertEqual(1228.8, dev.parse_mib('1.2GiB'))
        self.assertEqual(0.5, dev.parse_mib('512KiB'))
        self.assertEqual(0.0, dev.parse_mib('?'))


class LogSourcesTest(unittest.TestCase):
    """Log-Zeilen wie sie Laravel, Postgres (log_line_prefix aus backend/compose.yaml) und php artisan serve schreiben."""

    def setUp(self):
        # Ortszeit unabhängig vom Rechner: UTC-Text bleibt, wie er ist
        patcher = mock.patch.object(dev, 'local_time', lambda text: (text, text[11:]))
        patcher.start()
        self.addCleanup(patcher.stop)

    def test_laravel_mit_stacktrace_in_details(self):
        [entry] = dev.parse_laravel_log([
            '[2026-10-16 18:00:00] local.ERROR: Kaputt {"exception":"[object]"}',
            '[stacktrace]',
            '#0 /var/www/html/app/X.php(12)',
        ])
        self.assertEqual(('local', 'ERROR', 'Kaputt {"exception":"[object]"}'), (entry['env'], entry['level'], entry['text']))
        self.assertEqual('[stacktrace]\n#0 /var/www/html/app/X.php(12)\n', entry['detail'])

    def test_postgres_mit_datenbank_details_und_langsamen_abfragen(self):
        entries = dev.parse_postgres_log([
            '2026-10-16 18:43:26.258 UTC [1] LOG:  database system is ready to accept connections',
            '2026-10-16 18:43:47.155 UTC [42] sail@laravel ERROR:  duplicate key value violates unique constraint "users_email_unique"',
            '2026-10-16 18:43:47.155 UTC [42] sail@laravel DETAIL:  Key (email)=(a@b.c) already exists.',
            '2026-10-16 18:43:47.156 UTC [42] sail@laravel STATEMENT:  insert into "users" ...',
            '2026-10-16 18:43:47.768 UTC [56] sail@laravel LOG:  duration: 307.172 ms  statement: select pg_sleep(0.3)',
            '2026-10-16 18:43:48.001 UTC [57] sail@testing LOG:  duration: 512.9 ms  execute pdo_stmt_00000001: select * from "users"',
            'unbekannte Zeile ohne Format',
        ])
        self.assertEqual([(None, 'LOG'), ('laravel', 'ERROR'), ('laravel', 'SLOW'), ('testing', 'SLOW')],
                         [(entry['db'], entry['level']) for entry in entries])
        self.assertEqual('DETAIL: Key (email)=(a@b.c) already exists.\nSTATEMENT: insert into "users" ...\n', entries[1]['detail'])
        self.assertEqual('307 ms: select pg_sleep(0.3)', entries[2]['text'])
        self.assertEqual('513 ms: select * from "users"', entries[3]['text'])

    def test_anfragen_ohne_up(self):
        requests = dev.parse_requests([
            '  2026-10-16 18:40:20 /up .......................................... ~ 0.12ms',
            '  2026-10-16 18:40:21 /api/v1/me ................................... ~ 12.5ms',
            '  2026-10-16 18:40:22 /admin/users ................................ ~ 1.02s',
            '   INFO  Server running on [http://0.0.0.0:80].',
        ])
        self.assertEqual([('18:40:21', '/api/v1/me'), ('18:40:22', '/admin/users')],
                         [(request['time'], request['path']) for request in requests])

    def test_quellen_trennen_app_datenbank_und_tests(self):
        laravel = ['[2026-10-16 18:00:00] local.ERROR: App kaputt', '[2026-10-16 18:00:01] testing.ERROR: aus einem alten Testlauf']
        testing = ['[2026-10-16 18:00:02] testing.WARNING: aus testing.log']
        postgres = [
            '2026-10-16 18:00:03.000 UTC [1] sail@laravel ERROR:  echter Fehler',
            '2026-10-16 18:00:04.000 UTC [2] sail@testing ERROR:  gewollter Fehler',
            '2026-10-16 18:00:05.000 UTC [3] LOG:  checkpoint starting: time',
        ]
        requests = ['  2026-10-16 18:00:06 /api/v1/me ...... ~ 700ms']
        logs = dev.classify_logs(laravel, testing, postgres, requests, ['\x1b[32mVITE ready\x1b[39m', ''])

        self.assertEqual(['App kaputt'], [entry['text'] for entry in logs['app']])
        self.assertEqual(['echter Fehler', 'checkpoint starting: time'], [entry['text'] for entry in logs['database']])
        self.assertEqual([('Laravel', 'aus einem alten Testlauf'), ('Laravel', 'aus testing.log'), ('Datenbank', 'gewollter Fehler')],
                         [(entry['origin'], entry['text']) for entry in logs['tests']])
        self.assertEqual([{'text': 'VITE ready'}], logs['vite'])
        self.assertEqual({
            'app': {'total': 1, 'problems': 1}, 'requests': {'total': 1, 'problems': 0}, 'database': {'total': 2, 'problems': 1},
            'tests': {'total': 3, 'problems': 0}, 'vite': {'total': 1, 'problems': 0},
        }, dev.log_counts(logs))


class TestHistoryTest(unittest.TestCase):
    def test_verlauf_behaelt_nur_die_letzten_laeufe_samt_ausgabe(self):
        with tempfile.TemporaryDirectory() as folder, \
                mock.patch.object(dev, 'STATE_DIR', Path(folder)), \
                mock.patch.object(dev, 'TEST_HISTORY', Path(folder) / 'test-history.json'), \
                mock.patch.object(dev, 'TEST_HISTORY_LIMIT', 2):
            for number in range(3):
                dev.save_test_run({'id': f'tests-backend-{number}', 'ok': True}, f'Ausgabe {number}')

            self.assertEqual(['tests-backend-1', 'tests-backend-2'], [run['id'] for run in dev.load_test_history()])
            self.assertFalse((Path(folder) / 'tests-backend-0.log').exists())
            self.assertEqual('Ausgabe 2', (Path(folder) / 'tests-backend-2.log').read_text())

    def test_lauf_wird_mit_zahlen_und_stand_gespeichert(self):
        def fake_run(cmd, cwd=None, timeout=None):
            if cmd[:2] == ['git', 'branch']:
                return 0, 'dev\n', ''
            if cmd[:2] == ['git', 'rev-parse']:
                return 0, 'abc1234\n', ''
            return 1, '  Tests:    1 failed, 9 passed (20 assertions)\n', ''

        with tempfile.TemporaryDirectory() as folder, \
                mock.patch.object(dev, 'STATE_DIR', Path(folder)), \
                mock.patch.object(dev, 'TEST_HISTORY', Path(folder) / 'test-history.json'), \
                mock.patch.object(dev, 'run', fake_run):
            messages = []
            dev.run_tests(['backend'], messages.append)
            [run] = dev.load_test_history()

        self.assertEqual(('backend', False, 'dev', 'abc1234'), (run['suite'], run['ok'], run['branch'], run['commit']))
        self.assertEqual({'passed': 9, 'failed': 1, 'total': 10}, run['counts'])
        self.assertEqual(['Backend-Tests laufen …', 'Backend: ROT · Tests: 1 failed, 9 passed (20 assertions)'], messages)


if __name__ == '__main__':
    unittest.main()
