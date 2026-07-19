import { defineStore } from 'pinia';
import { ref, computed } from 'vue';
import type { Project } from '@/types/models';

export const useProjectStore = defineStore('project', () => {
    const projects = ref<Project[]>([]);
    const loading = ref(false);
    const currentProjectId = ref<string | null>(null);

    const currentProject = computed(() =>
        projects.value.find((p) => p.id === currentProjectId.value),
    );

    const activeProjects = computed(() =>
        projects.value.filter((p) => !p.is_archived),
    );

    const archivedProjects = computed(() =>
        projects.value.filter((p) => p.is_archived),
    );

    function setProjects(data: Project[]) {
        projects.value = data;
    }

    function addProject(project: Project) {
        projects.value.push(project);
    }

    function updateProject(id: string, data: Partial<Project>) {
        const index = projects.value.findIndex((p) => p.id === id);

        if (index !== -1) {
            projects.value[index] = { ...projects.value[index], ...data };
        }
    }

    function removeProject(id: string) {
        projects.value = projects.value.filter((p) => p.id !== id);

        if (currentProjectId.value === id) {
            currentProjectId.value = null;
        }
    }

    function setCurrentProject(id: string | null) {
        currentProjectId.value = id;
    }

    return {
        projects,
        loading,
        currentProjectId,
        currentProject,
        activeProjects,
        archivedProjects,
        setProjects,
        addProject,
        updateProject,
        removeProject,
        setCurrentProject,
    };
});
