<script setup lang="ts">
import { Head, setLayoutProps, useForm } from '@inertiajs/vue3';
import AppearanceTabs from '@/components/AppearanceTabs.vue';
import { Button } from '@/components/ui/button';
import {
    Card,
    CardContent,
    CardHeader,
    CardTitle,
    CardDescription,
} from '@/components/ui/card';
import { Label } from '@/components/ui/label';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { useToast } from '@/composables/useToast';
import { useUi } from '@/composables/useUi';
import { update } from '@/routes/preferences';
import type { SettingsLayoutProps } from '@/types';
import type { UserPreference } from '@/types/models';

const props = defineProps<{ preferences: UserPreference }>();
const toast = useToast();
const { t } = useUi();

setLayoutProps<SettingsLayoutProps>({
    settingsEyebrow: t('account.menu.settings'),
    settingsTitle: t('settings.preferences.title'),
    settingsDescription: t('settings.preferences.description'),
});

const form = useForm({
    timezone: props.preferences.timezone,
    language: props.preferences.language,
    date_format: props.preferences.date_format,
    time_format: props.preferences.time_format,
    default_view: props.preferences.default_view,
    start_page: props.preferences.start_page,
});

function submit() {
    form.put(update.url(), {
        onSuccess: () => toast.success(t('settings.preferences.saved')),
    });
}

const timezones = [
    'UTC',
    'America/New_York',
    'America/Chicago',
    'America/Denver',
    'America/Los_Angeles',
    'Europe/London',
    'Europe/Berlin',
    'Europe/Moscow',
    'Asia/Tokyo',
    'Asia/Shanghai',
    'Australia/Sydney',
];
const dateFormats = ['Y-m-d', 'd/m/Y', 'm/d/Y', 'd.m.Y'];
</script>

<template>
    <Head :title="t('settings.preferences.title')" />
    <div class="space-y-6">
        <Card>
            <CardHeader>
                <CardTitle>{{
                    t('settings.preferences.appearance')
                }}</CardTitle>
                <CardDescription>
                    {{ t('settings.preferences.appearance_description') }}
                </CardDescription>
            </CardHeader>
            <CardContent>
                <AppearanceTabs />
            </CardContent>
        </Card>

        <form @submit.prevent="submit" class="space-y-6">
            <Card>
                <CardHeader
                    ><CardTitle>{{
                        t('settings.preferences.display')
                    }}</CardTitle></CardHeader
                >
                <CardContent class="space-y-4">
                    <div class="space-y-2">
                        <Label>{{
                            t('settings.preferences.default_view')
                        }}</Label>
                        <Select v-model="form.default_view">
                            <SelectTrigger><SelectValue /></SelectTrigger>
                            <SelectContent>
                                <SelectItem value="list">{{
                                    t('settings.preferences.list_view')
                                }}</SelectItem>
                                <SelectItem value="calendar">{{
                                    t('settings.preferences.calendar_view')
                                }}</SelectItem>
                            </SelectContent>
                        </Select>
                    </div>
                </CardContent>
            </Card>
            <Card>
                <CardHeader
                    ><CardTitle>{{
                        t('settings.preferences.locale')
                    }}</CardTitle></CardHeader
                >
                <CardContent class="space-y-4">
                    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                        <div class="space-y-2">
                            <Label>{{
                                t('settings.preferences.timezone')
                            }}</Label>
                            <Select v-model="form.timezone">
                                <SelectTrigger><SelectValue /></SelectTrigger>
                                <SelectContent>
                                    <SelectItem
                                        v-for="tz in timezones"
                                        :key="tz"
                                        :value="tz"
                                        >{{ tz }}</SelectItem
                                    >
                                </SelectContent>
                            </Select>
                        </div>
                        <div class="space-y-2">
                            <Label>{{
                                t('settings.preferences.date_format')
                            }}</Label>
                            <Select v-model="form.date_format">
                                <SelectTrigger><SelectValue /></SelectTrigger>
                                <SelectContent>
                                    <SelectItem
                                        v-for="f in dateFormats"
                                        :key="f"
                                        :value="f"
                                        >{{ f }}</SelectItem
                                    >
                                </SelectContent>
                            </Select>
                        </div>
                    </div>
                </CardContent>
            </Card>
            <div class="flex justify-end">
                <Button type="submit" :disabled="form.processing">{{
                    t('settings.preferences.save')
                }}</Button>
            </div>
        </form>
    </div>
</template>
