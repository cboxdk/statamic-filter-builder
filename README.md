# Filter Builder for Statamic

A Statamic addon that lets you build dynamic collection filters and sort orders through the control panel. Define which fields can be filtered, set operators and values (including dynamic Antlers variables), and apply them to your collection tags — all without writing custom query scopes.

## The Problem

You want editors to control which entries appear in a listing — filtering by category, date range, or status — without hardcoding query parameters in templates or writing custom scopes for every use case.

## The Solution

Add the `filter_builder` fieldtype to your blueprint, select which collections it applies to, and editors get a visual filter builder in the CP:

```yaml
- handle: filters
  field:
    type: filter_builder
    mode: config
    collections:
      - articles
```

Then use it in your templates:

```antlers
{{ collection:articles :filter_builder="filters" }}
    <h2>{{ title }}</h2>
{{ /collection:articles }}
```

### Site Builder Pattern

Use **field mode** to let editors choose both the collection and its filters dynamically — ideal for reusable listing components:

```yaml
- handle: collections
  field:
    type: collections
- handle: filters
  field:
    type: filter_builder
    mode: field
    field: collections
- handle: sorting
  field:
    type: sort_builder
    mode: field
    field: collections
```

```antlers
{{ collection :from="collections" :filter_builder="filters" :sort="sorting" }}
    <h2>{{ title }}</h2>
{{ /collection }}
```

## Features

- **Filter Builder fieldtype** — visual UI for building collection filters with field-aware operators
- **Sort Builder fieldtype** — companion field for defining sort orders
- **Dynamic variables** — reference Antlers cascade values in filter conditions
- **Relationship support** — filter by entries, terms, and users fields
- **Auto-scope injection** — automatically adds the query scope when `filter_builder` param is present

## Quick Start

```bash
composer require cboxdk/statamic-filter-builder
```

No config files, no publishing, no migrations.

## Documentation

See [DOCUMENTATION.md](DOCUMENTATION.md) for full setup guide, field type reference, template usage, and configuration options.

## Requirements

- PHP 8.2+
- Statamic 6.x
- Laravel 12+

## Development

```bash
composer check    # Pint + PHPStan level 9 + Pest
```

## License

MIT

## Credits

Originally designed and developed by [Sylvester Damgaard](https://github.com/cboxdk) while working at [TV2 Regionerne](https://github.com/tv2regionerne/statamic-filter-builder). Now maintained and actively developed under [Cbox](https://github.com/cboxdk) with a full rewrite for Statamic 6, Laravel 12, and Vue 3.
