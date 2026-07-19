<script setup lang="ts">
import { router } from '@inertiajs/vue3';
import { Search, Folder, CheckSquare, Settings, LogOut } from '@lucide/vue';
import { ref, computed, watch } from 'vue';
import { Input } from '@/components/ui/input';
import { useUi } from '@/composables/useUi';
import { dashboard, logout, projects } from '@/routes';
import { edit as editProfile } from '@/routes/profile';
import { index as tasks } from '@/routes/todos';
import { useUiStore } from '@/stores/ui';

const ui = useUiStore();
const { t } = useUi();
const query = ref('');
const inputRef = ref<HTMLInputElement>();

interface CommandItem {
    id: string;
    label: string;
    icon: typeof Search;
    action: () => void;
    section: string;
}

const commands = computed<CommandItem[]>(() => [
    {
        id: 'dashboard',
        label: t('commands.go_dashboard'),
        icon: CheckSquare,
        action: () => router.visit(dashboard().url),
        section: t('commands.navigation'),
    },
    {
        id: 'tasks',
        label: t('commands.go_tasks'),
        icon: CheckSquare,
        action: () => router.visit(tasks().url),
        section: t('commands.navigation'),
    },
    {
        id: 'projects',
        label: t('commands.go_projects'),
        icon: Folder,
        action: () => router.visit(projects().url),
        section: t('commands.navigation'),
    },
    {
        id: 'settings',
        label: t('commands.go_settings'),
        icon: Settings,
        action: () => router.visit(editProfile().url),
        section: t('commands.navigation'),
    },
    {
        id: 'logout',
        label: t('commands.logout'),
        icon: LogOut,
        action: () => router.post(logout().url),
        section: t('commands.account'),
    },
]);

const filteredCommands = computed(() => {
    if (!query.value) {
        return commands.value;
    }

    return commands.value.filter((c) =>
        c.label.toLowerCase().includes(query.value.toLowerCase()),
    );
});

const groupedCommands = computed(() => {
    const groups: Record<string, CommandItem[]> = {};
    filteredCommands.value.forEach((cmd) => {
        if (!groups[cmd.section]) {
            groups[cmd.section] = [];
        }

        groups[cmd.section].push(cmd);
    });

    return groups;
});

watch(
    () => ui.commandPaletteOpen,
    (open) => {
        if (open) {
            query.value = '';
            setTimeout(() => inputRef.value?.focus(), 100);
        }
    },
);

function executeCommand(command: CommandItem) {
    command.action();
    ui.closeCommandPalette();
}

function handleKeydown(e: KeyboardEvent) {
    if (e.key === 'Escape') {
        ui.closeCommandPalette();
    }
}
</script>

<template>
    <Teleport to="body">
        <Transition name="fade">
            <div
                v-if="ui.commandPaletteOpen"
                class="fixed inset-0 z-[100] flex items-start justify-center bg-black/50 pt-[20vh]"
                @click="ui.closeCommandPalette"
            >
                <div
                    class="w-full max-w-md overflow-hidden rounded-xl border bg-background shadow-2xl"
                    @click.stop
                >
                    <div class="flex items-center border-b px-4">
                        <Search class="h-4 w-4 text-muted-foreground" />
                        <Input
                            ref="inputRef"
                            v-model="query"
                            :placeholder="t('commands.placeholder')"
                            class="border-0 shadow-none focus-visible:ring-0"
                            @keydown="handleKeydown"
                        />
                    </div>
                    <div class="max-h-[300px] overflow-y-auto p-2">
                        <template
                            v-for="(items, section) in groupedCommands"
                            :key="section"
                        >
                            <p
                                class="px-2 py-1 text-xs font-medium text-muted-foreground"
                            >
                                {{ section }}
                            </p>
                            <button
                                v-for="cmd in items"
                                :key="cmd.id"
                                class="flex w-full items-center gap-3 rounded-md px-3 py-2 text-sm transition-colors hover:bg-muted"
                                @click="executeCommand(cmd)"
                            >
                                <component
                                    :is="cmd.icon"
                                    class="h-4 w-4 text-muted-foreground"
                                />
                                {{ cmd.label }}
                            </button>
                        </template>
                        <p
                            v-if="filteredCommands.length === 0"
                            class="px-3 py-6 text-center text-sm text-muted-foreground"
                        >
                            {{ t('commands.empty') }}
                        </p>
                    </div>
                </div>
            </div>
        </Transition>
    </Teleport>
</template>

<style scoped>
.fade-enter-active,
.fade-leave-active {
    transition: opacity 0.15s ease;
}
.fade-enter-from,
.fade-leave-to {
    opacity: 0;
}
</style>
