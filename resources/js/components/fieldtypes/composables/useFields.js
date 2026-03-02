import { ref, computed, watch, getCurrentInstance, onMounted } from 'vue';
import { injectPublishContext } from '@statamic/cms/ui';

export function useFields(props, { update, updateMeta }) {
    const { proxy } = getCurrentInstance();
    const { values: containerValues } = injectPublishContext();

    const collapsed = ref(props.value.map(item => item.id));
    const previews = ref(props.meta.previews || {});
    const itemCache = ref({});

    const mode = computed(() => props.config.mode || 'config');

    const fieldPath = computed(() => {
        return [props.fieldPathPrefix, props.handle].filter(Boolean).join('.');
    });

    const metaPath = computed(() => {
        return [props.metaPathPrefix, props.handle].filter(Boolean).join('.');
    });

    const collections = computed(() => {
        const prefix = props.fieldPathPrefix || '';
        const key = prefix.slice(0, -props.handle.length) + props.config.field;
        return data_get(containerValues.value, key);
    });

    const fieldsObject = computed(() => {
        return Object.fromEntries(props.meta.fields.map(field => ([
            field.handle,
            field,
        ])));
    });

    const usedHandles = computed(() => {
        return new Set(props.value.map(item => item.handle));
    });

    const fieldsOptions = computed(() => {
        return props.meta.fields
            .filter(field => !usedHandles.value.has(field.handle))
            .map(field => ({
                value: field.handle,
                label: field.display,
            }));
    });

    const itemFields = computed(() => {
        return {
            field: Object.fromEntries(props.meta.fields.map(field => ([
                field.handle,
                field.fields,
            ]))),
        };
    });

    function loadCollectionsMeta(cols) {
        const params = {
            config: utf8btoa(JSON.stringify({
                ...props.config,
                mode: 'config',
                collections: cols,
            })),
        };

        proxy.$axios.post(cp_url('fields/field-meta'), params).then(response => {
            updateMeta(response.data.meta);
        }).catch(error => {
            console.error('Failed to load field meta:', error);
        });
    }

    function addItem(type, handle) {
        const id = uniqid();
        update([
            ...props.value,
            { id, type, handle, values: props.meta.defaults[handle] },
        ]);
        updateMeta({
            ...props.meta,
            existing: {
                ...props.meta.existing,
                [id]: props.meta.new[handle],
            },
        });
        previews.value[id] = {};
    }

    function removeItem(index) {
        update([
            ...props.value.slice(0, index),
            ...props.value.slice(index + 1),
        ]);
    }

    function collapseItem(id) {
        if (!collapsed.value.includes(id)) {
            collapsed.value.push(id);
        }
    }

    function expandItem(id) {
        const index = collapsed.value.indexOf(id);
        if (index > -1) {
            collapsed.value.splice(index, 1);
        }
    }

    function collapseAll(items) {
        collapsed.value = items.map(item => item.id);
    }

    function expandAll() {
        collapsed.value = [];
    }

    function itemPreviews(id) {
        return previews.value[id];
    }

    function updateItemPreviews(id, itemPrevs) {
        previews.value[id] = itemPrevs;
    }

    function itemFieldPath(index) {
        return `${fieldPath.value}.${index}`;
    }

    function itemMetaPath(id) {
        return `${metaPath.value}.existing.${id}`;
    }

    onMounted(() => {
        if (props.meta.fields.length === 0) {
            loadCollectionsMeta(collections.value);
        }
    });

    function cacheKey(cols) {
        return JSON.stringify([...(cols || [])].sort());
    }

    watch(collections, (newVal, oldVal) => {
        if (JSON.stringify(newVal) === JSON.stringify(oldVal)) {
            return;
        }

        // Cache current items before clearing
        const oldKey = cacheKey(oldVal);
        if (props.value.length > 0) {
            itemCache.value[oldKey] = {
                items: [...props.value],
                existing: { ...props.meta.existing },
            };
        }

        // Restore cached items if available for the new collection set
        const newKey = cacheKey(newVal);
        const cached = itemCache.value[newKey];
        if (cached) {
            update(cached.items);
            updateMeta({
                ...props.meta,
                existing: cached.existing,
            });
            delete itemCache.value[newKey];
        } else {
            update([]);
            updateMeta({
                ...props.meta,
                existing: {},
            });
        }

        loadCollectionsMeta(newVal);
    });

    return {
        collapsed,
        previews,
        mode,
        fieldPath,
        metaPath,
        collections,
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
        updateItemPreviews,
        itemFieldPath,
        itemMetaPath,
    };
}
