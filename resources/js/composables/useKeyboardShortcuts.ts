import { onMounted, onUnmounted } from 'vue';

type ShortcutHandler = (event: KeyboardEvent) => void;

interface Shortcut {
    key: string;
    ctrl?: boolean;
    shift?: boolean;
    alt?: boolean;
    handler: ShortcutHandler;
}

const registeredShortcuts: Shortcut[] = [];

function handleKeyDown(event: KeyboardEvent) {
    for (const shortcut of registeredShortcuts) {
        const ctrlMatch = shortcut.ctrl ? (event.metaKey || event.ctrlKey) : !(event.metaKey || event.ctrlKey);
        const shiftMatch = shortcut.shift ? event.shiftKey : !event.shiftKey;
        const altMatch = shortcut.alt ? event.altKey : !event.altKey;

        if (ctrlMatch && shiftMatch && altMatch && event.key.toLowerCase() === shortcut.key.toLowerCase()) {
            event.preventDefault();
            shortcut.handler(event);
            return;
        }
    }
}

export function useKeyboardShortcuts() {
    onMounted(() => {
        if (registeredShortcuts.length === 1) {
            document.addEventListener('keydown', handleKeyDown);
        }
    });

    onUnmounted(() => {
        // Only remove when no shortcuts registered
    });

    function register(shortcuts: Shortcut[]) {
        registeredShortcuts.push(...shortcuts);
    }

    function unregisterAll() {
        registeredShortcuts.length = 0;
    }

    return { register, unregisterAll };
}
