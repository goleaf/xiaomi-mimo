<script setup lang="ts">
import { useHttp } from '@inertiajs/vue3';
import { ArrowDown, ArrowUp, Plus, Save, Trash2 } from '@lucide/vue';
import { reactive, ref, watch } from 'vue';
import InputError from '@/components/InputError.vue';
import WorkspaceConfirmDialog from '@/components/shared/WorkspaceConfirmDialog.vue';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import { Input } from '@/components/ui/input';
import { Spinner } from '@/components/ui/spinner';
import { useToast } from '@/composables/useToast';
import { useUi } from '@/composables/useUi';
import {
    destroy as destroyItem,
    reorder as reorderItems,
    store as storeItem,
    toggle as toggleItem,
    update as updateItem,
} from '@/routes/api/v1/checklist-items';
import {
    destroy as destroyChecklist,
    reorder as reorderChecklists,
    store as storeChecklist,
    update as updateChecklist,
} from '@/routes/api/v1/checklists';
import type { Checklist, ChecklistItem } from '@/types/models';

type DeleteTarget =
    | { type: 'checklist'; checklist: Checklist }
    | { type: 'item'; checklist: Checklist; item: ChecklistItem };

const props = defineProps<{ todoId: string; checklists: Checklist[] }>();
const emit = defineEmits<{ refresh: [] }>();
const toast = useToast();
const { t } = useUi();
const checklistDrafts = reactive<Record<string, string>>({});
const itemDrafts = reactive<Record<string, string>>({});
const newItemDrafts = reactive<Record<string, string>>({});
const newChecklistName = ref('');
const busyKey = ref<string | null>(null);
const deleteTarget = ref<DeleteTarget | null>(null);
const checklistRequest = useHttp<{ name: string }, { data: Checklist }>({
    name: '',
});
const itemRequest = useHttp<{ content: string }, { data: ChecklistItem }>({
    content: '',
});
const reorderRequest = useHttp<{ ids: string[] }, undefined>({ ids: [] });
const emptyRequest = useHttp<Record<string, never>, undefined>({});

function resetDrafts(): void {
    for (const key of Object.keys(checklistDrafts)) {
        delete checklistDrafts[key];
    }

    for (const key of Object.keys(itemDrafts)) {
        delete itemDrafts[key];
    }

    for (const key of Object.keys(newItemDrafts)) {
        delete newItemDrafts[key];
    }

    for (const checklist of props.checklists) {
        checklistDrafts[checklist.id] = checklist.name;

        for (const item of checklist.items ?? []) {
            itemDrafts[item.id] = item.content;
        }
    }

    newChecklistName.value = '';
    busyKey.value = null;
    deleteTarget.value = null;
}

watch(() => props.todoId, resetDrafts, { immediate: true, flush: 'sync' });
watch(
    () => props.checklists,
    () => {
        for (const checklist of props.checklists) {
            checklistDrafts[checklist.id] ??= checklist.name;

            for (const item of checklist.items ?? []) {
                itemDrafts[item.id] ??= item.content;
            }
        }
    },
    { deep: true },
);

async function createChecklist(): Promise<void> {
    if (!newChecklistName.value.trim() || busyKey.value) {
        return;
    }

    busyKey.value = 'checklist:new';
    checklistRequest.name = newChecklistName.value;

    try {
        await checklistRequest.post(storeChecklist(props.todoId).url);
        newChecklistName.value = '';
        emit('refresh');
    } catch {
        if (!checklistRequest.hasErrors) {
            toast.error(t('common.errors.generic'));
        }
    } finally {
        busyKey.value = null;
    }
}

async function renameChecklist(checklist: Checklist): Promise<void> {
    const name = checklistDrafts[checklist.id]?.trim();

    if (!name || name === checklist.name || busyKey.value) {
        return;
    }

    busyKey.value = `checklist:${checklist.id}`;
    checklistRequest.name = name;

    try {
        await checklistRequest.put(
            updateChecklist([props.todoId, checklist]).url,
        );
        emit('refresh');
    } catch {
        if (!checklistRequest.hasErrors) {
            toast.error(t('common.errors.generic'));
        }
    } finally {
        busyKey.value = null;
    }
}

async function createItem(checklist: Checklist): Promise<void> {
    const content = newItemDrafts[checklist.id]?.trim();

    if (!content || busyKey.value) {
        return;
    }

    busyKey.value = `item:new:${checklist.id}`;
    itemRequest.content = content;

    try {
        await itemRequest.post(storeItem([props.todoId, checklist]).url);
        delete newItemDrafts[checklist.id];
        emit('refresh');
    } catch {
        if (!itemRequest.hasErrors) {
            toast.error(t('common.errors.generic'));
        }
    } finally {
        busyKey.value = null;
    }
}

