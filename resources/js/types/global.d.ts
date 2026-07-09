import type { Auth } from '@/types/auth';
import type Pusher from 'pusher-js';

declare global {
    interface Window {
        Pusher: typeof Pusher;
    }
}

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
            sidebarOpen: boolean;
            currentTeam: { id: string; name: string; personal_team: boolean; is_owner: boolean } | null;
            teams: { id: string; name: string; personal_team: boolean }[];
            servers: { id: string; name: string }[];
            activeServer: { id: string; name: string } | null;
            profiles: { id: string; name: string }[];
            activeProfile: { id: string; name: string } | null;
            counts: { servers: number; profiles: number; mods: number };
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
