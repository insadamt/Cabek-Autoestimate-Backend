# Authentication

AutoEstimate uses application bearer tokens. A token represents a consuming application or environment, not a human user.

Examples of API clients might include:

```text
Claims Service - Production
Claims Service - Staging
Internal Integration
```

## Request authentication

Send the token in the standard bearer-token header:

```http
Authorization: Bearer <token>
Accept: application/json
```

A missing, invalid, or revoked token returns HTTP `401`:

```json
{
  "message": "Unauthenticated."
}
```

## Token format and storage

New tokens are generated with the `cae_` prefix followed by a random secret.

Example shape:

```text
cae_<random-secret>
```

The plaintext token is shown only when it is created. AutoEstimate stores only a SHA-256 hash in `api_clients.token_hash`.

During authentication the incoming bearer token is hashed and compared with an active API-client record. A successful request updates `last_used_at`.

## Token lifetime

Tokens do not expire automatically.

A token remains valid until its API client is revoked.

## Create a token

```bash
php artisan api:token:create "Claims Service - Production"
```

The client name must be unique.

The command prints the plaintext token once. Store it immediately in the consuming application's secret/environment configuration.

## List API clients

```bash
php artisan api:token:list
```

The command displays:

- client ID;
- name;
- active/revoked status;
- creation time;
- last-used time.

## Revoke a token

```bash
php artisan api:token:revoke <id>
```

Revocation sets the API client to inactive. Requests using that token will then receive HTTP `401`.

## Rate limiting

API requests are rate limited per authenticated API client.

Default:

```text
120 requests/minute
```

Configuration:

```env
AUTOESTIMATE_API_RATE_LIMIT=120
```

When the limit is exceeded, the API returns HTTP `429`.

## Operational rules

- use a different token for each application/environment;
- never commit plaintext tokens to Git;
- store production tokens in the consuming service's secret configuration;
- revoke a token immediately when it is no longer trusted or needed;
- create a new client/token when rotating credentials rather than trying to recover the old plaintext token.
