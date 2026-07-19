<script setup lang="ts">
import { Form, Head, setLayoutProps } from '@inertiajs/vue3';
import { BadgeCheck } from '@lucide/vue';
import InputError from '@/components/InputError.vue';
import PasskeyVerify from '@/components/PasskeyVerify.vue';
import PasswordInput from '@/components/PasswordInput.vue';
import TextLink from '@/components/TextLink.vue';
import { Alert, AlertDescription } from '@/components/ui/alert';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Spinner } from '@/components/ui/spinner';
import { useUi } from '@/composables/useUi';
import { register } from '@/routes';
import { store } from '@/routes/login';
import { request } from '@/routes/password';

const { t } = useUi();

setLayoutProps({
    title: t('auth.login.heading'),
    description: t('auth.login.description'),
});

defineProps<{
    status?: string;
    canResetPassword: boolean;
}>();
</script>

<template>
    <Head :title="t('auth.login.title')" />

    <Alert v-if="status" variant="success" class="mb-4">
        <BadgeCheck aria-hidden="true" />
        <AlertDescription class="font-medium">
            {{ status }}
        </AlertDescription>
    </Alert>

    <PasskeyVerify />

    <Form
        v-bind="store.form()"
        :reset-on-success="['password']"
        disable-while-processing
        v-slot="{ errors, processing }"
        class="flex flex-col gap-6"
    >
        <div class="grid gap-6">
            <div class="grid gap-2">
                <Label for="email">{{ t('auth.common.email') }}</Label>
                <Input
                    id="email"
                    type="email"
                    name="email"
                    required
                    autofocus
                    autocomplete="email"
                    :placeholder="t('auth.common.email_placeholder')"
                    :aria-invalid="Boolean(errors.email)"
                />
                <InputError :message="errors.email" />
            </div>

            <div class="grid gap-2">
                <div class="flex items-center justify-between">
                    <Label for="password">{{
                        t('auth.common.password')
                    }}</Label>
                    <TextLink
                        v-if="canResetPassword"
                        :href="request()"
                        class="text-sm"
                    >
                        {{ t('auth.login.forgot_password') }}
                    </TextLink>
                </div>
                <PasswordInput
                    id="password"
                    name="password"
                    required
                    autocomplete="current-password"
                    :placeholder="t('auth.common.password')"
                    :aria-invalid="Boolean(errors.password)"
                />
                <InputError :message="errors.password" />
            </div>

            <div class="flex items-center justify-between">
                <Label for="remember" class="flex items-center space-x-3">
                    <Checkbox id="remember" name="remember" />
                    <span>{{ t('auth.login.remember') }}</span>
                </Label>
            </div>

            <Button
                type="submit"
                size="lg"
                class="mt-4 w-full"
                :disabled="processing"
                data-test="login-button"
            >
                <Spinner v-if="processing" />
                {{ t('auth.login.submit') }}
            </Button>
        </div>

        <div class="text-center text-sm text-muted-foreground">
            {{ t('auth.login.no_account') }}
            <TextLink :href="register()">{{
                t('auth.login.sign_up')
            }}</TextLink>
        </div>
    </Form>
</template>
