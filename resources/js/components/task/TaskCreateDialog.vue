<script setup lang="ts">
import { router } from '@inertiajs/vue3';
import { ref, watch } from 'vue';
import { useToast } from '@/composables/useToast';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Dialog, DialogContent, DialogHeader, DialogTitle, DialogFooter } from '@/components/ui/dialog';

const props = defineProps<{
    open: boolean;
    workspaceId: string;
    projectId?: string;
}>();
const emit = defineEmits<{ close: []; created: [] }>();
const toast = useToast();

const form = ref({
    title: '',
    description: '',
    priority: 'none',
    due_date: '',
    project_id: props.projectId ?? '',
});

watch(() => props.open, (open) => {
    if (open) {
        form.value = { title: '', description: '', priority: 'none', due_date: '', project_id: props.projectId ?? '' };
    }
});

function submit() {
    if (!form.value.title.trim()) return;

    router.post(route('todos.store', props.workspaceId), form.value, {
        preserveScroll: true,
        onSuccess: () => {
            toast.success('Task created');
            emit('created');
            emit('close');
        },
    });
}
</script>

<template>
    <Dialog :open="open" @update:open="emit('close')">
        <DialogContent class="sm:max-w-md">
            <DialogHeader>
                <DialogTitle>New Task</DialogTitle>
            </DialogHeader>
            <form @submit.prevent="submit" class="space-y-4">
                <div class="space-y-2">
                    <Label for="title">Title</Label>
                    <Input id="title" v-model="form.title" placeholder="What needs to be done?" autofocus />
                </div>
                <div class="space-y-2">
                    <Label for="description">Description (optional)</Label>
                    <Input id="description" v-model="form.description" placeholder="Add more details..." />
                </div>
                <div class="grid grid-cols-2 gap-4">
                    <div class="space-y-2">
                        <Label>Priority</Label>
                        <Select v-model="form.priority">
                            <SelectTrigger><SelectValue /></SelectTrigger>
                            <SelectContent>
                                <SelectItem value="none">None</SelectItem>
                                <SelectItem value="low">Low</SelectItem>
                                <SelectItem value="medium">Medium</SelectItem>
                                <SelectItem value="high">High</SelectItem>
                                <SelectItem value="urgent">Urgent</SelectItem>
                            </SelectContent>
                        </Select>
                    </div>
                    <div class="space-y-2">
                        <Label for="due_date">Due Date</Label>
                        <Input id="due_date" v-model="form.due_date" type="date" />
                    </div>
                </div>
                <DialogFooter>
                    <Button type="button" variant="outline" @click="emit('close')">Cancel</Button>
                    <Button type="submit">Create Task</Button>
                </DialogFooter>
            </form>
        </DialogContent>
    </Dialog>
</template>
