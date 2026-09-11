# Cabek AutoEstimate Backend

Laravel backend for Cabek AutoEstimate's normalized car-part pricing database.

The production-facing application is focused on **querying and analysing a verified pricing dataset**. Dataset construction is performed separately through scraping, normalization, validation, and the local CLI importer. The production API does not scrape, normalize, or ingest observations.

## Dataset construction workflow

```text
Websites / legacy data
   ↓
Scrapers / extraction
   ↓
Normalization AI
   ↓
Import-ready JSON / ZIP
   ↓
Artisan CLI importer
   ↓
Local verified database
   ↓
Database dump / migration to the company server
```

The initial website dataset is collected and imported as a controlled dataset-construction process. The production application does not schedule recurring scraping.

## Setup

```bash
composer install
cp .env.example .env
php artisan key:generate
php artisan migrate
php artisan db:seed
```

Configure the database connection in `.env` before importing observations.

# Observation importer

The importer accepts:

- one normalized `.json` file;
- a directory containing normalized JSON batches;
- a `.zip` containing normalized JSON batches.

The command is:

```bash
php artisan observations:import <path>
```

Examples:

```bash
php artisan observations:import normalized_import_batch_001.json
```

```bash
php artisan observations:import ./normalized-output/
```

```bash
php artisan observations:import normalized-output.zip
```

## Safe dry run

Validate the complete input without writing anything to the database:

```bash
php artisan observations:import normalized-output.zip --dry-run
```

The importer performs a complete preflight first. If **any** import batch fails validation, no observations are imported.

Validation includes the canonical marque/model/part/source rules already used by the backend, model-to-marque consistency, allowed part types, price rules, production-year ordering, timestamp format, and the maximum 500 observations per JSON file.

Each JSON file is also limited to the configured 5 MB maximum.

## Non-interactive import

Skip the confirmation prompt:

```bash
php artisan observations:import normalized-output.zip --yes
```

## JSON audit report

```bash
php artisan observations:import normalized-output.zip \
  --dry-run \
  --report=storage/import-reports/preflight.json
```

Or for the real import:

```bash
php artisan observations:import normalized-output.zip \
  --yes \
  --report=storage/import-reports/import.json
```

The report includes file counts, observation counts, inserted rows, skipped exact duplicates, duration, and validation/import errors when applicable.

## Normalization review files

A normalization package may contain:

```text
normalized_import_batch_001.json
normalized_import_batch_002.json
normalization_review.json
```

`normalization_review.json` is an audit/recovery artifact, not an import file. The CLI importer automatically ignores that exact filename.

## Resume / rerun behavior

The database ingestor checks the full observation identity before insertion. Re-running the same normalized batches is duplicate-safe: already imported observations are skipped and reported as duplicates rather than inserted again.

## Import limits

Configured in `config/observation_imports.php`:

- ZIP: 100 MB compressed maximum;
- up to 1000 JSON batch files;
- 5 MB maximum per JSON file;
- 512 MB maximum total expanded JSON data;
- maximum 500 observations per JSON file through payload validation.

# Read-only API v1

Base path:

```text
/api/v1
```

All API endpoints are read-only. Observation importing remains CLI-only.

## Authentication

Each consuming application/environment receives a permanent bearer token. Tokens are valid until explicitly revoked.

Create a token:

```bash
php artisan api:token:create "Cabek Production"
```

The plaintext token is displayed **once**. Store it in the consuming application's secret/environment configuration.

List clients/tokens:

```bash
php artisan api:token:list
```

Revoke a token:

```bash
php artisan api:token:revoke <id>
```

Requests authenticate with:

```http
Authorization: Bearer <token>
Accept: application/json
```

Only a SHA-256 hash of the token is stored in the AutoEstimate database.

## Rate limiting

The API is rate limited per authenticated API client/token.

Default:

```text
120 requests/minute
```

Override with:

```env
AUTOESTIMATE_API_RATE_LIMIT=120
```

## Endpoints

### Marques

