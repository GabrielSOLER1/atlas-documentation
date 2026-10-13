# Internal reverse-proxy integration

1. Copy the application to VM1 and run `sh scripts/start.sh`, or `sh scripts/start.sh --offline` after loading the prepared images.
2. Set `APP_PUBLIC_URL=https://atlas.u5` in `.env`, then restart the application with `docker compose up -d --wait`.
3. Configure internal DNS so `atlas.u5` resolves to VM1's private address.
4. Bind the host reverse proxy only to VM1's private LAN interface. Serve `atlas.u5` over HTTPS with the internal certificate and forward every path to `https://localhost:8443`.
5. Enable upstream certificate verification and use `certs/ca.crt` as the proxy's trusted CA. The upstream name `localhost` is covered by the certificate.
6. Import the public CA certificate on VM3 and any authorized client. Never distribute a private key.
7. Validate DNS, the login page, a normal-user consultation, an administrator publication and database recovery from VM3.

Keep host routing, firewall and proxy bindings internal. There is no Internet-facing deployment or public certificate service in this project. The application's HTTP port performs redirects and is not the recommended proxy upstream.

The certificate script covers `atlas.u5`, `localhost` and `127.0.0.1`. For another internal hostname, issue a certificate containing that hostname before changing the service URL. Server certificates are valid for one year; renew them with the internal CA before expiry.

## Domain upgrade

Configure internal DNS and the proxy virtual host for **atlas.u5**. The certificate script renews a certificate that does not cover this hostname, while keeping the existing CA. Default bootstrap credentials use `admin@atlas.u5`, display name `admin`. Existing passwords survive the database migration; update private local credential files to the new identifier.

The default Compose project is `atlas-documentation`. Preserve data when moving from an older project name: take an SQL export, start the new database service, restore into it while the application is stopped, then start the application so transactional migrations and missing guide seeds apply. Verify account and document counts before retiring the previous services.
