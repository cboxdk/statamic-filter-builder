# Filter Builder Documentation

## How It Works

The Filter Builder addon provides two fieldtypes — **Filter Builder** and **Sort Builder** — that let editors build dynamic query filters and sort orders through the Statamic control panel.

When a collection tag includes a `:filter_builder` parameter, the addon automatically registers a query scope that applies the configured filters to the collection query. No manual scope wiring required.

## Setup

### 1. Install the addon

```bash
composer require cboxdk/statamic-filter-builder
```

### 2. Add fields to your blueprint

Add a `filter_builder` field (and optionally a `sort_builder` field) to the blueprint of the entry that controls the listing:

```yaml
fields:
  - handle: filters
    field:
      type: filter_builder
      mode: config
      collections:
        - articles
  - handle: sorting
    field:
      type: sort_builder
      mode: config
      collections:
        - articles
```

### 3. Use in templates

```antlers
{{ collection:articles :filter_builder="filters" :sort="sorting" }}
    <h2>{{ title }}</h2>
{{ /collection:articles }}
```

## Configuration

### Mode

Both fieldtypes support two modes for determining which collections to filter/sort:

| Mode | Description |
|------|-------------|
| `config` | Collections are selected directly in the field configuration |
| `field` | Collections are read from another field in the same blueprint |

**Config mode** (default):

```yaml
- handle: filters
  field:
    type: filter_builder
    mode: config
    collections:
      - articles
      - news
```

**Field mode** — useful when the collection selection is dynamic:

```yaml
- handle: collections
  field:
    type: collections

- handle: filters
  field:
    type: filter_builder
    mode: field
    field: collections
```

When using field mode, the filter builder reads the collection handles from the specified field and updates its available filter fields accordingly.

## Filter Builder Field

The Filter Builder presents editors with a dropdown of available fields from the configured collections. When a field is added, the appropriate operator and value inputs are shown based on the field type.

### Supported Field Types

| Field Type | Operators | Value Input |
|------------|-----------|-------------|
| Text (default) | Is, Isn't, Contains | List |
| Toggle | Is, Isn't | Toggle |
| Date | Before, After | Date picker |
| Integer / Float | =, !=, >, >=, <, <= | List |
| Entries | Is, Isn't | Entries selector |
| Terms | Is, Isn't | Terms selector |
| Users | Is, Isn't | Users selector |

An **ID** field is always available for filtering by entry ID.

### Variables

Each filter supports a **Variables** field where you can enter Antlers expressions that resolve at render time. This lets you create context-aware filters that depend on the current page or cascade values.

```
{{ page.location_id }}
{{ current_user:id }}
{{ page.categories | pluck('id') | to_json }}
```

Variables are parsed against the Antlers cascade when the collection tag renders. If a variable resolves to an empty value, the filter is skipped.

**Rules for variables:**
- Must be wrapped in `{{ }}` syntax
- Can use Antlers modifiers like `pluck`, `to_json`, `join`
- Multidimensional arrays are ignored (use `pluck` to flatten)
- Values are type-cast: `1`/`0` become booleans, date strings become Carbon instances

## Sort Builder Field

The Sort Builder lets editors define sort orders for collection queries.

### Configuration

Same `mode`/`collections`/`field` options as Filter Builder.

### Field Structure

Each sort item has:

| Field | Options |
|-------|---------|
| Direction | Ascending, Descending |

### Template Usage

The sort builder augments its value to a pipe-separated string compatible with Statamic's `:sort` parameter:

```antlers
{{ collection:articles :filter_builder="filters" :sort="sorting" }}
```

If the editor configures `title:asc` and `date:desc`, the augmented value is `title:asc|date:desc`.

## Template Usage

### Basic Collection Filtering

```antlers
{{ collection:articles :filter_builder="filters" }}
    <h2>{{ title }}</h2>
    <p>{{ content }}</p>
{{ /collection:articles }}
```

### With Sort Builder

```antlers
{{ collection:articles :filter_builder="filters" :sort="sorting" }}
    <h2>{{ title }}</h2>
{{ /collection:articles }}
```

### With Additional Parameters

You can combine the filter builder with other collection tag parameters:

```antlers
{{ collection:articles :filter_builder="filters" limit="10" paginate="true" }}
    <h2>{{ title }}</h2>
{{ /collection:articles }}
```

### Important Notes

- The filter builder uses a query scope internally. It **cannot** be combined with a manual `query_scope` parameter — the addon automatically adds `query_scope="filter_builder"` when it detects the `filter_builder` parameter.
- You **can** combine it with standard filter parameters like `limit`, `paginate`, `sort`, etc.

## Blueprint Examples

### Article Listing Page

```yaml
title: Listing
fields:
  - handle: title
    field:
      type: text

  - handle: collections
    field:
      type: collections
      display: Collections
      max_items: 3

  - handle: filters
    field:
      type: filter_builder
      display: Filters
      mode: field
      field: collections

  - handle: sorting
    field:
      type: sort_builder
      display: Sort Order
      mode: field
      field: collections
```

### Simple Filtered Section

```yaml
title: Featured Articles
fields:
  - handle: filters
    field:
      type: filter_builder
      display: Article Filters
      mode: config
      collections:
        - articles
```

## Site Builder Example

A common use case is a "site builder" pattern where editors create dynamic listing pages — choosing which collection to display, adding filters, and defining sort orders.

### Blueprint

```yaml
title: Dynamic Listing
fields:
  - handle: title
    field:
      type: text

  - handle: collections
    field:
      type: collections
      display: Collection
      max_items: 1
      instructions: Choose which collection to list

  - handle: filters
    field:
      type: filter_builder
      display: Filters
      mode: field
      field: collections

  - handle: sorting
    field:
      type: sort_builder
      display: Sort Order
      mode: field
      field: collections
```

### Template

```antlers
{{ collection :from="collections" :filter_builder="filters" :sort="sorting" limit="10" paginate="true" }}
    <article>
        <h2>{{ title }}</h2>
        <p>{{ date format="M j, Y" }}</p>
    </article>
{{ /collection }}
```

### What the Editor Sees

1. Select a collection (e.g., "Articles")
2. Add a filter: Category **is** "News"
3. Add a filter: Date **after** `{{ now }}`
4. Add a sort: Date **Descending**

The filter builder and sort builder fields update their available fields automatically when the editor changes the collection.

## Performance Notes

- **Stache-based**: All queries run against Statamic's stache (flat-file cache). No database queries are involved, making lookups fast even with many filters.
- **Relationship filters**: When filtering by relationship fields (entries, terms, users) with multiple values, the addon creates OR conditions per value within a single WHERE group. Multiple filters are AND-chained together.
- **Collection field lookups**: `Collection::findByHandle()` is a memory lookup from the stache, not a filesystem or database call.

## Edge Cases & Behavior

| Scenario | Behavior |
|----------|----------|
| Deleted field handle in saved filter | Filter is silently skipped (field not found in blueprint) |
| Deleted collection | Collection is skipped, no error thrown |
| Empty variable value | Filter is skipped (no values to match against) |
| Empty filter values array | Filter is skipped entirely |
| Malformed filter data (missing handle/operator) | Filter is skipped |
| Multidimensional array from variable | Variable is ignored (use `pluck` to flatten) |
| All sort items have invalid data | Augments to empty string, no sorting applied |

## Variable Type Casting

When Antlers variables are resolved, their values are automatically cast:

| Input Value | Cast To | Example |
|-------------|---------|---------|
| `1` or `"1"` | `true` (boolean) | Toggle fields |
| `0` or `"0"` | `false` (boolean) | Toggle fields |
| `"2024-06-15 14:30:00"` | Carbon instance | DateTime format `Y-m-d H:i:s` |
| `"2024-06-15"` | Carbon instance (start of day) | Date-only format `Y-m-d` |
| Any other value | Unchanged | Strings, arrays, etc. |

## Limitations

- **One filter_builder per collection tag**: Each collection tag can only reference one `filter_builder` field.
- **All filters are AND-chained**: There is no OR toggle between filters. Every filter narrows the result set further.
- **Cannot combine with manual query_scope**: The addon automatically injects `query_scope="filter_builder"`. If a `query_scope` is already set on the collection tag, the addon does not override it.
- **Filterable fields only**: Only fields marked as filterable in their blueprint configuration appear in the filter builder dropdown.