async function renameItem(
    checklist: Checklist,
    item: ChecklistItem,
): Promise<void> {
    const content = itemDrafts[item.id]?.trim();

    if (!content || content === item.content || busyKey.value) {
        return;
    }

    busyKey.value = `item:${item.id}`;
    itemRequest.content = content;

    try {
        await itemRequest.put(updateItem([props.todoId, checklist, item]).url);
        emit('refresh');
    } catch {
        if (!itemRequest.hasErrors) {
            toast.error(t('common.errors.generic'));
        }
    } finally {
        busyKey.value = null;
    }
}

async function toggle(
    checklist: Checklist,
    item: ChecklistItem,
): Promise<void> {
    if (busyKey.value) {
        return;
    }

    busyKey.value = `item:${item.id}`;

    try {
        await emptyRequest.patch(
            toggleItem([props.todoId, checklist, item]).url,
        );
        emit('refresh');
    } catch {
        toast.error(t('common.errors.generic'));
    } finally {
        busyKey.value = null;
    }
}

async function moveChecklist(index: number, offset: -1 | 1): Promise<void> {
    const target = index + offset;

    if (target < 0 || target >= props.checklists.length || busyKey.value) {
        return;
    }

    const ids = props.checklists.map((checklist) => checklist.id);
    [ids[index], ids[target]] = [ids[target], ids[index]];
    reorderRequest.ids = ids;
    busyKey.value = 'checklist:reorder';

    try {
        await reorderRequest.put(reorderChecklists(props.todoId).url);
        emit('refresh');
    } catch {
        toast.error(t('common.errors.generic'));
    } finally {
        busyKey.value = null;
    }
}

async function moveItem(
    checklist: Checklist,
    index: number,
    offset: -1 | 1,
): Promise<void> {
    const items = checklist.items ?? [];
    const target = index + offset;

    if (target < 0 || target >= items.length || busyKey.value) {
        return;
    }

    const ids = items.map((item) => item.id);
    [ids[index], ids[target]] = [ids[target], ids[index]];
    reorderRequest.ids = ids;
    busyKey.value = `item:reorder:${checklist.id}`;

    try {
        await reorderRequest.put(reorderItems([props.todoId, checklist]).url);
        emit('refresh');
    } catch {
        toast.error(t('common.errors.generic'));
    } finally {
        busyKey.value = null;
    }
}

async function confirmDelete(): Promise<void> {
    const target = deleteTarget.value;

    if (!target || busyKey.value) {
        return;
    }

    busyKey.value = `delete:${target.type}`;

    try {
        if (target.type === 'checklist') {
            await emptyRequest.delete(
                destroyChecklist([props.todoId, target.checklist]).url,
            );
        } else {
            await emptyRequest.delete(
                destroyItem([props.todoId, target.checklist, target.item]).url,
            );
        }

        deleteTarget.value = null;
        emit('refresh');
    } catch {
        toast.error(t('common.errors.generic'));
    } finally {
        busyKey.value = null;
    }
}
</script>

