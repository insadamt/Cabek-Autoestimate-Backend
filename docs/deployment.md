# Deployment

AutoEstimate is deployed as a small Docker Compose stack. The deployment keeps the web server, PHP application, and database isolated while making the database data persistent across application updates.

## Topology

```text
Cabek infrastructure / reverse proxy
                ↓
          AutoEstimate Nginx
                ↓ FastCGI
        AutoEstimate PHP-FPM
                ↓ SQL
             MariaDB
                ↓
      persistent Docker volume
```

The stack contains three services:

| Service | Responsibility | Host exposure |
|---|---|---|
| `nginx` | HTTP entry point and static files | `127.0.0.1:8080` by default |
| `app` | PHP-FPM + Laravel | Docker network only |
| `database` | MariaDB | Docker network only |

Only Nginx publishes a host port. MariaDB is not exposed on the host.

## Environment separation

Local Laravel development and production Docker use different environment files:

```text
.env
    local development only

.env.production
    production Docker only
```

The committed templates are:

```text
.env.example
.env.production.example
```

The real `.env` and `.env.production` files are ignored by Git.

Do not copy `.env.production.example` over `.env`. Create production configuration with:

```bash
cp .env.production.example .env.production
```

Every production Compose command must explicitly use:

```bash
docker compose --env-file .env.production ...
```

This prevents Docker production settings from replacing or depending on the local development `.env`.

### Existing checkout that previously used `.env` for production

If `.env` currently contains the production Docker values from the earlier setup, preserve them first:

```bash
mv .env .env.production
cp .env.example .env
php artisan key:generate
```

Then restore any local database credentials required by your development environment in `.env`.

## Files

```text
Dockerfile
compose.yaml
.env.production.example
.dockerignore

docker/
├── nginx/
│   └── default.conf
└── php/
    ├── entrypoint.sh
    └── php.ini
```

The `Dockerfile` uses multiple build stages:

1. Node installs frontend dependencies and builds Vite assets.
2. PHP/Composer installs production PHP dependencies with `--no-dev`.
3. The production Composer stage rebuilds Laravel package-discovery manifests without development-only service providers.
4. The final `app` image contains PHP-FPM, Laravel, `vendor/`, the production package manifests, and compiled assets.
5. The final `nginx` image contains the public files and compiled assets required by the web server.

`.dockerignore` excludes generated local Laravel cache files such as `bootstrap/cache/*.php`. This prevents development-only providers such as Laravel Boost from being copied into the production image when production dependencies are installed with `--no-dev`.

## Requirements

The server needs:

- Docker Engine;
- Docker Compose v2 (`docker compose`);
- access to the repository or to images built from it;
- enough disk space for application images, the MariaDB volume, and backups.

The external Cabek reverse-proxy/domain/TLS setup is not defined here yet. By default AutoEstimate binds only to loopback so a reverse proxy on the same server can expose it safely.

## Production environment

Create the production file:

```bash
cp .env.production.example .env.production
```

Edit `.env.production` before starting the stack.

At minimum, replace:

```env
APP_KEY=
DB_PASSWORD=change-me
DB_ROOT_PASSWORD=change-root-password
```

Also set the real production URL when it is known:

```env
APP_URL=https://autoestimate.example.internal
```

### Generate `APP_KEY`

Build the application image first:

```bash
docker compose --env-file .env.production build app
```

The production entrypoint refuses to start Laravel when `APP_ENV=production` and `APP_KEY` is empty. Generate a key directly from the built image:

```bash
docker run --rm autoestimate-app:latest php artisan key:generate --show
```

Copy the returned value into `.env.production`:

```env
APP_KEY=base64:...
```

The production `APP_KEY` must remain stable across deployments.

## Network exposure

The default production template uses:

```env
AUTOESTIMATE_BIND_ADDRESS=127.0.0.1
AUTOESTIMATE_HTTP_PORT=8080
```

This publishes AutoEstimate as:

```text
127.0.0.1:8080
```

Use this when a reverse proxy on the same server provides the domain and HTTPS.

If that port is already occupied, choose another host port in `.env.production`, for example:

```env
AUTOESTIMATE_HTTP_PORT=8081
```

If AutoEstimate must be reachable directly from another machine on the network, explicitly change:

```env
AUTOESTIMATE_BIND_ADDRESS=0.0.0.0
```

Do this only when the server firewall and network policy are configured appropriately.

## First start

After `.env.production` is configured:

```bash
docker compose --env-file .env.production build --pull
docker compose --env-file .env.production up -d
```

Check container state:

```bash
docker compose --env-file .env.production ps
```

Inspect logs when necessary:

```bash
docker compose --env-file .env.production logs app
docker compose --env-file .env.production logs nginx
docker compose --env-file .env.production logs database
```

