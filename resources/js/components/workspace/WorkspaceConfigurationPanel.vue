<script setup lang="ts">
import { ArrowRight, ListChecks, Tags } from '@lucide/vue';
import { Button } from '@/components/ui/button';
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import { useUi } from '@/composables/useUi';

defineEmits<{ overview: [] }>();

const { t } = useUi();
</script>

<template>
    <section aria-labelledby="workspace-configuration-title">
        <Card class="relative overflow-hidden border-orange-500/15">
            <span
                class="absolute inset-y-0 left-0 w-1.5 bg-orange-500"
                aria-hidden="true"
            />
            <span
                class="absolute -right-12 -bottom-20 size-52 rounded-full border-[28px] border-orange-500/[0.04]"
                aria-hidden="true"
            />
            <CardHeader class="relative max-w-3xl px-7 pt-8 sm:px-10 sm:pt-10">
                <div class="flex gap-3">
                    <div
                        class="flex size-12 items-center justify-center rounded-2xl bg-orange-500/10 text-orange-700 dark:text-orange-300"
                    >
                        <ListChecks class="size-6" aria-hidden="true" />
                    </div>
                    <div
                        class="flex size-12 items-center justify-center rounded-2xl bg-sky-500/10 text-sky-700 dark:text-sky-300"
                    >
                        <Tags class="size-6" aria-hidden="true" />
                    </div>
                </div>
                <CardTitle
                    id="workspace-configuration-title"
                    class="pt-3 text-2xl tracking-tight"
                >
                    {{ t('workspaces.management.configuration.title') }}
                </CardTitle>
                <CardDescription
                    class="max-w-2xl text-sm leading-6 sm:text-base"
                >
                    {{ t('workspaces.management.configuration.description') }}
                </CardDescription>
            </CardHeader>
            <CardContent class="relative px-7 pb-8 sm:px-10 sm:pb-10">
                <div
                    class="grid gap-3 rounded-2xl border border-border/70 bg-muted/25 p-4 sm:grid-cols-2 sm:p-5"
                >
                    <div class="rounded-xl border bg-background p-4">
                        <p class="text-sm font-semibold">
                            {{
                                t(
                                    'workspaces.management.configuration.taxonomies',
                                )
                            }}
                        </p>
                        <p class="mt-1 text-sm leading-6 text-muted-foreground">
                            {{
                                t(
                                    'workspaces.management.configuration.taxonomies_description',
                                )
                            }}
                        </p>
                    </div>
                    <div class="rounded-xl border bg-background p-4">
                        <p class="text-sm font-semibold">
                            {{ t('workspaces.management.configuration.rules') }}
                        </p>
                        <p class="mt-1 text-sm leading-6 text-muted-foreground">
                            {{
                                t(
                                    'workspaces.management.configuration.rules_description',
                                )
                            }}
                        </p>
                    </div>
                </div>
                <Button
                    variant="outline"
                    size="lg"
                    class="mt-5"
                    @click="$emit('overview')"
                >
                    {{ t('workspaces.management.configuration.back') }}
                    <ArrowRight aria-hidden="true" />
                </Button>
            </CardContent>
        </Card>
    </section>
</template>
