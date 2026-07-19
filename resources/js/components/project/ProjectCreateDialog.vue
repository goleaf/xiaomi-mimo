<script setup lang="ts">
import { router, useHttp } from '@inertiajs/vue3';
import {
    BookOpen,
    BriefcaseBusiness,
    Code2,
    Folder,
    Globe2,
    Palette,
    Rocket,
    Star,
} from '@lucide/vue';
import { computed, watch } from 'vue';
import type { Component } from 'vue';
import InputError from '@/components/InputError.vue';
import WorkspaceDialogContent from '@/components/shared/WorkspaceDialogContent.vue';
import { Button } from '@/components/ui/button';
import { Dialog, DialogFooter } from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Spinner } from '@/components/ui/spinner';
import { useToast } from '@/composables/useToast';
import { useWorkspaceUi } from '@/composables/useWorkspaceUi';
import { store as projectStore } from '@/routes/projects';
import type { Project } from '@/types/models';

interface ProjectForm {
    name: string;
    description: string;
    color: string;
    icon: string;
}

interface ProjectResponse {
    project: Project;
}

const props = defineProps<{
    open: boolean;
    workspaceId: string;
}>();
const emit = defineEmits<{ close: []; created: [] }>();
const toast = useToast();
const { copy } = useWorkspaceUi();
const form = useHttp<ProjectForm, ProjectResponse>({
    name: '',
    description: '',
    color: '#f97316',
    icon: 'folder',
});

const colors = [
    '#f97316',
    '#ef4444',
    '#eab308',
    '#14b8a6',
    '#0ea5e9',
    '#6366f1',
    '#a855f7',
    '#ec4899',
];
const iconOptions = computed<
    Array<{ value: string; label: string; icon: Component }>
>(() => [
    {
        value: 'folder',
        label: copy.value.projects.icon_folder,
        icon: Folder,
    },
    {
        value: 'briefcase',
        label: copy.value.projects.icon_briefcase,
        icon: BriefcaseBusiness,
    },
    {
        value: 'code',
        label: copy.value.projects.icon_code,
        icon: Code2,
    },
    {
        value: 'palette',
        label: copy.value.projects.icon_palette,
        icon: Palette,
    },
    {
        value: 'book',
        label: copy.value.projects.icon_book,
        icon: BookOpen,
    },
    {
        value: 'star',
        label: copy.value.projects.icon_star,
        icon: Star,
    },
    {
        value: 'rocket',
        label: copy.value.projects.icon_rocket,
        icon: Rocket,
    },
    {
        value: 'globe',
        label: copy.value.projects.icon_globe,
        icon: Globe2,
    },
]);

watch(
    () => props.open,
    (open) => {
        if (!open) {
            return;
        }

        form.resetAndClearErrors();
        form.color = '#f97316';
        form.icon = 'folder';
    },
);

async function submit(): Promise<void> {
    if (!form.name.trim()) {
        form.setError('name', copy.value.projects.name_required);

        return;
    }

    try {
        await form.submit(projectStore({ workspace: props.workspaceId }), {
            onSuccess: () => {
                toast.success(copy.value.projects.created);
                emit('created');
                emit('close');
                router.reload({ only: ['projects'] });
            },
            onHttpException: () => {
                toast.error(copy.value.projects.create_failed);
            },
            onNetworkError: () => {
                toast.error(copy.value.projects.create_failed);
            },
        });
    } catch {
        if (!form.hasErrors) {
            toast.error(copy.value.projects.create_failed);
        }
    }
}
</script>

