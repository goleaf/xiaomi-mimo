import { ref, computed } from 'vue';

export function useBulkSelect<T extends { id: string }>() {
    const selectedIds = ref<Set<string>>(new Set());
    const isAllSelected = ref(false);

    const selectedCount = computed(() => selectedIds.value.size);
    const hasSelection = computed(() => selectedIds.value.size > 0);

    function toggle(id: string) {
        if (selectedIds.value.has(id)) {
            selectedIds.value.delete(id);
        } else {
            selectedIds.value.add(id);
        }
    }

    function selectAll(items: T[]) {
        items.forEach((item) => selectedIds.value.add(item.id));
        isAllSelected.value = true;
    }

    function clearSelection() {
        selectedIds.value.clear();
        isAllSelected.value = false;
    }

    function isSelected(id: string): boolean {
        return selectedIds.value.has(id);
    }

    return {
        selectedIds,
        selectedCount,
        hasSelection,
        isAllSelected,
        toggle,
        selectAll,
        clearSelection,
        isSelected,
    };
}
