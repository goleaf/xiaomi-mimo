<script setup lang="ts">
import { router } from '@inertiajs/vue3';
import { ref, computed, watch, onMounted, onUnmounted } from 'vue';
import { useUiStore } from '@/stores/ui';
import { Input } from '@/components/ui/input';
import { Search, Folder, CheckSquare, Settings, LogOut } from '@lucide/vue';

const ui = useUiStore();
const query = ref('');
const inputRef = ref<HTMLInputElement>();

interface CommandItem {
    id: string;
    label: string;
    icon: typeof Search;
    action: () => void;
    section: string;
}

const commands: CommandItem[] = [
    { id: 'dashboard', label: 'Go to Dashboard', icon: CheckSquare, action: () => router.visit(route('dashboard')), section: 'Navigation' },
    { id: 'tasks', label: 'Go to Tasks', icon: CheckSquare, action: () => router.visit(route('todos.index', '1')), section: 'Navigation' },
    { id: 'projects', label: 'Go to Projects', icon: Folder, action: () => router.visit(route('projects.index', '1')), section: 'Navigation' },
    { id: 'settings', label: 'Go to Settings', icon: Settings, action: () => router.visit(/settings/profile), section: 'Navigation' },
    { id: 'logout', label: 'Log Out', icon: LogOut, action: () => router.post(/logout), section: 'Account' },
];

const filteredCommands = computed(() => {
    if (!query.value) return commands;
    return commands.filter((c) => c.label.toLowerCase().includes(query.value.toLowerCase()));
});

const groupedCommands = computed(() => {
    const groups: Record<string, CommandItem[]> = {};
    filteredCommands.value.forEach((cmd) => {
        if (!groups[cmd.section]) groups[cmd.section] = [];
        groups[cmd.section].push(cmd);
    });
    return groups;
});

watch(() => ui.commandPaletteOpen, (open) => {
    if (open) {
        query.value = '';
        setTimeout(() => inputRef.value?.focus(), 100);
    }
});

function executeCommand(command: CommandItem) {
    command.action();
    ui.closeCommandPalette();
}

function handleKeydown(e: KeyboardEvent) {
    if (e.key === 'Escape') ui.closeCommandPalette();
}
</script>

<template>
    <Teleport to="body">
        <Transition name="fade">
            <div v-if="ui.commandPaletteOpen" class="fixed inset-0 z-[100] bg-black/50 flex items-start justify-center pt-[20vh]" @click="ui.closeCommandPalette">
                <div class="w-full max-w-md bg-background rounded-xl border shadow-2xl overflow-hidden" @click.stop>
                    <div class="flex items-center border-b px-4">
                        <Search class="h-4 w-4 text-muted-foreground" />
                        <Input ref="inputRef" v-model="query" placeholder="Type a command..." class="border-0 focus-visible:ring-0 shadow-none" @keydown="handleKeydown" />
                    </div>
                    <div class="max-h-[300px] overflow-y-auto p-2">
                        <template v-for="(items, section) in groupedCommands" :key="section">
                            <p class="px-2 py-1 text-xs font-medium text-muted-foreground">{{ section }}</p>
                            <button
                                v-for="cmd in items"
                                :key="cmd.id"
                                class="flex items-center gap-3 w-full px-3 py-2 text-sm rounded-md hover:bg-muted transition-colors"
                                @click="executeCommand(cmd)"
                            >
                                <component :is="cmd.icon" class="h-4 w-4 text-muted-foreground" />
                                {{ cmd.label }}
                            </button>
                        </template>
                        <p v-if="filteredCommands.length === 0" class="px-3 py-6 text-center text-sm text-muted-foreground">No commands found</p>
                    </div>
                </div>
            </div>
        </Transition>
    </Teleport>
</template>

<style scoped>
.fade-enter-active, .fade-leave-active { transition: opacity 0.15s ease; }
.fade-enter-from, .fade-leave-to { opacity: 0; }
</style>
