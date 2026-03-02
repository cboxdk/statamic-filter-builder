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
        </div>
    </div>
</template>

<script setup>
import { computed } from 'vue';
import { Fieldtype, SortableList } from '@statamic/cms';
import { Combobox } from '@statamic/cms/ui';
import FilterItem from './FilterItem.vue';
import { useFields } from './composables/useFields.js';

const emit = defineEmits(Fieldtype.emits);
const props = defineProps(Fieldtype.props);
const { expose, update, updateMeta, isReadOnly } = Fieldtype.use(emit, props);
defineExpose(expose);

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
