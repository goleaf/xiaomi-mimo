<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { ChevronLeft, ChevronRight } from '@lucide/vue';
import { Button } from '@/components/ui/button';
import { useUi } from '@/composables/useUi';
import type { PaginatedResponse } from '@/types/api';
import type { Todo } from '@/types/models';

defineProps<{ pagination: PaginatedResponse<Todo>; processing: boolean }>();
const { formatNumber, t } = useUi();
</script>

<template>
    <nav
        v-if="pagination.last_page > 1"
        class="mt-5 flex flex-col gap-3 border-t border-border/70 pt-4 sm:flex-row sm:items-center sm:justify-between"
        :aria-label="t('tasks.pagination.label')"
    >
        <p class="text-sm text-muted-foreground">
            {{
                t('tasks.pagination.range', {
                    from: formatNumber(pagination.from ?? 0),
                    to: formatNumber(pagination.to ?? 0),
                    total: formatNumber(pagination.total),
                })
            }}
        </p>
        <div class="flex gap-2">
            <Button
                v-if="pagination.prev_page_url"
                as-child
                variant="outline"
                size="sm"
            >
                <Link
                    :href="pagination.prev_page_url"
                    :only="['todos', 'filters', 'stats']"
                    preserve-scroll
                    preserve-state
                    :aria-disabled="processing"
                >
                    <ChevronLeft class="size-4" aria-hidden="true" />
                    {{ t('tasks.pagination.previous') }}
                </Link>
            </Button>
            <Button v-else variant="outline" size="sm" disabled>
                <ChevronLeft class="size-4" aria-hidden="true" />
                {{ t('tasks.pagination.previous') }}
            </Button>
            <Button
                v-if="pagination.next_page_url"
                as-child
                variant="outline"
                size="sm"
            >
                <Link
                    :href="pagination.next_page_url"
                    :only="['todos', 'filters', 'stats']"
                    preserve-scroll
                    preserve-state
                    :aria-disabled="processing"
                >
                    {{ t('tasks.pagination.next') }}
                    <ChevronRight class="size-4" aria-hidden="true" />
                </Link>
            </Button>
            <Button v-else variant="outline" size="sm" disabled>
                {{ t('tasks.pagination.next') }}
                <ChevronRight class="size-4" aria-hidden="true" />
            </Button>
        </div>
    </nav>
</template>
