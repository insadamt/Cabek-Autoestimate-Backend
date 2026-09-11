# Architecture

## Service role

AutoEstimate is an independent service in Cabek's software ecosystem. Its responsibility is to maintain a normalized automotive part-pricing dataset and make that dataset available to other Cabek applications and services through a stable API.

AutoEstimate has four main components:

1. a normalized relational pricing database;
2. a read-only API for consuming pricing data;
3. internal statistics and price-exploration tools;
4. a CLI importer for loading normalized datasets.

Scrapers and the AI normalization process are upstream components of the dataset pipeline rather than runtime dependencies of the API.

## Data pipeline

```text
Pieces Auto Maroc / AtlasAuto / CarClic / RT Maroc / legacy data
                              ↓
                    scraping / extraction
                              ↓
                          raw JSON
                              ↓
                      AI normalization
                              ↓
                      normalized JSON
                              ↓
                    AutoEstimate importer
                              ↓
                    AutoEstimate database
                              ↓
             ┌────────────────┴────────────────┐
             ↓                                 ↓
       read-only API                    internal tools
             ↓
    Cabek applications and services
```

The AI normalization layer is governed by the versioned instruction file documented in [Normalization](normalization.md).

## API layer

API v1 is registered under:

```text
/api/v1
```

Resources:

```text
/marques
/models
/parts
/sources
/estimation
/observations
```

Every API request passes through application-token authentication and the AutoEstimate API rate limiter.

The API does not own a user-facing login model. Credentials identify consuming applications or environments such as a production service, staging service, or internal integration.

## Pricing lookup flow

A typical estimation query follows this path:

```text
marque_id
   ↓ validate marque/model relationship
model_id
   ↓
part_id
   ↓
type
   ↓
year (optional)
   ↓
source_id (optional)
   ↓
matching observations
   ↓
median / average / minimum / maximum / count
```

No single `estimated_price` field is generated. The API returns the statistics calculated from the matching observations.

## Production-year compatibility

When a year is supplied, production ranges use half-open interval semantics:

```text
[from, to)
```

`production_from` is inclusive and `production_to` is exclusive.

```text
2014 → 2018 = 2014, 2015, 2016, 2017
2018 → 2022 = 2018, 2019, 2020, 2021
```

The runtime rule is:

```text
(production_from IS NULL OR production_from <= year)
AND
(production_to IS NULL OR year < production_to)
AND
NOT (production_from IS NULL AND production_to IS NULL)
```

If no year is supplied, production-year fields do not filter observations.

The internal Price Explorer and API use the same compatibility rule.

## Main code areas

```text
app/
├── Http/
│   ├── Controllers/Api/V1/      API endpoints
│   ├── Controllers/Internal/    internal statistics and price explorer
│   ├── Middleware/              API-client authentication
│   └── Requests/Api/V1/         API validation
├── Models/                      Eloquent models
└── Services/
    ├── Pricing/                 pricing lookup/statistics
    └── ...                      import validation and ingestion

routes/
├── api.php                      API v1
├── web.php                      internal web tools
└── console.php                  API-token commands

database/
├── migrations/                  schema
└── seeders/                     canonical reference data

normalization/
└── Data_Normalization_instructions_v2.1.6.md
```

## Dataset construction

Website data is collected, normalized, validated, and imported to build the AutoEstimate dataset. The current workflow does not require recurring scraper execution as part of the production API runtime.

The importer remains available whenever a normalized dataset needs to be loaded or validated.
