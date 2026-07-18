import { ref, watch, onUnmounted } from 'vue';
import { debounce } from '@vueuse/core';

export function useAutosave<T>(data: T, saveFn: (data: T) => Promise<unknown>, delay = 1000) {
    const saving = ref(false);
    const lastSaved = ref<Date | null>(null);

    const debouncedSave = debounce(async () => {
        saving.value = true;
        try {
            await saveFn(data);
            lastSaved.value = new Date();
        } finally {
            saving.value = false;
        }
    }, delay);

    watch(() => data, debouncedSave, { deep: true });

    onUnmounted(() => {
        debouncedSave.cancel();
    });

    return { saving, lastSaved };
}
