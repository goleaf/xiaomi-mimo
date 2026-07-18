export interface PaginatedResponse<T> {
    data: T[];
    current_page: number;
    last_page: number;
    per_page: number;
    total: number;
}

export interface ApiResponse<T> {
    data?: T;
    message?: string;
}

export interface TodoFilters {
    search?: string;
    project_id?: string;
    status?: string;
    priority?: string;
    assigned_to?: string;
    label_id?: string;
    tag_id?: string;
    is_pinned?: boolean;
    is_favorite?: boolean;
    overdue?: boolean;
    completed_today?: boolean;
    due_date_from?: string;
    due_date_to?: string;
    sort?: string;
    direction?: 'asc' | 'desc';
}
