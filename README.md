# Cabek AutoEstimate Backend

Laravel backend for Cabek AutoEstimate's normalized car-part pricing database.

The production-facing application is intentionally focused on **querying and analysing price observations**. Data ingestion is an internal/local CLI workflow and is not exposed through a browser or HTTP API.

## Local data workflow

```text
Scrapers
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

## Setup

```bash
composer install
cp .env.example .env
php artisan key:generate
php artisan migrate
php artisan db:seed
```

Configure the local database connection in `.env` before importing observations.

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

This is useful in scripts after a successful dry run.

## JSON audit report

Write a machine-readable summary:

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

The database ingestor checks the full observation identity before insertion. Re-running the same normalized batches is therefore duplicate-safe: already imported observations are skipped and reported as duplicates rather than inserted again.

This is useful if a long import is interrupted after some files have already completed.

## Import limits

Configured in `config/observation_imports.php`:

- ZIP: 100 MB compressed maximum;
- up to 1000 JSON batch files;
- 5 MB maximum per JSON file;
- 512 MB maximum total expanded JSON data;
- maximum 500 observations per JSON file through payload validation.

## Web application

Internal web tools currently expose:

- Statistics
- Price Explorer

Observation importing is deliberately **not available** under `/internal` and no `/api/ingest` endpoint is exposed.

# Moving the finished database to the server

Once the local dataset is verified, transfer the database using the database engine's normal dump/restore tooling rather than rebuilding the dataset through the production web application.

For MySQL/MariaDB, for example:

```bash
mysqldump -u <user> -p <database> > cabek_autoestimate.sql
```

Restore on the server:

```bash
mysql -u <user> -p <database> < cabek_autoestimate.sql
```

Keep the local importer available for future refresh cycles such as:

```text
new scrape
→ normalization
→ dry-run
→ local import
→ verification
→ controlled database update
```

# Tests

```bash
composer test
```

or:

```bash
php artisan test
```
