import { ref, watch } from 'vue';

export function useAutosave<T>(
    data: T,
    saveFn: (data: T) => Promise<unknown>,
    delay = 1000,
) {
    const saving = ref(false);
    const lastSaved = ref<Date | null>(null);

    const save = async (): Promise<void> => {
        saving.value = true;

        try {
            await saveFn(data);
            lastSaved.value = new Date();
        } finally {
            saving.value = false;
        }
    };

    watch(
        () => data,
        (_value, _oldValue, onCleanup) => {
            const timeoutId = setTimeout(() => {
                void save();
            }, delay);

            onCleanup(() => {
                clearTimeout(timeoutId);
            });
        },
        { deep: true },
    );

    return { saving, lastSaved };
}
