<template>
    <div>
        <p v-if="value.length === 0 && isReadOnly" class="text-xs text-gray-500">
            {{ __('statamic-filter-builder::fieldtypes.filter_builder.no_filters') }}
        </p>

        <sortable-list
            v-if="value.length > 0"
            :model-value="value"
            :vertical="true"
            item-class="filter-builder-sortable-item"
            handle-class="filter-builder-sortable-handle"
            append-to="body"
            constrain-dimensions
            :disabled="isReadOnly"
            @update:model-value="update($event)"
        >
            <div>
                <filter-item
                    v-for="(filter, index) in value"
                    :key="filter.id"
                    class="filter-builder-sortable-item"
                    :item="filter"
                    :field="fieldsObject[filter.handle]"
                    :fields="itemFields.field[filter.handle]"
                    :field-path="itemFieldPath(index)"
                    :meta-path="itemMetaPath(filter.id)"
                    :read-only="isReadOnly"
                    :index="index"
                    :collapsed="collapsed.includes(filter.id)"
                    :previews="itemPreviews(filter.id)"
                    @collapsed="collapseItem(filter.id)"
                    @expanded="expandItem(filter.id)"
                    @removed="removeItem(index)"
                />
            </div>
        </sortable-list>

        <div v-if="!isReadOnly" class="flex items-center gap-2">
            <Combobox
                class="w-52"
                :placeholder="__('statamic-filter-builder::fieldtypes.filter_builder.add_filter')"
                :options="fieldsOptions"
                :model-value="null"
                @update:model-value="onAdd"
            />
            <button
                v-if="value.length > 1"
                type="button"
                class="text-xs text-gray-500 hover:text-gray-700 dark:hover:text-gray-300 cursor-pointer"
                @click="toggleCollapseAll"
            >
                {{ allCollapsed ? __('Expand All') : __('Collapse All') }}
            </button>
            <button
                type="button"
                class="ml-auto p-1 text-gray-400 hover:text-gray-700 dark:hover:text-gray-300 cursor-pointer"
                :aria-label="__('statamic-filter-builder::fieldtypes.filter_builder.help')"
                @click="showHelp = true"
            >
                <ui-icon name="info" class="size-4" />
            </button>
        </div>

        <Stack v-model:open="showHelp" :title="__('statamic-filter-builder::fieldtypes.filter_builder.help_title')" narrow>
            <StackContent>
                <div class="p-6 space-y-6 text-sm text-gray-700 dark:text-gray-300">
                    <section>
                        <h3 class="font-semibold text-gray-900 dark:text-white mb-2">{{ __('statamic-filter-builder::fieldtypes.filter_builder.help_overview_title') }}</h3>
                        <p>{{ __('statamic-filter-builder::fieldtypes.filter_builder.help_overview') }}</p>
                    </section>

                    <section>
                        <h3 class="font-semibold text-gray-900 dark:text-white mb-2">{{ __('statamic-filter-builder::fieldtypes.filter_builder.help_operators_title') }}</h3>
                        <p class="mb-2">{{ __('statamic-filter-builder::fieldtypes.filter_builder.help_operators') }}</p>
                        <div class="bg-gray-100 dark:bg-gray-800 rounded-lg p-3 space-y-1 font-mono text-xs">
                            <div><span class="text-blue-600 dark:text-blue-400">Is / Isn't</span> — {{ __('statamic-filter-builder::fieldtypes.filter_builder.help_op_is') }}</div>
                            <div><span class="text-blue-600 dark:text-blue-400">Contains</span> — {{ __('statamic-filter-builder::fieldtypes.filter_builder.help_op_contains') }}</div>
                            <div><span class="text-blue-600 dark:text-blue-400">Before / After</span> — {{ __('statamic-filter-builder::fieldtypes.filter_builder.help_op_date') }}</div>
                            <div><span class="text-blue-600 dark:text-blue-400">&gt; &gt;= &lt; &lt;=</span> — {{ __('statamic-filter-builder::fieldtypes.filter_builder.help_op_numeric') }}</div>
                        </div>
                    </section>

                    <section>
                        <h3 class="font-semibold text-gray-900 dark:text-white mb-2">{{ __('statamic-filter-builder::fieldtypes.filter_builder.help_dynamic_title') }}</h3>
                        <p class="mb-2">{{ __('statamic-filter-builder::fieldtypes.filter_builder.help_dynamic') }}</p>
                        <div class="bg-gray-100 dark:bg-gray-800 rounded-lg p-3 space-y-1 font-mono text-xs">
                            <div v-for="example in dynamicExamples" :key="example" class="text-green-600 dark:text-green-400" v-text="example" />
                        </div>
                        <p class="mt-2 text-xs text-gray-500">{{ __('statamic-filter-builder::fieldtypes.filter_builder.help_dynamic_note') }}</p>
                    </section>

                    <section>
                        <h3 class="font-semibold text-gray-900 dark:text-white mb-2">{{ __('statamic-filter-builder::fieldtypes.filter_builder.help_behavior_title') }}</h3>
                        <ul class="list-disc list-inside space-y-1">
                            <li>{{ __('statamic-filter-builder::fieldtypes.filter_builder.help_behavior_and') }}</li>
                            <li>{{ __('statamic-filter-builder::fieldtypes.filter_builder.help_behavior_or') }}</li>
                            <li>{{ __('statamic-filter-builder::fieldtypes.filter_builder.help_behavior_empty') }}</li>
                            <li>{{ __('statamic-filter-builder::fieldtypes.filter_builder.help_behavior_cast') }}</li>
                        </ul>
                    </section>
                </div>
            </StackContent>
        </Stack>
    </div>
</template>

<script setup>
import { computed, ref } from 'vue';
import { Fieldtype, SortableList } from '@statamic/cms';
import { Combobox, Stack, StackContent } from '@statamic/cms/ui';
import FilterItem from './FilterItem.vue';
import { useFields } from './composables/useFields.js';

const emit = defineEmits(Fieldtype.emits);
const props = defineProps(Fieldtype.props);
const { expose, update, updateMeta, isReadOnly } = Fieldtype.use(emit, props);
defineExpose(expose);

const showHelp = ref(false);
const dynamicExamples = [
    '{{ request:category }}',
    '{{ now format="Y-m-d" }}',
    '{{ current_user:id }}',
];

const {
    collapsed,
    fieldsObject,
    fieldsOptions,
    itemFields,
    addItem,
    removeItem,
    collapseItem,
    expandItem,
    collapseAll,
    expandAll,
    itemPreviews,
    itemFieldPath,
    itemMetaPath,
} = useFields(props, { update, updateMeta });

const allCollapsed = computed(() => {
    return props.value.length > 0 && props.value.every(item => collapsed.value.includes(item.id));
});

function onAdd(handle) {
    if (handle) {
        addItem('field', handle);
    }
}

function toggleCollapseAll() {
    if (allCollapsed.value) {
        expandAll(props.value);
    } else {
        collapseAll(props.value);
    }
}
</script>

<style>
.filter_builder-fieldtype .publish-fields {
    gap: 0.5rem;
}
</style>
