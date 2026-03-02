<template>
    <div class="replicator-set mb-2" :class="{ 'border-red-400': hasError }">
        <div class="replicator-set-header p-0" :class="{ 'collapsed': collapsed }">
            <div class="flex items-center justify-between flex-1 px-2 py-1.5 replicator-set-header-inner cursor-pointer" @click="toggleCollapsed">
                <DragHandle v-if="!readOnly" class="sort-builder-sortable-handle" />
                <label class="text-xs whitespace-nowrap mr-2">
                    {{ field.display }}
                </label>
                <div v-show="collapsed" class="flex-1 min-w-0 w-1 pr-8">
                    <div v-html="previewText" class="help-block mb-0 whitespace-nowrap overflow-hidden text-ellipsis" />
                </div>
                <button v-if="!readOnly" class="flex group items-center" @click.stop="emit('removed')" :aria-label="__('statamic-filter-builder::fieldtypes.sort_builder.delete_sort')">
                    <ui-icon name="trash" class="w-4 h-4 text-gray-600 group-hover:text-gray-900" />
                </button>
            </div>
        </div>
        <div class="replicator-set-body flex-1 publish-fields @container" v-show="!collapsed">
            <PublishFieldsProvider
                :fields="fields"
                :field-path-prefix="fieldPath + '.values'"
                :meta-path-prefix="metaPath"
            >
                <PublishFields />
            </PublishFieldsProvider>
        </div>
    </div>
</template>

<script setup>
import { computed } from 'vue';
import { DragHandle, PublishFields, PublishFieldsProvider, injectPublishContext } from '@statamic/cms/ui';

const emit = defineEmits(['collapsed', 'expanded', 'removed']);

const props = defineProps({
    item: Object,
    field: Object,
    fields: Array,
    fieldPath: String,
    metaPath: String,
    readOnly: Boolean,
    index: Number,
    collapsed: {
        type: Boolean,
        default: false,
    },
    previews: Object,
});

const { previews: containerPreviews, errors } = injectPublishContext();

const hasError = computed(() => {
    if (!errors?.value) return false;
    const prefix = props.fieldPath + '.values';
    return Object.keys(errors.value).some(key => key.startsWith(prefix));
});

const previewText = computed(() => {
    const fieldPreviews = data_get(containerPreviews.value, props.fieldPath + '.values') || {};
    return Object.entries(fieldPreviews)
        .filter(([, value]) => {
            if (['null', '[]', '{}', ''].includes(JSON.stringify(value))) return false;
            return value;
        })
        .map(([, value]) => {
            if (typeof value === 'string') return escapeHtml(value);
            if (Array.isArray(value) && typeof value[0] === 'string') {
                return escapeHtml(value.join(', '));
            }
            return escapeHtml(JSON.stringify(value));
        })
        .join(' / ');
});

function toggleCollapsed() {
    emit(props.collapsed ? 'expanded' : 'collapsed');
}
</script>
