<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import {
    Archive,
    Boxes,
    FolderKanban,
    Megaphone,
    Palette,
    Plus,
    Rocket,
    Server,
    Smartphone,
    Sparkles,
} from '@lucide/vue';
import { computed, ref } from 'vue';
import type { Component } from 'vue';
import ProjectCreateDialog from '@/components/project/ProjectCreateDialog.vue';
import EmptyState from '@/components/shared/EmptyState.vue';
import WorkspaceMetric from '@/components/shared/WorkspaceMetric.vue';
import WorkspacePageHeader from '@/components/shared/WorkspacePageHeader.vue';
import { Button } from '@/components/ui/button';
import { useWorkspaceUi } from '@/composables/useWorkspaceUi';
import { show as projectShow } from '@/routes/projects';
import type { Project, Workspace } from '@/types/models';

type ProjectFilter = 'all' | 'active' | 'archived';

const props = defineProps<{
    projects: { data: Project[] };
    workspace: Workspace;
}>();

const { copy, formatDate, formatNumber } = useWorkspaceUi();
const showCreateDialog = ref(false);
const activeFilter = ref<ProjectFilter>('all');

const activeProjects = computed(() =>
    props.projects.data.filter((project) => !project.is_archived),
);
const archivedProjects = computed(() =>
    props.projects.data.filter((project) => project.is_archived),
);
const totalTasks = computed(() =>
    props.projects.data.reduce(
        (total, project) => total + (project.todos_count ?? 0),
        0,
    ),
);
const visibleProjects = computed(() => {
    if (activeFilter.value === 'active') {
        return activeProjects.value;
    }

    if (activeFilter.value === 'archived') {
        return archivedProjects.value;
    }

    return props.projects.data;
});
const filters = computed(() => [
    {
        value: 'all' as const,
        label: copy.value.common.all,
        count: props.projects.data.length,
    },
    {
        value: 'active' as const,
        label: copy.value.projects.active,
        count: activeProjects.value.length,
    },
    {
        value: 'archived' as const,
        label: copy.value.projects.archived,
        count: archivedProjects.value.length,
    },
]);

function projectIcon(icon: string): Component {
    return (
        {
            rocket: Rocket,
            palette: Palette,
            smartphone: Smartphone,
            megaphone: Megaphone,
            server: Server,
            archive: Archive,
        }[icon] ?? FolderKanban
    );
}

function openCreateDialog(): void {
    if (props.workspace.id) {
        showCreateDialog.value = true;
    }
}
</script>

