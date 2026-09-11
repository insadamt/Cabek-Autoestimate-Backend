# API v1

## Base path

```text
/api/v1
```

AutoEstimate API v1 exposes pricing/reference data to Cabek applications and services.

## Authentication

Send the application token as a bearer token:

```http
Authorization: Bearer <token>
Accept: application/json
```

Missing, invalid, or revoked tokens return HTTP `401`:

```json
{
  "message": "Unauthenticated."
}
```

See [Authentication](authentication.md) for token creation, revocation, storage, and rate limiting.

## Rate limiting

Requests are rate limited per authenticated API client.

Default:

```text
120 requests/minute
```

Configuration:

```env
AUTOESTIMATE_API_RATE_LIMIT=120
```

HTTP `429` is returned when the limit is exceeded.

## Response conventions

Non-paginated collection:

```json
{
  "data": []
}
```

Paginated collection:

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

A valid estimation query with no matching observations returns HTTP `200`:

```json
{
  "data": null
}
```

Invalid query parameters return HTTP `422` using Laravel's JSON validation-error format.

## `GET /marques`

Returns all marques ordered by `libelle`.

```http
GET /api/v1/marques
```

Example:

```json
{
  "data": [
    {
      "id": 1,
      "libelle": "Renault"
    }
  ]
}
```

Pagination: **no**.

## `GET /models`

Returns vehicle models.

### Global listing

```http
GET /api/v1/models
```

The global listing is paginated.

| Parameter | Type | Default | Rules |
|---|---:|---:|---|
| `page` | integer | `1` | positive page number |
| `per_page` | integer | `50` | `1-100` |

Example:

```http
GET /api/v1/models?page=2&per_page=50
```

### Models for one marque

```http
GET /api/v1/models?marque_id=1
```

When `marque_id` is supplied, all models for that marque are returned without pagination.

Example:

```json
{
  "data": [
    {
      "id": 10,
      "marque_id": 1,
      "libelle": "Clio"
    }
  ]
}
```

## `GET /parts`

Returns canonical parts ordered by `libelle`.

### All parts

```http
GET /api/v1/parts
```

Pagination: **no**.

### Parts available for one model

```http
GET /api/v1/parts?model_id=10
```

When `model_id` is supplied, only parts having at least one price observation for that model are returned.

Example:

```json
{
  "data": [
    {
      "id": 7,
      "libelle": "Pare-choc Avant"
    }
  ]
}
```

## `GET /sources`

Returns all pricing sources ordered by `libelle`.

```http
GET /api/v1/sources
```

Pagination: **no**.

Example:

```json
{
  "data": [
    {
      "id": 3,
      "libelle": "AtlasAuto"
    }
  ]
}
```

## `GET /estimation`

Calculates statistics over observations matching one vehicle/part/type selection.

```http
GET /api/v1/estimation
```

### Required parameters

| Parameter | Type | Description |
|---|---|---|
| `marque_id` | integer | Existing marque ID |
| `model_id` | integer | Existing model ID belonging to the selected marque |
| `part_id` | integer | Existing canonical part ID |
| `type` | string | `original`, `adaptable`, or `occasion` |

### Optional parameters

| Parameter | Type | Description |
|---|---|---|
| `year` | integer | Vehicle year used for compatibility filtering |
| `source_id` | integer | Restrict statistics to one source |

Example:

```http
GET /api/v1/estimation?marque_id=1&model_id=10&part_id=7&type=adaptable&year=2018&source_id=3
```

Example response:

```json
{
  "data": {
    "marque": {
      "id": 1,
      "libelle": "Renault"
    },
    "model": {
      "id": 10,
      "libelle": "Clio"
    },
    "part": {
      "id": 7,
      "libelle": "Pare-choc Avant"
    },
    "type": "adaptable",
    "year": 2018,
    "source": {
      "id": 3,
      "libelle": "AtlasAuto"
    },
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

If `source_id` is omitted, `source` is `null` and matching observations from all sources may contribute.

The endpoint returns statistics only; it does not add a separate `estimated_price` field.

## `GET /observations`

Returns normalized pricing observations.

```http
GET /api/v1/observations
```

This endpoint is always paginated.

| Parameter | Type | Description |
|---|---|---|
| `marque_id` | integer | Restrict to models belonging to a marque |
| `model_id` | integer | Restrict to one model |
| `part_id` | integer | Restrict to one part |
| `type` | string | `original`, `adaptable`, or `occasion` |
| `year` | integer | Apply production-year compatibility filtering |
| `source_id` | integer | Restrict to one source |
| `page` | integer | Page number |
| `per_page` | integer | `1-100`, default `50` |

Observations are ordered by:

```text
observed_at DESC
id DESC
```

Example:

```http
GET /api/v1/observations?model_id=10&part_id=7&type=original&year=2018&source_id=2&page=1&per_page=50
```

## Production-year matching

When `year` is omitted, production-year fields do not filter observations.

When `year` is present:

```text
(production_from IS NULL OR production_from <= year)
AND
(production_to IS NULL OR year < production_to)
AND
NOT (production_from IS NULL AND production_to IS NULL)
```

`production_from` is inclusive and `production_to` is exclusive.

```text
2014 → 2018 = 2014, 2015, 2016, 2017
2018 → 2022 = 2018, 2019, 2020, 2021
```

For year `2018`, only the second range matches.

A missing lower or upper bound is treated as open on that side. An observation with both bounds missing is excluded from a year-specific query because it does not establish compatibility with that year.

## Status codes

| Status | Meaning |
|---:|---|
| `200` | Successful request |
| `200` | Valid estimation request with no matching data (`data: null`) |
| `401` | Missing, invalid, or revoked API token |
| `422` | Invalid query parameters or inconsistent marque/model selection |
| `429` | Rate limit exceeded |
| `500` | Unexpected server error |
