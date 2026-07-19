<script setup lang="ts">
import { Link, router, useHttp, usePage } from '@inertiajs/vue3';
import {
    Building2,
    Check,
    ChevronsUpDown,
    LoaderCircle,
    Settings2,
} from '@lucide/vue';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuSeparator,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import {
    SidebarMenu,
    SidebarMenuButton,
    SidebarMenuItem,
    useSidebar,
} from '@/components/ui/sidebar';
import { useToast } from '@/composables/useToast';
import { index as workspacesIndex, switchMethod } from '@/routes/workspaces';
import type { SidebarNavigationLabels } from '@/types';
import type { Workspace } from '@/types/models';

const props = defineProps<{
    workspaces: Workspace[];
    currentWorkspace: Workspace | null;
    labels: SidebarNavigationLabels;
}>();

type SwitchWorkspaceResponse = {
    workspace: Workspace;
};

const page = usePage();
const toast = useToast();
const { isMobile, state } = useSidebar();
const switchRequest = useHttp<Record<string, never>, SwitchWorkspaceResponse>(
    {},
);

async function switchWorkspace(workspace: Workspace) {
    if (workspace.id === props.currentWorkspace?.id) {
        return;
    }

    try {
        await switchRequest.post(switchMethod(workspace).url);
        router.visit(page.url, {
            replace: true,
            preserveScroll: false,
            preserveState: false,
        });
    } catch {
        toast.error(props.labels.switchingFailed);
    }
}
</script>

<template>
    <SidebarMenu>
        <SidebarMenuItem>
            <DropdownMenu>
                <DropdownMenuTrigger as-child>
                    <SidebarMenuButton
                        size="lg"
                        :tooltip="labels.workspace"
                        :disabled="switchRequest.processing"
                        class="border border-sidebar-border/80 bg-sidebar-accent/45 data-[state=open]:bg-sidebar-accent"
                    >
                        <span
                            class="flex aspect-square size-8 items-center justify-center rounded-md bg-background shadow-sm ring-1 ring-sidebar-border"
                        >
                            <Building2 class="size-4 text-orange-600" />
                        </span>
                        <span
                            class="grid min-w-0 flex-1 text-left leading-tight"
                        >
                            <span
                                class="truncate text-[10px] font-semibold tracking-widest text-sidebar-foreground/50 uppercase"
                            >
                                {{ labels.workspace }}
                            </span>
                            <span class="truncate text-sm font-medium">
                                {{
                                    currentWorkspace?.name ??
                                    labels.selectWorkspace
                                }}
                            </span>
                        </span>
                        <LoaderCircle
                            v-if="switchRequest.processing"
                            class="ml-auto size-4 animate-spin motion-reduce:animate-none"
                        />
                        <ChevronsUpDown
                            v-else
                            class="ml-auto size-4 opacity-60"
                        />
                    </SidebarMenuButton>
                </DropdownMenuTrigger>

                <DropdownMenuContent
                    class="w-(--reka-dropdown-menu-trigger-width) min-w-64 rounded-lg"
                    :side="
                        isMobile
                            ? 'bottom'
                            : state === 'collapsed'
                              ? 'right'
                              : 'bottom'
                    "
                    align="start"
                    :side-offset="6"
                >
                    <DropdownMenuItem
                        v-for="workspace in workspaces"
                        :key="workspace.id"
                        class="cursor-pointer gap-2"
                        :disabled="switchRequest.processing"
                        @click="switchWorkspace(workspace)"
                    >
                        <span
                            class="flex size-7 items-center justify-center rounded-md bg-muted"
                        >
                            <Building2 class="size-3.5" />
                        </span>
                        <span class="min-w-0 flex-1 truncate">
                            {{ workspace.name }}
                        </span>
                        <Check
                            v-if="workspace.id === currentWorkspace?.id"
                            class="size-4 text-orange-600"
                        />
                    </DropdownMenuItem>

                    <DropdownMenuSeparator />

                    <DropdownMenuItem :as-child="true">
                        <Link :href="workspacesIndex()" prefetch>
                            <Settings2 class="size-4" />
                            {{ labels.manageWorkspaces }}
                        </Link>
                    </DropdownMenuItem>
                </DropdownMenuContent>
            </DropdownMenu>
        </SidebarMenuItem>
    </SidebarMenu>
</template>
