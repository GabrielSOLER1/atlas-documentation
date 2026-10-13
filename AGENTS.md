# Project rules

- Keep Atlas internal-only. No external APIs, CDN resources, remote fonts, email services or third-party tracking.
- Bundle all visual assets locally. Keep secrets, private keys and bootstrap credentials server-side.
- Account creation, publishing and audit access require an authenticated administrator on both client and server.
- Keep HTTPS enforced in deployment. Use the local CA; never disable certificate verification to deploy.
- Run mutating checks against an isolated database and container, not the primary database.
- Use French interface text, an English quick-start README, and minimal English code comments.
- Preserve the professional internal-company presentation.
