#!/usr/bin/env python3
"""Administrator roadmap access and personal progression."""
from smoke import Client, ADMIN_EMAIL, ADMIN_PASSWORD, USER_EMAIL, USER_PASSWORD
anonymous = Client()
anonymous.request('/roadmap', 401)
member = Client()
member.login(USER_EMAIL, USER_PASSWORD)
member.request('/roadmap', 403)
member.request('/roadmap', 403, 'PATCH', {'completed': []})
admin = Client()
admin.login(ADMIN_EMAIL, ADMIN_PASSWORD)
data = admin.request('/roadmap')
assert len(data['nodes']) == 12 and len({n['id'] for n in data['nodes']}) == 12
assert all(n['link'].startswith('/') and not n['link'].startswith('//') for n in data['nodes'])
admin.request('/roadmap', 403, 'PATCH', {'completed': []}, csrf=False)
for payload in [{'completed': ['unknown']}, {'completed': 'besoin'}, {'completed': [None]}, {'completed': [], 'user': 2}]:
    admin.request('/roadmap', 422, 'PATCH', payload)
completed = [data['nodes'][0]['id']]
admin.request('/roadmap', method='PATCH', data={'completed': completed})
assert admin.request('/roadmap')['completed'] == completed
admin.request('/logout', method='POST')
admin.login(ADMIN_EMAIL, ADMIN_PASSWORD)
assert admin.request('/roadmap')['completed'] == completed
admin.request('/roadmap', method='PATCH', data={'completed': data['completed']})
print('Roadmap access, links and saved progress verified.')
