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

    def test_log_nur_warnungen_und_fehler_mit_erster_zeile(self):
        lines = [
            '[2026-10-16 16:40:01] local.INFO: Mail verschickt',
            '[2026-10-16 16:41:02] local.ERROR: SQLSTATE[08006] Verbindung fehlgeschlagen {"exception":"[object]"}',
            '#0 /var/www/html/vendor/laravel/framework/src/Database/Connection.php(1)',
            '[2026-10-16 16:42:03] local.WARNING: Langsame Anfrage',
        ]
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
