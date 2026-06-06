# Dimensions and Multi-Site

Neos supports multiple content dimensions, most commonly used for:

- **Language** (en, de, fr, ...)
- **Market/Region** (us, eu, apac, ...)

## Dimension Space Points

A Dimension Space Point (DSP) is a specific coordinate in the dimension space,
e.g., `{"language": "en"}` or `{"language": "de", "market": "eu"}`.

## Node Variants

A single NodeAggregate can have variants at multiple dimension space points.
Each variant may have different property values (e.g., different titles per
language) but shares the same NodeType and child structure.

The `dimensionSpacePoint` in a NodeAddress selects which variant to operate on.

## Shadowing / Fallback

When a variant doesn't exist for a given DSP, Neos can "shadow" (fall back to)
a variant from another DSP. This is common for untranslated content — the
German page may shadow the English version.

Use `list_dimensions` to see configured dimensions and their values.
Use `list_dimension_combinations` to see all valid DSPs.
