<script setup lang="ts">
import {
    ArrowDownAZ,
    Columns3,
    List,
    Search,
    SlidersHorizontal,
    X,
} from '@lucide/vue';
import { ref, watch } from 'vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import {
    Sheet,
    SheetContent,
    SheetDescription,
    SheetHeader,
    SheetTitle,
} from '@/components/ui/sheet';
import { useTaskDefinitions } from '@/composables/useTaskDefinitions';
import { useUi } from '@/composables/useUi';
import type { TodoFilters } from '@/types/api';
import type { Project, TaskDefinitionCatalog } from '@/types/models';

const props = defineProps<{
    filters: TodoFilters;
    projects: Project[];
    taskDefinitions: TaskDefinitionCatalog;
    processing: boolean;
}>();
const emit = defineEmits<{ update: [filters: TodoFilters] }>();
const { t } = useUi();
const { statuses, priorities } = useTaskDefinitions(
    () => props.taskDefinitions,
);
const search = ref('');
const projectId = ref('all');
const status = ref('all');
const priority = ref('all');
const sort = ref('default');
const direction = ref<'asc' | 'desc'>('asc');
const perPage = ref<'100' | '25' | '50'>('50');
const view = ref<'board' | 'list'>('list');
const mobileFiltersOpen = ref(false);

watch(
    () => props.filters,
    (filters) => {
        search.value = filters.search ?? '';
        projectId.value = filters.project_id ?? 'all';
        status.value = filters.status ?? 'all';
        priority.value = filters.priority ?? 'all';
        sort.value = filters.sort ?? 'default';
        direction.value = filters.direction ?? 'asc';
        perPage.value = String(filters.per_page ?? 50) as '100' | '25' | '50';
        view.value = filters.view ?? 'list';
    },
    { immediate: true, deep: true },
);

function currentFilters(): TodoFilters {
    return {
        search: search.value || undefined,
        project_id: projectId.value === 'all' ? undefined : projectId.value,
        status: status.value === 'all' ? undefined : status.value,
        priority: priority.value === 'all' ? undefined : priority.value,
        sort: sort.value === 'default' ? undefined : sort.value,
        direction: direction.value,
        per_page: Number(perPage.value) as 25 | 50 | 100,
        view: view.value,
    };
}

function apply(): void {
    mobileFiltersOpen.value = false;
    emit('update', currentFilters());
}

function clear(): void {
    search.value = '';
    projectId.value = 'all';
    status.value = 'all';
    priority.value = 'all';
    sort.value = 'default';
    direction.value = 'asc';
    apply();
}

function setView(nextView: 'board' | 'list'): void {
    view.value = nextView;
    apply();
}
</script>

