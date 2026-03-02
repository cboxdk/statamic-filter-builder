<template>
    <div>
        <sortable-list
            :model-value="value"
            :vertical="true"
            item-class="sort-builder-sortable-item"
            handle-class="sort-builder-sortable-handle"
            append-to="body"
            constrain-dimensions
            :disabled="isReadOnly"
            @update:model-value="update($event)"
        >
            <div>
                <sort-item
                    v-for="(sort, index) in value"
                    :key="sort.id"
                    class="sort-builder-sortable-item"
                    :item="sort"
                    :field="fieldsObject[sort.handle]"
                    :fields="itemFields.field[sort.handle]"
                    :field-path="itemFieldPath(index)"
                    :meta-path="itemMetaPath(sort.id)"
                    :read-only="isReadOnly"
                    :index="index"
                    :collapsed="collapsed.includes(sort.id)"
                    :previews="itemPreviews(sort.id)"
                    @collapsed="collapseItem(sort.id)"
                    @expanded="expandItem(sort.id)"
                    @removed="removeItem(index)"
                />
            </div>
        </sortable-list>

        <div v-if="!isReadOnly" class="flex">
            <Combobox
                class="w-52"
                :placeholder="__('statamic-filter-builder::fieldtypes.sort_builder.add_sort')"
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
import SortItem from './SortItem.vue';
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
.sort_builder-fieldtype .replicator-set-body.publish-fields {
    padding: 0.5rem;
    gap: 0.5rem;
}
</style>
