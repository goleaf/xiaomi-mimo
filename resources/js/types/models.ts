export interface User {
    id: string;
    name: string;
    email: string;
    avatar?: string;
    created_at: string;
}

export interface Workspace {
    id: string;
    name: string;
    slug: string;
    description: string | null;
    owner_id: string;
    owner?: User;
    members_count?: number;
    projects_count?: number;
    todos_count?: number;
    is_current?: boolean;
    permissions?: {
        view: boolean;
        update: boolean;
        duplicate: boolean;
        delete: boolean;
        manage_members: boolean;
        manage_task_configuration?: boolean;
        transfer_ownership?: boolean;
    };
    created_at: string;
    updated_at: string;
}

export type WorkspaceRole = 'owner' | 'admin' | 'member';
export type WorkspaceManagementSection =
    'overview' | 'members' | 'configuration' | 'danger';

export interface WorkspaceManagementMember {
    id: string;
    membership_id: string;
    name: string;
    email: string;
    avatar?: string | null;
    role: WorkspaceRole;
    is_current_user: boolean;
    permissions: {
        update: boolean;
        remove: boolean;
        transfer_ownership: boolean;
    };
}

export interface WorkspaceInvitation {
    id: string;
    email: string;
    role: Exclude<WorkspaceRole, 'owner'>;
    expires_at: string;
    is_expired: boolean;
    created_at: string;
    permissions: {
        resend: boolean;
        cancel: boolean;
    };
}

export interface WorkspaceMemberRouteUrls {
    invite: string;
    resendInvitation: (invitationId: string) => string;
    cancelInvitation: (invitationId: string) => string;
    updateMember: (userId: string) => string;
    removeMember: (userId: string) => string;
}

export interface WorkspaceMember {
    id: string;
    workspace_id: string;
    user_id: string;
    role: 'owner' | 'admin' | 'member';
    user?: User;
    created_at: string;
}

export interface Project {
    id: string;
    workspace_id: string;
    name: string;
    description: string | null;
    color: string;
    icon: string;
    is_archived: boolean;
    position: number;
    todos_count?: number;
    completed_count?: number;
    created_at: string;
    updated_at: string;
}

export type TodoStatus = string;
export type TodoPriority = string;

export interface TaskStatusDefinition {
    id: string;
    workspace_id: string;
    key: string;
    name: string;
    color: string;
    position: number;
    is_default: boolean;
    is_completed: boolean;
    is_completion_target: boolean;
    is_archived: boolean;
    todos_count?: number;
    permissions?: {
        update: boolean;
        delete: boolean;
        archive: boolean;
        set_default: boolean;
        set_completion_target: boolean;
    };
}

export interface TaskPriorityDefinition {
    id: string;
    workspace_id: string;
    key: string;
    name: string;
    color: string;
    position: number;
    is_default: boolean;
    is_archived: boolean;
    todos_count?: number;
    permissions?: {
        update: boolean;
        delete: boolean;
        archive: boolean;
        set_default: boolean;
    };
}

export interface TaskDefinitionCatalog {
    statuses: TaskStatusDefinition[];
    priorities: TaskPriorityDefinition[];
}

export interface Todo {
    id: string;
    project_id: string | null;
    workspace_id: string;
    assigned_to: string | null;
    parent_id: string | null;
    title: string;
    description: string | null;
    status: TodoStatus;
    status_id: string;
    status_definition?: TaskStatusDefinition;
    is_completed: boolean;
    priority: TodoPriority;
    priority_id: string;
    priority_definition?: TaskPriorityDefinition;
    due_date: string | null;
    start_date: string | null;
    estimated_time: number | null;
    spent_time: number | null;
    is_pinned: boolean;
    is_favorite: boolean;
    is_archived: boolean;
    is_recurring: boolean;
    recurring_rule: string | null;
    position: number;
    completed_at: string | null;
    project?: Project;
    assignee?: User;
    labels?: Label[];
    tags?: Tag[];
    available_labels?: Label[];
    available_tags?: Tag[];
    checklists?: Checklist[];
    comments?: Comment[];
    attachments?: Attachment[];
    reminders?: Reminder[];
    subtasks?: Todo[];
    comments_count?: number;
    checklists_count?: number;
    attachments_count?: number;
    subtasks_count?: number;
    created_at: string;
    updated_at: string;
}

export interface Checklist {
    id: string;
    todo_id: string;
    name: string;
    position: number;
    items?: ChecklistItem[];
    created_at: string;
}

export interface ChecklistItem {
    id: string;
    checklist_id: string;
    content: string;
    is_checked: boolean;
    position: number;
    created_at: string;
}

export interface Label {
    id: string;
    workspace_id: string;
    name: string;
    color: string;
    todos_count?: number;
    permissions?: {
        update: boolean;
        delete: boolean;
    };
    created_at: string;
    updated_at?: string;
}

export interface Tag {
    id: string;
    workspace_id: string;
    name: string;
    todos_count?: number;
    permissions?: {
        update: boolean;
        delete: boolean;
    };
    created_at: string;
    updated_at?: string;
}

export interface WorkspaceMetadataRouteUrls {
    storeLabel: string;
    updateLabel: (labelId: string) => string;
    deleteLabel: (labelId: string) => string;
    storeTag: string;
    updateTag: (tagId: string) => string;
    deleteTag: (tagId: string) => string;
    storeStatus: string;
    updateStatus: (statusId: string) => string;
    manageStatus: (statusId: string) => string;
    deleteStatus: (statusId: string) => string;
    reorderStatuses: string;
    storePriority: string;
    updatePriority: (priorityId: string) => string;
    managePriority: (priorityId: string) => string;
    deletePriority: (priorityId: string) => string;
    reorderPriorities: string;
}

export interface Comment {
    id: string;
    todo_id: string;
    user_id: string;
    body: string;
    user?: User;
    permissions?: {
        update: boolean;
        delete: boolean;
    };
    created_at: string;
    updated_at: string;
}

export interface Attachment {
    id: string;
    todo_id: string;
    user_id: string;
    filename: string;
    mime_type: string;
    size: number;
    download_url: string;
    user?: User;
    permissions?: {
        delete: boolean;
    };
    created_at: string;
}

export interface Reminder {
    id: string;
    todo_id: string;
    user_id: string;
    reminded_at: string;
    is_sent: boolean;
    type: 'email' | 'in_app' | 'browser';
    permissions?: {
        delete: boolean;
    };
    created_at: string;
}

export interface ActivityLog {
    id: string;
    user_id: string | null;
    workspace_id: string | null;
    subject_type: string;
    subject_id: string;
    event: string;
    properties: Record<string, unknown> | null;
    user?: User;
    created_at: string;
}

export interface UserPreference {
    id: string;
    user_id: string;
    timezone: string;
    language: 'en' | 'lt' | 'ru';
    date_format: 'Y-m-d' | 'd/m/Y' | 'm/d/Y' | 'd.m.Y';
    time_format: 'H:i' | 'h:i A';
    theme: 'system' | 'light' | 'dark';
    default_view: 'list' | 'board' | 'calendar';
    start_page: 'dashboard' | 'tasks' | 'projects' | 'calendar';
    notification_email: boolean;
    notification_browser: boolean;
    notification_in_app: boolean;
    created_at: string;
    updated_at: string;
}
