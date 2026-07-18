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
    created_at: string;
    updated_at: string;
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

export type TodoStatus = 'pending' | 'in_progress' | 'completed';
export type TodoPriority = 'none' | 'low' | 'medium' | 'high' | 'urgent';

export interface Todo {
    id: string;
    project_id: string | null;
    workspace_id: string;
    assigned_to: string | null;
    parent_id: string | null;
    title: string;
    description: string | null;
    status: TodoStatus;
    priority: TodoPriority;
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
    created_at: string;
}

export interface Tag {
    id: string;
    workspace_id: string;
    name: string;
    todos_count?: number;
    created_at: string;
}

export interface Comment {
    id: string;
    todo_id: string;
    user_id: string;
    body: string;
    user?: User;
    created_at: string;
    updated_at: string;
}

export interface Attachment {
    id: string;
    todo_id: string;
    user_id: string;
    filename: string;
    path: string;
    mime_type: string;
    size: number;
    url: string;
    user?: User;
    created_at: string;
}

export interface Reminder {
    id: string;
    todo_id: string;
    user_id: string;
    reminded_at: string;
    is_sent: boolean;
    type: 'email' | 'in_app' | 'browser';
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
    language: string;
    date_format: string;
    time_format: string;
    theme: 'system' | 'light' | 'dark';
    default_view: 'list' | 'board' | 'calendar';
    start_page: string;
    notification_email: boolean;
    notification_browser: boolean;
    notification_in_app: boolean;
    created_at: string;
    updated_at: string;
}
