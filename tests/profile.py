#!/usr/bin/env python3
"""Profile, private photos and personal favorites on an isolated instance."""
import base64
from smoke import Client, BASE, ADMIN_EMAIL, ADMIN_PASSWORD, USER_EMAIL, USER_PASSWORD

anonymous = Client()
for path in ['/profile', '/profile/avatar']:
    anonymous.request(path, 401)
member = Client()
member.login(USER_EMAIL, USER_PASSWORD)
admin = Client()
admin.login(ADMIN_EMAIL, ADMIN_PASSWORD)
original = member.request('/profile')
for data in [{'name': ''}, {'name': None}, {'role': 'ROLE_ADMIN'}, {'email': 'other@atlas.u5'}, {'id': admin.session['user']['id']}, {'active': False}, {'readingSize': 'huge'}, {'accent': 'red'}, {'bio': 'a' * 301}]:
    member.request('/profile', 422, 'PUT', data)
member.request('/profile', 403, 'PUT', {'name': 'New name'}, csrf=False)
changed = member.request('/profile', method='PUT', data={'name': 'Compte de lecture', 'department': 'Opérations', 'jobTitle': 'Collaborateur', 'bio': '<script>plain text</script>', 'readingSize': 'large', 'accent': 'teal'})
assert changed['name'] == 'Compte de lecture' and changed['readingSize'] == 'large' and changed['admin'] is False
assert member.request('/profile')['bio'] == '<script>plain text</script>'
assert admin.request('/profile')['name'] != changed['name']
member.request('/profile/avatar', 400, 'PUT', {'image': base64.b64encode(b'<svg onload="alert(1)"></svg>').decode()})
member.request('/profile/avatar', 400, 'PUT', {'image': base64.b64encode(b'a' * 262145).decode()})
photo = base64.b64encode(base64.b64decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mNk+A8AAQUBAScY42YAAAAASUVORK5CYII=')).decode()
member.request('/profile/avatar', 403, 'PUT', {'image': photo}, csrf=False)
updated = member.request('/profile/avatar', method='PUT', data={'image': photo})
assert updated['avatarUrl'].startswith('/api/profile/avatar?v=')
response = member.opener.open(BASE + updated['avatarUrl'])
assert response.headers['Content-Type'] == 'image/webp'
assert response.read()[:4] == b'RIFF'
admin.request('/profile/avatar', 404)
member.request('/profile/avatar', method='DELETE')
assert member.request('/profile')['avatarUrl'] is None
member.request('/profile/avatar', 404)
member.request('/profile', method='PUT', data={key: original[key] for key in ['name', 'department', 'jobTitle', 'bio', 'readingSize', 'accent']})
doc = member.request('/documents')[0]
for _ in range(2): member.request(f"/documents/{doc['id']}/favorite", method='PUT')
assert member.request(f"/documents/{doc['id']}")['favorite'] is True
assert admin.request(f"/documents/{doc['id']}")['favorite'] is False
member.request(f"/documents/{doc['id']}/favorite", 403, 'DELETE', csrf=False)
member.request('/documents/999999/favorite', 404, 'PUT')
member.request(f"/documents/{doc['id']}/favorite", method='DELETE')
assert member.request(f"/documents/{doc['id']}")['favorite'] is False
print('Private profile, image validation and isolated favorites verified.')
