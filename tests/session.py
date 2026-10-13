#!/usr/bin/env python3
"""Expire only a session created in the isolated validation container."""
import json
import os
import re
import subprocess
from smoke import Client, BASE, USER_EMAIL, USER_PASSWORD

container = os.environ.get('ATLAS_TEST_CONTAINER', 'atlas-gabriel-editor-check')
if container != 'atlas-gabriel-editor-check':
    raise RuntimeError('This test only supports the named isolated validation container.')
client = Client()
client.login(USER_EMAIL, USER_PASSWORD)
sid = next(cookie.value for cookie in client.cookies if cookie.name == 'ATLAS_SESSION')
assert re.fullmatch(r'[a-zA-Z0-9,-]{16,128}', sid)
code = """<?php
session_id(SID);
if (!session_start() || !isset($_SESSION['_sf2_attributes'])) { exit(1); }
$_SESSION['_sf2_attributes']['last_activity'] = time()-1801;
session_write_close();
""".replace('SID', json.dumps(sid))
subprocess.run(['docker', 'exec', '-i', container, 'php'], input=code.encode(), check=True, capture_output=True)
client.request('/documents', 401)
client.refresh()
assert client.session['user'] is None
print('Inactive sessions are rejected after 30 minutes.')

client.login(USER_EMAIL, USER_PASSWORD)
sid = next(cookie.value for cookie in client.cookies if cookie.name == 'ATLAS_SESSION')
assert re.fullmatch(r'[a-zA-Z0-9,-]{16,128}', sid)
code = """<?php
session_id(SID);
if (!session_start() || !isset($_SESSION['_sf2_attributes'])) { exit(1); }
$_SESSION['_sf2_attributes']['last_activity'] = time()-1801;
session_write_close();
""".replace('SID', json.dumps(sid))
subprocess.run(['docker', 'exec', '-i', container, 'php'], input=code.encode(), check=True, capture_output=True)
response = client.opener.open(BASE + '/')
assert response.status == 200 and 'text/html' in response.headers['Content-Type']
assert 'Connexion' in response.read().decode() or '#/connexion' in response.url
client.refresh()
assert client.session['user'] is None
print('An expired page reload returns to the dedicated login page.')
