<?php

use Statamic\Facades;

beforeEach(function () {
    $collection = tap(Facades\Collection::make()
        ->handle('pages'))
        ->save();

    $collection->entryBlueprints()->first()->setContents([
        'fields' => [
            [
                'handle' => 'title',
                'field' => [
                    'type' => 'text',
                ],
            ],
            [
                'handle' => 'entries',
                'field' => [
                    'type' => 'entries',
                    'max_items' => 5,
                ],
            ],
            [
                'handle' => 'date',
                'field' => [
                    'type' => 'date',
                ],
            ],
            [
                'handle' => 'number',
                'field' => [
                    'type' => 'integer',
                ],
            ],
            [
                'handle' => 'featured',
                'field' => [
                    'type' => 'toggle',
                ],
            ],
        ],
    ])->save();

    Facades\Entry::make()
        ->id('one')
        ->collection('pages')
        ->merge([
            'title' => 'One',
            'entries' => ['One'],
            'date' => '2024-01-15',
            'number' => 10,
            'featured' => true,
        ])
        ->save();

    Facades\Entry::make()
        ->id('two')
        ->collection('pages')
        ->merge([
            'title' => 'Two',
            'entries' => ['One', 'Two'],
            'date' => '2024-06-15',
            'number' => 20,
            'featured' => false,
        ])
        ->save();

    Facades\Entry::make()
        ->id('three')
        ->collection('pages')
        ->merge([
            'title' => 'Three',
            'entries' => ['One', 'Two', 'Three'],
            'date' => '2024-12-15',
            'number' => 30,
            'featured' => true,
        ])
        ->save();
});

it('filters text fields', function () {
    $result = (string) Facades\Antlers::parse('{{ collection:pages :filter_builder="params" }}{{ title }}{{ /collection:pages }}', [
        'params' => [
            [
                'handle' => 'title',
                'values' => [
                    'operator' => '=',
                    'values' => ['one'],
                    'variables' => [],
                ],
            ],
        ],
    ], true);

    $this->assertSame('One', $result);

    $result = (string) Facades\Antlers::parse('{{ collection:pages :filter_builder="params" }}{{ title }}{{ /collection:pages }}', [
        'params' => [
            [
                'handle' => 'title',
                'values' => [
                    'operator' => '<>',
                    'values' => ['one'],
                    'variables' => [],
                ],
            ],
        ],
    ], true);

    $this->assertSame('ThreeTwo', $result);

    $result = (string) Facades\Antlers::parse('{{ collection:pages :filter_builder="params" }}{{ title }}{{ /collection:pages }}', [
        'params' => [
            [
                'handle' => 'title',
                'values' => [
                    'operator' => '=',
                    'values' => ['one', 'two'],
                    'variables' => [],
                ],
            ],
        ],
    ], true);

    $this->assertSame('OneTwo', $result);

    $result = (string) Facades\Antlers::parse('{{ collection:pages :filter_builder="params" }}{{ title }}{{ /collection:pages }}', [
        'params' => [
            [
                'handle' => 'title',
                'values' => [
                    'operator' => '=',
                    'values' => ['four'],
                    'variables' => [],
                ],
            ],
        ],
    ], true);

    $this->assertSame('', $result);
});

it('filters fields with values from the cascade', function () {
    Facades\Cascade::set('cascade_variable', 'one');

    $result = (string) Facades\Antlers::parse('{{ collection:pages :filter_builder="params" }}{{ title }}{{ /collection:pages }}', [
        'params' => [
            [
                'handle' => 'title',
                'values' => [
                    'operator' => '=',
                    'values' => [],
                    'variables' => ['{{ cascade_variable }}'],
                ],
            ],
        ],
    ], true);

    $this->assertSame('One', $result);
});

