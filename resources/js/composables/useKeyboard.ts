import { onMounted, onUnmounted } from 'vue';
import { useUiStore } from '@/stores/ui';

type KeyHandler = (event: KeyboardEvent) => void;

export function useKeyboard() {
    const ui = useUiStore();
    const handlers = new Map<string, KeyHandler>();

    function register(key: string, handler: KeyHandler) {
        handlers.set(key, handler);
    }

    function handleKeyDown(event: KeyboardEvent) {
        const key = buildKey(event);
        const handler = handlers.get(key);
        if (handler) {
            event.preventDefault();
            handler(event);
        }

        if ((event.metaKey || event.ctrlKey) && event.key === 'k') {
            event.preventDefault();
            ui.commandPaletteOpen ? ui.closeCommandPalette() : ui.openCommandPalette();
        }

        if (event.key === 'Escape') {
            ui.closeCommandPalette();
            ui.closeDrawer();
            ui.closeModal();
        }
    }

    function buildKey(event: KeyboardEvent): string {
        const parts: string[] = [];
        if (event.metaKey || event.ctrlKey) parts.push('mod');
        if (event.shiftKey) parts.push('shift');
        if (event.altKey) parts.push('alt');
        parts.push(event.key.toLowerCase());
        return parts.join('+');
    }

    onMounted(() => {
        document.addEventListener('keydown', handleKeyDown);
    });

    onUnmounted(() => {
        document.removeEventListener('keydown', handleKeyDown);
        handlers.clear();
    });

    return { register };
}
