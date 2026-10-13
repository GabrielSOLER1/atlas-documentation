#!/usr/bin/env python3
"""Publishing, auditing and access-control checks on an isolated database."""
import time
from smoke import Client, ADMIN_EMAIL, ADMIN_PASSWORD, USER_EMAIL, USER_PASSWORD

admin = Client()
admin.login(ADMIN_EMAIL, ADMIN_PASSWORD)
member = Client()
member.login(USER_EMAIL, USER_PASSWORD)
anonymous = Client()
payload = {
    'title': 'Documentation de validation', 'category': 'Validation', 'summary': 'Guide structuré de validation.',
    'blocks': [
        {'type': 'section', 'title': 'Préparer', 'text': 'Une étape claire.'},
        {'type': 'note', 'title': 'À retenir', 'text': '<script>alert(1)</script> doit rester du texte.'},
        {'type': 'checklist', 'title': 'Vérifier', 'items': ['Premier point', 'Deuxième point']},
        {'type': 'code', 'title': 'Commande', 'text': 'echo "Atlas"'},
    ],
}
anonymous.request('/documents', 401, 'POST', payload)
member.request('/documents', 403, 'POST', payload)
admin.request('/documents', 403, 'POST', payload, csrf=False)
admin.request('/documents', 400, 'POST', {**payload, 'blocks': [{'type': 'html', 'text': '<img src="https://outside.invalid">'}]})
admin.request('/documents', 400, 'POST', {**payload, 'blocks': payload['blocks'] * 8})
created = admin.request('/documents', 201, 'POST', payload)
document = member.request(f"/documents/{created['id']}")
assert document['blocks'] == payload['blocks']
assert '<script>alert(1)</script>' in document['content']
updated = {**payload, 'title': 'Documentation mise à jour'}
admin.request(f"/documents/{created['id']}", method='PUT', data=updated)
assert member.request(f"/documents/{created['id']}")['title'] == updated['title']
anonymous.request('/activity', 401)
member.request('/activity', 403)
activity = admin.request('/activity')
assert activity['summary']['connections'] >= 2
assert any(event['type'] == 'document_created' and event['target_id'] == created['id'] for event in activity['events'])
assert any(event['type'] == 'login_success' and event['actor_email'] == 'gabriel@atlas.local' for event in activity['events'])
assert all('password' not in event and 'attempt_key' not in event for event in activity['events'])
filtered = admin.request('/activity?type=document_created&days=30')
assert all(event['type'] == 'document_created' for event in filtered['events'])
admin.request('/activity?type=invalid', 400)

throttled = Client()
email = f'blocked-{time.time_ns()}@atlas.local'
for _ in range(5):
    throttled.login(email, 'IncorrectPassword!', 401)
throttled.login(email, 'IncorrectPassword!', 429)
print('\nPublishing, local activity and login throttling checks passed.')