The Nginx container health check requests Laravel's `/up` endpoint.

Manual health check, using the configured host port:

```bash
curl http://127.0.0.1:8080/up
```

Use the actual `AUTOESTIMATE_HTTP_PORT` value if it differs from `8080`.

## Database persistence

MariaDB stores its files in the Compose named volume `autoestimate_db` (Docker may prefix the engine-level volume name with the Compose project name). It is mounted at:

```text
/var/lib/mysql
```

Rebuilding or replacing the `app`, `nginx`, or `database` container does not remove this volume.

Do not use:

```bash
docker compose --env-file .env.production down -v
```

in production unless destroying the database is explicitly intended. The `-v` flag removes named volumes.

A persistent volume is not a backup. Backups must be stored separately from the Docker volume/server disk.

## Initial database strategy

AutoEstimate can be initialized in either of two ways.

### Preferred: restore the verified database

The normalized dataset is constructed and verified before deployment. Export that database from the trusted environment, transfer the dump to the server, then restore it into production MariaDB.

Example export:

```bash
mariadb-dump \
  --single-transaction \
  --routines \
  --triggers \
  cabek_autoestimate_backend > autoestimate.sql
```

On the production server, with the stack running:

```bash
docker compose --env-file .env.production exec -T database sh -c \
  'mariadb -uroot -p"$MARIADB_ROOT_PASSWORD" "$MARIADB_DATABASE"' \
  < autoestimate.sql
```

If the dump contains the Laravel `migrations` table and complete application schema, do not recreate the schema before restoring it.

After the restore:

```bash
docker compose --env-file .env.production exec app php artisan migrate --force
```

This applies only migrations that are not already recorded as executed.

### Fresh empty database

For an intentionally new database:

```bash
docker compose --env-file .env.production exec app php artisan migrate --force
docker compose --env-file .env.production exec app php artisan db:seed --force
```

This creates the schema and canonical seed data, but it does not recreate the normalized production observation dataset.

## Production optimization

After deployment or configuration changes:

```bash
docker compose --env-file .env.production exec app php artisan optimize
```

To clear generated runtime caches while troubleshooting:

```bash
docker compose --env-file .env.production exec app php artisan optimize:clear
```

## API key administration

Run application CLI commands inside the production `app` container.

Create an API key:

```bash
docker compose --env-file .env.production exec app php artisan api:token:create "Cabek Production"
```

List API clients:

```bash
docker compose --env-file .env.production exec app php artisan api:token:list
```

Revoke an API key:

```bash
docker compose --env-file .env.production exec app php artisan api:token:revoke <id>
```

The generated API key is displayed once. Store it in the consuming application's secret configuration, not in the AutoEstimate repository.

## Deploying an application update

A manual update follows this sequence:

```bash
git pull

docker compose --env-file .env.production build --pull
docker compose --env-file .env.production up -d --remove-orphans

docker compose --env-file .env.production exec app php artisan migrate --force
docker compose --env-file .env.production exec app php artisan optimize

docker compose --env-file .env.production ps
curl http://127.0.0.1:8080/up
```

Use the configured host port in the health check.

Application containers are replaced by new images. The MariaDB volume remains attached and is not recreated.

Before applying a migration that changes important production data or schema, create a database backup.

## Database backup

Create a logical dump from the running MariaDB container:

```bash
docker compose --env-file .env.production exec -T database sh -c \
  'mariadb-dump -uroot -p"$MARIADB_ROOT_PASSWORD" --single-transaction --routines --triggers "$MARIADB_DATABASE"' \
  > autoestimate-$(date +%F-%H%M%S).sql
```

The resulting `.sql` file must be copied to backup storage outside the AutoEstimate Docker volume and preferably outside the server itself.

## Restore a backup

```bash
docker compose --env-file .env.production exec -T database sh -c \
  'mariadb -uroot -p"$MARIADB_ROOT_PASSWORD" "$MARIADB_DATABASE"' \
  < autoestimate-backup.sql
```

Restoration replaces database state and should be performed deliberately, normally after taking a current backup.

## Useful Docker commands

```bash
# Container state
docker compose --env-file .env.production ps

# Follow all logs
docker compose --env-file .env.production logs -f

# Follow one service
docker compose --env-file .env.production logs -f app

# Open a shell in the Laravel container
docker compose --env-file .env.production exec app sh

# Run Artisan
docker compose --env-file .env.production exec app php artisan about

# Restart one service
docker compose --env-file .env.production restart app

# Stop the stack without deleting the database volume
docker compose --env-file .env.production down
```

## TLS / HTTPS

The Compose stack currently serves HTTP. HTTPS should be terminated by Cabek's reverse proxy or ingress layer once the actual server infrastructure and domain are known.

API keys must not be transmitted over unencrypted public networks. Production API traffic should ultimately use HTTPS.
