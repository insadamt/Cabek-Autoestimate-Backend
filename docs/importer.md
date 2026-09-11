# Importer

The AutoEstimate importer validates and loads normalized price observations into the database.

## Command

```bash
php artisan observations:import <path>
```

Accepted input paths:

- one normalized `.json` file;
- a directory containing normalized JSON batches;
- a `.zip` archive containing normalized JSON batches.

Examples:

```bash
php artisan observations:import normalized_import_batch_001.json
php artisan observations:import ./normalized-output/
php artisan observations:import normalized-output.zip
```

## Dry run

Validate the complete input without inserting observations:

```bash
php artisan observations:import normalized-output.zip --dry-run
```

Preflight validation is performed before writes. If a batch is invalid, the package is blocked before observations are inserted.

## Non-interactive import

Skip the confirmation prompt:

```bash
php artisan observations:import normalized-output.zip --yes
```

## Audit report

Use `--report` to write a machine-readable import report.

Dry run:

```bash
php artisan observations:import normalized-output.zip \
  --dry-run \
  --report=storage/import-reports/preflight.json
```

Import:

```bash
php artisan observations:import normalized-output.zip \
  --yes \
  --report=storage/import-reports/import.json
```

Reports contain file/observation counts, inserted observations, exact duplicates, duration, and validation/import errors when applicable.

## JSON shape

The root contains only `observations`:

```json
{
  "observations": [
    {
      "marque": "Renault",
      "model": "Clio",
      "part": "Pare-choc Avant",
      "production_from": 2018,
      "production_to": 2022,
      "type": "adaptable",
      "price": 900,
      "source": "AtlasAuto",
      "source_reference": null,
      "source_url": null,
      "observed_at": "2026-09-08 12:30:00"
    }
  ]
}
```

No additional top-level keys are accepted.

## Validation

### Batch

- `observations` is required;
- it must be an array;
- it must contain at least one observation;
- maximum 500 observations per JSON file.

### Canonical references

`marque`, `model`, `part`, and `source` must exist in their canonical tables for a normal import batch.

The importer verifies that the submitted model belongs to the submitted marque.

Special normalization outputs for newly detected marques, models, or non-canonical parts must first have the corresponding reference entities approved/created before those observations can be imported as canonical data. See [Normalization](normalization.md).

### Production years

`production_from` and `production_to` are optional integers.

Accepted range:

```text
1900 → current year + 1
```

When both values exist:

```text
production_to >= production_from
```

The API uses `production_to` as an exclusive upper bound when filtering by year. See [API v1](api.md#production-year-matching).

### Type

Allowed values:

```text
original
adaptable
occasion
```

### Price

`price` is required, numeric, and greater than zero.

### Source metadata

- `source_reference`: optional string, maximum 255 characters;
- `source_url`: optional valid URL, maximum 2048 characters.

### Observation time

`observed_at` is required in:

```text
Y-m-d H:i:s
```

It cannot be in the future.

## Duplicate handling

The importer checks the complete observation identity before insertion. Exact observations already present in the database are skipped and counted as duplicates.

## Normalization review files

A normalized delivery may contain:

```text
normalized_import_batch_001.json
normalized_import_batch_002.json
normalization_review.json
```

`normalization_review.json` is review/diagnostic output rather than an observation batch. The importer ignores that exact filename.

## Limits

Configured in `config/observation_imports.php`:

| Limit | Value |
|---|---:|
| Maximum compressed ZIP | 100 MB |
| Maximum JSON files | 1000 |
| Maximum single JSON file | 5 MB |
| Maximum total expanded JSON | 512 MB |
| Maximum observations per JSON file | 500 |

## Typical workflow

```text
normalized data
      ↓
dry run
      ↓
review validation output
      ↓
import
      ↓
inspect database/statistics
```
