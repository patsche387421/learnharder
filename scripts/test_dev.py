"""Tests für die Auswertung im Dashboard: `python3 -m unittest discover -s scripts` (aus dem Repo)."""

import json
import unittest

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


if __name__ == '__main__':
    unittest.main()
