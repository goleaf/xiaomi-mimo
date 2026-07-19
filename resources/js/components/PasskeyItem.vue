<script setup lang="ts">
import { KeyRound, Trash2 } from '@lucide/vue';
import { ref } from 'vue';
import WorkspaceDialogContent from '@/components/shared/WorkspaceDialogContent.vue';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogClose,
    DialogFooter,
    DialogTrigger,
} from '@/components/ui/dialog';
import { useUi } from '@/composables/useUi';
import type { Passkey } from '@/types/auth';

const props = defineProps<{
    passkey: Passkey;
}>();

const emit = defineEmits<{
    remove: [id: number, onError: () => void];
}>();

const isDeleting = ref(false);
const { t } = useUi();

const handleDelete = () => {
    isDeleting.value = true;
    emit('remove', props.passkey.id, () => {
        isDeleting.value = false;
    });
};
</script>

<template>
    <div class="flex items-center justify-between border-b p-4 last:border-b-0">
        <div class="flex items-center gap-4">
            <div
                class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-muted"
            >
                <KeyRound class="h-5 w-5 text-muted-foreground" />
            </div>
            <div class="space-y-1">
                <div class="flex items-center gap-2.5">
                    <p class="font-medium tracking-tight">{{ passkey.name }}</p>
                    <span
                        v-if="passkey.authenticator"
                        class="inline-flex items-center gap-1 rounded-md bg-muted px-2 py-0.5 text-[11px] font-medium tracking-wide text-muted-foreground uppercase ring-1 ring-border ring-inset"
                    >
                        {{ passkey.authenticator }}
                    </span>
                </div>
                <p class="text-sm text-muted-foreground">
                    {{ t('account.passkeys.added') }}
                    {{ passkey.created_at_diff }}
                    <template v-if="passkey.last_used_at_diff">
                        <span class="mx-1 text-muted-foreground/50">/</span>
                        {{ t('account.passkeys.last_used') }}
                        {{ passkey.last_used_at_diff }}
                    </template>
                </p>
            </div>
        </div>

        <Dialog>
            <DialogTrigger as-child>
                <Button
                    variant="ghost"
                    size="sm"
                    class="text-destructive hover:bg-destructive/10 hover:text-destructive"
                >
                    <Trash2 class="h-4 w-4" />
                    <span class="sr-only">{{
                        t('account.passkeys.remove')
                    }}</span>
                </Button>
            </DialogTrigger>

            <WorkspaceDialogContent
                :title="t('account.passkeys.remove_title')"
                :description="
                    t('account.passkeys.remove_description', {
                        name: passkey.name,
                    })
                "
                :close-label="t('common.actions.cancel')"
                accent="red"
            >
                <DialogFooter class="gap-2 px-6 py-6 sm:gap-2 sm:px-8">
                    <DialogClose as-child>
                        <Button
                            variant="outline"
                            class="min-h-11 cursor-pointer rounded-xl"
                        >
                            {{ t('common.actions.cancel') }}
                        </Button>
                    </DialogClose>
                    <Button
                        variant="destructive"
                        class="min-h-11 cursor-pointer rounded-xl"
                        :disabled="isDeleting"
                        @click="handleDelete"
                    >
                        {{
                            isDeleting
                                ? t('account.passkeys.removing')
                                : t('account.passkeys.remove_title')
                        }}
                    </Button>
                </DialogFooter>
            </WorkspaceDialogContent>
        </Dialog>
    </div>
</template>
