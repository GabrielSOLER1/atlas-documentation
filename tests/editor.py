#!/usr/bin/env python3
"""Reject stale document edits without overwriting a newer publication."""
from smoke import Client, ADMIN_EMAIL, ADMIN_PASSWORD, USER_EMAIL, USER_PASSWORD
first, second, member = Client(), Client(), Client()
first.login(ADMIN_EMAIL, ADMIN_PASSWORD)
second.login(ADMIN_EMAIL, ADMIN_PASSWORD)
member.login(USER_EMAIL, USER_PASSWORD)
payload = {'title': 'Versions du guide', 'category': 'Validation', 'summary': 'Vérifier la publication concurrente.', 'blocks': [{'type': 'section', 'title': 'Repère', 'text': 'Contenu initial.'}]}
created = first.request('/documents', 201, 'POST', payload)
path = f"/documents/{created['id']}"
old = second.request(path)
first.request(path, method='PUT', data={**payload, 'title': 'Version récente', 'expectedUpdatedAt': old['updated_at']})
second.request(path, 409, 'PUT', {**payload, 'title': 'Version dépassée', 'expectedUpdatedAt': old['updated_at']})
assert member.request(path)['title'] == 'Version récente'
for invalid in [None, 1, 'yesterday', '2026-01-01; DROP TABLE documents']:
    second.request(path, 400, 'PUT', {**payload, 'expectedUpdatedAt': invalid})
latest = second.request(path)
second.request(path, method='PUT', data={**payload, 'title': 'Version finale', 'expectedUpdatedAt': latest['updated_at']})
assert member.request(path)['title'] == 'Version finale'
member.request(path, 403, 'PUT', {**payload, 'expectedUpdatedAt': latest['updated_at']})
print('Concurrent publication checks passed.')
