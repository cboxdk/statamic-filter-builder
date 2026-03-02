<?php

namespace Cbox\FilterBuilder\Fieldtypes\Concerns;

use Facades\Statamic\Fieldtypes\RowId;
use Statamic\Facades\Blink;
use Statamic\Facades\Collection;
use Statamic\Fields\Field;
use Statamic\Fields\Fields;
use Statamic\Support\Arr;

trait UsesFields
{
    /** @var list<string> */
    protected array $singleTypes = [
        'toggle',
        'date',
    ];

    /**
     * @param  array<int, array<string, mixed>>  $data
     * @return array<int, array<string, mixed>>
     */
    public function process($data)
    {
        return collect($data)->map(function (array $item): array {
            $item['id'] = $item['id'] ?? RowId::generate();
            /** @phpstan-ignore argument.type */
            $fields = $this->getItemFields($item);
            /** @var array<string, mixed> $values */
            $values = $item['values'];
            $values = $fields
                ->addValues($values)
                ->process()
                ->values()
                ->all();
            if ($fields->has('values') && in_array($fields->get('values')->type(), $this->singleTypes)) {
                $values['values'] = [$values['values']];
            }
            $item['values'] = $values;

            return $item;
        })->all();
    }

    /**
     * @param  array<int, array<string, mixed>>  $data
     * @return array<int, array<string, mixed>>
     */
    public function preProcess($data)
    {
        $fields = $this->getFields();

        return collect($data)
            ->filter(function (array $item) use (&$fields): bool {
                /** @phpstan-ignore argument.type */
                return $fields->has($item['handle']);
            })
            ->map(function (array $item): array {
                $item['id'] = $item['id'] ?? RowId::generate();
                /** @phpstan-ignore argument.type */
                $fields = $this->getItemFields($item);
                /** @var array<string, mixed> $values */
                $values = $item['values'];
                if ($fields->has('values') && in_array($fields->get('values')->type(), $this->singleTypes)) {
                    /** @phpstan-ignore offsetAccess.nonOffsetAccessible */
                    $values['values'] = $values['values'][0];
                }
                $values = $fields
                    ->addValues($values)
                    ->preProcess()
                    ->values()
                    ->all();
                $item['values'] = $values;

                return $item;
            })
            ->all();
    }

    /**
     * @param  array<int, array<string, mixed>>  $data
     * @return array<int, array<string, mixed>>
     */
    public function preProcessValidatable($data)
    {
        return collect($data)->map(function (array $item): array {
            /** @phpstan-ignore argument.type */
            $fields = $this->getItemFields($item);
            /** @var array<string, mixed> $values */
            $values = $item['values'];
            /** @phpstan-ignore argument.type */
            $processed = $fields
                ->addValues($item['values'] ?? [])
                ->preProcessValidatables()
                ->values()
                ->all();
            /** @phpstan-ignore argument.type */
            $item['values'] = array_merge($values, $processed);

            return $item;
        })->all();
    }

    /**
     * @return array<string, mixed>
     */
    public function extraRules(): array
    {
        /** @phpstan-ignore argument.templateType, argument.templateType */
        return collect($this->field->value())->map(function (array $item, int $index): array {
            $prefix = $this->field->handle().'.'.$index.'.values';
            /** @phpstan-ignore argument.type */
            $fields = $this->getItemFields($item);
            /** @phpstan-ignore argument.type */
            $values = $item['values'] ?? [];
            $rules = $fields
                ->addValues($values)
                ->validator()
                ->withContext([
                    'prefix' => $this->field->validationContext('prefix').$prefix.'.',
                ])
                ->rules();

            return collect($rules)
                ->mapWithKeys(function (mixed $rules, string $handle) use ($prefix): array {
                    return [$prefix.'.'.$handle => $rules];
                })->all();
        })->reduce(function ($carry, $rules) {
            return $carry->merge($rules);
        }, collect())->all();
    }

    /**
     * @return array<string, string>
     */
    public function extraValidationAttributes(): array
    {
        /** @phpstan-ignore argument.templateType, argument.templateType */
        return collect($this->field->value())->map(function (array $item, int $index): array {
            $prefix = $this->field->handle().'.'.$index.'.values';
            /** @phpstan-ignore argument.type */
            $fields = $this->getItemFields($item);
            /** @phpstan-ignore argument.type */
            $values = $item['values'] ?? [];
            $attributes = $fields
                ->addValues($values)
                ->validator()
                ->attributes();

            return collect($attributes)
                ->mapWithKeys(function (mixed $rules, string $handle) use ($prefix): array {
                    return [$prefix.'.'.$handle => $rules];
                })->all();
        })->reduce(function ($carry, $attributes) {
            return $carry->merge($attributes);
        }, collect())->all();
    }

