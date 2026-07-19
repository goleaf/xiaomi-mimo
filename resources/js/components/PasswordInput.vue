<script setup lang="ts">
import { Eye, EyeOff } from '@lucide/vue';
import { ref, useTemplateRef } from 'vue';
import type { HTMLAttributes } from 'vue';
import { Input } from '@/components/ui/input';
import { useUi } from '@/composables/useUi';
import { cn } from '@/lib/utils';

defineOptions({ inheritAttrs: false });

const props = defineProps<{
    class?: HTMLAttributes['class'];
}>();

const showPassword = ref(false);
const inputRef = useTemplateRef('inputRef');
const { t } = useUi();

defineExpose({
    $el: inputRef,
    focus: () => inputRef.value?.$el?.focus(),
});
</script>

<template>
    <div class="relative">
        <Input
            ref="inputRef"
            :type="showPassword ? 'text' : 'password'"
            :class="cn('pr-12', props.class)"
            v-bind="$attrs"
        />
        <button
            type="button"
            @click="showPassword = !showPassword"
            :class="
                cn(
                    'absolute inset-y-0 right-0 flex min-w-11 cursor-pointer items-center justify-center rounded-r-xl text-muted-foreground transition-colors hover:bg-orange-500/8 hover:text-orange-800 focus-visible:ring-[3px] focus-visible:ring-orange-500/25 focus-visible:outline-none dark:hover:bg-orange-500/10 dark:hover:text-orange-200',
                )
            "
            :aria-label="
                t(
                    showPassword
                        ? 'auth.common.hide_password'
                        : 'auth.common.show_password',
                )
            "
            :aria-pressed="showPassword"
        >
            <EyeOff v-if="showPassword" class="size-4" aria-hidden="true" />
            <Eye v-else class="size-4" aria-hidden="true" />
        </button>
    </div>
</template>
