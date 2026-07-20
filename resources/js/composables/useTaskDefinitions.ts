import { computed, toValue } from 'vue';
import type { MaybeRefOrGetter } from 'vue';
import type {
    TaskDefinitionCatalog,
    TaskPriorityDefinition,
    TaskStatusDefinition,
} from '@/types/models';

export function useTaskDefinitions(
    catalog: MaybeRefOrGetter<TaskDefinitionCatalog>,
) {
    const statuses = computed(() =>
        [...toValue(catalog).statuses]
            .filter((status) => !status.is_archived)
            .sort((left, right) => left.position - right.position),
    );
    const priorities = computed(() =>
        [...toValue(catalog).priorities]
            .filter((priority) => !priority.is_archived)
            .sort((left, right) => left.position - right.position),
    );
    const statusByKey = computed(
        () =>
            new Map<string, TaskStatusDefinition>(
                toValue(catalog).statuses.map((status) => [status.key, status]),
            ),
    );
    const priorityByKey = computed(
        () =>
            new Map<string, TaskPriorityDefinition>(
                toValue(catalog).priorities.map((priority) => [
                    priority.key,
                    priority,
                ]),
            ),
    );

    return {
        statuses,
        priorities,
        statusByKey,
        priorityByKey,
        defaultStatus: computed(() =>
            statuses.value.find((status) => status.is_default),
        ),
        defaultPriority: computed(() =>
            priorities.value.find((priority) => priority.is_default),
        ),
    };
}

export function safeDefinitionColor(
    color: string | null | undefined,
    fallback = '#64748b',
): string {
    return /^#[0-9a-f]{6}$/i.test(color ?? '') ? (color as string) : fallback;
}
