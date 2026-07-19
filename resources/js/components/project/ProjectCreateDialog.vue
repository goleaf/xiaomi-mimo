<script setup lang="ts">
import { router } from '@inertiajs/vue3';
import { ref, watch } from 'vue';
import { useToast } from '@/composables/useToast';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Dialog, DialogContent, DialogHeader, DialogTitle, DialogFooter } from '@/components/ui/dialog';

const props = defineProps<{
    open: boolean;
    workspaceId: string;
}>();
const emit = defineEmits<{ close: []; created: [] }>();
const toast = useToast();

const form = ref({ name: '', description: '', color: '#6366f1', icon: 'folder' });

watch(() => props.open, (open) => {
    if (open) form.value = { name: '', description: '', color: '#6366f1', icon: 'folder' };
});

const colors = ['#6366f1', '#ec4899', '#14b8a6', '#f59e0b', '#ef4444', '#3b82f6', '#8b5cf6', '#06b6d4'];
const icons = ['folder', 'briefcase', 'code', 'palette', 'book', 'star', 'rocket', 'globe'];

function submit() {
    if (!form.value.name.trim()) return;
    router.post(route('projects.store', props.workspaceId), form.value, {
        preserveScroll: true,
        onSuccess: () => { toast.success('Project created'); emit('created'); emit('close'); },
    });
}
</script>

<template>
    <Dialog :open="open" @update:open="emit('close')">
        <DialogContent class="sm:max-w-md">
            <DialogHeader><DialogTitle>New Project</DialogTitle></DialogHeader>
            <form @submit.prevent="submit" class="space-y-4">
                <div class="space-y-2">
                    <Label for="name">Name</Label>
                    <Input id="name" v-model="form.name" placeholder="Project name" autofocus />
                </div>
                <div class="space-y-2">
                    <Label for="description">Description</Label>
                    <Input id="description" v-model="form.description" placeholder="Optional description" />
                </div>
                <div class="space-y-2">
                    <Label>Color</Label>
                    <div class="flex gap-2">
                        <button v-for="c in colors" :key="c" type="button"
                            class="h-8 w-8 rounded-full border-2 transition-transform hover:scale-110"
                            :style="{ backgroundColor: c, borderColor: form.color === c ? 'black' : 'transparent' }"
                            @click="form.color = c" />
                    </div>
                </div>
                <div class="space-y-2">
                    <Label>Icon</Label>
                    <div class="flex gap-2">
                        <button v-for="i in icons" :key="i" type="button"
                            :class="['px-3 py-1 rounded-md border text-xs', form.icon === i ? 'bg-primary text-primary-foreground' : 'bg-muted']"
                            @click="form.icon = i">{{ i }}</button>
                    </div>
                </div>
                <DialogFooter>
                    <Button type="button" variant="outline" @click="emit('close')">Cancel</Button>
                    <Button type="submit">Create Project</Button>
                </DialogFooter>
            </form>
        </DialogContent>
    </Dialog>
</template>
