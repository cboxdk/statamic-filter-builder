<?php

namespace Cbox\FilterBuilder;

use Statamic\Providers\AddonServiceProvider;
use Statamic\Tags\Collection\Collection as CollectionTag;

class ServiceProvider extends AddonServiceProvider
{
    /** @var list<class-string<\Statamic\Fields\Fieldtype>> */
    protected $fieldtypes = [
        Fieldtypes\FilterBuilder::class,
        Fieldtypes\SortBuilder::class,
    ];

    /** @var list<class-string<\Statamic\Query\Scopes\Scope>> */
    protected $scopes = [
        Scopes\FilterBuilder::class,
    ];

    /** @phpstan-ignore property.defaultValue */
    protected $vite = [
        'input' => [
            'resources/js/addon.js',
        ],
        'publicDirectory' => 'resources/dist',
    ];

    public function bootAddon(): void
    {
        $this->addCollectionHook();
    }

    private function addCollectionHook(): void
    {
        CollectionTag::hook('init', function ($value, $next) {
            /** @phpstan-ignore property.notFound */
            if (! $this->params->get('filter_builder')) {
                return $next($value);
            }

            /** @phpstan-ignore property.notFound */
            if ($this->params->get('query_scope')) {
                return $next($value);
            }

            /** @phpstan-ignore property.notFound, property.notFound */
            $this->params = $this->params->merge([
                'query_scope' => 'filter_builder',
            ]);

            return $next($value);
        });
    }
}
