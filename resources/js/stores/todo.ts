import { defineStore } from 'pinia';
import { ref, computed } from 'vue';
import type { TodoFilters } from '@/types/api';
import type { Todo } from '@/types/models';

export const useTodoStore = defineStore('todo', () => {
    const todos = ref<Todo[]>([]);
    const loading = ref(false);
    const filters = ref<TodoFilters>({});
    const sort = ref<string>('position');
    const selectedIds = ref<Set<string>>(new Set());

    const filteredTodos = computed(() => {
        let result = [...todos.value];

        if (filters.value.search) {
            const search = filters.value.search.toLowerCase();
            result = result.filter(
                (t) =>
                    t.title.toLowerCase().includes(search) ||
                    t.description?.toLowerCase().includes(search),
            );
        }

        if (filters.value.project_id) {
            result = result.filter(
                (t) => t.project_id === filters.value.project_id,
            );
        }

        if (filters.value.status) {
            result = result.filter((t) => t.status === filters.value.status);
        }

        if (filters.value.priority) {
            result = result.filter(
                (t) => t.priority === filters.value.priority,
            );
        }

        if (filters.value.assigned_to) {
            result = result.filter(
                (t) => t.assigned_to === filters.value.assigned_to,
            );
        }

        if (filters.value.is_pinned !== undefined) {
            result = result.filter(
                (t) => t.is_pinned === filters.value.is_pinned,
            );
        }

        if (filters.value.is_favorite !== undefined) {
            result = result.filter(
                (t) => t.is_favorite === filters.value.is_favorite,
            );
        }

        if (filters.value.overdue) {
            const today = new Date().toISOString().split('T')[0];
            result = result.filter(
                (t) =>
                    t.due_date &&
                    t.due_date < today &&
                    t.status !== 'completed',
            );
        }

        return result;
    });

    const pendingTodos = computed(() =>
        filteredTodos.value.filter((t) => t.status === 'pending'),
    );
    const inProgressTodos = computed(() =>
        filteredTodos.value.filter((t) => t.status === 'in_progress'),
    );
    const completedTodos = computed(() =>
        filteredTodos.value.filter((t) => t.status === 'completed'),
    );
    const pinnedTodos = computed(() => todos.value.filter((t) => t.is_pinned));
    const favoriteTodos = computed(() =>
        todos.value.filter((t) => t.is_favorite),
    );
    const overdueTodos = computed(() => {
        const today = new Date().toISOString().split('T')[0];

        return todos.value.filter(
            (t) => t.due_date && t.due_date < today && t.status !== 'completed',
        );
    });

    function setTodos(data: Todo[]) {
        todos.value = data;
    }

    function addTodo(todo: Todo) {
        todos.value.unshift(todo);
    }

    function updateTodo(id: string, data: Partial<Todo>) {
        const index = todos.value.findIndex((t) => t.id === id);

        if (index !== -1) {
            todos.value[index] = { ...todos.value[index], ...data };
        }
    }

    function removeTodo(id: string) {
        todos.value = todos.value.filter((t) => t.id !== id);
        selectedIds.value.delete(id);
    }

    function toggleSelect(id: string) {
        if (selectedIds.value.has(id)) {
            selectedIds.value.delete(id);
        } else {
            selectedIds.value.add(id);
        }
    }

    function selectAll() {
        filteredTodos.value.forEach((t) => selectedIds.value.add(t.id));
    }

    function clearSelection() {
        selectedIds.value.clear();
    }

    function setFilters(newFilters: TodoFilters) {
        filters.value = { ...filters.value, ...newFilters };
    }

    function clearFilters() {
        filters.value = {};
    }

    return {
        todos,
        loading,
        filters,
        sort,
        selectedIds,
        filteredTodos,
        pendingTodos,
        inProgressTodos,
        completedTodos,
        pinnedTodos,
        favoriteTodos,
        overdueTodos,
        setTodos,
        addTodo,
        updateTodo,
        removeTodo,
        toggleSelect,
        selectAll,
        clearSelection,
        setFilters,
        clearFilters,
    };
});
