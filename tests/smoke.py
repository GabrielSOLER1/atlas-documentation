#!/usr/bin/env python3
"""HTTP checks for an isolated validation instance."""
import http.cookiejar
import os
import ssl
import json
import sys
import urllib.error
import urllib.parse
import urllib.request

BASE = (sys.argv[1] if len(sys.argv) > 1 else "https://localhost:8443").rstrip("/")

ADMIN_EMAIL = os.environ.get('ATLAS_ADMIN_EMAIL')
ADMIN_PASSWORD = os.environ.get('ATLAS_ADMIN_PASSWORD')
USER_EMAIL = os.environ.get('ATLAS_USER_EMAIL')
USER_PASSWORD = os.environ.get('ATLAS_USER_PASSWORD')

class Client:
    def __init__(self):
        self.cookies = http.cookiejar.CookieJar()
        context = ssl.create_default_context(cafile=os.environ.get('ATLAS_CA_FILE'))
        self.opener = urllib.request.build_opener(urllib.request.HTTPCookieProcessor(self.cookies), urllib.request.HTTPSHandler(context=context))
        self.session = None

    def request(self, path, expected=200, method="GET", data=None, csrf=True, form=False):
        headers = {}
        if data is not None:
            if form:
                body = urllib.parse.urlencode(data).encode()
                headers["Content-Type"] = "application/x-www-form-urlencoded"
            else:
                body = json.dumps(data).encode()
                headers["Content-Type"] = "application/json"
        else:
            body = None
        if csrf and self.session:
            headers["X-CSRF-Token"] = self.session["csrfToken"]
        request = urllib.request.Request(BASE + "/api" + path, data=body, headers=headers, method=method)
        try:
            response = self.opener.open(request, timeout=15)
        except urllib.error.HTTPError as error:
            response = error
        payload = response.read().decode()
        assert response.status == expected, f"{method} {path}: attendu {expected}, reçu {response.status}: {payload}"
        result = json.loads(payload)
        print(f"OK — {method} {path} : {expected}")
        return result

    def refresh(self):
        self.session = self.request("/session")

    def login(self, email, password, expected=200, token=True):
        if not isinstance(email, str) or not email or not isinstance(password, str) or not password:
            raise RuntimeError('Provide the validation account using ATLAS_ADMIN_EMAIL / ATLAS_ADMIN_PASSWORD or ATLAS_USER_EMAIL / ATLAS_USER_PASSWORD.')
        self.refresh()
        data = {"email": email, "password": password}
        if token:
            data["_csrf_token"] = self.session["loginToken"]
        self.request("/login", expected, "POST", data, form=True)
        self.refresh()



def run_smoke():
    client = Client()
    client.request("/health")
    client.request("/documents", 401)
    client.login(USER_EMAIL, "incorrect", 401)
    client.login(USER_EMAIL, USER_PASSWORD, 401, token=False)
    client.login(USER_EMAIL, USER_PASSWORD)
    assert client.session["user"]["admin"] is False
    documents = client.request("/documents")
    assert len(documents) >= 2
    document = client.request(f"/documents/{documents[0]['id']}")
    client.request("/documents/999999", 404)
    client.request(f"/documents/{document['id']}", 403, "PUT", document)
    client.request("/logout", 403, "POST", csrf=False)
    client.request("/logout", method="POST")
    client.request("/documents", 401)

    admin = Client()
    admin.login(ADMIN_EMAIL, ADMIN_PASSWORD)
    assert admin.session["user"]["admin"] is True
    original = admin.request(f"/documents/{document['id']}")
    admin.request(f"/documents/{document['id']}", 403, "PUT", original, csrf=False)
    admin.request(f"/documents/{document['id']}", 400, "PUT", {**original, "title": ""})
    changed = {**original, "content": original["content"] + "\n\nVérification automatisée."}
    if original.get("blocks"):
        changed["blocks"] = original["blocks"] + [{"type": "text", "title": "", "text": "Vérification automatisée."}]
    try:
        admin.request(f"/documents/{document['id']}", method="PUT", data=changed)
        saved = admin.request(f"/documents/{document['id']}")
        if original.get("blocks"):
            assert saved["blocks"][-1]["text"] == "Vérification automatisée."
        else:
            assert saved["content"] == changed["content"], "La modification n’a pas été conservée."
    finally:
        admin.request(f"/documents/{document['id']}", method="PUT", data=original)
    admin.request("/logout", method="POST")
    admin.request("/documents", 401)
    print("\nTous les tests fonctionnels ont réussi.")


if __name__ == "__main__":
    run_smoke()
