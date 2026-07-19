<script setup lang="ts">
import { Form, Head, setLayoutProps } from '@inertiajs/vue3';
import InputError from '@/components/InputError.vue';
import PasswordInput from '@/components/PasswordInput.vue';
import TextLink from '@/components/TextLink.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Spinner } from '@/components/ui/spinner';
import { useUi } from '@/composables/useUi';
import { login } from '@/routes';
import { store } from '@/routes/register';

defineProps<{
    passwordRules: string;
}>();

const { t } = useUi();

setLayoutProps({
    title: t('auth.register.heading'),
    description: t('auth.register.description'),
});
</script>

<template>
    <Head :title="t('auth.register.title')" />

    <Form
        v-bind="store.form()"
        :reset-on-success="['password', 'password_confirmation']"
        v-slot="{ errors, processing }"
        class="flex flex-col gap-6"
    >
        <div class="grid gap-6">
            <div class="grid gap-2">
                <Label for="name">{{ t('auth.common.name') }}</Label>
                <Input
                    id="name"
                    type="text"
                    required
                    autofocus
                    autocomplete="name"
                    name="name"
                    :placeholder="t('auth.common.name_placeholder')"
                />
                <InputError :message="errors.name" />
            </div>

            <div class="grid gap-2">
                <Label for="email">{{ t('auth.common.email') }}</Label>
                <Input
                    id="email"
                    type="email"
                    required
                    autocomplete="email"
                    name="email"
                    :placeholder="t('auth.common.email_placeholder')"
                />
                <InputError :message="errors.email" />
            </div>

            <div class="grid gap-2">
                <Label for="password">{{ t('auth.common.password') }}</Label>
                <PasswordInput
                    id="password"
                    required
                    autocomplete="new-password"
                    name="password"
                    :placeholder="t('auth.common.password')"
                    :passwordrules="passwordRules"
                />
                <InputError :message="errors.password" />
            </div>

            <div class="grid gap-2">
                <Label for="password_confirmation">{{
                    t('auth.common.confirm_password')
                }}</Label>
                <PasswordInput
                    id="password_confirmation"
                    required
                    autocomplete="new-password"
                    name="password_confirmation"
                    :placeholder="t('auth.common.confirm_password')"
                    :passwordrules="passwordRules"
                />
                <InputError :message="errors.password_confirmation" />
            </div>

            <Button
                type="submit"
                class="mt-2 w-full"
                :disabled="processing"
                data-test="register-user-button"
            >
                <Spinner v-if="processing" />
                {{ t('auth.register.submit') }}
            </Button>
        </div>

        <div class="text-center text-sm text-muted-foreground">
            {{ t('auth.register.has_account') }}
            <TextLink :href="login()" class="underline underline-offset-4">{{
                t('auth.register.login')
            }}</TextLink>
        </div>
    </Form>
</template>
