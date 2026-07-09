<script setup lang="ts">
import { Link, router, usePage } from '@inertiajs/vue3';
import { computed, ref, watch, onMounted, onUnmounted } from 'vue';
import { Activity, BookOpen, Blocks, ChevronsUpDown, Circle, FileCode, FolderGit2, FolderOpen, Info, KeyRound, LayoutGrid, Layers, Server, Terminal } from '@lucide/vue';
import echo from '@/echo';
import GameServerController from '@/actions/App/Http/Controllers/GameServerController';
import GameServerProfileController from '@/actions/App/Http/Controllers/GameServerProfileController';
import ProfileConfigController from '@/actions/App/Http/Controllers/ProfileConfigController';
import ProfileModsController from '@/actions/App/Http/Controllers/ProfileModsController';
import ServerDetailController from '@/actions/App/Http/Controllers/ServerDetailController';
import ServerFilesController from '@/actions/App/Http/Controllers/ServerFilesController';
import ServerProcessController from '@/actions/App/Http/Controllers/ServerProcessController';
import ServerRconController from '@/actions/App/Http/Controllers/ServerRconController';
import AppLogo from '@/components/AppLogo.vue';
import NavFooter from '@/components/NavFooter.vue';
import NavMain from '@/components/NavMain.vue';
import NavUser from '@/components/NavUser.vue';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuLabel,
    DropdownMenuRadioGroup,
    DropdownMenuRadioItem,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import { Tooltip, TooltipContent, TooltipProvider, TooltipTrigger } from '@/components/ui/tooltip';
import {
    Sidebar,
    SidebarContent,
    SidebarFooter,
    SidebarGroup,
    SidebarHeader,
    SidebarMenu,
    SidebarMenuBadge,
    SidebarMenuButton,
    SidebarMenuItem,
} from '@/components/ui/sidebar';
import IntegrationController from '@/actions/App/Http/Controllers/IntegrationController';
import { dashboard } from '@/routes';
import { index as serversIndex } from '@/routes/servers';
import { index as profilesIndex } from '@/routes/profiles';
import type { NavItem } from '@/types';
import SidebarGroupLabel from './ui/sidebar/SidebarGroupLabel.vue';
import { useCurrentUrl } from '@/composables/useCurrentUrl.js';

const page = usePage();
const { isCurrentUrl } = useCurrentUrl();

interface ServerOption {
    id: string;
    name: string;
}

interface ProfileOption {
    id: string;
    name: string;
}

const servers = computed(() => page.props.servers as ServerOption[]);
const activeServer = computed(() => page.props.activeServer as ServerOption | null);
const profiles = computed(() => page.props.profiles as ProfileOption[]);
const activeProfile = computed(() => page.props.activeProfile as ProfileOption | null);
const counts = computed(() => page.props.counts as { servers: number; profiles: number; mods: number });

const mainNavItems = computed<NavItem[]>(() => [
    {
        title: 'Dashboard',
        href: dashboard(),
        icon: LayoutGrid,
    },

]);

const infrastructureNavItems = computed<NavItem[]>(() => [
    {
        title: 'Servers',
        href: serversIndex(),
        icon: Server,
        badge: counts.value.servers,
    },
    {
        title: 'Integrations',
        href: IntegrationController.index.url(),
        icon: KeyRound,
    },
]);

const serverNavItems = computed<NavItem[]>(() => {
    if (!activeServer.value) return [];
    return [
        {
            title: 'Details',
            href: ServerDetailController.active.url(),
            icon: Info,
        },
        {
            title: 'Files',
            href: ServerFilesController.active.url(),
            icon: FolderOpen,
        },
        {
            title: 'Processes',
            href: ServerProcessController.active.url(),
            icon: Activity,
        },
        {
            title: 'RCON',
            href: ServerRconController.active.url(),
            icon: Terminal,
        },
        {
            title: 'Profiles',
            href: profilesIndex(),
            icon: Layers,
            badge: counts.value.profiles,
        },
    ];
});

const profileNavItems = computed<NavItem[]>(() => {
    if (!activeProfile.value) return [];
    return [
        {
            title: 'Config',
            href: ProfileConfigController.active.url(),
            icon: FileCode,
        },
        {
            title: 'Mods',
            href: ProfileModsController.active.url(),
            icon: Blocks,
            badge: counts.value.mods,
        },
    ];
});

interface ServerStatus {
    running: boolean;
    installed: boolean;
    error: string | null;
}

