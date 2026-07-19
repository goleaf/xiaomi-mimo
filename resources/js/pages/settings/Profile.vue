<script setup lang="ts">
import { Head, useForm } from '@inertiajs/vue3';
import { useToast } from '@/composables/useToast';
import { Card, CardContent, CardHeader, CardTitle, CardDescription } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Separator } from '@/components/ui/separator';
import { User, Mail, Trash2 } from '@lucide/vue';

const props = defineProps<{
    user: { id: string; name: string; email: string; email_verified_at: string | null };
}>();

const toast = useToast();

const profileForm = useForm({
    name: props.user.name,
    email: props.user.email,
});

const passwordForm = useForm({
    current_password: '',
    password: '',
    password_confirmation: '',
});

const deleteForm = useForm({});

function updateProfile() {
    profileForm.patch(route('profile.update'), {
        onSuccess: () => toast.success('Profile updated'),
    });
}

function updatePassword() {
    passwordForm.put(route('user-password.update'), {
        onSuccess: () => { toast.success('Password updated'); passwordForm.reset(); },
    });
}

function deleteAccount() {
    if (confirm('Are you sure you want to delete your account? This action cannot be undone.')) {
        deleteForm.delete(route('profile.destroy'), {
            onSuccess: () => toast.success('Account deleted'),
        });
    }
}
</script>

<template>
    <Head title="Profile" />
    <div class="space-y-6">
        <div>
            <h2 class="text-lg font-semibold">Profile</h2>
            <p class="text-sm text-muted-foreground">Manage your account settings</p>
        </div>

        <!-- Profile Info -->
        <Card>
            <CardHeader>
                <CardTitle>Personal Information</CardTitle>
                <CardDescription>Update your name and email address</CardDescription>
            </CardHeader>
            <CardContent>
                <form @submit.prevent="updateProfile" class="space-y-4 max-w-md">
                    <div class="space-y-2">
                        <Label for="name">Name</Label>
                        <div class="flex items-center gap-2">
                            <User class="h-4 w-4 text-muted-foreground" />
                            <Input id="name" v-model="profileForm.name" required />
                        </div>
                        <p v-if="profileForm.errors.name" class="text-sm text-destructive">{{ profileForm.errors.name }}</p>
                    </div>
                    <div class="space-y-2">
                        <Label for="email">Email</Label>
                        <div class="flex items-center gap-2">
                            <Mail class="h-4 w-4 text-muted-foreground" />
                            <Input id="email" v-model="profileForm.email" type="email" required />
                        </div>
                        <p v-if="profileForm.errors.email" class="text-sm text-destructive">{{ profileForm.errors.email }}</p>
                        <p v-if="!user.email_verified_at" class="text-sm text-yellow-600">Your email is not verified.</p>
                    </div>
                    <Button type="submit" :disabled="profileForm.processing">Save Changes</Button>
                </form>
            </CardContent>
        </Card>

        <!-- Password -->
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

        <!-- Danger Zone -->
        <Card class="border-destructive">
            <CardHeader>
                <CardTitle class="text-destructive">Danger Zone</CardTitle>
                <CardDescription>Permanently delete your account</CardDescription>
            </CardHeader>
            <CardContent>
                <Button variant="destructive" @click="deleteAccount" :disabled="deleteForm.processing">
                    <Trash2 class="mr-2 h-4 w-4" />Delete Account
                </Button>
            </CardContent>
        </Card>
    </div>
</template>
