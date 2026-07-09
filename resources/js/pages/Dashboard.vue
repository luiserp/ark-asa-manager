<script setup lang="ts">
import { Head, Link, Deferred, usePoll } from '@inertiajs/vue3';
import { computed } from 'vue';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Badge } from '@/components/ui/badge';
import { Server, Users, Layers, KeyRound, Cpu, MemoryStick, Clock, AlertTriangle, Circle, UserCheck } from '@lucide/vue';
import GameServerController from '@/actions/App/Http/Controllers/GameServerController';
import ServerDetailController from '@/actions/App/Http/Controllers/ServerDetailController';
import { dashboard } from '@/routes';
import { index as serversIndex } from '@/routes/servers';

defineOptions({
    layout: {
        breadcrumbs: [
            {
                title: 'Dashboard',
                href: dashboard(),
            },
        ],
    },
});

interface ServerInfo {
    id: string;
    name: string;
    host: string;
    is_current: boolean;
    profile_count: number;
    active_profile: string | null;
}

interface ServerMetric {
    connected: boolean;
    running: boolean;
    pid: number | null;
    uptime: string | null;
    cpu: number | null;
    memory_used: number | null;
    memory_total: number | null;
    players: number | null;
    max_players: number | null;
    error: string | null;
}

interface TeamStats {
    members: number;
    servers: number;
    profiles: number;
    integrations: number;
}

const props = defineProps<{
    servers: ServerInfo[];
    teamStats: TeamStats;
    serverMetrics: Record<string, ServerMetric> | null;
}>();

usePoll(30000, { only: ['serverMetrics'] });

const runningCount = computed(() => {
    if (!props.serverMetrics) return 0;
    return Object.values(props.serverMetrics).filter(m => m.running).length;
});

const unreachableCount = computed(() => {
    if (!props.serverMetrics) return 0;
    return Object.values(props.serverMetrics).filter(m => !m.connected).length;
});

function metric(serverId: string): ServerMetric | null {
    return props.serverMetrics?.[serverId] ?? null;
}

function statusColor(m: ServerMetric | null): string {
    if (!m) return 'bg-muted';
    if (!m.connected) return 'bg-red-500';
    if (m.running) return 'bg-green-500';
    return 'bg-yellow-500';
}

function statusLabel(m: ServerMetric | null): string {
    if (!m) return 'Loading...';
    if (!m.connected) return 'Unreachable';
    if (m.running) return 'Running';
    return 'Stopped';
}

function memoryPercent(m: ServerMetric | null): number {
    if (!m?.memory_used || !m?.memory_total) return 0;
    return Math.min(100, Math.round((m.memory_used / m.memory_total) * 100));
}

function formatMemory(mb: number | null): string {
    if (mb === null) return '—';
    if (mb >= 1024) return `${(mb / 1024).toFixed(1)} GB`;
    return `${mb} MB`;
}

function gaugeColor(percent: number): string {
    if (percent >= 90) return 'bg-red-500';
    if (percent >= 70) return 'bg-yellow-500';
    return 'bg-green-500';
}
</script>