const serverStatus = ref<ServerStatus | null>(null);
let subscribedServerId: string | null = null;

const statusColor = computed(() => {
    if (!serverStatus.value) return 'text-muted-foreground';
    if (serverStatus.value.error) return 'text-destructive';
    if (serverStatus.value.running) return 'text-green-600';
    return 'text-muted-foreground';
});

const statusLabel = computed(() => {
    if (!serverStatus.value) return 'Checking...';
    if (serverStatus.value.error) return 'Unreachable';
    if (serverStatus.value.running) return 'Running';
    return 'Stopped';
});

function subscribeToStatus(serverId: string) {
    if (subscribedServerId !== serverId) {
        unsubscribeFromStatus();
        serverStatus.value = null;
    }

    subscribedServerId = serverId;

    echo.join(`server.${serverId}`)
        .listen('.ServerStatusUpdated', (e: { running: boolean; installed: boolean; error: string | null }) => {
            serverStatus.value = { running: e.running, installed: e.installed, error: e.error };
        });
}

function unsubscribeFromStatus() {
    if (!subscribedServerId) return;
    echo.leave(`server.${subscribedServerId}`);
    subscribedServerId = null;
    serverStatus.value = null;
}

watch(() => activeServer.value?.id, (newId) => {
    if (newId) {
        subscribeToStatus(newId);
    } else {
        unsubscribeFromStatus();
    }
}, { immediate: true });

router.on('navigate', () => {
    if (activeServer.value?.id) {
        subscribeToStatus(activeServer.value.id);
    }
});

onUnmounted(() => {
    unsubscribeFromStatus();
});

const footerNavItems: NavItem[] = [
    // {
    //     title: 'Repository',
    //     href: 'https://github.com/laravel/vue-starter-kit',
    //     icon: FolderGit2,
    // },
    // {
    //     title: 'Documentation',
    //     href: 'https://laravel.com/docs/starter-kits#vue',
    //     icon: BookOpen,
    // },
];

function switchServer(value: unknown) {
    const serverId = String(value);
    if (activeServer.value?.id === serverId) return;
    router.patch(GameServerController.toggleActive.url({ server: serverId }), {}, {
        preserveScroll: true,
    });
}

function switchProfile(value: unknown) {
    const profileId = String(value);
    if (activeProfile.value?.id === profileId) return;
    router.patch(GameServerProfileController.toggleActive.url({ profile: profileId }), {}, {
        preserveScroll: true,
    });
}
</script>