<template>
    <Dialog :open="open" @update:open="emit('close')">
        <WorkspaceDialogContent
            :title="copy.projects.create_title"
            :description="copy.projects.create_description"
            :close-label="copy.projects.cancel"
            max-width-class="sm:max-w-xl"
        >
            <form class="space-y-6 px-6 py-6 sm:px-8" @submit.prevent="submit">
                <div class="space-y-2">
                    <Label for="project-name">{{ copy.projects.name }}</Label>
                    <Input
                        id="project-name"
                        v-model="form.name"
                        :placeholder="copy.projects.name_placeholder"
                        autocomplete="off"
                        autofocus
                        :disabled="form.processing"
                        :aria-invalid="Boolean(form.errors.name)"
                        @input="form.clearErrors('name')"
                    />
                    <InputError :message="form.errors.name" />
                </div>

                <div class="space-y-2">
                    <Label for="project-description">
                        {{ copy.projects.description_label }}
                    </Label>
                    <Input
                        id="project-description"
                        v-model="form.description"
                        :placeholder="copy.projects.description_placeholder"
                        :disabled="form.processing"
                        :aria-invalid="Boolean(form.errors.description)"
                        @input="form.clearErrors('description')"
                    />
                    <InputError :message="form.errors.description" />
                </div>

                <fieldset class="space-y-3">
                    <legend class="text-sm font-medium">
                        {{ copy.projects.color }}
                    </legend>
                    <div class="flex flex-wrap gap-2.5">
                        <button
                            v-for="color in colors"
                            :key="color"
                            type="button"
                            class="flex size-11 cursor-pointer items-center justify-center rounded-xl border transition-[background-color,border-color,box-shadow,transform] hover:-translate-y-0.5 focus-visible:ring-2 focus-visible:ring-orange-500 focus-visible:ring-offset-2 focus-visible:outline-none disabled:cursor-not-allowed disabled:opacity-60 motion-reduce:transform-none motion-reduce:transition-none"
                            :class="
                                form.color === color
                                    ? 'border-orange-500/50 bg-orange-500/[0.08] shadow-sm'
                                    : 'border-border/80 bg-background hover:border-orange-500/25 hover:bg-orange-500/[0.04]'
                            "
                            :disabled="form.processing"
                            :aria-label="color"
                            :aria-pressed="form.color === color"
                            @click="form.color = color"
                        >
                            <span
                                class="size-6 rounded-lg shadow-sm"
                                :style="{ backgroundColor: color }"
                                aria-hidden="true"
                            />
                        </button>
                    </div>
                    <InputError :message="form.errors.color" />
                </fieldset>

                <fieldset class="space-y-3">
                    <legend class="text-sm font-medium">
                        {{ copy.projects.icon }}
                    </legend>
                    <div class="grid grid-cols-4 gap-2 sm:grid-cols-8">
                        <button
                            v-for="option in iconOptions"
                            :key="option.value"
                            type="button"
                            :class="[
                                'flex min-h-12 cursor-pointer items-center justify-center rounded-xl border transition-[background-color,border-color,box-shadow,color] focus-visible:ring-2 focus-visible:ring-orange-500 focus-visible:ring-offset-2 focus-visible:outline-none disabled:cursor-not-allowed disabled:opacity-60 motion-reduce:transition-none',
                                form.icon === option.value
                                    ? 'border-orange-500/50 bg-orange-500/10 text-orange-700 shadow-sm dark:text-orange-300'
                                    : 'border-border/80 bg-background text-muted-foreground hover:border-orange-500/25 hover:bg-orange-500/[0.04] hover:text-foreground',
                            ]"
                            :disabled="form.processing"
                            :aria-label="option.label"
                            :aria-pressed="form.icon === option.value"
                            :title="option.label"
                            @click="form.icon = option.value"
                        >
                            <component
                                :is="option.icon"
                                class="size-4.5"
                                aria-hidden="true"
                            />
                        </button>
                    </div>
                    <InputError :message="form.errors.icon" />
                </fieldset>

                <DialogFooter
                    class="gap-2 border-t border-border/70 pt-5 sm:gap-2"
                >
                    <Button
                        type="button"
                        variant="outline"
                        size="lg"
                        :disabled="form.processing"
                        @click="emit('close')"
                    >
                        {{ copy.projects.cancel }}
                    </Button>
                    <Button type="submit" size="lg" :disabled="form.processing">
                        <Spinner v-if="form.processing" />
                        {{
                            form.processing
                                ? copy.projects.creating
                                : copy.projects.create
                        }}
                    </Button>
                </DialogFooter>
            </form>
        </WorkspaceDialogContent>
    </Dialog>
</template>
