# Cabek AutoEstimate Backend

AutoEstimate is an independent service in Cabek's software ecosystem. It centralizes normalized automotive part-price observations and exposes them through a read-only API that can be consumed by Cabek applications and services.

The backend also provides internal statistics and price-exploration tools, plus a CLI importer used to load normalized datasets into the AutoEstimate database.

## Technology

- PHP 8.3+
- Laravel 13
- MariaDB / MySQL-compatible relational database
- Vite + Tailwind CSS for the internal web interface
- PHPUnit through Laravel's test runner

## Data flow

```text
Data sources
    ↓
Scrapers / legacy extraction
    ↓
Raw JSON
    ↓
AI normalization
    ↓
Normalized JSON
    ↓
AutoEstimate importer
    ↓
AutoEstimate database
    ↓
AutoEstimate API
    ↓
Cabek applications and services
```

See [Architecture](docs/architecture.md) and [Normalization](docs/normalization.md) for the full flow.

## Quick start

```bash
composer install
npm install
cp .env.example .env
php artisan key:generate
```

Configure the database connection in `.env`, then run:

```bash
php artisan migrate
php artisan db:seed
npm run build
```

For local development:

```bash
composer run dev
```

## API

Base path:

```text
/api/v1
```

Available endpoints:

```text
GET /api/v1/marques
GET /api/v1/models
GET /api/v1/parts
GET /api/v1/sources
GET /api/v1/estimation
GET /api/v1/observations
```

All API requests require an application bearer token. See [API v1](docs/api.md) and [Authentication](docs/authentication.md).

## CLI importer

```bash
php artisan observations:import <path>
```

The importer accepts normalized JSON files, directories of JSON batches, and ZIP archives. See [Importer](docs/importer.md).

## Tests

```bash
php artisan test
```

or:

```bash
composer test
```

## Documentation

- [Architecture](docs/architecture.md)
- [API v1](docs/api.md)
- [Authentication](docs/authentication.md)
- [Database](docs/database.md)
- [Importer](docs/importer.md)
- [Normalization](docs/normalization.md)

The canonical AI normalization instructions are versioned in [`normalization/Data_Normalization_instructions_v2.1.6.md`](normalization/Data_Normalization_instructions_v2.1.6.md).
