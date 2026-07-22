import type { Auth } from '@/types/auth';
import type { UserPreference } from '@/types/models';
import type { SidebarNavigation } from '@/types/navigation';
import type { WorkspaceUiCopy } from '@/types/workspace-ui';

// Extend ImportMeta interface for Vite...
declare module 'vite/client' {
    interface ImportMetaEnv {
        readonly VITE_APP_NAME: string;
        [key: string]: string | boolean | undefined;
    }

    interface ImportMeta {
        readonly env: ImportMetaEnv;
        readonly glob: <T>(pattern: string) => Record<string, () => Promise<T>>;
    }
}

declare module '@inertiajs/core' {
    export interface InertiaConfig {
        sharedPageProps: {
            name: string;
            auth: Auth;
            capabilities: {
                manageDatabaseBackups: boolean;
            };
            navigation: SidebarNavigation;
            preferences: UserPreference | null;
            sidebarOpen: boolean;
            ui: Record<string, unknown>;
            workspaceUi: WorkspaceUiCopy;
            [key: string]: unknown;
        };
    }
}

declare module 'vue' {
    interface ComponentCustomProperties {
        $inertia: typeof Router;
        $page: Page;
        $headManager: ReturnType<typeof createHeadManager>;
    }
}