<template>
    <div>
        <Head :title="copy.projects.title" />

        <main class="min-h-full bg-muted/20 px-4 py-5 sm:p-6 lg:p-8">
            <div class="mx-auto flex max-w-[1480px] flex-col gap-6">
                <WorkspacePageHeader
                    :eyebrow="copy.projects.collection"
                    :title="copy.projects.title"
                    :description="copy.projects.description"
                >
                    <template #actions>
                        <Button
                            class="min-h-11 cursor-pointer rounded-xl bg-orange-600 px-4 text-white shadow-sm hover:bg-orange-700 focus-visible:ring-orange-500"
                            :disabled="!workspace.id"
                            @click="openCreateDialog"
                        >
                            <Plus class="size-4" aria-hidden="true" />
                            {{ copy.projects.new_project }}
                        </Button>
                    </template>

                    <template #metrics>
                        <WorkspaceMetric
                            :label="copy.projects.total"
                            :value="formatNumber(projects.data.length)"
                            :icon="Boxes"
                            tone="orange"
                        />
                        <WorkspaceMetric
                            :label="copy.projects.active"
                            :value="formatNumber(activeProjects.length)"
                            :icon="Sparkles"
                            tone="emerald"
                        />
                        <WorkspaceMetric
                            :label="copy.projects.task_count"
                            :value="formatNumber(totalTasks)"
                            :icon="FolderKanban"
                            tone="blue"
                        />
                    </template>
                </WorkspacePageHeader>

                <section
                    class="rounded-[1.5rem] border border-border/80 bg-card p-4 shadow-[0_20px_60px_-52px_rgba(15,23,42,0.6)] sm:p-6"
                >
                    <div
                        class="flex flex-col gap-4 border-b border-border/70 pb-5 sm:flex-row sm:items-center sm:justify-between"
                    >
                        <div>
                            <p
                                class="text-[0.68rem] font-semibold tracking-[0.16em] text-orange-700 uppercase dark:text-orange-300"
                            >
                                {{ copy.projects.workspace }}
                            </p>
                            <h2
                                class="mt-1.5 text-lg font-semibold tracking-tight"
                            >
                                {{ workspace.name }}
                            </h2>
                        </div>

                        <div
                            class="inline-flex w-fit max-w-full gap-1 overflow-x-auto rounded-xl bg-muted p-1"
                            role="tablist"
                            :aria-label="copy.common.filters"
                        >
                            <button
                                v-for="filter in filters"
                                :key="filter.value"
                                type="button"
                                role="tab"
                                :aria-selected="activeFilter === filter.value"
                                :class="[
                                    'flex min-h-10 min-w-max cursor-pointer items-center gap-2 rounded-lg px-3.5 text-sm font-medium transition-all focus-visible:ring-2 focus-visible:ring-orange-500 focus-visible:outline-none motion-reduce:transition-none',
                                    activeFilter === filter.value
                                        ? 'bg-card text-foreground shadow-sm'
                                        : 'text-muted-foreground hover:text-foreground',
                                ]"
                                @click="activeFilter = filter.value"
                            >
                                {{ filter.label }}
                                <span class="text-xs tabular-nums opacity-65">
                                    {{ formatNumber(filter.count) }}
                                </span>
                            </button>
                        </div>
                    </div>

                    <div
                        v-if="visibleProjects.length"
                        class="mt-6 grid gap-4 md:grid-cols-2 xl:grid-cols-3"
                    >
                        <Link
                            v-for="(project, index) in visibleProjects"
                            :key="project.id"
                            :href="projectShow({ workspace, project })"
                            prefetch
                            class="group relative min-h-64 cursor-pointer overflow-hidden rounded-[1.35rem] border border-border/80 bg-background p-5 transition-[border-color,box-shadow,transform] duration-200 hover:-translate-y-0.5 hover:border-orange-500/30 hover:shadow-[0_24px_50px_-38px_rgba(234,88,12,0.5)] focus-visible:ring-2 focus-visible:ring-orange-500 focus-visible:ring-offset-2 focus-visible:outline-none motion-reduce:transform-none sm:p-6"
                            :aria-label="`${copy.projects.open_project}: ${project.name}`"
                        >
                            <span
                                class="absolute inset-y-0 left-0 w-1.5"
                                :style="{ backgroundColor: project.color }"
                                aria-hidden="true"
                            />
                            <span
                                class="absolute -right-5 -bottom-11 text-[8.5rem] leading-none font-semibold tracking-[-0.1em] text-foreground/[0.025] select-none dark:text-white/[0.035]"
                                aria-hidden="true"
                            >
                                {{ String(index + 1).padStart(2, '0') }}
                            </span>

                            <div class="relative flex h-full flex-col">
                                <div
                                    class="flex items-start justify-between gap-4"
                                >
                                    <div
                                        class="flex size-12 items-center justify-center rounded-2xl"
                                        :style="{
                                            backgroundColor: `${project.color}18`,
                                            color: project.color,
                                        }"
                                    >
                                        <component
                                            :is="projectIcon(project.icon)"
                                            class="size-5"
                                            aria-hidden="true"
                                        />
                                    </div>
                                    <span
                                        :class="[
                                            'rounded-full px-2.5 py-1 text-[0.65rem] font-semibold tracking-[0.08em] uppercase',
                                            project.is_archived
                                                ? 'bg-muted text-muted-foreground'
                                                : 'bg-emerald-500/10 text-emerald-700 dark:text-emerald-300',
                                        ]"
                                    >
                                        {{
                                            project.is_archived
                                                ? copy.projects.archived
                                                : copy.projects.active
                                        }}
                                    </span>
                                </div>

                                <div class="mt-7">
                                    <h3
                                        class="text-lg font-semibold tracking-[-0.02em] group-hover:text-orange-700 dark:group-hover:text-orange-300"
                                    >
                                        {{ project.name }}
                                    </h3>
                                    <p
                                        class="mt-2 line-clamp-2 text-sm leading-6 text-muted-foreground"
                                    >
                                        {{
                                            project.description ??
                                            copy.projects.no_description
                                        }}
                                    </p>
                                </div>

                                <div
                                    class="mt-auto flex items-end justify-between gap-4 pt-7"
                                >
                                    <div>
                                        <p
                                            class="text-2xl font-semibold tracking-tight tabular-nums"
                                        >
                                            {{
                                                formatNumber(
                                                    project.todos_count ?? 0,
                                                )
                                            }}
                                        </p>
                                        <p
                                            class="mt-0.5 text-xs text-muted-foreground"
                                        >
                                            {{ copy.common.tasks }}
                                        </p>
                                    </div>
                                    <p
                                        class="text-right text-[0.68rem] leading-5 text-muted-foreground"
                                    >
                                        {{
                                            formatDate(project.updated_at, {
                                                month: 'short',
                                                day: 'numeric',
                                                year: 'numeric',
                                            })
                                        }}
                                    </p>
                                </div>
                            </div>
                        </Link>
                    </div>

                    <EmptyState
                        v-else
                        :title="copy.projects.empty_title"
                        :description="copy.projects.empty_description"
                        :action-label="
                            activeFilter === 'all'
                                ? copy.projects.create_first
                                : undefined
                        "
                        @action="openCreateDialog"
                    >
                        <template #icon>
                            <FolderKanban class="size-8" aria-hidden="true" />
                        </template>
                    </EmptyState>
                </section>
            </div>
        </main>

        <ProjectCreateDialog
            :open="showCreateDialog"
            :workspace-id="workspace.id"
            @close="showCreateDialog = false"
            @created="showCreateDialog = false"
        />
    </div>
</template>
