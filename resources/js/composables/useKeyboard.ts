import { onMounted, onUnmounted } from 'vue';

type KeyHandler = () => void;

const shortcuts = new Map<string, KeyHandler>();

export function registerShortcut(key: string, handler: KeyHandler) {
    shortcuts.set(key, handler);
}

export function removeShortcut(key: string) {
    shortcuts.delete(key);
}

function handleKeyDown(event: KeyboardEvent) {
    const key = [
        event.metaKey || event.ctrlKey ? 'mod' : '',
        event.shiftKey ? 'shift' : '',
        event.altKey ? 'alt' : '',
        event.key.toLowerCase(),
    ].filter(Boolean).join('+');

    const handler = shortcuts.get(key);

    if (handler) {
        event.preventDefault();
        handler();
    }
}

export function useKeyboard() {
    onMounted(() => {
        document.addEventListener('keydown', handleKeyDown);
    });

    onUnmounted(() => {
        document.removeEventListener('keydown', handleKeyDown);
    });
}
