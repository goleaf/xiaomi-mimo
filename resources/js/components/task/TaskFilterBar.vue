<script setup lang="ts">
import { Search, SlidersHorizontal, X } from '@lucide/vue';
import { ref } from 'vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { useUi } from '@/composables/useUi';

const props = defineProps<{
    filters: Record<string, string>;
    workspaceId: string;
}>();

const emit = defineEmits<{ update: [filters: Record<string, string>] }>();
const { t } = useUi();

const searchQuery = ref(props.filters.search ?? '');
const statusFilter = ref(props.filters.status ?? 'all');
const priorityFilter = ref(props.filters.priority ?? 'all');
const showAdvanced = ref(false);

function applyFilters() {
    const filters: Record<string, string> = {};

    if (searchQuery.value) {
        filters.search = searchQuery.value;
    }

    if (statusFilter.value !== 'all') {
        filters.status = statusFilter.value;
    }

    if (priorityFilter.value !== 'all') {
        filters.priority = priorityFilter.value;
    }

    emit('update', filters);
}

function clearFilters() {
    searchQuery.value = '';
    statusFilter.value = 'all';
    priorityFilter.value = 'all';
    emit('update', {});
}
</script>

<template>
    <div class="space-y-3">
        <div class="flex items-center gap-3">
            <div class="relative max-w-sm flex-1">
                <Search
                    class="absolute top-1/2 left-3 h-4 w-4 -translate-y-1/2 text-muted-foreground"
                />
                <Input
                    v-model="searchQuery"
                    :placeholder="t('tasks.filters.search')"
                    class="pl-9"
                    @keyup.enter="applyFilters"
                />
            </div>
            <Select v-model="statusFilter" @update:model-value="applyFilters">
                <SelectTrigger class="w-[140px]"
                    ><SelectValue :placeholder="t('tasks.filters.status')"
                /></SelectTrigger>
                <SelectContent>
                    <SelectItem value="all">{{
                        t('tasks.filters.all_statuses')
                    }}</SelectItem>
                    <SelectItem value="pending">{{
                        t('tasks.statuses.pending')
                    }}</SelectItem>
                    <SelectItem value="in_progress">{{
                        t('tasks.statuses.in_progress')
                    }}</SelectItem>
                    <SelectItem value="completed">{{
                        t('tasks.statuses.completed')
                    }}</SelectItem>
                </SelectContent>
            </Select>
            <Select v-model="priorityFilter" @update:model-value="applyFilters">
                <SelectTrigger class="w-[140px]"
                    ><SelectValue :placeholder="t('tasks.filters.priority')"
                /></SelectTrigger>
                <SelectContent>
                    <SelectItem value="all">{{
                        t('tasks.filters.all_priorities')
                    }}</SelectItem>
                    <SelectItem value="urgent">{{
                        t('tasks.priorities.urgent')
                    }}</SelectItem>
                    <SelectItem value="high">{{
                        t('tasks.priorities.high')
                    }}</SelectItem>
                    <SelectItem value="medium">{{
                        t('tasks.priorities.medium')
                    }}</SelectItem>
                    <SelectItem value="low">{{
                        t('tasks.priorities.low')
                    }}</SelectItem>
                </SelectContent>
            </Select>
            <Button
                variant="ghost"
                size="sm"
                @click="showAdvanced = !showAdvanced"
            >
                <SlidersHorizontal class="h-4 w-4" />
            </Button>
            <Button
                v-if="
                    searchQuery ||
                    statusFilter !== 'all' ||
                    priorityFilter !== 'all'
                "
                variant="ghost"
                size="sm"
                @click="clearFilters"
            >
                <X class="h-4 w-4" />
            </Button>
        </div>
    </div>
</template>
