#!/usr/bin/env python3
"""Verify TLS, response headers, private endpoints and custom HTTP errors."""
import os
import ssl
import urllib.error
import urllib.request
from smoke import Client, BASE

context = ssl.create_default_context(cafile=os.environ.get('ATLAS_CA_FILE'))
response = urllib.request.urlopen(BASE + '/', context=context)
assert response.status == 200
csp = response.headers['Content-Security-Policy']
for rule in ["connect-src 'self'", "script-src 'self'", "object-src 'none'", "frame-ancestors 'none'"]:
    assert rule in csp
assert response.headers['X-Content-Type-Options'] == 'nosniff'
assert response.headers['Referrer-Policy'] == 'no-referrer'
assert 'no-store' in response.headers['Cache-Control']
assert 'max-age=' in response.headers['Strict-Transport-Security']
assert 'X-Powered-By' not in response.headers
client = Client()
client.refresh()
assert any(cookie.name == 'ATLAS_SESSION' and cookie.secure for cookie in client.cookies)
client.request('/users', 401)
client.request('/users', 401, 'POST', {})
client.request('/activity', 401)
client.request('/unknown-endpoint', 404)
try:
    urllib.request.urlopen(BASE + '/missing-page', context=context)
    raise AssertionError('Missing page must return HTTP 404.')
except urllib.error.HTTPError as error:
    assert error.code == 404
    assert 'Cette page ne fait pas partie du guide.' in error.read().decode()

class NoRedirect(urllib.request.HTTPRedirectHandler):
    def redirect_request(self, req, fp, code, msg, headers, newurl):
        return None

opener = urllib.request.build_opener(NoRedirect())
http_base = os.environ.get('ATLAS_HTTP_URL', 'http://localhost:8080')
for path in ['/', '/api/session', '/build/atlas-mark.svg']:
    try:
        opener.open(http_base + path)
        raise AssertionError('HTTP must redirect to HTTPS.')
    except urllib.error.HTTPError as error:
        assert error.code == 308
        assert error.headers['Location'].startswith('https://')
print('TLS verification, HTTPS redirects, secure cookies, CSP and HTTP 404 checks passed.')
