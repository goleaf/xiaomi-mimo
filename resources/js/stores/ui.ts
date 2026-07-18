import { defineStore } from 'pinia';
import { ref } from 'vue';

export const useUiStore = defineStore('ui', () => {
    const sidebarOpen = ref(true);
    const commandPaletteOpen = ref(false);
    const activeModal = ref<string | null>(null);
    const activeDrawer = ref<string | null>(null);
    const activeDrawerData = ref<unknown>(null);

    function toggleSidebar() {
        sidebarOpen.value = !sidebarOpen.value;
    }

    function openCommandPalette() {
        commandPaletteOpen.value = true;
    }

    function closeCommandPalette() {
        commandPaletteOpen.value = false;
    }

    function openModal(name: string) {
        activeModal.value = name;
    }

    function closeModal() {
        activeModal.value = null;
    }

    function openDrawer(name: string, data?: unknown) {
        activeDrawer.value = name;
        activeDrawerData.value = data ?? null;
    }

    function closeDrawer() {
        activeDrawer.value = null;
        activeDrawerData.value = null;
    }

    return {
        sidebarOpen,
        commandPaletteOpen,
        activeModal,
        activeDrawer,
        activeDrawerData,
        toggleSidebar,
        openCommandPalette,
        closeCommandPalette,
        openModal,
        closeModal,
        openDrawer,
        closeDrawer,
    };
});
