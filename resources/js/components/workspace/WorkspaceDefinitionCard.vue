<script setup lang="ts">
import { router, useHttp } from '@inertiajs/vue3';
import {
    Archive,
    ArrowDown,
    ArrowUp,
    CheckCircle2,
    Pencil,
    Plus,
    RotateCcw,
    Star,
    Trash2,
} from '@lucide/vue';
import { computed, ref } from 'vue';
import InputError from '@/components/InputError.vue';
import { Alert, AlertDescription } from '@/components/ui/alert';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import { Checkbox } from '@/components/ui/checkbox';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { Spinner } from '@/components/ui/spinner';
import { safeDefinitionColor } from '@/composables/useTaskDefinitions';
import { useToast } from '@/composables/useToast';
import { useUi } from '@/composables/useUi';
import type {
    TaskPriorityDefinition,
    TaskStatusDefinition,
} from '@/types/models';

type Definition = TaskStatusDefinition | TaskPriorityDefinition;

const props = defineProps<{
    kind: 'status' | 'priority';
    definitions: Definition[];
    search: string;
    locale: string;
    canManage: boolean;
    storeUrl: string;
    updateUrl: (id: string) => string;
    manageUrl: (id: string) => string;
    deleteUrl: (id: string) => string;
    reorderUrl: string;
    reloadProps: string[];
}>();

const { formatNumber, t } = useUi();
const toast = useToast();
const editing = ref<Definition | null>(null);
const deleting = ref<Definition | null>(null);
const createForm = useHttp({
    name: '',
    color: props.kind === 'status' ? '#64748b' : '#94a3b8',
    is_completed: false,
});
const editForm = useHttp({
    name: '',
    color: '#64748b',
    is_completed: false,
});
const manageRequest = useHttp({ operation: '' });
const reorderRequest = useHttp<{ ids: string[] }>({ ids: [] });
const deleteRequest = useHttp<{ replacement_id: string | null }>({
    replacement_id: null,
});

const section = computed(() =>
    props.kind === 'status' ? 'statuses' : 'priorities',
);
const filteredDefinitions = computed(() => {
    const query = props.search.trim().toLocaleLowerCase(props.locale);

    if (!query) {
        return props.definitions;
    }

    return props.definitions.filter(
        (definition) =>
            definition.name.toLocaleLowerCase(props.locale).includes(query) ||
            definition.key.toLocaleLowerCase(props.locale).includes(query),
    );
});
const replacementOptions = computed(() =>
    props.definitions.filter(
        (definition) =>
            definition.id !== deleting.value?.id && !definition.is_archived,
    ),
);

function isStatus(definition: Definition): definition is TaskStatusDefinition {
    return 'is_completed' in definition;
}

function reload(): void {
    router.reload({ only: props.reloadProps });
}

function notify(action: string): void {
    toast.success(
        t(
            'workspaces.management.configuration.' +
                section.value +
                '.' +
                action,
        ),
    );
}

async function createDefinition(): Promise<void> {
    createForm.name = createForm.name.trim();

    try {
        await createForm.post(props.storeUrl);

        if (createForm.wasSuccessful) {
            notify('created');
            createForm.resetAndClearErrors();
            createForm.name = '';
            createForm.color = props.kind === 'status' ? '#64748b' : '#94a3b8';
            createForm.is_completed = false;
            reload();
        }
    } catch {
        notify('create_failed');
    }
}

function startEditing(definition: Definition): void {
    editing.value = definition;
    editForm.name = definition.name;
    editForm.color = definition.color;
    editForm.is_completed = isStatus(definition) && definition.is_completed;
    editForm.clearErrors();
}

async function saveDefinition(): Promise<void> {
    if (!editing.value) {
        return;
    }

    editForm.name = editForm.name.trim();

    try {
        await editForm.put(props.updateUrl(editing.value.id));

        if (editForm.wasSuccessful) {
            notify('updated');
            editing.value = null;
            reload();
        }
    } catch {
        notify('save_failed');
    }
}

async function manageDefinition(
    definition: Definition,
    operation: string,
): Promise<void> {
    manageRequest.operation = operation;

    try {
        await manageRequest.patch(props.manageUrl(definition.id));

        if (manageRequest.wasSuccessful) {
            notify('updated');
            reload();
        }
    } catch {
        notify('save_failed');
    }
}

