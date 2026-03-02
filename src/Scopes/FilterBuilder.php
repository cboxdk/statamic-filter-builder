<?php

namespace Cbox\FilterBuilder\Scopes;

use Cbox\FilterBuilder\VariableParser;
use Statamic\Facades\Blink;
use Statamic\Facades\Cascade;
use Statamic\Facades\Collection;
use Statamic\Fields\Field;
use Statamic\Query\Scopes\Scope;
use Statamic\Support\Arr;
use Statamic\Support\Str;

class FilterBuilder extends Scope
{
    /**
     * @param  \Statamic\Contracts\Query\Builder  $query
     * @param  array<string, mixed>  $values
     */
    public function apply($query, $values): void
    {
        $from = $values['from'] ?? '';

        if (! is_string($from) || $from === '') {
            return;
        }

        $fields = $this->fields(explode('|', $from));
        $filters = $values['filter_builder'] ?? [];

        if (! is_array($filters)) {
            return;
        }

        /** @var array<int, array<string, mixed>> $filters */
        foreach ($filters as $filter) {
            /** @phpstan-ignore offsetAccess.nonOffsetAccessible */
            if (! isset($filter['handle'], $filter['values']['operator'])) {
                continue;
            }

            /** @var string $handle */
            $handle = $filter['handle'];

            if (! $fields->has($handle)) {
                continue;
            }

            /** @var array{operator: string, values?: array<int, mixed>, variables?: array<int, string>} $filterValues_ */
            $filterValues_ = $filter['values'];
            $operator = $filterValues_['operator'];
            /** @var array<int, mixed> $filterValues */
            $filterValues = $filterValues_['values'] ?? [];
            /** @var array<int, string> $variables */
            $variables = $filterValues_['variables'] ?? [];

            /** @var Field $field */
            $field = $fields[$handle];

            $json = $field->isRelationship() && $field->get('max_items', 0) !== 1;

            /** @var array<string, mixed> $cascade */
            $cascade = Cascade::toArray();
            foreach ($variables as $variable) {
                if (! $parsed = VariableParser::parse($variable, $cascade)) {
                    continue;
                }

                $filterValues = array_merge($filterValues, $parsed);
            }

            // If we have no values, ignore the filter
            if (! $filterValues) {
                continue;
            }

            /** @phpstan-ignore argument.type */
            $query->where(function ($query) use ($json, $handle, $operator, $filterValues): void {
                foreach ($filterValues as $i => $value) {
                    if ($json) {
                        $method = $operator === '='
                            ? ($i ? 'orWhereJsonContains' : 'whereJsonContains')
                            : ($i ? 'orWhereJsonDoesntContain' : 'whereJsonDoesntContain');
                        $query->{$method}($handle, $value);
                    } else {
                        if ($operator === 'like') {
                            $value = Str::ensureLeft($value, '%');
                            $value = Str::ensureRight($value, '%');
                        }
                        $method = $i ? 'orWhere' : 'where';
                        $query->{$method}($handle, $operator, $value);
                    }
                }
            });
        }
    }

    /**
     * @param  array<int, string>  $collections
     * @return \Illuminate\Support\Collection<string, Field>
     */
    protected function fields(array $collections): \Illuminate\Support\Collection
    {
        $key = 'filter-builder.scope-fields.'.implode('|', $collections);

        if (Blink::has($key)) {
            /** @phpstan-ignore return.type */
            return Blink::get($key);
        }

        $collectionList = collect(Arr::wrap($collections));

        $groups = $collectionList
            ->mapWithKeys(function (string $collection): array {
                $col = Collection::findByHandle($collection);

                $fields = $col
                    ? $col->entryBlueprints()
                        ->flatMap(function ($blueprint) {
                            return $blueprint
                                ->fields()
                                ->all()
                                ->filter->isFilterable();
                        })
                    : collect();

                return [$collection => $fields];
            });

        // Only include fields common to all collections
        $handles = $groups
            ->flatMap(fn ($fields) => $fields->keys())
            ->unique();
        foreach ($groups as $fields) {
            $handles = $handles->intersect($fields->keys());
        }

        $fields = $groups
            ->flatMap(fn ($fields) => $fields)
            ->only($handles)
            ->merge([
                'id' => new Field('id', [
                    'display' => 'ID',
                    'type' => 'text',
                ]),
            ]);

        Blink::put($key, $fields);

        /** @phpstan-ignore return.type */
        return $fields;
    }
}
