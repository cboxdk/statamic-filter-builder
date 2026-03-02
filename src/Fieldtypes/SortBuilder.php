<?php

namespace Cbox\FilterBuilder\Fieldtypes;

use Statamic\Fields\Field;
use Statamic\Fields\Fields;
use Statamic\Fields\Fieldtype;

class SortBuilder extends Fieldtype
{
    use Concerns\UsesFields;

    /**
     * @return array<int, array<string, mixed>>
     */
    protected function configFieldItems(): array
    {
        return [
            [
                'display' => __('Appearance & Behavior'),
                'fields' => [
                    'mode' => [
                        'display' => __('Mode'),
                        'instructions' => __('The collection listing source'),
                        'type' => 'button_group',
                        'default' => 'config',
                        'options' => [
                            'config' => __('Field Configuration'),
                            'field' => __('Blueprint Field'),
                        ],
                    ],
                    'collections' => [
                        'display' => __('Collections'),
                        'instructions' => __('The sorted collections'),
                        'mode' => 'select',
                        'type' => 'collections',
                        'validate' => 'required_if:mode,config',
                        'if' => [
                            'mode' => 'config',
                        ],
                    ],
                    'field' => [
                        'display' => __('Field'),
                        'instructions' => __('The field listing the sorted collections'),
                        'type' => 'text',
                        'validate' => 'required_if:mode,field',
                        'if' => [
                            'mode' => 'field',
                        ],
                    ],
                ],
            ],
        ];
    }

    /**
     * @param  array<int, array<string, mixed>>|null  $value
     */
    public function augment($value): ?string
    {
        if (empty($value)) {
            return null;
        }

        return collect($value)
            /** @phpstan-ignore offsetAccess.nonOffsetAccessible */
            ->filter(fn (array $sort): bool => isset($sort['handle'], $sort['values']['direction']))
            ->map(function (array $sort): string {
                /** @var string $handle */
                $handle = $sort['handle'];
                /** @var array{direction: string} $values */
                $values = $sort['values'];

                return $handle.':'.$values['direction'];
            })
            ->join('|');
    }

    protected function getFieldFields(Field $field): Fields
    {
        $fieldItems = [
            'direction' => [
                'type' => 'select',
                'display' => __('statamic-filter-builder::fieldtypes.sort_builder.direction'),
                'options' => [
                    'asc' => __('Ascending'),
                    'desc' => __('Descending'),
                ],
                'default' => 'asc',
                'width' => 25,
                'replicator_preview' => true,
            ],
        ];

        $fields = collect($fieldItems)->map(function (array $field, string $handle): array {
            return compact('handle', 'field');
        });

        return new Fields(
            $fields,
            /** @phpstan-ignore method.nonObject */
            $this->field()->parent(),
            $this->field()
        );
    }
}