```http
GET /api/v1/marques
```

Returns all marques without pagination.

### Models

```http
GET /api/v1/models
```

Returns all models with pagination.

Optional pagination parameters:

```text
page
per_page (1-100, default 50)
```

Filtering by marque disables pagination and returns all models for that marque:

```http
GET /api/v1/models?marque_id=1
```

### Parts

```http
GET /api/v1/parts
```

Returns all canonical parts without pagination.

Optionally return only parts having observations for one model:

```http
GET /api/v1/parts?model_id=10
```

### Sources

```http
GET /api/v1/sources
```

Returns all pricing sources without pagination.

### Estimation

```http
GET /api/v1/estimation
```

Required parameters:

```text
marque_id
model_id
part_id
type = original | adaptable | occasion
```

Optional parameters:

```text
year
source_id
```

Example:

```http
GET /api/v1/estimation?marque_id=1&model_id=10&part_id=7&type=adaptable&year=2018&source_id=3
```

A valid request with matching observations returns:

```json
{
  "data": {
    "marque": {"id": 1, "libelle": "Renault"},
    "model": {"id": 10, "libelle": "Clio"},
    "part": {"id": 7, "libelle": "Pare-choc Avant"},
    "type": "adaptable",
    "year": 2018,
    "source": {"id": 3, "libelle": "AtlasAuto"},
    "estimation": {
      "median": 850,
      "average": 920.5,
      "minimum": 650,
      "maximum": 1400,
      "observation_count": 6
    }
  }
}
```

No fixed `estimated_price` field is returned. The API exposes the calculated statistics from the matching observations.

If the request is valid but no observations match:

```json
{
  "data": null
}
```

with HTTP `200`.

### Observations

```http
GET /api/v1/observations
```

This endpoint is always paginated.

Optional filters:

```text
marque_id
model_id
part_id
type
year
source_id
page
per_page (1-100, default 50)
```

Observations are ordered by `observed_at DESC`, then `id DESC`.

## Production-year matching

When `year` is not supplied, production years do not filter observations.

When `year` is supplied, compatible observations follow half-open interval semantics:

```text
production_from <= year
AND
(production_to IS NULL OR year < production_to)
```

A missing lower bound is treated as open. A missing upper bound is treated as open. An observation with both `production_from = NULL` and `production_to = NULL` is excluded from a year-specific request because it does not establish compatibility with that year.

The critical rule is:

```text
production_from = inclusive
production_to   = exclusive
```

Therefore:

```text
2014 -> 2018 = 2014, 2015, 2016, 2017
2018 -> 2022 = 2018, 2019, 2020, 2021
```

A 2018 vehicle matches only the second range.

## API response conventions

Normal collections:

```json
{"data": []}
```

Paginated collections:

```json
{
  "data": [],
  "meta": {
    "current_page": 1,
    "per_page": 50,
    "last_page": 4,
    "total": 173
  }
}
```

Important status codes:

```text
200 successful request
200 valid estimation request with no matching data (data = null)
401 missing, invalid, or revoked API token
422 invalid query parameters or inconsistent marque/model relationship
429 API rate limit exceeded
500 unexpected server error
```

# Internal web application

Internal web tools expose:

- Statistics
- Price Explorer

The Price Explorer uses the same exclusive `production_to` year-boundary semantics as API v1.

Observation importing is deliberately **not available** under `/internal` and no HTTP ingestion endpoint is exposed.

# Moving the finished database to the server

Once the local dataset is verified, transfer the database using the database engine's normal dump/restore tooling rather than rebuilding the dataset through the production web application.

For MySQL/MariaDB:

```bash
mysqldump -u <user> -p <database> > cabek_autoestimate.sql
```

Restore on the server:

```bash
mysql -u <user> -p <database> < cabek_autoestimate.sql
```

Keep the local importer available as an administrative dataset-construction/recovery tool. It is not part of the production HTTP API.

# Tests

```bash
composer test
```

or:

```bash
php artisan test
```
