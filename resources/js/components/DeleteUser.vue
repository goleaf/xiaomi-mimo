<script setup lang="ts">
import { Form } from '@inertiajs/vue3';
import { useTemplateRef } from 'vue';
import ProfileController from '@/actions/App/Http/Controllers/Settings/ProfileController';
import Heading from '@/components/Heading.vue';
import InputError from '@/components/InputError.vue';
import PasswordInput from '@/components/PasswordInput.vue';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogClose,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
    DialogTrigger,
} from '@/components/ui/dialog';
import { Label } from '@/components/ui/label';

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
        <div
            class="space-y-4 rounded-lg border border-red-100 bg-red-50 p-4 dark:border-red-200/10 dark:bg-red-700/10"
        >
            <div class="relative space-y-0.5 text-red-600 dark:text-red-100">
                <p class="font-medium">{{ labels.warning_title }}</p>
                <p class="text-sm">{{ labels.warning_description }}</p>
            </div>
            <Dialog>
                <DialogTrigger as-child>
                    <Button
                        variant="destructive"
                        data-test="delete-user-button"
                        >{{ labels.trigger }}</Button
                    >
                </DialogTrigger>
                <DialogContent>
                    <Form
                        v-bind="ProfileController.destroy.form()"
                        reset-on-success
                        @error="() => passwordInput?.focus()"
                        :options="{
                            preserveScroll: true,
                        }"
                        class="space-y-6"
                        v-slot="{ errors, processing, reset, clearErrors }"
                    >
                        <DialogHeader class="space-y-3">
                            <DialogTitle>{{ labels.dialog_title }}</DialogTitle>
                            <DialogDescription>
                                {{ labels.dialog_description }}
                            </DialogDescription>
                        </DialogHeader>

                        <div class="grid gap-2">
                            <Label for="password" class="sr-only">{{
                                labels.password
                            }}</Label>
                            <PasswordInput
                                id="password"
                                name="password"
                                ref="passwordInput"
                                :placeholder="labels.password_placeholder"
                            />
                            <InputError :message="errors.password" />
                        </div>

                        <DialogFooter class="gap-2">
                            <DialogClose as-child>
                                <Button
                                    type="button"
                                    variant="secondary"
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
                                :disabled="processing"
                                data-test="confirm-delete-user-button"
                            >
                                {{ labels.confirm }}
                            </Button>
                        </DialogFooter>
                    </Form>
                </DialogContent>
            </Dialog>
        </div>
    </div>
</template>
