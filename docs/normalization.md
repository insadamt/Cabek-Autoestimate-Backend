# Normalization

AI normalization converts raw scraped automotive-part data into the normalized observation format expected by AutoEstimate.

The canonical instruction set is versioned in the repository:

[`normalization/Data_Normalization_instructions_v2.1.6.md`](../normalization/Data_Normalization_instructions_v2.1.6.md)

That file is the operational instruction source for normalization. This document explains where normalization fits in the system; it does not replace or duplicate the detailed rules in the instruction file.

## Position in the data pipeline

```text
Raw scraped JSON / ZIP
        ↓
AI normalization
        ↓
normalized canonical observations
        ├── standard import batches
        ├── new-marque files
        ├── new-model files
        ├── non-canonical-part files
        └── normalization_review.json
```

The normalized standard batches are then validated and loaded through the AutoEstimate importer.

## Inputs

Normalization accepts raw scraper output in JSON or ZIP form. Raw data can contain inconsistent vehicle names, generation notation, part terminology, prices, type/condition wording, compatibility years, URLs, references, and scraper failures.

The instruction set requires normalization to use the complete available product evidence together with the project reference data rather than relying on one raw field in isolation.

## Canonical observation output

Standard import batches use this root shape:

```json
{
  "observations": []
}
```

A normalized observation uses these fields:

```text
marque
model
part
production_from
production_to
type
price
source
source_reference
source_url
observed_at
```

Database IDs are not produced by normalization; the backend resolves canonical entities during import.

## Important normalization behaviors

The current instruction version includes rules for:

- canonical marque/model/part/source mapping;
- marque/model relationship validation;
- generation and production-period interpretation;
- part anti-contamination rules so sub-components are not forced into complete assemblies;
- `original`, `adaptable`, and `occasion` normalization;
- source-specific original/adaptable policy for project websites;
- price normalization to final TTC MAD values;
- scraper-error recovery and re-scraping when recoverable;
- same-run exact deduplication;
- pending and rejected review output;
- detection and preservation of new marques and models;
- preservation of legitimate non-canonical automotive parts, including sub-parts, interior, mechanical, electrical/electronic, accessories, and other categories.

## New marques

A high-confidence new marque is preserved in its own output:

```text
new_marque_<marque>.json
```

If both marque and model are new, the observations remain in the new-marque file and the new model is also reported in the review output.

## New models

A high-confidence new model under an existing canonical marque is preserved independently:

```text
new_model_<marque>_<model>.json
```

One new model is kept per file.

## Non-canonical parts

Legitimate automotive parts that do not map to the canonical part reference are preserved separately rather than forced into broader assemblies or discarded.

Typical output:

```text
non_canonical_parts.json
```

or split batches when required by the normal file/count limits.

## Review output

Normalization uses `normalization_review.json` to preserve information that needs review, including pending/rejected observations and detected new entities.

This review artifact is separate from standard import batches.

## Scraper-error recovery

The instruction set treats recoverable scraper failures as recovery work. When enough identifying information exists, normalization attempts to locate the original source page, recover the product data, and then validate the recovered observation under the same normalization rules.

Recovered pricing must remain associated with the original commercial source; a price from a different retailer cannot be relabeled as the failed source.

## File limits and delivery

Normal output files follow the importer limits of at most 500 observations and 5 MB per JSON file.

The instruction set also defines delivery rules for standard import batches, new-marque files, new-model files, non-canonical-part files, and the separate review file.

## Updating the instruction set

When normalization rules change:

1. update the canonical instruction file;
2. increment its version;
3. keep this documentation link pointed at the active version;
4. update importer/API documentation only when the data contract or runtime behavior changes.
