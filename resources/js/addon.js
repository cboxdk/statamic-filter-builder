import FilterBuilder from './components/fieldtypes/FilterBuilder.vue'
import SortBuilder from './components/fieldtypes/SortBuilder.vue'

Statamic.booting(() => {
    Statamic.$components.register('filter_builder-fieldtype', FilterBuilder)
    Statamic.$components.register('sort_builder-fieldtype', SortBuilder)
});
