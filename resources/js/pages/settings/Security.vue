<script setup lang="ts">
import { Head, useForm } from '@inertiajs/vue3';
import { useToast } from '@/composables/useToast';
import { Card, CardContent, CardHeader, CardTitle, CardDescription } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Shield } from '@lucide/vue';

const props = defineProps<{
    user: { id: string; two_factor_enabled?: boolean };
}>();

const toast = useToast();

const passwordForm = useForm({
    current_password: '',
    password: '',
    password_confirmation: '',
});

const twoFactorForm = useForm({});

function updatePassword() {
    passwordForm.put(route('user-password.update'), {
        onSuccess: () => { toast.success('Password updated'); passwordForm.reset(); },
    });
}

function enable2FA() {
    twoFactorForm.post(route('two-factor.enable'), {
        onSuccess: () => toast.success('Two-factor authentication enabled'),
    });
}

function disable2FA() {
    if (confirm('Disable two-factor authentication?')) {
        twoFactorForm.delete(route('two-factor.disable'), {
            onSuccess: () => toast.success('Two-factor authentication disabled'),
        });
    }
}
</script>

<template>
    <Head title="Security" />
    <div class="space-y-6">
        <div>
            <h2 class="text-lg font-semibold">Security</h2>
            <p class="text-sm text-muted-foreground">Manage your account security</p>
        </div>

        <Card>
            <CardHeader>
                <CardTitle>Update Password</CardTitle>
                <CardDescription>Ensure your account is using a long, random password</CardDescription>
            </CardHeader>
            <CardContent>
                <form @submit.prevent="updatePassword" class="space-y-4 max-w-md">
                    <div class="space-y-2">
                        <Label for="current_password">Current Password</Label>
                        <Input id="current_password" v-model="passwordForm.current_password" type="password" required />
                        <p v-if="passwordForm.errors.current_password" class="text-sm text-destructive">{{ passwordForm.errors.current_password }}</p>
                    </div>
                    <div class="space-y-2">
                        <Label for="password">New Password</Label>
                        <Input id="password" v-model="passwordForm.password" type="password" required />
                        <p v-if="passwordForm.errors.password" class="text-sm text-destructive">{{ passwordForm.errors.password }}</p>
                    </div>
                    <div class="space-y-2">
                        <Label for="password_confirmation">Confirm Password</Label>
                        <Input id="password_confirmation" v-model="passwordForm.password_confirmation" type="password" required />
                    </div>
                    <Button type="submit" :disabled="passwordForm.processing">Update Password</Button>
                </form>
            </CardContent>
        </Card>

        <Card>
            <CardHeader>
                <div class="flex items-center gap-2">
                    <Shield class="h-5 w-5" />
                    <CardTitle>Two-Factor Authentication</CardTitle>
                </div>
                <CardDescription>Add additional security with 2FA</CardDescription>
            </CardHeader>
            <CardContent>
                <div v-if="user.two_factor_enabled" class="flex items-center gap-4">
                    <p class="text-sm text-green-600 font-medium">Two-factor authentication is enabled</p>
                    <Button variant="destructive" size="sm" @click="disable2FA">Disable</Button>
                </div>
                <div v-else class="flex items-center gap-4">
                    <p class="text-sm text-muted-foreground">Two-factor authentication is not enabled</p>
                    <Button size="sm" @click="enable2FA">Enable</Button>
                </div>
            </CardContent>
        </Card>
    </div>
</template>
