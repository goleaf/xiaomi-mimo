<script setup lang="ts">
import { Head, useForm } from '@inertiajs/vue3';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import { Label } from '@/components/ui/label';
import { useToast } from '@/composables/useToast';
import { useUi } from '@/composables/useUi';
import { update } from '@/routes/preferences';
import type { UserPreference } from '@/types/models';

const props = defineProps<{ preferences: UserPreference }>();
const toast = useToast();
const { t } = useUi();

const form = useForm({
    notification_email: props.preferences.notification_email,
    notification_browser: props.preferences.notification_browser,
    notification_in_app: props.preferences.notification_in_app,
});

function submit() {
    form.put(update.url(), {
        onSuccess: () => toast.success(t('settings.notifications.saved')),
    });
}
</script>

<template>
    <Head :title="t('settings.notifications.title')" />
    <div class="space-y-6">
        <div>
            <h2 class="text-lg font-semibold">
                {{ t('settings.notifications.title') }}
            </h2>
            <p class="text-sm text-muted-foreground">
                {{ t('settings.notifications.description') }}
            </p>
        </div>
        <form @submit.prevent="submit">
            <Card>
                <CardContent class="space-y-4 pt-6">
                    <div
                        v-for="(field, key) in {
                            notification_email: t(
                                'settings.notifications.email',
                            ),
                            notification_browser: t(
                                'settings.notifications.browser',
                            ),
                            notification_in_app: t(
                                'settings.notifications.in_app',
                            ),
                        }"
                        :key="key"
                        class="flex items-center justify-between"
                    >
                        <Label>{{ field }}</Label>
                        <input
                            type="checkbox"
                            v-model="form[key as keyof typeof form]"
                            class="h-4 w-4 rounded border-gray-300"
                        />
                    </div>
                </CardContent>
            </Card>
            <div class="mt-4 flex justify-end">
                <Button type="submit" :disabled="form.processing">{{
                    t('common.actions.save')
                }}</Button>
            </div>
        </form>
    </div>
</template>
