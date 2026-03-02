<template>
    <div
        class="relative w-full rounded-lg border bg-white text-base dark:bg-gray-900 dark:border-white/10 shadow-ui-sm mb-2"
        :class="hasError ? 'border-red-500' : 'border-gray-300'"
    >
        <header
            class="flex items-center px-1.5 bg-gray-100/50 dark:bg-gray-925 hover:bg-gray-100 dark:hover:bg-gray-950/45"
            :class="collapsed
                ? 'rounded-[calc(var(--radius-lg)-1px)]'
                : 'rounded-t-[calc(var(--radius-lg)-1px)] border-b border-b-gray-300 dark:border-b-white/10'"
        >
            <DragHandle v-if="!readOnly" class="sort-builder-sortable-handle size-4 cursor-grab text-gray-400" />
            <button
                type="button"
                class="flex flex-1 items-center gap-3 p-2 py-1.5 min-w-0 cursor-pointer focus:outline-none"
                @click="toggleCollapsed"
            >
                <span class="text-xs font-medium whitespace-nowrap">{{ field.display }}</span>
                <span
                    v-show="collapsed"
                    v-html="previewText"
                    class="text-xs text-gray-500 overflow-hidden text-ellipsis whitespace-nowrap"
                />
            </button>
            <button
                v-if="!readOnly"
                class="p-1.5 text-gray-400 hover:text-gray-700 dark:hover:text-gray-300 cursor-pointer"
                @click.stop="emit('removed')"
                :aria-label="__('statamic-filter-builder::fieldtypes.sort_builder.delete_sort')"
            >
                <ui-icon name="trash" class="size-3.5" />
            </button>
        </header>
        <div v-show="!collapsed" class="@container p-3">
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
