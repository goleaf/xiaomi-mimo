<script setup lang="ts">
import { Form, Head, setLayoutProps } from '@inertiajs/vue3';
import { BadgeCheck } from '@lucide/vue';
import TextLink from '@/components/TextLink.vue';
import { Alert, AlertDescription } from '@/components/ui/alert';
import { Button } from '@/components/ui/button';
import { Spinner } from '@/components/ui/spinner';
import { useUi } from '@/composables/useUi';
import { logout } from '@/routes';
import { send } from '@/routes/verification';

const { t } = useUi();

setLayoutProps({
    title: t('auth.verify_email.title'),
    description: t('auth.verify_email.description'),
});

defineProps<{
    status?: string;
}>();
</script>

<template>
    <Head :title="t('auth.verify_email.title')" />

    <Alert
        v-if="status === 'verification-link-sent'"
        variant="success"
        class="mb-4 text-left"
    >
        <BadgeCheck aria-hidden="true" />
        <AlertDescription class="font-medium">
            {{ t('auth.verify_email.sent') }}
        </AlertDescription>
    </Alert>

    <Form
        v-bind="send.form()"
        class="space-y-6 text-center"
        disable-while-processing
        v-slot="{ processing }"
    >
        <Button
            :disabled="processing"
            variant="secondary"
            size="lg"
            class="w-full"
        >
            <Spinner v-if="processing" />
            {{ t('auth.verify_email.resend') }}
        </Button>

        <TextLink :href="logout()" as="button" class="mx-auto block text-sm">
            {{ t('auth.verify_email.logout') }}
        </TextLink>
    </Form>
</template>