it('filters relationship fields', function () {
    $result = (string) Facades\Antlers::parse('{{ collection:pages :filter_builder="params" }}{{ title }}{{ /collection:pages }}', [
        'params' => [
            [
                'handle' => 'entries',
                'values' => [
                    'operator' => '=',
                    'values' => ['Three'],
                    'variables' => [],
                ],
            ],
        ],
    ], true);

    $this->assertSame('Three', $result);

    $result = (string) Facades\Antlers::parse('{{ collection:pages :filter_builder="params" }}{{ title }}{{ /collection:pages }}', [
        'params' => [
            [
                'handle' => 'entries',
                'values' => [
                    'operator' => '<>',
                    'values' => ['Three'],
                    'variables' => [],
                ],
            ],
        ],
    ], true);

    $this->assertSame('OneTwo', $result);

    $result = (string) Facades\Antlers::parse('{{ collection:pages :filter_builder="params" }}{{ title }}{{ /collection:pages }}', [
        'params' => [
            [
                'handle' => 'entries',
                'values' => [
                    'operator' => '=',
                    'values' => ['Three', 'Two'],
                    'variables' => [],
                ],
            ],
        ],
    ], true);

    $this->assertSame('ThreeTwo', $result);

    $result = (string) Facades\Antlers::parse('{{ collection:pages :filter_builder="params" }}{{ title }}{{ /collection:pages }}', [
        'params' => [
            [
                'handle' => 'title',
                'values' => [
                    'operator' => '=',
                    'values' => ['Four'],
                    'variables' => [],
                ],
            ],
        ],
    ], true);

    $this->assertSame('', $result);
});

it('filters integer fields with comparison operators', function () {
    $result = (string) Facades\Antlers::parse('{{ collection:pages :filter_builder="params" }}{{ title }}{{ /collection:pages }}', [
        'params' => [
            [
                'handle' => 'number',
                'values' => [
                    'operator' => '>',
                    'values' => [15],
                    'variables' => [],
                ],
            ],
        ],
    ], true);

    $this->assertSame('ThreeTwo', $result);

    $result = (string) Facades\Antlers::parse('{{ collection:pages :filter_builder="params" }}{{ title }}{{ /collection:pages }}', [
        'params' => [
            [
                'handle' => 'number',
                'values' => [
                    'operator' => '>=',
                    'values' => [20],
                    'variables' => [],
                ],
            ],
        ],
    ], true);

    $this->assertSame('ThreeTwo', $result);

    $result = (string) Facades\Antlers::parse('{{ collection:pages :filter_builder="params" }}{{ title }}{{ /collection:pages }}', [
        'params' => [
            [
                'handle' => 'number',
                'values' => [
                    'operator' => '<',
                    'values' => [20],
                    'variables' => [],
                ],
            ],
        ],
    ], true);

    $this->assertSame('One', $result);
});

it('filters toggle fields', function () {
    $result = (string) Facades\Antlers::parse('{{ collection:pages :filter_builder="params" }}{{ title }}{{ /collection:pages }}', [
        'params' => [
            [
                'handle' => 'featured',
                'values' => [
                    'operator' => '=',
                    'values' => [true],
                    'variables' => [],
                ],
            ],
        ],
    ], true);

    $this->assertSame('OneThree', $result);
});

it('skips filters with empty values', function () {
    $result = (string) Facades\Antlers::parse('{{ collection:pages :filter_builder="params" }}{{ title }}{{ /collection:pages }}', [
        'params' => [
            [
                'handle' => 'title',
                'values' => [
                    'operator' => '=',
                    'values' => [],
                    'variables' => [],
                ],
            ],
        ],
    ], true);

    $this->assertSame('OneThreeTwo', $result);
});

it('skips filters with non-existent field handle', function () {
    $result = (string) Facades\Antlers::parse('{{ collection:pages :filter_builder="params" }}{{ title }}{{ /collection:pages }}', [
        'params' => [
            [
                'handle' => 'nonexistent_field',
                'values' => [
                    'operator' => '=',
                    'values' => ['test'],
                    'variables' => [],
                ],
            ],
        ],
    ], true);

    $this->assertSame('OneThreeTwo', $result);
});

it('skips filters with malformed data', function () {
    $result = (string) Facades\Antlers::parse('{{ collection:pages :filter_builder="params" }}{{ title }}{{ /collection:pages }}', [
        'params' => [
            [
                'handle' => 'title',
            ],
        ],
    ], true);

    $this->assertSame('OneThreeTwo', $result);
});
