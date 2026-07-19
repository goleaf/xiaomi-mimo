import assert from 'node:assert/strict';
import test from 'node:test';
import { ref } from 'vue';
import { useTaskDetailState } from './useTaskDetailState.ts';

test('switching from task A to task B discards task A unsaved detail and checklist edits', () => {
    const selectedTask = ref({
        id: 'task-a',
        title: 'Task A',
        description: 'Task A description',
        status: 'pending',
        priority: 'high',
        due_date: '2026-07-20',
    });
    const state = useTaskDetailState(selectedTask);

    state.form.title = 'Unsaved task A title';
    state.form.description = 'Unsaved task A description';
    state.comment.value = 'Unsaved task A comment';
    state.checklistName.value = 'Unsaved task A checklist';
    state.checklistItemDrafts['checklist-a'] = 'Unsaved task A item';

    selectedTask.value = {
        id: 'task-b',
        title: 'Task B',
        description: 'Task B description',
        status: 'in_progress',
        priority: 'low',
        due_date: '2026-07-25',
    };

    assert.equal(state.form.title, 'Task B');
    assert.equal(state.form.description, 'Task B description');
    assert.equal(state.form.status, 'in_progress');
    assert.equal(state.form.priority, 'low');
    assert.equal(state.form.dueDate, '2026-07-25');
    assert.equal(state.comment.value, '');
    assert.equal(state.checklistName.value, '');
    assert.deepEqual({ ...state.checklistItemDrafts }, {});
});
