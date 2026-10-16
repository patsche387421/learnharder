"""Tests für die Testzentrale: `python3 -m unittest discover -s scripts` (aus dem Repo)."""

import http.client
import json
import threading
import time
import unittest
from http.server import ThreadingHTTPServer
from unittest import mock

import dev_web


class AllowedTest(unittest.TestCase):
    def test_nur_eigene_adresse_und_aktionen_nur_mit_header(self):
        self.assertTrue(dev_web.allowed('GET', 'localhost:8090', None, None, 8090))
        self.assertTrue(dev_web.allowed('POST', '127.0.0.1:8090', 'http://127.0.0.1:8090', '1', 8090))
        # DNS-Rebinding: fremder Name, der auf 127.0.0.1 zeigt
        self.assertFalse(dev_web.allowed('GET', 'boese.example:8090', None, None, 8090))
        # Fremde Seite im selben Browser
        self.assertFalse(dev_web.allowed('POST', 'localhost:8090', 'https://boese.example', '1', 8090))
        # Formular ohne eigenen Header
        self.assertFalse(dev_web.allowed('POST', 'localhost:8090', None, None, 8090))

    def test_messpunkt_aus_dem_zustand(self):
        state = {
            'services': {'pgsql': {'name': 'backend-pgsql-1', 'state': 'running'}, 'redis': {'name': 'backend-redis-1', 'state': 'exited'}},
            'stats': {'backend-pgsql-1': ('0.30%', '48.1MiB')},
            'up': (200, 57.4),
        }
        self.assertEqual({'t': 100, 'cpu': {'pgsql': 0.3}, 'mem': {'pgsql': 48.1}, 'up': 57}, dev_web.sample(state, 100.2))
        self.assertIsNone(dev_web.sample({**state, 'up': None}, 0)['up'])


class ServerTest(unittest.TestCase):
    """Echter Server auf einem freien Port, ohne Docker: Der Zustand ist vorgegeben, die Aktion ist ein Platzhalter."""

    def setUp(self):
        self.center = dev_web.Center()
        self.center.state = {'services': {}, 'stats': {}, 'mails': None, 'problems': []}
        self.server = ThreadingHTTPServer(('127.0.0.1', 0), dev_web.make_handler(self.center))
        self.port = self.server.server_port
        threading.Thread(target=self.server.serve_forever, daemon=True).start()
        self.addCleanup(self.server.server_close)
        self.addCleanup(self.server.shutdown)

    def request(self, method, path, headers=None):
        connection = http.client.HTTPConnection('127.0.0.1', self.port, timeout=5)
        connection.request(method, path, headers={'Host': f'localhost:{self.port}', **(headers or {})})
        response = connection.getresponse()
        body = response.read()
        connection.close()
        return response.status, body

    def test_seite_zustand_und_design_tokens(self):
        status, body = self.request('GET', '/')
        self.assertEqual(200, status)
        self.assertIn(b'Testzentrale', body)

        status, body = self.request('GET', '/api/state')
        self.assertEqual(200, status)
        self.assertEqual({'state', 'history', 'activity', 'busy', 'tests'}, set(json.loads(body)))

        status, body = self.request('GET', '/styles/tokens.css')
        self.assertEqual((200, True), (status, b'--surface' in body))

    def test_logs_je_quelle_neueste_zuerst(self):
        self.center.logs = {source: [] for source in dev_web.LOG_SOURCES}
        self.center.logs['database'] = [{'text': 'alt'}, {'text': 'neu'}]

        status, body = self.request('GET', '/api/logs/database')
        self.assertEqual((200, ['neu', 'alt']), (status, [entry['text'] for entry in json.loads(body)['entries']]))
        self.assertEqual(404, self.request('GET', '/api/logs/geheim')[0])

    def test_fremder_host_und_unbekannte_pfade(self):
        self.assertEqual(403, self.request('GET', '/api/state', {'Host': f'boese.example:{self.port}'})[0])
        self.assertEqual(404, self.request('GET', '/styles/../dev.py')[0])
        self.assertEqual(404, self.request('GET', '/api/tests/..%2Fdev')[0])

    def test_aktion_nur_mit_header_und_nicht_doppelt(self):
        started = threading.Event()
        release = threading.Event()

        def slow(log):
            started.set()
            release.wait(5)
            log('fertig')

        with mock.patch.dict(dev_web.ACTIONS, {'probe': ('Probe', slow)}):
            self.assertEqual(403, self.request('POST', '/api/actions/probe')[0])
            self.assertEqual(404, self.request('POST', '/api/actions/gibtsnicht', {'X-LH-Dev': '1'})[0])
            self.assertEqual(202, self.request('POST', '/api/actions/probe', {'X-LH-Dev': '1'})[0])
            self.assertTrue(started.wait(5))
            self.assertEqual(409, self.request('POST', '/api/actions/probe', {'X-LH-Dev': '1'})[0])
            release.set()
            for _ in range(50):
                if self.center.busy is None:
                    break
                time.sleep(0.05)

        self.assertIsNone(self.center.busy)
        self.assertTrue(self.center.activity[-1].endswith('fertig'))


if __name__ == '__main__':
    unittest.main()
