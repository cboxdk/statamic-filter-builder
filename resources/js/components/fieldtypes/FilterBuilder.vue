<template>
    <div>
        <sortable-list
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

        <div v-if="!isReadOnly" class="flex">
            <Combobox
                class="w-52"
                :placeholder="__('statamic-filter-builder::fieldtypes.filter_builder.add_filter')"
                :options="fieldsOptions"
                :model-value="null"
                @update:model-value="onAdd"
            />
        </div>
    </div>
</template>

<script setup>
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
    itemPreviews,
    itemFieldPath,
    itemMetaPath,
} = useFields(props, { update, updateMeta });

function onAdd(handle) {
    if (handle) {
        addItem('field', handle);
    }
}
</script>

<style>
.filter_builder-fieldtype .publish-fields {
    gap: 0.5rem;
}
</style>
