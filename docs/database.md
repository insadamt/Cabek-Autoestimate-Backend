# Database

## Core pricing model

AutoEstimate stores normalized pricing data around five core entities:

```text
marques
   ↓ 1:N
models
   ↓ 1:N through observations
part_price_observations
   ↑ N:1                 ↑ N:1
parts                  sources
```

API credentials are stored separately in `api_clients`.

## `marques`

Vehicle manufacturers.

Important fields:

```text
id
libelle
```

Relationship:

```text
marque 1 → N models
```

## `models`

Canonical vehicle models.

Important fields:

```text
id
marque_id
libelle
```

Each model belongs to one marque.

## `parts`

Canonical normalized part names.

Important fields:

```text
id
libelle
```

A part may have observations for many models, sources, market types, and production ranges.

## `sources`

Pricing-data sources.

Important fields:

```text
id
libelle
url
```

## `part_price_observations`

The central pricing fact table.

| Field | Meaning |
|---|---|
| `id` | Observation ID |
| `model_id` | Canonical vehicle model |
| `part_id` | Canonical part |
| `source_id` | Observation source |
| `production_from` | Optional inclusive lower compatibility bound |
| `production_to` | Optional exclusive upper compatibility bound at query time |
| `type` | `original`, `adaptable`, or `occasion` |
| `price` | Positive price value |
| `source_reference` | Optional source-side product/reference identifier |
| `source_url` | Optional source URL |
| `observed_at` | Time the price was observed |
| `created_at` | Database insertion timestamp |

The application model does not use an `updated_at` column for observations.

### Type values

```text
original
adaptable
occasion
```

### Production-year semantics

Runtime compatibility uses half-open ranges:

```text
[production_from, production_to)
```

Example:

```text
production_from = 2018
production_to   = 2022
```

matches 2018, 2019, 2020, and 2021.

A `NULL` lower or upper bound is open on that side. When both bounds are `NULL`, the observation remains valid data but is excluded from a year-specific query because it does not establish year compatibility.

### Main observation indexes

The schema includes indexes covering common lookup paths such as:

```text
(model_id, part_id)
(model_id, part_id, observed_at)
source_id
observed_at
(model_id, part_id, production_from, production_to)
```

## `api_clients`

Represents applications/environments authorized to call the API.

| Field | Meaning |
|---|---|
| `id` | API-client ID |
| `name` | Unique client name |
| `token_hash` | SHA-256 hash of the bearer token |
| `is_active` | Whether requests using the token are accepted |
| `last_used_at` | Last successful authenticated request |
| `created_at` / `updated_at` | Administrative timestamps |

See [Authentication](authentication.md).

## Import tracking

The importer uses two tracking tables.

### `observation_imports`

Tracks an import operation and aggregate progress/counts.

Important fields include:

```text
status
total_files
validated_files
processed_files
total_observations
processed_observations
inserted_observations
duplicate_observations
failed_files
started_at
finished_at
```

### `observation_import_files`

Tracks each file inside an import, including its position, status, observation counts, duplicate/insert counts, errors, and processing timestamps.

## Relationship summary

```text
Marque
  └── has many VehicleModel

VehicleModel
  ├── belongs to Marque
  └── has many PartPriceObservation

Part
  └── has many PartPriceObservation

Source
  └── has many PartPriceObservation

PartPriceObservation
  ├── belongs to VehicleModel
  ├── belongs to Part
  └── belongs to Source
```
