<script setup lang="ts">
import { Form, Head, setLayoutProps, useForm } from '@inertiajs/vue3';
import { BadgeCheck, Camera, CircleAlert, ImageUp, Trash2 } from '@lucide/vue';
import { computed, onBeforeUnmount, ref, useTemplateRef } from 'vue';
import {
    destroyAvatar,
    update,
    updateAvatar,
} from '@/actions/App/Http/Controllers/Settings/ProfileController';
import DeleteUser from '@/components/DeleteUser.vue';
import InputError from '@/components/InputError.vue';
import { Alert, AlertDescription, AlertTitle } from '@/components/ui/alert';
import { Avatar, AvatarFallback, AvatarImage } from '@/components/ui/avatar';
import { Button } from '@/components/ui/button';
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Spinner } from '@/components/ui/spinner';
import { useInitials } from '@/composables/useInitials';
import { useToast } from '@/composables/useToast';
import { useUi } from '@/composables/useUi';
import { send as sendVerification } from '@/routes/verification';
import type { SettingsLayoutProps } from '@/types';

type ProfileLabels = {
    title: string;
    description: string;
    navigation_label: string;
    avatar: {
        title: string;
        description: string;
        alt: string;
        choose: string;
        change: string;
        remove: string;
        upload: string;
        uploading: string;
        help: string;
        uploaded: string;
        removed: string;
    };
    personal: {
        title: string;
        description: string;
        name: string;
        email: string;
        save: string;
        saving: string;
        saved: string;
        email_unverified: string;
        resend_verification: string;
        verification_sent: string;
    };
    delete: {
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
};

const props = defineProps<{
    user: {
        id: string;
        name: string;
        email: string;
        email_verified_at: string | null;
        avatar_url: string | null;
    };
    canVerifyEmail: boolean;
    status?: string;
    labels: ProfileLabels;
}>();

const { t } = useUi();

setLayoutProps<SettingsLayoutProps>({
    navigationLabel: props.labels.navigation_label,
    settingsEyebrow: t('account.menu.settings'),
    settingsTitle: props.labels.title,
    settingsDescription: props.labels.description,
});

const toast = useToast();
const { getInitials } = useInitials();
const avatarInput = useTemplateRef<HTMLInputElement>('avatarInput');
const selectedAvatarUrl = ref<string | null>(null);

const profileForm = useForm({
    name: props.user.name,
    email: props.user.email,
});

const avatarForm = useForm<{ avatar: File | null }>({
    avatar: null,
});

const avatarRemoveForm = useForm({});

const avatarSource = computed(
    () => selectedAvatarUrl.value ?? props.user.avatar_url ?? undefined,
);
const avatarAlt = computed(() =>
    props.labels.avatar.alt.replace(':name', props.user.name),
);

function clearAvatarPreview(): void {
    if (selectedAvatarUrl.value) {
        URL.revokeObjectURL(selectedAvatarUrl.value);
        selectedAvatarUrl.value = null;
    }
}

function selectAvatar(event: Event): void {
    const input = event.target as HTMLInputElement;
    const file = input.files?.[0] ?? null;

    clearAvatarPreview();
    avatarForm.clearErrors();
    avatarForm.avatar = file;

    if (file) {
        selectedAvatarUrl.value = URL.createObjectURL(file);
    }
}

function updateProfile(): void {
    profileForm.submit(update(), {
        preserveScroll: true,
        onSuccess: () => {
            profileForm.defaults();
            toast.success(props.labels.personal.saved);
        },
    });
}

function uploadAvatar(): void {
    if (!avatarForm.avatar) {
        avatarInput.value?.click();

        return;
    }

    avatarForm.submit(updateAvatar(), {
        forceFormData: true,
        preserveScroll: true,
        onSuccess: () => {
            clearAvatarPreview();
            avatarForm.reset();

            if (avatarInput.value) {
                avatarInput.value.value = '';
            }

            toast.success(props.labels.avatar.uploaded);
        },
    });
}

function removeAvatar(): void {
    avatarRemoveForm.submit(destroyAvatar(), {
        preserveScroll: true,
        onSuccess: () => toast.success(props.labels.avatar.removed),
    });
}

onBeforeUnmount(clearAvatarPreview);
</script>

<template>
    <Head :title="labels.title" />

    <div class="max-w-4xl space-y-6">
        <Card>
            <CardHeader>
                <div class="flex items-start gap-3">
                    <div
                        class="flex size-10 items-center justify-center rounded-xl border border-orange-500/15 bg-orange-500/[0.08] text-orange-700 dark:text-orange-300"
                    >
                        <Camera class="size-5" aria-hidden="true" />
                    </div>
                    <div class="space-y-1">
                        <CardTitle>{{ labels.avatar.title }}</CardTitle>
                        <CardDescription>
                            {{ labels.avatar.description }}
                        </CardDescription>
                    </div>
                </div>
            </CardHeader>
            <CardContent>
                <form
                    class="grid gap-6 sm:grid-cols-[auto_minmax(0,1fr)] sm:items-center"
                    @submit.prevent="uploadAvatar"
                >
                    <Avatar class="size-24 border bg-muted shadow-sm">
                        <AvatarImage
                            v-if="avatarSource"
                            :src="avatarSource"
                            :alt="avatarAlt"
                            class="object-cover"
                        />
                        <AvatarFallback class="text-xl font-semibold">
                            {{ getInitials(user.name) }}
                        </AvatarFallback>
                    </Avatar>

                    <div class="min-w-0 space-y-3">
                        <input
                            ref="avatarInput"
                            class="sr-only"
                            type="file"
                            name="avatar"
                            accept=".jpg,.jpeg,.png,.webp,image/jpeg,image/png,image/webp"
                            :disabled="avatarForm.processing"
                            :aria-invalid="Boolean(avatarForm.errors.avatar)"
                            @change="selectAvatar"
                        />

                        <div class="flex flex-wrap gap-2">
                            <Button
                                type="button"
                                variant="outline"
                                :disabled="avatarForm.processing"
                                @click="avatarInput?.click()"
                            >
                                <ImageUp class="size-4" aria-hidden="true" />
                                {{
                                    user.avatar_url
                                        ? labels.avatar.change
                                        : labels.avatar.choose
                                }}
                            </Button>
                            <Button
                                v-if="avatarForm.avatar"
                                type="submit"
                                :disabled="avatarForm.processing"
                            >
                                <Spinner v-if="avatarForm.processing" />
                                {{
                                    avatarForm.processing
                                        ? labels.avatar.uploading
                                        : labels.avatar.upload
                                }}
                            </Button>
                            <Button
                                v-if="user.avatar_url && !avatarForm.avatar"
                                type="button"
                                variant="outline"
                                class="text-destructive hover:text-destructive"
                                :disabled="avatarRemoveForm.processing"
                                @click="removeAvatar"
                            >
                                <Spinner v-if="avatarRemoveForm.processing" />
                                <Trash2
                                    v-else
                                    class="size-4"
                                    aria-hidden="true"
                                />
                                {{ labels.avatar.remove }}
                            </Button>
                        </div>

                        <p
                            v-if="avatarForm.avatar"
                            class="truncate text-sm font-medium"
                        >
                            {{ avatarForm.avatar.name }}
                        </p>
                        <p class="text-sm text-muted-foreground">
                            {{ labels.avatar.help }}
                        </p>
                        <div
                            v-if="avatarForm.progress"
                            class="h-2 w-full max-w-sm overflow-hidden rounded-full bg-muted"
                            role="progressbar"
                            :aria-label="labels.avatar.uploading"
                            aria-valuemin="0"
                            aria-valuemax="100"
                            :aria-valuenow="avatarForm.progress.percentage"
                        >
                            <div
                                class="h-full rounded-full bg-orange-600 transition-[width] duration-200 motion-reduce:transition-none"
                                :style="{
                                    width: `${avatarForm.progress.percentage}%`,
                                }"
                            />
                        </div>
                        <InputError :message="avatarForm.errors.avatar" />
                    </div>
                </form>
            </CardContent>
        </Card>

        <Card>
            <CardHeader>
                <CardTitle>{{ labels.personal.title }}</CardTitle>
                <CardDescription>{{
                    labels.personal.description
                }}</CardDescription>
            </CardHeader>
            <CardContent class="space-y-5">
                <form
                    class="max-w-xl space-y-4"
                    @submit.prevent="updateProfile"
                >
                    <div class="space-y-2">
                        <Label for="name">{{ labels.personal.name }}</Label>
                        <Input
                            id="name"
                            v-model="profileForm.name"
                            name="name"
                            autocomplete="name"
                            :disabled="profileForm.processing"
                            :aria-invalid="Boolean(profileForm.errors.name)"
                            required
                        />
                        <InputError :message="profileForm.errors.name" />
                    </div>

                    <div class="space-y-2">
                        <Label for="email">{{ labels.personal.email }}</Label>
                        <Input
                            id="email"
                            v-model="profileForm.email"
                            name="email"
                            type="email"
                            autocomplete="email"
                            :disabled="profileForm.processing"
                            :aria-invalid="Boolean(profileForm.errors.email)"
                            required
                        />
                        <InputError :message="profileForm.errors.email" />
                    </div>

                    <div class="flex items-center gap-3">
                        <Button
                            type="submit"
                            size="lg"
                            :disabled="
                                profileForm.processing || !profileForm.isDirty
                            "
                        >
                            <Spinner v-if="profileForm.processing" />
                            {{
                                profileForm.processing
                                    ? labels.personal.saving
                                    : labels.personal.save
                            }}
                        </Button>
                        <Alert
                            v-if="profileForm.recentlySuccessful"
                            variant="success"
                            class="min-w-0 flex-1 py-2.5"
                        >
                            <BadgeCheck aria-hidden="true" />
                            <AlertDescription class="font-medium">
                                {{ labels.personal.saved }}
                            </AlertDescription>
                        </Alert>
                    </div>
                </form>

                <Alert
                    v-if="canVerifyEmail && !user.email_verified_at"
                    variant="warning"
                    class="max-w-xl"
                >
                    <CircleAlert aria-hidden="true" />
                    <AlertTitle>
                        {{ labels.personal.email_unverified }}
                    </AlertTitle>
                    <AlertDescription class="space-y-3">
                        <Form
                            v-bind="sendVerification.form()"
                            :options="{ preserveScroll: true }"
                            v-slot="{ processing }"
                        >
                            <Button
                                type="submit"
                                size="sm"
                                variant="outline"
                                :disabled="processing"
                            >
                                <Spinner v-if="processing" />
                                {{ labels.personal.resend_verification }}
                            </Button>
                        </Form>
                        <p
                            v-if="status === 'verification-link-sent'"
                            class="flex items-center gap-1.5 font-medium"
                            role="status"
                            aria-live="polite"
                        >
                            <BadgeCheck class="size-4" aria-hidden="true" />
                            {{ labels.personal.verification_sent }}
                        </p>
                    </AlertDescription>
                </Alert>
            </CardContent>
        </Card>

        <DeleteUser :labels="labels.delete" />
    </div>
</template>