async function moveDefinition(id: string, offset: -1 | 1): Promise<void> {
    const definitions = [...props.definitions];
    const index = definitions.findIndex((definition) => definition.id === id);
    const destination = index + offset;

    if (index < 0 || destination < 0 || destination >= definitions.length) {
        return;
    }

    [definitions[index], definitions[destination]] = [
        definitions[destination],
        definitions[index],
    ];
    reorderRequest.ids = definitions.map((definition) => definition.id);

    try {
        await reorderRequest.put(props.reorderUrl);

        if (reorderRequest.wasSuccessful) {
            reload();
        }
    } catch {
        toast.error(t('workspaces.management.configuration.reorder_failed'));
    }
}

function startDeleting(definition: Definition): void {
    deleting.value = definition;
    deleteRequest.replacement_id = null;
    deleteRequest.clearErrors();
}

function needsReplacement(definition: Definition): boolean {
    return (
        (definition.todos_count ?? 0) > 0 ||
        definition.is_default ||
        (isStatus(definition) && definition.is_completion_target)
    );
}

async function deleteDefinition(): Promise<void> {
    if (!deleting.value) {
        return;
    }

    try {
        await deleteRequest.delete(props.deleteUrl(deleting.value.id));

        if (deleteRequest.wasSuccessful) {
            notify('deleted');
            deleting.value = null;
            reload();
        }
    } catch {
        notify('delete_failed');
    }
}
</script>

