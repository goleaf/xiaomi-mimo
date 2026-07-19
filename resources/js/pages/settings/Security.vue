<script setup lang="ts">
import { Head, setLayoutProps, useForm } from '@inertiajs/vue3';
import { Shield, ShieldCheck } from '@lucide/vue';
import { ref } from 'vue';
import InputError from '@/components/InputError.vue';
import WorkspaceConfirmDialog from '@/components/shared/WorkspaceConfirmDialog.vue';
import { Alert, AlertDescription } from '@/components/ui/alert';
import { Button } from '@/components/ui/button';
import {
    Card,
    CardContent,
    CardHeader,
    CardTitle,
    CardDescription,
} from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Spinner } from '@/components/ui/spinner';
import { useToast } from '@/composables/useToast';
import { useUi } from '@/composables/useUi';
import { disable, enable } from '@/routes/two-factor';
import { update as updatePasswordRoute } from '@/routes/user-password';
import type { SettingsLayoutProps } from '@/types';

withDefaults(
    defineProps<{
        canManageTwoFactor: boolean;
        twoFactorEnabled?: boolean;
    }>(),
    {
        twoFactorEnabled: false,
    },
);

const toast = useToast();
const { t } = useUi();

setLayoutProps<SettingsLayoutProps>({
    settingsEyebrow: t('account.menu.settings'),
    settingsTitle: t('settings.security.title'),
    settingsDescription: t('settings.security.description'),
});

const passwordForm = useForm({
    current_password: '',
    password: '',
    password_confirmation: '',
});

const twoFactorForm = useForm({});
const showDisableDialog = ref(false);

function updatePassword() {
    passwordForm.put(updatePasswordRoute.url(), {
        onSuccess: () => {
            toast.success(t('settings.security.password_updated'));
            passwordForm.reset();
        },
    });
}

function enable2FA() {
    twoFactorForm.post(enable.url(), {
        onSuccess: () => toast.success(t('settings.security.enabled_2fa')),
    });
}

function disable2FA(): void {
    twoFactorForm.delete(disable.url(), {
        preserveScroll: true,
        onSuccess: () => {
            toast.success(t('settings.security.disabled_2fa'));
            showDisableDialog.value = false;
        },
    });
}
</script>

<template>
    <Head :title="t('settings.security.title')" />
    <div class="space-y-6">
        <Card>
            <CardHeader>
                <CardTitle>{{
                    t('settings.security.update_password')
                }}</CardTitle>
                <CardDescription>{{
                    t('settings.security.password_description')
                }}</CardDescription>
            </CardHeader>
            <CardContent>
                <form
                    @submit.prevent="updatePassword"
                    class="max-w-md space-y-4"
                >
                    <div class="space-y-2">
                        <Label for="current_password">{{
                            t('settings.security.current_password')
                        }}</Label>
                        <Input
                            id="current_password"
                            v-model="passwordForm.current_password"
                            type="password"
                            :aria-invalid="
                                Boolean(passwordForm.errors.current_password)
                            "
                            required
                        />
                        <InputError
                            :message="passwordForm.errors.current_password"
                        />
                    </div>
                    <div class="space-y-2">
                        <Label for="password">{{
                            t('settings.security.new_password')
                        }}</Label>
                        <Input
                            id="password"
                            v-model="passwordForm.password"
                            type="password"
                            :aria-invalid="
                                Boolean(passwordForm.errors.password)
                            "
                            required
                        />
                        <InputError :message="passwordForm.errors.password" />
                    </div>
                    <div class="space-y-2">
                        <Label for="password_confirmation">{{
                            t('settings.security.confirm_password')
                        }}</Label>
                        <Input
                            id="password_confirmation"
                            v-model="passwordForm.password_confirmation"
                            type="password"
                            :aria-invalid="
                                Boolean(
                                    passwordForm.errors.password_confirmation,
                                )
                            "
                            required
                        />
                        <InputError
                            :message="passwordForm.errors.password_confirmation"
                        />
                    </div>
                    <Button
                        type="submit"
                        size="lg"
                        :disabled="passwordForm.processing"
                    >
                        <Spinner v-if="passwordForm.processing" />
                        {{ t('settings.security.update_password') }}
                    </Button>
                </form>
            </CardContent>
        </Card>

        <Card v-if="canManageTwoFactor">
            <CardHeader>
                <div class="flex items-center gap-2">
                    <Shield class="h-5 w-5" />
                    <CardTitle>{{
                        t('settings.security.two_factor_title')
                    }}</CardTitle>
                </div>
                <CardDescription>{{
                    t('settings.security.two_factor_description')
                }}</CardDescription>
            </CardHeader>
            <CardContent>
                <div
                    v-if="twoFactorEnabled"
                    class="flex flex-col gap-3 sm:flex-row sm:items-center"
                >
                    <Alert variant="success" class="flex-1 py-3">
                        <ShieldCheck aria-hidden="true" />
                        <AlertDescription class="font-medium">
                            {{ t('settings.security.enabled_state') }}
                        </AlertDescription>
                    </Alert>
                    <Button
                        variant="destructive"
                        @click="showDisableDialog = true"
                        >{{ t('common.actions.disable') }}</Button
                    >
                </div>
                <div
                    v-else
                    class="flex flex-col gap-3 sm:flex-row sm:items-center"
                >
                    <Alert role="status" class="flex-1 py-3">
                        <Shield aria-hidden="true" />
                        <AlertDescription>
                            {{ t('settings.security.not_enabled_state') }}
                        </AlertDescription>
                    </Alert>
                    <Button
                        :disabled="twoFactorForm.processing"
                        @click="enable2FA"
                    >
                        <Spinner v-if="twoFactorForm.processing" />
                        {{ t('common.actions.enable') }}
                    </Button>
                </div>
            </CardContent>
        </Card>

        <WorkspaceConfirmDialog
            :open="showDisableDialog"
            :title="t('settings.security.disable_2fa_title')"
            :description="t('settings.security.disable_2fa_confirm')"
            :confirm-label="t('common.actions.disable')"
            :cancel-label="t('common.actions.cancel')"
            :processing="twoFactorForm.processing"
            @update:open="showDisableDialog = $event"
            @confirm="disable2FA"
        >
            <template #icon>
                <Shield class="size-5" aria-hidden="true" />
            </template>
        </WorkspaceConfirmDialog>
    </div>
</template>