<template>

    <Head title="Dashboard" />

    <div class="flex h-full flex-1 flex-col gap-6 overflow-x-auto p-4 md:p-6">
        <!-- Summary Cards -->
        <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
            <Card>
                <CardHeader class="flex flex-row items-center justify-between pb-2">
                    <CardTitle class="text-sm font-medium text-muted-foreground">Servers</CardTitle>
                    <Server class="h-4 w-4 text-muted-foreground" />
                </CardHeader>
                <CardContent>
                    <div class="text-2xl font-bold">{{ teamStats.servers }}</div>
                    <Deferred data="serverMetrics">
                        <template #fallback>
                            <p class="mt-1 text-xs text-muted-foreground">Checking status...</p>
                        </template>
                        <p class="mt-1 text-xs text-muted-foreground">
                            <span class="text-green-600 dark:text-green-400">{{ runningCount }} running</span>
                            <template v-if="unreachableCount > 0">
                                <span class="mx-1">·</span>
                                <span class="text-red-600 dark:text-red-400">{{ unreachableCount }} unreachable</span>
                            </template>
                        </p>
                    </Deferred>
                </CardContent>
            </Card>

            <Card>
                <CardHeader class="flex flex-row items-center justify-between pb-2">
                    <CardTitle class="text-sm font-medium text-muted-foreground">Team Members</CardTitle>
                    <Users class="h-4 w-4 text-muted-foreground" />
                </CardHeader>
                <CardContent>
                    <div class="text-2xl font-bold">{{ teamStats.members }}</div>
                    <p class="mt-1 text-xs text-muted-foreground">Active on this team</p>
                </CardContent>
            </Card>

            <Card>
                <CardHeader class="flex flex-row items-center justify-between pb-2">
                    <CardTitle class="text-sm font-medium text-muted-foreground">Profiles</CardTitle>
                    <Layers class="h-4 w-4 text-muted-foreground" />
                </CardHeader>
                <CardContent>
                    <div class="text-2xl font-bold">{{ teamStats.profiles }}</div>
                    <p class="mt-1 text-xs text-muted-foreground">Across all servers</p>
                </CardContent>
            </Card>

            <Card>
                <CardHeader class="flex flex-row items-center justify-between pb-2">
                    <CardTitle class="text-sm font-medium text-muted-foreground">Integrations</CardTitle>
                    <KeyRound class="h-4 w-4 text-muted-foreground" />
                </CardHeader>
                <CardContent>
                    <div class="text-2xl font-bold">{{ teamStats.integrations }}</div>
                    <p class="mt-1 text-xs text-muted-foreground">Connected services</p>
                </CardContent>
            </Card>
        </div>

        <!-- Empty State -->
        <div v-if="servers.length === 0"
            class="flex flex-1 flex-col items-center justify-center rounded-xl border border-dashed p-12 text-center">
            <Server class="mb-4 h-12 w-12 text-muted-foreground/50" />
            <h3 class="text-lg font-semibold">No servers yet</h3>
            <p class="mt-1 max-w-sm text-sm text-muted-foreground">
                Add a server to start managing your ARK: Survival Ascended dedicated servers.
            </p>
            <Link :href="serversIndex()"
                class="mt-4 inline-flex items-center gap-2 rounded-lg bg-primary px-4 py-2 text-sm font-medium text-primary-foreground hover:bg-primary/90">
                <Server class="h-4 w-4" />
                Add Server
            </Link>
        </div>

        <!-- Server Grid -->
        <div v-else class="grid gap-4 md:grid-cols-2 xl:grid-cols-3">
            <Card v-for="server in servers" :key="server.id" class="relative overflow-hidden">
                <!-- Status indicator strip -->
                <Deferred data="serverMetrics">
                    <template #fallback>
                        <div class="absolute inset-x-0 top-0 h-1 bg-muted animate-pulse" />
                    </template>
                    <div class="absolute inset-x-0 top-0 h-1" :class="statusColor(metric(server.id))" />
                </Deferred>

                <CardHeader class="pb-3">
                    <div class="flex items-center justify-between">
                        <Link :href="GameServerController.toggleActive.url({ server: server.id })" method="patch"
                            as="button" class="group flex items-center gap-2 text-left cursor-pointer" preserve-scroll>
                            <CardTitle class="text-base group-hover:underline">{{ server.name }}</CardTitle>
                        </Link>
                        <div class="flex items-center gap-2">
                            <Badge v-if="server.is_current" variant="secondary" class="text-xs">
                                Current
                            </Badge>
                            <Deferred data="serverMetrics">
                                <template #fallback>
                                    <Badge variant="outline" class="animate-pulse text-xs">...</Badge>
                                </template>
                                <Badge :variant="metric(server.id)?.running ? 'default' : 'outline'" :class="[
                                    metric(server.id)?.running ? 'bg-green-600 hover:bg-green-700' : '',
                                    !metric(server.id)?.connected ? 'border-red-300 text-red-600 dark:border-red-700 dark:text-red-400' : '',
                                ]" class="text-xs">
                                    <Circle class="mr-1 h-2 w-2 fill-current" />
                                    {{ statusLabel(metric(server.id)) }}
                                </Badge>
                            </Deferred>
                        </div>
                    </div>
                    <p class="text-xs text-muted-foreground">{{ server.host }}</p>
                </CardHeader>

                <CardContent class="space-y-4">
                    <!-- Server info -->
                    <div class="flex items-center gap-4 text-sm text-muted-foreground">
                        <span class="flex items-center gap-1">
                            <Layers class="h-3.5 w-3.5" />
                            {{ server.profile_count }} {{ server.profile_count === 1 ? 'profile' : 'profiles' }}
                        </span>
                        <span v-if="server.active_profile" class="truncate">
                            Active: <span class="font-medium text-foreground">{{ server.active_profile }}</span>
                        </span>
                    </div>

                    <!-- Metrics (deferred) -->
                    <Deferred data="serverMetrics">
                        <template #fallback>
                            <div class="space-y-3">
                                <div class="h-4 animate-pulse rounded bg-muted" />
                                <div class="h-4 animate-pulse rounded bg-muted" />
                                <div class="h-4 animate-pulse rounded bg-muted" />
                            </div>
                        </template>

                        <template v-if="metric(server.id)?.running">
                            <div class="space-y-3">
                                <!-- CPU -->
                                <div class="space-y-1">
                                    <div class="flex items-center justify-between text-xs">
                                        <span class="flex items-center gap-1 text-muted-foreground">
                                            <Cpu class="h-3 w-3" /> CPU
                                        </span>
                                        <span class="font-mono font-medium">
                                            {{ metric(server.id)?.cpu !== null ?
                                                `${metric(server.id)!.cpu!.toFixed(1)}%` : '—' }}
                                        </span>
                                    </div>
                                    <div class="h-2 overflow-hidden rounded-full bg-secondary">
                                        <div class="h-full rounded-full transition-all duration-500"
                                            :class="gaugeColor(metric(server.id)?.cpu ?? 0)"
                                            :style="{ width: `${Math.min(100, metric(server.id)?.cpu ?? 0)}%` }" />
                                    </div>
                                </div>

                                <!-- Memory -->
                                <div class="space-y-1">
                                    <div class="flex items-center justify-between text-xs">
                                        <span class="flex items-center gap-1 text-muted-foreground">
                                            <MemoryStick class="h-3 w-3" /> Memory
                                        </span>
                                        <span class="font-mono font-medium">
                                            {{ formatMemory(metric(server.id)?.memory_used ?? null) }}
                                            /
                                            {{ formatMemory(metric(server.id)?.memory_total ?? null) }}
                                        </span>
                                    </div>
                                    <div class="h-2 overflow-hidden rounded-full bg-secondary">
                                        <div class="h-full rounded-full transition-all duration-500"
                                            :class="gaugeColor(memoryPercent(metric(server.id)))"
                                            :style="{ width: `${memoryPercent(metric(server.id))}%` }" />
                                    </div>
                                </div>

                                <!-- Players & Uptime row -->
                                <div class="flex items-center justify-between text-xs text-muted-foreground">
                                    <span class="flex items-center gap-1">
                                        <UserCheck class="h-3 w-3" />
                                        <span class="font-medium text-foreground">{{ metric(server.id)?.players ?? 0
                                            }}</span>
                                        / {{ metric(server.id)?.max_players ?? '?' }} players
                                    </span>
                                    <span v-if="metric(server.id)?.uptime" class="flex items-center gap-1">
                                        <Clock class="h-3 w-3" />
                                        {{ metric(server.id)!.uptime }}
                                    </span>
                                </div>
                            </div>
                        </template>

                        <template v-else-if="metric(server.id)?.error">
                            <div
                                class="flex items-start gap-2 rounded-lg bg-red-50 p-3 text-xs text-red-700 dark:bg-red-950/50 dark:text-red-400">
                                <AlertTriangle class="mt-0.5 h-3.5 w-3.5 shrink-0" />
                                <span class="line-clamp-2">{{ metric(server.id)!.error }}</span>
                            </div>
                        </template>

                        <template v-else-if="metric(server.id)?.connected && !metric(server.id)?.running">
                            <div class="text-xs text-muted-foreground">
                                Server is stopped. Start it from the
                                <Link :href="ServerDetailController.index.url({ server: server.id })"
                                    class="font-medium text-foreground underline">
                                    server details
                                </Link>
                                page.
                            </div>
                        </template>
                    </Deferred>
                </CardContent>
            </Card>
        </div>
    </div>
</template>
