#!/usr/bin/env python3
"""Account checks for an isolated validation database."""
import time
from smoke import Client, ADMIN_EMAIL, ADMIN_PASSWORD, USER_EMAIL, USER_PASSWORD

admin = Client()
anonymous = Client()
anonymous.request('/users', 401)
anonymous.request('/users', 401, 'POST', {})
admin.login(ADMIN_EMAIL, ADMIN_PASSWORD)
accounts = admin.request('/users')
assert all('password' not in user for user in accounts)
own_id = admin.session['user']['id']
admin.request(f'/users/{own_id}', 409, 'PATCH', {'active': False})
admin.request(f'/users/{own_id}', 409, 'PATCH', {'role': 'ROLE_USER'})
data = {
    'name': 'Compte de validation',
    'email': f'validation-{time.time_ns()}@atlas.local',
    'password': 'ValidationAtlas2026!',
    'role': 'ROLE_USER',
}
admin.request('/users', 403, 'POST', data, csrf=False)
admin.request('/users', 422, 'POST', {**data, 'password': 'court'})
admin.request('/users', 422, 'POST', {**data, 'email': 'incorrect'})
admin.request('/users', 422, 'POST', {**data, 'role': 'ROLE_SUPERADMIN'})
user = admin.request('/users', 201, 'POST', data)
assert user['active'] is True and user['role'] == 'ROLE_USER'
assert 'password' not in user
admin.request('/users', 409, 'POST', {**data, 'email': data['email'].upper()})

member = Client()
member.login(data['email'], data['password'])
member.request('/users', 403)
member.request('/users', 403, 'POST', {**data, 'email': 'other@atlas.local'})
member.request(f"/users/{user['id']}", 403, 'PATCH', {'role': 'ROLE_ADMIN'})
admin.request(f"/users/{user['id']}", 403, 'PATCH', {'active': False}, csrf=False)
admin.request(f"/users/{user['id']}", 422, 'PATCH', {'active': 'false'})
admin.request(f"/users/{user['id']}", 422, 'PATCH', {'password': 'cannot-change'})
admin.request('/users/999999', 404, 'PATCH', {'active': False})

try:
    # Role changes invalidate existing sessions.
    admin.request(f"/users/{user['id']}", method='PATCH', data={'role': 'ROLE_ADMIN'})
    member.request('/documents', 401)
    member.login(data['email'], data['password'])
    assert member.session['user']['admin'] is True
    member.request('/users')
    admin.request(f"/users/{user['id']}", method='PATCH', data={'role': 'ROLE_USER'})
    member.request('/users', 401)
    member.login(data['email'], data['password'])
    member.request('/users', 403)
    # Deactivation blocks the next authenticated request.
    admin.request(f"/users/{user['id']}", method='PATCH', data={'active': False})
    member.request('/documents', 401)
    member.login(data['email'], data['password'], 401)
    admin.request(f"/users/{user['id']}", method='PATCH', data={'active': True})
    member.login(data['email'], data['password'])
    member.request('/documents')
finally:
    admin.request(f"/users/{user['id']}", method='PATCH', data={'role': 'ROLE_USER', 'active': False})

print('\nGestion des comptes vérifiée : création, droits, rôles, désactivation et réactivation.')