    /**
     * @return array<string, mixed>
     */
    public function preload(): array
    {
        $fields = $this->getFields();

        /** @phpstan-ignore argument.templateType, argument.templateType */
        $existing = collect($this->field->value())
            ->filter(function (array $item) use (&$fields): bool {
                /** @phpstan-ignore argument.type */
                return $fields->has($item['handle']);
            })
            ->mapWithKeys(function (array $item): array {
                /** @phpstan-ignore argument.type */
                return [$item['id'] => $this->getItemFields($item)->addValues($item['values'] ?? [])->meta()];
            })
            ->toArray();

        /** @phpstan-ignore argument.templateType, argument.templateType */
        $defaults = $this->getFields()->map(function (mixed $field): array {
            /** @var Field $field */
            return $this->getFieldFields($field)->all()->map(function (Field $innerField): mixed {
                return $innerField->fieldtype()->preProcess($innerField->defaultValue());
            })->all();
        })->all();

        /** @phpstan-ignore argument.templateType, argument.templateType */
        $new = $this->getFields()->map(function (mixed $field, string $handle) use ($defaults): mixed {
            /** @var Field $field */
            return $this->getFieldFields($field)->addValues($defaults[$handle])->meta();
        })->toArray();

        /** @phpstan-ignore argument.type */
        $previews = collect($existing)->map(function (mixed $fields): array {
            /** @var array<string, mixed> $fields */
            return collect($fields)->map(function (): null {
                return null;
            })->all();
        })->all();

        /** @phpstan-ignore argument.templateType, argument.templateType */
        $publishFields = $fields->map(function (mixed $field): array {
            /** @var Field $field */
            return [
                'handle' => $field->handle(),
                'display' => $field->display(),
                'type' => $field->type(),
                'fields' => $this->getFieldFields($field)->toPublishArray(),
            ];
        })->values();

        return [
            'fields' => $publishFields,
            'existing' => $existing,
            'new' => $new,
            'defaults' => $defaults,
            'previews' => $previews,
        ];
    }

    /**
     * @return \Illuminate\Support\Collection<string, Field>
     *
     * @phpstan-ignore return.phpDocType
     */
    protected function getFields(): mixed
    {
        $collections = collect(Arr::wrap($this->getCollections()));
        if (! $collections->count()) {
            return $collections;
        }

        $key = 'filter-builder.fields.'.$collections->join('|');

        if (Blink::has($key)) {
            return Blink::get($key);
        }

        $groups = $collections
            ->mapWithKeys(function (string $collection): array {
                $col = Collection::findByHandle($collection);

                $fields = $col
                    ? $col->entryBlueprints()
                        /** @phpstan-ignore argument.templateType, argument.templateType */
                        ->flatMap(function ($blueprint) {
                            return $blueprint
                                ->fields()
                                ->all();
                        })
                    : collect();

                return [$collection => $fields];
            });

        $handles = $groups
            ->flatMap(fn ($fields) => $fields->keys())
            ->unique();
        foreach ($groups as $fields) {
            $handles = $handles->intersect($fields->keys());
        }

        /** @phpstan-ignore argument.type */
        $fields = $groups
            ->flatMap(fn ($fields) => $fields)
            ->only($handles)
            ->merge([
                'id' => new Field('id', [
                    'display' => 'ID',
                    'type' => 'text',
                ]),
            ])
            /** @phpstan-ignore argument.type */
            ->sort(function (mixed $a, mixed $b): int {
                /** @var Field $a */
                /** @var Field $b */
                return $a->display() <=> $b->display();
            });

        Blink::put($key, $fields);

        return $fields;
    }

    /**
     * @param  array{handle: string, values?: array<string, mixed>}  $item
     */
    protected function getItemFields(array $item): Fields
    {
        /** @phpstan-ignore argument.type */
        return $this->getFieldFields($this->getFields()[$item['handle']]);
    }

    /**
     * @return mixed
     */
    protected function getCollections()
    {
        if ($this->config('mode', 'config') === 'config') {
            return $this->config('collections');
        }

        $key = $this->field->fieldPathKeys();

        array_splice($key, -1, 1, [$this->config('field')]);
        $key = implode('.', $key);

        // We have to do this because the collection fields value may have changed
        // but the parent object has not yet been updated with the new value
        // We only want to do this during save requests, not publish requests, so
        // we check for the presence of the _blueprint key as well
        $post = request()->post();
        if (isset($post['_blueprint'])) {
            return data_get($post, $key);
        }

        // We have to check this because when a new entry is created the field
        // parent wont yet be an entry object, it'll be the collection object
        $parent = $this->field->parent();
        /** @phpstan-ignore method.nonObject */
        if (method_exists($parent, 'data')) {
            return data_get($parent->data(), $key) ?? [];
        }

        return [];
    }
}