<template>
    <div class="space-y-4 border-b border-border/70 pb-5">
        <div class="flex flex-col gap-3 lg:flex-row lg:items-center">
            <form
                class="relative min-w-0 flex-1"
                role="search"
                @submit.prevent="apply"
            >
                <Search
                    class="pointer-events-none absolute top-1/2 left-3.5 size-4 -translate-y-1/2 text-muted-foreground"
                    aria-hidden="true"
                />
                <Input
                    v-model="search"
                    type="search"
                    :placeholder="t('tasks.filters.search')"
                    class="pl-10"
                    :disabled="processing"
                />
            </form>

            <div class="flex items-center gap-2">
                <Button
                    type="button"
                    variant="outline"
                    class="md:hidden"
                    :disabled="processing"
                    @click="mobileFiltersOpen = true"
                >
                    <SlidersHorizontal class="size-4" aria-hidden="true" />
                    {{ t('tasks.filters.filters') }}
                </Button>
                <div
                    class="ml-auto flex rounded-lg border border-border/80 bg-muted/25 p-1"
                    role="group"
                    :aria-label="t('tasks.filters.view')"
                >
                    <Button
                        type="button"
                        size="sm"
                        :variant="view === 'list' ? 'secondary' : 'ghost'"
                        :aria-pressed="view === 'list'"
                        :disabled="processing"
                        @click="setView('list')"
                    >
                        <List class="size-4" aria-hidden="true" />
                        {{ t('tasks.filters.list') }}
                    </Button>
                    <Button
                        type="button"
                        size="sm"
                        :variant="view === 'board' ? 'secondary' : 'ghost'"
                        :aria-pressed="view === 'board'"
                        :disabled="processing"
                        @click="setView('board')"
                    >
                        <Columns3 class="size-4" aria-hidden="true" />
                        {{ t('tasks.filters.board') }}
                    </Button>
                </div>
            </div>
        </div>

        <div class="hidden gap-3 md:grid md:grid-cols-3 xl:grid-cols-6">
            <Select
                v-model="projectId"
                :disabled="processing"
                @update:model-value="apply"
            >
                <SelectTrigger
                    ><SelectValue :placeholder="t('tasks.filters.project')"
                /></SelectTrigger>
                <SelectContent>
                    <SelectItem value="all">{{
                        t('tasks.filters.all_projects')
                    }}</SelectItem>
                    <SelectItem
                        v-for="project in projects"
                        :key="project.id"
                        :value="project.id"
                    >
                        {{ project.name }}
                    </SelectItem>
                </SelectContent>
            </Select>
            <Select
                v-model="status"
                :disabled="processing"
                @update:model-value="apply"
            >
                <SelectTrigger
                    ><SelectValue :placeholder="t('tasks.filters.status')"
                /></SelectTrigger>
                <SelectContent>
                    <SelectItem value="all">{{
                        t('tasks.filters.all_statuses')
                    }}</SelectItem>
                    <SelectItem
                        v-for="item in statuses"
                        :key="item.id"
                        :value="item.key"
                    >
                        {{ item.name }}
                    </SelectItem>
                </SelectContent>
            </Select>
            <Select
                v-model="priority"
                :disabled="processing"
                @update:model-value="apply"
            >
                <SelectTrigger
                    ><SelectValue :placeholder="t('tasks.filters.priority')"
                /></SelectTrigger>
                <SelectContent>
                    <SelectItem value="all">{{
                        t('tasks.filters.all_priorities')
                    }}</SelectItem>
                    <SelectItem
                        v-for="item in priorities"
                        :key="item.id"
                        :value="item.key"
                    >
                        {{ item.name }}
                    </SelectItem>
                </SelectContent>
            </Select>
            <Select
                v-model="sort"
                :disabled="processing"
                @update:model-value="apply"
            >
                <SelectTrigger
                    ><SelectValue :placeholder="t('tasks.filters.sort')"
                /></SelectTrigger>
                <SelectContent>
                    <SelectItem value="default">{{
                        t('tasks.filters.default_order')
                    }}</SelectItem>
                    <SelectItem value="due_date">{{
                        t('tasks.filters.due_date')
                    }}</SelectItem>
                    <SelectItem value="priority">{{
                        t('tasks.filters.priority')
                    }}</SelectItem>
                    <SelectItem value="status">{{
                        t('tasks.filters.status')
                    }}</SelectItem>
                    <SelectItem value="title">{{
                        t('tasks.filters.title')
                    }}</SelectItem>
                    <SelectItem value="created_at">{{
                        t('tasks.filters.created')
                    }}</SelectItem>
                </SelectContent>
            </Select>
            <Button
                type="button"
                variant="outline"
                :disabled="processing"
                @click="
                    direction = direction === 'asc' ? 'desc' : 'asc';
                    apply();
                "
            >
                <ArrowDownAZ class="size-4" aria-hidden="true" />
                {{
                    direction === 'asc'
                        ? t('tasks.filters.ascending')
                        : t('tasks.filters.descending')
                }}
            </Button>
            <div class="flex gap-2">
                <Select
                    v-model="perPage"
                    :disabled="processing"
                    @update:model-value="apply"
                >
                    <SelectTrigger><SelectValue /></SelectTrigger>
                    <SelectContent>
                        <SelectItem value="25">25</SelectItem>
                        <SelectItem value="50">50</SelectItem>
                        <SelectItem value="100">100</SelectItem>
                    </SelectContent>
                </Select>
                <Button
                    type="button"
                    variant="ghost"
                    size="icon"
                    :aria-label="t('tasks.filters.clear')"
                    :disabled="processing"
                    @click="clear"
                >
                    <X class="size-4" aria-hidden="true" />
                </Button>
            </div>
        </div>

        <Sheet
            :open="mobileFiltersOpen"
            @update:open="mobileFiltersOpen = $event"
        >
            <SheetContent
                side="bottom"
                class="max-h-[92vh] overflow-y-auto rounded-t-[1.75rem]"
            >
                <SheetHeader>
                    <SheetTitle>{{ t('tasks.filters.filters') }}</SheetTitle>
                    <SheetDescription>{{
                        t('tasks.filters.description')
                    }}</SheetDescription>
                </SheetHeader>
                <div class="grid gap-4 px-4 pb-6">
                    <Select v-model="projectId">
                        <SelectTrigger
                            ><SelectValue
                                :placeholder="t('tasks.filters.project')"
                        /></SelectTrigger>
                        <SelectContent>
                            <SelectItem value="all">{{
                                t('tasks.filters.all_projects')
                            }}</SelectItem>
                            <SelectItem
                                v-for="project in projects"
                                :key="project.id"
                                :value="project.id"
                                >{{ project.name }}</SelectItem
                            >
                        </SelectContent>
                    </Select>
                    <Select v-model="status">
                        <SelectTrigger
                            ><SelectValue
                                :placeholder="t('tasks.filters.status')"
                        /></SelectTrigger>
                        <SelectContent>
                            <SelectItem value="all">{{
                                t('tasks.filters.all_statuses')
                            }}</SelectItem>
                            <SelectItem
                                v-for="item in statuses"
                                :key="item.id"
                                :value="item.key"
                                >{{ item.name }}</SelectItem
                            >
                        </SelectContent>
                    </Select>
                    <Select v-model="priority">
                        <SelectTrigger
                            ><SelectValue
                                :placeholder="t('tasks.filters.priority')"
                        /></SelectTrigger>
                        <SelectContent>
                            <SelectItem value="all">{{
                                t('tasks.filters.all_priorities')
                            }}</SelectItem>
                            <SelectItem
                                v-for="item in priorities"
                                :key="item.id"
                                :value="item.key"
                                >{{ item.name }}</SelectItem
                            >
                        </SelectContent>
                    </Select>
                    <Select v-model="sort">
                        <SelectTrigger
                            ><SelectValue
                                :placeholder="t('tasks.filters.sort')"
                        /></SelectTrigger>
                        <SelectContent>
                            <SelectItem value="default">{{
                                t('tasks.filters.default_order')
                            }}</SelectItem>
                            <SelectItem value="due_date">{{
                                t('tasks.filters.due_date')
                            }}</SelectItem>
                            <SelectItem value="priority">{{
                                t('tasks.filters.priority')
                            }}</SelectItem>
                            <SelectItem value="status">{{
                                t('tasks.filters.status')
                            }}</SelectItem>
                            <SelectItem value="title">{{
                                t('tasks.filters.title')
                            }}</SelectItem>
                            <SelectItem value="created_at">{{
                                t('tasks.filters.created')
                            }}</SelectItem>
                        </SelectContent>
                    </Select>
                    <Button
                        type="button"
                        variant="outline"
                        @click="
                            direction = direction === 'asc' ? 'desc' : 'asc'
                        "
                    >
                        <ArrowDownAZ class="size-4" aria-hidden="true" />
                        {{
                            direction === 'asc'
                                ? t('tasks.filters.ascending')
                                : t('tasks.filters.descending')
                        }}
                    </Button>
                    <Select v-model="perPage">
                        <SelectTrigger><SelectValue /></SelectTrigger>
                        <SelectContent>
                            <SelectItem value="25">25</SelectItem>
                            <SelectItem value="50">50</SelectItem>
                            <SelectItem value="100">100</SelectItem>
                        </SelectContent>
                    </Select>
                    <div class="grid grid-cols-2 gap-3">
                        <Button
                            type="button"
                            variant="outline"
                            @click="clear"
                            >{{ t('tasks.filters.clear') }}</Button
                        >
                        <Button type="button" @click="apply">{{
                            t('tasks.filters.apply')
                        }}</Button>
                    </div>
                </div>
            </SheetContent>
        </Sheet>
    </div>
</template>