<template>
    <Sidebar collapsible="icon" variant="inset">
        <SidebarHeader>
            <SidebarMenu>
                <SidebarMenuItem>
                    <SidebarMenuButton size="lg" as-child>
                        <Link :href="dashboard()">
                            <AppLogo />
                        </Link>
                    </SidebarMenuButton>
                </SidebarMenuItem>
            </SidebarMenu>
        </SidebarHeader>

        <SidebarContent>
            <SidebarGroup class="px-2 py-0">
                <SidebarGroupLabel>Platform</SidebarGroupLabel>
                <SidebarMenu>
                    <SidebarMenuItem v-for="item in mainNavItems" :key="item.title">
                        <SidebarMenuButton as-child :is-active="isCurrentUrl(item.href)" :tooltip="item.title">
                            <Link :href="item.href">
                                <component :is="item.icon" />
                                <span>{{ item.title }}</span>
                            </Link>
                        </SidebarMenuButton>
                        <SidebarMenuBadge v-if="item.badge !== undefined">{{ item.badge }}</SidebarMenuBadge>
                    </SidebarMenuItem>
                </SidebarMenu>
            </SidebarGroup>

            <SidebarGroup class="px-2 py-0">
                <SidebarGroupLabel>Infrastructure</SidebarGroupLabel>
                <SidebarMenu>
                    <SidebarMenuItem v-for="item in infrastructureNavItems" :key="item.title">
                        <SidebarMenuButton as-child :is-active="isCurrentUrl(item.href)" :tooltip="item.title">
                            <Link :href="item.href">
                                <component :is="item.icon" />
                                <span>{{ item.title }}</span>
                            </Link>
                        </SidebarMenuButton>
                        <SidebarMenuBadge v-if="item.badge !== undefined">{{ item.badge }}</SidebarMenuBadge>
                    </SidebarMenuItem>
                </SidebarMenu>
            </SidebarGroup>

            <SidebarGroup v-if="servers.length > 0" class="mt-4 px-2 py-0">
                <SidebarGroupLabel>Current Server</SidebarGroupLabel>
                <SidebarMenu>
                    <SidebarMenuItem>
                        <DropdownMenu>
                            <DropdownMenuTrigger as-child>
                                <SidebarMenuButton class="w-full justify-between">
                                    <span class="flex items-center gap-2">
                                        <Server class="h-4 w-4 shrink-0" />
                                        <span class="truncate">{{ activeServer?.name ?? 'No server' }}</span>
                                        <TooltipProvider v-if="activeServer">
                                            <Tooltip>
                                                <TooltipTrigger as-child>
                                                    <Circle class="h-2.5 w-2.5 shrink-0 fill-current" :class="statusColor" />
                                                </TooltipTrigger>
                                                <TooltipContent side="right">
                                                    {{ statusLabel }}
                                                </TooltipContent>
                                            </Tooltip>
                                        </TooltipProvider>
                                    </span>
                                    <ChevronsUpDown class="h-4 w-4 shrink-0 opacity-50" />
                                </SidebarMenuButton>
                            </DropdownMenuTrigger>
                            <DropdownMenuContent class="w-56" align="start" side="right">
                                <DropdownMenuLabel>Switch Server</DropdownMenuLabel>
                                <DropdownMenuRadioGroup :model-value="activeServer?.id ?? ''"
                                    @update:model-value="switchServer">
                                    <DropdownMenuRadioItem v-for="s in servers" :key="s.id" :value="s.id">
                                        {{ s.name }}
                                    </DropdownMenuRadioItem>
                                </DropdownMenuRadioGroup>
                            </DropdownMenuContent>
                        </DropdownMenu>
                    </SidebarMenuItem>
                </SidebarMenu>
                <SidebarMenu v-if="activeServer" class="pl-4">
                    <SidebarMenuItem v-for="item in serverNavItems" :key="item.title">
                        <SidebarMenuButton as-child :tooltip="item.title">
                            <Link :href="item.href">
                                <component :is="item.icon" />
                                <span>{{ item.title }}</span>
                            </Link>
                        </SidebarMenuButton>
                        <SidebarMenuBadge v-if="item.badge !== undefined">{{ item.badge }}</SidebarMenuBadge>
                    </SidebarMenuItem>
                </SidebarMenu>
            </SidebarGroup>

            <SidebarGroup v-if="profiles.length > 0" class="mt-2 px-2 py-0">
                <SidebarGroupLabel>Current Profile</SidebarGroupLabel>
                <SidebarMenu>
                    <SidebarMenuItem>
                        <DropdownMenu>
                            <DropdownMenuTrigger as-child>
                                <SidebarMenuButton class="w-full justify-between">
                                    <span class="flex items-center gap-2">
                                        <Layers class="h-4 w-4 shrink-0" />
                                        <span class="truncate">{{ activeProfile?.name ?? 'No profile' }}</span>
                                    </span>
                                    <ChevronsUpDown class="h-4 w-4 shrink-0 opacity-50" />
                                </SidebarMenuButton>
                            </DropdownMenuTrigger>
                            <DropdownMenuContent class="w-56" align="start" side="right">
                                <DropdownMenuLabel>Switch Profile</DropdownMenuLabel>
                                <DropdownMenuRadioGroup :model-value="activeProfile?.id ?? ''"
                                    @update:model-value="switchProfile">
                                    <DropdownMenuRadioItem v-for="p in profiles" :key="p.id" :value="p.id">
                                        {{ p.name }}
                                    </DropdownMenuRadioItem>
                                </DropdownMenuRadioGroup>
                            </DropdownMenuContent>
                        </DropdownMenu>
                    </SidebarMenuItem>
                </SidebarMenu>
                <SidebarMenu v-if="activeProfile" class="pl-4">
                    <SidebarMenuItem v-for="item in profileNavItems" :key="item.title">
                        <SidebarMenuButton as-child :tooltip="item.title">
                            <Link :href="item.href">
                                <component :is="item.icon" />
                                <span>{{ item.title }}</span>
                            </Link>
                        </SidebarMenuButton>
                        <SidebarMenuBadge v-if="item.badge !== undefined">{{ item.badge }}</SidebarMenuBadge>
                    </SidebarMenuItem>
                </SidebarMenu>
            </SidebarGroup>
        </SidebarContent>

        <SidebarFooter>
            <NavFooter :items="footerNavItems" />
            <NavUser />
        </SidebarFooter>
    </Sidebar>
    <slot />
</template>
