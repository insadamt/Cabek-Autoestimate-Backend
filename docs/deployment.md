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

Only Nginx publishes a host port. MariaDB is intentionally not exposed on the host.

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
2. PHP/Composer installs production PHP dependencies.
3. The final `app` image contains PHP-FPM, Laravel, `vendor/`, and compiled assets.
4. The final `nginx` image contains the public files and compiled assets required by the web server.

Node and development PHP dependencies are not required in the running application container.

## Requirements

The server needs:

- Docker Engine;
- Docker Compose v2 (`docker compose`);
- access to the repository or to images built from it;
- enough disk space for the application images, MariaDB volume, and database backups.

The external Cabek reverse-proxy/domain/TLS setup is intentionally not defined here. By default AutoEstimate binds only to loopback so a reverse proxy on the same server can expose it safely.

## Production environment

Create the runtime environment file:

```bash
cp .env.production.example .env
```

Edit `.env` before starting the stack.

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
docker compose build app
```

The production entrypoint refuses to start Laravel when `APP_ENV=production` and `APP_KEY` is empty. Generate a key directly from the built image:

```bash
docker run --rm autoestimate-app:latest php artisan key:generate --show
```

Copy the returned value into `.env`:

```env
APP_KEY=base64:...
```

The production `APP_KEY` must remain stable across deployments.

## Network exposure

The default production example uses:

```env
AUTOESTIMATE_BIND_ADDRESS=127.0.0.1
AUTOESTIMATE_HTTP_PORT=8080
```

This publishes AutoEstimate as:

```text
127.0.0.1:8080
```

Use this when a reverse proxy on the same server will provide the public/internal domain and HTTPS.

If AutoEstimate must be reachable directly from another machine on the network, explicitly change:

```env
AUTOESTIMATE_BIND_ADDRESS=0.0.0.0
```

Do this only when the server firewall/network policy is configured appropriately.

## First start

After `.env` is configured:

```bash
docker compose build --pull
docker compose up -d
```

Check container state:

```bash
docker compose ps
```

Inspect logs when necessary:

```bash
docker compose logs app
docker compose logs nginx
docker compose logs database
```

The Nginx container health check requests Laravel's `/up` endpoint.

Manual health check:

```bash
curl http://127.0.0.1:8080/up
```

## Database persistence

MariaDB stores its files in the Compose named volume `autoestimate_db` (Docker may prefix the engine-level volume name with the Compose project name). It is mounted at:

```text
/var/lib/mysql
```

Rebuilding or replacing the `app`, `nginx`, or `database` container does not remove this volume.

Do not use `docker compose down -v` in production unless destroying the database is explicitly intended. The `-v` flag removes named volumes.

A persistent volume is not a backup. Backups must be stored separately from the Docker volume/server disk.

## Initial database strategy

AutoEstimate can be initialized in either of two ways.

### Preferred for the current project: restore the verified database

The normalized dataset is constructed and verified before deployment. Export that database from the trusted environment, transfer the dump to the server, then restore it into the production MariaDB container.

Example backup/export from a MariaDB environment:

```bash
mariadb-dump \
  --single-transaction \
  --routines \
  --triggers \
  cabek_autoestimate_backend > autoestimate.sql
```

On the production server, with the stack running:

```bash
docker compose exec -T database sh -c \
  'mariadb -uroot -p"$MARIADB_ROOT_PASSWORD" "$MARIADB_DATABASE"' \
  < autoestimate.sql
```

If the dump contains the Laravel `migrations` table and the complete application schema, do not recreate the schema before restoring it.

After the restore:

```bash
docker compose exec app php artisan migrate --force
```

This applies only migrations that are not already recorded as executed.

### Fresh empty database

For an intentionally new database:

```bash
docker compose exec app php artisan migrate --force
docker compose exec app php artisan db:seed --force
```

This creates the schema and canonical seed data, but it does not recreate the normalized production observation dataset.

## Production optimization

After deployment or configuration changes:

```bash
docker compose exec app php artisan optimize
```

To clear generated caches while troubleshooting:

```bash
docker compose exec app php artisan optimize:clear
```

## API key administration

Run application CLI commands inside the `app` container.

Create an API key:

```bash
docker compose exec app php artisan api:token:create "Cabek Production"
```

List API clients:

```bash
docker compose exec app php artisan api:token:list
```

Revoke an API key:

```bash
docker compose exec app php artisan api:token:revoke <id>
```

The generated API key is displayed once. Store it in the consuming application's secret configuration, not in the AutoEstimate repository.

## Deploying an application update

A manual update currently follows this sequence:

```bash
git pull

docker compose build --pull
docker compose up -d --remove-orphans

docker compose exec app php artisan migrate --force
docker compose exec app php artisan optimize

docker compose ps
curl http://127.0.0.1:8080/up
```

Application containers are replaced by new images. The MariaDB volume remains attached and is not recreated.

Before applying a migration that changes important production data or schema, create a database backup.

## Database backup

Create a logical dump from the running MariaDB container:

```bash
docker compose exec -T database sh -c \
  'mariadb-dump -uroot -p"$MARIADB_ROOT_PASSWORD" --single-transaction --routines --triggers "$MARIADB_DATABASE"' \
  > autoestimate-$(date +%F-%H%M%S).sql
```

The resulting `.sql` file must be copied to backup storage outside the AutoEstimate Docker volume and preferably outside the server itself.

## Restore a backup

```bash
docker compose exec -T database sh -c \
  'mariadb -uroot -p"$MARIADB_ROOT_PASSWORD" "$MARIADB_DATABASE"' \
  < autoestimate-backup.sql
```

Restoration replaces database state and should be performed deliberately, normally after taking a current backup.

## Useful Docker commands

```bash
# Container state
docker compose ps

# Follow all logs
docker compose logs -f

# Follow one service
docker compose logs -f app

# Open a shell in the Laravel container
docker compose exec app sh

# Run Artisan
docker compose exec app php artisan about

# Restart one service
docker compose restart app

# Stop the stack without deleting the database volume
docker compose down
```

## TLS / HTTPS

The Compose stack currently serves HTTP. HTTPS should be terminated by Cabek's reverse proxy or ingress layer once the actual server infrastructure and domain are known.

API keys must not be transmitted over unencrypted public networks. Production API traffic should ultimately use HTTPS.