<template>
    <Card
        :class="
            kind === 'status' ? 'border-emerald-500/15' : 'border-amber-500/15'
        "
    >
        <CardHeader>
            <CardTitle>
                {{
                    t(
                        'workspaces.management.configuration.' +
                            section +
                            '.title',
                    )
                }}
            </CardTitle>
            <CardDescription>
                {{
                    t(
                        'workspaces.management.configuration.' +
                            section +
                            '.description',
                    )
                }}
            </CardDescription>
        </CardHeader>
        <CardContent class="space-y-5">
            <form
                v-if="canManage"
                class="grid gap-3 rounded-xl border bg-muted/20 p-4 sm:grid-cols-[minmax(0,1fr)_4rem_auto] sm:items-end"
                @submit.prevent="createDefinition"
            >
                <div class="space-y-2">
                    <Label :for="'new-' + kind + '-name'">
                        {{ t('workspaces.management.configuration.name') }}
                    </Label>
                    <Input
                        :id="'new-' + kind + '-name'"
                        v-model="createForm.name"
                        :placeholder="
                            t(
                                'workspaces.management.configuration.' +
                                    section +
                                    '.placeholder',
                            )
                        "
                        :disabled="createForm.processing"
                    />
                    <InputError :message="createForm.errors.name" />
                    <label
                        v-if="kind === 'status'"
                        class="flex items-center gap-2 text-sm"
                    >
                        <Checkbox
                            :model-value="createForm.is_completed"
                            :disabled="createForm.processing"
                            @update:model-value="
                                createForm.is_completed = $event === true
                            "
                        />
                        {{
                            t(
                                'workspaces.management.configuration.statuses.completed_semantic',
                            )
                        }}
                    </label>
                </div>
                <div class="space-y-2">
                    <Label :for="'new-' + kind + '-color'">
                        {{ t('workspaces.management.configuration.color') }}
                    </Label>
                    <Input
                        :id="'new-' + kind + '-color'"
                        v-model="createForm.color"
                        type="color"
                        class="h-10 w-full cursor-pointer p-1"
                    />
                </div>
                <Button
                    type="submit"
                    :disabled="createForm.processing || !createForm.name.trim()"
                >
                    <Spinner v-if="createForm.processing" />
                    <Plus v-else aria-hidden="true" />
                    {{ t('workspaces.management.configuration.create') }}
                </Button>
            </form>

            <ul
                v-if="filteredDefinitions.length"
                class="divide-y rounded-xl border"
            >
                <li
                    v-for="(definition, index) in filteredDefinitions"
                    :key="definition.id"
                    class="space-y-3 p-4"
                >
                    <form
                        v-if="editing?.id === definition.id"
                        class="grid gap-3 sm:grid-cols-[minmax(0,1fr)_4rem_auto]"
                        @submit.prevent="saveDefinition"
                    >
                        <div class="space-y-2">
                            <Input v-model="editForm.name" />
                            <InputError :message="editForm.errors.name" />
                            <label
                                v-if="kind === 'status'"
                                class="flex items-center gap-2 text-sm"
                            >
                                <Checkbox
                                    :model-value="editForm.is_completed"
                                    @update:model-value="
                                        editForm.is_completed = $event === true
                                    "
                                />
                                {{
                                    t(
                                        'workspaces.management.configuration.statuses.completed_semantic',
                                    )
                                }}
                            </label>
                        </div>
                        <Input
                            v-model="editForm.color"
                            type="color"
                            class="h-10 w-full cursor-pointer p-1"
                        />
                        <div class="flex gap-2">
                            <Button
                                type="button"
                                variant="outline"
                                @click="editing = null"
                            >
                                {{ t('common.actions.cancel') }}
                            </Button>
                            <Button
                                type="submit"
                                :disabled="editForm.processing"
                            >
                                <Spinner v-if="editForm.processing" />
                                {{ t('common.actions.save') }}
                            </Button>
                        </div>
                    </form>

                    <template v-else>
                        <div
                            class="flex flex-wrap items-center justify-between gap-3"
                        >
                            <div class="flex min-w-0 items-center gap-3">
                                <span
                                    class="size-4 shrink-0 rounded-full border border-black/10"
                                    :style="{
                                        backgroundColor: safeDefinitionColor(
                                            definition.color,
                                        ),
                                    }"
                                    aria-hidden="true"
                                />
                                <div class="min-w-0">
                                    <p class="truncate font-medium">
                                        {{ definition.name }}
                                    </p>
                                    <p class="text-xs text-muted-foreground">
                                        {{ definition.key }}
                                    </p>
                                </div>
                            </div>
                            <div class="flex flex-wrap gap-1.5">
                                <Badge
                                    v-if="definition.is_default"
                                    variant="secondary"
                                >
                                    {{
                                        t(
                                            'workspaces.management.configuration.default',
                                        )
                                    }}
                                </Badge>
                                <Badge
                                    v-if="
                                        isStatus(definition) &&
                                        definition.is_completed
                                    "
                                    variant="outline"
                                >
                                    {{
                                        t(
                                            'workspaces.management.configuration.statuses.completed',
                                        )
                                    }}
                                </Badge>
                                <Badge
                                    v-if="
                                        isStatus(definition) &&
                                        definition.is_completion_target
                                    "
                                >
                                    {{
                                        t(
                                            'workspaces.management.configuration.statuses.completion_target',
                                        )
                                    }}
                                </Badge>
                                <Badge
                                    v-if="definition.is_archived"
                                    variant="destructive"
                                >
                                    {{
                                        t(
                                            'workspaces.management.configuration.archived',
                                        )
                                    }}
                                </Badge>
                                <Badge variant="outline">
                                    {{
                                        formatNumber(
                                            definition.todos_count ?? 0,
                                        )
                                    }}
                                </Badge>
                            </div>
                        </div>

                        <div v-if="canManage" class="flex flex-wrap gap-2">
                            <Button
                                size="icon"
                                variant="outline"
                                :disabled="
                                    index === 0 || reorderRequest.processing
                                "
                                :aria-label="
                                    t(
                                        'workspaces.management.configuration.move_up',
                                        { name: definition.name },
                                    )
                                "
                                @click="moveDefinition(definition.id, -1)"
                            >
                                <ArrowUp aria-hidden="true" />
                            </Button>
                            <Button
                                size="icon"
                                variant="outline"
                                :disabled="
                                    index === filteredDefinitions.length - 1 ||
                                    reorderRequest.processing
                                "
                                :aria-label="
                                    t(
                                        'workspaces.management.configuration.move_down',
                                        { name: definition.name },
                                    )
                                "
                                @click="moveDefinition(definition.id, 1)"
                            >
                                <ArrowDown aria-hidden="true" />
                            </Button>
                            <Button
                                size="sm"
                                variant="outline"
                                @click="startEditing(definition)"
                            >
                                <Pencil aria-hidden="true" />
                                {{ t('common.actions.edit') }}
                            </Button>
                            <Button
                                v-if="
                                    !definition.is_default &&
                                    !definition.is_archived &&
                                    (!isStatus(definition) ||
                                        !definition.is_completed)
                                "
                                size="sm"
                                variant="outline"
                                @click="
                                    manageDefinition(definition, 'set_default')
                                "
                            >
                                <Star aria-hidden="true" />
                                {{
                                    t(
                                        'workspaces.management.configuration.set_default',
                                    )
                                }}
                            </Button>
                            <Button
                                v-if="
                                    isStatus(definition) &&
                                    definition.is_completed &&
                                    !definition.is_completion_target &&
                                    !definition.is_archived
                                "
                                size="sm"
                                variant="outline"
                                @click="
                                    manageDefinition(
                                        definition,
                                        'set_completion_target',
                                    )
                                "
                            >
                                <CheckCircle2 aria-hidden="true" />
                                {{
                                    t(
                                        'workspaces.management.configuration.statuses.set_completion_target',
                                    )
                                }}
                            </Button>
                            <Button
                                v-if="definition.is_archived"
                                size="sm"
                                variant="outline"
                                @click="manageDefinition(definition, 'restore')"
                            >
                                <RotateCcw aria-hidden="true" />
                                {{
                                    t(
                                        'workspaces.management.configuration.restore',
                                    )
                                }}
                            </Button>
                            <Button
                                v-else-if="definition.permissions?.archive"
                                size="sm"
                                variant="outline"
                                @click="manageDefinition(definition, 'archive')"
                            >
                                <Archive aria-hidden="true" />
                                {{
                                    t(
                                        'workspaces.management.configuration.archive',
                                    )
                                }}
                            </Button>
                            <Button
                                size="icon"
                                variant="ghost"
                                class="text-destructive"
                                :aria-label="
                                    t(
                                        'workspaces.management.configuration.delete_action',
                                        { name: definition.name },
                                    )
                                "
                                @click="startDeleting(definition)"
                            >
                                <Trash2 aria-hidden="true" />
                            </Button>
                        </div>
                    </template>
                </li>
            </ul>

            <Alert v-if="deleting" variant="destructive">
                <Trash2 aria-hidden="true" />
                <AlertDescription class="space-y-3">
                    <p>
                        {{
                            t(
                                'workspaces.management.configuration.delete_description',
                                {
                                    name: deleting.name,
                                    count: formatNumber(
                                        deleting.todos_count ?? 0,
                                    ),
                                },
                            )
                        }}
                    </p>
                    <div v-if="needsReplacement(deleting)" class="space-y-2">
                        <Label :for="'replacement-' + kind">
                            {{
                                t(
                                    'workspaces.management.configuration.replacement',
                                )
                            }}
                        </Label>
                        <Select v-model="deleteRequest.replacement_id">
                            <SelectTrigger :id="'replacement-' + kind">
                                <SelectValue
                                    :placeholder="
                                        t(
                                            'workspaces.management.configuration.choose_replacement',
                                        )
                                    "
                                />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem
                                    v-for="option in replacementOptions"
                                    :key="option.id"
                                    :value="option.id"
                                >
                                    {{ option.name }}
                                </SelectItem>
                            </SelectContent>
                        </Select>
                        <InputError
                            :message="deleteRequest.errors.replacement_id"
                        />
                    </div>
                    <div class="flex gap-2">
                        <Button variant="outline" @click="deleting = null">
                            {{ t('common.actions.cancel') }}
                        </Button>
                        <Button
                            variant="destructive"
                            :disabled="
                                deleteRequest.processing ||
                                (needsReplacement(deleting) &&
                                    !deleteRequest.replacement_id)
                            "
                            @click="deleteDefinition"
                        >
                            <Spinner v-if="deleteRequest.processing" />
                            {{ t('common.actions.delete') }}
                        </Button>
                    </div>
                </AlertDescription>
            </Alert>
        </CardContent>
    </Card>
</template>