<template>
    <section class="rounded-[1.5rem] border border-border/80 bg-card p-5">
        <h2 class="text-base font-semibold">
            {{ t('tasks.detail.checklists') }}
        </h2>

        <div class="mt-4 space-y-4">
            <article
                v-for="(checklist, checklistIndex) in checklists"
                :key="checklist.id"
                class="rounded-2xl border border-border/70 bg-muted/20 p-4"
                :aria-busy="busyKey?.includes(checklist.id)"
            >
                <div class="flex items-center gap-2">
                    <Input
                        v-model="checklistDrafts[checklist.id]"
                        class="h-9 font-medium"
                        :aria-label="t('tasks.detail.checklist_name')"
                        :disabled="busyKey !== null"
                        @keyup.enter="renameChecklist(checklist)"
                    />
                    <Button
                        variant="ghost"
                        size="icon-sm"
                        :aria-label="t('common.actions.save')"
                        :disabled="busyKey !== null"
                        @click="renameChecklist(checklist)"
                    >
                        <Spinner
                            v-if="busyKey === `checklist:${checklist.id}`"
                        />
                        <Save v-else class="size-4" aria-hidden="true" />
                    </Button>
                    <Button
                        variant="ghost"
                        size="icon-sm"
                        :aria-label="t('tasks.detail.move_up')"
                        :disabled="busyKey !== null || checklistIndex === 0"
                        @click="moveChecklist(checklistIndex, -1)"
                    >
                        <ArrowUp class="size-4" aria-hidden="true" />
                    </Button>
                    <Button
                        variant="ghost"
                        size="icon-sm"
                        :aria-label="t('tasks.detail.move_down')"
                        :disabled="
                            busyKey !== null ||
                            checklistIndex === checklists.length - 1
                        "
                        @click="moveChecklist(checklistIndex, 1)"
                    >
                        <ArrowDown class="size-4" aria-hidden="true" />
                    </Button>
                    <Button
                        variant="ghost"
                        size="icon-sm"
                        class="text-muted-foreground hover:text-destructive"
                        :aria-label="t('tasks.detail.delete_checklist')"
                        :disabled="busyKey !== null"
                        @click="deleteTarget = { type: 'checklist', checklist }"
                    >
                        <Trash2 class="size-4" aria-hidden="true" />
                    </Button>
                </div>
                <InputError
                    v-if="busyKey === `checklist:${checklist.id}`"
                    :message="checklistRequest.errors.name"
                    class="mt-2"
                />

                <div class="mt-3 space-y-2">
                    <div
                        v-for="(item, itemIndex) in checklist.items ?? []"
                        :key="item.id"
                        class="grid grid-cols-[auto_minmax(0,1fr)_auto_auto_auto] items-center gap-1.5"
                    >
                        <Checkbox
                            :model-value="item.is_checked"
                            :aria-label="item.content"
                            :disabled="busyKey !== null"
                            @update:model-value="toggle(checklist, item)"
                        />
                        <Input
                            v-model="itemDrafts[item.id]"
                            class="h-8 text-sm"
                            :class="
                                item.is_checked ? 'line-through opacity-65' : ''
                            "
                            :aria-label="t('tasks.detail.item_content')"
                            :disabled="busyKey !== null"
                            @keyup.enter="renameItem(checklist, item)"
                        />
                        <Button
                            variant="ghost"
                            size="icon-sm"
                            :aria-label="t('common.actions.save')"
                            :disabled="busyKey !== null"
                            @click="renameItem(checklist, item)"
                        >
                            <Spinner v-if="busyKey === `item:${item.id}`" />
                            <Save v-else class="size-3.5" aria-hidden="true" />
                        </Button>
                        <div class="flex">
                            <Button
                                variant="ghost"
                                size="icon-sm"
                                :aria-label="t('tasks.detail.move_up')"
                                :disabled="busyKey !== null || itemIndex === 0"
                                @click="moveItem(checklist, itemIndex, -1)"
                            >
                                <ArrowUp class="size-3.5" aria-hidden="true" />
                            </Button>
                            <Button
                                variant="ghost"
                                size="icon-sm"
                                :aria-label="t('tasks.detail.move_down')"
                                :disabled="
                                    busyKey !== null ||
                                    itemIndex ===
                                        (checklist.items?.length ?? 0) - 1
                                "
                                @click="moveItem(checklist, itemIndex, 1)"
                            >
                                <ArrowDown
                                    class="size-3.5"
                                    aria-hidden="true"
                                />
                            </Button>
                        </div>
                        <Button
                            variant="ghost"
                            size="icon-sm"
                            class="text-muted-foreground hover:text-destructive"
                            :aria-label="t('tasks.detail.delete_item')"
                            :disabled="busyKey !== null"
                            @click="
                                deleteTarget = {
                                    type: 'item',
                                    checklist,
                                    item,
                                }
                            "
                        >
                            <Trash2 class="size-3.5" aria-hidden="true" />
                        </Button>
                    </div>
                    <div class="flex gap-2 pt-1">
                        <Input
                            v-model="newItemDrafts[checklist.id]"
                            :placeholder="t('tasks.detail.add_item')"
                            :disabled="busyKey !== null"
                            @keyup.enter="createItem(checklist)"
                        />
                        <Button
                            variant="outline"
                            :disabled="busyKey !== null"
                            @click="createItem(checklist)"
                        >
                            <Spinner
                                v-if="busyKey === `item:new:${checklist.id}`"
                            />
                            <Plus v-else class="size-4" aria-hidden="true" />
                            {{ t('common.actions.add') }}
                        </Button>
                    </div>
                    <InputError :message="itemRequest.errors.content" />
                </div>
            </article>

            <p
                v-if="checklists.length === 0"
                class="rounded-xl border border-dashed border-border/80 px-4 py-6 text-center text-sm text-muted-foreground"
            >
                {{ t('tasks.detail.no_checklists') }}
            </p>

            <div class="flex flex-col gap-2 sm:flex-row">
                <Input
                    v-model="newChecklistName"
                    :placeholder="t('tasks.detail.checklist_name')"
                    :disabled="busyKey !== null"
                    @keyup.enter="createChecklist"
                />
                <Button
                    variant="outline"
                    :disabled="busyKey !== null"
                    @click="createChecklist"
                >
                    <Spinner v-if="busyKey === 'checklist:new'" />
                    <Plus v-else class="size-4" aria-hidden="true" />
                    {{ t('common.actions.add') }}
                </Button>
            </div>
            <InputError :message="checklistRequest.errors.name" />
        </div>
    </section>

    <WorkspaceConfirmDialog
        :open="deleteTarget !== null"
        :title="t('tasks.detail.delete_child_title')"
        :description="t('tasks.detail.delete_child_description')"
        :confirm-label="t('common.actions.delete')"
        :cancel-label="t('common.actions.cancel')"
        :processing="busyKey?.startsWith('delete:') ?? false"
        @update:open="!$event && (deleteTarget = null)"
        @confirm="confirmDelete"
    />
</template>
