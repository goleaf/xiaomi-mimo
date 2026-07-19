<script setup lang="ts">
import { Head, router, useForm } from '@inertiajs/vue3';
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
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { useToast } from '@/composables/useToast';
import type { UserPreference } from '@/types/models';

const props = defineProps<{ preferences: UserPreference }>();
const toast = useToast();

const form = useForm({
    timezone: props.preferences.timezone,
    language: props.preferences.language,
    date_format: props.preferences.date_format,
    time_format: props.preferences.time_format,
    theme: props.preferences.theme,
    default_view: props.preferences.default_view,
    start_page: props.preferences.start_page,
});

function submit() {
    form.put('/settings/preferences', {
        onSuccess: () => toast.success('Preferences saved'),
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
    <Head title="Preferences" />
    <div class="space-y-6">
        <div>
            <h2 class="text-lg font-semibold">Preferences</h2>
            <p class="text-sm text-muted-foreground">
                Customize your experience
            </p>
        </div>
        <form @submit.prevent="submit" class="space-y-6">
            <Card>
                <CardHeader><CardTitle>Display</CardTitle></CardHeader>
                <CardContent class="space-y-4">
                    <div class="grid grid-cols-2 gap-4">
                        <div class="space-y-2">
                            <Label>Theme</Label>
                            <Select v-model="form.theme">
                                <SelectTrigger><SelectValue /></SelectTrigger>
                                <SelectContent>
                                    <SelectItem value="system"
                                        >System</SelectItem
                                    >
                                    <SelectItem value="light">Light</SelectItem>
                                    <SelectItem value="dark">Dark</SelectItem>
                                </SelectContent>
                            </Select>
                        </div>
                        <div class="space-y-2">
                            <Label>Default View</Label>
                            <Select v-model="form.default_view">
                                <SelectTrigger><SelectValue /></SelectTrigger>
                                <SelectContent>
                                    <SelectItem value="list">List</SelectItem>
                                    <SelectItem value="board">Board</SelectItem>
                                    <SelectItem value="calendar"
                                        >Calendar</SelectItem
                                    >
                                </SelectContent>
                            </Select>
                        </div>
                    </div>
                </CardContent>
            </Card>
            <Card>
                <CardHeader><CardTitle>Locale</CardTitle></CardHeader>
                <CardContent class="space-y-4">
                    <div class="grid grid-cols-2 gap-4">
                        <div class="space-y-2">
                            <Label>Timezone</Label>
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
                            <Label>Date Format</Label>
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
                <Button type="submit" :disabled="form.processing"
                    >Save Preferences</Button
                >
            </div>
        </form>
    </div>
</template>
