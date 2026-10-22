# Nutrition Portal API v1

Base URL (local): `http://localhost/barangay_default/api/v1/`

## Auth model (mobile)

- **Web portal:** unchanged PHP sessions + CSRF.
- **Mobile app:** opaque **Bearer tokens** stored hashed in `nutrition_api_tokens`.
- Send: `Authorization: Bearer <token>`
- No CSRF for Bearer requests.
- Tokens expire (default 30 days); logout revokes the current token.

## Endpoints

| Method | Path | Auth | Purpose | Status |
|--------|------|------|---------|--------|
| `POST` | `/login.php` | No | Username/password → token + profile. Blocks double login (`already_logged_in`); pass `force_login: true` to take over | **Done** |
| `GET` | `/me.php` | Bearer | Current user, role, barangays, puroks | **Done** |
| `POST` | `/logout.php` | Bearer | Revoke current token | **Done** |
| `POST` | `/change-password.php` | Bearer | Change password (current + new + confirm); revokes other tokens | **Done** |
| `GET` | `/barangays.php` | Bearer | Barangays the user may open | **Done** |
| `GET` | `/puroks.php?barangay_id=` | Bearer | Puroks for encode (BNS-filtered) | **Done** |
| `POST` | `/context.php` | Bearer | Set active barangay for this token | **Done** |
| `GET` | `/dashboard.php` | Bearer | Status chip counts | **Done** |
| `GET` | `/children.php?status=` | Bearer | Children by nutritional status | **Done** |
| `GET` | `/households.php` | Bearer | Registered households list | **Done** |
| `GET` | `/households.php?id=` | Bearer | One household + members | **Done** |
| `POST` | `/household-survey.php` | Bearer | Create/update survey (shared commit) | **Done** |
| `GET` | `/survey-options.php` | Bearer | Full encode option lists (BSI, FP, meals, relationships, …) | **Done** |
| `GET` | `/households.php?id=&full=1` | Bearer | Household + full `survey` edit payload | **Done** |
| `GET` | `/reports.php` | Bearer | Summary + consolidated household list | **Done** |

## Typical mobile flow

1. `POST login.php` → store `token`
2. If multiple barangays: `POST context.php` with `{ "barangay_id": "…" }`
3. `GET puroks.php` → encode form
4. `POST household-survey.php` → save
5. `GET households.php` → list
6. `GET dashboard.php` → chips; `GET children.php?status=underweight` → names

## Auth header

```http
Authorization: Bearer ntr_…
Content-Type: application/json
```

## Who may use the API

Nutrition portal roles only: SSA, Nutrition SA, Nutrition Admin (A), CNPC, BNS.
