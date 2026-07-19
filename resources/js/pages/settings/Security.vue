<script setup lang="ts">
import { Head, useForm } from '@inertiajs/vue3';
import { Shield } from '@lucide/vue';
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
import { useToast } from '@/composables/useToast';
import { useUi } from '@/composables/useUi';
import { disable, enable } from '@/routes/two-factor';
import { update as updatePasswordRoute } from '@/routes/user-password';

defineProps<{
    user: { id: string; two_factor_enabled?: boolean };
}>();

const toast = useToast();
const { t } = useUi();

const passwordForm = useForm({
    current_password: '',
    password: '',
    password_confirmation: '',
});

const twoFactorForm = useForm({});

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

function disable2FA() {
    if (confirm(t('settings.security.disable_2fa_confirm'))) {
        twoFactorForm.delete(disable.url(), {
            onSuccess: () => toast.success(t('settings.security.disabled_2fa')),
        });
    }
}
</script>

<template>
    <Head :title="t('settings.security.title')" />
    <div class="space-y-6">
        <div>
            <h2 class="text-lg font-semibold">
                {{ t('settings.security.title') }}
            </h2>
            <p class="text-sm text-muted-foreground">
                {{ t('settings.security.description') }}
            </p>
        </div>

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
                            required
                        />
                        <p
                            v-if="passwordForm.errors.current_password"
                            class="text-sm text-destructive"
                        >
                            {{ passwordForm.errors.current_password }}
                        </p>
                    </div>
                    <div class="space-y-2">
                        <Label for="password">{{
                            t('settings.security.new_password')
                        }}</Label>
                        <Input
                            id="password"
                            v-model="passwordForm.password"
                            type="password"
                            required
                        />
                        <p
                            v-if="passwordForm.errors.password"
                            class="text-sm text-destructive"
                        >
                            {{ passwordForm.errors.password }}
                        </p>
                    </div>
                    <div class="space-y-2">
                        <Label for="password_confirmation">{{
                            t('settings.security.confirm_password')
                        }}</Label>
                        <Input
                            id="password_confirmation"
                            v-model="passwordForm.password_confirmation"
                            type="password"
                            required
                        />
                    </div>
                    <Button type="submit" :disabled="passwordForm.processing">{{
                        t('settings.security.update_password')
                    }}</Button>
                </form>
            </CardContent>
        </Card>

        <Card>
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
                    v-if="user.two_factor_enabled"
                    class="flex items-center gap-4"
                >
                    <p class="text-sm font-medium text-green-600">
                        {{ t('settings.security.enabled_state') }}
                    </p>
                    <Button
                        variant="destructive"
                        size="sm"
                        @click="disable2FA"
                        >{{ t('common.actions.disable') }}</Button
                    >
                </div>
                <div v-else class="flex items-center gap-4">
                    <p class="text-sm text-muted-foreground">
                        {{ t('settings.security.not_enabled_state') }}
                    </p>
                    <Button size="sm" @click="enable2FA">{{
                        t('common.actions.enable')
                    }}</Button>
                </div>
            </CardContent>
        </Card>
    </div>
</template>
