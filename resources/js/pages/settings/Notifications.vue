<script setup lang="ts">
import { Head, useForm } from '@inertiajs/vue3';
import { useToast } from '@/composables/useToast';
import type { UserPreference } from '@/types/models';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { Label } from '@/components/ui/label';

const props = defineProps<{ preferences: UserPreference }>();
const toast = useToast();

const form = useForm({
    notification_email: props.preferences.notification_email,
    notification_browser: props.preferences.notification_browser,
    notification_in_app: props.preferences.notification_in_app,
});

function submit() {
    form.put(route('preferences.update'), {
        onSuccess: () => toast.success('Notification settings saved'),
    });
}
</script>

<template>
    <Head title="Notification Settings" />
    <div class="space-y-6">
        <div>
            <h2 class="text-lg font-semibold">Notifications</h2>
            <p class="text-sm text-muted-foreground">Configure how you receive notifications</p>
        </div>
        <form @submit.prevent="submit">
            <Card>
                <CardContent class="space-y-4 pt-6">
                    <div v-for="(field, key) in { notification_email: 'Email notifications', notification_browser: 'Browser notifications', notification_in_app: 'In-app notifications' }" :key="key" class="flex items-center justify-between">
                        <Label>{{ field }}</Label>
                        <input type="checkbox" v-model="form[key as keyof typeof form]" class="h-4 w-4 rounded border-gray-300" />
                    </div>
                </CardContent>
            </Card>
            <div class="flex justify-end mt-4">
                <Button type="submit" :disabled="form.processing">Save</Button>
            </div>
        </form>
    </div>
</template>
