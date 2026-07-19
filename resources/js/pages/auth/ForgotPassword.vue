<script setup lang="ts">
import { Form, Head, setLayoutProps } from '@inertiajs/vue3';
import { BadgeCheck } from '@lucide/vue';
import InputError from '@/components/InputError.vue';
import TextLink from '@/components/TextLink.vue';
import { Alert, AlertDescription } from '@/components/ui/alert';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Spinner } from '@/components/ui/spinner';
import { useUi } from '@/composables/useUi';
import { login } from '@/routes';
import { email } from '@/routes/password';

const { t } = useUi();

setLayoutProps({
    title: t('auth.forgot_password.heading'),
    description: t('auth.forgot_password.description'),
});

defineProps<{
    status?: string;
}>();
</script>

<template>
    <Head :title="t('auth.forgot_password.title')" />

    <Alert v-if="status" variant="success" class="mb-4">
        <BadgeCheck aria-hidden="true" />
        <AlertDescription class="font-medium">
            {{ status }}
        </AlertDescription>
    </Alert>

    <div class="space-y-6">
        <Form v-bind="email.form()" v-slot="{ errors, processing }">
            <div class="grid gap-2">
                <Label for="email">{{ t('auth.common.email') }}</Label>
                <Input
                    id="email"
                    type="email"
                    name="email"
                    autocomplete="off"
                    autofocus
                    :placeholder="t('auth.common.email_placeholder')"
                />
                <InputError :message="errors.email" />
            </div>

            <div class="my-6 flex items-center justify-start">
                <Button
                    class="w-full"
                    :disabled="processing"
                    data-test="email-password-reset-link-button"
                >
                    <Spinner v-if="processing" />
                    {{ t('auth.forgot_password.submit') }}
                </Button>
            </div>
        </Form>

        <div class="space-x-1 text-center text-sm text-muted-foreground">
            <span>{{ t('auth.forgot_password.return_to') }}</span>
            <TextLink :href="login()">{{
                t('auth.forgot_password.login')
            }}</TextLink>
        </div>
    </div>
</template>
