<script setup lang="ts">
import { Form } from '@inertiajs/vue3';
import { TriangleAlert } from '@lucide/vue';
import { useTemplateRef } from 'vue';
import ProfileController from '@/actions/App/Http/Controllers/Settings/ProfileController';
import Heading from '@/components/Heading.vue';
import InputError from '@/components/InputError.vue';
import PasswordInput from '@/components/PasswordInput.vue';
import WorkspaceDialogContent from '@/components/shared/WorkspaceDialogContent.vue';
import { Alert, AlertDescription, AlertTitle } from '@/components/ui/alert';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogClose,
    DialogFooter,
    DialogTrigger,
} from '@/components/ui/dialog';
import { Label } from '@/components/ui/label';
import { Spinner } from '@/components/ui/spinner';

defineProps<{
    labels: {
        title: string;
        description: string;
        warning_title: string;
        warning_description: string;
        trigger: string;
        dialog_title: string;
        dialog_description: string;
        password: string;
        password_placeholder: string;
        cancel: string;
        confirm: string;
    };
}>();

const passwordInput = useTemplateRef('passwordInput');
</script>

<template>
    <div class="space-y-6">
        <Heading
            variant="small"
            :title="labels.title"
            :description="labels.description"
        />
        <Alert variant="destructive">
            <TriangleAlert aria-hidden="true" />
            <AlertTitle>{{ labels.warning_title }}</AlertTitle>
            <AlertDescription class="space-y-4">
                <p>{{ labels.warning_description }}</p>
                <Dialog>
                    <DialogTrigger as-child>
                        <Button
                            variant="destructive"
                            data-test="delete-user-button"
                            >{{ labels.trigger }}</Button
                        >
                    </DialogTrigger>
                    <WorkspaceDialogContent
                        :title="labels.dialog_title"
                        :description="labels.dialog_description"
                        :close-label="labels.cancel"
                        accent="red"
                    >
                        <Form
                            v-bind="ProfileController.destroy.form()"
                            reset-on-success
                            disable-while-processing
                            @error="() => passwordInput?.focus()"
                            :options="{
                                preserveScroll: true,
                            }"
                            class="space-y-6 px-6 py-6 sm:px-8"
                            v-slot="{ errors, processing, reset, clearErrors }"
                        >
                            <div class="grid gap-2">
                                <Label for="password" class="sr-only">{{
                                    labels.password
                                }}</Label>
                                <PasswordInput
                                    id="password"
                                    name="password"
                                    ref="passwordInput"
                                    :placeholder="labels.password_placeholder"
                                    :aria-invalid="Boolean(errors.password)"
                                />
                                <InputError :message="errors.password" />
                            </div>

                            <DialogFooter
                                class="gap-2 border-t border-border/70 pt-5 sm:gap-2"
                            >
                                <DialogClose as-child>
                                    <Button
                                        type="button"
                                        variant="outline"
                                        size="lg"
                                        @click="
                                            () => {
                                                clearErrors();
                                                reset();
                                            }
                                        "
                                    >
                                        {{ labels.cancel }}
                                    </Button>
                                </DialogClose>

                                <Button
                                    type="submit"
                                    variant="destructive"
                                    size="lg"
                                    :disabled="processing"
                                    data-test="confirm-delete-user-button"
                                >
                                    <Spinner v-if="processing" />
                                    {{ labels.confirm }}
                                </Button>
                            </DialogFooter>
                        </Form>
                    </WorkspaceDialogContent>
                </Dialog>
            </AlertDescription>
        </Alert>
    </div>
</template>
